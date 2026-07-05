import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { InputError } from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import { Separator } from '@/components/ui/separator';
import { CalendarDays, Package, RotateCcw, Trash2 } from 'lucide-react';
import { formatCurrency } from '@/utils/helpers';

interface PurchaseInvoice {
    id: number;
    invoice_number: string;
    vendor: {
        id: number;
        name: string;
    };
    warehouse?: {
        id: number;
        name: string;
    };
    items: Array<{
        id: number;
        product: {
            id: number;
            name: string;
            sku?: string;
        };
        quantity: number;
        available_quantity?: number;
        unit_price: number;
        discount_percentage?: number;
        discount_amount?: number;
        tax_percentage?: number;
        tax_amount?: number;
        taxes?: Array<{tax_name: string; tax_rate: number}>;
    }>;
}

interface CreateProps {
    invoices: PurchaseInvoice[];
    warehouses: Array<{id: number; name: string}>;
    [key: string]: any;
}

export default function Create() {
    const { t } = useTranslation();
    const { invoices, warehouses } = usePage<CreateProps>().props;


    const [selectedInvoice, setSelectedInvoice] = useState<PurchaseInvoice | null>(null);
    const [returnItems, setReturnItems] = useState<Array<{
        product_id: number;
        original_invoice_item_id: number;
        return_quantity: number;
        unit_price: number;
        reason: string;
        total_amount: number;
    }>>([]);

    const { data, setData, post, processing, errors } = useForm({
        return_date: new Date().toISOString().split('T')[0],
        vendor_id: '',
        warehouse_id: '',
        original_invoice_id: '',
        reason: 'defective',
        notes: '',
        items: [] as any[]
    });

    const handleInvoiceSelect = (invoiceId: string) => {
        const invoice = invoices.find(inv => inv.id.toString() === invoiceId);
        if (invoice) {
            setSelectedInvoice(invoice);
            setData({
                ...data,
                vendor_id: invoice.vendor.id.toString(),
                warehouse_id: invoice.warehouse?.id?.toString() || '',
                original_invoice_id: invoiceId
            });
            setReturnItems([]);
        }
    };

    const addReturnItem = (productId: number, originalInvoiceItemId: number, maxQuantity: number, unitPrice: number) => {
        const existingItem = returnItems.find(item => item.original_invoice_item_id === originalInvoiceItemId);
        if (!existingItem) {
            const originalItem = selectedInvoice?.items.find(i => i.id === originalInvoiceItemId);
            const lineTotal = 1 * unitPrice;
            const discountAmount = (lineTotal * (originalItem?.discount_percentage || 0)) / 100;
            const afterDiscount = lineTotal - discountAmount;
            const taxAmount = (afterDiscount * (originalItem?.tax_percentage || 0)) / 100;
            const totalAmount = afterDiscount + taxAmount;

            const newItem = {
                product_id: productId,
                original_invoice_item_id: originalInvoiceItemId,
                return_quantity: 1,
                unit_price: unitPrice,
                reason: '',
                total_amount: totalAmount
            };
            setReturnItems([...returnItems, newItem]);
        }
    };

    const updateReturnItem = (originalInvoiceItemId: number, field: string, value: any) => {
        setReturnItems(returnItems.map(item => {
            if (item.original_invoice_item_id === originalInvoiceItemId) {
                const updatedItem = { ...item, [field]: value };
                if (field === 'return_quantity' || field === 'unit_price') {
                    const originalItem = selectedInvoice?.items.find(i => i.id === originalInvoiceItemId);
                    const lineTotal = updatedItem.return_quantity * updatedItem.unit_price;
                    const discountAmount = (lineTotal * (originalItem?.discount_percentage || 0)) / 100;
                    const afterDiscount = lineTotal - discountAmount;
                    const taxAmount = (afterDiscount * (originalItem?.tax_percentage || 0)) / 100;
                    updatedItem.total_amount = afterDiscount + taxAmount;
                }
                return updatedItem;
            }
            return item;
        }));
    };

    const removeReturnItem = (originalInvoiceItemId: number) => {
        setReturnItems(returnItems.filter(item => item.original_invoice_item_id !== originalInvoiceItemId));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('purchase-returns.store'));
    };

    // Update form data when returnItems change
    React.useEffect(() => {
        setData('items', returnItems);
    }, [returnItems]);

    const totals = {
        subtotal: returnItems.reduce((sum, item) => {
            return sum + (item.return_quantity * item.unit_price);
        }, 0),
        discountAmount: returnItems.reduce((sum, item) => {
            const originalItem = selectedInvoice?.items.find(i => i.id === item.original_invoice_item_id);
            const lineTotal = item.return_quantity * item.unit_price;
            const discount = (lineTotal * (originalItem?.discount_percentage || 0)) / 100;
            return sum + discount;
        }, 0),
        taxAmount: returnItems.reduce((sum, item) => {
            const originalItem = selectedInvoice?.items.find(i => i.id === item.original_invoice_item_id);
            const lineTotal = item.return_quantity * item.unit_price;
            const discount = (lineTotal * (originalItem?.discount_percentage || 0)) / 100;
            const afterDiscount = lineTotal - discount;
            const tax = (afterDiscount * (originalItem?.tax_percentage || 0)) / 100;
            return sum + tax;
        }, 0),
        total: returnItems.reduce((sum, item) => sum + item.total_amount, 0)
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Purchase Returns'), url: route('purchase-returns.index')},
                {label: t('Create Purchase Return')}
            ]}
            pageTitle={t('Create Purchase Return')}
            backUrl={route('purchase-returns.index')}
        >
            <Head title={t('Create Purchase Return')} />

            <div>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Return Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <CalendarDays className="h-5 w-5" />
                                {t('Purchase Return Details')}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <Label htmlFor="return_date" required>
                                        {t('Return Date')}
                                    </Label>
                                    <DatePicker
                                        id="return_date"
                                        value={data.return_date}
                                        onChange={(value) => setData('return_date', value)}
                                        required
                                    />
                                    <InputError message={errors.return_date} />
                                </div>

                                <div>
                                    <Label htmlFor="original_invoice_id" required>
                                        {t('Original Invoice')}
                                    </Label>
                                    <Select value={data.original_invoice_id} onValueChange={handleInvoiceSelect}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Select Invoice')} />
                                        </SelectTrigger>
                                        <SelectContent searchable>
                                            {invoices.map((invoice) => (
                                                <SelectItem key={invoice.id} value={invoice.id.toString()}>
                                                    {invoice.invoice_number} - {invoice.vendor.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.original_invoice_id} />
                                </div>

                                <div>
                                    <Label htmlFor="warehouse_id" required>
                                        {t('Warehouse')}
                                    </Label>
                                    <Select value={data.warehouse_id} onValueChange={(value) => setData('warehouse_id', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Select Warehouse')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {warehouses.map((warehouse) => (
                                                <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                                    {warehouse.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.warehouse_id} />
                                </div>

                                <div>
                                    <Label htmlFor="reason" required>
                                        {t('Return Reason')}
                                    </Label>
                                    <Select value={data.reason} onValueChange={(value) => setData('reason', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Select Reason')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="defective">{t('Defective')}</SelectItem>
                                            <SelectItem value="wrong_item">{t('Wrong Item')}</SelectItem>
                                            <SelectItem value="damaged">{t('Damaged')}</SelectItem>
                                            <SelectItem value="excess_quantity">{t('Excess Quantity')}</SelectItem>
                                            <SelectItem value="other">{t('Other')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.reason} />
                                </div>
                            </div>

                            <div className="mt-4">
                                <Label htmlFor="notes">
                                    {t('Notes')}
                                </Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    rows={2}
                                    placeholder={t('Additional notes...')}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Available Items */}
                    {selectedInvoice && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Package className="h-5 w-5" />
                                    {t('Available Items from Invoice')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full">
                                        <thead>
                                            <tr className="border-b border-border">
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Product')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Available Qty')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Unit Price')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Discount')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Tax')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Total')}</th>
                                                <th className="px-4 py-3 text-center text-sm font-semibold text-foreground">{t('Action')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border">
                                            {selectedInvoice.items.map((item) => (
                                                <tr key={item.id}>
                                                    <td className="px-4 py-4">
                                                        <div>
                                                            <h4 className="font-medium">{item.product.name}</h4>
                                                            <p className="text-xs text-muted-foreground">{item.product.sku || ''}</p>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <span className={`text-sm font-medium ${(item.available_quantity || 0) <= 0 ? 'text-red-600' : ''}`}>
                                                            {item.available_quantity !== undefined ? item.available_quantity : item.quantity}
                                                        </span>
                                                        {(item.available_quantity || 0) <= 0 && (
                                                            <div className="text-xs text-red-600 mt-1">{t('No items available for return')}</div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <span className="text-sm">{formatCurrency(item.unit_price)}</span>
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        {(item.discount_percentage || 0) > 0 ? (
                                                            <div className="text-sm">
                                                                <span>{item.discount_percentage || 0}%</span>
                                                                <div className="text-xs text-muted-foreground">({formatCurrency(item.discount_amount || 0)})</div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-muted-foreground">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        {item.taxes && item.taxes.length > 0 ? (
                                                            <div className="flex flex-wrap gap-1">
                                                                {item.taxes.map((tax, taxIndex) => (
                                                                    <span key={taxIndex} className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                        {tax.tax_name} ({tax.tax_rate}%)
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        ) : (item.tax_percentage || 0) > 0 ? (
                                                            <div className="text-sm">
                                                                <span>{item.tax_percentage || 0}%</span>
                                                                <div className="text-xs text-muted-foreground">({formatCurrency(item.tax_amount || 0)})</div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-muted-foreground">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <span className="text-sm font-medium">{formatCurrency((() => {
                                                            const qty = item.available_quantity || item.quantity;
                                                            const lineTotal = qty * item.unit_price;
                                                            const discountAmount = (lineTotal * (item.discount_percentage || 0)) / 100;
                                                            const afterDiscount = lineTotal - discountAmount;
                                                            const taxAmount = (afterDiscount * (item.tax_percentage || 0)) / 100;
                                                            return afterDiscount + taxAmount;
                                                        })())}</span>
                                                    </td>
                                                    <td className="px-4 py-4 text-center">
                                                        <Button
                                                            type="button"
                                                            onClick={() => addReturnItem(item.product.id, item.id, item.available_quantity || item.quantity, item.unit_price)}
                                                            disabled={returnItems.some(ri => ri.original_invoice_item_id === item.id) || (item.available_quantity || 0) <= 0}
                                                            size="sm"
                                                        >
                                                            {returnItems.some(ri => ri.original_invoice_item_id === item.id) ? t('Added') : t('Add to Return')}
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Return Items */}
                    {returnItems.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <RotateCcw className="h-5 w-5" />
                                    {t('Return Items')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full">
                                        <thead>
                                            <tr className="border-b border-border">
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Product')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Return Qty')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Unit Price')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Discount')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Tax')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Total')}</th>
                                                <th className="px-4 py-3 text-left text-sm font-semibold text-foreground">{t('Reason')}</th>
                                                <th className="px-4 py-3 text-center text-sm font-semibold text-foreground">{t('Action')}</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border">
                                            {returnItems.map((item) => {
                                                const originalItem = selectedInvoice?.items.find(i => i.id === item.original_invoice_item_id);
                                                return (
                                                    <tr key={item.original_invoice_item_id}>
                                                        <td className="px-4 py-4">
                                                            <div>
                                                                <p className="font-medium">{originalItem?.product.name}</p>
                                                                <p className="text-xs text-muted-foreground">{originalItem?.product.sku || ''}</p>
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <Input
                                                                type="number"
                                                                min="1"
                                                                max={originalItem?.available_quantity || originalItem?.quantity}
                                                                value={item.return_quantity}
                                                                onChange={(e) => updateReturnItem(item.original_invoice_item_id, 'return_quantity', parseInt(e.target.value) || 1)}
                                                                className="w-20 text-sm"
                                                            />
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <span className="text-sm">{formatCurrency(item.unit_price)}</span>
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            {(originalItem?.discount_percentage || 0) > 0 ? (
                                                                <div className="text-sm">
                                                                    <span>{originalItem?.discount_percentage || 0}%</span>
                                                                    <div className="text-xs text-muted-foreground">
                                                                        -{formatCurrency((item.return_quantity * item.unit_price * (originalItem?.discount_percentage || 0)) / 100)}
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <span className="text-sm text-muted-foreground">-</span>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            {originalItem?.taxes && originalItem.taxes.length > 0 ? (
                                                                <div className="flex flex-wrap gap-1">
                                                                    {originalItem.taxes.map((tax, taxIndex) => (
                                                                        <span key={taxIndex} className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                            {tax.tax_name} ({tax.tax_rate}%)
                                                                        </span>
                                                                    ))}
                                                                </div>
                                                            ) : (originalItem?.tax_percentage || 0) > 0 ? (
                                                                <div className="text-sm">
                                                                    <span>{originalItem?.tax_percentage || 0}%</span>
                                                                    <div className="text-xs text-muted-foreground">
                                                                        {formatCurrency((() => {
                                                                            const lineTotal = item.return_quantity * item.unit_price;
                                                                            const discount = (lineTotal * (originalItem?.discount_percentage || 0)) / 100;
                                                                            const afterDiscount = lineTotal - discount;
                                                                            return (afterDiscount * (originalItem?.tax_percentage || 0)) / 100;
                                                                        })())}
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <span className="text-sm text-muted-foreground">-</span>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <span className="text-sm font-medium">{formatCurrency(item.total_amount)}</span>
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <Input
                                                                value={item.reason}
                                                                onChange={(e) => updateReturnItem(item.original_invoice_item_id, 'reason', e.target.value)}
                                                                placeholder={t('Optional reason')}
                                                                className="text-sm"
                                                            />
                                                        </td>
                                                        <td className="px-4 py-4 text-center">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => removeReturnItem(item.original_invoice_item_id)}
                                                                className="text-red-600 hover:text-red-800 h-8 w-8 p-0"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Return Summary */}
                                <div className="mt-6 flex justify-end">
                                    <div className="w-80 bg-muted/30 rounded-lg p-4">
                                        <h3 className="font-semibold mb-3">{t('Return Summary')}</h3>
                                        <div>
                                            <div className="flex justify-between text-sm">
                                                <span className="text-muted-foreground">{t('Subtotal')}</span>
                                                <span className="font-medium">{formatCurrency(totals.subtotal)}</span>
                                            </div>
                                            {totals.discountAmount > 0 && (
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-muted-foreground">{t('Discount')}</span>
                                                    <span className="font-medium text-red-600">-{formatCurrency(totals.discountAmount)}</span>
                                                </div>
                                            )}
                                            {totals.taxAmount > 0 && (
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-muted-foreground">{t('Tax')}</span>
                                                    <span className="font-medium">{formatCurrency(totals.taxAmount)}</span>
                                                </div>
                                            )}
                                            <div className="border-t pt-3">
                                                <div className="flex justify-between">
                                                    <span className="font-semibold">{t('Total Return Amount')}</span>
                                                    <span className="font-bold text-lg">{formatCurrency(totals.total)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Actions */}
                    <div className="flex justify-between items-center">
                        <div className="text-sm text-muted-foreground">
                            {returnItems.length} {t('items selected for return')}
                        </div>
                        <div className="flex gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => window.history.back()}
                            >
                                {t('Cancel')}
                            </Button>
                            <Button
                                type="submit"
                                disabled={processing || returnItems.length === 0}
                            >
                                {processing ? t('Creating...') : t('Create')}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
