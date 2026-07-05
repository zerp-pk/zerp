import { usePage } from '@inertiajs/react';

// Add window type declaration
declare global {
  interface Window {
    location: Location;
  }
}

/**
 * Get company setting value
 */
const getCompanySetting = (key: string, pageProps?: any) => {
  try {
    // If pageProps is provided, use it; otherwise get from usePage
    let companySettings;
    if (pageProps?.companyAllSetting) {
      companySettings = pageProps.companyAllSetting;
    } else {
      const { props } = usePage();
      companySettings = (props as any).companyAllSetting || {};
    }

    return companySettings[key];
  } catch {
    return null;
  }
};

/**
 * Get admin setting value
 */
const getAdminSetting = (key: string, pageProps?: any) => {
  try {
    // If pageProps is provided, use it; otherwise get from usePage
    let adminSettings;
    if (pageProps?.adminAllSetting) {
      adminSettings = pageProps.adminAllSetting;
    } else {
      const { props } = usePage();
      adminSettings = (props as any).adminAllSetting || {};
    }

    return adminSettings[key];
  } catch {
    return null;
  }
};

/**
 * Format date to readable format
 */
const formatDate = (date: string | Date, pageProps?: any): string => {
  if (!date) return '';
  const format = getCompanySetting('dateFormat', pageProps) || 'Y-m-d';
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');

  return format
    .replace('Y', String(year))
    .replace('m', month)
    .replace('d', day);
};

/**
 * Format time to readable format
 */
const formatTime = (time: string, pageProps?: any): string => {
  if (!time) return '';
  const timeFormat = getCompanySetting('timeFormat', pageProps) || 'H:i';
  const [hours, minutes] = time.split(':');
  const h = parseInt(hours);
  const m = String(parseInt(minutes)).padStart(2, '0');

  if (timeFormat === 'g:i A') {
    const period = h >= 12 ? 'PM' : 'AM';
    const displayHour = h === 0 ? 12 : h > 12 ? h - 12 : h;
    return `${displayHour}:${m} ${period}`;
  }

  return timeFormat
    .replace('H', String(h).padStart(2, '0'))
    .replace('i', m);
};

/**
 * Format date and time to readable format
 */
const formatDateTime = (date: string | Date, pageProps?: any): string => {
  if (!date) return '';
  const dateFormat = getCompanySetting('dateFormat', pageProps) || 'Y-m-d';
  const timeFormat = getCompanySetting('timeFormat', pageProps) || 'H:i';

  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const h = d.getHours();
  const m = String(d.getMinutes()).padStart(2, '0');

  const formattedDate = dateFormat
    .replace('Y', String(year))
    .replace('m', month)
    .replace('d', day);

  let formattedTime;
  if (timeFormat === 'g:i A') {
    const period = h >= 12 ? 'PM' : 'AM';
    const displayHour = h === 0 ? 12 : h > 12 ? h - 12 : h;
    formattedTime = `${displayHour}:${m} ${period}`;
  } else {
    formattedTime = timeFormat
      .replace('H', String(h).padStart(2, '0'))
      .replace('i', m);
  }

  return `${formattedDate} ${formattedTime}`;
};

/**
 * Get full image path
 */
const getImagePath = (path: string, pageProps?: any): string => {
  if (!path || typeof path !== 'string') return '';
  if (path.startsWith('http')) return path;

  // Handle module package paths (legacy packages/local or vendor/zerp) or storage/media paths - use baseUrl directly
  if (path.includes('packages/local') || path.includes('vendor/zerp') || path.includes('storage/media')) {
    let baseUrl;
    if (pageProps?.baseUrl) {
      baseUrl = pageProps.baseUrl;
    } else {
      const { props } = usePage();
      baseUrl = (props as any).baseUrl || window.location.origin;
    }
    const cleanPath = path.startsWith('/') ? path : '/' + path;
    return `${baseUrl}${cleanPath}`;
  }

  try {
    let imageUrlPrefix;
    if (pageProps?.imageUrlPrefix) {
      imageUrlPrefix = pageProps.imageUrlPrefix;
    } else {
      const { props } = usePage();
      imageUrlPrefix = (props as any).imageUrlPrefix || '';
    }

    if (!imageUrlPrefix) return '';

    const prefixEndsWithSlash = imageUrlPrefix.endsWith('/');
    const pathStartsWithSlash = path.startsWith('/');

    if (prefixEndsWithSlash && pathStartsWithSlash) {
      return imageUrlPrefix + path.substring(1);
    } else if (!prefixEndsWithSlash && !pathStartsWithSlash) {
      return imageUrlPrefix + '/' + path;
    } else {
      return imageUrlPrefix + path;
    }
  } catch {
    return '';
  }
};

/**
 * Format currency based on saved settings
 */
