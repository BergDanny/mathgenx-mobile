<script setup>
import { ref, provide, onMounted, onUnmounted } from 'vue';
import Breadcrumb from "@/components/Breadcrumb.vue";
import Sidebar from "@/components/Sidebar.vue";
import MainFooter from "@/components/MainFooter.vue";
import { LucideMenu } from 'lucide-vue-next';
import Toast from '@/components/Toast.vue';

const toastRef = ref(null);
provide('toast', toastRef);

// Mobile sidebar state
const isMobileSidebarOpen = ref(false);

const toggleMobileSidebar = () => {
    isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
};

const closeMobileSidebar = () => {
    isMobileSidebarOpen.value = false;
};

// Check if screen is mobile size
const isMobile = ref(false);

const checkMobile = () => {
    isMobile.value = window.innerWidth < 1024; // lg breakpoint
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
});

</script>

<template>
    <div class="flex min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
        <Toast ref="toastRef" />
        
        <!-- Desktop Sidebar (only on large screens) -->
        <Sidebar v-if="!isMobile" :is-mobile="false" />
        
        <!-- Mobile Sidebar (only on small screens) -->
        <Sidebar v-else :is-mobile="true" :is-open="isMobileSidebarOpen" @close="closeMobileSidebar" />
        
        <!-- Main Content Area -->
        <div class="flex flex-col flex-1 w-full lg:pl-64">
            <!-- Mobile Hamburger Button -->
            <div class="lg:hidden fixed top-3 sm:top-4 left-3 sm:left-4 z-40">
                <button
                    @click="toggleMobileSidebar"
                    class="min-h-[44px] min-w-[44px] p-2 rounded-lg bg-white/10 backdrop-blur-sm shadow-md hover:bg-white/20 dark:bg-neutral-800/80 dark:hover:bg-neutral-700/90 transition-colors flex items-center justify-center"
                    aria-label="Open sidebar">
                    <LucideMenu class="w-6 h-6 text-gray-700 dark:text-neutral-200" />
                </button>
            </div>
            
            <!-- <Breadcrumb /> -->
            <main class="flex-1 overflow-y-auto p-2 sm:p-3 md:p-4 focus:outline-none" tabindex="0">
                <router-view />
            </main>
        </div>
    </div>
    <MainFooter />
</template>

<style scoped>
/* Ensure main element doesn't show focus outline but still accepts focus */
main:focus {
    outline: none;
}
</style>
