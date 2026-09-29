import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import { createHead } from '@vueuse/head'
import App from './App.vue'
import './assets/main.css'
import { useAuthStore } from './stores/auth'
import { useThemeStore } from './stores/theme'
import { initEcho } from './utils/echo'

// Initialize head management
const head = createHead()

// Create app
const app = createApp(App)

// Initialize Pinia
const pinia = createPinia()
app.use(pinia)

// Initialize stores
const authStore = useAuthStore()
const themeStore = useThemeStore()

// Initialize theme immediately
themeStore.initTheme()

// Initialize Echo for real-time
initEcho()

// Use router and head
app.use(router)
app.use(head)

// Mount app
app.mount('#app')