import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

/**
 * Resolve the host browsers should use to load assets from the Vite dev server.
 *
 * It mirrors APP_URL so the dev server stays reachable from other machines on
 * the same LAN. Set VITE_DEV_SERVER_HOST to override it explicitly.
 */
function resolveDevServerHost(env) {
    if (env.VITE_DEV_SERVER_HOST) {
        return env.VITE_DEV_SERVER_HOST;
    }

    try {
        return new URL(env.APP_URL).hostname;
    } catch {
        return 'localhost';
    }
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/dashboard/theme.css'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            tailwindcss(),
        ],
        server: {
            host: '0.0.0.0',
            hmr: {
                host: resolveDevServerHost(env),
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
