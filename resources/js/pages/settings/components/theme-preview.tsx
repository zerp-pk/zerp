import React from 'react';
import { useTranslation } from 'react-i18next';
import { getImagePath } from '@/utils/helpers';

interface ThemePreviewProps {
  logoDark?: string;
  logoLight?: string;
  themeColor?: string;
  customColor?: string;
  sidebarVariant?: string;
  sidebarStyle?: string;
  layoutDirection?: string;
  themeMode?: string;
}

export function ThemePreview({ 
  logoDark, 
  logoLight, 
  themeColor = 'zawat',
  customColor = '#DA8F29',
  sidebarVariant = 'inset',
  sidebarStyle = 'plain',
  layoutDirection = 'ltr',
  themeMode = 'light'
}: ThemePreviewProps) {
  const { t } = useTranslation();

  const getDisplayUrl = (path: string): string => {
    if (!path) return path;
    return getImagePath(path);
  };

  const themeColors = {
    blue: '#3b82f6',
    zawat: '#DA8F29',
    purple: '#8b5cf6',
    orange: '#f97316',
    red: '#ef4444'
  };

  const primaryColor = themeColor === 'custom' ? customColor : themeColors[themeColor as keyof typeof themeColors] || '#DA8F29';
  
  const isDark = themeMode === 'dark';
  const isRTL = layoutDirection === 'rtl';
  
  const getSidebarStyles = () => {
    let baseClasses = 'w-16 border-r flex flex-col py-3 px-2 gap-2';
    
    if (sidebarStyle === 'colored') {
      baseClasses += ' text-white';
    } else if (sidebarStyle === 'gradient') {
      baseClasses += ' text-white';
    }
    
    return baseClasses;
  };
  
  const getSidebarBackground = () => {
    if (sidebarStyle === 'colored') {
      return { backgroundColor: primaryColor };
    } else if (sidebarStyle === 'gradient') {
      return { 
        background: `linear-gradient(135deg, ${primaryColor} 0%, ${primaryColor}80 100%)` 
      };
    }
    return {};
  };

  const currentLogo = isDark ? logoLight : logoDark;

  return (
    <div 
      className={`border rounded-lg overflow-hidden text-xs transition-all duration-300 ${
        isDark ? 'bg-gray-900 text-white border-gray-700' : 'bg-background text-foreground'
      } ${isRTL ? 'rtl' : 'ltr'}`}
      style={{
        '--primary-color': primaryColor
      } as React.CSSProperties}
    >
      {/* Top Bar */}
      <div className={`px-3 py-2 border-b flex items-center justify-between ${
        isDark ? 'bg-gray-800 border-gray-700' : 'bg-muted'
      }`}>
        <div className="flex items-center gap-2 order-1 rtl:order-2">
          <span className="font-medium">{t('Dashboard')}</span>
        </div>
        <div className="flex items-center gap-1 order-2 rtl:order-1">
          <div className={`w-4 h-4 rounded ${
            isDark ? 'bg-gray-600' : 'bg-muted-foreground/20'
          }`}></div>
          <div className={`w-4 h-4 rounded ${
            isDark ? 'bg-gray-600' : 'bg-muted-foreground/20'
          }`}></div>
        </div>
      </div>

      {/* Main Layout */}
      <div className={`flex h-48 ${isRTL ? 'flex-row-reverse' : ''}`}>
        {/* Sidebar */}
        <div 
          className={`order-1 rtl:order-2 ${getSidebarStyles()} ${
            sidebarVariant === 'floating' ? 'm-2 rounded-lg shadow-sm' : ''
          } ${
            sidebarVariant === 'minimal' ? 'w-12' : 'w-16'
          } ${
            isDark && sidebarStyle === 'plain' ? 'bg-gray-800 border-gray-700' : 
            !isDark && sidebarStyle === 'plain' ? 'bg-muted/50' : ''
          }`}
          style={getSidebarBackground()}
        >
          {/* Logo */}
          <div className="flex justify-center mb-2">
            {currentLogo ? (
              <img
                src={getDisplayUrl(currentLogo)}
                alt="Logo"
                className="w-8 h-4 object-contain"
              />
            ) : (
              <div 
                className="w-8 h-2 rounded"
                style={{ backgroundColor: primaryColor }}
              ></div>
            )}
          </div>
          
          {/* Menu Items */}
          <div className="space-y-2">
            <div 
              className="w-full h-2 rounded"
              style={{ backgroundColor: sidebarStyle === 'plain' ? primaryColor : 'rgba(255,255,255,0.8)' }}
            ></div>
            {Array.from({ length: 5 }).map((_, i) => (
              <div 
                key={i}
                className={`w-full h-2 rounded ${
                  sidebarStyle === 'plain' 
                    ? (isDark ? 'bg-gray-600' : 'bg-muted-foreground/30')
                    : 'bg-white/30'
                }`}
              ></div>
            ))}
          </div>
        </div>

        {/* Content Area */}
        <div className="flex-1 p-3 space-y-2 order-2 rtl:order-1">
          <div className={`h-2 rounded w-3/4 ${
            isDark ? 'bg-gray-700' : 'bg-muted'
          }`}></div>
          <div className={`h-2 rounded w-1/2 ${
            isDark ? 'bg-gray-700' : 'bg-muted'
          }`}></div>
          <div className={`h-2 rounded w-2/3 ${
            isDark ? 'bg-gray-700' : 'bg-muted'
          }`}></div>
          <div className="flex gap-2 mt-3">
            <div 
              className="w-6 h-4 rounded"
              style={{ backgroundColor: `${primaryColor}33` }}
            ></div>
            <div className={`w-6 h-4 rounded ${
              isDark ? 'bg-gray-700' : 'bg-muted'
            }`}></div>
            <div className={`w-6 h-4 rounded ${
              isDark ? 'bg-gray-700' : 'bg-muted'
            }`}></div>
          </div>
          <div className="space-y-1 mt-4">
            <div className={`h-1.5 rounded w-full ${
              isDark ? 'bg-gray-700' : 'bg-muted'
            }`}></div>
            <div className={`h-1.5 rounded w-4/5 ${
              isDark ? 'bg-gray-700' : 'bg-muted'
            }`}></div>
            <div className={`h-1.5 rounded w-3/5 ${
              isDark ? 'bg-gray-700' : 'bg-muted'
            }`}></div>
          </div>
        </div>
      </div>
    </div>
  );
}