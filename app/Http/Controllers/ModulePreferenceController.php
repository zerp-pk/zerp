<?php

namespace App\Http\Controllers;

use App\Classes\Module;
use App\Models\DisabledModule;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Lets a company switch off modules it does not use.
 *
 * Switching off is a preference, not a cancellation: the entitlement (the plan, or a
 * purchased add-on recorded in user_active_modules) is left alone, so switching back
 * on is free and instant. See DisabledModule.
 */
class ModulePreferenceController extends Controller
{
    public function index()
    {
        $company = $this->company();
        $disabled = DisabledModule::forCompany($company->id);
        $catalogue = (new Module())->allModules();

        // Only what the company is entitled to — its plan plus any add-ons it bought.
        // Note this reflects entitlement, not the disabled state, so a switched-off
        // module still appears here (that is the point: it can be switched back on).
        $entitled = Plan::getUserSubscriptionModules($company->id);

        $modules = collect($entitled)->map(function (string $name) use ($catalogue, $disabled) {
            $meta = collect($catalogue)->firstWhere('name', $name) ?? [];

            return [
                'module' => $name,
                'title' => $meta['alias'] ?? $meta['name'] ?? $name,
                'description' => $meta['description'] ?? null,
                'image' => url('/packages/local/' . $name . '/favicon.png'),
                'enabled' => !in_array($name, $disabled, true),
            ];
        })->sortBy('title')->values();

        return Inertia::render('settings/modules', [
            'modules' => $modules,
        ]);
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
