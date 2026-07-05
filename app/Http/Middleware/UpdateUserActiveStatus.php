<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UpdateUserActiveStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->update(['active_status' => 1]);
            
            // Cache user as online for 5 minutes
            Cache::put("user_online_{$user->id}", true, now()->addMinutes(5));
        }

        return $next($request);
    }
}