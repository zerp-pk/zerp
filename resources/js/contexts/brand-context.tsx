import React, { createContext, useContext, ReactNode, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { getImagePath } from '@/utils/helpers';

interface BrandSettings {
  logo_dark?: string;
  logo_light?: string;
  favicon?: string;
  titleText?: string;
  footerText?: string;
  sidebarVariant?: string;
  sidebarStyle?: string;
  layoutDirection?: string;
  themeMode?: string;
  themeColor?: string;
  customColor?: string;
}

interface BrandContextType {
  settings: BrandSettings;
  getPreviewUrl: (path: string) => string;
  getPrimaryColor: () => string;
  getSidebarStyles: () => React.CSSProperties;
  getSidebarClasses: () => string;
  getCompleteSidebarProps: () => { style: React.CSSProperties; className: string };
}

const BrandContext = createContext<BrandContextType | undefined>(undefined);

export function BrandProvider({ children }: { children: ReactNode }) {
  const { adminAllSetting, companyAllSetting, auth } = usePage().props as any;
  const isSuperAdmin = auth?.user?.roles?.includes('superadmin');

  let globalSettings;
  if(isSuperAdmin != undefined) {
        globalSettings = isSuperAdmin ? adminAllSetting : companyAllSetting;
    } else {
        globalSettings = adminAllSetting ;
    }

  const settings: BrandSettings = {
    logo_dark: globalSettings?.logo_dark || '',
    logo_light: globalSettings?.logo_light || '',
    favicon: globalSettings?.favicon || '',
    titleText: globalSettings?.titleText || 'Zerp',
    footerText: globalSettings?.footerText || `© ${new Date().getFullYear()} Zerp. All rights reserved.`,
    sidebarVariant: globalSettings?.sidebarVariant || 'inset',
    sidebarStyle: globalSettings?.sidebarStyle || 'plain',
    layoutDirection: globalSettings?.layoutDirection || 'ltr',
    themeMode: globalSettings?.themeMode || 'light',
    themeColor: globalSettings?.themeColor || 'zawat',
    customColor: globalSettings?.customColor || '#DA8F29',
  };

  const getPreviewUrl = (path: string) => {
    return getImagePath(path);
  };

  const themeColors = {
    blue: '#3b82f6',
    zawat: '#DA8F29',
    purple: '#8b5cf6',
    orange: '#f97316',
    red: '#ef4444'
  };

  const getPrimaryColor = () => {
    return settings.themeColor === 'custom'
      ? settings.customColor || '#DA8F29'
      : themeColors[settings.themeColor as keyof typeof themeColors] || '#DA8F29';
  };

  useEffect(() => {
    const primaryColor = getPrimaryColor();
    const root = document.documentElement;

    // Convert hex to HSL for CSS custom properties
    const hexToHsl = (hex: string) => {
      const r = parseInt(hex.slice(1, 3), 16) / 255;
      const g = parseInt(hex.slice(3, 5), 16) / 255;
      const b = parseInt(hex.slice(5, 7), 16) / 255;

      const max = Math.max(r, g, b);
      const min = Math.min(r, g, b);
      let h = 0, s = 0, l = (max + min) / 2;

      if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
          case r: h = (g - b) / d + (g < b ? 6 : 0); break;
          case g: h = (b - r) / d + 2; break;
          case b: h = (r - g) / d + 4; break;
        }
        h /= 6;
      }

      return `${Math.round(h * 360)} ${Math.round(s * 100)}% ${Math.round(l * 100)}%`;
    };

    // Set CSS custom properties
    root.style.setProperty('--primary', hexToHsl(primaryColor));
    root.style.setProperty('--primary-foreground', '0 0% 98%');

    // Set global RTL direction
    const isRTL = settings.layoutDirection === 'rtl';

    root.dir = isRTL ? 'rtl' : 'ltr';
    root.style.direction = isRTL ? 'rtl' : 'ltr';
    document.body.dir = isRTL ? 'rtl' : 'ltr';
    document.body.style.direction = isRTL ? 'rtl' : 'ltr';

    // Add/remove RTL class from body
    if (isRTL) {
      document.body.classList.add('rtl');
      document.body.classList.remove('ltr');
    } else {
      document.body.classList.add('ltr');
      document.body.classList.remove('rtl');
    }

    // Set theme mode
    const themeMode = settings.themeMode;

    if (themeMode === 'light') {
      document.body.classList.remove('dark');
      document.body.classList.add('light');
    } else if (themeMode === 'dark') {
      document.body.classList.remove('light');
      document.body.classList.add('dark');
    } else {
      // system mode - let next-themes handle it
      document.body.classList.remove('light', 'dark');
    }

    // Override sidebar default styles with brand colors
    let existingStyle = document.getElementById('brand-sidebar-styles');
    if (!existingStyle) {
      existingStyle = document.createElement('style');
      existingStyle.id = 'brand-sidebar-styles';
      document.head.appendChild(existingStyle);
    }

    if (settings.sidebarStyle === 'colored' || settings.sidebarStyle === 'gradient') {
      const sidebarBg = settings.sidebarStyle === 'colored'
        ? primaryColor
        : `linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor}80 100%)`;

      existingStyle.textContent = `

        [data-sidebar] .bg-sidebar-primary {
          background: rgba(255,255,255,0.2);
        }
        [data-sidebar] [data-sidebar="menu-button"]:hover {
          background: rgba(255,255,255,0.1);
        }
        [data-sidebar] [data-sidebar="menu-button"][data-active="true"] {
          background: rgba(255,255,255,0.2);
        }
      `;
    } else {
      existingStyle.textContent = '';
    }

  }, [settings.themeColor, settings.customColor, settings.layoutDirection, settings.themeMode]);

  const getSidebarStyles = (): React.CSSProperties => {
    const primaryColor = getPrimaryColor();

    if (settings.sidebarStyle === 'colored') {
      return { backgroundColor: primaryColor };
    } else if (settings.sidebarStyle === 'gradient') {
      return {
        background: `linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor}80 100%)`
      };
    }
    return {};
  };

  const getSidebarClasses = () => {
    let classes = '';

    if (settings.sidebarVariant === 'floating') {
      classes += ' m-2 rounded-lg shadow-sm';
    }

    return classes;
  };

  const getCompleteSidebarProps = () => {
    const styles = getSidebarStyles();
    const classes = getSidebarClasses();
    const hasCustomBackground = styles.backgroundColor || styles.background;

    return {
      style: {
        ...styles,
        ...(hasCustomBackground && {
          backgroundColor: styles.backgroundColor || 'transparent',
          background: styles.background || styles.backgroundColor || 'transparent'
        })
      },
      className: `${classes}`
    };
  };

  return (
    <BrandContext.Provider value={{
      settings,
      getPreviewUrl,
      getPrimaryColor,
      getSidebarStyles,
      getSidebarClasses,
      getCompleteSidebarProps
    }}>
      {children}
    </BrandContext.Provider>
  );
}

export function useBrand() {
  const context = useContext(BrandContext);
  if (context === undefined) {
    throw new Error('useBrand must be used within a BrandProvider');
  }
  return context;
}