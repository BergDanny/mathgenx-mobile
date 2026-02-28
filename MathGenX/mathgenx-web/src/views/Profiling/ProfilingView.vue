<script setup>
import { onMounted, inject } from 'vue';
import Stepper from '@/components/ui/Stepper/Stepper.vue';
import MathQuestion from '@/views/Profiling/MathQuestion.vue';
import VarkQuestion from '@/views/Profiling/VarkQuestion.vue';
import BigModal from '@/components/Modal/BigModal.vue';
import { useRouter } from 'vue-router';
import { useProfilingStore } from '@/stores/profilingStore';

const router = useRouter();
const profilingStore = useProfilingStore();
const toast = inject('toast');

// Stepper event handlers
const handleStepChange = (step) => {
  console.log('🧭 Current step:', step);
};

// Validate before allowing step change. Returns true to allow, false to block.
const validateBeforeStepChange = (from, to) => {
  // from and to are step numbers (1-based)
  // Step 1 -> VARK; Step 2 -> Math
  if (from === 1) {
    // moving away from VARK: ensure all VARK questions answered
    const total = profilingStore.varkQuestions.length || 0;
    const answered = Object.keys(profilingStore.varkResponses || {}).length;
    if (answered < total) {
      toast?.value?.showToast?.({
        type: 'error',
        title: 'Complete all questions',
        message: 'Please answer all VARK questions before proceeding.',
        duration: 3000,
      });
      return false;
    }
  }

  if (from === 2) {
    // moving away from Math (or completing): ensure all Math questions answered
    const total = profilingStore.mathQuestions.length || 0;
    const answered = Object.keys(profilingStore.mathResponses || {}).length;
    if (answered < total) {
      toast?.value?.showToast?.({
        type: 'error',
        title: 'Complete all questions',
        message: 'Please answer all Math questions before proceeding.',
        duration: 3000,
      });
      return false;
    }
  }

  return true;
};

const handleComplete = async () => {
  try {
    // bundle all profiling data into one payload
    const payload = {
      vark_responses: profilingStore.varkResponses,
      math_responses: profilingStore.mathResponses,
      user_profile: profilingStore.userProfile,
    };

    const result = await profilingStore.submitAllProfiling(payload);

    if (result.ok) {
      toast?.value?.showToast?.({
        type: 'success',
        title: 'Profiling Complete',
        message: 'Your learning profile has been successfully submitted!',
        duration: 3000,
      });

      profilingStore.clearState();

      router.push('/dashboard');
    } else {
      toast?.value?.showToast?.({
        type: 'error',
        title: 'Submission Failed',
        message: result.message || 'An error occurred while completing profiling.',
        duration: 4000,
      });
    }
  } catch (err) {
    console.error('Profiling submission failed:', err);
    toast?.value?.showToast?.({
      type: 'error',
      title: 'Unexpected Error',
      message: 'Something went wrong. Please try again.',
      duration: 4000,
    });
  }
};

onMounted(() => {
  profilingStore.restoreState();
});
</script>

<template>
  <Stepper
    :initial-step="1"
    :on-step-change="handleStepChange"
    :on-before-step-change="validateBeforeStepChange"
    :on-final-step-completed="handleComplete"
    back-button-text="Previous"
    next-button-text="Next"
    final-button-text="Complete Assessment"
  >
    <VarkQuestion />
    <MathQuestion />
  </Stepper>
</template>
