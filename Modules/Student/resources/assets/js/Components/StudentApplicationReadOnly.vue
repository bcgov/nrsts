<template>
    <form v-if="newApplicationForm != null" class="card-body">
        <div class="modal-body">
            <div v-if="programs != null && programs.length > 0" class="row g-3">

                <ClaimProfileFields :form="newApplicationForm" :utils="$attrs.utils" :student-utils="$attrs.studentUtils" readonly />

                <div class="row col-12 g-3 mt-0">
                    <div class="col-12">
                        <Label for="inputInstGuid" class="form-label" value="Institution Name"/>
                        <Select @change="fetchPrograms($event)" v-if="institutions != null && institutions.length > 0" class="form-select" id="inputInstGuid" v-model="newApplicationForm.institution_guid" readonly="readonly" disabled>
                            <template  v-for="p in institutions">
                                <option v-if="p.active_status === true" :value="p.guid">{{ p.name }}</option>
                            </template>
                        </Select>
                    </div>
                    <div class="col-12">
                        <Label for="inputProgramGuid" class="form-label" value="Program Name"/>
                        <Select class="form-select" id="inputProgramGuid" v-model="newApplicationForm.program_guid"  readonly="readonly" disabled>
                            <option></option>
                            <template  v-for="p in programs">
                                <option v-if="p.active_status === true" :value="p.guid">{{ p.program_name }}</option>
                            </template>
                        </Select>
                    </div>
                    
                    <div class="col-md-6">
                        <Label for="inputApprenticeNumber" class="form-label" value="SkilledTradesBC Registration Number"/>
                        <input id="inputApprenticeNumber" type="text" class="form-control" v-model="application.apprentice_number" readonly="readonly" disabled />
                    </div>

                    <div v-if="application.ei_reference_code" class="col-md-6">
                        <Label for="inputEiRef" class="form-label" value="EI Reference Code"/>
                        <Input type="text" class="form-control" id="inputEiRef" :value="application.ei_reference_code" readonly="readonly" disabled/>
                    </div>

                    <!-- Training Ended flow: the learner reports their exit employment status -->
                    <div v-if="application.claim_status === 'Training Started'" class="col-12">
                        <div class="alert alert-info">
                            <div class="form-check">
                                <input id="pf_end_training" type="checkbox" class="form-check-input" v-model="endingTraining" />
                                <label class="form-check-label" for="pf_end_training">I have completed my training</label>
                            </div>
                            <div v-if="endingTraining" class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label" for="pf_exit_status">Employment Status (Exit)</label>
                                    <select id="pf_exit_status" class="form-select" v-model="exitEmploymentStatus">
                                        <option value=""></option>
                                        <option v-for="opt in exitOptions" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="transitionError" class="col-12">
                        <div class="alert alert-danger">{{ transitionError }}</div>
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
                                    <li v-for="err in newApplicationForm.errors">{{ err }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="row g-3">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button v-if="application.claim_status === 'Training Started' && endingTraining" type="button" class="btn btn-primary" :disabled="transitioning" @click="endTraining">
                End Training
            </button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Close</button>

        </div>
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
    name: 'StudentApplicationReadOnly',
    components: {
        Input, Label, Select, Link, useForm, FormSubmitAlert, ClaimProfileFields
    },
    props: {
        results: Object,
        application: Object,
        institutions: Object
    },
    data() {
        return {

            programs: null,
            newApplicationForm: null,
            endingTraining: false,
            exitEmploymentStatus: '',
            transitioning: false,
            transitionError: '',
            newApplicationFormData: {
                formState: true,
                formSuccessMsg: 'Form was submitted successfully.',
                formFailMsg: 'There was an error submitting this form.',
                institution_guid: "",
                program_guid: "",
                claim_status: "Submitted"
            },
        }
    },
    computed: {
        
        exitOptions() {
            const studentUtils = this.$attrs.studentUtils;
            return (studentUtils && studentUtils.options && studentUtils.options.employment_status) || [];
        },
    },
    methods: {

        startApprenticeProgram: function () {
            let vm = this;
            this.transitionError = '';
            this.transitioning = true;
            this.$inertia.put('/student/applications/transition', {
                id: this.application.id,
                claim_status: 'Training Started',
            }, {
                preserveScroll: true,
                onSuccess: function () {
                    vm.transitioning = false;
                    vm.$emit('close');
                },
                onError: function (errors) {
                    vm.transitioning = false;
                    vm.transitionError = Object.values(errors).join(' ');
                },
            });
        },

        endTraining: function () {
            let vm = this;
            this.transitionError = '';
            if (!this.exitEmploymentStatus) {
                this.transitionError = 'Please select your exit employment status.';
                return;
            }
            this.transitioning = true;
            this.$inertia.put('/student/applications/transition', {
                id: this.application.id,
                claim_status: 'Training Ended',
                employment_status_exit: this.exitEmploymentStatus,
            }, {
                preserveScroll: true,
                onSuccess: function () {
                    vm.transitioning = false;
                    vm.$emit('close');
                },
                onError: function (errors) {
                    vm.transitioning = false;
                    vm.transitionError = Object.values(errors).join(' ');
                },
            });
        },

        fetchPrograms: function (e) {
            let guid = e;
            if(e.target != undefined){
                guid = e.target.value;
                this.newApplicationForm.program_guid = '';
            }
            let vm = this;
            this.programs = null;
            this.newApplicationForm.processing = true;
            axios.get('/student/api/fetch/institutions/' + guid)
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
        this.newApplicationForm = useForm(this.application);
        
        this.fetchPrograms(this.application.institution_guid);
        // this.newApplicationForm.institution_guid = this.results.guid;
    }
}
</script>
