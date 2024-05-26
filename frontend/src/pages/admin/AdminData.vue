<script setup>
import { ref, onMounted, inject } from 'vue'

import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import { useToast } from 'primevue/usetoast';
import VcardsList from '@/components/admin/VCardsList.vue'
import DefaultCategoriesList from '@/components/admin/DefaultCategoriesList.vue'
import TransactionsList from '@/components/admin/TransactionsList.vue'
import AdminsList from '@/components/admin/AdminsList.vue'
const axios = inject('axios')

const isLoading = ref(false)
const activeTab = ref(0)
const socket = inject('socket')
const loading = ref(false)
const admins = ref([])
const vcards = ref([])
const transactions = ref([])
const categories = ref([])
const vcardStatistcs = ref(0)
const numberTransactionsRows = ref(0)
const numberVCardsRows = ref(0)
const toast = useToast()

const wantsAddAdmin = ref(false);
const wantUpdateVCard = ref(false)
const wantCreateTransaction = ref(false)
const wantCreateOrUpdateCategory = ref(false)

const loadVCards = async (event, inputText) => {
  console.log(event)
  try {
    await axios.get('vcards', {
      params: {
        first: event ? event.first : 0,
        rows: event ? event.rows : 5,
        sortField: event ? event.sortField : 'created_at',
        sortOrder: event ? (event.sortOrder == -1 ? 'desc' : 'asc') : 'asc',
        searchable_columns: JSON.stringify(['phone_number', 'name', 'email', 'balance', 'max_debit', 'created_at']),
        globalSearch: inputText
      }
    }).then(response => {
      isLoading.value = false;
      vcards.value = response.data.vCards
      numberVCardsRows.value = response.data.numberRows
      vcardStatistcs.value = response.data.vCards.filter(e => e.isBlocked == '0').length
    })
  } catch (error) {
    isLoading.value = false;
    console.log(error)
  }
}

const loadAdmins = async () => {
  try {
    await axios.get('admins').then(response => {
      isLoading.value = false;
      admins.value = response.data.admins

    })
  } catch (error) {
    isLoading.value = false;
    console.log(error)
  }
}

const loadTransactions = async (transactionsFilter, event, inputText) => {
  try {
    await axios.get('transactions/bydate', {
      params: {
        transactionsFilter,
        first: event ? event.first : 0,
        rows: event ? event.rows : 5,
        sortField: event ? event.sortField : 'datetime',
        sortOrder: event ? (event.sortOrder == -1 ? 'desc' : 'asc') : 'asc',
        searchable_columns: JSON.stringify(['vcard', 'payment_type', 'payment_reference', 'value', 'datetime']),
        globalSearch: inputText
      }
    }).then(response => {
      console.log(response)
      isLoading.value = false;
      transactions.value = response.data.transactions
      numberTransactionsRows.value = response.data.numberRows
    })
  } catch (error) {
    isLoading.value = false;
    console.log(error)
  }
}

const loadCategories = async () => {
  try {
    await axios.get('defaultcategories').then(response => {
      isLoading.value = false;
      categories.value = response.data.defaultCategories

    })
  } catch (error) {
    isLoading.value = false;
    console.log(error)
  }
}



const createCategory = async (category) => {
  try {
    isLoading.value = true;
    await axios.post('defaultcategories', category).then(response => {
      isLoading.value = false;
      if (response.status == 201) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
        wantCreateOrUpdateCategory.value = false;
        loadCategories();
      }
    })

  } catch (error) {
    isLoading.value = false;
    console.error(error)
    toast.add({ severity: 'error', summary: 'Erro', detail: error.response.data.message, life: 3000 });
  }
}

const updateCategory = async (category) => {
  isLoading.value = true;
  try {
    await axios.put('defaultcategories/' + category.id, category).then(response => {
      isLoading.value = false;
      if (response.status == 201) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
        wantCreateOrUpdateCategory.value = false;
        loadCategories();
      }
    })

  } catch (error) {
    isLoading.value = false;
    //category.value.errors.push(error.data)
    console.log(error)
  }
}

const deleteCategoryAPI = async (id) => {
  try {
    isLoading.value = true;
    await axios.delete('defaultcategories/' + id).then(response => {
      isLoading.value = false;
      if (response.status == 200) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
        loadCategories();
      }
    })

  } catch (error) {
    isLoading.value = false;
    category.value.errors.push(error.data)
    console.log(error)
  }
}

const changeVCardStatus = async (vcard) => {
  try {
    loading.value = true;
    await axios.patch('/vcards/' + vcard.phone_number + '/changestatus').then(response => {
      loading.value = false;
      if (response.status == 200) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: "Vcard #" + vcard.phone_number + " " + (vcard.isBlocked ? 'desbloqueado' : 'bloqueado') + ' com sucesso', life: 3000 });
        loadVCards();
      }
    })
    socket.emit('changedVcardStatus', vcard)
  } catch (error) {
    isLoading.value = false;
    console.log(error)
  }
}

const editMaxDebit = async (userPhoneNumber, userMaxDebit) => {
  try {
    isLoading.value = true;
    await axios.patch('/vcards/' + userPhoneNumber + '/maxdebit', { value_max_debit: userMaxDebit }).then(response => {
      isLoading.value = false;
      if (response.status == 200) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
        loadVCards();
        wantUpdateVCard.value = false;
      }
    })

  } catch (error) {
    isLoading.value = false;
    console.log(error)
  }
}

