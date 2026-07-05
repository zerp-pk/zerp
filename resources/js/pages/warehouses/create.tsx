import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import InputError from "@/components/ui/input-error";
import { PhoneInputComponent } from "@/components/ui/phone-input";
import { useFormFields } from '@/hooks/useFormFields';

import { CreateWarehouseProps, CreateWarehouseFormData } from './types';

export default function Create({ onSuccess }: CreateWarehouseProps) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<CreateWarehouseFormData>({
        name: '',
        address: '',
        city: '',
        zip_code: '',
        phone: '',
        email: '',
        is_active: true,
    });

    // Hook for dynamic package fields
    const formFields = useFormFields('warehouse', data, setData, errors);

    // AI hook for warehouse name field
    const nameAI = useFormFields('aiField', data, setData, errors, 'create', 'name', 'Warehouse Name', 'general', 'warehouses');



    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('warehouses.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Warehouse')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div className="flex gap-2 items-end">
                    <div className="flex-1">
                        <Label htmlFor="name">{t('Name')}</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={t('Enter warehouse name')}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>
                    {nameAI.map(field => <div key={field.id}>{field.component}</div>)}
                </div>
                <div>
                    <Label htmlFor="address">{t('Address')}</Label>
                    <Input
                        id="address"
                        value={data.address}
                        onChange={(e) => setData('address', e.target.value)}
                        placeholder={t('Enter full address')}
                        required
                    />
                    <InputError message={errors.address} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="city">{t('City')}</Label>
                        <Input
                            id="city"
                            value={data.city}
                            onChange={(e) => setData('city', e.target.value)}
                            placeholder={t('Enter city')}
                            required
                        />
                        <InputError message={errors.city} />
                    </div>
                    <div>
                        <Label htmlFor="zip_code">{t('Zip Code')}</Label>
                        <Input
                            id="zip_code"
                            value={data.zip_code}
                            onChange={(e) => setData('zip_code', e.target.value)}
                            placeholder={t('Enter zip code')}
                            required
                        />
                        <InputError message={errors.zip_code} />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="phone">{t('Phone')}</Label>
                        <PhoneInputComponent
                            value={data.phone}
                            onChange={(value) => setData('phone', value)}
                            placeholder={t('Enter phone number')}
                        />
                        <InputError message={errors.phone} />
                    </div>
                    <div>
                        <Label htmlFor="email">{t('Email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder={t('Enter email address')}
                        />
                        <InputError message={errors.email} />
                    </div>
                </div>
                <div>
                    <Label htmlFor="is_active">{t('Status')}</Label>
                    <Select value={data.is_active ? "1" : "0"} onValueChange={(value) => setData('is_active', value === "1")}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="1">{t('Active')}</SelectItem>
                            <SelectItem value="0">{t('Inactive')}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError message={errors.is_active} />
                </div>
                {formFields.map((field) => (
                    <div key={field.id}>{field.component}</div>
                ))}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('Creating...') : t('Create')}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
