import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './style.css' // <-- Essa é a linha que faz o Tailwind funcionar

const app = createApp(App)

app.use(router)
app.mount('#app')