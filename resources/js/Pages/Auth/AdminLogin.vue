<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(window.location.href);
};
</script>

<template>
    <Head title="Admin Login" />

    <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
        <div class="card shadow-sm border-0" style="width: 100%; max-width: 400px;">
            <div class="card-header bg-white border-bottom text-center py-4">
                <h4 class="font-weight-bold text-red mb-0">Recruitment Portal</h4>
                <p class="text-muted small mb-0">Administrator Sign In</p>
            </div>
            <form @submit.prevent="submit" class="card-body p-4">
                <!-- Session Status / Errors -->
                <div v-if="$page.props.flash?.error" class="alert alert-danger px-3 py-2 text-sm">
                    {{ $page.props.flash.error }}
                </div>

                <TextInput v-model="form.email" type="email" label="Admin Email" placeholder="Enter email" :error="form.errors.email" required />
                <TextInput v-model="form.password" type="password" label="Password" placeholder="Enter password" :error="form.errors.password" required />

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" v-model="form.remember" id="remember">
                    <label class="form-check-label small" for="remember">
                        Remember Me
                    </label>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm" :disabled="form.processing">Login</button>
                </div>
            </form>
            <div class="card-footer bg-light text-center py-3">
                 <Link :href="route('applicant.login')" class="text-muted small text-decoration-none">
                     <i class="bi bi-person"></i> Go to Applicant Login
                 </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.text-red {
    color: #dc3545;
}
.text-sm {
    font-size: 0.875rem;
}
</style>
