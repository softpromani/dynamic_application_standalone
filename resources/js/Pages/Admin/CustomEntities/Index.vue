<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    entities: Array
});

const form = useForm({
    display_name: '',
    description: '',
});

const isEditing = ref(false);
const editingEntityId = ref(null);

const resetForm = () => {
    form.reset();
    isEditing.value = false;
    editingEntityId.value = null;
};

const editEntity = (entity) => {
    isEditing.value = true;
    editingEntityId.value = entity.id;
    form.display_name = entity.display_name;
    form.description = entity.description;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.custom-entities.update', editingEntityId.value), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('admin.custom-entities.store'), {
            onSuccess: () => resetForm(),
        });
    }
};

const deleteEntity = (id) => {
    if (confirm('Are you sure? This will delete all values and fields associated with this entity.')) {
        form.delete(route('admin.custom-entities.destroy', id));
    }
};
</script>

<template>
    <Head title="Custom Entities" />

    <AdminLayout>
        <template #header>Master Entities / Select Options</template>

        <div class="row">
            <!-- Form -->
            <div class="col-md-4">
                <div class="card card-outline" :class="isEditing ? 'card-warning' : 'card-primary'">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ isEditing ? 'Edit Entity' : 'Create New Entity' }}</h5>
                    </div>
                    <form @submit.prevent="submit" class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Display Name</label>
                            <input v-model="form.display_name" type="text" class="form-control form-control-sm" 
                                placeholder="e.g. Department, College Name..." required />
                            <div v-if="form.errors.display_name" class="text-danger small">{{ form.errors.display_name }}</div>
                            <div class="form-text x-small">A unique name for this list of options.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea v-model="form.description" class="form-control form-control-sm" rows="3" 
                                placeholder="Optional description..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm px-4" :class="isEditing ? 'btn-warning' : 'btn-primary'" :disabled="form.processing">
                                {{ isEditing ? 'Update Entity' : 'Create Entity' }}
                            </button>
                            <button v-if="isEditing" type="button" @click="resetForm" class="btn btn-sm btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- List -->
            <div class="col-md-8">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Existing Entities</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Internal Key</th>
                                        <th class="text-center">Values Count</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="entities.length === 0">
                                        <td colspan="4" class="text-center text-muted py-4">No custom entities defined yet.</td>
                                    </tr>
                                    <tr v-for="entity in entities" :key="entity.id">
                                        <td class="fw-bold">{{ entity.display_name }}</td>
                                        <td class="text-monospace small text-muted">{{ entity.name }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-info rounded-pill px-3">{{ entity.values_count }}</span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <Link :href="route('admin.custom-entities.show', entity.id)" class="btn btn-xs btn-outline-success me-1" title="Manage Values">
                                                <i class="bi bi-list-task"></i> Values
                                            </Link>
                                            <button @click="editEntity(entity)" class="btn btn-xs btn-outline-primary me-1">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button @click="deleteEntity(entity.id)" class="btn btn-xs btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.btn-xs { padding: 1px 6px; font-size: 12px; }
.x-small { font-size: 0.75rem; }
</style>
