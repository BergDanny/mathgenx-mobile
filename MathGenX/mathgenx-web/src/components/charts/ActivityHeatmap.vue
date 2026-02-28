<script setup>
import { ref, computed, onUnmounted } from 'vue';

const props = defineProps({
    heatmapData: {
        type: Array,
        required: true,
        default: () => []
        // Expected format: [{ date: '2024-01-15', count: 3 }, ...]
    }
});

const tooltip = ref({
    visible: false,
    x: 0,
    y: 0,
    date: '',
    count: 0,
    dayName: ''
});

// Create a map of date to count for quick lookup
const dateCountMap = computed(() => {
    const map = new Map();
    props.heatmapData.forEach(item => {
        map.set(item.date, item.count);
    });
    return map;
});

// Get date range - always 12 months - memoized
const dateRange = computed(() => {
    // Use local date to match user's timezone
    const today = new Date();
    const todayYear = today.getFullYear();
    const todayMonth = today.getMonth();
    const todayDay = today.getDate();
    const todayDate = new Date(todayYear, todayMonth, todayDay, 0, 0, 0, 0);
    
    // Always show last 12 months
    const startDate = new Date(todayDate);
    startDate.setMonth(todayDate.getMonth() - 12);
    
    return { startDate, endDate: todayDate };
});

// Generate calendar grid - optimized
const calendarGrid = computed(() => {
    // Early return if no data
    if (props.heatmapData.length === 0) {
        return [];
    }
    
    const { startDate, endDate } = dateRange.value;
    const map = dateCountMap.value;
    const grid = [];
    
    // Start from the first day of the week containing startDate
    const firstDay = new Date(startDate);
    const dayOfWeek = firstDay.getDay(); // 0 = Sunday, 1 = Monday, etc.
    // Adjust to Monday = 0 (GitHub style)
    const mondayOffset = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    firstDay.setDate(firstDay.getDate() - mondayOffset);
    
    // Generate all days from first day to end date
    const currentDate = new Date(firstDay);
    let week = [];
    let currentWeekStart = new Date(currentDate);
    
    // Limit to prevent excessive computation (max ~400 days = ~57 weeks)
    const maxDays = 400;
    let dayCount = 0;
    
    // Normalize endDate for comparison (use date only, ignore time)
    // Stop at endDate (today) - do not include future dates
    const endDateNormalized = new Date(endDate);
    endDateNormalized.setHours(0, 0, 0, 0);
    
    const startDateNormalized = new Date(startDate);
    startDateNormalized.setHours(0, 0, 0, 0);
    
    // Generate weeks from oldest (left) to newest (right), ending at today (no future dates)
    while (currentDate <= endDateNormalized && dayCount < maxDays) {
        // Use local date string to match the data format
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const day = currentDate.getDate();
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const count = map.get(dateStr) || 0;
        const currentDateNormalized = new Date(year, month, day, 0, 0, 0, 0);
        const isInRange = currentDateNormalized >= startDateNormalized && currentDateNormalized <= endDateNormalized;
        
        week.push({
            date: dateStr,
            count,
            isInRange
        });
        
        currentDate.setDate(currentDate.getDate() + 1);
        dayCount++;
        
        if (week.length === 7) {
            grid.push({
                weekStart: new Date(currentWeekStart),
                days: week
            });
            week = [];
            currentWeekStart = new Date(currentDate);
        }
    }
    
    // Add remaining days if any (only up to endDate, no future dates)
    if (week.length > 0 && dayCount < maxDays) {
        grid.push({
            weekStart: new Date(currentWeekStart),
            days: week
        });
    }
    
    // Grid is in order: oldest week (left) to newest week (right)
    // The last week in the array contains today and will appear at the rightmost column
    // This matches GitHub style where most recent dates are on the right
    // Ensure the grid is properly ordered with newest (today) at the end
    // Weeks are displayed from array index 0 (left/oldest) to array.length-1 (right/newest)
    
    // Explicitly sort by weekStart date to ensure correct chronological order
    // Oldest weeks first (left), newest weeks last (right)
    grid.sort((a, b) => {
        const dateA = new Date(a.weekStart).getTime();
        const dateB = new Date(b.weekStart).getTime();
        return dateA - dateB; // Ascending order: oldest first, newest last
    });
    
    return grid;
});

