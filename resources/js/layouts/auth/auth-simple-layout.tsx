import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';
import { LanguageSwitcher } from '@/components/language-switcher';
import { useBrand } from '@/contexts/brand-context';
import { useFavicon } from '@/hooks/use-favicon';
import { getImagePath } from '@/utils/helpers';
import ApplicationLogo from '@/components/application-logo';
import CookieConsent from '@/components/cookie-consent';
import { usePage } from '@inertiajs/react';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    const { settings, getPrimaryColor } = useBrand();
    const { adminAllSetting } = usePage().props as any;
    useFavicon();
    
    const logoSrc = settings.themeMode === 'dark' ? (settings.logo_light || settings.logo_dark) : (settings.logo_dark || settings.logo_light);
    const primaryColor = getPrimaryColor();
    
    return (
        <div className="min-h-screen bg-gray-50 relative overflow-hidden">
            <style>{`
                .bg-primary {
                    background-color: ${primaryColor} !important;
                    color: white !important;
                }
                .bg-primary:hover {
                    background-color: ${primaryColor}dd !important;
                }
                .border-primary {
                    border-color: ${primaryColor} !important;
                }
                .text-primary {
                    color: ${primaryColor} !important;
                }
                .dark .bg-primary {
                    background-color: ${primaryColor} !important;
                    color: white !important;
                }
                .dark .bg-primary:hover {
                    background-color: ${primaryColor}dd !important;
                }
            `}</style>

            {/* Enhanced Background Design */}
            <div className="absolute inset-0">
                {/* Base Gradient */}
                <div className="absolute inset-0 bg-gradient-to-br from-slate-50 via-gray-50 to-stone-100 dark:from-slate-900 dark:via-slate-800 dark:to-stone-900"></div>
                
                {/* Elegant Pattern Overlay */}
                <div 
                    className="absolute inset-0 opacity-70 dark:opacity-30" 
                    style={{
                        backgroundImage: `radial-gradient(circle at 30% 70%, ${primaryColor} 1px, transparent 1px)`,
                        backgroundSize: '80px 80px'
                    }}
                ></div>
            </div>
            
            {/* Language Switcher */}
            <div className="absolute top-6 right-6 z-10 md:block hidden">
                <LanguageSwitcher />
            </div>

            <div className="flex items-center justify-center min-h-screen p-6">
                <div className="w-full max-w-md">
                    {/* Logo */}
                    <div className="text-center mb-8">
                        <div className="relative lg:inline-block lg:px-6">
                            <Link href={route('dashboard')} className="inline-block max-w-[180px]">
                                {logoSrc ? (
                                    <img
                                        src={getImagePath(logoSrc)}
                                        alt={settings.titleText || 'Logo'}
                                        className="h-12 w-auto mx-auto object-contain"
                                    />
                                ) : (
                                    <ApplicationLogo className="h-8 w-8 mx-auto text-primary" />
                                )}
                            </Link>
                        </div>
                    </div>

                    {/* Main Card */}
                    <div className="relative">
                        {/* Corner accents */}
                        <div 
                            className="absolute -top-3 -left-3 w-6 h-6 border-l-2 border-t-2 rounded-tl-md" 
                            style={{ borderColor: primaryColor }}
                        ></div>
                        <div 
                            className="absolute -bottom-3 -right-3 w-6 h-6 border-r-2 border-b-2 rounded-br-md" 
                            style={{ borderColor: primaryColor }}
                        ></div>

                        <div className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg lg:p-8 p-4 lg:pt-5 shadow-sm">
                            {/* Header */}
                            <div className="text-center mb-4">
                                <h1 className="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-white mb-1.5">{title}</h1>
                                <div 
                                    className="w-12 h-px mx-auto mb-2.5" 
                                    style={{ backgroundColor: primaryColor }}
                                ></div>
                                <p className="text-gray-700 dark:text-gray-300 text-sm">{description}</p>
                            </div>

                            {/* Content */}
                            {children}
                        </div>
                    </div>

                    {/* Footer */}
                    <div className="text-center mt-6">
                            <div className="inline-flex items-center space-x-2 bg-white dark:bg-slate-800/80 backdrop-blur-sm rounded-md px-4 py-2 border border-gray-200 dark:border-slate-700">
                                <p className="text-sm text-gray-500 dark:text-gray-400">{settings.footerText || `© ${new Date().getFullYear()} Zerp. All rights reserved.`}</p>
                            </div>
                    </div>
                </div>
            </div>
            <CookieConsent settings={adminAllSetting || {}} />
        </div>
    );
}