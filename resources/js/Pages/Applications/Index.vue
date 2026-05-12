<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    applications: Object,
    programs: Array,
    filters: Object,
});

const programId = ref(props.filters?.program_id ?? '');
const actionStatus = ref(props.filters?.action_status ?? '');
const status = ref(props.filters?.status ?? '');
const formStatus = ref(props.filters?.form_status ?? '');
const search = ref(props.filters?.search ?? '');
const selectedIds = ref([]);

const toggleAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = props.applications.data.map(app => app.id);
    } else {
        selectedIds.value = [];
    }
};

const dossierExportUrl = computed(() => {
    const params = new URLSearchParams();
    if (selectedIds.value.length > 0) {
        selectedIds.value.forEach(id => params.append('ids[]', id));
    } else {
        // If nothing selected, maybe export all in current filter? 
        // User said "checkbox or all showing in one page".
        props.applications.data.forEach(app => params.append('ids[]', app.id));
    }
    return route('admin.reports.dossier') + '?' + params.toString();
});

watch([programId, actionStatus, status, formStatus, search], () => {
    router.get(route('applications.index'), {
        program_id: programId.value || undefined,
        action_status: actionStatus.value || undefined,
        status: status.value || undefined,
        form_status: formStatus.value || undefined,
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
});

const exportUrl = computed(() => {
    return route('admin.dashboard.export', {
        program_id: programId.value || undefined,
        action_status: actionStatus.value || undefined,
        status: status.value || undefined,
        form_status: formStatus.value || undefined,
    });
});

const statusBadge = (s) => ({
    draft:        'bg-secondary',
    submitted:    'bg-info text-dark',
})[s] ?? 'bg-secondary';

const statusLabel = (s) => ({
    draft:        'Draft',
    submitted:    'Submitted',
})[s] ?? s;

const actionBadge = (s) => ({
    pending:      'bg-warning text-dark',
    under_review: 'bg-info text-dark',
    approved:     'bg-success',
    rejected:     'bg-danger',
})[s] ?? 'bg-secondary';

const actionLabel = (s) => ({
    pending:      'Pending',
    under_review: 'Under Review',
    approved:     'Approved',
    rejected:     'Rejected',
})[s] ?? s;

const paymentBadge = (s) => ({
    pending:      'bg-warning text-dark',
    paid:         'bg-success',
    failed:       'bg-danger',
})[s] ?? 'bg-secondary';

const paymentLabel = (s) => ({
    pending:      'Pending',
    paid:         'Paid',
    failed:       'Failed',
})[s] ?? s;

const syncAllPayments = () => {
    if (confirm('This will scan all applications and update their status based on successful transactions. Continue?')) {
        router.post(route('applications.bulk-sync'), {}, {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <AdminLayout>
        <template #header>Applicant Applications</template>

        <!-- Filters -->
        <div class="card card-outline card-primary mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Filter by Program</label>
                        <select v-model="programId" class="form-select form-select-sm">
                            <option value="">All Programs</option>
                            <option v-for="program in programs" :key="program.id" :value="program.id">
                                {{ program.job_code }} — {{ program.title }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Payment Status</label>
                        <select v-model="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Form Status</label>
                        <select v-model="formStatus" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small mb-1">Action Status</label>
                        <select v-model="actionStatus" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="under_review">Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-12 mt-2">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input v-model="search" type="text" class="form-control border-start-0 ps-0" placeholder="Search by App No., Applicant Name or Email..." />
                            <button v-if="search" @click="search = ''" class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">All Applications</h5>
                <div class="card-tools d-flex gap-2 align-items-center">
                    <span class="badge bg-primary rounded-pill me-2">{{ applications.total }} Total</span>
                    <button @click="syncAllPayments" class="btn btn-sm btn-info text-white">
                        <i class="bi bi-arrow-repeat me-1"></i> Sync Statuses
                    </button>
                    <a :href="exportUrl" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
                    </a>
                    <a :href="dossierExportUrl" class="btn btn-sm btn-success">
                        <i class="bi bi-file-earmark-excel me-1"></i> Dossier Excel (Selected)
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" @change="toggleAll" :checked="selectedIds.length === applications.data.length && applications.data.length > 0" />
                                </th>
                                <th>App No.</th>
                                <th>Applicant</th>
                                <th>Email</th>
                                <th>Program</th>
                                <th>Subject</th>
                                <th>Payment Status</th>
                                <th>Form Status</th>
                                <th>Action Status</th>
                                <th>Total</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="applications.data.length === 0">
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                    No applications found.
                                </td>
                            </tr>
                            <tr v-for="app in applications.data" :key="app.id">
                                <td class="ps-3">
                                    <input type="checkbox" v-model="selectedIds" :value="app.id" class="form-check-input" />
                                </td>
                                <td class="fw-semibold text-monospace small">{{ app.application_no }}</td>
                                <td>{{ app.applicant?.name ?? '—' }}</td>
                                <td class="small text-muted">{{ app.applicant?.email ?? '—' }}</td>
                                <td class="small">{{ app.opening?.program?.title ?? '—' }}</td>
                                <td class="small">{{ app.opening?.subject?.name ?? '—' }}</td>
                                <td>
                                    <template v-if="app.opening?.program?.is_payable === false">
                                        <span class="badge rounded-pill bg-info text-dark">FREE</span>
                                    </template>
                                    <template v-else>
                                        <span class="badge rounded-pill" :class="paymentBadge(app.status)">
                                            {{ paymentLabel(app.status) }}
                                        </span>
                                    </template>
                                </td>
                                <td>
                                    <span class="badge rounded-pill" :class="statusBadge(app.form_status)">
                                        {{ statusLabel(app.form_status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill" :class="actionBadge(app.action_status)">
                                        {{ actionLabel(app.action_status) }}
                                    </span>
                                </td>
                                <td class="small">₹{{ app.total_amount }}</td>
                                <td class="small text-muted">{{ app.created_at?.slice(0, 10) }}</td>
                                <td>
                                    <Link :href="route('applications.show', app.id)"
                                        class="btn btn-xs btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i> View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="card-footer d-flex justify-content-between align-items-center py-2" v-if="applications.last_page > 1">
                <small class="text-muted">
                    Showing {{ applications.from }}–{{ applications.to }} of {{ applications.total }}
                </small>
                <div class="d-flex gap-1">
                    <Link v-for="link in applications.links" :key="link.label"
                        :href="link.url ?? '#'"
                        class="btn btn-sm"
                        :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                        :disabled="!link.url"
                        v-html="link.label">
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
