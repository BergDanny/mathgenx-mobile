<template>
  <div class="min-h-screen w-full bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 flex flex-col" v-bind="$attrs">
    <!-- Header Section - Fixed -->
    <div v-if="!isCompleted"
      class="flex-shrink-0 w-full px-4 py-4 md:py-6 md:px-8 lg:px-16 bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 md:mb-4 gap-3 sm:gap-0">
          <div class="flex-1">
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900">Student Profiling</h1>
            <p class="text-gray-600 text-xs sm:text-sm md:text-base mt-0.5 sm:mt-1">Complete the assessment to personalize your learning
              experience</p>
          </div>
          <div class="text-left sm:text-right flex items-center gap-2 sm:block">
            <div class="text-xs text-gray-500">Step</div>
            <div class="text-lg sm:text-xl md:text-2xl font-bold text-blue-600">{{ currentStep }} / {{ totalSteps }}</div>
          </div>
        </div>

        <!-- Step Indicators -->
        <div :class="`flex items-center justify-center w-full ${stepContainerClassName}`">
          <template v-for="(_, index) in stepsArray" :key="index + 1">
            <div v-if="!renderStepIndicator" @click="() => handleStepClick(index + 1)" :class="[
              // Mobile-optimized circle size
              'relative outline-none flex h-7 w-7 sm:h-8 sm:w-8 md:h-9 md:w-9 items-center justify-center rounded-full font-semibold transition-all duration-300',
              isCompleted && lockOnComplete ? 'cursor-default' : 'cursor-pointer',
              getStepStatus(index + 1) === 'active' ? 'ring-2 sm:ring-4 ring-blue-200' : ''
            ]" :style="getStepIndicatorStyle(index + 1)">
              <!-- Mobile-optimized check icon -->
              <svg v-if="getStepStatus(index + 1) === 'complete'" class="h-3.5 w-3.5 sm:h-4 sm:w-4 md:h-5 md:w-5 text-white stroke-white"
                fill="none" stroke="currentColor" :stroke-width="3" viewBox="0 0 24 24">
                <Motion as="path" d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"
                  :initial="{ pathLength: 0, opacity: 0 }" :animate="getStepStatus(index + 1) === 'complete'
                    ? { pathLength: 1, opacity: 1 }
                    : { pathLength: 0, opacity: 0 }
                    " />
              </svg>
              <!-- Mobile-optimized active dot -->
              <div v-else-if="getStepStatus(index + 1) === 'active'" class="h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-white" />
              <!-- Mobile-optimized label font -->
              <span v-else class="text-xs sm:text-sm md:text-base">{{ index + 1 }}</span>
            </div>

            <component v-else :is="renderStepIndicator" :step="index + 1" :current-step="currentStep"
              :on-step-click="handleCustomStepClick" />

            <div v-if="index < totalSteps - 1"
              class="relative ml-2 mr-2 sm:ml-3 sm:mr-3 md:ml-4 md:mr-4 h-0.5 sm:h-1 flex-1 overflow-hidden rounded-full bg-gray-200">
              <Motion as="div" class="absolute left-0 top-0 h-full" :initial="{ width: 0, backgroundColor: '#e5e7eb' }"
                :animate="currentStep > index + 1
                  ? { width: '100%', backgroundColor: '#3b82f6' }
                  : { width: 0, backgroundColor: '#e5e7eb' }
                  " :transition="{ type: 'spring', stiffness: 100, damping: 15, duration: 0.4 }" />
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Completion Message -->
    <div v-if="isCompleted" class="min-h-[60vh] flex items-center justify-center px-4">
      <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8 md:p-12 text-center max-w-xl w-full">
        <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-green-100 rounded-full mb-4 sm:mb-6">
          <svg class="w-8 h-8 sm:w-10 sm:h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3 sm:mb-4 flex items-center justify-center gap-2">
          Assessment Complete!
          <LucidePartyPopper class="w-6 h-6 sm:w-7 sm:h-7 text-yellow-500" />
        </h2>
        <p class="text-gray-600 text-base sm:text-lg">Your profile has been successfully created.</p>
      </div>
    </div>

  <!-- Content Section - Scrollable -->
  <!-- add bottom padding so footer doesn't cover last content -->
  <div class="flex-1 overflow-y-auto pb-24 sm:pb-28">
      <div class="max-w-6xl mx-auto px-4 pt-4 sm:pt-6 pb-4 sm:px-6 md:px-8 lg:px-16">
        <Motion as="div" :class="`w-full ${contentClassName}`" :style="{
          position: 'relative',
          minHeight: '300px'
        }">
          <AnimatePresence :initial="false" mode="sync" :custom="direction">
            <Motion v-if="!isCompleted" ref="containerRef" as="div" :key="currentStep"
              :initial="getStepContentInitial()" :animate="{ x: '0%', opacity: 1 }" :exit="getStepContentExit()"
              :transition="{ type: 'tween', stiffness: 300, damping: 30, duration: 0.4 }">
              <div ref="contentRef" v-if="slots.default && slots.default()[currentStep - 1]">
                <component :is="slots.default()[currentStep - 1]" />
              </div>
            </Motion>
          </AnimatePresence>
        </Motion>
      </div>
    </div>

    <!-- Navigation Buttons - Fixed at Bottom -->
    <!-- fixed to viewport so Next/Complete always visible -->
    <div v-if="!isCompleted"
      class="fixed bottom-0 left-0 right-0 w-full border-t border-gray-200 bg-white shadow-lg z-50 safe-area-bottom">
      <div class="max-w-6xl mx-auto px-4 py-3 sm:py-4 md:px-8 lg:px-16">
        <div :class="`flex w-full gap-3 ${currentStep !== 1 ? 'justify-between' : 'justify-end'}`">
          <button v-if="currentStep !== 1" @click="handleBack" :disabled="backButtonProps?.disabled"
            :class="`flex items-center gap-1.5 sm:gap-2 px-4 sm:px-5 md:px-6 py-2.5 sm:py-2 md:py-3 text-sm sm:text-base text-gray-600 bg-gray-100 hover:bg-gray-200 cursor-pointer transition-all duration-300 rounded-lg sm:rounded-xl font-semibold disabled:opacity-50 disabled:cursor-not-allowed ${currentStep === 1 ? 'opacity-50 cursor-not-allowed' : ''}`"
            v-bind="backButtonProps">
            <svg class="w-4 h-4 sm:w-4 sm:h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="hidden sm:inline">{{ backButtonText }}</span>
            <span class="sm:hidden">Back</span>
          </button>
          <button @click="isLastStep ? handleComplete() : handleNext()" :disabled="nextButtonProps?.disabled"
            :class="`flex items-center gap-1.5 sm:gap-2 px-5 sm:px-6 md:px-8 py-2.5 sm:py-2 md:py-3 text-sm sm:text-base bg-blue-600 hover:bg-blue-700 transition-all duration-300 rounded-lg sm:rounded-xl text-white font-semibold tracking-tight cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl`">
            <span class="hidden sm:inline">{{ isLastStep ? 'Complete Assessment' : nextButtonText }}</span>
            <span class="sm:hidden">{{ isLastStep ? 'Complete' : 'Next' }}</span>
            <svg v-if="!isLastStep" class="w-4 h-4 sm:w-4 sm:h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <svg v-else class="w-4 h-4 sm:w-4 sm:h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  ref,
  computed,
  useSlots,
  watch,
  onMounted,
  nextTick,
  useTemplateRef,
  type VNode,
  type ButtonHTMLAttributes,
  type Component
} from 'vue';
import { Motion, AnimatePresence } from 'motion-v'
import { LucidePartyPopper } from 'lucide-vue-next'

