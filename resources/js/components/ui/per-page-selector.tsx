import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTranslation } from 'react-i18next';

export interface PerPageOption {
  value: string;
  label: string;
}

interface PerPageSelectorProps {
  routeName: string;
  filters?: Record<string, any>;
  defaultValue?: string;
  options?: PerPageOption[];
  className?: string;
  onPageChange?: (value: string, allParams: Record<string, any>) => void;
}

export function PerPageSelector({ 
  routeName,
  filters = {},
  defaultValue = '10',
  options,
  className = 'w-32',
  onPageChange
}: PerPageSelectorProps) {
  const { t } = useTranslation();
  
  const defaultOptions = [
    { value: '10', label: t('10 per page') },
    { value: '25', label: t('25 per page') },
    { value: '50', label: t('50 per page') },
    { value: '100', label: t('100 per page') },
  ];
  const [perPage, setPerPage] = useState(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('per_page') || defaultValue;
  });

  const handlePerPageChange = (value: string) => {
    setPerPage(value);
    
    const params = {
      ...filters,
      per_page: value,
      page: 1
    };

    if (onPageChange) {
      onPageChange(value, params);
    } else {
      router.get(route(routeName), params, {
        preserveState: false,
        replace: true
      });
    }
  };

  return (
    <Select value={perPage} onValueChange={handlePerPageChange}>
      <SelectTrigger className={className}>
        <SelectValue />
      </SelectTrigger>
      <SelectContent>
        {(options || defaultOptions).map((option) => (
          <SelectItem key={option.value} value={option.value}>
            {option.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}