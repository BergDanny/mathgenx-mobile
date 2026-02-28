import { api } from "@/services/api";

export const generateQuestions = async (topic, subtopic, questionFormat, language) => {
    try {
        const res = await api.get("mathquest/quiz", {
            searchParams: {
                topic: topic,
                subtopic: subtopic,
                question_format: questionFormat,
                language: language
            }
        }).json();
        return res;
    }
    catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Failed to generate questions.",
                errors: {}
            }
        );
    }
};

export const submitQuizAttempt = async (attemptData) => {
    try {
        const res = await api.post("mathquest/attempts", {
            json: attemptData
        }).json();
        return res;
    }
    catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                status: 'error',
                message: "Failed to submit quiz attempt.",
                errors: {}
            }
        );
    }
};

export const getQuizAttempts = async () => {
    try {
        const res = await api.get("mathquest/attempts").json();
        return res;
    }
    catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                status: 'error',
                message: "Failed to fetch quiz attempts.",
                data: []
            }
        );
    }
};

export const getQuizAttemptDetails = async (attemptId) => {
    try {
        const res = await api.get(`mathquest/attempts/${attemptId}`).json();
        return res;
    }
    catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                status: 'error',
                message: "Failed to fetch quiz attempt details.",
                data: null
            }
        );
    }
};