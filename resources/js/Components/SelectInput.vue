<script setup>
defineProps({
    modelValue: {
        type: [String, Number, Array],
        required: true,
    },
    label: String,
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Select an option',
    },
    error: String,
    id: String,
    multiple: Boolean,
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="mb-3">
        <label v-if="label" :for="id" class="form-label font-weight-bold text-sm">{{ label }}</label>
        <select
            :id="id"
            class="form-select form-select-sm"
            :class="{ 'is-invalid': error }"
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            :multiple="multiple"
        >
            <option disabled value="">{{ placeholder }}</option>
            <template v-for="option in options" :key="option.id || option.value || option">
                <option :value="option.value !== undefined ? option.value : option">
                    {{ option.label || option.text || option }}
                </option>
            </template>
        </select>
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
