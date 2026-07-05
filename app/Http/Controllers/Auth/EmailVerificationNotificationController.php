<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Apply dynamic mail configuration
        $adminUser = User::where('type', 'superadmin')->first();
        try {
            if ($adminUser) {
                SetConfigEmail($adminUser->id);
            }
            $request->user()->sendEmailVerificationNotification();
            return back()->with('status', 'verification-link-sent');
        } catch (\Throwable $th) {
            return back()->withErrors(['email' => 'Failed to send verification email. Please try again later.']);
        }
    }
}
