<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useMathPracticeStore } from '@/stores/MathPracticeStore'
import { LucideChevronLeft, LucideChevronRight, LucideMessageCircle, LucideX } from 'lucide-vue-next'
import Aurora from '@/components/ui/Backgrounds/Aurora/Aurora.vue'
import PracticeChatbot from '@/components/MathPractice/PracticeChatbot.vue'
import PracticeResultsModal from '@/components/Modal/PracticeResultsModal.vue'
import UnavailableQuestionsModal from '@/components/Modal/UnavailableQuestionsModal.vue'

const router = useRouter()
const route = useRoute()
const mathPracticeStore = useMathPracticeStore()

// State
const subjectiveInput = ref('')
const showSteps = ref(false)
const showResultsModal = ref(false)
const showUnavailableModal = ref(false)
const showChatbotModal = ref(false)

// Computed - use store getters
const questions = computed(() => mathPracticeStore.questions)
const currentQuestion = computed(() => mathPracticeStore.currentQuestion)
const progress = computed(() => mathPracticeStore.progress)
const answeredCount = computed(() => mathPracticeStore.answeredCount)
const isLastQuestion = computed(() => mathPracticeStore.isLastQuestion)
const loading = computed(() => mathPracticeStore.loading)
const error = computed(() => mathPracticeStore.error)
const responses = computed(() => mathPracticeStore.responses)
const currentQuestionIndex = computed(() => mathPracticeStore.currentQuestionIndex)
const roundedProgress = computed(() => Math.round(progress.value))

// Keep local subjective input in sync with store responses when question changes
watch(currentQuestionIndex, (idx) => {
  const q = mathPracticeStore.questions[idx]
  subjectiveInput.value = (q && mathPracticeStore.responses[q.id]) || ''
  showSteps.value = false
})

// Sync local input to store when user types
watch(subjectiveInput, (val) => {
  const q = mathPracticeStore.currentQuestion
  if (q) {
    mathPracticeStore.selectAnswer(q.id, val)
  }
})

// Methods
const fetchQuestions = async () => {
  // Get params from route query
  const { topic, subtopic, question_format, language, tp, difficulty } = route.query

  // Fetch questions from store (vark_style is handled by backend)
  const result = await mathPracticeStore.fetchQuestions(
    topic,
    subtopic,
    question_format,
    language || 'english',
    parseInt(tp),
    difficulty
  )

  // If questions loaded successfully, generate practice session ID
  if (result.success && mathPracticeStore.hasQuestions) {
    mathPracticeStore.generatePracticeSessionId()
  }

  // If no questions after fetch attempt, show modal instead of redirecting
  if (!mathPracticeStore.hasQuestions) {
    showUnavailableModal.value = true
  }
}

const selectAnswer = (questionId, answerId) => {
  mathPracticeStore.selectAnswer(questionId, answerId)
}

const goToQuestion = (index) => {
  mathPracticeStore.goToQuestion(index)
}

const nextQuestion = () => {
  mathPracticeStore.nextQuestion()
}

const previousQuestion = () => {
  mathPracticeStore.previousQuestion()
}

// Calculate results
const practiceResults = computed(() => {
  let correctCount = 0
  let wrongCount = 0

  questions.value.forEach((question) => {
    const userAnswer = responses.value[question.id]
    if (!userAnswer) {
      wrongCount++
      return
    }

    // For multiple choice questions
    if (question.choices && question.choices.length > 0) {
      const correctChoice = question.choices.find((choice) => choice.is_correct)
      if (correctChoice && userAnswer === correctChoice.id) {
        correctCount++
      } else {
        wrongCount++
      }
    } else {
      wrongCount++
    }
  })

  const total = questions.value.length
  const scorePercentage = total > 0 ? Math.round((correctCount / total) * 100) : 0

  return {
    correctCount,
    wrongCount,
    scorePercentage,
    total,
  }
})

const handleFinish = () => {
  // Show results modal
  showResultsModal.value = true
}

