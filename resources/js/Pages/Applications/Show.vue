<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    application: Object,
});

const newStatus = ref(props.application.action_status);

const statusOptions = [
    { value: 'pending',      label: 'Pending' },
    { value: 'under_review', label: 'Under Review' },
    { value: 'approved',     label: 'Approved' },
    { value: 'rejected',     label: 'Rejected' },
];

const statusBadge = (s) => ({
    pending:      'bg-warning text-dark',
    under_review: 'bg-info text-dark',
    approved:     'bg-success',
    rejected:     'bg-danger',
})[s] ?? 'bg-secondary';

const updateStatus = () => {
    router.patch(route('applications.update-status', props.application.id), {
        action_status: newStatus.value,
    });
};

const responsesByStep = computed(() => {
    const steps = {};
    const templateFields = props.application.opening?.program?.form_template?.fields ?? [];
    const responses = props.application.responses ?? {};

    templateFields.forEach(field => {
        const step = field.step ?? 1;
        if (!steps[step]) steps[step] = [];
        
        // Check for value using alias first, then fallback to field ID
        let val = '—';
        if (field.system_alias && responses[field.system_alias] !== undefined) {
            val = responses[field.system_alias];
        } else if (responses[field.id] !== undefined) {
            val = responses[field.id];
        }

        steps[step].push({
            field: field,
            value: val
        });
    });
    return steps;
});

const parseResponseValue = (val) => {
    if (!val || typeof val !== 'string') return val;
    if (val.startsWith('[') || val.startsWith('{')) {
        try { return JSON.parse(val); } catch (e) { return val; }
    }
    return val;
};

const isTable = (val) => {
    const p = parseResponseValue(val);
    return Array.isArray(p) && p.length > 0 && typeof p[0] === 'object' && p[0] !== null;
};

