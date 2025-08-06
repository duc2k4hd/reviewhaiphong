import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Optimize chunk splitting
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['axios'],
                }
            }
        },
        // Enable minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        // Source maps for debugging (disable in production)
        sourcemap: process.env.NODE_ENV !== 'production',
        // Chunk size warning limit
        chunkSizeWarningLimit: 1000,
    },
    server: {
        // HMR optimization
        hmr: {
            host: 'localhost',
        },
        // Watch options
        watch: {
            usePolling: false,
        },
    },
    // CSS optimization
    css: {
        devSourcemap: true,
    },
    // Define global constants
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
    },
});
