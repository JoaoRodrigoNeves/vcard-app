<script setup>
import { ref, onMounted, inject } from 'vue';
import Sidebar from 'primevue/sidebar';
import RadioButton from 'primevue/radiobutton';
import Calendar from 'primevue/calendar';
import InputMask from 'primevue/inputmask';
import Checkbox from 'primevue/checkbox';
import { useUserStore } from '@/stores/user'
import Transactions from '@/components/transactions/Transactions.vue'
import LoadingSpinner from '@/components/global/LoadingSpinner.vue'
import Button from 'primevue/button';

const userStore = useUserStore()
const axios = inject('axios')
const isLoading = ref(false)

let isAdminLogin = ref(window.location.pathname.split('/')[1] == "admin")
let sidebarOderFilter = ref(false)
let isOrderActive = ref(false)
let currentDate = ref(new Date())
let transactionFilters = ref({
    orderBy: "lowDate",
    startDateFilter: null,
    endDateFilter: null,
    phoneNumberInput: '',
    isDebitTransactions: true,
    isCreditTransactions: true,
    categories: []
})

let transactions = ref([]);
let transactionsCategories = ref([]);

const loadTransactions = async () => {
    try {
        isLoading.value = true
        const filteredParams = Object.fromEntries(
            Object.entries(transactionFilters.value).filter(([key, value]) => value !== null && value != '')
        );
        await axios.get('vcards/' + userStore.userId + '/transactions/filtered', {
            params: filteredParams
        }).then(response => {
            transactions.value = response.data.transactions;
            isLoading.value = false;
        })
    } catch (error) {
        isLoading.value = false;
        console.log(error)
    }
}

const loadTransactionsCategories = async () => {
    try {
        await axios.get('vcards/' + userStore.userId + '/transactions/categories').then(response => {
            transactionsCategories.value = response.data.transactionsCategories;
        })
    } catch (error) {
        console.log(error)
    }
}

const orderbyOrFilterHasBeenUpdated = async () => {
    updateTransactionHistory();
    sidebarOderFilter.value = false;
}

const resetFilters = async () => {
    transactionFilters.value = {
        orderBy: "lowDate",
        startDateFilter: null,
        endDateFilter: null,
        phoneNumberInput: '',
        isDebitTransactions: true,
        isCreditTransactions: true,
        categories: []
    }
    updateTransactionHistory()
}

const updateTransactionHistory = async () => {
    await loadTransactionsCategories();
    await loadTransactions();
}




onMounted(async () => {
    updateTransactionHistory()
})

</script>

