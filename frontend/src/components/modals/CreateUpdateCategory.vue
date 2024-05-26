<script setup>
import { ref, watch } from 'vue'
import InputText from 'primevue/inputtext';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';

const emit = defineEmits(['createOrUpdate', 'delete', 'toggleCreateOrUpdateCategory'])

const props = defineProps({
    category: {
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
const category = ref(Object.assign({}, props.category))
const editCategory = () => {
    emit('createOrUpdate', category.value);
}

const closeAddUpdateCategoryModal = () => {
    emit('toggleCreateOrUpdateCategory')
}

watch(
    () => props,
    (newProps) => {
        category.value = Object.assign({}, newProps.category)
    },
    {immediate: true}
)

</script>
<template>
    <Dialog :draggable="false" :visible="props.wantCreateOrUpdateCategory" @update:visible="closeAddUpdateCategoryModal"
        modal :header="category.id != -1 ? 'Editar Categoria' : 'Criar Categoria'" :style="{ width: '30rem' }">
        <div class="flex modal-edit-container">
            <div class="input-container">
                <label>Nome</label>
                <InputText type="text" class="input-style" v-model="category.name"
                    placeholder="Nome" :class="{ 'input-style-error': category.errors && !category.name }" />
                <span class="input-error" v-if="category.errors && !category.name">Campo obrigatório.</span>
            </div>
            <div class="input-container" v-if="category.id == -1">
                <label>Tipo de Transação</label>
                <div class="flex gap-5">
                    <div class="flex align-items-center">
                        <Checkbox v-model="category.credit" inputId="credit" name="credit" :binary="true" />
                        <span for="credit" class="ml-2"> Crédito </span>
                    </div>
                    <div class="flex align-items-center">
                        <Checkbox v-model="category.debit" name="debit" inputId="debit" :binary="true" />
                        <span for="debit" class="ml-2"> Débito </span>
                    </div>
                </div>
                <span class="input-error"
                    v-if="category.errors && !category.credit && !category.debit">Tem de
                    selecionar
                    pelo menos um tipo de transação</span>
            </div>
            <Button :label="category.id == -1 ? 'Criar Categoria' : 'Editar Categoria'" :loading="isLoading"
                class="btn-main" raised @click="editCategory()" />
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
