import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    resolve: {
        // Keeps imports inside the package resolving against this app's node_modules
        // when the package is symlinked from a local checkout (a Composer path repository).
        preserveSymlinks: true,
        alias: {
            // Shared scripts and styles ship in the foundation package; nothing is copied here.
            '@foundation': fileURLToPath(new URL('./vendor/mrjthedifferent/laravel-foundation/ui/resources', import.meta.url)),
        },
    },
});
