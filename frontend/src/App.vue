  <script setup>
  import {ref, inject, onMounted} from 'vue'
  import {RouterView} from 'vue-router'
  import Navbar from '@/components/global/Navbar.vue'
  import {useUserStore} from './stores/user';
  import Button from 'primevue/button';
  import {useToast} from "primevue/usetoast";
  import Avatar from 'primevue/avatar';
  import { useConfirm } from "primevue/useconfirm";
  import InputText from 'primevue/inputtext';

  const toast = useToast();
  const axios = inject('axios')
  const socket = inject('socket')
  const userStore = useUserStore();
  const confirm = useConfirm();
  const wantSendMoneyVCard = ref(false)
  const confirmationCode = ref('')
  const closeCreateTransactionToast = () => {
    toast.removeGroup('askForMoney');
  }

  const createTransaction = async (request) => {
    try {
      const payload = ref({
        vcard: userStore.userId,
        value: request.value,
        payment_type: request.payment_type,
        payment_reference: request.vcard,
        description: request.description,
        confirmationCode: confirmationCode.value,
        category: request.category,
        isAdmin: false
      })
      const response = await axios.post('/transactions', payload.value)
      if (response.data.data.payment_type === "VCARD")
        socket.emit('newTransaction', response.data.data, false)
      if (response.status === 201) {
        closeCreateTransactionToast()
        confirmationCode.value = ''
        toast.add({
          severity: 'success',
          summary: 'Sucesso',
          detail: "Transação criada com sucesso.",
          life: 3000
        });
        wantSendMoneyVCard.value = false
      }
    } catch (error) {
      toast.add({
        severity: 'error',
        summary: 'Erro',
        detail: error.response.data.message,
        life: 3000
      });
    }
  }

  const sendMoney = (request) => {
    wantSendMoneyVCard.value = true
    confirm.require({
        group: 'addConfirmationCOde',
        header: 'Código de Confirmação',
        accept: () => {
            createTransaction(request)
        },
        reject: () => {
            wantSendMoneyVCard.value = false
        }
    });

}

  onMounted(async () => {
    try {
      const token = sessionStorage.getItem("token")
      if (!token) {
        return
      }
      axios.defaults.headers.common.Authorization = 'Bearer ' + token
      await userStore.loadUser()
    } catch (error) {
      console.log(error)
    }
  })

  </script>

  <template>
    <ConfirmDialog group="addConfirmationCOde" :visible="wantSendMoneyVCard" >
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="flex flex-column align-items-center p-5 surface-overlay border-round">
                <div
                    class="border-circle bg-primary inline-flex justify-content-center align-items-center h-6rem w-6rem -mt-8 circle-background">
                    <img class="text-5xl" style="width: 70px;" src="/src/assets/images/v-card-full-logo.png">
                </div>
                <span class="font-bold text-2xl block mb-2 mt-4">{{ message.header }}</span>
                <div class="flex flex-column gap-4 mt-4">
                    <InputText type="password" class="input-style" v-model="confirmationCode" placeholder="Código de confirmação" /> 
                </div>
                <div class="flex align-items-center gap-2 mt-4">
                    <Button label="Sim" @click="acceptCallback" class="w-8rem btn-main"></Button>
                    <Button label="Cancelar" outlined @click="rejectCallback" class="w-8rem"></Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>
    <ConfirmDialog></ConfirmDialog>
    <Toast/>
    <Toast class="receive-money-toast" position="top-right" group="askForMoney">
      <template #message="slotProps">
        <div class="receive-money-toast-container">
          <div class="receive-money-header">
            <Avatar :image="'/storage/fotos/'+userStore.moneyRequest.photo_url" shape="circle" v-if="userStore.moneyRequest.photo_url"/>
            <label>{{ userStore.moneyRequest.name }} ({{ userStore.moneyRequest.vcard }})</label>
          </div>
          <div class="receive-money-description">
            {{ userStore.moneyRequest.description }}
          </div>
          <div class="receive-money-options">
            <Button class="p-button-sm btn-main" :label="'Enviar ' + userStore.moneyRequest.value + '€'"
                    @click="sendMoney(userStore.moneyRequest)"></Button>
            <Button class="p-button-sm btn-cancel" label="Cancelar" @click="closeCreateTransactionToast" text></Button>
          </div>
        </div>
      </template>
    </Toast>
    <div class="flex flex-column min-height-full" v-if="userStore.user">
      <div>
        <Navbar></Navbar>
      </div>
      <div class="app-container">
        <RouterView/>
      </div>
    </div>
    <div class="flex flex-column min-height-full no-navbar-container" v-else>
      <RouterView/>
    </div>
  </template>

  <style scoped>
  .app-container {
    width: 100%;
    padding: 24px 70px;
    margin-top: 74px;
    min-height: calc(100vh - 74px);
  }

  .no-navbar-container {
    padding: 20px;
    background: linear-gradient(48deg, rgba(241, 103, 88, 0.36458333333333337) 0%, rgba(255, 255, 255, 0.21052170868347342) 66%);
    justify-content: center;
  }

  .receive-money-toast-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    color: #000;
  }

  .receive-money-toast-container .receive-money-header, .receive-money-toast-container .receive-money-options {
    display: flex;
    gap: 12px;
    align-items: center;
  }

  .receive-money-toast-container .receive-money-description {
    opacity: 0.8;
  }


  .receive-money-toast-container .receive-money-header label {
    padding-top: 10px;
    font-size: 16px;
    font-weight: 700;
  }

  .circle-background {
    background-color: white !important;
}
  </style>
