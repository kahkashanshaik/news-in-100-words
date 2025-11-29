import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// Get port from environment variable or use default
const PORT = parseInt(process.env.VITE_PORT || process.env.PORT || '3000', 10);

export default defineConfig({
  plugins: [react()],
  root: path.resolve(__dirname),
  base: '/wp-content/plugins/ai-blog-summary/',
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        admin: path.resolve(__dirname, 'assets/admin/js/index.js'),
        frontend: path.resolve(__dirname, 'assets/frontend/js/index.js'),
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith('.css')) {
            return 'css/[name][extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
      // Externalize WordPress packages - they're provided by WordPress at runtime
      external: (id) => {
        return id.startsWith('@wordpress/') || id === 'wp';
      },
    },
  },
  server: {
    host: '0.0.0.0', // Allow external connections in Docker
    port: PORT,
    strictPort: false, // Allow Vite to find an available port if specified port is taken
    hmr: {
      host: 'localhost',
      port: PORT,
    },
  },
  css: {
    postcss: './postcss.config.js',
  },
});

