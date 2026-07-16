import { NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { getSuperAdminMenu } from './menus/superadmin-menu';
import { getCompanyMenu } from './menus/company-menu';
import * as LucideIcons from 'lucide-react';
import { findPackageModule } from './helpers';

// Get role-based core menu items
const getCoreMenuItems = (userRoles: string[], t: (key: string) => string): NavItem[] => {
    if (userRoles.includes('superadmin')) {
        return getSuperAdminMenu(t);
    }
    return getCompanyMenu(t);
};

// Auto-load package menus based on activated packages
const getPackageMenuItems = (userRoles: string[], activatedPackages: string[], t: (key: string) => string): NavItem[] => {
    const menuItems: NavItem[] = [];
    const menuType = userRoles.includes('superadmin') ? 'superadmin-menu' : 'company-menu';

    const allModules = import.meta.glob([
        '../../../packages/local/*/src/Resources/js/menus/*.ts',
        '../../../vendor/zerp/*/src/Resources/js/menus/*.ts',
    ], { eager: true });

    // Ensure activatedPackages is an array before iterating
    if (!Array.isArray(activatedPackages)) {
        return menuItems;
    }

    activatedPackages.forEach(packageName => {
        const module = findPackageModule(allModules, packageName, `/src/Resources/js/menus/${menuType}.ts`) as any;

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

// Get custom menu items from database
const getCustomMenuItems = (userRoles: string[], t: (key: string) => string): NavItem[] => {
    const { auth } = usePage().props as any;
    const customMenus = auth?.customMenus || [];
    
    return customMenus.map((menu: any) => {
        // Convert string icon to Lucide icon component
        let iconComponent = null;
        if (menu.icon && typeof menu.icon === 'string') {
            const IconComponent = (LucideIcons as any)[menu.icon];
            if (IconComponent) {
                iconComponent = IconComponent;
            }
        }
        
        return {
            ...menu,
            icon: iconComponent,
        };
    });
};

// Group menu items by parent
const groupMenusByParent = (menuItems: NavItem[], packageMenuItems: NavItem[]): NavItem[] => {
    const groupedItems = [...menuItems];

    packageMenuItems.forEach(packageItem => {
        if (packageItem.parent) {
            const parentMenu = groupedItems.find(item =>
                item.name === packageItem.parent
            );

            if (parentMenu) {
                if (!parentMenu.children) {
                    parentMenu.children = [];
                }
                parentMenu.children.push({
                    ...packageItem,
                    parent: undefined
                });

                // Sort children by order
                if (parentMenu.children) {
                    parentMenu.children.sort((a, b) => (a.order || 999) - (b.order || 999));
                }
            } else {
                groupedItems.push(packageItem);
            }
        } else {
            groupedItems.push(packageItem);
        }
    });

    return groupedItems;
};

// Filter menu items based on permissions
const filterByPermission = (items: NavItem[], userPermissions: string[]): NavItem[] => {
    return items.filter(item => {
        if (!item.permission) {
            if (item.children) {
                item.children = filterByPermission(item.children, userPermissions);
            }
            return true;
        }

        if (!userPermissions.includes(item.permission)) {
            return false;
        }

        if (item.children) {
            item.children = filterByPermission(item.children, userPermissions);
            return item.children.length > 0;
        }

        return true;
    });
};

/** The sidebar arranger. It may be reordered, but never hidden. */
export const MENU_MANAGER_KEY = 'menu-manager';

/**
 * A stable identity for a top-level menu item.
 *
 * Not the title: titles are translated, so keying on them would scatter a user's
 * saved layout the moment they switched language. `name` and `permission` are
 * language-independent and every top-level item carries at least one.
 */
export const menuKey = (item: NavItem): string =>
    item.name || item.permission || item.href || item.title;

/**
 * Apply a saved sidebar layout: the chosen order, and the hidden items.
 *
 * Items the preference has never seen — a module installed since the user last
 * arranged their menu — keep their built-in order and follow the arranged ones,
 * rather than vanishing or jumping to the top.
 */
export const applyMenuPreference = (
    items: NavItem[],
    preference?: { order?: string[]; hidden?: string[] },
): NavItem[] => {
    const order = preference?.order ?? [];
    const hidden = preference?.hidden ?? [];

    // The menu manager itself is never hideable: hiding it would leave the user with
    // no way back into the screen that unhides things.
    const hideable = hidden.filter(key => key !== MENU_MANAGER_KEY);

    const visible = hideable.length ? items.filter(item => !hideable.includes(menuKey(item))) : items;

    if (!order.length) {
        return visible;
    }

    const rank = new Map(order.map((key, index) => [key, index]));

    return [...visible].sort((a, b) => {
        const ra = rank.get(menuKey(a));
        const rb = rank.get(menuKey(b));

        if (ra !== undefined && rb !== undefined) return ra - rb;
        if (ra !== undefined) return -1;   // arranged items first, in the chosen order
        if (rb !== undefined) return 1;
        return (a.order || 999) - (b.order || 999);   // the rest keep their built-in order
    });
};

// Main function to get filtered menu items
export const allMenuItems = (): NavItem[] => {
    const { auth } = usePage().props as any;
    const { t } = useTranslation();
    const userPermissions = auth?.user?.permissions || [];
    const userRoles = auth?.user?.roles || [];
    const activatedPackages = auth?.user?.activatedPackages || [];
    const menuPreference = auth?.menuPreference;

    const coreMenuItems = getCoreMenuItems(userRoles, t);

    const packageMenuItems = getPackageMenuItems(userRoles, activatedPackages, t);

    const customMenuItems = getCustomMenuItems(userRoles, t);

    // Separate custom menus into parents and children
    const customParentMenus = customMenuItems.filter(menu => !menu.parent);
    const customChildMenus = customMenuItems.filter(menu => menu.parent);

    // First add custom parent menus to core menus
    const coreWithCustomParents = [...coreMenuItems, ...customParentMenus];

    // Then group all children (package + custom children) with their parents
    const allChildMenus = [...packageMenuItems, ...customChildMenus];
    const finalGroupedMenuItems = groupMenusByParent(coreWithCustomParents, allChildMenus);

    const sortedMenuItems = finalGroupedMenuItems.sort((a, b) => (a.order || 999) - (b.order || 999));

    // Permissions decide what exists; the preference only decides how it is arranged.
    // Filter first, so a hidden item cannot smuggle back anything the user may not see.
    const finalMenuItems = filterByPermission(sortedMenuItems, userPermissions);

    return applyMenuPreference(finalMenuItems, menuPreference);
};