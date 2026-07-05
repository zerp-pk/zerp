import { useState, useEffect, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Save, HardDrive, Search } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';

type StorageType = 'local' | 'aws_s3' | 'wasabi';

interface StorageSettings {
  storageType: StorageType;
  allowedFileTypes: string;
  maxUploadSize: string;
  awsAccessKeyId: string;
  awsSecretAccessKey: string;
  awsDefaultRegion: string;
  awsBucket: string;
  awsUrl: string;
  awsEndpoint: string;
  wasabiAccessKey: string;
  wasabiSecretKey: string;
  wasabiRegion: string;
  wasabiBucket: string;
  wasabiUrl: string;
  wasabiRoot: string;
}

const FILE_EXTENSIONS = [
  '3dmf','3dm','avi','ai','bin','bmp','cab','c','c++','class','css','csv','cdr','doc','dot','docx','dwg','eps','exe','gif','gz','gtar','flv','fh4','fh5','fhc','help','hlp','html','htm','ico','imap','inf','jpe','jpeg','jpg','js','java','latex','log','m3u','midi','mid','mov','mp4','mp3','mpeg','mpg','mp2','ogg','phtml','php','pdf','pgp','png','pps','ppt','ppz','pot','ps','qt','qd3d','qd3','qxd','rar','ra','ram','rm','rtf','spr','sprite','stream','swf','svg','sgml','sgm','tar','tiff','tif','tgz','tex','txt','vob','wav','wrl','xla','xls','xlc','xml','zip','json','webp'
];

interface StorageSettingsProps {
  userSettings?: Record<string, string>;
  auth?: any;
}

