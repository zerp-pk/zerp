export interface Role {
    id: number;
    name: string;
    label: string;
    editable: boolean;
    permissions_count?: number;
    users?: Array<{
        id: number;
        name: string;
    }>;
}

export interface RolesIndexProps {
    roles: {
        data: Role[];
        links: any[];
        meta: any;
    };
    auth: {
        user: {
            permissions?: string[];
        };
    };
    [key: string]: any;
}

export interface RoleFilters {
    name: string;
    [key: string]: any;
}

export interface Permission {
    id: number;
    name: string;
    label: string;
    module: string;
}

export interface RoleCreateProps {
    permissions: Record<string, Permission[]>;
    [key: string]: any;
}

export interface RoleEditProps {
    role: Role & { editable: boolean };
    permissions: Record<string, Permission[]>;
    rolePermissions: string[];
    [key: string]: any;
}

export interface RoleModalState {
    isOpen: boolean;
    mode: string;
    data: Role | null;
}