// Get color class based on count
const getColorClass = (count) => {
    if (count === 0) return 'bg-gray-200';
    if (count === 1) return 'bg-blue-200';
    if (count >= 2 && count <= 3) return 'bg-blue-400';
    if (count >= 4 && count <= 5) return 'bg-blue-600';
    return 'bg-purple-600';
};

// Format date for tooltip - memoized
const dateFormatCache = new Map();
const formatDate = (dateStr) => {
    if (dateFormatCache.has(dateStr)) {
        return dateFormatCache.get(dateStr);
    }
    
    const date = new Date(dateStr);
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    const formatted = {
        dayName: days[date.getDay()],
        month: months[date.getMonth()],
        day: date.getDate()
    };
    
    dateFormatCache.set(dateStr, formatted);
    return formatted;
};

const showTooltip = (event, day) => {
    if (!day.isInRange) {
        tooltip.value.visible = false;
        return;
    }
    
    const rect = event.currentTarget.getBoundingClientRect();
    const formatted = formatDate(day.date);
    
    tooltip.value = {
        visible: true,
        x: rect.left + rect.width / 2,
        y: rect.top - 10,
        date: day.date,
        count: day.count,
        dayName: formatted.dayName,
        month: formatted.month,
        dayNum: formatted.day
    };
};

const hideTooltip = () => {
    tooltip.value.visible = false;
};

// Get month labels for bottom axis - optimized to avoid circular dependency
const monthLabels = computed(() => {
    // Early return if no data
    if (props.heatmapData.length === 0) {
        return [];
    }
    
    const grid = calendarGrid.value;
    if (grid.length === 0) return [];
    
    const { startDate, endDate } = dateRange.value;
    const months = [];
    const seenMonths = new Set();
    
    // Find the first week that contains the first day of each month
    const current = new Date(startDate);
    current.setDate(1); // Start from first day of start month
    
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    while (current <= endDate) {
        const monthKey = `${current.getFullYear()}-${current.getMonth()}`;
        
        if (!seenMonths.has(monthKey)) {
            seenMonths.add(monthKey);
            
            // Find the week that contains the first day of this month
            const firstDayOfMonth = new Date(current.getFullYear(), current.getMonth(), 1);
            firstDayOfMonth.setHours(0, 0, 0, 0);
            
            // Find week more efficiently by checking grid directly
            // Note: grid is reversed, so we need to find from the end
            let weekIndex = -1;
            for (let i = grid.length - 1; i >= 0; i--) {
                const weekStart = new Date(grid[i].weekStart);
                weekStart.setHours(0, 0, 0, 0);
                const weekEnd = new Date(weekStart);
                weekEnd.setDate(weekEnd.getDate() + 6);
                
                if (firstDayOfMonth >= weekStart && firstDayOfMonth <= weekEnd) {
                    // Convert to position from left (reversed index)
                    weekIndex = grid.length - 1 - i;
                    break;
                }
            }
            
            if (weekIndex !== -1) {
                months.push({
                    name: monthNames[current.getMonth()],
                    weekIndex
                });
            }
        }
        
        current.setMonth(current.getMonth() + 1);
    }
    
    return months;
});

// Get day labels for left axis (show Monday, Wednesday, Friday)
const dayLabels = ['Mon', 'Wed', 'Fri'];
const dayLabelIndices = [0, 2, 4];

// Calculate grid width for month label positioning
const gridWidth = computed(() => {
    // w-4 (16px) + gap-1 (4px) = 20px per week column
    return calendarGrid.value.length * 30;
});

// Calculate total quizzes in range
const totalQuizzes = computed(() => {
    return props.heatmapData.reduce((sum, item) => sum + item.count, 0);
});

onUnmounted(() => {
    // Clear cache on unmount
    dateFormatCache.clear();
});
</script>

