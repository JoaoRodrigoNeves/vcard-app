import { ref, inject, computed } from 'vue'
import { defineStore } from 'pinia'
import { useToast } from "primevue/usetoast";
import { useRouter } from "vue-router";

export const useUserStore = defineStore('user', () => {
    const axios = inject('axios')
    const socket = inject('socket')
    const toast = useToast()
    const router = useRouter()
    const user = ref(null)
    const wantRounded = ref(false)
    const moneyRequest = ref([])
    const userId = computed(() => user.value?.id ?? -1)
    const userName = ref([])

    const formatter = new Intl.NumberFormat('pt-PT', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        useGrouping: true,
    });

    socket.on('newTransaction', (transaction) => {
        toast.add({
            severity: 'info',
            summary: 'Informação',
            detail: `O vCard #${transaction.vcard} enviou ${transaction.value}€!`,
            life: 3000
        });
    })

    socket.on('newMoneyRequest', async (request) => {
        moneyRequest.value = request
        toast.add({
            group: 'askForMoney'
        });
    })

    socket.on('changedVcardStatus', async (vcard) => {
        toast.add({
            severity: 'info',
            summary: 'Informação',
            detail: `Conta bloqueada`,
            life: 2000
        });
        clearUser()
        await router.push({ name: 'Login' })
    })

    socket.on('deletedVcard', async (vcard) => {
        toast.add({
            severity: 'info',
            summary: 'Informação',
            detail: `Conta apagada`,
            life: 2000
        });
        clearUser()
        await router.push({ name: 'Login' })
    })

    async function loadUser() {
        try {
            const response = await axios.get('users/me')
            user.value = response.data.data
            if (user.value.user_type === 'V') {
                wantRounded.value = response.data.wantRounded;
                user.value.piggyBankBalance = formatter.format(response.data.piggyBankBalance)
                user.value.balance = formatter.format(response.data.balance)
                userName.value = response.data.data.name.split(' ').length > 1 ? (response.data.data.name.split(' ')[0] + ' ' + response.data.data.name.split(' ')[response.data.data.name.split(' ').length - 1]) : response.data.data.name.split(' ')[0]
                wantRounded.value = response.data.wantRounded
            }

            await socket.emit('loggedIn', user.value)
        } catch (error) {
            clearUser()
        }
    }

    function clearUser() {
        delete axios.defaults.headers.common.Authorization
        sessionStorage.clear()
        user.value = null
    }

    async function login(credentials) {
        try {
            const response = await axios.post('auth/login', credentials)
            axios.defaults.headers.common.Authorization = "Bearer " + response.data.access_token
            sessionStorage.setItem("token", response.data.access_token)
            await loadUser()
            return true
        } catch (error) {
            clearUser()
            return false
        }
    }

    async function logout() {
        try {
            await axios.post('logout',)
            socket.emit('loggedOut', user.value)
            clearUser()
            return true
        } catch (error) {
            return false
        }
    }

    async function restoreToken() {
        let storedToken = sessionStorage.getItem('token')
        if (storedToken) {
            axios.defaults.headers.common.Authorization = "Bearer " + storedToken
            await loadUser()
            return true
        }
        clearUser()
        return false
    }

    return { user, userId, userName, moneyRequest, login, loadUser, clearUser, logout, restoreToken, wantRounded }
})