<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash-es';

const props = defineProps({
    announcements: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const performSearch = debounce(() => {
    router.get(route('announcements.index'), { search: search.value }, { preserveState: true, replace: true });
}, 300);

watch(search, performSearch);

const deleteAnnouncement = (uuid) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?')) {
        router.delete(route('announcements.destroy', uuid));
    }
};

const formatDateTime = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
};

const isActive = (announcement) => {
    const now = new Date();
    return announcement.is_active && new Date(announcement.starts_at) <= now && new Date(announcement.ends_at) >= now;
};

const typeColors = {
    info: 'bg-blue-100 text-blue-800',
    warning: 'bg-yellow-100 text-yellow-800',
    success: 'bg-green-100 text-green-800',
    danger: 'bg-red-100 text-red-800',
};

const typeLabels = {
    info: 'Info',
    warning: 'Attention',
    success: 'Succès',
    danger: 'Urgent',
};
</script>

<template>
    <AppLayout title="Annonces">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                        Gestion des annonces
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Gérez les annonces affichées aux utilisateurs
                    </p>
                </div>
                <Link
                    :href="route('announcements.create')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouvelle annonce
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <!-- Search -->
                <div class="mb-6">
                    <div class="relative max-w-md">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher une annonce..."
                            class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Début</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="announcement in announcements.data" :key="announcement.uuid" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 max-w-xs truncate">{{ announcement.title }}</div>
                                    <div class="text-xs text-gray-500 max-w-xs truncate">{{ announcement.content }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="typeColors[announcement.type]" class="px-2 py-1 text-xs font-medium rounded-full">
                                        {{ typeLabels[announcement.type] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ formatDateTime(announcement.starts_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ formatDateTime(announcement.ends_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="isActive(announcement) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ isActive(announcement) ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        :href="route('announcements.edit', announcement.uuid)"
                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                    >
                                        Modifier
                                    </Link>
                                    <button
                                        @click="deleteAnnouncement(announcement.uuid)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Supprimer
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="announcements.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Aucune annonce trouvée
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="announcements.links && announcements.links.length > 3" class="px-6 py-4 bg-gray-50 border-t border-gray-100 mt-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Affichage de <span class="font-medium">{{ announcements.from }}</span> à <span class="font-medium">{{ announcements.to }}</span> sur <span class="font-medium">{{ announcements.total }}</span> résultats
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="(link, index) in announcements.links"
                                :key="index"
                                :href="link.url"
                                :class="[
                                    'px-3 py-2 text-sm rounded-md transition-colors',
                                    link.active
                                        ? 'bg-blue-600 text-white font-medium'
                                        : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                :disabled="!link.url"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
