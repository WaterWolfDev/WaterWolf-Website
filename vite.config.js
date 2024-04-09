import {defineConfig} from "vite";
import {resolve} from "path";
import vue from '@vitejs/plugin-vue';

const inputs = {
    "layout": resolve(__dirname, "./frontend/layout.js"),
    "wwradio": resolve(__dirname, "./frontend/wwradio.js"),
    "vue_comments": resolve(__dirname, "./frontend/vue/comments.js")
};

console.log(inputs);

// https://vitejs.dev/config/
export default defineConfig({
    base: "/static/dist",
    build: {
        rollupOptions: {
            input: inputs
        },
        manifest: true,
        emptyOutDir: true,
        chunkSizeWarningLimit: "1m",
        outDir: resolve(__dirname, "./web/static/dist"),
    },
    server: {
        strictPort: true,
        host: true,
        fs: {
            allow: ["."],
        },
    },
    plugins: [
        vue(),
    ],
    resolve: {
        alias: {
            "!": resolve(__dirname),
            "~": resolve(__dirname, "./frontend"),
        },
        extensions: [".mjs", ".js", ".mts", ".ts", ".jsx", ".tsx", ".json", ".vue"],
    },
});
