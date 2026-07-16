import { SettingMenuItem } from './menus/superadmin-setting';
import { getSuperAdminSettings } from './menus/superadmin-setting';
import { getCompanySettings } from './menus/company-setting';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { findPackageModule } from './helpers';

// Get role-based core settings items
const getCoreSettingsItems = (userRoles: string[], t: (key: string) => string): SettingMenuItem[] => {
    if (userRoles.includes('superadmin')) {
        return getSuperAdminSettings(t);
    }
    return getCompanySettings(t);
};

// Auto-load package settings based on activated packages
const getPackageSettingsItems = (userRoles: string[], activatedPackages: string[], t: (key: string) => string): SettingMenuItem[] => {
    const menuItems: SettingMenuItem[] = [];
    const settingType = userRoles.includes('superadmin') ? 'superadmin-setting' : 'company-setting';

    const allModules = userRoles.includes('superadmin')
        ? import.meta.glob([
            '../../../packages/local/*/src/Resources/js/settings/superadmin-setting.ts',
            '../../../vendor/zerp/*/src/Resources/js/settings/superadmin-setting.ts',
          ], { eager: true })
        : import.meta.glob([
            '../../../packages/local/*/src/Resources/js/settings/company-setting.ts',
            '../../../vendor/zerp/*/src/Resources/js/settings/company-setting.ts',
          ], { eager: true });

    (Array.isArray(activatedPackages) ? activatedPackages : []).forEach(packageName => {
        const module = findPackageModule(allModules, packageName, `/src/Resources/js/settings/${settingType}.ts`) as any;

        if (module) {
            Object.values(module).forEach((item: any) => {
                const result = typeof item === 'function' ? item(t) : item;
                const items = Array.isArray(result) ? result : [result];
                menuItems.push(...items);
            });
        }
    });

    return menuItems;
};

// Filter settings items based on permissions
const filterByPermission = (items: SettingMenuItem[], userPermissions: string[]): SettingMenuItem[] => {
    // An item with no permission is a personal preference rather than a privilege,
    // so it shows for everyone - staff hold no settings permission at all.
    return items.filter(item => !item.permission || userPermissions.includes(item.permission));
};

// Main function to get filtered settings items
export const allSettingsItems = (): SettingMenuItem[] => {
    const { auth } = usePage().props as any;
    const { t } = useTranslation();
    const userPermissions = auth?.user?.permissions || [];
    const userRoles = auth?.user?.roles || [];
    const activatedPackages = auth?.user?.activatedPackages || [];

    const coreSettingsItems = getCoreSettingsItems(userRoles, t);
    const packageSettingsItems = getPackageSettingsItems(userRoles, activatedPackages, t);

    const allItems = [...coreSettingsItems, ...packageSettingsItems];

    // Sort by order
    const sortedItems = allItems.sort((a, b) => (a.order || 999) - (b.order || 999));

    return filterByPermission(sortedItems, userPermissions);
};