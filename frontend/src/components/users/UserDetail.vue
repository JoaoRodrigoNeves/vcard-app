<script setup>
import {ref, inject, watch, computed} from 'vue';
import InputText from 'primevue/inputtext';
import InputMask from 'primevue/inputmask';
import Button from 'primevue/button';
import avatarNoneUrl from '@/assets/images/avatar-none.png'
import {useUserStore} from "@/stores/user";

const serverBaseUrl = inject('serverBaseUrl')
const userStore = useUserStore()

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  inserting: {
    type: Boolean,
    required: true
  },
  errors: {
    required: true
  }
})
let isAdminUser = ref(window.location.pathname.split('/')[1] == "admin")

const emit = defineEmits(['save'])

const editingUser = ref(Object.assign({}, props.user))
const inputPhotoFile = ref(null)
const editingImageAsBase64 = ref(null)
const deletePhotoOnTheServer = ref(false)

watch(
    () => props.user,
    (newUser) => {
      editingUser.value = Object.assign({}, newUser)
    },
    {immediate: true}
)

const photoFullUrl = computed(() => {
  if (deletePhotoOnTheServer.value) {
    return avatarNoneUrl
  }
  if (editingImageAsBase64.value) {
    return editingImageAsBase64.value
  } else {
    return editingUser.value.photo_url
        ? serverBaseUrl + "/storage/fotos/" + editingUser.value.photo_url
        : avatarNoneUrl
  }
})

const save = () => {
  const userToSave = editingUser.value
  userToSave.deletePhotoOnServer = deletePhotoOnTheServer.value
  userToSave.base64ImagePhoto = editingImageAsBase64.value
  emit('save', userToSave)
}

const loadPhoto = () => {
  document.getElementById('inputPhoto').click()
}

const changePhotoFile = () => {
  try {
    document.getElementById('inputPhoto').click()
    const file = inputPhotoFile.value.files[0]
    if (!file) {
      editingImageAsBase64.value = null
    } else {
      const reader = new FileReader()
      reader.addEventListener(
          'load',
          () => {
            // convert image file to base64 string
            editingImageAsBase64.value = reader.result
            deletePhotoOnTheServer.value = false
          },
          false,
      )
      if (file) {
        reader.readAsDataURL(file)
      }
    }
  } catch (error) {
    editingImageAsBase64.value = null
  }
}

const resetToOriginalPhoto = () => {
  deletePhotoOnTheServer.value = false
  inputPhotoFile.value.value = ''
  changePhotoFile()
}

const cleanPhoto = () => {
  deletePhotoOnTheServer.value = true
  editingImageAsBase64.value = null
}
</script>

