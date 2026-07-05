import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface Requirement {
    name: string;
    check: boolean;
    current?: string;
}

interface Requirements {
    php: Requirement;
    extensions: Record<string, Requirement>;
}

interface Props {
    requirements: Requirements;
}

export default function Requirements({ requirements }: Props) {
    const { t } = useTranslation();
    const allPassed = requirements.php.check && 
        Object.values(requirements.extensions).every(ext => ext.check);

    return (
        <>
            <Head title={t('Installation - Requirements')} />
            <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div className="sm:mx-auto sm:w-full sm:max-w-2xl">
                    <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                        <h2 className="text-2xl font-bold text-gray-900 mb-6">{t('Server Requirements')}</h2>
                        
                        <div className="space-y-4">
                            <div className="flex items-center justify-between p-3 border rounded">
                                <span>{requirements.php.name}</span>
                                <div className="flex items-center space-x-2">
                                    <span className="text-sm text-gray-500">{requirements.php.current}</span>
                                    {requirements.php.check ? (
                                        <span className="text-green-600">✓</span>
                                    ) : (
                                        <span className="text-red-600">✗</span>
                                    )}
                                </div>
                            </div>

                            {Object.entries(requirements.extensions).map(([key, ext]) => (
                                <div key={key} className="flex items-center justify-between p-3 border rounded">
                                    <span>{ext.name}</span>
                                    {ext.check ? (
                                        <span className="text-green-600">✓</span>
                                    ) : (
                                        <span className="text-red-600">✗</span>
                                    )}
                                </div>
                            ))}
                        </div>

                        <div className="mt-8 flex justify-between">
                            <Link
                                href={route('installer.welcome')}
                                className="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                {t('Back')}
                            </Link>
                            {allPassed ? (
                                <Link
                                    href={route('installer.permissions')}
                                    className="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                                >
                                    {t('Next')}
                                </Link>
                            ) : (
                                <button
                                    disabled
                                    className="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-400 cursor-not-allowed"
                                >
                                    {t('Fix Requirements First')}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}