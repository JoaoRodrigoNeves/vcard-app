<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import { ref, onMounted, inject, watch } from 'vue'
import { useUserStore } from '../../stores/user'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from "primevue/useconfirm";
import Button from 'primevue/button'
import LoadingSpinner from '@/components/global/LoadingSpinner.vue'
import UserDetail from '@/components/users/UserDetail.vue';
import InputSwitch from 'primevue/inputswitch';
import CreateUpdateCategory from '@/components/modals/createUpdateCategory.vue';
import InputText from 'primevue/inputtext';
import { useRouter } from 'vue-router'

const router = useRouter()
const categories = ref([])
const toast = useToast()
const axios = inject('axios')
const wantCreateOrUpdateCategory = ref(false)
const confirm = useConfirm();
const userStore = useUserStore()
const isLoading = ref(false);
const user = ref(null);
const errors = ref(null);
const isRegister = ref(false);
const wantDeleteVCard = ref(false);

let isAdminUser = ref(window.location.pathname.split('/')[1] == "admin")
const actualSettingsTab = ref(window.location.pathname.split('/')[1] == "admin" ? 'userSettings' : 'default');

const credentials = ref({
    confirmationCode: '',
    password: ''
})

const category = ref({
    id: -1,
    name: '',
    vcard: '',
    debit: true,
    credit: true,
    errors: false
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

const loadCategories = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/vcards/' + userStore.userId + '/categories').then(response => {
            if (response.status == 200) {
                categories.value = response.data.categories
            }
            isLoading.value = false;
        })
        categories.value = response.data.categories
    } catch (error) {
        isLoading.value = false;
    }
}

const createCategory = async (category) => {
    try {
        category.vcard = userStore.userId
        const response = await axios.post('categories', category)
        if (response.status == 201) {
            toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
            loadCategories();
            wantCreateOrUpdateCategory.value = false;
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: error.response.data.message, life: 3000 });
    }
}

const updateCategory = async (category) => {
    try {
        category.vcard = userStore.userId
        const response = await axios.put('categories/' + category.id, category)
        if (response.status == 201) {
            toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
            loadCategories();
            wantCreateOrUpdateCategory.value = false;
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: error.response.data.message, life: 3000 });
    }
}

const deleteCategoryAPI = async (id) => {
    try {
        const response = await axios.delete('categories/' + id)
        if (response.status == 200) {
            toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
            loadCategories();
        }
    } catch (error) {
        category.value.errors.push(error.data)
        console.log(error)
    }
}

const editCategory = (cat) => {
    category.value = cat
    wantCreateOrUpdateCategory.value = true
}

const createOrUpdateCategory = (category) => {
    if (!category.name) {
        category.errors = true
        return;
    }
    if (category.id == -1) {
        createCategory(category)
    } else {
        updateCategory(category)
    }
}

const deleteCategory = async (category) => {

    let catType = ref(category.type == 'D' ? 'Débito' : 'Crédito')
    confirm.require({
        message: 'Tem a certeza que pretende apagar a categoria ' + category.name + ' (' + catType.value + ') ?',
        header: 'Apagar Categoria',
        rejectLabel: 'Não',
        acceptLabel: 'Sim',
        accept: async () => {
            await deleteCategoryAPI(category.id)
            await loadCategories()
        }
    });
}

const deleteVCard = () => {
    wantDeleteVCard.value = true
    confirm.require({
        group: 'headless',
        header: 'Apagar Conta',
        message: 'Tem a certeza que deseja apagar a conta?',
        accept: () => {
            axios.delete('vcards/' + userStore.userId, {
            params: credentials.value
            }).then(response => {
                if (response.status == 200) {
                    wantDeleteVCard.value = false
                    userStore.logout()
                    location.reload()
                }
            }).catch((error) => {
                toast.add({ severity: 'error', summary: 'Erro', detail: error.response.data.message, life: 3000 });
            })
        },
        reject: () => {
            wantDeleteVCard.value = false
        }
    });

}

