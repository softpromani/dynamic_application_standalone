<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@softpro-core/Layouts/AdminLayout.vue';

const props = defineProps({
    templates: Array,
});

const form = useForm({});

const deleteTemplate = (id) => {
    if (confirm('Delete this template? All linked fields will be removed.')) {
        form.delete(route('templates.destroy', id));
    }
};

const toggleStatus = (id) => {
    form.post(route('templates.toggle', id));
};

const formatDate = (d) =>
    d ? new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <Head title="Form Templates" />
    <AdminLayout>
        <template #header>Application Form Templates</template>

        <div class="templates-page">
            <!-- Header Bar -->
            <div class="page-actions d-flex justify-content-between align-items-center mb-4">
                <div>
                    <p class="text-muted mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Create step-by-step application form templates for applicants to fill up when applying to a job.
                    </p>
                </div>
                <Link :href="route('templates.create')" class="btn btn-create">
                    <i class="bi bi-plus-circle me-2"></i>Create Template
                </Link>
            </div>

            <!-- Templates Grid -->
            <div v-if="templates.length === 0" class="empty-state text-center py-5">
                <i class="bi bi-clipboard-x display-3 text-muted d-block mb-3"></i>
                <h5 class="text-muted">No templates yet</h5>
                <p class="text-muted small">Create your first application form template to get started.</p>
                <Link :href="route('templates.create')" class="btn btn-create mt-2">
                    <i class="bi bi-plus-circle me-2"></i>Create Template
                </Link>
            </div>

            <div v-else class="row g-4">
                <div v-for="tpl in templates" :key="tpl.id" class="col-md-6 col-xl-4">
                    <div class="tpl-card" :class="{ 'tpl-inactive': !tpl.is_active }">
                        <!-- Card Header -->
                        <div class="tpl-card-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span class="tpl-status-badge me-2" :class="tpl.is_active ? 'badge-active' : 'badge-inactive'">
                                        {{ tpl.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <h6 class="tpl-title mt-2 mb-1">{{ tpl.name }}</h6>
                                    <p class="tpl-job-tag mb-0" v-if="tpl.jobs_count > 0">
                                        <i class="bi bi-briefcase me-1"></i>Used in {{ tpl.jobs_count }} Program{{ tpl.jobs_count > 1 ? 's' : '' }}
                                    </p>
                                    <p class="tpl-job-tag mb-0 text-muted" v-else>
                                        <i class="bi bi-dash-circle me-1"></i>Not linked to any job
                                    </p>
                                </div>
                                <div class="tpl-icon-wrap">
                                    <i class="bi bi-file-earmark-text-fill"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="tpl-card-body">
                            <p class="tpl-desc text-muted small mb-3">
                                {{ tpl.description || 'No description provided.' }}
                            </p>
                            <div class="tpl-stats d-flex gap-3">
                                <div class="tpl-stat">
                                    <span class="tpl-stat-num">{{ tpl.fields_count }}</span>
                                    <span class="tpl-stat-label">Fields</span>
                                </div>
                                <div class="tpl-stat">
                                    <span class="tpl-stat-num">{{ formatDate(tpl.created_at) }}</span>
                                    <span class="tpl-stat-label">Created</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="tpl-card-footer d-flex gap-2">
                            <Link :href="route('templates.show', tpl.id)" class="btn btn-outline-primary btn-sm flex-grow-1">
                                <i class="bi bi-eye me-1"></i>Preview
                            </Link>
                            <Link :href="route('templates.edit', tpl.id)" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </Link>
                            <button @click="toggleStatus(tpl.id)" class="btn btn-sm"
                                :class="tpl.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                                :title="tpl.is_active ? 'Deactivate' : 'Activate'">
                                <i class="bi" :class="tpl.is_active ? 'bi-toggle-on' : 'bi-toggle-off'"></i>
                            </button>
                            <button @click="deleteTemplate(tpl.id)" class="btn btn-outline-danger btn-sm" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.templates-page { padding-bottom: 2rem; }

.btn-create {
    background: linear-gradient(135deg, #8b0000, #c0392b);
    color: #fff;
    border: none;
    padding: 9px 22px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-create:hover { background: linear-gradient(135deg, #6b0000, #a93226); color: #fff; transform: translateY(-1px); }

.empty-state { background: #fff; border-radius: 14px; padding: 60px 40px; border: 2px dashed #e0e0e0; }

/* Template Card */
.tpl-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #eaeaea;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.tpl-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(139,0,0,0.1); }
.tpl-inactive { opacity: 0.65; }

.tpl-card-header {
    background: linear-gradient(135deg, #8b0000 0%, #b71c1c 100%);
    padding: 20px;
    color: #fff;
}
.tpl-title { font-size: 1rem; font-weight: 700; color: #fff; line-height: 1.3; }
.tpl-job-tag { font-size: 0.78rem; color: rgba(255,255,255,0.75); }
.tpl-icon-wrap { font-size: 2rem; color: rgba(255,255,255,0.2); flex-shrink: 0; }

.tpl-status-badge {
    font-size: 0.65rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 100px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.badge-active { background: rgba(255,215,0,0.25); color: #ffd700; border: 1px solid rgba(255,215,0,0.4); }
.badge-inactive { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.5); border: 1px solid rgba(255,255,255,0.2); }

.tpl-card-body { padding: 18px 20px; }
.tpl-desc { line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.tpl-stats { gap: 16px; }
.tpl-stat { display: flex; flex-direction: column; }
.tpl-stat-num { font-size: 0.95rem; font-weight: 700; color: #8b0000; }
.tpl-stat-label { font-size: 0.7rem; color: #999; text-transform: uppercase; letter-spacing: 0.5px; }

.tpl-card-footer { padding: 12px 20px; border-top: 1px solid #f0f0f0; background: #fafafa; }
</style>
