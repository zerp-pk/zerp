import { PaginatedData, ModalState, AuthContext, CreateProps, EditProps } from '@/types/common';

export interface Item {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    created_at: string;
}

export interface ItemFormData {
    name: string;
    description?: string;
    is_active: boolean;
}

export interface CreateItemProps extends CreateProps {}

export interface EditItemProps extends EditProps<Item> {}

export interface ItemFilters {
    name: string;
    is_active: string | boolean;
}

export type PaginatedItems = PaginatedData<Item>;
export type ItemModalState = ModalState<Item>;

export interface ItemsIndexProps {
    items: PaginatedItems;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface ItemFormErrors {
    name?: string;
    description?: string;
    is_active?: string;
}