<?php

namespace App\Http\Controllers;

use App\Classes\Module;
use App\Models\User;
use App\Models\Setting;
use App\Models\AddOn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;

class TranslationController extends Controller
{
    private function getAllowedLanguages(): array
    {
        $languagesData = json_decode(File::get(resource_path('lang/language.json')), true);
        return collect($languagesData)->pluck('code')->toArray();
    }

    /**
     * Identifies a module in app-owned storage. Prefer the Composer slug; fall
     * back to the module name for a module that predates package_name.
     */
    private function moduleKey(AddOn $addon): string
    {
        return $addon->package_name ?: $addon->module;
    }

    /**
     * The strings a module ships. Resolved through Module::path() rather than
     * assembled here, so the core never hardcodes a module's internal layout.
     * Read-only on purpose: a Composer package is replaced wholesale on every
     * update, so anything written here is lost at the next install.
     */
    private function moduleLangFile(AddOn $addon, string $locale): string
    {
        return Module::path($addon->module, $addon->package_name) . "/src/Resources/lang/{$locale}.json";
    }

    /**
     * Where edits to a module's strings live. The app owns these, the module
     * owns its defaults, so a customised label survives a module upgrade
     * instead of being overwritten by it.
     */
    private function moduleOverrideFile(string $moduleKey, string $locale): string
    {
        return resource_path("lang/modules/{$moduleKey}/{$locale}.json");
    }

    private function readJson(string $path): array
    {
        return File::exists($path) ? (json_decode(File::get($path), true) ?? []) : [];
    }

    /**
     * The strings a module defines for a locale. Falls back to its English file
     * when it ships nothing for this locale, so adding a language shows real
     * labels rather than blanks, and picks up the real translations for free
     * whenever the module later ships them.
     */
    private function moduleShipped(AddOn $addon, string $locale): array
    {
        return $this->readJson($this->moduleLangFile($addon, $locale))
            ?: $this->readJson($this->moduleLangFile($addon, 'en'));
    }

    /**
     * A module's effective strings: what it ships, with the app's overrides on
     * top. Only correct for a single module read; a full payload has to apply
     * every module's overrides in a second pass (see getTranslations).
     */
    private function moduleTranslations(AddOn $addon, string $locale): array
    {
        return array_merge(
            $this->moduleShipped($addon, $locale),
            $this->readJson($this->moduleOverrideFile($this->moduleKey($addon), $locale))
        );
    }

    public function getTranslations($locale)
    {
        $locale = strtolower($locale);
        if (!in_array($locale, array_map('strtolower', $this->getAllowedLanguages()))) {
            $locale = 'en';
        }

        $path = resource_path("lang/{$locale}.json");

        if (!File::exists($path)) {
            $path = resource_path("lang/en.json");
            $locale = 'en';
        }

        // $layoutDirection = in_array($locale, ['ar', 'he']) ? 'rtl' : 'ltr';
        $layoutDirection = in_array($locale, ['ar', 'he']) ? 'rtl' : 'ltr';

        $translations = json_decode(File::get($path), true) ?? [];

        // Three layers, weakest first: core strings, then what enabled modules
        // ship, then the edits an admin saved. Disabled modules are left out so a
        // company never downloads strings for a module it cannot reach.
        //
        // Overrides are applied in a second pass rather than per module: modules
        // share plenty of key names, so folding a module's override in alongside
        // its own defaults let the next module's default overwrite it again.
        $enabled = AddOn::where('is_enable', true)->get(['module', 'package_name']);

        foreach ($enabled as $addon) {
            $translations = array_merge($translations, $this->moduleShipped($addon, $locale));
        }

        foreach ($enabled as $addon) {
            $translations = array_merge($translations, $this->readJson($this->moduleOverrideFile($this->moduleKey($addon), $locale)));
        }

        if (empty($translations)) {
            return response()->json(['error' => __('Invalid translation file')], 500);
        }

        app()->setLocale($locale);

        return response()->json([
            'translations' => $translations,
            'layoutDirection' => $layoutDirection,
            'locale' => $locale
        ]);
    }

