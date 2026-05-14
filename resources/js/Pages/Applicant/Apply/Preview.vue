<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';

const props = defineProps({
    template: Object,
    application: Object,
    applicant: Object,
    responses: Object,
    customEntityData: Object,
    previewConfig: Object,
});

const fieldsByStep = computed(() => {
    if (!props.template || !props.template.fields) return {};
    return props.template.fields.reduce((acc, field) => {
        if (!acc[field.step]) acc[field.step] = [];
        acc[field.step].push(field);
        return acc;
    }, {});
});

const sortedSteps = computed(() => Object.keys(fieldsByStep.value).map(Number).sort((a, b) => a - b));

const getFieldValue = (field) => {
    if (field.field_type === 'table') {
        let val = props.responses ? props.responses[field.id] : null;
        if (field.system_alias && props.responses[field.system_alias] !== undefined) {
            val = props.responses[field.system_alias];
        }
        
        if (!val) return [];
        try {
            return typeof val === 'string' ? JSON.parse(val) : val;
        } catch (e) {
            return [];
        }
    }

    // Check for value using alias first, then fallback to field ID
    let val = props.responses[field.id];
    if (field.system_alias && props.responses[field.system_alias] !== undefined) {
        val = props.responses[field.system_alias];
    }
    
    if (val === undefined || val === null || val === '') return '—';
    
    if (field.field_type === 'checkbox') {
        return val ? 'Yes' : 'No';
    }

    if (field.is_subject_field) {
        const subjects = props.customEntityData && props.customEntityData['subject'] ? props.customEntityData['subject'].values : [];
        const sub = subjects.find(s => s.id == val);
        return sub ? `${sub.name} (${sub.code})` : val;
    }

    return val;
};

const form = useForm({});

const submitFinal = () => {
    if (confirm('Are you sure you want to final submit? You cannot edit the form after submission.')) {
        form.post(route('applicant.apply.submit', props.application.opening_id));
    }
};

</script>

