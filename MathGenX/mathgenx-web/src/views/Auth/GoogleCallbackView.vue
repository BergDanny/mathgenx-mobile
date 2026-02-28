<script setup>
import { onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

onMounted(async () => {
  const token = route.query.token;
  const error = route.query.error;

  if (error) {
    // Handle error
    authStore.errors = { message: error };
    router.push("/login");
    return;
  }

  if (token) {
    // Set token and fetch profile
    authStore.token = token;
    localStorage.setItem("token", token);

    try {
      // Fetch profile to get user data and learning results
      const profileRes = await authStore.fetchProfile();
      
      if (profileRes.success) {
        // Check if profiling is incomplete
        const hasIncompleteProfiling = !authStore.learning_results || 
                                       authStore.learning_results.vark === null || 
                                       authStore.learning_results.math === null;
        
        if (hasIncompleteProfiling) {
          router.push("/profiling");
        } else {
          router.push("/dashboard");
        }
      } else {
        // If profile fetch fails, still try to proceed (token might be valid)
        router.push("/dashboard");
      }
    } catch (error) {
      console.error("Failed to fetch profile:", error);
      // Even if profile fetch fails, try to go to dashboard
      router.push("/dashboard");
    }
  } else {
    router.push("/login");
  }
});
</script>

<template>
  <div class="flex items-center justify-center min-h-screen">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">Completing sign in...</p>
    </div>
  </div>
</template>

