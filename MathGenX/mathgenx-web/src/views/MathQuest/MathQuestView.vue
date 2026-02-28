<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useMathQuestStore } from '@/stores/MathQuestStore'
import { useAuthStore } from '@/stores/authStore'
import { submitQuizAttempt } from '@/services/MathQuestService'
import QuizResultsModal from '@/components/Modal/QuizResultsModal.vue'
import LevelUpModal from '@/components/Modal/LevelUpModal.vue'
import Aurora from '@/components/ui/Backgrounds/Aurora/Aurora.vue'
import { LucideSparkles, LucideChevronLeft, LucideChevronRight } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const mathQuestStore = useMathQuestStore()
const authStore = useAuthStore()

// State
const submitting = ref(false)
const showSteps = ref(false)
const subjectiveInput = ref('')
const showResultsModal = ref(false)
const showLevelUpModal = ref(false)
const oldLevel = ref(null)
const newLevel = ref(null)
const pendingAction = ref(null) // 'review' or 'dashboard'
const quizResults = ref({
  correctCount: 0,
  wrongCount: 0,
  scorePercentage: 0,
  attemptId: null,
  expGained: 0
})
const quizStartedAt = ref(null)

// Computed - use store getters
const questions = computed(() => mathQuestStore.questions)
const currentQuestion = computed(() => mathQuestStore.currentQuestion)
const progress = computed(() => mathQuestStore.progress)
const answeredCount = computed(() => mathQuestStore.answeredCount)
const isLastQuestion = computed(() => mathQuestStore.isLastQuestion)
const canSubmit = computed(() => mathQuestStore.canSubmit)
const loading = computed(() => mathQuestStore.loading)
const error = computed(() => mathQuestStore.error)
const responses = computed(() => mathQuestStore.responses)
const currentQuestionIndex = computed(() => mathQuestStore.currentQuestionIndex)

// Computed for current quiz parameters
const currentParams = computed(() => ({
  topic: route.query.topic || '',
  subtopic: route.query.subtopic || '',
  question_format: route.query.question_format || 'multiple_choice',
  language: route.query.language || 'english'
}))

// Keep local subjective input in sync with store responses when question changes
watch(currentQuestionIndex, (idx) => {
  const q = mathQuestStore.questions[idx]
  subjectiveInput.value = (q && mathQuestStore.responses[q.id]) || ''
  showSteps.value = false
})

// Sync local input to store when user types
watch(subjectiveInput, (val) => {
  const q = mathQuestStore.currentQuestion
  if (q) {
    mathQuestStore.selectAnswer(q.id, val)
  }
})

// Methods
const fetchQuestions = async () => {
  // Get params from route query
  const { topic, subtopic, question_format, language } = route.query

  // If no questions in store or params don't match, fetch from API
  if (!mathQuestStore.hasQuestions || !mathQuestStore.isSameParams(topic, subtopic, question_format, language)) {
    // This will use cached data if params match, otherwise fetch new
    await mathQuestStore.fetchQuestions(topic, subtopic, question_format, language || 'english')
  }

  // If still no questions after fetch attempt, redirect back to builder
  if (!mathQuestStore.hasQuestions) {
    router.push({ name: 'mathquest_builder' })
  }
}

const selectAnswer = (questionId, answerId) => {
  mathQuestStore.selectAnswer(questionId, answerId)
}

const goToQuestion = (index) => {
  mathQuestStore.goToQuestion(index)
}

const nextQuestion = () => {
  mathQuestStore.nextQuestion()
}

const previousQuestion = () => {
  mathQuestStore.previousQuestion()
}

const submitQuiz = async () => {
  if (!canSubmit.value) {
    alert('Please answer all questions before submitting.')
    return
  }

  try {
    submitting.value = true

    // Store old level before submission
    oldLevel.value = authStore.user?.level || 1

    // Get quiz parameters from route
    const { topic, subtopic, question_format, language } = route.query

    // Prepare attempt data
    const attemptData = {
      topic_id: topic,
      subtopic_id: subtopic,
      question_format: question_format || 'multiple_choice',
      language: language || 'english',
      mastery_level: questions.value[0]?.mastery_level || null,
      learning_style: questions.value[0]?.learning_style || null,
      questions: questions.value,
      responses: responses.value,
      started_at: quizStartedAt.value,
    }

    // Submit to backend
    const result = await submitQuizAttempt(attemptData)

    if (result.status === 'success' && result.data) {
      const totalQuestions = questions.value.length
      const correctCount = result.data.correct_count || 0
      const wrongCount = result.data.incorrect_count || 0
      const scorePercentage = result.data.score_percentage || 0

      // Refresh user profile to update exp and level
      try {
        await authStore.fetchProfile()
        // Get new level after profile refresh
        newLevel.value = authStore.user?.level || 1
      } catch (error) {
        console.error('Failed to refresh profile after quiz submission:', error)
        // Continue even if profile refresh fails
        newLevel.value = oldLevel.value
      }

      // Show results modal
      quizResults.value = {
        correctCount,
        wrongCount,
        scorePercentage: Math.round(scorePercentage),
        attemptId: result.data.attempt_id,
        expGained: result.data.exp_gained || 0
      }
      showResultsModal.value = true
    } else {
      alert('Failed to submit quiz. Please try again.')
      console.error('Submit error:', result)
    }
  } catch (err) {
    alert('Failed to submit quiz. Please try again.')
    console.error('Submit error:', err)
  } finally {
    submitting.value = false
  }
}

