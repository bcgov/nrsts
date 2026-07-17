<template>
    <Head title="Programs" />

    <AuthenticatedLayout v-bind="$attrs">

        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-header">
                            Program Search
                        </div>
                        <div class="card-body">
                            <ProgramSearchBox />
                        </div>
                    </div>
                </div>
                <div class="col-md-9 mb-3">
                    <div v-if="flash && flash.success" class="alert alert-success" role="alert">{{ flash.success }}</div>
                    <div v-if="flash && flash.error" class="alert alert-danger" role="alert">{{ flash.error }}</div>
                    <div class="card">
                        <div class="card-header">
                            Programs
                        </div>
                        <div class="card-body">
                            <div v-if="results != null && results.data.length > 0" class="table-responsive pb-3">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Program Name</th>
                                            <th>Category</th>
                                            <th class="text-end"># Offerings</th>
                                            <th class="text-end"># Seats</th>
                                            <th class="text-end"># Levels</th>
                                            <th class="text-end">${{ supportPaymentPerWeek }}/week</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, i) in results.data" :key="row.id">
                                            <td><Link :href="'/ministry/programs/' + row.id">{{ row.program_name }}</Link></td>
                                            <td>{{ row.category }}</td>
                                            <td class="text-end">{{ row.offerings_count ?? 0 }}</td>
                                            <td class="text-end">{{ row.offerings_sum_total_seats ?? 0 }}</td>
                                            <td class="text-end">{{ row.number_levels ?? 0 }}</td>
                                            <td class="text-end">{{ formatCurrency(supportPaymentPerWeek * (row.number_weeks ?? 0) * (row.offerings_sum_total_seats ?? 0)) }}</td>
                                            <td>
                                                <span v-if="row.active_status" class="badge rounded-pill text-bg-success">Active</span>
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
import ProgramSearchBox from '../Components/ProgramSearch.vue';
import Pagination from "@/Components/Pagination";
import { Link, Head } from '@inertiajs/vue3';

export default {
    name: 'Programs',
    components: {
        AuthenticatedLayout, ProgramSearchBox, Head, Link, Pagination
    },
    props: {
        results: Object,
        supportPaymentPerWeek: {
            type: Number,
            default: 0
        }
    },
    computed: {
        flash() {
            return this.$page.props.flash;
        }
    },
    methods: {
        formatCurrency(value) {
            return (Number(value) || 0).toLocaleString('en-CA', {
                style: 'currency',
                currency: 'CAD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0,
            });
        }
    }
}
</script>
