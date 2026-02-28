<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { LucideBookOpen, LucideArrowRight, LucideList, LucideTarget, LucideCalculator, LucideInfo, LucideZap } from 'lucide-vue-next'
import Aurora from "@/components/ui/Backgrounds/Aurora/Aurora.vue";
import OnboardingTour from '@/components/Onboarding/OnboardingTour.vue'
import { completeOnboarding } from '@/services/onboardingService'

const router = useRouter()
const authStore = useAuthStore()

// State
const selectedTopic = ref('1')
const selectedSubtopic = ref(null)
const selectedQuestionType = ref('multiple_choice')
const selectedLanguage = ref('english')
const selectedTP = ref(1)
const selectedDifficulty = ref('easy')
const isLoading = ref(false)
const error = ref(null)

// VARK style is now handled by backend from user profile

// Languages
const languages = ref([
    {
        code: 'english',
        name: 'English',
        description: 'Questions in English',
        icon: '🇬🇧'
    },
    {
        code: 'malay',
        name: 'Bahasa Malaysia',
        description: 'Soalan dalam Bahasa Malaysia',
        icon: '🇲🇾'
    }
])

// Question types - only multiple choice available for practice
const questionTypes = ref([
    {
        code: 'multiple_choice',
        name: 'Multiple Choice',
        description: 'Choose the correct answer from the options',
        icon: LucideList,
        color: 'green'
    },
])

// Available topics (for now only Topic 1)
const topics = ref([
    {
        code: '1',
        name: 'Rational Numbers',
        description: 'Rational Numbers',
        icon: LucideCalculator
    }
])

// Subtopics for Topic 1
const subtopics = ref({
    '1': [
        {
            code: '1.1',
            name: 'Integers',
            description: 'Positive and negative whole numbers'
        },
        {
            code: '1.2',
            name: 'Basic arithmetic operations involving integers',
            description: 'basic arithmetic'
        },
        {
            code: '1.3',
            name: 'Positive and negative fractions',
            description: 'positive and negative fractions'
        },
        {
            code: '1.4',
            name: 'Positive and negative decimals',
            description: 'positive and negative decimals'
        },
        {
            code: '1.5',
            name: 'Rational numbers',
            description: 'Comprehensive concept of rational numbers'
        }
    ]
})

// TP options (Mastery Level 1-6)
const tpOptions = ref([
    { value: 1, label: 'TP1', description: 'Mastery Level 1' },
    { value: 2, label: 'TP2', description: 'Mastery Level 2' },
    { value: 3, label: 'TP3', description: 'Mastery Level 3' },
    { value: 4, label: 'TP4', description: 'Mastery Level 4' },
    { value: 5, label: 'TP5', description: 'Mastery Level 5' },
    { value: 6, label: 'TP6', description: 'Mastery Level 6' },
])

// Difficulty options
const difficultyOptions = ref([
    { value: 'easy', label: 'Easy', description: 'Beginner level questions' },
    { value: 'hard', label: 'Hard', description: 'Advanced level questions' },
])

// Computed
const currentSubtopics = computed(() => {
    return subtopics.value[selectedTopic.value] || []
})

const canProceed = computed(() => {
    return selectedTopic.value &&
        selectedSubtopic.value &&
        selectedLanguage.value &&
        selectedTP.value !== null &&
        selectedDifficulty.value !== null
})

const selectedSubtopicDetails = computed(() => {
    return currentSubtopics.value.find(s => s.code === selectedSubtopic.value)
})

const selectedQuestionTypeDetails = computed(() => {
    return questionTypes.value[0] // Always multiple choice
})

const selectedLanguageDetails = computed(() => {
    return languages.value.find(l => l.code === selectedLanguage.value)
})

const selectedTPDetails = computed(() => {
    return tpOptions.value.find(tp => tp.value === selectedTP.value)
})

const selectedDifficultyDetails = computed(() => {
    return difficultyOptions.value.find(d => d.value === selectedDifficulty.value)
})

