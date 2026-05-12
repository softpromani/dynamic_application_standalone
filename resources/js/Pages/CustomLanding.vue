<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    htmlContent: String
});
</script>

<template>
    <Head title="Welcome" />
    <div v-html="htmlContent"></div>
</template>

<style scoped>
/* Scoped styles won't apply to v-html, so it allows the custom HTML to use its own global styles if included */
</style>
