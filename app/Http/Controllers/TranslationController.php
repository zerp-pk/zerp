<?php

namespace App\Http\Controllers;

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

        // Merge enabled package translations
        $enabledPackages = AddOn::where('is_enable', true)->pluck('module');
        foreach ($enabledPackages as $packageName) {
            $packageLangFile = base_path("packages/local/{$packageName}/src/Resources/lang/{$locale}.json");
            if (File::exists($packageLangFile)) {
                $packageTranslations = json_decode(File::get($packageLangFile), true) ?? [];
                $translations = array_merge($translations, $packageTranslations);
            }
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

            $packageLangFile = base_path("packages/local/{$package->module}/src/Resources/lang/{$locale}.json");
            if (!File::exists($packageLangFile)) {
                $packageLangFile = base_path("packages/local/{$package->module}/src/Resources/lang/en.json");
                if (!File::exists($packageLangFile)) {
                    return response()->json(['translations' => []]);
                }
            }

            $translations = json_decode(File::get($packageLangFile), true) ?? [];

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
            $packageLangFile = base_path("packages/local/{$package->module}/src/Resources/lang/{$locale}.json");

            try {
                $packageLangDir = dirname($packageLangFile);
                if (!File::exists($packageLangDir)) {
                    File::makeDirectory($packageLangDir, 0755, true);
                }

                if (File::exists($packageLangFile)) {
                    $currentTranslations = json_decode(File::get($packageLangFile), true) ?? [];
                    $translations = array_merge($currentTranslations, $translations);
                }
                File::put($packageLangFile, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
                'code' => 'required|string|max:10',
                'name' => 'required|string|max:255',
                'countryCode' => 'required|string|size:2'
            ], [
                'code.required' => __('Language code is required.'),
                'code.string' => __('Language code must be a valid string.'),
                'code.max' => __('Language code must not exceed 10 characters.'),
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

                // Copy package translations
                $enabledPackages = AddOn::where('is_enable', true)->pluck('module');
                foreach ($enabledPackages as $packageName) {
                    $packageEnFile = base_path("packages/local/{$packageName}/src/Resources/lang/en.json");
                    $packageNewFile = base_path("packages/local/{$packageName}/src/Resources/lang/{$request->code}.json");
                    if (File::exists($packageEnFile)) {
                        $packageDir = dirname($packageNewFile);
                        if (!File::exists($packageDir)) {
                            File::makeDirectory($packageDir, 0755, true);
                        }
                        File::copy($packageEnFile, $packageNewFile);
                    }
                }

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

                // Delete package language files
                $enabledPackages = AddOn::where('is_enable', true)->pluck('module');
                foreach ($enabledPackages as $packageName) {
                    $packageLangFile = base_path("packages/local/{$packageName}/src/Resources/lang/{$languageCode}.json");
                    if (File::exists($packageLangFile)) {
                        File::delete($packageLangFile);
                    }
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