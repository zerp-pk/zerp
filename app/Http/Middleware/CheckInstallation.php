<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CheckInstallation
{
    public function handle(Request $request, Closure $next)
    {
        if (!$this->isInstalled() && !$request->is('install*')) {
            return redirect()->route('installer.welcome');
        }

        if ($this->isInstalled() && $request->is('install*')) {
            return redirect('/dashboard');
        }

        return $next($request);
    }

    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }
}