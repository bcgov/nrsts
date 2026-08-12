<template>
    <form v-if="newOfferingForm != null" class="card-body">
        <div class="modal-body">
            <div class="row g-3">

                <div class="col-md-12">
                    <Label for="inputProgram" class="form-label" value="Program" />
                    <Select class="form-select" id="inputProgram" v-model="newOfferingForm.program_guid">
                        <option value=""></option>
                        <option v-for="prog in programs" :value="prog.guid" :key="prog.id">{{ prog.program_name }}</option>
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

                <div class="col-md-4">
                    <Label for="inputLocation" class="form-label" value="Location" />
                    <Input type="text" class="form-control" id="inputLocation" v-model="newOfferingForm.location_name" />
                </div>

                <div class="col-md-4">
                    <Label for="inputTotalSeats" class="form-label" value="Total Seats" />
                    <Input type="number" min="0" class="form-control" id="inputTotalSeats" v-model="newOfferingForm.total_seats" />
                </div>

                <div class="col-md-4">
                    <Label for="inputLangService" class="form-label" value="Intervention Language of Service" />
                    <Select class="form-select" id="inputLangService" v-model="newOfferingForm.intervention_language_of_service">
                        <option value=""></option>
                        <option v-for="opt in languageServiceOptions" :key="opt" :value="opt">{{ opt }}</option>
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
            <button @click="submitForm('draft')" type="button" class="btn btn-sm btn-outline-secondary" :disabled="newOfferingForm.processing">
                Save Draft
            </button>
            <button @click="submitForm('submitted')" type="button" class="btn btn-sm btn-success" :disabled="newOfferingForm.processing">
                Submit Offering Request
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
import { useForm, usePage } from '@inertiajs/vue3';

export default {
    name: 'InstitutionOfferingCreate',
    components: {
        Input, Label, Select, FormSubmitAlert
    },
    props: {
        programs: Array
    },
    data() {
        return {
            newOfferingForm: null,
            newOfferingFormData: {
                formState: true,
                formSuccessMsg: 'Offering saved successfully.',
                formFailMsg: 'There was an error saving this offering.',
                program_guid: "",
                offering_name: "",
                offering_description: "",
                start_date: "",
                end_date: "",
                location_name: "",
                intervention_language_of_service: "",
                total_seats: 0,
                offering_status: 'draft',
            },
        }
    },
    computed: {
        languageServiceOptions() {
            let utils = usePage().props.utils || {};
            return (utils['Language Service'] || []).map(u => u.field_name);
        }
    },
    methods: {
        submitForm: function (status) {
            this.newOfferingForm.offering_status = status;
            this.newOfferingForm.formState = null;
            this.newOfferingForm.post('/institution/offerings', {
                onSuccess: () => {
                    $("#newInstOfferingModal").modal('hide');
                    this.newOfferingForm.reset(this.newOfferingFormData);
                    this.$inertia.visit('/institution/offerings');
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
    }
}
</script>
