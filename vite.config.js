import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                //app
                'resources/scss/app.scss',
                'resources/js/app.js',
                'resources/js/pages/userdashboard.js',
                'resources/js/pages/appcustomize.js',
                'resources/js/pages/appgallery.js',
                'resources/js/pages/appsavedmodel.js',
                'resources/js/pages/downloadcustomized.js',
                'resources/js/pages/model-parser.js',
                
                //admin
                'resources/scss/admin.scss',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Menghilangkan peringatan dari folder node_modules
                quietDeps: true,
                // Mengabaikan tipe peringatan tertentu yang paling sering muncul
                silenceDeprecations: ['slash-div', 'import', 'global-builtin'],
            },
        },
    },
});
