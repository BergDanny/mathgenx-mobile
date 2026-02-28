<script setup>
import { ref, inject, onMounted, computed, nextTick } from 'vue';
import { useAuthStore } from '@/stores/authStore';
import { useRouter } from 'vue-router';
import { updateProfile } from '@/services/userProfileService';
import { getUserProfilingResponses } from '@/services/profilingService';
import { getQuizAttempts } from '@/services/MathQuestService';
import { LucideUser, LucideEdit, LucideSave, LucideLoader2, LucideUserCircle, LucideBrain, LucideBarChart3, LucideChevronDown, LucideTarget, LucideBookOpen, LucideEye } from 'lucide-vue-next';
import Aurora from '@/components/ui/Backgrounds/Aurora/Aurora.vue';
import ActivityHeatmap from '@/components/charts/ActivityHeatmap.vue';
import DonutChart from '@/components/charts/DonutChart.vue';
import OnboardingTour from '@/components/Onboarding/OnboardingTour.vue';
import { completeOnboarding } from '@/services/onboardingService';

const authStore = useAuthStore();
const router = useRouter();
const toast = inject('toast');

const isEditing = ref(false);
const saving = ref(false);
const editForm = ref({
    name: '',
    email: ''
});

const profilingData = ref(null);
const profilingLoading = ref(false);
const expandedVark = ref(false);
const expandedMath = ref(false);

// Quiz activity heatmap data
const quizAttempts = ref([]);
const heatmapLoading = ref(false);
const selectedTimeRange = ref('1month');

const capitalize = (s) => (s ? String(s).charAt(0).toUpperCase() + String(s).slice(1) : s);

const enableEditMode = () => {
    editForm.value = {
        name: authStore.user?.name || '',
        email: authStore.user?.email || ''
    };
    isEditing.value = true;
};

const cancelEdit = () => {
    isEditing.value = false;
    editForm.value = {
        name: '',
        email: ''
    };
};

const saveProfile = async () => {
    if (!editForm.value.name.trim() || !editForm.value.email.trim()) {
        toast?.value?.showToast?.({
            type: 'error',
            title: 'Validation Error',
            message: 'Please fill in all fields.',
            duration: 3000,
        });
        return;
    }

    saving.value = true;
    try {
        const result = await updateProfile({
            name: editForm.value.name.trim(),
            email: editForm.value.email.trim()
        });

        if (result.success) {
            // Refresh profile data
            await authStore.fetchProfile();
            isEditing.value = false;

            toast?.value?.showToast?.({
                type: 'success',
                title: 'Profile Updated',
                message: 'Your profile has been successfully updated!',
                duration: 3000,
            });
        } else {
            const errorMessage = result.message || 'Failed to update profile. Please try again.';
            toast?.value?.showToast?.({
                type: 'error',
                title: 'Update Failed',
                message: errorMessage,
                duration: 4000,
            });
        }
    } catch (error) {
        console.error('Profile update error:', error);
        toast?.value?.showToast?.({
            type: 'error',
            title: 'Unexpected Error',
            message: 'Something went wrong. Please try again.',
            duration: 4000,
        });
    } finally {
        saving.value = false;
    }
};

const fetchProfilingData = async () => {
    try {
        profilingLoading.value = true;
        const result = await getUserProfilingResponses();
        console.log('Profiling API Response:', result);
        if (result.success && result.data) {
            profilingData.value = result.data;
            console.log('Profiling Data Set:', profilingData.value);
        } else {
            console.warn('Profiling data not available:', result.message || 'Unknown error');
        }
    } catch (err) {
        console.error('Error fetching profiling data:', err);
        // Don't show error to user, just log it
    } finally {
        profilingLoading.value = false;
    }
};

const fetchQuizAttempts = async () => {
    try {
        heatmapLoading.value = true;
        const result = await getQuizAttempts();
        if (result.status === 'success' && result.data) {
            quizAttempts.value = result.data;
        } else {
            console.warn('Quiz attempts not available:', result.message || 'Unknown error');
            quizAttempts.value = [];
        }
    } catch (err) {
        console.error('Error fetching quiz attempts:', err);
        quizAttempts.value = [];
    } finally {
        heatmapLoading.value = false;
    }
};

