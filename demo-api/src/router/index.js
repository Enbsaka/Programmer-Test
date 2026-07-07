import { createRouter, createWebHistory } from 'vue-router'
import AboutView from '../views/AboutView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'demo', component: AboutView },
    { path: '/sobre', redirect: '/' },
    { path: '/login', redirect: '/' },
    { path: '/cadastro', redirect: '/' },
    { path: '/loja', redirect: '/' },
    { path: '/produto/:id', redirect: '/' }
  ]
})

export default router
