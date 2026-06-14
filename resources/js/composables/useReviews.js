import { ref } from 'vue'
import axios from 'axios'

export function useReviews() {
    const reviews = ref([])
    const loading = ref(false)
    const error = ref(null)
    const currentPage = ref(1)
    const lastPage = ref(1)
    const total = ref(0)
    const perPage = ref(50)

    async function fetchReviews(orgId, page = 1) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`/organizations/${orgId}/reviews`, {
                params: { page },
            })
            reviews.value = data.data
            currentPage.value = data.current_page
            lastPage.value = data.last_page
            total.value = data.total
            perPage.value = data.per_page
        } catch (e) {
            error.value = e.response?.data?.message || 'Ошибка загрузки отзывов'
        } finally {
            loading.value = false
        }
    }

    function goToPage(orgId, page) {
        return fetchReviews(orgId, page)
    }

    return { reviews, loading, error, currentPage, lastPage, total, perPage, fetchReviews, goToPage }
}
