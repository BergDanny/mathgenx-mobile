<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useMathQuestStore } from '@/stores/MathQuestStore'
import { useAuthStore } from '@/stores/authStore'
import { LucideBookOpen, LucideArrowRight, LucideSparkles, LucideList, LucideEdit3, LucideLayers, LucideTarget, LucideFileText, LucideCalculator, LucideLock } from 'lucide-vue-next'
import Aurora from "@/components/ui/Backgrounds/Aurora/Aurora.vue";
import QuestionGenerationModal from '@/components/Modal/QuestionGenerationModal.vue'
import OnboardingTour from '@/components/Onboarding/OnboardingTour.vue'
import { completeOnboarding } from '@/services/onboardingService'

const router = useRouter()
const mathQuestStore = useMathQuestStore()
const authStore = useAuthStore()

// Configuration: Set to false to enable subjective question type
const DISABLE_SUBJECTIVE = true

// State
const selectedTopic = ref('1')
const selectedSubtopic = ref(null)
const selectedQuestionType = ref('multiple_choice')
const selectedLanguage = ref('english')
const isGenerating = ref(false)
const generationError = ref(null)
const showGenerationModal = ref(false)
const generationStep = ref(-1)

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

// Question types
const questionTypes = ref([
    {
        code: 'multiple_choice',
        name: 'Multiple Choice',
        description: 'Choose the correct answer from the options',
        icon: LucideList,
        color: 'green'
    },
    {
        code: 'subjective',
        name: 'Subjective',
        description: 'Write a brief answer to the question',
        icon: LucideEdit3,
        color: 'purple'
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
    // Add more topics later
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

// Computed
const currentSubtopics = computed(() => {
    return subtopics.value[selectedTopic.value] || []
})

const canProceed = computed(() => {
    // Don't allow proceeding if subjective is selected and disabled
    if (DISABLE_SUBJECTIVE && selectedQuestionType.value === 'subjective') {
        return false
    }
    return selectedTopic.value && selectedSubtopic.value && selectedQuestionType.value && selectedLanguage.value
})

const selectedSubtopicDetails = computed(() => {
    return currentSubtopics.value.find(s => s.code === selectedSubtopic.value)
})

const selectedQuestionTypeDetails = computed(() => {
    return questionTypes.value.find(qt => qt.code === selectedQuestionType.value)
})

const selectedLanguageDetails = computed(() => {
    return languages.value.find(l => l.code === selectedLanguage.value)
})

// Methods
const selectSubtopic = (subtopicCode) => {
    selectedSubtopic.value = subtopicCode
}

const selectQuestionType = (typeCode) => {
    // Prevent selecting subjective if disabled
    if (DISABLE_SUBJECTIVE && typeCode === 'subjective') {
        return
    }
    selectedQuestionType.value = typeCode
}

const selectLanguage = (languageCode) => {
    selectedLanguage.value = languageCode
}

const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms))

const generateQuestions = async () => {
    if (!canProceed.value || isGenerating.value) {
        return
    }

    isGenerating.value = true
    generationError.value = null
    showGenerationModal.value = true
    generationStep.value = -1

    try {
        // Clear previous questions to force new generation
        mathQuestStore.clearQuestions()

        // Step 0: Fetching skill level
        generationStep.value = 0
        await delay(800)

        // Step 1: Analyzing learning style
        generationStep.value = 1
        await delay(1000)

        // Step 2: Generating questions - start the animation
        generationStep.value = 2

        // Fetch new questions from API (this happens during step 2 animation)
        const result = await mathQuestStore.fetchQuestions(
            selectedTopic.value,
            selectedSubtopic.value,
            selectedQuestionType.value,
            selectedLanguage.value
        )

        if (result.success) {
            // Step 3: Almost ready - show completion
            generationStep.value = 3
            await delay(800)

            // Close modal and navigate
            showGenerationModal.value = false

            // Navigate to quiz page after successful generation
            router.push({
                name: 'mathquest_quiz',
                query: {
                    topic: selectedTopic.value,
                    subtopic: selectedSubtopic.value,
                    question_format: selectedQuestionType.value,
                    language: selectedLanguage.value
                }
            })
        } else {
            showGenerationModal.value = false
            generationError.value = result.message || "Failed to generate questions. Please try again."
        }
    } catch (error) {
        showGenerationModal.value = false
        generationError.value = "An unexpected error occurred. Please try again."
        console.error('Error generating questions:', error)
    } finally {
        isGenerating.value = false
        generationStep.value = -1
    }
}

// Onboarding tour
const showOnboardingTour = ref(false)

const tourSteps = [
  {
    title: "MathQuest Quiz Builder",
    description: "Welcome to MathQuest! This is a quiz mode where you'll earn Experience Points (EXP) and get detailed analytics. Your performance will be tracked to help you improve.",
    targetSelector: "[data-onboarding='builder-header']"
  },
  {
    title: "Select Question Type",
    description: "Choose your preferred question format. Questions are personalized based on your learning style. You'll earn EXP when you complete the quiz.",
    targetSelector: "[data-onboarding='question-type']"
  },
  {
    title: "Choose Your Subtopic",
    description: "Select the subtopic you want to practice. The quiz will be generated based on your learning profile and will award EXP upon completion.",
    targetSelector: "[data-onboarding='subtopic-selection']"
  },
  {
    title: "Start Your Quiz",
    description: "Click 'Start Practice' to generate your personalized quiz. After completing it, you'll earn EXP points and receive detailed performance analytics.",
    targetSelector: "[data-onboarding='start-button']"
  }
]

const handleTourComplete = async () => {
  try {
    const result = await completeOnboarding('onboarding_mathquest')
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
  if (!onboardingFlags || !onboardingFlags.onboarding_mathquest) {
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
                            MathQuest Builder
                            <LucideTarget class="w-5 h-5 sm:w-7 sm:h-7 text-blue-600" />
                        </h1>
                        <p class="text-gray-600 text-sm sm:text-base md:text-lg">Select a topic and question type to
                            start your practice</p>
                    </div>
                    <!-- <div class="mt-4 md:mt-0 bg-white/90 rounded-xl px-6 py-4 border border-white/60 shadow-sm">
                        <div class="text-sm text-gray-500 mb-1">Status</div>
                        <div class="text-2xl font-bold text-gray-800">Sedia</div>
                    </div> -->
                </div>
            </div>
        </div>

        <!-- Main Content - Single View -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">

            <!-- Right: Selection Options -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100 h-full flex flex-col">
                    <!-- Question Type Selection with Language Selector -->
                    <div class="mb-4 sm:mb-6" data-onboarding="question-type">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-0 mb-2">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 flex items-center">
                                <div
                                    class="bg-gradient-to-br from-blue-400 to-purple-500 p-2 sm:p-3 rounded-xl shadow-md mr-2 sm:mr-3 flex items-center justify-center">
                                    <LucideFileText class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                                </div>
                                Question Type
                            </h2>
                            <!-- Compact Language Selector -->
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                                <span class="text-xs text-gray-500 whitespace-nowrap">Language:</span>
                                <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                                    <button v-for="lang in languages" :key="lang.code"
                                        @click="selectLanguage(lang.code)" class="min-h-[44px]" :class="[
                                            'px-3 py-2 sm:py-1.5 rounded-md text-xs font-medium transition-all flex items-center gap-1.5',
                                            selectedLanguage === lang.code
                                                ? 'bg-white text-gray-900 shadow-sm'
                                                : 'text-gray-600 hover:text-gray-900'
                                        ]">
                                        <span class="text-sm">{{ lang.icon }}</span>
                                        <span>{{ lang.code === 'english' ? 'EN' : 'BM' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Choose how you want to answer questions</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button v-for="qType in questionTypes" :key="qType.code"
                                @click="selectQuestionType(qType.code)" 
                                :disabled="DISABLE_SUBJECTIVE && qType.code === 'subjective'"
                                class="min-h-[44px]" :class="[
                                    'p-4 rounded-lg border-2 transition-all text-center relative',
                                    DISABLE_SUBJECTIVE && qType.code === 'subjective'
                                        ? 'border-amber-300 bg-gradient-to-br from-amber-50 via-yellow-50 to-amber-100 cursor-not-allowed relative'
                                        : selectedQuestionType === qType.code
                                            ? 'border-blue-500 bg-gradient-to-r from-blue-50 to-purple-50 shadow-md sm:scale-105 hover:shadow-md'
                                            : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50 hover:shadow-md'
                                ]">
                                <!-- Coming Soon Badge for Subjective -->
                                <div v-if="DISABLE_SUBJECTIVE && qType.code === 'subjective'"
                                    class="absolute top-2 right-2 flex items-center gap-1 bg-gradient-to-r from-amber-400 to-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold shadow-lg z-10">
                                    <LucideLock class="w-3 h-3" />
                                    <span>Coming Soon</span>
                                </div>

                                <!-- Selected indicator for non-Coming Soon -->
                                <div v-if="selectedQuestionType === qType.code && !(DISABLE_SUBJECTIVE && qType.code === 'subjective')"
                                    class="absolute top-2 right-2 w-5 h-5 bg-blue-600 rounded-full flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <div class="text-xl mb-2" :class="DISABLE_SUBJECTIVE && qType.code === 'subjective' ? 'opacity-70' : ''">
                                    <component :is="qType.icon" class="w-5 h-5 mx-auto text-current" />
                                </div>
                                <h3 class="font-bold text-sm mb-1"
                                    :class="DISABLE_SUBJECTIVE && qType.code === 'subjective' ? 'text-gray-700' : 'text-gray-900'">{{
                                    qType.name }}</h3>
                                <p class="text-xs"
                                    :class="DISABLE_SUBJECTIVE && qType.code === 'subjective' ? 'text-gray-600' : 'text-gray-600'">{{
                                    qType.description }}</p>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 sm:mb-6" data-onboarding="subtopic-selection">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 flex items-center mt-10 md:mt-0">
                            <div
                                class="bg-gradient-to-br from-blue-400 to-purple-500 p-2 sm:p-3 rounded-xl shadow-md mr-2 sm:mr-3 flex items-center justify-center">
                                <LucideBookOpen class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
                            </div>
                            Select Your Subtopic
                        </h2>
                        <p class="text-sm text-gray-600">Choose the topic you want to practice</p>
                    </div>

                    <!-- Subtopics Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-4 mb-4 sm:mb-6 flex-1">
                        <button v-for="subtopic in currentSubtopics" :key="subtopic.code"
                            @click="selectSubtopic(subtopic.code)" class="min-h-[44px]" :class="[
                                'group p-4 rounded-xl border-2 transition-all text-left relative overflow-hidden hover:shadow-md',
                                selectedSubtopic === subtopic.code
                                    ? 'border-blue-600 bg-gradient-to-br from-blue-50 to-purple-50 shadow-lg sm:scale-105'
                                    : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                            ]">
                            <!-- Selected indicator -->
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
                        <!-- Selected Question Type Info -->
                        <div v-if="selectedQuestionTypeDetails">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-600 font-semibold uppercase tracking-wide">Selected Question
                                    Type</p>
                                <div v-if="selectedLanguageDetails"
                                    class="flex items-center gap-1 bg-gray-100 px-2 py-1 rounded text-xs">
                                    <span>{{ selectedLanguageDetails.icon }}</span>
                                    <span class="text-gray-700 font-medium">{{ selectedLanguageDetails.code ===
                                        'english' ? 'EN' : 'BM' }}</span>
                                </div>
                            </div>
                            <div class="rounded-xl p-4 border" :class="{
                                'bg-gradient-to-r from-green-50 to-emerald-100 border-green-200': selectedQuestionTypeDetails.color === 'green',
                                'bg-gradient-to-r from-purple-50 to-pink-100 border-purple-200': selectedQuestionTypeDetails.color === 'purple',
                                'bg-gradient-to-r from-red-50 to-orange-100 border-red-200': selectedQuestionTypeDetails.color === 'red'
                            }">
                                <p class="font-semibold mb-1 text-gray-900">{{ selectedQuestionTypeDetails.name }}</p>
                                <p class="text-xs text-gray-700">{{ selectedQuestionTypeDetails.description }}</p>
                            </div>
                        </div>

                        <!-- Selected Subtopic Info -->
                        <div v-if="selectedSubtopicDetails">
                            <p class="text-xs text-gray-600 mb-2 font-semibold uppercase tracking-wide">Selected
                                Subtopic</p>
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

                        <!-- Error Message -->
                        <div v-if="generationError">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <p class="text-sm text-red-800">{{ generationError }}</p>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <div class="pt-4 sm:pt-6 border-t border-gray-200 mt-auto" data-onboarding="start-button">
                            <button @click="generateQuestions" :disabled="!canProceed || isGenerating"
                                class="min-h-[44px]" :class="[
                                    'w-full font-semibold px-4 py-3 rounded-lg transition-all duration-300 flex items-center justify-center gap-2 group shadow-sm border',
                                    canProceed && !isGenerating
                                        ? 'bg-gradient-to-r from-blue-50 to-purple-50 hover:from-blue-100 hover:to-purple-100 text-blue-700 border-blue-100 hover:shadow-md transform hover:-translate-y-0.5'
                                        : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'
                                ]">
                                <LucideSparkles v-if="!isGenerating" :size="20"
                                    class="group-hover:scale-110 transition-transform" />
                                <div v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-400"></div>
                                <span>{{ isGenerating ? 'Generating Questions...' : 'Start Practice' }}</span>
                                <LucideArrowRight v-if="!isGenerating" :size="20"
                                    class="group-hover:translate-x-1 transition-transform" />
                            </button>

                            <!-- Loading Message -->
                            <div v-if="isGenerating" class="mt-4 text-center">
                                <p class="text-sm text-gray-600 animate-pulse flex items-center justify-center gap-2">
                                    <LucideSparkles class="w-4 h-4" />
                                    Please wait... We are generating questions based on your learning personality
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Question Generation Modal -->
        <QuestionGenerationModal modal-id="question-generation-modal" :show="showGenerationModal"
            :current-step="generationStep" />
    </div>
</template>

<style scoped>
button:disabled {
    transform: none !important;
}
</style>