<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    subjects: Array,
    virtualColumns: Array,
    customEntities: Array,
    profileFields: Array,
});

/* ─── Wizard state ─── */
const wizardStep = ref(1); // builder wizard: 1=Info, 2=Fields, 3=Review

/* ─── Template meta ─── */
const meta = useForm({
    name:               '',
    description:        '',
    is_profile:         false,
    fields:             [],
});

/* ─── Steps (of the FORM being built, not the wizard) ─── */
const formSteps = ref([{ id: 1, title: 'Step 1' }]);
const nextStepId = ref(2);

const addFormStep = () => {
    formSteps.value.push({ id: nextStepId.value, title: `Step ${nextStepId.value}` });
    nextStepId.value++;
};
const removeFormStep = (id) => {
    if (formSteps.value.length === 1) return;
    formSteps.value = formSteps.value.filter(s => s.id !== id);
    meta.fields = meta.fields.filter(f => f.step !== id);
};

/* ─── Field types ─── */
const FIELD_TYPES = [
    { value: 'text',     label: 'Short Text',    icon: 'bi-input-cursor-text' },
    { value: 'textarea', label: 'Long Text',      icon: 'bi-text-paragraph' },
    { value: 'email',    label: 'Email',          icon: 'bi-envelope' },
    { value: 'tel',      label: 'Phone',          icon: 'bi-telephone' },
    { value: 'number',   label: 'Number',         icon: 'bi-123' },
    { value: 'date',     label: 'Date',           icon: 'bi-calendar3' },
    { value: 'select',   label: 'Dropdown',       icon: 'bi-menu-button-wide' },
    { value: 'radio',    label: 'Radio Buttons',  icon: 'bi-ui-radios' },
    { value: 'checkbox', label: 'Checkbox',       icon: 'bi-check2-square' },
    { value: 'file',     label: 'File Upload',    icon: 'bi-cloud-upload' },
    { value: 'table',    label: 'Tabular/Table',  icon: 'bi-table' },
];

const SYSTEM_FIELDS = computed(() => {
    if (!props.profileFields) return [];
    
    return props.profileFields.map(f => ({
        value: f.system_alias || f.id.toString(),
        label: `Profile: ${f.label}`,
        field_type: f.field_type,
        icon: getIconForType(f.field_type),
        original: f
    })); // Removed alias filter to show all fields
});

function getIconForType(type) {
    return FIELD_TYPES.find(t => t.value === type)?.icon || 'bi-input-cursor-text';
}

/* ─── Active step being edited ─── */
const activeStepId = ref(1);
const activeStep = computed(() => formSteps.value.find(s => s.id === activeStepId.value));

/* ─── Fields for active step ─── */
const activeFields = computed(() => meta.fields.filter(f => f.step === activeStepId.value));

/* ─── Add field panel ─── */
const showPanel = ref(false);
const newField  = ref(makeBlankField());
const editingFieldIndex = ref(null);

function makeBlankField() {
    return {
        step:         activeStepId.value,
        field_type:   'text',
        label:        '',
        placeholder:  '',
        is_required:  false,
        options:      [],          // for select / radio
        _optionInput: '',          // temp buffer
        table_config: {
            columns: [{ label: '', type: 'text' }],
            is_dynamic: true,
            fixed_rows: []
        },
        _columnLabelInput: '',
        _fixedRowInput: '',
        system_alias: '',
        custom_entity_id: null,
    };
}

const openPanel = () => {
    editingFieldIndex.value = null;
    newField.value = makeBlankField();
    newField.value.step = activeStepId.value;
    showPanel.value = true;
};

const editField = (index) => {
    const items = meta.fields.filter(f => f.step === activeStepId.value);
    const target = items[index];
    const globalIdx = meta.fields.indexOf(target);
    
    editingFieldIndex.value = globalIdx;
    
    // Deep clone
    const fieldData = JSON.parse(JSON.stringify(target));
    
    // Handle table_config
    if (fieldData.field_type === 'table' && (!fieldData.table_config || !fieldData.table_config.columns)) {
        fieldData.table_config = typeof fieldData.options === 'string' 
            ? JSON.parse(fieldData.options) 
            : fieldData.options;
    }
    
    fieldData._optionInput = '';
    fieldData._columnLabelInput = '';
    fieldData._fixedRowInput = '';
    
    newField.value = fieldData;
    showPanel.value = true;
};

const selectSystemField = (sf) => {
    newField.value.field_type = sf.field_type;
    newField.value.label = sf.original.label;
    newField.value.placeholder = sf.original.placeholder || `Pre-filled from profile...`;
    newField.value.is_required = sf.original.is_required;
    newField.value.system_alias = sf.value;
    newField.value.custom_entity_id = sf.original.custom_entity_id;
    
    if (sf.field_type === 'table') {
        newField.value.table_config = typeof sf.original.options === 'string' 
            ? JSON.parse(sf.original.options) 
            : sf.original.options;
    } else if (sf.field_type === 'select' || sf.field_type === 'radio') {
        newField.value.options = sf.original.options || [];
    }
};

