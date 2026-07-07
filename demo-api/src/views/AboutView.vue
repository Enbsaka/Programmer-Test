<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api'

const storedUser = localStorage.getItem('auth_user')

const apiBaseUrl = import.meta.env.VITE_API_URL
  || `${window.location.protocol}//${window.location.hostname}:8000/api`

const authMode = ref('login')
const authLoading = ref(false)
const authError = ref('')
const authSuccess = ref('')
const authenticatedUser = ref(storedUser ? JSON.parse(storedUser) : null)

const authForm = reactive({
  name: '',
  email: 'enzo@example.com',
  password: '12345678',
  document: ''
})

const filters = reactive({
  name: '',
  category_id: '',
  price_min: '',
  price_max: ''
})

const catalogLoading = ref(false)
const catalogError = ref('')
const categories = ref([])
const products = ref([])
const selectedProductId = ref('')
const selectedProduct = ref(null)

const orderLoading = ref(false)
const orderError = ref('')
const orderSuccess = ref('')
const ordersLoading = ref(false)
const ordersError = ref('')
const orders = ref([])
const selectedOrderId = ref('')
const orderStatus = ref('paid')

const orderForm = reactive({
  payment_method: 'pix',
  quantity: 1
})

const cep = ref('01001000')
const cepLoading = ref(false)
const cepError = ref('')
const address = ref(null)

const totalEndpoints = 10

const isAuthenticated = computed(() => !!localStorage.getItem('auth_token'))

const productPreview = computed(() => {
  if (!selectedProduct.value) return null

  return {
    ...selectedProduct.value,
    total: Number(selectedProduct.value.price) * Number(orderForm.quantity || 1)
  }
})

const productFilterParams = () => Object.fromEntries(
  Object.entries(filters).filter(([, value]) => value !== '' && value !== null)
)

const persistUser = (user) => {
  authenticatedUser.value = user || null

  if (user) {
    localStorage.setItem('auth_user', JSON.stringify(user))
  } else {
    localStorage.removeItem('auth_user')
  }
}

const resetMessages = () => {
  authError.value = ''
  authSuccess.value = ''
  orderError.value = ''
  orderSuccess.value = ''
}

const fetchCategories = async () => {
  const response = await api.get('/categories')
  categories.value = response.data
}

const fetchProducts = async () => {
  catalogLoading.value = true
  catalogError.value = ''

  try {
    const response = await api.get('/products', { params: productFilterParams() })
    products.value = response.data.data

    if (selectedProductId.value) {
      const current = products.value.find((product) => product.id === Number(selectedProductId.value))
      if (current) {
        selectedProduct.value = current
      }
    }
  } catch (err) {
    console.error('Erro ao buscar produtos:', err)
    catalogError.value = err.response?.data?.message || 'Nao foi possivel carregar os produtos.'
  } finally {
    catalogLoading.value = false
  }
}

const fetchProductDetails = async () => {
  if (!selectedProductId.value) {
    selectedProduct.value = null
    return
  }

  try {
    const response = await api.get(`/products/${selectedProductId.value}`)
    selectedProduct.value = response.data
  } catch (err) {
    console.error('Erro ao buscar detalhe do produto:', err)
    orderError.value = err.response?.data?.message || 'Nao foi possivel carregar o produto selecionado.'
  }
}

const fetchOrders = async () => {
  if (!isAuthenticated.value) {
    orders.value = []
    return
  }

  ordersLoading.value = true
  ordersError.value = ''

  try {
    const response = await api.get('/orders')
    orders.value = response.data.data
  } catch (err) {
    console.error('Erro ao buscar pedidos:', err)
    ordersError.value = err.response?.data?.message || 'Nao foi possivel consultar os pedidos.'
  } finally {
    ordersLoading.value = false
  }
}

