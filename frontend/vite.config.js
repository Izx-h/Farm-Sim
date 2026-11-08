import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: ['css/app.css', 'js/app.js'],
      refresh: true,
      // Build into the Laravel app's public folder so the `@vite` Blade
      // directive (which reads backend/public/build/manifest.json) works.
      publicDirectory: '../backend/public',
      buildDirectory: 'build',
    }),
    vue(),
  ],
});
