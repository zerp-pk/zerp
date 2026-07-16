import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Blocks } from 'lucide-react';

interface ModuleRow {
    module: string;
    title: string;
    description?: string | null;
    image: string;
    enabled: boolean;
}

export default function ModulesSettings() {
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
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Blocks className="h-5 w-5" />
                    {t('Modules')}
                </CardTitle>
                <p className="text-muted-foreground mt-1 text-sm">
                    {t('Switch off the modules you do not use. They disappear from the sidebar and their pages stop opening. Nothing is deleted, and you can switch them back on at any time.')}
                </p>
            </CardHeader>

            <CardContent>
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
            </CardContent>
        </Card>
    );
}
