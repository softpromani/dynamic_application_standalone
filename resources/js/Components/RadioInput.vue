<script setup>
defineProps({
    modelValue: [String, Number, Boolean],
    value: [String, Number, Boolean],
    label: String,
    name: String,
    error: String,
    id: String,
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="mb-3 form-check">
        <input
            type="radio"
            class="form-check-input"
            :class="{ 'is-invalid': error }"
            :id="id"
            :name="name"
            :value="value"
            :checked="modelValue === value"
            @change="$emit('update:modelValue', value)"
        />
        <label v-if="label" class="form-check-label text-sm" :for="id">
            {{ label }}
        </label>
        <div v-if="error" class="text-danger d-block text-xs mt-1">
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
