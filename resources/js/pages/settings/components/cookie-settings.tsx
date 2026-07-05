import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Cookie, Save, Download } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';

interface CookieSettings {
  enableCookiePopup: boolean;
  enableLogging: boolean;
  strictlyNecessaryCookies: boolean;
  cookieTitle: string;
  strictlyCookieTitle: string;
  cookieDescription: string;
  strictlyCookieDescription: string;
  contactUsDescription: string;
  contactUsUrl: string;
}

interface CookieSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

const getInitialSettings = (userSettings?: Record<string, string>): CookieSettings => ({
  enableCookiePopup: userSettings?.enableCookiePopup === '1' || false,
  enableLogging: userSettings?.enableLogging === '1' || false,
  strictlyNecessaryCookies: userSettings?.strictlyNecessaryCookies === '1' || true,
  cookieTitle: userSettings?.cookieTitle || 'Cookie Consent',
  strictlyCookieTitle: userSettings?.strictlyCookieTitle || 'Strictly Necessary Cookies',
  cookieDescription: userSettings?.cookieDescription || 'We use cookies to enhance your browsing experience and provide personalized content.',
  strictlyCookieDescription: userSettings?.strictlyCookieDescription || 'These cookies are essential for the website to function properly.',
  contactUsDescription: userSettings?.contactUsDescription || 'If you have any questions about our cookie policy, please contact us.',
  contactUsUrl: userSettings?.contactUsUrl || 'https://example.com/contact'
});

export default function CookieSettings({ userSettings, auth }: CookieSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-cookie-settings');
  const [settings, setSettings] = useState<CookieSettings>(() => getInitialSettings(userSettings));

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

    router.post(route('settings.cookie.update'), {
      settings: settings as any
    }, {
      preserveScroll: true,
      onSuccess: () => {},
      onError: () => {},
      onFinish: () => {
        setIsLoading(false);
      }
    });
  };

  const downloadCookieData = () => {
    window.location.href = route('settings.cookie.download');
  };



  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Cookie className="h-5 w-5" />
            {t('Cookie Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure cookie consent and privacy settings for your application')}
          </p>
        </div>
        <div className="flex gap-2 order-2 rtl:order-1">
          <Button onClick={downloadCookieData} variant="outline" size="sm">
            <Download className="h-4 w-4 mr-2" />
            {t('Download Cookie Data')}
          </Button>
          {canEdit && (
            <Button onClick={saveSettings} disabled={isLoading} size="sm">
              <Save className="h-4 w-4 mr-2" />
              {isLoading ? t('Saving...') : t('Save Changes')}
            </Button>
          )}
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Enable Cookie Popup Switch */}
          <div className="flex items-center justify-between space-x-2">
            <div className="space-y-0.5">
              <Label htmlFor="enableCookiePopup">{t("Enable Cookie Popup")}</Label>
              <p className="text-sm text-muted-foreground">
                {t("Show cookie consent popup to visitors")}
              </p>
            </div>
            <Switch
              id="enableCookiePopup"
              checked={settings.enableCookiePopup}
              onCheckedChange={(checked) => handleChange('enableCookiePopup', checked)}
              disabled={!canEdit}
            />
          </div>

          {/* Enable Logging Switch */}
          <div className="flex items-center justify-between space-x-2">
            <div className="space-y-0.5">
              <Label htmlFor="enableLogging">{t("Enable Logging")}</Label>
              <p className="text-sm text-muted-foreground">
                {t("Enable cookie activity logging")}
              </p>
            </div>
            <Switch
              id="enableLogging"
              checked={settings.enableLogging}
              onCheckedChange={(checked) => handleChange('enableLogging', checked)}
              disabled={!canEdit}
            />
          </div>

          {/* Strictly Necessary Cookies Switch */}
          <div className="flex items-center justify-between space-x-2">
            <div className="space-y-0.5">
              <Label htmlFor="strictlyNecessaryCookies">{t("Strictly Necessary Cookies")}</Label>
              <p className="text-sm text-muted-foreground">
                {t("Enable strictly necessary cookies")}
              </p>
            </div>
            <Switch
              id="strictlyNecessaryCookies"
              checked={settings.strictlyNecessaryCookies}
              onCheckedChange={(checked) => handleChange('strictlyNecessaryCookies', checked)}
              disabled={!canEdit}
            />
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Cookie Title */}
          <div className="grid gap-2">
            <Label htmlFor="cookieTitle">{t("Cookie Title")}</Label>
            <Input
              id="cookieTitle"
              type="text"
              value={settings.cookieTitle}
              onChange={(e) => handleChange('cookieTitle', e.target.value)}
              disabled={!canEdit}
              placeholder={t("Enter the main cookie consent title")}
            />
          </div>

          {/* Strictly Cookie Title */}
          <div className="grid gap-2">
            <Label htmlFor="strictlyCookieTitle">{t("Strictly Cookie Title")}</Label>
            <Input
              id="strictlyCookieTitle"
              type="text"
              value={settings.strictlyCookieTitle}
              onChange={(e) => handleChange('strictlyCookieTitle', e.target.value)}
              disabled={!canEdit}
              placeholder={t("Enter the strictly necessary cookies title")}
            />
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Cookie Description */}
          <div className="grid gap-2">
            <Label htmlFor="cookieDescription">{t("Cookie Description")}</Label>
            <Textarea
              id="cookieDescription"
              value={settings.cookieDescription}
              onChange={(e) => handleChange('cookieDescription', e.target.value)}
              disabled={!canEdit}
              placeholder={t("Enter the cookie consent description")}
              rows={4}
            />
          </div>

          {/* Strictly Cookie Description */}
          <div className="grid gap-2">
            <Label htmlFor="strictlyCookieDescription">{t("Strictly Cookie Description")}</Label>
            <Textarea
              id="strictlyCookieDescription"
              value={settings.strictlyCookieDescription}
              onChange={(e) => handleChange('strictlyCookieDescription', e.target.value)}
              disabled={!canEdit}
              placeholder={t("Enter the strictly necessary cookies description")}
              rows={4}
            />
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Contact Us Description */}
          <div className="grid gap-2">
            <Label htmlFor="contactUsDescription">{t("Contact Us Description")}</Label>
            <Textarea
              id="contactUsDescription"
              value={settings.contactUsDescription}
              onChange={(e) => handleChange('contactUsDescription', e.target.value)}
              disabled={!canEdit}
              placeholder={t("Enter the contact us description for cookie inquiries")}
              rows={3}
            />
          </div>

          {/* Contact Us URL */}
          <div className="grid gap-2">
            <Label htmlFor="contactUsUrl">{t("Contact Us URL")}</Label>
            <Input
              id="contactUsUrl"
              type="url"
              value={settings.contactUsUrl}
              onChange={(e) => handleChange('contactUsUrl', e.target.value)}
              disabled={!canEdit}
              placeholder={t("Enter the contact us URL for cookie inquiries")}
            />
          </div>
        </div>


        </div>
      </CardContent>
    </Card>
  );
}