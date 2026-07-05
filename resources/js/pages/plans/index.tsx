import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Check, Plus, Edit, Trash2, X, Package, MoreVertical, Clock } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SearchInput } from '@/components/ui/search-input';
import { formatDate, formatAdminCurrency, formatStorage } from '@/utils/helpers';

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
    orders_count?: number;
    creator?: {
        name: string;
    };
}

interface Props {
    plans: Plan[];
    canCreate: boolean;
    activeModules: { module: string; alias: string; image: string; monthly_price: number; yearly_price: number; }[];
    bankTransferEnabled: string;
    userTrialInfo?: {
        is_trial_done: number;
        trial_expire_date: string | null;
    };
    currentSubscription?: {
        plan_name: string;
        duration: string;
        expire_date: string | null;
        payment_amount: number;
        currency: string;
        is_free: boolean;
    };
}

export default function PlansIndex({ plans, canCreate, activeModules, bankTransferEnabled, userTrialInfo, currentSubscription }: Props) {
    const { t } = useTranslation();
    const [subscriptionType, setSubscriptionType] = useState<'pre-package'>('pre-package');
    const { auth } = usePage().props as any;
    const isCompanyUser = !auth.user?.roles?.includes('superadmin');

    const [moduleSearch, setModuleSearch] = useState('');
    const [deletingPlan, setDeletingPlan] = useState<Plan | null>(null);
    const [pricingPeriod, setPricingPeriod] = useState<'monthly' | 'yearly'>(
        currentSubscription?.duration === 'yearly' ? 'yearly' : 'monthly'
    );
    
    useEffect(() => {
        if (currentSubscription?.duration === 'yearly') {
            setPricingPeriod('yearly');
        } else {
            setPricingPeriod('monthly');
        }
    }, [currentSubscription?.duration]);

    const handleDelete = (plan: Plan) => {
        setDeletingPlan(plan);
    };

    const confirmDelete = () => {
        if (deletingPlan) {
            router.delete(route('plans.destroy', deletingPlan.id));
            setDeletingPlan(null);
        }
    };





    // Use active modules from Features
    const allModules = activeModules.sort((a, b) => a.alias.localeCompare(b.alias));

    const activePlans = isCompanyUser ? plans.filter(plan => plan.status) : plans;

    // Find the plan with the highest order count for "Most Popular" badge
    const mostPopularPlanId = activePlans.length > 0
        ? activePlans.reduce((prev, current) =>
            (current.orders_count || 0) > (prev.orders_count || 0) ? current : prev
          ).id
        : null;

    const hasModule = (plan: Plan, moduleObj: { module: string; alias: string; image: string; }) => {
        return Array.isArray(plan.modules) ? plan.modules.includes(moduleObj.module) : false;
    };

    const handleStartTrial = (plan: Plan) => {
        router.post(route('plans.start-trial', plan.id), {}, {
            preserveState: true,
            onSuccess: () => {
                // Reload the page to update sidebar modules and user trial info
                router.reload();
            }
        });
    };

    const handleAssignFreePlan = (plan: Plan) => {
        router.post(route('plans.assign-free', plan.id), {
            duration: pricingPeriod === 'monthly' ? 'Month' : 'Year'
        }, {
            preserveState: true
        });
    };

    const canStartTrial = (plan: Plan) => {
        return isCompanyUser &&
               plan.trial &&
               plan.trial_days > 0 &&
               (auth.user?.is_trial_done === 0 || auth.user?.is_trial_done === '0');
    };

    const isCurrentlySubscribed = (plan: Plan) => {
        if (!isCompanyUser || !currentSubscription) return false;
        return currentSubscription.plan_name === plan.name;
    };



    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Subscription Setting') }]}
            pageTitle={t('Subscription Setting')}
            pageActions={
                !isCompanyUser ? (
                    <TooltipProvider>
                        {canCreate && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Link href={route('plans.create')}>
                                        <Button size="sm">
                                            <Plus className="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Create')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                ) : null
            }
        >
            <Head title={t('Plans')} />

            <div className="space-y-6">
                {/* Monthly/Yearly Toggle */}
                <div className="flex items-center justify-center space-x-6">
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

                {/* Plans Content */}
                {activePlans.length > 0 ? (
                    <div className="space-y-6 overflow-x-auto pt-6">
                        {/* Plans Header Cards */}
                        <div className="grid gap-6" style={{ gridTemplateColumns: `300px repeat(${activePlans.length}, 280px)`, minWidth: `${300 + (activePlans.length * 280) + ((activePlans.length - 1) * 24)}px` }}>
                            {/* Features Header */}
                            <div className="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-gray-800 dark:to-gray-900 rounded-2xl p-6 border border-slate-200 dark:border-gray-700 sticky left-0 z-20">
                                <div className="flex items-center justify-center space-x-3">
                                    <h3 className="text-xl font-bold text-gray-900 dark:text-white">{t('Features')}</h3>
                                </div>
                            </div>

                            {/* Plan Header Cards */}
                            {activePlans.map((plan, index) => (
                                <div key={plan.id} className={`relative rounded-2xl p-6 border-2 ${
                                    plan.id === mostPopularPlanId && activePlans.length > 1
                                        ? 'bg-white dark:bg-gray-800 border-primary ring-2 ring-primary/20'
                                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
                                }`}>
                                    {plan.id === mostPopularPlanId && activePlans.length > 1 && (
                                        <div className="absolute -top-4 left-1/2 transform -translate-x-1/2 whitespace-nowrap z-10">
                                            <Badge className="bg-primary text-white px-4 py-2 text-sm font-bold shadow-lg border-2 border-white dark:border-gray-800">
                                                ⭐ {t('Most Popular')}
                                            </Badge>
                                        </div>
                                    )}

                                    {!isCompanyUser && (
                                        <div className="absolute top-4 right-4 z-10">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={route('plans.edit', plan.id)} className="flex items-center">
                                                            <Edit className="w-4 h-4 mr-2" />
                                                            {t('Edit')}
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onClick={() => handleDelete(plan)}
                                                        className="text-red-600 focus:text-red-600"
                                                    >
                                                        <Trash2 className="w-4 h-4 mr-2" />
                                                        {t('Delete')}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                    )}

                                    {isCompanyUser && isCurrentlySubscribed(plan) && (
                                        <div className="absolute -top-px -right-px w-[100px] h-[100px] overflow-hidden rounded-tr-[14px] z-10 pointer-events-none">
                                            <div className="absolute top-[20px] -right-[28px] w-[130px] transform rotate-45 bg-primary/20 text-primary dark:bg-primary/30 dark:text-white text-[12px] font-semibold text-center py-1 border border-primary/35 shadow-sm backdrop-blur-sm tracking-wide uppercase">
                                                {t('Active')}
                                            </div>
                                        </div>
                                    )}

                                    <div className={`text-center space-y-4 pt-2 ${!plan.status ? 'grayscale-0 opacity-100' : ''}`}>
                                        <div className={`pt-2 transition-all duration-300 ${!plan.status ? 'grayscale opacity-50 contrast-75' : ''}`}>
                                            <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-1">{plan.name}</h3>
                                            <p className="text-xs text-gray-600 dark:text-gray-300">{plan.description}</p>
                                        </div>

                                        <div className={`space-y-2 transition-all duration-300 ${!plan.status ? 'grayscale opacity-50' : ''}`}>
                                            {plan.free_plan ? (
                                                <div>
                                                    <div className="text-5xl font-black text-primary mb-1">
                                                        {t('Free')}
                                                    </div>
                                                    <div className="text-primary font-semibold">
                                                        {t('Forever')}
                                                    </div>
                                                </div>
                                            ) : (
                                                <div>
                                                    <div className="flex items-baseline justify-center space-x-1 mb-2">
                                                        <span className="text-5xl font-black text-gray-900 dark:text-white">
                                                            {formatAdminCurrency(pricingPeriod === 'monthly' ? plan.package_price_monthly : plan.package_price_yearly).replace('.00', '')}
                                                        </span>
                                                        <span className="text-xl text-gray-500 dark:text-gray-400 font-semibold">
                                                            /{pricingPeriod === 'monthly' ? t('mo') : t('yr')}
                                                        </span>
                                                    </div>
                                                </div>
                                            )}
                                        </div>

                                        <div className={`space-y-3 py-4 transition-all duration-300 ${!plan.status ? 'grayscale opacity-50' : ''}`}>
                                            <div className="flex items-center space-x-2">
                                                <div className="w-2 h-2 rounded-full bg-primary flex-shrink-0"></div>
                                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {plan.number_of_users === -1 ? t('Unlimited users') : `${plan.number_of_users} ${t('users')}`}
                                                </span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <div className="w-2 h-2 rounded-full bg-primary flex-shrink-0"></div>
                                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {formatStorage(plan.storage_limit)} {t('storage')}
                                                </span>
                                            </div>
                                            {plan.trial && (
                                                <div className="flex items-center space-x-2">
                                                    <div className="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></div>
                                                    <span className="text-sm font-medium text-green-600 dark:text-green-400">
                                                        {plan.trial_days}d {t('trial')}
                                                    </span>
                                                </div>
                                            )}
                                        </div>

                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Features Comparison Cards */}
                        <div className="space-y-4">
                                <div className="grid gap-6" style={{ gridTemplateColumns: `300px repeat(${activePlans.length}, 280px)`, minWidth: `${300 + (activePlans.length * 280) + ((activePlans.length - 1) * 24)}px` }}>
                                    {/* All Modules Card */}
                                    <div className="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 sticky left-0 z-20">
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-center py-2 h-10 border-b border-gray-200 dark:border-gray-600 mb-3">
                                                <span className="text-gray-900 dark:text-white font-semibold text-sm">
                                                    {t('Features')}
                                                </span>
                                            </div>
                                            {allModules.map((module) => (
                                                <div key={module.module} className="flex items-center justify-center py-0.5 h-6">
                                                    <span className="text-gray-700 dark:text-gray-300 capitalize text-center leading-none">
                                                        {module.alias}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Plan Feature Cards */}
                                    {activePlans.map((plan) => {
                                        const enabledFeatures = allModules.filter(module => hasModule(plan, module));
                                        const totalFeatures = allModules.length;

                                        return (
                                        <div key={plan.id} className={`bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 transition-all duration-300 ${!plan.status ? 'grayscale opacity-50' : ''}`}>
                                            <div className="space-y-3">
                                                <div className="flex items-center justify-center py-2 h-10 border-b border-gray-200 dark:border-gray-600 mb-3">
                                                    <span className="text-gray-900 dark:text-white font-semibold text-sm">
                                                        {enabledFeatures.length}/{totalFeatures} {t('Enabled')}
                                                    </span>
                                                </div>
                                                {allModules.map((module) => (
                                                    <div key={module.module} className="flex items-center justify-center py-0.5 h-6">
                                                        {hasModule(plan, module) ? (
                                                            <div className="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/40">
                                                                <Check className="w-3 h-3 text-green-600 dark:text-green-400" />
                                                            </div>
                                                        ) : (
                                                            <div className="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-700">
                                                                <X className="w-3 h-3 text-gray-400" />
                                                            </div>
                                                        )}
                                                    </div>
                                                ))}
                                                {isCompanyUser && (
                                                    <div className="pt-4 border-t space-y-2">
                                                        {isCurrentlySubscribed(plan) && currentSubscription ? (
                                                            <div className="text-center p-2 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                                                <p className="text-xs text-green-600 dark:text-green-300">
                                                                    {t('Expires on')} {currentSubscription.expire_date ? formatDate(currentSubscription.expire_date) : t('Lifetime')}
                                                                </p>
                                                            </div>
                                                        ) : auth.user?.trial_expire_date && auth.user.active_plan === plan.id ? (
                                                            <div className="text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                                                <p className="text-xs text-blue-600 dark:text-blue-300 mt-1">
                                                                    {t('Trial expires on')} {formatDate(auth.user.trial_expire_date)}
                                                                </p>
                                                            </div>
                                                        ) : (
                                                            <>
                                                                {plan.free_plan ? (
                                                                    <Button
                                                                        className="w-full"
                                                                        size="sm"
                                                                        onClick={() => handleAssignFreePlan(plan)}
                                                                    >
                                                                        {t('Subscribe to Plan')}
                                                                    </Button>
                                                                ) : (
                                                                    <Button
                                                                        className="w-full"
                                                                        size="sm"
                                                                        onClick={() => router.visit(route('plans.subscribe', { plan: plan.id, period: pricingPeriod }))}
                                                                    >
                                                                        {t('Subscribe to Plan')}
                                                                    </Button>
                                                                )}
                                                                {canStartTrial(plan) && (
                                                                    <Button
                                                                        className="w-full"
                                                                        size="sm"
                                                                        variant="outline"
                                                                        onClick={() => handleStartTrial(plan)}
                                                                    >
                                                                        <Clock className="h-4 w-4 mr-2" />
                                                                        {t('Start Trial')} ({plan.trial_days}d)
                                                                    </Button>
                                                                )}

                                                            </>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                        );
                                    })}
                                </div>
                        </div>
                    </div>
                ) : (
                    <div className="text-center py-12">
                        <div className="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <Plus className="w-6 h-6 text-gray-400" />
                        </div>
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            {t('No active plans found')}
                        </h3>
                        <p className="text-gray-600 dark:text-gray-400 mb-4">
                            {t('Create your first plan to get started')}
                        </p>
                        {canCreate && (
                            <Link href={route('plans.create')}>
                                <Button>
                                    <Plus className="w-4 h-4 mr-2" />
                                    {t('Create Plan')}
                                </Button>
                            </Link>
                        )}
                    </div>
                )}
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog open={!!deletingPlan} onOpenChange={() => setDeletingPlan(null)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Delete Plan')}</DialogTitle>
                        <DialogDescription>
                            {t('Are you sure you want to delete')} "{deletingPlan?.name}"? {t('This action cannot be undone.')}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeletingPlan(null)}>
                            {t('Cancel')}
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            {t('Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
