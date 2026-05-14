<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    template: Object,
    application: Object, 
    applicant: Object,   
    responses: Object,   
    customEntityData: Object,
    subjects: {
        type: Array,
        default: () => []
    },
    previewConfig: {
        type: Object,
        default: () => ({})
    }
});

const fieldsByStep = computed(() => {
    if (!props.template || !props.template.fields) return {};
    return props.template.fields.reduce((acc, field) => {
        const step = field.step || 1;
        if (!acc[step]) acc[step] = [];
        acc[step].push(field);
        return acc;
    }, {});
});

const sortedSteps = computed(() => Object.keys(fieldsByStep.value).map(Number).sort((a, b) => a - b));

const getFieldValue = (field) => {
    if (field.field_type === 'table') {
        let val = props.responses ? props.responses[field.id] : null;
        if (!val) {
             return [{}]; 
        }
        try {
            return typeof val === 'string' ? JSON.parse(val) : val;
        } catch (e) {
            return [{}];
        }
    }

    if (!props.responses) return '____________________';
    
    let val = props.responses[field.id];
    if (field.system_alias && props.responses[field.system_alias] !== undefined) {
        val = props.responses[field.system_alias];
    }
    
    if (val === undefined || val === null || val === '') return '—';
    
    if (field.field_type === 'checkbox') {
        return val ? 'Yes' : 'No';
    }

    if (field.is_subject_field && props.subjects) {
        const sub = props.subjects.find(s => s.id == val);
        return sub ? `${sub.name} (${sub.code})` : val;
    }

    return val;
};

const print = () => {
    window.print();
};

const goBack = () => {
    window.history.back();
};

const getTableConfig = (field) => {
    if (!field.options) return { columns: [] };
    try {
        return typeof field.options === 'string' ? JSON.parse(field.options) : field.options;
    } catch (e) {
        return { columns: [] };
    }
};
</script>