const isVirtualColumnCreated = (alias) => {
    if (!alias || !props.virtualColumns) return false;
    return props.virtualColumns.some(col => col.Field === alias);
};

const closePanel = () => { showPanel.value = false; };

const addOption = () => {
    const val = newField.value._optionInput.trim();
    if (val) {
        newField.value.options.push(val);
        newField.value._optionInput = '';
    }
};
const removeOption = (i) => newField.value.options.splice(i, 1);

/* ─── Table Config Helpers ─── */
const addColumn = () => newField.value.table_config.columns.push({ label: '', type: 'text' });
const removeColumn = (i) => {
    if (newField.value.table_config.columns.length > 1) {
        newField.value.table_config.columns.splice(i, 1);
    }
};
const addFixedRow = () => {
    const val = newField.value._fixedRowInput.trim();
    if (val) {
        newField.value.table_config.fixed_rows.push(val);
        newField.value._fixedRowInput = '';
    }
};
const removeFixedRow = (i) => newField.value.table_config.fixed_rows.splice(i, 1);

const saveField = () => {
    if (!newField.value.label.trim()) return;
    const { _optionInput, _columnLabelInput, _fixedRowInput, ...field } = newField.value;
    
    // Convert table_config to options JSON for storage if it's a table
    if (field.field_type === 'table') {
        field.options = JSON.stringify(field.table_config);
    }
    
    if (editingFieldIndex.value !== null) {
        meta.fields[editingFieldIndex.value] = { ...field };
        editingFieldIndex.value = null;
    } else {
        meta.fields.push({ ...field });
    }
    
    showPanel.value = false;
};

const selectBasicField = (type) => {
    newField.value.field_type = type;
    newField.value.custom_entity_id = null;
};

const selectEntityField = (ent) => {
    newField.value.field_type = 'select';
    newField.value.custom_entity_id = ent.id;
    if (!newField.value.label) {
        newField.value.label = ent.display_name;
    }
};

const removeField = (index) => {
    const globalIndex = meta.fields.findIndex(
        (f, i) => f.step === activeStepId.value && meta.fields.filter(x => x.step === activeStepId.value).indexOf(f) === index
    );
    // simpler: remove from activeFields index
    const items = meta.fields.filter(f => f.step === activeStepId.value);
    const target = items[index];
    meta.fields = meta.fields.filter(f => f !== target);
};

const moveField = (index, dir) => {
    const items = meta.fields.filter(f => f.step === activeStepId.value);
    const swapIdx = index + dir;
    if (swapIdx < 0 || swapIdx >= items.length) return;
    // swap in meta.fields
    const ai = meta.fields.indexOf(items[index]);
    const bi = meta.fields.indexOf(items[swapIdx]);
    [meta.fields[ai], meta.fields[bi]] = [meta.fields[bi], meta.fields[ai]];
};

/* ─── Validation ─── */
const errors = ref({});

const validateMeta = () => {
    errors.value = {};
    if (!meta.name.trim()) errors.value.name = 'Template name is required.';
    return Object.keys(errors.value).length === 0;
};
const validateFields = () => {
    if (meta.fields.length === 0) {
        errors.value.fields = 'Add at least one field.';
        return false;
    }
    return true;
};

/* ─── Wizard navigation ─── */
const goToStep = (s) => {
    if (s === 2 && !validateMeta()) return;
    if (s === 3 && !validateFields()) return;
    wizardStep.value = s;
};

/* ─── Submit ─── */
const submit = () => {
    if (!validateMeta() || !validateFields()) return;
    meta.post(route('templates.store'));
};

/* ─── Helpers ─── */
const fieldTypeLabel = (type) => FIELD_TYPES.find(t => t.value === type)?.label || type;
const fieldTypeIcon  = (type) => FIELD_TYPES.find(t => t.value === type)?.icon  || 'bi-input-cursor';

const needsOptions = (type) => ['select', 'radio'].includes(type);
</script>

