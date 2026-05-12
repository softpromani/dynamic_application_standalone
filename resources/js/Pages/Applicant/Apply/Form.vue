<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    opening: Object,
    template: Object, // Could be null if no template exists for this program
    existingDraft: Object,
    existingResponses: Object,
    applicationTypes: Array,
    customEntityData: Object,
});

const page = usePage();
const user = computed(() => page.props.auth?.applicant || page.props.auth?.user);
const uploadingFiles = ref(false);

const getHtmlFieldType = (type) => {
    if (!type) return 'text';
    if (type.startsWith('system_')) {
        const base = type.replace('system_', '');
        if (base === 'email') return 'email';
        if (base === 'phone') return 'tel';
        if (base === 'dob') return 'date';
        if (base === 'photo' || base === 'signature') return 'file';
        if (base.includes('address')) return 'textarea';
        return 'text';
    }
    return type;
};

// Group fields by step
const steps = computed(() => {
    if (!props.template || !props.template.fields) return {};
    return props.template.fields.reduce((acc, field) => {
        if (!acc[field.step]) acc[field.step] = [];
        acc[field.step].push(field);
        return acc;
    }, {});
});

const stepNumbers = computed(() => Object.keys(steps.value).map(Number).sort((a, b) => a - b));
const totalSteps = computed(() => stepNumbers.value.length);
const currentStep = ref(
    props.existingDraft?.current_step && stepNumbers.value.includes(Number(props.existingDraft.current_step)) 
    ? Number(props.existingDraft.current_step) 
    : (stepNumbers.value.length > 0 ? stepNumbers.value[0] : 1)
);

const stepFields = computed(() => steps.value[currentStep.value] || []);

const getSafeOptions = (field) => {
    if (!field.options) return { columns: [], is_dynamic: false };
    if (typeof field.options === 'string') {
        try {
            return JSON.parse(field.options);
        } catch (e) {
            console.error('Invalid JSON in field options for field', field.id);
            return { columns: [], is_dynamic: false };
        }
    }
    return field.options;
};

const getFieldOptions = (field) => {
    if (field.custom_entity_id && props.customEntityData && props.customEntityData[field.custom_entity_id]) {
        return props.customEntityData[field.custom_entity_id].values.map(v => v.value);
    }
    return Array.isArray(field.options) ? field.options : [];
};

