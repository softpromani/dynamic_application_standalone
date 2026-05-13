<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';
import TextInput from '@softpro-core/Components/TextInput.vue';
import ImageCropper from '@softpro-core/Components/UI/ImageCropper.vue';

const props = defineProps({
    applicant: Object,
});

const form = useForm({
    name:                   props.applicant?.name || '',
    phone:                  props.applicant?.phone || '',
    dob:                    props.applicant?.dob ? props.applicant.dob.slice(0, 10) : '',
    gender:                 props.applicant?.gender || '',
    category:               props.applicant?.category || '',
    father_name:            props.applicant?.father_name || '',
    mother_name:            props.applicant?.mother_name || '',
    marital_status:         props.applicant?.marital_status || '',
    permanent_address:      props.applicant?.permanent_address || '',
    correspondence_address: props.applicant?.correspondence_address || '',
    id_proof_type:          props.applicant?.id_proof_type || '',
    id_proof_number:        props.applicant?.id_proof_number || '',
    profile_photo:          null,
    signature:              null,
});

const photoPreview = ref(null);
const signaturePreview = ref(null);

const showPhotoCropper = ref(false);
const showSignatureCropper = ref(false);

const rawPhoto = ref(null);
const rawSignature = ref(null);

const onFileChange = (e, type) => {
    const file = e.target.files[0];
    if (file) {
        // Enforce 2MB limit
        if (file.size > (2 * 1024 * 1024)) {
            alert('File is too large! Maximum allowed size is 2MB.');
            e.target.value = ''; // Reset input
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            if (type === 'photo') {
                rawPhoto.value = event.target.result;
                showPhotoCropper.value = true;
            } else {
                rawSignature.value = event.target.result;
                showSignatureCropper.value = true;
            }
        };
        reader.readAsDataURL(file);
    }
};

const handleCrop = (data, type) => {
    if (type === 'photo') {
        photoPreview.value = data;
        form.profile_photo = data;
        showPhotoCropper.value = false;
    } else {
        signaturePreview.value = data;
        form.signature = data;
        showSignatureCropper.value = false;
    }
};

const submit = () => {
    form.post(route('applicant.profile-update'), {
        preserveScroll: true,
        onSuccess: () => {
            alert('Profile updated successfully!');
        }
    });
};

const getImageUrl = (path) => {
    return path ? `/storage/${path}` : null;
};
</script>

<template>
    <Head title="Profile Setup" />

    <AdminLayout>
        <template #header>
            Profile Setup
        </template>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <form @submit.prevent="submit" class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <h5 class="card-title mb-0"><i class="bi bi-person-circle me-2"></i>Personal & Identity</h5>
                    </div>
                    <div class="card-body bg-light">
                        <!-- Basic Info -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <TextInput v-model="form.name" label="Full Name" :error="form.errors.name" required />
                            </div>
                            <div class="col-md-4">
                                <TextInput v-model="form.phone" label="Mobile Number" :error="form.errors.phone" />
                            </div>
                            <div class="col-md-4">
                                <TextInput v-model="form.dob" type="date" label="Date of Birth" :error="form.errors.dob" required />
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Gender</label>
                                <select v-model="form.gender" class="form-select form-select-sm">
                                    <option value="">Select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Social Category</label>
                                <select v-model="form.category" class="form-select form-select-sm">
                                    <option value="">Select...</option>
                                    <option value="UR">UR (General)</option>
                                    <option value="EWS">EWS</option>
                                    <option value="BC">BC</option>
                                    <option value="EBC">EBC</option>
                                    <option value="SC">SC</option>
                                    <option value="ST">ST</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Marital Status</label>
                                <select v-model="form.marital_status" class="form-select form-select-sm">
                                    <option value="">Select...</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Parental Info -->
                        <div class="row g-3 mb-4 border-top pt-4">
                            <div class="col-md-6">
                                <TextInput v-model="form.father_name" label="Father's Name" :error="form.errors.father_name" />
                            </div>
                            <div class="col-md-6">
                                <TextInput v-model="form.mother_name" label="Mother's Name" :error="form.errors.mother_name" />
                            </div>
                        </div>

                        <!-- ID Proof -->
                        <div class="row g-3 mb-4 border-top pt-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">ID Proof Type</label>
                                <select v-model="form.id_proof_type" class="form-select form-select-sm">
                                    <option value="">Select ID Type...</option>
                                    <option value="Aadhar">Aadhar Card</option>
                                    <option value="PAN">PAN Card</option>
                                    <option value="VoterID">Voter ID</option>
                                    <option value="Passport">Passport</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <TextInput v-model="form.id_proof_number" label="ID Proof Number" :error="form.errors.id_proof_number" />
                            </div>
                        </div>

                        <!-- Addresses -->
                        <div class="row g-3 mb-4 border-top pt-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Permanent Address</label>
                                <textarea v-model="form.permanent_address" class="form-control form-control-sm" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Correspondence Address</label>
                                <textarea v-model="form.correspondence_address" class="form-control form-control-sm" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Photos -->
                        <div class="row g-3 border-top pt-4">
                            <div class="col-md-6 text-center border-end">
                                <label class="form-label fw-bold small d-block">Profile Photo</label>
                                <div class="mb-2">
                                    <img :src="photoPreview || getImageUrl(applicant?.profile_photo_path) || '/images/default-avatar.png'" 
                                        class="img-thumbnail bg-white" style="width: 150px; height: 150px; object-fit: cover;" />
                                </div>
                                <input type="file" @change="onFileChange($event, 'photo')" class="form-control form-control-sm w-75 mx-auto" accept="image/*" />
                                <small class="text-danger fw-bold d-block mt-1">Max Size: 2MB</small>
                                <small class="text-muted d-block">Passport size photo (Square)</small>
                            </div>
 
                            <div class="col-md-6 text-center">
                                <label class="form-label fw-bold small d-block">Digital Signature</label>
                                <div class="mb-2">
                                    <img :src="signaturePreview || getImageUrl(applicant?.signature_path) || '/images/default-signature.png'" 
                                        class="img-thumbnail bg-white" style="width: 200px; height: 80px; object-fit: contain;" />
                                </div>
                                <input type="file" @change="onFileChange($event, 'signature')" class="form-control form-control-sm w-75 mx-auto" accept="image/*" />
                                <small class="text-danger fw-bold d-block mt-1">Max Size: 2MB</small>
                                <small class="text-muted d-block">Clear signature on white background</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-end py-3">
                        <button type="submit" class="btn btn-primary btn-sm px-5 shadow-sm" :disabled="form.processing">
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cropper Modals -->
        <ImageCropper 
            v-if="showPhotoCropper"
            :src="rawPhoto"
            :aspectRatio="1"
            title="Crop Profile Photo"
            @crop="(data) => handleCrop(data, 'photo')"
            @close="showPhotoCropper = false"
        />

        <ImageCropper 
            v-if="showSignatureCropper"
            :src="rawSignature"
            :aspectRatio="3 / 1"
            title="Crop Digital Signature"
            @crop="(data) => handleCrop(data, 'signature')"
            @close="showSignatureCropper = false"
        />
    </AdminLayout>
</template>

<style scoped>
.img-thumbnail {
    border-radius: 4px;
    border: 2px solid #dee2e6;
}
</style>
