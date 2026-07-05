import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Mail, Save } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import { getCompanySetting, getPackageAlias } from '@/utils/helpers';
interface Notification {
  id: number;
  module: string;
  type: string;
  action: string;
  status: string;
  permissions: string;
}

interface EmailNotificationSettingsProps {
  userSettings?: any;
  canEdit?: boolean;
  isSuperAdmin?: boolean;
}

export default function EmailNotificationSettings({
  userSettings,
  canEdit = false,
  isSuperAdmin = false
}: EmailNotificationSettingsProps) {
  const { t } = useTranslation();
  const pageProps = usePage().props as any;
  const { notifications, auth } = pageProps;
  const activatedPackages = auth?.user?.activatedPackages || [];
  const [isLoading, setIsLoading] = useState(false);
  const canEditNotifications = canEdit || auth?.user?.permissions?.includes('manage-email-notification-settings');

  const [settings, setSettings] = useState<Record<string, string>>(() => {
    const initial: Record<string, string> = {};
    Object.values(notifications || {}).forEach((moduleNotifications: any) => {
      moduleNotifications.forEach((notification: Notification) => {
        initial[notification.action] = getCompanySetting(notification.action, pageProps) || 'off';
      });
    });
    return initial;
  });

  const handleToggle = (action: string, checked: boolean) => {
    setSettings(prev => ({
      ...prev,
      [action]: checked ? 'on' : 'off'
    }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    const userSettings: Record<string, string> = {};
    Object.values(notifications || {}).forEach((moduleNotifications: any) => {
      moduleNotifications.forEach((notification: Notification) => {
        if (auth.user?.permissions?.includes(notification.permissions)) {
          userSettings[notification.action] = settings[notification.action];
        }
      });
    });

    router.post(route('email.notification.setting.store'), {
      mail_noti: userSettings
    }, {
      onSuccess: () => {},
      onError: () => {},
      onFinish: () => setIsLoading(false)
    });
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Mail className="h-5 w-5" />
            {t('Email Notification Settings')}
          </CardTitle>
        </div>
        {canEditNotifications && (
          <Button className="order-2 rtl:order-1" onClick={saveSettings} disabled={isLoading} size="sm">
            <Save className="h-4 w-4 mr-2" />
            {isLoading ? t('Saving...') : t('Save Changes')}
          </Button>
        )}
      </CardHeader>
      <CardContent className="space-y-6">
        {(() => {
          const filteredModules = Object.keys(notifications || {}).filter(module =>
            module.toLowerCase() === 'general' || activatedPackages.includes(module)
          );
          return filteredModules.length > 0 ? (
            <Tabs defaultValue={filteredModules[0]}>
              <TabsList className="flex-wrap h-auto">
                {filteredModules.map((module) => (
                  <TabsTrigger key={module} value={module} className="capitalize">
                    {getPackageAlias(module)}
                  </TabsTrigger>
                ))}
              </TabsList>

              {filteredModules.map((module) => (
                  <TabsContent key={module} value={module}>
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {(notifications[module] || []).map((notification: Notification) => (
                          <div key={notification.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                          <span className="font-medium text-gray-3900">
                              {notification.action}
                          </span>
                          <Switch
                              checked={settings[notification.action] === 'on'}
                              onCheckedChange={(checked) => handleToggle(notification.action, checked)}
                              disabled={!canEditNotifications}
                          />
                          </div>
                      ))}
                      </div>
                  </TabsContent>
              ))}
            </Tabs>
          ) : (
            <p className="text-muted-foreground">
              No email notifications configured.
            </p>
          );
        })()}
      </CardContent>
    </Card>
  );
}