// Process quiz attempts into heatmap data format - filter by time range
const heatmapData = computed(() => {
    if (!quizAttempts.value || quizAttempts.value.length === 0) {
        return [];
    }

    // Calculate date range once - include today
    // Use local date to match user's timezone
    const today = new Date();
    const todayYear = today.getFullYear();
    const todayMonth = today.getMonth();
    const todayDay = today.getDate();
    const todayDate = new Date(todayYear, todayMonth, todayDay, 0, 0, 0, 0);
    const todayStr = `${todayYear}-${String(todayMonth + 1).padStart(2, '0')}-${String(todayDay).padStart(2, '0')}`;

    let startDate;
    if (selectedTimeRange.value === '1month') {
        startDate = new Date(todayDate);
        startDate.setMonth(todayDate.getMonth() - 1);
    } else if (selectedTimeRange.value === '6months') {
        startDate = new Date(todayDate);
        startDate.setMonth(todayDate.getMonth() - 6);
    } else if (selectedTimeRange.value === '12months') {
        startDate = new Date(todayDate);
        startDate.setMonth(todayDate.getMonth() - 12);
    } else {
        // All time - include all dates
        startDate = new Date(0);
        startDate.setHours(0, 0, 0, 0);
    }

    // Group attempts by date (YYYY-MM-DD format, using UTC)
    const dateCountMap = new Map();

    quizAttempts.value.forEach(attempt => {
        if (attempt.completed_at) {
            // Parse ISO string and convert to local date
            const date = new Date(attempt.completed_at);
            // Get local date components to avoid timezone issues
            const year = date.getFullYear();
            const month = date.getMonth();
            const day = date.getDate();
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const attemptDate = new Date(year, month, day, 0, 0, 0, 0);

            // Include dates from startDate (inclusive) to today (inclusive)
            // Compare dates at midnight for accurate comparison
            if (attemptDate >= startDate && attemptDate <= todayDate) {
                const currentCount = dateCountMap.get(dateStr) || 0;
                dateCountMap.set(dateStr, currentCount + 1);
            }
        }
    });

    // Convert map to array format
    return Array.from(dateCountMap.entries()).map(([date, count]) => ({
        date,
        count
    }));
});

const updateTimeRange = (range) => {
    selectedTimeRange.value = range;
};

// Computed properties for statistics
const averageAccuracy = computed(() => {
    if (!quizAttempts.value || quizAttempts.value.length === 0) {
        return 0;
    }
    const validAttempts = quizAttempts.value.filter(attempt => {
        const score = attempt.score_percentage;
        return score !== null && score !== undefined && !isNaN(score) && isFinite(score);
    });

    if (validAttempts.length === 0) {
        return 0;
    }

    const totalScore = validAttempts.reduce((sum, attempt) => {
        return sum + (Number(attempt.score_percentage) || 0);
    }, 0);

    const average = totalScore / validAttempts.length;
    return isNaN(average) || !isFinite(average) ? 0 : Math.round(average);
});

const totalQuestionsAnswered = computed(() => {
    if (!quizAttempts.value || quizAttempts.value.length === 0) {
        return 0;
    }
    return quizAttempts.value.reduce((sum, attempt) => {
        return sum + (attempt.total_questions || 0);
    }, 0);
});

// EXP progress for donut chart
const expLeft = computed(() => {
    if (!authStore.exp_info || !authStore.exp_info.exp_needed_for_next_level) {
        return 0;
    }
    const expInCurrentLevel = authStore.exp_info.exp_in_current_level || 0;
    const expNeeded = authStore.exp_info.exp_needed_for_next_level || 0;
    return Math.max(0, expNeeded - expInCurrentLevel);
});

