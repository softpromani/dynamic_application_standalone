<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';

const props = defineProps({
    template: Object,
    steps:    Object,    // fields grouped by step number
    subjects: Array,
});

const currentStep = ref(
    Object.keys(props.steps).length > 0 ? parseInt(Object.keys(props.steps)[0]) : 1
);

const stepNumbers = computed(() => Object.keys(props.steps).map(Number).sort((a, b) => a - b));
const totalSteps  = computed(() => stepNumbers.value.length);
const stepFields  = computed(() => props.steps[currentStep.value] || []);

const fieldTypeIcon = (type) => {
    const icons = {
        text:     'bi-input-cursor-text',
        textarea: 'bi-text-paragraph',
        email:    'bi-envelope',
        tel:      'bi-telephone',
        number:   'bi-123',
        date:     'bi-calendar3',
        select:   'bi-menu-button-wide',
        radio:    'bi-ui-radios',
        checkbox: 'bi-check2-square',
        file:     'bi-cloud-upload',
        subject:  'bi-journal-bookmark-fill',
    };
    return icons[type] || 'bi-input-cursor';
};
</script>

<template>
    <Head :title="`Preview: ${template.name}`" />
    <AdminLayout>
        <template #header>
            <span class="text-muted fw-normal">Templates /</span> Preview
        </template>

        <div class="preview-page">
            <!-- Back + Edit buttons -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <Link :href="route('templates.index')" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>All Templates
                </Link>
                <Link :href="route('templates.edit', template.id)" class="btn btn-edit btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit Template
                </Link>
                <Link :href="route('templates.preview', template.id)" class="btn btn-outline-danger btn-sm shadow-sm">
                    <i class="bi bi-printer me-1"></i> Print Version
                </Link>
                <span class="badge-status ms-auto" :class="template.is_active ? 'badge-active' : 'badge-inactive'">
                    {{ template.is_active ? '● Active' : '○ Inactive' }}
                </span>
            </div>

            <div class="preview-layout">

                <!-- Left: Template info + step nav -->
                <div class="preview-sidebar">
                    <div class="sidebar-card">
                        <div class="sidebar-header">
                            <i class="bi bi-file-earmark-text-fill me-2"></i>{{ template.name }}
                        </div>
                        <div class="sidebar-body">
                            <p v-if="template.description" class="sidebar-desc">{{ template.description }}</p>
                            <div v-if="template.jobs && template.jobs.length > 0" class="mt-3">
                                <div class="step-nav-title">LINKED PROGRAMS</div>
                                <div v-for="job in template.jobs" :key="job.id" class="linked-job-chip mb-2">
                                    <i class="bi bi-briefcase me-2"></i>{{ job.title }}
                                </div>
                            </div>

                            <div class="step-nav mt-4">
                                <div class="step-nav-title">FORM STEPS</div>
                                <div
                                    v-for="sn in stepNumbers" :key="sn"
                                    class="step-nav-item"
                                    :class="{ 'step-nav-active': currentStep === sn }"
                                    @click="currentStep = sn"
                                >
                                    <div class="step-nav-num">{{ sn }}</div>
                                    <div class="step-nav-info">
                                        <span>Step {{ sn }}</span>
                                        <small>{{ (steps[sn] || []).length }} fields</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Live form preview -->
                <div class="preview-main">
                    <div class="form-preview-card">
                        <!-- Step indicator -->
                        <div class="form-step-bar">
                            <div v-for="sn in stepNumbers" :key="sn"
                                class="form-step-dot"
                                :class="{ 'form-step-dot-active': currentStep === sn, 'form-step-dot-done': sn < currentStep }">
                                <div class="fsd-circle">{{ sn }}</div>
                                <span class="fsd-label">Step {{ sn }}</span>
                            </div>
                        </div>

                        <div class="form-body px-4 pb-4">
                            <div v-if="stepFields.length === 0" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-3 d-block mb-3 opacity-50"></i>
                                No fields in this step.
                            </div>

                            <div v-for="field in stepFields" :key="field.id" class="preview-field-wrap">
                                <label class="preview-field-label">
                                    {{ field.label }}
                                    <span v-if="field.is_required" class="req-mark">*</span>
                                </label>

                                <!-- Subject picker -->
                                <select v-if="field.is_subject_field" class="form-select">
                                    <option value="">— Select Subject —</option>
                                    <option v-for="sub in subjects" :key="sub.id" :value="sub.id">
                                        {{ sub.name }} ({{ sub.code }})
                                    </option>
                                </select>

                                <!-- Select -->
                                <select v-else-if="field.field_type === 'select'" class="form-select">
                                    <option value="">{{ field.placeholder || '— Select —' }}</option>
                                    <option v-for="opt in field.options" :key="opt">{{ opt }}</option>
                                </select>

                                <!-- Textarea -->
                                <textarea v-else-if="field.field_type === 'textarea'" class="form-control"
                                    rows="3" :placeholder="field.placeholder"></textarea>

                                <!-- File -->
                                <input v-else-if="field.field_type === 'file'" type="file" class="form-control" />

                                <!-- Checkbox -->
                                <div v-else-if="field.field_type === 'checkbox'" class="form-check">
                                    <input class="form-check-input" type="checkbox" />
                                    <label class="form-check-label">{{ field.label }}</label>
                                </div>

                                <!-- Radio -->
                                <div v-else-if="field.field_type === 'radio'" class="d-flex flex-wrap gap-3">
                                    <div v-for="opt in field.options" :key="opt" class="form-check">
                                        <input class="form-check-input" type="radio" :name="`field_${field.id}`" />
                                        <label class="form-check-label">{{ opt }}</label>
                                    </div>
                                </div>

                                <!-- Default -->
                                <input v-else :type="field.field_type" class="form-control"
                                    :placeholder="field.placeholder" />

                                <!-- Type tag -->
                                <div class="field-meta-tag mt-1">
                                    <i class="bi me-1" :class="fieldTypeIcon(field.field_type)"></i>
                                    {{ field.is_subject_field ? 'Subject Picker' : field.field_type }}
                                    <span v-if="field.is_required" class="ms-2 text-danger">Required</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step navigation -->
                        <div class="form-nav-footer">
                            <button class="btn btn-outline-secondary" :disabled="currentStep === stepNumbers[0]"
                                @click="currentStep = stepNumbers[stepNumbers.indexOf(currentStep) - 1]">
                                <i class="bi bi-chevron-left me-1"></i>Previous
                            </button>
                            <span class="step-counter">Step {{ stepNumbers.indexOf(currentStep) + 1 }} of {{ totalSteps }}</span>
                            <button class="btn btn-primary-red" :disabled="currentStep === stepNumbers[stepNumbers.length - 1]"
                                @click="currentStep = stepNumbers[stepNumbers.indexOf(currentStep) + 1]">
                                Next <i class="bi bi-chevron-right ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.preview-page { padding-bottom: 2rem; }

