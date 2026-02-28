<script setup lang="ts">
import { LucideChevronRight, LucideGrid, LucideHome } from 'lucide-vue-next';

</script>

<template>
    <ol class="flex items-center whitespace-nowrap py-4 px-6">
        <li class="inline-flex items-center">
            <a class="flex items-center text-sm text-gray-500 hover:text-blue-600 focus:outline-hidden focus:text-blue-600 dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500"
                href="#">
                <LucideHome class="w-4 h-4 mr-2" />
                Home
            </a>
            <LucideChevronRight class="w-4 h-4 mx-2 text-gray-400" />
        </li>
        <li class="inline-flex items-center text-sm font-semibold text-gray-800 truncate dark:text-neutral-200"
            aria-current="page">
            <LucideGrid class="w-4 h-4 mr-2" />
            Dashboard
        </li>
    </ol>
</template>