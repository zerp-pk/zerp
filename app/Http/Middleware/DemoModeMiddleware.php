<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoModeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.is_demo', false)) {
            return $next($request);
        }

        // Allow GET requests (viewing data)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Check if this is a restricted route/method
        if ($this->isRestrictedAction($request)) {
            return $this->demoModeResponse($request);
        }

        return $next($request);
    }

    /**
     * Check if the request is for a restricted action (update, delete, etc.)
     */
    private function isRestrictedAction(Request $request): bool
    {
        $uri = $request->getPathInfo();
        $routeName = $request->route() ? $request->route()->getName() : null;

        // Exempt routes (always allowed even in demo mode)
        $exemptUris = [
            '/login',
            '/logout',
            '/user/language',
            '/languages/change', // Allow changing UI language
        ];

        foreach ($exemptUris as $exemptUri) {
            if (str_contains($uri, $exemptUri)) {
                return false;
            }
        }

        // Specific exemptions by route name
        $exemptRouteNames = [
            'sales-returns.approve',
            'sales-returns.complete',
            'purchase-returns.approve',
            'purchase-returns.complete',
            'retainers.sent',
            'retainers.accept',
            'retainers.reject',
            'retainers.duplicate',
            'retainers.convert-to-invoice',
            'retainer-payments.store',
            'retainer-payments.update-status',
            'account.vendor-payments.store',
            'account.vendor-payments.update-status',
            'account.customer-payments.store',
            'account.customer-payments.update-status',
            'account.debit-notes.approve',
            'account.credit-notes.approve',
            'account.revenues.approve',
            'account.revenues.post',
            'account.expenses.approve',
            'account.expenses.post',
            'assets.asset-assignments.return',
            'sales-proposals.accept',
            'sales-proposals.reject',
            'sales-proposals.sent',
            'quotations.approve',
            'quotations.reject',
            'quotations.sent',
            'settings.brand.update', // Allow brand settings update for demo mode cookies
            'newsletter.subscribe',
        ];

        if ($routeName && in_array($routeName, $exemptRouteNames)) {
            return false;
        }

        // Messenger file upload restriction
        if ($routeName === 'messenger.send' && $request->hasFile('attachment')) {
            return true;
        }

        // 1. Block by Method
        if (in_array($request->method(), ['PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        // 2. Block by URI patterns (Commonly used for POST-based updates/deletes)
        $restrictedPatterns = [
            '/update',
            '/destroy',
            '/toggle-status',
            '/approve',
            '/reject',
            '/reset-password',
            '/upgrade-plan',
            '/settings',
            '/password',
            '/media/batch',
            '/subscribe',
            '/start-trial',
            '/assign-free',
            '/landing-page',
        ];

        foreach ($restrictedPatterns as $pattern) {
            if (str_contains($uri, $pattern)) {
                return true;
            }
        }

        // 3. Block by specific route names if available
        if ($routeName) {
            $restrictedRoutePatterns = [
                '.update',
                '.destroy',
                '.toggle-status',
                '.approve',
                '.reject',
                '.reset-password',
                '.upgrade-plan',
                'settings.save',
                'password.update',
                'plans.subscribe',
                'plans.start-trial',
                'plans.assign-free',
                'subscriptions.store',
                'payment.bank-transfer.store',
                'landing-page.store',
                'custom-pages.store',
                'newsletter-subscribers.destroy',
                'email.notification.setting.store',
                'ai-assistant.settings.store',
                'contract-settings.store',
                'twilio.settings.store',
                'payment.stripe.store',
                'payment.paypal.store',
                'users.change-password',
                '.change-password',
            ];

            foreach ($restrictedRoutePatterns as $pattern) {
                if (str_contains($routeName, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return response for blocked actions
     */
    private function demoModeResponse(Request $request): Response
    {
        $message = __('This action is disabled in demo mode. You can only create new data, not modify existing demo data.');

        // If it's an Inertia request or standard web request, redirect back
        if (!$request->is('api/*') && !$request->expectsJson() || $request->header('X-Inertia')) {
            return redirect()->back()->with('error', $message);
        }

        // For actual API or pure JSON requests
        return response()->json([
            'message' => $message,
            'demo_mode' => true,
            'success' => false
        ], 403);
    }
}
