import { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { findPackageModule } from '../utils/helpers';

interface FormField {
    id: string;
    component: ReactNode;
    order?: number;
}

export const useFormFields = (hookName: string, data: any, setData: any, errors: any, mode: string = 'create', ...additionalParams: any[]): FormField[] => {
    try {
        const { auth } = usePage().props as any;
        const activatedPackages = auth?.user?.activatedPackages || [];
        const allModules = import.meta.glob([
            '../../../packages/local/*/src/Resources/js/fields/fields.tsx',
            '../../../vendor/zerp/*/src/Resources/js/fields/fields.tsx',
        ], { eager: true });

        const fields: FormField[] = [];

        activatedPackages.forEach((packageName: string) => {
            const module = findPackageModule(allModules, packageName, '/src/Resources/js/fields/fields.tsx') as any;

            if (module && module[hookName]) {
                const fieldExport = module[hookName];
                if (typeof fieldExport === 'function') {
                    try {
                        const fieldComponents = fieldExport(data, setData, errors, mode, ...additionalParams);
                        if (Array.isArray(fieldComponents)) {
                            fields.push(...fieldComponents);
                        } else if (fieldComponents) {
                            fields.push(fieldComponents);
                        }
                    } catch (error) {
                        // Silent error handling
                    }
                }
            }
        });

        return fields.sort((a, b) => (a.order || 999) - (b.order || 999));
    } catch (error) {
        return [];
    }
};