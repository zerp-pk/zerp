import { PaginatedData, ModalState, AuthContext, CreateProps } from '@/types/common';

export interface Coupon {
    id: number;
    name: string;
    description?: string;
    code: string;
    discount: number;
    limit?: number;
    type: 'percentage' | 'flat' | 'fixed';
    minimum_spend?: number;
    maximum_spend?: number;
    limit_per_user?: number;
    expiry_date?: string;
    included_module?: string[];
    excluded_module?: string[];
    status: boolean;
    created_at: string;
}

export interface CreateCouponFormData {
    name: string;
    description?: string;
    code: string;
    discount: number;
    limit?: number;
    type: 'percentage' | 'flat' | 'fixed';
    minimum_spend?: number;
    maximum_spend?: number;
    limit_per_user?: number;
    expiry_date?: string;
    included_module?: string[];
    excluded_module?: string[];
    status: boolean;
}

export interface EditCouponFormData extends CreateCouponFormData {}

export interface CreateCouponProps extends CreateProps {}

export interface EditCouponProps {
    coupon: Coupon;
    onSuccess: () => void;
}

export interface CouponFilters {
    name: string;
    code: string;
    type: string;
    status: string;
}

export type PaginatedCoupons = PaginatedData<Coupon>;
export type CouponModalState = ModalState<Coupon>;

export interface CouponsIndexProps {
    coupons: PaginatedCoupons;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CouponFormErrors {
    name?: string;
    description?: string;
    code?: string;
    discount?: string;
    limit?: string;
    type?: string;
    minimum_spend?: string;
    maximum_spend?: string;
    limit_per_user?: string;
    expiry_date?: string;
    included_module?: string;
    excluded_module?: string;
    status?: string;
}