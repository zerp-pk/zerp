import { useEffect } from 'react';
import { useBrand } from '@/contexts/brand-context';
import { usePage } from '@inertiajs/react';
import { getImagePath } from '@/utils/helpers';

export function useFavicon() {
  const { settings } = useBrand();
  const { props } = usePage();

  useEffect(() => {
    const favicon = settings.favicon;
    if (!favicon) return;

    const faviconUrl = getImagePath(favicon, props);

    // Remove existing favicon links
    const existingLinks = document.querySelectorAll('link[rel*="icon"]');
    existingLinks.forEach(link => link.remove());

    // Create comprehensive favicon links for all platforms
    const faviconLinks = [
      // Standard favicon
      { rel: 'icon', type: 'image/x-icon', href: faviconUrl },
      { rel: 'shortcut icon', type: 'image/x-icon', href: faviconUrl },
      
      // Apple Touch Icons
      { rel: 'apple-touch-icon', sizes: '57x57', href: faviconUrl },
      { rel: 'apple-touch-icon', sizes: '60x60', href: faviconUrl },
      { rel: 'apple-touch-icon', sizes: '72x72', href: faviconUrl },
      { rel: 'apple-touch-icon', sizes: '76x76', href: faviconUrl },
      { rel: 'apple-touch-icon', sizes: '114x114', href: faviconUrl },
      { rel: 'apple-touch-icon', sizes: '120x120', href: faviconUrl },
      { rel: 'apple-touch-icon', sizes: '144x144', href: faviconUrl },
      { rel: 'apple-touch-icon', sizes: '152x152', href: faviconUrl },
      { rel: 'apple-touch-icon', sizes: '180x180', href: faviconUrl },
      
      // Android Chrome Icons
      { rel: 'icon', type: 'image/png', sizes: '192x192', href: faviconUrl },
      { rel: 'icon', type: 'image/png', sizes: '32x32', href: faviconUrl },
      { rel: 'icon', type: 'image/png', sizes: '96x96', href: faviconUrl },
      { rel: 'icon', type: 'image/png', sizes: '16x16', href: faviconUrl },
      
      // Safari Pinned Tab
      { rel: 'mask-icon', href: faviconUrl, color: '#000000' }
    ];

    // Add all favicon links to document head
    faviconLinks.forEach(linkData => {
      const link = document.createElement('link');
      Object.entries(linkData).forEach(([key, value]) => {
        link.setAttribute(key, value);
      });
      document.head.appendChild(link);
    });

    // Add Microsoft Tile meta tags
    const existingMeta = document.querySelectorAll('meta[name*="msapplication"]');
    existingMeta.forEach(meta => meta.remove());

    const metaTags = [
      { name: 'msapplication-TileColor', content: '#ffffff' },
      { name: 'msapplication-TileImage', content: faviconUrl },
      { name: 'msapplication-config', content: '/browserconfig.xml' }
    ];

    metaTags.forEach(metaData => {
      const meta = document.createElement('meta');
      Object.entries(metaData).forEach(([key, value]) => {
        meta.setAttribute(key, value);
      });
      document.head.appendChild(meta);
    });

  }, [settings.favicon]);
}