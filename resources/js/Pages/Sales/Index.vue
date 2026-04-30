<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    transactions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    disbursements: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    disbursementStats: {
        type: Object,
        default: () => ({ total: 0, count: 0, today_total: 0, today_count: 0 }),
    },
    userDepotStat: {
        type: Boolean,
        default: false,
    },
    filters: Object,
});

const page = usePage();
const activePharmacy = computed(() => page.props.auth?.active_pharmacy ?? null);
const roles = computed(() => page.props.auth?.roles ?? []);
const isSuperAdmin = computed(() => roles.value.includes('super_admin'));

const search = ref(props.filters?.search || '');
const debouncedSearch = debounce(() => {
    router.get(route('sales.index'), { search: search.value || undefined }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);
watch(search, () => debouncedSearch());

// Show financials (Total column, prices) for super_admin, pharmacy_admin, pharmacy_staff — or when depot has stats enabled
const canSeeFinancials = computed(() =>
    props.userDepotStat
    || roles.value.includes('super_admin')
    || roles.value.includes('pharmacy_admin')
    || roles.value.includes('pharmacy_staff')
);

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

// ── Stats cards ──
const txData = computed(() => props.transactions?.data ?? []);
const dispData = computed(() => props.disbursements?.data ?? []);

const totalRevenue = computed(() =>
    txData.value.reduce((s, tx) => s + (parseFloat(tx.total) || 0), 0)
);
const todayTx = computed(() => {
    const today = new Date().toDateString();
    return txData.value.filter(tx => new Date(tx.created_at).toDateString() === today);
});
const todaySalesCount = computed(() => todayTx.value.length);
const todayRevenue    = computed(() => todayTx.value.reduce((s, tx) => s + (parseFloat(tx.total) || 0), 0));
const averageSale     = computed(() => txData.value.length ? totalRevenue.value / txData.value.length : 0);

// ── Disbursement Stats ──
const totalDisbursements = computed(() => props.disbursementStats?.total || 0);
const todayDisbursements = computed(() => props.disbursementStats?.today_total || 0);
const netRevenue = computed(() => totalRevenue.value - totalDisbursements.value);
const todayNetRevenue = computed(() => todayRevenue.value - todayDisbursements.value);
const showDisbursementDetails = ref(false);

// ── Receipt print ──
const printingTx = ref(null);

const openReceipt = (tx) => {
    printingTx.value = tx;
    document.body.classList.add('printing-receipt');
    setTimeout(() => window.print(), 80);
};

const onAfterPrint = () => {
    printingTx.value = null;
    printingDisbursement.value = null;
    document.body.classList.remove('printing-receipt');
    document.body.classList.remove('printing-disbursement');
};
window.addEventListener('afterprint', onAfterPrint);
onBeforeUnmount(() => window.removeEventListener('afterprint', onAfterPrint));

// ── Disbursement print ──
const printingDisbursement = ref(null);

const openDisbursementReceipt = (d) => {
    printingDisbursement.value = d;
    document.body.classList.add('printing-disbursement');
    setTimeout(() => window.print(), 80);
};
</script>

<template>
    <AppLayout title="Historique des Ventes">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">Historique des Ventes</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        <span v-if="activePharmacy && !isSuperAdmin">Ventes de <strong>{{ activePharmacy.name }}</strong></span>
                        <span v-else>Suivi et analyse des ventes réalisées</span>
                    </p>
                </div>
                <Link :href="route('sales.create')"
                    class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg font-semibold text-sm text-white shadow-md hover:from-blue-700 hover:to-blue-800 transition-all duration-200">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        Point de Vente
                    </span>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Active Pharmacy Banner -->
                <div v-if="activePharmacy && !isSuperAdmin"
                    class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-5 py-3 mb-6">
                    <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-sm text-green-800">Pharmacie active : <strong>{{ activePharmacy.name }}</strong> — seules les ventes de cette pharmacie sont affichées.</span>
                </div>

                <!-- Stats Cards -->
                <div v-if="canSeeFinancials" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <!-- Ventes Aujourd'hui -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700">Ventes Aujourd'hui</p>
                                <p class="text-2xl font-bold text-green-900 mt-1">{{ todaySalesCount }}</p>
                                <p class="text-xs text-green-600 mt-1">{{ todayRevenue.toLocaleString('fr-FR') }} FCFA</p>
                            </div>
                            <div class="w-12 h-12 bg-green-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!-- Total Ventes -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-700">Total Ventes</p>
                                <p class="text-2xl font-bold text-blue-900 mt-1">{{ txData.length }}</p>
                                <p class="text-xs text-blue-600 mt-1">{{ totalRevenue.toLocaleString('fr-FR') }} FCFA</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <!-- Décaissements -->
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 border border-red-200 cursor-pointer hover:shadow-md transition-shadow"
                         @click="showDisbursementDetails = !showDisbursementDetails">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-red-700">Décaissements</p>
                                <p class="text-2xl font-bold text-red-900 mt-1">{{ dispData.length }}</p>
                                <p class="text-xs text-red-600 mt-1">-{{ totalDisbursements.toLocaleString('fr-FR') }} FCFA</p>
                            </div>
                            <div class="w-12 h-12 bg-red-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0zM5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-red-500 mt-2 flex items-center gap-1">
                            <span v-if="showDisbursementDetails">▼ Masquer les détails</span>
                            <span v-else>▶ Cliquer pour voir les détails</span>
                        </p>
                    </div>
                    <!-- Revenu Net -->
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4 border border-emerald-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-emerald-700">Revenu Net</p>
                                <p class="text-2xl font-bold text-emerald-900 mt-1">{{ netRevenue.toLocaleString('fr-FR') }}</p>
                                <p class="text-xs text-emerald-600 mt-1">FCFA (Ventes - Décaissements)</p>
                            </div>
                            <div class="w-12 h-12 bg-emerald-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 36v-3m-3 3h.01M9 17h.01M9 14h.01M12 3v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-xs">
                            <span class="text-emerald-600">Aujourd'hui: {{ todayNetRevenue.toLocaleString('fr-FR') }} FCFA</span>
                        </div>
                    </div>
                </div>

                <!-- Résumé Financier -->
                <div v-if="canSeeFinancials" class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200 mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Résumé Financier</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="flex justify-between items-center p-2 bg-white rounded-lg">
                            <span class="text-gray-600">Total Ventes (brut)</span>
                            <span class="font-semibold text-green-700">+{{ totalRevenue.toLocaleString('fr-FR') }} FCFA</span>
                        </div>
                        <div class="flex justify-between items-center p-2 bg-white rounded-lg">
                            <span class="text-gray-600">Total Décaissements</span>
                            <span class="font-semibold text-red-700">-{{ totalDisbursements.toLocaleString('fr-FR') }} FCFA</span>
                        </div>
                        <div class="flex justify-between items-center p-2 bg-emerald-50 rounded-lg border border-emerald-200">
                            <span class="text-gray-700 font-medium">Revenu Net</span>
                            <span class="font-bold text-emerald-800">={{ netRevenue.toLocaleString('fr-FR') }} FCFA</span>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-500">
                        <div class="text-center">
                            Aujourd'hui: <span class="font-medium text-green-600">+{{ todayRevenue.toLocaleString('fr-FR') }} FCFA</span>
                        </div>
                        <div class="text-center">
                            Décaissements: <span class="font-medium text-red-600">-{{ todayDisbursements.toLocaleString('fr-FR') }} FCFA</span>
                        </div>
                        <div class="text-center">
                            Net Aujourd'hui: <span class="font-medium text-emerald-600">={{ todayNetRevenue.toLocaleString('fr-FR') }} FCFA</span>
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
                        <input v-model="search" type="text" placeholder="Rechercher par médicament, vendeur, dépôt..."
                            class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button v-if="search" @click="search = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Transaction</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Articles vendus</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date & Heure</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Vendeur</th>
                                    <th v-if="canSeeFinancials" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="tx in txData" :key="tx.transaction_id"
                                    class="hover:bg-gray-50 transition-colors duration-150">
                                    <!-- Transaction ref + depot -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-xs font-mono text-gray-500 uppercase">{{ String(tx.transaction_id).slice(0, 8) }}</div>
                                                <div class="text-xs text-indigo-600 font-medium mt-0.5">{{ tx.depot?.name ?? '—' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Items list -->
                                    <td class="px-6 py-4">
                                        <div class="space-y-1">
                                            <div v-for="item in tx.items" :key="item.id" class="flex items-center gap-2 text-xs">
                                                <span class="font-medium text-gray-800">{{ item.drug_unit?.drug?.name ?? '—' }}</span>
                                                <span class="px-1.5 py-0.5 bg-gray-100 rounded text-gray-500">×{{ item.quantity }}</span>
                                                <span v-if="canSeeFinancials" class="text-gray-400">{{ parseFloat(item.price).toLocaleString('fr-FR') }} FCFA</span>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Date -->
                                    <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                        {{ formatDate(tx.created_at) }}
                                    </td>
                                    <!-- Seller -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-br from-purple-100 to-purple-200 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
                                                <span class="text-purple-700 font-bold text-xs">
                                                    {{ (tx.seller?.name || 'S').substring(0, 2).toUpperCase() }}
                                                </span>
                                            </div>
                                            <span class="text-sm text-gray-700">{{ tx.seller?.name ?? 'Système' }}</span>
                                        </div>
                                    </td>
                                    <!-- Total -->
                                    <td v-if="canSeeFinancials" class="px-6 py-4">
                                        <div class="text-sm font-bold text-green-700">
                                            {{ parseFloat(tx.total).toLocaleString('fr-FR') }}
                                            <span class="text-xs font-normal text-gray-500">FCFA</span>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ tx.items_count }} art. · {{ tx.total_qty }} unité(s)</div>
                                    </td>
                                    <!-- Receipt button -->
                                    <td class="px-4 py-4">
                                        <button @click="openReceipt(tx)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-600 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors"
                                            title="Imprimer le reçu">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                            Reçu
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="txData.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 font-medium">Aucune vente enregistrée</p>
                                            <p class="text-gray-400 text-sm mt-1">Commencez à vendre pour voir l'historique</p>
                                            <Link :href="route('sales.create')"
                                                class="mt-4 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                                Aller au POS
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200"
                        v-if="transactions?.links && txData.length > 0">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                Affichage de <span class="font-semibold">{{ txData.length }}</span> transaction(s)
                            </div>
                            <div class="flex gap-1">
                                <template v-for="(link, key) in transactions.links" :key="key">
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

                <!-- Décaissements Section -->
                <div v-if="showDisbursementDetails && canSeeFinancials" class="mt-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0zM5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Historique des Décaissements
                        </h3>
                        <Link :href="route('disbursements.index')"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Voir tout →
                        </Link>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gradient-to-r from-red-50 to-red-100">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-red-700 uppercase tracking-wider">Référence</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-red-700 uppercase tracking-wider">Désignation</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-red-700 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-red-700 uppercase tracking-wider">Initié par</th>
                                        <th class="px-6 py-4 text-left text-xs font-bold text-red-700 uppercase tracking-wider">Total</th>
                                        <th class="px-6 py-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    <tr v-for="d in dispData" :key="d.id"
                                        class="hover:bg-red-50/50 transition-colors duration-150">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-br from-red-100 to-red-200 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                    </svg>
                                                </div>
                                                <div class="text-xs font-mono text-gray-500 uppercase">{{ String(d.uuid).slice(0, 8) }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="space-y-1">
                                                <div v-for="item in d.items.slice(0, 2)" :key="item.id" class="text-xs text-gray-700">
                                                    • {{ item.designation }}
                                                </div>
                                                <div v-if="d.items.length > 2" class="text-xs text-gray-400">
                                                    +{{ d.items.length - 2 }} autres...
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                            {{ formatDate(d.performed_at) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-gradient-to-br from-red-100 to-red-200 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
                                                    <span class="text-red-700 font-bold text-xs">
                                                        {{ (d.initiator?.name || 'U').substring(0, 2).toUpperCase() }}
                                                    </span>
                                                </div>
                                                <span class="text-sm text-gray-700">{{ d.initiator?.name ?? '—' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-red-700">
                                                -{{ parseFloat(d.total).toLocaleString('fr-FR') }}
                                                <span class="text-xs font-normal text-gray-500">FCFA</span>
                                            </div>
                                            <div class="text-xs text-gray-400 mt-0.5">{{ d.items_count }} article(s)</div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <button @click="openDisbursementReceipt(d)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors"
                                                title="Imprimer le reçu">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                                </svg>
                                                Reçu
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="dispData.length === 0">
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <p class="text-gray-500">Aucun décaissement enregistré</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Disbursements Pagination -->
                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200"
                            v-if="disbursements?.links && dispData.length > 0">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    Affichage de <span class="font-semibold">{{ dispData.length }}</span> décaissement(s)
                                </div>
                                <div class="flex gap-1">
                                    <template v-for="(link, key) in disbursements.links" :key="key">
                                        <div v-if="link.url === null"
                                            class="px-3 py-2 text-sm text-gray-400 border border-gray-200 rounded-lg bg-white cursor-not-allowed"
                                            v-html="link.label" />
                                        <Link v-else
                                            class="px-3 py-2 text-sm border rounded-lg transition-all duration-150 hover:shadow-sm"
                                            :class="link.active
                                                ? 'bg-red-600 text-white border-red-600 font-semibold shadow-sm'
                                                : 'bg-white text-gray-700 border-gray-200 hover:border-red-300 hover:text-red-600'"
                                            :href="link.url"
                                            v-html="link.label" />
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Print receipt overlay ── -->
        <div v-if="printingTx" class="receipt-print">
            <div class="receipt-header">
                <div class="receipt-logo">⚕</div>
                <div class="receipt-brand">{{ activePharmacy?.name ?? (printingTx.depot?.name ?? 'Pharmacie') }}</div>
                <div class="receipt-sub">Sys E-Dépôt Pharma — Lumière Afrique Group Sarl</div>
                <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
                <div class="receipt-meta">
                    <span>Réf : {{ String(printingTx.transaction_id).slice(0, 8).toUpperCase() }}</span>
                    <span>Date : {{ formatDate(printingTx.created_at) }}</span>
                    <span>Vendeur : {{ printingTx.seller?.name ?? '—' }}</span>
                    <span v-if="printingTx.depot">Dépôt : {{ printingTx.depot.name }}</span>
                </div>
                <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
            </div>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="text-center">Qté</th>
                        <th class="text-right">P.U.</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in printingTx.items" :key="item.id">
                        <td>
                            <div class="item-name">{{ item.drug_unit?.drug?.name ?? '—' }}</div>
                            <div class="item-barcode">{{ item.drug_unit?.barcode }}</div>
                        </td>
                        <td class="text-center">{{ item.quantity }}</td>
                        <td class="text-right">{{ (parseFloat(item.price) / item.quantity).toLocaleString('fr-FR') }}</td>
                        <td class="text-right">{{ parseFloat(item.price).toLocaleString('fr-FR') }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-total">
                <span>TOTAL</span>
                <span>{{ parseFloat(printingTx.total).toLocaleString('fr-FR') }} FCFA</span>
            </div>
            <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-footer">Merci de votre confiance !</div>
        </div>

        <!-- ── Disbursement print overlay ── -->
        <div v-if="printingDisbursement" class="disbursement-print">
            <div class="receipt-header">
                <div class="receipt-logo">📄</div>
                <div class="receipt-brand">{{ activePharmacy?.name ?? 'Pharmacie' }}</div>
                <div class="receipt-sub">Sys E-Dépôt Pharma — Lumière Afrique Group Sarl</div>
                <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
                <div class="receipt-title">DÉCAISSEMENT</div>
                <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
                <div class="receipt-meta">
                    <span>Réf : {{ String(printingDisbursement.uuid).slice(0, 8).toUpperCase() }}</span>
                    <span>Date : {{ formatDate(printingDisbursement.performed_at) }}</span>
                    <span>Par : {{ printingDisbursement.initiator?.name ?? '—' }}</span>
                </div>
                <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
            </div>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th class="text-center">Qté</th>
                        <th class="text-right">P.U.</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in printingDisbursement.items" :key="item.id">
                        <td>
                            <div class="item-name">{{ item.designation }}</div>
                        </td>
                        <td class="text-center">{{ item.quantite }}</td>
                        <td class="text-right">{{ parseFloat(item.prix_unitaire).toLocaleString('fr-FR') }}</td>
                        <td class="text-right">{{ (item.quantite * item.prix_unitaire).toLocaleString('fr-FR') }}</td>
                    </tr>
                </tbody>
            </table>

            <div v-if="printingDisbursement.notes" class="receipt-notes">
                <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
                <div class="notes-label">Notes:</div>
                <div class="notes-content">{{ printingDisbursement.notes }}</div>
            </div>

            <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-total disbursement-total">
                <span>TOTAL DÉCAISSEMENT</span>
                <span>-{{ parseFloat(printingDisbursement.total).toLocaleString('fr-FR') }} FCFA</span>
            </div>
            <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-footer">
                <div>Signature:</div>
                <div class="signature-line">_________________</div>
                <div class="mt-2">Merci !</div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.receipt-print { display: none; }
.disbursement-print { display: none; }
</style>

<style>
@media print {
    body.printing-receipt * { visibility: hidden !important; }
    body.printing-receipt .receipt-print,
    body.printing-receipt .receipt-print * { visibility: visible !important; }
    body.printing-disbursement * { visibility: hidden !important; }
    body.printing-disbursement .disbursement-print,
    body.printing-disbursement .disbursement-print * { visibility: visible !important; }
    .receipt-print, .disbursement-print {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 72mm !important;
        padding: 4mm !important;
        font-family: 'Courier New', monospace !important;
        font-size: 12px !important;
        color: #000 !important;
        background: white !important;
        display: block !important;
    }
    .receipt-print .receipt-header, .disbursement-print .receipt-header { text-align: center; margin-bottom: 6px; }
    .receipt-print .receipt-logo, .disbursement-print .receipt-logo { font-size: 24px; margin-bottom: 4px; }
    .receipt-print .receipt-brand, .disbursement-print .receipt-brand { font-size: 14px; font-weight: 700; }
    .receipt-print .receipt-sub, .disbursement-print .receipt-sub { font-size: 10px; color: #555; margin-bottom: 4px; }
    .receipt-print .receipt-title { font-size: 12px; font-weight: 700; text-transform: uppercase; color: #dc2626; margin: 4px 0; }
    .receipt-print .receipt-meta, .disbursement-print .receipt-meta { font-size: 10px; display: flex; flex-direction: column; gap: 2px; margin: 4px 0; }
    .receipt-print .receipt-divider, .disbursement-print .receipt-divider { color: #999; font-size: 10px; text-align: center; margin: 4px 0; }
    .receipt-print .receipt-table, .disbursement-print .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    .receipt-print .receipt-table th, .disbursement-print .receipt-table th { font-size: 10px; font-weight: 700; text-transform: uppercase; border-bottom: 1px dashed #ccc; padding: 2px 3px; text-align: left; }
    .receipt-print .receipt-table td, .disbursement-print .receipt-table td { padding: 3px; vertical-align: top; }
    .receipt-print .item-name, .disbursement-print .item-name { font-weight: 600; font-size: 11px; }
    .receipt-print .item-barcode { font-size: 9px; color: #666; }
    .receipt-print .receipt-total, .disbursement-print .receipt-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; margin: 4px 0; }
    .disbursement-print .disbursement-total { color: #dc2626; }
    .receipt-print .receipt-footer, .disbursement-print .receipt-footer { text-align: center; font-size: 11px; margin-top: 8px; }
    .disbursement-print .receipt-footer .signature-line { margin-top: 8px; color: #666; }
    .disbursement-print .receipt-footer .notes-label { font-weight: 700; margin-bottom: 2px; }
    .disbursement-print .receipt-footer .notes-content { font-size: 9px; color: #555; font-style: italic; }
    .receipt-print .text-center, .disbursement-print .text-center { text-align: center; }
    .receipt-print .text-right, .disbursement-print .text-right { text-align: right; }
}
</style>
