<script setup>
import { computed, ref, onMounted, nextTick } from "vue";
import { LucideRefreshCw, LucideUser, LucideBrain, LucideBarChart3, LucideFileText, LucideBookOpen, LucideCalculator, LucideTrophy, LucideZap, LucideTrendingUp } from "lucide-vue-next";
import { useAuthStore } from "@/stores/authStore";
import { useRouter } from "vue-router";
import { confirmAlert } from "@/utils/alert";
import Aurora from "@/components/ui/Backgrounds/Aurora/Aurora.vue";
import RadarChart from "@/components/charts/RadarChart.vue";
import LineChart from "@/components/charts/LineChart.vue";
import { getQuizAttempts } from "@/services/MathQuestService";
import OnboardingTour from "@/components/Onboarding/OnboardingTour.vue";
import { completeOnboarding } from "@/services/onboardingService";

const authStore = useAuthStore();
const router = useRouter();

const capitalize = (s) => (s ? String(s).charAt(0).toUpperCase() + String(s).slice(1) : s);

const roleDisplay = computed(() => {
  const u = authStore.user;
  if (!u) return "—";
  if (Array.isArray(u.roles) && u.roles.length) return capitalize(u.roles[0]);
  if (u.role && typeof u.role === "string") return capitalize(u.role);
  return "—";
});

const varkLabel = computed(() => {
  const raw = authStore.learning_results?.vark;
  if (!raw) return "Unknown";
  const key = String(raw).trim().toLowerCase();
  const map = {
    v: "Visual",
    a: "Auditori",
    r: "Baca/Tulis",
    k: "Kinestetik",
  };
  return map[key] || String(raw);
});

const varkDescription = computed(() => {
  const raw = authStore.learning_results?.vark;
  if (!raw) return "Take the profiling test to discover your learning style.";
  const key = String(raw).trim().toLowerCase();
  const map = {
    visual: "Learn best through images, graphs, charts, and visualizations. Use diagrams, charts, and colored notes to understand concepts.",
    auditory: "Learn through listening: lectures, discussions, audio recordings, and verbal explanations help understanding.",
    read: "Learn through reading and writing: written notes, articles, summaries, and written exercises are effective.",
    kinesthetic: "Learn through physical activities and hands-on experiences: experiments, practical exercises, and simulations.",
    multimodal: "Learn with a combination of various learning styles. You are flexible and can adapt methods according to the situation.",
  };
  return map[key] || "Your personalized learning style description.";
});

const handleLogout = async () => {
  const confirmed = await confirmAlert("Are you sure?", "You will be logged out.");
  if (confirmed) {
    authStore.logoutWithPopup();
    router.push("/");
  }
};

// 🔹 Math capability data - computed from learning results
const mathCapability = computed(() => {
  const mathLevel = authStore.learning_results?.math;
  const totalScore = authStore.learning_results?.total_score || 0;
  const scorePercentage = totalScore * 10; // Assuming total_score is 0-10 scale
  
  // Determine label based on level
  let label = "Initial Math Knowledge";
  if (mathLevel === "Beginner") {
    label = "Beginner Level";
  } else if (mathLevel === "Advanced") {
    label = "Advanced Level";
  } else if (!mathLevel) {
    label = "Not Assessed";
  }
  
  return {
    label,
    level: scorePercentage,
    color: "blue",
  };
});

const recentActivity = ref([]);
const allAttempts = ref([]);
const loadingActivity = ref(false);
const stats = ref({
  totalQuizzes: 0,
  totalExp: 0,
  averageScore: 0,
  totalQuestions: 0
});

