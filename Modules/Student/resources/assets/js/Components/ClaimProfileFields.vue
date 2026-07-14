<template>
    <div class="row g-3">
        <!-- Identity -->
        <div class="col-12">
            <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Applicant Details</h6>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="pf_first_name">First Name</label>
            <input id="pf_first_name" type="text" class="form-control" v-model="form.first_name" :disabled="readonly" />
            <span v-if="suggestion('first_name')" class="badge bg-secondary mt-1" style="cursor:pointer" @click="apply('first_name', 'first_name')" title="Click to use this value">{{ suggestion('first_name') }}</span>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_middle_name">Middle Name</label>
            <input id="pf_middle_name" type="text" class="form-control" v-model="form.middle_name" :disabled="readonly" />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_last_name">Last Name</label>
            <input id="pf_last_name" type="text" class="form-control" v-model="form.last_name" :disabled="readonly" />
            <span v-if="suggestion('last_name')" class="badge bg-secondary mt-1" style="cursor:pointer" @click="apply('last_name', 'last_name')" title="Click to use this value">{{ suggestion('last_name') }}</span>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="pf_sin">SIN</label>
            <input id="pf_sin" type="number" min="100000000" max="999999999" class="form-control" v-model="form.sin" :disabled="readonly" />
            <span v-if="suggestion('sin')" class="badge bg-secondary mt-1" style="cursor:pointer" @click="apply('sin', 'sin')" title="Click to use this value">{{ suggestion('sin') }}</span>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_dob">Birth Date</label>
            <input id="pf_dob" type="date" min="1920-01-01" class="form-control" v-model="form.dob" :disabled="readonly" />
            <span v-if="suggestion('dob')" class="badge bg-secondary mt-1" style="cursor:pointer" @click="apply('dob', 'dob')" title="Click to use this value">{{ suggestion('dob') }}</span>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_email">Email</label>
            <input id="pf_email" type="email" class="form-control" v-model="form.email" :disabled="readonly" />
            <span v-if="suggestion('email_address')" class="badge bg-secondary mt-1" style="cursor:pointer" @click="apply('email', 'email_address')" title="Click to use this value">{{ suggestion('email_address') }}</span>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pf_telephone">Telephone</label>
            <input id="pf_telephone" type="text" class="form-control" v-model="form.telephone" :disabled="readonly" />
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
        <div class="col-md-3">
            <label class="form-label" for="pf_city">City</label>
            <input id="pf_city" type="text" class="form-control" v-model="form.city" :disabled="readonly" />
            <span v-if="suggestion('city')" class="badge bg-secondary mt-1" style="cursor:pointer" @click="apply('city', 'city')" title="Click to use this value">{{ suggestion('city') }}</span>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_province">Province</label>
            <select id="pf_province" class="form-select" v-model="form.province" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Province')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_country">Country</label>
            <input id="pf_country" type="text" class="form-control" v-model="form.country" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_zip">Postal Code</label>
            <input id="pf_zip" type="text" maxlength="7" class="form-control" v-model="form.zip_code" :disabled="readonly" />
            <span v-if="suggestion('zip_code')" class="badge bg-secondary mt-1" style="cursor:pointer" @click="apply('zip_code', 'zip_code')" title="Click to use this value">{{ suggestion('zip_code') }}</span>
        </div>

        <!-- Demographics -->
        <div class="col-12 mt-4">
            <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Demographics <small class="text-muted fw-normal">(optional)</small></h6>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_gender">Gender Identity</label>
            <select id="pf_gender" class="form-select" v-model="form.gender_identity" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Gender Identity')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_marital">Marital Status</label>
            <select id="pf_marital" class="form-select" v-model="form.marital_status" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Marital Status')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_dependants">Number of Dependants</label>
            <input id="pf_dependants" type="number" min="0" class="form-control" v-model="form.number_of_dependants" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_disability">Disability Status</label>
            <select id="pf_disability" class="form-select" v-model="form.disability_status" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Disability Status')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_indigenous">Indigenous Identity</label>
            <select id="pf_indigenous" class="form-select" v-model="form.indigenous_identity" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Indigenous Identity')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_immigration">Immigration Status</label>
            <select id="pf_immigration" class="form-select" v-model="form.immigration_status" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Immigration Status')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_immigration_year">Immigration Year</label>
            <input id="pf_immigration_year" type="number" min="1900" max="2100" class="form-control" v-model="form.immigration_year" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_visible_minority">Visible Minority</label>
            <select id="pf_visible_minority" class="form-select" v-model="form.visible_minority_status" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Visible Minority')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_education">Highest Education Level</label>
            <select id="pf_education" class="form-select" v-model="form.highest_education_level" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Education Level')" :key="opt" :value="opt">{{ opt }}</option>
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
                <option v-for="opt in options('Employment Status')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_emp_exit">Employment Status (Exit)</label>
            <select id="pf_emp_exit" class="form-select" v-model="form.employment_status_exit" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Employment Status')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_precarious">Precarious Employment</label>
            <select id="pf_precarious" class="form-select" v-model="form.precarious_employment" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Precarious Employment')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>

        <!-- Intervention -->
        <div class="col-12 mt-4">
            <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Intervention <small class="text-muted fw-normal">(optional)</small></h6>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_intervention_name">Intervention Name</label>
            <input id="pf_intervention_name" type="text" class="form-control" v-model="form.intervention_name" :disabled="readonly" />
        </div>
        <div class="col-md-2">
            <label class="form-label" for="pf_intervention_code">Intervention Code</label>
            <input id="pf_intervention_code" type="text" class="form-control" v-model="form.intervention_code" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_intervention_start">Start Date</label>
            <input id="pf_intervention_start" type="date" class="form-control" v-model="form.intervention_start_date" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_intervention_end">End Date</label>
            <input id="pf_intervention_end" type="date" class="form-control" v-model="form.intervention_end_date" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_intervention_outcome">Intervention Outcome</label>
            <select id="pf_intervention_outcome" class="form-select" v-model="form.intervention_outcome" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Intervention Outcome')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_credential">Credential Earned</label>
            <select id="pf_credential" class="form-select" v-model="form.credential_earned" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Credential Earned')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_noc">NOC Code</label>
            <input id="pf_noc" type="text" class="form-control" v-model="form.noc_code" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_naics">NAICS Code</label>
            <input id="pf_naics" type="text" class="form-control" v-model="form.naics_code" :disabled="readonly" />
        </div>

        <!-- Action Plan -->
        <div class="col-12 mt-4">
            <h6 class="fw-bold text-secondary border-bottom pb-1 mb-2">Action Plan <small class="text-muted fw-normal">(optional)</small></h6>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_ap_start">Start Date</label>
            <input id="pf_ap_start" type="date" class="form-control" v-model="form.action_plan_start_date" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_ap_end">End Date</label>
            <input id="pf_ap_end" type="date" class="form-control" v-model="form.action_plan_end_date" :disabled="readonly" />
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_ap_outcome">Outcome</label>
            <select id="pf_ap_outcome" class="form-select" v-model="form.action_plan_outcome" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Action Plan Outcome')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="pf_ap_outcome_date">Outcome Date</label>
            <input id="pf_ap_outcome_date" type="date" class="form-control" v-model="form.action_plan_outcome_date" :disabled="readonly" />
        </div>
        <div class="col-md-4">
            <label class="form-label" for="pf_literacy">Literacy / Essential Skills Increase</label>
            <select id="pf_literacy" class="form-select" v-model="form.literacy_essential_skills_increase" :disabled="readonly">
                <option value=""></option>
                <option v-for="opt in options('Literacy/Essential Skills')" :key="opt" :value="opt">{{ opt }}</option>
            </select>
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
        // BCSC individual_data.individual object used to suggest identity values.
        individual: {
            type: Object,
            default: null,
        },
        readonly: {
            type: Boolean,
            default: false,
        },
    },
    methods: {
        // Return the list of selectable option labels for a util category.
        options(category) {
            const list = this.utils && this.utils[category] ? this.utils[category] : [];
            return list.map((u) => u.field_name);
        },
        // Return a suggested BCSC value for a given key, or null.
        suggestion(individualKey) {
            if (this.readonly || !this.individual) return null;
            const val = this.individual[individualKey];
            return val !== null && val !== undefined && val !== '' ? val : null;
        },
        // Apply a BCSC suggestion to the form.
        apply(formKey, individualKey) {
            const val = this.suggestion(individualKey);
            if (val !== null) {
                this.form[formKey] = val;
            }
        },
    },
};
</script>
