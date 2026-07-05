<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission as ModelsPermission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasRoles, HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
            'name',
            'email',
            'mobile_no',
            'email_verified_at',
            'password',
            'type',
            'avatar',
            'lang',
            'active_plan',
            'plan_expire_date',
            'trial_expire_date',
            'is_trial_done',
            'total_user',
            'commission_amount',
            'storage_limit',
            'is_disable',
            'is_enable_login',
            'creator_id',
            'created_by',
            'active_status',
            'last_seen_at',
            'slug',
            'avatar_media_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->slug)) {
                $user->slug = static::generateUniqueSlug($user->name);
            }
        });
    }

    public static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public static $superadmin_activated_module = [
        'ProductService',
        'LandingPage',
    ];

    public  $not_emp_type = [
        'super admin',
        'company',
        'client',
        'vendor',
        'doctor',
        'student',
        'parent'
    ];

    public function scopeEmp($query, $additionalTypes = [], $includeTypes = [])
    {
        $excludeTypes = array_diff(array_merge($this->not_emp_type, $additionalTypes), $includeTypes);
        return $query->whereNotIn('type', $excludeTypes);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function avatarMedia(): BelongsTo
    {
        return $this->belongsTo(\Spatie\MediaLibrary\MediaCollections\Models\Media::class, 'avatar_media_id');
    }

    public static function CompanySetting($user_id)
    {
        $company_settings = [
            // Brand Settings
            'logo_light' => admin_setting('logo_light'),
            'logo_dark' => admin_setting('logo_dark'),
            'favicon' => admin_setting('favicon'),
            'titleText' => admin_setting('titleText'),
            'footerText' => admin_setting('footerText'),
            'sidebarVariant' => admin_setting('sidebarVariant'),
            'sidebarStyle' => admin_setting('sidebarStyle'),
            'layoutDirection' => admin_setting('layoutDirection'),
            'themeMode' => admin_setting('themeMode'),
            'themeColor' => admin_setting('themeColor'),
            'customColor' => admin_setting('customColor'),

            // System Settings
            'defaultLanguage' => admin_setting('defaultLanguage'),
            'dateFormat' => admin_setting('dateFormat'),
            'timeFormat' => admin_setting('timeFormat'),
            'calendarStartDay' => admin_setting('calendarStartDay'),
            'enableRegistration' => admin_setting('enableRegistration') === 'on' ? 'on' : 'off',
            'enableEmailVerification' => admin_setting('enableEmailVerification') === 'on' ? 'on' : 'off',
            'landingPageEnabled' => admin_setting('landingPageEnabled') === 'on' ? 'on' : 'off',
            'termsConditionsUrl' =>admin_setting('termsConditionsUrl'),

            // Currency Settings
            'defaultCurrency' => admin_setting('defaultCurrency'),
            'currencySymbol' => admin_setting('currencySymbol'),
            'currency_format' => admin_setting('currency_format'),
            'decimalFormat' => admin_setting('decimalFormat'),
            'decimalSeparator' => admin_setting('decimalSeparator'),
            'thousandsSeparator' => admin_setting('thousandsSeparator'),
            'floatNumber' => admin_setting('floatNumber'),
            'currencySymbolSpace' => admin_setting('currencySymbolSpace'),
            'currencySymbolPosition' => admin_setting('currencySymbolPosition'),
        ];
        foreach($company_settings as $key => $value){
            if (!company_setting($key,$user_id)) {
                setSetting($key, $value, $user_id);
            }
        }
    }

    public static function MakeRole($userId)
    {
        // Create staff role
        $staffRole = Role::where('name','staff')->where('created_by',$userId)->where('guard_name','web')->first();
        if(empty($staffRole))
        {
            $staffRole                   = new Role();
            $staffRole->name             = 'staff';
            $staffRole->guard_name       = 'web';
            $staffRole->label            = 'Staff';
            $staffRole->editable         = false;
            $staffRole->created_by       = $userId;
            $staffRole->save();

            $permissions = ModelsPermission::whereIn('name', [
                'manage-dashboard',
                'manage-media',
                'manage-own-media',
                'create-media',
                'download-media',
                'delete-media',
                'manage-media-directories',
                'manage-own-media-directories',
                'create-media-directories',
                'edit-media-directories',
                'delete-media-directories',
                'manage-profile',
                'edit-profile',
                'change-password-profile',
                'manage-messenger',
                'send-messages',
                'view-messages',
                'toggle-favorite-messages',
                'toggle-pinned-messages'
            ])->get();

            $staffRole->givePermissionTo($permissions);
        }

        // Create client role
        $clientRole = Role::where('name','client')->where('created_by',$userId)->where('guard_name','web')->first();
        if(empty($clientRole))
        {
            $clientRole                   = new Role();
            $clientRole->name             = 'client';
            $clientRole->guard_name       = 'web';
            $clientRole->label            = 'Client';
            $clientRole->editable         = false;
            $clientRole->created_by       = $userId;
            $clientRole->save();

            $permissions = ModelsPermission::whereIn('name', [
                'manage-dashboard',
                'manage-media',
                'manage-own-media',
                'create-media',
                'download-media',
                'delete-media',
                'manage-media-directories',
                'manage-own-media-directories',
                'create-media-directories',
                'edit-media-directories',
                'delete-media-directories',
                'manage-profile',
                'edit-profile',
                'change-password-profile',
                'manage-messenger',
                'send-messages',
                'view-messages',
                'toggle-favorite-messages',
                'toggle-pinned-messages',
                'manage-sales-invoices',
                'manage-own-sales-invoices',
                'view-sales-invoices',
                'print-sales-invoices',
                'manage-sales-return-invoices',
                'manage-own-sales-return-invoices',
                'view-sales-return-invoices',
                'manage-sales-proposals',
                'manage-own-sales-proposals',
                'view-sales-proposals',
                'print-sales-proposals',
                'accept-sales-proposals',
                'reject-sales-proposals',
            ])->get();

            $clientRole->givePermissionTo($permissions);
        }

        // Create vendor role
        $vendorRole = Role::where('name','vendor')->where('created_by',$userId)->where('guard_name','web')->first();
        if(empty($vendorRole))
        {
            $vendorRole                   = new Role();
            $vendorRole->name             = 'vendor';
            $vendorRole->guard_name       = 'web';
            $vendorRole->label            = 'Vendor';
            $vendorRole->editable         = false;
            $vendorRole->created_by       = $userId;
            $vendorRole->save();

            $permissions = ModelsPermission::whereIn('name', [
                'manage-dashboard',
                'manage-media',
                'manage-own-media',
                'create-media',
                'download-media',
                'delete-media',
                'manage-media-directories',
                'manage-own-media-directories',
                'create-media-directories',
                'edit-media-directories',
                'delete-media-directories',
                'manage-profile',
                'edit-profile',
                'change-password-profile',
                'manage-messenger',
                'send-messages',
                'view-messages',
                'toggle-favorite-messages',
                'toggle-pinned-messages',
                'manage-purchase-invoices',
                'manage-own-purchase-invoices',
                'view-purchase-invoices',
                'print-purchase-invoices',
                'manage-purchase-return-invoices',
                'manage-own-purchase-return-invoices',
                'view-purchase-return-invoices',
            ])->get();

            $vendorRole->givePermissionTo($permissions);
        }
    }
}
