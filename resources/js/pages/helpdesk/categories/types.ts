import { PaginatedData, ModalState, AuthContext } from '@/types/common';

export interface HelpdeskCategory {
    id: number;
    name: string;
    description?: string;
    color: string;
    is_active: boolean;
    created_at: string;
}

export interface CreateHelpdeskCategoryFormData {
    name: string;
    description: string;
    color: string;
    is_active: boolean;
}

export interface EditHelpdeskCategoryFormData {
    name: string;
    description?: string;
    color: string;
    is_active: boolean;
}

export interface HelpdeskCategoryFilters {
    name: string;
    is_active: string;
}

export type PaginatedHelpdeskCategories = PaginatedData<HelpdeskCategory>;
export type HelpdeskCategoryModalState = ModalState<HelpdeskCategory>;

export interface HelpdeskCategoriesIndexProps {
    categories: PaginatedHelpdeskCategories;
    auth: AuthContext;
    [key: string]: unknown;
}

export interface CreateHelpdeskCategoryProps {
    onSuccess: () => void;
}

export interface EditHelpdeskCategoryProps {
    category: HelpdeskCategory;
    onSuccess: () => void;
}