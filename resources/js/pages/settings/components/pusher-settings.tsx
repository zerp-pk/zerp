import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Radio, Save, Eye, EyeOff, Zap } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';

interface PusherSettings {
  app_id: string;
  app_key: string;
  app_secret: string;
  app_cluster: string;
  enabled: boolean;
}

interface PusherSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

const getInitialSettings = (userSettings?: Record<string, string>): PusherSettings => ({
  app_id: userSettings?.pusher_app_id || '',
  app_key: userSettings?.pusher_app_key || '',
  app_secret: userSettings?.pusher_app_secret || '',
  app_cluster: userSettings?.pusher_app_cluster || 'mt1',
  enabled: userSettings?.pusher_enabled === '1' || false
});

export default function PusherSettings({ userSettings, auth }: PusherSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-pusher-settings');

  const [settings, setSettings] = useState<PusherSettings>(() => getInitialSettings(userSettings));
  const [showSecret, setShowSecret] = useState(false);

  useEffect(() => {
    if (userSettings) {
      setSettings(getInitialSettings(userSettings));
    }
  }, [userSettings]);

  const handleChange = (name: string, value: string | boolean) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    router.post(route('settings.pusher.update'), {
      settings: { ...settings }
    }, {
      preserveScroll: true,
      onSuccess: () => {},
      onError: () => {},
      onFinish: () => {
        setIsLoading(false);
      }
    });
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Radio className="h-5 w-5" />
            {t('Pusher Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure Pusher for real-time messaging and notifications')}
          </p>
        </div>
        {canEdit && (
          <Button className="order-2 rtl:order-1" onClick={saveSettings} disabled={isLoading} size="sm">
            <Save className="h-4 w-4 mr-2" />
            {isLoading ? t('Saving...') : t('Save Changes')}
          </Button>
        )}
      </CardHeader>
      <CardContent>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Main Pusher Settings */}
          <div className="lg:col-span-2">
            <div className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-1.5">
                  <Label htmlFor="app_id" className="font-medium">{t("App ID")}</Label>
                  <Input
                    id="app_id"
                    value={settings.app_id}
                    onChange={(e) => handleChange('app_id', e.target.value)}
                    disabled={!canEdit}
                    placeholder="123456"
                  />
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="app_key" className="font-medium">{t("App Key")}</Label>
                  <Input
                    id="app_key"
                    value={settings.app_key}
                    onChange={(e) => handleChange('app_key', e.target.value)}
                    disabled={!canEdit}
                    placeholder="your-app-key"
                  />
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="app_secret" className="font-medium">{t("App Secret")}</Label>
                  <div className="relative">
                    <Input
                      id="app_secret"
                      type={showSecret ? "text" : "password"}
                      value={settings.app_secret}
                      onChange={(e) => handleChange('app_secret', e.target.value)}
                      disabled={!canEdit}
                      placeholder="••••••••••••"
                      className="pr-10"
                    />
                    {canEdit && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-0 top-0 h-full px-3 py-2 hover:bg-transparent"
                        onClick={() => setShowSecret(!showSecret)}
                      >
                        {showSecret ? (
                          <EyeOff className="h-4 w-4 text-muted-foreground" />
                        ) : (
                          <Eye className="h-4 w-4 text-muted-foreground" />
                        )}
                      </Button>
                    )}
                  </div>
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="app_cluster" className="font-medium">{t("App Cluster")}</Label>
                  <Input
                    id="app_cluster"
                    value={settings.app_cluster}
                    onChange={(e) => handleChange('app_cluster', e.target.value)}
                    disabled={!canEdit}
                    placeholder="mt1"
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Info Section */}
          <div className="lg:col-span-1">
            <Card>
              <CardContent className="pt-6">
                <div className="space-y-4">
                  <div className="flex items-center gap-2 mb-4">
                    <Radio className="h-4 w-4 text-primary" />
                    <h3 className="text-base font-medium">{t("Real-time Features")}</h3>
                  </div>

                  <div className="space-y-3 text-sm text-muted-foreground">
                    <p>{t("Pusher enables real-time messaging and notifications in your application.")}</p>
                    
                    <div className="space-y-2">
                      <p className="font-medium text-foreground">{t("Features enabled:")}</p>
                      <ul className="space-y-1 ml-4">
                        <li>• {t("Instant messaging")}</li>
                        <li>• {t("Real-time notifications")}</li>
                        <li>• {t("Live updates")}</li>
                      </ul>
                    </div>

                    <div className="p-3 bg-blue-50 rounded-md border border-blue-200">
                      <p className="text-blue-700 text-xs">
                        {t("Get your Pusher credentials from")} <a href="https://pusher.com" target="_blank" rel="noopener noreferrer" className="underline">pusher.com</a>
                      </p>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}