const formatCurrency = (amount: number | string, pageProps?: any): string => {
  try {
    const num = Number(amount) || 0;
    const decimalPlaces = parseInt(getCompanySetting('decimalFormat', pageProps) || '2');
    const decimalSeparator = getCompanySetting('decimalSeparator', pageProps) || '.';
    const thousandsSeparator = getCompanySetting('thousandsSeparator', pageProps) || ',';
    const floatNumber = getCompanySetting('floatNumber', pageProps) !== '0';
    const currencySymbolSpace = getCompanySetting('currencySymbolSpace', pageProps) === '1';
    const currencySymbolPosition = getCompanySetting('currencySymbolPosition', pageProps) || 'before';

    let finalAmount = floatNumber ? num : Math.floor(num);
    const parts = Number(finalAmount).toFixed(decimalPlaces).split('.');

    if (thousandsSeparator !== 'none') {
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
    }

    const formattedNumber = parts.join(decimalSeparator);
    const symbol = getCurrencySymbol(pageProps);
    const space = currencySymbolSpace ? ' ' : '';

    return currencySymbolPosition === 'before'
      ? `${symbol}${space}${formattedNumber}`
      : `${formattedNumber}${space}${symbol}`;
  } catch {
    return `$${Number(amount).toFixed(2)}`;
  }
};

const formatAdminCurrency = (amount: number | string, pageProps?: any): string => {
  try {
    const num = Number(amount) || 0;
    const decimalPlaces = parseInt(getAdminSetting('decimalFormat', pageProps) || '2');
    const decimalSeparator = getAdminSetting('decimalSeparator', pageProps) || '.';
    const thousandsSeparator = getAdminSetting('thousandsSeparator', pageProps) || ',';
    const floatNumber = getAdminSetting('floatNumber', pageProps) !== '0';
    const currencySymbolSpace = getAdminSetting('currencySymbolSpace', pageProps) === '1';
    const currencySymbolPosition = getAdminSetting('currencySymbolPosition', pageProps) || 'before';

    let finalAmount = floatNumber ? num : Math.floor(num);
    const parts = Number(finalAmount).toFixed(decimalPlaces).split('.');

    if (thousandsSeparator !== 'none') {
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
    }

    const formattedNumber = parts.join(decimalSeparator);
    const symbol = getAdminSetting('currencySymbol', pageProps) || '$';
    const space = currencySymbolSpace ? ' ' : '';

    return currencySymbolPosition === 'before'
      ? `${symbol}${space}${formattedNumber}`
      : `${formattedNumber}${space}${symbol}`;
  } catch {
    return `$${Number(amount).toFixed(2)}`;
  }
};

/**
 * Get currency symbol from settings
 */
const getCurrencySymbol = (pageProps?: any): string => {
  try {
    return getCompanySetting('currencySymbol', pageProps) || '$';
  } catch {
    return '$';
  }
};

const getAdminCurrencySymbol = (pageProps?: any): string => {
  try {
    return getAdminSetting('currencySymbol', pageProps) || '$';
  } catch {
    return '$';
  }
};

/**
 * Check if a package is active
 */
const isPackageActive = (packageName: string, pageProps?: any): boolean => {
  try {
    let activatedPackages;
    if (pageProps?.auth?.user?.activatedPackages) {
      activatedPackages = pageProps.auth.user.activatedPackages;
    } else {
      const { props } = usePage();
      activatedPackages = (props as any).auth?.user?.activatedPackages || [];
    }
    return activatedPackages.includes(packageName);
  } catch {
    return false;
  }
};
const formatStorage = (kb: number) => {
    if (kb >= 1024 * 1024) {
      return `${(kb / (1024 * 1024)).toFixed(1)} GB`;
    } else if (kb >= 1024) {
      return `${(kb / 1024).toFixed(1)} MB`;
    } else {
      return `${kb} GB`;
    }
  };


/**
 * Get package favicon by package name
 */
const getPackageFavicon = (packageName: string, pageProps?: any): string | undefined => {
    try {
      let packages;

      if (pageProps?.packages) {
        packages = pageProps.packages;
      } else {
        const { props } = usePage();
        packages = (props as any).packages || [];
      }

      const packageData = packages.find((pkg: any) => pkg.name === packageName);
      return packageData?.image || undefined;
    } catch {
      return undefined;
    }
  };

/**
 * Get package alias name by package name
 */
const getPackageAlias = (packageName: string, pageProps?: any): string | undefined => {
    try {
      let packages;
      if (pageProps?.packages) {
        packages = pageProps.packages;
      } else {
        // Only call usePage if pageProps not provided
        const { props } = usePage();
        packages = (props as any).packages || [];
      }

      const packageData = packages.find((pkg: any) => pkg.name === packageName);
      return packageData?.alias || packageName;
    } catch {
      return packageName;
    }
  };

/**
 * Get enabled packages names
 */
const adminPackages = (pageProps?: any): string[] => {
    try {
      let packages;
      if (pageProps?.packages) {
        packages = pageProps.packages;
      } else {
        const { props } = usePage();
        packages = (props as any).packages || [];
      }

      return packages.filter((pkg: any) => pkg.is_enable === true).map((pkg: any) => pkg.name);
    } catch {
      return [];
    }
  };

