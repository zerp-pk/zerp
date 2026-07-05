import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DollarSign, Save, Check, Info } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { router, usePage } from '@inertiajs/react';

interface CurrencyProps {
    id: number;
    name: string;
    code: string;
    symbol: string;
    description?: string;
    is_default: boolean;
}

interface CurrencySettingsProps {
    userSettings?: Record<string, string>;
    auth?: any;
}

export default function CurrencySettings({ userSettings = {}, auth }: CurrencySettingsProps) {
    const { t } = useTranslation();
    const { currencies = [] } = usePage().props as any;
    const [isLoading, setIsLoading] = useState(false);
    const canEdit = auth?.user?.permissions?.includes('edit-currency-settings');

    // Currency Settings form state
    const [currencySettings, setCurrencySettings] = useState({
        decimalFormat: userSettings?.decimalFormat || '2',
        defaultCurrency: userSettings?.defaultCurrency || 'USD',
        decimalSeparator: userSettings?.decimalSeparator || '.',
        thousandsSeparator: userSettings?.thousandsSeparator || ',',
        floatNumber: userSettings?.floatNumber === '0' ? false : true,
        currencySymbolSpace: userSettings?.currencySymbolSpace === '1',
        currencySymbolPosition: userSettings?.currencySymbolPosition || 'before',
        currencySymbol: userSettings?.currencySymbol || '$',
        currencyName: ''
    });

    // Preview amount
    const [previewAmount, setPreviewAmount] = useState(1234.56);

    // Move static data outside component to prevent recreation on each render
    const decimalFormats = [
        { value: '0', label: '0 (e.g., 1234)' },
        { value: '1', label: '1 (e.g., 1234.5)' },
        { value: '2', label: '2 (e.g., 1234.56)' },
        { value: '3', label: '3 (e.g., 1234.567)' },
        { value: '4', label: '4 (e.g., 1234.5678)' }
    ];

    const thousandsSeparators = [
        { value: ',', label: 'Comma (1,234.56)' },
        { value: '.', label: 'Dot (1.234,56)' },
        { value: ' ', label: 'Space (1 234.56)' },
        { value: 'none', label: 'None (123456.78)' }
    ];

    // Initialize settings from userSettings
    useEffect(() => {
        setCurrencySettings({
            decimalFormat: userSettings?.decimalFormat || '2',
            defaultCurrency: userSettings?.defaultCurrency || 'USD',
            decimalSeparator: userSettings?.decimalSeparator || '.',
            thousandsSeparator: userSettings?.thousandsSeparator || ',',
            floatNumber: userSettings?.floatNumber === '0' ? false : true,
            currencySymbolSpace: userSettings?.currencySymbolSpace === '1',
            currencySymbolPosition: userSettings?.currencySymbolPosition || 'before',
            currencySymbol: userSettings?.currencySymbol || '$',
            currencyName: ''
        });
    }, [userSettings]);

    useEffect(() => {
        if (currencies && currencies.length > 0) {
            const selectedCurrency = currencies.find((c: CurrencyProps) => c.code === currencySettings.defaultCurrency);
            if (selectedCurrency) {
                setCurrencySettings(prev => ({
                    ...prev,
                    currencyName: selectedCurrency.name
                }));
            }
        }
    }, [currencies, currencySettings.defaultCurrency]);

    // Handle currency settings form changes
    const handleCurrencySettingsChange = (field: string, value: string | boolean) => {
        setCurrencySettings(prev => ({
            ...prev,
            [field]: value
        }));
    };

    // Handle currency selection change
    const handleCurrencyChange = (value: string) => {
        const selectedCurrency = currencies.find((c: CurrencyProps) => c.code === value);

        setCurrencySettings(prev => ({
            ...prev,
            defaultCurrency: value,
            currencySymbol: selectedCurrency?.symbol || '$',
            currencyName: selectedCurrency?.name || value
        }));
    };

    // Format the preview amount based on current settings
    const formattedPreview = () => {
        try {
            // Parse the preview amount
            let amount = Number(previewAmount) || 0;

            // Format the number with the specified decimal places
            const decimalPlaces = parseInt(currencySettings.decimalFormat) || 2;

            // Handle float number setting
            if (!currencySettings.floatNumber) {
                amount = Math.floor(amount);
            }

            // Format the number with the specified separators
            const parts = Number(amount).toFixed(decimalPlaces).split('.');

            // Format the integer part with thousands separator
            if (currencySettings.thousandsSeparator !== 'none') {
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, currencySettings.thousandsSeparator);
            }

            // Join with decimal separator
            let formattedNumber = parts.join(currencySettings.decimalSeparator);

            // Get currency symbol from the currencies array
            const selectedCurrency = currencies.find((c: CurrencyProps) => c.code === currencySettings.defaultCurrency);
            const symbol = selectedCurrency?.symbol || '$';

            // Add currency symbol with proper positioning and spacing
            const space = currencySettings.currencySymbolSpace ? ' ' : '';

            if (currencySettings.currencySymbolPosition === 'before') {
                return `${symbol}${space}${formattedNumber}`;
            } else {
                return `${formattedNumber}${space}${symbol}`;
            }
        } catch (error) {
            return 'Invalid format';
        }
    };

    // Handle currency settings form submission
    const saveCurrencySettings = () => {
        setIsLoading(true);

        router.post(route('settings.currency.update'), {
            settings: currencySettings
        }, {
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
                        <DollarSign className="h-5 w-5" />
                        {t('Currency Settings')}
                    </CardTitle>
                    <p className="text-sm text-muted-foreground mt-1">
                        {t('Configure how currency values are displayed throughout the application')}
                    </p>
                </div>
                {canEdit && (
                    <Button className="order-2 rtl:order-1"onClick={saveCurrencySettings} disabled={isLoading} size="sm">
                        <Save className="h-4 w-4 mr-2" />
                        {isLoading ? t('Saving...') : t('Save Changes')}
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                {/* Live Preview Section */}
                <div className="mb-6 p-4 bg-muted/30 rounded-md border flex flex-col md:flex-row items-center justify-between">
                    <div className="flex flex-col items-center md:items-start mb-3 md:mb-0">
                        <div className="text-2xl font-semibold mb-1">
                            {formattedPreview()}
                        </div>
                        <div className="text-xs text-muted-foreground">
                            {currencySettings.currencyName} ({currencySettings.defaultCurrency})
                        </div>
                    </div>
                    <div className="w-full md:w-auto md:max-w-[200px]">
                        <div className="flex items-center gap-2">
                            <Input
                                type="number"
                                className="text-right h-8 text-sm"
                                value={previewAmount}
                                onChange={(e) => setPreviewAmount(parseFloat(e.target.value) || 0)}
                                placeholder="Test amount"
                                disabled={!canEdit}
                            />
                            <Button
                                variant="outline"
                                onClick={() => setPreviewAmount(1234.56)}
                                type="button"
                                size="sm"
                                className="h-8 text-xs"
                                disabled={!canEdit}
                            >
                                {t("Reset")}
                            </Button>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-3">
                        <Label>{t('Default Currency')}</Label>
                        <Select
                            value={currencySettings.defaultCurrency}
                            onValueChange={handleCurrencyChange}
                            disabled={!canEdit}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select currency')} />
                            </SelectTrigger>
                            <SelectContent>
                                <div className="max-h-[300px] overflow-y-auto">
                                    {currencies && currencies.length > 0 ? (
                                        currencies.map((currency: CurrencyProps) => (
                                            <SelectItem key={currency.id} value={currency.code}>
                                                <div className="flex items-center">
                                                    <span className="w-8 text-center">{currency.symbol}</span>
                                                    <span>{currency.code} - {currency.name}</span>
                                                    {currency.code === currencySettings.defaultCurrency && (
                                                        <span className="ml-2 text-xs text-primary">(Selected)</span>
                                                    )}
                                                </div>
                                            </SelectItem>
                                        ))
                                    ) : (
                                        <div className="p-2 text-center text-muted-foreground">
                                            {t('No currencies found')}
                                        </div>
                                    )}
                                </div>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-3">
                        <Label>{t('Decimal Places')}</Label>
                        <Select
                            value={currencySettings.decimalFormat}
                            onValueChange={(value) => handleCurrencySettingsChange('decimalFormat', value)}
                            disabled={!canEdit}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select decimal format')} />
                            </SelectTrigger>
                            <SelectContent>
                                {decimalFormats.map((format) => (
                                    <SelectItem key={format.value} value={format.value}>
                                        {format.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-3">
                        <Label>{t('Symbol Position')}</Label>
                        <div className="grid grid-cols-2 gap-2">
                            <Button
                                type="button"
                                variant={currencySettings.currencySymbolPosition === 'before' ? "default" : "outline"}
                                className="justify-center"
                                onClick={() => handleCurrencySettingsChange('currencySymbolPosition', 'before')}
                                disabled={!canEdit}
                            >
                                <span className="mr-2">{currencies.find((c: CurrencyProps) => c.code === currencySettings.defaultCurrency)?.symbol || "$"}</span>100
                                {currencySettings.currencySymbolPosition === 'before' && (
                                    <Check className="h-4 w-4 ml-2" />
                                )}
                            </Button>
                            <Button
                                type="button"
                                variant={currencySettings.currencySymbolPosition === 'after' ? "default" : "outline"}
                                className="justify-center"
                                onClick={() => handleCurrencySettingsChange('currencySymbolPosition', 'after')}
                                disabled={!canEdit}
                            >
                                100<span className="ml-2">{currencies.find((c: CurrencyProps) => c.code === currencySettings.defaultCurrency)?.symbol || "$"}</span>
                                {currencySettings.currencySymbolPosition === 'after' && (
                                    <Check className="h-4 w-4 ml-2" />
                                )}
                            </Button>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <Label>{t('Decimal Separator')}</Label>
                        <div className="grid grid-cols-2 gap-2">
                            <Button
                                type="button"
                                variant={currencySettings.decimalSeparator === '.' ? "default" : "outline"}
                                className="justify-center"
                                onClick={() => handleCurrencySettingsChange('decimalSeparator', '.')}
                                disabled={!canEdit}
                            >
                                {t('Dot')} (123.45)
                                {currencySettings.decimalSeparator === '.' && (
                                    <Check className="h-4 w-4 ml-2" />
                                )}
                            </Button>
                            <Button
                                type="button"
                                variant={currencySettings.decimalSeparator === ',' ? "default" : "outline"}
                                className="justify-center"
                                onClick={() => handleCurrencySettingsChange('decimalSeparator', ',')}
                                disabled={!canEdit}
                            >
                                {t('Comma')} (123,45)
                                {currencySettings.decimalSeparator === ',' && (
                                    <Check className="h-4 w-4 ml-2" />
                                )}
                            </Button>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <Label>{t('Thousands Separator')}</Label>
                        <Select
                            value={currencySettings.thousandsSeparator}
                            onValueChange={(value) => handleCurrencySettingsChange('thousandsSeparator', value)}
                            disabled={!canEdit}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder={t('Select thousands separator')} />
                            </SelectTrigger>
                            <SelectContent>
                                {thousandsSeparators.map((separator) => (
                                    <SelectItem key={separator.value} value={separator.value}>
                                        {separator.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-3 border rounded-md p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <Label htmlFor="floatNumber">{t('Show Decimals')}</Label>
                                <p className="text-xs text-muted-foreground mt-1">{t('Display decimal places in amounts')}</p>
                            </div>
                            <Switch
                                id="floatNumber"
                                checked={currencySettings.floatNumber}
                                onCheckedChange={(checked) => handleCurrencySettingsChange('floatNumber', checked)}
                                disabled={!canEdit}
                            />
                        </div>
                    </div>

                    <div className="space-y-3 border rounded-md p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <Label htmlFor="currencySymbolSpace">{t('Add Space')}</Label>
                                <p className="text-xs text-muted-foreground mt-1">{t('Space between amount and symbol')}</p>
                            </div>
                            <Switch
                                id="currencySymbolSpace"
                                checked={currencySettings.currencySymbolSpace}
                                onCheckedChange={(checked) => handleCurrencySettingsChange('currencySymbolSpace', checked)}
                                disabled={!canEdit}
                            />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}