// Form setup (Inertia setup for multiple steps)
// We create a structure like `responses: { field_id: value }`
const initialResponses = {};
if (props.template && props.template.fields) {
    props.template.fields.forEach(field => {
        let val = null;
        
        if (props.existingResponses && props.existingResponses[field.id] !== undefined) {
             val = props.existingResponses[field.id];
             if ((field.field_type === 'checkbox' || field.field_type === 'table') && typeof val === 'string') {
                 try { 
                    val = JSON.parse(val); 
                 } catch (e) {
                     console.warn('Failed to parse response for field', field.id, e);
                 }
             }
        }

        // If it's a table, ensure it's an array of objects
        if (field.field_type === 'table') {
            const config = getSafeOptions(field);
            
            if (!Array.isArray(val) || val.length === 0) {
                if (!config.is_dynamic && config.fixed_rows?.length > 0) {
                    // Fixed rows: pre-fill first column
                    val = config.fixed_rows.map(rowLabel => {
                        const row = {};
                        config.columns.forEach((col, idx) => {
                            row[col.label] = idx === 0 ? rowLabel : '';
                        });
                        return row;
                    });
                } else {
                    // Dynamic or empty fixed: one blank row
                    const row = {};
                    (config.columns || []).forEach(col => row[col.label] = '');
                    val = [row];
                }
            } else {
                // If it IS an array, ensure each row has all columns defined to avoid undefined errors in v-model
                val = val.map(row => {
                    const newRow = { ...row };
                    (config.columns || []).forEach(col => {
                        if (newRow[col.label] === undefined) newRow[col.label] = '';
                    });
                    return newRow;
                });
            }
            initialResponses[field.id] = val;
        } else if (val !== null) {
            initialResponses[field.id] = val;
        } else if (field.is_subject_field) {
            initialResponses[field.id] = props.opening?.subject_id;
        } 
        // --- System Pre-fills ---
        // --- System Pre-fills ---
        else if (field.field_type === 'system_name')           initialResponses[field.id] = user.value?.name;
        else if (field.field_type === 'system_email')          initialResponses[field.id] = user.value?.email;
        else if (field.field_type === 'system_phone')          initialResponses[field.id] = user.value?.phone;
        else if (field.field_type === 'system_dob')            initialResponses[field.id] = user.value?.dob?.slice(0, 10);
        else if (field.field_type === 'system_gender')         initialResponses[field.id] = user.value?.gender;
        else if (field.field_type === 'system_category')       initialResponses[field.id] = user.value?.category;
        else if (field.field_type === 'system_father_name')    initialResponses[field.id] = user.value?.father_name;
        else if (field.field_type === 'system_mother_name')    initialResponses[field.id] = user.value?.mother_name;
        else if (field.field_type === 'system_marital_status') initialResponses[field.id] = user.value?.marital_status;
        else if (field.field_type === 'system_address_perm')   initialResponses[field.id] = user.value?.permanent_address;
        else if (field.field_type === 'system_address_corr')   initialResponses[field.id] = user.value?.correspondence_address;
        else if (field.field_type === 'system_photo')          initialResponses[field.id] = user.value?.profile_photo_path;
        else if (field.field_type === 'system_signature')      initialResponses[field.id] = user.value?.signature_path;

        // --- Dynamic Profile Pre-fills (from profile_data) ---
        else if (field.system_alias && user.value?.profile_data && user.value.profile_data[field.system_alias] !== undefined) {
            initialResponses[field.id] = user.value.profile_data[field.system_alias];
        }

        else if (field.field_type === 'checkbox') {
            initialResponses[field.id] = Array.isArray(val) ? val : [];
        } else if (field.field_type === 'file') {
            initialResponses[field.id] = null;
        } else {
            initialResponses[field.id] = '';
        }
    });
}

const form = useForm({
    program_application_type_id: props.existingDraft?.program_application_type_id || '',
    responses: initialResponses,
});

const selectType = (type) => {
    form.program_application_type_id = type.id;
};

const confirmTypeSelection = () => {
    if (!form.program_application_type_id) return;
    // We don't need to do an extra call here, the nextStep/saveStep logic in nextStep will handle it
    // because it sends the whole form state.
    // However, we need to make sure the UI shows the form now.
};

