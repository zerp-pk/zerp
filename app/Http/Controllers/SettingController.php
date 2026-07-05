<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Mail\TestMail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cookie;

class SettingController extends Controller
{
    public function index()
    {
        if(Auth::user()->can('manage-settings'))
        {
            $globalSettings = getCompanyAllSetting();
            $emailProviders = config('email-providers');

            if(Auth::user()->hasRole('superadmin'))
            {
               $notifications = \App\Models\Notification::where('type','mail')->where('module', 'general')->get()->groupBy('module');
            }
            else
            {
               $notifications = \App\Models\Notification::where('type','mail')->get()->groupBy('module');
            }



            return Inertia::render('settings/index', [
                'globalSettings' => $globalSettings,
                'emailProviders' => $emailProviders,
                'notifications' => $notifications,
                'cacheSize' => $this->getCacheSize()
            ]);
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateBrandSettings(Request $request)
    {
        if(Auth::user()->can('edit-brand-settings'))
        {
            $request->validate([
                'settings.logo_dark' => 'nullable|string|max:500',
                'settings.logo_light' => 'nullable|string|max:500',
                'settings.favicon' => 'nullable|string|max:500',
                'settings.titleText' => 'required|string|max:255',
                'settings.footerText' => 'required|string|max:500',
                'settings.sidebarVariant' => 'nullable|string|max:50',
                'settings.sidebarStyle' => 'nullable|string|max:50',
                'settings.layoutDirection' => 'nullable|string|max:50',
                'settings.themeMode' => 'nullable|string|max:50',
                'settings.themeColor' => 'nullable|string|max:50',
                'settings.customColor' => 'nullable|string|max:50',
            ], [
                'settings.titleText.required' => __('Title text is required.'),
                'settings.titleText.string' => __('Title text must be a valid string.'),
                'settings.titleText.max' => __('Title text must not exceed 255 characters.'),
                'settings.footerText.required' => __('Footer text is required.'),
                'settings.footerText.string' => __('Footer text must be a valid string.'),
                'settings.footerText.max' => __('Footer text must not exceed 500 characters.'),
                'settings.logo_dark.string' => __('Dark logo must be a valid string.'),
                'settings.logo_light.string' => __('Light logo must be a valid string.'),
                'settings.favicon.string' => __('Favicon must be a valid string.'),
                'settings.sidebarVariant.string' => __('Sidebar variant must be a valid string.'),
                'settings.sidebarStyle.string' => __('Sidebar style must be a valid string.'),
                'settings.layoutDirection.string' => __('Layout direction must be a valid string.'),
                'settings.themeMode.string' => __('Theme mode must be a valid string.'),
                'settings.themeColor.string' => __('Theme color must be a valid string.'),
                'settings.customColor.string' => __('Custom color must be a valid string.'),
                'settings.logo_dark.max' => __('Dark logo path is too long.'),
                'settings.logo_light.max' => __('Light logo path is too long.'),
                'settings.favicon.max' => __('Favicon path is too long.'),
                'settings.sidebarVariant.max' => __('Sidebar variant must not exceed 50 characters.'),
                'settings.sidebarStyle.max' => __('Sidebar style must not exceed 50 characters.'),
                'settings.layoutDirection.max' => __('Layout direction must not exceed 50 characters.'),
                'settings.themeMode.max' => __('Theme mode must not exceed 50 characters.'),
                'settings.themeColor.max' => __('Theme color must not exceed 50 characters.'),
                'settings.customColor.max' => __('Custom color must not exceed 50 characters.'),
            ]);

            $settings = $request->input('settings');
            
            if (config('app.is_demo')) {
                // In demo mode, only handle theme settings via cookies
                $themeKeys = [
                    'theme_color' => 'themeColor',
                    'sidebar_variant' => 'sidebarVariant',
                    'sidebar_style' => 'sidebarStyle',
                    'layout_direction' => 'layoutDirection', 
                    'theme_mode' => 'themeMode',
                    'custom_color' => 'customColor'
                ];

                $cookieData = [];
                foreach ($themeKeys as $cookieKey => $settingKey) {
                    if (isset($settings[$settingKey])) {
                        $cookieData[$cookieKey] = $settings[$settingKey];
                    }
                }

                if (!empty($cookieData)) {
                    // Get existing cookie data to merge if needed, or start fresh
                    $cookieName = 'theme_settings_' . creatorId();
                    $existingCookie = \Cookie::get($cookieName);
                    $existingData = $existingCookie ? json_decode($existingCookie, true) : [];
                    
                    if(is_array($existingData)) {
                        $cookieData = array_merge($existingData, $cookieData);
                    }
                    
                    $cookieValue = json_encode($cookieData);
                    $cookie = \Cookie::make($cookieName, $cookieValue, 60 * 24 * 30); // 1 month

                    return redirect()->back()->with('success', __('Theme settings saved in demo mode successfully.'))->withCookie($cookie);
                }

                return redirect()->back()->with('success', __('Theme settings saved in demo mode successfully.'));
            }

            if (isset($settings['logo_dark']) ) {
                $settings['logo_dark'] = basename($settings['logo_dark']);
            }

            if (isset($settings['logo_light']) ) {
                $settings['logo_light'] = basename($settings['logo_light']);
            }

            if (isset($settings['favicon']) ) {
                $settings['favicon'] = basename($settings['favicon']);
            }

            foreach ($settings as $key => $value) {
                setSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Brand settings save successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateCompanySettings(Request $request)
    {
        if(Auth::user()->can('edit-company-settings'))
        {
            $request->validate([
                'settings.company_name' => 'nullable|string|max:255',
                'settings.company_address' => 'nullable|string|max:500',
                'settings.company_city' => 'nullable|string|max:100',
                'settings.company_state' => 'nullable|string|max:100',
                'settings.company_country' => 'nullable|string|max:100',
                'settings.company_zipcode' => 'nullable|string|max:20',
                'settings.company_telephone' => 'nullable|string|max:20',
                'settings.company_email_from_name' => 'nullable|string|max:255',
                'settings.registration_number' => 'nullable|string|max:100',
                'settings.company_email' => 'nullable|email|max:255',
            ], [
                'settings.company_name.string' => __('Company name must be a valid string.'),
                'settings.company_name.max' => __('Company name must not exceed 255 characters.'),
                'settings.company_address.string' => __('Company address must be a valid string.'),
                'settings.company_city.string' => __('City must be a valid string.'),
                'settings.company_state.string' => __('State must be a valid string.'),
                'settings.company_country.string' => __('Country must be a valid string.'),
                'settings.company_zipcode.string' => __('Zipcode must be a valid string.'),
                'settings.company_telephone.string' => __('Telephone must be a valid string.'),
                'settings.company_email_from_name.string' => __('Email from name must be a valid string.'),
                'settings.registration_number.string' => __('Registration number must be a valid string.'),
                'settings.company_address.max' => __('Company address must not exceed 500 characters.'),
                'settings.company_city.max' => __('City must not exceed 100 characters.'),
                'settings.company_state.max' => __('State must not exceed 100 characters.'),
                'settings.company_country.max' => __('Country must not exceed 100 characters.'),
                'settings.company_zipcode.max' => __('Zipcode must not exceed 20 characters.'),
                'settings.company_telephone.max' => __('Telephone must not exceed 20 characters.'),
                'settings.company_email_from_name.max' => __('Email from name must not exceed 255 characters.'),
                'settings.registration_number.max' => __('Registration number must not exceed 100 characters.'),
                'settings.company_email.email' => __('Please enter a valid email address.'),
                'settings.company_email.max' => __('Email must not exceed 255 characters.'),
            ]);

            $settings = $request->input('settings');

            foreach ($settings as $key => $value) {
                setSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Company settings save successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateSystemSettings(Request $request)
    {
        if(Auth::user()->can('edit-system-settings'))
        {
            $request->validate([
                'settings.defaultLanguage' => 'required|string|max:10',
                'settings.dateFormat' => 'required|string|max:50',
                'settings.timeFormat' => 'required|string|max:50',
                'settings.calendarStartDay' => 'required|string|max:20',
                'settings.enableRegistration' => 'required|string|in:on,off',
                'settings.enableEmailVerification' => 'required|string|in:on,off',
                'settings.landingPageEnabled' => 'required|string|in:on,off',
                'settings.termsConditionsUrl' => 'nullable|url|max:500',
            ], [
                'settings.defaultLanguage.required' => __('Default language is required.'),
                'settings.defaultLanguage.string' => __('Default language must be a valid string.'),
                'settings.defaultLanguage.max' => __('Default language must not exceed 10 characters.'),
                'settings.dateFormat.string' => __('Date format must be a valid string.'),
                'settings.timeFormat.string' => __('Time format must be a valid string.'),
                'settings.calendarStartDay.string' => __('Calendar start day must be a valid string.'),
                'settings.dateFormat.required' => __('Date format is required.'),
                'settings.dateFormat.max' => __('Date format must not exceed 50 characters.'),
                'settings.timeFormat.required' => __('Time format is required.'),
                'settings.timeFormat.max' => __('Time format must not exceed 50 characters.'),
                'settings.calendarStartDay.required' => __('Calendar start day is required.'),
                'settings.calendarStartDay.max' => __('Calendar start day must not exceed 20 characters.'),
                'settings.enableRegistration.required' => __('Registration setting is required.'),
                'settings.enableRegistration.in' => __('Registration setting must be on or off.'),
                'settings.enableEmailVerification.required' => __('Email verification setting is required.'),
                'settings.enableEmailVerification.in' => __('Email verification setting must be on or off.'),
                'settings.landingPageEnabled.required' => __('Landing page setting is required.'),
                'settings.landingPageEnabled.in' => __('Landing page setting must be on or off.'),
                'settings.termsConditionsUrl.url' => __('Please enter a valid URL for terms and conditions.'),
                'settings.termsConditionsUrl.max' => __('Terms and conditions URL must not exceed 500 characters.'),
            ]);

            $settings = $request->input('settings');

            foreach ($settings as $key => $value) {
                setSetting($key, $value);
            }

            return redirect()->back()->with('success', __('System settings save successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateCurrencySettings(Request $request)
    {
        if(Auth::user()->can('edit-system-settings'))
        {
            $request->validate([
                'settings.defaultCurrency' => 'required|string|max:10',
                'settings.decimalFormat' => 'required|string|max:10',
                'settings.decimalSeparator' => 'required|string|max:5',
                'settings.thousandsSeparator' => 'required|string|max:10',
                'settings.floatNumber' => 'required|boolean',
                'settings.currencySymbolSpace' => 'required|boolean',
                'settings.currencySymbolPosition' => 'required|string|max:10',
            ], [
                'settings.defaultCurrency.required' => __('Default currency is required.'),
                'settings.defaultCurrency.string' => __('Default currency must be a valid string.'),
                'settings.defaultCurrency.max' => __('Default currency must not exceed 10 characters.'),
                'settings.decimalFormat.string' => __('Decimal format must be a valid string.'),
                'settings.decimalSeparator.string' => __('Decimal separator must be a valid string.'),
                'settings.thousandsSeparator.string' => __('Thousands separator must be a valid string.'),
                'settings.currencySymbolPosition.string' => __('Currency symbol position must be a valid string.'),
                'settings.decimalFormat.required' => __('Decimal format is required.'),
                'settings.decimalFormat.max' => __('Decimal format must not exceed 10 characters.'),
                'settings.decimalSeparator.required' => __('Decimal separator is required.'),
                'settings.decimalSeparator.max' => __('Decimal separator must not exceed 5 characters.'),
                'settings.thousandsSeparator.required' => __('Thousands separator is required.'),
                'settings.thousandsSeparator.max' => __('Thousands separator must not exceed 10 characters.'),
                'settings.floatNumber.required' => __('Float number setting is required.'),
                'settings.floatNumber.boolean' => __('Float number must be true or false.'),
                'settings.currencySymbolSpace.required' => __('Currency symbol space setting is required.'),
                'settings.currencySymbolSpace.boolean' => __('Currency symbol space must be true or false.'),
                'settings.currencySymbolPosition.required' => __('Currency symbol position is required.'),
                'settings.currencySymbolPosition.max' => __('Currency symbol position must not exceed 10 characters.'),
            ]);

            $settings = $request->input('settings');

            foreach ($settings as $key => $value) {
                setSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Currency settings save successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function clearCache(Request $request)
    {
        if(Auth::user()->can('clear-cache'))
        {
            try {
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');

                return redirect()->back()->with('success', __('Cache cleared successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to clear cache: ') . $e->getMessage());
            }
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function optimizeSite(Request $request)
    {
        if(Auth::user()->can('clear-cache'))
        {
            try {
                Artisan::call('optimize:clear');
                Artisan::queue('optimize');

                return redirect()->back()->with('success', __('Site optimized successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to optimize site: ') . $e->getMessage());
            }
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function getCacheSize()
    {
        try {
            $cacheSize = 0;
            $cachePaths = [
                storage_path('framework/cache'),
                storage_path('framework/views'),
                storage_path('framework/sessions')
            ];

            foreach ($cachePaths as $cachePath) {
                if (File::exists($cachePath)) {
                    $files = File::allFiles($cachePath);
                    foreach ($files as $file) {
                        $cacheSize += $file->getSize();
                    }
                }
            }

            return number_format($cacheSize / 1024 / 1024, 2);
        } catch (\Exception $e) {
            return '0.00';
        }
    }

    public function updateCookieSettings(Request $request)
    {
        if(Auth::user()->can('edit-cookie-settings'))
        {
            $request->validate([
                'settings.enableLogging' => 'required|boolean',
                'settings.strictlyNecessaryCookies' => 'required|boolean',
                'settings.cookieTitle' => 'required|string|max:255',
                'settings.strictlyCookieTitle' => 'required|string|max:255',
                'settings.cookieDescription' => 'required|string|max:1000',
                'settings.strictlyCookieDescription' => 'required|string|max:1000',
                'settings.contactUsDescription' => 'required|string|max:1000',
                'settings.contactUsUrl' => 'required|url|max:500',
            ], [
                'settings.enableLogging.required' => __('Enable logging setting is required.'),
                'settings.enableLogging.boolean' => __('Enable logging must be true or false.'),
                'settings.strictlyNecessaryCookies.required' => __('Strictly necessary cookies setting is required.'),
                'settings.strictlyNecessaryCookies.boolean' => __('Strictly necessary cookies must be true or false.'),
                'settings.cookieTitle.required' => __('Cookie title is required.'),
                'settings.cookieTitle.string' => __('Cookie title must be a valid string.'),
                'settings.cookieTitle.max' => __('Cookie title must not exceed 255 characters.'),
                'settings.strictlyCookieTitle.string' => __('Strictly cookie title must be a valid string.'),
                'settings.cookieDescription.string' => __('Cookie description must be a valid string.'),
                'settings.strictlyCookieDescription.string' => __('Strictly cookie description must be a valid string.'),
                'settings.contactUsDescription.string' => __('Contact us description must be a valid string.'),
                'settings.strictlyCookieTitle.required' => __('Strictly cookie title is required.'),
                'settings.strictlyCookieTitle.max' => __('Strictly cookie title must not exceed 255 characters.'),
                'settings.cookieDescription.required' => __('Cookie description is required.'),
                'settings.cookieDescription.max' => __('Cookie description must not exceed 1000 characters.'),
                'settings.strictlyCookieDescription.required' => __('Strictly cookie description is required.'),
                'settings.strictlyCookieDescription.max' => __('Strictly cookie description must not exceed 1000 characters.'),
                'settings.contactUsDescription.required' => __('Contact us description is required.'),
                'settings.contactUsDescription.max' => __('Contact us description must not exceed 1000 characters.'),
                'settings.contactUsUrl.required' => __('Contact us URL is required.'),
                'settings.contactUsUrl.url' => __('Please enter a valid contact us URL.'),
                'settings.contactUsUrl.max' => __('Contact us URL must not exceed 500 characters.'),
            ]);

            $settings = $request->input('settings');

            foreach ($settings as $key => $value) {
                setSetting($key, $value);
            }

            return redirect()->back()->with('success', __('Cookie settings save successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateEmailSettings(Request $request)
    {
        if(Auth::user()->can('edit-email-settings'))
        {
            $request->validate([
                'settings.provider' => 'required|string|max:50',
                'settings.driver' => 'required|string|max:50',
                'settings.host' => 'required|string|max:255',
                'settings.port' => 'required|string|max:10',
                'settings.username' => 'required|string|max:255',
                'settings.password' => 'nullable|string|max:255',
                'settings.encryption' => 'required|string|max:10',
                'settings.fromAddress' => 'required|email|max:255',
            ], [
                'settings.provider.required' => __('Email provider is required.'),
                'settings.provider.string' => __('Email provider must be a valid string.'),
                'settings.provider.max' => __('Email provider must not exceed 50 characters.'),
                'settings.driver.string' => __('Email driver must be a valid string.'),
                'settings.host.string' => __('SMTP host must be a valid string.'),
                'settings.port.string' => __('SMTP port must be a valid string.'),
                'settings.username.string' => __('SMTP username must be a valid string.'),
                'settings.password.string' => __('SMTP password must be a valid string.'),
                'settings.encryption.string' => __('Email encryption must be a valid string.'),
                'settings.driver.required' => __('Email driver is required.'),
                'settings.driver.max' => __('Email driver must not exceed 50 characters.'),
                'settings.host.required' => __('SMTP host is required.'),
                'settings.host.max' => __('SMTP host must not exceed 255 characters.'),
                'settings.port.required' => __('SMTP port is required.'),
                'settings.port.max' => __('SMTP port must not exceed 10 characters.'),
                'settings.username.required' => __('SMTP username is required.'),
                'settings.username.max' => __('SMTP username must not exceed 255 characters.'),
                'settings.password.max' => __('SMTP password must not exceed 255 characters.'),
                'settings.encryption.required' => __('Email encryption is required.'),
                'settings.encryption.max' => __('Email encryption must not exceed 10 characters.'),
                'settings.fromAddress.required' => __('From email address is required.'),
                'settings.fromAddress.email' => __('Please enter a valid from email address.'),
                'settings.fromAddress.max' => __('From email address must not exceed 255 characters.'),
            ]);

            $settings = $request->input('settings');

            foreach ($settings as $key => $value) {
                setSetting('email_' . $key, $value, null, false);
            }

            return redirect()->back()->with('success', __('Email settings save successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function testEmail(Request $request)
    {
        if(Auth::user()->can('test-email'))
        {
            $request->validate([
                'email' => 'required|email'
            ], [
                'email.required' => __('Email address is required.'),
                'email.email' => __('Please enter a valid email address.'),
            ]);

            try {
                // Apply dynamic mail configuration
                SetConfigEmail();

                Mail::to($request->email)->send(new TestMail());

                return redirect()->back()->with('success', __('Test email sent successfully to :email', ['email' => $request->email]));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to send test email: :error', ['error' => $e->getMessage()]));
            }
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateSeoSettings(Request $request)
    {
        if(Auth::user()->can('edit-seo-settings'))
        {
            $request->validate([
                'settings.metaKeywords' => 'required|string|max:500',
                'settings.metaTitle' => 'required|string|max:100',
                'settings.metaDescription' => 'required|string|max:160',
                'settings.metaImage' => 'nullable|string|max:500',
            ], [
                'settings.metaKeywords.required' => __('Meta keywords are required.'),
                'settings.metaKeywords.string' => __('Meta keywords must be a valid string.'),
                'settings.metaKeywords.max' => __('Meta keywords must not exceed 500 characters.'),
                'settings.metaTitle.string' => __('Meta title must be a valid string.'),
                'settings.metaDescription.string' => __('Meta description must be a valid string.'),
                'settings.metaImage.string' => __('Meta image must be a valid string.'),
                'settings.metaTitle.required' => __('Meta title is required.'),
                'settings.metaTitle.max' => __('Meta title must not exceed 100 characters.'),
                'settings.metaDescription.required' => __('Meta description is required.'),
                'settings.metaDescription.max' => __('Meta description must not exceed 160 characters.'),
                'settings.metaImage.max' => __('Meta image path is too long.'),
            ]);

            $settings = $request->input('settings');
            if (isset($settings['metaImage']) ) {
                $settings['metaImage'] = basename($settings['metaImage']);
            }


            foreach ($settings as $key => $value) {
                setSetting($key, $value);
            }

            return redirect()->back()->with('success', __('SEO settings save successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updateStorageSettings(Request $request)
    {
        if(Auth::user()->can('edit-storage-settings'))
        {
            $request->validate([
                'settings.storageType' => 'required|string|in:local,aws_s3,wasabi',
                'settings.allowedFileTypes' => 'required|string|max:1000',
                'settings.maxUploadSize' => 'required|numeric|min:1',
                'settings.awsAccessKeyId' => 'nullable|string|max:255',
                'settings.awsSecretAccessKey' => 'nullable|string|max:255',
                'settings.awsDefaultRegion' => 'nullable|string|max:50',
                'settings.awsBucket' => 'nullable|string|max:255',
                'settings.awsUrl' => 'nullable|url|max:500',
                'settings.awsEndpoint' => 'nullable|url|max:500',
                'settings.wasabiAccessKey' => 'nullable|string|max:255',
                'settings.wasabiSecretKey' => 'nullable|string|max:255',
                'settings.wasabiRegion' => 'nullable|string|max:50',
                'settings.wasabiBucket' => 'nullable|string|max:255',
                'settings.wasabiUrl' => 'nullable|url|max:500',
                'settings.wasabiRoot' => 'nullable|string|max:255',
            ], [
                'settings.storageType.required' => __('Storage type is required.'),
                'settings.storageType.string' => __('Storage type must be a valid string.'),
                'settings.storageType.in' => __('Storage type must be local, aws_s3, or wasabi.'),
                'settings.allowedFileTypes.string' => __('Allowed file types must be a valid string.'),
                'settings.awsAccessKeyId.string' => __('AWS Access Key ID must be a valid string.'),
                'settings.awsSecretAccessKey.string' => __('AWS Secret Access Key must be a valid string.'),
                'settings.awsDefaultRegion.string' => __('AWS Default Region must be a valid string.'),
                'settings.awsBucket.string' => __('AWS Bucket must be a valid string.'),
                'settings.wasabiAccessKey.string' => __('Wasabi Access Key must be a valid string.'),
                'settings.wasabiSecretKey.string' => __('Wasabi Secret Key must be a valid string.'),
                'settings.wasabiRegion.string' => __('Wasabi Region must be a valid string.'),
                'settings.wasabiBucket.string' => __('Wasabi Bucket must be a valid string.'),
                'settings.wasabiRoot.string' => __('Wasabi Root must be a valid string.'),
                'settings.allowedFileTypes.required' => __('Allowed file types are required.'),
                'settings.allowedFileTypes.max' => __('Allowed file types must not exceed 1000 characters.'),
                'settings.maxUploadSize.required' => __('Maximum upload size is required.'),
                'settings.maxUploadSize.numeric' => __('Maximum upload size must be a number.'),
                'settings.maxUploadSize.min' => __('Maximum upload size must be at least 1 MB.'),
                'settings.awsAccessKeyId.max' => __('AWS Access Key ID must not exceed 255 characters.'),
                'settings.awsSecretAccessKey.max' => __('AWS Secret Access Key must not exceed 255 characters.'),
                'settings.awsDefaultRegion.max' => __('AWS Default Region must not exceed 50 characters.'),
                'settings.awsBucket.max' => __('AWS Bucket must not exceed 255 characters.'),
                'settings.awsUrl.max' => __('AWS URL must not exceed 500 characters.'),
                'settings.awsEndpoint.max' => __('AWS Endpoint must not exceed 500 characters.'),
                'settings.wasabiAccessKey.max' => __('Wasabi Access Key must not exceed 255 characters.'),
                'settings.wasabiSecretKey.max' => __('Wasabi Secret Key must not exceed 255 characters.'),
                'settings.wasabiRegion.max' => __('Wasabi Region must not exceed 50 characters.'),
                'settings.wasabiBucket.max' => __('Wasabi Bucket must not exceed 255 characters.'),
                'settings.wasabiUrl.max' => __('Wasabi URL must not exceed 500 characters.'),
                'settings.wasabiRoot.max' => __('Wasabi Root must not exceed 255 characters.'),
                'settings.awsUrl.url' => __('Please enter a valid AWS URL.'),
                'settings.awsEndpoint.url' => __('Please enter a valid AWS endpoint URL.'),
                'settings.wasabiUrl.url' => __('Please enter a valid Wasabi URL.'),
            ]);

            $settings = $request->input('settings');

            foreach ($settings as $key => $value) {
                setSetting($key, $value, null, false);
            }

            // Clear storage configuration cache
            \App\Services\StorageConfigService::clearCache();

            return redirect()->back()->with('success', __('Storage settings save successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function downloadCookieData()
    {
        if(Auth::user()->can('manage-cookie-settings'))
        {
            $cookieDataPath = storage_path('app/cookie_data.csv');

            if (!file_exists($cookieDataPath)) {
                $headers = ['IP Address', 'User Agent', 'Accepted At', 'Necessary', 'Analytics', 'Marketing'];
                $file = fopen($cookieDataPath, 'w');
                fputcsv($file, $headers);
                fclose($file);
            }

            return response()->download($cookieDataPath, 'cookie_data.csv');
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function logCookieConsent(Request $request)
    {
        $cookieDataPath = storage_path('app/cookie_data.csv');

        if (!file_exists($cookieDataPath)) {
            $headers = ['IP Address', 'User Agent', 'Accepted At', 'Necessary', 'Analytics', 'Marketing'];
            $file = fopen($cookieDataPath, 'w');
            fputcsv($file, $headers);
            fclose($file);
        }

        $consent = $request->input('consent');
        $data = [
            $request->ip(),
            $request->input('userAgent'),
            now()->format('Y-m-d H:i:s'),
            $consent['necessary'] ? 'Yes' : 'No',
            $consent['analytics'] ? 'Yes' : 'No',
            $consent['marketing'] ? 'Yes' : 'No'
        ];

        $file = fopen($cookieDataPath, 'a');
        fputcsv($file, $data);
        fclose($file);

        return back();
    }

    public function updateBankTransferSettings(Request $request)
    {
        if(Auth::user()->can('edit-bank-transfer-settings'))
        {
            $request->validate([
                'settings.bankTransferEnabled' => 'required|string|in:on,off',
                'settings.instructions' => 'nullable|string|max:2000',
            ], [
                'settings.bankTransferEnabled.required' => __('Bank transfer setting is required.'),
                'settings.bankTransferEnabled.string' => __('Bank transfer setting must be a valid string.'),
                'settings.bankTransferEnabled.in' => __('Bank transfer setting must be on or off.'),
                'settings.instructions.string' => __('Instructions must be a valid string.'),
                'settings.instructions.max' => __('Instructions must not exceed 2000 characters.'),
            ]);

            $settings = $request->input('settings');

            foreach ($settings as $key => $value) {
                setSetting($key, $value, null, false);
            }

            return redirect()->back()->with('success', __('Bank transfer settings updated successfully'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function updatePusherSettings(Request $request)
    {
        if(Auth::user()->can('edit-pusher-settings'))
        {
            $request->validate([
                'settings.app_id' => 'required|string|max:50',
                'settings.app_key' => 'required|string|max:100',
                'settings.app_secret' => 'required|string|max:100',
                'settings.app_cluster' => 'required|string|max:20',
            ], [
                'settings.app_id.required' => __('Pusher App ID is required.'),
                'settings.app_id.string' => __('Pusher App ID must be a valid string.'),
                'settings.app_id.max' => __('Pusher App ID must not exceed 50 characters.'),
                'settings.app_key.required' => __('Pusher App Key is required.'),
                'settings.app_key.string' => __('Pusher App Key must be a valid string.'),
                'settings.app_key.max' => __('Pusher App Key must not exceed 100 characters.'),
                'settings.app_secret.required' => __('Pusher App Secret is required.'),
                'settings.app_secret.string' => __('Pusher App Secret must be a valid string.'),
                'settings.app_secret.max' => __('Pusher App Secret must not exceed 100 characters.'),
                'settings.app_cluster.required' => __('Pusher App Cluster is required.'),
                'settings.app_cluster.string' => __('Pusher App Cluster must be a valid string.'),
                'settings.app_cluster.max' => __('Pusher App Cluster must not exceed 20 characters.'),
            ]);

            $settings = $request->input('settings');

            foreach ($settings as $key => $value) {
                setSetting('pusher_' . $key, $value, null, false);
            }

            return redirect()->back()->with('success', __('Pusher settings saved successfully.'));
        }
        else
        {
            return back()->with('error', __('Permission denied'));
        }
    }

    public function mailNotificationStore(Request $request)
    {
        // mail notification save
        if ($request->has('mail_noti')) {
            foreach ($request->mail_noti as $key => $notification) {
                setSetting($key, $notification, null, false);
            }
        }
        return redirect()->back()->with('success', __('Mail Notification Setting save sucessfully.'));
    }
}
