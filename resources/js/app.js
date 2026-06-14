import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import axios from 'axios'
import App from './App.vue'
import LoginPage from './pages/LoginPage.vue'
import SettingsPage from './pages/SettingsPage.vue'
import OrganizationPage from './pages/OrganizationPage.vue'

axios.defaults.withCredentials = true
axios.defaults.baseURL = '/api'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login', component: LoginPage, meta: { guest: true } },
        { path: '/settings', component: SettingsPage, meta: { auth: true } },
        { path: '/organization/:id', component: OrganizationPage, meta: { auth: true } },
        { path: '/:pathMatch(.*)*', redirect: '/settings' },
    ],
})

router.beforeEach(async (to, from, next) => {
    try {
        const { data } = await axios.get('/user')
        const isAuthenticated = !!data?.id

        if (to.meta.auth && !isAuthenticated) {
            return next('/login')
        }
        if (to.meta.guest && isAuthenticated) {
            return next('/settings')
        }
        next()
    } catch {
        if (to.meta.auth) {
            return next('/login')
        }
        next()
    }
})

const app = createApp(App)
app.use(router)
app.mount('#app')