    public function manage(Request $request)
    {
        if (auth()->user()->can('manage-languages')) {
            $currentLanguage = $request->get('lang', 'en');
            $search = $request->get('search', '');
            $page = $request->get('page', 1);
            $perPage = 50;

            if (!in_array($currentLanguage, $this->getAllowedLanguages())) {
                $currentLanguage = 'en';
            }

            // Load current language translations
            $path = resource_path("lang/{$currentLanguage}.json");
            $allTranslations = [];

            if (File::exists($path)) {
                $allTranslations = json_decode(File::get($path), true) ?? [];
            }

            // Filter translations based on search
            $filteredTranslations = $allTranslations;
            if ($search) {
                $filteredTranslations = array_filter($allTranslations, function($value, $key) use ($search) {
                    return stripos($key, $search) !== false || stripos($value, $search) !== false;
                }, ARRAY_FILTER_USE_BOTH);
            }

            // Paginate translations
            $total = count($filteredTranslations);
            $lastPage = $total > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            $paginatedTranslations = array_slice($filteredTranslations, $offset, $perPage, true);

            $paginationData = [
                'current_page' => (int)$page,
                'data' => $paginatedTranslations,
                'first_page_url' => $request->url() . '?' . http_build_query(array_merge($request->except('page'), ['page' => 1])),
                'from' => $total > 0 ? $offset + 1 : 0,
                'last_page' => $lastPage,
                'last_page_url' => $request->url() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $lastPage])),
                'next_page_url' => $page < $lastPage ? $request->url() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $page + 1])) : null,
                'path' => $request->url(),
                'per_page' => $perPage,
                'prev_page_url' => $page > 1 ? $request->url() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $page - 1])) : null,
                'to' => min($offset + $perPage, $total),
                'total' => $total
            ];

            // Get enabled packages list only
            $enabledPackages = AddOn::where('is_enable', true)->get(['package_name', 'name']);

            // Get available languages with flags
            $languagesData = json_decode(File::get(resource_path('lang/language.json')), true);
            $availableLanguages = collect($languagesData)
                ->map(function ($lang) {
                    return [
                        'code' => $lang['code'],
                        'name' => $lang['name'],
                        'countryCode' => $lang['countryCode'],
                        'enabled' => $lang['enabled'] ?? true,
                        'flag' => $this->getCountryFlag($lang['countryCode'])
                    ];
                })->values()->toArray();

            // Get current language status
            $currentLangData = collect($languagesData)->firstWhere('code', $currentLanguage);
            $isCurrentLanguageEnabled = $currentLangData['enabled'] ?? true;

            return Inertia::render('languages/manage', [
                'currentLanguage' => $currentLanguage,
                'translations' => $paginationData,
                'enabledPackages' => $enabledPackages,
                'availableLanguages' => $availableLanguages,
                'isCurrentLanguageEnabled' => $isCurrentLanguageEnabled,
                'filters' => ['search' => $search]
            ]);
        } else {
            return redirect()->route('dashboard')->with('error', __('Permission denied'));
        }
    }

    public function updateTranslations(Request $request, $locale)
    {
        if (auth()->user()->can('manage-languages')) {
            if (!in_array($locale, $this->getAllowedLanguages())) {
                return response()->json(['error' => __('Invalid language')], 400);
            }

            $request->validate([
                'translations' => 'required|array'
            ], [
                'translations.required' => __('Translations are required.'),
                'translations.array' => __('Translations must be a valid array.'),
            ]);

            $translations = $request->input('translations');
            $path = resource_path("lang/{$locale}.json");

            try {
                // Save new translations
                $currentTranslations = json_decode(File::get($path), true) ?? [];
                $translations = array_merge($currentTranslations, $translations);
                File::put($path, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                return response()->json(['success' => true, 'message' => __('The translation details are updated successfully.')]);
            } catch (\Exception $e) {
                return response()->json(['error' => __('Failed to save translations: :error', ['error' => $e->getMessage()])], 500);
            }
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function getPackageTranslations(Request $request, $locale, $packageName)
    {
        if (auth()->user()->can('manage-languages')) {
            $search = $request->get('search', '');
            $page = $request->get('page', 1);
            $perPage = 50;

            if (!in_array($locale, $this->getAllowedLanguages())) {
                return response()->json(['error' => __('Invalid language')], 400);
            }

            $package = AddOn::where('package_name', $packageName)->where('is_enable', true)->first();
            if (!$package) {
                return response()->json(['error' => __('Package not found or disabled')], 404);
            }

            $translations = $this->moduleTranslations($package, $locale);

            // Filter translations based on search
            $filteredTranslations = $translations;
            if ($search) {
                $filteredTranslations = array_filter($translations, function($value, $key) use ($search) {
                    return stripos($key, $search) !== false || stripos($value, $search) !== false;
                }, ARRAY_FILTER_USE_BOTH);
            }

            // Paginate translations
            $total = count($filteredTranslations);
            $lastPage = $total > 0 ? ceil($total / $perPage) : 1;
            $offset = ($page - 1) * $perPage;
            $paginatedTranslations = array_slice($filteredTranslations, $offset, $perPage, true);

            $paginationData = [
                'current_page' => (int)$page,
                'data' => $paginatedTranslations,
                'first_page_url' => $request->url() . '?' . http_build_query(array_merge($request->except('page'), ['page' => 1])),
                'from' => $total > 0 ? $offset + 1 : 0,
                'last_page' => $lastPage,
                'last_page_url' => $request->url() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $lastPage])),
                'next_page_url' => $page < $lastPage ? $request->url() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $page + 1])) : null,
                'path' => $request->url(),
                'per_page' => $perPage,
                'prev_page_url' => $page > 1 ? $request->url() . '?' . http_build_query(array_merge($request->except('page'), ['page' => $page - 1])) : null,
                'to' => min($offset + $perPage, $total),
                'total' => $total
            ];
            return response()->json(['translations' => $paginationData]);
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function updatePackageTranslations(Request $request, $locale, $packageName)
    {
        if (auth()->user()->can('manage-languages')) {
            if (!in_array($locale, $this->getAllowedLanguages())) {
                return response()->json(['error' => __('Invalid language')], 400);
            }

            $package = AddOn::where('package_name', $packageName)->where('is_enable', true)->first();
            if (!$package) {
                return response()->json(['error' => __('Package not found or disabled')], 404);
            }

            $request->validate([
                'translations' => 'required|array'
            ], [
                'translations.required' => __('Package translations are required.'),
                'translations.array' => __('Package translations must be a valid array.'),
            ]);

            $translations = $request->input('translations');

            // Saved as an app-owned override, never back into the module. The
            // module directory is Composer-managed and is replaced on every
            // update, so edits written there disappear without a trace.
            $overrideFile = $this->moduleOverrideFile($this->moduleKey($package), $locale);

            try {
                File::ensureDirectoryExists(dirname($overrideFile));
                $translations = array_merge($this->readJson($overrideFile), $translations);
                File::put($overrideFile, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return response()->json(['success' => true, 'message' => __('The package translations updated successfully.')]);
            } catch (\Exception $e) {
                return response()->json(['error' => __('Failed to save package translations: :error', ['error' => $e->getMessage()])], 500);
            }
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function createLanguage(Request $request)
    {
        if (auth()->user()->can('manage-languages')) {
            $request->validate([
                // regex, because the code is interpolated into a file path below.
                'code' => 'required|string|max:10|regex:/^[A-Za-z0-9_-]+$/',
                'name' => 'required|string|max:255',
                'countryCode' => 'required|string|size:2'
            ], [
                'code.required' => __('Language code is required.'),
                'code.string' => __('Language code must be a valid string.'),
                'code.max' => __('Language code must not exceed 10 characters.'),
                'code.regex' => __('Language code may only contain letters, numbers, dashes and underscores.'),
                'name.required' => __('Language name is required.'),
                'name.string' => __('Language name must be a valid string.'),
                'name.max' => __('Language name must not exceed 255 characters.'),
                'countryCode.required' => __('Country code is required.'),
                'countryCode.string' => __('Country code must be a valid string.'),
                'countryCode.size' => __('Country code must be exactly 2 characters.'),
            ]);

            try {
                // Check if language already exists in language.json
                $languagesFile = resource_path('lang/language.json');
                $languages = json_decode(File::get($languagesFile), true);

                $existingLanguage = collect($languages)->firstWhere('code', $request->code);
                if ($existingLanguage) {
                    return response()->json(['error' => __('The language code already exists')], 422);
                }
                $languages[] = [
                    'code' => $request->code,
                    'name' => $request->name,
                    'countryCode' => strtoupper($request->countryCode)
                ];
                File::put($languagesFile, json_encode($languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                // Copy en.json to new language
                $enFile = resource_path('lang/en.json');
                $newLangFile = resource_path("lang/{$request->code}.json");
                if (File::exists($enFile)) {
                    File::copy($enFile, $newLangFile);
                }

                // No module files to copy: moduleTranslations() already falls back
                // to each module's English strings for a locale it does not ship,
                // and picks up the real ones as soon as the module ships them.

                return response()->json(['success' => true, 'message' => __('The language has been created successfully.')]);
            } catch (\Exception $e) {
                return response()->json(['error' => __('Failed to create language: :error', ['error' => $e->getMessage()])], 500);
            }
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function deleteLanguage($languageCode)
    {
        if (auth()->user()->can('manage-languages')) {
            if ($languageCode === 'en') {
                return response()->json(['error' => __('Cannot delete English language')], 422);
            }

            // The code reaches File::delete() as part of a path, so it has to be a
            // language we know about rather than whatever the URL carried.
            if (!in_array($languageCode, $this->getAllowedLanguages())) {
                return response()->json(['error' => __('Invalid language')], 400);
            }

            try {
                // Remove from language.json
                $languagesFile = resource_path('lang/language.json');
                $languages = json_decode(File::get($languagesFile), true);
                $languages = array_filter($languages, fn($lang) => $lang['code'] !== $languageCode);
                File::put($languagesFile, json_encode(array_values($languages), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                // Delete main language file
                $mainLangFile = resource_path("lang/{$languageCode}.json");
                if (File::exists($mainLangFile)) {
                    File::delete($mainLangFile);
                }

                // Drop this locale's module overrides. Every module at once, not
                // just the enabled ones, so disabling a module before deleting a
                // language does not strand its override file.
                foreach (File::glob(resource_path("lang/modules/*/{$languageCode}.json")) as $overrideFile) {
                    File::delete($overrideFile);
                }

                return response()->json(['success' => true, 'message' => __('The language has been deleted.')]);
            } catch (\Exception $e) {
                return response()->json(['error' => __('Failed to delete language: :error', ['error' => $e->getMessage()])], 500);
            }
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function toggleLanguageStatus($languageCode)
    {
        if (auth()->user()->can('manage-languages')) {
            if ($languageCode === 'en') {
                return response()->json(['error' => __('Cannot disable English language')], 422);
            }

            try {
                $languagesFile = resource_path('lang/language.json');
                $languages = json_decode(File::get($languagesFile), true);

                foreach ($languages as &$language) {
                    if ($language['code'] === $languageCode) {
                        $language['enabled'] = !($language['enabled'] ?? true);
                        break;
                    }
                }

                File::put($languagesFile, json_encode($languages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return response()->json(['success' => true, 'message' => __('The language status updated successfully.')]);
            } catch (\Exception $e) {
                return response()->json(['error' => __('Failed to update language status: :error', ['error' => $e->getMessage()])], 500);
            }
        } else {
            return response()->json(['error' => __('Permission denied')], 403);
        }
    }

    public function changeLanguage(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => __('Unauthorized')], 401);
        }

        $request->validate([
            'lang' => 'required|string'
        ]);

        $locale = strtolower($request->input('lang'));
        
        if (config('app.is_demo')) {
            $cookie = Cookie::make('language', $locale, 60 * 24 * 30); // 1 month
            return redirect()->back()->withCookie($cookie);
        }

        $user = auth()->user();
        $user->update(['lang' => $locale]);

        // In Standard Mode, also update the layout direction in settings
        $layoutDirection = in_array($locale, ['ar', 'he']) ? 'rtl' : 'ltr';
        Setting::updateOrCreate(
            ['key' => 'layoutDirection', 'created_by' => $user->id],
            ['value' => $layoutDirection]
        );

        return redirect()->back();
    }

    private function getCountryFlag(string $countryCode): string
    {
        if (strlen($countryCode) !== 2) {
            return '🌐'; // Default flag for invalid codes
        }

        $codePoints = str_split(strtoupper($countryCode));
        $codePoints = array_map(fn($char) => 127397 + ord($char), $codePoints);
        return mb_convert_encoding('&#' . implode(';&#', $codePoints) . ';', 'UTF-8', 'HTML-ENTITIES');
    }
}