const expProgressPercentage = computed(() => {
    if (!authStore.exp_info || !authStore.exp_info.exp_needed_for_next_level) {
        return 0;
    }
    const expInCurrentLevel = authStore.exp_info.exp_in_current_level || 0;
    const expNeeded = authStore.exp_info.exp_needed_for_next_level || 0;
    if (expNeeded <= 0) return 100;
    return Math.min(100, (expInCurrentLevel / expNeeded) * 100);
});

// Onboarding tour
const showOnboardingTour = ref(false)

const tourSteps = [
  {
    title: "Profile Overview",
    description: "View your learning statistics including accuracy, questions answered, and total EXP gained. Track your progress at a glance.",
    targetSelector: "[data-onboarding='profile-overview']"
  },
  {
    title: "Activity Heatmap",
    description: "See your quiz activity over time. This shows when you've completed MathQuest quizzes (which give EXP and analytics).",
    targetSelector: "[data-onboarding='activity-heatmap']"
  },
  {
    title: "VARK Assessment",
    description: "Review your learning style assessment results. This helps personalize your learning experience.",
    targetSelector: "[data-onboarding='vark-assessment']"
  },
  {
    title: "Profile Information",
    description: "Edit your profile information including name and email. Keep your account details up to date.",
    targetSelector: "[data-onboarding='profile-info']"
  }
]

const handleTourComplete = async () => {
  try {
    const result = await completeOnboarding('profile')
    if (result.success && result.data?.onboarding_flags) {
      authStore.updateOnboardingFlags(result.data.onboarding_flags)
    }
  } catch (error) {
    console.error('Error completing onboarding:', error)
  } finally {
    showOnboardingTour.value = false
  }
}

const handleTourSkip = async () => {
  await handleTourComplete()
}

const checkOnboardingStatus = async () => {
  const onboardingFlags = authStore.onboarding_flags
  if (!onboardingFlags || !onboardingFlags.profile) {
    await nextTick()
    setTimeout(() => {
      showOnboardingTour.value = true
    }, 500)
  }
}

onMounted(async () => {
    // Ensure we have the latest profile data including exp_info
    if (!authStore.user || !authStore.learning_results || !authStore.exp_info) {
        await authStore.fetchProfile();
    }
    await Promise.all([
        fetchProfilingData(),
        fetchQuizAttempts()
    ]);
    
    await checkOnboardingStatus()
});
</script>

<style scoped></style>


