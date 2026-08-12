<template>
    <form v-if="editOfferingForm != null" class="card-body">
        <div class="modal-body">
            <div class="row g-3">

                <div class="col-md-12">
                    <Label for="editInstitution" class="form-label" value="Institution" />
                    <Select v-if="!institutionReadonly" class="form-select" id="editInstitution" v-model="editOfferingForm.institution_guid">
                        <option value=""></option>
                        <option v-for="inst in institutions" :value="inst.guid" :key="inst.id">{{ inst.name }}</option>
                    </Select>
                    <Input v-else type="text" class="form-control" id="editInstitution" :value="institutionName" readonly disabled />
                </div>

                <div class="col-md-12">
                    <Label for="editProgramYear" class="form-label" value="Program Year" />
                    <Select class="form-select" id="editProgramYear" v-model="editOfferingForm.program_year_guid">
                        <option value=""></option>
                        <option v-for="py in programYears" :value="py.guid" :key="py.id">{{ formatProgramYear(py) }}</option>
                    </Select>
                </div>

                <div class="col-md-12">
                    <Label for="editOfferingName" class="form-label" value="Offering Name" />
                    <Input type="text" class="form-control" id="editOfferingName" v-model="editOfferingForm.offering_name" />
                </div>

                <div class="col-md-12">
                    <Label for="editOfferingDescription" class="form-label" value="Description" />
                    <textarea class="form-control" id="editOfferingDescription" rows="2" v-model="editOfferingForm.offering_description"></textarea>
                </div>

                <div class="col-md-6">
                    <Label for="editStudyStart" class="form-label" value="Study Start Date" />
                    <Input type="date" class="form-control" id="editStudyStart" v-model="editOfferingForm.start_date" />
                </div>

                <div class="col-md-6">
                    <Label for="editStudyEnd" class="form-label" value="Study End Date" />
                    <Input type="date" class="form-control" id="editStudyEnd" v-model="editOfferingForm.end_date" />
                </div>

                <div class="col-md-6">
                    <Label for="editLocation" class="form-label" value="Location" />
                    <Input type="text" class="form-control" id="editLocation" v-model="editOfferingForm.location_name" />
                </div>
                <div class="col-md-6">
                    <Label for="editLangService" class="form-label" value="Intervention Language of Service" />
                    <Select class="form-select" id="editLangService" v-model="editOfferingForm.intervention_language_of_service">
                        <option value=""></option>
                        <option v-for="opt in languageServiceOptions" :key="opt" :value="opt">{{ opt }}</option>
                    </Select>
                </div>
                <div class="col-md-6">
                    <Label for="editTotalSeats" class="form-label" value="Total Seats" />
                    <Input type="number" min="0" class="form-control" id="editTotalSeats" v-model="editOfferingForm.total_seats" />
                </div>

                <div class="col-md-6">
                    <Label for="editTotalAmount" class="form-label" value="Total Budget" />
                    <Input v-if="!isBudgetDerived" type="number" step="0.01" min="0" class="form-control" id="editTotalAmount" v-model="editOfferingForm.total_amount" />
                    <div v-else class="form-control-plaintext">
                        {{ formatMoney(supportPaymentPerWeek) }} &times; {{ editOfferingForm.total_seats || 0 }} = <strong>{{ formatMoney(computedTotalAmount) }}</strong>
                    </div>
                </div>


                <div class="col-md-4">
                    <Label for="editActiveStatus" class="form-label" value="Status" />
                    <Select class="form-select" id="editActiveStatus" v-model="editOfferingForm.offering_status">
                        <option value="draft">Draft</option>
                        <option value="submitted">Submitted</option>
                        <option value="approved">Approved</option>
                        <option value="declined">Declined</option>
                        <option value="inactive">Inactive</option>
                    </Select>
                </div>

                <div v-if="editOfferingForm.errors != undefined" class="row">
                    <div class="col-12">
                        <div v-if="editOfferingForm.hasErrors == true" class="alert alert-danger mt-3">
                            <ul>
                                <li v-for="err in editOfferingForm.errors">{{ err }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="modal-footer">
            <button @click="submitForm" type="button" class="btn btn-sm btn-success" :disabled="editOfferingForm.processing">
                Update Offering
            </button>
        </div>
        <FormSubmitAlert :form-state="editOfferingForm.formState" :success-msg="editOfferingForm.formSuccessMsg"
                         :fail-msg="editOfferingForm.formFailMsg"></FormSubmitAlert>
    </form>
</template>
<script>
import Select from '@/Components/Select.vue';
import Input from '@/Components/Input.vue';
import Label from '@/Components/Label.vue';
import FormSubmitAlert from '@/Components/FormSubmitAlert.vue';
import { useForm, usePage } from '@inertiajs/vue3';

export default {
    name: 'ProgramOfferingEdit',
    components: {
        Input, Label, Select, FormSubmitAlert
    },
    props: {
        results: Object,
        institutions: Object,
        programYears: Object,
        offering: Object,
        redirectUrl: {
            type: String,
            default: ''
        },
        institutionReadonly: {
            type: Boolean,
            default: false
        },
        supportPaymentPerWeek: {
            type: [Number, String],
            default: 0
        }
    },
    data() {
        return {
            editOfferingForm: null,
            editOfferingFormData: {
                formState: true,
                formSuccessMsg: 'Form was submitted successfully.',
                formFailMsg: 'There was an error submitting this form.',
                id: null,
                guid: "",
                program_guid: "",
                program_year_guid: "",
                institution_guid: "",
                offering_name: "",
                offering_description: "",
                start_date: "",
                end_date: "",
                location_name: "",
                intervention_language_of_service: "",
                total_amount: 0,
                total_seats: 0,
                offering_status: 'draft',
            },
        }
    },
    computed: {
        institutionName() {
            if (!this.editOfferingForm) return '';
            let match = (this.institutions || []).find(i => i.guid === this.editOfferingForm.institution_guid);
            return match ? match.name : '';
        },
        languageServiceOptions() {
            let utils = usePage().props.utils || {};
            return (utils['Language Service'] || []).map(u => u.field_name);
        },
        isBudgetDerived() {
            return Number(this.supportPaymentPerWeek) > 0;
        },
        computedTotalAmount() {
            if (!this.editOfferingForm) return 0;
            return Number(this.supportPaymentPerWeek) * Number(this.editOfferingForm.total_seats || 0);
        }
    },
    methods: {
        formatMoney: function (value) {
            let num = Number(value || 0);
            return '$' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        formatProgramYear: function (py) {
            let label = (py.start_date || '').split('T')[0] + ' to ' + (py.end_date || '').split('T')[0];
            return label + ' (' + py.status + ')';
        },
        toDateInput: function (value) {
            if (value !== undefined && value !== null && value !== '') {
                return value.split("T")[0];
            }
            return "";
        },
        submitForm: function () {
            let vm = this;
            if (this.isBudgetDerived) {
                this.editOfferingForm.total_amount = this.computedTotalAmount;
            }
            this.editOfferingForm.formState = null;
            this.editOfferingForm.put('/ministry/program-offerings', {
                onSuccess: () => {
                    $("#editOfferingModal").modal('hide');
                    let target = vm.redirectUrl !== '' ? vm.redirectUrl : '/ministry/programs/' + vm.results.id + '/offerings';
                    vm.$inertia.visit(target);
                    vm.$emit('close');
                },
                onError: () => {
                    this.editOfferingForm.formState = false;
                },
                preserveState: true
            });
        }
    },
    mounted() {
        this.editOfferingFormData.id = this.offering.id;
        this.editOfferingFormData.guid = this.offering.guid;
        this.editOfferingFormData.program_guid = this.offering.program_guid;
        this.editOfferingFormData.program_year_guid = this.offering.program_year_guid;
        this.editOfferingFormData.institution_guid = this.offering.institution_guid;
        this.editOfferingFormData.offering_name = this.offering.offering_name;
        this.editOfferingFormData.offering_description = this.offering.offering_description;
        this.editOfferingFormData.start_date = this.toDateInput(this.offering.start_date);
        this.editOfferingFormData.end_date = this.toDateInput(this.offering.end_date);
        this.editOfferingFormData.location_name = this.offering.location_name;
        this.editOfferingFormData.intervention_language_of_service = this.offering.intervention_language_of_service;
        this.editOfferingFormData.total_amount = this.offering.total_amount;
        this.editOfferingFormData.total_seats = this.offering.total_seats;
        this.editOfferingFormData.offering_status = this.offering.offering_status;

        this.editOfferingForm = useForm(this.editOfferingFormData);
    }
}
</script>
