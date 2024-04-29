import {defineConfig} from "vite";
import {resolve} from "path";

const inputs = {
    "layout": resolve(__dirname, "./frontend/layout.js"),
    "wwradio": resolve(__dirname, "./frontend/wwradio.js")
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
    resolve: {
        alias: {
            "!": resolve(__dirname),
            "~": resolve(__dirname, "./frontend"),
        },
        extensions: [".mjs", ".js", ".mts", ".ts", ".jsx", ".tsx", ".json", ".vue"],
    },
});
