<script setup>
import { computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { LucideAmpersands, LucideBarChart3, LucideChartLine, LucideChevronDown, LucideDiameter, LucideLayoutDashboard, LucideLibrary, LucideListChecks, LucideListOrdered, LucidePencil, LucideSettings, LucideSparkles, LucideTarget, LucideUser, LucideClock, LucideX } from 'lucide-vue-next';
import { confirmAlert } from '@/utils/alert';

const props = defineProps({
    isMobile: {
        type: Boolean,
        default: false
    },
    isOpen: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const handleLogout = async () => {
    const confirmed = await confirmAlert("Are you sure?", "You will be logged out.");
    if (confirmed) {
        authStore.logout();
        router.push("/");
    }
};

const showAdminSection = computed(() => {
    const user = authStore.user;
    if (!user) return false;
    if (Array.isArray(user.roles)) {
        return user.roles.some(role => typeof role === 'string' && role.toLowerCase() === 'admin');
    }
    if (user.role && typeof user.role === 'string') {
        return user.role.toLowerCase() === 'admin';
    }
    return false;
});

// Initialize Preline UI accordion functionality
const ensurePrelineInit = () => {
    if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
        window.HSStaticMethods.autoInit();
    }
};

// Watch for route changes and reinitialize if needed
watch(() => route.path, () => {
    nextTick(() => {
        ensurePrelineInit();
    });
    // Close mobile sidebar on route change
    if (props.isMobile) {
        emit('close');
    }
});

// Initialize on mount
onMounted(() => {
    ensurePrelineInit();
});

const handleNavClick = () => {
    if (props.isMobile) {
        emit('close');
    }
};
</script>

<style scoped>
.w-65 {
    width: 260px;
}
</style>

<template>
    <!-- Desktop Sidebar (for large screens) -->
    <div v-if="!isMobile"
        id="hs-application-sidebar"
        class="hs-overlay [--auto-close:lg] hs-overlay-open:translate-x-0 -translate-x-full transition-all duration-300 transform w-65 h-full hidden fixed inset-y-0 start-0 z-60 bg-white border-e border-gray-200 lg:block lg:translate-x-0 text-gray-900"
        style="color-scheme: light;"
        role="dialog" tabindex="-1" aria-label="Sidebar">
        <div class="relative flex flex-col h-full max-h-full">
            <!-- Logo Header -->
            <div class="px-6 pt-4 flex items-center">
                <a class="flex items-center gap-x-2 rounded-xl text-xl font-semibold focus:outline-none focus:opacity-80"
                    aria-label="MathGenα"
                    @click="router.push('/dashboard')">
                    <img src="/icon/iconv3.png" alt="MathGenα" class="w-15 h-15 object-contain" />
                    <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        MathGen<span class="text-2xl align-baseline ml-0.5">α</span></span>
                </a>
            </div>

            <!-- Navigation -->
            <div
                class="mt-6 h-full overflow-y-auto flex-1 [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300">
                <nav class="hs-accordion-group p-3 w-full flex flex-col flex-wrap" data-hs-accordion-always-open>
                    <ul class="flex flex-col space-y-1">
                        <!-- Dashboard -->
                        <li>
                            <a @click="router.push('/dashboard')" :class="[
                                'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                route.name === 'dashboard'
                                    ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]">
                                <LucideLayoutDashboard class="w-5 h-5" /> Dashboard
                            </a>
                        </li>

                        <!-- MathQuest Attempts -->
                        <li>
                            <a @click="router.push('/mathpractice')" :class="[
                                'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                route.name === 'mathpractice_builder'
                                    ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]">
                                <LucideTarget class="w-5 h-5" /> Drill
                            </a>
                        </li>

                        <!-- mathquest Accordion -->
                        <li class="hs-accordion" id="mathquest-accordion">
                            <button type="button"
                                class="hs-accordion-toggle w-full text-start flex items-center justify-between gap-x-3.5 py-2 px-2.5 text-sm text-gray-700 rounded-lg hover:bg-gray-100"
                                aria-expanded="false" aria-controls="mathquest-accordion-child">
                                <span class="flex items-center gap-x-3.5">
                                    <LucideSparkles class="w-5 h-5" />
                                    MathQuest
                                </span>
                                <LucideChevronDown class="w-4 h-4" />
                            </button>

                            <div id="mathquest-accordion-child"
                                class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300 hidden"
                                role="region" aria-labelledby="mathquest-accordion">
                                <ul class="hs-accordion-group ps-8 pt-1 space-y-1" data-hs-accordion-always-open>
                                    <li>
                                        <a
                                            @click="router.push('/mathquest')"
                                            :class="[
                                                'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                                route.name === 'mathquest_builder'
                                                    ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                                    : 'text-gray-700 hover:bg-gray-100'
                                            ]"
                                        >
                                            <LucideListChecks class="w-5 h-5" /> Quiz
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            @click="router.push('/mathquest/attempts')"
                                            :class="[
                                                'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                                route.name === 'quiz_attempts'
                                                    ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                                    : 'text-gray-700 hover:bg-gray-100'
                                            ]"
                                        >
                                            <LucideClock class="w-5 h-5" /> History
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Divider -->
                        <li class="pt-2 pb-1">
                            <div class="border-t border-gray-200"></div>
                        </li>

                        <!-- profile_settings -->
                        <li>
                            <a @click="router.push('/profile')" :class="[
                                'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                route.name === 'profile_settings'
                                    ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]">
                                <LucideUser class="w-5 h-5" /> Profile
                            </a>
                        </li>

                        <!-- Profiling Problems -->
                        <!-- <li>
                            <a @click="router.push('/profiling')" :class="[
                                'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                route.name === 'profiling'
                                    ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]">
                                <LucideDiameter class="w-5 h-5" /> Profiling (temp)
                            </a>
                        </li> -->
                    </ul>
                </nav>
            </div>

            <!-- Logout Button at Bottom -->
            <div class="p-3 mt-auto">
                <button @click="handleLogout"
                    class="w-full bg-gradient-to-r from-white-600 to-white-700 text-red-600 py-2 px-4 rounded-lg hover:from-red-600 hover:to-red-700 hover:text-rose-50 hover:shadow-lg font-medium">
                    Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar (for small screens) -->
    <div v-else>
        <!-- Backdrop -->
        <div v-if="isOpen"
            class="fixed inset-0 z-50 lg:hidden transition-opacity duration-300"
            @click="emit('close')"></div>
        
        <!-- Mobile Sidebar -->
        <div :class="[
            'fixed inset-y-0 start-0 z-50 w-65 h-full bg-white border-e border-gray-200 transform transition-transform duration-300 ease-in-out lg:hidden text-gray-900',
            isOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
        style="color-scheme: light;"
        role="dialog" tabindex="-1" aria-label="Mobile Sidebar">
            <div class="relative flex flex-col h-full max-h-full">
                <!-- Logo Header with Close Button for Mobile -->
                <div class="px-6 pt-4 flex items-center justify-between">
                    <a class="flex items-center gap-x-2 rounded-xl text-xl font-semibold focus:outline-none focus:opacity-80"
                        aria-label="MathGenα"
                        @click="router.push('/dashboard'); handleNavClick()">
                        <img src="/icon/iconv3.png" alt="MathGenα" class="w-15 h-15 object-contain" />
                        <span
                            class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">MathGen<span class="text-2xl align-baseline ml-0.5">α</span></span>
                    </a>
                    <button
                        @click="emit('close')"
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                        aria-label="Close sidebar">
                        <LucideX class="w-5 h-5 text-gray-600" />
                    </button>
                </div>

                <!-- Navigation -->
                <div
                    class="mt-6 h-full overflow-y-auto flex-1 [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300">
                    <nav class="hs-accordion-group p-3 w-full flex flex-col flex-wrap" data-hs-accordion-always-open>
                        <ul class="flex flex-col space-y-1">
                            <!-- Dashboard -->
                            <li>
                                <a @click="router.push('/dashboard'); handleNavClick()" :class="[
                                    'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                    route.name === 'dashboard'
                                        ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                        : 'text-gray-700 hover:bg-gray-100'
                                ]">
                                    <LucideLayoutDashboard class="w-5 h-5" /> Dashboard
                                </a>
                            </li>

                            <!-- MathQuest Attempts -->
                            <li>
                                <a @click="router.push('/mathpractice'); handleNavClick()" :class="[
                                    'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                    route.name === 'mathpractice_builder'
                                        ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                        : 'text-gray-700 hover:bg-gray-100'
                                ]">
                                    <LucideTarget class="w-5 h-5" /> Drill
                                </a>
                            </li>

                            <!-- mathquest Accordion -->
                            <li class="hs-accordion" id="mathquest-accordion-mobile">
                                <button type="button"
                                    class="hs-accordion-toggle w-full text-start flex items-center justify-between gap-x-3.5 py-2 px-2.5 text-sm text-gray-700 rounded-lg hover:bg-gray-100"
                                    aria-expanded="false" aria-controls="mathquest-accordion-child-mobile">
                                    <span class="flex items-center gap-x-3.5">
                                        <LucideSparkles class="w-5 h-5" />
                                        MathQuest
                                    </span>
                                    <LucideChevronDown class="w-4 h-4" />
                                </button>

                                <div id="mathquest-accordion-child-mobile"
                                    class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300 hidden"
                                    role="region" aria-labelledby="mathquest-accordion-mobile">
                                    <ul class="hs-accordion-group ps-8 pt-1 space-y-1" data-hs-accordion-always-open>
                                        <li>
                                            <a
                                                @click="router.push('/mathquest'); handleNavClick()"
                                                :class="[
                                                    'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                                    route.name === 'mathquest_builder'
                                                        ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                                        : 'text-gray-700 hover:bg-gray-100'
                                                ]"
                                            >
                                                <LucideListChecks class="w-5 h-5" /> Quiz
                                            </a>
                                        </li>
                                        <li>
                                            <a
                                                @click="router.push('/mathquest/attempts'); handleNavClick()"
                                                :class="[
                                                    'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                                    route.name === 'quiz_attempts'
                                                        ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                                        : 'text-gray-700 hover:bg-gray-100'
                                                ]"
                                            >
                                                <LucideClock class="w-5 h-5" /> History
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Divider -->
                            <li class="pt-2 pb-1">
                                <div class="border-t border-gray-200"></div>
                            </li>

                            <!-- profile_settings -->
                            <li>
                                <a @click="router.push('/profile'); handleNavClick()" :class="[
                                    'flex items-center gap-x-3.5 py-2 px-2.5 text-sm rounded-lg transition cursor-pointer',
                                    route.name === 'profile_settings'
                                        ? 'bg-gradient-to-r from-blue-50 to-purple-50 text-blue-700 font-medium border border-blue-100'
                                        : 'text-gray-700 hover:bg-gray-100'
                                ]">
                                    <LucideUser class="w-5 h-5" /> Profile
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <!-- Logout Button at Bottom -->
                <div class="p-3 mt-auto">
                    <button @click="handleLogout"
                        class="w-full bg-gradient-to-r from-white-600 to-white-700 text-red-600 py-2 px-4 rounded-lg hover:from-red-600 hover:to-red-700 hover:text-rose-50 hover:shadow-lg font-medium">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
