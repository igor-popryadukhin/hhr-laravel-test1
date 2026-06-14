<template>
  <div class="flex items-center justify-center gap-1 mt-6">
    <button
      :disabled="currentPage === 1"
      @click="$emit('pageChange', currentPage - 1)"
      class="px-3 py-1.5 rounded text-sm border hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition"
    >
      &laquo;
    </button>

    <template v-for="page in pages" :key="page">
      <span v-if="page === '...'" class="px-2 text-gray-400">...</span>
      <button
        v-else
        @click="$emit('pageChange', page)"
        :class="[
          'px-3 py-1.5 rounded text-sm border transition',
          page === currentPage
            ? 'bg-blue-600 text-white border-blue-600'
            : 'hover:bg-gray-100',
        ]"
      >
        {{ page }}
      </button>
    </template>

    <button
      :disabled="currentPage >= lastPage"
      @click="$emit('pageChange', currentPage + 1)"
      class="px-3 py-1.5 rounded text-sm border hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition"
    >
      &raquo;
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
})

defineEmits(['pageChange'])

const pages = computed(() => {
  const current = props.currentPage
  const last = props.lastPage
  const pages = []

  if (last <= 7) {
    for (let i = 1; i <= last; i++) pages.push(i)
    return pages
  }

  pages.push(1)
  if (current > 3) pages.push('...')

  for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
    pages.push(i)
  }

  if (current < last - 2) pages.push('...')
  pages.push(last)

  return pages
})
</script>
