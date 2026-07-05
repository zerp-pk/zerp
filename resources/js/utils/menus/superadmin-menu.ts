import { LayoutGrid, Users, Building2, Settings, Shield, Image, Package, CreditCard, FileText, Ticket, Mail, Bell, Headphones} from 'lucide-react';
import { NavItem } from '@/types';

export const getSuperAdminMenu = (t: (key: string) => string): NavItem[] => [
    {
        title: t('Dashboard'),
        href: route('dashboard'),
        icon: LayoutGrid,
        permission: 'manage-dashboard',
        order: 1,
    },
    {
        title: t('Users'),
        href: route('users.index'),
        icon: Users,
        permission: 'manage-users',
        order: 20,
    },
    {
        title: t('Helpdesk'),
        icon: Headphones,
        permission: 'manage-helpdesk-tickets',
        order: 2750,
        children: [
            {
                title: t('Tickets'),
                href: route('helpdesk-tickets.index'),
                permission: 'manage-any-helpdesk-tickets',
            },
            {
                title: t('Categories'),
                href: route('helpdesk-categories.index'),
                permission: 'manage-helpdesk-categories',
            }
        ]
    },
    {
        title: t('Subscription'),
        icon: CreditCard,
        permission: 'manage-plans',
        order: 2800,
        children: [
            {
                title: t('Subscription Setting'),
                href: route('plans.index'),
                permission: 'manage-plans',
            },
            {
                title: t('Coupons'),
                href: route('coupons.index'),
                permission: 'manage-coupons',
            },
            {
                title: t('Bank Transfer Requests'),
                href: route('bank-transfer.index'),
                permission: 'manage-bank-transfer-requests',
            },
            {
                title: t('Orders'),
                href: route('orders.index'),
                permission: 'manage-orders',
            },
        ]
    },
    {
        title: t('Email Templates'),
        href: route('email-templates.index'),
        icon: Mail,
        permission: 'manage-email-templates',
        order: 2850,
    },
    {
        title: t('Notification Templates'),
        href: route('notification-templates.index'),
        icon: Bell,
        permission: 'manage-notification-templates',
        order: 2900,
    },
    {
        title: t('Media Library'),
        href: route('media-library'),
        icon: Image,
        permission: 'manage-media',
        order: 2950,
    },
    {
        title: t('Add-ons Manager'),
        href: route('add-ons.index'),
        icon: Package,
        permission: 'manage-add-on',
        order: 3000,
    },
    {
        title: t('Settings'),
        href: route('settings.index'),
        icon: Settings,
        permission: 'manage-settings',
        order: 3050,
    },
];
