<script setup>
import { ref, nextTick, onMounted } from 'vue';

const props = defineProps({
  title: { type: String, default: 'Confirm' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: 'OK' },
  cancelText: { type: String, default: 'Cancel' }
});

const emit = defineEmits(['confirm', 'cancel', 'closed']);

const rootEl = ref(null);
const modalId = `hs-confirm-${Math.random().toString(36).slice(2, 9)}`;

function open() {
  nextTick(() => {
    window.HSOverlay?.open(rootEl.value);
  });
}

function close() {
  window.HSOverlay?.close(rootEl.value);
  emit('closed');
}

function onConfirm() {
  emit('confirm');
  close();
}

function onCancel() {
  emit('cancel');
  close();
}

onMounted(() => {
  // Ensure Preline initializes if content is dynamically added
  window.HSStaticMethods?.autoInit?.();
});

defineExpose({ open, close });
</script>

<template>
  <div ref="rootEl" class="hs-overlay hidden fixed inset-0 z-80 overflow-x-hidden overflow-y-auto
           opacity-0 transition-opacity
           hs-overlay-open:opacity-100 hs-overlay-open:bg-gray-900/50 hs-overlay-open:backdrop-blur-sm" role="dialog"
    tabindex="-1" :id="modalId" :aria-labelledby="`${modalId}-label`">
    <div
      class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
      <div class="relative flex flex-col bg-white shadow-lg rounded-xl dark:bg-neutral-900">
        <div class="absolute top-2 end-2">
          <button type="button"
            class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600"
            aria-label="Close" @click="onCancel">
            <span class="sr-only">Close</span>
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
              fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>
        </div>

        <div class="p-4 sm:p-10 text-center overflow-y-auto">
          <span
            class="mb-4 inline-flex justify-center items-center size-15.5 rounded-full border-4 border-yellow-50 bg-yellow-100 text-yellow-500 dark:bg-yellow-700 dark:border-yellow-600 dark:text-yellow-100">
            <svg class="shrink-0 size-5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
              viewBox="0 0 16 16">
              <path
                d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
            </svg>
          </span>

          <h3 :id="`${modalId}-label`" class="mb-2 text-2xl font-bold text-gray-800 dark:text-neutral-200">
            {{ props.title }}
          </h3>
          <p class="text-gray-500 dark:text-neutral-500">
            {{ props.message }}
          </p>

          <div class="mt-6 flex justify-center gap-x-4">
            <button type="button"
              class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-gray-400 text-white hover:bg-gray-700 focus:outline-hidden focus:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none"
              @click="onCancel">
              {{ props.cancelText }}
            </button>
            <button type="button"
              class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-red-600 text-white shadow-2xs hover:bg-red-700 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-red-700 dark:bg-transparent dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800"
              @click="onConfirm">
              {{ props.confirmText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>