<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TextInput from '@softpro-core/Components/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('applicant.register'));
};
</script>

<template>
    <Head title="Applicant Registration" />

    <div class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
        <div class="card shadow-sm border-0" style="width: 100%; max-width: 400px;">
            <div class="card-header bg-white border-bottom text-center py-4">
                <h4 class="font-weight-bold text-red mb-0">Application Portal</h4>
                <p class="text-muted small mb-0">Applicant Registration</p>
            </div>
            <form @submit.prevent="submit" class="card-body p-4">
                <TextInput v-model="form.name" label="Full Name" placeholder="Your full name" :error="form.errors.name" required />
                <TextInput v-model="form.email" type="email" label="Email Address" placeholder="Email" :error="form.errors.email" required />
                <TextInput v-model="form.password" type="password" label="Password" placeholder="Minimum 8 characters" :error="form.errors.password" required />
                <TextInput v-model="form.password_confirmation" type="password" label="Confirm Password" placeholder="Repeat password" required />

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary w-100 shadow-sm" :disabled="form.processing">Create Account</button>
                </div>

                <div class="text-center mt-3 small">
                    Already have an account? <Link :href="route('applicant.login')" class="text-red font-weight-bold">Sign In</Link>
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
