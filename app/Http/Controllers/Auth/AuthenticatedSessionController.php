<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $enableRegistration = admin_setting('enableRegistration');

        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'enableRegistration' => $enableRegistration === 'on',
            'isDemo' => config('app.is_demo', false),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Log login history
        $this->logLoginHistory($request);

        if (Auth::check() && Auth::user()->hasRole('superadmin')) {
            try {
                $output = Artisan::call('migrate:status');
                $result = Artisan::output();

                // Check if there are pending migrations
                if (strpos($result, 'Pending') !== false) {
                    // Redirect to updater if not already on updater route
                    return redirect()->route('updater.index');
                }
            } catch (\Exception $e) {
                // Ignore errors in checking migrations
            }
        }

        // intended(), not a bare dashboard redirect: clicking an email verification
        // link while logged out lands on this form, and the signed URL the auth
        // middleware stashed is the whole reason the user is here. Dropping it sent
        // them to the dashboard, which bounced them straight back to the "verify your
        // email" notice, so verifying only worked on a second link. See zerp-pk/zerp#72.
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function logLoginHistory(Request $request): void
    {
        $ip = $request->ip();
        $locationData = $this->getLocationData($ip);
        $userAgent = $request->userAgent();
        $browserData = parseBrowserData($userAgent);
        $details = array_merge($locationData, $browserData, [
            'status' => 'success',
            'referrer_host' => $request->headers->get('referer') ? parse_url($request->headers->get('referer'), PHP_URL_HOST) : null,
            'referrer_path' => $request->headers->get('referer') ? parse_url($request->headers->get('referer'), PHP_URL_PATH) : null,
        ]);

        $loginHistory             = new LoginHistory();
        $loginHistory->user_id    = Auth::id();
        $loginHistory->ip         = $ip;
        $loginHistory->date       = now()->toDateString();
        $loginHistory->details    = $details;
        $loginHistory->type       = Auth::user()->type;
        $loginHistory->created_by = creatorId();
        $loginHistory->save();
    }

    private function getLocationData(string $ip): array
    {
        try {
            $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'country' => $data['country'] ?? null,
                    'countryCode' => $data['countryCode'] ?? null,
                    'region' => $data['region'] ?? null,
                    'regionName' => $data['regionName'] ?? null,
                    'city' => $data['city'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'isp' => $data['isp'] ?? null,
                    'org' => $data['org'] ?? null,
                    'as' => $data['as'] ?? null,
                    'query' => $data['query'] ?? $ip,
                ];
            }
        } catch (\Exception $e) {
            // Ignore API errors
        }

        return ['query' => $ip];
    }


}