const addAdminAPI = async (admin) => {
  try {
    isLoading.value = true;
    await axios.post('admins', admin).then(response => {
      isLoading.value = false;
      if (response.status == 200) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
        wantsAddAdmin.value = false;
        loadAdmins()
      }
    })

  } catch (error) {
    isLoading.value = false;

  }
}

const deleteAdminAPI = async (admin) => {
  try {
    isLoading.value = true;
    await axios.delete('/admins/' + admin.id).then(response => {
      isLoading.value = false;
      if (response.status == 200) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
        loadAdmins();
      }
    })

  } catch (error) {
    isLoading.value = false;

    admin.value.errors.push(error.response.data.errors)
    console.log(error)
  }
}

const deleteVcardAPI = async (vcard) => {
  try {
    loading.value = true;
    await axios.delete('/vcards/' + vcard.phone_number, { params: { isAdmin: true } }).then(response => {
      loading.value = false;
      if (response.status == 200) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Vcard apagado com sucesso', life: 3000 });
        loadVCards()
        socket.emit('deletedVcard', response.data.data)
      }
    })
  } catch (error) {
    isLoading.value = false;
    console.log(error)
  }
}

const createTransactionAPI = async (transaction) => {
  try {
    isLoading.value = true;
    const payload = ref({
      vcard: transaction.vcard,
      value: transaction.value,
      type: 'C',
      payment_type: transaction.type,
      payment_reference: transaction.reference,
      description: transaction.description,
      confirmation_code: null,
      category: transaction.category,
      isAdmin: true
    })

    await axios.post('transactions', payload.value).then(response => {
      isLoading.value = false;

      if (response.status == 201) {
        toast.add({ severity: 'success', summary: 'Sucesso', detail: response.data.message, life: 3000 });
        socket.emit('newTransaction',response.data.data, true )
        wantCreateTransaction.value = false;
        loadVCards()
      }
    })
  } catch (error) {
    isLoading.value = false;
    toast.add({
      severity: 'error',
      summary: 'Erro',
      detail: error.response.data.message,
      life: 3000
    });
    console.log(error)
  }
}


const onTabChange = async (event) => {
  activeTab.value = event.index
  isLoading.value = true;
  switch (event.index) {
    case 0:
      await loadVCards();
      break;
    case 1:
      await loadAdmins();
      break;
    case 2:
      await loadTransactions();
      break;
    case 3:
      await loadCategories();
      break;
  }
}



const createOrUpdateCategory = (category) => {
  if (category.id >= 0) {
    updateCategory(category)
  } else {
    createCategory(category)
  }

}

const toggleEditMaxDebitModal = () => {
  wantUpdateVCard.value = !wantUpdateVCard.value;
}

const toggleCreateTransactionModal = () => {
  wantCreateTransaction.value = !wantCreateTransaction.value;
}

const toggleCreateOrUpdateCategory = () => {
  wantCreateOrUpdateCategory.value = !wantCreateOrUpdateCategory.value;
}

const toggleCreateAdminModal = () => {
  wantsAddAdmin.value = !wantsAddAdmin.value;
}

onMounted(async () => {
  await loadVCards();
})
</script>
<template>
  <div class="admin-dashboard-list-container">
    <TabView @tab-change="onTabChange">
      <TabPanel header="Utilizadores">
        <vcards-list v-if="activeTab == 0" :vcards="vcards" :wantCreateTransaction="wantCreateTransaction" :numberRows="numberVCardsRows" :wantUpdateVCard="wantUpdateVCard"
          :isLoading="isLoading" @createTransaction="createTransactionAPI" @updateVCards="loadVCards"
          @toggleEditMaxDebitModal="toggleEditMaxDebitModal" @toggleCreateTransaction="toggleCreateTransactionModal"
          @changeVCardStatus="changeVCardStatus" @editUserMaxDebit="editMaxDebit"
          @deleteVcard="deleteVcardAPI"></vcards-list>
      </TabPanel>
      <TabPanel header="Administradores">
        <admins-list v-if="activeTab == 1" :admins="admins" :wantsAddAdmin="wantsAddAdmin" :isLoading="isLoading"
          @toggleCreateAdminModal="toggleCreateAdminModal" @create="addAdminAPI" @delete="deleteAdminAPI"></admins-list>
      </TabPanel>
      <TabPanel header="Transações">
        <transactions-list v-if="activeTab == 2" :transactions="transactions" :numberRows="numberTransactionsRows" :isLoading="isLoading"
          @updateTransactions="loadTransactions"></transactions-list>
      </TabPanel>
      <TabPanel header="Categorias">
        <default-categories-list v-if="activeTab == 3" :categories="categories" :wantCreateOrUpdateCategory="wantCreateOrUpdateCategory"
          :isLoading="isLoading" @delete="deleteCategoryAPI" @createOrUpdate="createOrUpdateCategory"
          @toggleCreateOrUpdateCategory="toggleCreateOrUpdateCategory"></default-categories-list>
      </TabPanel>
    </TabView>
  </div>
</template>

<style scoped>
.simple-stats-container {
  gap: 24px;
  justify-content: space-around;
}

.simple-stats-container .stats-card-container {
  width: 250px;
}

.simple-stats-container .stats-card-container .stat-info {
  justify-content: center;
  align-items: center;
  gap: 12px;
  position: relative;
}

.simple-stats-container .stats-card-container .stat-info i {
  position: absolute;
  right: -10px;
  top: -10px;
  cursor: pointer;
}

.admin-dashboard-list-container {
  margin-top: 20px;
  height: calc(100vh - 200px);
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
  