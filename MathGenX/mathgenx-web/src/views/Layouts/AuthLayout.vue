<script setup lang="ts">
import RotatingText from "@/components/ui/TextAnimation/RotatingText/RotatingText.vue";
import Iridescence from "@/components/ui/Backgrounds/Iridescence/Iridescence.vue";

</script>


<template>
  <main class="flex min-h-full">
    <!-- MOBILE LAYOUT: Full-screen iridescence background with white card -->
    <div class="md:hidden relative w-screen h-screen overflow-hidden">
      <!-- Full-screen Iridescence Background -->
      <div class="fixed inset-0 w-full h-full z-0">
        <Iridescence :color="[1, 1, 1]" :speed="1.0" :amplitude="0.1" :mouseReact="true" />
      </div>
      
      <!-- Mobile Branding - Centered in visible purple area -->
      <div class="absolute top-0 left-0 right-0 h-[25vh] z-5 flex items-center justify-center px-4 pointer-events-none">
        <span class="text-3xl font-bold mr-4 text-white drop-shadow-lg">MathGen<span class="text-2xl align-baseline ml-0.5">α</span></span>
        <RotatingText :texts="['Solve', 'Explore', 'Create', 'Advance']"
          mainClassName="px-2.5 bg-blue-300 text-black overflow-hidden py-1.5 justify-center rounded-lg font-bold text-xl drop-shadow-md"
          :staggerFrom="0" :initial="{ y: '100%' }" :animate="{ y: 0 }" :exit="{ y: '-120%' }"
          :staggerDuration="0.025" splitLevelClassName="overflow-hidden pb-0.5"
          :transition="{ type: 'spring', damping: 30, stiffness: 400 }" :rotationInterval="2000" />
      </div>
      
      <!-- White Card Container - Slides up from bottom -->
      <div class="relative z-10 w-full h-full flex items-end">
        <div class="w-full bg-white text-gray-900 rounded-t-[2rem] min-h-[75vh] overflow-y-auto shadow-2xl" style="color-scheme: light;">
          <router-view />
        </div>
      </div>
    </div>

    <!-- DESKTOP LAYOUT: Split-screen (normal layout) -->
    <div class="hidden md:flex h-screen w-screen">
      <!-- Left Side: Branding with Iridescence -->
      <div class="w-1/2 h-full relative">
        <Iridescence :color="[1, 1, 1]" :speed="1.0" :amplitude="0.1" :mouseReact="true" />
        <div class="absolute inset-0 flex items-center justify-center h-full px-8 z-10 pointer-events-none">
          <span class="text-4xl font-bold mr-6">MathGen<span class="text-5xl align-baseline ml-0.5">α</span></span>
          <RotatingText :texts="['Solve', 'Explore', 'Create', 'Advance']"
            mainClassName="px-2 sm:px-2 md:px-3 bg-blue-300 text-black overflow-hidden py-0.5 sm:py-1 md:py-2 justify-center rounded-lg font-bold text-5xl"
            :staggerFrom="0" :initial="{ y: '100%' }" :animate="{ y: 0 }" :exit="{ y: '-120%' }"
            :staggerDuration="0.025" splitLevelClassName="overflow-hidden pb-0.5 sm:pb-1 md:pb-1"
            :transition="{ type: 'spring', damping: 30, stiffness: 400 }" :rotationInterval="2000" />
        </div>
      </div>
      
      <!-- Right Side: Form Section -->
      <div class="w-1/2 h-full overflow-y-auto bg-white text-gray-900" style="color-scheme: light;">
        <router-view />
      </div>
    </div>
  </main>
</template>

