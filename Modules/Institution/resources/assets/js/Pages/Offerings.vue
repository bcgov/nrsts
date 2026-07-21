<template>
    <Head title="Offerings"/>

    <AuthenticatedLayout v-bind="$attrs">

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            Offerings Search
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="search">
                                <div class="mb-3">
                                    <Label for="filterName" class="form-label" value="Offering Name" />
                                    <Input type="text" class="form-control" id="filterName" v-model="searchForm.filter_name" />
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm ms-1" @click="resetSearch">Reset</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="card mb-3">
                        <div class="card-header d-flex align-items-center">
                            Program Offerings
                            <button type="button" class="btn btn-success btn-sm ms-auto text-nowrap" @click="openNewForm">New Offering</button>
                        </div>
                        <div class="card-body">
                            <div v-if="results.length > 0" class="table-responsive pb-3">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Offering Name</th>
                                            <th>Program</th>
                                            <th>Program Year</th>
                                            <th>Location</th>
                                            <th>Study Start</th>
                                            <th>Study End</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in results" :key="row.id">
                                            <td>
                                                <a v-if="row.offering_status === 'draft'" href="#" @click.prevent="openEditForm(row)">{{ row.offering_name }}</a>
                                                <span v-else>{{ row.offering_name }}</span>
                                            </td>
                                            <td>{{ row.program ? row.program.program_name : '—' }}</td>
                                            <td>{{ row.py ? formatProgramYear(row.py) : '—' }}</td>
                                            <td>{{ row.location_name || '—' }}</td>
                                            <td>{{ formatDate(row.start_date) }}</td>
                                            <td>{{ formatDate(row.end_date) }}</td>
                                            <td>
                                                <span v-if="row.offering_status === 'approved'" class="badge rounded-pill text-bg-success">Approved</span>
                                                <span v-else-if="row.offering_status === 'submitted'" class="badge rounded-pill text-bg-info">Submitted</span>
                                                <span v-else-if="row.offering_status === 'draft'" class="badge rounded-pill text-bg-secondary">Draft</span>
                                                <span v-else-if="row.offering_status === 'declined'" class="badge rounded-pill text-bg-warning">Declined</span>
                                                <span v-else class="badge rounded-pill text-bg-danger">Inactive</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <h1 v-else class="lead">No results</h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal modal-lg fade" id="newInstOfferingModal" tabindex="-1"
             aria-labelledby="newInstOfferingModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newInstOfferingModalLabel">New Program Offering</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <InstitutionOfferingCreate :programs="programs" />
                </div>
            </div>
        </div>

        <div v-if="editOffering != ''" class="modal modal-lg fade" id="editInstOfferingModal" tabindex="-1"
             aria-labelledby="editInstOfferingModalLabel" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editInstOfferingModalLabel">Edit Program Offering</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <InstitutionOfferingEdit :programs="programs" :offering="editOffering" @close="closeEditForm" />
                </div>
            </div>
        </div>

    </AuthenticatedLayout>

</template>
<script>
import AuthenticatedLayout from '../Layouts/Authenticated.vue';
import InstitutionOfferingCreate from '../Components/InstitutionOfferingCreate.vue';
import InstitutionOfferingEdit from '../Components/InstitutionOfferingEdit.vue';
import Input from '@/Components/Input.vue';
import Label from '@/Components/Label.vue';
import { Head, useForm } from '@inertiajs/vue3';

export default {
    name: 'Offerings',
    components: {
        AuthenticatedLayout, InstitutionOfferingCreate, InstitutionOfferingEdit, Input, Label, Head
    },
    props: {
        results: Array,
        institution: Object,
        programs: Array,
        programYear: Object,
        filters: Object
    },
    data() {
        return {
            editOffering: '',
            searchForm: useForm({
                filter_name: this.filters && this.filters.filter_name ? this.filters.filter_name : '',
            }),
        }
    },
    methods: {
        search: function () {
            this.searchForm.get('/institution/offerings', { preserveState: true, preserveScroll: true });
        },
        resetSearch: function () {
            this.searchForm.filter_name = '';
            this.$inertia.visit('/institution/offerings');
        },
        openNewForm: function () {
            setTimeout(function () {
                $("#newInstOfferingModal").modal('show');
            }, 10);
        },
        openEditForm: function (offering) {
            this.editOffering = offering;
            setTimeout(function () {
                $("#editInstOfferingModal").modal('show');
            }, 10);
        },
        closeEditForm: function () {
            $("#editInstOfferingModal").modal('hide');
            let vm = this;
            setTimeout(function () {
                vm.editOffering = '';
            }, 1000);
        },
        formatDate: function (value) {
            if (value !== undefined && value !== null && value !== '') {
                return value.split("T")[0];
            }
            return '—';
        },
        formatProgramYear: function (py) {
            let label = (py.start_date || '').split('T')[0] + ' to ' + (py.end_date || '').split('T')[0];
            return label + ' (' + py.status + ')';
        }
    }
}
</script>
