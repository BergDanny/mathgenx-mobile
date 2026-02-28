import { defineStore } from "pinia";
import { login, register, getProfile, redirectToGoogle } from "@/services/authService";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    roles: null,
    learning_results: null,
    exp_info: null,
    onboarding_flags: null,
    token: localStorage.getItem("token") || null,
    loading: false,
    errors: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
  },

  actions: {
    async registerUser(payload) {
      this.loading = true;
      this.errors = null;
      const res = await register(payload);

      if (res.success && res.data?.token) {
        this.token = res.data.token;
        localStorage.setItem("token", res.data.token);
        this.user = res.data.user;
        
        // Fetch full profile after registration to get roles and learning_results
        try {
          await this.fetchProfile();
        } catch (error) {
          console.error("Failed to fetch profile after registration:", error);
          // Continue even if profile fetch fails
        }
        
        this.loading = false;
        return true;
      } else {
        this.errors = res.errors || { message: res.message };
        this.loading = false;
        return false;
      }
    },

    async loginUser(payload) {
      this.loading = true;
      this.errors = null;
      const res = await login(payload);

      if (res.success && res.data?.token) {
        this.token = res.data.token;
        localStorage.setItem("token", res.data.token);
        this.user = res.data.user;
        
        // Fetch full profile after login to get roles and learning_results
        try {
          await this.fetchProfile();
        } catch (error) {
          console.error("Failed to fetch profile after login:", error);
          // Continue even if profile fetch fails
        }
        
        this.loading = false;
        return true;
      } else {
        this.errors = res.errors || { message: res.message };
        this.loading = false;
        return false;
      }
    },

    async fetchProfile() {
      this.loading = true;
      try {
        const res = await getProfile();
        if (res.success) {
          this.user = res.data.user;
          this.roles = res.data.roles[0];
          this.learning_results = res.data.learning_results;
          this.exp_info = res.data.exp_info || null;
          this.onboarding_flags = res.data.onboarding_flags || null;
        }
        return res;
      } finally {
        this.loading = false;
      }
    },

    updateOnboardingFlags(flags) {
      this.onboarding_flags = flags;
    },

    clearErrors() {
      this.errors = null;
    },

    loginWithGoogle() {
      // Redirect to Google OAuth
      redirectToGoogle();
    },

    logout() {
      this.user = null;
      this.roles = null;
      this.learning_results = null;
      this.exp_info = null;
      this.onboarding_flags = null;
      this.token = null;
      this.errors = null;
      localStorage.removeItem("user");
      localStorage.removeItem("token");
    },
  },
});
