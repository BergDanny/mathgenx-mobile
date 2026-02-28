<template>
  <Teleport to="body">
    <div v-if="isActive" class="fixed inset-0 z-[9999]">
      <!-- Light overlay with cutout for highlighted element -->
      <div 
        class="absolute inset-0 bg-black/30 transition-opacity duration-300"
        @click="handleOverlayClick"
      ></div>

    <!-- Highlighted element border/glow -->
    <div
      v-if="currentStep && highlightStyle"
      :style="highlightStyle"
      class="fixed border-4 border-purple-500 rounded-xl shadow-[0_0_0_9999px_rgba(0,0,0,0.3)] pointer-events-none z-10 transition-all duration-300"
    ></div>

    <!-- Popup -->
    <div
      v-if="currentStep && popupStyle"
      :style="popupStyle"
      class="fixed bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-2xl p-3 sm:p-4 z-20 w-[calc(100vw-2rem)] sm:w-auto sm:min-w-[260px] sm:max-w-[300px]"
    >
      <!-- Step counter -->
      <div class="text-white/80 text-xs font-semibold mb-2">
        {{ currentStepIndex + 1 }}/{{ steps.length }}
      </div>

      <!-- Title -->
      <h3 class="text-white text-base sm:text-lg font-bold mb-1.5">
        {{ currentStep.title }}
      </h3>

      <!-- Description -->
      <p class="text-white/90 text-xs sm:text-sm mb-4 leading-relaxed">
        {{ currentStep.description }}
      </p>

      <!-- Buttons -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
        <button
          @click="skipTour"
          class="px-3 py-2 text-xs sm:text-sm font-semibold text-white/80 hover:text-white transition-colors rounded-lg hover:bg-white/10 min-h-[40px] flex items-center justify-center"
        >
          Skip tour
        </button>
        <button
          @click="nextStep"
          class="px-4 py-2 bg-white text-purple-600 text-xs sm:text-sm font-semibold rounded-lg hover:bg-white/90 transition-colors shadow-md min-h-[40px] flex items-center justify-center flex-1 sm:flex-initial"
        >
          {{ currentStepIndex === steps.length - 1 ? 'Finish' : 'Next' }}
        </button>
      </div>
    </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
  steps: {
    type: Array,
    required: true,
    validator: (steps) => {
      return steps.every(step => 
        step.title && 
        step.description && 
        step.targetSelector
      );
    }
  },
  isActive: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['complete', 'skip']);

const currentStepIndex = ref(0);
const highlightStyle = ref(null);
const popupStyle = ref(null);

const currentStep = computed(() => {
  return props.steps[currentStepIndex.value] || null;
});

