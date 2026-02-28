<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import AuthLayout from "@/views/Layouts/AuthLayout.vue";
import MainLayout from "@/views/Layouts/MainLayout.vue";
import OnBoardingLayout from "@/views/Layouts/OnBoardingLayout.vue";
import LandingLayout from "@/views/Layouts/LandingLayout.vue";
import { useAuthStore } from "@/stores/authStore";

const route = useRoute();
const user = ref(null);
const isLoading = ref(true);

// fetch user profile on app mount when token exists so store is hydrated after refresh
const authStore = useAuthStore();
onMounted(async () => {
  if (authStore.token) {
    await authStore.fetchProfile();
  }
  isLoading.value = false;
});

// Decide which layout to use based on route meta
const layout = computed(() => {
  if (route.meta.layout === "auth") return AuthLayout;
  if (route.meta.layout === "onboarding") return OnBoardingLayout;
  if (route.meta.layout === "landing") return LandingLayout;
  return MainLayout;
});

</script>

<template>
  <component :is="layout">
    <router-view />
  </component>
</template>

