<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    transfers: Object,
    transfersForPrint: Array,
    depots: Array,
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
const fromDate  = ref(props.filters?.from_date || '');
const toDate    = ref(props.filters?.to_date   || '');
const depotId   = ref(props.filters?.depot_id  || '');

const applyFilters = () => {
    router.get(route('transfers.index'), {
        search:    search.value || undefined,
        sort:      sort.value,
        direction: direction.value,
        from_date: fromDate.value || undefined,
        to_date:   toDate.value   || undefined,
        depot_id:  depotId.value  || undefined,
    }, { preserveState: true, replace: true, preserveScroll: true });
};

const debouncedSearch = debounce(() => applyFilters(), 300);
watch(search, () => debouncedSearch());
watch([sort, direction, depotId], () => applyFilters());
watch([fromDate, toDate], () => {
    if ((!fromDate.value && !toDate.value) || (fromDate.value && toDate.value)) {
        applyFilters();
    }
});

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
    const labels = { 'pending': 'En attente', 'completed': 'Complété', 'cancelled': 'Annulé' };
    return labels[status] || status;
};

const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
});

const pendingCount   = computed(() => props.transfers.data.filter(t => t.status === 'pending').length);
const completedCount = computed(() => props.transfers.data.filter(t => t.status === 'completed').length);
const totalBoxes     = computed(() => props.transfers.data.reduce((s, t) => s + (t.boxes || 0), 0));
const totalQty       = computed(() => props.transfers.data.reduce((s, t) => s + (t.qtyTransferred || 0), 0));

const cancelTransfer = (uuid) => {
    if (confirm('Annuler ce transfert ?')) {
        router.post(route('transfers.cancel', uuid), {}, { preserveScroll: true });
    }
};
const deleteTransfer = (uuid) => {
    if (confirm('Supprimer définitivement ce transfert ? Cette action est irréversible.')) {
        router.delete(route('transfers.destroy', uuid), { preserveScroll: true });
    }
};

// ── Period print ─────────────────────────────────────────────────────────────
const isPrinting     = ref(false);
const printDepotOnly = ref(false); // true = print only filtered depot

const canPrint = computed(() => fromDate.value && toDate.value && props.transfersForPrint?.length > 0);

const transfersByDay = computed(() => {
    if (!props.transfersForPrint) return [];
    const groups = {};
    for (const t of props.transfersForPrint) {
        const day = new Date(t.performed_at).toLocaleDateString('fr-FR', {
            weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
        });
        if (!groups[day]) groups[day] = [];
        groups[day].push(t);
    }
    return Object.entries(groups).map(([day, items]) => ({ day, items }));
});

