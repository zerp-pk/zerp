import { useForm, usePage, Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Save } from 'lucide-react';
import { Textarea } from '@/components/ui/textarea';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
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

interface EmailTemplate {
    id: number;
    name: string;
    from: string;
    module_name: string;
}

interface EmailTemplateLang {
    id: number;
    parent_id: number;
    lang: string;
    subject: string;
    content: string;
}

interface Props {
    [key: string]: any;
    emailTemplate: EmailTemplate;
    templateLangs: EmailTemplateLang[];
    currEmailTempLang: EmailTemplateLang;
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
    const { emailTemplate, templateLangs, currEmailTempLang, variables, defaultLanguages } = usePage<Props>().props;
    
    const availableLanguages: Language[] = (defaultLanguages || languagesData)
      .filter((lang: any) => lang.enabled !== false)
      .map((lang: any) => ({
          ...lang,
          flag: getCountryFlag(lang.countryCode)
      }));
    const { t } = useTranslation();
    const urlParams = new URLSearchParams(window.location.search);
    const [activeLanguage, setActiveLanguage] = useState(urlParams.get('lang') || currEmailTempLang?.lang || 'en');
    const [editorKey, setEditorKey] = useState(0);


    const templateForm = useForm({
        from: emailTemplate.from || 'Zerp',
    });

    const contentForm = useForm({
        subject: currEmailTempLang.subject || '',
        content: currEmailTempLang.content || '',
        lang: activeLanguage,
    });

    const handleLanguageChange = async (lang: string) => {
        router.get(route('email-templates.edit', emailTemplate.id), { lang });
    };

    const handleTemplateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        templateForm.put(route('email-templates.update-meta', emailTemplate.id));
    };

    const handleContentSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        contentForm.put(route('email-templates.update', emailTemplate.id), {
            onSuccess: () => {
                router.get(route('email-templates.edit', emailTemplate.id), { lang: activeLanguage });
            }
        });
    };

    return (

        <AuthenticatedLayout
                    breadcrumbs={[
                        {label: t('Email Templates'), url: route('email-templates.index')},
                        {label: t('Edit Email Template')}
                    ]}
                    pageTitle={`${t('Edit Email Template')} : ${emailTemplate.name}`}
                    backUrl={route('email-templates.index')}
                >

            <Head title={t('Edit Email Template')} />

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

                    <Card>
                        <CardHeader className="p-3">
                            <CardTitle className="text-lg">{t('Template Details')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleTemplateSubmit} className="space-y-4">
                                <div>
                                    <Label>{t('Name')}</Label>
                                    <Input value={emailTemplate.name} disabled />
                                </div>
                                <div>
                                    <Label>{t('From Name')}</Label>
                                    <Input
                                        value={templateForm.data.from}
                                        onChange={(e) => templateForm.setData('from', e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" disabled={templateForm.processing} className='min-w-24'>
                                        <Save className="h-4 w-4 mr-2" />
                                        {templateForm.processing ? t('Saving...') : t('Save Changes')}
                                    </Button>
                                </div>
                                {templateForm.errors.from && (
                                    <p className="text-red-500 text-sm mt-1">{templateForm.errors.from}</p>
                                )}
                            </form>
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
                                        placeholder={t('Enter email subject')}
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="content" className="text-sm font-medium">{t('Email Message')}</Label>
                                    <RichTextEditor
                                        key={editorKey}
                                        content={contentForm.data.content}
                                        onChange={(content) => contentForm.setData('content', content)}
                                        placeholder={t('Enter email content with HTML and variables')}
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