const isFile = (val) => {
    if (typeof val !== 'string' || !val) return false;
    // Tenant-scoped paths: UUID/applicant/files/..., UUID/logos/..., UUID/news/...
    if (/^[0-9a-f-]{36}\//i.test(val)) return true;
    // Legacy paths
    return val.startsWith('candidate_uploads/') || 
           val.startsWith('signatures/') || 
           val.startsWith('photos/') ||
           val.startsWith('applicants/');
};
</script>

<template>
    <AdminLayout>
        <template #header>Application Detail</template>

        <div class="row">
            <!-- Left: Application Info -->
            <div class="col-lg-4 mb-3">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-person-circle me-2"></i>Applicant Info
                        </h5>
                        <a :href="route('applications.print', application.id)" target="_blank" class="btn btn-xs btn-outline-danger btn-sm shadow-sm">
                            <i class="bi bi-printer me-1"></i> Print Form
                        </a>
                    </div>
                    <div class="card-body small">
                        <dl class="row mb-0">
                            <dt class="col-5">Name</dt>
                            <dd class="col-7">{{ application.applicant?.name ?? '—' }}</dd>

                            <dt class="col-5">Email</dt>
                            <dd class="col-7">{{ application.applicant?.email ?? '—' }}</dd>

                            <dt class="col-5">Mobile</dt>
                            <dd class="col-7">{{ application.applicant?.mobile ?? '—' }}</dd>

                            <dt class="col-5">App No.</dt>
                            <dd class="col-7 fw-bold font-monospace">{{ application.application_no }}</dd>

                            <dt class="col-5">Program</dt>
                            <dd class="col-7">{{ application.opening?.program?.title ?? '—' }}</dd>

                            <dt class="col-5">Subject</dt>
                            <dd class="col-7">{{ application.opening?.subject?.name ?? '—' }}</dd>

                            <dt class="col-5">Fee</dt>
                            <dd class="col-7">₹{{ application.fee_amount }}</dd>

                            <dt class="col-5">Tax</dt>
                            <dd class="col-7">₹{{ application.tax_amount }}</dd>

                            <dt class="col-5">Fine</dt>
                            <dd class="col-7">₹{{ application.fine_amount }}</dd>

                            <dt class="col-5">Total</dt>
                            <dd class="col-7 fw-bold text-danger">₹{{ application.total_amount }}</dd>

                            <dt class="col-5">Submitted</dt>
                            <dd class="col-7">{{ application.created_at?.slice(0, 10) }}</dd>

                            <dt class="col-5">Form Status</dt>
                            <dd class="col-7">
                                <span class="badge rounded-pill" :class="application.form_status === 'submitted' ? 'bg-info text-dark' : 'bg-secondary'">
                                    {{ application.form_status }}
                                </span>
                                <Link v-if="application.form_status === 'submitted'" 
                                      :href="route('applications.unlock', application.id)" 
                                      method="post" 
                                      as="button" 
                                      class="btn btn-sm btn-outline-primary ms-2 py-0 px-2" 
                                      preserve-scroll
                                      title="Allow Applicant to Edit Form">
                                    <i class="bi bi-unlock"></i> Unlock Form
                                </Link>
                            </dd>

                            <dt class="col-5">Payment Status</dt>
                            <dd class="col-7 d-flex align-items-center">
                                <span class="badge rounded-pill" :class="{
                                    'bg-warning text-dark': application.status === 'pending',
                                    'bg-success': application.status === 'paid',
                                    'bg-danger': application.status === 'failed',
                                }">
                                    {{ application.status?.toUpperCase() }}
                                </span>
                                <Link v-if="application.status !== 'paid'" 
                                      :href="route('applications.refresh-payment', application.id)" 
                                      method="post" 
                                      as="button" 
                                      class="btn btn-sm btn-outline-secondary ms-2 py-0 px-2" 
                                      preserve-scroll
                                      title="Refresh Payment Status">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </Link>
                            </dd>

                            <dt class="col-5">Action Status</dt>
                            <dd class="col-7">
                                <span class="badge rounded-pill" :class="statusBadge(application.action_status)">
                                    {{ application.action_status }}
                                </span>
                            </dd>
                        </dl>
                    </div>
                    <!-- Update Status -->
                    <div class="card-footer">
                        <label class="form-label small fw-semibold mb-1">Update Action Status</label>
                        <div class="input-group input-group-sm">
                            <select v-model="newStatus" class="form-select">
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <button class="btn btn-primary" @click="updateStatus">
                                <i class="bi bi-check-lg"></i> Save
                            </button>
                        </div>
                    </div>
                </div>
            <!-- Payment Info -->
                <div class="card card-outline card-success mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-credit-card me-2"></i>Payment Details
                        </h5>
                    </div>
                    <div class="card-body small">
                        <div v-if="!application.transactions?.length" class="text-center text-muted py-3">
                            <i class="bi bi-x-circle fs-4 d-block mb-1"></i>
                            No payments found.
                        </div>
                        <div v-else>
                            <div v-for="txn in application.transactions" :key="txn.id" class="border rounded p-3 mb-2 bg-light">
                                <dl class="row mb-0">
                                    <dt class="col-5">Txn ID</dt>
                                    <dd class="col-7 font-monospace fw-bold">{{ txn.transaction_id }}</dd>

                                    <dt class="col-5">Date</dt>
                                    <dd class="col-7">{{ new Date(txn.created_at).toLocaleString() }}</dd>

                                    <dt class="col-5">Amount</dt>
                                    <dd class="col-7 text-success fw-bold">₹{{ txn.amount }}</dd>

                                    <dt class="col-5">Status</dt>
                                    <dd class="col-7">
                                        <span class="badge rounded-pill" :class="statusBadge(txn.status === 'success' || txn.status === 'completed' ? 'approved' : (txn.status === 'failed' ? 'rejected' : 'pending'))">
                                            {{ txn.status.toUpperCase() }}
                                        </span>
                                    </dd>
                                    <dt class="col-5">Gateway Txn</dt>
                                    <dd class="col-7 font-monospace">{{ txn.gateway_transaction_id ?? '—' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Form Responses -->
            <div class="col-lg-8 mb-3">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-ui-checks me-2"></i>Form Responses
                        </h5>
                    </div>
                    <div class="card-body">
                        <div v-if="!Object.keys(responsesByStep).length" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                            No form responses recorded for this application.
                        </div>

                        <template v-for="(responses, step) in responsesByStep" :key="step">
                            <h6 class="text-uppercase text-muted small fw-bold mb-2 mt-3">
                                <i class="bi bi-layers me-1"></i>Step {{ step }}
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:40%">Field</th>
                                            <th>Answer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(r, idx) in responses" :key="idx">
                                            <td class="fw-semibold small">{{ r.field?.label ?? 'Field' }}</td>
                                            <td class="small">
                                                <!-- File link (standard) -->
                                                <a v-if="(r.field?.field_type === 'file' || isFile(r.value)) && !isTable(r.value)"
                                                    :href="'/storage/' + r.value"
                                                    target="_blank"
                                                    class="btn btn-xs btn-outline-secondary btn-sm">
                                                    <i class="bi bi-file-earmark-arrow-down me-1"></i>Download
                                                </a>
                                                
                                                <!-- Tabular data -->
                                                <div v-else-if="isTable(r.value)" class="table-responsive">
                                                    <table class="table table-bordered table-sm mb-0 extra-small">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th v-for="key in Object.keys(parseResponseValue(r.value)[0])" :key="key">{{ key }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr v-for="(row, idx) in parseResponseValue(r.value)" :key="idx">
                                                                <td v-for="(cell, key) in row" :key="key">
                                                                    <a v-if="isFile(cell)" :href="'/storage/' + cell" target="_blank" class="text-primary text-decoration-none">
                                                                        <i class="bi bi-file-earmark-arrow-down"></i> File
                                                                    </a>
                                                                    <span v-else>{{ cell }}</span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- Multi-value (checkbox/array) -->
                                                <span v-else-if="Array.isArray(r.value)">
                                                    {{ r.value.join(', ') }}
                                                </span>
                                                <span v-else-if="typeof r.value === 'string' && r.value.startsWith('[')">
                                                    {{ Array.isArray(parseResponseValue(r.value)) ? parseResponseValue(r.value).join(', ') : r.value }}
                                                </span>
                                                <span v-else>{{ r.value ?? '—' }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                    <div class="card-footer text-end">
                        <Link :href="route('applications.index')" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to All Applications
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.extra-small { font-size: 0.75rem; }
</style>
