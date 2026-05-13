<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';
import TextInput from '@softpro-core/Components/TextInput.vue';
import ImageCropper from '@softpro-core/Components/UI/ImageCropper.vue';
import axios from 'axios';

const props = defineProps({
    applicant: Object,
    template: Object,
    existingData: Object,
    customEntityData: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.applicant);

// Setup form fields based on template
const initialResponses = {};
const photoFields = [];

if (props.template && props.template.fields) {
    props.template.fields.forEach(field => {
        let val = null;
        
        // If data exists in profile_data
        if (props.existingData && props.existingData[field.id] !== undefined) {
            val = props.existingData[field.id];
        } else if (field.system_alias && props.existingData && props.existingData[field.system_alias] !== undefined) {
            val = props.existingData[field.system_alias];
        }

        // Handle System aliases pre-fills if no profile_data
        if (val === null) {
            const alias = field.system_alias;
            if (alias === 'system_name')           val = user.value?.name;
            else if (alias === 'system_email')     val = user.value?.email;
            else if (alias === 'system_phone')     val = user.value?.phone;
            else if (alias === 'system_dob')       val = user.value?.dob?.slice(0, 10);
            else if (alias === 'system_gender')    val = user.value?.gender;
            else if (alias === 'system_category')  val = user.value?.category;
            else if (alias === 'system_father_name') val = user.value?.father_name;
            else if (alias === 'system_mother_name') val = user.value?.mother_name;
            else if (alias === 'system_marital_status') val = user.value?.marital_status;
            else if (alias === 'system_address_perm') val = user.value?.permanent_address;
            else if (alias === 'system_address_corr') val = user.value?.correspondence_address;
            else if (alias === 'system_id_proof_type') val = user.value?.id_proof_type;
            else if (alias === 'system_id_proof_number') val = user.value?.id_proof_number;
            else if (alias === 'system_photo')     val = user.value?.profile_photo_path;
            else if (alias === 'system_signature') val = user.value?.signature_path;
        }

        if (field.field_type === 'checkbox') {
            initialResponses[field.system_alias || field.id] = Array.isArray(val) ? val : [];
        } else {
            initialResponses[field.system_alias || field.id] = val || '';
        }

        if (field.field_type === 'file' || field.system_alias === 'system_photo' || field.system_alias === 'system_signature') {
            photoFields.push({
                key: field.system_alias || field.id,
                label: field.label,
                aspectRatio: field.system_alias === 'system_signature' ? 3/1 : 1,
            });
        }
    });
}

const form = useForm({
    responses: initialResponses,
});

const getFieldOptions = (field) => {
    if (field.custom_entity_id && props.customEntityData && props.customEntityData[field.custom_entity_id]) {
        return props.customEntityData[field.custom_entity_id].values.map(v => v.value);
    }
    if (typeof field.options === 'string') {
        try {
            return JSON.parse(field.options);
        } catch(e) { return []; }
    }
    return Array.isArray(field.options) ? field.options : [];
};

// Cropper logic
const activeCropperKey = ref(null);
const rawImage = ref(null);
const showCropper = ref(false);
const activeAspectRatio = ref(1);
const cropperTitle = ref('');

const onFileChange = (e, fieldKey, title, aspectRatio) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > (2 * 1024 * 1024)) {
            alert('File is too large! Maximum allowed size is 2MB.');
            e.target.value = ''; 
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            rawImage.value = event.target.result;
            activeCropperKey.value = fieldKey;
            cropperTitle.value = title;
            activeAspectRatio.value = aspectRatio;
            showCropper.value = true;
        };
        reader.readAsDataURL(file);
    }
};

const handleCrop = (data) => {
    if (activeCropperKey.value) {
        form.responses[activeCropperKey.value] = data;
    }
    showCropper.value = false;
};

const getImageUrl = (pathOrBase64) => {
    if (!pathOrBase64) return null;
    if (pathOrBase64.startsWith('data:image')) return pathOrBase64;
    return `/storage/${pathOrBase64}`;
};

const submit = () => {
    form.post(route('applicant.profile-update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Handled by controller redirect
        }
    });
};
</script>

