import { PaginatedData, ModalState, AuthContext, CreateProps } from '@/types/common';

export interface Transfer {
    id: number;
    from_warehouse: {
        id: number;
        name: string;
    };
    to_warehouse: {
        id: number;
        name: string;
    };
    product: {
        id: number;
        name: string;
        sku: string;
    };
    quantity: string;
    date: string;
    created_at: string;
}

export interface Warehouse {
    id: number;
    name: string;
}

export interface Product {
    id: number;
    name: string;
    sku: string;
}

export interface CreateTransferFormData {
    from_warehouse: string;
    to_warehouse: string;
    product_id: string;
    quantity: string;
    date: string;
}

export interface CreateTransferProps extends CreateProps {}

export interface TransferFilters {
    product_name: string;
    from_warehouse: string;
}

export type PaginatedTransfers = PaginatedData<Transfer>;
export type TransferModalState = ModalState<Transfer>;

export interface WarehouseStock {
    id: number;
    product_id: number;
    warehouse_id: number;
    quantity: string;
    product: Product;
}

export interface TransfersIndexProps {
    transfers: PaginatedTransfers;
    warehouses: Warehouse[];
    products: Product[];
    warehouseStocks?: WarehouseStock[];
    auth: AuthContext;
    [key: string]: any;
}