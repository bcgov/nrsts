<template>
    <form v-if="newApplicationForm != null" class="card-body">
        <div class="modal-body">

            <ClaimProfileFields :form="newApplicationForm" :utils="$attrs.utils" :individual="individual" />

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
                            <option v-if="p.active_status === true" :value="p.guid">{{ p.program_name }}</option>
                        </template>
                    </Select>
                </div>

                <div v-if="newApplicationForm.program_guid != ''" class="col-12">
                    <div class="form-check">
                        <label for="flexCheckChecked1" class="form-check-label">
                            {{ $attrs.utils['Student Agreement'][0].field_name }}
                        </label>
                        <input type="checkbox" class="form-check-input" id="flexCheckChecked1"
                               v-model="newApplicationForm.agreement_confirmed" :checked="newApplicationForm.agreement_confirmed" />
                    </div>
                    <div class="form-check">
                        <label for="flexCheckChecked2" class="form-check-label">
                            {{ $attrs.utils['Student Registration Confirmation'][0].field_name }}
                        </label>
                        <input type="checkbox" class="form-check-input" id="flexCheckChecked2"
                               v-model="newApplicationForm.registration_confirmed" :checked="newApplicationForm.registration_confirmed" />
                    </div>
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
            <button @click="save" type="button" class="btn me-2 btn-primary" :disabled="newApplicationForm.processing ||
            newApplicationForm.institution_guid == '' || newApplicationForm.program_guid == ''">Save Draft</button>
            <button @click="submitForm" type="button" class="btn btn-success" :disabled="newApplicationForm.processing ||
            newApplicationForm.institution_guid == '' || newApplicationForm.program_guid == ''">
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
import ClaimProfileFields from './ClaimProfileFields.vue';
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
                agreement_confirmed: false,
                registration_confirmed: false,
                claim_status: "Submitted",

                // Applicant profile (captured on the claim)
                first_name: "",
                middle_name: "",
                last_name: "",
                sin: "",
                dob: "",
                email: "",
                telephone: "",
                address_line1: "",
                address_line2: "",
                city: "",
                province: "",
                country: "Canada",
                zip_code: "",
                gender_identity: "",
                marital_status: "",
                number_of_dependants: "",
                disability_status: "",
                indigenous_identity: "",
                immigration_status: "",
                immigration_year: "",
                visible_minority_status: "",
                highest_education_level: "",
                official_language_choice: "",
                official_language_service: "",
                employment_status_intake: "",
                employment_status_exit: "",
                precarious_employment: "",
                intervention_name: "",
                intervention_code: "",
                intervention_start_date: "",
                intervention_end_date: "",
                intervention_outcome: "",
                credential_earned: "",
                noc_code: "",
                naics_code: "",
                action_plan_start_date: "",
                action_plan_end_date: "",
                action_plan_outcome: "",
                action_plan_outcome_date: "",
                literacy_essential_skills_increase: "",
            },
        }
    },
    computed: {
        individual() {
            return this.$attrs.individual_data?.individual ?? null;
        }
    },
    methods: {
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
        this.newApplicationForm = useForm(this.newApplicationFormData);
    }
}
</script>
