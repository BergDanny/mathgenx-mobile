import path from 'path'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          // Core Vue ecosystem
          'vue-vendor': ['vue', 'vue-router', 'pinia'],
          
          // Chart libraries (large, used in dashboard/analytics)
          'chart-vendor': ['chart.js', 'vue-chartjs'],
          
          // Animation libraries (large, used in specific views)
          'animation-vendor': ['gsap', 'motion-v'],
          
          // UI libraries
          'ui-vendor': ['preline', '@preline/remove-element', 'lucide-vue-next'],
          
          // WebGL/3D library (only used in specific views)
          'webgl-vendor': ['ogl'],
          
          // Utilities
          'utils-vendor': ['ky', 'sortablejs', 'toastify-js'],
        },
      },
    },
    chunkSizeWarningLimit: 1000, // Increase limit to 1MB (you can adjust this)
  },
})
