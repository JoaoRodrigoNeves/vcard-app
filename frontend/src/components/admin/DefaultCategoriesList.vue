<script setup>
import { ref } from 'vue'

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import { FilterMatchMode } from 'primevue/api';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import { useConfirm } from "primevue/useconfirm";
import CreateUpdateCategory from '../modals/createUpdateCategory.vue';
const confirm = useConfirm();

const emit = defineEmits(['createOrUpdate', 'delete', 'toggleCreateOrUpdateCategory'])

const props = defineProps({
  categories: {
    type: Object,
    required: true
  },
  wantCreateOrUpdateCategory: {
    type: Boolean,
    required: true
  },
  isLoading: {
    type: Boolean,
    required: true
  }
})

const category = ref({
    id: -1,
    name: '',
    debit: true,
    credit: true,
    errors: false
})

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});
const deleteCategory = async (category) => {
    let catType = ref(category.type == 'D' ? 'Débito' : 'Crédito')
    confirm.require({
        message: 'Tem a certeza que pretende apagar a categoria ' + category.name + ' (' + catType.value + ') ?',
        header: 'Apagar Categoria',
        rejectLabel: 'Não',
        acceptLabel: 'Sim',
        accept: async () => {
            emit('delete', category.id);
        }
    });
}

const editCategory = (category) => {
    emit('createOrUpdate', category);
}

const editCategoryModal = (cat) => {
    category.value = cat;
    emit('toggleCreateOrUpdateCategory')
}

const toggleCreateOrUpdateCategory = () => {
    emit('toggleCreateOrUpdateCategory')
    
    category.value = {
        id: -1,
        name: '',
        debit: true,
        credit: true,
        errors: null
    }
}

</script>
<template>
    <create-update-category v-if="props.wantCreateOrUpdateCategory" :category="category" :isLoading="isLoading" :wantCreateOrUpdateCategory="props.wantCreateOrUpdateCategory" @createOrUpdate="editCategory" @toggleCreateOrUpdateCategory="toggleCreateOrUpdateCategory"></create-update-category>
    <DataTable :value="props.categories" sortField="name" :sortOrder="1" scrollable :loading="isLoading" :rowsPerPageOptions="[5, 10, 20, 50]"
        v-model:filters="filters" paginator :rows="5" dataKey="phone_number" filterDisplay="row"
        tableStyle="min-width: 50rem" :globalFilterFields="['name', 'type']">
        <template #header>
            <div class="flex justify-content-end gap-4">
                <span class="p-input-icon-left">
                    <i class="pi pi-search" />
                    <InputText class="input-style" v-model="filters['global'].value" placeholder="Pesquisar categoria" />
                </span>
                <Button :loading="isLoading" label="Criar Categoria" class="btn-main"
                    @click="toggleCreateOrUpdateCategory()" />
            </div>
        </template>
        <template #empty> Não existem categorias registadas.</template>
        <template #loading> A carregar categorias...</template>
        <Column field="photo_url" :sortable="false" style="width: 5%">
            <template #body="{ data }">
                <div class="name-circle-user bg-main">
                    {{ data.name[0] + (data.name.split(" ").length > 1 ? data.name.split(" ")[1][0] : '') }}
                </div>
            </template>
        </Column>
        <Column field="name" header="Nome" sortable style="width: 30%"></Column>
        <Column field="type" header="Tipo" sortable style="width: 30%">
            <template #body="{ data }">
                {{ data.type == "C" ? "Crédito" : "Débito" }}
            </template>
        </Column>
        <Column header="Ações" :sortable="false" style="width: 5%">
            <template #body="{ data }">
                <div class="table-action-container">
                    <span @click="editCategoryModal(data);" v-tooltip.top="'Editar Categoria'"><i class="pi pi-pencil" /></span>
                    <span @click="deleteCategory(data)" v-tooltip.top="'Eliminar Categoria'"><i
                            class="pi pi-trash" /></span>
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
