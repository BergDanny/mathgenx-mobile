<script setup lang="ts">
import { ref } from 'vue';
import { LucideCheckCircle, LucideAlertCircle, LucideInfo, LucideAlertTriangle, LucideX } from 'lucide-vue-next';

export interface ToastOptions {
  type?: 'success' | 'error' | 'warning' | 'info';
  title?: string;
  message: string;
  duration?: number;
}

interface Toast extends ToastOptions {
  id: number;
  show: boolean;
}

const toasts = ref<Toast[]>([]);
let toastId = 0;

const showToast = (options: ToastOptions) => {
  const id = toastId++;
  const toast: Toast = {
    id,
    type: options.type || 'info',
    title: options.title,
    message: options.message,
    duration: options.duration || 3000,
    show: true,
  };

  toasts.value.push(toast);

  setTimeout(() => {
    removeToast(id);
  }, toast.duration);
};

const removeToast = (id: number) => {
  const index = toasts.value.findIndex((t) => t.id === id);
  if (index !== -1 && toasts.value[index]) {
    toasts.value[index].show = false;
    setTimeout(() => {
      toasts.value.splice(index, 1);
    }, 300);
  }
};

// Expose the showToast function to be used globally
defineExpose({
  showToast
});

const getIcon = (type?: 'success' | 'error' | 'warning' | 'info') => {
  switch (type) {
    case 'success':
      return LucideCheckCircle;
    case 'error':
      return LucideAlertCircle;
    case 'warning':
      return LucideAlertTriangle;
    default:
      return LucideInfo;
  }
};

const getColorClasses = (type?: 'success' | 'error' | 'warning' | 'info') => {
  switch (type) {
    case 'success':
      return 'backdrop-blur-sm bg-emerald-50/90 border-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:border-emerald-700';
    case 'error':
      return 'backdrop-blur-sm bg-rose-50/95 border-rose-100 text-rose-800 dark:bg-rose-900/60 dark:border-rose-700';
    case 'warning':
      return 'backdrop-blur-sm bg-amber-50/95 border-amber-100 text-amber-800 dark:bg-amber-900/60 dark:border-amber-700';
    default:
      return 'backdrop-blur-sm bg-sky-50/95 border-sky-100 text-sky-800 dark:bg-sky-900/60 dark:border-sky-700';
  }
};

const getIconColor = (type?: 'success' | 'error' | 'warning' | 'info') => {
  switch (type) {
    case 'success':
      return 'text-emerald-600 dark:text-emerald-300';
    case 'error':
      return 'text-rose-600 dark:text-rose-300';
    case 'warning':
      return 'text-amber-600 dark:text-amber-300';
    default:
      return 'text-sky-600 dark:text-sky-300';
  }
};
</script>

<template>
  <div class="fixed top-5 right-5 z-[100] space-y-3 max-w-xs w-full">
    <transition-group name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'relative rounded-xl shadow-lg border-2 p-4 transition-all duration-300',
          getColorClasses(toast.type),
          toast.show ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-full'
        ]"
        role="alert"
      >
        <div class="flex items-start gap-3">
          <component
            :is="getIcon(toast.type)"
            :class="['w-5 h-5 shrink-0 mt-0.5', getIconColor(toast.type)]"
            stroke-width="2"
          />
          
          <div class="flex-1 min-w-0">
            <h3 v-if="toast.title" class="font-semibold text-gray-800 dark:text-white text-sm mb-1">
              {{ toast.title }}
            </h3>
            <p class="text-sm text-gray-700 dark:text-gray-300" v-html="toast.message"></p>
          </div>

          <button
            @click="removeToast(toast.id)"
            class="shrink-0 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors"
            aria-label="Close"
          >
            <LucideX class="w-4 h-4" />
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>