import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

import { DatePicker } from '@/components/ui/date-picker';
import { CurrencyInput } from '@/components/ui/currency-input';

import { CreateCouponProps, CreateCouponFormData, CouponFormErrors } from './types';

export default function Create({ onSuccess }: CreateCouponProps) {
    const { t } = useTranslation();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { props } = usePage();
    const currencySymbol = (props as any)?.companyAllSetting?.currencySymbol || '$';

    const { data, setData, post, errors, reset } = useForm<CreateCouponFormData>({
        name: '',
        description: '',
        code: '',
        discount: 0,
        limit: undefined,
        type: 'percentage',
        minimum_spend: undefined,
        maximum_spend: undefined,
        limit_per_user: undefined,
        expiry_date: '',
        included_module: [],
        excluded_module: [],
        status: true
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        post(route('coupons.store'), {
            onSuccess: () => {
                reset();
                onSuccess();
            },
            onFinish: () => setIsSubmitting(false)
        });
    };

    return (
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{t('Create Coupon')}</DialogTitle>
            </DialogHeader>

            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">{t('Name')}</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={t('Enter coupon name')}
                            className={errors.name ? 'border-red-500' : ''}
                            required
                        />
                        {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="code">{t('Code')}</Label>
                        <div className="flex gap-2">
                            <Input
                                id="code"
                                value={data.code}
                                onChange={(e) => setData('code', e.target.value.toUpperCase())}
                                placeholder={t('Enter coupon code')}
                                className={errors.code ? 'border-red-500' : ''}
                                required
                            />
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setData('code', 'COUP-' + Date.now())}
                                disabled={isSubmitting}
                            >
                                {t('Generate')}
                            </Button>
                        </div>
                        {errors.code && <p className="text-sm text-red-500">{errors.code}</p>}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="type" required>{t('Type')}</Label>
                        <Select value={data.type} onValueChange={(value: 'percentage' | 'flat' | 'fixed') => setData('type', value)}>
                            <SelectTrigger className={errors.type ? 'border-red-500' : ''}>
                                <SelectValue placeholder={t('Select type')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="percentage">{t('Percentage')}</SelectItem>
                                <SelectItem value="flat">{t('Flat Amount')}</SelectItem>
                                <SelectItem value="fixed">{t('Fixed Price')}</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.type && <p className="text-sm text-red-500">{errors.type}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="discount" required>
                            {t('Discount')} {data.type === 'percentage' ? '(%)' : `(${currencySymbol})`}
                        </Label>
                        <Input
                            id="discount"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.discount}
                            onChange={(e) => setData('discount', parseFloat(e.target.value) || 0)}
                            placeholder={t('Enter discount value')}
                            className={errors.discount ? 'border-red-500' : ''}
                        />
                        {errors.discount && <p className="text-sm text-red-500">{errors.discount}</p>}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="limit">{t('Usage Limit')}</Label>
                        <Input
                            id="limit"
                            type="number"
                            min="1"
                            value={data.limit || ''}
                            onChange={(e) => setData('limit', e.target.value ? parseInt(e.target.value) : undefined)}
                            placeholder={t('Enter Usage Limit')}
                            className={errors.limit ? 'border-red-500' : ''}
                        />
                        {errors.limit && <p className="text-sm text-red-500">{errors.limit}</p>}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="limit_per_user">{t('Limit Per User')}</Label>
                        <Input
                            id="limit_per_user"
                            type="number"
                            min="1"
                            value={data.limit_per_user || ''}
                            onChange={(e) => setData('limit_per_user', e.target.value ? parseInt(e.target.value) : undefined)}
                            placeholder={t('Enter Limit Per User')}
                            className={errors.limit_per_user ? 'border-red-500' : ''}
                        />
                        {errors.limit_per_user && <p className="text-sm text-red-500">{errors.limit_per_user}</p>}
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <CurrencyInput
                            label={t('Minimum Spend')}
                            value={(data.minimum_spend || '').toString()}
                            onChange={(value) => setData('minimum_spend', value ? parseFloat(value) : undefined)}
                            error={errors.minimum_spend}
                            placeholder={t('Enter minimum spend')}
                        />
                    </div>

                    <div>
                        <CurrencyInput
                            label={t('Maximum Spend')}
                            value={(data.maximum_spend || '').toString()}
                            onChange={(value) => setData('maximum_spend', value ? parseFloat(value) : undefined)}
                            error={errors.maximum_spend}
                            placeholder={t('Enter maximum spend')}
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label htmlFor="expiry_date">{t('Expiry Date')}</Label>
                    <DatePicker
                        value={data.expiry_date}
                        onChange={(value) => setData('expiry_date', value)}
                        placeholder={t('Select expiry date')}
                        className={errors.expiry_date ? 'border-red-500' : ''}
                    />
                    {errors.expiry_date && <p className="text-sm text-red-500">{errors.expiry_date}</p>}
                </div>

                <div className="space-y-2">
                    <Label htmlFor="description">{t('Description')}</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder={t('Enter coupon description')}
                        rows={3}
                        className={errors.description ? 'border-red-500' : ''}
                    />
                    {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                </div>
                <div className="flex items-center space-x-2">
                    <Switch
                        id="status"
                        checked={data.status}
                        onCheckedChange={(checked) => setData('status', checked)}
                    />
                    <Label htmlFor="status">{t('Active')}</Label>
                </div>

                <div className="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={isSubmitting}>
                        {isSubmitting ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
