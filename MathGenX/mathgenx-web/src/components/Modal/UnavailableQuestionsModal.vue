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
      class="hs-overlay-open:mt-0 sm:hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all w-full sm:max-w-2xl sm:w-full m-0 sm:m-3 h-full sm:h-auto sm:mx-auto"
    >
      <div
        class="max-h-full overflow-hidden flex flex-col bg-white border-0 sm:border border-gray-200 shadow-2xl rounded-none sm:rounded-xl pointer-events-auto"
      >
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sm:py-4 flex-shrink-0">
          <div class="flex items-center justify-between">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Questions Unavailable</h2>
            <button
              @click="handleClose"
              class="min-h-[44px] min-w-[44px] rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors flex items-center justify-center"
              aria-label="Close modal"
            >
              <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 sm:py-6">
          <!-- Main Message -->
          <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-50 rounded-full mb-4">
              <LucideFileQuestion class="h-8 w-8 text-blue-600" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">
              No Questions Available
            </h3>
            <p class="text-gray-600 text-sm leading-relaxed max-w-md mx-auto">
              {{ message || "We couldn't find any practice questions matching your selected criteria at this time." }}
            </p>
          </div>

          <!-- Information Box -->
          <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-4">
            <div class="flex items-start gap-3">
              <LucideInfo class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
              <div class="flex-1">
                <h4 class="font-medium text-gray-900 mb-2 text-sm">Why might this happen?</h4>
                <ul class="space-y-1.5 text-sm text-gray-600">
                  <li class="flex items-start gap-2">
                    <span class="text-blue-500 mt-1">•</span>
                    <span>The selected topic or subtopic may not have questions available yet</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-blue-500 mt-1">•</span>
                    <span>The question format or difficulty level might not be available for this topic</span>
                  </li>
                  <li class="flex items-start gap-2">
                    <span class="text-blue-500 mt-1">•</span>
                    <span>Questions may be temporarily unavailable due to system updates</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Suggestions -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-3 text-sm">What you can do:</h4>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-medium mt-0.5">1.</span>
                <span>Try selecting a different topic or subtopic</span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-medium mt-0.5">2.</span>
                <span>Adjust the difficulty level or question format</span>
              </li>
              <li class="flex items-start gap-3">
                <span class="text-blue-600 font-medium mt-0.5">3.</span>
                <span>Check back later as new questions are added regularly</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 bg-white px-4 sm:px-6 py-3 sm:py-4 flex-shrink-0">
          <div class="flex flex-col sm:flex-row gap-3 justify-end">
            <button
              @click="handleClose"
              class="min-h-[44px] px-5 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors text-sm"
            >
              Close
            </button>
            <button
              @click="handleGoToBuilder"
              class="min-h-[44px] px-5 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors text-sm"
            >
              Adjust Settings
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { 
  LucideFileQuestion, 
  LucideInfo
} from 'lucide-vue-next'

const props = defineProps({
  modalId: {
    type: String,
    required: true,
  },
  show: {
    type: Boolean,
    default: false,
  },
  message: {
    type: String,
    default: null,
  },
})

const emit = defineEmits(['close'])
const router = useRouter()

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

const handleGoToBuilder = () => {
  closeModal()
  router.push({ name: 'mathpractice_builder' })
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