<template>
  <div class="container" v-if="editingUser">
    <img src="../../assets/images/v-card-full-logo.png" v-if="inserting">
    <div class="main">
      <div class="flex flex-column form-container">
        <div class="input-container">
          <label for="name">Nome</label>
          <InputText type="text" class="input-style" id="name"
                     v-model="editingUser.name"
                     :class="{ 'input-style-error': errors ? errors.name : false }"/>
          <span class="input-error" v-if=" errors ? errors.name : false">{{ errors.name[0] }}</span>
        </div>
        <div class="input-container">
          <label for="email">Email</label>
          <InputText type="text" class="input-style" id="email"
                     v-model="editingUser.email"
                     :class="{ 'input-style-error': errors ? errors.email : false }"/>
          <span class="input-error"
                v-if="errors ? errors.email : false">{{ errors.email[0] }}</span>
        </div>
        <div class="input-container" v-if="!isAdminUser && inserting">
          <label for="username">Telemóvel</label>
          <InputMask class="input-style" id="username"
                     :disabled="!inserting"
                     v-model="editingUser.username"
                     slotChar=""
                     date="phone"
                     mask="999999999"
                     :class="{ 'input-style-error': errors ? errors.username : false }"/>
          <span class="input-error" v-if="errors ? errors.username : false">{{ errors.username[0] }}</span>
        </div>
        <div class="input-container" v-if="!inserting">
          <label for="password">Password Antiga</label>
          <InputText type="password" class="input-style" id="old_password"
                     v-model="editingUser.old_password"
                     :class="{ 'input-style-error': errors ? errors.old_password : false }"/>
          <span class="input-error" v-if="errors ? errors.old_password : false">{{ errors.old_password[0] }}</span>
        </div>
        <div class="input-container">
          <label for="password">Password</label>
          <InputText type="password" class="input-style" id="password"
                     v-model="editingUser.password"
                     :class="{ 'input-style-error': errors ? errors.password : false }"/>
          <span class="input-error" v-if="errors ? errors.password : false">{{ errors.password[0] }}</span>
        </div>
        <div class="input-container">
          <label for="confirmPassword">Confirmar Password</label>
          <InputText type="password" class="input-style" id="confirmPassword"
                     v-model="editingUser.confirm_password"
                     :class="{ 'input-style-error': errors ? errors.confirm_password : false }"/>
          <span class="input-error"
                v-if="errors ? errors.confirm_password : false">{{ errors.confirm_password[0] }}</span>
        </div>
        <div class="input-container" v-if="!isAdminUser && !inserting">
          <label for="confirmation_code">Código de Confirmação Antigo</label>
          <InputText type="password" class="input-style" id="old_confirmation_code"
                     v-model="editingUser.old_confirmation_code"
                     :class="{ 'input-style-error': errors ? errors.old_confirmation_code : false }"/>
          <span class="input-error"
                v-if="errors ? errors.old_confirmation_code : false">{{ errors.old_confirmation_code[0] }}</span>
        </div>
        <div class="input-container" v-if="!isAdminUser">
          <label for="confirmation_code">Código de Confirmação</label>
          <InputText type="password" class="input-style" id="confirmation_code"
                     v-model="editingUser.confirmation_code"
                     :class="{ 'input-style-error': errors ? errors.confirmation_code : false }"/>
          <span class="input-error"
                v-if="errors ? errors.confirmation_code : false">{{ errors.confirmation_code[0] }}</span>
        </div>
        <div class="input-container" v-if="!isAdminUser">
          <label for="confirm_confirmation_code">Confirmar Código de Confirmação</label>
          <InputText type="password" class="input-style" id="confirm_confirmation_code"
                     v-model="editingUser.confirm_confirmation_code"
                     :class="{ 'input-style-error': errors ? errors.confirm_confirmation_code : false }"/>
          <span class="input-error"
                v-if="errors ? errors.confirm_confirmation_code : false">{{ errors.confirm_confirmation_code[0] }}</span>
        </div>

      </div>
      <div style="display: flex; flex-direction: column; gap: 16px" v-if="!isAdminUser">
        <img :src="photoFullUrl"/>
        <div style="display: flex; flex-direction: column; gap: 16px">
          <Button label="Carregar" class="btn-main" @click="loadPhoto" raised/>
          <Button label="Repor" class="btn-main" raised @click.prevent="resetToOriginalPhoto"
                  v-if="editingUser.photo_url"/>
          <Button label="Apagar" class="btn-main" raised @click.prevent="cleanPhoto"
                  v-show="editingUser.photo_url || editingImageAsBase64"/>
        </div>
      </div>
    </div>
    <div class="buttons-container">
      <Button :label="props.inserting ? 'Registar' : 'Editar Perfil'" class="btn-main" raised @click="save"/>
    </div>
    <router-link :to="{ name: 'Dashboard' }" class="text-main login-hiper" v-if="!userStore.user">Voltar ao Dashboard</router-link>

    </div>
  <input type="file" style="visibility:hidden;" id="inputPhoto" ref="inputPhotoFile" @change.prevent="changePhotoFile"/>
</template>
<style scoped>
.container {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  gap: 16px;
  justify-content: center;
  align-items: center;
}

.container img {
  width: 175px;
  height: 175px;
  object-fit: cover;
  border-radius: 8px;
}

.container .form-container {
  gap: 12px;
  width: 100%;
  max-width: 360px;
}

.container .form-container .input-container {
  display: flex;
  flex-direction: column;
  width: 100%;
}

.container .form-container .input-container .input-error {
  color: rgba(255, 0, 0, 0.80);;
  opacity: 0.8;
  margin-left: 6px;
  margin-top: 2px;
  font-size: 12px;
}

.container .form-container .input-container .input-style-error {
  border-color: rgba(255, 0, 0, 0.80);
}

.container .form-container .input-container label {
  font-size: 14px;
  margin-bottom: 4px;
}

.container .main {
  display: flex;
  flex-direction: row;
  gap: 250px;
  justify-content: center;
  align-items: center;
  width: 100%;
}

.container .buttons-container {
  display: flex;
  flex-direction: row;
  gap: 24px;
  width: 100%;
  max-width: 360px;
  margin-top: 16px;
}

.container .buttons-container button {
  width: 100%;
}


.login-hiper:hover {
  cursor: pointer;
  text-decoration: underline;
}
</style>