<script setup>
import { inject, watch, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast';
import { useUserStore } from '../../stores/user'

const router = useRouter()
const axios = inject('axios')
const toast = useToast()
const userStore = useUserStore()
const currentRouteName = ref('')
let isAdminLogin = ref(window.location.pathname.split('/')[1] == "admin")


const logout = async () => {
  try {
    await userStore.logout()
    delete axios.defaults.headers.common.Authorization
    router.push({ name: 'Login' })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'There was a problem logging out of the application!',
      life: 3000
    });
  }
}

let redirectRoute = ((route) => {
  router.push({ name: route })
})

const goTo = async (routeName) => {
  router.push({ name: routeName })
}

watch(() => router.currentRoute.value.name, (newName) => {
  currentRouteName.value = newName;
});

</script>
<template>
  <div id="navbar">
    <div class="navbar-options">
      <img src="../../assets/images/v-card-logo.png" @click="() => redirectRoute('Dashboard')">
      <div class="option-item" :class="{ 'active': currentRouteName === 'AdminDashboard' }" @click="goTo('AdminDashboard')" v-if="isAdminLogin">
        Estatísticas
      </div>
      <div class="option-item" :class="{ 'active': currentRouteName === 'AdminData' }" @click="goTo('AdminData')" v-if="isAdminLogin">
        Dados da Aplicação
      </div>

    </div>

    <div class="flex user-settings">
      <router-link class="username" :to="{ name: isAdminLogin ? 'AdminSettings' : 'UserSettings' }"> {{ userStore.user?.name }}</router-link>
      <i class="pi pi-sign-out" style="font-size: 1rem" @click="logout"></i>
    </div>
  </div>
</template>

<style scoped>
#navbar {
  width: 100%;
  padding: 12px 70px;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  position: fixed;
  z-index: 99;
  background-color: #F5F5F5;
}

#navbar .navbar-options {
  display: flex;
  align-items: center;
  gap: 32px;
}

#navbar .navbar-options .option-item {
  cursor: pointer;
  padding: 12px;
  border-radius: 8px;
}

#navbar .navbar-options .option-item:hover,
#navbar .navbar-options .option-item.active {
  background-color: #f16758;
  color: #f5f5f5;
}

#navbar .user-settings {
  gap: 12px;
  align-items: center;
}

#navbar .user-settings i {
  font-size: 24px;
  cursor: pointer;
}

#navbar .user-settings .username {
  color: #f16758;
  text-decoration: none;
}

#navbar .user-settings .username:hover {
  text-decoration: underline;
}

#navbar img {
  width: 50px;
  cursor: pointer;
}
</style>
