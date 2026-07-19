import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslation } from 'react-i18next';

export default function Database() {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        admin_name: '',
        admin_email: '',
        admin_password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('installer.database.store'));
    };

    return (
        <>
            <Head title={t('Installation - Database Setup')} />
            <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div className="sm:mx-auto sm:w-full sm:max-w-md">
                    <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                        <h2 className="text-2xl font-bold text-gray-900 mb-6">{t('Database Setup')}</h2>
                        
                        <div className="mb-6">
                            <p className="text-gray-600 mb-4">
                                {t('This step will create the database tables and seed initial data.')}
                            </p>
                            <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <h3 className="font-semibold text-blue-800 mb-2">{t('What will happen')}:</h3>
                                <ul className="text-sm text-blue-700 space-y-1">
                                    <li>• {t('Run database migrations')}</li>
                                    <li>• {t('Seed initial data')}</li>
                                    <li>• {t('Create default roles and permissions')}</li>
                                    <li>• {t('Setup system settings')}</li>
                                </ul>
                            </div>
                        </div>

                        {(errors as any).database && (
                            <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                                <p className="text-red-700">{(errors as any).database}</p>
                            </div>
                        )}
                        
                        <form onSubmit={submit}>
                            <div className="mb-6 space-y-4">
                                <h3 className="font-semibold text-gray-900">{t('Super Admin Account')}</h3>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">{t('Name')}</label>
                                    <input
                                        type="text"
                                        value={data.admin_name}
                                        onChange={(e) => setData('admin_name', e.target.value)}
                                        className="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    {(errors as any).admin_name && <p className="mt-1 text-sm text-red-600">{(errors as any).admin_name}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">{t('Email')}</label>
                                    <input
                                        type="email"
                                        value={data.admin_email}
                                        onChange={(e) => setData('admin_email', e.target.value)}
                                        className="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    {(errors as any).admin_email && <p className="mt-1 text-sm text-red-600">{(errors as any).admin_email}</p>}
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">{t('Password')}</label>
                                    <input
                                        type="password"
                                        value={data.admin_password}
                                        onChange={(e) => setData('admin_password', e.target.value)}
                                        className="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    {(errors as any).admin_password && <p className="mt-1 text-sm text-red-600">{(errors as any).admin_password}</p>}
                                </div>
                            </div>
                            {processing && (
                                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                                    <div className="bg-white p-6 rounded-lg shadow-lg text-center">
                                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto mb-4"></div>
                                        <p>{t('Setting up database...')}</p>
                                    </div>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <Link
                                    href={route('installer.environment')}
                                    className="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    {t('Back')}
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {processing ? t('Setting up Database...') : t('Setup Database')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}