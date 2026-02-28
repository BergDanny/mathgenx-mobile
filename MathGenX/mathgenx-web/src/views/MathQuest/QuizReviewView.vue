<script setup>
  import { ref, onMounted } from 'vue'
  import { useRouter, useRoute } from 'vue-router'
  import { getQuizAttemptDetails } from '@/services/MathQuestService'
  import { LucideChevronLeft, LucideCheck, LucideX } from 'lucide-vue-next'
  
  const router = useRouter()
  const route = useRoute()
  
  const loading = ref(true)
  const error = ref(null)
  const attemptData = ref(null)
  
  const isSelectedChoice = (choice, response) => {
    return response.student_answer && parseInt(response.student_answer) === parseInt(choice.id)
  }
  
  const isCorrectChoice = (choice, response) => {
    if (response.question_data?.answer_key) {
      return choice.label === response.question_data.answer_key
    }
    return choice.is_correct || choice.isCorrect || choice.correct
  }
  
  const getChoiceClass = (choice, response) => {
    const isSelected = isSelectedChoice(choice, response)
    const isCorrect = isCorrectChoice(choice, response)
  
    if (isCorrect) {
      return 'border-green-500 bg-green-50'
    }
    if (isSelected && !response.is_correct) {
      return 'border-red-500 bg-red-50'
    }
    return 'border-gray-200 bg-gray-50'
  }
  
  const getChoiceIconClass = (choice, response) => {
    const isSelected = isSelectedChoice(choice, response)
    const isCorrect = isCorrectChoice(choice, response)
  
    if (isCorrect) {
      return 'border-green-600 bg-green-600'
    }
    if (isSelected && !response.is_correct) {
      return 'border-red-600 bg-red-600'
    }
    return 'border-gray-300'
  }
  
  const goBack = () => {
    router.push({ name: 'quiz_attempts' })
  }
  
  const fetchAttemptDetails = async () => {
    try {
      loading.value = true
      error.value = null
  
      const attemptId = route.params.id
      if (!attemptId) {
        error.value = 'Attempt ID not found'
        return
      }
  
      const result = await getQuizAttemptDetails(attemptId)
  
      console.log('Quiz Review API Response:', result)
  
      if (result.status === 'success' && result.data) {
        attemptData.value = result.data
        console.log('Attempt Data:', attemptData.value)
        console.log('Responses:', attemptData.value.responses)
        console.log('Responses Length:', attemptData.value.responses?.length)
      } else {
        error.value = result.message || 'Failed to load attempt details'
      }
    } catch (err) {
      error.value = 'An error occurred while loading attempt details'
      console.error('Error fetching attempt details:', err)
    } finally {
      loading.value = false
    }
  }
  
  onMounted(() => {
    fetchAttemptDetails()
  })
  </script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4 md:py-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div>
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900">Answer Review</h1>
            <p class="text-xs sm:text-sm text-gray-600 mt-1">View correct and incorrect answers</p>
          </div>
          <button @click="goBack"
            class="min-h-[44px] w-full sm:w-auto px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
            <LucideChevronLeft class="w-4 h-4" />
            Back
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center min-h-[60vh]">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-lg font-semibold text-gray-800">Loading review...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <p class="text-red-800">{{ error }}</p>
        <button @click="goBack" class="mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
          Back
        </button>
      </div>
    </div>

    <!-- Review Content -->
    <div v-else-if="attemptData" class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
      <!-- Summary Card -->
      <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ attemptData.attempt.correct_answers }}</div>
            <div class="text-sm text-gray-600">Correct</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-red-600">{{ attemptData.attempt.incorrect_answers }}</div>
            <div class="text-sm text-gray-600">Incorrect</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ Math.round(attemptData.attempt.score_percentage) }}%</div>
            <div class="text-sm text-gray-600">Score</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-gray-700">{{ attemptData.attempt.total_questions }}</div>
            <div class="text-sm text-gray-600">Total</div>
          </div>
        </div>
      </div>

      <!-- Questions Review -->
      <div v-if="!attemptData.responses || attemptData.responses.length === 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
        <p class="text-yellow-800">No questions found for this attempt.</p>
        <p class="text-sm text-yellow-700 mt-2">The responses may not have been saved correctly.</p>
      </div>
      
      <div v-else class="space-y-4 sm:space-y-6">
        <div v-for="(response, index) in attemptData.responses" :key="response.id"
          class="bg-white rounded-xl shadow-md p-4 sm:p-5 md:p-6">
          <!-- Question Header -->
          <div class="mb-4">
            <div class="flex items-start gap-2 sm:gap-3 mb-3">
              <span :class="[
                'flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-semibold text-sm sm:text-base',
                response.is_correct
                  ? 'bg-green-100 text-green-700'
                  : 'bg-red-100 text-red-700'
              ]">
                {{ index + 1 }}
              </span>
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <span :class="[
                    'inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-full text-xs font-medium',
                    response.is_correct
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'
                  ]">
                    <span class="flex items-center gap-1">
                        <LucideCheck v-if="response.is_correct" class="w-3 h-3 sm:w-4 sm:h-4" />
                        <LucideX v-else class="w-3 h-3 sm:w-4 sm:h-4" />
                        {{ response.is_correct ? 'Correct' : 'Incorrect' }}
                    </span>
                  </span>
                  <span v-if="response.mastery_level"
                    class="inline-flex items-center px-2 sm:px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    {{ response.mastery_level }}
                  </span>
                </div>
                <h3 class="text-sm sm:text-base md:text-lg font-semibold text-gray-900 leading-relaxed">
                  {{ response.question_text }}
                </h3>
              </div>
            </div>

            <!-- Question Image (if exists) -->
            <div v-if="response.question_data?.image_url" class="mt-3 sm:mt-4 sm:ml-11">
              <img :src="response.question_data.image_url" :alt="`Question ${index + 1}`"
                class="max-w-full sm:max-w-md rounded-lg border border-gray-200" />
            </div>
          </div>

          <!-- Multiple Choice Answers -->
          <div v-if="response.question_format === 'multiple_choice' && response.question_data?.choices && response.question_data.choices.length > 0"
            class="space-y-2 mb-4">
            <div v-for="choice in response.question_data.choices" :key="choice.id" 
                class="min-h-[44px]"
                :class="[
              'p-3 sm:p-4 rounded-lg border-2',
              getChoiceClass(choice, response)
            ]">
              <div class="flex items-start gap-3">
                <div :class="[
                  'flex-shrink-0 w-5 h-5 sm:w-6 sm:h-6 rounded-full border-2 flex items-center justify-center',
                  getChoiceIconClass(choice, response)
                ]">
                  <svg v-if="isSelectedChoice(choice, response) || isCorrectChoice(choice, response)"
                    class="w-3 h-3 sm:w-4 sm:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path v-if="isCorrectChoice(choice, response)" fill-rule="evenodd"
                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                      clip-rule="evenodd" />
                    <path v-else fill-rule="evenodd"
                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                      clip-rule="evenodd" />
                  </svg>
                </div>
                <span class="text-sm sm:text-base text-gray-900 flex-1">{{ choice.answer_text || choice.text }}</span>
                <span v-if="isCorrectChoice(choice, response)" class="ml-2 text-xs font-medium text-green-700 whitespace-nowrap">
                  Correct Answer
                </span>
                <span v-else-if="isSelectedChoice(choice, response) && !response.is_correct"
                  class="ml-2 text-xs font-medium text-red-700 whitespace-nowrap">
                  Your Answer
                </span>
              </div>
            </div>
          </div>

          <!-- Subjective Answer or Missing Question Data -->
          <div v-else-if="response.question_format === 'subjective' || !response.question_data" class="space-y-3 mb-4">
            <!-- Show warning if question data is missing -->
            <div v-if="!response.question_data" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
              <p class="text-yellow-800 text-sm">Question data not available. Showing basic information.</p>
            </div>
            <!-- Working Steps (if available) -->
            <div v-if="response.question_data?.working_steps?.length" class="bg-gray-50 border rounded-lg p-4">
              <h4 class="font-semibold text-gray-900 mb-2">Solution Steps:</h4>
              <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700">
                <li v-for="(step, stepIndex) in response.question_data.working_steps" :key="stepIndex">
                  {{ step }}
                </li>
              </ol>
            </div>

            <!-- Your Answer -->
            <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4">
              <h4 class="font-semibold text-red-800 mb-2">Your Answer:</h4>
              <p class="text-gray-900">{{ response.student_answer || 'No answer' }}</p>
            </div>

            <!-- Correct Answer -->
            <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4">
              <h4 class="font-semibold text-green-800 mb-2">Correct Answer:</h4>
              <p class="text-gray-900">{{ response.correct_answer || 'No answer' }}</p>
              <p v-if="response.question_data?.accepted_variations?.length" class="text-xs text-gray-600 mt-2">
                Accepted variations: {{ response.question_data.accepted_variations.join(', ') }}
              </p>
            </div>
          </div>

          <!-- Marks -->
          <div class="pt-4 border-t border-gray-200">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Score:</span>
              <span class="font-semibold text-gray-900">
                {{ response.marks_obtained }} / {{ response.total_marks }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


