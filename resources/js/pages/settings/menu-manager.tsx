import { useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { DragDropContext, Draggable, Droppable, type DropResult } from '@hello-pangea/dnd';
import { Eye, EyeOff, GripVertical } from 'lucide-react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { allMenuItems, applyMenuPreference, menuKey, MENU_MANAGER_KEY } from '@/utils/menu';
import { cn } from '@/lib/utils';

type Scope = 'user' | 'company';

interface Preference {
    order?: string[];
    hidden?: string[];
    source?: 'user' | 'company' | 'default';
}

export default function MenuManager() {
    const { t } = useTranslation();
    const { preference, companyDefault, canSetCompanyDefault } = usePage().props as unknown as {
        preference: Preference;
        companyDefault: Preference;
        canSetCompanyDefault: boolean;
    };

    const [scope, setScope] = useState<Scope>('user');

    // The real sidebar, already filtered by permission - so the manager can only ever
    // offer items the user actually has.
    const menu = allMenuItems();

    const starting = scope === 'company' ? companyDefault : preference;

    const [items, setItems] = useState(() => applyMenuPreference(menu, starting).map(menuKey));
    const [hidden, setHidden] = useState<string[]>(() => starting?.hidden ?? []);

    const labels = useMemo(() => {
        const map = new Map<string, string>();
        menu.forEach(item => map.set(menuKey(item), item.title));
        return map;
    }, [menu]);

    // Switching tab means editing a different layout, so reload that one.
    const switchScope = (next: Scope) => {
        const source = next === 'company' ? companyDefault : preference;
        setScope(next);
        setItems(applyMenuPreference(menu, source).map(menuKey));
        setHidden(source?.hidden ?? []);
    };

    const onDragEnd = (result: DropResult) => {
        if (!result.destination) return;

        const next = [...items];
        const [moved] = next.splice(result.source.index, 1);
        next.splice(result.destination.index, 0, moved);
        setItems(next);
    };

    const toggleHidden = (key: string) => {
        setHidden(current =>
            current.includes(key) ? current.filter(k => k !== key) : [...current, key],
        );
    };

    const save = () => {
        router.put(route('settings.menu.update'), { scope, order: items, hidden }, { preserveScroll: true });
    };

    const reset = () => {
        router.delete(route('settings.menu.reset'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Menu')} />

            <div className="max-w-2xl space-y-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">{t('Menu')}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {t('Drag to reorder your sidebar. Hiding an item only removes it from the sidebar - it does not change what you are allowed to open.')}
                    </p>
                </div>

                {canSetCompanyDefault && (
                    <Tabs value={scope} onValueChange={value => switchScope(value as Scope)}>
                        <TabsList>
                            <TabsTrigger value="user">{t('My sidebar')}</TabsTrigger>
                            <TabsTrigger value="company">{t('Company default')}</TabsTrigger>
                        </TabsList>
                    </Tabs>
                )}

                {scope === 'company' && (
                    <p className="text-muted-foreground text-sm">
                        {t('This is what everyone in the company sees, unless they have arranged their own sidebar.')}
                    </p>
                )}

                {scope === 'user' && preference?.source === 'company' && (
                    <p className="text-muted-foreground text-sm">
                        {t('You are currently using the company default. Rearranging below makes it your own.')}
                    </p>
                )}

                <DragDropContext onDragEnd={onDragEnd}>
                    <Droppable droppableId="menu">
                        {dropProvided => (
                            <div ref={dropProvided.innerRef} {...dropProvided.droppableProps} className="space-y-2">
                                {items.map((key, index) => {
                                    const isHidden = hidden.includes(key);

                                    return (
                                        <Draggable key={key} draggableId={key} index={index}>
                                            {(dragProvided, snapshot) => (
                                                <Card
                                                    ref={dragProvided.innerRef}
                                                    {...dragProvided.draggableProps}
                                                    className={cn(
                                                        'flex items-center gap-3 p-3',
                                                        snapshot.isDragging && 'shadow-lg',
                                                        isHidden && 'opacity-50',
                                                    )}
                                                >
                                                    <span
                                                        {...dragProvided.dragHandleProps}
                                                        className="text-muted-foreground cursor-grab active:cursor-grabbing"
                                                        aria-label={t('Reorder')}
                                                    >
                                                        <GripVertical className="h-4 w-4" />
                                                    </span>

                                                    <span className="flex-1 truncate text-sm">
                                                        {labels.get(key) ?? key}
                                                    </span>

                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        disabled={key === MENU_MANAGER_KEY}
                                                        title={key === MENU_MANAGER_KEY ? t('This page cannot be hidden') : undefined}
                                                        onClick={() => toggleHidden(key)}
                                                        aria-label={isHidden ? t('Show') : t('Hide')}
                                                    >
                                                        {isHidden ? (
                                                            <EyeOff className="h-4 w-4" />
                                                        ) : (
                                                            <Eye className="h-4 w-4" />
                                                        )}
                                                    </Button>
                                                </Card>
                                            )}
                                        </Draggable>
                                    );
                                })}
                                {dropProvided.placeholder}
                            </div>
                        )}
                    </Droppable>
                </DragDropContext>

                <div className="flex gap-2">
                    <Button onClick={save}>{t('Save')}</Button>

                    {scope === 'user' && preference?.source === 'user' && (
                        <Button variant="outline" onClick={reset}>
                            {t('Reset to company default')}
                        </Button>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
