<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';

// Helper to format time distance to now in French (replaces date-fns formatDistanceToNow)
const formatDistanceToNow = (date) => {
    const now = new Date();
    const target = new Date(date);
    const diffMs = target - now;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffSec < 0) return 'terminée';
    if (diffSec < 60) return 'dans moins d\'une minute';
    if (diffMin < 60) return `dans ${diffMin} minute${diffMin > 1 ? 's' : ''}`;
    if (diffHour < 24) return `dans ${diffHour} heure${diffHour > 1 ? 's' : ''}`;
    if (diffDay < 30) return `dans ${diffDay} jour${diffDay > 1 ? 's' : ''}`;
    return `dans ${Math.floor(diffDay / 30)} mois`;
};

const announcements = ref([]);
const currentIndex = ref(0);
let pollInterval = null;

const typeStyles = {
    info: 'bg-blue-600 border-blue-700',
    warning: 'bg-yellow-500 border-yellow-600',
    success: 'bg-green-600 border-green-700',
    danger: 'bg-red-600 border-red-700',
};

const iconPaths = {
    info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    warning: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    success: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    danger: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
};

const hasAnnouncements = computed(() => announcements.value.length > 0);

const currentAnnouncement = computed(() => {
    if (!hasAnnouncements.value) return null;
    return announcements.value[currentIndex.value];
});

const timeRemaining = computed(() => {
    if (!currentAnnouncement.value) return '';
    const endDate = new Date(currentAnnouncement.value.ends_at);
    return formatDistanceToNow(endDate);
});

const fetchAnnouncements = async () => {
    try {
        const response = await fetch(route('announcements.active'));
        const data = await response.json();
        announcements.value = data.announcements || [];
        if (currentIndex.value >= announcements.value.length) {
            currentIndex.value = 0;
        }
    } catch {
        // Silently fail - don't break the UI if announcements can't be fetched
    }
};

const nextAnnouncement = () => {
    if (announcements.value.length > 1) {
        currentIndex.value = (currentIndex.value + 1) % announcements.value.length;
    }
};

const prevAnnouncement = () => {
    if (announcements.value.length > 1) {
        currentIndex.value = (currentIndex.value - 1 + announcements.value.length) % announcements.value.length;
    }
};

const closeBanner = () => {
    // Store closed announcement IDs in session storage to not show them again
    const closedIds = JSON.parse(sessionStorage.getItem('closedAnnouncements') || '[]');
    if (currentAnnouncement.value && !closedIds.includes(currentAnnouncement.value.id)) {
        closedIds.push(currentAnnouncement.value.id);
        sessionStorage.setItem('closedAnnouncements', JSON.stringify(closedIds));
    }
    // Remove current announcement from the list
    announcements.value = announcements.value.filter((_, i) => i !== currentIndex.value);
    if (currentIndex.value >= announcements.value.length) {
        currentIndex.value = 0;
    }
};

const visibleAnnouncements = computed(() => {
    const closedIds = JSON.parse(sessionStorage.getItem('closedAnnouncements') || '[]');
    return announcements.value.filter(a => !closedIds.includes(a.id));
});

onMounted(() => {
    fetchAnnouncements();
    pollInterval = setInterval(fetchAnnouncements, 60000); // Refresh every minute
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<template>
    <div
        v-if="visibleAnnouncements.length > 0 && currentAnnouncement"
        :class="typeStyles[currentAnnouncement.type]"
        class="relative text-white px-4 py-2"
    >
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center flex-1 min-w-0">
                <!-- Icon -->
                <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[currentAnnouncement.type]"/>
                </svg>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm">{{ currentAnnouncement.title }}</span>
                        <span class="text-xs opacity-80 hidden sm:inline">— {{ currentAnnouncement.content }}</span>
                    </div>
                   <!-- <div class="text-xs opacity-70 mt-0.5">
                        Se termine {{ timeRemaining }}
                    </div> -->
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex items-center gap-2 ml-4 flex-shrink-0">
                <button
                    v-if="visibleAnnouncements.length > 1"
                    @click="prevAnnouncement"
                    class="p-1 hover:bg-white/20 rounded transition-colors"
                    title="Annonce précédente"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <span v-if="visibleAnnouncements.length > 1" class="text-xs opacity-80">
                    {{ currentIndex + 1 }} / {{ visibleAnnouncements.length }}
                </span>

                <button
                    v-if="visibleAnnouncements.length > 1"
                    @click="nextAnnouncement"
                    class="p-1 hover:bg-white/20 rounded transition-colors"
                    title="Annonce suivante"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <!-- Close -->
                <!-- <button
                    @click="closeBanner"
                    class="p-1 hover:bg-white/20 rounded transition-colors ml-2"
                    title="Fermer cette annonce"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button> -->
            </div>
        </div>
    </div>
</template>
