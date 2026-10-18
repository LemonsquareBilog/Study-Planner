import { defineConfig, loadEnv } from 'vite';
import { validateMobileApiUrl } from './scripts/mobile-config.mjs';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import fs from 'fs';

export default defineConfig(({ mode }) => {
    const isWebBuild = mode === 'web' || mode === 'development';
    if (!isWebBuild && mode !== 'preview') {
        const env = { ...loadEnv(mode, process.cwd(), 'VITE_'), ...process.env };
        validateMobileApiUrl(env.VITE_API_BASE_URL, mode === 'mobile-dev');
    }
    const publicDirectory = isWebBuild ? 'public' : 'www';
    const buildDirectory = isWebBuild ? 'build' : 'assets';
    const outputDirectory = isWebBuild ? 'public/build' : 'www';

    return {
        root: __dirname,
        base: isWebBuild ? '/build/' : './',
        plugins: [
            {
                name: 'generate-index-html',
                apply: 'build',
                enforce: 'post',
                writeBundle(outputOptions) {
                    if (isWebBuild) return;

                    const wwwDir = path.resolve(outputOptions.dir || 'www');
                    const manifestPath = path.join(wwwDir, '.vite', 'manifest.json');
                    if (!fs.existsSync(manifestPath)) {
                        throw new Error('Mobile build manifest is missing.');
                    }
                    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
                    const jsFile = manifest['resources/js/app.ts']?.file;
                    const cssFile = manifest['resources/css/app.css']?.file;
                    if (!jsFile || !cssFile) {
                        throw new Error('Mobile build is missing its JavaScript or stylesheet entry.');
                    }
                    const cssFiles = new Set([cssFile, ...(manifest['resources/js/app.ts'].css || [])]);

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
    ${[...cssFiles].map(file => `<link rel="stylesheet" href="${file}">`).join('\n    ')}
</head>
<body>
    <div id="app"></div>
    <script type="module" src="${jsFile}"></script>
</body>
</html>`;
                    fs.writeFileSync(path.join(wwwDir, 'index.html'), html);
                    // Also copy manifest.json to www root for Capacitor
                    fs.copyFileSync(manifestPath, path.join(wwwDir, 'manifest.json'));
                }
            },
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
                refresh: false,
                publicDirectory,
                buildDirectory,
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
        ],
        resolve: {
            alias: {
                '@': path.resolve(__dirname, './resources/js'),
            },
        },
        optimizeDeps: {
            entries: ['resources/js/app.ts'],
        },
        build: {
            outDir: outputDirectory,
            assetsDir: 'assets',
            emptyOutDir: true,
            manifest: isWebBuild ? 'manifest.json' : true,
        },
        server: {
            host: '127.0.0.1',
            watch: {
                ignored: ['**/storage/**', '**/vendor/**', '**/.verification/**', '**/agentic-awesome-skills/**'],
            },
            proxy: {
                '/api': {
                    target: 'http://127.0.0.1:8000',
                    changeOrigin: true,
                    configure(proxy) {
                        proxy.on('proxyReq', (request, incoming) => {
                            // Preserve the backend's same-origin CSRF check for
                            // the two explicit loopback Vite origins only.
                            if (['http://localhost:5173', 'http://127.0.0.1:5173'].includes(incoming.headers.origin)) {
                                request.setHeader('Origin', 'http://127.0.0.1:8000');
                            }
                        });
                    },
                },
                '/sanctum': {
                    target: 'http://127.0.0.1:8000',
                    changeOrigin: true,
                },
            },
        },
    };
});