// Methods
const selectSubtopic = (subtopicCode) => {
    selectedSubtopic.value = subtopicCode
}

const selectLanguage = (languageCode) => {
    selectedLanguage.value = languageCode
}

const selectTP = (tpValue) => {
    selectedTP.value = tpValue
}

const selectDifficulty = (difficultyValue) => {
    selectedDifficulty.value = difficultyValue
}

const startPractice = () => {
    if (!canProceed.value || isLoading.value) {
        return
    }

    error.value = null
    isLoading.value = true

    // Navigate to practice view with query params (always multiple_choice)
    // vark_style is handled by backend from user profile
    router.push({
        name: 'mathpractice_view',
        query: {
            topic: selectedTopic.value,
            subtopic: selectedSubtopic.value,
            question_format: 'multiple_choice',
            language: selectedLanguage.value,
            tp: selectedTP.value,
            difficulty: selectedDifficulty.value
        }
    })
}

// Onboarding tour
const showOnboardingTour = ref(false)

const tourSteps = [
  {
    title: "Drill Builder (Practice Mode)",
    description: "This is a practice/drill mode for trying out questions. Unlike MathQuest Quiz, this mode does NOT give EXP points or track analytics. It's just for practice and learning with AI tutor support.",
    targetSelector: "[data-onboarding='builder-header']"
  },
  {
    title: "Select Practice Settings",
    description: "Choose your language, mastery level (TP), and difficulty. These settings help customize your practice questions. Remember: no EXP or analytics are tracked in drill mode.",
    targetSelector: "[data-onboarding='question-format']"
  },
  {
    title: "Choose Your Subtopic",
    description: "Select the subtopic you want to practice. You can practice freely here without worrying about performance tracking - it's all about learning and trying different approaches.",
    targetSelector: "[data-onboarding='subtopic-selection']"
  },
  {
    title: "Start Practice",
    description: "Click 'Start Practice' to begin your drill session. You'll have access to an AI tutor chatbot for help, but remember: this is practice mode - no EXP or analytics are recorded.",
    targetSelector: "[data-onboarding='start-button']"
  }
]

