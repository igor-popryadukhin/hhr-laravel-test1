import { ref } from 'vue'
import axios from 'axios'

// Модульный ref — шарится между NavBar и SettingsPage
const organization = ref(null)
const loading = ref(false)
const error = ref(null)

export function useSettings() {
    async function fetchSettings() {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get('/settings')
            organization.value = data.organization
        } catch (e) {
            error.value = e.response?.data?.message || 'Ошибка загрузки настроек'
        } finally {
            loading.value = false
        }
    }

    async function saveSettings(yandexUrl) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.put('/settings', { yandex_url: yandexUrl })
            organization.value = data.organization
            return data.organization
        } catch (e) {
            error.value = e.response?.data?.errors?.yandex_url?.[0]
                || e.response?.data?.message
                || 'Ошибка сохранения'
            throw e
        } finally {
            loading.value = false
        }
    }

    return { organization, loading, error, fetchSettings, saveSettings }
}
