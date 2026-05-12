<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    stats: Object,
    statusBreakdown: Array,
    programBreakdown: Array,
    subjectBreakdown: Array,
    recentApps: Array
});

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'submitted': return 'badge-soft-info text-info';
        case 'draft': return 'badge-soft-secondary text-secondary';
        default: return 'badge-soft-secondary text-secondary';
    }
};

const getPaymentBadgeClass = (status) => {
    switch (status) {
        case 'paid': return 'badge-soft-success text-success';
        case 'pending': return 'badge-soft-warning text-warning';
        case 'failed': return 'badge-soft-danger text-danger';
        default: return 'badge-soft-secondary text-secondary';
    }
};

const getActionBadgeClass = (status) => {
    switch (status) {
        case 'approved': return 'badge-soft-success text-success';
        case 'rejected': return 'badge-soft-danger text-danger';
        case 'under_review': return 'badge-soft-info text-info';
        case 'pending': return 'badge-soft-warning text-warning';
        default: return 'badge-soft-secondary text-secondary';
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>
            <div class="d-flex justify-content-between align-items-center w-100">
                <span class="fs-4 fw-bold text-dark tracking-tight">System Overview</span>
                <span class="text-muted small"><i class="bi bi-clock-history me-1"></i> Real-time Analytics</span>
            </div>
        </template>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-sm-6 col-md-3">
                <div class="stat-card p-4 h-100 position-relative overflow-hidden">
                    <div class="stat-icon bg-primary-gradient">
                        <i class="bi bi-file-earmark-person"></i>
                    </div>
                    <div class="mt-3">
                        <span class="stat-label">Total Applications</span>
                        <h3 class="stat-value text-dark mb-0 mt-1">{{ stats.total_applications }}</h3>
                    </div>
                    <div class="stat-bg-icon"><i class="bi bi-file-earmark-person"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="stat-card p-4 h-100 position-relative overflow-hidden">
                    <div class="stat-icon bg-success-gradient">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="mt-3">
                        <span class="stat-label">Paid Applications</span>
                        <h3 class="stat-value text-dark mb-0 mt-1">{{ stats.paid_applications }}</h3>
                    </div>
                    <div class="stat-bg-icon"><i class="bi bi-cash-stack"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="stat-card p-4 h-100 position-relative overflow-hidden">
                    <div class="stat-icon bg-warning-gradient">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="mt-3">
                        <span class="stat-label">Total Candidates</span>
                        <h3 class="stat-value text-dark mb-0 mt-1">{{ stats.total_candidates }}</h3>
                    </div>
                    <div class="stat-bg-icon"><i class="bi bi-people"></i></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <div class="stat-card p-4 h-100 position-relative overflow-hidden">
                    <div class="stat-icon bg-info-gradient">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <div class="mt-3">
                        <span class="stat-label">Active Programs</span>
                        <h3 class="stat-value text-dark mb-0 mt-1">{{ stats.total_programs }}</h3>
                    </div>
                    <div class="stat-bg-icon"><i class="bi bi-briefcase"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Subject Breakdown -->
            <div class="col-md-6">
                <div class="dashboard-panel h-100">
                    <div class="panel-header border-bottom py-3 px-4 d-flex align-items-center">
                        <div class="icon-circle bg-primary-soft me-3">
                            <i class="bi bi-bar-chart-fill text-primary"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-dark">Top Subjects by Entity</h6>
                    </div>
                    <div class="panel-body p-4">
                        <div v-for="(sub, index) in subjectBreakdown" :key="sub.label" class="mb-4 subject-row">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold text-dark">{{ sub.label }}</span>
                                <span class="badge bg-light text-dark border">{{ sub.value }} apps</span>
                            </div>
                            <div class="progress custom-progress">
                                <div class="progress-bar bg-primary-gradient rounded-pill" 
                                     :style="{ width: (stats.total_applications > 0 ? (sub.value / stats.total_applications * 100) : 0) + '%' }">
                                </div>
                            </div>
                        </div>
                        <div v-if="!subjectBreakdown.length" class="empty-state py-5 text-center">
                            <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                            <span class="text-muted fw-medium">No application data available yet.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Program Breakdown -->
            <div class="col-md-6">
                <div class="dashboard-panel h-100">
                    <div class="panel-header border-bottom py-3 px-4 d-flex align-items-center">
                        <div class="icon-circle bg-success-soft me-3">
                            <i class="bi bi-pie-chart-fill text-success"></i>
                        </div>
                        <h6 class="mb-0 fw-bold text-dark">Applications by Program Post</h6>
                    </div>
                    <div class="panel-body p-4">
                        <div v-for="program in programBreakdown" :key="program.label" class="mb-4 subject-row">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold text-dark">{{ program.label }} <span class="text-muted fw-normal ms-1">{{ program.title }}</span></span>
                                <span class="badge bg-light text-dark border">{{ program.value }} apps</span>
                            </div>
                            <div class="progress custom-progress">
                                <div class="progress-bar bg-success-gradient rounded-pill" 
                                     :style="{ width: (stats.total_applications > 0 ? (program.value / stats.total_applications * 100) : 0) + '%' }">
                                </div>
                            </div>
                        </div>
                        <div v-if="!programBreakdown.length" class="empty-state py-5 text-center">
                            <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                            <span class="text-muted fw-medium">No application data available yet.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Applications Table -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-panel overflow-hidden">
                    <div class="panel-header border-bottom py-3 px-4 d-flex justify-content-between align-items-center bg-light-soft">
                        <div class="d-flex align-items-center">
                            <div class="icon-circle bg-danger-soft me-3">
                                <i class="bi bi-clock-history text-danger"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-dark">Recent Applications</h6>
                        </div>
                        <div class="card-tools d-flex gap-2">
                            <Link :href="route('applications.index')" class="btn btn-sm btn-light border fw-semibold text-dark shadow-sm hover-elevate">
                                View All
                            </Link>
                            <a :href="route('admin.dashboard.export')" class="btn btn-sm btn-success-gradient text-white fw-semibold shadow-sm hover-elevate">
                                <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover custom-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">App No</th>
                                    <th>Applicant</th>
                                    <th>Subject Entity</th>
                                    <th>Form Status</th>
                                    <th>Payment Status</th>
                                    <th>Admin Action</th>
                                    <th>Applied On</th>
                                    <th class="text-end pe-4">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="app in recentApps" :key="app.id" class="table-row-animate">
                                    <td class="ps-4 fw-bold text-primary">#{{ app.application_no }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center me-3 fw-bold">
                                                {{ app.applicant?.name ? app.applicant.name.charAt(0).toUpperCase() : '?' }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ app.applicant?.name || 'Unknown Applicant' }}</div>
                                                <div class="text-muted small">{{ app.applicant?.email || 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="fw-medium text-dark">{{ app.opening?.subject?.name || '—' }}</span></td>
                                    <td>
                                        <span class="badge rounded-pill fw-medium px-3 py-2" :class="getStatusBadgeClass(app.form_status)">{{ app.form_status.toUpperCase() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill fw-medium px-3 py-2" :class="getPaymentBadgeClass(app.status)">{{ app.status.toUpperCase() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill fw-medium px-3 py-2" :class="getActionBadgeClass(app.action_status)">{{ app.action_status.toUpperCase() }}</span>
                                    </td>
                                    <td class="text-muted">{{ formatDate(app.created_at) }}</td>
                                    <td class="text-end pe-4">
                                        <Link :href="route('applications.show', app.id)" class="btn btn-sm btn-light-primary rounded-circle shadow-sm btn-icon">
                                            <i class="bi bi-chevron-right"></i>
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="!recentApps.length">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <div class="empty-state">
                                            <i class="bi bi-search fs-1 mb-2 d-block"></i>
                                            <p class="mb-0">No recent applications found.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Typography & Layout */
.tracking-tight { letter-spacing: -0.025em; }

/* Dashboard Panel */
.dashboard-panel {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Stat Cards */
.stat-card {
    background: #ffffff;
    border-radius: 1rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}
.stat-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stat-value {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -1px;
}
.stat-bg-icon {
    position: absolute;
    right: -20px;
    bottom: -20px;
    font-size: 8rem;
    color: rgba(0,0,0,0.02);
    transform: rotate(-15deg);
    pointer-events: none;
}

/* Gradients */
.bg-primary-gradient { background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); }
.bg-success-gradient { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.bg-warning-gradient { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.bg-info-gradient { background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); }

/* Soft Backgrounds & Badges */
.bg-primary-soft { background-color: rgba(59, 130, 246, 0.1); }
.bg-success-soft { background-color: rgba(16, 185, 129, 0.1); }
.bg-danger-soft { background-color: rgba(239, 68, 68, 0.1); }
.bg-light-soft { background-color: #f8fafc; }

.badge-soft-info { background-color: rgba(14, 165, 233, 0.1); color: #0284c7; }
.badge-soft-secondary { background-color: rgba(100, 116, 139, 0.1); color: #475569; }
.badge-soft-success { background-color: rgba(16, 185, 129, 0.1); color: #059669; }
.badge-soft-warning { background-color: rgba(245, 158, 11, 0.1); color: #b45309; }
.badge-soft-danger { background-color: rgba(239, 68, 68, 0.1); color: #dc2626; }

.btn-success-gradient {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
}
.btn-success-gradient:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: white;
}
.btn-light-primary {
    background: #f1f5f9;
    color: #3b82f6;
    border: 1px solid #e2e8f0;
}
.btn-light-primary:hover {
    background: #e0e7ff;
    color: #4f46e5;
}

/* UI Elements */
.icon-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}
.avatar-sm {
    width: 40px;
    height: 40px;
}
.custom-progress {
    height: 8px;
    background-color: #f1f5f9;
    border-radius: 10px;
    overflow: hidden;
}
.hover-elevate {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-elevate:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
}

/* Table Styles */
.custom-table {
    border-collapse: separate;
    border-spacing: 0;
}
.custom-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 1rem 0.5rem;
    border-bottom: 2px solid #e2e8f0;
}
.custom-table tbody td {
    padding: 1rem 0.5rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.table-row-animate {
    transition: background-color 0.2s ease;
}
.table-row-animate:hover {
    background-color: #f8fafc;
}
.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>
