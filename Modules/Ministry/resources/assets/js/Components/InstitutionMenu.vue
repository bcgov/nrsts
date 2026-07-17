<template>

    <div class="list-group">
        <Link href="#" class="list-group-item list-group-item-action disabled list-group-item-secondary"><strong>{{ name }}</strong></Link>
        <Link :href="'/ministry/institutions/' + id" class="list-group-item list-group-item-action" :class="page === 'details' ? 'active' : ''">Information &amp; Contact Info.</Link>
        <Link :href="'/ministry/institutions/' + id + '/claims-by-course'" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" :class="page === 'claims-by-course' ? 'active' : ''">
            <span>Applications</span>
            <span class="badge rounded-pill" :class="page === 'claims-by-course' ? 'bg-secondary' : 'bg-primary'">{{ applicationsCount ?? 0 }}</span>
        </Link>
        <Link :href="'/ministry/institutions/' + id + '/offerings'" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" :class="page === 'offerings' ? 'active' : ''">
            <span>Offerings</span>
            <span class="badge rounded-pill" :class="page === 'offerings' ? 'bg-secondary' : 'bg-primary'">{{ offeringsCount ?? 0 }}</span>
        </Link>
        <Link :href="'/ministry/institutions/' + id + '/staff'" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" :class="page === 'staff' ? 'active' : ''">
            <span>BCeID Staff List</span>
            <span class="badge rounded-pill" :class="page === 'staff' ? 'bg-secondary' : 'bg-primary'">{{ staffCount ?? 0 }}</span>
        </Link>
    </div>
</template>
<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({
    page: String,
    id: Number,
    name: String,
    applicationsCount: Number,
    offeringsCount: Number,
    staffCount: Number
});

</script>
