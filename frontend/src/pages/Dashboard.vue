<!-- eslint-disable vue/multi-word-component-names -->
<script setup>
import { ref, inject, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useUserStore } from '../stores/user';
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber';
import { useRouter } from 'vue-router';
import CreateTransaction from '@/components/modals/CreateTransaction.vue'
import Transactions from '@/components/transactions/Transactions.vue'
import LoadingSpinner from '@/components/global/LoadingSpinner.vue'

const toast = useToast()
const router = useRouter()
const axios = inject('axios')
const socket = inject('socket')
const userStore = useUserStore()

const isLoading = ref(false)
const wantAddOrRemovePiggy = ref(false)
const isAddMoneyOnPiggy = ref(false)
const isAskingMoney = ref(false)
const wantCreateTransaction = ref(false)
const transactions = ref([])
const categories = ref([])

const transactionToPiggy = ref({
  value: null,
  errors: false,
})

const category = ref({
  id: -1,
  name: '',
  debit: true,
  credit: true,
  errors: false
})

const createTransaction = async (transaction) => {
  if (!transaction.reference || !transaction.value) {
    transaction.errors = true
    return;
  }
  try {
    isLoading.value = true;
    const payload = ref({
      vcard: userStore.userId,
      value: transaction.value,
      type: 'D',
      payment_type: transaction.type,
      payment_reference: transaction.reference,
      description: transaction.description,
      confirmationCode: transaction.confirmationCode,
      category: transaction.category,
      isAdmin: false
    })
    const response = await axios.post('/transactions', payload.value)
    socket.emit('newTransaction', response.data.data, false)
    if (response.status === 201) {
      toast.add({
        severity: 'success',
        summary: 'Sucesso',
        detail: "Transação criada com sucesso.",
        life: 3000
      });
      wantCreateTransaction.value = false;
      await loadDashboard()
    }
    isLoading.value = false;

  } catch (error) {
    isLoading.value = false;
    console.log(error)
    toast.add({
      severity: 'error',
      summary: 'Erro',
      detail: error.response.data.message,
      life: 3000
    });
  }
}

const askForMoney = async (transaction) => {
  if (!transaction.reference || !transaction.value) {
    transaction.errors = true
    return;
  }
  try {
    isLoading.value = true
    const payload = ref({
      name: userStore.userName,
      vcard: userStore.userId,
      value: transaction.value,
      payment_type: transaction.type,
      payment_reference: transaction.reference,
      description: transaction.description,
      confirmationCode: transaction.confirmationCode,
      photo_url: userStore.user.photo_url,
      category: null
    })
    socket.emit('newMoneyRequest', payload.value)
    wantCreateTransaction.value = false;
    isLoading.value = false
  } catch (error) {
    isLoading.value = false;
    toast.add({
      severity: 'error',
      summary: 'Erro',
      detail: "Erro ao efetuar pedido",
      life: 3000
    });
  }
}

const addOrRemovePiggy = async (info) => {
  if (!transactionToPiggy.value.value || transactionToPiggy.value.value < 0 || transactionToPiggy.value.value == 0) {
    transactionToPiggy.value.errors = true
    return;
  }
  try {
    isLoading.value = true;

    const payload = ref({
      phone_number: useUserStore().user.id,
      value: transactionToPiggy.value.value,
    })
    if (info == 'AddMoney') {
      let response = await axios.patch('/vcards/' + payload.value.phone_number + '/piggybank/add', payload.value)
      if (response.status == 200) {
        toast.add({
          severity: 'success',
          summary: 'Sucesso',
          detail: "Dinheiro adicionado com sucesso.",
          life: 3000
        });
        wantAddOrRemovePiggy.value = false;
        await loadDashboard()
      }
    } else {
      let response = await axios.patch('/vcards/' + payload.value.phone_number + '/piggybank/remove', payload.value)
      if (response.status == 200) {
        toast.add({
          severity: 'success',
          summary: 'Sucesso',
          detail: "Dinheiro removido com sucesso.",
          life: 3000
        });
        wantAddOrRemovePiggy.value = false;
        await loadDashboard()
      }
      isLoading.value = false;

    }
  } catch (error) {
    isLoading.value = false;
    console.log(error)
    toast.add({
      severity: 'error',
      summary: 'Erro',
      detail: error.response.data.message,
      life: 3000
    });
  }
}

