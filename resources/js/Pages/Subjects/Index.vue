<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    subjects: Array,
});

const form = useForm({
    id: null,
    name: '',
    code: '',
    description: '',
});

const isEditing = ref(false);
const showModal = ref(false);

const openModal = (subject = null) => {
    if (subject) {
        isEditing.value = true;
        form.id = subject.id;
        form.name = subject.name;
        form.code = subject.code;
        form.description = subject.description;
    } else {
        isEditing.value = false;
        form.reset();
    }
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('subjects.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('subjects.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const deleteSubject = (id) => {
    if (confirm('Are you sure you want to delete this subject?')) {
        form.delete(route('subjects.destroy', id));
    }
};
</script>

<template>
    <Head title="Subjects Management" />

    <AdminLayout>
        <template #header>
            Subjects Management
        </template>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">List of Subjects</h5>
                <button @click="openModal()" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Subject
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px">#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th style="width: 150px" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(subject, index) in subjects" :key="subject.id">
                            <td>{{ index + 1 }}</td>
                            <td><span class="badge bg-secondary">{{ subject.code || 'N/A' }}</span></td>
                            <td class="font-weight-bold">{{ subject.name }}</td>
                            <td class="text-muted small">{{ subject.description }}</td>
                            <td class="text-center">
                                <button @click="openModal(subject)" class="btn btn-info btn-xs me-1">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button @click="deleteSubject(subject.id)" class="btn btn-danger btn-xs">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="subjects.length === 0">
                            <td colspan="5" class="text-center py-4 text-muted">No subjects found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal (Simple Implementation) -->
        <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-white border-bottom">
                        <h5 class="modal-title">{{ isEditing ? 'Edit Subject' : 'Add New Subject' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="modal-body bg-light">
                            <TextInput 
                                v-model="form.code"
                                label="Subject Code"
                                placeholder="e.g., MATH101"
                                :error="form.errors.code"
                            />
                            <TextInput 
                                v-model="form.name"
                                label="Subject Name"
                                placeholder="e.g., Higher Mathematics"
                                :error="form.errors.name"
                                required
                            />
                            <div class="mb-3">
                                <label class="form-label font-weight-bold text-sm">Description</label>
                                <textarea 
                                    v-model="form.description"
                                    class="form-control form-control-sm"
                                    rows="3"
                                    placeholder="Enter subject details..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-white border-top">
                            <button type="button" class="btn btn-secondary btn-sm" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm" :disabled="form.processing">
                                {{ isEditing ? 'Update Subject' : 'Create Subject' }}
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