interface StepperProps {
  children?: VNode[];
  initialStep?: number;
  onStepChange?: (step: number) => void;
  onFinalStepCompleted?: () => void;
  onBeforeStepChange?: (from: number, to: number) => boolean | Promise<boolean>;
  stepCircleContainerClassName?: string;
  stepContainerClassName?: string;
  contentClassName?: string;
  footerClassName?: string;
  backButtonProps?: ButtonHTMLAttributes;
  nextButtonProps?: ButtonHTMLAttributes;
  backButtonText?: string;
  nextButtonText?: string;
  disableStepIndicators?: boolean;
  renderStepIndicator?: Component;
  lockOnComplete?: boolean;
}

const props = withDefaults(defineProps<StepperProps>(), {
  initialStep: 1,
  onStepChange: () => { },
  onFinalStepCompleted: () => { },
  stepCircleContainerClassName: '',
  stepContainerClassName: '',
  contentClassName: '',
  footerClassName: '',
  backButtonProps: () => ({}),
  nextButtonProps: () => ({}),
  backButtonText: 'Back',
  nextButtonText: 'Continue',
  disableStepIndicators: false,
  renderStepIndicator: undefined,
  lockOnComplete: true
});

const slots = useSlots();
const currentStep = ref(props.initialStep);
const direction = ref(1);
const isCompleted = ref(false);
const parentHeight = ref(0);
const containerRef = useTemplateRef<HTMLDivElement>('containerRef');
const contentRef = useTemplateRef<HTMLDivElement>('contentRef');