const loadCategories = async () => {
  try {
    const response = await axios.get('vcards/' + userStore.userId + '/categories')
    categories.value = response.data.categories.D
  } catch (error) {
    console.log(error)
  }
}

const loadTransactions = async () => {
  try {
    isLoading.value = true;
    const response = await axios.get('vcards/' + userStore.userId + '/transactions/last3')
    transactions.value = response.data.transactions
    isLoading.value = false;
  } catch (error) {
    isLoading.value = false;
    console.log(error)
  }
}

const goTo = async (routeName) => {
  router.push({ name: routeName })
}

const addTransaction = async (isAskingMoneyValue) => {
  if (userStore.user?.balance == "0,00 €" && !isAskingMoneyValue) {
    noMoneyToast();
    return;
  }
  isAskingMoney.value = isAskingMoneyValue;
  wantCreateTransaction.value = true;
  await loadCategories();
}

const checkPiggyBalance = async (isAddMoneyOnPiggyValue) => {
  if (userStore.user?.piggyBankBalance == "0,00 €" && !isAddMoneyOnPiggyValue) {
    noMoneyToast();
    return;
  }

  if (userStore.user?.balance == "0,00 €" && isAddMoneyOnPiggyValue) {
    noMoneyToast();
    return;
  }
  wantAddOrRemovePiggy.value = true;
  isAddMoneyOnPiggy.value = isAddMoneyOnPiggyValue;
}

const onCloseModal = () => {
  transactionToPiggy.value.errors = false;
  transactionToPiggy.value.value = null;
}

const toggleCreateTransaction = () => {
  wantCreateTransaction.value = !wantCreateTransaction.value;
}

const loadDashboard = async () => {
  await userStore.loadUser()
  await loadTransactions()
}

const noMoneyToast = () => {
  toast.add({
    severity: 'warn',
    summary: 'Aviso',
    detail: 'Não possui dinheiro suficiente para concluir esta ação',
    life: 3000
  });
}

socket.on('newTransaction', async (transaction) => {
  await loadDashboard()
})

onMounted(async () => {
  await loadTransactions()
})
</script>

<template>
  <CreateTransaction :categories="categories" :wantCreateTransaction="wantCreateTransaction"
    :isAskingMoney="isAskingMoney" :isLoading="isLoading" @toggleCreateTransaction="toggleCreateTransaction"
    @createTransaction="createTransaction" @askForMoney="askForMoney">
  </CreateTransaction>
  <div class="card flex justify-content-center">
    <Dialog :draggable="false" v-model:visible="wantAddOrRemovePiggy" modal
      :header="isAddMoneyOnPiggy ? 'Adicionar dinheiro à conta poupança' : 'Retirar dinheiro da conta poupança'"
      :style="{ width: '30rem' }" @after-hide="onCloseModal">
      <div class="flex modal-edit-container">
        <div class="input-container">
          <label>Valor<span class="input-error" style="margin-left: 2px;">*</span></label>
          <InputNumber type="number" class="input-style" v-model="transactionToPiggy.value" mode="currency" currency="EUR"
            locale="pt-PT" :class="{ 'input-style-error': !transactionToPiggy.value && transactionToPiggy.errors }" />
          <span class="input-error" v-if="!transactionToPiggy.value && transactionToPiggy.errors">Campo
            obrigatório.</span>
        </div>
        <Button :label="isAddMoneyOnPiggy ? 'Adicionar' : 'Remover'" class="btn-main" raised
          @click="isAddMoneyOnPiggy ? addOrRemovePiggy('AddMoney') : addOrRemovePiggy('')" />
      </div>

    </Dialog>
  </div>
  <div class="dashboard-container">
    <div class="card-options-container">
      <div class="card-tempalte-container">
        <div class="card-template">
          <div class="card-chip-container">
            <img src="../assets/images/card-chip.avif" />
          </div>
          <div class="v-card-info-container">
            <div class="v-card-name">vCard</div>
            <div class="v-card-user-info">
              <span>{{
                userStore.userName
              }}</span>
              <span>V{{ userStore.user?.id }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="card-info">
        <div class="card-balances-container">
          <div class="card-balances-info">
            <span class="card-balance">{{ userStore.user?.balance }}</span>
            <span class="piggy-balance">Valor Poupado: {{ userStore.user?.piggyBankBalance }}</span>
          </div>
          <div class="card-options-container">
            <i class="pi pi-chart-line" style="font-size: 2rem" @click="goTo('UserStats')"></i>
            <i class="pi pi-cog" style="font-size: 2rem" @click="goTo('UserSettings')"></i>
          </div>
        </div>
        <div class="card-info-action all-transactions animation" @click="addTransaction(false)">
          <i class="pi pi-send" />
          Criar Transação
        </div>
        <div class="card-info-action all-transactions animation" @click="addTransaction(true)">
          <i class="pi pi-send" />
          Pedir Dinheiro
        </div>
        <div class="card-info-action all-transactions animation" @click="checkPiggyBalance(true)">
          <i class="pi pi-plus" />
          Adicionar Dinheiro à conta poupança
        </div>
        <div class="card-info-action all-transactions animation" @click="checkPiggyBalance(false)">
          <i class="pi pi-minus" />
          Retirar Dinheiro da conta poupança
        </div>
      </div>
    </div>


    <div class="transactions-history-container">
      <div class="transaction-history-header">
        <span> Últimas Transações </span>
        <span class="all-transactions animation" @click="goTo('TransactionHistory')" v-if="transactions.length > 0"> Ver
          Todos </span>
      </div>
    </div>
  </div>
  <transactions :transactions="transactions" @updateTransactions="loadTransactions"
    v-if="!isLoading && transactions.length > 0"></transactions>
  <div class="no-transactions" v-if="(!transactions || transactions.length == 0) && !isLoading">
    Não tem transações registadas
  </div>
  <div class="loading-container" v-if="isLoading">
    <LoadingSpinner></LoadingSpinner>
  </div>
</template>

<style scoped>
.dashboard-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.dashboard-container .card-tempalte-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 70%;
}

