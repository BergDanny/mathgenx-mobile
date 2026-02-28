<script setup lang="ts">
import { useRouter } from "vue-router";
import { LucideCalculator, LucideBrain, LucideTarget, LucideTrendingUp, LucideBookOpen, LucideChevronRight } from "lucide-vue-next";
import Iridescence from "@/components/ui/Backgrounds/Iridescence/Iridescence.vue";

const router = useRouter();

const handleGetStarted = () => {
  router.push("/register");
};

const handleLogin = () => {
  router.push("/login");
};

const handleRegister = () => {
  router.push("/register");
};
</script>

<template>
  <div class="min-h-screen bg-white text-gray-900" style="color-scheme: light;">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-200 text-gray-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Logo -->
          <div class="flex items-center gap-2">
            <img src="/icon/iconv3.png" alt="MathGenα" class="w-15 h-15" />
            <span class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
              MathGen<span class="text-3xl align-baseline ml-0.5">α</span>
            </span>
          </div>

          <!-- Navigation -->
          <nav class="hidden md:flex items-center space-x-8">
            <a href="#home" class="text-gray-700 hover:text-purple-600 transition-colors">Home</a>
            <a href="#features" class="text-gray-700 hover:text-purple-600 transition-colors">Features</a>
            <a href="#about" class="text-gray-700 hover:text-purple-600 transition-colors">About</a>
          </nav>

          <!-- Auth Buttons -->
          <div class="flex items-center space-x-4">
            <button @click="handleLogin"
              class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 hover:text-purple-600 transition-colors">
              Log In
            </button>
            <button @click="handleRegister"
              class="inline-flex items-center px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl">
              Sign Up
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="relative overflow-hidden pt-10 pb-24 sm:pt-14 sm:pb-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-2">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <!-- Left Side - Text Content -->
          <div class="text-center lg:text-left">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
              <span class="block">Investing in Knowledge and</span>
              <span class="block bg-gradient-to-r from-purple-600 to-orange-500 bg-clip-text text-transparent">
                Your Future
              </span>
            </h1>
            <p class="mt-6 text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto lg:mx-0">
              MathGen<span class="text-2xl align-baseline ml-0.5">α</span> is your AI-powered math problem generator
              designed to deliver personalized learning solutions and
              help you master mathematics.
            </p>

            <!-- CTA Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
              <button @click="handleGetStarted"
                class="inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                Get Started
                <LucideChevronRight class="ml-2 w-5 h-5" />
              </button>
              <button @click="handleLogin"
                class="inline-flex items-center justify-center px-8 py-3.5 text-base font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-purple-600 hover:text-purple-600 transition-all">
                Log In
              </button>
            </div>
          </div>

          <!-- Right Side - Visual Content -->
          <div class="relative h-[550px] lg:h-[650px]">
            <!-- Iridescence Background -->
            <div class="absolute inset-0 rounded-3xl overflow-hidden opacity-30">
              <Iridescence :color="[1, 1, 1]" :speed="0.8" :amplitude="0.15" :mouseReact="true" />
            </div>

            <div class="relative z-10 h-full flex items-center justify-center">
              <!-- Main Visual Card -->
              <div
                class="bg-white/90 backdrop-blur-sm rounded-3xl p-10 lg:p-12 shadow-2xl border border-white/20 w-full max-w-md lg:max-w-lg">
                <div class="bg-white rounded-2xl p-8 lg:p-10 shadow-lg">
                  <!-- Math Problem Example -->
                  <div class="space-y-4">
                    <div class="flex items-center justify-between">
                      <h3 class="text-lg font-semibold text-gray-800">Sample Problem</h3>
                      <LucideCalculator class="w-6 h-6 text-purple-600" />
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                      <p class="text-gray-700 font-medium mb-2">Solve:</p>
                      <p class="text-2xl font-bold text-gray-900">(-1/2) x (3/4)</p>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                      <LucideBrain class="w-4 h-4 text-purple-600" />
                      <span>AI-Generated Problem</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Floating Cards -->
              <div class="absolute -top-4 -left-4 bg-white rounded-xl p-4 shadow-xl z-20">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 bg-gradient-to-br from-green-400 to-blue-400 rounded-lg flex items-center justify-center">
                    <LucideTarget class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <div class="text-xl font-bold text-gray-900">TP1-TP6</div>
                    <div class="text-xs text-gray-600">Mastery Levels</div>
                  </div>
                </div>
              </div>
              <div class="absolute -bottom-4 -right-4 bg-white rounded-xl p-4 shadow-xl z-20">
                <div class="flex items-center gap-3">
                  <div
                    class="w-10 h-10 bg-gradient-to-br from-orange-500 to-pink-500 rounded-lg flex items-center justify-center">
                    <LucideTrendingUp class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <div class="text-lg font-bold text-gray-900">Learning</div>
                    <div class="text-xs text-gray-600">Progress Chart</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DSKP Curriculum Section -->
    <section class="py-16 bg-gradient-to-b from-white to-gray-50">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-200">
          <div class="flex flex-col md:flex-row items-center gap-6">
            <div class="flex-shrink-0">
              <img src="/logos/KPM.png" alt="Kementerian Pendidikan Malaysia" class="h-24 w-auto object-contain" />
            </div>
            <div class="flex-1 text-center md:text-left">
              <h3 class="text-2xl font-bold text-gray-900 mb-2">DSKP Curriculum Aligned</h3>
              <p class="text-gray-600">
                MathGen<span class="text-xl align-baseline ml-0.5">α</span> follows the Malaysian Ministry of
                Education's
                <strong>Dokumen Standard Kurikulum dan Pentaksiran (DSKP)</strong> framework, ensuring that all
                generated
                problems align with national educational standards and curriculum requirements.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-gradient-to-b from-white to-purple-50 relative overflow-hidden">
      <!-- Subtle Iridescence Background -->
      <div class="absolute inset-0 opacity-10 pointer-events-none">
        <Iridescence :color="[1, 1, 1]" :speed="0.5" :amplitude="0.08" :mouseReact="false" />
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-16">
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
            Browse Top Essential Features
          </h2>
          <p class="text-lg text-gray-600 max-w-2xl mx-auto">
            Discover the powerful tools that make MathGen<span class="text-2xl align-baseline ml-0.5">α</span> the
            perfect learning companion
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <!-- Feature Card 1 -->
          <div
            class="group relative bg-gradient-to-br from-pink-500 to-red-500 rounded-2xl p-6 text-white shadow-lg hover:shadow-2xl transition-all transform hover:-translate-y-2 cursor-pointer">
            <div class="mb-4">
              <LucideCalculator class="w-12 h-12" />
            </div>
            <h3 class="text-xl font-bold mb-2">AI Problem Generator</h3>
            <p class="text-sm opacity-90">Generate math problems tailored to your learning level</p>
          </div>

          <!-- Feature Card 2 -->
          <div
            class="group relative bg-gradient-to-br from-purple-500 to-indigo-500 rounded-2xl p-6 text-white shadow-lg hover:shadow-2xl transition-all transform hover:-translate-y-2 cursor-pointer">
            <div class="mb-4">
              <LucideBrain class="w-12 h-12" />
            </div>
            <h3 class="text-xl font-bold mb-2">Smart Learning</h3>
            <p class="text-sm opacity-90">Adaptive learning paths that adjust to your progress</p>
          </div>

          <!-- Feature Card 3 -->
          <div
            class="group relative bg-gradient-to-br from-blue-400 to-cyan-500 rounded-2xl p-6 text-white shadow-lg hover:shadow-2xl transition-all transform hover:-translate-y-2 cursor-pointer">
            <div class="mb-4">
              <LucideTarget class="w-12 h-12" />
            </div>
            <h3 class="text-xl font-bold mb-2">Progress Tracking</h3>
            <p class="text-sm opacity-90">Monitor your improvement with detailed analytics</p>
          </div>

          <!-- Feature Card 4 -->
          <div
            class="group relative bg-gradient-to-br from-orange-500 to-amber-500 rounded-2xl p-6 text-white shadow-lg hover:shadow-2xl transition-all transform hover:-translate-y-2 cursor-pointer">
            <div class="mb-4">
              <LucideBookOpen class="w-12 h-12" />
            </div>
            <h3 class="text-xl font-bold mb-2">Comprehensive Level</h3>
            <p class="text-sm opacity-90">Cover 1 math topics from basics to advanced levels TP1-TP6</p>
          </div>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-24 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
          <div>
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6">
              Why Choose MathGen<span class="text-4xl align-baseline ml-0.5">α</span>?
            </h2>
            <div class="space-y-4 text-gray-600">
              <p class="text-lg">
                MathGen<span class="text-2xl align-baseline ml-0.5">α</span> revolutionizes math learning by combining
                artificial intelligence with personalized education.
                Our platform generates custom math problems that adapt to your learning style and pace.
              </p>
              <p>
                Whether you're a student preparing for exams, a teacher creating assignments, or someone looking to
                improve their math skills, MathGen<span class="text-2xl align-baseline ml-0.5">α</span> provides the
                tools you need to succeed.
              </p>
              <ul class="space-y-2 mt-6">
                <li class="flex items-center gap-2">
                  <LucideChevronRight class="w-5 h-5 text-purple-600" />
                  <span>AI-powered problem generation</span>
                </li>
                <li class="flex items-center gap-2">
                  <LucideChevronRight class="w-5 h-5 text-purple-600" />
                  <span>Personalized learning paths</span>
                </li>
                <li class="flex items-center gap-2">
                  <LucideChevronRight class="w-5 h-5 text-purple-600" />
                  <span>Real-time progress tracking</span>
                </li>
                <li class="flex items-center gap-2">
                  <LucideChevronRight class="w-5 h-5 text-purple-600" />
                  <span>Comprehensive topic coverage</span>
                </li>
              </ul>
            </div>
            <div class="mt-8">
              <button @click="handleGetStarted"
                class="inline-flex items-center px-8 py-3.5 text-base font-semibold text-white bg-gradient-to-r from-purple-600 to-blue-600 rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl">
                Get Started Today
                <LucideChevronRight class="ml-2 w-5 h-5" />
              </button>
            </div>
          </div>
          <div class="relative">
            <div class="bg-gradient-to-br from-purple-100 to-blue-100 rounded-3xl p-8">
              <div class="space-y-6">
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                  <LucideBookOpen class="w-12 h-12 text-purple-600 mx-auto mb-3" />
                  <h4 class="text-lg font-semibold text-gray-900 mb-2">Curriculum-Based Learning</h4>
                  <p class="text-sm text-gray-600">Structured learning paths following DSKP standards</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-lg text-center">
                  <LucideBrain class="w-12 h-12 text-blue-600 mx-auto mb-3" />
                  <h4 class="text-lg font-semibold text-gray-900 mb-2">Personalized Experience</h4>
                  <p class="text-sm text-gray-600">Adaptive problems tailored to your learning style</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 relative overflow-hidden">
      <!-- Iridescence Background with overlay -->
      <div class="absolute inset-0">
        <Iridescence :color="[1, 1, 1]" :speed="1.2" :amplitude="0.12" :mouseReact="true" />
      </div>
      <!-- Gradient overlay for better text readability -->
      <div class="absolute inset-0 bg-gradient-to-r from-purple-600/80 to-blue-600/80"></div>
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
          Ready to Transform Your Math Learning?
        </h2>
        <p class="text-xl text-purple-100 mb-8">
          Start your personalized math learning journey with MathGen<span
            class="text-2xl align-baseline ml-0.5">α</span> today
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button @click="handleGetStarted"
            class="inline-flex items-center justify-center px-8 py-3.5 text-base font-semibold text-purple-600 bg-white rounded-lg hover:bg-gray-100 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
            Get Started Free
            <LucideChevronRight class="ml-2 w-5 h-5" />
          </button>
          <button @click="handleLogin"
            class="inline-flex items-center justify-center px-8 py-3.5 text-base font-medium text-white border-2 border-white rounded-lg hover:bg-white/10 transition-all">
            Log In
          </button>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div>
            <h3 class="text-xl font-bold text-white mb-4">MathGen<span class="text-2xl align-baseline ml-0.5">α</span>
            </h3>
            <p class="text-sm">
              Your AI-powered math learning companion
            </p>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4">Product</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
              <li><a href="#about" class="hover:text-white transition-colors">About</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4">Account</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="/login" class="hover:text-white transition-colors">Log In</a></li>
              <li><a href="/register" class="hover:text-white transition-colors">Sign Up</a></li>
            </ul>
          </div>
          <div>
            <h4 class="text-white font-semibold mb-4">Support</h4>
            <ul class="space-y-2 text-sm">
              <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
              <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
            </ul>
          </div>
        </div>
        <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm">
          <p>&copy; 2024 MathGen<span class="text-2xl align-baseline ml-0.5">α</span>. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>
