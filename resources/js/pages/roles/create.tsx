import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import InputError from '@/components/ui/input-error';
import { RoleCreateProps } from './types';
import { getPackageAlias } from '@/utils/helpers';
import { useState } from 'react';

export default function Create() {
    const { t } = useTranslation();
    const { permissions } = usePage<RoleCreateProps>().props;
    const [searchTerm, setSearchTerm] = useState('');
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        label: '',
        permissions: [] as string[]
    });


    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('roles.store'));
    };

    const handlePermissionChange = (permissionName: string, checked: boolean) => {
        if (checked) {
            setData('permissions', [...data.permissions, permissionName]);
        } else {
            setData('permissions', data.permissions.filter(p => p !== permissionName));
        }
    };

    const handleModuleChange = (modulePermissions: any[], checked: boolean) => {
        const modulePermissionNames = modulePermissions.map(p => p.name);
        if (checked) {
            const newPermissions = [...new Set([...data.permissions, ...modulePermissionNames])];
            setData('permissions', newPermissions);
        } else {
            setData('permissions', data.permissions.filter(p => !modulePermissionNames.includes(p)));
        }
    };

    const getModuleCheckState = (modulePermissions: any[]) => {
        const modulePermissionNames = modulePermissions.map(p => p.name);
        const checkedCount = modulePermissionNames.filter(name => data.permissions.includes(name)).length;

        if (checkedCount === 0) return { checked: false, indeterminate: false };
        if (checkedCount === modulePermissionNames.length) return { checked: true, indeterminate: false };
        return { checked: false, indeterminate: true };
    };

    const filteredFeatures = Object.keys(permissions).filter(addOn =>
        getPackageAlias(addOn)?.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Roles'), url: route('roles.index')},
                {label: t('Create Role')}
            ]}
            pageTitle={t('Create New Role')}
            backUrl={route('roles.index')}
            className="overflow-hidden"
        >
            <Head title={t('Create Role')} />

            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="name">{t('Name')}</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder={t('Enter role name')}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div>
                                <Label htmlFor="label">{t('Label')}</Label>
                                <Input
                                    id="label"
                                    value={data.label}
                                    onChange={(e) => setData('label', e.target.value)}
                                    placeholder={t('Enter role label')}
                                    required
                                />
                                <InputError message={errors.label} />
                            </div>
                        </div>

                        <div>
                            <div className="mt-2 mb-4">
                                <Input
                                    placeholder={t('Search features...')}
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="max-w-sm"
                                />
                            </div>
                            <Tabs defaultValue={filteredFeatures[0] || 'default'} className="mt-2">
                                <TabsList className="mb-3 w-full justify-start overflow-x-auto overflow-y-hidden h-auto p-1">
                                    {filteredFeatures.map((addOn) => (
                                        <TabsTrigger key={addOn} value={addOn} className="capitalize whitespace-nowrap flex-shrink-0">
                                            {getPackageAlias(addOn)}
                                        </TabsTrigger>
                                    ))}
                                </TabsList>

                                <Label>{t('Permissions')}</Label>
                                {filteredFeatures.map((addOn) => {
                                    const modules = permissions[addOn];
                                    return (
                                    <TabsContent key={addOn} value={addOn}>
                                        <div className="space-y-3">
                                            {Object.entries(modules).map(([module, modulePermissions]) => {
                                                const moduleState = getModuleCheckState(modulePermissions as any);
                                                return (
                                                <div key={module} className="border p-4 rounded">
                                                    <div className="flex items-center space-x-2 mb-3">
                                                        <Checkbox
                                                            id={`module-${module}`}
                                                            checked={moduleState.checked}
                                                            ref={(el) => {
                                                                if (el) (el as any).indeterminate = moduleState.indeterminate;
                                                            }}
                                                            onCheckedChange={(checked) =>
                                                                handleModuleChange(modulePermissions as any, checked as boolean)
                                                            }
                                                        />
                                                        <Label htmlFor={`module-${module}`} className="font-medium capitalize">
                                                            {module}
                                                        </Label>
                                                    </div>
                                                    <div className="grid grid-cols-3 gap-2">
                                                        {(modulePermissions as any).map((permission: any) => (
                                                            <div key={permission.name} className="flex items-center space-x-2">
                                                                <Checkbox
                                                                    id={permission.name}
                                                                    checked={data.permissions.includes(permission.name)}
                                                                    onCheckedChange={(checked) =>
                                                                        handlePermissionChange(permission.name, checked as boolean)
                                                                    }
                                                                />
                                                                <Label htmlFor={permission.name} className="text-sm">
                                                                    {permission.label}
                                                                </Label>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )})}
                                        </div>
                                    </TabsContent>
                                    );
                                })}
                            </Tabs>
                            <InputError message={errors.permissions} />
                        </div>

                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => window.history.back()}>
                                {t('Cancel')}
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? t('Creating...') : t('Create')}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