const changeRounded = async () => {
    try {
        const response = await axios.patch('vcards/' + userStore.userId + '/piggybank/changestatus')
        if (response.status == 201) {
            toast.add({ severity: 'success', summary: 'Sucesso', detail: userStore.wantRounded ? 'Arredondamento por transação ativado' : 'Arredondamento por transação desativado', life: 3000 });
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Erro', detail: "Ocorreu um erro", life: 3000 });
        console.log(error)
    }
}



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
                await router.push({ name: 'AdminDashboard' })
            }
        } catch (error) {
            if (error.response.status === 422) {
                errors.value = error.response.data.errors
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Ocorreu um problema por erros de validação!',
                    life: 3000
                });
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Ocorreu um problema ao editar!',
                    life: 3000
                });
            }
        }
    } else {
        try {
            const response = await axios.put('/vcards/' + userToSave.id, userToSave)
            if (response.status === 200) {
                user.value = response.data.data
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: isAdminUser.value ? 'Administrador atualizado com sucesso' :`VCard #${user.value.id} editado com sucesso!`,
                    life: 3000
                });
                await router.push({ name: 'Dashboard' })
            }
        } catch (error) {
            if (error.response.status === 422) {
                errors.value = error.response.data.errors
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Ocorreu um problema por erros de validação!',
                    life: 3000
                });
            } else {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: error.response.data.message,
                    life: 3000
                });
            }
        }
    }
}

const toggleCreateOrUpdateCategory = () => {
    wantCreateOrUpdateCategory.value = !wantCreateOrUpdateCategory.value;
    category.value = {
        id: -1,
        name: '',
        debit: true,
        credit: true,
        errors: null
    }
}

watch(
    () => window.location.pathname,
    (newValue) => {
        loadUser(newValue)
    },
    { immediate: true }
)

onMounted(async () => {
})

</script>

<template>
    <create-update-category v-if="wantCreateOrUpdateCategory" :category="category" :isLoading="isLoading"
        :wantCreateOrUpdateCategory="wantCreateOrUpdateCategory" @createOrUpdate="createOrUpdateCategory"
        @toggleCreateOrUpdateCategory="toggleCreateOrUpdateCategory"></create-update-category>
    <ConfirmDialog group="headless" :visible="wantDeleteVCard" >
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-column align-items-center p-5 surface-overlay border-round">
                <div
                    class="border-circle bg-primary inline-flex justify-content-center align-items-center h-6rem w-6rem -mt-8 circle-background">
                    <img class="text-5xl" style="width: 70px;" src="/src/assets/images/v-card-full-logo.png">
                </div>
                <span class="font-bold text-2xl block mb-2 mt-4">{{ message.header }}</span>
                <p class="mb-0">{{ message.message }}</p>
                <div class="flex flex-column gap-4 mt-4">
                    <InputText type="password" class="input-style" v-model="credentials.confirmationCode" placeholder="Código de confirmação" /> 
                    <InputText type="password" class="input-style" v-model="credentials.password" placeholder="Password" /> 
                </div>
                <div class="flex align-items-center gap-2 mt-4">
                    <Button label="Sim" @click="acceptCallback" class="w-8rem btn-main"></Button>
                    <Button label="Cancelar" outlined @click="rejectCallback" class="w-8rem"></Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>
    <div class="settings-container">
        <div class="settings-options">
            <div class="settings-option-item" :class="{ 'settings-option-item-active': actualSettingsTab === 'default' }"
                @click="actualSettingsTab = 'default'" v-if="!isAdminUser">
                <i class="pi pi-cog"></i>
                <label class="pointer">Definições Gerais</label>
            </div>
            <div class="settings-option-item"
                :class="{ 'settings-option-item-active': actualSettingsTab === 'userSettings' }"
                @click="actualSettingsTab = 'userSettings'" >
                <i class="pi pi-cog"></i>
                <label class="pointer">Editar Dados Pessoais</label>
            </div>
            <div class="settings-option-item" :class="{ 'settings-option-item-active': actualSettingsTab === 'categories' }" v-if="!isAdminUser"
                @click="loadCategories(); actualSettingsTab = 'categories'">
                <i class="pi pi-tag"></i>
                <label class="pointer">Categorias</label>
            </div>
        </div>
        <div class="default-main-container" v-if="actualSettingsTab === 'default' && !isLoading">
            <div class="default-setting-item">
                <label>Apagar Vcard</label>
                <Button @click="deleteVCard()" icon="pi pi-trash" class="btn-main" />
            </div>
            <div class="default-setting-item">
                <label>Arredondamento conta poupança</label>
                <InputSwitch class="switch" v-model="userStore.wantRounded" @click="changeRounded()" />
            </div>


        </div>
        <div class="categories-main-container" v-if="actualSettingsTab === 'categories' && !isLoading">
            <div class="categories-main-header">
                <Button label="Criar Categoria" class="btn-main" @click="toggleCreateOrUpdateCategory" />
            </div>
            <h3>Crédito</h3>
            <div class="categories-container">
                <div class="categoy-item bg-main" v-for="item in categories.C" :key="item.id" raised>
                    {{ item.name }}
                    <div>
                        <span class="span-body" @click="editCategory(item)"><i class="pi pi-pencil" /></span>
                        <span class="span-body" @click="deleteCategory(item)"><i class="pi pi-trash" /></span>
                    </div>
                </div>
            </div>
            <h3 style="margin-top: 50px;">Débito</h3>
            <div class="categories-container">
                <div class="categoy-item bg-main" v-for="item in categories.D" :key="item.id" raised>
                    {{ item.name }}
                    <div>
                        <span class="span-body" @click="editCategory(item)"><i class="pi pi-pencil" /></span>
                        <span class="span-body" @click="deleteCategory(item)"><i class="pi pi-trash" /></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="user-main-container" v-if="actualSettingsTab === 'userSettings' && !isLoading">
            <user-detail :user="user" :errors="errors" :inserting="isRegister" @save="save"></user-detail>
        </div>
        <div class="loading-container" v-if="isLoading">
            <LoadingSpinner></LoadingSpinner>
        </div>
    </div>
