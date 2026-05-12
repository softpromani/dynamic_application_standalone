<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    openings: Array,
    programs: Array,
    subjects: Array,
});

const form = useForm({
    id: null,
    program_id: '',
    subject_id: '',
    seats: 1,
});

const isEditing = ref(false);
const showModal = ref(false);

const openModal = (opening = null) => {
    if (opening) {
        isEditing.value = true;
        form.id = opening.id;
        form.program_id = opening.program_id;
        form.subject_id = opening.subject_id;
        form.seats = opening.seats;
    } else {
        isEditing.value = false;
        form.reset();
    }
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('openings.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('openings.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const deleteVacancy = (id) => {
    if (confirm('Remove this opening?')) {
        form.delete(route('openings.destroy', id));
    }
};
</script>

<template>
    <Head title="Vacancies Management" />

    <AdminLayout>
        <template #header>
            Subject-wise Vacancies
        </template>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Seat Allocations</h5>
                <button @click="openModal()" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Opening
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Program ID</th>
                            <th>Subject</th>
                            <th>Total Seats</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="opening in openings" :key="opening.id">
                            <td>
                                <div class="font-weight-bold">{{ opening.program.job_code }}</div>
                                <small class="text-muted">{{ opening.program.title }}</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary me-2">{{ opening.subject.code }}</span>
                                {{ opening.subject.name }}
                            </td>
                            <td>
                                <span class="badge bg-danger rounded-pill px-3">{{ opening.seats }} Seats</span>
                            </td>
                            <td class="text-center">
                                <button @click="openModal(opening)" class="btn btn-info btn-xs me-1">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button @click="deleteVacancy(opening.id)" class="btn btn-danger btn-xs">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="openings.length === 0">
                            <td colspan="4" class="text-center py-4 text-muted">No openings allocated yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ isEditing ? 'Edit Seats' : 'Add Opening' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body bg-light">
                            <div v-if="!isEditing">
                                <SelectInput 
                                    v-model="form.program_id"
                                    label="Select Program Post"
                                    :options="programs.map(j => ({ value: j.id, label: `${j.job_code} - ${j.title}` }))"
                                    :error="form.errors.program_id"
                                    required
                                />
                                <SelectInput 
                                    v-model="form.subject_id"
                                    label="Select Subject"
                                    :options="subjects.map(s => ({ value: s.id, label: s.name }))"
                                    :error="form.errors.subject_id"
                                    required
                                />
                            </div>
                            <div v-else class="mb-3">
                                <p class="text-muted small">Editing seats for <b>{{ openings.find(v => v.id === form.id)?.subject.name }}</b> under <b>{{ openings.find(v => v.id === form.id)?.program.job_code }}</b></p>
                            </div>

                            <TextInput 
                                v-model="form.seats"
                                type="number"
                                label="Number of Seats"
                                placeholder="Enter available seats"
                                :error="form.errors.seats"
                                required
                            />
                        </div>
                        <div class="modal-footer bg-white">
                            <button type="button" class="btn btn-secondary btn-sm" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="form.processing">
                                {{ isEditing ? 'Update Seats' : 'Add Opening' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.btn-xs {
    padding: 1px 5px;
    font-size: 12px;
}
.modal {
    z-index: 1050;
}
</style>
