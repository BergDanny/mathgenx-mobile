<template>
  <div
    :id="modalId"
    class="hs-overlay hidden size-full fixed top-0 start-0 z-80 overflow-x-hidden overflow-y-auto pointer-events-none"
    role="dialog"
    tabindex="-1"
    :aria-labelledby="`${modalId}-label`"
  >
    <div
      data-modal-inner
      class="hs-overlay-open:mt-0 sm:hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all w-full sm:max-w-4xl sm:w-full m-0 sm:m-3 h-full sm:h-auto sm:mx-auto"
    >
      <div
        class="max-h-full overflow-hidden flex flex-col bg-white border-0 sm:border border-gray-200 shadow-2xl rounded-none sm:rounded-xl pointer-events-auto"
      >
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-4 sm:px-6 py-3 sm:py-4 flex-shrink-0">
          <div class="flex items-center justify-between">
            <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-white">Practice Session Results</h2>
            <button
              @click="handleClose"
              class="min-h-[44px] min-w-[44px] rounded-lg p-1 text-white hover:bg-white/20 transition-colors flex items-center justify-center"
            >
              <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 sm:py-6">
          <!-- Warning Message -->
          <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start gap-3">
              <LucideAlertCircle class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" />
              <div>
                <h3 class="font-semibold text-amber-900 mb-1">Practice Session Notice</h3>
                <p class="text-sm text-amber-800">
                  This practice session is <strong>not being stored</strong> in your records.
                  Please review your answers carefully and use this as a learning opportunity.
                  Exit when you're done reviewing.
                </p>
              </div>
            </div>
          </div>

          <!-- Score Summary -->
          <div class="mb-4 sm:mb-6 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <div class="rounded-lg bg-green-50 border border-green-200 p-3 sm:p-4 text-center">
              <div class="flex items-center justify-center mb-2">
                <LucideCheckCircle2 class="h-6 w-6 sm:h-8 sm:w-8 text-green-600" />
              </div>
              <div class="text-2xl sm:text-3xl font-bold text-green-700 mb-1">{{ correctCount }}</div>
              <div class="text-xs sm:text-sm font-medium text-green-800">Correct</div>
            </div>

            <div class="rounded-lg bg-red-50 border border-red-200 p-3 sm:p-4 text-center">
              <div class="flex items-center justify-center mb-2">
                <LucideXCircle class="h-6 w-6 sm:h-8 sm:w-8 text-red-600" />
              </div>
              <div class="text-2xl sm:text-3xl font-bold text-red-700 mb-1">{{ wrongCount }}</div>
              <div class="text-xs sm:text-sm font-medium text-red-800">Incorrect</div>
            </div>

            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 sm:p-4 text-center">
              <div class="text-2xl sm:text-3xl font-bold text-blue-700 mb-1">{{ scorePercentage }}%</div>
              <div class="text-xs sm:text-sm font-medium text-blue-800">Score</div>
              <div class="text-xs text-blue-600 mt-1">
                {{ correctCount }} / {{ totalQuestions }} questions
              </div>
            </div>
          </div>

          <!-- Questions Review -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Question Review</h3>

            <div
              v-for="(question, index) in questions"
              :key="question.id"
              class="rounded-lg border-2 p-4"
              :class="isAnswerCorrect(question) ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'"
            >
              <!-- Question Header -->
              <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full font-semibold text-sm"
                    :class="isAnswerCorrect(question) ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
                  >
                    {{ index + 1 }}
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                      <span
                        v-if="isAnswerCorrect(question)"
                        class="inline-flex items-center gap-1 rounded-full bg-green-600 px-2 py-0.5 text-xs font-medium text-white"
                      >
                        <LucideCheckCircle2 class="h-3 w-3" />
                        Correct
                      </span>
                      <span
                        v-else
                        class="inline-flex items-center gap-1 rounded-full bg-red-600 px-2 py-0.5 text-xs font-medium text-white"
                      >
                        <LucideXCircle class="h-3 w-3" />
                        Incorrect
                      </span>
                    </div>
                    <p class="font-semibold text-gray-900">{{ question.question_text }}</p>
                  </div>
                </div>
              </div>

              <!-- Answers -->
              <div class="ml-11 space-y-2">
                <div>
                  <div class="text-xs font-medium text-gray-600 mb-1">Your Answer:</div>
                  <div
                    class="rounded-md border-2 px-3 py-2 text-sm"
                    :class="isAnswerCorrect(question) ? 'border-green-300 bg-white text-green-900' : 'border-red-300 bg-white text-red-900'"
                  >
                    {{ getUserAnswerText(question) }}
                  </div>
                </div>

                <div v-if="!isAnswerCorrect(question)">
                  <div class="text-xs font-medium text-gray-600 mb-1">Correct Answer:</div>
                  <div class="rounded-md border-2 border-green-300 bg-white px-3 py-2 text-sm text-green-900">
                    {{ getCorrectAnswerText(question) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3 sm:py-4 flex-shrink-0">
          <div class="flex justify-end">
            <button
              @click="handleClose"
              class="min-h-[44px] w-full sm:w-auto rounded-lg bg-blue-600 px-6 py-2 font-medium text-white hover:bg-blue-700 transition-colors"
            >
              Exit Practice
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { LucideCheckCircle2, LucideXCircle, LucideAlertCircle } from 'lucide-vue-next'

const props = defineProps({
  modalId: {
    type: String,
    required: true,
  },
  show: {
    type: Boolean,
    default: false,
  },
  questions: {
    type: Array,
    default: () => [],
  },
  responses: {
    type: Object,
    default: () => ({}),
  },
  correctCount: {
    type: Number,
    default: 0,
  },
  wrongCount: {
    type: Number,
    default: 0,
  },
  scorePercentage: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['close'])

const totalQuestions = computed(() => props.questions.length)

// Check if answer is correct
const isAnswerCorrect = (question) => {
  const userAnswer = props.responses[question.id]
  if (!userAnswer) return false

  // For multiple choice questions
  if (question.choices && question.choices.length > 0) {
    const correctChoice = question.choices.find((choice) => choice.is_correct)
    return correctChoice && userAnswer === correctChoice.id
  }

  return false
}

// Get user's selected answer text
const getUserAnswerText = (question) => {
  const userAnswerId = props.responses[question.id]
  if (!userAnswerId && question.choices) return 'Not answered'

  if (question.choices && question.choices.length > 0) {
    const selectedChoice = question.choices.find((choice) => choice.id === userAnswerId)
    return selectedChoice ? selectedChoice.answer_text : 'Not answered'
  }

  return 'Not answered'
}

// Get correct answer text
const getCorrectAnswerText = (question) => {
  if (question.choices && question.choices.length > 0) {
    const correctChoice = question.choices.find((choice) => choice.is_correct)
    return correctChoice ? correctChoice.answer_text : 'N/A'
  }
  return 'N/A'
}

const ensurePrelineInit = () => {
  if (window.HSStaticMethods && typeof window.HSStaticMethods.autoInit === 'function') {
    window.HSStaticMethods.autoInit()
  }
}

const openModal = () => {
  const modal = document.getElementById(props.modalId)
  if (!modal) return

  ensurePrelineInit()

  const api = window.HSOverlay
  if (api && typeof api.open === 'function') {
    try {
      api.open(modal)
      return
    } catch (e) {
      // fall through to fallback
    }
  }

  // Fallback
  modal.classList.remove('hidden')
  modal.classList.remove('pointer-events-none')
  modal.style.opacity = '1'
  const inner = modal.querySelector('[data-modal-inner]')
  if (inner && inner instanceof HTMLElement) {
    inner.classList.remove('mt-0')
    inner.classList.add('mt-7')
    inner.style.opacity = '1'
  }
}

const closeModal = () => {
  const modal = document.getElementById(props.modalId)
  if (!modal) return

  const api = window.HSOverlay
  if (api && typeof api.close === 'function') {
    try {
      api.close(modal)
      return
    } catch (e) {
      // fall through to fallback
    }
  }

  // Fallback
  modal.classList.add('hidden')
  modal.classList.add('pointer-events-none')
  modal.style.opacity = '0'
  const inner = modal.querySelector('[data-modal-inner]')
  if (inner && inner instanceof HTMLElement) {
    inner.classList.remove('mt-7')
    inner.classList.add('mt-0')
    inner.style.opacity = '0'
  }
}

const handleClose = () => {
  closeModal()
  emit('close')
}

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      openModal()
    } else {
      closeModal()
    }
  }
)

onMounted(() => {
  ensurePrelineInit()
  if (props.show) {
    openModal()
  }
})
</script>

<style scoped>
.hs-overlay {
  backdrop-filter: blur(4px);
  background-color: rgba(0, 0, 0, 0.5);
  pointer-events: auto;
}
</style>

