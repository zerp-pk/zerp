<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

class PermissionRoleSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        Artisan::call('cache:clear');

        // Create Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('1234'),
                'type' => 'superadmin',
                'lang' => 'en',
                'total_user' => -1,
                'creator_id' => null,
                'created_by' => null
            ]
        );


        // Create Company User
        $company = User::firstOrCreate(
            ['email' => 'company@example.com'],
            [
                'name' => 'Company',
                'email_verified_at' => now(),
                'password' => Hash::make('1234'),
                'mobile_no' => '1234567890',
                'type' => 'company',
                'lang' => 'en',
                'creator_id' => $superAdmin->id,
                'created_by' => $superAdmin->id
            ]
        );

        $permissions = [
            // Dashboard permissions
            ['name' => 'manage-dashboard', 'module' => 'dashboard', 'label' => 'Manage Dashboard'],

            // User management
            ['name' => 'manage-users', 'module' => 'users', 'label' => 'Manage Users'],
            ['name' => 'manage-any-users', 'module' => 'users', 'label' => 'Manage All Users'],
            ['name' => 'manage-own-users', 'module' => 'users', 'label' => 'Manage Own Users'],
            ['name' => 'create-users', 'module' => 'users', 'label' => 'Create Users'],
            ['name' => 'edit-users', 'module' => 'users', 'label' => 'Edit Users'],
            ['name' => 'delete-users', 'module' => 'users', 'label' => 'Delete Users'],
            ['name' => 'change-password-users', 'module' => 'users', 'label' => 'Change Password Users'],
            ['name' => 'impersonate-users', 'module' => 'users', 'label' => 'Login As User'],
            ['name' => 'toggle-status-users', 'module' => 'users', 'label' => 'Change Status Users'],
            ['name' => 'view-login-history', 'module' => 'users', 'label' => 'View Login History'],

            // Role management
            ['name' => 'manage-roles', 'module' => 'roles', 'label' => 'Manage Roles'],
            ['name' => 'create-roles', 'module' => 'roles', 'label' => 'Create Roles'],
            ['name' => 'edit-roles', 'module' => 'roles', 'label' => 'Edit Roles'],
            ['name' => 'delete-roles', 'module' => 'roles', 'label' => 'Delete Roles'],

            // Warehouse management
            ['name' => 'manage-warehouses', 'module' => 'warehouses', 'label' => 'Manage Warehouses'],
            ['name' => 'manage-any-warehouses', 'module' => 'warehouses', 'label' => 'Manage All Warehouses'],
            ['name' => 'manage-own-warehouses', 'module' => 'warehouses', 'label' => 'Manage Own Warehouses'],
            ['name' => 'create-warehouses', 'module' => 'warehouses', 'label' => 'Create Warehouses'],
            ['name' => 'edit-warehouses', 'module' => 'warehouses', 'label' => 'Edit Warehouses'],
            ['name' => 'delete-warehouses', 'module' => 'warehouses', 'label' => 'Delete Warehouses'],

            // Transfer management
            ['name' => 'manage-transfers', 'module' => 'transfers', 'label' => 'Manage Transfers'],
            ['name' => 'manage-any-transfers', 'module' => 'transfers', 'label' => 'Manage All Transfers'],
            ['name' => 'manage-own-transfers', 'module' => 'transfers', 'label' => 'Manage Own Transfers'],
            ['name' => 'create-transfers', 'module' => 'transfers', 'label' => 'Create Transfers'],
            ['name' => 'edit-transfers', 'module' => 'transfers', 'label' => 'Edit Transfers'],
            ['name' => 'delete-transfers', 'module' => 'transfers', 'label' => 'Delete Transfers'],

            // Settings management
            ['name' => 'manage-settings', 'module' => 'settings', 'label' => 'Manage Settings'],
            ['name' => 'edit-settings', 'module' => 'settings', 'label' => 'Edit Settings'],
            ['name' => 'manage-brand-settings', 'module' => 'settings', 'label' => 'Manage Brand Settings'],
            ['name' => 'edit-brand-settings', 'module' => 'settings', 'label' => 'Edit Brand Settings'],
            ['name' => 'manage-company-settings', 'module' => 'settings', 'label' => 'Manage Company Settings'],
            ['name' => 'edit-company-settings', 'module' => 'settings', 'label' => 'Edit Company Settings'],
            ['name' => 'manage-system-settings', 'module' => 'settings', 'label' => 'Manage System Settings'],
            ['name' => 'edit-system-settings', 'module' => 'settings', 'label' => 'Edit System Settings'],
            ['name' => 'manage-currency-settings', 'module' => 'settings', 'label' => 'Manage Currency Settings'],
            ['name' => 'edit-currency-settings', 'module' => 'settings', 'label' => 'Edit Currency Settings'],
            ['name' => 'manage-cache-settings', 'module' => 'settings', 'label' => 'Manage Cache Settings'],
            ['name' => 'clear-cache', 'module' => 'settings', 'label' => 'Clear Cache'],
            ['name' => 'manage-cookie-settings', 'module' => 'settings', 'label' => 'Manage Cookie Settings'],
            ['name' => 'edit-cookie-settings', 'module' => 'settings', 'label' => 'Edit Cookie Settings'],
            ['name' => 'manage-seo-settings', 'module' => 'settings', 'label' => 'Manage SEO Settings'],
            ['name' => 'edit-seo-settings', 'module' => 'settings', 'label' => 'Edit SEO Settings'],
            ['name' => 'manage-storage-settings', 'module' => 'settings', 'label' => 'Manage Storage Settings'],
            ['name' => 'edit-storage-settings', 'module' => 'settings', 'label' => 'Edit Storage Settings'],
            ['name' => 'manage-email-settings', 'module' => 'settings', 'label' => 'Manage Email Settings'],
            ['name' => 'edit-email-settings', 'module' => 'settings', 'label' => 'Edit Email Settings'],
            ['name' => 'test-email', 'module' => 'settings', 'label' => 'Test Email'],
            ['name' => 'manage-bank-transfer-settings', 'module' => 'settings', 'label' => 'Manage Bank Transfer Settings'],
            ['name' => 'edit-bank-transfer-settings', 'module' => 'settings', 'label' => 'Edit Bank Transfer Settings'],
            ['name' => 'manage-bank-transfer-requests', 'module' => 'bank-transfer', 'label' => 'Manage Bank Transfer Requests'],
            ['name' => 'approve-bank-transfer-requests', 'module' => 'bank-transfer', 'label' => 'Approve Bank Transfer Requests'],
            ['name' => 'reject-bank-transfer-requests', 'module' => 'bank-transfer', 'label' => 'Reject Bank Transfer Requests'],
            ['name' => 'delete-bank-transfer-requests', 'module' => 'bank-transfer', 'label' => 'Delete Bank Transfer Requests'],
            ['name' => 'manage-email-notification-settings', 'module' => 'settings', 'label' => 'Manage Email Notification Settings'],
            ['name' => 'manage-pusher-settings', 'module' => 'settings', 'label' => 'Manage Pusher Settings'],
            ['name' => 'edit-pusher-settings', 'module' => 'settings', 'label' => 'Edit Pusher Settings'],

            // Media management
            ['name' => 'manage-media', 'module' => 'media', 'label' => 'Manage Media'],
            ['name' => 'manage-any-media', 'module' => 'media', 'label' => 'Manage All Media'],
            ['name' => 'manage-own-media', 'module' => 'media', 'label' => 'Manage Own Media'],
            ['name' => 'create-media', 'module' => 'media', 'label' => 'Create Media'],
            ['name' => 'download-media', 'module' => 'media', 'label' => 'Download Media'],
            ['name' => 'delete-media', 'module' => 'media', 'label' => 'Delete Media'],
            ['name' => 'manage-media-directories', 'module' => 'media', 'label' => 'Manage Media Directories'],
            ['name' => 'manage-any-media-directories', 'module' => 'media', 'label' => 'Manage All Media Directories'],
            ['name' => 'manage-own-media-directories', 'module' => 'media', 'label' => 'Manage Own Media Directories'],
            ['name' => 'create-media-directories', 'module' => 'media', 'label' => 'Create Media Directories'],
            ['name' => 'edit-media-directories', 'module' => 'media', 'label' => 'Edit Media Directories'],
            ['name' => 'delete-media-directories', 'module' => 'media', 'label' => 'Delete Media Directories'],







            // Helpdesk Categories management
            ['name' => 'manage-helpdesk-categories', 'module' => 'helpdesk-categories', 'label' => 'Manage Helpdesk Categories'],
            ['name' => 'create-helpdesk-categories', 'module' => 'helpdesk-categories', 'label' => 'Create Helpdesk Categories'],
            ['name' => 'edit-helpdesk-categories', 'module' => 'helpdesk-categories', 'label' => 'Edit Helpdesk Categories'],
            ['name' => 'delete-helpdesk-categories', 'module' => 'helpdesk-categories', 'label' => 'Delete Helpdesk Categories'],

            // Helpdesk Tickets management
            ['name' => 'manage-helpdesk-tickets', 'module' => 'helpdesk-tickets', 'label' => 'Manage Helpdesk Tickets'],
            ['name' => 'manage-any-helpdesk-tickets', 'module' => 'helpdesk-tickets', 'label' => 'Manage All Helpdesk Tickets'],
            ['name' => 'manage-own-helpdesk-tickets', 'module' => 'helpdesk-tickets', 'label' => 'Manage Own Helpdesk Tickets'],
            ['name' => 'view-helpdesk-tickets', 'module' => 'helpdesk-tickets', 'label' => 'View Helpdesk Tickets'],
            ['name' => 'create-helpdesk-tickets', 'module' => 'helpdesk-tickets', 'label' => 'Create Helpdesk Tickets'],
            ['name' => 'edit-helpdesk-tickets', 'module' => 'helpdesk-tickets', 'label' => 'Edit Helpdesk Tickets'],
            ['name' => 'delete-helpdesk-tickets', 'module' => 'helpdesk-tickets', 'label' => 'Delete Helpdesk Tickets'],

            // Helpdesk Replies management
            ['name' => 'manage-helpdesk-replies', 'module' => 'helpdesk-replies', 'label' => 'Manage Helpdesk Replies'],
            ['name' => 'create-helpdesk-replies', 'module' => 'helpdesk-replies', 'label' => 'Create Helpdesk Replies'],
            ['name' => 'delete-helpdesk-replies', 'module' => 'helpdesk-replies', 'label' => 'Delete Helpdesk Replies'],

            // Language management
            ['name' => 'manage-languages', 'module' => 'languages', 'label' => 'Manage Languages'],
            ['name' => 'edit-languages', 'module' => 'languages', 'label' => 'Edit Languages'],

            //  // Add-on management
            //  ['name' => 'manage-add-on', 'module' => 'add-on', 'label' => 'Manage Add-on'],
            //  ['name' => 'manage-actions', 'module' => 'add-on', 'label' => 'Manage Actions'],

            // Plan management
            ['name' => 'manage-plans', 'module' => 'plans', 'label' => 'Manage Plans'],
            ['name' => 'manage-any-plans', 'module' => 'plans', 'label' => 'Manage All Plans'],
            ['name' => 'manage-own-plans', 'module' => 'plans', 'label' => 'Manage Own Plans'],
            ['name' => 'view-plans', 'module' => 'plans', 'label' => 'View Plans'],
            ['name' => 'create-plans', 'module' => 'plans', 'label' => 'Create Plans'],
            ['name' => 'edit-plans', 'module' => 'plans', 'label' => 'Edit Plans'],
            ['name' => 'delete-plans', 'module' => 'plans', 'label' => 'Delete Plans'],

            // Coupon management
            ['name' => 'manage-coupons', 'module' => 'coupons', 'label' => 'Manage Coupons'],
            ['name' => 'manage-any-coupons', 'module' => 'coupons', 'label' => 'Manage All Coupons'],
            ['name' => 'manage-own-coupons', 'module' => 'coupons', 'label' => 'Manage Own Coupons'],
            ['name' => 'view-coupons', 'module' => 'coupons', 'label' => 'View Coupons'],
            ['name' => 'create-coupons', 'module' => 'coupons', 'label' => 'Create Coupons'],
            ['name' => 'edit-coupons', 'module' => 'coupons', 'label' => 'Edit Coupons'],
            ['name' => 'delete-coupons', 'module' => 'coupons', 'label' => 'Delete Coupons'],

            // Profile management
            ['name' => 'manage-profile', 'module' => 'profile', 'label' => 'Manage Profile'],
            ['name' => 'edit-profile', 'module' => 'profile', 'label' => 'Edit Profile'],
            ['name' => 'change-password-profile', 'module' => 'profile', 'label' => 'Change Password Profile'],

            // Email Templates management
            ['name' => 'manage-email-templates', 'module' => 'email-templates', 'label' => 'Manage Email Templates'],
            ['name' => 'edit-email-templates', 'module' => 'email-templates', 'label' => 'Edit Email Templates'],

            // Notification Templates management
            ['name' => 'manage-notification-templates', 'module' => 'notification-templates', 'label' => 'Manage Notification Templates'],
            ['name' => 'edit-notification-templates', 'module' => 'notification-templates', 'label' => 'Edit Notification Templates'],

            // Order management
            ['name' => 'manage-orders', 'module' => 'orders', 'label' => 'Manage Orders'],

            // Messenger management
            ['name' => 'manage-messenger', 'module' => 'messenger', 'label' => 'Manage Messenger'],
            ['name' => 'send-messages', 'module' => 'messenger', 'label' => 'Send Messages'],
            ['name' => 'view-messages', 'module' => 'messenger', 'label' => 'View Messages'],
            ['name' => 'edit-messages', 'module' => 'messenger', 'label' => 'Edit Messages'],
            ['name' => 'delete-messages', 'module' => 'messenger', 'label' => 'Delete Messages'],
            ['name' => 'toggle-favorite-messages', 'module' => 'messenger', 'label' => 'Favorite Messages'],
            ['name' => 'toggle-pinned-messages', 'module' => 'messenger', 'label' => 'Pinned Messages'],

            // Purchase Invoice management
            ['name' => 'manage-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'Manage Purchase Invoices'],
            ['name' => 'manage-any-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'Manage All Purchase Invoices'],
            ['name' => 'manage-own-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'Manage Own Purchase Invoices'],
            ['name' => 'view-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'View Purchase Invoices'],
            ['name' => 'create-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'Create Purchase Invoices'],
            ['name' => 'edit-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'Edit Purchase Invoices'],
            ['name' => 'delete-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'Delete Purchase Invoices'],
            ['name' => 'post-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'Post Purchase Invoices'],
            ['name' => 'print-purchase-invoices', 'module' => 'purchase-invoices', 'label' => 'Print Purchase Invoices'],

            // Purchase Return Invoice Management
            ['name' => 'manage-purchase-return-invoices', 'module' => 'purchase-return-invoices', 'label' => 'Manage Purchase Return Invoices'],
            ['name' => 'manage-any-purchase-return-invoices', 'module' => 'purchase-return-invoices', 'label' => 'Manage All Purchase Return Invoices'],
            ['name' => 'manage-own-purchase-return-invoices', 'module' => 'purchase-return-invoices', 'label' => 'Manage Own Purchase Return Invoices'],
            ['name' => 'view-purchase-return-invoices', 'module' => 'purchase-return-invoices', 'label' => 'View Purchase Return Invoices'],
            ['name' => 'create-purchase-return-invoices', 'module' => 'purchase-return-invoices', 'label' => 'Create Purchase Return Invoices'],
            ['name' => 'delete-purchase-return-invoices', 'module' => 'purchase-return-invoices', 'label' => 'Delete Purchase Return Invoices'],
            ['name' => 'approve-purchase-returns-invoices', 'module' => 'purchase-return-invoices', 'label' => 'Approve Purchase Return Invoices'],
            ['name' => 'complete-purchase-returns-invoices', 'module' => 'purchase-return-invoices', 'label' => 'Complete Purchase Return Invoices'],

            // Sales Invoice management
            ['name' => 'manage-sales-invoices', 'module' => 'sales-invoices', 'label' => 'Manage Sales Invoices'],
            ['name' => 'manage-any-sales-invoices', 'module' => 'sales-invoices', 'label' => 'Manage All Sales Invoices'],
            ['name' => 'manage-own-sales-invoices', 'module' => 'sales-invoices', 'label' => 'Manage Own Sales Invoices'],
            ['name' => 'view-sales-invoices', 'module' => 'sales-invoices', 'label' => 'View Sales Invoices'],
            ['name' => 'create-sales-invoices', 'module' => 'sales-invoices', 'label' => 'Create Sales Invoices'],
            ['name' => 'edit-sales-invoices', 'module' => 'sales-invoices', 'label' => 'Edit Sales Invoices'],
            ['name' => 'delete-sales-invoices', 'module' => 'sales-invoices', 'label' => 'Delete Sales Invoices'],
            ['name' => 'post-sales-invoices', 'module' => 'sales-invoices', 'label' => 'Post Sales Invoices'],
            ['name' => 'print-sales-invoices', 'module' => 'sales-invoices', 'label' => 'Print Sales Invoices'],

            // Sales Return Invoice Management
            ['name' => 'manage-sales-return-invoices', 'module' => 'sales-return-invoices', 'label' => 'Manage Sales Return Invoices'],
            ['name' => 'manage-any-sales-return-invoices', 'module' => 'sales-return-invoices', 'label' => 'Manage All Sales Return Invoices'],
            ['name' => 'manage-own-sales-return-invoices', 'module' => 'sales-return-invoices', 'label' => 'Manage Own Sales Return Invoices'],
            ['name' => 'view-sales-return-invoices', 'module' => 'sales-return-invoices', 'label' => 'View Sales Return Invoices'],
            ['name' => 'create-sales-return-invoices', 'module' => 'sales-return-invoices', 'label' => 'Create Sales Return Invoices'],
            ['name' => 'delete-sales-return-invoices', 'module' => 'sales-return-invoices', 'label' => 'Delete Sales Return Invoices'],
            ['name' => 'approve-sales-returns-invoices', 'module' => 'sales-return-invoices', 'label' => 'Approve Sales Return Invoices'],
            ['name' => 'complete-sales-returns-invoices', 'module' => 'sales-return-invoices', 'label' => 'Complete Sales Return Invoices'],

             // Sales Proposal Management
            ['name' => 'manage-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Manage Sales Proposals'],
            ['name' => 'manage-any-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Manage All Sales Proposals'],
            ['name' => 'manage-own-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Manage Own Sales Proposals'],
            ['name' => 'view-sales-proposals', 'module' => 'sales-proposals', 'label' => 'View Sales Proposals'],
            ['name' => 'create-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Create Sales Proposals'],
            ['name' => 'edit-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Edit Sales Proposals'],
            ['name' => 'delete-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Delete Sales Proposals'],
            ['name' => 'print-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Print Sales Proposals'],
            ['name' => 'sent-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Sent Sales Proposals'],
            ['name' => 'accept-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Accept Sales Proposals'],
            ['name' => 'convert-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Convert Sales Proposals'],
            ['name' => 'reject-sales-proposals', 'module' => 'sales-proposals', 'label' => 'Reject Sales Proposals'],
        ];

        $roles = [
            'superadmin' => [
                'label' => 'Super Admin',
                'permissions' => [
                    'manage-dashboard',
                    'manage-users', 'manage-any-users', 'manage-own-users', 'create-users', 'edit-users', 'delete-users', 'change-password-users', 'toggle-status-users', 'impersonate-users', 'view-login-history',
                    'manage-helpdesk-tickets', 'manage-any-helpdesk-tickets', 'view-helpdesk-tickets', 'create-helpdesk-tickets', 'edit-helpdesk-tickets','delete-helpdesk-tickets',
                    'manage-helpdesk-categories', 'create-helpdesk-categories', 'edit-helpdesk-categories', 'delete-helpdesk-categories',
                    'manage-helpdesk-replies', 'create-helpdesk-replies', 'delete-helpdesk-replies',
                    'manage-settings', 'edit-settings', 'manage-brand-settings', 'edit-brand-settings', 'manage-system-settings', 'edit-system-settings', 'manage-currency-settings', 'edit-currency-settings', 'manage-cache-settings', 'clear-cache', 'manage-cookie-settings', 'edit-cookie-settings', 'manage-seo-settings', 'edit-seo-settings', 'manage-storage-settings', 'edit-storage-settings', 'manage-email-settings', 'edit-email-settings', 'test-email','manage-email-notification-settings','manage-pusher-settings', 'edit-pusher-settings','manage-notification-templates','edit-notification-templates','manage-bank-transfer-settings', 'edit-bank-transfer-settings',
                    'manage-languages', 'edit-languages', 'manage-media', 'manage-own-media', 'create-media', 'download-media', 'delete-media', 'manage-media-directories', 'manage-own-media-directories', 'manage-any-media-directories', 'create-media-directories', 'edit-media-directories', 'delete-media-directories',
                    'manage-email-templates', 'edit-email-templates',
                    'manage-plans', 'manage-any-plans', 'manage-own-plans', 'view-plans', 'create-plans', 'edit-plans', 'delete-plans',
                    'manage-coupons', 'manage-any-coupons', 'manage-own-coupons', 'view-coupons', 'create-coupons', 'edit-coupons', 'delete-coupons',
                    'manage-bank-transfer-requests', 'approve-bank-transfer-requests', 'reject-bank-transfer-requests','delete-bank-transfer-requests',
                    'manage-profile', 'edit-profile', 'change-password-profile',
                    'manage-orders', 'view-orders'
                ]
            ],
            'company' => [
                'label' => 'Company',
                'permissions' => [
                    'manage-dashboard',
                    'manage-users', 'manage-any-users', 'manage-own-users', 'create-users', 'edit-users', 'delete-users', 'change-password-users', 'toggle-status-users', 'impersonate-users', 'view-login-history',
                    'manage-roles', 'view-roles', 'create-roles', 'edit-roles', 'delete-roles',
                    'manage-warehouses', 'manage-any-warehouses', 'manage-own-warehouses', 'create-warehouses', 'edit-warehouses', 'delete-warehouses',
                    'manage-transfers', 'manage-any-transfers', 'manage-own-transfers', 'create-transfers', 'edit-transfers', 'delete-transfers',



                    'manage-helpdesk-tickets', 'manage-own-helpdesk-tickets', 'view-helpdesk-tickets', 'create-helpdesk-tickets',  'edit-helpdesk-tickets',
                    'manage-helpdesk-replies', 'create-helpdesk-replies',
                    'manage-settings', 'edit-settings', 'manage-brand-settings', 'edit-brand-settings', 'manage-company-settings', 'edit-company-settings', 'manage-system-settings', 'edit-system-settings', 'manage-email-settings', 'edit-email-settings', 'test-email','manage-email-notification-settings',
                    'manage-currency-settings', 'edit-currency-settings', 'manage-media', 'manage-any-media', 'manage-own-media', 'create-media', 'download-media', 'delete-media', 'manage-media-directories', 'manage-own-media-directories', 'manage-any-media-directories', 'create-media-directories', 'edit-media-directories', 'delete-media-directories',
                    'manage-plans', 'manage-any-plans', 'manage-own-plans', 'view-plans', 'create-plans', 'edit-plans', 'delete-plans',
                    'manage-bank-transfer-requests', 'delete-bank-transfer-requests',
                    'manage-orders', 'view-orders',
                    'manage-profile', 'edit-profile', 'change-password-profile',
                    'manage-messenger', 'send-messages', 'view-messages', 'edit-messages', 'delete-messages', 'toggle-favorite-messages', 'toggle-pinned-messages',
                    'manage-purchase-invoices', 'manage-any-purchase-invoices', 'manage-own-purchase-invoices', 'view-purchase-invoices', 'create-purchase-invoices', 'edit-purchase-invoices', 'delete-purchase-invoices', 'post-purchase-invoices', 'print-purchase-invoices',
                    'manage-purchase-return-invoices','manage-any-purchase-return-invoices','manage-own-purchase-return-invoices','view-purchase-return-invoices','create-purchase-return-invoices','edit-purchase-return-invoices','delete-purchase-return-invoices','approve-purchase-returns-invoices', 'complete-purchase-returns-invoices',
                    'manage-sales-invoices', 'manage-any-sales-invoices', 'manage-own-sales-invoices', 'view-sales-invoices', 'create-sales-invoices', 'edit-sales-invoices', 'delete-sales-invoices', 'post-sales-invoices', 'print-sales-invoices',
                    'manage-sales-return-invoices','manage-any-sales-return-invoices','manage-own-sales-return-invoices','view-sales-return-invoices','create-sales-return-invoices','delete-sales-return-invoices','approve-sales-returns-invoices', 'complete-sales-returns-invoices',
                    'manage-sales-proposals','manage-any-sales-proposals','manage-own-sales-proposals','view-sales-proposals','create-sales-proposals','edit-sales-proposals','delete-sales-proposals','print-sales-proposals','sent-sales-proposals','accept-sales-proposals','convert-sales-proposals','reject-sales-proposals',
                ]
            ]
        ];

        // Create permissions
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'general',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }

        // Create roles and assign permissions
        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                [
                    'label' => $roleData['label'],
                    'editable' => false,
                    'created_by' => $superAdmin->id
                ]
            );

            // Get permission objects by name
            $permissionObjects = Permission::whereIn('name', $roleData['permissions'])->get();
            $role->givePermissionTo($permissionObjects);
        }

        // Assign super admin role
        $superAdmin->assignRole('superadmin');

        // Assign company role
        $company->assignRole('company');

        // Make Company's role
        User::MakeRole($company->id);
    }
}
