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
      class="hs-overlay-open:mt-0 sm:hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all w-full sm:max-w-lg sm:w-full m-0 sm:m-3 h-full sm:h-auto sm:mx-auto"
    >
      <div
        class="max-h-full overflow-hidden flex flex-col bg-white border-0 sm:border border-gray-200 shadow-2xl rounded-none sm:rounded-xl pointer-events-auto"
      >
        <div class="p-4 sm:p-6 md:p-8 text-center">
          <!-- Success Icon -->
          <div class="mx-auto flex items-center justify-center h-12 w-12 sm:h-16 sm:w-16 rounded-full bg-green-100 mb-3 sm:mb-4">
            <svg
              class="h-8 w-8 sm:h-10 sm:w-10 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>

          <!-- Title -->
          <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2 flex items-center justify-center gap-2">
            Quiz Completed!
            <LucidePartyPopper class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-500" />
          </h3>
          <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">Thank you for completing this quiz.</p>

          <!-- Results Summary -->
          <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-4 sm:p-6 mb-4 sm:mb-6">
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
              <!-- Correct Answers -->
              <div class="text-center">
                <div class="text-2xl sm:text-3xl font-bold text-green-600 mb-1">{{ correctCount }}</div>
                <div class="text-xs sm:text-sm text-gray-600">Correct</div>
              </div>
              <!-- Wrong Answers -->
              <div class="text-center">
                <div class="text-2xl sm:text-3xl font-bold text-red-600 mb-1">{{ wrongCount }}</div>
                <div class="text-xs sm:text-sm text-gray-600">Incorrect</div>
              </div>
            </div>
            
            <!-- Score Percentage -->
            <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-200">
              <div class="text-center">
                <div class="text-3xl sm:text-4xl font-bold text-blue-600 mb-1">{{ scorePercentage }}%</div>
                <div class="text-xs sm:text-sm text-gray-600">Your Score</div>
              </div>
            </div>

            <!-- EXP Gained -->
            <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-200">
              <div class="text-center">
                <div class="flex items-center justify-center gap-2 mb-1">
                  <LucideZap class="h-5 w-5 sm:h-6 sm:w-6 text-amber-600" />
                  <div class="text-2xl sm:text-3xl font-bold text-amber-600">{{ expGained }}</div>
                </div>
                <div class="text-xs sm:text-sm text-gray-600">EXP Gained</div>
              </div>
            </div>
          </div>

          <!-- Message -->
          <p class="text-xs sm:text-sm text-gray-600 mb-4 sm:mb-6">
            You will be redirected to the review page to see correct and incorrect answers.
          </p>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-3">
            <button
              @click="handleReview"
              class="min-h-[44px] flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
            >
              View Answer Review
            </button>
            <button
              @click="handleClose"
              class="min-h-[44px] flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { LucidePartyPopper, LucideZap } from 'lucide-vue-next'

const props = defineProps({
  modalId: {
    type: String,
    required: true,
  },
  show: {
    type: Boolean,
    default: false,
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
  attemptId: {
    type: String,
    default: null,
  },
  expGained: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['close', 'review'])

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

const handleReview = () => {
  emit('review')
  closeModal()
}

const handleClose = () => {
  emit('close')
  closeModal()
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    openModal()
  } else {
    closeModal()
  }
})

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
  background-color: rgba(0, 0, 0, 0.3);
}
</style>

