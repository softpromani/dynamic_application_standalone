<script setup>
import TextInput from './TextInput.vue';

defineProps({
    startDate: String,
    endDate: String,
    label: String,
    error: String,
});

defineEmits(['update:startDate', 'update:endDate']);
</script>

<template>
    <div class="mb-3">
        <label v-if="label" class="form-label font-weight-bold text-sm">{{ label }}</label>
        <div class="input-group input-group-sm">
            <input
                type="date"
                class="form-control"
                :value="startDate"
                @input="$emit('update:startDate', $event.target.value)"
                placeholder="Start Date"
            />
            <span class="input-group-text bg-light">to</span>
            <input
                type="date"
                class="form-control"
                :value="endDate"
                @input="$emit('update:endDate', $event.target.value)"
                placeholder="End Date"
            />
        </div>
        <div v-if="error" class="text-danger text-xs mt-1">
            {{ error }}
        </div>
    </div>
</template>

<style scoped>
.text-sm {
    font-size: 0.875rem;
}
.text-xs {
    font-size: 0.75rem;
}
</style>
