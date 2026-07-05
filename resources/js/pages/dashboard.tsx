import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Head } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';

export default function Dashboard() {
    const { t } = useTranslation();
    
    return (
        <AuthenticatedLayout
            header={t('Dashboard')}
        >
            <Head title={t('Dashboard')} />

            <div className="flex flex-1 flex-col gap-4 h-full">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="aspect-video rounded-xl bg-muted/50" />
                    <div className="aspect-video rounded-xl bg-muted/50" />
                    <div className="aspect-video rounded-xl bg-muted/50" />
                </div>
                <div className="flex-1 rounded-xl bg-muted/50 h-full" />
            </div>
        </AuthenticatedLayout>
    );
}