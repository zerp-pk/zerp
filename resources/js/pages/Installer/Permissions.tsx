import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface Permission {
    name: string;
    path: string;
    check: boolean;
}

interface Props {
    permissions: Record<string, Permission>;
}

export default function Permissions({ permissions }: Props) {
    const { t } = useTranslation();
    const allPassed = Object.values(permissions).every(perm => perm.check);

    return (
        <>
            <Head title={t('Installation - Permissions')} />
            <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div className="sm:mx-auto sm:w-full sm:max-w-2xl">
                    <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                        <h2 className="text-2xl font-bold text-gray-900 mb-6">{t('File Permissions')}</h2>
                        
                        <div className="space-y-4">
                            {Object.entries(permissions).map(([key, perm]) => (
                                <div key={key} className="flex items-center justify-between p-3 border rounded">
                                    <div>
                                        <div className="font-medium">{perm.name}</div>
                                        <div className="text-sm text-gray-500">{perm.path}</div>
                                    </div>
                                    {perm.check ? (
                                        <span className="text-green-600">✓ {t('Writable')}</span>
                                    ) : (
                                        <span className="text-red-600">✗ {t('Not Writable')}</span>
                                    )}
                                </div>
                            ))}
                        </div>

                        {!allPassed && (
                            <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                                <h3 className="font-semibold text-yellow-800 mb-2">{t('Fix Permissions')}</h3>
                                <p className="text-sm text-yellow-700 mb-2">
                                    {t('Run the following commands to fix permissions')}:
                                </p>
                                <code className="block text-xs bg-gray-100 p-2 rounded">
                                    chmod -R 755 storage/<br/>
                                    chmod -R 755 bootstrap/cache/
                                </code>
                            </div>
                        )}

                        <div className="mt-8 flex justify-between">
                            <Link
                                href={route('installer.requirements')}
                                className="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                {t('Back')}
                            </Link>
                            {allPassed ? (
                                <Link
                                    href={route('installer.environment')}
                                    className="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                                >
                                    {t('Next')}
                                </Link>
                            ) : (
                                <button
                                    disabled
                                    className="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-400 cursor-not-allowed"
                                >
                                    {t('Fix Permissions First')}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}