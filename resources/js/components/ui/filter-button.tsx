import { Button } from "@/components/ui/button";
import { Filter, ChevronDown } from "lucide-react";
import { useTranslation } from 'react-i18next';

interface FilterButtonProps {
    showFilters: boolean;
    onToggle: () => void;
    icon?: React.ComponentType<{ className?: string }>;
    label?: string;
    className?: string;
}

export function FilterButton({ 
    showFilters, 
    onToggle, 
    icon: Icon = Filter,
    label,
    className = ""
}: FilterButtonProps) {
    const { t } = useTranslation();
    return (
        <Button 
            variant="outline" 
            onClick={onToggle} 
            className={`flex items-center gap-2 ${className}`}
        >
            <Icon className="h-4 w-4" />
            {label || t('Filters')}
            <ChevronDown className={`h-4 w-4 transition-transform ${showFilters ? 'rotate-180' : ''}`} />
        </Button>
    );
}