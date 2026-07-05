import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import axios from 'axios';

interface Module {
    name: string;
    alias: string;
    description: string;
    priority: number;
}

interface Props {
    modules: Module[];
}

export default function Addons({ modules }: Props) {
    const { t } = useTranslation();
    const [currentModuleIndex, setCurrentModuleIndex] = useState(0);
    const [installing, setInstalling] = useState(false);
    const [completed, setCompleted] = useState(false);
    const [error, setError] = useState('');
    const [installedModules, setInstalledModules] = useState<string[]>([]);

    const installNextModule = async () => {
        if (currentModuleIndex >= modules.length) {
            setCompleted(true);
            return;
        }

        const currentModule = modules[currentModuleIndex];
        setInstalling(true);
        setError('');

        try {
            const response = await axios.post(route('installer.addons.store'), {
                module: currentModule.name
            });

            if (response.data.success) {
                setInstalledModules(prev => [...prev, currentModule.name]);
                setCurrentModuleIndex(prev => prev + 1);
                
                if (response.data.completed) {
                    setCompleted(true);
                }
            } else {
                setError(response.data.message);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Installation failed');
        } finally {
            setInstalling(false);
        }
    };

    const startInstallation = () => {
        setCurrentModuleIndex(0);
        setInstalledModules([]);
        setCompleted(false);
        setError('');
        installNextModule();
    };

    useEffect(() => {
        if (currentModuleIndex > 0 && currentModuleIndex < modules.length && !error) {
            const timer = setTimeout(() => {
                installNextModule();
            }, 1000);
            return () => clearTimeout(timer);
        }
    }, [currentModuleIndex]);

    return (
        <>
            <Head title={t('Installation - Add-ons')} />
            <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
                <div className="sm:mx-auto sm:w-full sm:max-w-2xl">
                    <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                        <h2 className="text-2xl font-bold text-gray-900 mb-6">{t('Add-on Installation')}</h2>
                        


                        {error && (
                            <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                                <p className="text-red-700">{error}</p>
                            </div>
                        )}

                        <p className="text-gray-600 mb-4">
                            {t('This step will install and enable all available add-on modules one by one.')}
                        </p>

                        <div className="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                            <h3 className="font-semibold text-blue-800 mb-2">{t('Add-ons Progress')} ({installedModules.length}/{modules.length}):</h3>
                            
                            <div className="w-full bg-gray-200 rounded-full h-2 mb-4">
                                <div 
                                    className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                                    style={{ width: `${(installedModules.length / modules.length) * 100}%` }}
                                ></div>
                            </div>

                            <div className="max-h-60 overflow-y-auto space-y-2">
                                {modules.map((module, index) => (
                                    <div key={module.name} className={`p-2 rounded border ${
                                        installedModules.includes(module.name) ? 'bg-green-100 border-green-300' :
                                        index === currentModuleIndex && installing ? 'bg-yellow-100 border-yellow-300' :
                                        'bg-white border-gray-200'
                                    }`}>
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <h4 className="font-medium">{module.alias}</h4>
                                                <p className="text-sm text-gray-600">{module.description}</p>
                                            </div>
                                            <div>
                                                {installedModules.includes(module.name) && (
                                                    <span className="text-green-600">✓ {t('Installed')}</span>
                                                )}
                                                {index === currentModuleIndex && installing && (
                                                    <span className="text-yellow-600">⏳ {t('Installing...')}</span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="flex justify-between">
                            <Link
                                href={route('installer.database')}
                                className="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                {t('Back')}
                            </Link>
                            
                            {completed ? (
                                <Link
                                    href={route('installer.final')}
                                    className="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
                                >
                                    {t('Continue')}
                                </Link>
                            ) : (
                                <button
                                    onClick={startInstallation}
                                    disabled={installing}
                                    className="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {installing ? t('Installing...') : t('Start Installation')}
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}