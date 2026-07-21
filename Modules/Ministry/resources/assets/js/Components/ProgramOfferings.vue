<template>
    <div class="card">
        <div class="card-header d-flex align-items-center">
            Offerings
            <div class="ms-auto d-flex align-items-center">
                <div class="d-flex align-items-center me-2" style="max-width: 320px;">
                    <label for="offeringYearFilter" class="form-label mb-0 me-2 text-nowrap"><small>Program Year</small></label>
                    <select id="offeringYearFilter" class="form-select form-select-sm" v-model="selectedProgramYear">
                        <option value="all">All Program Years</option>
                        <option v-for="py in programYears" :value="py.guid" :key="py.id">{{ formatProgramYear(py) }}</option>
                    </select>
                </div>
                <button type="button" class="btn btn-success btn-sm text-nowrap" @click="openNewForm">New Offering</button>
            </div>
        </div>
        <div class="card-body">
            <div v-if="filteredOfferings.length > 0" class="table-responsive pb-3">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Offering Name</th>
                            <th>Institution</th>
                            <th>Program Year</th>
                            <th>Location</th>
                            <th>Study Start</th>
                            <th>Study End</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, i) in filteredOfferings" :key="row.id">
                            <td><a href="#" @click.prevent="openEditForm(row)">{{ row.offering_name }}</a></td>
                            <td>
                                <Link v-if="row.institution" :href="'/ministry/institutions/' + row.institution.id">{{ row.institution.name }}</Link>
                                <span v-else>—</span>
                            </td>
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

    <div class="modal modal-lg fade" id="newOfferingModal" tabindex="-1"
         aria-labelledby="newOfferingModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newOfferingModalLabel">New Program Offering</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <ProgramOfferingCreate v-bind="$attrs" :results="results" :institutions="institutions" />
            </div>
        </div>
    </div>

    <div v-if="editOffering != ''" class="modal modal-lg fade" id="editOfferingModal" tabindex="-1"
         aria-labelledby="editOfferingModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOfferingModalLabel">Edit Program Offering</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <ProgramOfferingEdit v-bind="$attrs" :results="results" :institutions="institutions"
                                     :offering="editOffering" @close="closeEditForm" />
            </div>
        </div>
    </div>
</template>
<script>
import { Link } from '@inertiajs/vue3';
import ProgramOfferingCreate from "./ProgramOfferingCreate";
import ProgramOfferingEdit from "./ProgramOfferingEdit";

export default {
    name: 'ProgramOfferings',
    components: {
        Link, ProgramOfferingCreate, ProgramOfferingEdit
    },
    props: {
        results: Object,
        institutions: Object,
        programYears: Object
    },
    data() {
        return {
            editOffering: '',
            selectedProgramYear: 'all'
        }
    },
    computed: {
        filteredOfferings() {
            let offerings = this.results && this.results.offerings ? this.results.offerings : [];
            if (this.selectedProgramYear === 'all') {
                return offerings;
            }
            return offerings.filter(o => o.program_year_guid === this.selectedProgramYear);
        }
    },
    methods: {
        openNewForm: function () {
            setTimeout(function () {
                $("#newOfferingModal").modal('show');
            }, 10);
        },
        openEditForm: function (offering) {
            this.editOffering = offering;
            setTimeout(function () {
                $("#editOfferingModal").modal('show');
            }, 10);
        },
        closeEditForm: function () {
            $("#editOfferingModal").modal('hide');
            let vm = this;
            setTimeout(function () {
                vm.editOffering = '';
            }, 1000);
        },
        formatDate: function (value) {
            if (value !== undefined && value !== null && value !== '') {
                let date = value.split("T");

                return date[0];
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
