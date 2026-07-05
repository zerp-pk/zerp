// Generic pagination interface
export interface PaginatedData<T> {
    data: T[];
    links: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

// Generic modal state interface
export interface ModalState<T = any> {
    isOpen: boolean;
    mode: 'add' | 'edit' | '';
    data: T | null;
}

// Auth user interface
export interface AuthUser {
    id: number;
    type?: string;
    permissions: string[];
}

// Generic auth context
export interface AuthContext {
    user: AuthUser;
}

// Generic component props
export interface CreateProps {
    onSuccess: () => void;
}

export interface EditProps<T> {
    data: T;
    onSuccess: () => void;
}