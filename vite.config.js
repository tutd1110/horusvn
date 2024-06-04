import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers'
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/assets/sass/app.scss", "resources/js/app.js"],
            refresh: true,
        }),
        vue(),
        AutoImport({
            resolvers: [ElementPlusResolver()],
          }),
        Components({
            resolvers: [ElementPlusResolver()],
        }),
    ],
    server: {
        host: "127.0.0.1",
        watch: {
            usePolling: true,
            interval: 1000,
        },
    },
    resolve: {
        alias: [
            {
                find: 'vue-i18n',
                replacement: 'vue-i18n/dist/vue-i18n.cjs.js',
            }
        ]
    },
    optimizeDeps: {
        include: ['vue-advanced-cropper']
    }
});