const handleTourComplete = async () => {
  try {
    const result = await completeOnboarding('onboarding_mathpractice')
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
  if (!onboardingFlags || !onboardingFlags.onboarding_mathpractice) {
    await nextTick()
    setTimeout(() => {
      showOnboardingTour.value = true
    }, 500)
  }
}

onMounted(async () => {
  await checkOnboardingStatus()
})
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
        <!-- Header Section with Aurora -->
        <div class="relative rounded-2xl overflow-hidden">
            <Aurora :color-stops="['#60A5FA', '#A78BFA', '#FBCFE8']" :amplitude="0.65" :blend="0.35" :speed="1.5"
                :intensity="0.65" class="absolute inset-0 w-full h-full" />

            <div class="absolute inset-0 bg-white/8 backdrop-blur-sm z-10 pointer-events-none"></div>

            <div class="relative z-20 p-4 sm:p-6 md:p-8 text-gray-900 mt-10 md:mt-0" data-onboarding="builder-header">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold mb-2 flex items-center gap-2">
                            Drill Builder
                            <LucideTarget class="w-5 h-5 sm:w-7 sm:h-7 text-blue-600" />
                        </h1>
                        <p class="text-gray-600 text-sm sm:text-base md:text-lg">Select your practice settings to start</p>
                        <p class="text-gray-500 text-sm sm:text-base"> Drill will help you master the topic with help of Chatbot but you will not gain EXP </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Right: Selection Options -->
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                <!-- Question Format - All Selections Compact -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100" data-onboarding="question-format">
                    <div class="flex items-center mb-3 sm:mb-4">
                        <div
                            class="bg-gradient-to-br from-blue-400 to-purple-500 p-2 sm:p-3 rounded-xl shadow-md mr-2 sm:mr-3 flex items-center justify-center">
                            <LucideList class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900">Question Format</h2>
                    </div>

                    <!-- Compact Selection Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <!-- Language Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Language</label>
                            <div class="flex gap-2">
                                <button v-for="lang in languages" :key="lang.code" @click="selectLanguage(lang.code)"
                                    class="min-h-[44px]" :class="[
                                        'flex items-center gap-1.5 px-3 py-2 rounded-lg border-2 text-sm font-medium transition-all flex-1 justify-center',
                                        selectedLanguage === lang.code
                                            ? 'border-blue-600 bg-blue-50 text-blue-900'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                                    ]">
                                    <span class="text-base">{{ lang.icon }}</span>
                                    <span>{{ lang.code === 'english' ? 'EN' : 'BM' }}</span>
                                    <svg v-if="selectedLanguage === lang.code" class="w-4 h-4 text-blue-600"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- TP Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">TP</label>
                            <div class="grid grid-cols-3 gap-1">
                                <button v-for="tp in tpOptions" :key="tp.value" @click="selectTP(tp.value)"
                                    class="min-h-[44px]" :class="[
                                        'flex items-center justify-center px-2 py-2 rounded-lg border-2 text-xs font-semibold transition-all relative',
                                        selectedTP === tp.value
                                            ? 'border-orange-600 bg-orange-50 text-orange-900'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                                    ]">
                                    {{ tp.label }}
                                    <svg v-if="selectedTP === tp.value"
                                        class="w-3 h-3 text-orange-600 absolute -top-1 -right-1 bg-white rounded-full"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Difficulty Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Difficulty</label>
                            <div class="flex gap-2">
                                <button v-for="difficulty in difficultyOptions" :key="difficulty.value"
                                    @click="selectDifficulty(difficulty.value)" class="min-h-[44px]" :class="[
                                        'flex items-center gap-2 px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all flex-1 justify-center',
                                        selectedDifficulty === difficulty.value
                                            ? 'border-indigo-600 bg-indigo-50 text-indigo-900'
                                            : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                                    ]">
                                    <span>{{ difficulty.label }}</span>
                                    <svg v-if="selectedDifficulty === difficulty.value" class="w-4 h-4 text-indigo-600"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subtopic Selection -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100" data-onboarding="subtopic-selection">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 flex items-center">
                        <div
                            class="bg-gradient-to-br from-blue-400 to-purple-500 p-2 sm:p-3 rounded-xl shadow-md mr-2 sm:mr-3 flex items-center justify-center">
                            <LucideBookOpen class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                        </div>
                        Select Subtopic
                    </h2>
                    <p class="text-sm text-gray-600 mb-3 sm:mb-4">Choose the subtopic you want to practice</p>

                    <div class="grid grid-cols-2 sm:grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4">
                        <button v-for="subtopic in currentSubtopics" :key="subtopic.code"
                            @click="selectSubtopic(subtopic.code)" class="min-h-[44px]" :class="[
                                'group p-4 rounded-xl border-2 transition-all text-left relative overflow-hidden hover:shadow-md',
                                selectedSubtopic === subtopic.code
                                    ? 'border-blue-600 bg-gradient-to-br from-blue-50 to-purple-50 shadow-lg sm:scale-105'
                                    : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                            ]">
                            <div v-if="selectedSubtopic === subtopic.code"
                                class="absolute top-3 right-3 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>

                            <div class="mb-3">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold',
                                    selectedSubtopic === subtopic.code
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-100 text-gray-700 group-hover:bg-blue-100'
                                ]">
                                    {{ subtopic.code }}
                                </span>
                            </div>

                            <h3 :class="[
                                'font-bold text-base mb-2 transition-colors',
                                selectedSubtopic === subtopic.code
                                    ? 'text-blue-900'
                                    : 'text-gray-900 group-hover:text-blue-700'
                            ]">
                                {{ subtopic.name }}
                            </h3>

                            <p class="text-sm text-gray-600 leading-relaxed">
                                {{ subtopic.description }}
                            </p>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Left: Summary Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100 h-full flex flex-col">
                    <div class="flex items-center gap-3 mb-6">
                        <div
                            class="bg-gradient-to-br from-blue-400 to-purple-500 p-3 rounded-xl shadow-md flex items-center justify-center">
                            <component :is="topics[0].icon" class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ topics[0].name }}</h2>
                            <p class="text-xs text-gray-600">{{ topics[0].description }}</p>
                        </div>
                    </div>

                    <div class="space-y-4 flex-1 flex flex-col">
                        <!-- Selected Settings Info -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide">Question Format
                                </p>
                                <div v-if="selectedLanguageDetails"
                                    class="flex items-center gap-1 bg-gray-100 px-2 py-1 rounded text-xs">
                                    <span>{{ selectedLanguageDetails.icon }}</span>
                                    <span class="text-gray-700 font-medium">{{ selectedLanguageDetails.code ===
                                        'english' ? 'EN' : 'BM' }}</span>
                                </div>
                            </div>
                            <div
                                class="rounded-xl p-4 border bg-gradient-to-r from-green-50 to-emerald-100 border-green-200">
                                <p class="font-semibold mb-1 text-gray-900">Multiple Choice</p>
                                <p class="text-xs text-gray-700">Choose the correct answer from the options</p>
                            </div>
                        </div>

                        <!-- Selected Subtopic -->
                        <div v-if="selectedSubtopicDetails">
                            <p class="text-xs text-gray-600 mb-2 font-semibold uppercase tracking-wide">Subtopic</p>
                            <div
                                class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-medium bg-white/60 px-2 py-1 rounded">{{
                                        selectedSubtopicDetails.code }}</span>
                                </div>
                                <p class="font-semibold mb-1 text-blue-900">{{ selectedSubtopicDetails.name }}</p>
                                <p class="text-xs text-blue-700">{{ selectedSubtopicDetails.description }}</p>
                            </div>
                        </div>

                        <!-- Selected TP -->
                        <div v-if="selectedTPDetails">
                            <p class="text-xs text-gray-600 mb-2 font-semibold uppercase tracking-wide">Mastery Level
                            </p>
                            <div
                                class="bg-gradient-to-r from-orange-50 to-yellow-50 border border-orange-200 rounded-xl p-4">
                                <p class="font-semibold mb-1 text-orange-900">{{ selectedTPDetails.label }}</p>
                                <p class="text-xs text-orange-700">{{ selectedTPDetails.description }}</p>
                            </div>
                        </div>

                        <!-- Selected Difficulty -->
                        <div v-if="selectedDifficultyDetails">
                            <p class="text-xs text-gray-600 mb-2 font-semibold uppercase tracking-wide">Difficulty</p>
                            <div
                                class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl p-4">
                                <p class="font-semibold mb-1 text-indigo-900">{{ selectedDifficultyDetails.label }}</p>
                                <p class="text-xs text-indigo-700">{{ selectedDifficultyDetails.description }}</p>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div v-if="error">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p class="text-sm text-red-800">{{ error }}</p>
                            </div>
                        </div>

                        <!-- Start Practice Button -->
                        <div class="pt-4 sm:pt-6 border-t border-gray-200 mt-auto" data-onboarding="start-button">
                            <button @click="startPractice" :disabled="!canProceed || isLoading" class="min-h-[44px]"
                                :class="[
                                    'w-full font-semibold px-4 py-3 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 group shadow-sm border',
                                    canProceed && !isLoading
                                        ? 'bg-gradient-to-r from-blue-50 to-purple-50 hover:from-blue-100 hover:to-purple-100 text-blue-700 border-blue-100 hover:shadow-md transform hover:-translate-y-0.5'
                                        : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                                ]">
                                <span>{{ isLoading ? 'Loading...' : 'Start Practice' }}</span>
                                <LucideArrowRight v-if="!isLoading" :size="20"
                                    class="group-hover:translate-x-1 transition-transform" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>



        </div>
    </div>
</template>

<style scoped>
button:disabled {
    transform: none !important;
}
</style>
