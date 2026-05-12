<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Modal } from 'bootstrap';

const props = defineProps({
    news: Array,
});

const isEditing = ref(false);
const editId = ref(null);
const modalRef = ref(null);

const form = useForm({
    title: '',
    type: 'link',
    file: null,
    link_url: '',
    is_active: true,
    sort_order: 0,
});

const openModal = (item = null) => {
    if (item) {
        isEditing.value = true;
        editId.value = item.id;
        form.title = item.title;
        form.type = item.type;
        form.link_url = item.link_url || '';
        form.is_active = !!item.is_active;
        form.sort_order = item.sort_order;
        form.file = null;
    } else {
        isEditing.value = false;
        editId.value = null;
        form.reset();
    }
    const modal = new Modal(modalRef.value);
    modal.show();
};

const submit = () => {
    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'put'
        })).post(route('news.update', editId.value), {
            forceFormData: true,
            onSuccess: () => {
                Modal.getInstance(modalRef.value).hide();
                form.reset();
            },
        });
    } else {
        form.post(route('news.store'), {
            onSuccess: () => {
                Modal.getInstance(modalRef.value).hide();
                form.reset();
            },
            onError: (errors) => {
                if (errors.file) {
                    alert('Upload Error: ' + errors.file);
                }
            }
        });
    }
};

const deleteNews = (id) => {
    if (confirm('Are you sure you want to delete this news item?')) {
        form.delete(route('news.destroy', id));
    }
};
</script>

<template>
    <Head title="News & Notifications" />

    <AdminLayout>
        <template #header>
            News & Notifications
        </template>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Manage News</h5>
                <button @click="openModal()" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add News
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px">Order</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in news" :key="item.id">
                            <td>{{ item.sort_order }}</td>
                            <td>{{ item.title }}</td>
                            <td>
                                <span class="badge" :class="item.type === 'file' ? 'bg-info' : 'bg-success'">
                                    {{ item.type }}
                                </span>
                            </td>
                            <td>
                                <a v-if="item.url" :href="item.url" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                    {{ item.type === 'file' ? 'View File' : item.link_url }}
                                </a>
                                <span v-else class="text-muted small">No target</span>
                            </td>
                            <td>
                                <span class="badge" :class="item.is_active ? 'bg-success' : 'bg-secondary'">
                                    {{ item.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button @click="openModal(item)" class="btn btn-info btn-xs me-1">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button @click="deleteNews(item.id)" class="btn btn-danger btn-xs">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="news.length === 0">
                            <td colspan="6" class="text-center py-4 text-muted">
                                No news items found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" ref="modalRef" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submit">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ isEditing ? 'Edit News' : 'Add News' }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Global Error Summary -->
                            <div v-if="Object.keys(form.errors).length > 0" class="alert alert-danger py-2 px-3 mb-3 small">
                                <ul class="mb-0 ps-3">
                                    <li v-for="err in form.errors" :key="err">{{ err }}</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input v-model="form.title" type="text" class="form-control" required>
                                <div v-if="form.errors.title" class="text-danger small">{{ form.errors.title }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select v-model="form.type" class="form-select">
                                    <option value="link">Link (URL)</option>
                                    <option value="file">File (PDF/Image)</option>
                                </select>
                            </div>

                            <div v-if="form.type === 'file'" class="mb-3">
                                <label class="form-label">Upload File</label>
                                <input type="file" @input="form.file = $event.target.files[0]" class="form-control">
                                <div class="form-text small text-muted">Leave empty to keep existing file when editing.</div>
                                <div v-if="form.errors.file" class="text-danger small">{{ form.errors.file }}</div>
                            </div>

                            <div v-if="form.type === 'link'" class="mb-3">
                                <label class="form-label">Link URL</label>
                                <input v-model="form.link_url" type="url" class="form-control" placeholder="https://example.com" :required="form.type === 'link'">
                                <div v-if="form.errors.link_url" class="text-danger small">{{ form.errors.link_url }}</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Sort Order</label>
                                    <input v-model="form.sort_order" type="number" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label d-block">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input v-model="form.is_active" class="form-check-input" type="checkbox" id="flexSwitchCheckChecked">
                                        <label class="form-check-input-label" for="flexSwitchCheckChecked">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                {{ isEditing ? 'Update News' : 'Save News' }}
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
.text-truncate {
    max-width: 100%;
}
</style>