const submitAuth = async () => {
  authLoading.value = true
  authError.value = ''
  authSuccess.value = ''

  try {
    const endpoint = authMode.value === 'register' ? '/register' : '/login'
    const payload = authMode.value === 'register'
      ? {
          name: authForm.name,
          email: authForm.email,
          password: authForm.password,
          document: authForm.document || null
        }
      : {
          email: authForm.email,
          password: authForm.password
        }

    const response = await api.post(endpoint, payload)
    localStorage.setItem('auth_token', response.data.access_token)
    persistUser(response.data.user || null)
    authSuccess.value = authMode.value === 'register'
      ? 'Conta criada e autenticada com sucesso.'
      : 'Login realizado com sucesso.'

    await fetchOrders()
  } catch (err) {
    console.error('Erro de autenticacao:', err)
    authError.value = err.response?.data?.message || 'Nao foi possivel autenticar.'
  } finally {
    authLoading.value = false
  }
}

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (err) {
    console.error('Erro ao fazer logout:', err)
  } finally {
    localStorage.removeItem('auth_token')
    persistUser(null)
    orders.value = []
    authSuccess.value = 'Sessao encerrada.'
  }
}

const createOrder = async () => {
  if (!selectedProduct.value) {
    orderError.value = 'Selecione um produto antes de criar o pedido.'
    return
  }

  orderLoading.value = true
  orderError.value = ''
  orderSuccess.value = ''

  try {
    const response = await api.post('/orders', {
      payment_method: orderForm.payment_method,
      products: [
        {
          id: selectedProduct.value.id,
          quantity: Number(orderForm.quantity)
        }
      ]
    })

    selectedOrderId.value = String(response.data.id)
    orderSuccess.value = `Pedido #${response.data.id} criado com sucesso.`
    await fetchOrders()
  } catch (err) {
    console.error('Erro ao criar pedido:', err)
    orderError.value = err.response?.data?.message || 'Nao foi possivel criar o pedido.'
  } finally {
    orderLoading.value = false
  }
}

const updateOrder = async () => {
  if (!selectedOrderId.value) {
    orderError.value = 'Selecione um pedido para atualizar.'
    return
  }

  orderLoading.value = true
  orderError.value = ''
  orderSuccess.value = ''

  try {
    await api.patch(`/orders/${selectedOrderId.value}`, {
      status: orderStatus.value
    })

    orderSuccess.value = `Pedido #${selectedOrderId.value} atualizado para ${orderStatus.value}.`
    await fetchOrders()
  } catch (err) {
    console.error('Erro ao atualizar pedido:', err)
    orderError.value = err.response?.data?.message || 'Nao foi possivel atualizar o pedido.'
  } finally {
    orderLoading.value = false
  }
}

const lookupCep = async () => {
  cepLoading.value = true
  cepError.value = ''
  address.value = null

  try {
    const cleanCep = cep.value.replace(/\D/g, '')
    const response = await api.get(`/cep/${cleanCep}`)
    address.value = response.data.data
  } catch (err) {
    console.error('Erro ao consultar CEP:', err)
    cepError.value = err.response?.data?.message || 'Nao foi possivel consultar o CEP.'
  } finally {
    cepLoading.value = false
  }
}

const applyCatalogDemoPreset = async () => {
  filters.name = 'Monitor'
  filters.category_id = ''
  filters.price_min = '500'
  filters.price_max = '2000'
  await fetchProducts()
}

onMounted(async () => {
  resetMessages()
  await Promise.all([fetchCategories(), fetchProducts()])
  await fetchOrders()
})
</script>

