<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    jobs: Array
});

const selectedJob = ref('');

const downloadMasterExcel = () => {
    let url = route('admin.reports.master');
    if (selectedJob.value) {
        url += `?job_id=${selectedJob.value}`;
    }
    window.location.href = url;
};
</script>

<template>
    <Head :title="($page.props.tenant?.name || 'Portal') + ' Reports'" />

    <AdminLayout>
        <template #header>Reports & Analytics</template>

        <div class="row">
            <!-- Summary Stats Card -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center p-4">
                        <div class="display-5 text-primary mb-2"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                        <h5 class="fw-bold">Master Application Report</h5>
                        <p class="text-muted small">Download a multi-sheet Excel file containing Executive Summary, Master Applicant List, and Subject-wise statistics.</p>
                        
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold">Filter by Program (Optional)</label>
                            <select v-model="selectedJob" class="form-select form-select-sm">
                                <option value="">All Programs (Full Export)</option>
                                <option v-for="job in jobs" :key="job.id" :value="job.id">{{ job.job_code }} - {{ job.title }}</option>
                            </select>
                        </div>

                        <button @click="downloadMasterExcel" class="btn btn-primary w-100 py-2">
                            <i class="bi bi-download me-2"></i>Download Master Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Category-wise Report -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center p-4">
                        <div class="display-5 text-success mb-2"><i class="bi bi-people"></i></div>
                        <h5 class="fw-bold">Social Category Report</h5>
                        <p class="text-muted small">Get a breakdown of applicants based on categories (General, OBC, SC, ST, EBC, EWS).</p>
                        <button disabled class="btn btn-outline-success w-100 py-2">
                             Coming Soon
                        </button>
                    </div>
                </div>
            </div>

            <!-- Subject-wise Report -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center p-4">
                        <div class="display-5 text-danger mb-2"><i class="bi bi-journal-check"></i></div>
                        <h5 class="fw-bold">Subject-wise Master List</h5>
                        <p class="text-muted small">Generate a consolidated list of applicants grouped by their applied subjects.</p>
                        <button disabled class="btn btn-outline-danger w-100 py-2">
                             Coming Soon
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-12">
                <div class="alert alert-info border-0 shadow-sm">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Note:</strong> Master Excel reports are generated in real-time and contain live data from the database. For very large datasets, generation may take a few moments.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.display-5 {
    font-size: 3rem;
}
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>
