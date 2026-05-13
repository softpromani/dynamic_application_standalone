<script setup>
defineProps({
    modelValue: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: [String, Number, Boolean],
        default: null,
    },
    label: String,
    error: String,
    id: String,
});

const emit = defineEmits(['update:modelValue']);

const handleChange = (event) => {
    const isChecked = event.target.checked;
    emit('update:modelValue', isChecked);
};
</script>

<template>
    <div class="mb-3 form-check">
        <input
            type="checkbox"
            class="form-check-input"
            :class="{ 'is-invalid': error }"
            :id="id"
            :checked="Array.isArray(modelValue) ? modelValue.includes(value) : modelValue"
            @change="handleChange"
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