const formatPeriod = computed(() => {
    if (!fromDate.value || !toDate.value) return '';
    const f = new Date(fromDate.value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    const t = new Date(toDate.value).toLocaleDateString('fr-FR',   { day: '2-digit', month: 'long', year: 'numeric' });
    return f === t ? f : `${f} au ${t}`;
});

const printDepotLabel = computed(() => {
    if (!depotId.value) return '';
    return props.depots?.find(d => String(d.id) === String(depotId.value))?.name || '';
});

const printTotalProducts = computed(() => props.transfersForPrint?.reduce((s, t) => s + (t.products || 0), 0) ?? 0);
const printTotalBoxes    = computed(() => props.transfersForPrint?.reduce((s, t) => s + (t.boxes || 0), 0) ?? 0);
const printTotalQty      = computed(() => props.transfersForPrint?.reduce((s, t) => s + (t.qtyTransferred || 0), 0) ?? 0);
const printTotalRemaining = computed(() => props.transfersForPrint?.reduce((s, t) => s + (t.qtyRemaining || 0), 0) ?? 0);

const printPeriod = () => {
    isPrinting.value = true;
    document.body.classList.add('printing-transfers');
    setTimeout(() => window.print(), 80);
};
const onAfterPrint = () => {
    isPrinting.value = false;
    document.body.classList.remove('printing-transfers');
};
window.addEventListener('afterprint', onAfterPrint);
onBeforeUnmount(() => window.removeEventListener('afterprint', onAfterPrint));
</script>

<template>
    <AppLayout title="Transferts de Stock">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">Transferts de Stock</h2>
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
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                        <p class="text-sm font-medium text-blue-700">Total transferts</p>
                        <p class="text-2xl font-bold text-blue-900 mt-1">{{ transfers.data.length }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 border border-yellow-200">
                        <p class="text-sm font-medium text-yellow-700">En attente</p>
                        <p class="text-2xl font-bold text-yellow-900 mt-1">{{ pendingCount }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                        <p class="text-sm font-medium text-green-700">Complétés</p>
                        <p class="text-2xl font-bold text-green-900 mt-1">{{ completedCount }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                        <p class="text-sm font-medium text-purple-700">Qté transférée (total)</p>
                        <p class="text-2xl font-bold text-purple-900 mt-1">{{ totalQty.toLocaleString('fr-FR') }}</p>
                        <p class="text-xs text-purple-600 mt-0.5">{{ totalBoxes }} boîte(s)</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex flex-wrap gap-3 items-end">
                        <!-- Search -->
                        <div class="relative flex-1 min-w-48">
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
                        <!-- Depot filter -->
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Filtrer par dépôt</label>
                            <select v-model="depotId" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                <option value="">Tous les dépôts</option>
                                <option v-for="depot in depots" :key="depot.id" :value="depot.id">{{ depot.name }}</option>
                            </select>
                        </div>
                        <!-- Date range -->
                        <div class="flex items-center gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Du</label>
                                <input v-model="fromDate" type="date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Au</label>
                                <input v-model="toDate" type="date" :min="fromDate" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            </div>
                            <button v-if="fromDate || toDate" @click="fromDate = ''; toDate = ''"
                                class="mt-5 p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Print button -->
                        <button :disabled="!canPrint" @click="printPeriod"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="canPrint ? 'bg-orange-600 hover:bg-orange-700 text-white shadow-sm' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                            :title="canPrint ? `Imprimer les transferts du ${formatPeriod}` : 'Sélectionnez une période complète pour imprimer'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Imprimer la période
                        </button>
                    </div>
                </div>

                <!-- Transfers Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th @click="setSort('id')" scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-orange-700">
                                        Transfert <span v-if="sort === 'id'">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Destination</th>
                                    <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Produits</th>
                                    <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Boîtes</th>
                                    <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Unités/Boîte</th>
                                    <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-orange-700 uppercase tracking-wider">Qté Transférée</th>
                                    <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-blue-700 uppercase tracking-wider">Qté Restante</th>
                                    <th @click="setSort('performed_at')" scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-orange-700">
                                        Date <span v-if="sort === 'performed_at'">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th @click="setSort('status')" scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer select-none hover:text-orange-700">
                                        Statut <span v-if="sort === 'status'">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th scope="col" class="px-4 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="transfer in transfers.data" :key="transfer.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <div class="w-9 h-9 bg-gradient-to-br from-orange-100 to-orange-200 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">Transfert #{{ transfer.id }}</div>
                                                <div class="text-xs text-gray-500">{{ transfer.user?.name || 'Système' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">{{ transfer.depot?.name || 'N/A' }}</div>
                                        <div class="text-xs text-gray-500">{{ transfer.depot?.address || '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold">
                                            {{ transfer.products ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 ring-1 ring-purple-600/20">
                                            {{ transfer.boxes ?? 0 }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-sm font-medium text-gray-700">{{ transfer.unitsPerBox ?? 0 }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 ring-1 ring-orange-600/20">
                                            {{ (transfer.qtyTransferred ?? 0).toLocaleString('fr-FR') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 ring-1 ring-blue-600/20">
                                            {{ (transfer.qtyRemaining ?? 0).toLocaleString('fr-FR') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-gray-700">{{ formatDate(transfer.created_at) }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1"
                                            :class="getStatusBadgeColor(transfer.status)">
                                            {{ getStatusLabel(transfer.status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-1.5">
                                            <Link :href="route('transfers.show', transfer.uuid)"
                                                class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Détails
                                            </Link>
                                            <button v-if="canManage && transfer.status === 'pending'" @click="cancelTransfer(transfer.uuid)"
                                                class="inline-flex items-center px-2.5 py-1.5 bg-yellow-50 text-yellow-700 text-xs font-medium rounded-lg hover:bg-yellow-100 transition-colors">
                                                Annuler
                                            </button>
                                            <button v-if="canManage" @click="deleteTransfer(transfer.uuid)"
                                                class="inline-flex items-center px-2.5 py-1.5 bg-red-50 text-red-700 text-xs font-medium rounded-lg hover:bg-red-100 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="transfers.data.length === 0">
                                    <td colspan="10" class="px-6 py-16 text-center">
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

        <!-- ── Print Report (visible only on print) ── -->
        <div v-if="isPrinting" class="print-transfers">
            <div class="ptr-header">
                <div>
                    <h1>RAPPORT DES TRANSFERTS</h1>
                    <p class="ptr-period">Période : {{ formatPeriod }}</p>
                    <p v-if="printDepotLabel" class="ptr-period">Dépôt : {{ printDepotLabel }}</p>
                </div>
                <div class="ptr-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Sys E-Dépôt Pharma
                </div>
            </div>

            <!-- Summary cards -->
            <div class="ptr-summary">
                <div class="ptr-card">
                    <div class="ptr-card-label">Transferts</div>
                    <div class="ptr-card-value">{{ transfersForPrint?.length ?? 0 }}</div>
                </div>
                <div class="ptr-card">
                    <div class="ptr-card-label">Produits distincts</div>
                    <div class="ptr-card-value">{{ printTotalProducts }}</div>
                </div>
                <div class="ptr-card">
                    <div class="ptr-card-label">Boîtes transférées</div>
                    <div class="ptr-card-value">{{ printTotalBoxes }}</div>
                </div>
                <div class="ptr-card">
                    <div class="ptr-card-label">Qté transférée</div>
                    <div class="ptr-card-value">{{ printTotalQty.toLocaleString('fr-FR') }}</div>
                </div>
                <div class="ptr-card">
                    <div class="ptr-card-label">Qté restante</div>
                    <div class="ptr-card-value">{{ printTotalRemaining.toLocaleString('fr-FR') }}</div>
                </div>
            </div>

            <div v-for="group in transfersByDay" :key="group.day" class="ptr-day-group">
                <h2 class="ptr-day-title">{{ group.day }}</h2>
                <div v-for="transfer in group.items" :key="transfer.id" class="ptr-transfer">
                    <div class="ptr-transfer-header">
                        <span class="ptr-transfer-id">Transfert #{{ transfer.id }}</span>
                        <span class="ptr-transfer-info">→ {{ transfer.depot?.name ?? 'N/A' }} &nbsp;|&nbsp; {{ transfer.user?.name ?? 'Système' }}</span>
                        <span class="ptr-transfer-status">{{ getStatusLabel(transfer.status) }}</span>
                    </div>
                    <!-- Stats bar -->
                    <div class="ptr-stats-bar">
                        <span><strong>{{ transfer.products }}</strong> produit(s)</span>
                        <span><strong>{{ transfer.boxes }}</strong> boîte(s)</span>
                        <span>Unités/boîte : <strong>{{ transfer.unitsPerBox }}</strong></span>
                        <span class="ptr-stats-transferred">Qté transférée : <strong>{{ (transfer.qtyTransferred ?? 0).toLocaleString('fr-FR') }}</strong></span>
                        <span class="ptr-stats-remaining">Qté restante : <strong>{{ (transfer.qtyRemaining ?? 0).toLocaleString('fr-FR') }}</strong></span>
                    </div>
                    <table class="ptr-table">
                        <thead>
                            <tr>
                                <th>Médicament</th>
                                <th>Forme / Dosage</th>
                                <th>Code-barres</th>
                                <th class="right">Boîtes</th>
                                <th class="right">Unités/Boîte</th>
                                <th class="right">Qté transférée</th>
                                <th class="right">Qté restante</th>
                                <th class="right">Prix unit.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in transfer.items" :key="i">
                                <td>{{ item.drug?.name ?? '—' }}</td>
                                <td>{{ [item.drug?.form_med, item.drug?.dosage_med].filter(Boolean).join(' ') || '—' }}</td>
                                <td class="mono">{{ item.barcode ?? '—' }}</td>
                                <td class="right">{{ item.quantity }}</td>
                                <td class="right">{{ item.quantite_contenu ?? '—' }}</td>
                                <td class="right bold">{{ ((item.quantite_contenu ?? 0) * (item.quantity ?? 1)).toLocaleString('fr-FR') }}</td>
                                <td class="right">{{ (item.quantite_actuelle ?? 0).toLocaleString('fr-FR') }}</td>
                                <td class="right">{{ item.price != null ? Number(item.price).toLocaleString('fr-FR') + ' FCFA' : '—' }}</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" class="right bold">Totaux</td>
                                <td class="right bold">{{ transfer.boxes }}</td>
                                <td class="right bold">{{ transfer.unitsPerBox }}</td>
                                <td class="right bold">{{ (transfer.qtyTransferred ?? 0).toLocaleString('fr-FR') }}</td>
                                <td class="right bold">{{ (transfer.qtyRemaining ?? 0).toLocaleString('fr-FR') }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="ptr-footer">Sys E-Dépôt Pharma — Lumière Afrique Group Sarl — Période : {{ formatPeriod }}</p>
        </div>
    </AppLayout>
</template>

<style scoped>
.print-transfers { display: none; }
.print-transfers.visible { display: block; }
.ptr-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #ea580c; padding-bottom:14px; margin-bottom:20px; font-family:'Segoe UI',Arial,sans-serif; }
.ptr-header h1 { font-size:20px; font-weight:800; color:#9a3412; letter-spacing:.5px; }
.ptr-period { font-size:12px; color:#6b7280; margin-top:4px; }
.ptr-logo { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#ea580c; }
.ptr-logo svg { width:22px; height:22px; }
.ptr-summary { display:flex; gap:10px; margin-bottom:22px; flex-wrap:wrap; }
.ptr-card { flex:1; min-width:80px; padding:12px; border-radius:8px; background:#fff7ed; border:1px solid #fed7aa; text-align:center; font-family:'Segoe UI',Arial,sans-serif; }
.ptr-card-label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#374151; margin-bottom:4px; }
.ptr-card-value { font-size:22px; font-weight:800; color:#c2410c; }
.ptr-day-group { margin-bottom:24px; }
.ptr-day-title { font-size:13px; font-weight:700; text-transform:capitalize; color:#9a3412; background:#fff7ed; border-left:4px solid #ea580c; padding:6px 10px; margin-bottom:10px; font-family:'Segoe UI',Arial,sans-serif; }
.ptr-transfer { margin-bottom:16px; }
.ptr-transfer-header { display:flex; align-items:center; gap:12px; font-size:11px; font-family:'Segoe UI',Arial,sans-serif; margin-bottom:4px; }
.ptr-transfer-id { font-weight:700; color:#374151; }
.ptr-transfer-info { color:#6b7280; flex:1; }
.ptr-transfer-status { font-size:10px; font-weight:600; padding:2px 8px; border-radius:999px; background:#e5e7eb; color:#374151; }
.ptr-stats-bar { display:flex; gap:16px; font-size:11px; font-family:'Segoe UI',Arial,sans-serif; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:6px 10px; margin-bottom:6px; flex-wrap:wrap; }
.ptr-stats-transferred { color:#c2410c; }
.ptr-stats-remaining { color:#1d4ed8; }
.ptr-table { width:100%; border-collapse:collapse; margin-bottom:6px; font-family:'Segoe UI',Arial,sans-serif; font-size:10.5px; }
.ptr-table th { background:#c2410c; color:white; padding:5px 7px; text-align:left; font-size:9.5px; text-transform:uppercase; }
.ptr-table td { padding:4px 7px; border-bottom:1px solid #e5e7eb; }
.ptr-table tbody tr:nth-child(even) td { background:#fafafa; }
.ptr-table .total-row td { border-top:2px solid #ea580c; background:#fff7ed; font-weight:700; }
.right { text-align:right; }
.bold { font-weight:700; }
.mono { font-family:monospace; font-size:10px; }
.ptr-footer { text-align:center; font-size:10px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:10px; margin-top:16px; font-family:'Segoe UI',Arial,sans-serif; }
</style>

<style>
@media print {
    /* Hide everything except print container */
    body.printing-transfers * { visibility: hidden !important; }
    body.printing-transfers .print-transfers,
    body.printing-transfers .print-transfers * { visibility: visible !important; }

    /* Print container - allow natural flow across pages */
    body.printing-transfers .print-transfers {
        position: static !important;
        width: 100% !important;
        max-width: 100% !important;
        background: white !important;
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
    }

    /* Page setup */
    @page {
        size: auto;
        margin: 12mm 10mm;
    }

    body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Page break controls */
    .ptr-day-group {
        page-break-inside: avoid;
        break-inside: avoid;
        margin-bottom: 20px !important;
    }

    .ptr-transfer {
        page-break-inside: avoid;
        break-inside: avoid;
        margin-bottom: 16px !important;
    }

    /* Force page break before each day group except first */
    .ptr-day-group + .ptr-day-group {
        page-break-before: auto;
        break-before: auto;
    }

    /* Table handling */
    .ptr-table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        font-size: 9px !important;
    }

    .ptr-table th,
    .ptr-table td {
        padding: 3px 5px !important;
        overflow-wrap: break-word;
        word-wrap: break-word;
    }

    /* Ensure tables don't break inside */
    .ptr-table {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    /* Header and footer positioning */
    .ptr-header {
        page-break-after: avoid;
        break-after: avoid;
        margin-bottom: 15px !important;
    }

    .ptr-footer {
        page-break-before: auto;
        break-before: auto;
        margin-top: 20px !important;
        position: static !important;
    }

    /* Summary cards */
    .ptr-summary {
        page-break-inside: avoid;
        break-inside: avoid;
        page-break-after: avoid;
        break-after: avoid;
        margin-bottom: 15px !important;
    }

    .ptr-card {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    /* Transfer title */
    .ptr-day-title {
        page-break-after: avoid;
        break-after: avoid;
        margin-bottom: 8px !important;
    }

    /* Stats bar */
    .ptr-stats-bar {
        page-break-inside: avoid;
        break-inside: avoid;
        page-break-after: avoid;
        break-after: avoid;
    }

    /* Prevent orphans/widows */
    .ptr-transfer-header,
    .ptr-table thead {
        page-break-after: avoid;
        break-after: avoid;
    }
}
</style>
