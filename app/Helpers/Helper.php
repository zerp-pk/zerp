<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Classes\Module;
use App\Events\DefaultData;
use App\Events\GivePermissionToRole;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserActiveModule;
use App\Models\UserCoupon;
use App\Models\AddOn;
use Spatie\Permission\Models\Role;
use App\Services\DynamicStorageService;
use App\Services\StorageConfigService;

if (!function_exists('creatorId')) {
    function creatorId()
    {
        if (Auth::user()->type == 'superadmin' || Auth::user()->type == 'company') {
            return Auth::user()->id;
        } else {
            return Auth::user()->created_by;
        }
    }
}

if (!function_exists('creatorUser')) {
    function creatorUser()
    {
        if (Auth::user() && (Auth::user()->type == 'superadmin' || Auth::user()->type == 'company')) {
            return Auth::user();
        } else {
            return Auth::user()->createdBy();
        }
    }
}

if (!function_exists('setSetting')) {
    function setSetting(string $key, $value, $userId = null, $isPublic = true): void
    {
        $createdBy = $userId ?? creatorId();
        Setting::updateOrCreate(
            ['key' => $key, 'created_by' => $createdBy],
            ['value' => $value, 'is_public' => $isPublic]
        );

        // Clear user-specific cache
        if (Auth::check() && Auth::user()->type == 'superadmin'){
            Cache::forget('admin_settings');
            Cache::forget('admin_settings_public');
        }
        Cache::forget('company_settings_' . $createdBy);
        Cache::forget('company_settings_' . $createdBy . '_public');
    }
}

if (!function_exists('getAdminAllSetting')) {
    function getAdminAllSetting($publicOnly = false)
    {
        $cacheKey = $publicOnly ? 'admin_settings_public' : 'admin_settings';
        $settings = Cache::rememberForever($cacheKey, function () use ($publicOnly) {
            $super_admin = User::where('type', 'superadmin')->first();
            if ($super_admin) {
                $query = Setting::where('created_by', $super_admin->id);
                if ($publicOnly) {
                    $query->where('is_public', 1);
                }
                return $query->pluck('value', 'key')->toArray();
            }
            return [];
        });

        if (config('app.is_demo')) {
            $themeKeys = [
                'theme_color' => 'themeColor',
                'sidebar_variant' => 'sidebarVariant',
                'sidebar_style' => 'sidebarStyle',
                'layout_direction' => 'layoutDirection', 
                'theme_mode' => 'themeMode',
                'custom_color' => 'customColor'
            ];
            
            $superadmin = User::where('type', 'superadmin')->first();
            $cookieName = 'theme_settings_' . ($superadmin ? $superadmin->id : 1);
            
            if (\Cookie::get($cookieName)) {
                $cookieData = json_decode(\Cookie::get($cookieName), true);
                if (is_array($cookieData)) {
                    foreach ($themeKeys as $cookieKey => $settingKey) {
                        if (isset($cookieData[$cookieKey])) {
                            $settings[$settingKey] = $cookieData[$cookieKey];
                        }
                    }
                }
            }
        }

        // Auto-set RTL for specific languages
        if (in_array(app()->getLocale(), ['ar', 'he'])) {
            $settings['layoutDirection'] = 'rtl';
        }

        return $settings;
    }
}

if (!function_exists('getCompanyAllSetting')) {
    function getCompanyAllSetting($user_id = null, $publicOnly = false)
    {
        $user = $user_id ? User::find($user_id) : auth()->user();

        if (!$user) return [];

        if (!in_array($user->type, ['company', 'superadmin'])) {
            $user = User::find($user->created_by);
        }

        if ($user) {
            $key = $publicOnly ? 'company_settings_' . $user->id . '_public' : 'company_settings_' . $user->id;
            $settings = Cache::rememberForever($key, function () use ($user, $publicOnly) {
                $query = Setting::where('created_by', $user->id);
                if ($publicOnly) {
                    $query->where('is_public', 1);
                }
                return $query->pluck('value', 'key')->toArray();
            });

            if (config('app.is_demo')) {
                $themeKeys = [
                    'theme_color' => 'themeColor',
                    'sidebar_variant' => 'sidebarVariant',
                    'sidebar_style' => 'sidebarStyle',
                    'layout_direction' => 'layoutDirection', 
                    'theme_mode' => 'themeMode',
                    'custom_color' => 'customColor'
                ];
                
                $cookieName = 'theme_settings_' . creatorId();
                if (\Cookie::get($cookieName)) {
                    $cookieData = json_decode(\Cookie::get($cookieName), true);
                    if (is_array($cookieData)) {
                        foreach ($themeKeys as $cookieKey => $settingKey) {
                            if (isset($cookieData[$cookieKey])) {
                                $settings[$settingKey] = $cookieData[$cookieKey];
                            }
                        }
                    }
                }
            }

            // Auto-set RTL for specific languages
            if (in_array(app()->getLocale(), ['ar', 'he'])) {
                $settings['layoutDirection'] = 'rtl';
            }

            return $settings;
        }

        return [];
    }
}

