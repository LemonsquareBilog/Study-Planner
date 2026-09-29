import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import fs from 'fs';

export default defineConfig({
    root: __dirname,
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            publicDirectory: 'www',
            buildDirectory: 'assets',
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
        {
            name: 'generate-index-html',
            apply: 'build',
            closeBundle() {
                const wwwDir = path.resolve(__dirname, 'www');
                const assetsDir = path.join(wwwDir, 'assets');
                const files = fs.readdirSync(assetsDir);
                const jsFile = files.find(f => f.startsWith('app-') && f.endsWith('.js'));
                const cssFile = files.find(f => f.startsWith('app-') && f.endsWith('.css'));
                
                const html = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FFC0CB">
    <title>Study Planner</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <link rel="manifest" href="manifest.json">
    ${cssFile ? `<link rel="stylesheet" href="assets/${cssFile}">` : ''}
</head>
<body>
    <div id="app"></div>
    ${jsFile ? `<script type="module" src="assets/${jsFile}"></script>` : ''}
</body>
</html>`;
                
                fs.writeFileSync(path.join(wwwDir, 'index.html'), html);
            }
        }
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    build: {
        outDir: 'www',
        assetsDir: 'assets',
        emptyOutDir: true,
        base: './',
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
            '/sanctum': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
        },
    },
});