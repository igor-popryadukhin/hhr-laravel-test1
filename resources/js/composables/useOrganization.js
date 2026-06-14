import { ref } from 'vue'
import axios from 'axios'

export function useOrganization() {
    const organization = ref(null)
    const loading = ref(false)
    const error = ref(null)

    async function fetchOrganization(id) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`/organizations/${id}`)
            organization.value = data.organization
        } catch (e) {
            error.value = e.response?.data?.message || 'Ошибка загрузки данных организации'
        } finally {
            loading.value = false
        }
    }

    async function reparse(id) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.post(`/organizations/${id}/reparse`)
            organization.value = data.organization
            return data.organization
        } catch (e) {
            error.value = e.response?.data?.message || 'Ошибка запуска парсинга'
            throw e
        } finally {
            loading.value = false
        }
    }

    return { organization, loading, error, fetchOrganization, reparse }
}
