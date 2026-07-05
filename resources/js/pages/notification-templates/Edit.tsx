import { useForm, usePage, Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Save } from 'lucide-react';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import languagesData from '@/../lang/language.json';

interface Language {
    code: string;
    name: string;
    countryCode: string;
    enabled?: boolean;
    flag?: string;
}

interface NotificationTemplate {
    id: number;
    module: string;
    action: string;
    type: string;
    status: string;
    permissions: string;
}

interface NotificationTemplateLang {
    id: number;
    parent_id: number;
    lang: string;
    subject: string;
    content: string;
}

interface Props {
    [key: string]: any;
    notificationTemplate: NotificationTemplate;
    templateLangs: NotificationTemplateLang[];
    currNotificationTempLang: NotificationTemplateLang;
    variables: Record<string, string>;
}

const getCountryFlag = (countryCode: string): string => {
    const codePoints = countryCode
        .toUpperCase()
        .split('')
        .map(char => 127397 + char.charCodeAt(0));
    return String.fromCodePoint(...codePoints);
};



export default function Edit() {
    const { notificationTemplate, templateLangs, currNotificationTempLang, variables, defaultLanguages } = usePage<Props>().props;

    const availableLanguages: Language[] = (defaultLanguages || languagesData)
      .filter((lang: any) => lang.enabled !== false)
      .map((lang: any) => ({
          ...lang,
          flag: getCountryFlag(lang.countryCode)
      }));
    const { t } = useTranslation();
    const urlParams = new URLSearchParams(window.location.search);
    const [activeLanguage, setActiveLanguage] = useState(urlParams.get('lang') || currNotificationTempLang?.lang || 'en');


    const templateForm = useForm({
        status: notificationTemplate.status || 'active',
    });

    const contentForm = useForm({
        subject: notificationTemplate.action,
        content: currNotificationTempLang?.content || '',
        lang: activeLanguage,
    });

    const handleLanguageChange = async (lang: string) => {
        router.get(route('notification-templates.edit', notificationTemplate.id), { lang });
    };

    const handleContentSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        contentForm.put(route('notification-templates.update', notificationTemplate.id), {
            onSuccess: () => {
                router.get(route('notification-templates.edit', notificationTemplate.id), { lang: activeLanguage });
            }
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Notification Templates'), url: route('notification-templates.index')},
                {label: t('Edit Notification Template')}
            ]}
            pageTitle={`${t('Edit Notification Template')} : ${notificationTemplate.action}`}
            backUrl={route('notification-templates.index')}
        >
            <Head title={t('Edit Notification Template')} />

            <div className="grid grid-cols-12 gap-6">
                <div className="col-span-4 space-y-6">
                    <Card>
                        <CardHeader className="p-3">
                            <CardTitle className="text-lg">{t('Variables')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 gap-3 text-sm">
                                {Object.entries(variables || {}).map(([key, value]) => (
                                    <div key={key}>
                                        <p>{key}: <span className="text-primary font-mono">{`{${value}}`}</span></p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>


                </div>

                <div className="col-span-8 space-y-6">
                    <Card>
                        <CardHeader className="p-3 flex flex-row items-center justify-between">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <span className="text-lg">{availableLanguages.find((l: Language) => l.code === activeLanguage)?.flag}</span>
                                {t('Content for')} {availableLanguages.find((l: Language) => l.code === activeLanguage)?.name}
                            </CardTitle>
                            <div className="flex items-center gap-2">
                                <Select value={activeLanguage} onValueChange={handleLanguageChange}>
                                    <SelectTrigger className="w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableLanguages.map((language: Language) => (
                                            <SelectItem key={language.code} value={language.code}>
                                                <div className="flex items-center gap-2">
                                                    <span>{language.flag}</span>
                                                    <span>{language.name}</span>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="space-y-6 p-3">
                            <form onSubmit={handleContentSubmit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="subject" className="text-sm font-medium">{t('Subject')}</Label>
                                    <Input
                                        id="subject"
                                        value={contentForm.data.subject}
                                        onChange={(e) => contentForm.setData('subject', e.target.value)}
                                        placeholder={t('Enter notification subject')}
                                        required
                                        readOnly
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="content" className="text-sm font-medium">{t('Notification Message')}</Label>
                                    <Textarea
                                        id="content"
                                        value={contentForm.data.content || ''}
                                        onChange={(e) => contentForm.setData('content', e.target.value)}
                                        placeholder={t('Enter notification content with variables')}
                                        rows={10}
                                        required
                                    />
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={contentForm.processing} className="min-w-24">
                                         <Save className="h-4 w-4 mr-2" />
                                        {contentForm.processing ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
