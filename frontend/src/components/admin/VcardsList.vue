<script setup>
import { ref, onMounted, inject } from 'vue'

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import moment from 'moment'
import { FilterMatchMode } from 'primevue/api';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import CreateTransaction from '../modals/CreateTransaction.vue';
import { useConfirm } from "primevue/useconfirm";
import { debounce } from 'lodash';

const axios = inject('axios')

const emit = defineEmits(['createTransaction', 'editUserMaxDebit', 'changeVCardStatus', 'deleteVcard', 'toggleEditMaxDebitModal','toggleCreateTransaction', 'closeModal', 'updateVCards'])
const confirm = useConfirm();

const props = defineProps({
    vcards: {
        type: Object,
        required: true
    },
    wantCreateTransaction: {
        type: Boolean,
        required: true
    },
    wantUpdateVCard: {
        type: Boolean,
        required: true
    },
    isLoading: {
        type: Boolean,
        required: true
    },
    numberRows: {
        required: true
    },
})
const inputTextValue = ref('')
const tableEvent = ref(null)

const userMaxDebit = ref(null)
const userToEdit = ref()
const transactionCategories = ref([]);
const userToCreateTransaction = ref(null)

const loadTransactionCategories = async () => {
    try {
        await axios.get('defaultcategories').then(response => {
            transactionCategories.value = response.data.defaultCategories.filter(e => e.type == 'C')
        })
    } catch (error) {
        console.log(error)
    }
}

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(value);
}

const formatDate = (value) => {
    return moment(String(value)).format('DD/MM/YYYY')
}

const loadVCards = async (event) => {
    if(event){
        tableEvent.value = event
    }
    emit('updateVCards', tableEvent.value, inputTextValue.value);
};

const loadCreateTransaction = async (user) => {
    loadTransactionCategories();
    userToCreateTransaction.value = user;
    emit('toggleCreateTransaction');
}

const createTransactionToDashboard = async (phoneNumber, transaction) => {
    emit('createTransaction', phoneNumber, transaction);
}

const editUser = (user) => {
    emit('toggleEditMaxDebitModal');
    userMaxDebit.value = parseFloat(user.max_debit)
    userToEdit.value = user
}

const deleteUser = async (user) => {
    confirm.require({
        message: 'Tem a certeza que pretende apagar o vcard #' + user.phone_number + ' ?',
        header: 'Apagar VCard',
        rejectLabel: 'Não',
        acceptLabel: 'Sim',
        accept: async () => {
            emit('deleteVcard', user);
        }
    });
}

const editMaxDebit = (phoneNumber) => {
    emit('editUserMaxDebit', phoneNumber, userMaxDebit.value);
}

const changeVCardStatus = (vcard) => {
    emit('changeVCardStatus', vcard);
}

const closeEditMaxDebitModal = () => {
    emit('toggleEditMaxDebitModal');
}

const toggleCreateTransaction = () => {
    emit('toggleCreateTransaction');
}

const filterByInputSearch = debounce(() => {
    loadVCards();
}, 300);

