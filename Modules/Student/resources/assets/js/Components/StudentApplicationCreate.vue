<template>
    <form v-if="newApplicationForm != null" class="card-body">
        <div class="modal-body">

            <div v-if="hasPrefill" class="alert alert-info d-flex justify-content-between align-items-center py-2">
                <span>Verified applicant details are available from PDEX.</span>
                <button @click="autofillFromPdex" type="button" class="btn btn-sm btn-outline-primary">Auto-fill from PDEX</button>
            </div>

            <ClaimProfileFields :form="newApplicationForm" :utils="$attrs.utils" :student-utils="$attrs.studentUtils" />

            <div class="row g-3 mt-1">
                <div class="col-12">
                    <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Program</h6>
                </div>
                <div class="col-12">
                    <Label for="inputInstGuid" class="form-label" value="Institution Name"/>
                    <Select @change="fetchPrograms($event)" v-if="institutions != null && institutions.length > 0" class="form-select" id="inputInstGuid" v-model="newApplicationForm.institution_guid">
                        <option value=""></option>
                        <template  v-for="p in institutions">
                            <option v-if="p.active_status === true" :value="p.guid">{{ p.name }}</option>
                        </template>
                    </Select>
                </div>
                <div v-if="programs != null && programs.length > 0" class="col-12">
                    <Label for="inputProgramGuid" class="form-label" value="Program Name"/>
                    <Select class="form-select" id="inputProgramGuid" v-model="newApplicationForm.program_guid">
                        <option></option>
                        <template  v-for="p in programs">
                            <option v-if="p.active_status === true" :value="p.guid">{{ programLabel(p) }}</option>
                        </template>
                    </Select>
                </div>

                <div class="col-12">
                    <Label for="inputApprenticeNumber" class="form-label" value="SkilledTradesBC Registration Number"/>
                    <input id="inputApprenticeNumber" type="text" class="form-control" v-model="newApplicationForm.apprentice_number" />
                </div>

                <div v-if="newApplicationForm.processing" class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-if="newApplicationForm.errors != undefined" class="row">
                    <div class="col-12">
                        <div v-if="newApplicationForm.hasErrors == true" class="alert alert-danger mt-3">
                            <ul>
                                <li v-for="err in newApplicationForm.errors" v-html="err"></li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="modal-footer">
            <button @click="save" type="button" class="btn me-2 btn-primary" :disabled="newApplicationForm.processing">Save Draft</button>
            <button @click="submitForm" type="button" class="btn btn-success" :disabled="newApplicationForm.processing ||
            newApplicationForm.institution_guid == '' || newApplicationForm.program_guid == '' ||
            !newApplicationForm.apprentice_number">
                Submit Application
            </button>
        </div>
        <FormSubmitAlert :form-state="newApplicationForm.formState" :success-msg="newApplicationForm.formSuccessMsg"
                         :fail-msg="newApplicationForm.formFailMsg"></FormSubmitAlert>
    </form>

</template>
<script>
import Select from '@/Components/Select.vue';
import Input from '@/Components/Input.vue';
import Label from '@/Components/Label.vue';
import FormSubmitAlert from '@/Components/FormSubmitAlert.vue';
import ClaimProfileFields from '@/Components/ClaimProfileFields.vue';
import {Link, useForm} from '@inertiajs/vue3';

export default {
    name: 'StudentApplicationCreate',
    components: {
        Input, Label, Select, Link, useForm, FormSubmitAlert, ClaimProfileFields
    },
    props: {
        institutions: Object,
        results: Object
    },
    data() {
        return {
            programs: null,
            newApplicationForm: null,
            newApplicationFormData: {
                formState: true,
                formSuccessMsg: 'Form was submitted successfully.',
                formFailMsg: 'There was an error submitting this form.',

                // Program selection
                institution_guid: "",
                program_guid: "",
                apprentice_number: "",
                claim_status: "Submitted",

                // Applicant profile (captured on the claim)
                first_name: "",
                middle_name: "",
                last_name: "",
                social_insurance_number: "",
                date_of_birth: "",
                email_address: "",
                phone_number: "",
                address_line1: "",
                address_line2: "",
                city: "",
                province: "",
                region: "",
                country: "Canada",
                postal_code: "",
                gender: "",
                marital_status: "",
                number_of_dependants: "",
                disability_status: false,
                indigenous_status: false,
                indigenous_group: "",
                immigration_status: "",
                immigration_year: "",
                visible_minority_status: "",
                racial_identity: "",
                is_visible_minority: false,
                highest_level_of_education: "",
                spoken_language: "",
                intervention_language_of_service: "",
                employment_status_intake: "",
                employment_status_exit: "",
                precarious_employment: "",
                intervention_outcome: "",
            },
        }
    },
    computed: {
        // BCSC individual token data mapped to claim columns, used to prefill a new claim.
        claimPrefill() {
            return this.$attrs.claim_prefill ?? {};
        },
        // Whether any PDEX prefill data is available to offer auto-fill.
        hasPrefill() {
            return Object.keys(this.claimPrefill).length > 0;
        }
    },
    methods: {
        // Build the program option label as "Program Name (start to end)" using the offering dates.
        programLabel: function (p) {
            let label = p.program_name;
            if (p.offerings && p.offerings.length > 0) {
                let offering = p.offerings[0];
                let start = offering.start_date ? offering.start_date.split('T')[0] : '';
                let end = offering.end_date ? offering.end_date.split('T')[0] : '';
                if (start || end) {
                    label += ' (' + start + ' to ' + end + ')';
                }
            }
            return label;
        },
        // Populate the form with the BCSC individual token data (PDEX) on demand.
        autofillFromPdex: function () {
            Object.assign(this.newApplicationForm, this.claimPrefill);
        },
        save: function () {
            this.newApplicationForm.claim_status = 'Draft';
            this.submitForm();
        },
        submitForm: function () {
            if(this.newApplicationForm.claim_status === 'Submitted'){
                let check = confirm('You are about to submit this application. You are not going to be able to edit this application anymore. Proceed?');
                if(!check){
                    return false;
                }
            }

            this.newApplicationForm.formState = null;
            this.newApplicationForm.post('/applications', {
                onSuccess: (response) => {
                    $("#newApplicationModal").modal('hide');
                    this.newApplicationForm.reset(this.newApplicationFormData);

                    this.$inertia.visit('/applications');
                },
                onError: () => {
                    this.newApplicationForm.formState = false;
                },
                preserveState: true
            });
        },

        fetchPrograms: function (e) {
            let vm = this;
            this.programs = null;
            this.newApplicationForm.program_guid = '';
            this.newApplicationForm.processing = true;
            axios.get('/student/api/fetch/institutions/' + e.target.value)
                .then(function (response) {
                    vm.programs = response.data.institution.active_programs;
                    vm.newApplicationForm.processing = false;

                })
                .catch(function (error) {
                    // handle error
                    vm.newApplicationForm.processing = false;
                    console.log(error);
                });
        },
    },

    mounted() {
        // Start with an empty form; PDEX data can be applied via the auto-fill option.
        this.newApplicationForm = useForm({ ...this.newApplicationFormData });
    }
}
</script>
