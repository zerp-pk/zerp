import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

export default function Welcome() {
    const { t } = useTranslation();
    return (
        <>
            <Head title={t('Installation - Welcome')} />
            <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div className="sm:mx-auto sm:w-full sm:max-w-md">
                    <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                        <div className="text-center">
                            <h2 className="text-3xl font-extrabold text-gray-900 mb-6">
                                {t('Welcome to Installation')}
                            </h2>
                            <p className="text-gray-600 mb-8">
                                {t('This installer will guide you through the setup process for your application.')}
                            </p>
                            <div className="space-y-4">
                                <div className="text-left">
                                    <h3 className="font-semibold text-gray-900 mb-2">{t('Installation Steps')}:</h3>
                                    <ul className="text-sm text-gray-600 space-y-1">
                                        <li>• {t('Check server requirements')}</li>
                                        <li>• {t('Verify file permissions')}</li>
                                        <li>• {t('Configure environment')}</li>
                                        <li>• {t('Setup database')}</li>
                                    </ul>
                                </div>
                            </div>
                            <div className="mt-8">
                                <Link
                                    href={route('installer.requirements')}
                                    className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    {t('Start Installation')}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}