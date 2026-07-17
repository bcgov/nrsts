<template>
    <Head title="Program" />

    <AuthenticatedLayout v-bind="$attrs">

        <div v-if="results != null" class="container-fluid">

            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="card">
                        <div class="card-header">
                            Program Menu
                        </div>
                        <div class="card-body">
                            <ProgramMenu :page="page" :id="results.id" :name="results.program_name" :offerings-count="results.offerings ? results.offerings.length : 0" />
                        </div>
                    </div>
                </div>
                <div class="col-md-9 mb-3">
                    <ProgramDetails v-bind="$attrs" v-if="page === 'details'" :results="results"></ProgramDetails>
                    <ProgramOfferings v-bind="$attrs" v-if="page === 'offerings'" :results="results" :institutions="institutions" :program-years="programYears"></ProgramOfferings>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

</template>
<script>
import AuthenticatedLayout from '../Layouts/Authenticated.vue';
import { Head, Link } from '@inertiajs/vue3';
import ProgramMenu from "../Components/ProgramMenu";
import ProgramDetails from "../Components/ProgramDetails";
import ProgramOfferings from "../Components/ProgramOfferings";

export default {
    name: 'Program',
    components: {
        AuthenticatedLayout, Head, Link, ProgramMenu, ProgramDetails, ProgramOfferings
    },
    props: {
        results: Object,
        page: String,
        institutions: Object,
        programYears: Object
    }
}
</script>
