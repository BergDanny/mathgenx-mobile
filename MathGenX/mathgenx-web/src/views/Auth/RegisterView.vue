<script setup>
import { ref, onMounted } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import Alert from "@/components/Alerts/Alert.vue";
import { LucideChevronRight } from "lucide-vue-next";

const name = ref("");
const email = ref("");
const password = ref("");
const password_confirmation = ref("");
const router = useRouter();
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const authStore = useAuthStore();

onMounted(() => {
  authStore.clearErrors();
});

const { registerUser } = authStore;
const { loading, errors: validationErrors } = storeToRefs(authStore);

// State for controlling the alert modal
const showAlert = ref(false);
const alertDetails = ref({
  title: '',
  message: '',
  type: 'success', 
});

const handleRegister = async () => {
  const res = await registerUser({
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: password_confirmation.value,
    role: 'learner',
  });

  if (res) {
    console.log("Registration successful:");
    router.push("/profiling");
  }
};


</script>

<template>
  <Alert :show="showAlert" :title="alertDetails.title" :message="alertDetails.message" :type="alertDetails.type"
    @close="showAlert = false" />

  <div class="grow px-6 md:px-8 bg-white">
    <div class="h-full min-h-[75vh] md:min-h-screen w-full max-w-md sm:w-112 flex flex-col justify-center mx-auto space-y-6 md:space-y-5 py-4 md:py-8">
      <!-- Title - Mobile optimized -->
      <div class="text-center md:text-left">
        <h1 class="text-3xl md:text-3xl font-bold text-gray-900 mb-2 mt-2">
          Create Account
        </h1>
        <p class="text-base md:text-base text-gray-600 leading-relaxed">
          Join us to start generating math problems and advance your learning.
        </p>
      </div>
      <!-- End Title -->

      <form @submit.prevent="handleRegister">
        <!-- ERROR MESSAGE BLOCK -->
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
              <div v-if="validationErrors.name">
                {{ validationErrors.name[0] }}
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
        <!-- END ERROR MESSAGE BLOCK -->
        <div class="space-y-5 md:space-y-5">
          <div>
            <label for="hs-pro-daln" class="block mb-2.5 md:mb-2 text-sm md:text-sm font-semibold text-gray-800">
              Name
            </label>

            <input v-model="name" type="text" id="hs-pro-daln"
              class="min-h-[44px] py-3.5 md:py-2.5 px-4 block w-full border-2 border-gray-200 rounded-xl md:rounded-lg text-base md:text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all disabled:opacity-50 disabled:pointer-events-none bg-white text-gray-900"
              placeholder="John Doe">
          </div>

          <div>
            <label for="hs-pro-dale" class="block mb-2.5 md:mb-2 text-sm md:text-sm font-semibold text-gray-800">
              Email
            </label>

            <input v-model="email" type="email" id="hs-pro-dale"
              class="min-h-[44px] py-3.5 md:py-2.5 px-4 block w-full border-2 border-gray-200 rounded-xl md:rounded-lg text-base md:text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all disabled:opacity-50 disabled:pointer-events-none bg-white text-gray-900"
              placeholder="Enter email">
          </div>

          <div>
            <div>
              <div class="flex justify-between items-center mb-2.5 md:mb-2">
                <label for="hs-pro-dalp" class="block text-sm md:text-sm font-semibold text-gray-800">
                  Password
                </label>
              </div>

              <div class="relative">
                <input id="hs-pro-dalp" v-model="password" :type="showPassword ? 'text' : 'password'"
                  class="min-h-[44px] py-3.5 md:py-2.5 px-4 pr-12 block w-full border-2 border-gray-200 rounded-xl md:rounded-lg text-base md:text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all disabled:opacity-50 disabled:pointer-events-none bg-white text-gray-900"
                  placeholder="Password">

                <button type="button" @click="showPassword = !showPassword"
                  class="min-h-[44px] absolute inset-y-0 end-0 flex items-center z-20 px-4 cursor-pointer text-gray-400 hover:text-gray-600 rounded-e-xl md:rounded-e-lg focus:outline-hidden focus:text-blue-600 transition-colors">

                  <svg v-if="!showPassword" class="shrink-0 size-4" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
          </div>

          <div>
            <div class="flex justify-between items-center mb-2.5 md:mb-2">
              <label for="hs-pro-dalcp" class="block text-sm md:text-sm font-semibold text-gray-800">
                Confirm Password
              </label>
            </div>

            <div class="relative">
              <input id="hs-pro-dalcp" v-model="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'"
                class="min-h-[44px] py-3.5 md:py-2.5 px-4 pr-12 block w-full border-2 border-gray-200 rounded-xl md:rounded-lg text-base md:text-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all disabled:opacity-50 disabled:pointer-events-none bg-white text-gray-900"
                placeholder="Confirm password">

              <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                class="min-h-[44px] absolute inset-y-0 end-0 flex items-center z-20 px-4 cursor-pointer text-gray-400 hover:text-gray-600 rounded-e-xl md:rounded-e-lg focus:outline-hidden focus:text-blue-600 transition-colors">

                <svg v-if="!showConfirmPassword" class="shrink-0 size-4" width="24" height="24" viewBox="0 0 24 24" fill="none"
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
            {{ loading ? "Loading..." : "Sign Up" }}
          </button>
        </div>
      </form>

      <p class="text-center md:text-left text-sm md:text-sm text-gray-600">
        Already have an account?
        <router-link
          class="inline-flex items-center gap-x-1 text-sm md:text-sm text-blue-600 decoration-2 hover:underline font-semibold focus:outline-hidden focus:underline hover:text-blue-700"
          to="/login">
          Sign In
          <LucideChevronRight class="w-4 h-4" />
        </router-link>
      </p>
    </div>
  </div>
</template>
