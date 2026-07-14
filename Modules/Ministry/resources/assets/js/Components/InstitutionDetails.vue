<template>
    <div>
        <div v-if="pdexInstitution != null" class="card mb-3 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    Institution Record
                    <small class="text-muted">(PDEX)</small>
                </span>
                <span :class="pdexInstitution.active_status ? 'badge bg-success' : 'badge bg-secondary'">
                    {{ pdexInstitution.active_status ? 'Active' : 'Inactive' }}
                </span>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-3">{{ pdexInstitution.legal_operating_name || '—' }}</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">Institution Type</div>
                        <div>{{ pdexInstitution.institution_type || '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">DLI</div>
                        <div>{{ pdexInstitution.dli || '—' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase">BCeID Business GUID</div>
                        <div>{{ pdexInstitution.bceid_business_guid || '—' }}</div>
                    </div>
                    <div class="col-md-12">
                        <div class="text-muted small text-uppercase">GUID</div>
                        <div><code>{{ pdexInstitution.guid || '—' }}</code></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    Institution Sites
                    <small class="text-muted">(PDEX)</small>
                </span>
                <span v-if="pdexSitesList.length > 0" class="badge bg-primary rounded-pill">
                    {{ pdexSitesList.length }}
                </span>
            </div>
            <div class="card-body">
                <div v-if="pdexSitesList.length > 0" class="row row-cols-1 row-cols-xl-2 g-3">
                    <div v-for="(site, idx) in pdexSitesList" :key="site.guid || idx" class="col">
                        <div class="border rounded h-100 p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">{{ siteTitle(site) }}</h6>
                                <span v-if="'active_status' in site"
                                      :class="site.active_status ? 'badge bg-success' : 'badge bg-secondary'">
                                    {{ site.active_status ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <dl class="row mb-0 small">
                                <template v-for="field in siteFields(site)" :key="field.key">
                                    <dt class="col-sm-5 text-muted fw-normal">{{ field.label }}</dt>
                                    <dd class="col-sm-7 mb-1">
                                        <a v-if="field.type === 'email'" :href="'mailto:' + field.value">{{ field.value }}</a>
                                        <a v-else-if="field.type === 'phone'" :href="'tel:' + field.value">{{ field.value }}</a>
                                        <a v-else-if="field.type === 'url'" :href="field.value" target="_blank" rel="noopener">{{ field.value }}</a>
                                        <code v-else-if="field.type === 'code'">{{ field.value }}</code>
                                        <span v-else>{{ field.value }}</span>
                                    </dd>
                                </template>
                            </dl>
                        </div>
                    </div>
                </div>
                <p v-else class="text-muted mb-0">No site data available from PDEX.</p>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'InstitutionDetails',
    props: {
        results: Object,
        pdexInstitution: Object,
        pdexSites: [Object, Array],
    },
    computed: {
        pdexSitesList() {
            if (Array.isArray(this.pdexSites)) {
                return this.pdexSites;
            }
            if (this.pdexSites && typeof this.pdexSites === 'object') {
                return [this.pdexSites];
            }
            return [];
        },
    },
    methods: {
        siteTitle(site) {
            return site.operating_name || site.name || site.site_name || ('Site ' + (site.id ?? ''));
        },
        formatLabel(key) {
            return String(key)
                .replace(/_/g, ' ')
                .replace(/\b\w/g, (c) => c.toUpperCase());
        },
        fieldType(key, value) {
            if (value == null || value === '') {
                return 'empty';
            }
            const k = key.toLowerCase();
            if (k.includes('email')) return 'email';
            if (k.includes('phone') || k.includes('fax')) return 'phone';
            if (k.includes('website') || k.includes('url')) return 'url';
            if (k === 'guid' || k.endsWith('_guid')) return 'code';
            return 'text';
        },
        siteFields(site) {
            const hidden = ['operating_name', 'name', 'site_name', 'active_status'];
            return Object.keys(site)
                .filter((key) => !hidden.includes(key))
                .map((key) => ({
                    key,
                    label: this.formatLabel(key),
                    value: site[key],
                    type: this.fieldType(key, site[key]),
                }))
                .filter((field) => field.type !== 'empty');
        },
    },
}
</script>
