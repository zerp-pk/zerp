import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import SubscriptionLayout from './subscription-layout';

interface Plan {
    id: number;
    name: string;
    description: string;
    number_of_users: number;
    status: boolean;
    free_plan: boolean;
    modules: string[];
    package_price_yearly: number;
    package_price_monthly: number;
    storage_limit: number;
    trial: boolean;
    trial_days: number;
}

interface Module {
    module: string;
    alias: string;
    image: string;
    monthly_price: number;
    yearly_price: number;
}

interface Props {
    plan: Plan;
    activeModules: Module[];
    userActiveModules: string[];
    bankTransferEnabled: string;
    bankTransferInstructions: string;
    planExpireDate?: string;
    initialPeriod?: 'monthly' | 'yearly';
}

export default function Subscribe({ plan, activeModules, userActiveModules, bankTransferEnabled, bankTransferInstructions, planExpireDate, initialPeriod = 'monthly' }: Props) {
    const { t } = useTranslation();
    const [pricingPeriod, setPricingPeriod] = useState<'monthly' | 'yearly'>(initialPeriod);
    

    const handleSubscribe = (subscriptionData: any) => {
        console.log('Processing subscription:', subscriptionData);
        
        // Here you would make the API call to process the subscription
        router.post(route('subscriptions.store'), subscriptionData, {
            onSuccess: () => {
                // Handle successful subscription
            },
            onError: (errors) => {
                console.error('Subscription failed:', errors);
            }
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Plans'), url: route('plans.index') },
                { label: t('Subscribe to') + ' ' + plan.name }
            ]}
            pageTitle={t('Subscribe to Plan')}
            backUrl={route('plans.index')}
        >
            <Head title={t('Subscribe to') + ' ' + plan.name} />

            <div className="space-y-6">
                {/* Pricing Period Toggle */}
                <div className="flex items-center justify-center">
                    <div className="bg-gray-100 dark:bg-gray-800 p-1 rounded-lg">
                        <div className="flex items-center">
                            <button
                                onClick={() => setPricingPeriod('monthly')}
                                className={`px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 ${
                                    pricingPeriod === 'monthly'
                                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                }`}
                            >
                                {t('Monthly')}
                            </button>
                            <button
                                onClick={() => setPricingPeriod('yearly')}
                                className={`px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 ${
                                    pricingPeriod === 'yearly'
                                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                                }`}
                            >
                                {t('Yearly')}
                            </button>
                        </div>
                    </div>
                </div>

                {/* Subscription Layout */}
                <SubscriptionLayout
                    plan={plan}
                    allModules={activeModules}
                    userActiveModules={userActiveModules}
                    pricingPeriod={pricingPeriod}
                    onSubscribe={handleSubscribe}
                    bankTransferEnabled={bankTransferEnabled === 'on'}
                    bankTransferInstructions={bankTransferInstructions}
                    planExpireDate={planExpireDate}
                />
            </div>
        </AuthenticatedLayout>
    );
}