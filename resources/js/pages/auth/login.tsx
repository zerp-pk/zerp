import { FormEventHandler, useEffect } from "react";
import AuthLayout from "@/layouts/auth-layout";
import { Head, Link, useForm, router } from "@inertiajs/react";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import InputError from "@/components/ui/input-error";
import { Checkbox } from "@/components/ui/checkbox";

import { useTranslation } from 'react-i18next';
import { useFormFields } from '@/hooks/useFormFields';
import { usePageButtons } from '@/hooks/usePageButtons';

export default function Login({
    status,
    canResetPassword,
    enableRegistration,
    isDemo = false,
}: {
    status?: string;
    canResetPassword: boolean;
    enableRegistration?: boolean;
    isDemo?: boolean;
}) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
        recaptcha_token: null,
    });

    const formFields = useFormFields('getReCaptchFields', data, setData, errors, 'create', t);
    const loginButtons = usePageButtons('getLoginButtons', { t, isLoading: processing });

    useEffect(() => {
        if (isDemo) {
            setData((prevData) => ({
                ...prevData,
                email: 'company@example.com',
                password: '1234',
            }));
        }
    }, [isDemo]);

    useEffect(() => {
        return () => {
            reset("password");
        };
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("login"), {
            onError: () => {
                if ((window as any).refreshRecaptchaV3) {
                    (window as any).refreshRecaptchaV3();
                }
            },
        });
    };

    const handleQuickLogin = (email: string, password: string) => {
        setData((prevData) => ({
            ...prevData,
            email: email,
            password: password,
        }));
        
        // Use router directly to ensure we post with the updated values immediately
        // while also showing the values in the input fields for a brief moment.
        router.post(route('login'), {
            email,
            password,
            remember: data.remember,
            recaptcha_token: data.recaptcha_token || '',
        });
    };

    return (
        <AuthLayout
            title={t('Log in to your account')}
            description={t('Enter your email and password below to log in')}
        >
            <Head title={t('Log in')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="email" className="text-sm font-medium text-gray-900 dark:text-white">{t('Email address')}</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            placeholder="email@example.com"
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400 dark:bg-slate-700 dark:text-white"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label htmlFor="password" className="text-sm font-medium text-gray-900 dark:text-white">{t('Password')}</Label>
                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-sm text-primary hover:underline"
                                    tabIndex={5}
                                >
                                    {t('Forgot password?')}
                                </Link>
                            )}
                        </div>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            placeholder={t('Password')}
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm focus:outline-none transition-colors placeholder-gray-400 dark:bg-slate-700 dark:text-white"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center space-x-3 mt-4 mb-5">
                        <Checkbox
                            id="remember"
                            name="remember"
                            checked={data.remember}
                            onCheckedChange={(checked) => setData('remember', !!checked)}
                            tabIndex={3}
                            className="w-[14px] h-[14px] border border-gray-300 dark:border-gray-600 rounded"
                        />
                        <Label htmlFor="remember" className="text-sm text-gray-600 dark:text-gray-300">{t('Remember me')}</Label>
                    </div>

                    {formFields.map((field) => (
                        <div key={field.id}>
                            {field.component}
                        </div>
                    ))}

                    <Button
                        type="submit"
                        className="w-full bg-primary text-white py-2.5 text-sm font-medium tracking-wide transition-all duration-200 rounded-md shadow-md hover:shadow-lg transform hover:scale-[1.02] mt-4"
                        tabIndex={4}
                        disabled={processing}
                        data-test="login-button"
                    >
                        {processing ? 'Loading...' : t('SIGN IN')}
                    </Button>

                    {loginButtons.length > 0 && (
                        <>
                            {/* Divider */}
                            <div className="my-5">
                                <div className="flex items-center">
                                    <div className="flex-1 h-px bg-gray-200 dark:bg-gray-600"></div>
                                    <div className="w-2 h-2 rotate-45 mx-4 bg-primary"></div>
                                    <div className="flex-1 h-px bg-gray-200 dark:bg-gray-600"></div>
                                </div>
                            </div>
                            
                            <div className="space-y-2">
                                <div className="relative">
                                    <div className="absolute inset-0 flex items-center">
                                        <span className="w-full border-t dark:border-gray-600" />
                                    </div>
                                    <div className="relative flex justify-center text-xs uppercase">
                                        <span className="bg-white dark:bg-slate-800 px-2 text-gray-500 dark:text-gray-400">{t('Or continue with')}</span>
                                    </div>
                                </div>
                                {loginButtons.map((button) => (
                                    <div key={button.id}>
                                        {button.component}
                                    </div>
                                ))}
                            </div>
                        </>
                    )}
                </div>

                {enableRegistration && (
                    <div className="text-center mt-5">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            {t("Don't have an account?")}{' '}
                            <Link href={route('register')} tabIndex={6} className="text-primary font-medium hover:underline">
                                {t('Create one')}
                            </Link>
                        </p>
                    </div>
                )}

                {isDemo && (
                    <div className="mt-5">
                        <div className="flex items-center">
                            <div className="flex-1 h-px bg-gray-200 dark:bg-gray-600"></div>
                            <div className="w-2 h-2 rotate-45 mx-4 bg-primary"></div>
                            <div className="flex-1 h-px bg-gray-200 dark:bg-gray-600"></div>
                        </div>
                    </div>
                )}

                {isDemo && (
                    <div>
                        <h3 className="text-sm font-medium text-gray-900 dark:text-gray-300 tracking-wider mb-4 text-center">{t('Quick Access')}</h3>
                        <div className="grid sm:grid-cols-2 gap-3">
                            <Button
                                type="button"
                                onClick={() => handleQuickLogin('superadmin@example.com', '1234')}
                                disabled={processing}
                                className="sm:col-span-2 group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02] bg-primary disabled:opacity-50"
                            >
                                {t('Login as Super Admin')}
                            </Button>
                            <Button
                                type="button"
                                onClick={() => handleQuickLogin('company@example.com', '1234')}
                                disabled={processing}
                                className="group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02] bg-primary disabled:opacity-50"
                            >
                                {t('Login as Company')}
                            </Button>
                            <Button
                                type="button"
                                onClick={() => handleQuickLogin('john.smith@company.com', '1234')}
                                disabled={processing}
                                className="group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02] bg-primary disabled:opacity-50"
                            >
                                {t('Login as Employee')}
                            </Button>
                            <Button
                                type="button"
                                onClick={() => handleQuickLogin('sarah.johnson@client.com', '1234')}
                                disabled={processing}
                                className="group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02] bg-primary disabled:opacity-50"
                            >
                                {t('Login as Customer')}
                            </Button>
                            <Button
                                type="button"
                                onClick={() => handleQuickLogin('alex.vendor@supplier.com', '1234')}
                                disabled={processing}
                                className="group h-auto relative py-2 px-4 border text-[13px] font-medium text-white transition-all duration-200 rounded-md shadow-sm hover:shadow-md transform hover:scale-[1.02] bg-primary disabled:opacity-50"
                            >
                                {t('Login as Vendor')}
                            </Button>
                        </div>
                    </div>
                )}
            </form>
        </AuthLayout>
    );
}
