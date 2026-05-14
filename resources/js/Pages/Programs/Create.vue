<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';
import TextInput from '@softpro-core/Components/TextInput.vue';

const props = defineProps({
    customEntities: Array,
    templates: Array,
});

const form = useForm({
    job_code: '',
    title: '',
    description: '',
    application_start_date: '',
    application_end_date: '',
    last_payment_date: '',
    application_types: [
        { name: 'General', fee: 0, fine_amount: 0 },
        { name: 'SC / ST / Women / PH', fee: 0, fine_amount: 0 }
    ],
    tax_percentage: 0,
    form_template_id: '',
    openings: [],
    footer_notes: '',
    is_payable: true,
    custom_entity_id: '', // The entity used for vacancies (e.g. Subject, Department)
});

// --- Application Type management ---
const addType = () => form.application_types.push({ name: '', fee: 0, fine_amount: 0 });
const removeType = (index) => {
    if (form.application_types.length > 1) form.application_types.splice(index, 1);
};

// --- Opening management ---
const newVacancy = ref({ subject_id: '', seats: 1 });
const vacancyError = ref('');

const selectedEntity = computed(() => {
    return props.customEntities.find(e => e.id == form.custom_entity_id);
});

const entityValues = computed(() => {
    return selectedEntity.value?.values || [];
});

const usedValueIds = () => form.openings.map(v => v.subject_id);

const addVacancy = () => {
    vacancyError.value = '';
    
    // If an entity is selected, a value must be chosen
    if (form.custom_entity_id && !newVacancy.value.subject_id) {
        vacancyError.value = `Please select a ${selectedEntity.value.display_name}.`;
        return;
    }

    if (newVacancy.value.subject_id && usedValueIds().includes(newVacancy.value.subject_id)) {
        vacancyError.value = 'This value is already added.';
        return;
    }

    if (!newVacancy.value.seats || newVacancy.value.seats < 1) {
        vacancyError.value = 'Seats must be at least 1.';
        return;
    }

    form.openings.push({ ...newVacancy.value });
    newVacancy.value = { subject_id: '', seats: 1 };
};

const removeVacancy = (index) => {
    form.openings.splice(index, 1);
};

const getEntityValueLabel = (id) => {
    if (!id) return 'General (No Entity)';
    // Search across all entities just in case
    for (const entity of props.customEntities) {
        const val = entity.values.find(v => v.id == id);
        if (val) return val.label;
    }
    return '—';
};

// Reset openings if entity changes
watch(() => form.custom_entity_id, () => {
    form.openings = [];
    newVacancy.value = { subject_id: '', seats: 1 };
});

const submit = () => {
    form.post(route('programs.store'));
};
</script>

