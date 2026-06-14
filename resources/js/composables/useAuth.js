import { ref } from 'vue'
import axios from 'axios'

const user = ref(null)
const loading = ref(true)

const token = localStorage.getItem('auth_token')
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

export function useAuth() {
    const authLoading = ref(false)
    const authError = ref(null)

    async function fetchUser() {
        try {
            const { data } = await axios.get('/user')
            user.value = data
        } catch {
            user.value = null
            localStorage.removeItem('auth_token')
            delete axios.defaults.headers.common['Authorization']
        } finally {
            loading.value = false
        }
    }

    async function login(email, password) {
        authLoading.value = true
        authError.value = null
        try {
            const { data } = await axios.post('/login', { email, password })
            user.value = data.user
            localStorage.setItem('auth_token', data.token)
            axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`
            return data
        } finally {
            authLoading.value = false
        }
    }

    async function logout() {
        try {
            await axios.post('/logout')
        } catch {
            // ignore — если токен уже протух, просто чистим локально
        }
        user.value = null
        localStorage.removeItem('auth_token')
        delete axios.defaults.headers.common['Authorization']
    }

    return { user, loading, authLoading, authError, fetchUser, login, logout }
}
