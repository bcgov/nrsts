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
            <label class="form-label" for="pf_gender">Gender Identity</label>
            <select id="pf_gender" class="form-select" v-model="form.gender" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('gender')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
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
            <label class="form-label" for="pf_indigenous_group">Indigenous Group</label>
            <select id="pf_indigenous_group" class="form-select" v-model="form.indigenous_group" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('indigenous_group')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_racial_identity">Racial Identity</label>
            <select id="pf_racial_identity" class="form-select" v-model="form.racial_identity" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('racial_identity')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
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
            <label class="form-label" for="pf_lang_choice">Official Language Choice</label>
            <select id="pf_lang_choice" class="form-select" v-model="form.official_language_choice" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Official Language')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_lang_service">Official Language of Service</label>
            <select id="pf_lang_service" class="form-select" v-model="form.official_language_service" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Language Service')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_emp_intake">Employment Status (Intake)</label>
            <select id="pf_emp_intake" class="form-select" v-model="form.employment_status_intake" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in pdexOptions('employment_status')" :key="opt.value" :value="opt.label">{{ opt.label }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_precarious">Precarious Employment</label>
            <select id="pf_precarious" class="form-select" v-model="form.precarious_employment" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Precarious Employment')" :key="opt" :value="opt">{{ opt }}</option>
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
                    <div class="form-check">
                        <input id="pf_disability" type="checkbox" class="form-check-input" v-model="form.disability_status" :disabled="readonly" />
                        <label class="form-check-label" for="pf_disability">{{ pdexLabel('disability_status') || 'I have a disability or accessibility needs' }}</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input id="pf_indigenous" type="checkbox" class="form-check-input" v-model="form.indigenous_status" :disabled="readonly" />
                        <label class="form-check-label" for="pf_indigenous">{{ pdexLabel('indigenous_status') || 'I identify as Indigenous' }}</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input id="pf_is_visible_minority" type="checkbox" class="form-check-input" v-model="form.is_visible_minority" :disabled="readonly" />
                        <label class="form-check-label" for="pf_is_visible_minority">{{ pdexLabel('is_visible_minority') || 'I identify as a visible minority' }}</label>
                    </div>
                </div>
            </div>
        </div>
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
        // Return the PDEX display label for a checkbox field_id.
        pdexLabel(fieldId) {
            const labels = this.studentUtils && this.studentUtils.labels ? this.studentUtils.labels : {};
            return labels[fieldId] || '';
        },
        // Return the list of country names for the country dropdown.
        pdexCountries() {
            return this.studentUtils && this.studentUtils.countries ? this.studentUtils.countries : [];
        },
    },
};
</script>
