import { useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Checkbox } from '@/components/ui/checkbox';
import { InputError } from '@/components/ui/input-error';
import { SearchInput } from '@/components/ui/search-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { SubscriptionInfo } from '@/components/ui/subscription-info';
import { ScrollArea } from '@/components/ui/scroll-area';
import { formatAdminCurrency, getPackageAlias, getPackageFavicon } from '@/utils/helpers';

interface Plan {
    id?: number;
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
    plan?: Plan;
    activeModules: Module[];
    isEdit?: boolean;
    userSubscriptionInfo?: UserSubscriptionInfo;
}

function PlanForm({ plan, activeModules, isEdit = false, userSubscriptionInfo }: Props) {
    const { t } = useTranslation();
    const [moduleSearch, setModuleSearch] = useState('');

    const getCurrencySymbol = () => {
        return formatAdminCurrency(1).replace(/[\d\s.,]/g, '').trim();
    };

    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        description: plan?.description || '',
        number_of_users: plan?.number_of_users || 1,
        storage_limit: plan?.storage_limit || 0,
        total: plan?.storage_limit || 0,
        status: plan?.status ?? true,
        free_plan: plan?.free_plan ?? false,
        modules: plan?.modules || [],
        package_price_yearly: plan?.package_price_yearly || 0,
        package_price_monthly: plan?.package_price_monthly || 0,

        trial: plan?.trial ?? false,
        trial_days: plan?.trial_days || 0,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (isEdit && plan) {
            put(route('plans.update', plan.id));
        } else {
            post(route('plans.store'));
        }
    };

    const handleModuleChange = (moduleName: string, checked: boolean) => {
        if (checked) {
            setData('modules', [...data.modules, moduleName]);
        } else {
            setData('modules', data.modules.filter(m => m !== moduleName));
        }
    };

    const filteredModules = activeModules.filter(module =>
        module.alias.toLowerCase().includes(moduleSearch.toLowerCase()) ||
        module.module.toLowerCase().includes(moduleSearch.toLowerCase())
    );

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid grid-cols-12 gap-6">
                {/* Left Sidebar - Quick Settings */}
                <div className="col-span-3 space-y-4">
                    {/* Subscription Info */}
                    {userSubscriptionInfo && (
                        <SubscriptionInfo
                            userSubscriptionInfo={userSubscriptionInfo}
                            totalModulesCount={activeModules.length}
                        />
                    )}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm">{t('Quick Settings')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between">
                                <Label className="text-xs">{t('Active')}</Label>
                                <Switch checked={data.status} onCheckedChange={(checked) => setData('status', checked)} />
                            </div>
                            <div className="flex items-center justify-between">
                                <Label className="text-xs">{t('Trial')}</Label>
                                <Switch checked={data.trial} onCheckedChange={(checked) => setData('trial', checked)} />
                            </div>
                            <div className="flex items-center justify-between">
                                <Label className="text-xs">{t('Free')}</Label>
                                <Switch checked={data.free_plan} onCheckedChange={(checked) => setData('free_plan', checked)} />
                            </div>
                        </CardContent>
                    </Card>
                    {data.trial && (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm">{t('Trial Settings')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div>
                                    <Label className="text-xs">{t('Trial Days')}</Label>
                                    <Input type="number" placeholder={t('Enter trial days')} value={data.trial_days || 0} onChange={(e) => setData('trial_days', parseInt(e.target.value) || 0)} />
                                </div>
                            </CardContent>
                        </Card>
                    )}
                    {!data.free_plan && (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm">{t('Pricing')}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <Label className="text-xs">{t('Monthly')} ({getCurrencySymbol()})</Label>
                                    <Input type="number" step="0.01" placeholder={t('Enter monthly price')} value={data.package_price_monthly || 0} onChange={(e) => setData('package_price_monthly', parseFloat(e.target.value) || 0)} />
                                </div>
                                <div>
                                    <Label className="text-xs">{t('Yearly')} ({getCurrencySymbol()})</Label>
                                    <Input type="number" step="0.01" placeholder={t('Enter yearly price')} value={data.package_price_yearly || 0} onChange={(e) => setData('package_price_yearly', parseFloat(e.target.value) || 0)} />
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Main Content */}
                <div className="col-span-9 space-y-6">
                    {/* Plan Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Plan Information')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-3 gap-4">
                                <div>
                                    <Label required>{t('Plan Name')}</Label>
                                    <Input placeholder={t('Enter plan name')} value={data.name} onChange={(e) => setData('name', e.target.value)} />
                                    <InputError message={errors.name} />
                                </div>
                                <div>
                                    <Label>{t('Max Users')}</Label>
                                    <Input type="number" placeholder={t('Enter max users')} value={data.number_of_users || ''} onChange={(e) => setData('number_of_users', parseInt(e.target.value) || 0)} />
                                    <p className="text-xs text-gray-500 mt-1">{t('Note: "-1" for Unlimited')}</p>
                                    <InputError message={errors.number_of_users} />
                                </div>
                                <div>
                                    <Label>{t('Storage Limit (GB)')}</Label>
                                    <Input type="number" placeholder={t('Enter storage limit in GB')} value={data.storage_limit || ''} onChange={(e) => {
                                        const value = parseInt(e.target.value) || 0;
                                        setData('storage_limit', value);
                                        setData('total', value);
                                    }} />
                                    <InputError message={errors.storage_limit} />
                                </div>
                            </div>
                            <div>
                                <Label>{t('Description')}</Label>
                                <Textarea placeholder={t('Enter plan description')} value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} />
                                <InputError message={errors.description} />
                            </div>
                        </CardContent>
                    </Card>



                    {/* Features */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                {t('Features')}
                                <div className="flex items-center gap-2">
                                    <Badge>{data.modules.length} {t('selected')}</Badge>
                                    {userSubscriptionInfo && !userSubscriptionInfo.is_superadmin && (
                                        <Badge variant="outline" className="text-xs">
                                            {userSubscriptionInfo.available_modules_count} {t('available')}
                                        </Badge>
                                    )}
                                </div>
                            </CardTitle>
                            {userSubscriptionInfo && !userSubscriptionInfo.is_superadmin && (
                                <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                                    <div className="flex items-start gap-2">
                                        <svg className="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                        </svg>
                                        <div>
                                            <p className="text-sm font-medium text-amber-800 dark:text-amber-200">
                                                {t('Subscription Limited')}
                                            </p>
                                            <p className="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                                {t('Only showing {{count}} modules from your subscription. Contact admin to access more modules.', { count: userSubscriptionInfo.available_modules_count })}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}
                            <div className="flex gap-2">
                                <div className="flex-1">
                                    <Input 
                                        value={moduleSearch} 
                                        onChange={(e) => setModuleSearch(e.target.value)} 
                                        placeholder={t('Search...')} 
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="default"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        const allSelected = filteredModules.every(m => data.modules.includes(m.module));
                                        if (allSelected) {
                                            setData('modules', data.modules.filter(m => !filteredModules.map(fm => fm.module).includes(m)));
                                        } else {
                                            setData('modules', [...new Set([...data.modules, ...filteredModules.map(m => m.module)])]);
                                        }
                                    }}
                                >
                                    {filteredModules.every(m => data.modules.includes(m.module)) ? t('Uncheck All') : t('Check All')}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <ScrollArea className="h-[300px]">
                                <div className="grid grid-cols-4 gap-3 pr-4">
                                {filteredModules.map((module) => (
                                    <div key={module.module} className="flex items-center gap-3 p-4 border rounded hover:bg-muted/50">
                                        <img src={getPackageFavicon(module.module)} alt="" className="w-8 h-8 border rounded" />
                                        <span className="text-sm truncate flex-1">{getPackageAlias(module.module)}</span>
                                        <Checkbox
                                            checked={data.modules.includes(module.module)}
                                            onCheckedChange={(checked) => handleModuleChange(module.module, !!checked)}
                                        />
                                    </div>
                                ))}
                                </div>
                            </ScrollArea>
                            <InputError message={errors.modules} />
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                    {t('Cancel')}
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? t('Saving...') : (isEdit ? t('Update') : t('Create'))}
                </Button>
            </div>
        </form>
    );
}

export default PlanForm;
export { PlanForm };
