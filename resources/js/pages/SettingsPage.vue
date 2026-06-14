<template>
  <AuthenticatedLayout>
    <div class="max-w-2xl mx-auto">
      <h1 class="text-2xl font-bold mb-6">Настройки</h1>

      <div class="bg-white rounded-lg shadow-md p-6">
        <form @submit.prevent="handleSave">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Ссылка на карточку организации в Яндекс.Картах
          </label>

          <div class="flex gap-2">
            <input
              v-model="url"
              type="url"
              required
              placeholder="https://yandex.ru/maps/org/..."
              class="flex-1 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <button
              type="submit"
              :disabled="submitting"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50 transition whitespace-nowrap"
            >
              {{ submitting ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>

        <div v-if="error" class="mt-4 bg-red-50 text-red-700 p-3 rounded text-sm">
          {{ error }}
        </div>

        <div v-if="organization" class="mt-6 border-t pt-4">
          <div v-if="organization.parse_status === 'parsing'" class="flex items-center gap-2 text-blue-600">
            <Spinner />
            <span>Идёт парсинг отзывов...</span>
          </div>

          <div v-else-if="organization.parse_status === 'completed'" class="space-y-2">
            <div class="flex items-center gap-2 text-green-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>Данные загружены</span>
            </div>
            <router-link
              :to="`/organization/${organization.id}`"
              class="inline-block bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
            >
              Посмотреть отзывы
            </router-link>
          </div>

          <div v-else-if="organization.parse_status === 'failed'" class="space-y-2">
            <div class="bg-red-50 text-red-700 p-3 rounded text-sm">
              <p class="font-medium">Ошибка парсинга:</p>
              <p>{{ organization.parse_error || 'Неизвестная ошибка' }}</p>
            </div>
            <button
              @click="handleReparse"
              :disabled="submitting"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50 transition"
            >
              Попробовать снова
            </button>
          </div>
        </div>

        <div v-if="!organization && !loading" class="mt-6 text-gray-500 text-sm">
          Вставьте ссылку на организацию в Яндекс.Картах и нажмите «Сохранить».
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthenticatedLayout from '../components/AuthenticatedLayout.vue'
import Spinner from '../components/SpinnerOverlay.vue'
import { useSettings } from '../composables/useSettings.js'
import { useOrganization } from '../composables/useOrganization.js'

const router = useRouter()
const { organization, loading, error, fetchSettings, saveSettings } = useSettings()
const { reparse } = useOrganization()

const url = ref('')
const submitting = ref(false)
let pollTimer = null

onMounted(() => {
  fetchSettings().then(() => {
    if (organization.value?.yandex_url) {
      url.value = organization.value.yandex_url
    }
    if (organization.value?.parse_status === 'parsing') {
      startPolling()
    }
  })
})

onUnmounted(() => stopPolling())

function startPolling() {
  stopPolling()
  pollTimer = setInterval(async () => {
    await fetchSettings()
    if (organization.value?.parse_status !== 'parsing') {
      stopPolling()
    }
  }, 2000)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

async function handleSave() {
  submitting.value = true
  try {
    const org = await saveSettings(url.value)
    if (org.parse_status === 'completed') {
      router.push(`/organization/${org.id}`)
    } else if (org.parse_status === 'parsing') {
      startPolling()
    }
  } catch {
    // error is handled by composable
  } finally {
    submitting.value = false
  }
}

async function handleReparse() {
  if (!organization.value) return
  submitting.value = true
  try {
    const org = await reparse(organization.value.id)
    organization.value = org
    if (org.parse_status === 'parsing') {
      startPolling()
    }
  } catch {
    // error is handled by composable
  } finally {
    submitting.value = false
  }
}
</script>
