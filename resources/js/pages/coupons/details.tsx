import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Input } from '@/components/ui/input';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Users } from "lucide-react";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import NoRecordsFound from '@/components/no-records-found';
import { formatDate } from '@/utils/helpers';

interface UserCoupon {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
    order_id: string;
    created_at: string;
}

interface CouponDetailsProps {
    coupon: {
        id: number;
        name: string;
        code: string;
        discount: number;
        type: string;
        status: boolean;
    };
    usageRecords: {
        data: UserCoupon[];
        links: any[];
        meta: any;
    };
    auth: any;
    [key: string]: any;
}

interface UsageFilters {
    user_name: string;
    order_id: string;
}

export default function Details() {
    const { t } = useTranslation();
    const { coupon, usageRecords, auth, ...pageProps } = usePage<CouponDetailsProps>().props;
    const urlParams = new URLSearchParams(window.location.search);
    const currencySymbol = (pageProps as any)?.companyAllSetting?.currencySymbol || '$';

    const [filters, setFilters] = useState<UsageFilters>({
        user_name: urlParams.get('user_name') || '',
        order_id: ''
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');



    const handleFilter = () => {
        router.get(route('coupons.show', coupon.id), {...filters, per_page: perPage, sort: sortField, direction: sortDirection}, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('coupons.show', coupon.id), {...filters, per_page: perPage, sort: field, direction}, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ user_name: '', order_id: '' });
        router.get(route('coupons.show', coupon.id), {per_page: perPage});
    };

    const tableColumns = [
        {
            key: 'user_name',
            header: t('User Name'),
            sortable: true,
            render: (_: any, record: UserCoupon) => record?.user?.name || 'N/A'
        },
        {
            key: 'user_email', 
            header: t('User Email'),
            render: (_: any, record: UserCoupon) => record?.user?.email || 'N/A'
        },
        {
            key: 'order_id',
            header: t('Order ID'),
            sortable: true,
            render: (_: any, record: UserCoupon) => record?.order_id || 'N/A'
        },
        {
            key: 'created_at',
            header: t('Used At'),
            sortable: true,
            render: (_: any, record: UserCoupon) => record?.created_at ? formatDate(record.created_at, pageProps) : 'N/A'
        }
    ];  

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                {label: t('Coupons'), url: route('coupons.index')},
                {label: coupon.name}
            ]}
            pageTitle={t('Coupon Details')}
            backUrl={route('coupons.index')}
        >
            <Head title={`${t('Coupon Details')} - ${coupon.name}`} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.user_name}
                                onChange={(value) => setFilters({...filters, user_name: value})}
                                onSearch={handleFilter}
                                placeholder={t('Search by user name...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="coupons.show"
                                filters={{...filters, coupon_id: coupon.id}}
                            />
                        </div>
                    </div>
                </CardContent>



                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <DataTable
                            data={usageRecords.data}
                            columns={tableColumns}
                            onSort={handleSort}
                            sortKey={sortField}
                            sortDirection={sortDirection as 'asc' | 'desc'}
                            className="rounded-none"
                            emptyState={
                                <NoRecordsFound
                                    icon={Users}
                                    title={t('No usage records found')}
                                    description={t('This coupon has not been used yet.')}
                                    hasFilters={!!(filters.user_name)}
                                    onClearFilters={clearFilters}
                                    className="h-auto"
                                />
                            }
                        />
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={{
                            current_page: usageRecords.meta?.current_page || 1,
                            last_page: usageRecords.meta?.last_page || 1,
                            per_page: usageRecords.meta?.per_page || 10,
                            total: usageRecords.meta?.total || 0,
                            from: usageRecords.meta?.from || 0,
                            to: usageRecords.meta?.to || 0
                        }}
                        routeName="coupons.show"
                        filters={{...filters, per_page: perPage, coupon_id: coupon.id}}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}