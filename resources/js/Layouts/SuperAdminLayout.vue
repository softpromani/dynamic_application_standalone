<script setup>
import { computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import 'bootstrap';
import 'admin-lte/dist/js/adminlte.js';

const page = usePage();

const currentUser = computed(() => page.props.auth?.user);

onMounted(() => {
    document.documentElement.setAttribute('data-bs-theme', 'light');
});
</script>

<template>
    <div class="app-wrapper">
        <!-- Header -->
        <nav class="app-header navbar navbar-expand bg-white shadow-sm">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown user-menu" v-if="currentUser">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-md-inline ms-1">{{ currentUser.name }} (Super Admin)</span>
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
                                <Link :href="route('superadmin.logout')"
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
                <Link href="/" class="brand-link px-3">
                    <span class="brand-text font-weight-light text-primary">Super<b>Admin</b></span>
                </Link>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2 text-sm">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                        <li class="nav-header">MANAGEMENT</li>
                        <li class="nav-item">
                            <Link :href="route('superadmin.dashboard')" class="nav-link"
                                :class="{ active: $page.component === 'SuperAdmin/Dashboard' }">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <p>Dashboard</p>
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link :href="route('superadmin.tenants.index')" class="nav-link"
                                :class="{ active: $page.component.startsWith('SuperAdmin/Tenants') }">
                                <i class="nav-icon bi bi-building"></i>
                                <p>Tenants (Panels)</p>
                            </Link>
                        </li>
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
    </div>
</template>

<style>
.text-primary {
    color: #0d6efd !important;
}
.text-sm {
    font-size: 0.9rem;
}
.nav-link p {
    margin-bottom: 0;
}
</style>