<template>
    <Head title="Application Preview" />

    <AdminLayout>
        <template #header>
            Preview Application: {{ application.opening.job?.title || 'Program' }}
        </template>

        <div class="row justify-content-center pb-5">
            <div class="col-lg-10">
                <div class="alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-warning"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Preview Mode</h6>
                        <p class="mb-0 small">Please review all your details carefully. Once submitted, you will not be able to make any further changes.</p>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="paper-sheet mx-auto p-5">
                            <!-- Official Header -->
                            <div class="print-header text-center mb-5 pb-4 border-bottom">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="logo-box" style="width: 80px;">
                                        <img src="/images/logo.png" alt="LNMU Logo" class="img-fluid" />
                                    </div>
                                    <div class="text-center flex-grow-1 px-3">
                                        <h4 class="fw-bold mb-1 text-uppercase" style="letter-spacing: 1px;">{{ $page.props.tenant?.name || 'Portal Name' }}</h4>
                                        <p class="mb-0 text-muted small">Kameshwaranagar, Darbhanga, Bihar 846004</p>
                                        <div class="mt-2 fw-bold text-primary border-top border-bottom py-1 d-inline-block px-4 small">
                                            APPLICATION FORM PREVIEW
                                        </div>
                                    </div>
                                    <div v-if="(previewConfig?.show_photo ?? true)" class="photo-box border rounded bg-light d-flex align-items-center justify-content-center" style="width: 100px; height: 120px; overflow: hidden;">
                                        <img v-if="applicant?.profile_photo_path" :src="`/storage/${applicant.profile_photo_path}`" class="img-fluid h-100 w-100" style="object-fit: cover;" />
                                        <span v-else class="text-muted small text-center px-2">PHOTO</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Info -->
                            <div class="row mb-5 g-4">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100">
                                        <div v-if="(previewConfig?.show_application_no ?? true)" class="mb-2"><span class="text-muted small fw-bold text-uppercase">Application No:</span> <span class="ms-2 fw-bold text-dark">{{ application.application_no }}</span></div>
                                        <div v-if="(previewConfig?.show_subject ?? true)" class="mb-2"><span class="text-muted small fw-bold text-uppercase">Subject:</span> <span class="ms-2 fw-bold text-dark">{{ application.opening.subject?.name || 'General' }}</span></div>
                                        <div v-if="(previewConfig?.show_program ?? true)" class="mb-0"><span class="text-muted small fw-bold text-uppercase">Program:</span> <span class="ms-2 fw-bold text-dark">{{ application.opening.job?.title || application.opening.program?.title }}</span></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 h-100 text-md-end">
                                        <div v-if="(previewConfig?.show_status ?? true)" class="mb-2"><span class="text-muted small fw-bold text-uppercase">Draft Status:</span> <span class="ms-2 badge bg-warning text-dark">DRAFT</span></div>
                                        <div v-if="(previewConfig?.show_fees ?? true)" class="mb-2"><span class="text-muted small fw-bold text-uppercase">Fees:</span> <span class="ms-2 fw-bold text-success">₹{{ application.total_amount }}</span></div>
                                        <div v-if="(previewConfig?.show_category ?? true)" class="mb-0"><span class="text-muted small fw-bold text-uppercase">Category:</span> <span class="ms-2 fw-bold text-dark">{{ application.application_type?.name }}</span></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dynamic Fields -->
                            <div v-for="step in sortedSteps" :key="step" class="mb-5">
                                <h6 class="fw-bold text-primary mb-4 border-bottom pb-2 d-flex align-items-center">
                                    <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 12px;">{{ step }}</span>
                                    Section {{ step }}
                                </h6>

                                <div class="row g-4">
                                    <div v-for="field in fieldsByStep[step]" :key="field.id" 
                                        :class="field.field_type === 'table' ? 'col-12' : (field.field_type === 'textarea' ? 'col-12' : 'col-md-6')">
                                        
                                        <div class="field-view">
                                            <label class="d-block text-muted small fw-bold text-uppercase mb-1">{{ field.label }}</label>
                                            
                                            <div v-if="field.field_type === 'table'" class="mt-2">
                                                <div class="table-responsive border rounded">
                                                    <table class="table table-bordered table-sm mb-0 align-middle small">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th v-for="col in (typeof field.options === 'string' ? JSON.parse(field.options) : field.options).columns" :key="col.label">
                                                                    {{ col.label }}
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(row, rIdx) in getFieldValue(field)" :key="rIdx">
                                                                <td v-for="col in (typeof field.options === 'string' ? JSON.parse(field.options) : field.options).columns" :key="col.label">
                                                                    <template v-if="col.type === 'file'">
                                                                        <div v-if="row[col.label]" class="d-flex align-items-center justify-content-between">
                                                                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Uploaded</span>
                                                                            <a :href="`/storage/${row[col.label]}`" target="_blank" class="btn btn-xs btn-outline-primary ms-2 py-0 px-1" style="font-size: 0.65rem;">
                                                                                <i class="bi bi-eye"></i> View
                                                                            </a>
                                                                        </div>
                                                                        <span v-else class="text-muted">—</span>
                                                                    </template>
                                                                    <template v-else>
                                                                        {{ row[col.label] || '—' }}
                                                                    </template>
                                                                </td>
                                                            </tr>
                                                            <tr v-if="getFieldValue(field).length === 0">
                                                                <td :colspan="(typeof field.options === 'string' ? JSON.parse(field.options) : field.options).columns.length" class="text-center py-3 text-muted italic">
                                                                    No data entered.
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            
                                            <div v-else-if="field.field_type === 'file' || field.field_type.startsWith('system_photo') || field.field_type.startsWith('system_signature')" class="mt-1">
                                                <div v-if="getFieldValue(field) && getFieldValue(field) !== '—'" class="d-flex align-items-center justify-content-between p-2 border rounded bg-white shadow-sm" style="max-width: 300px;">
                                                    <div class="d-flex align-items-center overflow-hidden">
                                                        <i class="bi bi-file-earmark-check-fill text-success fs-4 me-2"></i>
                                                        <div class="overflow-hidden">
                                                            <div class="text-success fw-bold small">Document Uploaded</div>
                                                            <div class="text-muted x-small text-truncate">{{ getFieldValue(field) }}</div>
                                                        </div>
                                                    </div>
                                                    <a :href="`/storage/${getFieldValue(field)}`" target="_blank" class="btn btn-sm btn-primary ms-3">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                </div>
                                                <div v-else class="text-muted p-2 border border-dashed rounded bg-light small">
                                                    Not uploaded
                                                </div>
                                            </div>

                                            <div v-else class="p-2 border-bottom bg-light bg-opacity-10 min-h-24 fw-medium text-dark">
                                                {{ getFieldValue(field) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                             <!-- Signature Section -->
                             <div v-if="(previewConfig?.show_declaration ?? true) || (previewConfig?.show_signature ?? true) || (previewConfig?.show_thumb_impression ?? true)" class="mt-5 pt-4 border-top">
                                <div class="row align-items-center">
                                    <div v-if="(previewConfig?.show_declaration ?? true)" class="col-md-6 mb-4 mb-md-0">
                                        <h6 class="fw-bold small text-uppercase mb-3">Declaration:</h6>
                                        <p class="text-muted small mb-0" style="text-align: justify; line-height: 1.5;">
                                            {{ previewConfig?.declaration_text || "I hereby declare that all the information given by me in this application are true and correct to the best of my knowledge and belief. I understand that my candidature is liable to be cancelled if any information is found false." }}
                                        </p>
                                    </div>
                                    <div v-if="(previewConfig?.show_signature ?? true)" class="col-md-6 d-flex flex-column align-items-center align-items-md-end">
                                        <div class="mb-2 text-center p-2 border rounded bg-light" style="width: 200px;">
                                            <img v-if="applicant?.signature_path" :src="`/storage/${applicant.signature_path}`" style="max-height: 60px; max-width: 180px;" />
                                            <div v-else class="py-4 text-muted small">No Signature Found</div>
                                        </div>
                                        <div class="text-center w-100 pe-md-4">
                                            <small class="fw-bold text-dark text-uppercase">Applicant's Signature</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-4 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        <Link :href="route('applicant.apply.form', application.opening_id)" class="btn btn-outline-secondary px-4 rounded-pill">
                            <i class="bi bi-pencil-square me-2"></i> Back to Edit Form
                        </Link>
                        
                        <div class="d-flex gap-3">
                            <button @click="submitFinal" class="btn btn-primary btn-lg px-5 rounded-pill shadow-sm fw-bold" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-shield-check me-2"></i> 
                                {{ form.processing ? 'Submitting...' : (application.status === 'paid' ? 'Final Resubmit' : 'Final Submit & Pay') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.paper-sheet {
    background: white;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.field-view {
    height: 100%;
}

.min-h-24 {
    min-height: 38px;
    display: flex;
    align-items: center;
}

.x-small {
    font-size: 0.7rem;
}
</style>
