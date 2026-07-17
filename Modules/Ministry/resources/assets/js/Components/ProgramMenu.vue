<template>

    <div class="list-group">
        <Link href="#" class="list-group-item list-group-item-action disabled list-group-item-secondary"><strong>{{ name }}</strong></Link>
        <Link :href="'/ministry/programs/' + id" class="list-group-item list-group-item-action" :class="page === 'details' ? 'active' : ''">Program Information</Link>
        <Link :href="'/ministry/programs/' + id + '/offerings'" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" :class="page === 'offerings' ? 'active' : ''">
            <span>Offerings</span>
            <span class="badge rounded-pill" :class="page === 'offerings' ? 'bg-secondary' : 'bg-primary'">{{ offeringsCount ?? 0 }}</span>
        </Link>
    </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    page: String,
    id: Number,
    name: String,
    offeringsCount: Number
});
</script>
