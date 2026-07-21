<template>
    <Head title="Offerings" />

    <AuthenticatedLayout v-bind="$attrs">

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-header">
                            Offering Search
                        </div>
                        <div class="card-body">
                            <OfferingSearchBox :filters="filters" />
                        </div>
                    </div>
                </div>
                <div class="col-md-9 mb-3">
                    <div class="card">
                        <div class="card-header">
                            Offerings
                        </div>
                        <div class="card-body">
                            <div v-if="results != null && results.data.length > 0" class="table-responsive pb-3">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Offering Name</th>
                                            <th>Institution</th>
                                            <th>Program</th>
                                            <th>Program Year</th>
                                            <th>Location</th>
                                            <th>Study Start</th>
                                            <th>Study End</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in results.data" :key="row.id">
                                            <td>
                                                <Link v-if="row.program" :href="'/ministry/programs/' + row.program.id + '/offerings'">{{ row.offering_name }}</Link>
                                                <span v-else>{{ row.offering_name }}</span>
                                            </td>
                                            <td>
                                                <Link v-if="row.institution" :href="'/ministry/institutions/' + row.institution.id">{{ row.institution.name }}</Link>
                                                <span v-else>—</span>
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
                                <Pagination :links="results.links" :active-page="results.current_page" />
                            </div>
                            <div v-else class="text-center py-4">
                                <h1 class="lead">No results</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>
<script>
import AuthenticatedLayout from '../Layouts/Authenticated.vue';
import OfferingSearchBox from '../Components/OfferingSearch.vue';
import Pagination from "@/Components/Pagination";
import { Link, Head } from '@inertiajs/vue3';

export default {
    name: 'Offerings',
    components: {
        AuthenticatedLayout, OfferingSearchBox, Head, Link, Pagination
    },
    props: {
        results: Object,
        filters: Object
    },
    methods: {
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
