<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    transfers: Object,
    filters: Object,
});

const page = usePage();
const activePharmacy = computed(() => page.props.auth?.active_pharmacy ?? null);
const roles = computed(() => page.props.auth?.roles ?? []);
const isSuperAdmin = computed(() => roles.value.includes('super_admin'));
const isPharmacyAdmin = computed(() => roles.value.includes('pharmacy_admin'));
const canManage = computed(() => isSuperAdmin.value || isPharmacyAdmin.value);

const search    = ref(props.filters?.search    || '');
const sort      = ref(props.filters?.sort      || 'performed_at');
const direction = ref(props.filters?.direction || 'desc');

const applyFilters = () => {
    router.get(route('transfers.index'), {
        search:    search.value || undefined,
        sort:      sort.value,
        direction: direction.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
};

const debouncedSearch = debounce(() => applyFilters(), 300);
watch(search, () => debouncedSearch());
watch([sort, direction], () => applyFilters());

const setSort = (column) => {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = 'asc';
    }
};

const getStatusBadgeColor = (status) => {
    const colors = {
        'pending':   'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
        'completed': 'bg-green-100 text-green-800 ring-green-600/20',
        'cancelled': 'bg-red-100 text-red-800 ring-red-600/20',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 ring-gray-600/20';
};

const getStatusLabel = (status) => {
    const labels = {
        'pending':   'En attente',
        'completed': 'Complété',
        'cancelled': 'Annulé',
    };
    return labels[status] || status;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const pendingCount   = computed(() => props.transfers.data.filter(t => t.status === 'pending').length);
const completedCount = computed(() => props.transfers.data.filter(t => t.status === 'completed').length);
const totalUnits     = computed(() => props.transfers.data.reduce((s, t) => s + (t.items_count || 0), 0));

const cancelTransfer = (id) => {
    if (confirm('Annuler ce transfert ?')) {
        router.post(route('transfers.cancel', id), {}, { preserveScroll: true });
    }
};

const deleteTransfer = (id) => {
    if (confirm('Supprimer définitivement ce transfert ? Cette action est irréversible.')) {
        router.delete(route('transfers.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Transferts de Stock">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        Transferts de Stock
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        <span v-if="activePharmacy && !isSuperAdmin">Transferts de <strong>{{ activePharmacy.name }}</strong></span>
                        <span v-else>Historique et gestion des transferts entre dépôts</span>
                    </p>
                </div>
                <Link :href="route('transfers.create')"
                    class="group px-5 py-2.5 bg-gradient-to-r from-orange-600 to-orange-700 border border-transparent rounded-lg font-semibold text-sm text-white shadow-md hover:shadow-lg hover:from-orange-700 hover:to-orange-800 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transform transition-all duration-200 hover:-translate-y-0.5">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nouveau Transfert
                    </span>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Active Pharmacy Banner -->
                <div v-if="activePharmacy && !isSuperAdmin" class="flex items-center gap-3 bg-orange-50 border border-orange-200 rounded-xl px-5 py-3 mb-6">
                    <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <span class="text-sm text-orange-800">Pharmacie active : <strong>{{ activePharmacy.name }}</strong> — seuls les transferts de cette pharmacie sont affichés.</span>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-700">Total</p>
                                <p class="text-2xl font-bold text-blue-900 mt-1">{{ transfers.data.length }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 border border-yellow-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-yellow-700">En attente</p>
                                <p class="text-2xl font-bold text-yellow-900 mt-1">{{ pendingCount }}</p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700">Complétés</p>
                                <p class="text-2xl font-bold text-green-900 mt-1">{{ completedCount }}</p>
                            </div>
                            <div class="w-12 h-12 bg-green-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-700">Unités</p>
                                <p class="text-2xl font-bold text-purple-900 mt-1">{{ totalUnits }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search bar -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="relative max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input v-model="search" type="text" placeholder="Rechercher par dépôt, utilisateur, statut..."
                            class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                        <button v-if="search" @click="search = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Transfers Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th @click="setSort('id')" scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-orange-700">
                                        Transfert <span v-if="sort === 'id'">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Destination
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Unités
                                    </th>
                                    <th @click="setSort('performed_at')" scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-orange-700">
                                        Date <span v-if="sort === 'performed_at'">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th @click="setSort('status')" scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-orange-700">
                                        Statut <span v-if="sort === 'status'">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="transfer in transfers.data" :key="transfer.id"
                                    class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-orange-100 to-orange-200 rounded-lg flex items-center justify-center mr-3">
                                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">Transfert #{{ transfer.id }}</div>
                                                <div class="text-xs text-gray-500 mt-0.5">Par {{ transfer.user?.name || 'Système' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center text-sm text-gray-700">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                            <div>
                                                <div class="font-medium text-gray-900">{{ transfer.depot?.name || 'N/A' }}</div>
                                                <div class="text-xs text-gray-500">{{ transfer.depot?.address || '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 ring-1 ring-purple-600/20">
                                            {{ transfer.items_count || 0 }} unité(s)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-700">{{ formatDate(transfer.created_at) }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ring-1"
                                            :class="getStatusBadgeColor(transfer.status)">
                                            {{ getStatusLabel(transfer.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="route('transfers.show', transfer.id)"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Détails
                                            </Link>
                                            <button v-if="canManage && transfer.status === 'pending'"
                                                @click="cancelTransfer(transfer.id)"
                                                class="inline-flex items-center px-3 py-1.5 bg-yellow-50 text-yellow-700 text-sm font-medium rounded-lg hover:bg-yellow-100 transition-colors">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                Annuler
                                            </button>
                                            <button v-if="canManage"
                                                @click="deleteTransfer(transfer.id)"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 transition-colors">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="transfers.data.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 font-medium">Aucun transfert trouvé</p>
                                            <p class="text-gray-400 text-sm mt-1">Créez votre premier transfert pour commencer</p>
                                            <Link :href="route('transfers.create')"
                                                class="mt-4 px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                                                Créer un transfert
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200" v-if="transfers.links && transfers.data.length > 0">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                Affichage de <span class="font-semibold">{{ transfers.data.length }}</span> transfert(s)
                            </div>
                            <div class="flex gap-1">
                                <template v-for="(link, key) in transfers.links" :key="key">
                                    <div v-if="link.url === null"
                                        class="px-3 py-2 text-sm text-gray-400 border border-gray-200 rounded-lg bg-white cursor-not-allowed"
                                        v-html="link.label" />
                                    <Link v-else
                                        class="px-3 py-2 text-sm border rounded-lg transition-all duration-150 hover:shadow-sm"
                                        :class="link.active
                                            ? 'bg-orange-600 text-white border-orange-600 font-semibold shadow-sm'
                                            : 'bg-white text-gray-700 border-gray-200 hover:border-orange-300 hover:text-orange-600'"
                                        :href="link.url"
                                        v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
