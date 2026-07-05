import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import { usePageButtons } from '@/hooks/usePageButtons';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit as EditIcon, Trash2, Eye, CheckCircle, Check, XCircle } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { formatCurrency, formatDate } from '@/utils/helpers';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import NoRecordsFound from '@/components/no-records-found';

interface PurchaseReturn {
    id: number;
    return_number: string;
    return_date: string;
    vendor: {
        name: string;
    };
    total_amount: number;
    status: string;
    reason: string;
    warehouse?: {
        name: string;
    };
    items?: any[];
}

interface PurchaseReturnFilters {
    search: string;
    vendor_id: string;
    status: string;
    date_range: string;
    warehouse_id: string;
}

interface PurchaseReturnIndexProps {
    returns: {
        data: PurchaseReturn[];
        links: any[];
        meta: any;
    };
    vendors: Array<{id: number; name: string; email: string}>;
    warehouses: Array<{id: number; name: string}>;
    filters: PurchaseReturnFilters;
    auth: any;
    [key: string]: any;
}

export default function Index() {
    const { t } = useTranslation();
    const { returns, vendors, warehouses, filters: initialFilters, auth } = usePage<PurchaseReturnIndexProps>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState<PurchaseReturnFilters>({
        search: initialFilters?.search || urlParams.get('search') || '',
        vendor_id: initialFilters?.vendor_id || urlParams.get('vendor_id') || '',
        status: initialFilters?.status || urlParams.get('status') || '',
        date_range: initialFilters?.date_range || urlParams.get('date_range') || '',
        warehouse_id: initialFilters?.warehouse_id || urlParams.get('warehouse_id') || ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);


    const pageButtons = usePageButtons('purchaseReturnBtn', 'Purchase Return data');

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'purchase-returns.destroy',
        defaultMessage: t('Are you sure you want to delete this purchase return?')
    });

    const getStatusBadgeClasses = (status: string) => {
        const colors = {
            draft: 'bg-gray-100 text-gray-800',
            approved: 'bg-green-100 text-green-800',
            completed: 'bg-blue-100 text-blue-800',
            cancelled: 'bg-red-100 text-red-800'
        };
        return `px-2 py-1 rounded-full text-sm ${colors[status as keyof typeof colors]}`;
    };

    const handleFilter = () => {
        router.get(route('purchase-returns.index'), {...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('purchase-returns.index'), {...filters, per_page: perPage, sort: field, direction, view: viewMode}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ search: '', vendor_id: '', status: '', date_range: '', warehouse_id: '' });
        router.get(route('purchase-returns.index'), {per_page: perPage, view: viewMode});
    };

    const tableColumns = [
        {
            key: 'return_number',
            header: t('Return Number'),
            sortable: true,
            render: (value: string, returnItem: PurchaseReturn) =>
                auth.user?.permissions?.includes('view-purchase-return-invoices') ? (
                    <span className="text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('purchase-returns.show', returnItem.id))}>{value}</span>
                ) : (
                    value
                )
        },
        {
            key: 'vendor',
            header: t('Vendor'),
            render: (value: any) => value?.name || '-'
        },
        {
            key: 'warehouse',
            header: t('Warehouse'),
            render: (value: any) => value?.name || '-'
        },
        {
            key: 'return_date',
            header: t('Return Date'),
            sortable: true,
            render: (value: string) => formatDate(value)
        },
        {
            key: 'total_amount',
            header: t('Total Amount'),
            sortable: true,
            render: (value: number) => formatCurrency(value)
        },
        {
            key: 'items',
            header: t('Items'),
            render: (value: any, returnItem: PurchaseReturn) => (
                <div className="text-sm">
                    {returnItem.items?.slice(0, 2).map((item: any, index: number) => (
                        <div key={index} className="flex justify-between">
                            <span className="truncate">{item.product?.name}</span>
                            <span className="ml-2 text-muted-foreground">×{item.return_quantity}</span>
                        </div>
                    ))}
                    {returnItem.items && returnItem.items.length > 2 && (
                        <div className="text-xs text-muted-foreground mt-1">
                            +{returnItem.items.length - 2} more items
                        </div>
                    )}
                </div>
            )
        },

        {
            key: 'status',
            header: t('Status'),
            sortable: true,
            render: (value: string) => (
                <span className={getStatusBadgeClasses(value)}>
                    {t(value.charAt(0).toUpperCase() + value.slice(1))}
                </span>
            )
        },
        ...(auth.user?.permissions?.some((p: string) => ['view-purchase-return-invoices', 'delete-purchase-return-invoices', 'approve-purchase-returns-invoices'].includes(p)) ? [{
            key: 'actions',
            header: t('Actions'),
            render: (_: any, returnItem: PurchaseReturn) => (
                <div className="flex gap-1">
                    <TooltipProvider>
                        {returnItem.status === 'draft' && (
                            <>
                            {auth.user?.permissions?.includes('approve-purchase-returns-invoices') && (
                                <Tooltip delayDuration={0}>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => router.post(route('purchase-returns.approve', returnItem.id))}
                                            className="h-8 w-8 p-0 text-gray-600 hover:text-gray-700"
                                        >
                                            <CheckCircle className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{t('Approve Return')}</p>
                                    </TooltipContent>
                                </Tooltip>
                            )}
                            </>
                        )}
                        {returnItem.status === 'approved' && auth.user?.permissions?.includes('complete-purchase-returns-invoices') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.post(route('purchase-returns.complete', returnItem.id))}
                                        className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                    >
                                        <Check className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Complete Return')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {auth.user?.permissions?.includes('view-purchase-return-invoices') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => router.get(route('purchase-returns.show', returnItem.id))}
                                        className="h-8 w-8 p-0 text-green-600 hover:text-green-700"
                                    >
                                        <Eye className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('View')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {returnItem.status === 'draft' && (
                            <>
                                {auth.user?.permissions?.includes('delete-purchase-return-invoices') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openDeleteDialog(returnItem.id)}
                                                className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Delete')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                            </>
                        )}

                    </TooltipProvider>
                </div>
            )
        }] : [])
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('Purchase Returns')}]}
            pageTitle={t('Manage Purchase Returns')}
            pageActions={
                <div className="flex gap-2">
                    <TooltipProvider>
                        {auth.user?.permissions?.includes('create-purchase-return-invoices') && (
                            <Tooltip delayDuration={0}>
                                <TooltipTrigger asChild>
                                    <Button size="sm" onClick={() => router.visit(route('purchase-returns.create'))}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p>{t('Create')}</p>
                                </TooltipContent>
                            </Tooltip>
                        )}
                        {pageButtons.map((button) => (
                            <div key={button.id}>{button.component}</div>
                        ))}
                    </TooltipProvider>
                </div>
            }
        >
            <Head title={t('Purchase Returns')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search || ''}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search by return number...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="purchase-returns.index"
                                filters={{...filters, per_page: perPage}}
                            />
                            <PerPageSelector
                                routeName="purchase-returns.index"
                                filters={{...filters, view: viewMode}}
                            />
                            <div className="relative">
                                <FilterButton
                                    showFilters={showFilters}
                                    onToggle={() => setShowFilters(!showFilters)}
                                />
                                {(() => {
                                    const activeFilters = [filters.vendor_id, filters.status, filters.date_range, filters.warehouse_id].filter(Boolean).length;
                                    return activeFilters > 0 && (
                                        <span className="absolute -top-2 -right-2 bg-primary text-primary-foreground text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium">
                                            {activeFilters}
                                        </span>
                                    );
                                })()}
                            </div>
                        </div>
                    </div>
                </CardContent>

                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                            {auth.user?.permissions?.includes('manage-users') && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Vendor')}</label>
                                    <Select value={filters.vendor_id} onValueChange={(value) => setFilters({...filters, vendor_id: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by vendor')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {vendors.map((vendor) => (
                                                <SelectItem key={vendor.id} value={vendor.id.toString()}>
                                                    {vendor.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            {auth.user?.permissions?.includes('manage-warehouses') && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-2">{t('Warehouse')}</label>
                                    <Select value={filters.warehouse_id} onValueChange={(value) => setFilters({...filters, warehouse_id: value})}>
                                        <SelectTrigger>
                                            <SelectValue placeholder={t('Filter by warehouse')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {warehouses?.map((warehouse) => (
                                                <SelectItem key={warehouse.id} value={warehouse.id.toString()}>
                                                    {warehouse.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Status')}</label>
                                <Select value={filters.status} onValueChange={(value) => setFilters({...filters, status: value})}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by status')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">{t('Draft')}</SelectItem>
                                        <SelectItem value="approved">{t('Approved')}</SelectItem>
                                        <SelectItem value="completed">{t('Completed')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Date Range')}</label>
                                <DateRangePicker
                                    value={filters.date_range}
                                    onChange={(value) => setFilters({...filters, date_range: value})}
                                    placeholder={t('Select date range')}
                                />
                            </div>
                            <div className="flex items-end gap-2">
                                <Button onClick={handleFilter} size="sm">{t('Apply')}</Button>
                                <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                            </div>
                        </div>
                    </CardContent>
                )}

                <CardContent className="p-0">
                    {viewMode === 'list' ? (
                        <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                            <div className="min-w-[800px]">
                                <DataTable
                                    data={returns.data}
                                    columns={tableColumns}
                                    onSort={handleSort}
                                    sortKey={sortField}
                                    sortDirection={sortDirection as 'asc' | 'desc'}
                                    className="rounded-none"
                                    emptyState={
                                        <NoRecordsFound
                                            icon={XCircle}
                                            title={t('No purchase returns found')}
                                            description={t('Get started by creating your first purchase return.')}
                                            hasFilters={!!(filters.search || filters.vendor_id || filters.status || filters.warehouse_id || filters.date_range)}
                                            onClearFilters={clearFilters}
                                            createPermission="create-purchase-return-invoices"
                                            onCreateClick={() => router.visit(route('purchase-returns.create'))}
                                            createButtonText={t('Create Purchase Return')}
                                            className="h-auto"
                                        />
                                    }
                                />
                            </div>
                        </div>
                    ) : (
                        <div className="overflow-auto max-h-[70vh] p-4">
                            {returns.data.length > 0 ? (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                                    {returns.data.map((returnItem) => (
                                        <Card key={returnItem.id} className="border border-gray-200 flex flex-col">
                                            <div className="p-4 flex-1">
                                                <div className="flex items-center justify-between mb-3">
                                                    {auth.user?.permissions?.includes('view-purchase-return-invoices') ? (
                                                        <h3 className="font-semibold text-base text-blue-600 hover:text-blue-700 cursor-pointer" onClick={() => router.get(route('purchase-returns.show', returnItem.id))}>{returnItem.return_number}</h3>
                                                    ) : (
                                                        <h3 className="font-semibold text-base text-gray-900">{returnItem.return_number}</h3>
                                                    )}
                                                    <span className={getStatusBadgeClasses(returnItem.status)}>
                                                        {t(returnItem.status.charAt(0).toUpperCase() + returnItem.status.slice(1))}
                                                    </span>
                                                </div>

                                                <div className="space-y-2 mb-4">
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Vendor')}</p>
                                                            <p className="text-sm text-gray-900 truncate font-medium">{returnItem.vendor?.name}</p>
                                                        </div>
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1 text-end">{t('Return Date')}</p>
                                                            <p className="text-xs text-gray-900 text-end">{formatDate(returnItem.return_date)}</p>
                                                        </div>
                                                    </div>
                                                    {returnItem.warehouse && (
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-600 mb-1">{t('Warehouse')}</p>
                                                            <p className="text-xs text-gray-900">{returnItem.warehouse.name}</p>
                                                        </div>
                                                    )}

                                                    <div>
                                                        <p className="text-xs font-medium text-gray-600 mb-1">{t('Items')}</p>
                                                        <div className="text-xs text-gray-900">
                                                            {returnItem.items?.slice(0, 2).map((item: any, index: number) => (
                                                                <div key={index} className="flex justify-between">
                                                                    <span className="truncate">{item.product?.name}</span>
                                                                    <span className="ml-2 text-muted-foreground">×{item.return_quantity}</span>
                                                                </div>
                                                            ))}
                                                            {returnItem.items && returnItem.items.length > 2 && (
                                                                <div className="text-xs text-muted-foreground mt-1">
                                                                    +{returnItem.items.length - 2} more items
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="bg-gray-50 rounded-lg p-3">
                                                        <div className="flex justify-between items-center">
                                                            <span className="text-sm font-semibold text-gray-900">{t('Total Amount')}</span>
                                                            <span className="text-lg font-bold text-gray-900">{formatCurrency(returnItem.total_amount)}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <div className="flex items-center justify-between p-3 border-t bg-gray-50/50">
                                                <div className="flex gap-1">
                                                    <TooltipProvider>
                                                        {returnItem.status === 'draft' && auth.user?.permissions?.includes('approve-purchase-returns-invoices') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => router.post(route('purchase-returns.approve', returnItem.id))} className="h-8 w-8 p-0 text-gray-600 hover:text-gray-700">
                                                                        <CheckCircle className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent><p>{t('Approve Return')}</p></TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                        {returnItem.status === 'approved' && auth.user?.permissions?.includes('complete-purchase-returns-invoices') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => router.post(route('purchase-returns.complete', returnItem.id))} className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700">
                                                                        <Check className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent><p>{t('Complete Return')}</p></TooltipContent>
                                                            </Tooltip>
                                                        )}

                                                    </TooltipProvider>
                                                </div>
                                                <div className="flex gap-1">
                                                    <TooltipProvider>
                                                        {auth.user?.permissions?.includes('view-purchase-return-invoices') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => router.get(route('purchase-returns.show', returnItem.id))} className="h-8 w-8 p-0 text-green-600 hover:text-green-700">
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent><p>{t('View')}</p></TooltipContent>
                                                            </Tooltip>
                                                        )}

                                                         {returnItem.status === 'draft' && auth.user?.permissions?.includes('delete-purchase-return-invoices') && (
                                                            <Tooltip delayDuration={0}>
                                                                <TooltipTrigger asChild>
                                                                    <Button variant="ghost" size="sm" onClick={() => openDeleteDialog(returnItem.id)} className="h-8 w-8 p-0 text-destructive hover:text-destructive">
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent><p>{t('Delete')}</p></TooltipContent>
                                                            </Tooltip>
                                                        )}
                                                    </TooltipProvider>
                                                </div>
                                            </div>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <NoRecordsFound
                                    icon={XCircle}
                                    title={t('No purchase returns found')}
                                    description={t('Get started by creating your first purchase return.')}
                                    hasFilters={!!(filters.search || filters.vendor_id || filters.status || filters.warehouse_id || filters.date_range)}
                                    onClearFilters={clearFilters}
                                    createPermission="create-purchase-return-invoices"
                                    onCreateClick={() => router.visit(route('purchase-returns.create'))}
                                    createButtonText={t('Create Purchase Return')}
                                />
                            )}
                        </div>
                    )}
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={{...returns, ...returns.meta}}
                        routeName="purchase-returns.index"
                        filters={{...filters, per_page: perPage, view: viewMode}}
                    />
                </CardContent>
            </Card>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Purchase Return')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
