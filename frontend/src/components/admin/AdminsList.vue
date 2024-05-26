<script setup>
import { ref } from 'vue'

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import moment from 'moment'
import { FilterMatchMode } from 'primevue/api';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useConfirm } from "primevue/useconfirm";

const emit = defineEmits(['create', 'delete', 'toggleCreateAdminModal'])

const props = defineProps({
    admins: {
        type: Object,
        required: true
    },
    wantsAddAdmin: {
        type: Boolean,
        required: true
    },
    isLoading: {
        type: Boolean,
        required: true
    }
})

const confirm = useConfirm();

const admin = ref({
    name: '',
    email: '',
    errors: []
})
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const addAdmin = async () => {
    emit('create', admin.value);
}

const deleteAdmin = async (admin) => {
    confirm.require({
        message: 'Tem a certeza que pretende apagar o administrador ' + admin.name + ' ?',
        header: 'Apagar Administrador',
        rejectLabel: 'Não',
        acceptLabel: 'Sim',
        accept: async () => {
            emit('delete', admin);
        }
    });
}

const resetAdmin = async () => {
    emit('toggleCreateAdminModal')
    admin.value = {
        name: '',
        email: '',
        errors: []
    };
}

const formatDate = (value) => {
    return moment(String(value)).format('DD/MM/YYYY')
}


const closeAddAdminModal = () => {
    emit('toggleCreateAdminModal');
}

</script>
<template>
    <Dialog :draggable="false" :visible="props.wantsAddAdmin" @update:visible="closeAddAdminModal" modal header="Adicionar Administrador"
        :style="{ width: '30rem' }">
        <div class="flex modal-edit-container">
            <div class="input-container">
                <label>Nome</label>
                <InputText type="text" class="input-style" v-model="admin.name" placeholder="Nome"
                    :class="{ 'input-style-error': admin.errors && admin.errors[0] && admin.errors[0]['name'] }" />
                <span class="input-error" v-if="admin.errors && admin.errors[0] && admin.errors[0]['name']">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container">
                <label>Email</label>
                <InputText type="text" class="input-style" v-model="admin.email" placeholder="Email"
                    :class="{ 'input-style-error': admin.errors && admin.errors[0] && admin.errors[0]['email'] }" />
                <span class="input-error"
                    v-if="admin.errors && admin.errors[0] && admin.errors[0]['email'] && admin.errors[0]['email'][0] != 'The email field must be a valid email address.'">Campo
                    obrigatório.</span>
                <span class="input-error"
                    v-if="admin.errors && admin.errors[0] && admin.errors[0]['email'] && admin.errors[0]['email'][0] === 'The email field must be a valid email address.'">Email
                    inválido.</span>
            </div>
            <Button label="Adicionar Administrador" :loading="props.isLoading" class="btn-main" raised
                @click="addAdmin()" />
        </div>
    </Dialog>
    <DataTable :value="props.admins" sortField="created_at" :sortOrder="-1" scrollable :rowsPerPageOptions="[5, 10, 20, 50]"
        v-model:filters="filters" paginator :rows="5" dataKey="phone_number" filterDisplay="row" :loading="isLoading"
        tableStyle="min-width: 50rem" :globalFilterFields="['name', 'email', 'created_at']">
        <template #header>
            <div class="flex justify-content-end gap-4">
                <span class="p-input-icon-left">
                    <i class="pi pi-search" />
                    <InputText class="input-style" v-model="filters['global'].value"
                        placeholder="Pesquisar administrador" />
                </span>
                <Button :loading="isLoading" label="Adicionar Administrador" class="btn-main"
                    @click="resetAdmin()" />
            </div>
        </template>
        <template #empty> Não existem administradores registados.</template>
        <template #loading> A carregar administradores...</template>
        <Column field="photo_url" :sortable="false" style="width: 5%">
            <template #body="{ data }">
                <div class="name-circle-user bg-main">
                    {{ data.name[0] + (data.name.split(" ").length > 1 ? data.name.split(" ")[1][0] : '') }}
                </div>
            </template>
        </Column>
        <Column field="name" header="Nome" sortable style="width: 20%"></Column>
        <Column field="email" header="Email" sortable style="width: 20%"></Column>
        <Column field="created_at" header="Data de Criação" sortable style="width: 20%">
            <template #body="{ data }">
                {{ formatDate(data.created_at) }}
            </template>
        </Column>
        <Column header="Ações" :sortable="false" style="width: 5%">
            <template #body="{ data }">
                <div class="table-action-container">
                    <span @click="deleteAdmin(data)"><i class="pi pi-trash" /></span>
                </div>
            </template>
        </Column>
    </DataTable>
</template>

<style scoped>
.name-circle-user {
    border-radius: 100px;
    width: 40px;
    height: 40px;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    text-transform: uppercase;
}

.table-action-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
}

.table-action-container span {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 14px;
    background-color: #f16758a9;
    border-radius: 8px;
    color: #f5f5f5;
    cursor: pointer;
}

.table-action-container .action-disabled {
    pointer-events: none;
    background-color: #f167584b;
}

.table-action-container span:hover {
    background-color: #f16758;
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
</style>
