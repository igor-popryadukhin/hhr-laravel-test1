<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold">
        Отзывы
        <span v-if="total" class="text-gray-500 font-normal text-base">({{ total }})</span>
      </h3>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <Spinner />
    </div>

    <div v-else-if="error" class="bg-red-50 text-red-700 p-4 rounded">
      {{ error }}
    </div>

    <template v-else>
      <div v-if="reviews.length === 0" class="text-center text-gray-500 py-12">
        Нет отзывов
      </div>

      <div v-else class="space-y-4">
        <ReviewCard
          v-for="review in reviews"
          :key="review.id"
          :review="review"
        />
      </div>

      <PaginationBar
        v-if="lastPage > 1"
        :current-page="currentPage"
        :last-page="lastPage"
        @page-change="onPageChange"
      />
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import ReviewCard from './ReviewCard.vue'
import PaginationBar from './PaginationBar.vue'
import Spinner from './SpinnerOverlay.vue'
import { useReviews } from '../composables/useReviews.js'

const props = defineProps({
  organizationId: { type: [String, Number], required: true },
})

const { reviews, loading, error, currentPage, lastPage, total, fetchReviews, goToPage } = useReviews()

onMounted(() => {
  fetchReviews(props.organizationId)
})

function onPageChange(page) {
  goToPage(props.organizationId, page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>