//toggleCreateTransaction
</script>
<template>
    <Dialog  :draggable="false" v-if="userToEdit" :visible="props.wantUpdateVCard" @update:visible="closeEditMaxDebitModal" modal header="Editar Utilizador"
        :style="{ width: '30rem' }">
        <div class="flex modal-edit-container">
            <div style="text-align: center;">{{ userToEdit.name + " - " + userToEdit.phone_number }}</div>
            <div class="input-container">
                <label>Débito Máximo</label>
                <InputNumber type="text" class="input-style" v-model="userMaxDebit" placeholder="Débito Máximo"
                    mode="currency" currency="EUR" locale="pt-PT" :class="{ 'input-style-error': !userMaxDebit }" />
                <span class="input-error" v-if="!userMaxDebit">Campo
                    obrigatório.</span>
            </div>
            <Button label="Editar Máximo Débito" class="btn-main" raised :loading="isLoading" 
                @click="editMaxDebit(userToEdit.phone_number)" />
        </div>
    </Dialog>
    <create-transaction :user="userToCreateTransaction" :categories="transactionCategories"
        :wantCreateTransaction="props.wantCreateTransaction" :isLoading="isLoading" @toggleCreateTransaction="toggleCreateTransaction"
        @createTransaction="createTransactionToDashboard"></create-transaction>
    <DataTable :totalRecords="props.numberRows" :onLazyLoad="loadVCards" lazy @page="loadVCards($event)"
        @sort="loadVCards($event)" @filter="loadVCards($event)" :value="props.vcards" sortField="created_at" :sortOrder="-1" scrollable :rowsPerPageOptions="[5, 10, 20, 50]"
        v-model:filters="filters" paginator :rows="5" dataKey="phone_number" filterDisplay="row" :loading="isLoading"
        tableStyle="min-width: 50rem"
        :globalFilterFields="['phone_number', 'name', 'email', 'balance', 'max_debit', 'created_at']">
        <template #header>
            <div class="flex justify-content-end">
                <span class="p-input-icon-left">
                    <i class="pi pi-search" />
                    <InputText class="input-style" v-model="inputTextValue" @input="filterByInputSearch" placeholder="Pesquisar utilizador" />
                </span>
            </div>
        </template>
        <template #empty> Não existem utilizadores registados.</template>
        <template #loading> A carregar utilizadores...</template>
        <Column field="photo_url" :sortable="false" style="width: 5%">
            <template #body="{ data }">
                <div class="status-container">
                    <div class="inactive-table-row" v-if="data.isBlocked"></div>
                    <div class="active-table-row" v-if="!data.isBlocked"></div>
                    <img :src="'/storage/fotos/' + data.photo_url" class="name-circle-user" v-if="data.photo_url" />
                    <div v-if="!data.photo_url" class="name-circle-user bg-main">
                        {{ data.name[0] + (data.name.split(" ").length > 1 ? data.name.split(" ")[1][0] : '') }}
                    </div>
                </div>

            </template>
        </Column>
        <Column field="phone_number" header="Telemóvel" sortable style="width: 20%"></Column>
        <Column field="name" header="Nome" sortable style="width: 20%"></Column>
        <Column field="email" header="Email" sortable style="width: 20%"></Column>
        <Column field="balance" header="Saldo" sortable style="width: 20%">
            <template #body="{ data }">
                {{ formatCurrency(data.balance) }}
            </template>
        </Column>
        <Column field="max_debit" header="Max Débito" sortable style="width: 20%">
            <template #body="{ data }">
                {{ formatCurrency(data.max_debit) }}
            </template>
        </Column>
        <Column field="created_at" header="Data de Criação" sortable style="width: 20%">
            <template #body="{ data }">
                {{ formatDate(data.created_at) }}
            </template>
        </Column>
        <Column header="Ações" :sortable="false" style="width: 20%">
            <template #body="{ data }">
                <div class="table-action-container">
                    <span @click="!data.isBlocked ? loadCreateTransaction(data) : '';" :class="{ 'action-disabled': data.isBlocked }"
                        v-tooltip.top="!data.isBlocked ? 'Criar Transação': 'vCard bloqueado'"><i class="pi pi-send" /></span>
                    <span @click="editUser(data)" v-tooltip.top="'Editar Débito Máximo'"><i
                            class="pi pi-pencil" /></span>
                    <span @click="changeVCardStatus(data)"
                        v-tooltip.top="data.isBlocked ? 'Desbloquear VCard' : 'Bloquear VCard'"><i class="pi"
                            :class="{ 'pi-lock': !data.isBlocked, 'pi-lock-open': data.isBlocked }" /></span>
                    <span @click="data.balance == 0 ? deleteUser(data) : ''"
                        :class="{ 'action-disabled no-events': data.balance > 0 }" v-tooltip.top="'Eliminar VCard'"><i
                            class="pi pi-trash" /></span>
                </div>
            </template>
        </Column>
    </DataTable>
</template>

<style scoped>
.status-container {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 8px;
}

.active-table-row {
    background-color: green;
    width: 10px;
    height: 10px;
    border-radius: 50px;
}

.inactive-table-row {
    background-color: red;
    width: 10px;
    height: 10px;
    border-radius: 50px;
}

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

.table-action-container .action-disabled, .table-action-container .action-disabled:hover {
    cursor: not-allowed;
    background-color: #f167584b;
}

.table-action-container .no-events{
    pointer-events: none;

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
}</style>