// Format time ago
const formatTimeAgo = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);

  if (diffInSeconds < 60) return 'Just now';
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} days ago`;
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

// Fetch recent quiz attempts
const fetchRecentActivity = async () => {
  try {
    loadingActivity.value = true;
    const result = await getQuizAttempts();

    if (result.status === 'success' && result.data && Array.isArray(result.data)) {
      // Store all attempts for chart
      allAttempts.value = result.data;
      
      // Get the 3 most recent attempts
      recentActivity.value = result.data.slice(0, 3).map(attempt => ({
        id: attempt.id,
        title: `Topic ${attempt.topic_id}${attempt.subtopic_id ? ` - ${attempt.subtopic_id}` : ''}`,
        topic: attempt.subtopic_id || `Topic ${attempt.topic_id}`,
        time: formatTimeAgo(attempt.completed_at || attempt.created_at),
        score: parseFloat(attempt.score_percentage) || 0,
        status: 'completed',
        exp_gained: attempt.exp_gained
      }));

      // Calculate statistics
      if (result.data.length > 0) {
        const totalQuizzes = result.data.length;
        const totalExp = result.data.reduce((sum, attempt) => sum + (parseFloat(attempt.exp_gained) || 0), 0);
        const totalScore = result.data.reduce((sum, attempt) => sum + (parseFloat(attempt.score_percentage) || 0), 0);
        const averageScore = totalScore / totalQuizzes;
        const totalQuestions = result.data.reduce((sum, attempt) => sum + (parseInt(attempt.total_questions) || 0), 0);

        stats.value = {
          totalQuizzes,
          totalExp,
          averageScore: Math.round(averageScore * 10) / 10,
          totalQuestions
        };
      }
    }
  } catch (err) {
    console.error('Error fetching recent activity:', err);
    recentActivity.value = [];
    allAttempts.value = [];
  } finally {
    loadingActivity.value = false;
  }
};

const viewQuizReview = (attemptId) => {
  router.push({ name: 'quiz_review', params: { id: attemptId } });
};


const varkScores = computed(() => {
  return authStore.learning_results?.vark_scores || {
    score_v: 0,
    score_a: 0,
    score_r: 0,
    score_k: 0
  };
});

// Process quiz attempts data for the progress chart
const progressChartData = computed(() => {
  if (!allAttempts.value || allAttempts.value.length === 0) {
    return [];
  }

  // Sort attempts by date (oldest to newest)
  const sortedAttempts = [...allAttempts.value].sort((a, b) => {
    const dateA = new Date(a.completed_at || a.created_at || 0);
    const dateB = new Date(b.completed_at || b.created_at || 0);
    return dateA - dateB;
  });

  // Map to chart data format
  return sortedAttempts.map((attempt, index) => ({
    attemptNumber: index + 1,
    accuracy: parseFloat(attempt.score_percentage) || 0
  }));
});

// Onboarding tour
const showOnboardingTour = ref(false);

const tourSteps = [
  {
    title: "Welcome to Your Dashboard",
    description: "This is your personalized dashboard where you can track your learning progress and access key features.",
    targetSelector: "[data-onboarding='welcome-header']"
  },
  {
    title: "Your Learning Style",
    description: "Discover your personalized learning style (VARK) and see how you learn best. Take the assessment to get personalized recommendations.",
    targetSelector: "[data-onboarding='learning-style']"
  },
  {
    title: "Track Your Progress",
    description: "Monitor your accuracy improvement over time with this progress chart. See how you're improving with each quiz attempt.",
    targetSelector: "[data-onboarding='student-progress']"
  },
  {
    title: "Recent Activity",
    description: "View your recent quiz attempts and see your performance at a glance. Click on any activity to review your answers.",
    targetSelector: "[data-onboarding='recent-activity']"
  },
  {
    title: "Your Statistics",
    description: "Check your overall statistics including total quizzes completed, experience points earned, average scores, and questions answered.",
    targetSelector: "[data-onboarding='statistics']"
  }
];

const handleTourComplete = async () => {
  try {
    const result = await completeOnboarding('onboarding_dashboard');
    if (result.success) {
      // Update authStore with new onboarding flags
      if (result.data?.onboarding_flags) {
        authStore.updateOnboardingFlags(result.data.onboarding_flags);
      }
    }
  } catch (error) {
    console.error('Error completing onboarding:', error);
  } finally {
    showOnboardingTour.value = false;
  }
};

const handleTourSkip = async () => {
  // Mark as complete even when skipped
  await handleTourComplete();
};

const checkOnboardingStatus = async () => {
  // Check if user has completed dashboard onboarding
  const onboardingFlags = authStore.onboarding_flags;
  if (!onboardingFlags || !onboardingFlags.onboarding_dashboard) {
    // Wait for DOM to be ready
    await nextTick();
    // Small delay to ensure all elements are rendered
    setTimeout(() => {
      showOnboardingTour.value = true;
    }, 500);
  }
};

onMounted(async () => {
  await fetchRecentActivity();
  await checkOnboardingStatus();
});
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 pb-6 sm:pb-10 px-2 sm:px-4 md:px-6">
    <!-- Onboarding Tour -->
    <OnboardingTour
      :steps="tourSteps"
      :is-active="showOnboardingTour"
      @complete="handleTourComplete"
      @skip="handleTourSkip"
    />
    <div class="relative rounded-2xl overflow-hidden">
      <Aurora :color-stops="['#60A5FA', '#A78BFA', '#FBCFE8']" :amplitude="0.65" :blend="0.35" :speed="1.5"
        :intensity="0.65" class="absolute inset-0 w-full h-full" />

      <!-- soft light overlay for subtle desaturation and contrast -->
      <div class="absolute inset-0 bg-white/8 backdrop-blur-sm z-10 pointer-events-none"></div>

      <div class="relative z-20 p-4 sm:p-6 md:p-8 text-gray-900 mt-10 md:mt-0" data-onboarding="welcome-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2 sm:pt-4">Welcome back, {{ authStore.user?.name || 'Student' }}!</h1>
            <p class="text-gray-600 text-sm sm:text-base md:text-lg">Ready to solve some math problems today?</p>
          </div>
          <div class="mt-0 md:mt-0 bg-white/90 rounded-xl px-4 sm:px-5 py-3 sm:py-4 border border-white/60 shadow-sm">
            <div class="flex items-center gap-3">
              <div class="bg-gradient-to-br from-blue-500 to-purple-500 p-2 sm:p-2.5 rounded-lg">
                <LucideTrophy class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
              </div>
              <div>
                <div class="text-xs text-gray-500 mb-0.5 font-medium">Level</div>
                <div
                  class="text-xl sm:text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                  {{ authStore.user?.level || 'Unknown' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
      <!-- Learning Style -->
      <div
        data-onboarding="learning-style"
        class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-4 sm:p-5 border border-gray-100 group">
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div
              class="bg-gradient-to-br from-blue-400 to-purple-500 p-3 rounded-xl shadow-md group-hover:scale-110 transition-transform duration-300">
              <LucideBrain class="w-5 h-5 text-white" />
            </div>
            <div>
              <p class="text-md font-bold text-blue-600 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-pulse"></span>
                {{ varkLabel }}
              </p>
              <p class="text-gray-700 text-xs leading-relaxed">
                {{ varkDescription }}
              </p>
            </div>
          </div>
          <span
            class="bg-gradient-to-r from-blue-500 to-purple-500 text-white text-xs font-semibold px-3 py-1.5 rounded-full shadow-sm">
            Personalized
          </span>
        </div>

        <!-- Radar Chart -->
        <div class="flex flex-row items-center gap-2 sm:gap-4 mb-4 bg-gradient-to-br from-blue-50/50 to-purple-50/50 rounded-xl p-2 sm:p-3 md:p-4">
          <div class="flex-1 h-44 sm:h-52 md:h-60 lg:h-72 w-full min-w-0">
            <RadarChart :vark-scores="varkScores" />
          </div>
          <div class="flex flex-col gap-1.5 sm:gap-2 pr-1 sm:pr-2 flex-shrink-0">
            <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs">
              <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-blue-500 flex-shrink-0"></div>
              <div class="flex flex-col min-w-[50px] sm:min-w-[70px]">
                <span class="font-semibold text-gray-700">V</span>
                <span class="text-[9px] sm:text-[10px] text-gray-500">Visual</span>
              </div>
              <span class="font-bold text-blue-600 text-[10px] sm:text-xs">{{ varkScores.score_v || 0 }}</span>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs">
              <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-blue-500 flex-shrink-0"></div>
              <div class="flex flex-col min-w-[50px] sm:min-w-[70px]">
                <span class="font-semibold text-gray-700">A</span>
                <span class="text-[9px] sm:text-[10px] text-gray-500">Auditory</span>
              </div>
              <span class="font-bold text-blue-600 text-[10px] sm:text-xs">{{ varkScores.score_a || 0 }}</span>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs">
              <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-blue-500 flex-shrink-0"></div>
              <div class="flex flex-col min-w-[50px] sm:min-w-[70px]">
                <span class="font-semibold text-gray-700">R</span>
                <span class="text-[9px] sm:text-[10px] text-gray-500">Read/Write</span>
              </div>
              <span class="font-bold text-blue-600 text-[10px] sm:text-xs">{{ varkScores.score_r || 0 }}</span>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs">
              <div class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-blue-500 flex-shrink-0"></div>
              <div class="flex flex-col min-w-[50px] sm:min-w-[70px]">
                <span class="font-semibold text-gray-700">K</span>
                <span class="text-[9px] sm:text-[10px] text-gray-500">Kinesthetic</span>
              </div>
              <span class="font-bold text-blue-600 text-[10px] sm:text-xs">{{ varkScores.score_k || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Retake test button -->
        <button @click="router.push('/profiling')"
          class="w-full min-h-[44px] bg-gradient-to-r from-blue-50 to-purple-50 hover:from-blue-100 hover:to-purple-100 text-blue-700 font-semibold px-4 py-3 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 group shadow-sm hover:shadow-md transform hover:-translate-y-0.5 border border-blue-100">
          <template v-if="varkLabel === 'Unknown'">
            <LucideUser class="w-4 h-4 group-hover:scale-110 transition-transform" />
          </template>
          <template v-else>
            <LucideRefreshCw class="w-4 h-4 group-hover:rotate-180 transition-transform duration-500" />
          </template>
          <span class="text-sm">
            {{ varkLabel === 'Unknown' ? 'Take Personality Test' : 'Retake Assessment' }}
          </span>
        </button>
      </div>

      <!-- Student Progress -->
      <div data-onboarding="student-progress" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-5 border border-gray-100 group">
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div
              class="bg-gradient-to-br from-blue-400 to-purple-500 p-3 rounded-xl shadow-md group-hover:scale-110 transition-transform duration-300">
              <LucideTrendingUp class="w-5 h-5 text-white" />
            </div>
            <div>
              <p class="text-md font-bold text-blue-600 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-pulse"></span>
                Student Progress
              </p>
              <p class="text-xs text-gray-900">
                Track your accuracy improvement up to 50 latest quiz attempts.
              </p>
            </div>
          </div>
        </div>

        <!-- Chart Display -->
        <div
          class="bg-gradient-to-br from-blue-50/50 to-purple-50/50 rounded-xl p-3 sm:p-4 mb-4">
          <div v-if="progressChartData.length > 0" class="h-48 sm:h-56 md:h-64">
            <LineChart :attempts-data="progressChartData" />
          </div>
          <div v-else class="h-48 sm:h-56 md:h-64 flex items-center justify-center text-gray-500 text-sm">
            <div class="text-center">
              <p class="mb-2">No quiz attempts yet</p>
              <p class="text-xs">Start practicing to see your progress!</p>
            </div>
          </div>
        </div>

        <!-- Summary Stats -->
        <div v-if="progressChartData.length > 0" class="bg-white rounded-lg p-3 border-2 border-dashed border-blue-300">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Total Attempts:</span>
            <div class="flex items-center gap-2">
              <div class="w-2 h-2 rounded-full animate-pulse bg-blue-600"></div>
              <span class="text-lg font-bold text-blue-700">
                {{ progressChartData.length }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">

      <!-- Recent Activity -->
      <div data-onboarding="recent-activity" class="lg:col-span-2 bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
        <h2 class="text-lg sm:text-xl font-bold mb-3 sm:mb-4 flex items-center">
          <div
            class="bg-gradient-to-br from-blue-400 to-purple-500 p-2 sm:p-3 rounded-xl shadow-md group-hover:scale-110 transition-transform duration-300">
            <LucideBookOpen class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
          </div>
          <span class="text-sm sm:text-md text-blue-600 uppercase font-bold ml-2">Recent Activity</span>
        </h2>
        <div class="space-y-2 sm:space-y-3">
          <div v-if="loadingActivity" class="text-center py-6 sm:py-8 text-gray-500 text-sm sm:text-base">
            Loading recent activity...
          </div>
          <div v-else-if="recentActivity.length === 0" class="text-center py-6 sm:py-8 text-gray-500 text-sm sm:text-base">
            No recent quiz attempts. Start practicing to see your activity here!
          </div>
          <div v-else>
            <div v-for="(activity, idx) in recentActivity" :key="activity.id || idx"
              @click="activity.id && viewQuizReview(activity.id)" :class="[
                'flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 sm:p-4 rounded-lg transition-all cursor-pointer border mt-2 gap-3 sm:gap-0',
                activity.id && 'hover:shadow-md',
                'bg-gradient-to-r from-blue-50 via-purple-50 to-pink-50 border-blue-100 hover:from-blue-100 hover:via-purple-100 hover:to-pink-100'
              ]">
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-sm sm:text-base text-gray-800 mb-1 truncate">{{ activity.title }}</h3>
                <p class="text-xs sm:text-sm text-gray-600 mb-2">{{ activity.topic }}</p>
                <div class="flex items-center gap-2">
                  <div class="flex items-center gap-1.5 bg-amber-50 px-2 sm:px-2.5 py-1 rounded-md border border-amber-200">
                    <LucideZap class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-amber-600" />
                    <span class="text-xs font-semibold text-amber-700">{{ activity.exp_gained || 0 }} EXP</span>
                  </div>
                </div>
              </div>
              <div class="text-left sm:text-right ml-0 sm:ml-4 flex-shrink-0">
                <span class="inline-block px-2 sm:px-3 py-1 sm:py-1.5 rounded-full text-xs font-semibold mb-1.5" :class="{
                  'bg-green-100 text-green-700': activity.score >= 80,
                  'bg-yellow-100 text-yellow-700': activity.score >= 60 && activity.score < 80,
                  'bg-red-100 text-red-700': activity.score < 60,
                }">
                  {{ typeof activity.score === 'number' ? activity.score.toFixed(0) : activity.score }}%
                </span>
                <p class="text-xs text-gray-500">{{ activity.time }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics -->
      <div data-onboarding="statistics" class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
        <h2 class="text-lg sm:text-xl font-bold mb-3 sm:mb-4 flex items-center">
          <div
            class="bg-gradient-to-br from-blue-400 to-purple-500 p-2 sm:p-3 rounded-xl shadow-md group-hover:scale-110 transition-transform duration-300">
            <LucideBarChart3 class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
          </div>
          <span class="text-sm sm:text-md text-blue-600 uppercase font-bold ml-2">Statistics</span>
        </h2>
        <div class="grid grid-cols-2 lg:grid-cols-1 gap-3 sm:gap-4">
          <!-- Total Quizzes -->
          <div class="p-3 sm:p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 mb-1 font-medium">Total Quizzes</p>
                <p class="text-xl sm:text-2xl font-bold text-blue-700">{{ stats.totalQuizzes }}</p>
              </div>
              <div class="bg-blue-200 p-2 sm:p-3 rounded-lg">
                <LucideFileText class="w-5 h-5 sm:w-6 sm:h-6 text-blue-700" />
              </div>
            </div>
          </div>

          <!-- Total EXP -->
          <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg border border-amber-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 mb-1 font-medium">Total EXP</p>
                <p class="text-xl sm:text-2xl font-bold text-amber-700">{{ stats.totalExp }}</p>
              </div>
              <div class="bg-amber-200 p-2 sm:p-3 rounded-lg">
                <LucideZap class="w-5 h-5 sm:w-6 sm:h-6 text-amber-700" />
              </div>
            </div>
          </div>

          <!-- Average Score -->
          <div class="p-3 sm:p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-lg border border-green-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 mb-1 font-medium">Average Score</p>
                <p class="text-xl sm:text-2xl font-bold text-green-700">{{ stats.averageScore.toFixed(1) }}%</p>
              </div>
              <div class="bg-green-200 p-2 sm:p-3 rounded-lg">
                <LucideBarChart3 class="w-5 h-5 sm:w-6 sm:h-6 text-green-700" />
              </div>
            </div>
          </div>

          <!-- Total Questions -->
          <div class="p-3 sm:p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg border border-purple-200">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-600 mb-1 font-medium">Questions Answered</p>
                <p class="text-xl sm:text-2xl font-bold text-purple-700">{{ stats.totalQuestions }}</p>
              </div>
              <div class="bg-purple-200 p-2 sm:p-3 rounded-lg">
                <LucideCalculator class="w-5 h-5 sm:w-6 sm:h-6 text-purple-700" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>