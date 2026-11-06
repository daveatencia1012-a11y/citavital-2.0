import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/componentes.css',
                'resources/css/inicio.css',
                'resources/css/quienes-somos.css',
                'resources/css/acceso.css',
                'resources/css/login.css',
                'resources/css/legal.css',
                'resources/css/administrativo.css',
                'resources/css/paciente.css',
                'resources/js/app.js',
                'resources/js/inicio.js',
                'resources/js/acceso.js',
                'resources/js/login.js',
                'resources/js/administrativo.js',
                'resources/js/paciente.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600, 700, 800],
                }),
            ],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
