<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    transactions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    userDepotStat: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const activePharmacy = computed(() => page.props.auth?.active_pharmacy ?? null);
const roles = computed(() => page.props.auth?.roles ?? []);
const isSuperAdmin = computed(() => roles.value.includes('super_admin'));

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

// ── Receipt print ──
const printingTx = ref(null);

const openReceipt = (tx) => {
    printingTx.value = tx;
    document.body.classList.add('printing-receipt');
    setTimeout(() => window.print(), 80);
};

const onAfterPrint = () => {
    printingTx.value = null;
    document.body.classList.remove('printing-receipt');
};
window.addEventListener('afterprint', onAfterPrint);
onBeforeUnmount(() => window.removeEventListener('afterprint', onAfterPrint));
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
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-700">Aujourd'hui</p>
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
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-700">Total Ventes</p>
                                <p class="text-2xl font-bold text-blue-900 mt-1">{{ txData.length }}</p>
                                <p class="text-xs text-blue-600 mt-1">transactions</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-700">Revenu Total</p>
                                <p class="text-2xl font-bold text-purple-900 mt-1">{{ totalRevenue.toLocaleString('fr-FR') }}</p>
                                <p class="text-xs text-purple-600 mt-1">FCFA</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4 border border-orange-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-orange-700">Moy. / Transaction</p>
                                <p class="text-2xl font-bold text-orange-900 mt-1">{{ Math.round(averageSale).toLocaleString('fr-FR') }}</p>
                                <p class="text-xs text-orange-600 mt-1">FCFA</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-200 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                        </div>
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
    </AppLayout>
</template>

<style scoped>
.receipt-print { display: none; }
</style>

<style>
@media print {
    body.printing-receipt * { visibility: hidden !important; }
    body.printing-receipt .receipt-print,
    body.printing-receipt .receipt-print * { visibility: visible !important; }
    .receipt-print {
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
    .receipt-print .receipt-header { text-align: center; margin-bottom: 6px; }
    .receipt-print .receipt-logo { font-size: 24px; margin-bottom: 4px; }
    .receipt-print .receipt-brand { font-size: 14px; font-weight: 700; }
    .receipt-print .receipt-sub { font-size: 10px; color: #555; margin-bottom: 4px; }
    .receipt-print .receipt-meta { font-size: 10px; display: flex; flex-direction: column; gap: 2px; margin: 4px 0; }
    .receipt-print .receipt-divider { color: #999; font-size: 10px; text-align: center; margin: 4px 0; }
    .receipt-print .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    .receipt-print .receipt-table th { font-size: 10px; font-weight: 700; text-transform: uppercase; border-bottom: 1px dashed #ccc; padding: 2px 3px; text-align: left; }
    .receipt-print .receipt-table td { padding: 3px; vertical-align: top; }
    .receipt-print .item-name { font-weight: 600; font-size: 11px; }
    .receipt-print .item-barcode { font-size: 9px; color: #666; }
    .receipt-print .receipt-total { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; margin: 4px 0; }
    .receipt-print .receipt-footer { text-align: center; font-size: 11px; margin-top: 8px; }
    .receipt-print .text-center { text-align: center; }
    .receipt-print .text-right { text-align: right; }
}
</style>