<template>
    <Head title="Create Form Template" />
    <AdminLayout>
        <template #header>Create Application Form Template</template>

        <div class="builder-page">

            <!-- ── Wizard Progress Bar ── -->
            <div class="wizard-progress mb-4">
                <div class="wp-track">
                    <div class="wp-step" :class="{ active: wizardStep >= 1, done: wizardStep > 1 }" @click="wizardStep === 1 ? null : (wizardStep = 1)">
                        <div class="wp-circle"><i class="bi bi-info-circle-fill"></i></div>
                        <span class="wp-label">Template Info</span>
                    </div>
                    <div class="wp-line" :class="{ filled: wizardStep > 1 }"></div>
                    <div class="wp-step" :class="{ active: wizardStep >= 2, done: wizardStep > 2 }">
                        <div class="wp-circle"><i class="bi bi-layout-text-sidebar-reverse"></i></div>
                        <span class="wp-label">Build Fields</span>
                    </div>
                    <div class="wp-line" :class="{ filled: wizardStep > 2 }"></div>
                    <div class="wp-step" :class="{ active: wizardStep >= 3 }">
                        <div class="wp-circle"><i class="bi bi-check2-all"></i></div>
                        <span class="wp-label">Review & Save</span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════
                 STEP 1 – TEMPLATE INFO
            ═══════════════════════════════════════ -->
            <div v-if="wizardStep === 1" class="builder-card">
                <div class="builder-card-header">
                    <i class="bi bi-info-circle-fill me-2"></i>Template Information
                </div>
                <div class="builder-card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label field-label required-star">Template Name</label>
                            <input v-model="meta.name" type="text" class="form-control" :class="{ 'is-invalid': errors.name }"
                                placeholder="e.g., Guest Teacher 2024-25 Application Form" />
                            <div class="invalid-feedback">{{ errors.name }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label field-label">Description</label>
                            <textarea v-model="meta.description" class="form-control" rows="3"
                                placeholder="Briefly describe the purpose of this form (optional)…"></textarea>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="isProfile" v-model="meta.is_profile">
                                <label class="form-check-label fw-bold" for="isProfile">
                                    <i class="bi bi-person-bounding-box me-1 text-primary"></i> Master Profile Template
                                </label>
                                <div class="form-text small">
                                    If enabled, this template will be used for Applicant Profile completion. 
                                    <span class="text-danger">Note: Only one template can be the active profile template.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="builder-card-footer text-end">
                    <button @click="goToStep(2)" class="btn btn-primary-red">
                        Next: Build Fields <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════
                 STEP 2 – FIELD BUILDER
            ═══════════════════════════════════════ -->
            <div v-if="wizardStep === 2" class="builder-layout">

                <!-- Left: Step tabs + Fields list -->
                <div class="builder-left">
                    <div class="builder-card h-100">
                        <div class="builder-card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-layout-text-sidebar-reverse me-2"></i>Form Steps</span>
                            <button @click="addFormStep" class="btn btn-xs btn-outline-light">
                                <i class="bi bi-plus-lg me-1"></i>Add Step
                            </button>
                        </div>

                        <!-- Step tabs -->
                        <div class="step-tabs">
                            <div
                                v-for="step in formSteps"
                                :key="step.id"
                                class="step-tab"
                                :class="{ 'step-tab-active': activeStepId === step.id }"
                                @click="activeStepId = step.id"
                            >
                                <div class="d-flex align-items-center justify-content-between">
                                    <span>
                                        <i class="bi bi-layers me-2"></i>{{ step.title }}
                                        <span class="step-field-count ms-2">
                                            {{ meta.fields.filter(f => f.step === step.id).length }} fields
                                        </span>
                                    </span>
                                    <button v-if="formSteps.length > 1" @click.stop="removeFormStep(step.id)"
                                        class="btn btn-link btn-sm text-danger p-0 ms-2" title="Remove step">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Fields in active step -->
                        <div class="fields-list">
                            <div v-if="activeFields.length === 0" class="empty-fields-hint">
                                <i class="bi bi-arrows-expand d-block mb-2 fs-2 text-muted"></i>
                                No fields yet. Click <strong>+ Add Field</strong>
                            </div>

                            <div v-for="(field, idx) in activeFields" :key="idx" class="field-item">
                                <div class="field-item-left">
                                    <div class="field-type-badge">
                                        <i class="bi" :class="fieldTypeIcon(field.field_type)"></i>
                                    </div>
                                    <div>
                                        <div class="field-label-text">
                                            {{ field.label }}
                                            <span v-if="field.is_required" class="req-mark">*</span>
                                        </div>
                                        <div class="field-type-text">
                                            {{ fieldTypeLabel(field.field_type) }}
                                            <span v-if="field.system_alias" class="ms-2 badge bg-light text-dark border fw-normal" style="font-size: 0.65rem;">
                                                <i class="bi bi-tag-fill me-1"></i>{{ field.system_alias }}
                                            </span>
                                            <span v-if="field.field_type === 'subject'" class="subject-pill ms-1">
                                                <i class="bi bi-journal-bookmark-fill me-1"></i>Subject Picker
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="field-item-actions">
                                    <button @click="moveField(idx, -1)" class="btn btn-icon" :disabled="idx === 0" title="Move up">
                                        <i class="bi bi-chevron-up"></i>
                                    </button>
                                    <button @click="moveField(idx, 1)" class="btn btn-icon" :disabled="idx === activeFields.length - 1" title="Move down">
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <button @click="editField(idx)" class="btn btn-icon text-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button @click="removeField(idx)" class="btn btn-icon text-danger" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="builder-card-footer">
                            <button @click="openPanel" class="btn btn-add-field w-100">
                                <i class="bi bi-plus-circle me-2"></i>Add Field to {{ activeStep?.title }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Add Field Panel -->
                <div class="builder-right" v-if="showPanel">
                    <div class="panel-card">
                        <div class="panel-header">
                            <span><i class="bi bi-pencil-square me-2"></i>{{ editingFieldIndex !== null ? 'Edit' : 'New' }} Field — {{ activeStep?.title }}</span>
                            <button @click="closePanel" class="btn btn-link text-white p-0">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="panel-body">

                            <!-- Quick Profile Pre-fill -->
                            <div class="card card-body bg-light border-0 py-2 px-3 mb-3">
                                <h6 class="small fw-bold text-muted mb-2 text-uppercase"><i class="bi bi-person-fill-gear me-1"></i> Profile Pre-fill Components</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <button v-for="sf in SYSTEM_FIELDS" :key="sf.value" type="button" 
                                        @click="selectSystemField(sf)"
                                        class="btn btn-xs bg-white border shadow-xs text-start d-flex align-items-center"
                                        style="width: 160px; font-size: 11px; padding: 4px 8px;">
                                        <i :class="sf.icon" class="me-2 text-primary"></i> {{ sf.label }}
                                    </button>
                                </div>
                                <div v-if="SYSTEM_FIELDS.length === 0" class="alert alert-warning py-2 px-3 small mb-0 mt-2">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    No active Profile Template found. Create one and mark it as "Master Profile Template" to see fields here.
                                </div>
                                <div v-else class="mt-2 x-small text-muted">
                                    <i class="bi bi-info-circle me-1"></i> Choose a component to pre-fill applicant data automatically.
                                </div>
                            </div>

                            <!-- Field Type Grid -->
                            <div class="mb-3">
                                <label class="form-label field-label required-star">Basic Field Type</label>
                                <div class="field-type-grid">
                                    <div
                                        v-for="ft in FIELD_TYPES"
                                        :key="ft.value"
                                        class="ft-chip"
                                        :class="{ 'ft-chip-active': newField.field_type === ft.value && !newField.custom_entity_id, 'ft-chip-subject': ft.value === 'subject' }"
                                        @click="selectBasicField(ft.value)"
                                    >
                                        <i class="bi" :class="ft.icon"></i>
                                        <span>{{ ft.label }}</span>
                                    </div>
                                </div>

                                <!-- Dynamic Entities Grid -->
                                <div v-if="customEntities && customEntities.length > 0" class="mt-3">
                                    <label class="form-label field-label small text-muted mb-2">Or Use Master Entity:</label>
                                    <div class="field-type-grid">
                                        <div
                                            v-for="ent in customEntities"
                                            :key="'ent_' + ent.id"
                                            class="ft-chip ft-chip-entity"
                                            :class="{ 'ft-chip-active': newField.custom_entity_id === ent.id }"
                                            @click="selectEntityField(ent)"
                                        >
                                            <i class="bi bi-database-fill"></i>
                                            <span>{{ ent.display_name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Label -->
                            <div class="mb-3">
                                <label class="form-label field-label required-star">Label</label>
                                <input v-model="newField.label" type="text" class="form-control"
                                    placeholder="e.g., Select Subject, Full Name…" />
                            </div>

                            <!-- Internal Alias (For future Search Columns) -->
                            <div class="mb-3">
                                <label class="form-label field-label">Internal Alias (for Search/Sorting)</label>
                                <div class="input-group">
                                    <input v-model="newField.system_alias" type="text" class="form-control" placeholder="e.g. hs_marks, pg_marks" />
                                    <div v-if="newField.system_alias" class="input-group-text bg-white">
                                        <span v-if="isVirtualColumnCreated(newField.system_alias)" class="text-success small">
                                            <i class="bi bi-check-circle-fill me-1"></i>Active
                                        </span>
                                        <span v-else class="text-muted small">
                                            <i class="bi bi-circle me-1"></i>Inactive
                                        </span>
                                    </div>
                                </div>
                                <div class="form-text small text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Used for creating Virtual Search Columns. Use lowercase and underscores.
                                </div>
                                
                                <div v-if="newField.system_alias && !isVirtualColumnCreated(newField.system_alias)" class="mt-2">
                                    <a :href="route('admin.virtual-columns.index')" class="btn btn-xs btn-outline-primary">
                                        <i class="bi bi-gear me-1"></i> Manage Virtual Columns
                                    </a>
                                </div>
                            </div>

                            <!-- Placeholder (not for checkbox/file/subject) -->
                            <div class="mb-3" v-if="!['checkbox', 'file', 'subject', 'radio'].includes(newField.field_type)">
                                <label class="form-label field-label">Placeholder</label>
                                <input v-model="newField.placeholder" type="text" class="form-control"
                                    placeholder="Placeholder text (optional)" />
                            </div>

                            <!-- Options for select/radio -->
                            <div class="mb-3" v-if="needsOptions(newField.field_type)">
                                <label class="form-label field-label required-star">Options</label>
                                <div class="d-flex gap-2 mb-2">
                                    <input v-model="newField._optionInput" type="text" class="form-control form-control-sm"
                                        placeholder="Type an option and press Add"
                                        @keyup.enter="addOption" />
                                    <button @click="addOption" class="btn btn-sm btn-outline-primary" type="button">Add</button>
                                </div>
                                <div class="option-pills">
                                    <span v-for="(opt, oi) in newField.options" :key="oi" class="option-pill">
                                        {{ opt }}
                                        <button @click="removeOption(oi)" class="btn-remove-opt"><i class="bi bi-x"></i></button>
                                    </span>
                                    <span v-if="newField.options.length === 0" class="text-muted small">No options yet.</span>
                                </div>

                                <!-- Dynamic Source from Custom Entities -->
                                <div class="mt-3 border-top pt-2">
                                    <label class="form-label field-label small text-primary">
                                        <i class="bi bi-database-fill me-1"></i> OR Dynamic Source (Master Entity)
                                    </label>
                                    <select v-model="newField.custom_entity_id" class="form-select form-select-sm">
                                        <option :value="null">-- Manual Entry (above) --</option>
                                        <option v-for="ent in customEntities" :key="ent.id" :value="ent.id">
                                            {{ ent.display_name }} (Dynamic List)
                                        </option>
                                    </select>
                                    <div class="form-text x-small text-muted">
                                        If selected, options will be pulled dynamically from the master list.
                                    </div>
                                </div>
                            </div>

                            <!-- Table Configuration -->
                            <div v-if="newField.field_type === 'table'" class="mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-3">
                                        <h6 class="field-label border-bottom pb-2 mb-3">Table Configuration</h6>
                                        
                                        <!-- Columns -->
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold">Columns</label>
                                            <div v-for="(col, ci) in newField.table_config.columns" :key="ci" class="input-group input-group-sm mb-2">
                                                <input v-model="col.label" type="text" class="form-control" placeholder="Column Label (e.g. Year)" />
                                                <select v-model="col.type" class="form-select" style="max-width: 100px;">
                                                    <option value="text">Text</option>
                                                    <option value="number">Number</option>
                                                    <option value="date">Date</option>
                                                    <option value="email">Email</option>
                                                    <option value="tel">Phone</option>
                                                    <option value="file">File Upload</option>
                                                    <option value="select">Dropdown</option>
                                                </select>
                                                <button @click="removeColumn(ci)" class="btn btn-outline-danger" type="button" :disabled="newField.table_config.columns.length === 1">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <!-- If select, show options input below or as part of group -->
                                                <div v-if="col.type === 'select'" class="w-100 mt-1">
                                                    <input 
                                                        v-model="col.select_options" 
                                                        type="text" 
                                                        class="form-control form-control-xs" 
                                                        placeholder="Options (comma separated: Male, Female, Other)" 
                                                        @blur="col.options = col.select_options.split(',').map(s => s.trim()).filter(s => s)"
                                                    />
                                                </div>
                                            </div>
                                            <button @click="addColumn" class="btn btn-xs btn-outline-primary mt-1" type="button">
                                                <i class="bi bi-plus-lg me-1"></i>Add Column
                                            </button>
                                        </div>

                                        <!-- Row behavior -->
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold d-block">Row Behavior</label>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" :value="true" v-model="newField.table_config.is_dynamic" id="rowDyn" />
                                                <label class="form-check-label small" for="rowDyn">Dynamic (+ Add Row)</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" :value="false" v-model="newField.table_config.is_dynamic" id="rowFixed" />
                                                <label class="form-check-label small" for="rowFixed">Fixed Rows</label>
                                            </div>
                                        </div>

                                        <!-- Fixed row labels -->
                                        <div v-if="!newField.table_config.is_dynamic" class="mb-2">
                                            <label class="form-label small fw-bold">Fixed Row Labels (1st Column)</label>
                                            <div class="d-flex gap-2 mb-2">
                                                <input v-model="newField._fixedRowInput" type="text" class="form-control form-control-sm"
                                                    placeholder="e.g. Matriculation" @keyup.enter="addFixedRow" />
                                                <button @click="addFixedRow" class="btn btn-sm btn-outline-primary" type="button">Add</button>
                                            </div>
                                            <div class="option-pills">
                                                <span v-for="(row, ri) in newField.table_config.fixed_rows" :key="ri" class="option-pill bg-secondary">
                                                    {{ row }}
                                                    <button @click="removeFixedRow(ri)" class="btn-remove-opt"><i class="bi bi-x"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Required toggle -->
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" v-model="newField.is_required" id="reqCheck" />
                                <label class="form-check-label field-label" for="reqCheck">Required field</label>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <button @click="closePanel" class="btn btn-outline-secondary me-2">Cancel</button>
                            <button @click="saveField" class="btn btn-primary-red">
                                <i class="bi bi-check-circle me-1"></i>{{ editingFieldIndex !== null ? 'Update' : 'Add' }} Field
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Placeholder when panel is closed -->
                <div class="builder-right-empty" v-else>
                    <div class="empty-panel-hint">
                        <i class="bi bi-plus-square display-2 text-muted d-block mb-3"></i>
                        <p class="text-muted">Click <strong>Add Field</strong> on the left to start adding fields to the current step.</p>
                    </div>
                </div>
            </div>

            <!-- Navigation for step 2 -->
            <div v-if="wizardStep === 2" class="d-flex justify-content-between mt-3">
                <button @click="wizardStep = 1" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </button>
                <div>
                    <span v-if="errors.fields" class="text-danger small me-3">{{ errors.fields }}</span>
                    <button @click="goToStep(3)" class="btn btn-primary-red">
                        Review Template <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

            <!-- ═══════════════════════════════════════
                 STEP 3 – REVIEW & SAVE
            ═══════════════════════════════════════ -->
            <div v-if="wizardStep === 3" class="builder-card">
                <div class="builder-card-header">
                    <i class="bi bi-check2-all me-2"></i>Review & Save Template
                </div>
                <div class="builder-card-body">

                    <!-- Meta summary -->
                    <div class="review-section mb-4">
                        <h6 class="review-section-title">Template Info</h6>
                        <div class="review-grid">
                            <div class="review-item">
                                <span class="review-key">Name</span>
                                <span class="review-val">{{ meta.name }}</span>
                            </div>

                            <div class="review-item" v-if="meta.description">
                                <span class="review-key">Description</span>
                                <span class="review-val">{{ meta.description }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Steps + fields preview -->
                    <div v-for="step in formSteps" :key="step.id" class="review-step mb-4">
                        <h6 class="review-step-title">
                            <i class="bi bi-layers me-2"></i>{{ step.title }}
                            <span class="step-field-count ms-2">{{ meta.fields.filter(f => f.step === step.id).length }} fields</span>
                        </h6>

                        <div class="preview-form-area">
                            <div v-if="meta.fields.filter(f => f.step === step.id).length === 0" class="text-muted small">
                                No fields in this step.
                            </div>

                            <div v-for="(field, fi) in meta.fields.filter(f => f.step === step.id)" :key="fi" class="preview-field">
                                <label class="preview-label">
                                    {{ field.label }}
                                    <span v-if="field.is_required" class="req-mark">*</span>
                                    <span class="preview-type-badge ms-2">{{ fieldTypeLabel(field.field_type) }}</span>
                                </label>

                                <!-- Subject picker preview -->
                                <select v-if="field.field_type === 'subject'" class="form-select form-select-sm" disabled>
                                    <option>— Select Subject —</option>
                                    <option v-for="sub in subjects" :key="sub.id">{{ sub.name }} ({{ sub.code }})</option>
                                </select>

                                <!-- Select preview -->
                                <select v-else-if="field.field_type === 'select'" class="form-select form-select-sm" disabled>
                                    <option>— {{ field.placeholder || 'Select…' }} —</option>
                                    <option v-for="opt in field.options" :key="opt">{{ opt }}</option>
                                </select>

                                <!-- Textarea preview -->
                                <textarea v-else-if="field.field_type === 'textarea'" class="form-control form-control-sm"
                                    rows="2" :placeholder="field.placeholder" disabled></textarea>

                                <!-- File preview -->
                                <input v-else-if="field.field_type === 'file'" type="file" class="form-control form-control-sm" disabled />

                                <!-- Checkbox preview -->
                                <div v-else-if="field.field_type === 'checkbox'" class="form-check">
                                    <input class="form-check-input" type="checkbox" disabled />
                                    <label class="form-check-label text-muted small">{{ field.label }}</label>
                                </div>

                                <!-- Radio preview -->
                                <div v-else-if="field.field_type === 'radio'" class="d-flex flex-wrap gap-3">
                                    <div v-for="opt in field.options" :key="opt" class="form-check">
                                        <input class="form-check-input" type="radio" disabled />
                                        <label class="form-check-label small">{{ opt }}</label>
                                    </div>
                                </div>

                                <!-- Table preview -->
                                <div v-else-if="field.field_type === 'table'" class="table-responsive">
                                    <table class="table table-bordered table-sm mb-0 small">
                                        <thead class="table-light">
                                            <tr>
                                                <th v-for="col in JSON.parse(field.options).columns" :key="col.label">{{ col.label }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="JSON.parse(field.options).is_dynamic">
                                                <td :colspan="JSON.parse(field.options).columns.length" class="text-center text-muted py-2">
                                                    Applicant can add multiple rows
                                                </td>
                                            </tr>
                                            <tr v-else v-for="row in JSON.parse(field.options).fixed_rows" :key="row">
                                                <td><strong>{{ row }}</strong></td>
                                                <td v-for="i in JSON.parse(field.options).columns.length - 1" :key="i"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Default input preview -->
                                <input v-else :type="field.field_type" class="form-control form-control-sm"
                                    :placeholder="field.placeholder" disabled />
                            </div>
                        </div>
                    </div>

                    <div v-if="meta.hasErrors" class="alert alert-danger small">{{ meta.errors }}</div>
                </div>

                <div class="builder-card-footer d-flex justify-content-between">
                    <button @click="wizardStep = 2" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Builder
                    </button>
                    <button @click="submit" class="btn btn-primary-red" :disabled="meta.processing">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ meta.processing ? 'Saving…' : 'Save Template' }}
                    </button>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
.builder-page { padding-bottom: 2rem; }

/* ── Wizard Progress ── */
.wizard-progress { background: #fff; border-radius: 12px; padding: 20px 32px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.wp-track { display: flex; align-items: center; }
.wp-step { display: flex; flex-direction: column; align-items: center; cursor: default; min-width: 80px; }
.wp-circle {
    width: 44px; height: 44px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    background: #f0f0f0; color: #aaa;
    border: 2px solid #e0e0e0;
    transition: all 0.3s;
}
.wp-step.active .wp-circle { background: #8b0000; color: #fff; border-color: #8b0000; }
.wp-step.done .wp-circle { background: #27ae60; color: #fff; border-color: #27ae60; }
.wp-label { font-size: 0.72rem; font-weight: 600; color: #999; margin-top: 6px; text-transform: uppercase; letter-spacing: 0.4px; }
.wp-step.active .wp-label { color: #8b0000; }
.wp-step.done .wp-label { color: #27ae60; }
.wp-line { flex: 1; height: 3px; background: #e0e0e0; border-radius: 2px; transition: background 0.3s; }
.wp-line.filled { background: #27ae60; }

/* ── Cards ── */
.builder-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.07); overflow: hidden; }
.builder-card-header {
    background: linear-gradient(135deg, #8b0000, #b71c1c);
    color: #fff; padding: 16px 24px; font-weight: 700; font-size: 0.95rem;
}
.builder-card-body { padding: 24px; }
.builder-card-footer { padding: 16px 24px; background: #fafafa; border-top: 1px solid #f0f0f0; }

/* ── Builder Layout ── */
.builder-layout { display: grid; grid-template-columns: 1fr 400px; gap: 20px; align-items: start; }
@media (max-width: 992px) { .builder-layout { grid-template-columns: 1fr; } }

/* ── Step Tabs ── */
.step-tabs { border-bottom: 1px solid #f0f0f0; }
.step-tab {
    padding: 12px 20px; cursor: pointer; border-left: 3px solid transparent;
    font-size: 0.875rem; font-weight: 500; color: #555;
    transition: all 0.15s; background: #fafafa;
}
.step-tab:hover { background: #f5f5f5; color: #8b0000; }
.step-tab-active { background: #fff8f8 !important; border-left-color: #8b0000 !important; color: #8b0000 !important; font-weight: 700; }
.step-field-count { background: #e0e0e0; color: #666; font-size: 0.68rem; padding: 1px 7px; border-radius: 100px; font-weight: 600; }
.step-tab-active .step-field-count { background: rgba(139,0,0,0.12); color: #8b0000; }

/* ── Fields List ── */
.fields-list { min-height: 200px; padding: 12px; }
.empty-fields-hint { text-align: center; padding: 40px 20px; color: #aaa; font-size: 0.875rem; }

.field-item {
    display: flex; align-items: center; justify-content: space-between;
    background: #fafafa; border: 1px solid #eee; border-radius: 8px;
    padding: 10px 12px; margin-bottom: 8px;
    transition: border-color 0.15s;
}
.field-item:hover { border-color: #c0392b; }
.field-item-left { display: flex; align-items: center; gap: 10px; }
.field-type-badge {
    width: 34px; height: 34px; background: #8b0000; color: #fff;
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.field-label-text { font-size: 0.875rem; font-weight: 600; color: #333; }
.field-type-text { font-size: 0.72rem; color: #888; }
.field-item-actions { display: flex; gap: 2px; }
.btn-icon { width: 28px; height: 28px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; border-radius: 6px; border: 1px solid #e0e0e0; background: #fff; color: #555; }
.btn-icon:hover { background: #f0f0f0; }
.btn-icon:disabled { opacity: 0.35; cursor: not-allowed; }

.req-mark { color: #e74c3c; font-weight: 700; margin-left: 2px; }

.subject-pill { background: #e8f4fd; color: #1565c0; font-size: 0.68rem; padding: 1px 7px; border-radius: 100px; font-weight: 600; }

/* ── Add Field Button ── */
.btn-add-field {
    background: linear-gradient(135deg, #8b0000, #c0392b);
    color: #fff; border: none; border-radius: 8px;
    padding: 10px; font-weight: 600; font-size: 0.875rem;
    transition: all 0.2s;
}
.btn-add-field:hover { background: linear-gradient(135deg, #6b0000, #a93226); color: #fff; }

/* ── Panel ── */
.panel-card { background: #fff; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; }
.panel-header { background: linear-gradient(135deg, #1a1a2e, #2d2d44); color: #fff; padding: 16px 20px; font-weight: 700; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center; }
.panel-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
.panel-footer { padding: 14px 20px; background: #f9f9f9; border-top: 1px solid #eee; text-align: right; }

/* Field Type Grid */
.field-type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.ft-chip {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 10px 6px; border: 2px solid #e0e0e0; border-radius: 10px;
    cursor: pointer; font-size: 0.72rem; font-weight: 600; color: #555; text-align: center;
    transition: all 0.15s; background: #fafafa;
    gap: 4px;
}
.ft-chip i { font-size: 1.2rem; }
.ft-chip:hover { border-color: #8b0000; color: #8b0000; background: #fff8f8; }
.ft-chip-active { border-color: #8b0000 !important; background: #8b0000 !important; color: #fff !important; }
.ft-chip-subject { border-color: #1565c0; color: #1565c0; background: #e8f4fd; }
.ft-chip-subject.ft-chip-active { background: #1565c0 !important; color: #fff !important; border-color: #1565c0 !important; }
.ft-chip-entity { border-color: #2e7d32; color: #2e7d32; background: #e8f5e9; }
.ft-chip-entity.ft-chip-active { background: #2e7d32 !important; color: #fff !important; border-color: #2e7d32 !important; }

.subject-info-box { background: #e8f4fd; border: 1px solid #90caf9; border-radius: 8px; padding: 10px 14px; font-size: 0.8rem; color: #1565c0; }

/* Options pills */
.option-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.option-pill {
    background: #8b0000; color: #fff; font-size: 0.75rem;
    padding: 4px 12px; border-radius: 100px; display: flex; align-items: center; gap: 4px;
}
.btn-remove-opt { background: transparent; border: none; color: rgba(255,255,255,0.7); cursor: pointer; padding: 0; line-height: 1; font-size: 0.8rem; }
.btn-remove-opt:hover { color: #fff; }

/* Builder right empty state */
.builder-right-empty { background: #fff; border-radius: 14px; border: 2px dashed #e0e0e0; min-height: 300px; display: flex; align-items: center; justify-content: center; }
.empty-panel-hint { text-align: center; padding: 40px; }

/* ── Form labels ── */
.field-label { font-weight: 600; font-size: 0.82rem; color: #444; margin-bottom: 5px; }
.required-star::after { content: ' *'; color: #e74c3c; }

/* ── Primary Red Button ── */
.btn-primary-red {
    background: linear-gradient(135deg, #8b0000, #c0392b);
    color: #fff; border: none; border-radius: 8px;
    padding: 9px 22px; font-weight: 600; font-size: 0.875rem;
    transition: all 0.2s;
}
.btn-primary-red:hover:not(:disabled) { background: linear-gradient(135deg, #6b0000, #a93226); color: #fff; transform: translateY(-1px); }
.btn-primary-red:disabled { opacity: 0.6; }

/* ── Review ── */
.review-section { background: #fafafa; border-radius: 10px; padding: 18px; border: 1px solid #f0f0f0; }
.review-section-title { font-weight: 700; font-size: 0.875rem; color: #8b0000; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
.review-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.review-item { display: flex; flex-direction: column; }
.review-key { font-size: 0.72rem; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: 0.5px; }
.review-val { font-size: 0.875rem; color: #333; font-weight: 500; }

.review-step { background: #fafcff; border: 1px solid #e0ecff; border-radius: 10px; overflow: hidden; }
.review-step-title { background: #eef4ff; padding: 12px 18px; font-size: 0.875rem; font-weight: 700; color: #1565c0; margin: 0; border-bottom: 1px solid #dce9ff; }

.preview-form-area { padding: 18px; display: flex; flex-direction: column; gap: 14px; }
.preview-field {}
.preview-label { display: block; font-size: 0.8rem; font-weight: 600; color: #333; margin-bottom: 5px; }
.preview-type-badge { background: #e0e0e0; color: #666; font-size: 0.65rem; padding: 2px 8px; border-radius: 100px; font-weight: 600; }

.btn-xs { font-size: 0.72rem; padding: 4px 10px; }
</style>
