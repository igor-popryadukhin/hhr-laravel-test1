<template>
  <div class="bg-white border rounded-lg p-4">
    <div class="flex items-start gap-3">
      <img
        v-if="review.author_avatar"
        :src="review.author_avatar"
        :alt="review.author_name"
        class="w-10 h-10 rounded-full object-cover flex-shrink-0"
      />
      <div
        v-else
        class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-medium flex-shrink-0"
      >
        {{ initials }}
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium text-gray-900">{{ review.author_name }}</span>
          <span class="text-xs text-gray-400">{{ review.review_date }}</span>
        </div>

        <div class="flex gap-0.5 mt-1">
          <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= review.rating ? 'text-yellow-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>

        <p v-if="review.text" class="text-sm text-gray-700 mt-2 whitespace-pre-line">
          {{ review.text }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  review: { type: Object, required: true },
})

const initials = computed(() => {
  const name = props.review.author_name || ''
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() || '?'
})
</script>
