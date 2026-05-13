<script setup>
import { computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import 'bootstrap';
import 'admin-lte/dist/js/adminlte.js';

const page = usePage();

// An applicant page is any page whose component path starts with 'Applicant/'
const isApplicant = computed(() =>
    page.component.startsWith('Applicant/') && !!page.props.auth?.applicant
);

const isAdmin = computed(() =>
    !isApplicant.value && !!page.props.auth?.user
);

const currentUser = computed(() =>
    isApplicant.value ? page.props.auth?.applicant : page.props.auth?.user
);

onMounted(() => {
    document.documentElement.setAttribute('data-bs-theme', 'light');
});
</script>

<template>
    <div class="app-wrapper">
        <!-- Header -->
        <nav class="app-header navbar navbar-expand bg-white shadow-sm">
            <div class="container-fluid">
                <!-- Start navbar links -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <Link href="/" class="nav-link">Home</Link>
                    </li>
                </ul>

                <!-- End navbar links -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </a>
                    </li>
                    <li class="nav-item dropdown user-menu" v-if="currentUser">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-md-inline ms-1">{{ currentUser.name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow">
                            <li class="user-header bg-light">
                                <i class="bi bi-person-circle display-4 text-secondary"></i>
                                <p>
                                    {{ currentUser.name }}
                                    <small>{{ currentUser.email }}</small>
                                </p>
                            </li>
                            <li class="user-footer">
                                <Link v-if="isApplicant"
                                    :href="route('applicant.logout')"
                                    method="post"
                                    as="button"
                                    class="btn btn-danger btn-sm float-end">
                                    <i class="bi bi-box-arrow-right me-1"></i> Sign out
                                </Link>
                                <Link v-else
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="btn btn-danger btn-sm float-end">
                                    <i class="bi bi-box-arrow-right me-1"></i> Sign out
                                </Link>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Sidebar -->
        <aside class="app-sidebar bg-white border-end" data-bs-theme="light">
            <div class="sidebar-brand">
                <Link href="/" class="brand-link px-3 text-decoration-none">
                    <span class="brand-text font-weight-light text-red fw-bold text-uppercase">{{ $page.props.tenant?.name || 'Portal' }}</span>
                </Link>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2 text-sm">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                        <!-- APPLICANT MENU -->
                        <template v-if="isApplicant">
                            <li class="nav-header">APPLICANT</li>
                            <li class="nav-item">
                                <Link :href="route('applicant.dashboard')" class="nav-link"
                                    :class="{ active: $page.component === 'Applicant/Dashboard' }">
                                    <i class="nav-icon bi bi-speedometer2"></i>
                                    <p>Dashboard</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('applicant.browse-programs')" class="nav-link"
                                    :class="{ active: $page.component === 'Applicant/BrowsePrograms' }">
                                    <i class="nav-icon bi bi-search"></i>
                                    <p>Browse Programs</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('applicant.profile-setup')" class="nav-link"
                                    :class="{ active: $page.component === 'Applicant/ProfileSetup' }">
                                    <i class="nav-icon bi bi-person-badge"></i>
                                    <p>My Profile</p>
                                </Link>
                            </li>
                        </template>

                        <!-- ADMIN MENU -->
                        <template v-else-if="isAdmin">
                            <li class="nav-header">ADMINISTRATION</li>
                            <li class="nav-item">
                                <Link :href="route('admin.dashboard')" class="nav-link"
                                    :class="{ active: $page.component === 'Admin/Dashboard' }">
                                    <i class="nav-icon bi bi-speedometer2"></i>
                                    <p>Dashboard</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('programs.index')" class="nav-link"
                                    :class="{ active: $page.component.startsWith('Programs/') }">
                                    <i class="nav-icon bi bi-briefcase"></i>
                                    <p>Programs</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('templates.index')" class="nav-link"
                                    :class="{ active: $page.component.startsWith('Templates/') }">
                                    <i class="nav-icon bi bi-ui-checks-grid"></i>
                                    <p>Form Templates</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('applications.index')" class="nav-link"
                                    :class="{ active: $page.component.startsWith('Applications/') }">
                                    <i class="nav-icon bi bi-file-earmark-person"></i>
                                    <p>Applications</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('admin.reports.index')" class="nav-link"
                                    :class="{ active: $page.component === 'Admin/Reports' }">
                                    <i class="nav-icon bi bi-file-earmark-bar-graph"></i>
                                    <p>Reports & Exports</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('news.index')" class="nav-link"
                                    :class="{ active: $page.component.startsWith('News/') }">
                                    <i class="nav-icon bi bi-megaphone"></i>
                                    <p>News & Notifications</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('admin.virtual-columns.index')" class="nav-link"
                                    :class="{ active: $page.component.startsWith('Admin/VirtualColumns') }">
                                    <i class="nav-icon bi bi-database-add"></i>
                                    <p>Search Columns</p>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link :href="route('admin.custom-entities.index')" class="nav-link"
                                    :class="{ active: $page.component.startsWith('Admin/CustomEntities') }">
                                    <i class="nav-icon bi bi-list-nested"></i>
                                    <p>Master Entities</p>
                                </Link>
                            </li>
                            <li class="nav-header">SETTINGS</li>
                            <li class="nav-item">
                                <Link :href="route('admin.settings.index')" class="nav-link"
                                    :class="{ active: $page.component.startsWith('Admin/Settings') }">
                                    <i class="nav-icon bi bi-gear"></i>
                                    <p>Tenant Settings</p>
                                </Link>
                            </li>
                        </template>

                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="app-main bg-light">
            <div class="app-content-header py-3">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h4 class="mb-0 text-dark font-weight-bold"><slot name="header" /></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    <!-- Flash Messages -->
                    <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ $page.props.flash.success }}
                        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                    </div>
                    <div v-if="$page.props.flash?.error" class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $page.props.flash.error }}
                        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
                    </div>
                    <slot />
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="app-footer bg-white border-top py-2">
            <div class="float-end d-none d-sm-inline">v1.0.0</div>
            <small class="text-muted">Copyright &copy; 2024 <a href="#" class="text-red text-decoration-none">Application Portal</a>.</small>
        </footer>
    </div>
</template>

<style>
.text-red {
    color: #dc3545 !important;
}
.text-sm {
    font-size: 0.9rem;
}
.nav-link p {
    margin-bottom: 0;
}
.user-header {
    padding: 1rem;
    text-align: center;
}
.user-header p {
    margin-top: .5rem;
    font-weight: 600;
    margin-bottom: 0;
}
.user-header small {
    display: block;
    font-weight: 400;
    font-size: 0.8rem;
    color: #666;
}
.user-footer {
    padding: .75rem 1rem;
    background: #f8f9fa;
}
</style>
