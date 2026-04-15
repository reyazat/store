import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useCartStore } from './stores/cart'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Initialize cart after pinia is ready
const cartStore = useCartStore()
cartStore.initialize()

app.mount('#app')
