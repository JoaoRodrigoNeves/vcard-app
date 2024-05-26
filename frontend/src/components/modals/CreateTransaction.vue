<script setup>
import { ref } from 'vue'
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputMask from 'primevue/inputmask';
import { useUserStore } from '../../stores/user';

const emit = defineEmits(['createTransaction', 'toggleCreateTransaction', 'askForMoney'])

const userStore = useUserStore()

const props = defineProps({
    user: {
        required: false
    },
    categories: {
        required: false
    },
    wantCreateTransaction: {
        type: Boolean,
        required: true
    },
    isAskingMoney: {
        type: Boolean,
        required: false
    },
    isLoading: {
        type: Boolean,
        required: true
    }
})

const adminTransactionType = ref([
    { type: 'MBWAY' },
    { type: 'PAYPAL' },
    { type: 'IBAN' },
    { type: 'MB' },
    { type: 'VISA' },
])

const userTransactionType = ref([
    { type: 'VCARD' },
    { type: 'MBWAY' },
    { type: 'PAYPAL' },
    { type: 'IBAN' },
    { type: 'MB' },
    { type: 'VISA' },
])

const creditTransaction = ref({
    type: userStore.user && userStore.user.user_type == "A" ? 'MBWAY' : 'VCARD',
    userRole: userStore.user?.user_type,
    vcard: '',
    reference: '',
    value: null,
    errors: false,
    description: '',
    category: '',
    confirmationCode: ''
})

const createTransaction = async (user) => {
    if (userStore.user.user_type == "A") {
        creditTransaction.value.vcard = user.phone_number
        emit('createTransaction', creditTransaction.value);
    } else {
        emit('createTransaction', creditTransaction.value);
    }
}

const askForMoney = async () => {
    emit('askForMoney', creditTransaction.value)
}

const resetReference = () => {
    creditTransaction.value.reference = '';
    creditTransaction.value.errors = false;
}

const clearFields = () => {
    creditTransaction.value = {
        type: userStore.user.user_type == "A" ? 'MBWAY' : 'VCARD',
        reference: '',
        value: null,
        errors: false,
        description: '',
        category: '',
    }
}

const closeCreateTransactionModal = () => {
    emit('toggleCreateTransaction');
}

</script>
<template>
    <Dialog :draggable="false" :visible="props.wantCreateTransaction" @update:visible="closeCreateTransactionModal" modal
        :header="props.isAskingMoney ? 'Pedir dinheiro' : 'Criar Transação'" @after-hide="clearFields"
        :style="{ width: '30rem' }">
        <div class="flex modal-edit-container">
            <div style="text-align: center;">{{ props.user ? (props.user.name + " - " + props.user.phone_number) : '' }} </div>
            <div class="input-container" v-if="!props.isAskingMoney">
                <label>Tipo de Pagamento</label>
                <Dropdown v-model="creditTransaction.type" @change="resetReference"
                    :options="userStore.user.user_type == 'A' ? adminTransactionType : userTransactionType"
                    optionLabel="type" optionValue="type" placeholder="Selecione um tipo de pagamento" class="w-full" />
                <span class="input-error" v-if="!creditTransaction.type && false">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container" v-if="creditTransaction.type == 'MBWAY' || creditTransaction.type == 'VCARD'">
                <label>Número de Telemóvel</label>
                <InputMask id="basic" class="input-style" v-model="creditTransaction.reference" mask="999999999" slotChar=""
                    :class="{ 'input-style-error': !creditTransaction.reference && creditTransaction.errors }" />
                <span class="input-error" v-if="!creditTransaction.reference && creditTransaction.errors">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container" v-if="creditTransaction.type == 'PAYPAL'">
                <label>Email</label>
                <InputText type="text" class="input-style" v-model="creditTransaction.reference"
                    :class="{ 'input-style-error': !creditTransaction.reference && creditTransaction.errors }" />
                <span class="input-error" v-if="!creditTransaction.reference && creditTransaction.errors">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container" v-if="creditTransaction.type == 'IBAN'">
                <label>IBAN</label>
                <InputMask id="basic" class="input-style" v-model="creditTransaction.reference"
                    mask="aa99 9999 9999 99999999999 99" slotChar=""
                    :class="{ 'input-style-error': !creditTransaction.reference && creditTransaction.errors }" />
                <span class="input-error" v-if="!creditTransaction.reference && creditTransaction.errors">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container" v-if="creditTransaction.type == 'MB'">
                <label>MB</label>
                <InputMask id="basic" class="input-style" v-model="creditTransaction.reference" mask="99999-999999999"
                    slotChar=""
                    :class="{ 'input-style-error': !creditTransaction.reference && creditTransaction.errors }" />
                <span class="input-error" v-if="!creditTransaction.reference && creditTransaction.errors">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container" v-if="creditTransaction.type == 'VISA'">
                <label>VISA</label>
                <InputMask id="basic" class="input-style" v-model="creditTransaction.reference" mask="9999 9999 9999 9999"
                    slotChar=""
                    :class="{ 'input-style-error': !creditTransaction.reference && creditTransaction.errors }" />
                <span class="input-error" v-if="creditTransaction.reference && creditTransaction.errors">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container" v-if="userStore.user.user_type != 'A'">
                <label>Descrição</label>
                <InputText type="text" class="input-style" v-model="creditTransaction.description" />
            </div>
            <div class="input-container">
                <label>Valor<span class="input-error" style="margin-left: 2px;">*</span></label>
                <InputNumber type="number" class="input-style" v-model="creditTransaction.value" mode="currency"
                    currency="EUR" locale="pt-PT"
                    :class="{ 'input-style-error': !creditTransaction.value && creditTransaction.errors }" />
                <span class="input-error" v-if="!creditTransaction.value && creditTransaction.errors">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container" v-if="userStore.user.user_type != 'A' && !props.isAskingMoney">
                <label for="confirmation_code">Código de Confirmação<span class="input-error" style="margin-left: 2px;">*</span></label>
                <InputText type="password" class="input-style" id="confirmation_code"
                    v-model="creditTransaction.confirmationCode"
                    :class="{ 'input-style-error': !creditTransaction.confirmationCode && creditTransaction.errors }" />
                <span class="input-error" v-if="!creditTransaction.confirmationCode && creditTransaction.errors">Campo
                    obrigatório.</span>
            </div>
            <div class="input-container"
                v-if="userStore.user.user_type != 'A' && categories.length > 0 && !props.isAskingMoney">
                <label>Categoria</label>
                <Dropdown v-model="creditTransaction.category" showClear :options="categories" optionLabel="name"
                    optionValue="id" placeholder="Selecione uma categoria" class="w-full" />
                <span class="input-error" v-if="!creditTransaction.name && false">Campo
                    obrigatório.</span>
            </div>
            <Button :label="props.isAskingMoney ? 'Efetuar Pedido' : 'Criar Transação'" :loading="isLoading"
                class="btn-main" raise @click="props.isAskingMoney ? askForMoney() : createTransaction(user)" />
        </div>

    </Dialog>
</template>

<style scoped>
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