.dashboard-container .card-options-container {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 50px;
  width: 100%;
}

.dashboard-container .card-options-container .card-info {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

.dashboard-container .card-options-container .card-info .card-balances-container {
  display: flex;
  justify-content: space-between;
}

.dashboard-container .card-options-container .card-info .card-balances-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
}

.dashboard-container .card-options-container .card-info .card-options-container {
  display: flex;
  gap: 24px;
  justify-content: end;
  opacity: 0.8;
}

.dashboard-container .card-options-container .card-info .card-options-container i:hover {
  cursor: pointer;
  color: #f16758;
}

.dashboard-container .card-options-container .card-info .card-balance {
  font-size: 40px;
  font-weight: bold;
  color: #000000b7;
}

.dashboard-container .card-options-container .card-info .piggy-balance {
  font-size: 16px;
  color: #00000073;
}

.dashboard-container .card-options-container .card-info .card-info-action {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 1px solid #f16758;
  width: 100%;
  border-radius: 8px;
  cursor: pointer;
  color: #000000b7;
  background: rgb(241, 103, 88);
  background: linear-gradient(48deg, rgba(241, 103, 88, 0.19091386554621848) 0%, rgba(255, 255, 255, 0.21052170868347342) 64%);
}

.dashboard-container .card-options-container .card-info .card-info-action:hover {
  background-color: #f16758;
  color: #fff;
}

.card-template {
  background-color: #f16758;
  border-radius: 12px;
  width: 340px;
  height: 190px;
  display: flex;
  padding: 24px;
}

.card-template .card-chip-container {
  display: flex;
  justify-content: center;
  align-items: center;
}

.card-template img {
  width: 50px;
}

.card-template .v-card-info-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.card-template .v-card-info-container .v-card-name {
  display: flex;
  justify-content: end;
  color: #fff;
  font-size: 20px;
  font-weight: 500;
}

.card-template .v-card-info-container .v-card-user-info {
  display: flex;
  flex-direction: column;
  align-items: end;
  color: #fff;
  gap: 8px;
}

.dashboard-container .transactions-history-container {
  width: 100%;
  display: flex;
  flex-direction: column;
}

.dashboard-container .transactions-history-container .transaction-history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 0;
}

.dashboard-container .transactions-history-container .transaction-history-header .all-transactions:hover {
  cursor: pointer;
  text-decoration: underline;
  color: #f16758;
}

.dashboard-button {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 14px;
  background-color: #f16758a9;
  border-radius: 8px;
  color: #f5f5f5;
  cursor: pointer;
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

.no-transactions {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 300px;
  opacity: 0.7;
}
</style>