const formatDate = (d) =>
    d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const nextStep = () => {
    const currentIndex = stepNumbers.value.indexOf(currentStep.value);
    if (currentIndex < totalSteps.value - 1) {
        // Validate current step fields before proceeding
        let isValid = true;
        stepFields.value.forEach(field => {
            const val = form.responses[field.id];
            let isMissing = false;
            let partialRowError = false;
            
            if (field.field_type === 'checkbox') {
                 isMissing = (Array.isArray(val) && val.length === 0) || val === false || val === null || val === '';
            } else if (field.field_type === 'table') {
                 isMissing = true;
                 if (Array.isArray(val) && val.length > 0) {
                     const config = getSafeOptions(field);
                     let hasFullyFilledRow = false;
                     val.forEach(row => {
                         let filledCols = 0;
                         let totalCols = 0;
                         config.columns.forEach((col, cIdx) => {
                             if (!config.is_dynamic && cIdx === 0) return;
                             totalCols++;
                             if (row[col.label] !== null && row[col.label] !== undefined && String(row[col.label]).trim() !== '') {
                                 filledCols++;
                             }
                         });
                         if (filledCols === totalCols && totalCols > 0) {
                             hasFullyFilledRow = true;
                         } else if (filledCols > 0 && filledCols < totalCols) {
                             partialRowError = true;
                         }
                     });
                     if (hasFullyFilledRow) isMissing = false;
                 }
            } else if (field.field_type !== 'file') {
                 isMissing = !val;
            } else if (field.field_type === 'file') {
                 isMissing = !val; 
            }

            if (partialRowError) {
                isValid = false;
                form.setError(`responses.${field.id}`, 'Please complete all columns for the rows you have started.');
            } else if (field.is_required && isMissing) {
                isValid = false;
                form.setError(`responses.${field.id}`, 'This field is required. Please add at least one complete entry.');
            } else {
                form.clearErrors(`responses.${field.id}`);
            }
        });

        if (isValid) {
            const nextStepVal = stepNumbers.value[currentIndex + 1];
            
            // Build FormData to properly handle file uploads via axios
            const formData = new FormData();
            formData.append('current_step', nextStepVal);
            formData.append('program_application_type_id', form.program_application_type_id);
            
            const appendFormData = (data, root = 'responses') => {
                for (const k in data) {
                    const value = data[k];
                    if (value instanceof File) {
                        formData.append(`${root}[${k}]`, value);
                    } else if (Array.isArray(value)) {
                        value.forEach((v, i) => {
                            if (v instanceof Object && !(v instanceof File)) {
                                appendFormData(v, `${root}[${k}][${i}]`);
                            } else {
                                formData.append(`${root}[${k}][]`, v);
                            }
                        });
                    } else if (value instanceof Object && value !== null) {
                        appendFormData(value, `${root}[${k}]`);
                    } else if (value !== null && value !== undefined) {
                        formData.append(`${root}[${k}]`, value);
                    }
                }
            };

            appendFormData(form.responses);

            // Save state to server
            axios.post(route('applicant.apply.saveStep', props.opening.id), formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            }).then(() => {
                currentStep.value = nextStepVal;
            }).catch(e => {
                console.error('Error saving step:', e);
            });
        }
    }
};

const prevStep = () => {
    const currentIndex = stepNumbers.value.indexOf(currentStep.value);
    if (currentIndex > 0) {
        currentStep.value = stepNumbers.value[currentIndex - 1];
    }
};

const goToPreview = () => {
    // Basic frontend validation for the final step
    if (props.template && props.template.fields) {
        let isValid = true;
        stepFields.value.forEach(field => {
            const val = form.responses[field.id];
            let isMissing = false;
            let partialRowError = false;
            
            if (field.field_type === 'checkbox') {
                 isMissing = (Array.isArray(val) && val.length === 0) || val === false || val === null || val === '';
            } else if (field.field_type === 'table') {
                 isMissing = true;
                 if (Array.isArray(val) && val.length > 0) {
                     const config = getSafeOptions(field);
                     let hasFullyFilledRow = false;
                     val.forEach(row => {
                         let filledCols = 0;
                         let totalCols = 0;
                         config.columns.forEach((col, cIdx) => {
                             if (!config.is_dynamic && cIdx === 0) return;
                             totalCols++;
                             if (row[col.label] !== null && row[col.label] !== undefined && String(row[col.label]).trim() !== '') {
                                 filledCols++;
                             }
                         });
                         if (filledCols === totalCols && totalCols > 0) {
                             hasFullyFilledRow = true;
                         } else if (filledCols > 0 && filledCols < totalCols) {
                             partialRowError = true;
                         }
                     });
                     if (hasFullyFilledRow) isMissing = false;
                 }
            } else if (field.field_type !== 'file') { 
                 isMissing = !val;
            }

            if (partialRowError) {
                isValid = false;
                form.setError(`responses.${field.id}`, 'Please complete all columns for the rows you have started.');
            } else if (field.is_required && isMissing) {
                isValid = false;
                form.setError(`responses.${field.id}`, 'This field is required. Please add at least one complete entry.');
            } else {
                form.clearErrors(`responses.${field.id}`);
            }
        });
        if (!isValid) return;
    }

    // Build FormData
    const formData = new FormData();
    formData.append('current_step', currentStep.value);
    formData.append('program_application_type_id', form.program_application_type_id);
    
    const appendFormData = (data, root = 'responses') => {
        for (const k in data) {
            const value = data[k];
            if (value instanceof File) {
                formData.append(`${root}[${k}]`, value);
            } else if (Array.isArray(value)) {
                value.forEach((v, i) => {
                    if (v instanceof Object && !(v instanceof File)) {
                        appendFormData(v, `${root}[${k}][${i}]`);
                    } else {
                        formData.append(`${root}[${k}][]`, v);
                    }
                });
            } else if (value instanceof Object && value !== null) {
                appendFormData(value, `${root}[${k}]`);
            } else if (value !== null && value !== undefined) {
                formData.append(`${root}[${k}]`, value);
            }
        }
    };

    appendFormData(form.responses);

    axios.post(route('applicant.apply.saveStep', props.opening.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    }).then(() => {
        window.location.href = route('applicant.apply.preview', props.opening.id);
    }).catch(e => {
        console.error('Error saving step before preview:', e);
    });
};

