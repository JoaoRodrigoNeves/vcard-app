<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import { inject, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import { useUserStore } from '../../stores/user'

const emit = defineEmits(['updateTransactions'])

const isLoading = ref(false)
const isUpdatingCategory = ref(false)
const axios = inject('axios')
const toast = useToast()
const transactionForm = ref({categoryId: null, description: '', transactionId: -1})
const wantUpdateTransaction = ref(false)
const categories = ref([])
const userStore = useUserStore()
const isCreating = ref(false)
const isDeleting = ref(false)

const props = defineProps({
  transactions: {
      type: Object,
      required: true
  },
})

const loadCategories = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('/vcards/' + userStore.userId + '/categories').then(response => {
      if (response.status === 200) {
        categories.value = response.data.categories.D
      }
      isLoading.value = false;
    })
  } catch (error) {
    isLoading.value = false;
  }
}

const editTransactionModel = async (transaction, isUpdatingCategoryValue, isCreatingValue) => {
  isCreating.value = isCreatingValue;
  isUpdatingCategory.value = isUpdatingCategoryValue;
  transactionForm.value.transactionId = transaction.id;
  if (isUpdatingCategoryValue) {
    transactionForm.value.categoryId = transaction.category_id;
    await loadCategories();
  } else {
    transactionForm.value.description = transaction.description;
  }
  wantUpdateTransaction.value = true;
}

const updateTransaction = async () => {
  try {
    const payload = {
      isUpdatingCategory: isUpdatingCategory.value,
      categoryId: transactionForm.value.categoryId,
      description: transactionForm.value.description
    }
    await axios.put('transactions/' + transactionForm.value.transactionId, payload).then(response => {
      isLoading.value = false;
      if (response.status == 200) {
        if(isDeleting.value){
          toast.add({ severity: 'success', summary: 'Sucesso', detail: isUpdatingCategory.value ? 'Categoria da transação apagada com sucesso' : 'Descrição da transação apagada com sucesso', life: 3000 });
        }else{
          toast.add({ severity: 'success', summary: 'Sucesso', detail: isUpdatingCategory.value ? (isCreating.value ? 'Categoria da transação adicionada com sucesso' : 'Categoria da transação alterada com sucesso') : (isCreating.value ? 'Descrição da transação adicionada com sucesso' : 'Descrição da transação alterada com sucesso'), life: 3000 });
        }
        emit('updateTransactions');
        isUpdatingCategory.value = false;
        wantUpdateTransaction.value = false;
        isDeleting.value = false;
        isCreating.value = false;
        transactionForm.value = {categoryId: null, description: '', transactionId: -1}
      }
    })
  } catch (error) {
    isLoading.value = false;
    //category.value.errors.push(error.data)
    console.log(error)
  }
}

const deleteCategoryTransaction = (transaction) => {
  isDeleting.value = true;
  isUpdatingCategory.value = true;
  transactionForm.value.transactionId = transaction.id;
  updateTransaction();
}

const deleteDescriptionTransaction = (transaction) => {
  isDeleting.value = true;
  isUpdatingCategory.value = false;
  transactionForm.value.transactionId = transaction.id;
  updateTransaction();
}



const formatCurrency = (value) => {
  return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'EUR' }).format(value);
}
</script>