<template>
    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
            <div class="flex items-center gap-3">
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 p-3 rounded-xl shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Quiz Activity</h2>
                    <p class="text-sm text-gray-600">{{ totalQuizzes }} quizzes completed</p>
                </div>
            </div>
        </div>

        <!-- Heatmap Content -->
        <div class="p-8">
            <div v-if="calendarGrid.length === 0" class="text-center py-12 text-gray-500">
                No activity data available
            </div>
            
            <div v-else>
                <div class="w-full">
                    <!-- Legend -->
                    <div class="flex items-center justify-end gap-2 mb-4">
                        <span class="text-xs text-gray-600">Less</span>
                        <div class="flex gap-1">
                            <div class="w-3 h-3 rounded bg-gray-200"></div>
                            <div class="w-3 h-3 rounded bg-blue-200"></div>
                            <div class="w-3 h-3 rounded bg-blue-400"></div>
                            <div class="w-3 h-3 rounded bg-blue-600"></div>
                            <div class="w-3 h-3 rounded bg-purple-600"></div>
                        </div>
                        <span class="text-xs text-gray-600">More</span>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="flex gap-1">
                        <!-- Day Labels (Left) -->
                        <div class="flex flex-col gap-1 pr-3" v-if="calendarGrid.length > 0">
                            <div v-for="(day, dayIdx) in calendarGrid[0].days" :key="dayIdx" 
                                class="h-4 flex items-center">
                                <span v-if="dayLabelIndices.includes(dayIdx)" 
                                    class="text-xs text-gray-500">
                                    {{ dayLabels[dayLabelIndices.indexOf(dayIdx)] }}
                                </span>
                            </div>
                        </div>

                        <!-- Weeks Grid -->
                        <!-- Display weeks from left (oldest) to right (newest/today) -->
                        <div class="flex gap-1 flex-1 justify-end">
                            <div v-for="(week, weekIdx) in calendarGrid" :key="weekIdx" 
                                class="flex flex-col gap-1">
                                <div v-for="(day, dayIdx) in week.days" :key="dayIdx"
                                    @mouseenter="showTooltip($event, day)"
                                    @mouseleave="hideTooltip"
                                    :class="[
                                        'w-4 h-4 rounded transition-all cursor-pointer',
                                        day.isInRange ? getColorClass(day.count) : 'bg-gray-100',
                                        day.isInRange && day.count > 0 ? 'hover:ring-2 hover:ring-blue-400 hover:ring-offset-1' : ''
                                    ]">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Month Labels (Bottom) -->
                    <div class="flex gap-1 mt-2">
                        <!-- Spacer for day labels -->
                        <div class="flex flex-col gap-1 pr-3" v-if="calendarGrid.length > 0" style="width: 2.5rem;"></div>
                        <!-- Month labels container, right-aligned -->
                        <div class="relative" :style="{ width: `${gridWidth}px`, marginLeft: 'auto' }">
                            <div v-for="(month, idx) in monthLabels" :key="idx"
                                class="text-xs text-gray-500 absolute"
                                :style="{ 
                                    right: `${(calendarGrid.length - 1 - month.weekIndex) * 20}px`,
                                    transform: 'translateX(50%)'
                                }">
                                {{ month.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Note -->
            <p class="text-xs text-gray-500 text-center mt-6">
                Note: All activity data is using local time.
            </p>
        </div>

        <!-- Tooltip -->
        <div v-if="tooltip.visible && tooltip.date" 
            class="fixed z-50 pointer-events-none"
            :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px', transform: 'translate(-50%, -100%)', marginTop: '-8px' }">
            <div class="bg-gray-900 text-white text-xs rounded px-3 py-2 shadow-lg border border-white/20 whitespace-nowrap">
                <div class="font-semibold mb-1">
                    {{ tooltip.dayName }}, {{ tooltip.month }} {{ tooltip.dayNum }}
                </div>
                <div class="text-gray-300">
                    {{ tooltip.count === 0 ? 'No activity' : `${tooltip.count} quiz${tooltip.count !== 1 ? 'es' : ''}` }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Close dropdown when clicking outside */
</style>

