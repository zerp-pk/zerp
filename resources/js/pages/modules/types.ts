import { AuthContext } from '@/types/common';

export interface Module {
    name: string;
    alias: string;
    description: string;
    version: string;
    image: string;
    is_enabled: boolean;
    package_name?: string;
    display?: boolean;
}

export interface ModulesIndexProps {
    modules: Module[];
    auth: AuthContext;
    [key: string]: unknown;
}
