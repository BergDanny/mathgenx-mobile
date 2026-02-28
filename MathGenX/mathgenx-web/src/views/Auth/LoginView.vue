<script setup>
import { ref, onMounted } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { useRouter } from "vue-router";
import { storeToRefs } from "pinia"; // Import storeToRefs
import { LucideChevronRight } from "lucide-vue-next";

const email = ref("");
const password = ref("");
const router = useRouter();
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const authStore = useAuthStore(); // Get the store instance

onMounted(() => {
  authStore.clearErrors();
});

const { loginUser, loginWithGoogle } = authStore;
// Make store state reactive in your component
const { loading, errors: validationErrors, learning_results } = storeToRefs(authStore);
const handleLogin = async () => {
  const res = await loginUser({ email: email.value, password: password.value });
  if (res) {
    console.log("Login successful:");
    // Check if learning_results is null or incomplete
    const hasIncompleteProfiling = !learning_results.value || 
                                   learning_results.value.vark === null || 
                                   learning_results.value.math === null;
    
    if (hasIncompleteProfiling) {
      router.push("/profiling");
    } else {
      router.push("/dashboard");
    }
  }
};

const handleGoogleLogin = () => {
  loginWithGoogle();
};
</script>

<template>
  <div class="grow px-6 md:px-8 bg-white">
    <div class="h-full min-h-[75vh] md:min-h-screen w-full max-w-md sm:w-112 flex flex-col justify-center mx-auto space-y-6 md:space-y-5 py-4 md:py-8">
      <!-- Title - Mobile optimized -->
      <div class="text-center md:text-left">
        <h1 class="text-3xl md:text-3xl font-bold text-gray-900 mb-2 mt-2">
          Welcome Back
        </h1>
        <p class="text-base md:text-base text-gray-600 leading-relaxed">
          Ready to continue your learning journey?<br class="md:hidden" />
          <span class="md:hidden">Your path is right here. <br/> </span>
          <span class="hidden md:inline"> MathGen<span class="text-xl align-baseline ml-0.5">α</span> is your AI-powered math problem generator.</span>
        </p>
      </div>
      <!-- End Title -->

      <!-- Button Group - Mobile optimized -->
      <div class="flex flex-col gap-3 md:flex-row md:gap-2">
        <button type="button" @click="handleGoogleLogin"
          class="py-3.5 md:py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-3 md:gap-x-2 text-base md:text-sm font-medium rounded-xl md:rounded-lg border-2 border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 hover:shadow-md transition-all disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-gray-50">
          <svg class="shrink-0 size-4" width="33" height="32" viewBox="0 0 33 32" fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_4132_5805)">
              <path
                d="M32.2566 16.36C32.2566 15.04 32.1567 14.08 31.9171 13.08H16.9166V19.02H25.7251C25.5454 20.5 24.5866 22.72 22.4494 24.22L22.4294 24.42L27.1633 28.1L27.4828 28.14C30.5189 25.34 32.2566 21.22 32.2566 16.36Z"
                fill="#4285F4" />
              <path
                d="M16.9166 32C21.231 32 24.8463 30.58 27.5028 28.12L22.4694 24.2C21.1111 25.14 19.3135 25.8 16.9366 25.8C12.7021 25.8 9.12677 23 7.84844 19.16L7.66867 19.18L2.71513 23L2.65521 23.18C5.2718 28.4 10.6648 32 16.9166 32Z"
                fill="#34A853" />
              <path
                d="M7.82845 19.16C7.48889 18.16 7.28915 17.1 7.28915 16C7.28915 14.9 7.48889 13.84 7.80848 12.84V12.62L2.81499 8.73999L2.6552 8.81999C1.55663 10.98 0.937439 13.42 0.937439 16C0.937439 18.58 1.55663 21.02 2.63522 23.18L7.82845 19.16Z"
                fill="#FBBC05" />
              <path
                d="M16.9166 6.18C19.9127 6.18 21.9501 7.48 23.0886 8.56L27.6027 4.16C24.8263 1.58 21.231 0 16.9166 0C10.6648 0 5.27181 3.6 2.63525 8.82L7.80851 12.84C9.10681 8.98 12.6821 6.18 16.9166 6.18Z"
                fill="#EB4335" />
            </g>
            <defs>
              <clipPath id="clip0_4132_5805">
                <rect width="32" height="32" fill="white" transform="translate(0.937439)" />
              </clipPath>
            </defs>
          </svg>
          Sign in with Google
        </button>
      </div>
      <!-- End Button Group -->

      <div
        class="flex items-center text-sm md:text-xs text-gray-400 uppercase font-medium before:flex-1 before:border-t before:border-gray-300 before:me-4 md:before:me-6 after:flex-1 after:border-t after:border-gray-300 after:ms-4 md:after:ms-6">
        Or</div>

      <form @submit.prevent="handleLogin">
        <div v-if="validationErrors">
          <div
            class="flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50"
            role="alert">
            <svg class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd"
                d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zm-8-4a1 1 0 00-1 1v2a1 1 0 002 0V7a1 1 0 00-1-1zm0 8a1 1 0 100-2 1 1 0 000 2z"
                clip-rule="evenodd" />
            </svg>
            <div>
              <div v-if="validationErrors.message">
                {{ validationErrors.message }}
              </div>
              <div v-if="validationErrors.email">
                {{ validationErrors.email[0] }}
              </div>
              <div v-if="validationErrors.password">
                {{ validationErrors.password[0] }}
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-5 md:space-y-5">
          <div>
            <label for="hs-pro-dale" class="block mb-2.5 md:mb-2 text-sm md:text-sm font-semibold text-gray-800">
              Email
            </label>

            <input v-model="email" type="email" id="hs-pro-dale"
              class="min-h-[44px] py-3.5 md:py-2.5 px-4 block w-full border-2 border-gray-200 rounded-xl md:rounded-lg text-base md:text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all disabled:opacity-50 disabled:pointer-events-none bg-white text-gray-900"
              placeholder="Enter email">
          </div>

          <div>
            <div class="flex justify-between items-center mb-2.5 md:mb-2">
              <label for="hs-pro-dalp" class="block text-sm md:text-sm font-semibold text-gray-800">
                Password
              </label>

              <!-- <a class="inline-flex items-center gap-x-1.5 text-sm md:text-xs text-blue-600 hover:text-blue-700 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium"
                href="#">
                Forgot password?
              </a> -->
            </div>

            <div class="relative">
              <input id="hs-pro-dalp" v-model="password" :type="showPassword ? 'text' : 'password'"
                class="min-h-[44px] py-3.5 md:py-2.5 px-4 pr-12 block w-full border-2 border-gray-200 rounded-xl md:rounded-lg text-base md:text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all disabled:opacity-50 disabled:pointer-events-none bg-white text-gray-900"
                placeholder="Password">

              <button type="button" @click="showPassword = !showPassword"
                class="min-h-[44px] absolute inset-y-0 end-0 flex items-center z-20 px-4 cursor-pointer text-gray-400 hover:text-gray-600 rounded-e-xl md:rounded-e-lg focus:outline-hidden focus:text-blue-600 transition-colors">

                <svg v-if="!showPassword" class="shrink-0 size-4" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                  <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                  <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                  <line x1="2" x2="22" y1="2" y2="22" />
                </svg>
                <svg v-else class="shrink-0 size-4" width="24" height="24" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
          </div>

          <button type="submit"
            class="min-h-[44px] py-4 md:py-2.5 px-4 w-full inline-flex justify-center items-center gap-x-2 text-base md:text-sm font-bold md:font-semibold rounded-xl md:rounded-lg border border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:pointer-events-none"
            :disabled="loading">
            {{ loading ? "Loading..." : "Log In" }}
          </button>
        </div>
      </form>


      <p class="text-center md:text-left text-sm md:text-sm text-gray-600">
        Don't have an account?
        <router-link
          class="inline-flex items-center gap-x-1 text-sm md:text-sm text-blue-600 decoration-2 hover:underline font-semibold focus:outline-hidden focus:underline hover:text-blue-700"
          to="/register">
          Sign Up
          <LucideChevronRight class="w-4 h-4" />
        </router-link>
      </p>
    </div>
  </div>
</template>