<template>
    <Head title="Create Program Post" />

    <AdminLayout>
        <template #header>Create New Program Posting</template>

        <form @submit.prevent="submit">
            <!-- General Validation Error Alert -->
            <div v-if="Object.keys(form.errors).length > 0" class="alert alert-danger py-2 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                Please correct the errors highlighted below.
            </div>

            <div class="row">
                <!-- Left: Program Details -->
                <div class="col-lg-7 mb-3">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0 fw-bold text-primary"><i class="bi bi-briefcase me-2"></i>Program Configuration</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <TextInput v-model="form.job_code" label="Program Code" placeholder="e.g., GUEST-2024-01"
                                        :error="form.errors.job_code" required />
                                </div>
                                <div class="col-md-8">
                                    <TextInput v-model="form.title" label="Program Title"
                                        placeholder="e.g., Recruitment of Guest Teachers"
                                        :error="form.errors.title" required />
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Application Form Template</label>
                                    <select v-model="form.form_template_id" class="form-select form-select-sm" :class="{'is-invalid': form.errors.form_template_id}">
                                        <option value="">— Select Template —</option>
                                        <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                                    </select>
                                    <div v-if="form.errors.form_template_id" class="invalid-feedback">{{ form.errors.form_template_id }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Opening Grouping (Entity)</label>
                                    <select v-model="form.custom_entity_id" class="form-select form-select-sm shadow-sm" style="border-color: #0d6efd40;">
                                        <option value="">None (General Program Application)</option>
                                        <option v-for="e in customEntities" :key="e.id" :value="e.id">{{ e.display_name }}</option>
                                    </select>
                                    <div class="form-text x-small text-muted">Select if applications should be grouped by Subject, Department, etc.</div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <TextInput v-model="form.application_start_date" type="date" label="Start Date"
                                        :error="form.errors.application_start_date" required />
                                </div>
                                <div class="col-md-4">
                                    <TextInput v-model="form.application_end_date" type="date" label="End Date"
                                        :error="form.errors.application_end_date" required />
                                </div>
                                <div class="col-md-4">
                                    <TextInput v-model="form.last_payment_date" type="date" label="Last Payment Date"
                                        :error="form.errors.last_payment_date" required />
                                </div>
                            </div>

                            <div v-if="form.is_payable">
                                <hr class="my-4 opacity-25" />
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-muted small text-uppercase mb-0">Application Types & Fees</h6>
                                    <button type="button" @click="addType" class="btn btn-xs btn-outline-primary shadow-sm rounded-pill px-3">
                                        <i class="bi bi-plus-lg me-1"></i>Add Category
                                    </button>
                                </div>

                                <div v-for="(type, idx) in form.application_types" :key="idx" class="row g-2 mb-3 align-items-end p-2 border rounded-3 bg-light position-relative">
                                    <div class="col-md-5">
                                        <TextInput v-model="type.name" label="Category / Type Name" placeholder="e.g. General, PH, SC/ST..." 
                                            :error="form.errors[`application_types.${idx}.name`]" required />
                                    </div>
                                    <div class="col-md-3">
                                        <TextInput v-model="type.fee" type="number" step="0.01" label="Fee (₹)" 
                                            :error="form.errors[`application_types.${idx}.fee`]" required />
                                    </div>
                                    <div class="col-md-3">
                                        <TextInput v-model="type.fine_amount" type="number" step="0.01" label="Fine (₹)" 
                                            :error="form.errors[`application_types.${idx}.fine_amount`]" />
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" @click="removeType(idx)" class="btn btn-outline-danger btn-sm mb-2 border-0" 
                                            :disabled="form.application_types.length === 1">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="alert alert-info py-2 small mt-4 mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Direct Application Enabled:</strong> Applicants will not be asked for category selection or fees for this program.
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <TextInput v-model="form.tax_percentage" type="number" step="0.01"
                                        label="Tax Percentage (%)" :error="form.errors.tax_percentage" />
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" v-model="form.is_payable" id="isPayable">
                                        <label class="form-check-label fw-semibold small" for="isPayable">Fee Required for this Program</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-2">
                                <label class="form-label fw-bold small text-primary"><i class="bi bi-info-circle me-1"></i>Declaration / Footer Notes (Public)</label>
                                <textarea v-model="form.footer_notes" class="form-control"
                                    rows="4" placeholder="Terms, conditions, or mandatory declarations..."></textarea>
                                <div class="form-text x-small text-muted">Visible to applicants at the bottom of the form.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Openings / Vacancies -->
                <div class="col-lg-5 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0 fw-bold text-success">
                                <i class="bi bi-layers me-2"></i> Program Openings
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Add row -->
                            <div class="row g-2 align-items-end mb-4 bg-light p-3 rounded-3 border border-dashed border-success">
                                <div class="col-12 mb-2" v-if="form.custom_entity_id">
                                    <label class="form-label small fw-bold mb-1">{{ selectedEntity?.display_name }}</label>
                                    <select v-model="newVacancy.subject_id" class="form-select form-select-sm shadow-sm">
                                        <option value="">— Select {{ selectedEntity?.display_name }} —</option>
                                        <option v-for="v in entityValues" :key="v.id" :value="v.id"
                                            :disabled="usedValueIds().includes(v.id)">
                                            {{ v.label }} <span v-if="usedValueIds().includes(v.id)">(added)</span>
                                        </option>
                                    </select>
                                </div>
                                <div :class="form.custom_entity_id ? 'col-8' : 'col-10'">
                                    <label class="form-label small fw-bold mb-1">Seats / Capacity</label>
                                    <input v-model.number="newVacancy.seats" type="number" min="1"
                                        class="form-control form-control-sm shadow-sm" placeholder="No. of Seats" />
                                </div>
                                <div class="col-2">
                                    <button type="button" @click="addVacancy"
                                        class="btn btn-success btn-sm w-100 shadow-sm">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                                <div v-if="vacancyError" class="col-12 mt-2">
                                    <div class="alert alert-danger py-1 x-small mb-0">{{ vacancyError }}</div>
                                </div>
                            </div>

                            <!-- List -->
                            <div v-if="form.openings.length === 0" class="text-center text-muted py-5 border rounded-3 bg-light border-dashed">
                                <i class="bi bi-inbox d-block display-4 mb-2 opacity-25"></i>
                                <p class="mb-0">No openings defined.</p>
                                <small v-if="!form.custom_entity_id">Add a general opening above or select an Entity on the left.</small>
                            </div>
                            <div v-else class="table-responsive rounded-3 border">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">{{ form.custom_entity_id ? selectedEntity?.display_name : 'Grouping' }}</th>
                                            <th class="text-center" style="width:100px">Seats</th>
                                            <th style="width:50px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(v, i) in form.openings" :key="i">
                                            <td class="ps-3">
                                                <div class="fw-bold small">{{ getEntityValueLabel(v.subject_id) }}</div>
                                                <div v-if="v.subject_id" class="x-small text-muted">{{ selectedEntity?.display_name }}</div>
                                            </td>
                                            <td class="text-center">
                                                <input v-model.number="v.seats" type="number" min="1"
                                                    class="form-control form-control-sm text-center fw-bold" />
                                            </td>
                                            <td class="text-end pe-3">
                                                <button type="button" @click="removeVacancy(i)"
                                                    class="btn btn-link text-danger p-0">
                                                    <i class="bi bi-trash-fill fs-5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="form.errors['openings']" class="text-danger small mt-2">
                                {{ form.errors['openings'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="card border-0 shadow-sm mb-5">
                <div class="card-body d-flex justify-content-end gap-2">
                    <a :href="route('programs.index')" class="btn btn-outline-secondary btn-sm px-4 rounded-pill">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm px-5 rounded-pill fw-bold shadow-sm" :disabled="form.processing">
                        <i class="bi bi-check-lg me-2"></i>Save Program Posting
                    </button>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>

<style scoped>
.btn-xs { padding: 1px 10px; font-size: 11px; }
.x-small { font-size: 0.75rem; }
.border-dashed { border-style: dashed !important; }
.font-mono { font-family: monospace; }
</style>
