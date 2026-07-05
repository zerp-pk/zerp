import { Input } from './input';
import { Label } from './label';
import InputError from './input-error';
import { useTranslation } from 'react-i18next';

interface PhoneInputProps {
    label?: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    error?: string;
    className?: string;
    id?: string;
    required?: boolean;
    readOnly?: boolean;
    style?: React.CSSProperties;
}

export function PhoneInputComponent({
    label,
    value,
    onChange,
    placeholder,
    error,
    className,
    id,
    required,
    readOnly,
    style
}: PhoneInputProps) {
    const { t } = useTranslation();
    return (
        <div>
            {label && <Label htmlFor={id} required={required}>{label}</Label>}
            <Input
                id={id}
                type="tel"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder || t('+1234567890')}
                className={`${className} ${readOnly ? 'bg-gray-50' : ''}`}
                pattern="^\+\d{1,3}\d{9,13}$"
                required={required}
                readOnly={readOnly}
                style={style}
            />
            <p className="text-xs text-muted-foreground mt-1">{t('Format: +[country code][phone number]')}</p>
            <InputError message={error} />
        </div>
    );
}