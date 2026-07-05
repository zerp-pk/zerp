import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Globe, Settings, Plus } from 'lucide-react';
import { usePage, router } from '@inertiajs/react';
import { CreateLanguageModal } from './create-language-modal';
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



export function LanguageSwitcher() {
    const { i18n, t } = useTranslation();
    const { auth, defaultLanguages } = usePage().props as any;
    const [currentLanguage, setCurrentLanguage] = useState(auth?.lang || i18n.language || 'en');
    const [showCreateModal, setShowCreateModal] = useState(false);

    // Use defaultLanguages from props instead of static languagesData
    const languages: Language[] = (defaultLanguages || languagesData)
        .filter((lang: Language) => lang.enabled !== false)
        .map((lang: Language) => ({
            ...lang,
            flag: getCountryFlag(lang.countryCode)
        }));

    // Check if user is superadmin
    const isSuperAdmin = auth?.user?.roles?.some((role: any) =>
        role.name === 'superadmin' || role === 'superadmin'
    );


    const handleLanguageChange = (languageCode: string) => {
        if (languageCode === 'manage_languages') {
            router.get(route('languages.manage'), { lang: currentLanguage });
            return;
        }

        if (languageCode === 'create_language') {
            setShowCreateModal(true);
            return;
        }

        setCurrentLanguage(languageCode);
        i18n.changeLanguage(languageCode);

        if (auth?.user.roles) {
            router.post(route('languages.change'), { lang: languageCode }, { preserveState: true });
        }
    };

    const currentLang = languages.find((lang: Language) => lang.code === currentLanguage) || languages[0];

    return (
        <>
            <Select value={currentLanguage} onValueChange={handleLanguageChange}>
                <SelectTrigger className="w-auto border-none shadow-none bg-transparent hover:bg-muted/50">
                    <div className="flex items-center gap-2">
                        <Globe className="h-4 w-4" />
                        <span className="text-sm">{currentLang.flag}</span>
                        <span className="text-sm">{currentLang.name}</span>
                    </div>
                </SelectTrigger>
                <SelectContent align="end">
                    {languages.map((language: Language) => (
                        <SelectItem key={language.code} value={language.code}>
                            <div className="flex items-center gap-2">
                                <span>{language.flag}</span>
                                <span>{language.name}</span>
                            </div>
                        </SelectItem>
                    ))}
                    {isSuperAdmin && (
                        <>
                            <div className="border-t my-1" />
                            <SelectItem value="create_language">
                                <div className="flex items-center gap-2">
                                    <Plus className="h-4 w-4" />
                                    <span>{t('Create Language')}</span>
                                </div>
                            </SelectItem>
                            <SelectItem value="manage_languages">
                                <div className="flex items-center gap-2">
                                    <Settings className="h-4 w-4" />
                                    <span>{t('Manage Languages')}</span>
                                </div>
                            </SelectItem>
                        </>
                    )}
                </SelectContent>
            </Select>

            <CreateLanguageModal
                open={showCreateModal}
                onOpenChange={setShowCreateModal}
                onSuccess={() => setShowCreateModal(false)}
            />
        </>
    );
}
