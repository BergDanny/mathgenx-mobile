<script setup>
import { ref, onMounted, inject, watch } from "vue";
import { getMathQuestions } from "@/services/profilingService";
import { useProfilingStore } from "@/stores/profilingStore";

const profilingStore = useProfilingStore();
const loading = ref(true);
const error = ref(null);
const mathQuestions = ref([]);
const toast = inject("toast");

// Load questions
async function loadMathQuestions() {
    loading.value = true;
    error.value = null;
    try {
        const res = await getMathQuestions();
        if (res.success) {
            mathQuestions.value = res.data || [];
            profilingStore.mathQuestions = res.data || [];
        } else {
            error.value = res.message || "Failed to load questions.";
        }
    } catch (err) {
        error.value = "Failed to load questions.";
        console.error(err);
    } finally {
        loading.value = false;
    }
}

// Save answer directly to Pinia
function selectAnswer(questionId, answerId) {
    profilingStore.saveMathResponse(questionId, answerId);
}

// Initialize local state from store (auto restore)
onMounted(async () => {
    await loadMathQuestions();
    profilingStore.restoreState(); // restore any saved answers
});
</script>

<template>
    <div class="max-w-5xl mx-auto py-4 sm:py-6 md:py-10 px-2 sm:px-4 pt-0">
        <div v-if="loading" class="text-center py-8 sm:py-12">
            <div class="inline-block animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-sm sm:text-base text-gray-600">Loading questions...</p>
        </div>

        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 sm:p-6 text-center">
            <p class="text-sm sm:text-base text-red-600">{{ error }}</p>
        </div>

        <div v-else class="space-y-3 sm:space-y-4">
            <div v-for="(question, index) in profilingStore.mathQuestions" :key="question.id"
                class="bg-white border-2 border-gray-200 rounded-lg sm:rounded-xl p-4 sm:p-5 md:p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="mb-4 sm:mb-6">
                    <span
                        class="inline-block px-2.5 sm:px-3 py-0.5 sm:py-1 bg-blue-100 text-blue-700 rounded-full text-xs sm:text-sm font-semibold mb-2 sm:mb-3">
                        Question {{ index + 1 }} of {{ profilingStore.mathQuestions.length }}
                    </span>
                    <p class="text-base sm:text-lg font-semibold text-gray-800 leading-relaxed">
                        {{ question.question_text }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 md:gap-4">
                    <label v-for="answer in question.answers" :key="answer.id"
                        class="relative flex items-center gap-2 sm:gap-2 md:gap-3 p-2 sm:p-2.5 md:p-3 border rounded-lg cursor-pointer transition-all duration-200 hover:border-blue-400 hover:bg-blue-50 min-h-[60px] sm:min-h-[70px] md:min-h-[80px]"
                        :class="profilingStore.mathResponses[question.id] === answer.id
                                ? 'border-blue-600 bg-blue-50 shadow-md'
                                : 'border-gray-200 bg-white'
                            ">
                        <input type="radio" :name="`q-${question.id}`" :value="answer.id"
                            class="flex-shrink-0 w-4 h-4 sm:w-5 sm:h-5 text-blue-600 focus:ring-blue-500 cursor-pointer"
                            :checked="profilingStore.mathResponses[question.id] === answer.id"
                            @change="selectAnswer(question.id, answer.id)" />

                        <div class="flex-1 pr-4 sm:pr-4 md:pr-6">
                            <div class="text-gray-800 leading-relaxed text-xs sm:text-sm md:text-base">
                                <div>{{ answer.answer_text }}</div>
                            </div>
                        </div>

                        <div v-if="profilingStore.mathResponses[question.id] === answer.id"
                            class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 md:top-3 md:right-3 w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6 bg-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 md:w-4 md:h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>