<template>
  <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
    <section class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="bg-gray-900 px-8 py-12 md:px-12">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-300 mb-4">Desafio tecnico</p>
        <h1 class="text-3xl md:text-5xl font-black text-white mb-4 tracking-tight">
          Painel de demonstracao da API
        </h1>
        <p class="text-gray-300 text-base md:text-lg max-w-4xl">
          Em vez de simular um e-commerce completo, esta interface demonstra exatamente o que o desafio pede:
          modelagem, autenticacao, filtros de produtos, pedidos e integracao externa.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-8 bg-gray-50 border-t border-gray-100">
        <article class="bg-white rounded-2xl border border-gray-100 p-5">
          <p class="text-sm text-gray-500 mb-1">Banco de dados</p>
          <p class="text-2xl font-black text-gray-900">5 entidades</p>
          <p class="text-sm text-gray-600 mt-2">Clientes, pedidos, produtos, categorias e pagamentos.</p>
        </article>
        <article class="bg-white rounded-2xl border border-gray-100 p-5">
          <p class="text-sm text-gray-500 mb-1">Endpoints</p>
          <p class="text-2xl font-black text-gray-900">{{ totalEndpoints }}</p>
          <p class="text-sm text-gray-600 mt-2">Produtos, autenticacao, pedidos, categorias e ViaCEP.</p>
        </article>
        <article class="bg-white rounded-2xl border border-gray-100 p-5">
          <p class="text-sm text-gray-500 mb-1">Autenticacao</p>
          <p class="text-2xl font-black text-gray-900">{{ isAuthenticated ? 'Ativa' : 'Inativa' }}</p>
          <p class="text-sm text-gray-600 mt-2">Sanctum com envio automatico do token pelo front.</p>
        </article>
        <article class="bg-white rounded-2xl border border-gray-100 p-5">
          <p class="text-sm text-gray-500 mb-1">Base URL</p>
          <p class="text-sm font-bold text-gray-900 break-all">{{ apiBaseUrl }}</p>
          <p class="text-sm text-gray-600 mt-2">Configuravel por `VITE_API_URL`.</p>
        </article>
      </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-8 items-start">
      <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 self-start">
        <div class="flex items-center justify-between gap-4 mb-6">
          <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">1. Autenticacao</p>
            <h2 class="text-2xl font-black text-gray-900 mt-2">Login e cadastro</h2>
          </div>
          <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
            POST /api/login | /api/register
          </span>
        </div>

        <div class="flex gap-3 mb-5">
          <button
            @click="authMode = 'login'; authError = ''; authSuccess = ''"
            :class="authMode === 'login' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700'"
            class="px-4 py-2 rounded-xl font-semibold text-sm transition-colors"
          >
            Login
          </button>
          <button
            @click="authMode = 'register'; authError = ''; authSuccess = ''"
            :class="authMode === 'register' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700'"
            class="px-4 py-2 rounded-xl font-semibold text-sm transition-colors"
          >
            Cadastro
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <form @submit.prevent="submitAuth" class="space-y-4">
            <div v-if="authMode === 'register'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
              <input v-model="authForm.name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
              <input v-model="authForm.email" type="email" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
              <input v-model="authForm.password" type="password" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div v-if="authMode === 'register'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Documento</label>
              <input v-model="authForm.document" type="text" placeholder="Opcional" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div class="flex gap-3">
              <button
                type="submit"
                :disabled="authLoading"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl transition-colors disabled:opacity-70"
              >
                {{ authLoading ? 'Enviando...' : authMode === 'register' ? 'Cadastrar' : 'Entrar' }}
              </button>
              <button
                v-if="isAuthenticated"
                type="button"
                @click="logout"
                class="border border-gray-300 hover:bg-gray-50 text-gray-900 font-semibold px-5 py-3 rounded-xl transition-colors"
              >
                Logout
              </button>
            </div>

            <div v-if="authError" class="p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-medium">
              {{ authError }}
            </div>

            <div v-if="authSuccess" class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-medium">
              {{ authSuccess }}
            </div>
          </form>

          <div class="bg-gray-50 rounded-2xl border border-gray-100 p-5">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Estado atual</h3>
            <div class="space-y-3 text-sm text-gray-600">
              <p><span class="font-semibold text-gray-900">Autenticado:</span> {{ isAuthenticated ? 'Sim' : 'Nao' }}</p>
              <p><span class="font-semibold text-gray-900">Usuario:</span> {{ authenticatedUser?.name || 'Nenhum' }}</p>
              <p><span class="font-semibold text-gray-900">E-mail:</span> {{ authenticatedUser?.email || 'Nenhum' }}</p>
              <p><span class="font-semibold text-gray-900">Cliente vinculado:</span> {{ authenticatedUser?.customer?.id || 'Nao informado' }}</p>
              <p class="pt-3 border-t border-gray-200">
                Dica: use `enzo@example.com` e `12345678` para a demo rapida.
              </p>
            </div>
          </div>
        </div>
      </article>

      <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8 self-start">
        <div class="flex items-center justify-between gap-4 mb-6">
          <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">2. Produtos</p>
            <h2 class="text-2xl font-black text-gray-900 mt-2">Filtros e consulta</h2>
          </div>
          <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
            GET /api/products
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
            <input v-model="filters.name" type="text" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
            <select v-model="filters.category_id" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none bg-white">
              <option value="">Todas</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Preco minimo</label>
            <input v-model="filters.price_min" type="number" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Preco maximo</label>
            <input v-model="filters.price_max" type="number" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
          </div>
        </div>

        <div class="flex flex-wrap gap-3 mb-6">
          <button @click="fetchProducts" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl transition-colors">
            Aplicar filtros
          </button>
          <button
            @click="filters.name = ''; filters.category_id = ''; filters.price_min = ''; filters.price_max = ''; fetchProducts()"
            class="border border-gray-300 hover:bg-gray-50 text-gray-900 font-semibold px-5 py-3 rounded-xl transition-colors"
          >
            Limpar
          </button>
          <button
            @click="applyCatalogDemoPreset"
            class="border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold px-5 py-3 rounded-xl transition-colors"
          >
            Rodar demo de filtros
          </button>
        </div>

        <div v-if="catalogError" class="mb-4 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-medium">
          {{ catalogError }}
        </div>

        <div class="bg-gray-50 rounded-2xl border border-gray-100 p-4">
          <div class="flex items-center justify-between gap-4 mb-3">
            <p class="text-sm text-gray-500">Resultado da consulta</p>
            <p class="text-xs font-semibold text-gray-500">
              {{ products.length }} {{ products.length === 1 ? 'produto' : 'produtos' }}
            </p>
          </div>
          <p v-if="catalogLoading" class="text-sm text-gray-600">Carregando produtos...</p>
          <div
            v-else
            class="space-y-2 max-h-64 overflow-y-auto pr-1"
          >
            <div
              v-for="product in products"
              :key="product.id"
              class="bg-white rounded-xl border border-gray-100 px-3 py-2.5"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <h3 class="font-bold text-gray-900 text-sm truncate">{{ product.name }}</h3>
                  <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-xs text-gray-500">
                    <span>Estoque: {{ product.stock }}</span>
                    <span class="text-gray-300">|</span>
                    <span class="truncate">{{ product.description || 'Sem descricao' }}</span>
                  </div>
                </div>
                <div class="text-right shrink-0">
                  <p class="font-black text-gray-900 text-sm">R$ {{ Number(product.price).toFixed(2) }}</p>
                </div>
              </div>
              <div class="mt-2 flex items-center justify-between gap-3">
                <div class="flex flex-wrap gap-1.5 min-w-0">
                <span
                  v-for="category in product.categories"
                  :key="category.id"
                  class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-semibold"
                >
                  {{ category.name }}
                </span>
                </div>
                <button
                  @click="selectedProductId = String(product.id); fetchProductDetails()"
                  class="text-xs font-semibold text-blue-700 hover:text-blue-900"
                >
                  Selecionar
                </button>
              </div>
            </div>
            <p v-if="!products.length" class="text-sm text-gray-600">Nenhum produto encontrado.</p>
          </div>
        </div>
      </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-8">
      <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
        <div class="flex items-center justify-between gap-4 mb-6">
          <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">3. Pedidos</p>
            <h2 class="text-2xl font-black text-gray-900 mt-2">Criacao e atualizacao</h2>
          </div>
          <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
            POST /api/orders | PATCH /api/orders/{id}
          </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Produto selecionado</label>
              <select
                v-model="selectedProductId"
                @change="fetchProductDetails"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none bg-white"
              >
                <option value="">Selecione um produto</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
              <input v-model.number="orderForm.quantity" min="1" type="number" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Metodo de pagamento</label>
              <select v-model="orderForm.payment_method" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none bg-white">
                <option value="pix">pix</option>
                <option value="credit_card">credit_card</option>
                <option value="boleto">boleto</option>
              </select>
            </div>

            <div class="flex flex-wrap gap-3">
              <button
                @click="createOrder"
                :disabled="orderLoading || !isAuthenticated"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl transition-colors disabled:opacity-70"
              >
                {{ orderLoading ? 'Processando...' : 'Criar pedido' }}
              </button>
              <button
                @click="fetchOrders"
                :disabled="!isAuthenticated"
                class="border border-gray-300 hover:bg-gray-50 text-gray-900 font-semibold px-5 py-3 rounded-xl transition-colors disabled:opacity-70"
              >
                Atualizar lista
              </button>
            </div>

            <div v-if="orderError" class="p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-medium">
              {{ orderError }}
            </div>

            <div v-if="orderSuccess" class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-medium">
              {{ orderSuccess }}
            </div>
          </div>

          <div class="bg-gray-50 rounded-2xl border border-gray-100 p-5">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Resumo do payload</h3>
            <div v-if="productPreview" class="space-y-2 text-sm text-gray-600">
              <p><span class="font-semibold text-gray-900">Produto:</span> {{ productPreview.name }}</p>
              <p><span class="font-semibold text-gray-900">Preco unitario:</span> R$ {{ Number(productPreview.price).toFixed(2) }}</p>
              <p><span class="font-semibold text-gray-900">Quantidade:</span> {{ orderForm.quantity }}</p>
              <p><span class="font-semibold text-gray-900">Pagamento:</span> {{ orderForm.payment_method }}</p>
              <p><span class="font-semibold text-gray-900">Total estimado:</span> R$ {{ productPreview.total.toFixed(2) }}</p>
            </div>
            <p v-else class="text-sm text-gray-600">
              Selecione um produto para montar o payload do pedido.
            </p>
          </div>
        </div>

        <div class="mt-8 pt-8 border-t border-gray-100">
          <div class="flex items-center justify-between gap-4 mb-4">
            <h3 class="text-xl font-bold text-gray-900">Atualizar status de pedido</h3>
            <span class="text-sm text-gray-500">GET /api/orders + PATCH /api/orders/{id}</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Pedido</label>
              <select v-model="selectedOrderId" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none bg-white">
                <option value="">Selecione um pedido</option>
                <option v-for="order in orders" :key="order.id" :value="order.id">
                  #{{ order.id }} - {{ order.status }} - R$ {{ Number(order.total_amount).toFixed(2) }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Novo status</label>
              <select v-model="orderStatus" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none bg-white">
                <option value="pending">pending</option>
                <option value="paid">paid</option>
                <option value="cancelled">cancelled</option>
                <option value="shipped">shipped</option>
              </select>
            </div>
          </div>

          <button
            @click="updateOrder"
            :disabled="orderLoading || !isAuthenticated"
            class="mt-4 bg-gray-900 hover:bg-gray-800 text-white font-semibold px-5 py-3 rounded-xl transition-colors disabled:opacity-70"
          >
            Atualizar status
          </button>
        </div>
      </article>

      <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
        <div class="flex items-center justify-between gap-4 mb-6">
          <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">4. Consulta e integracao</p>
            <h2 class="text-2xl font-black text-gray-900 mt-2">Pedidos e ViaCEP</h2>
          </div>
          <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">
            GET /api/orders | GET /api/cep/{cep}
          </span>
        </div>

        <div class="space-y-6">
          <div class="bg-gray-50 rounded-2xl border border-gray-100 p-5">
            <div class="flex items-center justify-between gap-4 mb-4">
              <h3 class="text-lg font-bold text-gray-900">Pedidos do usuario autenticado</h3>
              <button
                @click="fetchOrders"
                :disabled="ordersLoading || !isAuthenticated"
                class="text-sm font-semibold text-blue-700 hover:text-blue-900 disabled:opacity-70"
              >
                {{ ordersLoading ? 'Atualizando...' : 'Atualizar' }}
              </button>
            </div>

            <div v-if="ordersError" class="mb-4 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-medium">
              {{ ordersError }}
            </div>

            <div v-if="!isAuthenticated" class="text-sm text-gray-600">
              Faca login para listar pedidos protegidos.
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="order in orders"
                :key="order.id"
                class="bg-white rounded-2xl border border-gray-100 p-4"
              >
                <div class="flex items-start justify-between gap-4">
                  <div>
                    <p class="font-bold text-gray-900">Pedido #{{ order.id }}</p>
                    <p class="text-sm text-gray-600">
                      Status: {{ order.status }} | Total: R$ {{ Number(order.total_amount).toFixed(2) }}
                    </p>
                  </div>
                  <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
                    {{ order.payments?.[0]?.method || 'sem pagamento' }}
                  </span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                  <span
                    v-for="product in order.products"
                    :key="product.id"
                    class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold"
                  >
                    {{ product.name }} x{{ product.pivot.quantity }}
                  </span>
                </div>
              </div>
              <p v-if="!orders.length" class="text-sm text-gray-600">Nenhum pedido encontrado.</p>
            </div>
          </div>

          <div class="bg-blue-50 rounded-2xl border border-blue-100 p-6">
            <div class="flex items-center justify-between gap-4 mb-6">
              <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Integracao externa</p>
                <h3 class="text-2xl font-black text-gray-900 mt-2">Consulta de CEP</h3>
              </div>
              <span class="px-3 py-1 rounded-full bg-white text-blue-700 text-xs font-bold border border-blue-200">
                GET /api/cep/{cep}
              </span>
            </div>

            <form @submit.prevent="lookupCep" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                <input
                  v-model="cep"
                  type="text"
                  inputmode="numeric"
                  maxlength="9"
                  placeholder="Ex.: 01001000"
                  class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none transition-all bg-white"
                >
              </div>

              <div class="flex flex-wrap gap-3">
                <button
                  type="submit"
                  :disabled="cepLoading"
                  class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl transition-colors disabled:opacity-70"
                >
                  {{ cepLoading ? 'Consultando...' : 'Consultar CEP' }}
                </button>
                <button
                  type="button"
                  @click="cep = '01001000'; lookupCep()"
                  class="border border-gray-300 hover:bg-white text-gray-900 font-semibold px-5 py-3 rounded-xl transition-colors"
                >
                  Usar exemplo
                </button>
              </div>
            </form>

            <div v-if="cepError" class="mt-6 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm font-medium">
              {{ cepError }}
            </div>

            <div v-else-if="address" class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="p-4 rounded-xl bg-white border border-blue-100">
                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Logradouro</p>
                <p class="font-semibold text-gray-900">{{ address.logradouro || 'Nao informado' }}</p>
              </div>
              <div class="p-4 rounded-xl bg-white border border-blue-100">
                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Bairro</p>
                <p class="font-semibold text-gray-900">{{ address.bairro || 'Nao informado' }}</p>
              </div>
              <div class="p-4 rounded-xl bg-white border border-blue-100">
                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">Cidade</p>
                <p class="font-semibold text-gray-900">{{ address.localidade || 'Nao informado' }}</p>
              </div>
              <div class="p-4 rounded-xl bg-white border border-blue-100">
                <p class="text-xs uppercase tracking-widest text-gray-500 mb-1">UF</p>
                <p class="font-semibold text-gray-900">{{ address.uf || 'Nao informado' }}</p>
              </div>
            </div>
          </div>
        </div>
      </article>
    </section>
  </div>
</template>
