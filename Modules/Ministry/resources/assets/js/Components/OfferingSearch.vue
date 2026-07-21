<template>
    <form @submit.prevent="submit" class="m-3">
        <div class="mb-3">
            <BreezeLabel class="form-label" for="inputName" value="Offering Name" />
            <BreezeInput type="text" id="inputName" class="form-control" v-model="searchForm.filter_name" />
        </div>
        <div class="mb-3">
            <BreezeLabel class="form-label" for="inputStatus" value="Status" />
            <select id="inputStatus" class="form-select" v-model="searchForm.filter_status">
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="submitted">Submitted</option>
                <option value="approved">Approved</option>
                <option value="declined">Declined</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div class="d-flex">
            <BreezeButton class="btn btn-success" :class="{ 'opacity-25': searchForm.processing }" :disabled="searchForm.processing">
                Search
            </BreezeButton>
            <button type="button" class="btn btn-outline-secondary ms-2" @click="reset">Reset</button>
        </div>
    </form>
</template>
<script setup>
import BreezeInput from '@/Components/Input.vue';
import BreezeLabel from '@/Components/Label.vue';
import BreezeButton from '@/Components/Button.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    filters: Object,
});

const searchForm = useForm({
    filter_name: props.filters?.filter_name ?? '',
    filter_status: props.filters?.filter_status ?? '',
});

const submit = () => {
    searchForm.get('/ministry/offerings', { preserveState: true, preserveScroll: true });
};

const reset = () => {
    router.visit('/ministry/offerings');
};
</script>
