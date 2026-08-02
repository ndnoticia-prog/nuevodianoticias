import { defineConfig } from 'vite';
import { resolve } from 'node:path';

export default defineConfig({
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: false,
    cssCodeSplit: false,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/js/app.js'),
      },
      output: {
        entryFileNames: 'app.js',
        chunkFileNames: 'app-[name].js',
        assetFileNames: (assetInfo) =>
          assetInfo.name && assetInfo.name.endsWith('.css') ? 'app.css' : 'assets/[name][extname]',
      },
    },
  },
});
