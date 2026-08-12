<template>
    <div class="row g-3">
        <!-- Identity -->
        <div class="col-12">
            <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Applicant Details</h6>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="pf_first_name">First Name</label>
            <input id="pf_first_name" type="text" class="form-control" v-model="form.first_name" :disabled="readonly" />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_middle_name">Middle Name</label>
            <input id="pf_middle_name" type="text" class="form-control" v-model="form.middle_name" :disabled="readonly" />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_last_name">Last Name</label>
            <input id="pf_last_name" type="text" class="form-control" v-model="form.last_name" :disabled="readonly" />
        </div>

        <div class="col-md-3">
            <label class="form-label" for="pf_sin">SIN</label>
            <input id="pf_sin" type="number" min="100000000" max="999999999" class="form-control" v-model="form.social_insurance_number" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_dob">Birth Date</label>
            <input id="pf_dob" type="date" min="1920-01-01" class="form-control" v-model="form.date_of_birth" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_email">Email</label>
            <input id="pf_email" type="email" class="form-control" v-model="form.email_address" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_telephone">Telephone</label>
            <input id="pf_telephone" type="text" class="form-control" v-model="form.phone_number" :disabled="readonly" />
        </div>

        <!-- Address -->
        <div class="col-12 mt-4">
            <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Address</h6>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="pf_address1">Address Line 1</label>
            <input id="pf_address1" type="text" class="form-control" v-model="form.address_line1" :disabled="readonly" />
        </div>
        <div class="col-md-6">
            <label class="form-label" for="pf_address2">Address Line 2</label>
            <input id="pf_address2" type="text" class="form-control" v-model="form.address_line2" :disabled="readonly" />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_city">City</label>
            <input id="pf_city" type="text" class="form-control" v-model="form.city" :disabled="readonly" />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_zip">Postal Code</label>
            <input id="pf_zip" type="text" maxlength="7" class="form-control" v-model="form.postal_code" :disabled="readonly" />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_region">Region</label>
            <select id="pf_region" class="form-select" v-model="form.region" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Regions')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="pf_province">Province</label>
            <select v-if="isCanada" id="pf_province" class="form-select" v-model="form.province" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('province')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
            <input v-else id="pf_province" type="text" class="form-control" v-model="form.province" :disabled="readonly" />
        </div>
        <div class="col-md-6">
            <label class="form-label" for="pf_country">Country</label>
            <select id="pf_country" class="form-select" v-model="form.country" :disabled="readonly">
                <option value=""></option>
                <option v-for="c in pdexCountries()" :key="c" :value="c">{{ c }}</option>
            </select>
        </div>
        

        <!-- Demographics -->
        <div class="col-12 mt-4">
            <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Demographics <small class="text-muted fw-normal">(optional)</small></h6>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_gender">Gender</label>
            <select id="pf_gender" class="form-select" v-model="form.gender" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in genderOptions()" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_marital">Marital Status</label>
            <select id="pf_marital" class="form-select" v-model="form.marital_status" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('marital_status')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_dependants">Number of Dependants</label>
            <input id="pf_dependants" type="number" min="0" class="form-control" v-model="form.number_of_dependants" :disabled="readonly" />
        </div>
        
        <div class="col-md-3">
            <label class="form-label" for="pf_immigration">Immigration Status</label>
            <select id="pf_immigration" class="form-select" v-model="form.immigration_status" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('immigration_status')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_immigration_year">Immigration Year</label>
            <input id="pf_immigration_year" type="number" min="1900" max="2100" class="form-control" v-model="form.immigration_year" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_education">Highest Education Level</label>
            <select id="pf_education" class="form-select" v-model="form.highest_level_of_education" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('highest_level_of_education')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_lang_choice">Spoken Language</label>
            <select id="pf_lang_choice" class="form-select" v-model="form.spoken_language" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('language_spoken')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_emp_intake">Employment Status (Intake)</label>
            <select id="pf_emp_intake" class="form-select" v-model="form.employment_status_intake" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('employment_status')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
        </div>
        <div v-if="form.employment_status_exit" class="col-md-3">
            <label class="form-label" for="pf_emp_exit">Employment Status (Exit)</label>
            <select id="pf_emp_exit" class="form-select" v-model="form.employment_status_exit" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('employment_status')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
        </div>

        <!-- Self-identification -->
        <div class="col-12">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-check-label" for="pf_disability">{{ pdexPermissionLabel('disability_status') || 'I have a disability or accessibility neeeds' }}</label>
                    <select id="pf_disability" class="form-select" v-model="form.disability_status" :disabled="readonly">
                        <option value=""></option>
                        <option v-for="opt in pdexOptions('disability_status')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
                    </select>
                    <!-- <input id="pf_disability" type="checkbox" class="form-check-input" v-model="form.disability_status" :disabled="readonly" /> -->
                </div>
                <div class="col-md-4">
                    <label class="form-check-label" for="pf_indigenous">{{ pdexPermissionLabel('indigenous_status') || 'I identify as Indigenousss' }}</label>
                    <select id="pf_indigenous" class="form-select" v-model="form.indigenous_status" :disabled="readonly">
                        <option value=""></option>
                        <option v-for="opt in pdexOptions('indigenous_status')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
                    </select>
                    <!-- <input id="pf_indigenous" type="checkbox" class="form-check-input" v-model="form.indigenous_status" :disabled="readonly" /> -->
                </div>
                <div class="col-md-4">
                    <label class="form-check-label" for="pf_is_visible_minority">{{ pdexPermissionLabel('is_visible_minority') || 'I identify as a visible minoritttty' }}</label>
                    <select id="pf_is_visible_minority" class="form-select" v-model="form.is_visible_minority" :disabled="readonly">
                        <option value=""></option>
                        <option v-for="opt in pdexOptions('is_visible_minority')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
                    </select>

                    <!-- <input id="pf_is_visible_minority" type="checkbox" class="form-check-input" v-model="form.is_visible_minority" :disabled="readonly" /> -->
                </div>
            </div>
        </div>

        <!-- Intervention / Agreement (Institution & Ministry only) -->
        <template v-if="showInterventionFields">
            <div class="col-12 mt-4">
                <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Intervention / Agreement</h6>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_agreement_holder">Agreement Holder Name</label>
                <input id="pf_agreement_holder" type="text" class="form-control" v-model="form.agreement_holder_name" readonly disabled />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_agreement_number">Agreement Number</label>
                <input id="pf_agreement_number" type="text" class="form-control" v-model="form.agreement_number" readonly disabled />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_intervention_title">Intervention Title</label>
                <input id="pf_intervention_title" type="text" class="form-control" v-model="form.intervention_title" readonly disabled />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_intervention_code">Intervention Code</label>
                <input id="pf_intervention_code" type="text" class="form-control" v-model="form.intervention_code" readonly disabled />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_intervention_outcome">Intervention Outcome</label>
                <select id="pf_intervention_outcome" class="form-select" v-model="form.intervention_outcome" :disabled="interventionReadonly">
                    <option value=""></option>
                    <option v-for="opt in options('Intervention Outcome')" :key="opt" :value="opt">{{ opt }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_action_plan_result_code">Action Plan Result Code</label>
                <select id="pf_action_plan_result_code" class="form-select" v-model="form.action_plan_result_code" :disabled="interventionReadonly">
                    <option value=""></option>
                    <option v-for="opt in options('Action Plan Result Code')" :key="opt" :value="opt">{{ opt }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_intervention_essential_skills">Intervention Essential Skills</label>
                <select id="pf_intervention_essential_skills" class="form-select" v-model="form.intervention_essential_skills" :disabled="interventionReadonly">
                    <option value=""></option>
                    <option v-for="opt in options('Intervention Essential Skills')" :key="opt" :value="opt">{{ opt }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_provincial_office_code">Provincial Office Code</label>
                <input id="pf_provincial_office_code" type="text" class="form-control" v-model="form.provincial_office_code" :disabled="interventionReadonly" />
            </div>
            <div class="col-md-3">
                <label class="form-label" for="pf_credential_earned">Credential/Certificate Earned</label>
                <select id="pf_credential_earned" class="form-select" v-model="form.credential_certificate_earned" :disabled="interventionReadonly">
                    <option value=""></option>
                    <option v-for="opt in options('Credential Certificate Earned')" :key="opt" :value="opt">{{ opt }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label d-block" for="pf_ei_confirmation">EI Confirmation</label>
                <input id="pf_ei_confirmation" type="checkbox" class="form-check-input" v-model="form.ei_confirmation" :disabled="eiConfirmationLocked || interventionReadonly" />
                <small v-if="form.ei_confirmation_date" class="d-block text-muted">Confirmed {{ String(form.ei_confirmation_date).split('T')[0] }}</small>
            </div>
        </template>
    </div>
</template>


<script>
export default {
    name: 'ClaimProfileFields',
    props: {
        // The Inertia useForm object (create/edit) or a plain claim object (readonly).
        form: {
            type: Object,
            required: true,
        },
        // Sorted utils shared globally, keyed by field_type.
        utils: {
            type: Object,
            default: () => ({}),
        },
        // PDEX student profile option lists, keyed by field_id.
        studentUtils: {
            type: Object,
            default: () => ({}),
        },
        readonly: {
            type: Boolean,
            default: false,
        },
        // Show the Intervention/Agreement section (Institution & Ministry only).
        showInterventionFields: {
            type: Boolean,
            default: false,
        },
        // Disable the editable intervention fields (e.g. terminal claims).
        interventionReadonly: {
            type: Boolean,
            default: false,
        },
        // Lock the EI Confirmation checkbox (institutions cannot change it once set).
        eiConfirmationLocked: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        // The province dropdown is only meaningful for Canadian addresses.
        isCanada() {
            return (this.form.country || '') === 'Canada';
        },
    },
    methods: {
        // Return the list of selectable option labels for a util category.
        options(category) {
            const list = this.utils && this.utils[category] ? this.utils[category] : [];
            return list.map((u) => u.field_name);
        },
        // Return the PDEX option list ({ value, label }) for a student field_id.
        pdexOptions(fieldId) {
            const opts = this.studentUtils && this.studentUtils.options ? this.studentUtils.options : {};
            return opts[fieldId] ? opts[fieldId] : [];
        },
        // Gender is special-cased: PDEX labels are re-labelled and stored as codes.
        genderOptions() {
            const map = {
                'man/boy': { label: 'Male', value: 'm' },
                'woman/girl': { label: 'Female', value: 'f' },
                'non-binary': { label: 'Unspecified', value: 'x' },
                'prefer not to answer': { label: 'Prefer not to answer', value: 'u' },
            };
            return this.pdexOptions('gender').map((opt) => {
                const mapped = map[(opt.label || '').trim().toLowerCase()];
                return mapped ? mapped : { label: opt.label, value: opt.value };
            });
        },
        // Return the PDEX display label for a checkbox field_id.
        pdexLabel(fieldId) {
            const labels = this.studentUtils && this.studentUtils.labels ? this.studentUtils.labels : {};
            return labels[fieldId] || '';
        },
        pdexPermissionLabel(fieldId) {
            const permissionLabels = this.studentUtils && this.studentUtils.permission_labels ? this.studentUtils.permission_labels : {};
            return permissionLabels[fieldId] || '';
        },
        // Return the list of country names for the country dropdown.
        pdexCountries() {
            return this.studentUtils && this.studentUtils.countries ? this.studentUtils.countries : [];
        },
    },
    mounted() {
        console.log(this.studentUtils);
    },
};
</script>
