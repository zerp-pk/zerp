import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { useForm, usePage } from "@inertiajs/react";
import { useTranslation } from 'react-i18next';
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { DatePicker } from "@/components/ui/date-picker";
import InputError from "@/components/ui/input-error";

import { CreateTransferProps, CreateTransferFormData, TransfersIndexProps } from './types';

export default function Create({ onSuccess }: CreateTransferProps) {
    const { t } = useTranslation();
    const { warehouses, products, warehouseStocks } = usePage<TransfersIndexProps>().props;
    
    const { data, setData, post, processing, errors } = useForm<CreateTransferFormData>({
        from_warehouse: '',
        to_warehouse: '',
        product_id: '',
        quantity: '',
        date: new Date().toISOString().split('T')[0],
    });

    // Filter warehouses for "to" dropdown (exclude selected "from" warehouse)
    const availableToWarehouses = warehouses.filter(w => w.id.toString() !== data.from_warehouse);
    
    // Filter products based on selected warehouse and show available quantity
    const availableProducts = warehouseStocks
        ?.filter(stock => stock.warehouse_id.toString() === data.from_warehouse && Number(stock.quantity) > 0)
        ?.map(stock => ({
            ...stock.product,
            available_quantity: stock.quantity
        })) || [];

    const handleFromWarehouseChange = (value: string) => {
        setData({
            ...data,
            from_warehouse: value,
            to_warehouse: data.to_warehouse === value ? '' : data.to_warehouse,
            product_id: '',
            quantity: ''
        });
    };

    const selectedProduct = availableProducts.find(p => p.id.toString() === data.product_id);
    const maxQuantity = selectedProduct?.available_quantity || 0;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('transfers.store'), {
            onSuccess: () => {
                onSuccess();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Create Transfer')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="from_warehouse">{t('From Warehouse')}</Label>
                    <Select value={data.from_warehouse} onValueChange={handleFromWarehouseChange}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select warehouse')} />
                        </SelectTrigger>
                        <SelectContent>
                            {warehouses.map((warehouse) => (
                                <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                    {warehouse.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.from_warehouse} />
                </div>

                <div>
                    <Label htmlFor="to_warehouse">{t('To Warehouse')}</Label>
                    <Select value={data.to_warehouse} onValueChange={(value) => setData('to_warehouse', value)} disabled={!data.from_warehouse}>
                        <SelectTrigger>
                            <SelectValue placeholder={data.from_warehouse ? t('Select warehouse') : t('Select from warehouse first')} />
                        </SelectTrigger>
                        <SelectContent>
                            {availableToWarehouses.map((warehouse) => (
                                <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                    {warehouse.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.to_warehouse} />
                </div>

                <div>
                    <Label htmlFor="product_id">{t('Product')}</Label>
                    <Select value={data.product_id} onValueChange={(value) => setData('product_id', value)} disabled={!data.from_warehouse}>
                        <SelectTrigger>
                            <SelectValue placeholder={data.from_warehouse ? t('Select product') : t('Select from warehouse first')} />
                        </SelectTrigger>
                        <SelectContent>
                            {availableProducts.map((product) => (
                                <SelectItem key={product.id} value={product.id.toString()}>
                                    {product.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.product_id} />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="quantity">{t('Quantity')}</Label>
                        <Input
                            id="quantity"
                            type="number"
                            step="1"
                            min="1"
                            max={maxQuantity}
                            value={data.quantity}
                            onChange={(e) => setData('quantity', e.target.value)}
                            placeholder={selectedProduct ? `Available: ${maxQuantity}` : t('Select product first')}
                            disabled={!data.product_id}
                            required
                        />
                        <InputError message={errors.quantity} />
                    </div>
                    <div>
                        <Label>{t('Date')}</Label>
                        <DatePicker
                            value={data.date}
                            onChange={(value) => setData('date', value)}
                            placeholder={t('Select transfer date')}
                        />
                        <InputError message={errors.date} />
                    </div>
                </div>

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