<template>
    <Head title="Profile Setup" />

    <AdminLayout>
        <template #header>Profile Setup</template>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <form @submit.prevent="submit" class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0"><i class="bi bi-person-lines-fill me-2"></i>Complete Your Profile</h5>
                        <div class="small text-muted mt-1">Please fill in all required fields to complete your profile. This information will be used for your applications.</div>
                    </div>
                    
                    <div class="card-body bg-light">
                        <div class="row g-3">
                            <div v-for="field in props.template.fields" :key="field.id" 
                                :class="(field.field_type === 'textarea' || field.field_type === 'file') ? 'col-12' : 'col-md-6'">
                                
                                <label class="form-label fw-bold small">
                                    {{ field.label }}
                                    <span v-if="field.is_required" class="text-danger">*</span>
                                </label>

                                <template v-if="field.field_type === 'text'">
                                    <input type="text" class="form-control" v-model="form.responses[field.system_alias || field.id]" :required="field.is_required">
                                </template>

                                <template v-else-if="field.field_type === 'email'">
                                    <input type="email" class="form-control" v-model="form.responses[field.system_alias || field.id]" :required="field.is_required">
                                </template>

                                <template v-else-if="field.field_type === 'tel'">
                                    <input type="tel" class="form-control" v-model="form.responses[field.system_alias || field.id]" :required="field.is_required">
                                </template>

                                <template v-else-if="field.field_type === 'number'">
                                    <input type="number" class="form-control" v-model="form.responses[field.system_alias || field.id]" :required="field.is_required">
                                </template>

                                <template v-else-if="field.field_type === 'date'">
                                    <input type="date" class="form-control" v-model="form.responses[field.system_alias || field.id]" :required="field.is_required">
                                </template>

                                <template v-else-if="field.field_type === 'textarea'">
                                    <textarea class="form-control" rows="3" v-model="form.responses[field.system_alias || field.id]" :required="field.is_required"></textarea>
                                </template>

                                <template v-else-if="field.field_type === 'select'">
                                    <select class="form-select" v-model="form.responses[field.system_alias || field.id]" :required="field.is_required">
                                        <option value="">Select...</option>
                                        <option v-for="opt in getFieldOptions(field)" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                </template>

                                <template v-else-if="field.field_type === 'radio'">
                                    <div class="mt-2">
                                        <div v-for="(opt, oIdx) in getFieldOptions(field)" :key="oIdx" class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" :name="'field_'+field.id" :id="'opt_'+field.id+'_'+oIdx" :value="opt" v-model="form.responses[field.system_alias || field.id]" :required="field.is_required">
                                            <label class="form-check-label" :for="'opt_'+field.id+'_'+oIdx">{{ opt }}</label>
                                        </div>
                                    </div>
                                </template>

                                <template v-else-if="field.field_type === 'checkbox'">
                                    <div class="mt-2">
                                        <div v-for="(opt, oIdx) in getFieldOptions(field)" :key="oIdx" class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" :id="'chk_'+field.id+'_'+oIdx" :value="opt" v-model="form.responses[field.system_alias || field.id]">
                                            <label class="form-check-label" :for="'chk_'+field.id+'_'+oIdx">{{ opt }}</label>
                                        </div>
                                    </div>
                                </template>

                                <template v-else-if="field.field_type === 'file'">
                                    <div class="card card-body bg-white text-center">
                                        <div class="mb-2">
                                            <img v-if="form.responses[field.system_alias || field.id]" 
                                                :src="getImageUrl(form.responses[field.system_alias || field.id])" 
                                                class="img-thumbnail" style="max-height: 150px; object-fit: contain;" />
                                            <div v-else class="text-muted small py-4 bg-light border rounded">No file uploaded</div>
                                        </div>
                                        <input type="file" @change="e => onFileChange(e, field.system_alias || field.id, field.label, field.system_alias === 'system_signature' ? 3/1 : 1)" class="form-control form-control-sm mx-auto" style="max-width: 300px;" accept="image/*" />
                                        <small class="text-muted d-block mt-1">Image file (Max 2MB)</small>
                                    </div>
                                </template>

                                <div v-if="form.errors['responses.' + (field.system_alias || field.id)]" class="text-danger small mt-1">
                                    {{ form.errors['responses.' + (field.system_alias || field.id)] }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white text-end py-3">
                        <button type="submit" class="btn btn-primary px-5 shadow-sm" :disabled="form.processing">
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Image Cropper -->
        <ImageCropper 
            v-if="showCropper"
            :src="rawImage"
            :aspectRatio="activeAspectRatio"
            :title="'Crop ' + cropperTitle"
            @crop="handleCrop"
            @close="showCropper = false"
        />
    </AdminLayout>
</template>

<style scoped>
.img-thumbnail {
    border-radius: 4px;
    border: 2px solid #dee2e6;
}
</style>
