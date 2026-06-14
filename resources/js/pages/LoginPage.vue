<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
      <h1 class="text-2xl font-bold text-center mb-6">Вход</h1>

      <div v-if="error" class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm">
        {{ error }}
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="test@example.com"
          @input="error = null"
              />
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="password"
          @input="error = null"
              />
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-50 transition"
        >
          {{ submitting ? 'Вход...' : 'Войти' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'

const router = useRouter()
const { login } = useAuth()

const email = ref('test@example.com')
const password = ref('password')
const error = ref(null)
const submitting = ref(false)

async function handleLogin() {
  error.value = null
  submitting.value = true
  try {
    await login(email.value, password.value)
    router.push('/settings')
  } catch (e) {
    error.value = e.response?.data?.message || 'Ошибка входа'
  } finally {
    submitting.value = false
  }
}
</script>
