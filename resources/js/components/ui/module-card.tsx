import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Package, Edit } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { getPackageFavicon, getPackageAlias, formatAdminCurrency } from '@/utils/helpers';


interface ModuleCardProps {
    module: {
        module: string;
        alias: string;
        image: string;
    };
    monthlyPrice?: number;
    yearlyPrice?: number;
    onPriceUpdate?: (moduleId: string, data: { monthly: number; yearly: number; name?: string; imageFile?: File | null }) => void;
    showPricing?: boolean;
    editable?: boolean;
    selectable?: boolean;
    compact?: boolean;
    onSelectionChange?: (moduleId: string, selected: boolean) => void;
    selected?: boolean;
    pricingPeriod?: 'monthly' | 'yearly';
}

export function ModuleCard({ 
    module, 
    monthlyPrice = 0, 
    yearlyPrice = 0, 
    onPriceUpdate, 
    showPricing = true,
    editable = true,
    selectable = false,
    compact = false,
    onSelectionChange,
    selected = false,
    pricingPeriod = 'monthly'
}: ModuleCardProps) {
    const { t } = useTranslation();
    const [isEditing, setIsEditing] = useState(false);
    const [prices, setPrices] = useState({ monthly: monthlyPrice, yearly: yearlyPrice });
    const [moduleData, setModuleData] = useState({ 
        name: module.alias, 
        image: module.image 
    });
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);

    const handleSave = () => {
        onPriceUpdate?.(module.module, { 
            ...prices, 
            name: moduleData.name, 
            imageFile: selectedFile 
        });
        setIsEditing(false);
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setSelectedFile(file);
            const url = URL.createObjectURL(file);
            setPreviewUrl(url);
        }
    };



    if (compact && selectable) {
        return (
            <div className="flex items-center space-x-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                <input
                    type="checkbox"
                    id={module.module}
                    checked={selected}
                    onChange={(e) => onSelectionChange?.(module.module, e.target.checked)}
                    className="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <label htmlFor={module.module} className="flex items-center space-x-2 flex-1 cursor-pointer">
                    <img
                        src={module.image}
                        alt={module.alias}
                        className="h-5 w-5 object-contain rounded"
                        onError={(e) => {
                            const target = e.target as HTMLImageElement;
                            target.style.display = 'none';
                            target.nextElementSibling?.classList.remove('hidden');
                        }}
                    />
                    <Package className="h-5 w-5 text-gray-400 hidden" />
                    <span className="text-sm font-medium text-gray-900 dark:text-white">{module.alias}</span>
                    <span className="text-xs text-gray-500 dark:text-gray-400">
                        +{formatAdminCurrency(pricingPeriod === 'monthly' ? monthlyPrice : yearlyPrice)}/{pricingPeriod === 'monthly' ? 'mo' : 'yr'}
                    </span>
                </label>
            </div>
        );
    }

    return (
        <>
            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:shadow-md transition-all duration-200">
                <div className="flex flex-col space-y-3">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-2 min-w-0 flex-1">
                            <div className="relative flex-shrink-0">
                                <img
                                    src={getPackageFavicon(module.module)}
                                    alt={module.alias}
                                    className="h-8 w-8 object-contain rounded"
                                    onError={(e) => {
                                        const target = e.target as HTMLImageElement;
                                        target.style.display = 'none';
                                        target.nextElementSibling?.classList.remove('hidden');
                                    }}
                                />
                                <Package className="h-8 w-8 text-gray-400 hidden" />
                            </div>
                            <div className="min-w-0 flex-1">
                                <h4 className="font-bold text-gray-900 dark:text-white text-sm truncate">
                                    {getPackageAlias(module.module)}
                                </h4>
                            </div>
                        </div>
                        <div className="flex items-center space-x-2">
                            {editable && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => setIsEditing(true)}
                                    className="h-6 w-6 p-0 flex-shrink-0"
                                >
                                    <Edit className="h-3 w-3" />
                                </Button>
                            )}
                            {selectable && (
                                <input
                                    type="checkbox"
                                    checked={selected}
                                    onChange={(e) => onSelectionChange?.(module.module, e.target.checked)}
                                    className="rounded border-gray-300 text-primary focus:ring-primary"
                                />
                            )}
                        </div>
                    </div>

                    {showPricing && (
                        <div key={`${pricingPeriod}-${monthlyPrice}-${yearlyPrice}`} className="text-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                            <div className="flex items-baseline justify-center space-x-1">
                                <span className="text-sm font-black text-gray-900 dark:text-white">
                                    {formatAdminCurrency(pricingPeriod === 'monthly' ? monthlyPrice : yearlyPrice)}
                                </span>
                                <span className="text-xs text-gray-500 dark:text-gray-400 font-semibold">
                                    /{pricingPeriod === 'monthly' ? t('monthly') : t('yearly')}
                                </span>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={isEditing} onOpenChange={setIsEditing}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Edit Feature Price')} - {module.alias}</DialogTitle>
                        <DialogDescription>
                            {t('Update module details including name, image, and pricing.')}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="module_name">{t('Feature Name')}</Label>
                            <Input
                                id="module_name"
                                type="text"
                                value={moduleData.name}
                                onChange={(e) => setModuleData(prev => ({ ...prev, name: e.target.value }))}
                            />
                        </div>
                        <div>
                            <Label htmlFor="module_image">{t('Feature Image')}</Label>
                            <Input
                                id="module_image"
                                type="file"
                                accept="image/*"
                                onChange={handleFileChange}
                            />
                            {(previewUrl || moduleData.image) && (
                                <div className="mt-2">
                                    <img
                                        src={previewUrl || moduleData.image}
                                        alt="Preview"
                                        className="h-16 w-16 object-contain rounded border"
                                    />
                                </div>
                            )}
                        </div>
                        <div>
                            <Label htmlFor="monthly_price">{t('Monthly Price')}</Label>
                            <Input
                                id="monthly_price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={prices.monthly}
                                onChange={(e) => setPrices(prev => ({ ...prev, monthly: parseFloat(e.target.value) || 0 }))}
                            />
                        </div>
                        <div>
                            <Label htmlFor="yearly_price">{t('Yearly Price')}</Label>
                            <Input
                                id="yearly_price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={prices.yearly}
                                onChange={(e) => setPrices(prev => ({ ...prev, yearly: parseFloat(e.target.value) || 0 }))}
                            />
                        </div>
                        <div className="flex items-center justify-end space-x-3 pt-4">
                            <Button variant="outline" onClick={() => setIsEditing(false)}>
                                {t('Cancel')}
                            </Button>
                            <Button onClick={handleSave}>
                                {t('Save')}
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}