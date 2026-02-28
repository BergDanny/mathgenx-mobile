<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { getQuizAttempts } from '@/services/MathQuestService'
import Aurora from "@/components/ui/Backgrounds/Aurora/Aurora.vue"
import { LucideBarChart3, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const router = useRouter()

const loading = ref(true)
const error = ref(null)
const attempts = ref([])
const currentPage = ref(1)
const itemsPerPage = 6

const getScoreClass = (score) => {
    if (score >= 80) return 'bg-green-100 text-green-800'
    if (score >= 60) return 'bg-yellow-100 text-yellow-800'
    return 'bg-red-100 text-red-800'
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('ms-MY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    })
}

const formatTime = (seconds) => {
    if (!seconds) return '0 min'
    const minutes = Math.floor(seconds / 60)
    const secs = seconds % 60
    if (minutes > 0) {
        return secs > 0 ? `${minutes} min ${secs} s` : `${minutes} min`
    }
    return `${secs} s`
}

const viewAttempt = (attemptId) => {
    router.push({ name: 'quiz_review', params: { id: attemptId } })
}

const goToBuilder = () => {
    router.push({ name: 'mathquest_builder' })
}

// Pagination computed properties
const totalPages = computed(() => {
    return Math.ceil(attempts.value.length / itemsPerPage)
})

const paginatedAttempts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    const end = start + itemsPerPage
    return attempts.value.slice(start, end)
})

// Pagination methods
const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const getPageNumbers = () => {
    const pages = []
    const maxVisible = 5
    let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
    let end = Math.min(totalPages.value, start + maxVisible - 1)

    if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1)
    }

    for (let i = start; i <= end; i++) {
        pages.push(i)
    }

    return pages
}

const fetchAttempts = async () => {
    try {
        loading.value = true
        error.value = null

        const result = await getQuizAttempts()

        if (result.status === 'success' && result.data) {
            attempts.value = result.data
        } else {
            error.value = result.message || 'Failed to load attempts'
        }
    } catch (err) {
        error.value = 'An error occurred while loading attempts'
        console.error('Error fetching attempts:', err)
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    fetchAttempts()
})
</script>