</template>
<style scoped>
.settings-container {
    display: flex;
    height: 100%;
    min-height: calc(100vh - 122px);

}

.settings-container .settings-options {
    display: flex;
    flex-direction: column;
    border-right: 1px solid #0000004f;
    margin-right: 20px;
    padding-right: 20px;
    width: 100%;
    height: auto;
    max-width: 300px;
}

.settings-container .settings-options .settings-option-item {
    padding: 14px 32px;
    border-right: 0;
    border-top: 1px solid #0000001f;
    display: flex;
    align-items: center;
    gap: 12px;
}

.settings-container .settings-options .settings-option-item:first-child {
    border-top: 0;
}

.settings-container .settings-options .settings-option-item:hover,
.settings-container .settings-options .settings-option-item.settings-option-item-active {
    cursor: pointer;
    background-color: #f16758;
    color: #f5f5f5;
    border-color: #f16758;
}

.categories-main-container {
    flex: 1;
    padding-bottom: 20px;
}

.categories-main-container h3 {
    opacity: 0.7;
}

.categories-main-header {
    display: flex;
    flex-direction: row;
    justify-content: end;
    align-items: center;
    padding: 12px 0px;
}

.categories-container {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 20px;
}

.categories-container .categoy-item {
    display: flex;
    padding: 12px;
    border-radius: 8px;
    cursor: default;
    background-color: #f1675817;
    border: 1px solid #f16758;
    color: #f16758;
}

.categories-container .categoy-item div {
    margin-left: 12px;
}

.categories-container .categoy-item i:hover {
    cursor: pointer;
}

.button-body {
    gap: 10px;
    flex-direction: row;
    text-transform: capitalize;
}

.span-body {
    margin-left: 10px;
}

.modal-edit-container {
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 16px;
}

.modal-edit-container .input-container {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.modal-edit-container .input-container label {
    margin-bottom: 8px;
    font-size: 14px;
}

.modal-edit-container .input-container .input-error {
    color: rgba(255, 0, 0, 0.80);
    opacity: 0.8;
    margin-left: 6px;
    margin-top: 2px;
    font-size: 12px;
}

.default-main-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    width: 100%;
    padding-bottom: 20px;
}

.default-main-container .default-setting-item {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    padding: 30px 40px;
    width: 100%;
    border-radius: 16px;
    background-color: #f1675817;
    border: 1px solid #f16758;
    color: #f16758;
}

.user-main-container {
    width: 100%;
    padding-bottom: 20px;

}

.circle-background {
    background-color: white !important;
}
.loading-container{
  margin-top: 200px;
}
</style>
