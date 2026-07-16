<?php

namespace App\Http\Controllers;

use App\Classes\Module;
use App\Models\DisabledModule;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Lets a company switch off modules it does not use.
 *
 * Switching off is a preference, not a cancellation: the entitlement (the plan, or a
 * purchased add-on recorded in user_active_modules) is left alone, so switching back
 * on is free and instant. See DisabledModule.
 */
class ModulePreferenceController extends Controller
{
    /**
     * The modules screen lives inside Settings now, so this route only exists to send
     * anyone holding an old link or bookmark to the section.
     */
    public function index()
    {
        return redirect()->route('settings.index', [], 302)->withFragment('modules-settings');
    }

    /**
     * The rows the Modules section renders. Shared with SettingController, which is
     * what actually renders the page.
     */
    public static function rowsFor(User $company): array
    {
        $disabled = DisabledModule::forCompany($company->id);
        $catalogue = (new Module())->allModules();

        // Only what the company is entitled to, which is its plan. Note this reflects
        // entitlement, not the disabled state, so a switched-off module still appears
        // here (that is the point: it can be switched back on).
        $entitled = Plan::getUserSubscriptionModules($company->id);

        return collect($entitled)->map(function (string $name) use ($catalogue, $disabled) {
            // Module::find() returns the Module object itself, not an array - read it
            // with property access. Its image already resolves the add-on's own upload
            // and the vendor/zerp path, which packages/local no longer has.
            $meta = collect($catalogue)->firstWhere('name', $name);

            return [
                'module' => $name,
                'title' => $meta->alias ?? $name,
                'description' => $meta->description ?? null,
                'image' => $meta->image ?? url('/packages/local/' . $name . '/favicon.png'),
                'enabled' => !in_array($name, $disabled, true),
            ];
        })->sortBy('title')->values()->all();
    }

    public function update(Request $request)
    {
        $company = $this->company();

        $validated = $request->validate([
            'module' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        // A company may only switch modules it actually has. Without this check the
        // toggle would be a way to turn on modules the plan does not include.
        if (!in_array($validated['module'], Plan::getUserSubscriptionModules($company->id), true)) {
            throw ValidationException::withMessages([
                'module' => __('That module is not part of your plan.'),
            ]);
        }

        if ($validated['enabled']) {
            DisabledModule::where('user_id', $company->id)
                ->where('module', $validated['module'])
                ->delete();
        } else {
            DisabledModule::firstOrCreate([
                'user_id' => $company->id,
                'module' => $validated['module'],
            ]);
        }

        return back()->with('success', $validated['enabled']
            ? __('The module has been enabled.')
            : __('The module has been disabled.'));
    }

    /**
     * The company whose modules these are. Staff inherit their company's choices and
     * may not change them, so this is an admin-only screen.
     */
    private function company()
    {
        $user = Auth::user();

        abort_unless($user && $user->type === 'company', 403);

        return $user;
    }
}
