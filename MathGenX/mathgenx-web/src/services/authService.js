import { api } from "@/services/api";

export const register = async (payload) => {
    try {
        const res = await api.post("auth/register", { json: payload }).json();
        return res;
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Something went wrong.",
                errors: {}
            }
        );
    }
};

export const login = async (payload) => {
    try {
        const res = await api.post("auth/login", { json: payload }).json();
        return res;
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Something went wrong.",
                errors: {}
            }
        );
    }
};

export const getProfile = async () => {
    try {
        const res = await api.get("auth/profile").json();
        return { ...res, _status: 200 };
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        const status = err?.response?.status ?? null;
        return (
            res || {
                success: false,
                message: "Failed to fetch profile.",
                errors: {},
                _status: status,
            }
        );
    }
};

export const logout = async () => {
    try {
        const res = await api.post("auth/logout").json();
        localStorage.removeItem("token");
        return res;
    } catch (err) {
        const res = err?.response?.json ? await err.response.json() : null;
        return (
            res || {
                success: false,
                message: "Logout failed.",
                errors: {},
            }
        );
    }
};

export const redirectToGoogle = () => {
    // Get API base URL from environment
    const apiBase = import.meta.env.VITE_API_BASE || 'http://localhost:8000/api/v1';
    // Redirect to backend Google OAuth endpoint
    window.location.href = `${apiBase}/auth/google/redirect`;
};