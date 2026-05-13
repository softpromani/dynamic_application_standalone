<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    entity: Object
});

const form = useForm({
    label: '',
    value: '',
    sort_order: 0,
});

const isEditing = ref(false);
const editingValueId = ref(null);

const resetForm = () => {
    form.reset();
    isEditing.value = false;
    editingValueId.value = null;
};

const editValue = (val) => {
    isEditing.value = true;
    editingValueId.value = val.id;
    form.label = val.label;
    form.value = val.value;
    form.sort_order = val.sort_order;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.custom-entities.values.update', editingValueId.value), {
            onSuccess: () => resetForm(),
        });
    } else {
        form.post(route('admin.custom-entities.values.store', props.entity.id), {
            onSuccess: () => resetForm(),
        });
    }
};

const deleteValue = (id) => {
    if (confirm('Are you sure you want to delete this value?')) {
        form.delete(route('admin.custom-entities.values.destroy', id));
    }
};
</script>

<template>
    <Head :title="`Values for ${entity.display_name}`" />

    <AdminLayout>
        <template #header>
            <div class="d-flex align-items-center gap-2">
                <Link :href="route('admin.custom-entities.index')" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                </Link>
                <span>Options for: {{ entity.display_name }}</span>
            </div>
        </template>

        <div class="row">
            <!-- Form -->
            <div class="col-md-4">
                <div class="card card-outline" :class="isEditing ? 'card-warning' : 'card-primary'">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ isEditing ? 'Edit Option' : 'Add New Option' }}</h5>
                    </div>
                    <form @submit.prevent="submit" class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Label (Visible to Applicant)</label>
                            <input v-model="form.label" type="text" class="form-control form-control-sm" 
                                placeholder="e.g. Physics Department" required />
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Value (Internal ID)</label>
                            <input v-model="form.value" type="text" class="form-control form-control-sm" 
                                placeholder="e.g. physics_dept" required />
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Sort Order</label>
                            <input v-model.number="form.sort_order" type="number" class="form-control form-control-sm" />
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm px-4" :class="isEditing ? 'btn-warning' : 'btn-primary'" :disabled="form.processing">
                                {{ isEditing ? 'Update Option' : 'Add Option' }}
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
                        <h5 class="card-title mb-0">Current Options</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Label</th>
                                        <th>Value</th>
                                        <th class="text-center">Order</th>
                                        <th class="text-end pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="entity.values.length === 0">
                                        <td colspan="5" class="text-center text-muted py-4">No options added yet.</td>
                                    </tr>
                                    <tr v-for="(val, idx) in entity.values" :key="val.id">
                                        <td class="text-muted small">{{ idx + 1 }}</td>
                                        <td class="fw-bold">{{ val.label }}</td>
                                        <td class="text-monospace small text-muted">{{ val.value }}</td>
                                        <td class="text-center">{{ val.sort_order }}</td>
                                        <td class="text-end pe-3">
                                            <button @click="editValue(val)" class="btn btn-xs btn-outline-primary me-1">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button @click="deleteValue(val.id)" class="btn btn-xs btn-outline-danger">
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
</style>
