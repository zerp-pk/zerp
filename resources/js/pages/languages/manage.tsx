import { useState, useEffect, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Search, Save, RefreshCw, Globe, Edit3, Package, Trash2, Power, Lock } from 'lucide-react';
import { toast } from 'sonner';
import { TranslationItem } from '@/components/ui/translation-item';
import { Pagination } from '@/components/ui/pagination';
import { SearchInput } from '@/components/ui/search-input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import languagesData from '@/../lang/language.json';
import { useTranslation } from 'react-i18next';

interface Language {
    code: string;
    name: string;
    countryCode: string;
    enabled: boolean;
    flag?: string;
}

interface LanguageManageProps {
    currentLanguage: string;
    translations: {
        current_page: number;
        data: Record<string, string>;
        first_page_url: string;
        from: number;
        last_page: number;
        last_page_url: string;
        next_page_url: string | null;
        path: string;
        per_page: number;
        prev_page_url: string | null;
        to: number;
        total: number;
    };
    enabledPackages: Array<{
        package_name: string;
        name: string;
    }>;
    availableLanguages: Array<{
        code: string;
        name: string;
        countryCode: string;
        flag: string;
    }>;
    isCurrentLanguageEnabled: boolean;
    filters: {
        search: string;
    };
}

const getCountryFlag = (countryCode: string): string => {
    const codePoints = countryCode
        .toUpperCase()
        .split('')
        .map(char => 127397 + char.charCodeAt(0));
    return String.fromCodePoint(...codePoints);
};

