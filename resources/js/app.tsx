import "./bootstrap";
import "../css/app.css";
import "../css/rtl.css";
import "./i18n";

import { createRoot } from "react-dom/client";
import { createInertiaApp, router } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ThemeProvider } from "@/components/theme-provider";
import { Toaster } from "sonner";
import { Suspense } from "react";
import axios from "axios";
import { findPackageModule } from "@/utils/helpers";


// Silent CSRF token refresh
const refreshToken = async () => {
    try {
        const response = await fetch(window.location.href, { method: 'GET' });
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newToken = doc.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (newToken) {
            document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', newToken);
            axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
        }
    } catch (e) {}
};

router.on('before', (event) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        refreshToken();
    }
});

router.on('error', async (event) => {
    const errors = event.detail.errors;
    if (errors && (errors[419] || errors['419'] || Object.values(errors).some(e => String(e).includes('419')))) {
        await refreshToken();
    }
});

// Global fetch interceptor
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    const [url, options] = args;
    
    // Ensure fresh token before request
    if (options && options.method && options.method !== 'GET') {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            await refreshToken();
        }
        // Update token in headers
        const newToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (newToken && options.headers) {
            (options.headers as any)['X-CSRF-TOKEN'] = newToken;
        }
    }
    
    const response = await originalFetch(...args);
    
    // Fallback: retry on 419 error
    if (response.status === 419) {
        await refreshToken();
        if (options && options.headers) {
            const newToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (newToken) {
                (options.headers as any)['X-CSRF-TOKEN'] = newToken;
            }
        }
        return originalFetch(...args);
    }
    return response;
};

createInertiaApp({
    title: (title) => {
        const initialPage = JSON.parse(
            document.getElementById("app")?.dataset.page || "{}"
        );
        const pageProps = initialPage?.props ?? {};
        let customTitle;
        if (pageProps?.auth?.user?.type === "superadmin") {
            customTitle = pageProps?.adminAllSetting?.titleText;
        } else if (pageProps?.auth?.user?.type) {
            customTitle = pageProps?.companyAllSetting?.titleText;
        } else {
            customTitle = pageProps?.adminAllSetting?.titleText;
        }
        const appName = customTitle || import.meta.env.VITE_APP_NAME || "Laravel";
        return `${title} - ${appName}`;
    },
    resolve: (name) => {
        const allPages = {
            ...import.meta.glob('./pages/**/*.tsx'),
            ...import.meta.glob([
                '../../packages/local/*/src/Resources/js/Pages/**/*.tsx',
                '../../vendor/zerp/*/src/Resources/js/Pages/**/*.tsx',
            ])
        };

        // Try pages directory (lowercase p)
        const lowerPagePath = `./pages/${name}.tsx`;
        if (allPages[lowerPagePath]) {
            return allPages[lowerPagePath]();
        }

        // Try package pages
        const [packageName, ...pagePath] = name.split('/');
        const packagePage = findPackageModule(allPages, packageName, `/src/Resources/js/Pages/${pagePath.join('/')}.tsx`);
        if (packagePage) {
            return (packagePage as () => any)();
        }

        throw new Error(`Page not found: ${name}`);
    },
    setup({ el, App, props }) {
        // Make props globally available
        (window as any).page = props;
        const root = createRoot(el);

        root.render(
            <ThemeProvider
                attribute="class"
                defaultTheme="light"
                enableSystem
                disableTransitionOnChange
            >
                <Suspense fallback={null}>
                    <App {...props} />
                </Suspense>
                <Toaster position="top-center" richColors />
            </ThemeProvider>
        );
    },
    progress: {
        color: "#4B5563",
    },
});
