import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    cssCodeSplit: true,
    lib: {
      entry: 'src/main.js',
      name: 'Voyager Permissions',
      formats: ['umd'],
      fileName: 'voyager-permissions'
    },
    rollupOptions: {
      external: ['vue', 'axios', 'debounce'],
      output: {
        globals: {
          vue: 'Vue',
          axios: 'axios',
          debounce: 'debounce',
        }
      }
    }
  }
})