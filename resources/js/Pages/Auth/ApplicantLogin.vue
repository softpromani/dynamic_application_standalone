<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('applicant.login'));
};
</script>

<template>
    <Head title="Applicant Login" />

    <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
        <div class="card shadow-sm border-0" style="width: 100%; max-width: 400px;">
            <div class="card-header bg-white border-bottom text-center py-4">
                <h4 class="font-weight-bold text-red mb-0">Application Portal</h4>
                <p class="text-muted small mb-0">Applicant Sign In</p>
            </div>
            <form @submit.prevent="submit" class="card-body p-4">
                <TextInput v-model="form.email" type="email" label="Email Address" placeholder="Enter email" :error="form.errors.email" required />
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

                <div class="text-center mt-3 small">
                    Don't have an account? <Link :href="route('applicant.register')" class="text-red font-weight-bold">Register Now</Link>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.text-red {
    color: #dc3545;
}
</style>
