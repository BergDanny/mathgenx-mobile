import { api } from "@/services/api";

export const getOnboardingStatus = async () => {
    try {
        const res = await api.get("onboarding/status").json();
        return { ...res, _status: 200 };
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        const status = err?.response?.status ?? null;
        return (
            res || {
                success: false,
                message: "Failed to fetch onboarding status.",
                errors: {},
                _status: status,
            }
        );
    }
};

export const completeOnboarding = async (type) => {
    try {
        const res = await api.post("onboarding/complete", { json: { type } }).json();
        return { ...res, _status: 200 };
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        const status = err?.response?.status ?? null;
        return (
            res || {
                success: false,
                message: "Failed to complete onboarding.",
                errors: {},
                _status: status,
            }
        );
    }
};
