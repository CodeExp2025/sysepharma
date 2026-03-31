<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    drugUnits: Object,
    depots: Array,
    filters: Object,
    canViewAll: Boolean,
    currentDepotId: [Number, String, null],
    stockByDrug: Array,
    availableStock: { type: Array, default: () => [] },
    soldToday: { type: Array, default: () => [] },
    reportDate: { type: String, default: '' },
});

const page = usePage();
const roles = computed(() => page.props.auth?.roles ?? []);
const activePharmacy = computed(() => page.props.auth?.active_pharmacy ?? null);
const isSuperAdmin = computed(() => roles.value.includes('super_admin'));
const canCreateStock = computed(() => {
    return ['super_admin', 'pharmacy_admin', 'pharmacy_staff'].some((role) => roles.value.includes(role));
});
const isDepotUser = computed(() => {
    return roles.value.includes('depot_staff') || !!page.props.auth?.user?.depot_id;
});

const actionMessage = ref('');
const actionError = ref('');

const notifyLowStock = (drugId) => {
    actionMessage.value = '';
    actionError.value = '';

    if (!props.currentDepotId) {
        actionError.value = 'Dépôt non défini.';
        return;
    }

    router.post(
        route('depots.low-stock-notify', props.currentDepotId),
        { drug_id: drugId },
        {
            preserveScroll: true,
            onSuccess: () => {
                actionMessage.value = 'Notification envoyée.';
            },
            onError: (errors) => {
                actionError.value = errors?.drug_id || errors?.error || 'Échec de l’envoi.';
            },
        }
    );
};

const scope = ref(props.filters?.scope || 'all');
const depotId = ref(props.filters?.depot_id || '');
const sort = ref(props.filters?.sort || 'created_at');
const direction = ref(props.filters?.direction || 'desc');

