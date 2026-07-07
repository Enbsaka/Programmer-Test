<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import api from './services/api'

const router = useRouter()
const isAuthenticated = ref(!!localStorage.getItem('auth_token'))

const syncAuthState = () => {
  isAuthenticated.value = !!localStorage.getItem('auth_token')
}

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (error) {
    console.error('Erro ao encerrar sessao:', error)
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    syncAuthState()
    router.push('/')
  }
}

let removeAfterEachGuard

onMounted(() => {
  syncAuthState()
  window.addEventListener('storage', syncAuthState)
  removeAfterEachGuard = router.afterEach(() => {
    syncAuthState()
  })
})

onUnmounted(() => {
  window.removeEventListener('storage', syncAuthState)
  removeAfterEachGuard?.()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 font-sans">
    <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex-shrink-0 flex items-center">
            <RouterLink to="/" class="text-2xl font-black tracking-tighter text-gray-900">
              API<span class="text-blue-600">DEMO</span>
            </RouterLink>
          </div>
          
          <nav class="flex items-center space-x-6">
            <RouterLink to="/" class="text-sm font-semibold text-gray-600 hover:text-gray-900 transition-colors">Painel</RouterLink>
            <template v-if="isAuthenticated">
              <button @click="logout" class="text-sm font-semibold text-red-600 hover:text-red-800 transition-colors">Sair</button>
            </template>
            <template v-else>
              <RouterLink to="/" class="bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold px-5 py-2.5 rounded-full transition-all shadow-sm">
                Abrir Demo
              </RouterLink>
            </template>
          </nav>
        </div>
      </div>
    </header>

    <main>
      <RouterView />
    </main>
  </div>
</template>
