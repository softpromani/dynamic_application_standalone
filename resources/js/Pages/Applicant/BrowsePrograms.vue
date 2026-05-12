<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    programs: Array,
    myApplications: Object,
    profileComplete: Boolean,
});

const getApplicationStatus = (openingId) => props.myApplications ? props.myApplications[openingId] : null;

// Track which program card is expanded
const expandedProgram = ref(null);

const toggleProgram = (id) => {
    expandedProgram.value = expandedProgram.value === id ? null : id;
};

const isExpired = (endDate) => new Date(endDate) < new Date();

const daysLeft = (endDate) => {
    const diff = new Date(endDate) - new Date();
    return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)));
};

const formatDate = (d) =>
    d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const totalSeats = (program) => program.openings.reduce((s, v) => s + v.seats, 0);

const apply = (openingId) => {
    if (!props.profileComplete) {
        if (confirm('Your profile is incomplete. Please complete your profile in the profile setup section before applying. Go there now?')) {
            window.location.href = route('applicant.profile-setup');
        }
        return;
    }
    window.location.href = route('applicant.apply.form', openingId);
};

const getEntityName = (program) => {
    return program.custom_entity?.display_name || 'Program';
};

const isGeneralProgram = (program) => {
    return program.openings.length === 1 && !program.openings[0].subject_id;
};
</script>

