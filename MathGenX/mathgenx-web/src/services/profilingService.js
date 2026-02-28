import { api } from "@/services/api";

const handleError = (err) => {
    const message =
        err?.response?.data?.message ||
        err?.response?.data?.error ||
        err?.message ||
        "An unexpected error occurred.";
    return { success: false, message };
};

export const getVarkQuestions = async () => {
    try {
        const res = await api.get("profiling/vark/questions").json();
        return res;
    }
    catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Failed to fetch VARK questions.",
                errors: {}
            }
        );
    }
};

export const getMathQuestions = async () => {
    try {
        const res = await api.get("profiling/math/questions").json();
        return res;
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Failed to fetch Math questions.",
                errors: {}
            }
        );
    }
};

export async function completeProfiling(responses = []) {
    try {
        const res = await api.post("profiling/complete", {
            json: { responses }
        }).json();
        return {
            success: res.success ?? true,
            data: res.data ?? res,
            message: res.message ?? null,
        };
    } catch (err) {
        return handleError(err);
    }
};

export async function submitAllProfiling(payload) {
    try {
        const res = await api.post("profiling/submit", {
            json: payload
        }).json();
        return {
            success: res.success ?? true,
            data: res.data ?? res,
            message: res.message ?? null,
        };
    } catch (err) {
        const message =
            err?.response?.data?.message ||
            err?.response?.data?.error ||
            err?.message ||
            "An unexpected error occurred.";
        return { success: false, message };
    }
}

export const getUserProfilingResponses = async () => {
    try {
        const res = await api.get("profiling/responses").json();
        return res;
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Failed to fetch profiling responses.",
                data: null
            }
        );
    }
};

