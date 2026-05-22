<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({});

const resend = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <Head title="Verify Email" />
    <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
        <div class="card shadow-sm border-0" style="width: 100%; max-width: 500px;">
            <div class="card-header bg-white border-bottom text-center py-4">
                <h4 class="font-weight-bold text-red mb-0">Verify Your Email</h4>
                <p class="text-muted small mb-0">A verification link has been sent to your email address.</p>
            </div>
            <div class="card-body p-4">
                <div v-if="$page.props.flash?.status" class="alert alert-success" role="alert">
                    {{ $page.props.flash.status }}
                </div>
                <p class="mb-3">If you did not receive the email, you can request another one.</p>
                <button @click="resend" :disabled="form.processing" class="btn btn-primary w-100 shadow-sm">
                    Resend Verification Email
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.text-red { color: #dc3545; }
</style>