const updatePosition = async () => {
  if (!currentStep.value) return;

  await nextTick();

  const targetElement = document.querySelector(currentStep.value.targetSelector);
  if (!targetElement) {
    console.warn(`Target element not found: ${currentStep.value.targetSelector}`);
    return;
  }

  const rect = targetElement.getBoundingClientRect();
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const isMobile = viewportWidth < 640; // sm breakpoint

  // Calculate highlight position (around the target element) - use fixed positioning
  const padding = 8;
  highlightStyle.value = {
    left: `${rect.left - padding}px`,
    top: `${rect.top - padding}px`,
    width: `${rect.width + padding * 2}px`,
    height: `${rect.height + padding * 2}px`,
  };

  // Calculate popup position - smaller and more compact
  const popupWidth = isMobile ? viewportWidth - 32 : 280; // Reduced width
  const popupHeight = isMobile ? 180 : 160; // Reduced height for compactness
  const margin = isMobile ? 12 : 12;
  const spaceFromTarget = 12; // Space between popup and target element

  // Check if target is near bottom of viewport (within 40% of bottom)
  const isNearBottom = rect.bottom > viewportHeight * 0.6;
  // Check if target is near top of viewport (within 30% of top)
  const isNearTop = rect.top < viewportHeight * 0.3;
  // Check if target is near right edge
  const isNearRight = rect.right > viewportWidth * 0.7;
  // Check if target is near left edge
  const isNearLeft = rect.left < viewportWidth * 0.3;

  let popupLeft, popupTop;

  if (isMobile) {
    // Mobile: Center horizontally, smart vertical positioning
    popupLeft = (viewportWidth - popupWidth) / 2;
    
    // If target is near bottom, position popup above it
    if (isNearBottom && rect.top - popupHeight - spaceFromTarget >= margin) {
      popupTop = rect.top - popupHeight - spaceFromTarget;
    }
    // If target is near top, position popup below it
    else if (isNearTop && rect.bottom + popupHeight + spaceFromTarget <= viewportHeight - margin) {
      popupTop = rect.bottom + spaceFromTarget;
    }
    // Try above first if there's space
    else if (rect.top - popupHeight - spaceFromTarget >= margin) {
      popupTop = rect.top - popupHeight - spaceFromTarget;
    }
    // Try below
    else if (rect.bottom + popupHeight + spaceFromTarget <= viewportHeight - margin) {
      popupTop = rect.bottom + spaceFromTarget;
    }
    // Fallback: center vertically but ensure it doesn't cover the target
    else {
      const centerY = (viewportHeight - popupHeight) / 2;
      // If center would cover target, position above or below
      if (centerY < rect.bottom && centerY + popupHeight > rect.top) {
        if (rect.top - popupHeight - spaceFromTarget >= margin) {
          popupTop = rect.top - popupHeight - spaceFromTarget;
        } else {
          popupTop = Math.max(margin, viewportHeight - popupHeight - margin);
        }
      } else {
        popupTop = centerY;
      }
    }
  } else {
    // Desktop: Smart positioning based on available space
    // Priority: avoid covering the target element and ensure buttons are clickable
    
    // If target is near bottom, prefer positioning above
    if (isNearBottom && rect.top - popupHeight - spaceFromTarget >= margin) {
      // Position above, try right side first
      if (rect.right + popupWidth + margin <= viewportWidth) {
        popupLeft = rect.right + spaceFromTarget;
        popupTop = rect.top - popupHeight - spaceFromTarget;
      }
      // Try left side
      else if (rect.left - popupWidth - spaceFromTarget >= margin) {
        popupLeft = rect.left - popupWidth - spaceFromTarget;
        popupTop = rect.top - popupHeight - spaceFromTarget;
      }
      // Center horizontally above
      else {
        popupLeft = rect.left + (rect.width / 2) - (popupWidth / 2);
        popupTop = rect.top - popupHeight - spaceFromTarget;
      }
    }
    // If target is near top, prefer positioning below
    else if (isNearTop && rect.bottom + popupHeight + spaceFromTarget <= viewportHeight - margin) {
      // Position below, try right side first
      if (rect.right + popupWidth + margin <= viewportWidth) {
        popupLeft = rect.right + spaceFromTarget;
        popupTop = rect.bottom + spaceFromTarget;
      }
      // Try left side
      else if (rect.left - popupWidth - spaceFromTarget >= margin) {
        popupLeft = rect.left - popupWidth - spaceFromTarget;
        popupTop = rect.bottom + spaceFromTarget;
      }
      // Center horizontally below
      else {
        popupLeft = rect.left + (rect.width / 2) - (popupWidth / 2);
        popupTop = rect.bottom + spaceFromTarget;
      }
    }
    // Default: try right side first
    else if (rect.right + popupWidth + spaceFromTarget <= viewportWidth) {
      popupLeft = rect.right + spaceFromTarget;
      // Position vertically centered with target, but ensure it fits
      popupTop = Math.max(margin, Math.min(rect.top, viewportHeight - popupHeight - margin));
    }
    // Try left side
    else if (rect.left - popupWidth - spaceFromTarget >= margin) {
      popupLeft = rect.left - popupWidth - spaceFromTarget;
      popupTop = Math.max(margin, Math.min(rect.top, viewportHeight - popupHeight - margin));
    }
    // Fallback: position above if near bottom, below if near top
    else {
      popupLeft = Math.max(margin, Math.min(rect.left + (rect.width / 2) - (popupWidth / 2), viewportWidth - popupWidth - margin));
      
      if (isNearBottom && rect.top - popupHeight - spaceFromTarget >= margin) {
        popupTop = rect.top - popupHeight - spaceFromTarget;
      } else if (rect.bottom + popupHeight + spaceFromTarget <= viewportHeight - margin) {
        popupTop = rect.bottom + spaceFromTarget;
      } else {
        // Last resort: position in available space
        popupTop = Math.max(margin, Math.min((viewportHeight - popupHeight) / 2, viewportHeight - popupHeight - margin));
      }
    }
  }

  // Ensure popup stays within viewport and doesn't cover the target
  popupLeft = Math.max(margin, Math.min(popupLeft, viewportWidth - popupWidth - margin));
  
  // Ensure popup doesn't overlap with target element
  const popupBottom = popupTop + popupHeight;
  const popupRight = popupLeft + popupWidth;
  
  // If popup overlaps with target, adjust position
  if (
    popupLeft < rect.right &&
    popupRight > rect.left &&
    popupTop < rect.bottom &&
    popupBottom > rect.top
  ) {
    // Popup is overlapping target, move it
    if (isNearBottom) {
      // Move above
      popupTop = Math.max(margin, rect.top - popupHeight - spaceFromTarget);
    } else {
      // Move below
      popupTop = Math.min(viewportHeight - popupHeight - margin, rect.bottom + spaceFromTarget);
    }
  }
  
  // Final bounds check
  popupTop = Math.max(margin, Math.min(popupTop, viewportHeight - popupHeight - margin));

  popupStyle.value = {
    left: `${popupLeft}px`,
    top: `${popupTop}px`,
  };
};

const nextStep = () => {
  if (currentStepIndex.value < props.steps.length - 1) {
    currentStepIndex.value++;
    updatePosition();
  } else {
    emit('complete');
  }
};

const skipTour = () => {
  emit('skip');
};

const handleOverlayClick = () => {
  // Don't close on overlay click - require explicit skip/next
};

// Watch for step changes and active state
watch(() => props.isActive, (newVal) => {
  if (newVal) {
    currentStepIndex.value = 0;
    updatePosition();
  } else {
    // Clean up when tour becomes inactive
    highlightStyle.value = null;
    popupStyle.value = null;
  }
});

watch(() => currentStepIndex.value, () => {
  if (props.isActive) {
    updatePosition();
  }
});

// Handle window resize and scroll
const handleResize = () => {
  if (props.isActive) {
    updatePosition();
  }
};

const handleScroll = () => {
  if (props.isActive) {
    updatePosition();
  }
};

onMounted(() => {
  window.addEventListener('resize', handleResize);
  window.addEventListener('scroll', handleScroll, true); // Use capture to catch all scroll events
  if (props.isActive) {
    updatePosition();
  }
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
  window.removeEventListener('scroll', handleScroll, true);
});
</script>

<style scoped>
/* Ensure the highlight border is visible above overlay with lighter shadow */
.shadow-\[0_0_0_9999px_rgba\(0\,0\,0\,0\.3\)\] {
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.3);
}
</style>
