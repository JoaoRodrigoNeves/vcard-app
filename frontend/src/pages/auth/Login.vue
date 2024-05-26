<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import InputMask from 'primevue/inputmask';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import { useUserStore } from '@/stores/user'

let isAdminLogin = ref(window.location.pathname.split('/')[1] == "admin")
const toast = useToast()
const router = useRouter()
const userStore = useUserStore()

const credentials = ref({
    username: '',
    password: ''
})

const responseData = ref('')

const submit = (async () => {
    if (await userStore.login(credentials.value)) {
        isAdminLogin.value ? router.push({ name: 'AdminDashboard' }) : router.push({ name: 'Dashboard' })
    } else {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Ocorreu um problema ao entrar na aplicação!', life: 3000 });
    }
})

watch(
    () => router.currentRoute.value.path,
    (newPath, oldPath) => {
        isAdminLogin.value = newPath.split('/')[1] === 'admin';
    },
    { immediate: true }
);

</script>

<template>
    <div class="login-container">
        <img src="../../assets/images/v-card-full-logo.png">
        <div class="flex flex-column login-form-container">
            <InputMask class="input-style" v-model="credentials.username" date="phone" mask="999999999" slotChar=""
                placeholder="Telemóvel" v-if="!isAdminLogin" />
            <InputText type="text" class="input-style" v-model="credentials.username" placeholder="Email"
                v-if="isAdminLogin" />
            <InputText type="password" class="input-style" v-model="credentials.password" placeholder="Password" />
            <Button label="Entrar" class="btn-main" raised @click="submit"></Button>
        </div>
        <div class="my-5" v-if="responseData">
            <label for="exampleFormControlTextarea1" class="form-label">Response</label>
            <textarea :value="responseData" class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
        </div>
        <div v-if="!isAdminLogin">
            Ainda não tens conta? <router-link :to="{ name: 'NewUser' }"
                class="text-main register-hiper">Regista-te</router-link>
        </div>
        <div v-if="!isAdminLogin" style="position: absolute; bottom: 50px;">
            Área de Administrador <router-link :to="{ name: 'AdminLogin' }"
                class="text-main register-hiper">Aceder</router-link>
        </div>
        <div v-if="isAdminLogin" style="position: absolute; bottom: 50px;">
            Área de Utilizador <router-link :to="{ name: 'Login' }" class="text-main register-hiper">Aceder</router-link>
        </div>
    </div>
</template>
<style scoped>
.login-container {
    width: 100%;
    height: 100vh;
    display: flex;
    flex-direction: column;
    gap: 24px;
    justify-content: center;
    align-items: center;
    margin-top: -50px;
}

.login-container img {
    width: 200px;
}

.login-container .login-form-container {
    gap: 12px;
    width: 100%;
    max-width: 360px;
}

.register-hiper:hover {
    cursor: pointer;
    text-decoration: underline;
}
</style>