<template>
    <Head title="Browse Programs" />

    <AdminLayout>
        <template #header>Current Program Openings</template>

        <!-- Profile incomplete warning -->
        <div v-if="!profileComplete"
            class="alert alert-warning d-flex align-items-center gap-3 border-0 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div class="flex-grow-1">
                <strong>Profile Incomplete!</strong>
                You must complete your profile details before you can apply for any programs.
            </div>
            <Link :href="route('applicant.profile-setup')" class="btn btn-warning btn-sm fw-semibold">
                Complete Profile →
            </Link>
        </div>

        <!-- Empty state -->
        <div v-if="programs.length === 0" class="text-center py-5">
            <i class="bi bi-inboxes display-1 text-secondary opacity-50"></i>
            <h5 class="text-muted mt-3">No active programs at this time.</h5>
        </div>

        <!-- Program Cards -->
        <div v-for="program in programs" :key="program.id" class="job-card mb-3"
            :class="{ 'job-card--open': expandedProgram === program.id }">

            <!-- ── Card Header (always visible, clickable) ── -->
            <div class="job-card__header d-flex align-items-center gap-3"
                @click="toggleProgram(program.id)" role="button">

                <!-- Icon -->
                <div class="job-icon">
                    <i class="bi bi-briefcase-fill"></i>
                </div>

                <!-- Main info -->
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h6 class="job-title mb-0">{{ program.title }}</h6>
                        <span class="badge bg-secondary-subtle text-secondary border"
                            style="font-size:0.7rem">{{ program.job_code }}</span>
                        <span v-if="isExpired(program.application_end_date)"
                            class="badge bg-danger">Closed</span>
                        <span v-else-if="daysLeft(program.application_end_date) <= 5"
                            class="badge bg-warning text-dark">
                            {{ daysLeft(program.application_end_date) }} days left!
                        </span>
                    </div>
                    <div class="job-meta mt-1 d-flex flex-wrap gap-3">
                        <span><i class="bi bi-calendar-check me-1 text-success"></i>
                            Opens: {{ formatDate(program.application_start_date) }}</span>
                        <span><i class="bi bi-calendar-x me-1 text-danger"></i>
                            Last Date: {{ formatDate(program.application_end_date) }}</span>
                        <span><i class="bi bi-grid me-1 text-primary"></i>
                            <template v-if="isGeneralProgram(program)">General Opening</template>
                            <template v-else>{{ program.openings.length }} {{ program.custom_entity ? program.custom_entity.display_name + 's' : 'Openings' }}</template>
                            · {{ totalSeats(program) > 0 ? totalSeats(program) + ' Seats' : 'Flexible Capacity' }}</span>
                        <span><i class="bi bi-cash me-1 text-warning"></i>
                            Fee: {{ !program.is_payable ? 'FREE' : (program.application_types && program.application_types.length > 1 ? 'Varies by Category' : (program.application_types && program.application_types.length === 1 ? '₹' + program.application_types[0].fee : '—')) }}</span>
                    </div>
                </div>

                <!-- Expand chevron -->
                <div class="job-chevron" :class="{ rotated: expandedProgram === program.id }">
                    <i class="bi bi-chevron-down"></i>
                </div>
            </div>

            <!-- ── Expanded Detail Panel ── -->
            <div v-if="expandedProgram === program.id" class="job-card__body">

                <!-- Info strip -->
                <div class="info-strip row g-0 mb-0">
                    <div class="col-6 col-md-3 info-strip__item">
                        <div class="info-strip__label">Start Date</div>
                        <div class="info-strip__val">{{ formatDate(program.application_start_date) }}</div>
                    </div>
                    <div class="col-6 col-md-3 info-strip__item">
                        <div class="info-strip__label">Last Date</div>
                        <div class="info-strip__val text-danger">{{ formatDate(program.application_end_date) }}</div>
                    </div>
                    <div class="col-6 col-md-3 info-strip__item">
                        <div class="info-strip__label">Application Fee</div>
                        <div class="info-strip__val">
                            {{ !program.is_payable ? 'FREE' : (program.application_types && program.application_types.length > 1 ? 'Varies' : (program.application_types && program.application_types.length === 1 ? '₹' + program.application_types[0].fee : '—')) }}
                        </div>
                    </div>
                    <div class="col-6 col-md-3 info-strip__item">
                        <div class="info-strip__label">Total Seats</div>
                        <div class="info-strip__val">{{ totalSeats(program) }}</div>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="program.description" class="px-4 py-2 border-bottom small text-muted fst-italic">
                    {{ program.description }}
                </div>

                <!-- Simplified Apply for General Program -->
                <div v-if="isGeneralProgram(program)" class="p-4 text-center bg-white">
                    <p class="text-muted mb-3">This is a general application program. No specific subject or department selection is required.</p>
                    
                    <span v-if="getApplicationStatus(program.openings[0].id) && getApplicationStatus(program.openings[0].id) !== 'draft'"
                        class="btn btn-success rounded-pill px-5 py-2 disabled opacity-75 fw-bold">
                        <i class="bi bi-check-circle-fill me-2"></i>You have already applied
                    </span>
                    <button v-else-if="getApplicationStatus(program.openings[0].id) === 'draft'" @click="apply(program.openings[0].id)"
                        class="btn btn-warning rounded-pill px-5 py-2 shadow fw-bold">
                        Continue Application <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <span v-else-if="isExpired(program.application_end_date)"
                        class="btn btn-light border rounded-pill px-5 py-2 disabled">
                        Applications Closed
                    </span>
                    <button v-else @click="apply(program.openings[0].id)"
                        class="btn btn-primary rounded-pill px-5 py-2 shadow-sm fw-bold">
                        Apply Now <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>

                <!-- Entity-wise opening table -->
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0 subject-table">
                        <thead>
                            <tr>
                                <th style="width:40px" class="ps-4">#</th>
                                <th>{{ getEntityName(program) }}</th>
                                <th v-if="program.custom_entity">Code</th>
                                <th class="text-center">Available Seats</th>
                                <th class="text-center pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="program.openings.length === 0">
                                <td colspan="5" class="text-center text-muted py-3">
                                    No openings listed yet.
                                </td>
                            </tr>
                            <tr v-for="(opening, idx) in program.openings" :key="opening.id">
                                <td class="ps-4 text-muted small">{{ idx + 1 }}</td>
                                <td>
                                    <span v-if="opening.subject" class="fw-semibold">{{ opening.subject.name }}</span>
                                    <span v-else class="text-muted italic">General / All</span>
                                </td>
                                <td v-if="program.custom_entity">
                                    <span v-if="opening.subject" class="badge bg-light text-dark border">{{ opening.subject.code }}</span>
                                    <span v-else>—</span>
                                </td>
                                <td class="text-center">
                                    <span class="seats-pill">{{ opening.seats }}</span>
                                </td>
                                <td class="text-center pe-4">
                                    <!-- Already applied -->
                                    <span v-if="getApplicationStatus(opening.id) && getApplicationStatus(opening.id) !== 'draft'"
                                        class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                        <i class="bi bi-check-circle-fill me-1"></i>Applied
                                    </span>
                                    <!-- Saved Draft -->
                                    <button v-else-if="getApplicationStatus(opening.id) === 'draft'" @click="apply(opening.id)"
                                        class="btn btn-warning btn-sm rounded-pill px-4 shadow-sm fw-semibold">
                                        Continue <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                    <!-- Expired -->
                                    <span v-else-if="isExpired(program.application_end_date)"
                                        class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                                        Closed
                                    </span>
                                    <!-- Can apply -->
                                    <button v-else @click="apply(opening.id)"
                                        class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                                        Apply Now <i class="bi bi-arrow-right ms-1"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td :colspan="program.custom_entity ? 3 : 2" class="text-end text-muted small fw-semibold ps-4">Total Seats</td>
                                <td class="text-center fw-bold">{{ totalSeats(program) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* ── Program Card ── */
.job-card {
    background: #fff;
    border: 1px solid #e4e7ec;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s, border-color 0.2s;
}
.job-card:hover,
.job-card--open {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    border-color: #c9d3e0;
}
.job-card--open {
    border-color: #0d6efd40;
}

/* ── Header ── */
.job-card__header {
    padding: 16px 20px;
    cursor: pointer;
    user-select: none;
    transition: background 0.15s;
}
.job-card__header:hover { background: #f8faff; }

.job-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0d6efd, #6ea8fe);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.job-card--open .job-icon {
    background: linear-gradient(135deg, #8b0000, #cc2222);
}

.job-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1a1a2e;
}

.job-meta {
    font-size: 0.76rem;
    color: #666;
}

.job-chevron {
    font-size: 0.9rem;
    color: #999;
    transition: transform 0.25s ease;
    flex-shrink: 0;
}
.job-chevron.rotated { transform: rotate(180deg); color: #0d6efd; }

/* ── Body ── */
.job-card__body {
    border-top: 1px solid #e8ecf2;
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Info Strip ── */
.info-strip {
    background: #f8faff;
    border-bottom: 1px solid #e8ecf2;
}
.info-strip__item {
    padding: 12px 20px;
    border-right: 1px solid #e8ecf2;
}
.info-strip__item:last-child { border-right: none; }
.info-strip__label {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #999;
    margin-bottom: 2px;
}
.info-strip__val {
    font-size: 0.88rem;
    font-weight: 700;
    color: #222;
}

/* ── Subject Table ── */
.subject-table th {
    background: #fafafa;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #555;
    border-bottom: 2px solid #e8ecf2;
    padding: 10px 16px;
}
.subject-table td {
    font-size: 0.85rem;
    padding: 10px 16px;
    border-color: #f0f2f5;
}

.seats-pill {
    display: inline-block;
    background: #8b0000;
    color: #fff;
    font-weight: 700;
    font-size: 0.8rem;
    padding: 3px 14px;
    border-radius: 100px;
}
</style>
