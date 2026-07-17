<template>
    <form v-if="newOfferingForm != null" class="card-body">
        <div class="modal-body">
            <div class="row g-3">

                <div class="col-md-12">
                    <Label for="inputInstitution" class="form-label" value="Institution" />
                    <Select class="form-select" id="inputInstitution" v-model="newOfferingForm.institution_guid">
                        <option value=""></option>
                        <option v-for="inst in institutions" :value="inst.guid" :key="inst.id">{{ inst.name }}</option>
                    </Select>
                </div>

                <div class="col-md-12">
                    <Label for="inputProgramYear" class="form-label" value="Program Year" />
                    <Select class="form-select" id="inputProgramYear" v-model="newOfferingForm.program_year_guid">
                        <option value=""></option>
                        <option v-for="py in programYears" :value="py.guid" :key="py.id">{{ formatProgramYear(py) }}</option>
                    </Select>
                </div>

                <div class="col-md-12">
                    <Label for="inputOfferingName" class="form-label" value="Offering Name" />
                    <Input type="text" class="form-control" id="inputOfferingName" v-model="newOfferingForm.offering_name" />
                </div>

                <div class="col-md-12">
                    <Label for="inputOfferingDescription" class="form-label" value="Description" />
                    <textarea class="form-control" id="inputOfferingDescription" rows="2" v-model="newOfferingForm.offering_description"></textarea>
                </div>

                <div class="col-md-6">
                    <Label for="inputStudyStart" class="form-label" value="Study Start Date" />
                    <Input type="date" class="form-control" id="inputStudyStart" v-model="newOfferingForm.start_date" />
                </div>

                <div class="col-md-6">
                    <Label for="inputStudyEnd" class="form-label" value="Study End Date" />
                    <Input type="date" class="form-control" id="inputStudyEnd" v-model="newOfferingForm.end_date" />
                </div>

                <div class="col-md-8">
                    <Label for="inputLocation" class="form-label" value="Location" />
                    <Input type="text" class="form-control" id="inputLocation" v-model="newOfferingForm.location_name" />
                </div>

                <div class="col-md-6">
                    <Label for="inputTotalAmount" class="form-label" value="Total Budget" />
                    <Input type="number" step="0.01" min="0" class="form-control" id="inputTotalAmount" v-model="newOfferingForm.total_amount" />
                </div>

                <div class="col-md-6">
                    <Label for="inputTotalSeats" class="form-label" value="Total Seats" />
                    <Input type="number" min="0" class="form-control" id="inputTotalSeats" v-model="newOfferingForm.total_seats" />
                </div>

                <div class="col-md-4">
                    <Label for="inputActiveStatus" class="form-label" value="Status" />
                    <Select class="form-select" id="inputActiveStatus" v-model="newOfferingForm.active_status">
                        <option :value="true">Active</option>
                        <option :value="false">Inactive</option>
                    </Select>
                </div>

                <div v-if="newOfferingForm.errors != undefined" class="row">
                    <div class="col-12">
                        <div v-if="newOfferingForm.hasErrors == true" class="alert alert-danger mt-3">
                            <ul>
                                <li v-for="err in newOfferingForm.errors">{{ err }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="modal-footer">
            <button @click="submitForm" type="button" class="btn btn-sm btn-success" :disabled="newOfferingForm.processing">
                Create Offering
            </button>
        </div>
        <FormSubmitAlert :form-state="newOfferingForm.formState" :success-msg="newOfferingForm.formSuccessMsg"
                         :fail-msg="newOfferingForm.formFailMsg"></FormSubmitAlert>
    </form>
</template>
<script>
import Select from '@/Components/Select.vue';
import Input from '@/Components/Input.vue';
import Label from '@/Components/Label.vue';
import FormSubmitAlert from '@/Components/FormSubmitAlert.vue';
import { useForm } from '@inertiajs/vue3';

export default {
    name: 'ProgramOfferingCreate',
    components: {
        Input, Label, Select, FormSubmitAlert
    },
    props: {
        results: Object,
        institutions: Object,
        programYears: Object
    },
    data() {
        return {
            newOfferingForm: null,
            newOfferingFormData: {
                formState: true,
                formSuccessMsg: 'Form was submitted successfully.',
                formFailMsg: 'There was an error submitting this form.',
                program_guid: "",
                program_year_guid: "",
                institution_guid: "",
                offering_name: "",
                offering_description: "",
                start_date: "",
                end_date: "",
                location_name: "",
                total_amount: 0,
                total_seats: 0,
                active_status: true,
            },
        }
    },
    methods: {
        formatProgramYear: function (py) {
            let label = (py.start_date || '').split('T')[0] + ' to ' + (py.end_date || '').split('T')[0];
            return label + ' (' + py.status + ')';
        },
        submitForm: function () {
            this.newOfferingForm.formState = null;
            this.newOfferingForm.post('/ministry/program-offerings', {
                onSuccess: () => {
                    $("#newOfferingModal").modal('hide');
                    this.newOfferingForm.reset(this.newOfferingFormData);
                    this.$inertia.visit('/ministry/programs/' + this.results.id + '/offerings');
                },
                onError: () => {
                    this.newOfferingForm.formState = false;
                },
                preserveState: true
            });
        }
    },
    mounted() {
        this.newOfferingForm = useForm(this.newOfferingFormData);
        this.newOfferingForm.program_guid = this.results.guid;

        // Default the offering to the active program year when one exists.
        let activePy = (this.programYears || []).find(py => py.status === 'active');
        if (activePy) {
            this.newOfferingForm.program_year_guid = activePy.guid;
        }
    }
}
</script>
