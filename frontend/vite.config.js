import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const backendUrl = env.WORDPRESS_BACKEND_URL || 'http://localhost';
  let hmrHost = '';

  try {
    hmrHost = new URL(backendUrl).hostname;
  } catch (error) {
    hmrHost = '';
  }

  return {
    plugins: [react()],
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
      hmr: {
        host: env.VITE_HMR_HOST || hmrHost || undefined,
        port: 5173,
      },
      watch: {
        usePolling: true,
        interval: 300,
      },
      proxy: {
        '/wp-json': {
          target: backendUrl,
          changeOrigin: true,
        },
        '/wp-admin': {
          target: backendUrl,
          changeOrigin: true,
        },
      },
    },
  };
});
