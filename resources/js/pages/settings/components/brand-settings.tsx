import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Palette, Save, Upload, FileText, Settings as SettingsIcon, Layout, Moon, SidebarIcon, Check } from 'lucide-react';
import MediaPicker from '@/components/MediaPicker';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { getImagePath } from '@/utils/helpers';
import { ThemePreview } from './theme-preview';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';

interface BrandSettings {
  logo_dark: string;
  logo_light: string;
  favicon: string;
  titleText: string;
  footerText: string;
  sidebarVariant: string;
  sidebarStyle: string;
  layoutDirection: string;
  themeMode: string;
  themeColor: string;
  customColor: string;
  [key: string]: any;
}

interface BrandSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function BrandSettings({ userSettings, auth }: BrandSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-brand-settings');
  const [activeSection, setActiveSection] = useState<'logos' | 'text' | 'theme'>('logos');
  const [settings, setSettings] = useState<BrandSettings>({
    logo_dark: userSettings?.logo_dark || '',
    logo_light: userSettings?.logo_light || '',
    favicon: userSettings?.favicon || '',
    titleText: userSettings?.titleText || 'Zerp',
    footerText: userSettings?.footerText || `© ${new Date().getFullYear()} Zerp. All rights reserved.`,
    sidebarVariant: userSettings?.sidebarVariant || 'inset',
    sidebarStyle: userSettings?.sidebarStyle || 'plain',
    layoutDirection: userSettings?.layoutDirection || 'ltr',
    themeMode: userSettings?.themeMode || 'light',
    themeColor: userSettings?.themeColor || 'zawat',
    customColor: userSettings?.customColor || '#DA8F29',
  });

  // Update settings when userSettings prop changes
  useEffect(() => {
    if (userSettings) {
      setSettings({
        logo_dark: userSettings?.logo_dark || '',
        logo_light: userSettings?.logo_light || '',
        favicon: userSettings?.favicon || '',
        titleText: userSettings?.titleText || 'Zerp',
        footerText: userSettings?.footerText || `© ${new Date().getFullYear()} Zerp. All rights reserved.`,
        sidebarVariant: userSettings?.sidebarVariant || 'inset',
        sidebarStyle: userSettings?.sidebarStyle || 'plain',
        layoutDirection: userSettings?.layoutDirection || 'ltr',
        themeMode: userSettings?.themeMode || 'light',
        themeColor: userSettings?.themeColor || 'zawat',
        customColor: userSettings?.customColor || '#DA8F29',
      });
    }
  }, [userSettings]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setSettings(prev => ({ ...prev, [name]: value }));
  };


  const handleMediaSelect = (name: string, url: string | string[]) => {
    const urlString = Array.isArray(url) ? url[0] || '' : url;
    setSettings(prev => ({ ...prev, [name]: urlString }));
  };

  const handleSelectChange = (name: string, value: string) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };





  const saveSettings = () => {
    setIsLoading(true);

    router.post(route('settings.brand.update'), {
      settings: settings
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setIsLoading(false);
        // Reload settings to get updated values
        router.reload({ only: ['globalSettings'] });
      },
      onError: () => {
        setIsLoading(false);
      }
    });
  };

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <Palette className="h-5 w-5" />
            {t('Brand Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t("Customize your application's branding and appearance")}
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
          <div className="lg:col-span-2">
            <div className="flex space-x-2 mb-6">
              <Button
                variant={activeSection === 'logos' ? "default" : "outline"}
                size="sm"
                onClick={() => setActiveSection('logos')}
                className="flex-1"
              >
                <Upload className="h-4 w-4 mr-2" />
                {t('Logos')}
              </Button>
              <Button
                variant={activeSection === 'text' ? "default" : "outline"}
                size="sm"
                onClick={() => setActiveSection('text')}
                className="flex-1"
              >
                <FileText className="h-4 w-4 mr-2" />
                {t('Text')}
              </Button>
              <Button
                variant={activeSection === 'theme' ? "default" : "outline"}
                size="sm"
                onClick={() => setActiveSection('theme')}
                className="flex-1"
              >
                <SettingsIcon className="h-4 w-4 mr-2" />
                {t('Theme')}
              </Button>
            </div>

            {/* Logos Section */}
            {activeSection === 'logos' && (
              <div className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div className="space-y-3">
                    <Label>{t('Logo (Dark Mode)')}</Label>
                    <div className="flex flex-col gap-3">
                      <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 h-32">
                        {settings.logo_dark ? (
                          <img
                            src={getImagePath(settings.logo_dark)}
                            alt={t('Dark Logo')}
                            className="max-h-full max-w-full object-contain"
                          />
                        ) : (
                          <div className="text-muted-foreground flex flex-col items-center gap-2">
                            <div className="h-12 w-24 bg-muted flex items-center justify-center rounded border border-dashed">
                              <span className="font-semibold text-muted-foreground">{t('Logo')}</span>
                            </div>
                            <span className="text-xs">{t('No logo selected')}</span>
                          </div>
                        )}
                      </div>
                      <MediaPicker
                        value={settings.logo_dark}
                        onChange={(url) => handleMediaSelect('logo_dark', url)}
                        placeholder={t('Select dark mode logo...')}
                        showPreview={false}
                        disabled={!canEdit}
                      />
                    </div>
                  </div>

                  <div className="space-y-3">
                    <Label>{t('Logo (Light Mode)')}</Label>
                    <div className="flex flex-col gap-3">
                      <div className="border rounded-md p-4 flex items-center justify-center bg-gray-800 h-32">
                        {settings.logo_light ? (
                          <img
                            src={getImagePath(settings.logo_light)}
                            alt={t('Light Logo')}
                            className="max-h-full max-w-full object-contain"
                          />
                        ) : (
                          <div className="text-muted-foreground flex flex-col items-center gap-2">
                            <div className="h-12 w-24 bg-muted flex items-center justify-center rounded border border-dashed">
                              <span className="font-semibold text-muted-foreground">{t('Logo')}</span>
                            </div>
                            <span className="text-xs">{t('No logo selected')}</span>
                          </div>
                        )}
                      </div>
                      <MediaPicker
                        value={settings.logo_light}
                        onChange={(url) => handleMediaSelect('logo_light', url)}
                        placeholder={t('Select light mode logo...')}
                        showPreview={false}
                        disabled={!canEdit}
                      />
                    </div>
                  </div>

                  <div className="space-y-3">
                    <Label>{t('Favicon')}</Label>
                    <div className="flex flex-col gap-3">
                      <div className="border rounded-md p-4 flex items-center justify-center bg-muted/30 h-20">
                        {settings.favicon ? (
                          <img
                            src={getImagePath(settings.favicon)}
                            alt={t('Favicon')}
                            className="h-16 w-16 object-contain"
                          />
                        ) : (
                          <div className="text-muted-foreground flex flex-col items-center gap-1">
                            <div className="h-10 w-10 bg-muted flex items-center justify-center rounded border border-dashed">
                              <span className="font-semibold text-xs text-muted-foreground">{t('Icon')}</span>
                            </div>
                            <span className="text-xs">{t('No favicon selected')}</span>
                          </div>
                        )}
                      </div>
                      <MediaPicker
                        value={settings.favicon}
                        onChange={(url) => handleMediaSelect('favicon', url)}
                        placeholder={t('Select favicon...')}
                        showPreview={false}
                        disabled={!canEdit}
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Text Section */}
            {activeSection === 'text' && (
              <div className="space-y-6">
                <div className="grid grid-cols-1 gap-6">
                  <div className="space-y-3">
                    <Label htmlFor="titleText">{t('Title Text')}</Label>
                    <Input
                      id="titleText"
                      name="titleText"
                      value={settings.titleText}
                      onChange={handleInputChange}
                      placeholder="Zerp"
                      disabled={!canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Application title displayed in the browser tab')}
                    </p>
                  </div>

                  <div className="space-y-3">
                    <Label htmlFor="footerText">{t('Footer Text')}</Label>
                    <Input
                      id="footerText"
                      name="footerText"
                      value={settings.footerText}
                      onChange={handleInputChange}
                      placeholder={t(`© ${new Date().getFullYear()} Zerp. All rights reserved.`)}
                      disabled={!canEdit}
                    />
                    <p className="text-xs text-muted-foreground">
                      {t('Text displayed in the footer')}
                    </p>
                  </div>
                </div>
              </div>
            )}

            {/* Theme Section */}
            {activeSection === 'theme' && (
              <div className="space-y-6">
                <div className={`flex flex-col space-y-8 ${!canEdit ? 'pointer-events-none opacity-60' : ''}`}>
                  {/* Theme Color Section */}
                  <div className="space-y-4">
                    <div className="flex items-center">
                      <Palette className="h-5 w-5 mr-2 text-muted-foreground" />
                      <h3 className="text-base font-medium">{t('Theme Color')}</h3>
                    </div>
                    <Separator className="my-2" />

                    <div className="grid grid-cols-6 gap-2">
                      {Object.entries({ blue: '#3b82f6', zawat: '#DA8F29', purple: '#8b5cf6', orange: '#f97316', red: '#ef4444' }).map(([color, hex]) => (
                        <Button
                          key={color}
                          type="button"
                          variant={settings.themeColor === color ? "default" : "outline"}
                          className="h-8 w-full p-0 relative"
                          style={{ backgroundColor: settings.themeColor === color ? hex : 'transparent' }}
                          onClick={() => handleSelectChange('themeColor', color)}
                        >
                          <span
                            className="absolute inset-1 rounded-sm"
                            style={{ backgroundColor: hex }}
                          />
                        </Button>
                      ))}
                      <Button
                        type="button"
                        variant={settings.themeColor === 'custom' ? "default" : "outline"}
                        className="h-8 w-full p-0 relative"
                        style={{ backgroundColor: settings.themeColor === 'custom' ? settings.customColor : 'transparent' }}
                        onClick={() => handleSelectChange('themeColor', 'custom')}
                      >
                        <span
                          className="absolute inset-1 rounded-sm"
                          style={{ backgroundColor: settings.customColor }}
                        />
                      </Button>
                    </div>

                    {settings.themeColor === 'custom' && (
                      <div className="space-y-2 mt-4">
                        <Label htmlFor="customColor">{t('Custom Color')}</Label>
                        <div className="flex gap-2">
                          <div className="relative">
                            <Input
                              id="colorPicker"
                              type="color"
                              value={settings.customColor}
                              onChange={(e) => handleSelectChange('customColor', e.target.value)}
                              className="absolute inset-0 opacity-0 cursor-pointer"
                            />
                            <div
                              className="w-10 h-10 rounded border cursor-pointer"
                              style={{ backgroundColor: settings.customColor }}
                            />
                          </div>
                          <Input
                            id="customColor"
                            name="customColor"
                            type="text"
                            value={settings.customColor}
                            onChange={(e) => handleSelectChange('customColor', e.target.value)}
                            placeholder="#DA8F29"
                          />
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Sidebar Section */}
                  <div className="space-y-4">
                    <div className="flex items-center">
                      <SidebarIcon className="h-5 w-5 mr-2 text-muted-foreground" />
                      <h3 className="text-base font-medium">{t('Sidebar')}</h3>
                    </div>
                    <Separator className="my-2" />

                    <div className="space-y-6">
                      <div>
                        <Label className="mb-2 block">{t('Sidebar Variant')}</Label>
                        <div className="grid grid-cols-3 gap-3">
                          {['inset', 'floating', 'minimal'].map((variant) => (
                            <Button
                              key={variant}
                              type="button"
                              variant={settings.sidebarVariant === variant ? "default" : "outline"}
                              className="h-10 justify-start"
                              onClick={() => handleSelectChange('sidebarVariant', variant)}
                            >
                              {variant.charAt(0).toUpperCase() + variant.slice(1)}
                              {settings.sidebarVariant === variant && (
                                <Check className="h-4 w-4 ml-2" />
                              )}
                            </Button>
                          ))}
                        </div>
                      </div>

                      <div>
                        <Label className="mb-2 block">{t('Sidebar Style')}</Label>
                        <div className="grid grid-cols-3 gap-3">
                          {[
                            { id: 'plain', name: 'Plain' },
                            { id: 'colored', name: 'Colored' },
                            { id: 'gradient', name: 'Gradient' }
                          ].map((style) => (
                            <Button
                              key={style.id}
                              type="button"
                              variant={settings.sidebarStyle === style.id ? "default" : "outline"}
                              className="h-10 justify-start"
                              onClick={() => handleSelectChange('sidebarStyle', style.id)}
                            >
                              {style.name}
                              {settings.sidebarStyle === style.id && (
                                <Check className="h-4 w-4 ml-2" />
                              )}
                            </Button>
                          ))}
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Layout Section */}
                  <div className="space-y-4">
                    <div className="flex items-center">
                      <Layout className="h-5 w-5 mr-2 text-muted-foreground" />
                      <h3 className="text-base font-medium">{t('Layout')}</h3>
                    </div>
                    <Separator className="my-2" />

                    <div className="space-y-2">
                      <Label className="mb-2 block">{t('Layout Direction')}</Label>
                      <div className="grid grid-cols-2 gap-2">
                        <Button
                          type="button"
                          variant={settings.layoutDirection === "ltr" ? "default" : "outline"}
                          className="h-10 justify-start"
                          onClick={() => handleSelectChange('layoutDirection', 'ltr')}
                        >
                          {t('Left-to-Right')}
                          {settings.layoutDirection === "ltr" && (
                            <Check className="h-4 w-4 ml-2" />
                          )}
                        </Button>
                        <Button
                          type="button"
                          variant={settings.layoutDirection === "rtl" ? "default" : "outline"}
                          className="h-10 justify-start"
                          onClick={() => handleSelectChange('layoutDirection', 'rtl')}
                        >
                          {t('Right-to-Left')}
                          {settings.layoutDirection === "rtl" && (
                            <Check className="h-4 w-4 ml-2" />
                          )}
                        </Button>
                      </div>
                    </div>
                  </div>

                  {/* Mode Section */}
                  <div className="space-y-4">
                    <div className="flex items-center">
                      <Moon className="h-5 w-5 mr-2 text-muted-foreground" />
                      <h3 className="text-base font-medium">{t('Theme Mode')}</h3>
                    </div>
                    <Separator className="my-2" />

                    <div className="space-y-2">
                      <div className="grid grid-cols-3 gap-2">
                        <Button
                          type="button"
                          variant={settings.themeMode === "light" ? "default" : "outline"}
                          className="h-10 justify-start"
                          onClick={() => handleSelectChange('themeMode', 'light')}
                        >
                          {t('Light')}
                          {settings.themeMode === "light" && (
                            <Check className="h-4 w-4 ml-2" />
                          )}
                        </Button>
                        <Button
                          type="button"
                          variant={settings.themeMode === "dark" ? "default" : "outline"}
                          className="h-10 justify-start"
                          onClick={() => handleSelectChange('themeMode', 'dark')}
                        >
                          {t('Dark')}
                          {settings.themeMode === "dark" && (
                            <Check className="h-4 w-4 ml-2" />
                          )}
                        </Button>
                        <Button
                          type="button"
                          variant={settings.themeMode === "system" ? "default" : "outline"}
                          className="h-10 justify-start"
                          onClick={() => handleSelectChange('themeMode', 'system')}
                        >
                          {t('System')}
                          {settings.themeMode === "system" && (
                            <Check className="h-4 w-4 ml-2" />
                          )}
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Preview Column */}
          <div className="lg:col-span-1">
            <div className="sticky top-20 space-y-6">
              <div className="border rounded-md p-4">
                <div className="flex items-center gap-2 mb-4">
                  <Palette className="h-4 w-4" />
                  <h3 className="font-medium">{t('Live Preview')}</h3>
                </div>

                {/* Comprehensive Theme Preview */}
                <ThemePreview
                  logoDark={settings.logo_dark}
                  logoLight={settings.logo_light}
                  themeColor={settings.themeColor}
                  customColor={settings.customColor}
                  sidebarVariant={settings.sidebarVariant}
                  sidebarStyle={settings.sidebarStyle}
                  layoutDirection={settings.layoutDirection}
                  themeMode={settings.themeMode}
                />

                {/* Text Preview */}
                <div className="mt-4 pt-4 border-t">
                  <div className="text-xs mb-2 text-muted-foreground">{t('Title:')} <span className="font-medium text-foreground">{settings.titleText}</span></div>
                  <div className="text-xs text-muted-foreground">{t('Footer:')} <span className="font-medium text-foreground">{settings.footerText}</span></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}