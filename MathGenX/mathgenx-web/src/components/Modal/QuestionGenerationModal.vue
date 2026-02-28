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
        <div class="p-4 sm:p-6 md:p-8">
          <!-- Header -->
          <div class="text-center mb-4 sm:mb-6">
            <div class="mx-auto flex items-center justify-center h-12 w-12 sm:h-16 sm:w-16 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 mb-3 sm:mb-4">
              <div class="animate-spin rounded-full h-6 w-6 sm:h-8 sm:w-8 border-b-2 border-blue-600"></div>
            </div>
            <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Generating Your Questions</h3>
            <p class="text-sm sm:text-base text-gray-600">Please wait while we personalize your quiz...</p>
          </div>

          <!-- Progress Steps -->
          <div class="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
            <div
              v-for="(step, index) in steps"
              :key="index"
              class="flex items-start gap-3 sm:gap-4 p-3 sm:p-4 rounded-lg transition-all duration-300"
              :class="getStepClass(step.status)"
            >
              <!-- Step Icon -->
              <div class="flex-shrink-0 mt-0.5">
                <div
                  v-if="step.status === 'completed'"
                  class="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-green-500 flex items-center justify-center"
                >
                  <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div
                  v-else-if="step.status === 'active'"
                  class="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-blue-600 flex items-center justify-center"
                >
                  <div class="w-3 h-3 sm:w-4 sm:h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                </div>
                <div
                  v-else
                  class="w-6 h-6 sm:w-8 sm:h-8 rounded-full bg-gray-200 flex items-center justify-center"
                >
                  <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-gray-400"></div>
                </div>
              </div>

              <!-- Step Content -->
              <div class="flex-1 min-w-0">
                <p
                  class="text-sm sm:text-base font-medium transition-colors"
                  :class="step.status === 'active' ? 'text-blue-600' : step.status === 'completed' ? 'text-green-700' : 'text-gray-500'"
                >
                  {{ step.message }}
                </p>
                <p
                  v-if="step.status === 'active'"
                  class="text-xs text-gray-500 mt-1 animate-pulse"
                >
                  This may take a moment...
                </p>
              </div>
            </div>
          </div>

          <!-- Progress Bar -->
          <div class="mb-4">
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-500 ease-out"
                :style="{ width: `${progressPercentage}%` }"
              ></div>
            </div>
            <p class="text-xs text-gray-500 text-center mt-2">{{ progressPercentage }}% Complete</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, watch, onMounted } from 'vue'

const props = defineProps({
  modalId: {
    type: String,
    required: true,
  },
  show: {
    type: Boolean,
    default: false,
  },
  currentStep: {
    type: Number,
    default: 0,
  },
})

const steps = computed(() => {
  const getStepStatus = (index) => {
    if (index < props.currentStep) return 'completed'
    if (index === props.currentStep) return 'active'
    return 'pending'
  }

  return [
    {
      message: 'Fetching your skill level...',
      status: getStepStatus(0),
    },
    {
      message: 'Analyzing your learning style...',
      status: getStepStatus(1),
    },
    {
      message: 'Generating personalized questions...',
      status: getStepStatus(2),
    },
    {
      message: 'Almost ready!',
      status: getStepStatus(3),
    },
  ]
})

const progressPercentage = computed(() => {
  if (props.currentStep < 0) return 0
  if (props.currentStep >= steps.value.length) return 100
  // Each step is 25%, but we show progress as we complete each step
  return ((props.currentStep + 1) / steps.value.length) * 100
})

function getStepClass(status) {
  if (status === 'active') {
    return 'bg-blue-50 border border-blue-200'
  }
  if (status === 'completed') {
    return 'bg-green-50 border border-green-200'
  }
  return 'bg-gray-50 border border-gray-200'
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
  background-color: rgba(0, 0, 0, 0.5);
  pointer-events: auto;
}
</style>
