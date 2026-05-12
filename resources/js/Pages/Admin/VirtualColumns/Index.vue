<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    virtualColumns: Array,
    sampleKeys: Array,
});

const form = useForm({
    column_name: '',
    json_key: '',
    data_type: 'DECIMAL(10,2)',
});

const submit = () => {
    form.post(route('virtual-columns.store'), {
        onSuccess: () => form.reset(),
    });
};

const deleteColumn = (name) => {
    if (confirm(`Are you sure you want to drop the virtual column '${name}'? This will remove the ability to sort by this field but will NOT delete the actual data from the JSON responses.`)) {
        form.delete(route('virtual-columns.destroy', name));
    }
};

const setKey = (key) => {
    form.json_key = key;
    if (!form.column_name) {
        form.column_name = 'v_' + key.toLowerCase().replace(/[^a-z0-9]/g, '_');
    }
};
</script>

<template>
    <Head title="Virtual Search Columns" />

    <AdminLayout>
        <template #header>
            Virtual Search Columns
        </template>

        <div class="row">
            <div class="col-md-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Create New Search Column</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info small py-2 mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Use this tool to make specific data inside the "JSON responses" searchable and sortable in the database.
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="form-label">Column Name (in DB)</label>
                                <input v-model="form.column_name" type="text" class="form-control" placeholder="e.g. v_phd_year" required>
                                <div class="form-text small text-muted">Must be lowercase with underscores. We recommend prefixing with <code>v_</code>.</div>
                                <div v-if="form.errors.column_name" class="text-danger small">{{ form.errors.column_name }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">JSON Key (Field ID)</label>
                                <input v-model="form.json_key" type="text" class="form-control" placeholder="The internal ID of the field" required>
                                <div class="mt-2" v-if="sampleKeys.length">
                                    <div class="small text-muted mb-1">Detected keys from latest application:</div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button type="button" v-for="key in sampleKeys" :key="key" 
                                            @click="setKey(key)"
                                            class="btn btn-outline-secondary btn-xs">
                                            {{ key }}
                                        </button>
                                    </div>
                                </div>
                                <div v-if="form.errors.json_key" class="text-danger small">{{ form.errors.json_key }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Data Type</label>
                                <select v-model="form.data_type" class="form-select">
                                    <option value="DECIMAL(10,2)">Decimal (Marks/Percentage)</option>
                                    <option value="INTEGER">Integer (Years/Counts)</option>
                                    <option value="VARCHAR(255)">Short Text</option>
                                    <option value="DATE">Date</option>
                                </select>
                                <div v-if="form.errors.data_type" class="text-danger small">{{ form.errors.data_type }}</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                                    Create Virtual Column & Index
                                </button>
                            </div>
                        </form>

                        <div v-if="$page.props.flash.manual_sql" class="mt-4">
                            <div class="alert alert-warning border-warning">
                                <h6 class="alert-heading fw-bold"><i class="bi bi-shield-lock me-2"></i>Permission Required</h6>
                                <p class="small mb-2">Your database user doesn't have permissions to ALTER tables. Please ask your DB Admin to run this command manually:</p>
                                <div class="bg-dark text-light p-2 rounded small text-break font-monospace">
                                    {{ $page.props.flash.manual_sql }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Existing Virtual Columns</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Field (Column)</th>
                                    <th>Type</th>
                                    <th>Definition</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="col in virtualColumns" :key="col.Field">
                                    <td class="fw-bold text-red">{{ col.Field }}</td>
                                    <td>{{ col.Type }}</td>
                                    <td><code class="x-small">{{ col.Extra }}</code></td>
                                    <td class="text-center">
                                        <button @click="deleteColumn(col.Field)" class="btn btn-danger btn-xs">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="virtualColumns.length === 0">
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        No custom virtual columns created yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.btn-xs {
    padding: 1px 5px;
    font-size: 11px;
}
.x-small {
    font-size: 10px;
}
.text-red {
    color: #8b0000;
}
</style>