/**
 * Format file size in bytes to human readable format
 */
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/** Convert file to base64 string
 */
const convertFileToBase64 = (file: File): Promise<string> => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result as string);
    reader.onerror = error => reject(error);
  });
};

/**
 * Extract file extension from base64 string
 */
const getBase64FileExtension = (base64String: string): string => {
  const mimeExtensions: Record<string, string> = {
    'image/jpeg': 'jpg',
    'image/jpg': 'jpg',
    'image/png': 'png',
    'image/gif': 'gif',
    'image/svg+xml': 'svg',
    'application/pdf': 'pdf',
    'application/msword': 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'docx',
    'application/vnd.ms-excel': 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'xlsx',
    'text/plain': 'txt'
  };

  const match = base64String.match(/data:([a-zA-Z0-9]+\/[a-zA-Z0-9-.+]+).*,.*/);
  if (match) {
    const mimeType = match[1];
    return mimeExtensions[mimeType] || mimeType.split('/')[1] || 'png';
  }
  return 'png';
};


/**
 * Download any file (PDF, image, ZIP, etc.) from a URL.
 * Automatically extracts filename from Content-Disposition header.
 */

const downloadFile = (url: string): void => {
    // Create a temporary link
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';  // opens in new tab
    link.rel = 'noopener noreferrer';

    // Optional: if it’s a direct downloadable file, force download
    if (url.match(/\.(pdf|jpg|png|jpeg|docx|zip)$/i)) {
      link.download = '';
    }

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

/**
 * Get subscription details for a user (similar to Laravel SubscriptionDetails function)
 */
interface SubscriptionDetail {
  status: boolean;
  active_plan?: number;
  billing_type?: string;
  plan_expire_date?: string;
  total_user?: string | number;
  total_storage?: string;
  seeder_run?: boolean;
}

/**
 * Modules live either under packages/local/<PascalCaseName> (legacy, in-repo)
 * or vendor/zerp/<composer-slug> (installed as a real Composer package, where
 * Composer lowercases/kebab-cases the directory name). Compare on a normalized
 * form so both locations resolve to the same module.
 */
const normalizePackageSegment = (s: string): string => s.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();

/**
 * Does a glob-imported module path belong to the given (PascalCase) package name,
 * regardless of whether it was resolved from packages/local or vendor/zerp?
 */
const matchesPackageSegment = (path: string, packageName: string): boolean => {
  const match = path.match(/\/(?:packages\/local|vendor\/zerp)\/([^/]+)\//);
  return !!match && normalizePackageSegment(match[1]) === normalizePackageSegment(packageName);
};

/**
 * Find the glob-imported module for a package by matching the path segment
 * right before a fixed suffix (e.g. '/src/Resources/js/menus/superadmin-menu.ts'),
 * instead of constructing one exact expected key (which breaks once the module
 * moves from packages/local/<PascalCaseName> to vendor/zerp/<composer-slug>).
 */
const findPackageModule = <T = any>(modules: Record<string, T>, packageName: string, suffix: string): T | undefined => {
  for (const key of Object.keys(modules)) {
    if (!key.endsWith(suffix)) continue;
    const segment = key.slice(0, -suffix.length).split('/').pop() ?? '';
    if (normalizePackageSegment(segment) === normalizePackageSegment(packageName)) {
      return modules[key];
    }
  }
  return undefined;
};

const getSubscriptionDetails = (userId?: number, pageProps?: any): SubscriptionDetail => {
  const data: SubscriptionDetail = {
    status: false
  };

  try {
    let user;
    if (pageProps?.auth?.user) {
      user = pageProps.auth.user;
    } else {
      const { props } = usePage();
      user = (props as any).auth?.user;
    }

    if (!user) {
      return data;
    }
   

    if (user.active_plan && user.active_plan !== 0) {
      data.status = true;
      data.active_plan = user.active_plan;
      data.billing_type = user.billing_type || 'monthly';
      data.plan_expire_date = user.plan_expire_date;
      data.total_user = user.total_user === -1 ? 'Unlimited' : (user.total_user === 0 ? '0' : (user.total_user || 'Unlimited'));
      data.total_storage = user.storage_limit ? formatStorage(user.storage_limit) : '0';
      data.seeder_run = user.seeder_run;
    }

    return data;
  } catch {
    return data;
  }
};

export {
    formatDate,
    formatTime,
    formatDateTime,
    getImagePath,
    matchesPackageSegment,
    findPackageModule,
    formatCurrency,
    formatAdminCurrency,
    getCurrencySymbol,
    getAdminCurrencySymbol,
    isPackageActive,
    getCompanySetting,
    getAdminSetting,
    formatStorage,
    formatFileSize,
    getPackageFavicon,
    getPackageAlias,
    adminPackages,
    convertFileToBase64,
    getBase64FileExtension,
    downloadFile,
    getSubscriptionDetails
};