const submitForm = () => {
    // Basic frontend validation for the final step
    if (props.template && props.template.fields) {
        let isValid = true;
        stepFields.value.forEach(field => {
            const val = form.responses[field.id];
            let isMissing = false;
            let partialRowError = false;
            
            if (field.field_type === 'checkbox') {
                 isMissing = (Array.isArray(val) && val.length === 0) || val === false || val === null || val === '';
            } else if (field.field_type === 'table') {
                 isMissing = true;
                 if (Array.isArray(val) && val.length > 0) {
                     const config = getSafeOptions(field);
                     let hasFullyFilledRow = false;
                     val.forEach(row => {
                         let filledCols = 0;
                         let totalCols = 0;
                         config.columns.forEach((col, cIdx) => {
                             if (!config.is_dynamic && cIdx === 0) return;
                             totalCols++;
                             if (row[col.label] !== null && row[col.label] !== undefined && String(row[col.label]).trim() !== '') {
                                 filledCols++;
                             }
                         });
                         if (filledCols === totalCols && totalCols > 0) {
                             hasFullyFilledRow = true;
                         } else if (filledCols > 0 && filledCols < totalCols) {
                             partialRowError = true;
                         }
                     });
                     if (hasFullyFilledRow) isMissing = false;
                 }
            } else if (field.field_type !== 'file') { 
                 isMissing = !val;
            }

            if (partialRowError) {
                isValid = false;
                 form.setError(`responses.${field.id}`, 'Please complete all columns for the rows you have started.');
            } else if (field.is_required && isMissing) {
                isValid = false;
                 form.setError(`responses.${field.id}`, 'This field is required. Please add at least one complete entry.');
            } else {
                 form.clearErrors(`responses.${field.id}`);
            }
        });
        if (!isValid) return;
    }

    goToPreview();
};

const handleFileUpload = async (e, fieldId) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > (2 * 1024 * 1024)) {
            alert('File is too large! Maximum allowed size is 2MB.');
            e.target.value = '';
            form.responses[fieldId] = null;
            return;
        }
        
        uploadingFiles.value = true;
        const formData = new FormData();
        formData.append('file', file);
        
        try {
            const response = await axios.post(route('applicant.apply.upload-temp'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            form.responses[fieldId] = response.data.path;
        } catch (error) {
            console.error('File upload failed', error);
            alert('Failed to upload file. Please ensure it is a valid format and less than 2MB.');
            e.target.value = '';
            form.responses[fieldId] = null;
        } finally {
            uploadingFiles.value = false;
        }
    }
};

const addTableRow = (field) => {
    const config = getSafeOptions(field);
    const row = {};
    config.columns.forEach(col => row[col.label] = '');
    form.responses[field.id].push(row);
};