<template>
    <Head v-if="template" :title="`Print Preview - ${template.name}`" />

    <div v-if="template" class="print-container py-4">
        <!-- Print Button (Hidden on print) -->
        <div class="d-print-none mb-4 text-center">
            <div class="alert alert-info d-inline-block shadow-sm">
                <i class="bi bi-info-circle me-2"></i> This is a print-friendly preview. Press the button below to print.
            </div>
            <div class="mt-2">
                <button @click="goBack" class="btn btn-outline-secondary me-2 px-4">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </button>
                <button @click="print" class="btn btn-primary px-5 shadow">
                    <i class="bi bi-printer me-2"></i> Print Application
                </button>
            </div>
        </div>

        <!-- Official Header -->
        <div class="paper-sheet shadow-lg mx-auto">
            <div class="print-header text-center mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                    <div class="logo-box" style="width: 100px;">
                        <img v-if="$page.props.tenant?.logo_path" :src="`/storage/${$page.props.tenant.logo_path}`" class="img-fluid" />
                        <img v-else src="/images/logo.png" alt="LNMU Logo" class="img-fluid shadow-sm rounded-circle p-1 bg-white border" />
                    </div>
                    <div class="text-center flex-grow-1">
                        <h2 class="fw-bold mb-0 text-uppercase" style="font-family: serif; letter-spacing: 1px;">{{ $page.props.tenant?.name || 'Portal Name' }}</h2>
                        <h5 class="mb-0 text-muted">{{ $page.props.tenant?.header_address || 'Kameshwaranagar, Darbhanga, Bihar 846004' }}</h5>
                        <div class="mt-2 fw-bold text-dark border-top border-bottom py-1 d-inline-block px-4">
                            {{ $page.props.tenant?.header_subtext_prefix || 'APPLICATION FORM FOR' }} {{ application?.opening?.program?.title || application?.opening?.job?.title || 'PROGRAM' }}
                        </div>
                    </div>
                    <div v-if="(previewConfig?.show_photo ?? true)" class="photo-box border d-flex align-items-center justify-content-center text-muted" style="width: 120px; height: 150px; font-size: 10px;">
                        <img v-if="applicant?.profile_photo_path" :src="`/storage/${applicant.profile_photo_path}`" class="img-fluid h-100 w-100" style="object-fit: cover;" />
                        <span v-else>PASTE RECENT<br>PASSPORT SIZE<br>PHOTOGRAPH</span>
                    </div>
                </div>
            </div>

            <!-- Application Info -->
            <div class="row mb-4">
                <div class="col-6">
                    <div v-if="(previewConfig?.show_application_no ?? true)" class="info-row">
                        <span class="label">Application No:</span>
                        <span class="value fw-bold text-primary">{{ application?.application_no || '________________' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Date:</span>
                        <span class="value">{{ application?.submitted_at ? new Date(application.submitted_at).toLocaleDateString() : '________________' }}</span>
                    </div>
                    <div v-if="(previewConfig?.show_program ?? true)" class="info-row">
                        <span class="label">Program Code:</span>
                        <span class="value fw-bold">{{ application?.opening?.program?.job_code || '________________' }}</span>
                    </div>
                </div>
                <div class="col-6">
                    <div v-if="(previewConfig?.show_subject ?? true)" class="info-row">
                        <span class="label">Subject:</span>
                        <span class="value fw-bold">{{ application?.opening?.subject?.name || '________________' }}</span>
                    </div>
                    <div v-if="(previewConfig?.show_fees ?? true)" class="info-row">
                        <span class="label">Fees Paid:</span>
                        <span class="value fw-bold text-success">₹{{ application?.total_amount || '0' }}</span>
                    </div>
                    <div v-if="(previewConfig?.show_status ?? true)" class="info-row">
                        <span class="label">Status:</span>
                        <span class="value badge bg-light text-dark border">{{ application?.status?.toUpperCase() || 'DRAFT' }}</span>
                    </div>
                </div>
            </div>

            <!-- Form Fields -->
            <div v-for="step in sortedSteps" :key="step" class="mb-4">
                <div class="row g-3">
                    <div v-for="field in fieldsByStep[step]" :key="field.id" 
                        :class="field.field_type === 'table' ? 'col-12' : (field.field_type === 'textarea' ? 'col-12' : 'col-md-6')">
                        
                        <div class="field-view mb-2">
                            <label class="field-label d-block text-muted small fw-bold text-uppercase">{{ field.label }}</label>
                            
                            <div v-if="field.field_type === 'table'" class="mt-2">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0 align-middle small">
                                        <thead class="table-light">
                                            <tr>
                                                <th v-for="col in getTableConfig(field).columns" :key="col.label">
                                                    {{ col.label }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, rIdx) in getFieldValue(field)" :key="rIdx">
                                                <td v-for="col in getTableConfig(field).columns" :key="col.label">
                                                    <template v-if="col.type === 'file'">
                                                        <span v-if="row[col.label]" class="text-success"><i class="bi bi-check-circle-fill"></i> Uploaded</span>
                                                        <span v-else class="text-muted">No File</span>
                                                    </template>
                                                    <template v-else>
                                                        {{ row[col.label] || '—' }}
                                                    </template>
                                                </td>
                                            </tr>
                                            <tr v-if="!responses">
                                                <td :colspan="getTableConfig(field).columns.length" class="text-center py-3 text-muted italic">
                                                    (Tabular data space)
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div v-else-if="field.field_type === 'file'" class="mt-1">
                                <div v-if="getFieldValue(field) && responses" class="d-flex align-items-center gap-2">
                                    <span class="text-success"><i class="bi bi-file-earmark-check-fill me-1"></i> Document Uploaded</span>
                                    <small class="text-muted">(Verified at backend)</small>
                                </div>
                                <div v-else class="border-bottom border-dashed py-1 text-muted">
                                    ________________________________
                                </div>
                            </div>

                            <div v-else class="field-value-text border-bottom border-dashed py-1 min-h-24">
                                {{ getFieldValue(field) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signature Section -->
            <div v-if="(previewConfig?.show_signature ?? true) || (previewConfig?.show_thumb_impression ?? true)" class="mt-5 pt-4">
                <div class="row align-items-end">
                    <div v-if="(previewConfig?.show_thumb_impression ?? true)" class="col-6">
                        <div class="border-top pt-2 text-center" style="width: 200px;">
                            <small class="text-muted d-block">Applicant's Left Thumb Impression</small>
                            <div class="mt-4 border" style="height: 60px;"></div>
                        </div>
                    </div>
                    <div v-if="(previewConfig?.show_signature ?? true)" class="col-6 d-flex flex-column align-items-center">
                        <div class="mb-2 text-center">
                            <img v-if="applicant?.signature_path" :src="`/storage/${applicant.signature_path}`" style="max-height: 60px; max-width: 180px;" />
                            <div v-else class="mt-4" style="height: 40px;"></div>
                        </div>
                        <div class="border-top pt-2 text-center px-4">
                            <small class="fw-bold text-dark">Applicant's Signature</small>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="application?.opening?.job?.footer_notes" class="footer-notes mt-4 p-3 bg-light border-start border-4 border-primary">
                <h6 class="fw-bold small text-uppercase mb-2 text-primary">Instructions / Notes:</h6>
                <div class="text-dark small" style="white-space: pre-wrap; font-size: 11px; line-height: 1.6;">
                    {{ application.opening.job.footer_notes }}
                </div>
            </div>

            <div v-if="(previewConfig?.show_declaration ?? true)" class="footer-declaration mt-5 pt-4 border-top">
                <h6 class="fw-bold small text-uppercase mb-2">Declaration:</h6>
                <p class="text-muted" style="font-size: 11px; text-align: justify; line-height: 1.5;">
                    {{ previewConfig?.declaration_text || "I hereby declare that all the information given by me in this application are true and correct to the best of my knowledge and belief. In the event of any information being found false or incorrect or ineligibility being detected before or after the interview/selection, my candidature/appointment is liable to be cancelled." }}
                </p>
                <div class="mt-4 d-flex justify-content-between">
                    <span class="small text-muted">Date: ........................</span>
                    <span class="small text-muted">Place: ........................</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.print-container {
    background-color: #f8f9fa;
    min-height: 100vh;
}

.paper-sheet {
    background: white;
    width: 210mm;
    min-height: 297mm;
    padding: 20mm;
    margin: 0 auto;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

.info-row {
    margin-bottom: 5px;
}

.info-row .label {
    font-weight: 600;
    font-size: 13px;
    color: #666;
    margin-right: 8px;
}

.info-row .value {
    font-size: 14px;
}

.field-label {
    letter-spacing: 0.5px;
    font-size: 11px !important;
}

.field-value-text {
    font-size: 14px;
    color: #333;
    word-break: break-word;
}

.border-dashed {
    border-bottom-style: dashed !important;
    border-bottom-color: #dee2e6 !important;
}

.min-h-24 {
    min-height: 24px;
}

.step-title {
    font-size: 12px;
}

@media print {
    body {
        background: white !important;
    }
    .print-container {
        padding: 0 !important;
        background: white !important;
    }
    .paper-sheet {
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
    }
    .d-print-none {
        display: none !important;
    }
    .page-break {
        page-break-before: always;
    }
    @page {
        margin: 15mm;
    }
}
</style>
