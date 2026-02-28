<script setup>
import { computed } from 'vue';

const props = defineProps({
    current: {
        type: Number,
        required: true,
        default: 0
    },
    total: {
        type: Number,
        required: true,
        default: 100
    },
    size: {
        type: Number,
        default: 120
    },
    strokeWidth: {
        type: Number,
        default: 12
    },
    centerText: {
        type: String,
        default: ''
    },
    centerSubtext: {
        type: String,
        default: ''
    },
    currentLevel: {
        type: Number,
        default: null
    },
    nextLevel: {
        type: Number,
        default: null
    }
});

const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const progress = computed(() => {
    if (props.total <= 0) return 0;
    return Math.min(props.current / props.total, 1);
});
const offset = computed(() => circumference.value * (1 - progress.value));

const centerX = computed(() => props.size / 2);
const centerY = computed(() => props.size / 2);
</script>

<template>
    <div class="relative inline-flex items-center justify-center" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg :width="size" :height="size" class="transform -rotate-90">
            <!-- Background circle -->
            <circle
                :cx="centerX"
                :cy="centerY"
                :r="radius"
                :stroke-width="strokeWidth"
                fill="none"
                class="text-gray-200"
                stroke="currentColor"
            />
            <defs>
                <linearGradient :id="`expGradient-${size}`" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#6366F1;stop-opacity:1" />
                    <stop offset="50%" style="stop-color:#8B5CF6;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#A78BFA;stop-opacity:1" />
                </linearGradient>
            </defs>
            <!-- Progress circle with gradient -->
            <circle
                :cx="centerX"
                :cy="centerY"
                :r="radius"
                :stroke-width="strokeWidth"
                fill="none"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
                class="transition-all duration-500 ease-out"
                stroke-linecap="round"
                :style="`stroke: url(#expGradient-${size});`"
            >
                <animate attributeName="stroke-dashoffset" :values="`${circumference};${offset}`" dur="1s" fill="freeze" />
            </circle>
        </svg>
        <!-- Center text -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <div v-if="currentLevel !== null && nextLevel !== null" class="text-center">
                <div class="text-lg font-bold text-gray-900">Level {{ currentLevel }}</div>
                <div class="text-xs text-gray-500 mt-0.5">→ Level {{ nextLevel }}</div>
                <div class="text-sm font-semibold text-gray-700 mt-1">{{ centerText }}</div>
                <div v-if="centerSubtext" class="text-xs text-gray-600 mt-0.5">{{ centerSubtext }}</div>
            </div>
            <div v-else>
                <div class="text-2xl font-bold text-gray-900">{{ centerText }}</div>
                <div v-if="centerSubtext" class="text-xs text-gray-600 mt-0.5">{{ centerSubtext }}</div>
            </div>
        </div>
    </div>
</template>