<template>
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 pb-6 sm:pb-10 px-2 sm:px-4 md:px-6">
        <!-- Onboarding Tour -->
        <OnboardingTour
            :steps="tourSteps"
            :is-active="showOnboardingTour"
            @complete="handleTourComplete"
            @skip="handleTourSkip"
        />
        <!-- Header Section with Donut Chart -->
        <div class="relative rounded-xl shadow-md border border-gray-100 overflow-hidden" data-onboarding="profile-overview">
            <Aurora :color-stops="['#60A5FA', '#A78BFA', '#FBCFE8']" :amplitude="0.65" :blend="0.35" :speed="1.5"
                :intensity="0.65" class="absolute inset-0 w-full h-full" />
            <div class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 pointer-events-none"></div>
            <div class="relative z-20 p-4 sm:p-6 mt-10 md:mt-0">
                <div class="flex flex-col md:flex-row justify-between items-center md:items-start gap-4 sm:gap-6">
                    <!-- Left: Statistics (side by side) -->
                    <div class="flex flex-col gap-4 sm:gap-6 w-full md:w-auto">
                        <!-- Title -->
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                Profile Overview
                            </h2>
                            <p class="text-xs sm:text-sm text-gray-600 mt-1">Your learning progress and statistics</p>
                        </div>
                        <div class="grid grid-cols-3 gap-3 sm:gap-6">
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-2 sm:gap-3">
                                <div class="bg-blue-100 p-2 sm:p-3 rounded-lg flex-shrink-0">
                                    <LucideTarget class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600" />
                                </div>
                                <div class="text-center sm:text-left">
                                    <p class="text-xs sm:text-sm text-gray-600 mb-0.5">Accuracy</p>
                                    <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ averageAccuracy }}%</p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-2 sm:gap-3">
                                <div class="bg-purple-100 p-2 sm:p-3 rounded-lg flex-shrink-0">
                                    <LucideBookOpen class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600" />
                                </div>
                                <div class="text-center sm:text-left">
                                    <p class="text-xs sm:text-sm text-gray-600 mb-0.5">Answered</p>
                                    <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ totalQuestionsAnswered }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-2 sm:gap-3">
                                <div class="bg-yellow-100 p-2 sm:p-3 rounded-lg flex-shrink-0">
                                    <LucideBarChart3 class="w-4 h-4 sm:w-5 sm:h-5 text-yellow-600" />
                                </div>
                                <div class="text-center sm:text-left">
                                    <p class="text-xs sm:text-sm text-gray-600 mb-0.5">Exp Gained</p>
                                    <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ authStore.exp_info?.exp || 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Donut Chart -->
                    <div class="flex-shrink-0 flex justify-center">
                        <DonutChart 
                            :current="authStore.exp_info?.exp_in_current_level || 0"
                            :total="authStore.exp_info?.exp_needed_for_next_level || 100" 
                            :size="180" 
                            :stroke-width="22"
                            :center-text="expLeft.toString()" 
                            center-subtext="EXP left"
                            :current-level="authStore.user?.level || 1"
                            :next-level="authStore.exp_info?.exp_needed_for_next_level ? ((authStore.user?.level || 1) + 1) : null" />
                    </div>
                </div>

                <!-- Bottom: Learning Style & Math Capability -->
                <div class="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-200 flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4 items-start sm:items-center">
                    <div v-if="profilingData?.vark?.summary?.dominant_style" class="flex items-center gap-2">
                        <LucideEye class="w-4 h-4 text-blue-600" />
                        <span class="text-xs sm:text-sm font-medium text-gray-700">{{ profilingData.vark.summary.dominant_style
                        }}</span>
                    </div>
                    <div v-if="authStore.learning_results?.math" class="flex items-center gap-2">
                        <LucideBarChart3 class="w-4 h-4 text-purple-600" />
                        <span class="text-xs sm:text-sm font-medium text-gray-700">Math: {{
                            capitalize(authStore.learning_results.math) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz Activity Heatmap -->
        <div v-if="!heatmapLoading" class="overflow-x-auto" data-onboarding="activity-heatmap">
            <ActivityHeatmap :heatmap-data="heatmapData" :selected-time-range="selectedTimeRange"
                @update:selected-time-range="updateTimeRange" />
        </div>
        <div v-else class="bg-white rounded-xl shadow-md border border-gray-100 p-8 sm:p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-sm text-gray-600">Loading activity data...</p>
        </div>


        <!-- Profiling Results Section -->
        <div class="space-y-6">
            <!-- Loading State -->
            <div v-if="profilingLoading" class="bg-white rounded-xl shadow-md p-12 border border-gray-100 text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-lg font-semibold text-gray-800">Loading profiling data...</p>
            </div>

            <!-- VARK Assessment Card -->
            <div v-if="profilingData?.vark"
                data-onboarding="vark-assessment"
                class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="p-4 sm:p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="bg-gradient-to-br from-blue-500 to-purple-600 p-2 sm:p-3 rounded-xl shadow-md">
                                <LucideBrain class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                            </div>
                            <div>
                                <h2 class="text-lg sm:text-xl font-bold text-gray-900">VARK Learning Style Assessment</h2>
                                <p class="text-xs sm:text-sm text-gray-600">Your learning preferences and responses</p>
                            </div>
                        </div>
                        <button @click="expandedVark = !expandedVark"
                            class="min-h-[44px] w-full sm:w-auto px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 rounded-lg transition-colors flex items-center justify-center gap-2 border border-gray-200">
                            <LucideChevronDown
                                :class="['w-4 h-4 transition-transform', expandedVark ? 'rotate-180' : '']" />
                            {{ expandedVark ? 'Collapse' : 'View Details' }}
                        </button>
                    </div>
                </div>

                <!-- Summary Section (Always Visible) -->
                <div class="p-4 sm:p-6">
                    <div v-if="profilingData.vark.summary" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 sm:gap-4 mb-4 sm:mb-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <div class="text-xs text-blue-600 font-medium mb-1">Dominant Style</div>
                            <div class="text-lg font-bold text-blue-900">
                                {{ profilingData.vark.summary.dominant_style }}
                            </div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
                            <div class="text-xs text-purple-600 font-medium mb-1">Visual (V)</div>
                            <div class="text-lg font-bold text-purple-900">
                                {{ profilingData.vark.summary.score_v }}
                            </div>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <div class="text-xs text-green-600 font-medium mb-1">Auditory (A)</div>
                            <div class="text-lg font-bold text-green-900">
                                {{ profilingData.vark.summary.score_a }}
                            </div>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                            <div class="text-xs text-yellow-600 font-medium mb-1">Read/Write (R)</div>
                            <div class="text-lg font-bold text-yellow-900">
                                {{ profilingData.vark.summary.score_r }}
                            </div>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                            <div class="text-xs text-red-600 font-medium mb-1">Kinesthetic (K)</div>
                            <div class="text-lg font-bold text-red-900">
                                {{ profilingData.vark.summary.score_k }}
                            </div>
                        </div>
                    </div>

                    <!-- Expandable Details -->
                    <div v-show="expandedVark" class="mt-6 space-y-4 border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Question Responses</h3>
                        <div v-if="profilingData.vark.responses && profilingData.vark.responses.length > 0"
                            class="space-y-3">
                            <div v-for="(response, index) in profilingData.vark.responses" :key="response.id"
                                class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-blue-300 transition-colors">
                                <div class="flex items-start gap-3">
                                    <span
                                        class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-sm">
                                        {{ index + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 mb-2">
                                            {{ response.question_text }}
                                        </p>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs text-gray-600">Your Answer:</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ response.answer_text
                                            }}</span>
                                            <span v-if="response.category" :class="[
                                                'px-2 py-1 rounded text-xs font-medium',
                                                response.category === 'V'
                                                    ? 'bg-purple-100 text-purple-700'
                                                    : response.category === 'A'
                                                        ? 'bg-green-100 text-green-700'
                                                        : response.category === 'R'
                                                            ? 'bg-yellow-100 text-yellow-700'
                                                            : 'bg-red-100 text-red-700',
                                            ]">
                                                {{ response.category }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 italic text-center py-4">
                            No VARK responses found.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Math Assessment Card -->
            <div v-if="profilingData?.math"
                class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                <div class="p-4 sm:p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div class="bg-gradient-to-br from-blue-500 to-purple-600 p-2 sm:p-3 rounded-xl shadow-md">
                                <LucideBarChart3 class="w-5 h-5 sm:w-6 sm:h-6 text-white" />
                            </div>
                            <div>
                                <h2 class="text-lg sm:text-xl font-bold text-gray-900">Math Assessment</h2>
                                <p class="text-xs sm:text-sm text-gray-600">Your math capability and responses</p>
                            </div>
                        </div>
                        <button @click="expandedMath = !expandedMath"
                            class="min-h-[44px] w-full sm:w-auto px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 rounded-lg transition-colors flex items-center justify-center gap-2 border border-gray-200">
                            <LucideChevronDown
                                :class="['w-4 h-4 transition-transform', expandedMath ? 'rotate-180' : '']" />
                            {{ expandedMath ? 'Collapse' : 'View Details' }}
                        </button>
                    </div>
                </div>

                <!-- Summary Section (Always Visible) -->
                <div class="p-4 sm:p-6">
                    <div v-if="profilingData.math.summary" class="grid grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                            <div class="text-xs text-blue-600 font-medium mb-1">Level</div>
                            <div class="text-lg font-bold text-blue-900">
                                {{ profilingData.math.summary.level }}
                            </div>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
                            <div class="text-xs text-purple-600 font-medium mb-1">Total Score</div>
                            <div class="text-lg font-bold text-purple-900">
                                {{ profilingData.math.summary.total_score }}
                            </div>
                        </div>
                    </div>

                    <!-- Expandable Details -->
                    <div v-show="expandedMath" class="mt-6 space-y-4 border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Question Responses</h3>
                        <div v-if="profilingData.math.responses && profilingData.math.responses.length > 0"
                            class="space-y-3">
                            <div v-for="(response, index) in profilingData.math.responses" :key="response.id" :class="[
                                'rounded-lg p-4 border-2 transition-colors',
                                response.is_correct
                                    ? 'bg-green-50 border-green-200'
                                    : 'bg-red-50 border-red-200',
                            ]">
                                <div class="flex items-start gap-3">
                                    <span :class="[
                                        'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm',
                                        response.is_correct
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-red-100 text-red-700',
                                    ]">
                                        {{ index + 1 }}
                                    </span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 mb-2">
                                            {{ response.question_text }}
                                        </p>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs text-gray-600">Your Answer:</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ response.answer_text
                                            }}</span>
                                            <span :class="[
                                                'px-2 py-1 rounded text-xs font-medium',
                                                response.is_correct
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-red-100 text-red-700',
                                            ]">
                                                {{ response.is_correct ? 'Correct' : 'Incorrect' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 italic text-center py-4">
                            No Math responses found.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Information Card -->
            <div data-onboarding="profile-info" class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0 mb-4 sm:mb-6">
                    <h2 class="text-lg sm:text-xl font-bold flex items-center">
                        <LucideUser class="w-4 h-4 sm:w-5 sm:h-5 mr-2 text-blue-600" />
                        Profile Information
                    </h2>
                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <button v-if="!isEditing" @click="enableEditMode"
                            class="min-h-[44px] px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2">
                            <LucideEdit class="w-4 h-4" />
                            Edit
                        </button>
                        <div v-else class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                            <button @click="cancelEdit"
                                class="min-h-[44px] px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button @click="saveProfile" :disabled="saving"
                                class="min-h-[44px] px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <LucideSave v-if="!saving" class="w-4 h-4" />
                                <LucideLoader2 v-else class="w-4 h-4 animate-spin" />
                                {{ saving ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Name Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input v-if="isEditing" v-model="editForm.name" type="text"
                            class="w-full min-h-[44px] py-2.5 px-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-blue-500 transition-colors text-base"
                            placeholder="Enter your name">
                        <div v-else class="p-3 bg-gray-50 rounded-lg">
                            <p class="font-semibold text-gray-800">{{ authStore.user?.name || '—' }}</p>
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input v-if="isEditing" v-model="editForm.email" type="email"
                            class="w-full min-h-[44px] py-2.5 px-3 border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-blue-500 transition-colors text-base"
                            placeholder="Enter your email">
                        <div v-else class="p-3 bg-gray-50 rounded-lg">
                            <p class="font-semibold text-gray-800">{{ authStore.user?.email || '—' }}</p>
                        </div>
                    </div>

                    <!-- Role (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="font-semibold text-gray-800">{{ capitalize(authStore.roles || 'Student') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Profiling Data State -->
            <div v-if="!profilingLoading && (!profilingData || (!profilingData.vark && !profilingData.math))"
                class="bg-white rounded-xl shadow-md p-12 text-center border border-gray-100">
                <div
                    class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full mb-6">
                    <LucideUserCircle class="w-10 h-10 text-gray-400" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">No Profiling Data</h2>
                <p class="text-gray-600 mb-6">You haven't completed the profiling assessment yet.</p>
                <button @click="router.push('/profiling')"
                    class="min-h-[44px] px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all">
                    Take Assessment
                </button>
            </div>
        </div>
    </div>
</template>
