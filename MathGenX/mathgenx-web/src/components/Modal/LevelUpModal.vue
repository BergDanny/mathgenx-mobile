<script setup>
import { computed, onMounted, watch } from 'vue'
import { LucideTrophy, LucideSparkles, LucideArrowRight, LucideStar } from 'lucide-vue-next'
import Iridescence from '@/components/ui/Backgrounds/Iridescence/Iridescence.vue'

const props = defineProps({
  modalId: {
    type: String,
    required: true,
  },
  show: {
    type: Boolean,
    default: false,
  },
  oldLevel: {
    type: Number,
    required: true,
  },
  newLevel: {
    type: Number,
    required: true,
  },
})

const emit = defineEmits(['close'])

// Motivational messages array
const motivationalMessages = [
  "Amazing work! Your dedication is paying off!",
  "Outstanding! You're getting stronger with every challenge!",
  "Incredible! Your progress is inspiring!",
  "Fantastic! Keep pushing your limits!",
  "Brilliant! You're on an amazing learning journey!",
  "Excellent! Your hard work is showing results!",
  "Phenomenal! You're leveling up your skills!",
  "Terrific! Every level reached is a new milestone!",
  "Wonderful! You're becoming a math champion!",
  "Stupendous! Your growth is remarkable!",
]

// Randomly select a motivational message
const motivationalMessage = computed(() => {
  const randomIndex = Math.floor(Math.random() * motivationalMessages.length)
  return motivationalMessages[randomIndex]
})

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
  background-color: rgba(0, 0, 0, 0.4);
}
</style>


<template>
  <div :id="modalId"
    class="hs-overlay hidden size-full fixed top-0 start-0 z-90 overflow-x-hidden overflow-y-auto pointer-events-none"
    role="dialog" tabindex="-1" :aria-labelledby="`${modalId}-label`">
    <div data-modal-inner
      class="hs-overlay-open:mt-0 sm:hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all w-full sm:max-w-lg sm:w-full m-0 sm:m-3 h-full sm:h-auto sm:mx-auto flex items-center justify-center">
      <div
        class="max-h-full overflow-y-auto flex flex-col bg-transparent border-0 shadow-2xl rounded-none sm:rounded-xl pointer-events-auto relative w-full">
        <!-- Iridescent Background -->
        <div v-if="show" class="absolute inset-0 w-full h-full rounded-none sm:rounded-xl pointer-events-none z-0">
          <Iridescence :color="[0.38, 0.65, 1.0]" :speed="0.8" :amplitude="0.15" :mouse-react="false" />
        </div>

        <!-- Content Background with blur for readability -->
        <div
          class="absolute inset-0 w-full h-full rounded-none sm:rounded-xl pointer-events-none z-0 bg-white/80 backdrop-blur-md">
        </div>

        <div class="relative z-10 p-3 sm:p-4 md:p-6 lg:p-8 text-center">
          <!-- Celebration Icon -->
          <div
            class="mx-auto flex items-center justify-center h-14 w-14 sm:h-16 sm:w-16 md:h-20 md:w-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-3 sm:mb-4 md:mb-6 shadow-lg">
            <LucideTrophy class="h-8 w-8 sm:h-10 sm:w-10 md:h-12 md:w-12 text-white" />
          </div>

          <!-- Title -->
          <h3
            class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-2 sm:mb-3 flex flex-col sm:flex-row items-center justify-center gap-1.5 sm:gap-2">
            <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
              Level Up!
            </span>
            <LucideSparkles class="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7 text-blue-500 animate-pulse" />
          </h3>

          <!-- Level Display -->
          <div
            class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-4 sm:p-5 md:p-6 lg:p-8 mb-3 sm:mb-4 md:mb-6 border border-blue-100 mx-1 sm:mx-0">
            <p class="text-xs sm:text-sm md:text-base text-gray-600 mb-2 sm:mb-3 md:mb-4">You've advanced from</p>
            <div class="flex items-center justify-center gap-2 sm:gap-3 md:gap-4 mb-2 sm:mb-3 md:mb-4 flex-wrap">
              <div class="flex flex-col items-center">
                <div class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-400 line-through">Level {{ oldLevel }}
                </div>
              </div>
              <LucideArrowRight class="w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 text-blue-500 flex-shrink-0" />
              <div class="flex flex-col items-center">
                <div
                  class="text-3xl sm:text-4xl md:text-5xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                  Level {{ newLevel }}
                </div>
              </div>
            </div>
          </div>

          <!-- Motivational Message -->
          <div
            class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-xl p-3 sm:p-4 md:p-5 lg:p-6 mb-3 sm:mb-4 md:mb-6 border border-purple-100 mx-1 sm:mx-0">
            <p
              class="text-sm sm:text-base md:text-lg font-semibold text-gray-800 mb-2 sm:mb-3 px-1 sm:px-0 leading-relaxed">
              {{ motivationalMessage }}
            </p>
            <div class="flex items-center justify-center gap-2 mt-2 sm:mt-3">
              <LucideStar class="w-4 h-4 sm:w-5 sm:h-5 text-blue-500 fill-blue-500" />
              <LucideStar class="w-4 h-4 sm:w-5 sm:h-5 text-purple-500 fill-purple-500" />
              <LucideStar class="w-4 h-4 sm:w-5 sm:h-5 text-blue-500 fill-blue-500" />
            </div>
          </div>

          <!-- Action Button -->
          <button @click="handleClose"
            class="min-h-[48px] sm:min-h-[44px] w-full px-4 sm:px-6 py-2.5 sm:py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105 text-sm sm:text-base mx-1 sm:mx-0">
            Continue Your Journey!
            <LucideRocket class="w-4 h-4 sm:w-5 sm:h-5 text-white" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
