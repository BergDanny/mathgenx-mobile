import { defineStore } from "pinia";
import { getMathQuestions, getVarkQuestions, submitAllProfiling } from "@/services/profilingService";
import { useAuthStore } from "@/stores/authStore";

export const useProfilingStore = defineStore("profiling", {
    state: () => ({
        varkQuestions: [],
        mathQuestions: [],
        varkResponses: {},   // { question_id: answer_id }
        mathResponses: {},
        profileData: {},
        loading: false,
        error: null,
    }),

    persist: {
        storage: localStorage,
    },

    actions: {
        async fetchVarkQuestions() {
            this.loading = true;
            this.error = null;
            const res = await getVarkQuestions();
            if (res.success) {
                this.varkQuestions = res.data || [];
                return true;
            } else {
                this.loading = false;
                this.error = res.message || "Failed to load VARK questions.";
                return false;
            }
        },

        async fetchMathQuestions() {
            this.loading = true;
            this.error = null;
            const res = await getMathQuestions();
            if (res.success) {
                this.mathQuestions = res.data || [];
                return true;
            } else {
                this.loading = false;
                this.error = res.message || "Failed to load Math questions.";
                return false;
            }
        },

        saveVarkResponse(questionId, answerId) {
            this.varkResponses[questionId] = answerId;
            this.persistState();
        },

        saveMathResponse(questionId, answerId) {
            this.mathResponses[questionId] = answerId;
            this.persistState();
        },

        saveProfileData(data) {
            this.profileData = { ...this.profileData, ...data };
            this.persistState();
        },

        persistState() {
            // store in localStorage (manual persistence)
            localStorage.setItem(
                "profilingData",
                JSON.stringify({
                    varkResponses: this.varkResponses,
                    mathResponses: this.mathResponses,
                    profileData: this.profileData,
                })
            );
        },

        restoreState() {
            const saved = localStorage.getItem("profilingData");
            if (saved) {
                const parsed = JSON.parse(saved);
                this.varkResponses = parsed.varkResponses || {};
                this.mathResponses = parsed.mathResponses || {};
                this.profileData = parsed.profileData || {};
            }
        },

        clearState() {
            this.varkResponses = {};
            this.mathResponses = {};
            this.profileData = {};
            localStorage.removeItem("profilingData");
        },

        async submitAllProfiling() {
            this.loading = true;
            this.error = null;

            try {
                const payload = {
                    vark: Object.entries(this.varkResponses).map(([q, a]) => ({
                        question_id: parseInt(q, 10),
                        answer_id: a,
                    })),
                    math: Object.entries(this.mathResponses).map(([q, a]) => ({
                        question_id: parseInt(q, 10),
                        answer_id: a,
                    })),
                    profile: this.profileData,
                };

                const res = await submitAllProfiling(payload);

                if (res && res.success) {
                    this.clearState(); // remove local copy once uploaded
                    const authStore = useAuthStore();
                    await authStore.fetchProfile();
                    return { ok: true, data: res.data };
                } else {
                    this.error = res?.message || "Failed to submit profiling.";
                    return { ok: false, message: this.error };
                }
            } catch (err) {
                this.error = err?.message || "Unexpected error during submission.";
                return { ok: false, message: this.error };
            } finally {
                this.loading = false;
            }
        },
    },
});