import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface Warehouse {
    id: number;
    name: string;
    address: string;
    city: string;
    zip_code: string;
    phone?: string;
    email?: string;
    is_active: boolean;
    created_at: string;
}

export interface CreateWarehouseFormData {
    name: string;
    address: string;
    city: string;
    zip_code: string;
    phone: string;
    email: string;
    is_active: boolean;
    [key: string]: any;
}

export interface EditWarehouseFormData {
    name: string;
    address: string;
    city: string;
    zip_code: string;
    phone?: string;
    email?: string;
    is_active: boolean;
    [key: string]: any;
}

export interface CreateWarehouseProps extends CreateProps {}

export interface EditWarehouseProps extends EditProps<Warehouse> {
    warehouse: Warehouse;
}

export interface WarehouseFilters {
    name: string;
    city: string;
    is_active: string;
}

export type PaginatedWarehouses = PaginatedData<Warehouse>;
export type WarehouseModalState = ModalState<Warehouse>;

export interface WarehousesIndexProps {
    warehouses: PaginatedWarehouses;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface WarehouseFormErrors {
    name?: string;
    address?: string;
    city?: string;
    zip_code?: string;
    phone?: string;
    email?: string;
    is_active?: string;
}