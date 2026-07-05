import { Palette, Building, Settings as SettingsIcon, Search, HardDrive, Mail, Cookie, Trash2, DollarSign, CreditCard, FileText, Radio } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  permission: string;
  component: string;
}

export const getSuperAdminSettings = (t: (key: string) => string): SettingMenuItem[] => [
  {
    order: 10,
    title: t('Brand Settings'),
    href: '#brand-settings',
    icon: Palette,
    permission: 'manage-brand-settings',
    component: 'brand-settings'
  },
  {
    order: 20,
    title: t('System Settings'),
    href: '#system-settings',
    icon: SettingsIcon,
    permission: 'manage-system-settings',
    component: 'system-settings'
  },
  {
    order: 30,
    title: t('Currency Settings'),
    href: '#currency-settings',
    icon: DollarSign,
    permission: 'manage-currency-settings',
    component: 'currency-settings'
  },
  {
    order: 40,
    title: t('Cookie Settings'),
    href: '#cookie-settings',
    icon: Cookie,
    permission: 'manage-cookie-settings',
    component: 'cookie-settings'
  },
  {
    order: 50,
    title: t('Pusher Settings'),
    href: '#pusher-settings',
    icon: Radio,
    permission: 'manage-pusher-settings',
    component: 'pusher-settings'
  },
  {
    order: 60,
    title: t('SEO Settings'),
    href: '#seo-settings',
    icon: Search,
    permission: 'manage-seo-settings',
    component: 'seo-settings'
  },
  {
    order: 70,
    title: t('Cache Settings'),
    href: '#cache-settings',
    icon: Trash2,
    permission: 'manage-cache-settings',
    component: 'cache-settings'
  },
  {
    order: 80,
    title: t('Storage Settings'),
    href: '#storage-settings',
    icon: HardDrive,
    permission: 'manage-storage-settings',
    component: 'storage-settings'
  },
  {
    order: 500,
    title: t('Email Settings'),
    href: '#email-settings',
    icon: Mail,
    permission: 'manage-email-settings',
    component: 'email-settings'
  },
  {
    order: 510,
    title: t('Email Notification Settings'),
    href: '#email-notification-settings',
    icon: Mail,
    permission: 'manage-email-notification-settings',
    component: 'email-notification-settings'
  },
  {
    order: 1000,
    title: t('Bank Transfer Settings'),
    href: '#bank-transfer-settings',
    icon: CreditCard,
    permission: 'manage-bank-transfer-settings',
    component: 'bank-transfer-settings'
  }
];
