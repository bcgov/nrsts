<style scoped>
[type='checkbox']:checked, [type='radio']:checked {
    background-size: initial;
}
</style>
<template>
    <Head title="Institutions" />

    <AuthenticatedLayout v-bind="$attrs">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-header">
                                Institution Search
                            </div>
                            <div class="card-body">
                                <InstitutionSearchBox />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9 mb-3">
                        <div v-if="flash && flash.success" class="alert alert-success" role="alert">{{ flash.success }}</div>
                        <div v-if="flash && flash.error" class="alert alert-danger" role="alert">{{ flash.error }}</div>
                        <div class="card">
                            <div class="card-header">
                                Institutions
<!--                                <button type="button" class="btn btn-success btn-sm float-end" data-bs-toggle="modal" data-bs-target="#newInstModal">New Institution</button>-->
                            </div>
                            <div class="card-body">
                                <div v-if="results != null && results.data.length > 0" class="table-responsive pb-3">
                                    <table class="table table-striped">
                                        <thead>
                                            <InstitutionsHeader></InstitutionsHeader>
                                        </thead>
                                        <tbody>
                                        <tr v-for="(row, i) in results.data">
                                            <td><Link :href="'/ministry/institutions/' + row.id">{{ row.name }}</Link></td>
                                            <td>
                                                <span v-if="row.active_status" class="badge rounded-pill text-bg-success">Active</span>
                                                <span v-else class="badge rounded-pill text-bg-danger">Inactive</span>
                                            </td>
                                            <td>{{ row.size }}</td>
                                            <template v-if="row.active_allocation != null">
                                                <td>${{row.active_allocation.total_amount_formatted }}</td>
<!--                                                <td>{{row.active_allocation.on_hold_amount}}</td>-->
<!--                                                <td>{{row.active_allocation.total_amount - row.active_allocation.claimed_amount}}</td>-->
                                            </template>
                                            <template v-else>
                                                <td>$0</td>
<!--                                                <td></td>-->
<!--                                                <td></td>-->
                                            </template>
                                            <td>
                                                <span v-if="row.overallocation_flag" class="badge rounded-pill text-bg-danger">True</span>
                                                <span v-else class="badge rounded-pill text-bg-success">False</span>
                                            </td>

                                        </tr>
                                        </tbody>
                                    </table>
                                    <Pagination :links="results.links" :active-page="results.current_page" />
                                </div>
                                <div v-else class="text-center py-4">
                                    <h1 class="lead">No results</h1>
                                    <button type="button" class="btn btn-success mt-2" :disabled="fetching" @click="fetchFromPdex">
                                        <span v-if="fetching" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Fetch Institutions from PDEX
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <div class="modal modal-lg fade" id="newInstModal" tabindex="-1" aria-labelledby="newInstModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newInstModalLabel">Add New Institution</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <InstitutionCreate v-bind="$attrs" :newInst="newInst" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>
<script>
import AuthenticatedLayout from '../Layouts/Authenticated.vue';
import InstitutionSearchBox from '../Components/InstitutionSearch.vue';
import InstitutionsHeader from '../Components/InstitutionsHeader.vue';
import InstitutionCreate from '../Components/InstitutionCreate.vue';
import Pagination from "@/Components/Pagination";
import { Link, Head, router } from '@inertiajs/vue3';

export default {
    name: 'Institutions',
    components: {
        AuthenticatedLayout, InstitutionSearchBox, InstitutionsHeader, Head, Link, Pagination, InstitutionCreate
    },
    props: {
        results: Object,
        newInst: Object|null
    },
    computed: {
        flash() {
            return this.$page.props.flash;
        }
    },
    data() {
        return {
            fetching: false
        }
    },
    methods: {
        fetchFromPdex() {
            this.fetching = true;
            router.post('/ministry/institutions/fetch-pdex', {}, {
                preserveScroll: true,
                onFinish: () => { this.fetching = false; }
            });
        }
    }
}
</script>
