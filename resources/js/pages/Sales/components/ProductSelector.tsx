import React from 'react';
import { useTranslation } from 'react-i18next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/utils/helpers';

interface Product {
    id: number;
    name: string;
    sale_price: number;
    unit?: string;
    taxes?: Array<{id: number; tax_name: string; rate: number}>;
}

interface Props {
    products: Product[];
    value: number;
    onChange: (productId: number, product?: Product) => void;
}

export default function ProductSelector({ products, value, onChange }: Props) {
    const { t } = useTranslation();

    const handleChange = (productId: string) => {
        const id = parseInt(productId);
        const product = products.find(p => p.id === id);
        onChange(id, product);
    };

    return (
        <Select value={value.toString()} onValueChange={handleChange}>
            <SelectTrigger className="w-full">
                <SelectValue placeholder={t('Select Product')} />
            </SelectTrigger>
            <SelectContent searchable>
                {products.map((product) => (
                    <SelectItem key={product.id} value={product.id.toString()}>
                        {product.name} - {formatCurrency(product.sale_price || 0)}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}