import { api } from "@/services/api";

export const getPracticeQuestions = async (topic, subtopic, questionFormat, language, tp, difficulty) => {
    try {
        const res = await api.get("mathpractice/questions", {
            searchParams: {
                topic: topic,
                subtopic: subtopic || undefined,
                question_format: questionFormat,
                language: language || 'english',
                tp: tp,
                difficulty: difficulty
            }
        }).json();
        return res;
    }
    catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                status: 'error',
                message: "Failed to fetch practice questions.",
                errors: {}
            }
        );
    }
};

export const sendChatbotMessage = async (practiceSessionId, questionId, userPrompt) => {
    try {
        const res = await api.post("mathpractice/chatbot/message", {
            json: {
                practice_session_id: practiceSessionId,
                question_id: questionId,
                user_prompt: userPrompt
            }
        }).json();
        return res;
    }
    catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Failed to send message to chatbot.",
                errors: {}
            }
        );
    }
};

export const getChatHistory = async (practiceSessionId, questionId) => {
    try {
        const res = await api.get("mathpractice/chatbot/history", {
            searchParams: {
                practice_session_id: practiceSessionId,
                question_id: questionId
            }
        }).json();
        return res;
    }
    catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Failed to fetch chat history.",
                errors: {}
            }
        );
    }
};

