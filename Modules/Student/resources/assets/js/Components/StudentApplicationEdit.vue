<template>
    <form v-if="newApplicationForm != null" class="card-body">
        <div class="modal-body">
            <div class="row g-3">

                <ClaimProfileFields :form="newApplicationForm" :utils="$attrs.utils" :student-utils="$attrs.studentUtils" />

                <div v-if="institutions != null && institutions.length > 0" class="row col-12 g-3 mt-0">
                    <div class="col-12">
                        <Label for="inputInstGuid" class="form-label" value="Institution Name"/>
                        <Select @change="fetchPrograms($event)" class="form-select" id="inputInstGuid" v-model="newApplicationForm.institution_guid">
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
                <div v-else class="row g-3">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="modal-footer">
            <button @click="save" type="button" class="btn me-2 btn-primary" :disabled="newApplicationForm.processing ||
            newApplicationForm.institution_guid == '' || newApplicationForm.program_guid == ''">Save Draft</button>
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
    name: 'StudentApplicationEdit',
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
            this.newApplicationForm.put('/applications', {
                onSuccess: (response) => {
                    $("#editApplicationModal").modal('hide');
                    this.newApplicationForm.reset(this.newApplicationFormData);

                    this.$inertia.visit('/applications');
                    // console.log(response.props.institution)
                },
                onError: () => {
                    this.newApplicationForm.formState = false;
                },
                preserveState: true
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
        this.newApplicationForm.claim_status = 'Submitted';

        if (this.application.institution_guid) {
            this.fetchPrograms(this.application.institution_guid);
        } else {
            this.programs = [];
        }
        // this.newApplicationForm.institution_guid = this.results.guid;
    }
}
</script>
