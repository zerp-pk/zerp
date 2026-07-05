import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { SearchInput } from '@/components/ui/search-input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Pagination } from '@/components/ui/pagination';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Edit, Bell } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import NoRecordsFound from '@/components/no-records-found';
import { getPackageAlias } from '@/utils/helpers';

interface NotificationTemplate {
    id: number;
    module: string;
    action: string;
    type: string;
    status: string;
    permissions: string;
}

interface Props {
    [key: string]: any;
    notificationTemplates: {
        data: NotificationTemplate[];
        links: any[];
        meta: any;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    allTypes: string[];
    activeType: string;
    auth: {
        user: {
            permissions: string[];
        };
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { notificationTemplates, allTypes, activeType, auth } = usePage<Props>().props;

    const availableTypes = allTypes.filter(type => type !== 'mail');
    const urlParams = new URLSearchParams(window.location.search);
    const [searchValue, setSearchValue] = useState(urlParams.get('action') || '');
    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');


    const handleTabChange = (type: string) => {
        router.get(route('notification-templates.index'), {type, action: searchValue, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSearch = () => {
        router.get(route('notification-templates.index'), {type: activeType, action: searchValue, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('notification-templates.index'), {type: activeType, action: searchValue, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const tableColumns = [
        {
            key: 'action',
            header: t('Subject'),
            sortable: true
        },
        {
            key: 'module',
            header: t('Module'),
            sortable: true,
            render: (value: string) => (value ? getPackageAlias(value) : '-')
        },
        {
            key: 'actions',
            header: t('Actions'),
            render: (_: any, template: NotificationTemplate) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('edit-notification-templates') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.visit(route('notification-templates.edit', template.id))}
                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                    >
                                        <Edit className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Edit')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                    </TooltipProvider>
                </div>
            )
        }
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Notification Templates') }]}
            pageTitle={t('Manage Notification Templates')}
        >
            <Head title={t('Notification Templates')} />

            {availableTypes.length > 0 && (
                <div className='mb-4'>
                    <Tabs value={activeType} onValueChange={handleTabChange}>
                        <TabsList>
                            {availableTypes.map(type => (
                                <TabsTrigger key={type} value={type} className="capitalize">
                                    {getPackageAlias(type)}
                                </TabsTrigger>
                            ))}
                        </TabsList>
                    </Tabs>
                </div>
            )}

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={searchValue}
                                onChange={(value) => setSearchValue(value)}
                                onSearch={handleSearch}
                                placeholder={t('Search notification templates...')}
                            />
                        </div>
                        <PerPageSelector
                            routeName="notification-templates.index"
                        />
                    </div>
                </CardContent>



                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={notificationTemplates.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Bell}
                                        title={t('No notification templates found')}
                                        description={t('Notification templates will appear here.')}

                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={notificationTemplates}
                        routeName="notification-templates.index"
                        filters={{type: activeType, action: searchValue, per_page: perPage}}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