if (!function_exists('admin_setting')) {
    function admin_setting($key)
    {
        if ($key) {
            $admin_settings = getAdminAllSetting();
            $setting = (array_key_exists($key, $admin_settings)) ? $admin_settings[$key] : null;
            return $setting;
        }
    }
}

if (!function_exists('company_setting')) {
    function company_setting($key, $user_id = null)
    {
        if ($key) {
            $company_settings = getCompanyAllSetting($user_id);
            return $company_settings[$key] ?? null;
        }
        return null;
    }
}

if (!function_exists('getImageUrlPrefix')) {
    function getImageUrlPrefix(): string
    {
        $storageType = admin_setting('storageType') ?: 'local';

        switch ($storageType) {
            case 's3':
            case 'aws_s3':
                $endpoint = admin_setting('awsEndpoint');
                if ($endpoint && strpos($endpoint, 'amazonaws.com') === false) {
                    return rtrim($endpoint, '/') . '/media/';
                }
                $bucket = admin_setting('awsBucket');
                $region = admin_setting('awsDefaultRegion');
                return "https://{$bucket}.s3.{$region}.amazonaws.com/media";

            case 'wasabi':
                $url = admin_setting('wasabiUrl');
                $bucket = admin_setting('wasabiBucket');
                return $url ? rtrim($url, '/') . '/' . $bucket . '/media' : url('/storage/media/');

            case 'local':
                return url('/storage/media/');
            default:
                return url('/storage/media/');
        }
    }
}

// Users Activated Module
if (!function_exists('ActivatedModule')) {
    function ActivatedModule($user_id = null)
    {
        $activated_module = user::$superadmin_activated_module;
        $user_active_module = [];

        if ($user_id != null) {
            $user = User::find($user_id);
        } elseif (Auth::check()) {
            $user = Auth::user();
        } else {
            $user = null;
        }

        if (!empty($user)) {
            $available_modules = array_values((new Module())->allEnabled());

            if ($user->type == 'superadmin') {
                $user_active_module = $available_modules;
            } else {
                $active_module = [];
                if ($user->type != 'company') {
                    $user = User::find($user->created_by);
                }

                if ($user) {
                    $active_module = UserActiveModule::where('user_id', $user->id)->pluck('module')->toArray();
                    $user_active_module = array_values(array_intersect($available_modules, $active_module));
                    $user_active_module = array_values(array_unique(array_merge($activated_module,$user_active_module)));
                }
            }
        } else {
            $active_module = array_values((new Module())->allEnabledAdmin());
            $user_active_module = $active_module;
        }
        return $user_active_module;
    }
}

// check module is active
if (!function_exists('Module_is_active')) {
    function Module_is_active($module, $user_id = null)
    {
        if ((new Module())->has($module)) {

            $isModuleActive = (new Module())->isEnabled($module);
            if ($isModuleActive == false) {
                return false;
            }

            if (!empty($user_id)) {
                $user = User::find($user_id);
            } else {
                $user = Auth::user();
            }
            if (!empty($user)) {
                if ($user->type == 'superadmin') {
                    return true;
                } else {
                    $active_module = ActivatedModule($user->id);
                    if ((count($active_module) > 0 && in_array($module, $active_module))) {
                        return true;
                    }
                    return false;
                }
            }
            return false;
        }
        return false;
    }
}

