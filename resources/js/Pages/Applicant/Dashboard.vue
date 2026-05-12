<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    applications: Array
});

const page = usePage();
const applicant = page.props.auth?.applicant;
</script>

<template>
    <Head title="Applicant Dashboard" />

    <AdminLayout>
        <template #header>
            Welcome, {{ applicant?.name }}
        </template>

        <div class="row" v-if="applicant">
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-4 border-0">
                    <div class="mb-3">
                        <img
                            :src="applicant.profile_photo_path ? `/storage/${applicant.profile_photo_path}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(applicant.name)}&background=dc3545&color=fff&size=128`"
                            class="rounded-circle border shadow-sm"
                            style="width: 100px; height: 100px; object-fit: cover;"
                        />
                    </div>
                    <h5 class="mb-1 fw-bold">{{ applicant.name }}</h5>
                    <p class="text-muted small mb-1">{{ applicant.email }}</p>
                    <p v-if="applicant.phone" class="text-muted small mb-1"><i class="bi bi-phone me-1"></i>{{ applicant.phone }}</p>
                    <p class="text-muted small mb-3">
                        <span v-if="applicant.gender">{{ applicant.gender }}</span>
                        <span v-if="applicant.gender && applicant.category"> | </span>
                        <span v-if="applicant.category">{{ applicant.category }}</span>
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <span v-if="applicant.profile_photo_path" class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle-fill me-1"></i> Photo
                        </span>
                        <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle">
                            <i class="bi bi-exclamation-triangle me-1"></i> No Photo
                        </span>
                        <span v-if="applicant.signature_path" class="badge bg-success-subtle text-success border border-success-subtle">
                            <i class="bi bi-check-circle-fill me-1"></i> Signature
                        </span>
                        <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle">
                            <i class="bi bi-exclamation-triangle me-1"></i> No Signature
                        </span>
                    </div>
                    <Link :href="route('applicant.profile-setup')" class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                        <i class="bi bi-pencil-square me-1"></i> Edit Profile
                    </Link>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Profile Completion Warning -->
                <div v-if="$page.props.hasProfileTemplate && !$page.props.isProfileComplete" class="alert alert-danger shadow-sm border-0 d-flex align-items-center p-4 mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-3 text-danger me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Profile Incomplete</h6>
                        <p class="mb-0 small">You must complete your profile before you can apply for any programs. Click the "Edit Profile" button to fill in the required details.</p>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">Application Status</h6>
                        <Link :href="route('applicant.browse-programs')" 
                              class="btn btn-primary btn-sm rounded-pill px-3"
                              :class="{ 'disabled': $page.props.hasProfileTemplate && !$page.props.isProfileComplete }">
                            <i class="bi bi-search me-1"></i> Browse Programs
                        </Link>
                    </div>
                    <div v-if="!applications || applications.length === 0" class="card-body py-5 text-center text-muted">
                        <i class="bi bi-inbox display-4 mb-3 d-block text-secondary opacity-50"></i>
                        <p>You haven't applied for any programs yet.</p>
                    </div>
                    <div v-else class="list-group list-group-flush">
                        <div v-for="app in applications" :key="app.id" class="list-group-item p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="mb-1 fw-bold">{{ app.opening?.job?.title || 'Program' }} <span v-if="app.opening?.subject">({{ app.opening.subject.name }})</span></h6>
                                    <small class="text-muted">Application No: {{ app.application_no }}</small>
                                </div>
                                    <span class="badge" 
                                        :class="{
                                            'bg-success': app.status === 'paid',
                                            'bg-warning text-dark': app.status === 'pending',
                                            'bg-danger': app.status === 'failed',
                                            'bg-info': !app.opening?.job?.is_payable
                                        }">
                                        {{ !app.opening?.job?.is_payable ? 'FREE' : (app.status === 'paid' ? 'PAID' : app.status === 'failed' ? 'FAILED' : 'PAYMENT PENDING') }}
                                    </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span v-if="app.opening?.job?.is_payable && (app.status === 'pending' || app.status === 'failed')" class="text-danger fw-bold me-3">Payment Pending: ₹{{ app.total_amount }}</span>
                                    <span v-else-if="app.opening?.job?.is_payable" class="text-success fw-bold me-3">Fee: ₹{{ app.total_amount }}</span>
                                    <span v-else class="text-info fw-bold me-3">Free Application</span>
                                </div>
                                <div>
                                    <!-- Only show if form is still a draft -->
                                    <Link v-if="app.form_status === 'draft'" 
                                          :href="route('applicant.apply.form', app.opening_id)" 
                                          class="btn btn-sm btn-outline-primary me-2">
                                        Continue Application
                                    </Link>
                                    <!-- Pay Now: form submitted AND payment not yet done (pending/failed) -->
                                    <a v-if="app.form_status === 'submitted' && app.status !== 'paid'" 
                                          :href="route('applicant.payment.initiate', app.id)" 
                                          class="btn btn-sm btn-success rounded-pill px-3 me-2">
                                        <i class="bi bi-credit-card me-1"></i>
                                        {{ app.status === 'failed' ? 'Retry Payment' : 'Pay Now' }}
                                    </a>
                                    <!-- Print: form is submitted (regardless of payment status) -->
                                    <a v-if="app.form_status === 'submitted'" 
                                          :href="route('applicant.application.print', app.id)" 
                                          target="_blank"
                                          class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                        <i class="bi bi-printer me-1"></i> Print Form
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Payment History -->
                            <div class="mt-4 border-top pt-3" v-if="app.transactions && app.transactions.length > 0">
                                <h6 class="fw-bold mb-2 text-muted small">Payment History</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Transaction ID</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="txn in app.transactions" :key="txn.id">
                                                <td class="align-middle">{{ new Date(txn.created_at).toLocaleString('en-IN') }}</td>
                                                <td class="align-middle">{{ txn.merchant_transaction_id || txn.id }}</td>
                                                <td class="align-middle">₹{{ txn.amount }}</td>
                                                <td class="align-middle">
                                                    <span class="badge" 
                                                        :class="{
                                                            'bg-success': txn.status === 'success',
                                                            'bg-warning text-dark': txn.status === 'pending',
                                                            'bg-danger': txn.status === 'failed' || txn.status === 'aborted',
                                                            'bg-secondary': !['success', 'pending', 'failed', 'aborted'].includes(txn.status)
                                                        }">
                                                        {{ (txn.status || 'unknown').toUpperCase() }}
                                                    </span>
                                                </td>
                                                <td class="align-middle">
                                                    <Link v-if="txn.status !== 'success'" 
                                                          :href="route('applicant.payment.refresh-status', txn.merchant_transaction_id || txn.id)" 
                                                          method="post" 
                                                          as="button" 
                                                          preserve-scroll
                                                          class="btn btn-sm btn-outline-secondary py-0 px-2" 
                                                          title="Refresh Status">
                                                        <i class="bi bi-arrow-clockwise"></i> Refresh
                                                    </Link>
                                                    <span v-else class="text-muted"><i class="bi bi-check-lg"></i></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>
