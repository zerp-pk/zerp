import { Palette, Building,SettingsIcon, Mail, DollarSign, CreditCard, Blocks, LayoutList } from 'lucide-react';

export interface SettingMenuItem {
  order: number;
  title: string;
  href: string;
  icon: any;
  /** Empty means everyone: the section is a personal preference, not a privilege. */
  permission: string;
  component: string;
}

export const getCompanySettings = (t: (key: string) => string): SettingMenuItem[] => [
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
    title: t('Company Settings'),
    href: '#company-settings',
    icon: Building,
    permission: 'manage-company-settings',
    component: 'company-settings'
  },
  {
    order: 40,
    title: t('Currency Settings'),
    href: '#currency-settings',
    icon: DollarSign,
    permission: 'manage-currency-settings',
    component: 'currency-settings'
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
  },
  {
    order: 1100,
    title: t('Modules'),
    href: '#modules-settings',
    icon: Blocks,
    permission: 'manage-settings',
    component: 'modules-settings'
  },
  {
    // No permission: everyone arranges their own sidebar, including staff, who hold
    // no settings permission at all.
    order: 1200,
    title: t('Sidebar'),
    href: '#menu-settings',
    icon: LayoutList,
    permission: '',
    component: 'menu-settings'
  }
];
