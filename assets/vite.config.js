import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    // Generate assets with hashes in filenames
    assetsDir: '',
    // Output to a directory that you'll enqueue in WordPress
    outDir: 'dist',
    // This ensures we don't get a single massive JS file
    rollupOptions: {
      output: {
        entryFileNames: 'wp-vue-dashboard.js',
        chunkFileNames: 'wp-vue-[name].js',
        assetFileNames: 'wp-vue-[name].[ext]'
      }
    }
  },
  // Base path for assets - this can be adjusted based on your setup
  base: './'
});
