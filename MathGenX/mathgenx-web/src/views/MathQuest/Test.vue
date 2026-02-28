<template>
    <!-- testting sortable list -->
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-6">Sortable Lists Example</h1>

        <SortableList v-model:lists="sortableLists" group="shared" :animation="150" drag-class="rounded-none!">
            <template #item="{ item }">
                <component :is="item.icon" class="shrink-0 size-4" />
                {{ item.label }}
                <LucideGripVertical class="w-5 h-5 text-gray-500 ms-auto" />
            </template>
        </SortableList>
    </div>

    <!-- logo loop -->
    <div :style="{ height: '200px', position: 'relative', overflow: 'hidden' }">
        <LogoLoop :logos="techLogos" :speed="100" direction="left" :logoHeight="48" :gap="40" :pauseOnHover="true"
            :scaleOnHover="true" :fadeOut="true" fadeOutColor="#ffffff" ariaLabel="Technology partners" />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import SortableList from '@/components/ui/Sortable/SortableList.vue'
import LogoLoop from '@/components/ui/Animations/LogoLoop.vue'

import {
    LucideGripVertical,
    LucideBell,
    LucideDownload,
    LucideUsers,
    LucideHelpCircle,
    LucideSettings,
    LucideActivity
} from 'lucide-vue-next'

interface SortableItem {
    id: string
    label: string
    icon: any
}

// Using image sources from public/logos directory
const techLogos = [
    { src: "/logos/cursor.png", alt: "Cursor", href: "https://www.cursor.com" },
    { src: "/logos/github.png", alt: "Github", href: "https://www.github.com" },
    { src: "/logos/Laravel.svg", alt: "Laravel", href: "https://www.laravel.com" },
    { src: "/logos/pinia.svg", alt: "Pinia", href: "https://pinia.vuejs.org" },
    { src: "/logos/VUE.png", alt: "Vue.js", href: "https://vuejs.org" },
    { src: "/logos/tailwind.png", alt: "Tailwind CSS", href: "https://tailwindcss.com" },
    { src: "/logos/vite.png", alt: "Vite", href: "https://vitejs.dev" },
    { src: "/logos/php.png", alt: "PHP", href: "https://www.php.net" },
    { src: "/logos/nodejs.png", alt: "Node.js", href: "https://nodejs.org" },
];

const sortableLists = ref([
    {
        id: 'hs-shared-left-sortable',
        items: [
            {
                id: '1',
                label: 'Newsletter',
                icon: LucideBell,
            },
            {
                id: '2',
                label: 'Downloads',
                icon: LucideDownload,
            },
            {
                id: '3',
                label: 'Team Account',
                icon: LucideUsers,
            },
        ] as SortableItem[],
    },
    {
        id: 'hs-shared-right-sortable',
        items: [
            {
                id: '4',
                label: 'Questions',
                icon: LucideHelpCircle,
            },
            {
                id: '5',
                label: 'Settings',
                icon: LucideSettings,
            },
            {
                id: '6',
                label: 'Activity',
                icon: LucideActivity,
            },
        ] as SortableItem[],
    },
])
</script>