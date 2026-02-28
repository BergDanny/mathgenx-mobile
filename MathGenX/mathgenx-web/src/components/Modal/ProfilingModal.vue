<template>
  <teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-md bg-black/30 transition-opacity"
      @click.self="closeModal"
    >
      <div
        class="relative flex flex-col bg-white shadow-2xl rounded-none sm:rounded-xl w-full sm:max-w-4xl sm:w-full m-0 sm:m-3 max-h-[100vh] sm:max-h-[90vh] overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 sm:p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
          <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-900">Profiling Questionnaire</h2>
          <button
            type="button"
            class="min-h-[44px] min-w-[44px] inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-white text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 shadow-sm"
            @click="closeModal"
          >
            <span class="sr-only">Close</span>
            <svg
              class="shrink-0 size-4"
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
          </button>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto p-4 sm:p-6 space-y-4 sm:space-y-6">
          <!-- Loading State -->
          <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-sm text-gray-600">Loading profiling data...</p>
          </div>

          <!-- VARK Section -->
          <div v-if="profilingData?.vark" class="border border-gray-200 rounded-lg p-6 bg-white">
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">VARK Learning Style</h3>
              <div v-if="profilingData.vark.summary" class="flex flex-wrap gap-3 mb-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-2">
                  <div class="text-xs text-blue-600 font-medium">Dominant Style</div>
                  <div class="text-sm font-bold text-blue-900">
                    {{ profilingData.vark.summary.dominant_style }}
                  </div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg px-4 py-2">
                  <div class="text-xs text-purple-600 font-medium">Visual (V)</div>
                  <div class="text-sm font-bold text-purple-900">
                    {{ profilingData.vark.summary.score_v }}
                  </div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-2">
                  <div class="text-xs text-green-600 font-medium">Auditory (A)</div>
                  <div class="text-sm font-bold text-green-900">
                    {{ profilingData.vark.summary.score_a }}
                  </div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-2">
                  <div class="text-xs text-yellow-600 font-medium">Read/Write (R)</div>
                  <div class="text-sm font-bold text-yellow-900">
                    {{ profilingData.vark.summary.score_r }}
                  </div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-2">
                  <div class="text-xs text-red-600 font-medium">Kinesthetic (K)</div>
                  <div class="text-sm font-bold text-red-900">
                    {{ profilingData.vark.summary.score_k }}
                  </div>
                </div>
              </div>
            </div>
            <div
              v-if="profilingData.vark.responses && profilingData.vark.responses.length > 0"
              class="space-y-3"
            >
              <div
                v-for="(response, index) in profilingData.vark.responses"
                :key="response.id"
                class="bg-gray-50 rounded-lg p-4 border border-gray-200"
              >
                <div class="flex items-start gap-3">
                  <span
                    class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-xs"
                  >
                    {{ index + 1 }}
                  </span>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 mb-2">
                      {{ response.question_text }}
                    </p>
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="text-xs text-gray-600">Your Answer:</span>
                      <span class="text-sm font-semibold text-gray-900">{{
                        response.answer_text
                      }}</span>
                      <span
                        v-if="response.category"
                        :class="[
                          'ml-2 px-2 py-0.5 rounded text-xs font-medium',
                          response.category === 'V'
                            ? 'bg-purple-100 text-purple-700'
                            : response.category === 'A'
                              ? 'bg-green-100 text-green-700'
                              : response.category === 'R'
                                ? 'bg-yellow-100 text-yellow-700'
                                : 'bg-red-100 text-red-700',
                        ]"
                      >
                        {{ response.category }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-gray-500 italic text-center py-4">
              No VARK responses found.
            </div>
          </div>

          <!-- Math Section -->
          <div v-if="profilingData?.math" class="border border-gray-200 rounded-lg p-6 bg-white">
            <div class="mb-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Math Assessment</h3>
              <div v-if="profilingData.math.summary" class="flex flex-wrap gap-3 mb-4">
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-2">
                  <div class="text-xs text-indigo-600 font-medium">Level</div>
                  <div class="text-sm font-bold text-indigo-900">
                    {{ profilingData.math.summary.level }}
                  </div>
                </div>
                <div class="bg-teal-50 border border-teal-200 rounded-lg px-4 py-2">
                  <div class="text-xs text-teal-600 font-medium">Score</div>
                  <div class="text-sm font-bold text-teal-900">
                    {{ profilingData.math.summary.total_score }}
                  </div>
                </div>
              </div>
            </div>
            <div
              v-if="profilingData.math.responses && profilingData.math.responses.length > 0"
              class="space-y-3"
            >
              <div
                v-for="(response, index) in profilingData.math.responses"
                :key="response.id"
                :class="[
                  'rounded-lg p-4 border-2',
                  response.is_correct
                    ? 'bg-green-50 border-green-200'
                    : 'bg-red-50 border-red-200',
                ]"
              >
                <div class="flex items-start gap-3">
                  <span
                    :class="[
                      'flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center font-semibold text-xs',
                      response.is_correct
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700',
                    ]"
                  >
                    {{ index + 1 }}
                  </span>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900 mb-2">
                      {{ response.question_text }}
                    </p>
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="text-xs text-gray-600">Your Answer:</span>
                      <span class="text-sm font-semibold text-gray-900">{{
                        response.answer_text
                      }}</span>
                      <span
                        :class="[
                          'ml-2 px-2 py-0.5 rounded text-xs font-medium',
                          response.is_correct
                            ? 'bg-green-100 text-green-700'
                            : 'bg-red-100 text-red-700',
                        ]"
                      >
                        {{ response.is_correct ? 'Correct' : 'Incorrect' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-gray-500 italic text-center py-4">
              No Math responses found.
            </div>
          </div>

          <!-- No Data State -->
          <div
            v-if="!loading && (!profilingData || (!profilingData.vark && !profilingData.math))"
            class="text-center py-8"
          >
            <p class="text-gray-500">No profiling data available.</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end p-6 border-t border-gray-200 bg-gray-50">
          <button
            type="button"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
            @click="closeModal"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </teleport>
</template>

<script setup>
defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  profilingData: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const closeModal = () => {
  emit('close')
}
</script>