// for plan assign
if (!function_exists('assignPlan')) {
    function assignPlan($plan_id = null, $duration = null, $modules = null, $counter = null, $user_id = null)
    {
        if ($user_id != null) {
            $user = User::find($user_id);
        } else {
            $user = User::find(Auth::user()->id);
        }

        if ($plan_id != null) {
            $plan = \App\Models\Plan::find($plan_id);
        } else {
            $plan = \App\Models\Plan::where('free_plan', 1)->first();
        }

        if ($plan && $user) {
            $user->active_plan = $plan->id;
            if (!empty($duration)) {
                $durationStr = (string)$duration;

                if (strtolower($durationStr) == 'month' || $durationStr === '1') {
                    $user->plan_expire_date = \Carbon\Carbon::now()->addMonths(1)->isoFormat('YYYY-MM-DD');
                    $user->trial_expire_date = null;
                } elseif (strtolower($durationStr) == 'year') {
                    $user->plan_expire_date = \Carbon\Carbon::now()->addYears(1)->isoFormat('YYYY-MM-DD');
                    $user->trial_expire_date = null;
                } elseif (strtolower($durationStr) == 'trial') {
                    $user->trial_expire_date = \Carbon\Carbon::now()->addDays((int)$plan->trial_days)->isoFormat('YYYY-MM-DD');
                    if ($user->plan_expire_date) {
                        $user->plan_expire_date = null;
                    }
                } else {
                    $user->plan_expire_date = null;
                }
            } else {
                $user->plan_expire_date = null;
            }
            // Handle modules assignment
            if ($modules !== null) {
                $modules_array = explode(',', $modules);
            } else {
                $modules_array = is_array($plan->modules) ? $plan->modules : [];
            }
           if(!empty($modules))
            {
                UserActiveModule::where('user_id', $user->id)->delete();

                $modules_array = explode(',',$modules);
                $currentActiveModules = UserActiveModule::where('user_id', $user->id)->pluck('module')->toArray();
                
                $user_module = $currentActiveModules;
                foreach ($modules_array as $module) {
                    if(!in_array($module,$user_module)){
                        array_push($user_module,$module);
                    }
                }

                $newModules = array_diff($user_module, $currentActiveModules);
                foreach ($newModules as $moduleName) {
                    UserActiveModule::create([
                        'user_id' => $user->id,
                        'module' => $moduleName,
                    ]);
                }
                DefaultData::dispatch($user->id, $modules);
                $client_role = Role::where('name', 'client')->where('created_by', $user->id)->first();
                $staff_role = Role::where('name', 'staff')->where('created_by', $user->id)->first();

                if (!empty($client_role)) {
                    GivePermissionToRole::dispatch($client_role->id, 'client', $modules);
                }
                if (!empty($staff_role)) {
                    GivePermissionToRole::dispatch($staff_role->id, 'staff', $modules);
                }
            }
            
            // Set user limits from plan (don't modify the plan itself)
            $user->total_user = $plan->number_of_users;
            $user->storage_limit = $plan->storage_limit;
            $user->save();

            // User count management logic
            $users = User::where('created_by', $user->id)->where('is_disable', 0)->get();
            $total = $users->count();

            if ($plan->number_of_users == -1) {
                $users = User::where('created_by', $user->id)->get();
                foreach ($users as $item) {
                    $item->is_disable = 0;
                    $item->is_enable_login = 1;
                    $item->save();
                }
            } elseif ($plan->number_of_users > 0) {
                if ($total > $plan->number_of_users) {
                    $count = $total - $plan->number_of_users;
                    $usersToDisable = User::orderBy('created_at', 'desc')
                        ->where('created_by', $user->id)
                        ->where('is_disable', 0)
                        ->take($count)
                        ->get();
                    foreach ($usersToDisable as $userItem) {
                        $userItem->is_disable = 1;
                        $userItem->is_enable_login = 0;
                        $userItem->save();
                    }
                } else {
                    $count = $plan->number_of_users - $total;
                    $usersToEnable = User::where('created_by', $user->id)
                        ->where('is_disable', 1)
                        ->take($count)
                        ->get();

                    foreach ($usersToEnable as $userItem) {
                        $userItem->is_disable = 0;
                        $userItem->is_enable_login = 1;
                        $userItem->save();
                    }
                }
            }

            return ['is_success' => true];
        } else {
            return [
                'is_success' => false,
                'error' => 'Plan is deleted.',
            ];
        }
    }
}

// Plan check
if (!function_exists('canCreateUser')) {
    function canCreateUser($userId = null)
    {
        $user = $userId ? User::find($userId) : Auth::user();

        if (!$user) {
            return ['can_create' => false, 'message' => __('User not found')];
        }

        $creator = ($user->type == 'company' || $user->type == 'superadmin') ? $user : User::find($user->created_by);

        if (!$creator) {
            return ['can_create' => false, 'message' => __('Creator not found')];
        }

        if ($creator->total_user == -1) {
            return ['can_create' => true];
        }

        $currentUserCount = User::where('created_by', $creator->id)->where('is_disable', 0)->count();

        if ($currentUserCount >= $creator->total_user) {
            return ['can_create' => false, 'message' => __('You have reached the maximum user limit. Please upgrade your plan.')];
        }

        return ['can_create' => true];
    }
}

// use coupon
if (!function_exists('recordCouponUsage')) {
    function recordCouponUsage($couponId, $userId, $orderId = null)
    {
        UserCoupon::create([
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'order_id' => $orderId
        ]);

        return true;
    }
}

