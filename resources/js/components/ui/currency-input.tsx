import { useState, useEffect } from 'react';
import { Input } from './input';
import { Label } from './label';
import InputError from './input-error';
import { getCurrencySymbol } from '@/utils/helpers';

interface CurrencyInputProps {
    label?: string;
    value: string;
    onChange: (value: string) => void;
    currency?: string;
    placeholder?: string;
    error?: string;
    className?: string;
    id?: string;
    required?: boolean;
    disabled?: boolean;
}

export function CurrencyInput({ 
    label, 
    value, 
    onChange, 
    currency, 
    placeholder = '0.00',
    error,
    className,
    id,
    required,
    disabled
}: CurrencyInputProps) {
    const [displayValue, setDisplayValue] = useState('');
    const currencySymbol = getCurrencySymbol() || currency;

    const formatCurrency = (val: string) => {
        const numericValue = val.replace(/[^\d.]/g, '');
        const parts = numericValue.split('.');
        if (parts.length > 2) {
            return parts[0] + '.' + parts.slice(1).join('');
        }
        if (parts[1] && parts[1].length > 2) {
            return parts[0] + '.' + parts[1].substring(0, 2);
        }
        return numericValue;
    };

    useEffect(() => {
        setDisplayValue(value);
    }, [value]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const formatted = formatCurrency(e.target.value);
        setDisplayValue(formatted);
        onChange(formatted);
    };

    return (
        <div>
            {label && <Label htmlFor={id} required={required}>{label}</Label>}
            <div className="relative">
                <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground">
                    {currencySymbol}
                </span>
                <Input
                    id={id}
                    type="text"
                    value={displayValue}
                    onChange={handleChange}
                    placeholder={placeholder}
                    className={`pl-8 ${className}`}
                    required={required}
                    disabled={disabled}
                />
            </div>
            <InputError message={error} />
        </div>
    );
}