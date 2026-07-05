import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { InfoIcon } from 'lucide-react';

interface UserSubscriptionInfo {
    is_superadmin: boolean;
    active_plan_id?: number;
    available_modules_count: number;
}

interface Props {
    userSubscriptionInfo: UserSubscriptionInfo;
    totalModulesCount: number;
}

export function SubscriptionInfo({ userSubscriptionInfo, totalModulesCount }: Props) {
    const { t } = useTranslation();

    if (userSubscriptionInfo.is_superadmin) {
        return (
            <Card className="border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
                <CardHeader className="pb-3">
                    <CardTitle className="text-sm flex items-center gap-2">
                        <InfoIcon className="w-4 h-4 text-blue-600" />
                        {t('Super Admin Access')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-blue-800 dark:text-blue-200">
                        {t('You have access to all {{count}} available features as a super admin.', { count: totalModulesCount })}
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="border-orange-200 bg-orange-50 dark:bg-orange-900/20 dark:border-orange-800">
            <CardHeader className="pb-3">
                <CardTitle className="text-sm flex items-center gap-2">
                    <InfoIcon className="w-4 h-4 text-orange-600" />
                    {t('Subscription Features')}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
                <div className="flex items-center justify-between">
                    <span className="text-sm text-orange-800 dark:text-orange-200">
                        {t('Available Features')}
                    </span>
                    <Badge variant="outline" className="text-orange-700 border-orange-300">
                        {userSubscriptionInfo.available_modules_count}
                    </Badge>
                </div>
                <p className="text-xs text-orange-700 dark:text-orange-300">
                    {t('Only modules from your current subscription package are shown. To access more modules, upgrade your subscription.')}
                </p>
            </CardContent>
        </Card>
    );
}