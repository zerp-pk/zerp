import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'node:path';
import { glob } from 'glob';

const modulePackages = [
    ...glob.sync('packages/local/*/src/Resources/js/app.tsx'),
    ...glob.sync('vendor/zerp/*/src/Resources/js/app.tsx'),
];

export default defineConfig({
    base: './',
    plugins: [
        laravel({
            input:
            [
                'resources/css/app.css',
                'resources/js/app.tsx',
                ...modulePackages
            ],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: 'localhost',
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers': '*',
        },
        watch: {
            // Watch vendor/zerp (real source, symlinked in from ZerpPackages) for HMR,
            // but skip the rest of vendor/ (regular Composer dependencies) and node_modules.
            ignored: (path) => {
                if (path.includes('/vendor/zerp/')) return false;
                return path.includes('/vendor/') || path.includes('/node_modules/');
            }
        },
        fs: {
            allow: ['..', 'packages']
        }
    },

    esbuild: {
        jsx: 'automatic',
        jsxImportSource: 'react',
    },
    resolve: {
        alias: {
            'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    ui: ['@radix-ui/react-dialog', '@radix-ui/react-dropdown-menu'],
                    utils: ['date-fns', 'clsx']
                }
            },
        },
        assetsDir: 'assets',
    }
});
