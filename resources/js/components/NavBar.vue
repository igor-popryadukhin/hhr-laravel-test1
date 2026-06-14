<template>
  <nav class="bg-white shadow-sm border-b">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-8">
        <router-link to="/settings" class="flex items-center gap-2 text-gray-800 hover:text-blue-600 transition">
          <span class="text-xl font-bold tracking-tight">
            <span class="text-blue-600">Review</span>Parser
          </span>
        </router-link>

        <div class="flex items-center gap-4">
          <router-link
            :to="orgLink"
            class="text-sm text-gray-600 hover:text-gray-800 transition"
          >
            Отзывы
          </router-link>
          <router-link to="/settings" class="text-sm text-gray-600 hover:text-gray-800 transition">
            Настройки
          </router-link>
        </div>
      </div>

      <button
        @click="handleLogout"
        class="text-sm text-gray-500 hover:text-red-600 transition"
      >
        Выйти
      </button>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth.js'
import { useSettings } from '../composables/useSettings.js'

const router = useRouter()
const { logout } = useAuth()
const { organization, fetchSettings } = useSettings()

// Загружаем настройки при первом рендере если ещё нет
if (!organization.value) {
  fetchSettings()
}

const orgLink = computed(() =>
  organization.value?.id ? `/organization/${organization.value.id}` : '/settings'
)
</script>
