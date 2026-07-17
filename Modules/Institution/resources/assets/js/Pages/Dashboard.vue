<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout v-bind="$attrs">

        <div class="container">

            <!-- Institution info -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="display-5">{{ results.name }}</div>
                            <p class="mb-1">Welcome {{ $attrs.auth.user.first_name }} {{ $attrs.auth.user.last_name }}</p>
                            <h5 class="fw-light mb-0">
                                Active Program Year:
                                <strong>{{ programYear.start_date }} to {{ programYear.end_date }}</strong>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Application / claim stats -->
            <div class="row g-3 mb-3">
                <div class="col-6 col-md">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="display-6">{{ stats.submitted }}</div>
                            <div class="text-muted small text-uppercase">Submitted</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="display-6">{{ stats.hold }}</div>
                            <div class="text-muted small text-uppercase">Hold</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="display-6">{{ stats.trainingStarted }}</div>
                            <div class="text-muted small text-uppercase">Training Started</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="display-6">{{ stats.trainingEnded }}</div>
                            <div class="text-muted small text-uppercase">Training Ended</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="display-6">{{ stats.completed }}</div>
                            <div class="text-muted small text-uppercase">Completed</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seats and funding -->
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header">Seats</div>
                        <div class="card-body">
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="display-6">{{ seatSummary.total }}</div>
                                    <div class="text-muted small text-uppercase">Total Seats</div>
                                </div>
                                <div class="col-4">
                                    <div class="display-6">{{ seatSummary.used }}</div>
                                    <div class="text-muted small text-uppercase">Used</div>
                                </div>
                                <div class="col-4">
                                    <div class="display-6">{{ seatSummary.available }}</div>
                                    <div class="text-muted small text-uppercase">Available</div>
                                </div>
                            </div>
                            <div class="progress" role="progressbar" :aria-valuenow="seatSummary.used"
                                 aria-valuemin="0" :aria-valuemax="seatSummary.total" style="height: 1.25rem;">
                                <div class="progress-bar bg-success"
                                     :style="{ width: seatUsagePercent + '%' }">
                                    {{ seatUsagePercent }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center h-100">
                        <div class="card-header">Total Funding</div>
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="display-5">${{ $formatNumberWithCommas(Number(totalFunding).toFixed(2)) }}</div>
                            <div class="text-muted small">
                                ${{ supportPaymentPerWeek }}/week &times; seats &times; weeks
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Per-offering breakdown -->
            <div class="card">
                <div class="card-header">Offerings</div>
                <div class="card-body">
                    <div v-if="!offeringSummaries || offeringSummaries.length === 0" class="text-center text-muted">
                        You have no active offerings for this program year.
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Offering</th>
                                    <th class="text-end">Weeks</th>
                                    <th class="text-end">Seats</th>
                                    <th class="text-end">Used</th>
                                    <th class="text-end">Available</th>
                                    <th class="text-end">Funding</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="offering in offeringSummaries" :key="offering.guid">
                                    <td>{{ offering.offering_name }}</td>
                                    <td class="text-end">{{ offering.number_weeks }}</td>
                                    <td class="text-end">{{ offering.total_seats }}</td>
                                    <td class="text-end">{{ offering.seats_used }}</td>
                                    <td class="text-end">{{ offering.seats_available }}</td>
                                    <td class="text-end">${{ $formatNumberWithCommas(Number(offering.funding).toFixed(2)) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold border-top">
                                    <td>Total</td>
                                    <td class="text-end"></td>
                                    <td class="text-end">{{ seatSummary.total }}</td>
                                    <td class="text-end">{{ seatSummary.used }}</td>
                                    <td class="text-end">{{ seatSummary.available }}</td>
                                    <td class="text-end">${{ $formatNumberWithCommas(Number(totalFunding).toFixed(2)) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </AuthenticatedLayout>

</template>
<script>
import AuthenticatedLayout from '../Layouts/Authenticated.vue';
import { Link, Head } from '@inertiajs/vue3';

export default {
    name: 'Dashboard',
    components: {
        AuthenticatedLayout, Head, Link
    },
    props: {
        results: Object,
        programYear: Object,
        offeringSummaries: Array,
        stats: Object,
        seatSummary: Object,
        totalFunding: Number,
        supportPaymentPerWeek: Number
    },
    computed: {
        seatUsagePercent() {
            const total = Number(this.seatSummary?.total || 0);
            if (total === 0) {
                return 0;
            }
            return Math.round((Number(this.seatSummary?.used || 0) / total) * 100);
        }
    }
}
</script>
