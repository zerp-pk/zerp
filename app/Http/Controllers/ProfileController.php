<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
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

        if (isset($validated['avatar']) && $validated['avatar']) {
            $validated['avatar'] = basename($validated['avatar']);
        }

        $user->fill($validated);
        if ($user->isDirty('email') && admin_setting('enableEmailVerification') == 'on') {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->wasChanged('avatar') && $user->avatar) {
            $media = \App\Services\MediaAttachmentService::resolveOrBackfill(
                $user->avatar,
                \App\Models\User::class,
                $user->id,
                'avatars',
                $user->id,
                $user->created_by ?? $user->id,
                \App\Services\MediaAttachmentService::ensureDirectory('User Avatars', $user->created_by ?? $user->id, $user->id)
            );
            $user->update(['avatar_media_id' => $media?->id]);
        }

        return Redirect::route('profile.edit')->with('success', __('The profile details are updated successfully.'));
    }
}