const handleReview = () => {
  showResultsModal.value = false
  pendingAction.value = 'review'
  
  // Check if user leveled up
  if (oldLevel.value !== null && newLevel.value !== null && newLevel.value > oldLevel.value) {
    // Show level up modal first
    showLevelUpModal.value = true
  } else {
    // If no level up, proceed with review
    navigateToReview()
  }
}

const navigateToReview = () => {
  // Clear store after submission
  mathQuestStore.clearQuestions()
  
  // Navigate to review page
  if (quizResults.value.attemptId) {
    router.push({ 
      name: 'quiz_review', 
      params: { id: quizResults.value.attemptId } 
    })
  } else {
    router.push({ name: 'dashboard' })
  }
}

const handleModalClose = () => {
  showResultsModal.value = false
  pendingAction.value = 'dashboard'
  
  // Check if user leveled up
  if (oldLevel.value !== null && newLevel.value !== null && newLevel.value > oldLevel.value) {
    // Show level up modal
    showLevelUpModal.value = true
  } else {
    // If no level up, proceed with normal flow
    handleLevelUpModalClose()
  }
}

const handleLevelUpModalClose = () => {
  showLevelUpModal.value = false
  
  // Execute pending action after level up modal closes
  if (pendingAction.value === 'review') {
    navigateToReview()
  } else {
    // Clear store after submission
    mathQuestStore.clearQuestions()
    // Navigate to dashboard
    router.push({ name: 'dashboard' })
  }
  
  // Reset pending action
  pendingAction.value = null
}

