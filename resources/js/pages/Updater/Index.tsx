import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface Props {
    hasUpdates: boolean;
    pendingMigrations: string[];
}

export default function UpdaterIndex({ hasUpdates, pendingMigrations }: Props) {
    const { t } = useTranslation();
    const [updating, setUpdating] = useState(false);
    const [completed, setCompleted] = useState(false);
    const [error, setError] = useState('');

    const runUpdate = async () => {
        setUpdating(true);
        setError('');

        try {
            const response = await axios.post(route('updater.update'));

            if (response.data.success) {
                setCompleted(true);
                setTimeout(() => {
                    window.location.href = route('dashboard');
                }, 2000);
            } else {
                setError(response.data.message);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Update failed');
        } finally {
            setUpdating(false);
        }
    };

    return (
        <>
            <Head title={t('System Updater')} />
            <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div className="sm:mx-auto sm:w-full sm:max-w-2xl">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('System Updater')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {error && (
                                <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                                    <p className="text-red-700">{error}</p>
                                </div>
                            )}

                            {completed && (
                                <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                                    <p className="text-green-700">{t('System updated successfully!')}</p>
                                    <p className="text-sm text-green-600 mt-1">{t('Redirecting to dashboard...')}</p>
                                </div>
                            )}

                            {!hasUpdates && !completed ? (
                                <div className="text-center py-8">
                                    <div className="text-green-600 text-6xl mb-4">✓</div>
                                    <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                        {t('System is up to date')}
                                    </h3>
                                    <p className="text-gray-600">
                                        {t('No pending migrations found. Your system is running the latest version.')}
                                    </p>
                                </div>
                            ) : !completed ? (
                                <>
                                    <div className="mb-6">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-3">
                                            {t('Pending Updates')}
                                        </h3>
                                        <p className="text-gray-600 mb-4">
                                            {t('The following database migrations are pending and need to be executed:')}
                                        </p>

                                        <div className="bg-blue-50 border border-blue-200 rounded-md p-4">
                                            <h4 className="font-medium text-blue-900 mb-2">
                                                {t('Migrations to run')} ({pendingMigrations.length}):
                                            </h4>
                                            <div className="max-h-40 overflow-y-auto">
                                                {pendingMigrations.map((migration, index) => (
                                                    <div key={index} className="text-sm text-blue-800 py-1">
                                                        • {migration}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
                                        <h4 className="font-semibold text-yellow-800 mb-2">
                                            {t('Important Notice')}:
                                        </h4>
                                        <ul className="text-sm text-yellow-700 space-y-1">
                                            <li>• {t('Please backup your database before proceeding')}</li>
                                            <li>• {t('The update process may take a few minutes')}</li>
                                            <li>• {t('Do not close this page during the update')}</li>
                                        </ul>
                                    </div>
                                </>
                            ) : null}

                            <div className="flex justify-between">

                                {hasUpdates && !completed && (
                                    <Button
                                        onClick={runUpdate}
                                        disabled={updating}
                                        className="bg-blue-600 hover:bg-blue-700"
                                    >
                                        {updating ? (
                                            <>
                                                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                {t('Updating...')}
                                            </>
                                        ) : (
                                            t('Run Update')
                                        )}
                                    </Button>
                                )}

                                {completed && (
                                    <Link
                                        href={route('dashboard')}
                                        className="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
                                    >
                                        {t('Continue to Dashboard')}
                                    </Link>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}