<template>
    <Head title="Institution" />

    <AuthenticatedLayout v-bind="$attrs">

            <div v-if="results != null" class="container-fluid">

                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <div class="card-header">
                                Institution Menu
                            </div>
                            <div class="card-body">
                                <InstitutionMenu :page="page" :id="results.id" :name="results.name" :applications-count="results.claims_count" :offerings-count="results.offerings_count" :staff-count="results.staff_count" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9 mb-3">
                        <InstitutionDetails v-bind="$attrs" v-if="page === 'details'" :results="results"></InstitutionDetails>
                        <InstitutionOfferings v-bind="$attrs" v-if="page === 'offerings'" :results="results" :program-years="programYears" :support-payment-per-week="supportPaymentPerWeek"></InstitutionOfferings>
                        <InstitutionStaff v-bind="$attrs" v-if="page === 'staff'" :results="results"></InstitutionStaff>
                        <InstitutionClaimsByCourse v-bind="$attrs" v-if="page === 'claims-by-course'" :results="results"></InstitutionClaimsByCourse>
                        <InstitutionClaimsByStudent v-bind="$attrs" v-if="page === 'claims-by-student'" :results="results"></InstitutionClaimsByStudent>
                    </div>
                </div>
            </div>
    </AuthenticatedLayout>

</template>
<script>
import AuthenticatedLayout from '../Layouts/Authenticated.vue';
import { Head, Link } from '@inertiajs/vue3';
import InstitutionMenu from "../Components/InstitutionMenu";
import InstitutionDetails from "../Components/InstitutionDetails";
import InstitutionClaimsByCourse from "../Components/InstitutionClaimsByCourse";
import InstitutionClaimsByStudent from "../Components/InstitutionClaimsByStudent";
import InstitutionStaff from "../Components/InstitutionStaff";
import InstitutionOfferings from "../Components/InstitutionOfferings";

export default {
    name: 'Institution',
    components: {
        InstitutionOfferings,
        InstitutionMenu,
        AuthenticatedLayout, Head, Link, InstitutionDetails, InstitutionClaimsByCourse, InstitutionClaimsByStudent,
        InstitutionStaff
    },
    props: {
        results: Object,
        page: String,
        countries: Object,
        programYears: Object,
        activeClaims: Object,
        supportPaymentPerWeek: [Number, String]
    },
    mounted() {
    }
}
</script>
