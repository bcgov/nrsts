<template>
    <div>
        <div v-if="editStudentClaimForm == null" class="text-center m-5">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <form v-if="editStudentClaimForm != null && programs.length > 0" class="card-body">
            <div class="modal-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <Label for="inputProgram" class="form-label" value="Program" />
                        <p>{{ programs.find(p => p.guid === editStudentClaimForm.program_guid)?.program_name || ' - ' }}</p>
                    </div>
                    <div class="col-md-4">
                        <Label for="inputOfferingName" class="form-label" value="Offering Name" />
                        <p>{{ editStudentClaimForm.offering?.offering_name || ' - ' }}</p>
                    </div>
                    <div class="col-md-4">
                        <Label for="inputOfferingStart" class="form-label" value="Starting Date" />
                        <p>{{ editStudentClaimForm.offering?.start_date || ' - ' }}</p>
                    </div>

                    <ClaimProfileFields :form="editStudentClaimForm" :utils="$attrs.utils" :student-utils="$attrs.studentUtils" readonly />

                    <hr/>

                    <div class="col-md-4">
                        <Label for="inputClaimTotal" class="form-label" value="Claim Total" />
                        <p>${{ editStudentClaimForm.total_claim_amount }}</p>
                    </div>

                    <div class="col-md-4">
                        <Label class="form-label" value="Current Status" />
                        <p>{{ claim.claim_status }}</p>
                    </div>

                    <!-- Recorded outcome / reason for a claim that is already terminal -->
                    <div v-if="isTerminal && editStudentClaimForm.outcome_status" class="col-md-4">
                        <Label class="form-label" value="Outcome / Reason" />
                        <p>{{ editStudentClaimForm.outcome_status }}</p>
                    </div>

                    <div v-if="editStudentClaimForm.process_feedback != null" class="row">
                        <div class="col-12">
                            <div class="alert alert-warning mt-3 mb-3">
                                {{ editStudentClaimForm.process_feedback }}
                            </div>
                        </div>
                    </div>

                    <div v-if="editStudentClaimForm.errors != undefined" class="row">
                        <div class="col-12">
                            <div v-if="editStudentClaimForm.hasErrors == true" class="alert alert-danger mt-3">
                                <ul>
                                    <li v-for="err in editStudentClaimForm.errors">{{ err }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div v-if="!isTerminal" class="modal-footer d-flex justify-content-between">
                <div>
                    <button @click="submitForm('Declined')" v-if="['Submitted', 'Hold'].includes(claim.claim_status)" type="button" class="btn btn-sm btn-danger" :disabled="editStudentClaimForm.processing">
                        Decline
                    </button>
                    <button @click="submitForm('Dropped Out')" v-if="claim.claim_status === 'Training Started'" type="button" class="btn btn-sm btn-danger" :disabled="editStudentClaimForm.processing">
                        Dropped Out
                    </button>
                </div>
                <div class="float-end">
                    <button @click="submitForm('Hold')" v-if="claim.claim_status === 'Submitted'" type="button" class="me-3 btn btn-sm btn-warning" :disabled="editStudentClaimForm.processing">
                        Put on Hold
                    </button>
                    <button @click="submitForm('Training Started')" v-if="claim.claim_status === 'Hold'" type="button" class="btn btn-sm btn-success" :disabled="editStudentClaimForm.processing">
                        Start Training
                    </button>
                    <button @click="submitForm('Completed')" v-if="claim.claim_status === 'Training Started'" type="button" class="btn btn-sm btn-success" :disabled="editStudentClaimForm.processing">
                        Mark Completed
                    </button>
                </div>

            </div>
            <FormSubmitAlert :form-state="editStudentClaimForm.formState" :success-msg="editStudentClaimForm.formSuccessMsg"
                             :fail-msg="editStudentClaimForm.formFailMsg"></FormSubmitAlert>
        </form>
    </div>


</template>
<script>
import Select from '@/Components/Select.vue';
import Input from '@/Components/Input.vue';
import Label from '@/Components/Label.vue';
import FormSubmitAlert from '@/Components/FormSubmitAlert.vue';
import ClaimProfileFields from '@/Components/ClaimProfileFields.vue';
import {Link, useForm} from '@inertiajs/vue3';

export default {
    name: 'ClaimEdit',
    components: {
        Input, Label, Select, Link, useForm, FormSubmitAlert, ClaimProfileFields
    },
    props: {
        programYears: Object,
        results: Object,
        claim: Object,
        page: String,
        subPage: String
    },
    data() {
        return {
            editStudentClaimForm: null,
            editStudentClaimFormData: {
                formState: true,
                formSuccessMsg: 'Form was submitted successfully.',
                formFailMsg: 'There was an error submitting this form.',
                institution_guid: "",
                total_amount: "",
                program_year_guid: "",
                status: "",
            },
            programs: []
        }
    },
    computed: {
        isTerminal() {
            return ['Completed', 'Declined', 'Dropped Out', 'Cancelled', 'Expired'].includes(this.claim.claim_status);
        },
    },
    methods: {
        submitForm: function (status) {
            const reasonRequired = ['Declined', 'Dropped Out'];

            if (reasonRequired.includes(status)) {
                // Declined / Dropped Out prompt for a reason, stored in outcome_status.
                const action = status === 'Declined' ? 'declining this claim' : 'marking this claim as Dropped Out';
                const reason = window.prompt('Please provide a reason for ' + action + ':', '');
                if (reason === null) {
                    return false;
                }
                if (reason.trim() === '') {
                    alert('A reason is required for this action.');
                    return false;
                }
                this.editStudentClaimForm.outcome_status = reason.trim();
            } else {
                // Other statuses do not carry a reason; keep outcome clean and confirm the action.
                this.editStudentClaimForm.outcome_status = null;

                const prompts = {
                    'Hold': 'put this claim On Hold',
                    'Training Started': 'mark this claim as Training Started',
                    'Completed': 'mark this claim as Completed. This finalizes the program',
                };

                if (!confirm('You are about to ' + (prompts[status] || 'update this claim') + '. Proceed?')) {
                    return false;
                }
            }

            this.editStudentClaimForm.claim_status = status;

            this.editStudentClaimForm.formState = null;
            this.editStudentClaimForm.put(`/institution/claims${window.location.search}`, {
                onSuccess: (response) => {
                    this.editStudentClaimForm.formState = true;
                    this.$emit('close');
                },
                onError: () => {
                    this.editStudentClaimForm.formState = false;
                },
                preserveState: true
            });
        },
        fetchData: function () {
            let vm = this;
            let data = {
                institution_guid: this.results.guid,
            }
            axios.get('/institution/api/fetch/claims/' + this.claim.guid)
                .then(function (response) {
                    vm.programs = response.data.programs;
                    vm.editStudentClaimFormData = response.data.claim;
                    vm.editStudentClaimFormData.formState = null;
                    vm.editStudentClaimFormData.page = vm.page;
                    vm.editStudentClaimFormData.subpage = vm.subPage;
                    vm.editStudentClaimForm = useForm(vm.editStudentClaimFormData);


                })
                .catch(function (error) {
                    // handle error
                    console.log(error);
                });
        },
    },
    mounted() {
        this.fetchData();
    },
    watch: {
        claim: function (newVal, oldVal) {
            if (newVal != null) {
                this.fetchData();
            }
        },
    },
}
</script>
