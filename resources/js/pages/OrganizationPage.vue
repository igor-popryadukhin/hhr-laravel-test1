<template>
  <AuthenticatedLayout>
    <div class="max-w-4xl mx-auto">
      <div v-if="loading" class="flex justify-center py-12">
        <Spinner />
      </div>

      <div v-else-if="error" class="bg-red-50 text-red-700 p-4 rounded">
        {{ error }}
      </div>

      <template v-else-if="organization">
        <OrganizationHeader
          :organization="organization"
          @reparse="handleReparse"
        />

        <div class="mt-6">
          <ReviewList
            :key="reparseKey"
            :organization-id="organization.id"
          />
        </div>
      </template>

      <div v-else class="text-center text-gray-500 py-12">
        Организация не найдена.
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import AuthenticatedLayout from '../components/AuthenticatedLayout.vue'
import OrganizationHeader from '../components/OrganizationHeader.vue'
import ReviewList from '../components/ReviewList.vue'
import Spinner from '../components/SpinnerOverlay.vue'
import { useOrganization } from '../composables/useOrganization.js'

const route = useRoute()
const { organization, loading, error, fetchOrganization, reparse } = useOrganization()

let pollTimer = null
// Меняется после каждого успешного парсинга — заставляет ReviewList пересоздаться и перезагрузить отзывы
const reparseKey = ref(0)

onMounted(() => {
  fetchOrganization(route.params.id).then(() => {
    if (organization.value?.parse_status === 'parsing') {
      startPolling()
    }
  })
})

onUnmounted(() => stopPolling())

function startPolling() {
  stopPolling()
  pollTimer = setInterval(async () => {
    await fetchOrganization(route.params.id)
    if (organization.value?.parse_status !== 'parsing') {
      stopPolling()
      reparseKey.value++ // Форсируем перезагрузку списка отзывов
    }
  }, 2000)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

async function handleReparse() {
  await reparse(route.params.id)
  if (organization.value?.parse_status === 'parsing') {
    startPolling()
  } else {
    // Если парсинг завершился мгновенно (синхронный) — сразу обновляем отзывы
    reparseKey.value++
  }
}
</script>
