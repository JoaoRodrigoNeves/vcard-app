<script setup>
import { ref } from 'vue'
import { debounce } from 'lodash';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import moment from 'moment'
import { FilterMatchMode } from 'primevue/api';
import Calendar from 'primevue/calendar';

const emit = defineEmits(['updateTransactions'])

const props = defineProps({
    transactions: {
        type: Object,
        required: true
    },
    transactionCategories: {
        type: Object,
        required: false
    },
    isLoading: {
        type: Boolean,
        required: false
    },
    numberRows: {
        required: true
    },
})
const inputTextValue = ref('')
const tableEvent = ref(null)
const currentDate = ref(new Date())
const transactionDateFilter = ref({
    startDate: null,
    endDate: null
})

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(value);
}

const formatDateTime = (value) => {
    return moment(String(value)).format('DD/MM/YYYY HH:mm:ss')
}

const loadTransactions = async (event) => {
    if(event){
        tableEvent.value = event
    }
    emit('updateTransactions', transactionDateFilter.value, tableEvent.value, inputTextValue.value);
};

const filterByInputSearch = debounce(() => {
  loadTransactions();
}, 300);

</script>
<template>
    <DataTable :totalRecords="props.numberRows" :onLazyLoad="loadTransactions" lazy @page="loadTransactions($event)"
        @sort="loadTransactions($event)" @filter="loadTransactions($event)" :value="props.transactions" sortField="datetime"
        :sortOrder="-1" scrollable :rowsPerPageOptions="[5, 10, 20, 50]" v-model:filters="filters" paginator :rows="5"
        dataKey="phone_number" filterDisplay="menu" :loading="isLoading" tableStyle="min-width: 50rem"
        :globalFilterFields="['vcard', 'payment_type', 'payment_reference', 'value', 'datetime']">
        <template #header>

            <div class="flex justify-content-end gap-4">
                <span class="p-input-icon-right">
                    <i class="pi pi-calendar" />
                    <Calendar v-model="transactionDateFilter.startDate" dateFormat="dd-mm-yy"
                        :maxDate="transactionDateFilter.endDate || currentDate" v-on:update:modelValue="loadTransactions"
                        class="input-style" placeholder="Data de Inicio" />
                </span>
                <span class="p-input-icon-right">
                    <i class="pi pi-calendar" />
                    <Calendar v-model="transactionDateFilter.endDate" dateFormat="dd-mm-yy"
                        :minDate="transactionDateFilter.startDate" :maxDate="currentDate"
                        v-on:update:modelValue="loadTransactions" class="input-style" placeholder="Data de Fim" />
                </span>
                <span class="p-input-icon-left">
                    <i class="pi pi-search" />
                    <InputText class="input-style" v-model="inputTextValue" @input.debounce="filterByInputSearch" placeholder="Pesquisar transação" />
                </span>
            </div>
        </template>
        <template #empty> Não existem transações registadas.</template>
        <template #loading> A carregar transações...</template>

        <Column field="vcard" header="Origem" sortable style="width: 20%"></Column>
        <Column field="payment_type" header="Tipo" sortable style="width: 20%"></Column>
        <Column field="type" header="Tipo Transação" sortable style="width: 20%">
            <template #body="{ data }">
                {{ data.type == 'D' ? 'Débito' : 'Crédito' }}
            </template>
        </Column>
        <Column field="payment_reference" header="Destino" sortable style="width: 20%"></Column>
        <Column field="value" header="Valor" sortable style="width: 20%">
            <template #body="{ data }">
                {{ formatCurrency(data.value) }}
            </template>
        </Column>
        <Column field="datetime" filterFi eld="datetime" dataType="date" header="Data de Criação" sortable
            style="width: 20%">
            <template #body="{ data }">
                {{ formatDateTime(data.datetime) }}
            </template>
        </Column>
    </DataTable>
</template>

<style scoped></style>
