import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface Props {
    credentials: {
        admin: { email: string; password: string };
        company: { email: string; password: string };
    };
}

export default function Final({ credentials }: Props) {
    const { t } = useTranslation();
    return (
        <>
            <Head title={t('Installation Complete')} />
            <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div className="sm:mx-auto sm:w-full sm:max-w-md">
                    <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                        <div className="text-center">
                            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                                <svg className="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900 mb-4">
                                {t('Installation Complete!')} 
                            </h2>
                            <p className="text-gray-600 mb-8">
                                {t('Your application has been successfully installed and configured.')}
                            </p>
                            <div className="space-y-4 text-left">
                                <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                    <h3 className="font-semibold text-blue-800 mb-3">{t('Default Login Credentials')}:</h3>
                                    <div className="space-y-3 text-sm">
                                        <div>
                                            <strong className="text-blue-800">{t('Admin Account')}:</strong><br/>
                                            <span className="text-blue-700">{t('Email')}: {credentials.admin.email}</span><br/>
                                            <span className="text-blue-700">{t('Password')}: {credentials.admin.password}</span>
                                        </div>
                                        <div>
                                            <strong className="text-blue-800">{t('Company Account')}:</strong><br/>
                                            <span className="text-blue-700">{t('Email')}: {credentials.company.email}</span><br/>
                                            <span className="text-blue-700">{t('Password')}: {credentials.company.password}</span>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                                    <h3 className="font-semibold text-yellow-800 mb-2">{t('Important Security Note')}:</h3>
                                    <p className="text-sm text-yellow-700">
                                        {t('Please change the default passwords after login and delete the installer files from your server.')}
                                    </p>
                                </div>
                            </div>
                            <div className="mt-8">
                                <Link
                                    href={route('dashboard')}
                                    className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    {t('Go to Dashboard')}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}