<script setup>
import { computed } from 'vue';
import { Radar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    RadialLinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    Legend
} from 'chart.js';

// Register Chart.js components
ChartJS.register(RadialLinearScale, PointElement, LineElement, Filler, Tooltip, Legend);

const props = defineProps({
    varkScores: {
        type: Object,
        required: true,
        // Expected format: { score_v: 1, score_a: 2, score_r: 12, score_k: 1 }
    }
});

const chartData = computed(() => ({
    labels: ['V', 'A', 'R', 'K'],
    datasets: [
        {
            label: 'Learning Style Scores',
            data: [
                props.varkScores.score_v || 0,
                props.varkScores.score_a || 0,
                props.varkScores.score_r || 0,
                props.varkScores.score_k || 0
            ],
            backgroundColor: 'rgba(96, 165, 250, 0.2)', // blue with transparency
            borderColor: 'rgb(96, 165, 250)',
            borderWidth: 2,
            pointBackgroundColor: 'rgb(59, 130, 246)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgb(59, 130, 246)',
            pointRadius: 5,
            pointHoverRadius: 7
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    layout: {
        padding: {
            top: 25,
            right: 25,
            bottom: 25,
            left: 25
        }
    },
    scales: {
        r: {
            beginAtZero: true,
            max: 16, // Adjust based on your max possible score
            ticks: {
                stepSize: 4,
                font: {
                    size: 10
                }
            },
            pointLabels: {
                font: {
                    size: 11,
                    weight: '600'
                },
                padding: 10
            }
        }
    },
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 10,
            titleFont: {
                size: 14
            },
            bodyFont: {
                size: 13
            }
        }
    }
};
</script>

<template>
    <div class="w-full h-full flex justify-center items-center">
        <Radar :data="chartData" :options="chartOptions" />
    </div>
</template>