<template>

  <Dialog :draggable="false" v-model:visible="wantUpdateTransaction" modal
          :header="isUpdatingCategory ? (isCreating ? 'Adicionar Categoria' : 'Editar Categoria') : (isCreating ? 'Adicionar Descrição' : 'Editar Descrição')"
          :style="{ width: '30rem' }">
    <div class="flex modal-edit-container">
      <div class="input-container" v-if="!isUpdatingCategory">
        <label>Descrição</label>
        <InputText type="text" class="input-style" v-model="transactionForm.description" />
      </div>
      <div class="input-container" v-if="isUpdatingCategory">
        <label>Categoria</label>
        <Dropdown v-model="transactionForm.categoryId" showClear :options="categories" optionLabel="name"
                  optionValue="id" placeholder="Selecione uma categoria" class="w-full" />
      </div>
      <Button :label="isCreating ? 'Adicionar' : 'Editar'" :loading="isLoading" class="btn-main" raise @click="updateTransaction" />
    </div>

  </Dialog>
    <Accordion :lazy="true" >
        <AccordionTab v-for="transaction in transactions" :key="transaction.id" :headerClass="(transaction.new_balance - transaction.old_balance) > 0 ? 'transaction-positive' : 'transaction-negative'">
            <template #header>
                <div class="accordion-header">
                    <span class="font-bold white-space-nowrap">{{ formatCurrency(transaction.new_balance -
                        transaction.old_balance) }}</span>
                    <div class="acordion-header-info">
                        <div class="font-bold white-space-nowrap v-card-name mb-2">{{ transaction.datetime }}</div>
                        <div class="font-bold white-space-nowrap">{{ transaction.payment_reference }}</div>
                    </div>
                </div>
            </template>

            <div class="accordion-body">
                <div class="transaction-info-item">
                    <label>Tipo de Pagamento</label>
                    <span>{{ transaction.payment_type }}</span>
                </div>
                <div class="transaction-info-item">
                    <label>Tipo</label>
                    <span>{{ transaction.type == 'C' ? 'Crédito' : 'Débito' }}</span>
                </div>
                <div class="transaction-info-item">
                    <label>Saldo Antigo</label>
                    <span>{{ formatCurrency(transaction.old_balance) }}</span>
                </div>
                <div class="transaction-info-item">
                    <label>Saldo Novo</label>
                    <span>{{ formatCurrency(transaction.new_balance) }}</span>
                </div>
                <div class="transaction-info-item">
                    <label>Data</label>
                    <span>{{ transaction.datetime }}</span>
                </div>
                <div class="transaction-info-item">
                    <label>Categoria</label>
                    <div class="transaction-info-with-actions">
                        <span :class="{ 'no-field': !transaction.category_name }">{{ transaction.category_name ?
                            transaction.category_name : 'Sem categoria' }}</span>
                      <span v-if="!transaction.category_id" @click="editTransactionModel(transaction, true, true)" class="icon-edit"
                            v-tooltip.top="'Adicionar Categoria'"><i class="pi pi-plus" /></span>
                      <span v-if="transaction.category_id" @click="editTransactionModel(transaction, true, false)" v-tooltip.top="'Editar Categoria'"><i class="pi pi-pencil" /></span>
                      <span v-if="transaction.category_id" @click="deleteCategoryTransaction(transaction)"
                            class="icon-edit" v-tooltip.top="'Eliminar Categoria'"><i class="pi pi-trash" /></span>
                    </div>
                </div>
                <div class="transaction-info-item">
                    <label>Descrição</label>
                    <div class="transaction-info-with-actions">
                        <span class="no-capitalize" :class="{ 'no-field': !transaction.description }">{{ transaction.description ?
                            transaction.description
                            : 'Sem descrição' }}</span>
                      <span v-if="!transaction.description" @click="editTransactionModel(transaction, false, true)" class="icon-edit"
                            v-tooltip.top="'Adicionar Descrição'"><i class="pi pi-plus" /></span>
                        <span v-if="transaction.description" @click="editTransactionModel(transaction, false, false)" class="icon-edit"
                            v-tooltip.top="'Editar Descrição'"><i class="pi pi-pencil" /></span>
                        <span v-if="transaction.description" @click="deleteDescriptionTransaction(transaction)"
                            class="icon-edit" v-tooltip.top="'Eliminar Descrição'"><i class="pi pi-trash" /></span>
                    </div>
                </div>
            </div>
        </AccordionTab>
    </Accordion>
</template>
<style scoped>
.accordion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.accordion-header span {
    font-size: 20px;
}

.accordion-header .acordion-header-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: end;
    width: 100%;
}

.accordion-body {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    padding: 24px;
    gap: 20px;
}

.accordion-body .transaction-info-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: calc(50% - 10px);
}

.accordion-body .transaction-info-item .no-field {
    font-style: italic;
    opacity: 0.6;
}

.accordion-body .transaction-info-item .no-capitalize {
  text-transform: none;
}

.accordion-body .transaction-info-item label {
    opacity: 0.7;
}

.accordion-body .transaction-info-item span {
    font-size: 18px;
    text-transform: capitalize;
}

.accordion-body .transaction-info-item .transaction-info-with-actions {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px;
}

.accordion-body .transaction-info-item .transaction-info-with-actions .icon-edit:hover {
    cursor: pointer;
    color: #f16758;
}

.accordion-body .transaction-info-item .transaction-info-with-actions span:first-child {
    margin-right: 10px;
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
}.modal-edit-container {
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
