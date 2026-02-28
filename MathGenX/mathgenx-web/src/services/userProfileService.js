import { api } from "@/services/api";

export const updateProfile = async (payload) => {
    try {
        const res = await api.put("profile/update", { json: payload }).json();
        return { ...res, _status: 200 };
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        const status = err?.response?.status ?? null;
        return (
            res || {
                success: res.success ?? false,
                message: "Failed to update profile.",
                errors: {},
                _status: status,
            }
        );
    }
};