const stepsArray = computed(() => slots.default?.() || []);
const totalSteps = computed(() => stepsArray.value.length);
const isLastStep = computed(() => currentStep.value === totalSteps.value);

const getStepStatus = (step: number) => {
  if (isCompleted.value || currentStep.value > step) return 'complete';
  if (currentStep.value === step) return 'active';
  return 'inactive';
};

const getStepIndicatorStyle = (step: number) => {
  const status = getStepStatus(step);
  switch (status) {
    case 'active':
      return { backgroundColor: '#3b82f6', color: '#fff' };
    case 'complete':
      return { backgroundColor: '#10b981', color: '#fff' };
    default:
      return { backgroundColor: '#e5e7eb', color: '#9ca3af' };
  }
};

const getStepContentInitial = () => ({
  x: direction.value >= 0 ? '100%' : '-100%',
  opacity: 0
});

const getStepContentExit = () => ({
  x: direction.value >= 0 ? '-50%' : '50%',
  opacity: 0
});

const handleStepClick = async (step: number) => {
  if (isCompleted.value && props.lockOnComplete) return;
  if (!props.disableStepIndicators) {
    direction.value = step > currentStep.value ? 1 : -1;
    // call before-step-change hook if provided
    if (props.onBeforeStepChange && step !== currentStep.value) {
      const ok = await Promise.resolve(props.onBeforeStepChange(currentStep.value, step));
      if (!ok) return;
    }
    updateStep(step);
  }
};

const handleCustomStepClick = async (clicked: number) => {
  if (isCompleted.value && props.lockOnComplete) return;
  if (clicked !== currentStep.value && !props.disableStepIndicators) {
    direction.value = clicked > currentStep.value ? 1 : -1;
    if (props.onBeforeStepChange) {
      const ok = await Promise.resolve(props.onBeforeStepChange(currentStep.value, clicked));
      if (!ok) return;
    }
    updateStep(clicked);
  }
};

const measureHeight = () => {
  // Height measurement no longer needed for scrollable layout
  nextTick(() => {
    if (contentRef.value) {
      const height = contentRef.value.offsetHeight;
      if (height > 0) {
        parentHeight.value = height;
      }
    }
  });
};

const updateStep = (newStep: number) => {
  if (newStep >= 1 && newStep <= totalSteps.value) {
    currentStep.value = newStep;
  }
};

const handleBack = () => {
  direction.value = -1;
  updateStep(currentStep.value - 1);
};

const handleNext = async () => {
  direction.value = 1;
  const target = currentStep.value + 1;
  if (props.onBeforeStepChange) {
    const ok = await Promise.resolve(props.onBeforeStepChange(currentStep.value, target));
    if (!ok) return;
  }
  updateStep(target);
};

const handleComplete = async () => {
  // allow parent to validate the final step before completing
  if (props.onBeforeStepChange) {
    const ok = await Promise.resolve(props.onBeforeStepChange(currentStep.value, currentStep.value));
    if (!ok) return;
  }
  isCompleted.value = true;
  props.onFinalStepCompleted?.();
};

watch(currentStep, (newStep, oldStep) => {
  props.onStepChange?.(newStep);
  if (newStep !== oldStep && !isCompleted.value) {
    nextTick(measureHeight);
  } else if (!props.lockOnComplete && isCompleted.value) {
    isCompleted.value = false;
    nextTick(measureHeight);
  }
});

onMounted(() => {
  if (props.initialStep !== 1) {
    currentStep.value = props.initialStep;
  }
  measureHeight();
});
</script>