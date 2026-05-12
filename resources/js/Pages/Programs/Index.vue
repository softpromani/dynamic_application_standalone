<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    programs: Array,
});

const form = useForm({});

const deleteProgram = (id) => {
    if (confirm('Deleting this program will also remove all associated openings. Proceed?')) {
        form.delete(route('programs.destroy', id));
    }
};
</script>

<template>
    <Head title="Programs Management" />

    <AdminLayout>
        <template #header>
            Programs
        </template>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Active Programs</h5>
                <Link :href="route('programs.create')" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Create New Program
                </Link>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th>Program ID</th>
                            <th>Title</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Last Payment</th>
                            <th>Fee</th>
                            <th>Openings</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="program in programs" :key="program.id">
                            <td class="font-weight-bold">{{ program.job_code }}</td>
                            <td>{{ program.title }}</td>
                            <td>{{ new Date(program.application_start_date).toLocaleDateString() }}</td>
                            <td>{{ new Date(program.application_end_date).toLocaleDateString() }}</td>
                            <td>{{ new Date(program.last_payment_date).toLocaleDateString() }}</td>
                            <td><span class="text-success font-weight-bold">₹{{ program.application_fee }}</span></td>
                            <td>
                                <Link :href="route('programs.edit', program.id)" class="badge bg-info text-decoration-none">
                                    {{ program.openings_count }} Openings
                                </Link>
                            </td>
                            <td class="text-center text-nowrap">
                                <Link :href="route('programs.edit', program.id)" class="btn btn-info btn-xs me-1">
                                    <i class="bi bi-pencil"></i>
                                </Link>
                                <button @click="deleteProgram(program.id)" class="btn btn-danger btn-xs">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.btn-xs {
    padding: 1px 5px;
    font-size: 12px;
}
</style>
