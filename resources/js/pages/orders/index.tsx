import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { formatDate, formatAdminCurrency } from '@/utils/helpers';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Pagination } from '@/components/ui/pagination';
import { SearchInput } from '@/components/ui/search-input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { FilterButton } from '@/components/ui/filter-button';
import { Eye, ShoppingCart } from 'lucide-react';
import NoRecordsFound from '@/components/no-records-found';

interface Order {
    id: number;
    order_id: string;
    name: string;
    email: string;
    plan_name: string;
    price: string;
    currency: string;
    payment_status: string;
    payment_type: string;
    created_at: string;
    original_price?: string;
    total_coupon_used?: {
        coupon_detail?: {
            code: string;
            name: string;
        };
    };
    user?: {
        name: string;
        email: string;
    };
}

interface Props {
    orders: {
        data: Order[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
        links: any;
        meta: any;
    };
}

export default function OrdersIndex({ orders }: Props) {
    const { t } = useTranslation();
    const pageProps = usePage().props as any;
    const urlParams = new URLSearchParams(window.location.search);
    
    const [filters, setFilters] = useState({
        search: urlParams.get('search') || ''
    });
    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'desc');

    

    const handleFilter = () => {
        const params: any = {...filters, per_page: perPage};
        if (sortField) {
            params.sort = sortField;
            params.direction = sortDirection;
        }
        router.get(route('orders.index'), params, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('orders.index'), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ search: '' });
        setSortField('');
        setSortDirection('desc');
        router.get(route('orders.index'), {per_page: perPage});
    };

    const getStatusBadge = (status: string) => {
        return (
            <span className={`px-2 py-1 rounded-full text-sm ${
                status === 'succeeded' ? 'bg-green-100 text-green-800' :
                status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                status === 'failed' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800'
            }`}>
                {t(status.charAt(0).toUpperCase() + status.slice(1))}
            </span>
        );
    };



    const tableColumns = [
        {
            key: 'order_id',
            header: t('Order ID'),
            sortable: true,
            render: (_: any, order: Order) => order.order_id
        },
        {
            key: 'plan_name',
            header: t('Plan'),
            render: (_: any, order: Order) => (
                <span className="bg-gray-100 px-2 py-1 rounded-full text-xs font-medium text-gray-900">
                    {order.plan_name}
                </span>
            )
        },
        {
            
key: 'coupon_code',
            header: t('Coupon'),
            render: (_: any, order: Order) => {
                const couponCode = order.total_coupon_used?.coupon_detail?.code;
                return couponCode ? (
                    <Badge variant="outline" className="bg-blue-50 text-blue-700 border-blue-200">
                        {couponCode}
                    </Badge>
                ) : (
                    <span className="text-gray-400">-</span>
                );
            }
        },
        {
            key: 'price',
            header: t('Amount'),
            render: (_: any, order: Order) => (
                <div>
                    <div>{formatAdminCurrency(order.price, pageProps)}</div>
                    {order.total_coupon_used?.coupon_detail && order.original_price && (
                        <div className="text-xs text-gray-500">
                            <span className="line-through">{formatAdminCurrency(order.original_price, pageProps)}</span>
                        </div>
                    )}
                </div>
            )
        },
        {
            key: 'payment_status',
            header: t('Status'),
            sortable: true,
            render: (_: any, order: Order) => getStatusBadge(order.payment_status)
        },
        {
            key: 'payment_type',
            header: t('Payment Method'),
            render: (_: any, order: Order) => order.payment_type
        },
        {
            key: 'created_at',
            header: t('Date'),
            sortable: true,
            render: (_: any, order: Order) => formatDate(order.created_at, pageProps)
        },        
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Orders') }]}
            pageTitle={t('Manage Orders')}
        >
            <Head title={t('Orders')} />

            {/* Main Content Card */}
            <Card className="shadow-sm">
                {/* Search & Controls Header */}
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.search}
                                onChange={(value) => setFilters({...filters, search: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search orders...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="orders.index"
                                filters={filters}
                            />
                        </div>
                    </div>
                </CardContent>



                {/* Table Content */}
                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={orders.data}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={ShoppingCart}
                                        title={t('No orders found')}
                                        description={t('Orders will appear here when customers make purchases.')}
                                        hasFilters={!!(filters.search)}
                                        onClearFilters={clearFilters}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                {/* Pagination Footer */}
                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={orders}
                        routeName="orders.index"
                        filters={{...filters, per_page: perPage}}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}