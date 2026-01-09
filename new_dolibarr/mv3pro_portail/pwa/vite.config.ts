import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const basePath = env.VITE_BASE_PATH || '/custom/mv3pro_portail/pwa_dist';

  return {
    plugins: [
      react(),
      VitePWA({
        registerType: 'autoUpdate',
        includeAssets: ['icon-192.png', 'icon-512.png'],
        manifest: {
          name: 'MV3 PRO Mobile',
          short_name: 'MV3 PRO',
          description: 'Application mobile pour les ouvriers MV3 Carrelage',
          theme_color: '#0891b2',
          background_color: '#f9fafb',
          display: 'standalone',
          orientation: 'portrait',
          scope: `${basePath}/`,
          start_url: `${basePath}/#/dashboard`,
        icons: [
          {
            src: 'icon-192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'icon-512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'google-fonts-cache',
              expiration: {
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 365
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          }
          ]
        }
      })
    ],
    build: {
      outDir: '../pwa_dist',
      emptyOutDir: true,
      sourcemap: false
    },
    base: basePath
  };
});