// apply coupon
if (!function_exists('applyCouponDiscount')) {
    function applyCouponDiscount($couponCode, $originalAmount, $userId = null)
    {
        $coupon = \App\Models\Coupon::where('code', $couponCode)
            ->where('status', true)
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'message' => __('Invalid coupon code')];
        }

        if ($coupon->expiry_date && $coupon->expiry_date < now()) {
            return ['valid' => false, 'message' => __('Coupon has expired')];
        }

        if ($coupon->limit) {
            $usageCount = UserCoupon::where('coupon_id', $coupon->id)->count();
            if ($usageCount >= $coupon->limit) {
                return ['valid' => false, 'message' => __('Coupon usage limit exceeded')];
            }
        }

        if ($userId && $coupon->limit_per_user) {
            $userUsageCount = UserCoupon::where('coupon_id', $coupon->id)
                ->where('user_id', $userId)->count();
            if ($userUsageCount >= $coupon->limit_per_user) {
                return ['valid' => false, 'message' => __('You have exceeded the usage limit for this coupon')];
            }
        }

        if ($coupon->minimum_spend && $originalAmount < $coupon->minimum_spend) {
            return ['valid' => false, 'message' => __('Minimum spend amount not met')];
        }

        if ($coupon->maximum_spend && $originalAmount > $coupon->maximum_spend) {
            return ['valid' => false, 'message' => __('Maximum spend amount exceeded')];
        }

        $discountAmount = 0;

        switch ($coupon->type) {
            case 'percentage':
                $discountAmount = ($originalAmount * $coupon->discount) / 100;
                break;
            case 'flat':
                $discountAmount = min($coupon->discount, $originalAmount);
                break;
            case 'fixed':
                $discountAmount = max(0, $originalAmount - $coupon->discount);
                return [
                    'valid' => true,
                    'coupon' => $coupon,
                    'discount_amount' => $coupon->discount,
                    'final_amount' => $discountAmount
                ];
        }

        $finalAmount = max(0, $originalAmount - $discountAmount);

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount
        ];
    }
}