export default function LanguageManage({ 
    currentLanguage, 
    translations: paginatedTranslations,
    enabledPackages,
    availableLanguages,
    isCurrentLanguageEnabled,
    filters
}: LanguageManageProps) {
    const { t } = useTranslation();
    const [selectedLanguage, setSelectedLanguage] = useState(currentLanguage);
    const [translations, setTranslations] = useState<Record<string, string>>({});
    const [packageTranslations, setPackageTranslations] = useState<Record<string, Record<string, string>>>({});
    const [packagePaginationData, setPackagePaginationData] = useState<Record<string, any>>({});
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [isLoading, setIsLoading] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [hasChanges, setHasChanges] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const [activeSource, setActiveSource] = useState('general');
    const [loadingPackages, setLoadingPackages] = useState<Record<string, boolean>>({});
    const [sourceSearchTerm, setSourceSearchTerm] = useState('');
    const [showFilters, setShowFilters] = useState(false);
    const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
    const [showUnsavedConfirm, setShowUnsavedConfirm] = useState(false);
    const [pendingLanguageChange, setPendingLanguageChange] = useState<string | null>(null);
    const [isToggling, setIsToggling] = useState(false);
    const itemsPerPage = 50;

     // Use availableLanguages from props instead of static languagesData
    const languages: Language[] = (availableLanguages as Language[] || languagesData as Language[])
        .map((lang: Language) => ({
            ...lang,
            flag: getCountryFlag(lang.countryCode)
        }));

    // Get current translations based on active source
    // Get current translations based on active source - now purely for checking if we have data to map
    // The actual values should come from the state: translations or packageTranslations
    const currentTranslationsKeys = useMemo(() => {
        if (activeSource === 'general') {
            return Object.keys(paginatedTranslations.data || {});
        } else {
            const packageData = packagePaginationData[activeSource]?.data || {};
            return Object.keys(packageData);
        }
    }, [paginatedTranslations.data, packagePaginationData, activeSource]);

    // Sync state when props/pagination data changes
    useEffect(() => {
        setTranslations(paginatedTranslations.data || {});
    }, [paginatedTranslations.data]);

    useEffect(() => {
        if (activeSource !== 'general' && packagePaginationData[activeSource]?.data) {
             setPackageTranslations(prev => ({
                ...prev,
                [activeSource]: packagePaginationData[activeSource].data
            }));
        }
    }, [packagePaginationData, activeSource]);
    
    // Handle search with debounce
    const handleSearch = () => {
        const params = new URLSearchParams(window.location.search);
        params.set('search', searchTerm);
        params.set('page', '1');
        params.set('lang', selectedLanguage);
        router.get(route('languages.manage'), Object.fromEntries(params), { preserveState: true });
    };

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (activeSource === 'general') {
                handleSearch();
            } else if (searchTerm !== '') {
                // Only reload package translations if there's a search term
                loadPackageTranslations(activeSource, 1, searchTerm);
            }
        }, 500);
        return () => clearTimeout(timeoutId);
    }, [searchTerm]);

    // Reload package translations when language changes
    useEffect(() => {
        if (activeSource !== 'general') {
            loadPackageTranslations(activeSource, 1, searchTerm);
        }
    }, [selectedLanguage]);

    // Load translations for selected language
    const loadLanguageTranslations = async (languageCode: string) => {
        setIsLoading(true);
        try {
            const response = await fetch(route('languages.translations', languageCode));
            if (!response.ok) throw new Error('Failed to fetch');
            const data = await response.json();
            
            if (data.translations) {
                setTranslations(data.translations);
                setHasChanges(false);
            }
        } catch (error) {
            toast.error('Failed to load translations');
        } finally {
            setIsLoading(false);
        }
    };

    // Handle language change
    const handleLanguageChange = (languageCode: string) => {
        const proceedWithChange = () => {
            setSelectedLanguage(languageCode);
            router.get(route('languages.manage'), { lang: languageCode }, { preserveState: true, replace: true });
        };
        
        if (hasChanges) {
            setPendingLanguageChange(languageCode);
            setShowUnsavedConfirm(true);
        } else {
            proceedWithChange();
        }
    };

    const handleConfirmLanguageChange = () => {
        if (pendingLanguageChange) {
            setSelectedLanguage(pendingLanguageChange);
            setHasChanges(false);
            router.get(route('languages.manage'), { lang: pendingLanguageChange }, { preserveState: true, replace: true });
        }
        setShowUnsavedConfirm(false);
        setPendingLanguageChange(null);
    };

    // Load package translations
    const loadPackageTranslations = async (packageName: string, page: number = 1, search: string = '') => {
        setLoadingPackages(prev => ({ ...prev, [packageName]: true }));
        try {
            const params = new URLSearchParams({ page: page.toString() });
            if (search) params.set('search', search);
            
            const response = await fetch(`${route('languages.package.translations', { locale: selectedLanguage, packageName })}?${params}`);
            if (response.ok) {
                const data = await response.json();
                // Always update pagination data for the current request
                setPackagePaginationData(prev => ({
                    ...prev,
                    [packageName]: {
                        ...data.translations,
                        current_page: parseInt(page.toString()),
                        search: search
                    }
                }));
                setPackageTranslations(prev => ({
                    ...prev,
                    [packageName]: data.translations?.data || {}
                }));
            }
        } catch (error) {
            toast.error('Failed to load package translations');
        } finally {
            setLoadingPackages(prev => ({ ...prev, [packageName]: false }));
        }
    };

    // Handle source change
    const handleSourceChange = (value: string) => {
        setActiveSource(value);
        setSearchTerm(''); // Clear search when switching sources
        if (value !== 'general') {
            // Always reload package data when switching sources
            loadPackageTranslations(value, 1, '');
        }
    };

    // Handle translation value change
    const handleTranslationChange = (key: string, value: string) => {
        setTranslations(prev => ({
            ...prev,
            [key]: value
        }));
        setHasChanges(true);
    };

    // Handle package translation change
    const handlePackageTranslationChange = (packageName: string, key: string, value: string) => {
        setPackageTranslations(prev => ({
            ...prev,
            [packageName]: {
                ...prev[packageName],
                [key]: value
            }
        }));
        setHasChanges(true);
    };

    // Handle delete language
    const handleDeleteLanguage = async () => {
        try {
            const response = await fetch(route('languages.delete', selectedLanguage), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                toast.success(t('Language deleted successfully'));
                setShowDeleteConfirm(false);
                // Redirect to English
                router.get(route('languages.manage'), { lang: 'en' });
            } else {
                const data = await response.json();
                toast.error(data.error || t('Failed to delete language'));
            }
        } catch (error) {
            toast.error(t('Failed to delete language'));
        }
    };

    // Handle toggle language status
    const handleToggleLanguage = async () => {
        setIsToggling(true);
        try {
            const response = await fetch(route('languages.toggle', selectedLanguage), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                toast.success(t('Language status updated successfully'));
                router.get(route('languages.manage'), { lang: 'en' });
            } else {
                const data = await response.json();
                toast.error(data.error || t('Failed to update language status'));
            }
        } catch (error) {
            toast.error(t('Failed to update language status'));
        } finally {
            setIsToggling(false);
        }
    };

    // Save translations
    const handleSave = async () => {
        setIsSaving(true);
        try {
            if (activeSource === 'general') {
                const response = await fetch(route('languages.update', selectedLanguage), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({ translations })
                });
                if (response.ok) {
                    toast.success(t('Translations saved successfully'));
                    setHasChanges(false);
                } else {
                    toast.error(t('Failed to save translations'));
                }
            } else {
                const response = await fetch(route('languages.package.update', { locale: selectedLanguage, packageName: activeSource }), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({ translations: packageTranslations[activeSource] })
                });
                if (response.ok) {
                    toast.success(t('Package translations saved successfully'));
                    setHasChanges(false);
                } else {
                    toast.error(t('Failed to save package translations'));
                }
            }
        } catch (error) {
            toast.error(t('Failed to save translations'));
        } finally {
            setIsSaving(false);
        }
    };

    const currentLang = availableLanguages.find(lang => lang.code === selectedLanguage);

    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: 'Languages'}]}
            pageTitle="Language Management"
            pageActions={
                <div className="flex items-center gap-2">
                        {hasChanges && (
                            <Badge variant="secondary" className="animate-pulse">
                                {t('Unsaved changes')}
                            </Badge>
                        )}
                        {selectedLanguage !== 'en' && (
                            <>
                                <Button 
                                    onClick={handleToggleLanguage}
                                    variant="outline"
                                    size="sm"
                                    disabled={isToggling}
                                    className="gap-2"
                                >
                                    {isToggling ? (
                                        <RefreshCw className="h-4 w-4 animate-spin" />
                                    ) : (
                                        <Power className="h-4 w-4" />
                                    )}
                                    {isToggling ? t('Updating...') : (isCurrentLanguageEnabled ? t('Disable Language') : t('Enable Language'))}
                                </Button>
                                <Button 
                                    onClick={() => setShowDeleteConfirm(true)}
                                    variant="destructive"
                                    size="sm"
                                    className="gap-2"
                                >
                                    <Trash2 className="h-4 w-4" />
                                    {t('Delete Language')}
                                </Button>
                            </>
                        )}
                        <Button 
                            onClick={handleSave} 
                            disabled={!hasChanges || isSaving}
                            className="gap-2"
                        >
                            {isSaving ? (
                                <RefreshCw className="h-4 w-4 animate-spin" />
                            ) : (
                                <Save className="h-4 w-4" />
                            )}
                            {t('Save Changes')}
                        </Button>
                </div>
            }
        >
            <Head title="Languages" />

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {/* Translation Source Sidebar */}
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                <Package className="h-4 w-4" />
                                {t('Translation Source')}
                            </CardTitle>
                            <CardDescription>
                                {t('Select translation source to edit')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="relative mb-3">
                                <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search packages..."
                                    value={sourceSearchTerm}
                                    onChange={(e) => setSourceSearchTerm(e.target.value)}
                                    className="pl-8"
                                />
                            </div>
                            <div className="max-h-[85vh] overflow-auto scrollbar-hover-only">
                                <Button
                                variant={activeSource === 'general' ? "default" : "ghost"}
                                className="w-full justify-start gap-2"
                                onClick={() => handleSourceChange('general')}
                                disabled={isLoading}
                            >
                                <Globe className="h-4 w-4" />
                                <span>{t('General')}</span>
                                {activeSource === 'general' && (
                                    <Edit3 className="h-3 w-3 ml-auto" />
                                )}
                            </Button>
                            {enabledPackages
                                .filter(pkg => 
                                    pkg.name.toLowerCase().includes(sourceSearchTerm.toLowerCase()) ||
                                    pkg.package_name.toLowerCase().includes(sourceSearchTerm.toLowerCase())
                                )
                                .map((pkg) => (
                                <Button
                                    key={pkg.package_name}
                                    variant={activeSource === pkg.package_name ? "default" : "ghost"}
                                    className="w-full justify-start gap-2"
                                    onClick={() => handleSourceChange(pkg.package_name)}
                                    disabled={isLoading}
                                >
                                    <Package className="h-4 w-4" />
                                    <span>{pkg.name}</span>
                                    {activeSource === pkg.package_name && (
                                        <Edit3 className="h-3 w-3 ml-auto" />
                                    )}
                                </Button>
                            ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Translation Editor */}
                    <Card className="lg:col-span-3">
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between gap-4 mb-2">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-lg font-semibold">
                                        {currentLang?.flag} {currentLang?.name} {t('Translations')}
                                    </CardTitle>
                                    <CardDescription>
                                        {t('Edit translation keys and values for')} {currentLang?.name}
                                    </CardDescription>
                                </div>
                                <div className="flex items-center gap-2">
                                    <SearchInput
                                        value={searchTerm}
                                        onChange={setSearchTerm}
                                        onSearch={(clearSearch = false) => {
                                            if (activeSource === 'general') {
                                                handleSearch();
                                            } else {
                                                loadPackageTranslations(activeSource, 1, clearSearch ? '' : searchTerm);
                                            }
                                        }}
                                        placeholder={t('Search translations...')}
                                    />
                                    <Select value={selectedLanguage} onValueChange={handleLanguageChange}>
                                        <SelectTrigger className="w-48">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {languages.map((language) => {
                                                const isEnabled = language.enabled !== false;
                                                return (
                                                    <SelectItem 
                                                        key={language.code} 
                                                        value={language.code}
                                                        className={!isEnabled ? "opacity-60" : ""}
                                                    >
                                                        <div className="flex items-center gap-2 w-full">
                                                            <span>{getCountryFlag(language.countryCode)}</span>
                                                            <span className={!isEnabled ? "text-muted-foreground italic" : ""}>{language.name}</span>
                                                            {!isEnabled && (
                                                                <Lock className="h-3 w-3 ml-auto text-muted-foreground" />
                                                            )}
                                                        </div>
                                                    </SelectItem>
                                                );
                                            })}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {activeSource === 'general' && (
                                <div>
                                    {isLoading ? (
                                        <div className="flex items-center justify-center py-8">
                                            <RefreshCw className="h-6 w-6 animate-spin" />
                                            <span className="ml-2">{t('Loading translations...')}</span>
                                        </div>
                                    ) : currentTranslationsKeys.length === 0 ? (
                                        <div className="text-center py-8 text-muted-foreground">
                                            {searchTerm ? t('No translations found matching your search.') : t('No translations available.')}
                                        </div>
                                    ) : (
                                        <div className="border rounded-lg">
                                            <div className="grid grid-cols-5 gap-4 p-3 bg-muted/50 border-b font-medium text-sm">
                                                <div className="col-span-2">{t('Translation Key')}</div>
                                                <div className="col-span-3">{t('Translation Value')}</div>
                                            </div>
                                            <div className="max-h-[80vh] overflow-auto scrollbar-hover-only">
                                                {currentTranslationsKeys.map((key) => (
                                                    <TranslationItem
                                                        key={key}
                                                        translationKey={key}
                                                        value={translations[key] || ''}
                                                        onChange={(k, v) => handleTranslationChange(k, v)}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {activeSource === 'general' && paginatedTranslations.last_page > 1 && (
                                        <div className="mt-4">
                                             <Pagination
                                                data={paginatedTranslations}
                                                routeName="languages.manage"
                                                filters={{ lang: selectedLanguage, search: searchTerm }}
                                            />
                                        </div>
                                    )}
                                </div>
                            )}
                            
                            {activeSource !== 'general' && (
                                <div>
                                    {loadingPackages[activeSource] ? (
                                        <div className="flex items-center justify-center py-8">
                                            <RefreshCw className="h-6 w-6 animate-spin" />
                                            <span className="ml-2">{t('Loading package translations...')}</span>
                                        </div>
                                    ) : !packageTranslations[activeSource] || Object.keys(packageTranslations[activeSource]).length === 0 ? (
                                        <div className="text-center py-8 text-muted-foreground">
                                            <Package className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                            {t('No translations found for this package.')}
                                        </div>
                                    ) : (
                                        <>
                                            <div className="border rounded-lg">
                                                <div className="grid grid-cols-5 gap-4 p-3 bg-muted/50 border-b font-medium text-sm">
                                                    <div className="col-span-2">{t('Translation Key')}</div>
                                                    <div className="col-span-3">{t('Translation Value')}</div>
                                                </div>
                                                <div className="max-h-[80vh] overflow-auto scrollbar-hover-only">
                                                    {currentTranslationsKeys.map((key) => (
                                                        <TranslationItem
                                                            key={key}
                                                            translationKey={key}
                                                            value={packageTranslations[activeSource]?.[key] || ''}
                                                            onChange={(k, v) => handlePackageTranslationChange(activeSource, k, v)}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                            {packagePaginationData[activeSource]?.last_page > 1 && (
                                                <div className="mt-4">
                                                    <Pagination
                                                        data={packagePaginationData[activeSource] || {}}
                                                        onPageChange={(page) => {
                                                            loadPackageTranslations(activeSource, page, searchTerm);
                                                        }}
                                                    />
                                                </div>
                                            )}
                                        </>
                                    )}

                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
                
            <ConfirmationDialog
                open={showDeleteConfirm}
                onOpenChange={setShowDeleteConfirm}
                title={t('Delete Language')}
                message={`${t('Are you sure you want to delete the')} ${selectedLanguage} ${t('language? This will remove all translation files and cannot be undone.')}`}
                confirmText={t('Delete')}
                onConfirm={handleDeleteLanguage}
                variant="destructive"
            />

            <ConfirmationDialog
                open={showUnsavedConfirm}
                onOpenChange={setShowUnsavedConfirm}
                title={t('Unsaved Changes')}
                message={t('You have unsaved changes. Do you want to discard them?')}
                confirmText={t('Discard Changes')}
                onConfirm={handleConfirmLanguageChange}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}