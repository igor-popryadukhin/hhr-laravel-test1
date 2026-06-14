<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex items-start justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-900">{{ organization.name }}</h2>
        <p v-if="organization.address" class="text-sm text-gray-500 mt-1">{{ organization.address }}</p>
      </div>

      <button
        v-if="organization.parse_status === 'completed'"
        @click="$emit('reparse')"
        class="text-sm text-gray-400 hover:text-blue-600 transition"
        title="Обновить данные"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
      </button>
    </div>

    <div class="grid grid-cols-3 gap-4 mt-6">
      <div class="text-center p-4 bg-yellow-50 rounded-lg">
        <div class="flex items-center justify-center gap-1 mb-1">
          <svg v-for="i in 5" :key="i" class="w-5 h-5" :class="starClass(i)" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
        <div class="text-2xl font-bold text-gray-900">
          {{ organization.average_rating ?? '—' }}
        </div>
        <div class="text-xs text-gray-500 mt-1">Средний рейтинг</div>
      </div>

      <div class="text-center p-4 bg-blue-50 rounded-lg">
        <div class="text-2xl font-bold text-gray-900">
          {{ formatNumber(organization.rating_count) }}
        </div>
        <div class="text-xs text-gray-500 mt-1">Всего оценок</div>
      </div>

      <div class="text-center p-4 bg-green-50 rounded-lg">
        <div class="text-2xl font-bold text-gray-900">
          {{ formatNumber(organization.review_count) }}
        </div>
        <div class="text-xs text-gray-500 mt-1">Отзывов с текстом</div>
      </div>
    </div>

    <div v-if="organization.parse_status === 'failed'" class="mt-4 bg-red-50 text-red-700 p-3 rounded text-sm">
      Ошибка: {{ organization.parse_error || 'Не удалось загрузить данные' }}
    </div>

    <div class="mt-4 text-xs text-gray-400">
      <span v-if="organization.parsed_at">
        Данные получены: {{ new Date(organization.parsed_at).toLocaleString('ru-RU') }}
      </span>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  organization: { type: Object, required: true },
})
defineEmits(['reparse'])

function starClass(i) {
  const rating = props.organization?.average_rating || 0
  return i <= Math.round(rating) ? 'text-yellow-400' : 'text-gray-200'
}

function formatNumber(n) {
  if (n == null) return '—'
  return new Intl.NumberFormat('ru-RU').format(n)
}
</script>