const applyFilters = () => {
    const params = {
        scope: scope.value,
        depot_id: scope.value === 'depot' ? depotId.value : undefined,
        sort: sort.value,
        direction: direction.value,
    };

    router.get(route('drug-units.index'), params, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

watch([scope, depotId, sort, direction], () => {
    applyFilters();
});

const totalAvailable = () => props.availableStock.reduce((s, r) => s + (r.quantity ?? 0), 0);
const totalSold = () => props.soldToday.reduce((s, r) => s + (r.quantity ?? 0), 0);
const totalRevenue = () => props.soldToday.reduce((s, r) => s + Number(r.revenue ?? 0), 0);

const fmtDrug = (drug) => {
    if (!drug) return '—';
    let label = drug.name;
    if (drug.form_med) label += ` (${drug.form_med}`;
    if (drug.dosage_med) label += ` ${drug.dosage_med}`;
    if (drug.form_med) label += ')';
    return label;
};

// ── Report print ──
const isPrinting = ref(false);

const printReport = () => {
    isPrinting.value = true;
    document.body.classList.add('printing-drug-units');
    setTimeout(() => window.print(), 80);
};

const onAfterPrint = () => {
    isPrinting.value = false;
    document.body.classList.remove('printing-drug-units');
};
window.addEventListener('afterprint', onAfterPrint);
onBeforeUnmount(() => window.removeEventListener('afterprint', onAfterPrint));

const getStatusLabel = (status) => {
    const labels = {
        'en_stock': 'En Stock',
        'vendu': 'Vendu',
        'reserve': 'Réservé'
    };
    return labels[status] || status;
};

const getExpirationStatus = (date) => {
    const expDate = new Date(date);
    const today = new Date();
    const diffDays = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) return 'expired';
    if (diffDays <= 30) return 'warning';
    if (diffDays <= 90) return 'caution';
    return 'good';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout title="Stock (Unités)">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        Inventaire des Unités
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        <span v-if="activePharmacy">Stock de <strong>{{ activePharmacy.name }}</strong></span>
                        <span v-else>Gestion et suivi des unités de médicaments</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200"
                        @click="printReport"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Rapport du jour
                    </button>
                    <Link
                        v-if="canCreateStock"
                        :href="route('drug-units.create')"
                        class="group px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 border border-transparent rounded-lg font-semibold text-sm text-white shadow-md hover:shadow-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Entrée de Stock
                        </span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Active Pharmacy Banner -->
                <div v-if="activePharmacy && !isSuperAdmin" class="flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-xl px-5 py-3 mb-6">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="text-sm text-blue-800">Pharmacie active : <strong>{{ activePharmacy.name }}</strong> — seules les unités de cette pharmacie sont affichées.</span>
                </div>

                <div v-if="canViewAll" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Emplacement</label>
                            <select
                                v-model="scope"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="all">Tout</option>
                                <option value="pharmacy">Pharmacie</option>
                                <option value="depot">Dépôt</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Dépôt</label>
                            <select
                                v-model="depotId"
                                :disabled="scope !== 'depot'"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100"
                            >
                                <option value="">Tous les dépôts</option>
                                <option v-for="d in depots" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Trier par</label>
                            <select
                                v-model="sort"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="created_at">Date d’entrée</option>
                                <option value="drug_name">Médicament</option>
                                <option value="barcode">Code-barres</option>
                                <option value="expiration_date">Expiration</option>
                                <option value="status">Statut</option>
                                <option value="price">Prix</option>
                                <option value="location">Emplacement</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Ordre</label>
                            <select
                                v-model="direction"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="desc">Décroissant</option>
                                <option value="asc">Croissant</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div v-if="isDepotUser" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <div class="px-6 py-4 bg-gradient-to-r from-red-50 to-red-100 border-b border-red-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-red-900">Stock faible (Notifier la pharmacie)</h3>
                            <span class="text-xs font-semibold text-red-700">
                                {{ stockByDrug?.length || 0 }} médicament(s)
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="actionMessage" class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">
                            {{ actionMessage }}
                        </div>
                        <div v-if="actionError" class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">
                            {{ actionError }}
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Médicament</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Reste</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <tr v-for="row in stockByDrug" :key="row.drug_id" class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-semibold text-gray-900">{{ row.drug_name }}</div>
                                            <div class="text-xs text-gray-500 mt-0.5">{{ row.category_name || '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 ring-1 ring-red-600/20">
                                                {{ row.remaining }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 transition-colors duration-150"
                                                @click="notifyLowStock(row.drug_id)"
                                            >
                                                Notifier
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!stockByDrug || stockByDrug.length === 0">
                                        <td colspan="3" class="px-6 py-10 text-center text-sm text-gray-500">Aucune donnée de stock.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700">En Stock</p>
                                <p class="text-2xl font-bold text-green-900 mt-1">
                                    {{ drugUnits.data.filter(u => u.status === 'en_stock').length }}
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 border border-yellow-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-yellow-700">Réservés</p>
                                <p class="text-2xl font-bold text-yellow-900 mt-1">
                                    {{ drugUnits.data.filter(u => u.status === 'reserve').length }}
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-yellow-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 border border-red-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-red-700">Vendus</p>
                                <p class="text-2xl font-bold text-red-900 mt-1">
                                    {{ drugUnits.data.filter(u => u.status === 'vendu').length }}
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-red-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Table Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Médicament
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Code-barres
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Emplacement
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Expiration
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Prix
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Statut
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="unit in drugUnits.data" 
                                    :key="unit.id"
                                    class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-3">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">
                                                    {{ unit.drug?.name || 'Inconnu' }}
                                                </div>
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    {{ unit.drug?.category?.name || '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center px-3 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            <span class="text-sm font-mono text-gray-900">
                                                {{ unit.barcode }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20">
                                            {{ unit.location_label || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-2 h-2 rounded-full mr-2"
                                                :class="{
                                                    'bg-red-500': getExpirationStatus(unit.expiration_date) === 'expired',
                                                    'bg-orange-500': getExpirationStatus(unit.expiration_date) === 'warning',
                                                    'bg-yellow-500': getExpirationStatus(unit.expiration_date) === 'caution',
                                                    'bg-green-500': getExpirationStatus(unit.expiration_date) === 'good'
                                                }">
                                            </div>
                                            <span class="text-sm font-medium"
                                                :class="{
                                                    'text-red-700': getExpirationStatus(unit.expiration_date) === 'expired',
                                                    'text-orange-700': getExpirationStatus(unit.expiration_date) === 'warning',
                                                    'text-yellow-700': getExpirationStatus(unit.expiration_date) === 'caution',
                                                    'text-gray-700': getExpirationStatus(unit.expiration_date) === 'good'
                                                }">
                                                {{ formatDate(unit.expiration_date) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">
                                            {{ unit.price.toLocaleString() }} <span class="text-xs font-normal text-gray-500">FCFA</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-800 ring-1 ring-green-600/20': unit.status === 'en_stock',
                                                'bg-red-100 text-red-800 ring-1 ring-red-600/20': unit.status === 'vendu',
                                                'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-600/20': unit.status === 'reserve'
                                            }">
                                            {{ getStatusLabel(unit.status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="drugUnits.data.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 font-medium">Aucune unité en stock</p>
                                            <p class="text-gray-400 text-sm mt-1">Commencez par ajouter des unités à votre inventaire</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200" v-if="drugUnits.links && drugUnits.data.length > 0">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                Affichage de <span class="font-semibold">{{ drugUnits.data.length }}</span> unité(s)
                            </div>
                            <div class="flex gap-1">
                                <template v-for="(link, key) in drugUnits.links" :key="key">
                                    <div v-if="link.url === null" 
                                        class="px-3 py-2 text-sm text-gray-400 border border-gray-200 rounded-lg bg-white cursor-not-allowed" 
                                        v-html="link.label" />
                                    <Link v-else 
                                        class="px-3 py-2 text-sm border rounded-lg transition-all duration-150 hover:shadow-sm" 
                                        :class="link.active 
                                            ? 'bg-blue-600 text-white border-blue-600 font-semibold shadow-sm' 
                                            : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300 hover:text-blue-600'" 
                                        :href="link.url" 
                                        v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Print Report (only visible on print) ── -->
        <div v-if="isPrinting" class="print-report">
        <!-- Header -->
        <div class="pr-header">
            <div>
                <h1>RAPPORT DE STOCK JOURNALIER</h1>
                <p class="pr-date">Généré le {{ reportDate }}</p>
            </div>
            <div class="pr-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                Sys E-Dépôt Pharma
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="pr-summary">
            <div class="pr-card">
                <div class="pr-card-label">Stock Disponible</div>
                <div class="pr-card-value">{{ totalAvailable() }}</div>
                <div class="pr-card-sub">unités en stock</div>
            </div>
            <div class="pr-card sold">
                <div class="pr-card-label">Vendus Aujourd'hui</div>
                <div class="pr-card-value">{{ totalSold() }}</div>
                <div class="pr-card-sub">unités vendues</div>
            </div>
            <div class="pr-card revenue">
                <div class="pr-card-label">Chiffre d'Affaires</div>
                <div class="pr-card-value">{{ totalRevenue().toLocaleString('fr-FR') }}</div>
                <div class="pr-card-sub">FCFA aujourd'hui</div>
            </div>
        </div>

        <!-- Available Stock Table -->
        <h2 class="pr-section-title">Stock Disponible</h2>
        <table class="pr-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Médicament</th>
                    <th class="right">Quantité Disponible</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, idx) in availableStock" :key="idx">
                    <td class="center">{{ idx + 1 }}</td>
                    <td>{{ fmtDrug(row.drug) }}</td>
                    <td class="right bold">{{ row.quantity }}</td>
                </tr>
                <tr v-if="availableStock.length === 0">
                    <td colspan="3" class="center empty">Aucun stock disponible</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" class="right bold">Total</td>
                    <td class="right bold">{{ totalAvailable() }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Sold Today Table -->
        <h2 class="pr-section-title">Ventes du Jour</h2>
        <table class="pr-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Médicament</th>
                    <th class="right">Qté Vendue</th>
                    <th class="right">Montant (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, idx) in soldToday" :key="idx">
                    <td class="center">{{ idx + 1 }}</td>
                    <td>{{ row.drug?.name ?? '—' }}</td>
                    <td class="right">{{ row.quantity }}</td>
                    <td class="right bold">{{ Number(row.revenue).toLocaleString('fr-FR') }}</td>
                </tr>
                <tr v-if="soldToday.length === 0">
                    <td colspan="4" class="center empty">Aucune vente aujourd'hui</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" class="right bold">Total</td>
                    <td class="right bold">{{ totalSold() }}</td>
                    <td class="right bold">{{ totalRevenue().toLocaleString('fr-FR') }} FCFA</td>
                </tr>
            </tbody>
        </table>

        <p class="pr-footer">Sys E-Dépôt Pharma — Lumière Afrique Group Sarl — {{ reportDate }}</p>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom smooth transitions */
* {
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.print-report { display: none; }

/* Print report styles */
.pr-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 3px solid #2563eb; padding-bottom: 16px; margin-bottom: 24px;
    font-family: 'Segoe UI', Arial, sans-serif;
}
.pr-header h1 { font-size: 20px; font-weight: 800; color: #1e40af; letter-spacing: 0.5px; }
.pr-date { font-size: 12px; color: #6b7280; margin-top: 4px; }
.pr-logo {
    display: flex; align-items: center; gap: 8px;
    font-size: 14px; font-weight: 700; color: #2563eb;
}
.pr-logo svg { width: 24px; height: 24px; }

.pr-summary { display: flex; gap: 16px; margin-bottom: 28px; }
.pr-card {
    flex: 1; padding: 16px; border-radius: 8px;
    background: #eff6ff; border: 1px solid #bfdbfe; text-align: center;
    font-family: 'Segoe UI', Arial, sans-serif;
}
.pr-card.sold { background: #fef9c3; border-color: #fde68a; }
.pr-card.revenue { background: #f0fdf4; border-color: #bbf7d0; }
.pr-card-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #374151; margin-bottom: 6px; }
.pr-card-value { font-size: 28px; font-weight: 800; color: #1e40af; }
.pr-card.sold .pr-card-value { color: #92400e; }
.pr-card.revenue .pr-card-value { color: #166534; }
.pr-card-sub { font-size: 10px; color: #6b7280; margin-top: 2px; }

.pr-section-title {
    font-size: 13px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px; color: #1e40af; margin: 20px 0 8px;
    border-bottom: 1px solid #bfdbfe; padding-bottom: 4px;
    font-family: 'Segoe UI', Arial, sans-serif;
}

.pr-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; }
.pr-table th { background: #1e40af; color: white; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
.pr-table td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
.pr-table tbody tr:nth-child(even) td { background: #f9fafb; }
.pr-table .total-row td { border-top: 2px solid #2563eb; background: #eff6ff; font-weight: 700; }
.center { text-align: center; }
.right { text-align: right; }
.bold { font-weight: 700; }
.empty { text-align: center; color: #9ca3af; font-style: italic; padding: 16px; }

.pr-footer {
    text-align: center; font-size: 10px; color: #9ca3af;
    border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 16px;
    font-family: 'Segoe UI', Arial, sans-serif;
}
</style>

<style>
@media print {
    body.printing-drug-units * { visibility: hidden !important; }
    body.printing-drug-units .print-report,
    body.printing-drug-units .print-report * { visibility: visible !important; }
    body.printing-drug-units .print-report {
        position: fixed !important;
        top: 0 !important; left: 0 !important;
        width: 100% !important;
        background: white !important;
        display: block !important;
        padding: 20px !important;
    }
    body { background: white; margin: 0; }
    @page { margin: 1cm; }
}
</style>
