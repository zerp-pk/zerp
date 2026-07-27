<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        if(Auth::user()->can('manage-profile')){
            return Inertia::render('profile/edit', [
                'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
                'status' => session('status'),
            ]);
        }else{
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Clearing the picker submits an empty avatar, which ConvertEmptyStringsToNull
        // turns into null before it ever reaches validation. `users.avatar` is NOT NULL,
        // so writing that null blew up with an integrity violation (a 500 on the way
        // back to the profile page). Clearing means "back to the default placeholder".
        if (array_key_exists('avatar', $validated)) {
            $validated['avatar'] = filled($validated['avatar'])
                ? basename($validated['avatar'])
                : User::DEFAULT_AVATAR;
        }

        $user->fill($validated);
        if ($user->isDirty('email') && admin_setting('enableEmailVerification') == 'on') {
            $user->email_verified_at = null;
        }

        $user->save();

        // The media link has to be cleared as well as set, or a removed picture
        // leaves avatar_media_id pointing at media that no longer represents it.
        if ($user->wasChanged('avatar')) {
            $media = $user->hasCustomAvatar()
                ? \App\Services\MediaAttachmentService::resolveOrBackfill(
                    $user->avatar,
                    User::class,
                    $user->id,
                    'avatars',
                    $user->id,
                    $user->created_by ?? $user->id,
                    \App\Services\MediaAttachmentService::ensureDirectory('User Avatars', $user->created_by ?? $user->id, $user->id)
                )
                : null;

            $user->update(['avatar_media_id' => $media?->id]);
        }

        return Redirect::route('profile.edit')->with('success', __('The profile details are updated successfully.'));
    }
}
