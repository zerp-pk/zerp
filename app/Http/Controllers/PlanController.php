<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\AddOn;
use App\Models\Order;
use App\Classes\Module;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Requests\UpdateModulePriceRequest;
use App\Http\Requests\ApplyCouponRequest;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index()
    {
        if (Auth::user()->can('manage-plans')) {
            $user = Auth::user();

            // Super admin sees all plans, company users see only active plans
            $plans = Plan::query()
                ->when($user->type != 'superadmin', function ($query) {
                    return $query->where('status', true);
                })
                ->with('creator')
                ->withCount(['orders' => function ($query) {
                    $query->where('payment_status', 'succeeded');
                }])
                ->latest()
                ->get();

            // Get enabled addons with details
            $activeModules = AddOn::where('is_enable', 1)->where('for_admin',false)
                ->whereNotIn('module',User::$superadmin_activated_module)
                ->select('module', 'name', 'image', 'monthly_price', 'yearly_price')
                ->get()
                ->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $addon->image ?: url('/packages/local/' . $addon->module . '/favicon.png'),
                        'monthly_price' => $addon->monthly_price ?? 0,
                        'yearly_price' => $addon->yearly_price ?? 0,
                    ];
                })
                ->toArray();

            $userTrialInfo = null;
            $currentSubscription = null;
            if ($user->type != 'superadmin') {
                $userTrialInfo = [
                    'is_trial_done' => $user->is_trial_done ?? 0,
                    'trial_expire_date' => $user->trial_expire_date,
                ];
                
                // Active Subscription Details
                if ($user->active_plan) {
                    $activePlanObj = Plan::find($user->active_plan);
                    if ($activePlanObj) {
                        $latestOrder = Order::where('created_by', $user->id)
                            ->where('plan_id', $user->active_plan)
                            ->where('payment_status', 'succeeded')
                            ->latest()
                            ->first();

                        $duration = 'monthly';
                        if (!empty($user->plan_expire_date) && $user->plan_expire_date >= now()->format('Y-m-d')) {
                            if ($latestOrder) {
                                $diffDays = \Carbon\Carbon::parse($latestOrder->created_at)->diffInDays(\Carbon\Carbon::parse($user->plan_expire_date));
                                if ($diffDays > 40) { // If it's more than 40 days, it must be yearly
                                    $duration = 'yearly';
                                }
                            }
                        } else if (!empty($user->trial_expire_date) && $user->trial_expire_date >= now()->format('Y-m-d')) {
                            $duration = 'trial';
                        } else if (empty($user->plan_expire_date)) {
                            $duration = 'lifetime';
                        }
                        
                        $paymentAmount = $latestOrder ? $latestOrder->price : 0;
                        $currency = $latestOrder ? $latestOrder->currency : (admin_setting('defaultCurrency') ?? 'USD');

                        $currentSubscription = [
                            'plan_name' => $activePlanObj->name,
                            'duration' => $duration,
                            'expire_date' => $duration == 'trial' ? $user->trial_expire_date : $user->plan_expire_date,
                            'payment_amount' => $paymentAmount,
                            'currency' => $currency,
                            'is_free' => $activePlanObj->free_plan == 1
                        ];
                    }
                }
            }

            return Inertia::render('plans/index', [
                'plans' => $plans,
                'canCreate' => $user->can('create-plans'),
                'activeModules' => $activeModules,
                'bankTransferEnabled' => getAdminAllSetting()['bankTransferEnabled'] ?? false,
                'userTrialInfo' => $userTrialInfo,
                'currentSubscription' => $currentSubscription,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function create()
    {
        if (Auth::user()->can('create-plans')) {
            $user = Auth::user();

            // Get all enabled addons
            $allAddons = AddOn::where('is_enable', 1)->where('for_admin',false)
                ->select('module', 'name', 'image')
                ->get();

            // Filter modules based on user's subscription
            $availableModules = [];
            if ($user->hasRole('superadmin')) {
                // Super admin can see all modules except superadmin_activated_module
                $availableModules = $allAddons->whereNotIn('module', User::$superadmin_activated_module)->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $addon->image ?: url('/packages/local/' . $addon->module . '/favicon.png'),
                    ];
                })->values()->toArray();
            } else {
                // Company users see only modules from their subscription
                $userAvailableModules = (new Plan())->getAvailableModulesForUser($user->id);

                $availableModules = $allAddons->whereNotIn('module', User::$superadmin_activated_module)->filter(function ($addon) use ($userAvailableModules) {
                    return in_array($addon->module, $userAvailableModules);
                })->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $addon->image ?: url('/packages/local/' . $addon->module . '/favicon.png'),
                    ];
                })->values()->toArray();
            }

            return Inertia::render('plans/create', [
                'activeModules' => $availableModules,
                'userSubscriptionInfo' => [
                    'is_superadmin' => $user->hasRole('superadmin'),
                    'active_plan_id' => $user->active_plan,
                    'available_modules_count' => count($availableModules),
                ],
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function store(StorePlanRequest $request)
    {
        if (Auth::user()->can('create-plans')) {
            $validated = $request->validated();
            $plan = new Plan();
            $plan->name = $validated['name'];
            $plan->description = $validated['description'];
            $plan->number_of_users = $validated['number_of_users'];
            $plan->storage_limit = $validated['storage_limit'] * 1024 * 1024;
            $plan->status = $request->boolean('status', true);
            $plan->free_plan = $request->boolean('free_plan', false);
            $plan->modules = $validated['modules'] ?? [];
            $plan->package_price_yearly = $validated['package_price_yearly'];
            $plan->package_price_monthly = $validated['package_price_monthly'];
            $plan->trial = $request->boolean('trial', false);
            $plan->trial_days = $validated['trial_days'] ?? 0;
            $plan->created_by = creatorId();
            $plan->save();

            return redirect()->route('plans.index')
                ->with('success', __('The plan has been created successfully.'));
        } else {
            return redirect()->route('plans.index')->with('error', __('Permission denied'));
        }
    }

    public function show(Plan $plan)
    {
        return redirect()->back();
    }

    public function edit(Plan $plan)
    {
        if (Auth::user()->can('edit-plans')) {
            $user = Auth::user();

            // Get all enabled addons
            $allAddons = AddOn::where('is_enable', 1)->where('for_admin',false)
                ->select('module', 'name', 'image')
                ->get();

            // Filter modules based on user's subscription
            $availableModules = [];
            if ($user->hasRole('superadmin')) {
                // Super admin can see all modules except superadmin_activated_module
                $availableModules = $allAddons->whereNotIn('module',User::$superadmin_activated_module)->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $addon->image ?: url('/packages/local/' . $addon->module . '/favicon.png'),
                    ];
                })->values()->toArray();
            } else {
                // Company users see only modules from their subscription
                $userAvailableModules = (new Plan())->getAvailableModulesForUser($user->id);

                $availableModules = $allAddons->whereNotIn('module', User::$superadmin_activated_module)->filter(function ($addon) use ($userAvailableModules) {
                    return in_array($addon->module, $userAvailableModules);
                })->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $addon->image ?: url('/packages/local/' . $addon->module . '/favicon.png'),
                    ];
                })->values()->toArray();
            }

            // Convert storage_limit from KB to GB for display
            $planData = $plan->toArray();
            $planData['storage_limit'] = $plan->storage_limit ? round($plan->storage_limit / (1024 * 1024)) : 0;

            return Inertia::render('plans/edit', [
                'plan' => $planData,
                'activeModules' => $availableModules,
                'userSubscriptionInfo' => [
                    'is_superadmin' => $user->hasRole('superadmin'),
                    'active_plan_id' => $user->active_plan,
                    'available_modules_count' => count($availableModules),
                ],
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        if (Auth::user()->can('edit-plans')) {
            $validated = $request->validated();
            
            $plan->name = $validated['name'];
            $plan->description = $validated['description'];
            $plan->number_of_users = $validated['number_of_users'];
            $plan->storage_limit = $validated['storage_limit'] * 1024 * 1024;
            $plan->status = $request->boolean('status', true);
            $plan->free_plan = $request->boolean('free_plan', false);
            $plan->modules = $validated['modules'] ?? [];
            $plan->package_price_yearly = $validated['package_price_yearly'];
            $plan->package_price_monthly = $validated['package_price_monthly'];
            $plan->trial = $request->boolean('trial', false);
            $plan->trial_days = $validated['trial_days'] ?? 0;

            $plan->save();

            return redirect()->route('plans.index')->with('success', __('The plan details are updated successfully.'));
        } else {
            return redirect()->route('plans.index')->with('error', __('Permission denied'));
        }
    }

    public function destroy(Plan $plan)
    {
        if (Auth::user()->can('delete-plans')) {

            $plan->delete();

            return redirect()->route('plans.index')
                ->with('success', __('The plan has been deleted.'));
        } else {
            return redirect()->route('plans.index')->with('error', __('Permission denied'));
        }
    }

    public function updateModulePrice(UpdateModulePriceRequest $request)
    {
        $validated = $request->validated();

        $addon = AddOn::where('module', $validated['module'])->first();

        if (!$addon) {
            return back()->with('error', __('Module not found.'));
        }

        $updateData = [
            'monthly_price' => $validated['monthly_price'],
            'yearly_price' => $validated['yearly_price'],
        ];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if($request->hasFile('image')){
            $name = $addon->module . '.'.$request->image->getClientOriginalExtension();
            $file = upload_file($request,'image',$name,'add-ons');
            if($file['flag'])
            {
                $updateData['image'] = $file['url'];
                $media = \App\Services\MediaAttachmentService::resolveOrBackfill(
                    $file['url'],
                    AddOn::class,
                    $addon->id,
                    'addon_images',
                    Auth::id(),
                    Auth::id(),
                    \App\Services\MediaAttachmentService::ensureDirectory('Add-on Images', Auth::id(), Auth::id())
                );
                $updateData['media_id'] = $media?->id;
            }
            else
            {
                return back()->with('error', $file['msg']);
            }
        }

        $addon->update($updateData);

        (new Module())->moduleCacheForget($validated['module']);

        return back()->with('success', __('Add-On price updated successfully.'));
    }

    public function applyCoupon(ApplyCouponRequest $request)
    {
        $validated = $request->validated();

        $result = applyCouponDiscount($validated['coupon_code'], $validated['total_amount'], Auth::id());
        if (!$result['valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ]);
        }
        return response()->json([
            'success' => true,
            'discount_amount' => $result['discount_amount'],
            'final_amount' => $result['final_amount'],
            'coupon' => [
                'code' => $result['coupon']->code,
                'name' => $result['coupon']->name,
                'type' => $result['coupon']->type,
                'discount' => $result['coupon']->discount
            ]
        ]);
    }

    public function subscribe(\Illuminate\Http\Request $request, Plan $plan)
    {
        if (Auth::user()->can('view-plans')) {
            $user = Auth::user();

            // Get enabled addons with details
            $activeModules = AddOn::where('is_enable', 1)->where('for_admin',false)
                ->select('module', 'name', 'image', 'monthly_price', 'yearly_price')
                ->get()
                ->map(function ($addon) {
                    return [
                        'module' => $addon->module,
                        'alias' => $addon->name,
                        'image' => $addon->image ?: url('/packages/local/' . $addon->module . '/favicon.png'),
                        'monthly_price' => $addon->monthly_price ?? 0,
                        'yearly_price' => $addon->yearly_price ?? 0,
                    ];
                })
                ->toArray();

            // Get user's active modules
            $userActiveModules = UserActiveModule::where('user_id', $user->id)
                ->pluck('module')
                ->toArray();

            return Inertia::render('plans/subscribe', [
                'plan' => $plan,
                'activeModules' => $activeModules,
                'initialPeriod' => $request->query('period', 'monthly'),
                'userActiveModules' => $userActiveModules,
                'bankTransferEnabled' => getAdminAllSetting()['bankTransferEnabled'] ?? false,
                'bankTransferInstructions' => getAdminAllSetting()['instructions'] ?? '',
                'planExpireDate' => $user->plan_expire_date,
            ]);
        } else {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function startTrial(Plan $plan)
    {
        $user = Auth::user();  
        // Check if trial already done
        if ($user->is_trial_done == '1') {
            return back()->with('error', __('Your Plan trial already done.'));
        }           

        $counter = [
            'user_counter' => $plan->number_of_users ?? '0',
            'storage_limit' => $plan->storage_limit ?? '0',
        ];
        try {
            // Use assignPlan method similar to old code
            $result = assignPlan($plan->id, 'Trial', implode(',', $plan->modules ?? []),$counter,  $user->id);
            if ($result['is_success']) {
                $user->is_trial_done = 1;
                $user->save();
                
                return back()->with('success', __('Your trial has been started.'));
            } else {
                return back()->with('error', $result['error'] ?? __('Failed to start trial.'));
            }
        } catch (\Exception $e) {            
            return back()->with('error', __('Plan Not Found.'));
        }
    }

    public function assignFreePlan(Request $request, Plan $plan)
    {
        $user = Auth::user();

        if (!$plan->free_plan) {
            return back()->with('error', __('This plan is not a free plan.'));
        }

        $duration = $request->duration;
        $durationStr = (string)$duration;
        \Illuminate\Support\Facades\Log::error('ASSIGIPLAN LOG [DEBUG]: called', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'duration' => $durationStr
        ]);
        $counter = [
            'user_counter' => $plan->number_of_users ?? '0',
            'storage_limit' => $plan->storage_limit ?? '0',
        ];
        $result = assignPlan($plan->id, $duration, implode(',', $plan->modules ?? []), $counter, $user->id);
        $orderID = strtoupper(substr(uniqid(), -12));

        if ($result['is_success']) {
            $order = new Order();
            $order->order_id = $orderID;
            $order->name = $user->name;
            $order->email = $user->email;
            $order->card_number = null;
            $order->card_exp_month = null;
            $order->card_exp_year = null;
            $order->plan_name = $plan->name;
            $order->plan_id = $plan->id;
            $order->price = 0;
            $order->currency = admin_setting('defaultCurrency') ?? 'USD';
            $order->txn_id = '';
            $order->payment_type = '-';
            $order->payment_status = 'succeeded';
            $order->receipt = null;
            $order->created_by = $user->id;
            $order->save();
            return back()->with('success', __('Free plan has been assigned successfully.'));
        } else {
            return back()->with('error', $result['error'] ?? 'Failed to assign free plan.');
        }
    }
}
