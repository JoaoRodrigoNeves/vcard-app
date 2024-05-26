<script setup>
import {ref, inject, watch} from 'vue'
import UserDetail from "@/components/users/UserDetail.vue"
import {useUserStore} from "@/stores/user";
import {useRouter} from "vue-router";
import {useToast} from "primevue/usetoast";

const socket = inject('socket')
const axios = inject('axios')
const router = useRouter()
const toast = useToast()
const userStore = useUserStore()
let isAdminUser = ref(window.location.pathname.split('/')[1] === "admin")

const props = defineProps({
  id: {
    type: Number,
    default: null
  }
})

const newUser = () => {
  return {
    id: null,
    user_type: 'V',
    phone_number: '',
    old_password: '',
    password: '',
    confirm_password: '',
    name: '',
    email: '',
    old_confirmation_code: '',
    confirmation_code: '',
    confirm_confirmation_code: '',
    photo_url: null,
  }
}

const user = ref(newUser())
const errors = ref(null)
const isRegister = ref(false)

const loadUser = async (value) => {
  errors.value = null
  isRegister.value = value === '/register';
  if (isRegister.value) {
    // atribui um novo utilizador
    user.value = newUser()
  } else {
    try {
      // carrega o utilizador através do token
      await userStore.loadUser()
      user.value = userStore.user
    } catch (error) {
      console.log("loadUser: ", error)
    }
  }
}

const save = async (userToSave) => {
  errors.value = null
  if (isAdminUser.value) {
    try {
      const response = await axios.put('/admins/' + userToSave.id, userToSave)
      if (response.status === 200) {
        user.value = response.data.data
        toast.add({
          severity: 'success',
          summary: 'Success',
          detail: `User #${user.value.id} editado com sucesso!`,
          life: 3000
        });
        await router.push({name: 'AdminDashboard'})
      }
    } catch (error) {
      if (error.response.status === 422) {
        errors.value = error.response.data.errors
        toast.add({
          severity: 'error',
          summary: 'Erro',
          detail: 'Ocorreu um problema por erros de validação!',
          life: 3000
        });
      } else {
        toast.add({
          severity: 'error',
          summary: 'Erro',
          detail: 'Erro no servidor',
          life: 3000
        });
      }
    }
  } else {

    if (isRegister.value) {
      try {
        const response = await axios.post('/vcards', userToSave)
        if (response.status === 200) {
          user.value = response.data.vCard
          toast.add({
            severity: 'success',
            summary: 'Sucesso',
            detail: `VCard #${user.value.phone_number} registado com sucesso!`,
            life: 3000
          });
          await userStore.login({username: user.value.phone_number, password: userToSave.password})
          await router.push({name: 'Dashboard'})
        }
      } catch (error) {
        if (error.response.status === 422) {
          errors.value = error.response.data.errors
          toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: 'Ocorreu um problema por erros de validação!',
            life: 3000
          });
        } else {
          toast.add({
          severity: 'error',
          summary: 'Erro',
          detail: error.response.data.message,
          life: 3000
        });
        }
      }
    } else {
      // EDITAR
      try {
        const response = await axios.put('/vcards/' + userToSave.id, userToSave)
        if (response.status === 200) {
          user.value = response.data.data
          toast.add({
            severity: 'success',
            summary: 'Sucesso',
            detail: `VCard #${user.value.id} editado com sucesso!`,
            life: 3000
          });
          await router.push({name: 'Dashboard'})
        }
      } catch (error) {
        if (error.response.status === 422) {
          errors.value = error.response.data.errors
          toast.add({
            severity: 'error',
            summary: 'Erro',
            detail: 'Ocorreu um problema por erros de validação!',
            life: 3000
          });
        } else {
          toast.add({
          severity: 'error',
          summary: 'Erro',
          detail: error.response.data.message,
          life: 3000
        });
        }
      }
    }
  }
}

const cancel = () => {
  router.back()
}

watch(
    () => window.location.pathname,
    (newValue) => {
      loadUser(newValue)
    },
    {immediate: true}
)

</script>

<template>
  <user-detail :user="user" :errors="errors" :inserting="isRegister" @save="save" @cancel="cancel"></user-detail>
</template>
