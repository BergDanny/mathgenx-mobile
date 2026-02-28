<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Tooltip,
    Legend
} from 'chart.js';

// Register Chart.js components
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend);

const props = defineProps({
    attemptsData: {
        type: Array,
        required: true,
        // Expected format: [{ attemptNumber: 1, accuracy: 85 }, { attemptNumber: 2, accuracy: 92 }, ...]
        default: () => []
    }
});

const chartData = computed(() => {
    if (!props.attemptsData || props.attemptsData.length === 0) {
        return {
            labels: [],
            datasets: []
        };
    }

    // Get only the latest 50 data points
    const latestData = props.attemptsData.slice(-50);
    const labels = latestData.map(item => `Attempt ${item.attemptNumber}`);
    const accuracyData = latestData.map(item => item.accuracy || 0);

    return {
        labels,
        datasets: [
            {
                label: 'Accuracy',
                data: accuracyData,
                borderColor: 'rgb(96, 165, 250)', // Blue color
                backgroundColor: 'rgba(96, 165, 250, 0.05)', // Blue with lighter transparency
                borderWidth: 2,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgb(59, 130, 246)',
                pointRadius: 5,
                pointHoverRadius: 8,
                tension: 0.4, // Smooth curve
                fill: false
            }
        ]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    layout: {
        padding: {
            top: 10,
            right: 15,
            bottom: 10,
            left: 10
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            max: 100,
            ticks: {
                stepSize: 10,
                font: {
                    size: 11
                },
                color: '#6B7280'
            },
            grid: {
                display: false
            },
            title: {
                display: true,
                text: 'Accuracy (%)',
                font: {
                    size: 12,
                    weight: '600'
                },
                color: '#6B7280'
            }
        },
        x: {
            ticks: {
                font: {
                    size: 11
                },
                color: '#6B7280'
            },
            grid: {
                display: false
            }
        }
    },
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleFont: {
                size: 13,
                weight: '600'
            },
            bodyFont: {
                size: 12
            },
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(96, 165, 250, 0.5)',
            borderWidth: 1,
            callbacks: {
                label: function(context) {
                    return `Accuracy: ${context.parsed.y.toFixed(1)}%`;
                }
            }
        }
    }
};
</script>

<template>
    <div class="w-full h-full" style="min-height: 250px;">
        <Line v-if="attemptsData && attemptsData.length > 0" :data="chartData" :options="chartOptions" />
        <div v-else class="flex items-center justify-center h-full text-gray-500 text-sm">
            No data available
        </div>
    </div>
</template>

