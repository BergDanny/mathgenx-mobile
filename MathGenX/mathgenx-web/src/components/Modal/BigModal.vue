<template>
    <div class="text-center" v-if="showTrigger">
        <button type="button"
            class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none"
            @click="openModal">
            <slot name="trigger">Open Modal</slot>
        </button>
    </div>

    <div :id="modalId"
        class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
        role="dialog" tabindex="-1" :aria-labelledby="`${modalId}-label`">
        <div data-modal-inner
            class="hs-overlay-open:mt-0 sm:hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all w-full sm:max-w-4xl sm:w-full m-0 sm:m-3 h-full sm:h-[calc(100%-56px)] sm:mx-auto">
            <div
                class="max-h-full overflow-hidden flex flex-col bg-white border-0 sm:border border-gray-200 shadow-2xs rounded-none sm:rounded-xl pointer-events-auto dark:bg-neutral-900 dark:border-neutral-800 dark:shadow-neutral-700/70">
                <header v-if="title"
                    class="flex justify-between items-center p-4 sm:p-6 border-b border-gray-200 dark:border-neutral-700">
                    <h3 class="text-base sm:text-lg font-semibold text-gray-800 dark:text-neutral-100">
                        {{ title }}
                    </h3>
                    <button type="button" 
                        class="min-h-[44px] min-w-[44px] text-gray-400 hover:text-gray-600 dark:hover:text-neutral-400 flex items-center justify-center"
                        @click="closeModal">
                        ✕
                    </button>
                </header>

                <section :class="['flex-1 overflow-y-auto', props.contentClass || 'p-4 sm:p-6']">
                    <slot />
                </section>

                <footer v-if="$slots.footer"
                    class="p-4 sm:p-6 border-t border-gray-200 dark:border-neutral-700 flex flex-col sm:flex-row justify-end gap-2 sm:gap-x-2">
                    <slot name="footer" />
                </footer>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from "vue";

const props = defineProps({
    modalId: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: "",
    },
    showTrigger: {
        type: Boolean,
        default: true,
    },
    contentClass: {
        type: String,
        default: "",
    },
});

const ensurePrelineInit = () => {
    if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
        window.HSStaticMethods.autoInit();
    }
};

const openModal = () => {
    const modal = document.getElementById(props.modalId);
    if (!modal) return;

    // Ensure Preline is initialized before attempting programmatic open
    ensurePrelineInit();

    const api = window.HSOverlay;
    if (api && typeof api.open === 'function') {
        try {
            api.open(modal);
            return;
        } catch (e) {
            // fall through to fallback below
        }
    }

    // Fallback: manually make overlay visible
    modal.classList.remove('hidden');
    modal.classList.remove('pointer-events-none');
    modal.style.opacity = '1';
    const inner = modal.querySelector('[data-modal-inner]');
    if (inner && inner instanceof HTMLElement) {
        inner.classList.remove('mt-0');
        inner.classList.add('mt-7');
        inner.style.opacity = '1';
    }
};

const closeModal = () => {
    const modal = document.getElementById(props.modalId);
    if (!modal) return;

    const api = window.HSOverlay;
    if (api && typeof api.close === 'function') {
        try {
            api.close(modal);
            return;
        } catch (e) {
            // fall through to fallback below
        }
    }

    // Fallback: manually hide overlay
    modal.classList.add('hidden');
    modal.classList.add('pointer-events-none');
    modal.style.opacity = '0';
    const inner = modal.querySelector('[data-modal-inner]');
    if (inner && inner instanceof HTMLElement) {
        inner.classList.remove('mt-7');
        inner.classList.add('mt-0');
        inner.style.opacity = '0';
    }
};

onMounted(() => {
    // Auto-init Preline overlays
    ensurePrelineInit();
});
</script>

<style scoped>
.hs-overlay {
    backdrop-filter: blur(4px);
    background-color: rgba(0, 0, 0, 0.3);
}
</style>
