<script setup>
import { ref, computed, inject } from 'vue';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { useRouter } from 'vue-router'

const axios = inject('axios')
const router = useRouter()

let isAdminLogin = ref(window.location.pathname.split('/')[1] == "admin")

const form = ref({
    password: '',
    confirm_password: '',
    token: window.location.pathname.split('/')[3] ? window.location.pathname.split('/')[3] : '',
    errors: []
})

const isConfirmPasswordValid = computed(() => {
    if (!form.value.confirmPassword) {
        return false
    }
    return true
})

const responseData = ref('')

const submit = (async () => {
    const response = await axios.patch('/admin/updatepasswordbytoken', form.value)

    if (response.status == 200) {
        router.push({ name: 'AdminLogin' })
    }
})

</script>

<template>
    <div class="set-password-container">
        <img src="../../assets/images/v-card-full-logo.png">
        <div class="flex flex-column set-password-form-container">
            <div class="input-container">
                <label for="password">Password</label>
                <InputText type="password" class="input-style" id="password" v-model="form.password"
                    :class="{ 'input-style-error': form.errors && form.errors[0] && form.errors[0].password }" />
                <span class="input-error" v-if="form.errors && form.errors[0] && form.errors[0].password">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container">
                <label for="confirmPassword">Confirmar Password</label>
                <InputText type="password" class="input-style" id="confirmPassword" v-model="form.confirm_password"
                    :class="{ 'input-style-error': form.errors && form.errors[0] && !isConfirmPasswordValid }" />
                <span class="input-error" v-if="form.errors && form.errors[0] && !isConfirmPasswordValid">Campo
                    obrigatório.</span>
            </div>
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
    </div>
</template>
<style scoped>
.set-password-container {
    width: 100%;
    height: 100vh;
    display: flex;
    flex-direction: column;
    gap: 24px;
    justify-content: center;
    align-items: center;
    margin-top: -50px;
}

.set-password-container img {
    width: 200px;
}

.set-password-container .set-password-form-container {
    gap: 12px;
    width: 100%;
    max-width: 360px;
}

.set-password-container .set-password-form-container .input-container {
    display: flex;
    flex-direction: column;
    width: 100%;
    gap: 8px;
}

.set-password-container .set-password-form-container .input-container label {
    opacity: 0.7;
}

.register-hiper:hover {
    cursor: pointer;
    text-decoration: underline;
}
</style>