<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';

const props = defineProps({
    tenant: Object
});

const form = useForm({
    name: props.tenant?.name || '',
    landing_page_html: props.tenant?.landing_page_html || '',
    header_address: props.tenant?.header_address || '',
    header_subtext_prefix: props.tenant?.header_subtext_prefix || 'APPLICATION FORM FOR',
    logo: null,
});

const logoPreview = ref(props.tenant?.logo_path ? `/storage/${props.tenant.logo_path}` : null);

const handleLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.settings.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Success notification
        }
    });
};
</script>

<template>
    <Head title="Portal Settings" />

    <AdminLayout>
        <template #header>
            <div class="d-flex align-items-center justify-content-between">
                <span>Portal Settings</span>
            </div>
        </template>

        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <form @submit.prevent="submit">
                        <!-- General Settings Card -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h5 class="card-title mb-0 fw-bold"><i class="bi bi-gear-fill me-2 text-primary"></i> General Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Portal Name</label>
                                        <input v-model="form.name" type="text" class="form-control" placeholder="e.g. Lalit Narayan Mithila University" />
                                        <div v-if="form.errors.name" class="text-danger small mt-1">{{ form.errors.name }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Portal Logo</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="logo-preview-box border rounded d-flex align-items-center justify-content-center bg-light" style="width: 80px; height: 80px; overflow: hidden;">
                                                <img v-if="logoPreview" :src="logoPreview" class="img-fluid" />
                                                <i v-else class="bi bi-image text-muted fs-2"></i>
                                            </div>
                                            <div>
                                                <input type="file" @change="handleLogoChange" class="form-control form-control-sm" accept="image/*" />
                                                <small class="text-muted d-block mt-1">Recommended: Square, transparent PNG (max 2MB)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Print Settings Card -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h5 class="card-title mb-0 fw-bold"><i class="bi bi-printer-fill me-2 text-primary"></i> Print & Header Settings</h5>
                                <p class="text-muted small mb-0">These details appear on the official printable application forms.</p>
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Official Header Address</label>
                                    <input v-model="form.header_address" type="text" class="form-control" 
                                        placeholder="e.g. Kameshwaranagar, Darbhanga, Bihar 846004" />
                                    <div class="form-text small">This address will be displayed below the university name in the print header.</div>
                                    <div v-if="form.errors.header_address" class="text-danger small mt-1">{{ form.errors.header_address }}</div>
                                </div>

                                <div>
                                    <label class="form-label fw-bold">Form Title Prefix</label>
                                    <input v-model="form.header_subtext_prefix" type="text" class="form-control" 
                                        placeholder="e.g. APPLICATION FORM FOR" />
                                    <div class="form-text small">This text appears before the program name (e.g. <strong>APPLICATION FORM FOR</strong> GUEST TEACHER).</div>
                                    <div v-if="form.errors.header_subtext_prefix" class="text-danger small mt-1">{{ form.errors.header_subtext_prefix }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Advanced Card -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom-0">
                                <h5 class="card-title mb-0 fw-bold"><i class="bi bi-code-square me-2 text-primary"></i> Landing Page Customization</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Custom Landing Page HTML (Optional)</label>
                                    <p class="text-sm text-muted mb-3">
                                        If you provide HTML code here, it will replace the default portal landing page. You can include styles and scripts.
                                    </p>
                                    <textarea
                                        v-model="form.landing_page_html"
                                        rows="10"
                                        class="form-control font-mono"
                                        placeholder="<html>...</html>"
                                    ></textarea>
                                    <div v-if="form.errors.landing_page_html" class="text-danger small mt-1">{{ form.errors.landing_page_html }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mb-5">
                            <button
                                type="submit"
                                class="btn btn-primary px-5 py-2 fw-bold shadow-sm"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-check-circle me-2"></i>
                                Save All Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.font-mono {
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.85rem;
}
</style>