// set config email
if (!function_exists('SetConfigEmail')) {
    function SetConfigEmail($user_id = null)
    {
        try {
            if (!empty($user_id)) {
                $company_settings = getCompanyAllSetting($user_id);
            } else if (Auth::check()) {
                $company_settings = getCompanyAllSetting();
            } else {
                $user_id = User::where('type', 'superadmin')->first()->id;
                $company_settings = getCompanyAllSetting($user_id);
            }
            if(empty($company_settings['email_host'])) {
                throw new \Exception(__('Email host is not configured'));
            }

            config([
                'mail.default' => $company_settings['email_driver'] ?? 'smtp',
                'mail.mailers.smtp.host' => $company_settings['email_host'],
                'mail.mailers.smtp.port' => $company_settings['email_port'] ?? 587,
                'mail.mailers.smtp.encryption' => $company_settings['email_encryption'] ?? 'tls',
                'mail.mailers.smtp.username' => $company_settings['email_username'] ?? '',
                'mail.mailers.smtp.password' => $company_settings['email_password'] ?? '',
                'mail.from.address' => $company_settings['email_fromAddress'] ?? 'noreply@example.com',
            ]);
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}

if (! function_exists('isLandingPageEnabled')) {
    function isLandingPageEnabled()
    {
        return admin_setting('landingPageEnabled') === 'on';
    }
}


if (!function_exists('upload_file')) {
    function upload_file($request, $key_name, $name, $path)
    {
        try {

            $config = StorageConfigService::getStorageConfig();

            $file = $request->$key_name;
            $extension = strtolower($file->getClientOriginalExtension());
            $allowed_extensions = explode(',', $config['allowed_file_types']);
            if (empty($extension) || !in_array($extension, $allowed_extensions)) {
                return [
                    'flag' => 0,
                    'msg'  => 'The ' . $key_name . ' must be a file of type: ' .$config['allowed_file_types']. '.',
                ];
            }

            $validation = [
                'mimes:' . $config['allowed_file_types'],
                'max:' . $config['max_file_size_kb'],
            ];

            $validator = \Validator::make($request->all(), [
                $key_name => $validation
            ]);

            if ($validator->fails()) {
                return [
                    'flag' => 0,
                    'msg' => $validator->messages()->first()
                ];
            }

            DynamicStorageService::configureDynamicDisks();

            $activeDisk = StorageConfigService::getActiveDisk();

            // Store file directly to storage
            $file->storeAs( 'media/' . $path, $name, $activeDisk);

            return [
                'flag' => 1,
                'msg' => 'success',
                'url' => $path.'/'.$name
            ];

        } catch (\Exception $e) {
            return [
                'flag' => 0,
                'msg' => $e->getMessage()
            ];
        }
    }
}

if (!function_exists('upload_base64_file')) {
    function upload_base64_file($base64_string, $name, $path)
    {
        try {
            $config = StorageConfigService::getStorageConfig();

            // Decode base64 string
            if (preg_match('/^data:([a-zA-Z0-9][a-zA-Z0-9\/+]*);base64,(.+)$/', $base64_string, $matches)) {
                $mimeType = $matches[1];
                $data = base64_decode($matches[2]);

                // Get extension from mime type
                $mimeExtensions = [
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/svg+xml' => 'svg',
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'application/vnd.ms-excel' => 'xls',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                    'text/plain' => 'txt'
                ];
                $extension = $mimeExtensions[$mimeType] ?? null;

                if (!$extension) {
                    return ['flag' => 0, 'msg' => 'Unsupported file type'];
                }

                $allowed_extensions = explode(',', $config['allowed_file_types']);
                if (!in_array($extension, $allowed_extensions)) {
                    return ['flag' => 0, 'msg' => 'File type not allowed: ' . $extension];
                }

                // Check file size
                $fileSize = strlen($data);
                $maxSizeBytes = $config['max_file_size_kb'] * 1024;
                if ($fileSize > $maxSizeBytes) {
                    return ['flag' => 0, 'msg' => 'File size exceeds limit'];
                }

                DynamicStorageService::configureDynamicDisks();
                $activeDisk = StorageConfigService::getActiveDisk();

                // Add extension to filename if not present
                $finalName = pathinfo($name, PATHINFO_EXTENSION) ? $name : $name . '.' . $extension;

                // Store file
                \Storage::disk($activeDisk)->put('media/' . $path . '/' . $finalName, $data);

                return ['flag' => 1, 'msg' => 'success', 'url' => $path . '/' . $finalName];
            }

            return ['flag' => 0, 'msg' => 'Invalid base64 format'];

        } catch (\Exception $e) {
            return ['flag' => 0, 'msg' => $e->getMessage()];
        }
    }
}

if (!function_exists('delete_file')) {
    function delete_file($url)
    {
        try {
            DynamicStorageService::configureDynamicDisks();
            $activeDisk = StorageConfigService::getActiveDisk();

            $filePath = 'media/' . $url;

            if (\Storage::disk($activeDisk)->exists($filePath)) {
                \Storage::disk($activeDisk)->delete($filePath);
                return [
                    'flag' => 1,
                    'msg' => 'File deleted successfully'
                ];
            }

            return [
                'flag' => 0,
                'msg' => 'File not found'
            ];

        } catch (\Exception $e) {
            return [
                'flag' => 0,
                'msg' => $e->getMessage()
            ];
        }
    }
}

if (!function_exists('ModulePriceByName')) {
    function ModulePriceByName($module_name)
    {
        static $addons = [];
        static $resultArray = [];
        if (empty($resultArray)) {
            $addons = AddOn::all()->toArray();
            foreach ($addons as $item) {
                if (isset($item['module'])) {
                    $resultArray[$item['module']]['monthly_price'] = $item['monthly_price'];
                    $resultArray[$item['module']]['yearly_price'] = $item['yearly_price'];
                }
            }
        }

        $data = $resultArray[$module_name] ?? [];
        $data['monthly_price'] = $data['monthly_price'] ?? 0;
        $data['yearly_price'] = $data['yearly_price'] ?? 0;
        return $data;
    }
}

// module alias name
if (!function_exists('ModuleAliasName')) {
    function ModuleAliasName($moduleName)
    {
        $module = (new Module())->find($moduleName);
        return $module ? ($module->alias ?? $moduleName) : $moduleName;
    }
}

if (!function_exists('parseBrowserData')) {
    function parseBrowserData(string $userAgent): array
    {
        $browser = 'Unknown';
        $os = 'Unknown';
        $deviceType = 'desktop';

        // Browser detection
        if (preg_match('/Chrome\/([0-9.]+)/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge\/([0-9.]+)/', $userAgent)) {
            $browser = 'Edge';
        }

        // OS detection
        if (preg_match('/Windows NT/', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/', $userAgent)) {
            $os = 'Android';
            $deviceType = 'mobile';
        } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
            $deviceType = preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        }

        return [
            'browser_name' => $browser,
            'os_name' => $os,
            'browser_language' => 'en',
            'device_type' => $deviceType,
        ];
    }
}



