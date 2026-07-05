import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { getImagePath } from '@/utils/helpers';
import { Separator } from '@/components/ui/separator';
import { Search, Save, Upload, X, Eye, AlertCircle, CheckCircle2, Globe } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import MediaPicker from '@/components/MediaPicker';

interface SeoSettings {
  metaTitle: string;
  metaKeywords: string;
  metaDescription: string;
  metaImage: string;
}

interface SeoSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function SeoSettings({ userSettings, auth }: SeoSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-seo-settings');
  const [showPreview, setShowPreview] = useState(false);

  const [settings, setSettings] = useState<SeoSettings>({
    metaTitle: userSettings?.metaTitle || '',
    metaKeywords: userSettings?.metaKeywords || '',
    metaDescription: userSettings?.metaDescription || '',
    metaImage: userSettings?.metaImage || '',
  });

  useEffect(() => {
    if (userSettings) {
      setSettings({
        metaTitle: userSettings?.metaTitle || '',
        metaKeywords: userSettings?.metaKeywords || '',
        metaDescription: userSettings?.metaDescription || '',
        metaImage: userSettings?.metaImage || '',
      });
    }
  }, [userSettings]);



  const handleInputChange = (name: string, value: string) => {
    setSettings(prev => ({ ...prev, [name]: value }));
  };

  const handleMediaSelect = (url: string | string[]) => {
    const urlString = Array.isArray(url) ? url[0] || '' : url;
    setSettings(prev => ({ ...prev, metaImage: urlString }));
  };

  const getDescriptionStatus = () => {
    const length = settings.metaDescription.length;
    if (length === 0) return { status: 'empty', color: 'text-muted-foreground', icon: AlertCircle };
    if (length < 120) return { status: 'short', color: 'text-orange-500', icon: AlertCircle };
    if (length <= 160) return { status: 'good', color: 'text-green-500', icon: CheckCircle2 };
    return { status: 'long', color: 'text-red-500', icon: AlertCircle };
  };

  const getKeywordsCount = () => {
    return settings.metaKeywords.split(',').filter(k => k.trim()).length;
  };

  const saveSettings = () => {
    if (!settings.metaTitle.trim()) {
      
      return;
    }

    if (!settings.metaDescription.trim()) {
      
      return;
    }

    setIsLoading(true);

    router.post(route('settings.seo.update'), { settings: settings as any }, {
      preserveScroll: true,
      onSuccess: () => {
        setIsLoading(false);
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
            <Search className="h-5 w-5" />
            {t('SEO Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure SEO settings to improve your website\'s search engine visibility')}
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
          <div className="lg:col-span-2 space-y-6">
        {/* Meta Title */}
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <Label htmlFor="metaTitle">{t('Meta Title')}</Label>
            <span className={`text-sm ${
              settings.metaTitle.length > 60 ? 'text-red-500' :
              settings.metaTitle.length > 50 ? 'text-orange-500' : 'text-green-500'
            }`}>
              {settings.metaTitle.length}/60
            </span>
          </div>
          <Input
            id="metaTitle"
            value={settings.metaTitle}
            onChange={(e) => handleInputChange('metaTitle', e.target.value)}
            placeholder={t('Enter page title for search engines')}
            maxLength={60}
            disabled={!canEdit}
          />
          <p className="text-xs text-muted-foreground">
            {t('Appears as the clickable headline in search results')}
          </p>
        </div>

        {/* Meta Description */}
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <Label htmlFor="metaDescription">{t('Meta Description')}</Label>
            <div className="flex items-center gap-1">
              {(() => {
                const { color, icon: Icon } = getDescriptionStatus();
                return (
                  <>
                    <Icon className={`h-4 w-4 ${color}`} />
                    <span className={`text-sm ${color}`}>
                      {settings.metaDescription.length}/160
                    </span>
                  </>
                );
              })()}
            </div>
          </div>
          <Textarea
            id="metaDescription"
            value={settings.metaDescription}
            onChange={(e) => handleInputChange('metaDescription', e.target.value)}
            placeholder={t('Write a compelling description that summarizes your page content...')}
            maxLength={160}
            rows={3}
            disabled={!canEdit}
            className="resize-none"
          />
          <p className="text-xs text-muted-foreground">
            {t('Appears below the title in search results. Optimal length: 120-160 characters')}
          </p>
        </div>

        {/* Meta Keywords */}
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <Label htmlFor="metaKeywords">{t('Meta Keywords')}</Label>
            <Badge variant="outline">{getKeywordsCount()} {t('keywords')}</Badge>
          </div>
          <Input
            id="metaKeywords"
            value={settings.metaKeywords}
            onChange={(e) => handleInputChange('metaKeywords', e.target.value)}
            placeholder={t('seo, optimization, website, keywords')}
            disabled={!canEdit}
          />
          <p className="text-xs text-muted-foreground">
            {t('Comma-separated keywords relevant to your content')}
          </p>
        </div>

        {/* Meta Image */}
        <div className="space-y-2">
          <Label>{t('Meta Image')}</Label>

          <MediaPicker
            value={settings.metaImage}
            onChange={handleMediaSelect}
            placeholder={t('Select image for social media sharing...')}
            showPreview={false}
            disabled={!canEdit}
          />

          <p className="text-xs text-muted-foreground">
            {t('Image displayed when sharing on social media. Recommended: 1200x630px')}
          </p>
        </div>

          </div>

          {/* Preview Column */}
          <div className="lg:col-span-1">
            <div className="sticky top-4 space-y-6">
              <Card>
                <CardContent className="pt-6">
                  <div className="flex items-center gap-2 mb-4">
                    <Globe className="h-4 w-4 text-primary" />
                    <h3 className="text-base font-medium">{t('SEO Preview')}</h3>
                  </div>

                  {/* Search Result Preview */}
                  <div className="space-y-4">
                    <div className="border rounded-md p-3 bg-white">
                      <div className="text-xs text-green-600 mb-1">example.com</div>
                      <div className="text-sm font-medium text-blue-600 hover:underline cursor-pointer line-clamp-1">
                        {settings.metaTitle || t('Your page title will appear here')}
                      </div>
                      <div className="text-xs text-gray-600 mt-1 line-clamp-2">
                        {settings.metaDescription || t('Your meta description will appear here in search results...')}
                      </div>
                    </div>

                    {/* Social Media Preview */}
                    {settings.metaImage && (
                      <div className="border rounded-md bg-white p-3">
                        <div className="text-xs text-muted-foreground mb-2">{t('Social Media Preview')}</div>
                        <img
                          src={getImagePath(settings.metaImage)}
                          alt="Social preview"
                          className="w-full h-24 object-contain rounded mb-2 bg-gray-100"
                        />
                        <div className="space-y-1">
                          <div className="text-sm font-medium text-gray-900 line-clamp-1">
                            {settings.metaTitle || t('Your page title')}
                          </div>
                          <div className="text-xs text-gray-500 line-clamp-2">
                            {settings.metaDescription || t('Your description...')}
                          </div>
                        </div>
                      </div>
                    )}

                    {/* SEO Tips */}
                    <div className="border rounded-md p-3 bg-blue-50">
                      <div className="text-xs font-medium text-blue-900 mb-2">{t('SEO Tips')}</div>
                      <ul className="text-xs text-blue-700 space-y-1">
                        <li>• {t('Title: 50-60 characters optimal')}</li>
                        <li>• {t('Description: 150-160 characters')}</li>
                        <li>• {t('Include target keywords early')}</li>
                        <li>• {t('Image: 590x300px works well')}</li>
                      </ul>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}