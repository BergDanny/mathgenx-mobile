<script setup>
import { computed } from 'vue';

// Define props for the component to make it reusable
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'success', // Can be 'success', 'error', or 'warning'
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
});

// Define the 'close' event that the parent can listen to
const emit = defineEmits(['close']);

// Computed property to dynamically change the icon and color based on the 'type' prop
const alertConfig = computed(() => {
    switch (props.type) {
        case 'error':
            return {
                iconContainerClasses: 'border-red-50 bg-red-100 text-red-500 dark:bg-red-700 dark:border-red-600 dark:text-red-100',
                iconSvg: '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />', // X icon
            };
        case 'warning':
            return {
                iconContainerClasses: 'border-yellow-50 bg-yellow-100 text-yellow-500 dark:bg-yellow-700 dark:border-yellow-600 dark:text-yellow-100',
                iconSvg: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />', // Exclamation icon
            };
        case 'success':
        default:
            return {
                iconContainerClasses: 'border-green-50 bg-green-100 text-green-500 dark:bg-green-700 dark:border-green-600 dark:text-green-100',
                iconSvg: '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />', // Checkmark icon
            };
    }
});

function closeModal() {
    emit('close');
}
</script>

<template>
    <!-- Teleport moves the modal to the end of the body, preventing z-index issues -->
    <teleport to="body">
        <!-- Main modal container, shown based on the 'show' prop -->
        <div v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-md bg-transparent transition-opacity"
            @click.self="closeModal" style="-webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);">
            <!-- Modal content -->
            <div
                class="relative flex flex-col bg-white shadow-lg rounded-xl dark:bg-neutral-900 sm:max-w-lg sm:w-full m-3">
                <!-- Close button -->
                <div class="absolute top-2 end-2">
                    <button type="button"
                        class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600"
                        @click="closeModal">
                        <span class="sr-only">Close</span>
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-4 sm:p-10 text-center overflow-y-auto">
                    <!-- Dynamic Icon -->
                    <span
                        :class="['mb-4 inline-flex justify-center items-center size-11 rounded-full border-4', alertConfig.iconContainerClasses]">
                        <svg class="shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            v-html="alertConfig.iconSvg"></svg>
                    </span>
                    <!-- End Icon -->

                    <h3 class="mb-2 text-xl font-bold text-gray-800 dark:text-neutral-200">
                        {{ title }}
                    </h3>
                    <p class="text-gray-500 dark:text-neutral-500">
                        <!-- Use v-html to allow for links or other simple html in the message -->
                        <span v-html="message"></span>
                    </p>

                    <div class="mt-6 flex justify-center gap-x-4">
                        <button type="button"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 dark:bg-transparent dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800"
                            @click="closeModal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>
