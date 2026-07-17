<template>
    <form v-if="editProgramForm != null" class="card-body">
        <div class="modal-body">
            <div class="row g-3">

                <div class="col-md-12">
                    <Label for="inputProgramName" class="form-label" value="Program Name" />
                    <Input type="text" class="form-control" id="inputProgramName" v-model="editProgramForm.program_name" />
                </div>

                <div class="col-md-6">
                    <Label for="inputCategory" class="form-label" value="Category" />
                    <Input type="text" class="form-control" id="inputCategory" v-model="editProgramForm.category" />
                </div>

                <div class="col-md-6">
                    <Label for="inputNumberWeeks" class="form-label" value="Number of Weeks" />
                    <Input type="number" min="1" step="1" class="form-control" id="inputNumberWeeks" v-model="editProgramForm.number_weeks" />
                </div>

                <div class="col-md-6">
                    <Label for="inputNumberLevels" class="form-label" value="Number of Levels" />
                    <Input type="number" min="0" step="1" class="form-control" id="inputNumberLevels" v-model="editProgramForm.number_levels" />
                </div>

                <div class="col-md-4">
                    <Label for="inputProgramStatus" class="form-label" value="Status" />
                    <Select class="form-select" id="inputProgramStatus" v-model="editProgramForm.active_status">
                        <option :value="true">Active</option>
                        <option :value="false">Inactive</option>
                    </Select>
                </div>

                <div v-if="editProgramForm.errors != undefined" class="row">
                    <div class="col-12">
                        <div v-if="editProgramForm.hasErrors == true" class="alert alert-danger mt-3">
                            <ul>
                                <li v-for="err in editProgramForm.errors">{{ err }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="modal-footer">
            <button @click="submitForm" type="button" class="btn btn-sm btn-success" :disabled="editProgramForm.processing">
                Update Program
            </button>
        </div>
        <FormSubmitAlert :form-state="editProgramForm.formState" :success-msg="editProgramForm.formSuccessMsg"
                         :fail-msg="editProgramForm.formFailMsg"></FormSubmitAlert>
    </form>
</template>
<script>
import Select from '@/Components/Select.vue';
import Input from '@/Components/Input.vue';
import Label from '@/Components/Label.vue';
import FormSubmitAlert from '@/Components/FormSubmitAlert.vue';
import { useForm } from '@inertiajs/vue3';

export default {
    name: 'ProgramInformationEdit',
    components: {
        Input, Label, Select, FormSubmitAlert
    },
    props: {
        results: Object
    },
    data() {
        return {
            editProgramForm: null,
            editProgramFormData: {
                formState: true,
                formSuccessMsg: 'Form was submitted successfully.',
                formFailMsg: 'There was an error submitting this form.',
                id: null,
                program_name: "",
                category: "",
                number_weeks: null,
                number_levels: 0,
                active_status: true,
            },
        }
    },
    methods: {
        submitForm: function () {
            this.editProgramForm.formState = null;
            this.editProgramForm.put('/ministry/programs', {
                onSuccess: () => {
                    this.$inertia.visit('/ministry/programs/' + this.results.id);
                },
                onError: () => {
                    this.editProgramForm.formState = false;
                },
                preserveState: true
            });
        }
    },
    mounted() {
        this.editProgramFormData.id = this.results.id;
        this.editProgramFormData.program_name = this.results.program_name;
        this.editProgramFormData.category = this.results.category;
        this.editProgramFormData.number_weeks = this.results.number_weeks;
        this.editProgramFormData.number_levels = this.results.number_levels;
        this.editProgramFormData.active_status = this.results.active_status;

        this.editProgramForm = useForm(this.editProgramFormData);
    }
}
</script>
