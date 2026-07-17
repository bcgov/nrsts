<template>
    <form @submit.prevent="nameFormSubmit" class="m-3">
        <div class="row mb-3">
            <BreezeLabel class="col-auto col-form-label" for="inputName" value="Name" />
            <div class="col-auto">
                <BreezeInput type="text" id="inputName" class="form-control" v-model="nameForm.filter_name" />
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-auto">
                <BreezeButton class="btn btn-success" :class="{ 'opacity-25': nameForm.processing }" :disabled="nameForm.processing">
                    Search
                </BreezeButton>
            </div>
        </div>
    </form>
</template>
<script setup>
import BreezeInput from '@/Components/Input.vue';
import BreezeLabel from '@/Components/Label.vue';
import BreezeButton from '@/Components/Button.vue';

import { useForm } from '@inertiajs/vue3';

const nameForm = useForm({
    filter_name: '',
});

const nameFormSubmit = () => {
    nameForm.get('/ministry/programs', {
        onFinish: () => nameForm.reset('inputName'),
    });
};
</script>
