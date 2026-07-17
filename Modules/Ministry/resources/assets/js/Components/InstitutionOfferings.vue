<template>
    <div class="card">
        <div class="card-header">
            Institution Offerings
            <div class="float-end d-flex align-items-center" style="max-width: 320px;">
                <label for="offeringYearFilter" class="form-label mb-0 me-2 text-nowrap"><small>Program Year</small></label>
                <select id="offeringYearFilter" class="form-select form-select-sm" v-model="selectedProgramYear">
                    <option value="all">All Program Years</option>
                    <option v-for="py in programYears" :value="py.guid" :key="py.id">{{ formatProgramYear(py) }}</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div v-if="filteredOfferings.length > 0" class="table-responsive pb-3">
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
                        <tr v-for="row in filteredOfferings" :key="row.id">
                            <td>
                                <Link v-if="row.program" :href="'/ministry/programs/' + row.program.id + '/offerings'">{{ row.offering_name }}</Link>
                                <span v-else>{{ row.offering_name }}</span>
                            </td>
                            <td>
                                <Link v-if="row.program" :href="'/ministry/programs/' + row.program.id">{{ row.program.program_name }}</Link>
                                <span v-else>—</span>
                            </td>
                            <td>{{ row.py ? formatProgramYear(row.py) : '—' }}</td>
                            <td>{{ row.location_name || '—' }}</td>
                            <td>{{ formatDate(row.start_date) }}</td>
                            <td>{{ formatDate(row.end_date) }}</td>
                            <td>
                                <span v-if="row.active_status" class="badge rounded-pill text-bg-success">Active</span>
                                <span v-else class="badge rounded-pill text-bg-danger">Inactive</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h1 v-else class="lead">No results</h1>
        </div>
    </div>
</template>
<script>
import { Link } from '@inertiajs/vue3';

export default {
    name: 'InstitutionOfferings',
    components: {
        Link
    },
    props: {
        results: Object,
        programYears: Object
    },
    data() {
        return {
            selectedProgramYear: 'all'
        }
    },
    computed: {
        offerings() {
            return this.results && this.results.offerings ? this.results.offerings : [];
        },
        filteredOfferings() {
            if (this.selectedProgramYear === 'all') {
                return this.offerings;
            }
            return this.offerings.filter(o => o.program_year_guid === this.selectedProgramYear);
        }
    },
    methods: {
        formatDate(value) {
            if (value !== undefined && value !== null && value !== '') {
                return value.split('T')[0];
            }
            return '—';
        },
        formatProgramYear(py) {
            let label = (py.start_date || '').split('T')[0] + ' to ' + (py.end_date || '').split('T')[0];
            return label + ' (' + py.status + ')';
        }
    }
}
</script>
