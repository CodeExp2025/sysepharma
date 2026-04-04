<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    requests: Object,
    availableStock: { type: Array, default: () => [] },
    soldToday: { type: Array, default: () => [] },
    reportDate: { type: String, default: '' },
    filters: Object,
});

const search = ref(props.filters?.search || '');
const sort = ref(props.filters?.sort || 'created_at');
const direction = ref(props.filters?.direction || 'desc');

const applyFilters = () => {
    router.get(route('stock-requests.index'), {
        search: search.value || undefined,
        sort: sort.value,
        direction: direction.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
};

const debouncedSearch = debounce(applyFilters, 300);
watch(search, () => debouncedSearch());

const setSort = (column) => {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = 'asc';
    }
    applyFilters();
};

const sortIcon = (column) => {
    if (sort.value !== column) return '↕';
    return direction.value === 'asc' ? '↑' : '↓';
};

const getStatusClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        default: return 'bg-yellow-100 text-yellow-800';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'approved': return 'Approuvée';
        case 'rejected': return 'Rejetée';
        case 'completed': return 'Complétée';
        case 'pending': return 'En attente';
        default: return status;
    }
};

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
    document.body.classList.add('printing-stock-requests');
    setTimeout(() => window.print(), 80);
};

const onAfterPrint = () => {
    isPrinting.value = false;
    document.body.classList.remove('printing-stock-requests');
};
window.addEventListener('afterprint', onAfterPrint);
onBeforeUnmount(() => window.removeEventListener('afterprint', onAfterPrint));
</script>

<template>
    <Head title="Demandes de Stock" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Demandes de Stock</h2>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        @click="printReport"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Rapport du jour
                    </button>
                    <Link
                        :href="route('stock-requests.create')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Nouvelle Demande
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Search Bar -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Rechercher par demandeur, dépôt ou statut..."
                                class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    <div class="p-6 text-gray-900">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100" @click="setSort('id')">
                                            ID <span class="ml-1">{{ sortIcon('id') }}</span>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100" @click="setSort('user_name')">
                                            Demandeur <span class="ml-1">{{ sortIcon('user_name') }}</span>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100" @click="setSort('depot_name')">
                                            Dépôt <span class="ml-1">{{ sortIcon('depot_name') }}</span>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100" @click="setSort('status')">
                                            Statut <span class="ml-1">{{ sortIcon('status') }}</span>
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer select-none hover:bg-gray-100" @click="setSort('created_at')">
                                            Date <span class="ml-1">{{ sortIcon('created_at') }}</span>
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="req in requests.data" :key="req.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #{{ req.id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ req.user.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ req.depot ? req.depot.name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusClass(req.status)]">
                                                {{ getStatusLabel(req.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(req.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('stock-requests.show', req.uuid)" class="text-indigo-600 hover:text-indigo-900">
                                                Voir
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="requests.data.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Aucune demande trouvée.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Print Report (only visible on print) ── -->
        <div v-if="isPrinting" class="print-report">
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
.print-report { display: none; }
.pr-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 3px solid #2563eb; padding-bottom: 16px; margin-bottom: 24px;
    font-family: 'Segoe UI', Arial, sans-serif;
}
.pr-header h1 { font-size: 20px; font-weight: 800; color: #1e40af; letter-spacing: 0.5px; }
.pr-date { font-size: 12px; color: #6b7280; margin-top: 4px; }
.pr-logo { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; color: #2563eb; }
.pr-logo svg { width: 24px; height: 24px; }
.pr-summary { display: flex; gap: 16px; margin-bottom: 28px; }
.pr-card { flex: 1; padding: 16px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; text-align: center; font-family: 'Segoe UI', Arial, sans-serif; }
.pr-card.sold { background: #fef9c3; border-color: #fde68a; }
.pr-card.revenue { background: #f0fdf4; border-color: #bbf7d0; }
.pr-card-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #374151; margin-bottom: 6px; }
.pr-card-value { font-size: 28px; font-weight: 800; color: #1e40af; }
.pr-card.sold .pr-card-value { color: #92400e; }
.pr-card.revenue .pr-card-value { color: #166534; }
.pr-card-sub { font-size: 10px; color: #6b7280; margin-top: 2px; }
.pr-section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #1e40af; margin: 20px 0 8px; border-bottom: 1px solid #bfdbfe; padding-bottom: 4px; font-family: 'Segoe UI', Arial, sans-serif; }
.pr-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; }
.pr-table th { background: #1e40af; color: white; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
.pr-table td { padding: 7px 8px; border-bottom: 1px solid #e5e7eb; }
.pr-table tbody tr:nth-child(even) td { background: #f9fafb; }
.pr-table .total-row td { border-top: 2px solid #2563eb; background: #eff6ff; font-weight: 700; }
.center { text-align: center; }
.right { text-align: right; }
.bold { font-weight: 700; }
.empty { text-align: center; color: #9ca3af; font-style: italic; padding: 16px; }
.pr-footer { text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 16px; font-family: 'Segoe UI', Arial, sans-serif; }
</style>

<style>
@media print {
    body.printing-stock-requests * { visibility: hidden !important; }
    body.printing-stock-requests .print-report,
    body.printing-stock-requests .print-report * { visibility: visible !important; }
    body.printing-stock-requests .print-report {
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