const removeTableRow = (field, index) => {
    if (form.responses[field.id].length > 1) {
        form.responses[field.id].splice(index, 1);
    }
};

const handleTableFileUpload = async (e, fieldId, rowIdx, colLabel) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > (2 * 1024 * 1024)) {
            alert('File is too large! Maximum allowed size is 2MB.');
            e.target.value = '';
            form.responses[fieldId][rowIdx][colLabel] = null;
            return;
        }
        
        uploadingFiles.value = true;
        const formData = new FormData();
        formData.append('file', file);
        
        try {
            const response = await axios.post(route('applicant.apply.upload-temp'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            form.responses[fieldId][rowIdx][colLabel] = response.data.path;
        } catch (error) {
            console.error('File upload failed', error);
            alert('Failed to upload file. Please ensure it is a valid format and less than 2MB.');
            e.target.value = '';
            form.responses[fieldId][rowIdx][colLabel] = null;
        } finally {
            uploadingFiles.value = false;
        }
    }
};

const isFile = (val) => val instanceof File;

</script>

<template>
    <Head title="Program Application" />

    <AdminLayout>
        <template #header>
            Applying for {{ opening.job.title }} <span v-if="opening.subject">({{ opening.subject.name }})</span>
        </template>

        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-8">

                <!-- Selection of Application Type (Step 0) -->
                <div v-if="!form.program_application_type_id && applicationTypes && applicationTypes.length > 0" class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white p-4 text-center border-bottom">
                        <i class="bi bi-person-badge text-primary display-5 mb-2 d-block"></i>
                        <h5 class="fw-bold mb-1 text-dark">Select Application Category</h5>
                        <p class="text-muted small mb-0">Fees and late fines are determined by your category selection.</p>
                    </div>
                    <div class="card-body p-4 bg-light-subtle">
                        <div class="row g-3">
                            <div v-for="type in applicationTypes" :key="type.id" class="col-12">
                                <div class="p-3 border rounded-3 bg-white cursor-pointer hover-shadow-sm transition-all" 
                                    :class="{'border-primary ring-1 shadow-sm': form.program_application_type_id === type.id}"
                                    @click="selectType(type)"
                                    style="border-width: 2px !important;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="fw-bold mb-1 text-dark">{{ type.name }}</h6>
                                            <div class="d-flex gap-3 align-items-center">
                                                <span class="text-muted small">
                                                    <i class="bi bi-cash-stack me-1 text-success"></i>Fee: ₹{{ type.fee }}
                                                </span>
                                                <span v-if="type.fine_amount > 0" class="text-danger small fw-semibold">
                                                    <i class="bi bi-exclamation-circle me-1"></i>Late Fine: ₹{{ type.fine_amount }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ms-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                                style="width: 24px; height: 24px; border: 2px solid"
                                                :class="form.program_application_type_id === type.id ? 'border-primary bg-primary' : 'border-secondary-subtle'">
                                                <i v-if="form.program_application_type_id === type.id" class="bi bi-check text-white fw-bold"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-4 border-top text-end">
                        <Link :href="route('applicant.browse-programs')" class="btn btn-link text-muted text-decoration-none me-3">Cancel</Link>
                        <button class="btn btn-primary px-5 rounded-pill shadow-sm fw-bold" 
                            :disabled="!form.program_application_type_id" 
                            @click="confirmTypeSelection">
                            Start Application <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                 <div v-else-if="!template" class="card shadow-sm border-0">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-info-circle-fill display-4 text-primary d-block mb-3"></i>
                        <h5 class="mb-3">Confirm Application</h5>
                        <p class="text-muted">You are applying for <strong>{{ opening.job.title }}</strong> <span v-if="opening.subject">in <strong>{{ opening.subject.name }}</strong></span>.</p>
                        <hr class="my-4"/>
                        <p class="small text-muted mb-4">Clicking submit will finalize your submission process. No extra form is required for this program application type.</p>
                        <form @submit.prevent="submitForm">
                            <Link :href="route('applicant.browse-programs')" class="btn btn-outline-secondary px-4 me-2">Cancel</Link>
                            <button type="submit" class="btn btn-primary px-4" :disabled="form.processing">
                                Confirm & Apply
                            </button>
                        </form>
                    </div>
                 </div>

                 <div v-else-if="form.program_application_type_id" class="card shadow-sm border-0">
                    <!-- Progress Bar Header -->
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="mb-3 text-center fw-bold text-dark">{{ template.name }}</h5>
                        <p v-if="template.description" class="text-center text-muted small mb-4">{{ template.description }}</p>
                        
                        <!-- Modern Stepper -->
                        <div v-if="totalSteps > 1" class="stepper-wrapper mb-5 mt-2">
                            <div class="stepper-track">
                                <div class="stepper-progress" :style="`width: ${((stepNumbers.indexOf(currentStep)) / (totalSteps - 1)) * 100}%` "></div>
                            </div>
                            <div v-for="(sn, index) in stepNumbers" :key="sn" 
                                class="stepper-item" 
                                :class="{ 'completed': sn < currentStep, 'active': sn === currentStep }">
                                <div class="stepper-dot">
                                    <i v-if="sn < currentStep" class="bi bi-check-lg"></i>
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <div class="stepper-label">Step {{ index + 1 }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Body -->
                    <div class="card-body p-5 pt-4">
                        <form @submit.prevent="submitForm">
                            <div v-for="field in stepFields" :key="field.id" class="mb-4">
                                <label class="form-label fw-bold small text-dark">
                                    {{ field.label }}
                                    <span v-if="field.is_required" class="text-danger">*</span>
                                </label>

                                <!-- Field Types -->

                                <div v-if="field.field_type === 'select'">
                                    <select class="form-select" v-model="form.responses[field.id]" :class="{'is-invalid': form.errors[`responses.${field.id}`]}">
                                        <option value="" disabled>{{ field.placeholder || 'Select an option' }}</option>
                                        <option v-for="opt in getFieldOptions(field)" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>

                                <div v-else-if="getHtmlFieldType(field.field_type) === 'textarea'">
                                    <textarea class="form-control" rows="3" v-model="form.responses[field.id]"
                                        :placeholder="field.placeholder" :class="{'is-invalid': form.errors[`responses.${field.id}`]}"></textarea>
                                </div>

                                <div v-else-if="field.field_type === 'radio'">
                                    <div v-for="opt in getFieldOptions(field)" :key="opt" class="form-check me-3 mb-2">
                                        <input class="form-check-input" type="radio" :value="opt" v-model="form.responses[field.id]"
                                            :name="`field_${field.id}`" :class="{'is-invalid': form.errors[`responses.${field.id}`]}">
                                        <label class="form-check-label">{{ opt }}</label>
                                    </div>
                                </div>

                                <div v-else-if="field.field_type === 'checkbox'">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" v-model="form.responses[field.id]"
                                             :class="{'is-invalid': form.errors[`responses.${field.id}`]}">
                                        <label class="form-check-label text-muted">{{ field.placeholder || 'Yes, I acknowledge' }}</label>
                                    </div>
                                </div>

                                <div v-else-if="getHtmlFieldType(field.field_type) === 'file'">
                                    <!-- Show preview if it's a system file already present -->
                                    <div v-if="field.field_type.startsWith('system_') && typeof form.responses[field.id] === 'string' && form.responses[field.id]" class="mb-2">
                                        <img :src="`/storage/${form.responses[field.id]}`" class="img-thumbnail" style="max-height: 80px;" alt="Profile item" />
                                    </div>
                                    
                                    <input type="file" class="form-control" @change="(e) => handleFileUpload(e, field.id)"
                                        :class="{'is-invalid': form.errors[`responses.${field.id}`]}" />
                                    <div class="mt-1 d-flex justify-content-between align-items-center">
                                        <small class="text-danger fw-bold" style="font-size: 0.7rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Max Size: 2MB</small>
                                        <small class="text-muted" style="font-size: 0.7rem;">PDF, JPG, PNG only</small>
                                    </div>
                                    <div v-if="typeof form.responses[field.id] === 'string' && form.responses[field.id] !== ''" class="mt-1 d-flex align-items-center gap-2">
                                        <small class="text-success">
                                            <i class="bi bi-check-circle me-1"></i> {{ field.field_type.startsWith('system_') ? 'Pre-filled from profile.' : 'File already uploaded.' }}
                                        </small>
                                        <a :href="`/storage/${form.responses[field.id]}`" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size: 0.65rem;">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </div>
                                    <small v-else-if="isFile(form.responses[field.id])" class="text-primary mt-1 d-block">
                                        <i class="bi bi-file-earmark-check me-1"></i> Selected: {{ form.responses[field.id].name }}
                                    </small>
                                </div>

                                 <div v-else-if="field.field_type === 'table'">
                                     <div class="table-responsive border rounded bg-white">
                                         <table class="table table-bordered mb-0 align-middle">
                                             <thead class="table-light">
                                                 <tr class="small text-uppercase fw-bold">
                                                     <th v-for="col in getSafeOptions(field).columns" :key="col.label">
                                                         {{ col.label }}
                                                     </th>
                                                     <th v-if="getSafeOptions(field).is_dynamic" style="width: 50px;"></th>
                                                 </tr>
                                             </thead>
                                             <tbody>
                                                 <tr v-for="(row, rIdx) in form.responses[field.id]" :key="rIdx">
                                                     <td v-for="(col, cIdx) in getSafeOptions(field).columns" :key="col.label">
                                                         <input 
                                                             v-if="col.type === 'file'"
                                                             type="file" 
                                                             class="form-control form-control-sm"
                                                             @change="(e) => handleTableFileUpload(e, field.id, rIdx, col.label)"
                                                         />
                                                         <div v-if="col.type === 'file'" class="text-danger fw-bold mt-1" style="font-size: 0.6rem;">Max 2MB</div>
                                                         <select
                                                             v-else-if="col.type === 'select'"
                                                             v-model="row[col.label]"
                                                             class="form-select form-select-sm border-0 bg-transparent"
                                                         >
                                                             <option value="">Select...</option>
                                                             <option v-for="opt in col.options" :key="opt" :value="opt">{{ opt }}</option>
                                                         </select>
                                                         <input 
                                                             v-else
                                                             v-model="row[col.label]" 
                                                             :type="col.type" 
                                                             class="form-control form-control-sm border-0 bg-transparent"
                                                             :placeholder="col.label"
                                                             :disabled="!getSafeOptions(field).is_dynamic && cIdx === 0"
                                                         />
                                                         <!-- Show filename if already exists (string path) -->
                                                         <div v-if="col.type === 'file' && typeof row[col.label] === 'string' && row[col.label]" class="small text-success mt-1">
                                                             <span class="text-success"><i class="bi bi-file-earmark-check"></i> Uploaded</span><a :href="`/storage/${row[col.label]}`" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size: 0.6rem;"><i class="bi bi-eye"></i> View</a>
                                                         </div>
                                                         <div v-else-if="col.type === 'file' && isFile(row[col.label])" class="small text-primary mt-1">
                                                             <i class="bi bi-file-earmark-arrow-up"></i> {{ row[col.label].name }}
                                                         </div>
                                                     </td>
                                                     <td v-if="getSafeOptions(field).is_dynamic">
                                                         <button 
                                                             type="button" 
                                                             @click="removeTableRow(field, rIdx)" 
                                                             class="btn btn-link text-danger p-0" 
                                                             :disabled="form.responses[field.id].length === 1"
                                                         >
                                                             <i class="bi bi-dash-circle fs-5"></i>
                                                         </button>
                                                     </td>
                                                 </tr>
                                             </tbody>
                                         </table>
                                     </div>
                                     <div v-if="getSafeOptions(field).is_dynamic" class="mt-2 text-end">
                                         <button type="button" @click="addTableRow(field)" class="btn btn-sm btn-outline-success">
                                             <i class="bi bi-plus-lg me-1"></i>Add Row
                                         </button>
                                     </div>
                                 </div>

                                <div v-else>
                                    <input :type="getHtmlFieldType(field.field_type)" class="form-control" v-model="form.responses[field.id]"
                                        :placeholder="field.placeholder" :class="{'is-invalid': form.errors[`responses.${field.id}`]}" />
                                </div>

                                <div v-if="form.errors[`responses.${field.id}`]" class="invalid-feedback d-block">
                                    {{ form.errors[`responses.${field.id}`] }}
                                </div>
                            </div>
                        </form>

                        <!-- Footer Notes / Declaration -->
                        <div v-if="currentStep === stepNumbers[stepNumbers.length - 1] && opening.job?.footer_notes" class="mt-4 p-3 bg-light border rounded border-primary border-opacity-25">
                            <h6 class="fw-bold text-primary mb-2 small text-uppercase"><i class="bi bi-info-circle me-1"></i> Declaration & Important Notes</h6>
                            <div class="small text-muted" style="white-space: pre-wrap; line-height: 1.6;">
                                {{ opening.job.footer_notes }}
                            </div>
                        </div>
                    </div>

                    <!-- Footer Navigation -->
                    <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <button v-if="currentStep !== stepNumbers[0]" type="button" class="btn btn-outline-secondary px-4" @click="prevStep">
                                <i class="bi bi-arrow-left me-2"></i>Back
                            </button>
                            <Link v-else :href="route('applicant.browse-programs')" class="btn btn-link text-muted text-decoration-none">Cancel</Link>
                        </div>
                        
                        <div>
                            <button v-if="currentStep !== stepNumbers[stepNumbers.length - 1]" type="button" class="btn btn-primary px-4 shadow-sm" @click="nextStep" :disabled="uploadingFiles">
                                <span v-if="uploadingFiles" class="spinner-border spinner-border-sm me-2"></span>
                                {{ uploadingFiles ? 'Uploading files...' : 'Next Step' }} <i v-if="!uploadingFiles" class="bi bi-arrow-right ms-2"></i>
                            </button>
                            
                            <button v-else type="button" class="btn btn-primary px-4 shadow-sm" @click="goToPreview" :disabled="form.processing || uploadingFiles">
                                <span v-if="uploadingFiles" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-eye me-2"></i> {{ uploadingFiles ? 'Uploading files...' : (form.processing ? 'Saving...' : 'Preview Application') }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.stepper-wrapper {
    display: flex;
    justify-content: space-between;
    position: relative;
    padding: 0 10px;
}
.stepper-track {
    position: absolute;
    top: 18px;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: #e9ecef;
    z-index: 1;
}
.stepper-progress {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background-color: #28a745;
    transition: width 0.4s ease;
}
.stepper-item {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}
.stepper-dot {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #fff;
    border: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #6c757d;
    transition: all 0.3s ease;
    background-clip: padding-box;
}
.stepper-item.active .stepper-dot {
    border-color: #28a745;
    color: #28a745;
    box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.1);
}
.stepper-item.completed .stepper-dot {
    background-color: #28a745;
    border-color: #28a745;
    color: #fff;
}
.stepper-label {
    margin-top: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stepper-item.active .stepper-label {
    color: #28a745;
}

.cursor-pointer { cursor: pointer; }
.transition-all { transition: all 0.2s ease-in-out; }
.hover-shadow-sm:hover { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important; transform: translateY(-1px); }
.ring-1 { box-shadow: 0 0 0 1px #0d6efd25; }
</style>
