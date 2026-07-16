import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';

interface ModuleRow {
    module: string;
    title: string;
    description?: string | null;
    image: string;
    enabled: boolean;
}

export default function Modules() {
    const { t } = useTranslation();
    const { modules = [] } = usePage().props as unknown as { modules: ModuleRow[] };

    const toggle = (module: string, enabled: boolean) => {
        router.put(
            route('settings.modules.update'),
            { module, enabled },
            // The sidebar is built from the shared activatedPackages prop, so a full
            // visit is what makes the menu reflect the change immediately.
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Modules')} />

            <div className="space-y-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">{t('Modules')}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('Switch off the modules you do not use. They disappear from the sidebar and their pages stop opening. Nothing is deleted, and you can switch them back on at any time.')}
                    </p>
                </div>

                {modules.length === 0 && (
                    <p className="text-muted-foreground text-sm">
                        {t('Your plan does not include any modules yet.')}
                    </p>
                )}

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {modules.map(item => (
                        <Card key={item.module} className="flex items-center gap-3 p-4">
                            <img
                                src={item.image}
                                alt=""
                                className="h-10 w-10 shrink-0 rounded object-contain"
                                onError={e => {
                                    (e.currentTarget as HTMLImageElement).style.visibility = 'hidden';
                                }}
                            />

                            <div className="min-w-0 flex-1">
                                <Label htmlFor={`module-${item.module}`} className="cursor-pointer font-medium">
                                    {t(item.title)}
                                </Label>
                                {item.description && (
                                    <p className="text-muted-foreground truncate text-xs">{item.description}</p>
                                )}
                            </div>

                            <Switch
                                id={`module-${item.module}`}
                                checked={item.enabled}
                                onCheckedChange={checked => toggle(item.module, checked)}
                            />
                        </Card>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