<template>
    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 pb-6 sm:pb-10 px-2 sm:px-4 md:px-6">
        <!-- Header Section with Aurora -->
        <div class="relative rounded-2xl overflow-hidden">
            <Aurora :color-stops="['#60A5FA', '#A78BFA', '#FBCFE8']" :amplitude="0.65" :blend="0.35" :speed="1.5"
                :intensity="0.65" class="absolute inset-0 w-full h-full" />

            <div class="absolute inset-0 bg-white/8 backdrop-blur-sm z-10 pointer-events-none"></div>

            <div class="relative z-20 p-4 sm:p-6 md:p-8 text-gray-900 mt-10 md:mt-0">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold mb-2 flex items-center gap-2">
                            Quiz Attempt History
                            <LucideBarChart3 class="w-5 h-5 sm:w-7 sm:h-7" />
                        </h1>
                        <p class="text-gray-600 text-sm sm:text-base md:text-lg">View and review all your quiz attempts</p>
                    </div>
                    <div class="mt-0 md:mt-0">
                        <button @click="goToBuilder"
                            class="min-h-[44px] w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-600 shadow-lg hover:shadow-xl transition-all">
                            + New Quiz
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center min-h-[60vh]">
            <div class="text-center max-w-md mx-auto px-4">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="mt-4 text-lg font-semibold text-gray-800">Loading attempts...</p>
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error">
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                    <p class="text-red-800 mb-4">{{ error }}</p>
                    <button @click="fetchAttempts"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Try Again
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="attempts.length === 0">
            <div class="bg-white rounded-xl shadow-md p-12 text-center border border-gray-100">
                <div
                    class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">No Attempts</h2>
                <p class="text-gray-600 mb-6">You haven't completed any quizzes yet.</p>
                <button @click="goToBuilder"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 shadow-lg hover:shadow-xl transition-all">
                    Start New Quiz
                </button>
            </div>
        </div>

        <!-- Attempts List -->
        <div v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 md:gap-6">
                <div v-for="attempt in paginatedAttempts" :key="attempt.id"
                    class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100 hover:shadow-lg transition-all cursor-pointer sm:hover:scale-105"
                    @click="viewAttempt(attempt.id)">

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-2 sm:gap-3 mb-4">
                        <div class="text-center">
                            <div class="text-base sm:text-lg font-bold text-green-600">{{ attempt.correct_answers }}</div>
                            <div class="text-xs text-gray-600">Correct</div>
                        </div>
                        <div class="text-center">
                            <div class="text-base sm:text-lg font-bold text-red-600">{{ attempt.incorrect_answers }}</div>
                            <div class="text-xs text-gray-600">Incorrect</div>
                        </div>
                        <div class="text-center">
                            <div class="text-base sm:text-lg font-bold text-amber-700">{{ attempt.exp_gained }}</div>
                            <div class="text-xs text-gray-600">EXP Gained</div>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4 border-t border-gray-200 pt-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    getScoreClass(attempt.score_percentage)
                                ]">
                                    {{ Math.round(attempt.score_percentage) }}%
                                </span>
                                <span v-if="attempt.question_format"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gradient-to-r from-blue-100 to-purple-100 text-blue-800">
                                    {{ attempt.question_format === 'multiple_choice' ? 'Multiple Choice' : 'Subjective'
                                    }}
                                </span>
                            </div>
                            <p v-if="attempt.completed_at" class="text-xs text-gray-500">
                                {{ formatDate(attempt.completed_at) }}
                            </p>
                        </div>
                    </div>



                    <!-- Additional Info -->
                    <div class="space-y-1 text-xs text-gray-600">
                        <div v-if="attempt.mastery_level" class="flex items-center gap-1">
                            <span class="font-medium">Level:</span>
                            <span>{{ attempt.mastery_level }}</span>
                        </div>
                        <div v-if="attempt.learning_style" class="flex items-center gap-1">
                            <span class="font-medium">Style:</span>
                            <span>{{ attempt.learning_style }}</span>
                        </div>
                        <div v-if="attempt.time_spent_seconds" class="flex items-center gap-1">
                            <span class="font-medium">Time:</span>
                            <span>{{ formatTime(attempt.time_spent_seconds) }}</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button @click.stop="viewAttempt(attempt.id)"
                        class="min-h-[44px] mt-4 w-full px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 shadow-md hover:shadow-lg transition-all text-sm">
                        View Review
                    </button>
                </div>
            </div>

            <!-- Pagination Controls -->
            <div v-if="totalPages > 1"
                class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 flex items-center gap-1 sm:gap-2 bg-white/90 backdrop-blur-sm rounded-xl shadow-lg px-2 sm:px-4 py-2 border border-gray-200">
                <!-- Previous Button -->
                <button @click="prevPage" :disabled="currentPage === 1" 
                    class="min-h-[44px] min-w-[44px]"
                    :class="[
                        'p-2 rounded-lg transition-all',
                        currentPage === 1
                            ? 'text-gray-300 cursor-not-allowed'
                            : 'text-purple-600 hover:text-purple-700 hover:bg-purple-50'
                    ]">
                    <ChevronLeft class="w-5 h-5" />
                </button>

                <!-- Page Numbers -->
                <div class="flex items-center gap-1">
                    <button v-for="page in getPageNumbers()" :key="page" @click="goToPage(page)" 
                        class="min-h-[44px] min-w-[44px]"
                        :class="[
                            'px-2 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium transition-all',
                            currentPage === page
                                ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md'
                                : 'text-gray-600 hover:text-purple-600 hover:bg-purple-50'
                        ]">
                        {{ page }}
                    </button>
                </div>

                <!-- Next Button -->
                <button @click="nextPage" :disabled="currentPage === totalPages" 
                    class="min-h-[44px] min-w-[44px]"
                    :class="[
                        'p-2 rounded-lg transition-all',
                        currentPage === totalPages
                            ? 'text-gray-300 cursor-not-allowed'
                            : 'text-purple-600 hover:text-purple-700 hover:bg-purple-50'
                    ]">
                    <ChevronRight class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</template>