const handleCloseResults = () => {
  showResultsModal.value = false
  // Clear store and redirect to builder
  mathPracticeStore.resetPractice()
  router.push({ name: 'mathpractice_builder' })
}

const handleCloseUnavailableModal = () => {
  showUnavailableModal.value = false
  // Redirect to builder when modal is closed
  mathPracticeStore.resetPractice()
  router.push({ name: 'mathpractice_builder' })
}

onMounted(() => {
  fetchQuestions()

  // Ensure the page receives focus so scrolling and cursor work immediately
  setTimeout(() => {
    window.focus()
    const mainElement = document.querySelector('main')
    if (mainElement) {
      mainElement.focus()
    }
  }, 150)
})
</script>

<template>
  <div class="bg-gradient-to-br from-blue-50 via-white to-purple-50">
    <!-- Header - Full width with constrained content -->
    <div class="relative shadow-md border-b border-gray-200/50 overflow-hidden mx-2">
      <Aurora :color-stops="['#60A5FA', '#A78BFA', '#FBCFE8']" :amplitude="0.65" :blend="0.35" :speed="1.5"
        :intensity="0.65" class="absolute inset-0 w-full h-full" />
      <div class="absolute inset-0 bg-white/70 backdrop-blur-md z-10 pointer-events-none"></div>
      <div class="relative z-20 max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 py-4 sm:py-5 md:py-7 mt-10 md:mt-0">
        <!-- Title and Stats Row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 md:gap-6 mb-4 sm:mb-5">
          <div class="flex items-center gap-2 sm:gap-3">
            <div class="w-1 h-6 sm:h-8 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
            <div>
              <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Math Practice</h1>
              <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Practice your math skills</p>
            </div>
          </div>
          
          <!-- Stats Cards -->
          <div class="flex items-center gap-2 sm:gap-3 md:gap-4">
            <div class="bg-white/80 backdrop-blur-sm rounded-lg px-3 sm:px-4 py-2 sm:py-2.5 border border-gray-200/50 shadow-sm">
              <div class="flex items-center gap-1.5 sm:gap-2">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                <span class="text-xs text-gray-600">Question</span>
                <span class="text-sm font-bold text-gray-900">{{ currentQuestionIndex + 1 }}</span>
                <span class="text-xs text-gray-400">/</span>
                <span class="text-sm font-semibold text-gray-700">{{ questions.length }}</span>
              </div>
            </div>
            <div class="bg-white/80 backdrop-blur-sm rounded-lg px-3 sm:px-4 py-2 sm:py-2.5 border border-gray-200/50 shadow-sm">
              <div class="flex items-center gap-1.5 sm:gap-2">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-xs text-gray-600">Answered</span>
                <span class="text-sm font-bold text-green-600">{{ answeredCount }}</span>
                <span class="text-xs text-gray-400">/</span>
                <span class="text-sm font-semibold text-gray-700">{{ questions.length }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="relative">
          <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden shadow-inner">
            <div 
              class="h-full bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 transition-all duration-500 ease-out rounded-full relative overflow-hidden"
              :style="{ width: `${progress}%` }"
            >
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
            </div>
          </div>
          <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
            <span>Progress</span>
            <span class="font-medium text-gray-700">{{ roundedProgress }}%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-[60vh]">
      <div class="text-center max-w-md mx-auto px-4">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-lg font-semibold text-gray-800">Loading Practice Questions...</p>
        <p class="mt-2 text-sm text-gray-600">Please wait...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <p class="text-red-800">{{ error }}</p>
        <button 
          @click="fetchQuestions"
          class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
        >
          Try Again
        </button>
        <button 
          @click="router.push({ name: 'mathpractice_builder' })"
          class="mt-4 ml-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
        >
          Go Back
        </button>
      </div>
    </div>

    <!-- Practice Content -->
    <div v-else-if="questions.length > 0" class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 pt-4 sm:pt-6 pb-4 sm:pb-6">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
        <!-- Question Navigator (Left) - Appears first on mobile -->
        <div class="lg:col-span-2 order-1 lg:order-1">
          <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 lg:p-4 lg:sticky lg:top-4">
            <h3 class="font-semibold text-gray-900 mb-1.5 sm:mb-3 text-xs sm:text-sm lg:text-md">Questions</h3>
            
            <!-- Grid layout - more compact for mobile -->
            <div class="grid grid-cols-5 sm:grid-cols-10 lg:grid-cols-2 gap-1 sm:gap-1.5 justify-items-center">
              <button
                v-for="(q, index) in questions"
                :key="q.id"
                @click="goToQuestion(index)"
                class="w-12 h-12 sm:w-full sm:aspect-square sm:min-h-[36px]"
                :class="[
                  'aspect-square rounded font-medium transition-all text-xs sm:text-md flex items-center justify-center',
                  currentQuestionIndex === index
                    ? 'bg-blue-600 text-white ring-1 sm:ring-2 ring-blue-300 shadow-md'
                    : responses[q.id]
                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
              >
                {{ index + 1 }}
              </button>
            </div>
            
            <!-- Legend - more compact -->
            <div class="mt-2 sm:mt-3 lg:mt-4 pt-2 sm:pt-3 border-t border-gray-200 space-y-1 sm:space-y-1.5">
              <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs text-gray-600">
                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-green-100 rounded flex-shrink-0"></div>
                <span>Answered</span>
              </div>
              <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs text-gray-600">
                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-blue-600 rounded flex-shrink-0"></div>
                <span>Current</span>
              </div>
              <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-xs text-gray-600">
                <div class="w-2 h-2 sm:w-3 sm:h-3 bg-gray-100 rounded flex-shrink-0"></div>
                <span>Unanswered</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Question Area (Middle) -->
        <div class="lg:col-span-7 order-1 lg:order-2">
          <div class="bg-white rounded-xl shadow-md p-4 sm:p-5 md:p-6 lg:p-8 flex flex-col" style="height: auto; min-height: 400px; max-height: calc(100vh - 12rem);">
            <!-- Scrollable content area -->
            <div class="flex-1 overflow-y-auto mb-4 sm:mb-6">
              <!-- Question Header -->
              <div class="mb-4 sm:mb-6 md:mb-8">
                <div class="flex flex-wrap items-start gap-2 sm:gap-3 mb-3 sm:mb-4">
                  <span class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-semibold text-sm sm:text-base">
                    {{ currentQuestionIndex + 1 }}
                  </span>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                      <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ currentQuestion?.learning_style || 'Visual' }}
                      </span>
                    </div>
                    <h2 class="text-base sm:text-lg lg:text-xl font-semibold text-gray-900 leading-relaxed">
                      {{ currentQuestion?.question_text }}
                    </h2>
                  </div>
                </div>
                
                <!-- Question Image (if exists) -->
                <div v-if="currentQuestion?.image_url" class="mt-3 sm:mt-4 lg:ml-11">
                  <img 
                    :src="currentQuestion.image_url" 
                    :alt="`Question ${currentQuestionIndex + 1}`"
                    class="max-w-full sm:max-w-md rounded-lg border border-gray-200"
                  />
                </div>
              </div>

              <!-- Answer Options -->
              <div class="space-y-3">
                <!-- Multiple choice -->
                <div v-if="currentQuestion?.choices && currentQuestion.choices.length">
                  <button
                    v-for="answer in currentQuestion.choices"
                    :key="answer.id"
                    @click="selectAnswer(currentQuestion.id, answer.id)"
                    class="min-h-[44px]"
                    :class="[
                      'w-full text-left p-4 sm:p-5 rounded-lg border-2 transition-all',
                      responses[currentQuestion.id] === answer.id
                        ? 'border-blue-600 bg-blue-50 shadow-sm'
                        : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                    ]"
                  >
                    <div class="flex items-start gap-3">
                      <div :class="[
                        'flex-shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-full border-2 flex items-center justify-center',
                        responses[currentQuestion.id] === answer.id
                          ? 'border-blue-600 bg-blue-600'
                          : 'border-gray-300'
                      ]">
                        <svg 
                          v-if="responses[currentQuestion.id] === answer.id"
                          class="w-3 h-3 sm:w-4 sm:h-4 text-white" 
                          fill="currentColor" 
                          viewBox="0 0 20 20"
                        >
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                      </div>
                      <span class="text-sm sm:text-base text-gray-900">{{ answer.answer_text }}</span>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <!-- Navigation Buttons - fixed at bottom -->
            <div class="flex items-center justify-between pt-4 sm:pt-6 border-t border-gray-200 gap-3 flex-shrink-0">
              <button
                @click="previousQuestion"
                :disabled="currentQuestionIndex === 0"
                class="min-h-[44px]"
                :class="[
                  'px-4 sm:px-6 py-3 sm:py-2.5 rounded-lg font-medium transition-all text-sm sm:text-base',
                  currentQuestionIndex === 0
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                ]"
              >
                <span class="hidden sm:inline-flex items-center gap-1">
                  <LucideChevronLeft class="w-4 h-4" />
                  Previous
                </span>
                <LucideChevronLeft class="sm:hidden w-5 h-5" />
              </button>

              <button
                v-if="!isLastQuestion"
                @click="nextQuestion"
                class="min-h-[44px] px-4 sm:px-6 py-3 sm:py-2.5 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-all text-sm sm:text-base"
              >
                <span class="hidden sm:inline-flex items-center gap-1">
                  Next
                  <LucideChevronRight class="w-4 h-4" />
                </span>
                <LucideChevronRight class="sm:hidden w-5 h-5" />
              </button>

              <button
                v-else
                @click="handleFinish"
                class="min-h-[44px] px-4 sm:px-8 py-3 sm:py-2.5 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-all text-sm sm:text-base"
              >
                Finish Practice
              </button>
            </div>
          </div>
        </div>

        <!-- Chatbot (Right) - Hidden on mobile, visible on desktop -->
        <div class="hidden lg:block lg:col-span-3 order-3">
          <div style="height: calc(100vh - 16rem); min-height: 550px; max-height: 800px;">
            <PracticeChatbot />
          </div>
        </div>
      </div>

      <!-- Mobile Chatbot Floating Button -->
      <div class="lg:hidden fixed bottom-6 right-6 z-50">
        <button
          @click="showChatbotModal = true"
          class="w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-all flex items-center justify-center"
          aria-label="Open AI Tutor"
        >
          <LucideMessageCircle class="w-6 h-6" />
        </button>
      </div>

      <!-- Mobile Chatbot Modal -->
      <div
        v-if="showChatbotModal"
        class="lg:hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-end"
        @click.self="showChatbotModal = false"
      >
        <div class="w-full bg-white rounded-t-2xl shadow-2xl flex flex-col" style="height: 85vh; max-height: 85vh;">
          <!-- Modal Header -->
          <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
              <LucideMessageCircle class="w-5 h-5 text-blue-600" />
              <h3 class="font-semibold text-gray-900 text-sm">AI Math Tutor</h3>
            </div>
            <button
              @click="showChatbotModal = false"
              class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
              aria-label="Close chatbot"
            >
              <LucideX class="w-5 h-5 text-gray-600" />
            </button>
          </div>
          
          <!-- Chatbot Content -->
          <div class="flex-1 overflow-hidden">
            <PracticeChatbot :hide-header="true" />
          </div>
        </div>
      </div>
    </div>

    <!-- Practice Results Modal -->
    <PracticeResultsModal
      modal-id="practice-results-modal"
      :show="showResultsModal"
      :questions="questions"
      :responses="responses"
      :correct-count="practiceResults.correctCount"
      :wrong-count="practiceResults.wrongCount"
      :score-percentage="practiceResults.scorePercentage"
      @close="handleCloseResults"
    />

    <!-- Unavailable Questions Modal -->
    <UnavailableQuestionsModal
      modal-id="unavailable-questions-modal"
      :show="showUnavailableModal"
      :message="error"
      @close="handleCloseUnavailableModal"
    />
  </div>
</template>

<style scoped>
.aspect-square {
  aspect-ratio: 1 / 1;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

/* Custom scrollbar styling for cards */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>

