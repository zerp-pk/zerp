import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import PlanForm from './form';

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
}

interface UserSubscriptionInfo {
    is_superadmin: boolean;
    active_plan_id?: number;
    available_modules_count: number;
}

interface Props {
    plan: Plan;
    activeModules: Module[];
    userSubscriptionInfo?: UserSubscriptionInfo;
}

export default function EditPlan({ plan, activeModules, userSubscriptionInfo }: Props) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Subscription Setting'), url: route('plans.index') },
                { label: t('Edit Plan') }
            ]}
            pageTitle={t('Edit Plan')}
            backUrl={route('plans.index')}
        >
            <Head title={t('Edit Plan')} />

            <Card>
                <CardContent className="pt-6">
                    <PlanForm plan={plan} activeModules={activeModules} isEdit={true} userSubscriptionInfo={userSubscriptionInfo} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
