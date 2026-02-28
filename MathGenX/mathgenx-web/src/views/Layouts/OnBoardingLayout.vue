<script setup>
import { ref, provide } from 'vue';
import Toast from '@/components/Toast.vue';

const toastRef = ref(null);
provide('toast', toastRef);

</script>

<template>
    <div class="flex min-h-screen bg-gray-50 dark:bg-neutral-900">
        <Toast ref="toastRef" />
        <main class="flex-1 overflow-y-auto">
            <router-view />
        </main>
    </div>
</template>