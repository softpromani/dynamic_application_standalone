<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

const props = defineProps({
    aspectRatio: {
        type: Number,
        default: 1,
    },
    src: String,
    title: {
        type: String,
        default: 'Crop Image',
    },
});

const emit = defineEmits(['crop', 'close']);

const imageElement = ref(null);
const cropper = ref(null);

onMounted(() => {
    if (props.src) {
        initCropper();
    }
});

const initCropper = () => {
    if (cropper.value) {
        cropper.value.destroy();
    }
    // Ensure image is loaded before initializing
    const img = imageElement.value;
    cropper.value = new Cropper(img, {
        aspectRatio: props.aspectRatio,
        viewMode: 1, // Restrict crop box to within the canvas
        dragMode: 'move',
        responsive: true,
        restore: false,
        checkCrossOrigin: false,
        checkOrientation: false,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
    });
};

const handleCrop = () => {
    const canvas = cropper.value.getCroppedCanvas({
        width: props.aspectRatio === 1 ? 400 : 600,
        height: props.aspectRatio === 1 ? 400 : 200,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high',
    });
    emit('crop', canvas.toDataURL('image/jpeg', 0.9));
};

onBeforeUnmount(() => {
    if (cropper.value) {
        cropper.value.destroy();
    }
});
</script>

<template>
    <div class="cropper-wrapper">
        <div class="cropper-modal-overlay" @click="$emit('close')"></div>
        <div class="cropper-modal shadow bg-white rounded border">
            <div class="modal-header d-flex justify-content-between align-items-center p-3 border-bottom bg-light rounded-top">
                <h6 class="m-0 font-weight-bold text-dark">{{ title }}</h6>
                <button @click="$emit('close')" class="btn-close btn-sm"></button>
            </div>
            
            <div class="modal-body p-0">
                <div class="cropper-container">
                    <img ref="imageElement" :src="src" class="d-block" style="max-width: 100%;" />
                </div>
            </div>

            <div class="modal-footer d-flex justify-content-end gap-2 p-3 border-top bg-light rounded-bottom">
                <button @click="$emit('close')" class="btn btn-secondary btn-sm px-3">Cancel</button>
                <button @click="handleCrop" class="btn btn-primary btn-sm px-4 shadow-sm">Apply Crop</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cropper-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 2100;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cropper-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
}

.cropper-modal {
    position: relative;
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    z-index: 2200;
    overflow: hidden;
}

.modal-body {
    flex: 1;
    overflow: hidden;
    background: #333; /* Dark background for the cropper area */
}

.cropper-container {
    width: 100%;
    height: 100%;
    min-height: 300px;
    max-height: 60vh; /* Strictly limit height of the cropper area */
}

/* Ensure cropper element takes its parent's height */
:deep(.cropper-container) {
    height: 100% !important;
}

@media (max-width: 576px) {
    .cropper-modal {
        width: 95%;
        max-width: none;
    }
    .cropper-container {
        max-height: 50vh;
    }
}
</style>