.btn-edit { background: #8b0000; color: #fff; border: none; font-weight: 600; }
.btn-edit:hover { background: #6b0000; color: #fff; }

.badge-status { font-size: 0.78rem; font-weight: 700; padding: 5px 14px; border-radius: 100px; }
.badge-active { background: #d4edda; color: #155724; }
.badge-inactive { background: #f8d7da; color: #721c24; }

/* Layout */
.preview-layout { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start; }
@media (max-width: 768px) { .preview-layout { grid-template-columns: 1fr; } }

/* Sidebar */
.sidebar-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); overflow: hidden; }
.sidebar-header { background: linear-gradient(135deg, #8b0000, #b71c1c); color: #fff; padding: 18px; font-weight: 700; font-size: 0.9rem; line-height: 1.4; }
.sidebar-body { padding: 18px; }
.sidebar-desc { font-size: 0.82rem; color: #666; line-height: 1.5; margin-bottom: 12px; }
.linked-job-chip { background: #fff8f8; border: 1px solid #f0d0d0; color: #8b0000; font-size: 0.78rem; font-weight: 600; padding: 7px 12px; border-radius: 8px; }

.step-nav-title { font-size: 0.68rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
.step-nav-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; cursor: pointer; transition: background 0.15s; margin-bottom: 4px; }
.step-nav-item:hover { background: #f5f5f5; }
.step-nav-active { background: #fff8f8 !important; }
.step-nav-num { width: 28px; height: 28px; border-radius: 50%; background: #e0e0e0; color: #555; font-weight: 700; font-size: 0.82rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.step-nav-active .step-nav-num { background: #8b0000; color: #fff; }
.step-nav-info { display: flex; flex-direction: column; }
.step-nav-info span { font-size: 0.82rem; font-weight: 600; color: #333; }
.step-nav-info small { font-size: 0.7rem; color: #999; }

/* Form Preview Card */
.form-preview-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); overflow: hidden; }

.form-step-bar { display: flex; align-items: center; padding: 20px 24px; background: #f9f9f9; border-bottom: 1px solid #f0f0f0; gap: 0; }
.form-step-dot { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
.form-step-dot:not(:last-child)::after { content: ''; position: absolute; top: 14px; left: 50%; width: 100%; height: 2px; background: #e0e0e0; z-index: 0; }
.form-step-dot-done::after { background: #27ae60 !important; }
.fsd-circle { width: 30px; height: 30px; border-radius: 50%; background: #e0e0e0; color: #777; font-weight: 700; font-size: 0.82rem; display: flex; align-items: center; justify-content: center; z-index: 1; position: relative; transition: all 0.25s; }
.form-step-dot-active .fsd-circle { background: #8b0000; color: #fff; }
.form-step-dot-done .fsd-circle { background: #27ae60; color: #fff; }
.fsd-label { font-size: 0.68rem; color: #999; margin-top: 5px; font-weight: 500; }
.form-step-dot-active .fsd-label { color: #8b0000; font-weight: 700; }

.form-body { padding: 24px; display: flex; flex-direction: column; gap: 18px; }

.preview-field-wrap {}
.preview-field-label { display: block; font-size: 0.875rem; font-weight: 600; color: #333; margin-bottom: 6px; }
.req-mark { color: #e74c3c; font-weight: 700; }
.field-meta-tag { font-size: 0.7rem; color: #aaa; }

.form-nav-footer { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-top: 1px solid #f0f0f0; background: #fafafa; }
.step-counter { font-size: 0.82rem; color: #666; font-weight: 600; }

.btn-primary-red { background: linear-gradient(135deg, #8b0000, #c0392b); color: #fff; border: none; border-radius: 8px; padding: 9px 22px; font-weight: 600; font-size: 0.875rem; }
.btn-primary-red:hover:not(:disabled) { background: linear-gradient(135deg, #6b0000, #a93226); color: #fff; }
.btn-primary-red:disabled { opacity: 0.4; }
</style>
