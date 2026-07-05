import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import PlanForm from './form';

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
    activeModules: Module[];
    userSubscriptionInfo?: UserSubscriptionInfo;
}

export default function CreatePlan({ activeModules, userSubscriptionInfo }: Props) {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Subscription Setting'), url: route('plans.index') },
                { label: t('Create Plan') }
            ]}
            pageTitle={t('Create Plan')}
            backUrl={route('plans.index')}
        >
            <Head title={t('Create Plan')} />

            <Card>
                <CardContent className="pt-6">
                    <PlanForm activeModules={activeModules} userSubscriptionInfo={userSubscriptionInfo} />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