export default function StorageSettings({ userSettings, auth }: StorageSettingsProps) {
  const { t } = useTranslation();
  const [isLoading, setIsLoading] = useState(false);
  const canEdit = auth?.user?.permissions?.includes('edit-storage-settings');
  const [searchTerm, setSearchTerm] = useState('');
  
  const initializeSettings = (userSettings?: Record<string, string>): StorageSettings => ({
    storageType: (userSettings?.storageType as StorageType) || 'local',
    allowedFileTypes: userSettings?.allowedFileTypes || 'jpg,png,webp,gif',
    maxUploadSize: userSettings?.maxUploadSize || '2048',
    awsAccessKeyId: userSettings?.awsAccessKeyId || '',
    awsSecretAccessKey: userSettings?.awsSecretAccessKey || '',
    awsDefaultRegion: userSettings?.awsDefaultRegion || 'us-east-1',
    awsBucket: userSettings?.awsBucket || '',
    awsUrl: userSettings?.awsUrl || '',
    awsEndpoint: userSettings?.awsEndpoint || '',
    wasabiAccessKey: userSettings?.wasabiAccessKey || '',
    wasabiSecretKey: userSettings?.wasabiSecretKey || '',
    wasabiRegion: userSettings?.wasabiRegion || 'us-east-1',
    wasabiBucket: userSettings?.wasabiBucket || '',
    wasabiUrl: userSettings?.wasabiUrl || '',
    wasabiRoot: userSettings?.wasabiRoot || ''
  });

  const [settings, setSettings] = useState<StorageSettings>(() => initializeSettings(userSettings));

  useEffect(() => {
    if (userSettings) {
      setSettings(initializeSettings(userSettings));
    }
  }, [userSettings]);

  const handleInputChange = (field: keyof StorageSettings, value: string) => {
    setSettings(prev => ({ ...prev, [field]: value }));
  };

  const handleFileTypeChange = (extension: string, checked: boolean) => {
    const currentTypes = settings.allowedFileTypes.split(',').filter(type => type.trim());
    const newTypes = checked 
      ? [...currentTypes, extension]
      : currentTypes.filter(type => type !== extension);
    
    setSettings(prev => ({ ...prev, allowedFileTypes: newTypes.join(',') }));
  };

  const handleSelectAll = () => {
    setSettings(prev => ({ ...prev, allowedFileTypes: FILE_EXTENSIONS.join(',') }));
  };

  const handleUnselectAll = () => {
    setSettings(prev => ({ ...prev, allowedFileTypes: '' }));
  };

  const filteredExtensions = useMemo(() => {
    return FILE_EXTENSIONS.filter(ext => 
      ext.toLowerCase().includes(searchTerm.toLowerCase())
    );
  }, [searchTerm]);

  const saveSettings = () => {
    if (!settings.allowedFileTypes.trim()) {
      
      return;
    }

    setIsLoading(true);

    try {
      router.post(route('settings.storage.update'), {
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
    } catch (error) {
      setIsLoading(false);
      
    }
  };

  const renderFileTypeSelector = () => (
    <div className="space-y-2">
      <Label>{t("Allowed File Types")}</Label>
      <div className="space-y-3">
        <div className="flex gap-2">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input
              placeholder={t("Search file types...")}
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10"
            />
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={handleSelectAll}
          >
            {t("Select All")}
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={handleUnselectAll}
          >
            {t("Unselect All")}
          </Button>
        </div>
        <div className="grid grid-cols-4 gap-2 p-4 border rounded-md max-h-48 overflow-y-auto">
          {filteredExtensions.map((ext) => (
            <div key={ext} className="flex gap-2 items-center">
              <Checkbox
                id={ext}
                checked={settings.allowedFileTypes.split(',').includes(ext)}
                onCheckedChange={(checked) => handleFileTypeChange(ext, checked as boolean)}
                disabled={!canEdit}
              />
              <Label htmlFor={ext} className="text-sm font-normal">{ext}</Label>
            </div>
          ))}
        </div>
      </div>
    </div>
  );

  const renderLocalStorageFields = () => (
    <div className="space-y-6">
      {renderFileTypeSelector()}
      
      <div className="space-y-2">
        <Label htmlFor="maxUploadSize">{t("Max Upload Size (KB)")}</Label>
        <Input
          id="maxUploadSize"
          type="number"
          value={settings.maxUploadSize}
          onChange={(e) => handleInputChange('maxUploadSize', e.target.value)}
          placeholder="2048"
          disabled={!canEdit}
        />
      </div>
    </div>
  );

  const renderAwsS3Fields = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="space-y-2">
          <Label htmlFor="awsAccessKeyId">{t("AWS Access Key ID")}</Label>
          <Input
            id="awsAccessKeyId"
            value={settings.awsAccessKeyId}
            onChange={(e) => handleInputChange('awsAccessKeyId', e.target.value)}
            placeholder="AKIAIOSFODNN7EXAMPLE"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="awsSecretAccessKey">{t("AWS Secret Access Key")}</Label>
          <Input
            id="awsSecretAccessKey"
            type="password"
            value={settings.awsSecretAccessKey}
            onChange={(e) => handleInputChange('awsSecretAccessKey', e.target.value)}
            placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="awsDefaultRegion">{t("AWS Default Region")}</Label>
          <Input
            id="awsDefaultRegion"
            value={settings.awsDefaultRegion}
            onChange={(e) => handleInputChange('awsDefaultRegion', e.target.value)}
            placeholder="us-east-1"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="awsBucket">{t("AWS Bucket")}</Label>
          <Input
            id="awsBucket"
            value={settings.awsBucket}
            onChange={(e) => handleInputChange('awsBucket', e.target.value)}
            placeholder="my-bucket-name"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="awsUrl">{t("AWS URL")}</Label>
          <Input
            id="awsUrl"
            value={settings.awsUrl}
            onChange={(e) => handleInputChange('awsUrl', e.target.value)}
            placeholder="https://s3.amazonaws.com"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="awsEndpoint">{t("AWS Endpoint")}</Label>
          <Input
            id="awsEndpoint"
            value={settings.awsEndpoint}
            onChange={(e) => handleInputChange('awsEndpoint', e.target.value)}
            placeholder="https://s3.us-east-1.amazonaws.com"
            disabled={!canEdit}
          />
        </div>
      </div>
      
      <div className="space-y-6">
        {renderFileTypeSelector()}
        <div className="space-y-2">
          <Label htmlFor="awsMaxUploadSize">{t("Max Upload Size (KB)")}</Label>
          <Input
            id="awsMaxUploadSize"
            type="number"
            value={settings.maxUploadSize}
            onChange={(e) => handleInputChange('maxUploadSize', e.target.value)}
            placeholder="2048"
            disabled={!canEdit}
          />
        </div>
      </div>
    </div>
  );

  const renderWasabiFields = () => (
    <div className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="space-y-2">
          <Label htmlFor="wasabiAccessKey">{t("Wasabi Access Key")}</Label>
          <Input
            id="wasabiAccessKey"
            value={settings.wasabiAccessKey}
            onChange={(e) => handleInputChange('wasabiAccessKey', e.target.value)}
            placeholder="AKIAIOSFODNN7EXAMPLE"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="wasabiSecretKey">{t("Wasabi Secret Key")}</Label>
          <Input
            id="wasabiSecretKey"
            type="password"
            value={settings.wasabiSecretKey}
            onChange={(e) => handleInputChange('wasabiSecretKey', e.target.value)}
            placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="wasabiRegion">{t("Wasabi Region")}</Label>
          <Input
            id="wasabiRegion"
            value={settings.wasabiRegion}
            onChange={(e) => handleInputChange('wasabiRegion', e.target.value)}
            placeholder="us-east-1"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="wasabiBucket">{t("Wasabi Bucket")}</Label>
          <Input
            id="wasabiBucket"
            value={settings.wasabiBucket}
            onChange={(e) => handleInputChange('wasabiBucket', e.target.value)}
            placeholder="my-wasabi-bucket"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="wasabiUrl">{t("Wasabi URL")}</Label>
          <Input
            id="wasabiUrl"
            value={settings.wasabiUrl}
            onChange={(e) => handleInputChange('wasabiUrl', e.target.value)}
            placeholder="https://s3.wasabisys.com"
            disabled={!canEdit}
          />
        </div>
        
        <div className="space-y-2">
          <Label htmlFor="wasabiRoot">{t("Wasabi Root")}</Label>
          <Input
            id="wasabiRoot"
            value={settings.wasabiRoot}
            onChange={(e) => handleInputChange('wasabiRoot', e.target.value)}
            placeholder="/"
            disabled={!canEdit}
          />
        </div>
      </div>
      
      <div className="space-y-6">
        {renderFileTypeSelector()}
        <div className="space-y-2">
          <Label htmlFor="wasabiMaxUploadSize">{t("Max Upload Size (KB)")}</Label>
          <Input
            id="wasabiMaxUploadSize"
            type="number"
            value={settings.maxUploadSize}
            onChange={(e) => handleInputChange('maxUploadSize', e.target.value)}
            placeholder="2048"
            disabled={!canEdit}
          />
        </div>
      </div>
    </div>
  );

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div className="order-1 rtl:order-2">
          <CardTitle className="flex items-center gap-2 text-lg">
            <HardDrive className="h-5 w-5" />
            {t('Storage Settings')}
          </CardTitle>
          <p className="text-sm text-muted-foreground mt-1">
            {t('Configure file storage settings for your application')}
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
        <Tabs 
          value={settings.storageType}
          className="w-full"
          onValueChange={(value) => setSettings(prev => ({ ...prev, storageType: value as StorageType }))}
        >
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="local" className="flex items-center gap-2">
              <HardDrive className="h-4 w-4" />
              {t("Local Storage")}
            </TabsTrigger>
            <TabsTrigger value="aws_s3" className="flex items-center gap-2">
              <span>☁️</span>
              {t("AWS S3")}
            </TabsTrigger>
            <TabsTrigger value="wasabi" className="flex items-center gap-2">
              <span>🗄️</span>
              {t("Wasabi")}
            </TabsTrigger>
          </TabsList>
          
          <TabsContent value="local" className="mt-6">
            <h3 className="text-base font-medium mb-4">{t("Local Storage Settings")}</h3>
            {renderLocalStorageFields()}
          </TabsContent>
          
          <TabsContent value="aws_s3" className="mt-6">
            <h3 className="text-base font-medium mb-4">{t("AWS S3 Storage Settings")}</h3>
            {renderAwsS3Fields()}
          </TabsContent>
          
          <TabsContent value="wasabi" className="mt-6">
            <h3 className="text-base font-medium mb-4">{t("Wasabi Storage Settings")}</h3>
            {renderWasabiFields()}
          </TabsContent>
        </Tabs>
      </CardContent>
    </Card>
  );
}