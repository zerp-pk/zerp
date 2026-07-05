import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Settings, Save } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';
import languagesData from '@/../lang/language.json';

interface Language {
    code: string;
    name: string;
    countryCode: string;
    enabled?: boolean;
    flag?: string;
}

const getCountryFlag = (countryCode: string): string => {
  const codePoints = countryCode
    .toUpperCase()
    .split('')
    .map(char => 127397 + char.charCodeAt(0));
  return String.fromCodePoint(...codePoints);
};

interface SystemSettings {
  defaultLanguage: string;
  dateFormat: string;
  timeFormat: string;
  calendarStartDay: string;
  enableRegistration: string;
  enableEmailVerification: string;
  landingPageEnabled: string;
  termsConditionsUrl: string;
}

interface SystemSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function SystemSettings({ userSettings, auth }: SystemSettingsProps) {
  const { t } = useTranslation();
  const { defaultLanguages } = usePage().props as any;
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-system-settings');
  const isSuperAdmin = auth?.user?.type === 'superadmin';

  const [settings, setSettings] = useState<SystemSettings>({
    defaultLanguage: userSettings?.defaultLanguage || 'en',
    dateFormat: userSettings?.dateFormat || 'Y-m-d',
    timeFormat: userSettings?.timeFormat || 'H:i',
    calendarStartDay: userSettings?.calendarStartDay || '0',
    enableRegistration: userSettings?.enableRegistration === 'on' || userSettings?.enableRegistration === '1' ? 'on' : 'off',
    enableEmailVerification: userSettings?.enableEmailVerification === 'on' || userSettings?.enableEmailVerification === '1' ? 'on' : 'off',
    landingPageEnabled: userSettings?.landingPageEnabled === 'on' || userSettings?.landingPageEnabled === '1' ? 'on' : 'off',
    termsConditionsUrl: userSettings?.termsConditionsUrl || '',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        defaultLanguage: userSettings?.defaultLanguage || 'en',
        dateFormat: userSettings?.dateFormat || 'Y-m-d',
        timeFormat: userSettings?.timeFormat || 'H:i',
        calendarStartDay: userSettings?.calendarStartDay || '0',
        enableRegistration: userSettings?.enableRegistration === 'on' || userSettings?.enableRegistration === '1' ? 'on' : 'off',
        enableEmailVerification: userSettings?.enableEmailVerification === 'on' || userSettings?.enableEmailVerification === '1' ? 'on' : 'off',
        landingPageEnabled: userSettings?.landingPageEnabled === 'on' || userSettings?.landingPageEnabled === '1' ? 'on' : 'off',
        termsConditionsUrl: userSettings?.termsConditionsUrl || '',
      });
    }
  }, [userSettings]);

  const handleSelectChange = (name: string, value: string) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const handleSwitchChange = (name: string, value: boolean) => {
    setSettings(prev => ({ ...prev, [name]: value ? 'on' : 'off' }));
  };

  const handleInputChange = (name: string, value: string) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const saveSettings = () => {
    setIsLoading(true);

    router.post(route('settings.system.update'), {
      settings: settings as any
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setIsLoading(false);
        router.reload({ only: ['globalSettings'] });
      },
      onError: () => {
        setIsLoading(false);
      }
    });
  };

   // Use availableLanguages from props instead of static languagesData
    const languages: Language[] = (defaultLanguages || languagesData)
      .filter((lang: Language) => lang.enabled !== false)
      .map((lang: Language) => ({
          ...lang,
          flag: getCountryFlag(lang.countryCode)
      }));

  const dateFormats = [
    { value: 'Y-m-d', label: 'YYYY-MM-DD (2024-01-15)' },
    { value: 'm-d-Y', label: 'MM-DD-YYYY (01-15-2024)' },
    { value: 'd-m-Y', label: 'DD-MM-YYYY (15-01-2024)' },
    { value: 'Y/m/d', label: 'YYYY/MM/DD (2024/01/15)' },
    { value: 'm/d/Y', label: 'MM/DD/YYYY (01/15/2024)' },
    { value: 'd/m/Y', label: 'DD/MM/YYYY (15/01/2024)' },
  ];

  const timeFormats = [
    { value: 'H:i', label: '24 Hour (13:30)' },
    { value: 'g:i A', label: '12 Hour (1:30 PM)' },
  ];

  const days = [
    { value: '0', label: 'Sunday' },
    { value: '1', label: 'Monday' },
    { value: '2', label: 'Tuesday' },
    { value: '3', label: 'Wednesday' },
    { value: '4', label: 'Thursday' },
    { value: '5', label: 'Friday' },
    { value: '6', label: 'Saturday' },
  ];

  const timezones = [
    { value: 'UTC', label: 'UTC' },
    { value: 'America/New_York', label: 'Eastern Time (ET)' },
    { value: 'America/Chicago', label: 'Central Time (CT)' },
    { value: 'America/Denver', label: 'Mountain Time (MT)' },
    { value: 'America/Los_Angeles', label: 'Pacific Time (PT)' },
    { value: 'Europe/London', label: 'London (GMT)' },
    { value: 'Europe/Paris', label: 'Paris (CET)' },
    { value: 'Asia/Tokyo', label: 'Tokyo (JST)' },
    { value: 'Asia/Kolkata', label: 'India (IST)' },
  ];

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Settings className="h-5 w-5" />
            {t('System Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure system-wide settings for your application')}
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
        <div className="space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="space-y-3">
            <Label>{t('Default Language')}</Label>
            <Select
              value={settings.defaultLanguage}
              onValueChange={(value) => handleSelectChange('defaultLanguage', value)}
              disabled={!canEdit}
            >
              <SelectTrigger>
                <SelectValue placeholder={t('Select language')} />
              </SelectTrigger>
              <SelectContent>
                {languages.map((lang) => (
                  <SelectItem key={lang.code} value={lang.code}>
                    <div className="flex items-center gap-2">
                      <span>{lang.flag}</span>
                      <span>{lang.name}</span>
                    </div>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-3">
            <Label>{t('Date Format')}</Label>
            <Select
              value={settings.dateFormat}
              onValueChange={(value) => handleSelectChange('dateFormat', value)}
              disabled={!canEdit}
            >
              <SelectTrigger>
                <SelectValue placeholder={t('Select date format')} />
              </SelectTrigger>
              <SelectContent>
                {dateFormats.map((format) => (
                  <SelectItem key={format.value} value={format.value}>
                    {format.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-3">
            <Label>{t('Time Format')}</Label>
            <Select
              value={settings.timeFormat}
              onValueChange={(value) => handleSelectChange('timeFormat', value)}
              disabled={!canEdit}
            >
              <SelectTrigger>
                <SelectValue placeholder={t('Select time format')} />
              </SelectTrigger>
              <SelectContent>
                {timeFormats.map((format) => (
                  <SelectItem key={format.value} value={format.value}>
                    {format.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-3">
            <Label>{t('Calendar Start Day')}</Label>
            <Select
              value={settings.calendarStartDay}
              onValueChange={(value) => handleSelectChange('calendarStartDay', value)}
              disabled={!canEdit}
            >
              <SelectTrigger>
                <SelectValue placeholder={t('Select start day')} />
              </SelectTrigger>
              <SelectContent>
                {days.map((day) => (
                  <SelectItem key={day.value} value={day.value}>
                    {t(day.label)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        {isSuperAdmin && (
          <>
            <div className="space-y-3">
              <Label>{t('Terms & Conditions URL')}</Label>
              <Input
                type="url"
                value={settings.termsConditionsUrl}
                onChange={(e) => handleInputChange('termsConditionsUrl', e.target.value)}
                placeholder="https://example.com/terms"
                disabled={!canEdit}
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="space-y-3">
                <Label>{t('Enable Registration')}</Label>
                <div className="flex items-center space-x-2">
                  <Switch
                    checked={settings.enableRegistration === 'on'}
                    onCheckedChange={(checked) => handleSwitchChange('enableRegistration', checked)}
                    disabled={!canEdit}
                  />
                  <span className="text-sm text-muted-foreground">
                    {settings.enableRegistration === 'on' ? t('New users can register accounts') : t('Registration is disabled')}
                  </span>
                </div>
              </div>

              <div className="space-y-3">
                <Label>{t('Enable Email Verification')}</Label>
                <div className="flex items-center space-x-2">
                  <Switch
                    checked={settings.enableEmailVerification === 'on'}
                    onCheckedChange={(checked) => handleSwitchChange('enableEmailVerification', checked)}
                    disabled={!canEdit}
                  />
                  <span className="text-sm text-muted-foreground">
                    {settings.enableEmailVerification === 'on' ? t('Users must verify their email') : t('Email verification not required')}
                  </span>
                </div>
              </div>

              <div className="space-y-3">
                <Label>{t('Enable Landing Page')}</Label>
                <div className="flex items-center space-x-2">
                  <Switch
                    checked={settings.landingPageEnabled === 'on'}
                    onCheckedChange={(checked) => handleSwitchChange('landingPageEnabled', checked)}
                    disabled={!canEdit}
                  />
                  <span className="text-sm text-muted-foreground">
                    {settings.landingPageEnabled === 'on' ? t('Landing page is accessible') : t('Landing page is disabled')}
                  </span>
                </div>
              </div>
            </div>
          </>
        )}
        </div>
      </CardContent>
    </Card>
  );
}