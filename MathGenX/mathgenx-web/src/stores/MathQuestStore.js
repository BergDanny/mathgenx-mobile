import { defineStore } from "pinia";
import { generateQuestions } from "@/services/MathQuestService";

const STORAGE_KEY = 'mathquest_quiz_state';

// Helper function to restore from localStorage (used during store initialization)
function restoreFromStorage() {
    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            const parsed = JSON.parse(saved);
            // Validate that we have questions
            if (parsed.questions && Array.isArray(parsed.questions) && parsed.questions.length > 0) {
                console.log('Restored quiz state from localStorage on initialization');
                return parsed;
            }
        }
    } catch (err) {
        console.error('Failed to restore quiz state from localStorage:', err);
    }
    return null;
}

export const useMathQuestStore = defineStore("mathquest", {
    state: () => {
        // Try to restore from localStorage on initialization
        const savedState = restoreFromStorage();
        
        return {
            questions: savedState?.questions || [],
            currentParams: savedState?.currentParams || {
                topic: null,
                subtopic: null,
                questionFormat: null,
                language: null
            },
            loading: false,
            error: null,
            responses: savedState?.responses || {},
            currentQuestionIndex: savedState?.currentQuestionIndex || 0
        };
    },

    getters: {
        hasQuestions: (state) => state.questions.length > 0,

        currentQuestion: (state) => state.questions[state.currentQuestionIndex] || null,

        progress: (state) => {
            if (state.questions.length === 0) return 0;
            return ((state.currentQuestionIndex + 1) / state.questions.length) * 100;
        },

        answeredCount: (state) => Object.keys(state.responses).length,

        isLastQuestion: (state) => state.currentQuestionIndex === state.questions.length - 1,

        canSubmit: (state) => Object.keys(state.responses).length === state.questions.length,

        // Check if current params match cached questions
        isSameParams: (state) => (topic, subtopic, questionFormat, language) => {
            return state.currentParams.topic === topic &&
                state.currentParams.subtopic === subtopic &&
                state.currentParams.questionFormat === questionFormat &&
                state.currentParams.language === language;
        }
    },

    actions: {
        // Save state to localStorage
        saveToStorage() {
            try {
                const stateToSave = {
                    questions: this.questions,
                    currentParams: this.currentParams,
                    responses: this.responses,
                    currentQuestionIndex: this.currentQuestionIndex,
                    savedAt: new Date().toISOString()
                };
                localStorage.setItem(STORAGE_KEY, JSON.stringify(stateToSave));
            } catch (err) {
                console.error('Failed to save quiz state to localStorage:', err);
            }
        },

        // Restore state from localStorage
        restoreFromStorage() {
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                if (saved) {
                    const parsed = JSON.parse(saved);
                    // Validate that we have questions
                    if (parsed.questions && Array.isArray(parsed.questions) && parsed.questions.length > 0) {
                        console.log('Restored quiz state from localStorage');
                        return parsed;
                    }
                }
            } catch (err) {
                console.error('Failed to restore quiz state from localStorage:', err);
            }
            return null;
        },

        async fetchQuestions(topic, subtopic, questionFormat, language, forceRefresh = false) {
            // First, check if we have saved state in localStorage (for page reloads)
            if (!forceRefresh) {
                const savedState = this.restoreFromStorage();
                if (savedState && savedState.questions.length > 0) {
                    // Check if saved params match current request
                    if (savedState.currentParams.topic === topic &&
                        savedState.currentParams.subtopic === subtopic &&
                        savedState.currentParams.questionFormat === questionFormat &&
                        savedState.currentParams.language === language) {
                        console.log("Restoring questions from localStorage after page reload");
                        this.questions = savedState.questions;
                        this.currentParams = savedState.currentParams;
                        this.responses = savedState.responses || {};
                        this.currentQuestionIndex = savedState.currentQuestionIndex || 0;
                        return { success: true, cached: true, restored: true };
                    }
                }
            }

            // Check if we already have questions in memory for these params
            if (!forceRefresh && this.hasQuestions && this.isSameParams(topic, subtopic, questionFormat, language)) {
                console.log("Using cached questions from memory");
                return { success: true, cached: true };
            }

            try {
                this.loading = true;
                this.error = null;

                const response = await generateQuestions(topic, subtopic, questionFormat, language);

                if (response.status === 'success' && response.data) {
                    this.questions = response.data.questions || [];
                    this.currentParams = { topic, subtopic, questionFormat, language };
                    // Reset responses and index when fetching new questions (not restoring)
                    this.responses = {};
                    this.currentQuestionIndex = 0;
                    // Save to localStorage
                    this.saveToStorage();
                    return { success: true, cached: false };
                } else {
                    this.error = response.message || "Failed to generate questions";
                    return { success: false, message: this.error };
                }
            } catch (err) {
                this.error = err.message || "API connection failed";
                return { success: false, message: this.error };
            } finally {
                this.loading = false;
            }
        },

        selectAnswer(questionId, answerId) {
            this.responses[questionId] = answerId;
            // Auto-save to localStorage when answer is selected
            this.saveToStorage();
        },

        goToQuestion(index) {
            if (index >= 0 && index < this.questions.length) {
                this.currentQuestionIndex = index;
                // Auto-save to localStorage when navigating
                this.saveToStorage();
            }
        },

        nextQuestion() {
            if (!this.isLastQuestion) {
                this.currentQuestionIndex++;
                // Auto-save to localStorage when navigating
                this.saveToStorage();
            }
        },

        previousQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
                // Auto-save to localStorage when navigating
                this.saveToStorage();
            }
        },

        clearQuestions() {
            this.questions = [];
            this.currentParams = { topic: null, subtopic: null, questionFormat: null, language: null };
            this.responses = {};
            this.currentQuestionIndex = 0;
            this.error = null;
            // Clear localStorage when quiz is submitted/completed
            try {
                localStorage.removeItem(STORAGE_KEY);
                console.log('Cleared quiz state from localStorage');
            } catch (err) {
                console.error('Failed to clear quiz state from localStorage:', err);
            }
        },

        reset() {
            this.responses = {};
            this.currentQuestionIndex = 0;
            // Save reset state to localStorage
            this.saveToStorage();
        }
    }
});