<template>
    <Sidebar v-model:visible="sidebarOderFilter" header="Right Sidebar" position="right">
        <div class="sidebar-container">
            <div v-if="isOrderActive" class="sidebar-order-container">
                <span>Ordenar por:</span>
                <div class="order-item">
                    <RadioButton v-model="transactionFilters.orderBy" @update:modelValue="orderbyOrFilterHasBeenUpdated"
                        inputId="lowValue" name="lowValue" value="lowValue" />
                    Valor mais alto
                </div>
                <div class="order-item">
                    <RadioButton v-model="transactionFilters.orderBy" @update:modelValue="orderbyOrFilterHasBeenUpdated"
                        inputId="highValue" name="highValue" value="highValue" />
                    Valor mais baixo
                </div>
                <div class="order-item">
                    <RadioButton v-model="transactionFilters.orderBy" @update:modelValue="orderbyOrFilterHasBeenUpdated"
                        inputId="lowDate" name="lowDate" value="lowDate" />
                    Data mais recente
                </div>
                <div class="order-item">
                    <RadioButton v-model="transactionFilters.orderBy" @update:modelValue="orderbyOrFilterHasBeenUpdated"
                        inputId="highDate" name="highDate" value="highDate" />
                    Data mais antiga
                </div>
            </div>
            <div v-else class="sidebar-filter-container">
                <div style="display: flex;flex-direction: column;gap: 12px">
                    Filtrar por:
                    <Button label="Repor Filtros" class="btn-main" raised @click="resetFilters"></Button>
                </div>

                <div class="filter-group-item">
                    <div class="filter-item">
                        <label>Data inicio</label>
                        <Calendar v-model="transactionFilters.startDateFilter" dateFormat="dd-mm-yy"
                            @update:modelValue="orderbyOrFilterHasBeenUpdated"
                            :maxDate="transactionFilters.endDateFilter || currentDate" :manualInput="false"
                            class="input-style" />
                    </div>
                    <div class="filter-item">
                        <label>Data fim</label>
                        <Calendar v-model="transactionFilters.endDateFilter" dateFormat="dd-mm-yy"
                            @update:modelValue="orderbyOrFilterHasBeenUpdated" :minDate="transactionFilters.startDateFilter"
                            :maxDate="currentDate" :manualInput="false" class="input-style" />
                    </div>
                </div>
                <div class="filter-group-item">
                    <div class="filter-item">
                        <label>Telemóvel</label>
                        <InputMask class="input-style" v-model="transactionFilters.phoneNumberInput"
                            @update:modelValue="orderbyOrFilterHasBeenUpdated" date="phone" mask="999999999" slotChar=""
                            v-if="!isAdminLogin" />
                    </div>
                </div>
                <div class="filter-group-item">
                    <div class="filter-item">
                        <label>Tipo de Transação</label>
                        <div class="checkbox-transaction-container">
                            <div class="checkbox-item">
                                <Checkbox v-model="transactionFilters.isCreditTransactions"
                                    @update:modelValue="orderbyOrFilterHasBeenUpdated" :binary="true" />
                                <label>Crédito</label>
                            </div>
                            <div class="checkbox-item">
                                <Checkbox v-model="transactionFilters.isDebitTransactions"
                                    @update:modelValue="orderbyOrFilterHasBeenUpdated" :binary="true" />
                                <label>Débito</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="filter-group-item">
                    <div class="filter-item" v-if="transactionsCategories">
                        <label>Categorias</label>
                        <div class="checkbox-item" v-for="transactionsCategory in transactionsCategories">
                            <Checkbox v-model="transactionFilters.categories"
                                @update:modelValue="orderbyOrFilterHasBeenUpdated" :key="transactionsCategory.id"
                                :value="transactionsCategory.id" />
                            <label>{{ transactionsCategory.name }}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </Sidebar>
    <div class="transaction-history-container">
        <div class="transaction-history-header">
            <div>Histórico de Transações</div>
            <div>
                <i class="pi pi-filter" style="font-size: 1.5rem"
                    @click="sidebarOderFilter = true; isOrderActive = false"></i>
                <i class="pi pi-sort-amount-up-alt" style="font-size: 1.5rem"
                    @click="sidebarOderFilter = true; isOrderActive = true"></i>
            </div>
        </div>
        <div class="transactions-container" v-if="!isLoading && transactions.length > 0">
            <transactions :transactions="transactions" @updateTransactions="loadTransactions"></transactions>
        </div>
        <div class="no-transactions" v-if="(!transactions || transactions.length == 0) && !isLoading">
            Não tem transações registadas
        </div>
        <div class="loading-container" v-if="isLoading">
            <LoadingSpinner></LoadingSpinner>
        </div>
    </div>
</template>
<style scoped>
.sidebar-container {
    padding: 12px;
}

.sidebar-container .sidebar-order-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-container .sidebar-filter-container {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

.sidebar-container .sidebar-order-container span:first-child,
.sidebar-container .sidebar-filter-container span:first-child {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
}

.sidebar-container .sidebar-order-container .order-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.sidebar-container .sidebar-filter-container .filter-group-item {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-container .sidebar-filter-container .filter-item .checkbox-transaction-container {
    display: flex;
    align-items: center;
    gap: 50px;
}

.sidebar-container .sidebar-filter-container .filter-item .checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-container .sidebar-filter-container .filter-item .checkbox-item label {
    opacity: 1;
    text-transform: capitalize;
}


.sidebar-container .sidebar-filter-container .filter-group-item .filter-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.sidebar-container .sidebar-filter-container .filter-item label {
    opacity: 0.7;
    font-size: 14px;
}

.transaction-history-container {
    height: 100%;
}

.transaction-history-container .transaction-history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.transaction-history-container .transaction-history-header div:first-child {
    font-size: 20px;
    opacity: 0.9;
}

.transaction-history-container .transaction-history-header div:last-child {
    display: flex;
    gap: 16px;
    opacity: 0.7;
}

.transaction-history-container .transaction-history-header div:last-child i {
    cursor: pointer;
}

.transaction-history-container .transactions-container {
    padding-top: 50px;
}

.no-transactions {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 300px;
    opacity: 0.7;
}
</style>