onMounted(() => {
  fetchQuestions()
  quizStartedAt.value = new Date().toISOString()

  // Ensure the page receives focus so scrolling and cursor work immediately
  // This prevents the need to click before interacting with the page
  setTimeout(() => {
    window.focus()
    // Focus the scrollable main container
    const mainElement = document.querySelector('main')
    if (mainElement) {
      mainElement.focus()
    }
  }, 150)
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
    <!-- Header - Full width with constrained content -->
    <div class="relative shadow-sm border-b border-gray-200 overflow-hidden">
      <Aurora :color-stops="['#60A5FA', '#A78BFA', '#FBCFE8']" :amplitude="0.65" :blend="0.35" :speed="1.5"
        :intensity="0.65" class="absolute inset-0 w-full h-full" />
      <div class="absolute inset-0 bg-white/60 backdrop-blur-sm z-10 pointer-events-none"></div>
      <div class="relative z-20 max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4 md:py-6 mt-10 md:mt-0">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4">
          <div class="flex-1 min-w-0">
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900">MathQuest Quiz</h1>
            <p v-if="currentParams.subtopic" class="text-xs sm:text-sm text-gray-600 mt-1">
              Subtopic: <span class="font-semibold text-gray-800">{{ currentParams.subtopic }}</span>
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2 sm:gap-3 md:gap-4 text-xs sm:text-sm">
            <span class="text-gray-600">
              Question <span class="font-semibold">{{ currentQuestionIndex + 1 }}</span> of <span class="font-semibold">{{ questions.length }}</span>
            </span>
            <span class="font-medium text-blue-600">
              {{ answeredCount }}/{{ questions.length }} answered
            </span>
          </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-3 sm:mt-4 h-2 bg-gray-200 rounded-full overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-300"
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-[60vh]">
      <div class="text-center max-w-md mx-auto px-4">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-lg font-semibold text-gray-800">Generating Questions...</p>
        <p class="mt-2 text-sm text-gray-600 animate-pulse flex items-center justify-center gap-2">
          <LucideSparkles class="w-4 h-4" />
          Please wait... We are generating questions based on your learning personality
        </p>
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
      </div>
    </div>

    <!-- Quiz Content - Constrained to match header width -->
    <div v-else-if="questions.length > 0" class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 sm:gap-6">
        <!-- Question Navigator (Sidebar) - Smaller width -->
        <div class="lg:col-span-1 order-1">
          <div class="bg-white rounded-xl shadow-md p-3 sm:p-4 lg:p-4 lg:sticky lg:top-4">
            <h3 class="font-semibold text-gray-900 mb-1.5 sm:mb-3 text-xs sm:text-sm lg:text-md">Questions</h3>
            
            <!-- Grid layout - more compact for mobile -->
            <div class="grid grid-cols-5 sm:grid-cols-10 lg:grid-cols-3 gap-1 sm:gap-1.5 justify-items-center">
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

        <!-- Main Question Area - Wider to compensate -->
        <div class="lg:col-span-4 order-2">
          <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 lg:p-8">
            <!-- Question Header with Mastery Level Badge -->
            <div class="mb-6 sm:mb-8">
              <div class="flex flex-wrap items-start gap-3 mb-4">
                <span class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-semibold text-sm sm:text-base">
                  {{ currentQuestionIndex + 1 }}
                </span>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                      {{ currentQuestion.level }}
                    </span>
                  </div>
                  <h2 class="text-base sm:text-lg lg:text-xl font-semibold text-gray-900 leading-relaxed">
                    {{ currentQuestion.question_text }}
                  </h2>
                </div>
              </div>
              
              <!-- Question Image (if exists) -->
              <div v-if="currentQuestion.image_url" class="mt-4 lg:ml-11">
                <img 
                  :src="currentQuestion.image_url" 
                  :alt="`Question ${currentQuestionIndex + 1}`"
                  class="max-w-full sm:max-w-md rounded-lg border border-gray-200"
                />
              </div>
            </div>

            <!-- Answer Options -->
            <div class="space-y-3 mb-6 sm:mb-8">
              <!-- Multiple choice -->
              <div v-if="currentQuestion.choices && currentQuestion.choices.length">
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

              <!-- Subjective (text / numeric) -->
              <div v-else>
                <div class="flex items-center justify-between mb-2">
                  <div class="text-sm text-gray-600">Answer ({{ currentQuestion.answer_type || 'text' }})</div>
                  <button @click="showSteps = !showSteps" class="text-sm text-blue-600 hover:underline">
                    {{ showSteps ? 'Hide Steps' : 'Show Steps' }}
                  </button>
                </div>

                <transition name="fade">
                  <div v-show="showSteps" class="mb-3 p-3 bg-gray-50 border rounded-lg text-sm text-gray-700">
                    <ol class="list-decimal list-inside space-y-1">
                      <li v-for="(step, key) in currentQuestion.working_steps" :key="key">{{ step }}</li>
                    </ol>
                  </div>
                </transition>

                <textarea
                  v-model="subjectiveInput"
                  placeholder="Enter your answer here..."
                  rows="4"
                  class="w-full min-h-[44px] p-3 sm:p-4 border rounded-lg resize-none text-sm sm:text-base"
                ></textarea>

                <p class="text-xs text-gray-500 mt-2">Tip: Enter your answer in the requested format (e.g., "6°C" or "0 meters").</p>
              </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between pt-4 sm:pt-6 border-t border-gray-200 gap-3">
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
                @click="submitQuiz"
                :disabled="!canSubmit || submitting"
                :class="[
                  'min-h-[44px] px-4 sm:px-8 py-3 sm:py-2.5 rounded-lg font-medium transition-all text-sm sm:text-base',
                  canSubmit && !submitting
                    ? 'bg-green-600 text-white hover:bg-green-700'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                ]"
              >
                <span v-if="submitting">Submitting...</span>
                <span v-else>Submit Quiz</span>
              </button>
            </div>
          </div>

          <!-- Warning if not all answered -->
          <div 
            v-if="isLastQuestion && !canSubmit" 
            class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4"
          >
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              <div>
                <p class="font-medium text-yellow-800 text-sm sm:text-base">Not all questions answered</p>
                <p class="text-xs sm:text-sm text-yellow-700 mt-1">
                  You've answered {{ answeredCount }} out of {{ questions.length }} questions. 
                  Please answer all questions before submitting.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quiz Results Modal -->
    <QuizResultsModal
      modal-id="quiz-results-modal"
      :show="showResultsModal"
      :correct-count="quizResults.correctCount"
      :wrong-count="quizResults.wrongCount"
      :score-percentage="quizResults.scorePercentage"
      :attempt-id="quizResults.attemptId"
      :exp-gained="quizResults.expGained"
      @review="handleReview"
      @close="handleModalClose"
    />

    <!-- Level Up Modal -->
    <LevelUpModal
      v-if="oldLevel !== null && newLevel !== null"
      modal-id="level-up-modal"
      :show="showLevelUpModal"
      :old-level="oldLevel"
      :new-level="newLevel"
      @close="handleLevelUpModalClose"
    />
  </div>
</template>

<style scoped>
/* Ensure proper aspect ratio for question navigator buttons */
.aspect-square {
  aspect-ratio: 1 / 1;
}
</style>