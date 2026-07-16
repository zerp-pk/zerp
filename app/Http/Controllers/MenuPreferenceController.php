<?php

namespace App\Http\Controllers;

use App\Models\MenuPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The sidebar layout: order of the top-level items, and which are hidden.
 *
 * Hiding is cosmetic. It takes an item out of the sidebar and nothing else - the
 * routes stay reachable and permissions still decide who may do what. To actually
 * take a module out of the product, use ModulePreferenceController.
 */
class MenuPreferenceController extends Controller
{
    /**
     * The arranger lives inside Settings now, so this route only exists to send anyone
     * holding an old link or bookmark to the section.
     */
    public function index()
    {
        return redirect()->route('settings.index', [], 302)->withFragment('menu-settings');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'scope' => 'required|in:user,company',
            'order' => 'array',
            'order.*' => 'string',
            'hidden' => 'array',
            'hidden.*' => 'string',
        ]);

        abort_if($validated['scope'] === 'company' && $user->type !== 'company', 403);

        // A company admin has both rows, and they key on the same user_id - hence
        // scope. Staff only ever write a `user` row.
        MenuPreference::updateOrCreate(
            ['user_id' => $user->id, 'scope' => $validated['scope']],
            ['order' => $validated['order'] ?? [], 'hidden_items' => $validated['hidden'] ?? []],
        );

        return back()->with('success', $validated['scope'] === 'company'
            ? __('The company menu layout has been saved.')
            : __('Your menu layout has been saved.'));
    }

    /** Drop the personal override and fall back to the company default. */
    public function destroy()
    {
        $user = Auth::user();

        MenuPreference::where('user_id', $user->id)->where('scope', 'user')->delete();

        return back()->with('success', __('Your menu layout has been reset.'));
    }

    /**
     * The layout a company admin set for everyone. Shared with SettingController,
     * which renders the section.
     */
    public static function companyDefaultFor($user): array
    {
        $default = MenuPreference::where('user_id', creatorId())->where('scope', 'company')->first();

        return [
            'order' => $default->order ?? [],
            'hidden' => $default->hidden_items ?? [],
        ];
    }
}
