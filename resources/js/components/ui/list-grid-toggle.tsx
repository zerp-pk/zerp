import { Button } from '@/components/ui/button';
import { List, Grid3X3 } from 'lucide-react';
import { router } from '@inertiajs/react';

interface ListGridToggleProps {
    currentView: 'list' | 'grid';
    routeName: string;
    routeParams?: any[];
    filters?: Record<string, any>;
    onViewChange?: (view: 'list' | 'grid') => void;
}

export function ListGridToggle({ currentView, routeName, routeParams = [], filters = {}, onViewChange }: ListGridToggleProps) {
    const handleViewChange = (view: 'list' | 'grid') => {
        const urlParams = new URLSearchParams(window.location.search);
        const params = { ...filters, view, page: urlParams.get('page') || '1' };
        
        if (onViewChange) {
            onViewChange(view);
        }
        
        router.get(route(routeName, ...routeParams), params, {
            preserveState: false,
            replace: true
        });
    };

    return (
        <div className="flex flex-row items-center border rounded-md">
            <Button
                variant={currentView === 'list' ? 'default' : 'ghost'}
                size="sm"
                onClick={() => handleViewChange('list')}
                className="rounded-r-none"
            >
                <List className="h-4 w-4" />
            </Button>
            <Button
                variant={currentView === 'grid' ? 'default' : 'ghost'}
                size="sm"
                onClick={() => handleViewChange('grid')}
                className="rounded-l-none"
            >
                <Grid3X3 className="h-4 w-4" />
            </Button>
        </div>
    );
}