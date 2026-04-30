<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    pharmacy:            Object,
    depots:              Array,
    stockByDrug:         Array,
    stockPerDepot:       Object,
    dailyRevenue:        Array,
    dailyDisbursements:  Array,
    topDrugs:            Array,
    topSellers:          Array,
    topDisbursers:       Array,
    nearExpiry:          Array,
    transferStats:       Array,
    transferQtys:        Object,
    totals:              Object,
    filters:             Object,
});

const page = usePage();
const roles = computed(() => page.props.auth?.roles ?? []);
const isSuperAdmin = computed(() => roles.value.includes('super_admin'));

// ── Date range filter ──────────────────────────────────────────────────────
const from = ref(props.filters?.from ?? '');
const to   = ref(props.filters?.to   ?? '');

function applyFilters() {
    router.get(route('stats.index'), { from: from.value, to: to.value }, { preserveScroll: true });
}

// ── Print ──────────────────────────────────────────────────────────────────
const isPrinting = ref(false);

function printPage() {
    isPrinting.value = true;
    document.body.classList.add('printing-stats');
    setTimeout(() => window.print(), 80);
}

const onAfterPrint = () => {
    isPrinting.value = false;
    document.body.classList.remove('printing-stats');
};
window.addEventListener('afterprint', onAfterPrint);
onBeforeUnmount(() => window.removeEventListener('afterprint', onAfterPrint));

// ── Pagination ─────────────────────────────────────────────────────────────
const ITEMS_PER_PAGE = 25;

const stockPage = ref(1);
const revenuePage = ref(1);
const topDrugsPage = ref(1);
const topSellersPage = ref(1);
const topDisbursersPage = ref(1);
const nearExpiryPage = ref(1);
const transfersPage = ref(1);

function paginatedData(data, page) {
    if (!data || !data.length) return { items: [], totalPages: 0, start: 0, end: 0, total: 0 };
    const total = data.length;
    const totalPages = Math.ceil(total / ITEMS_PER_PAGE);
    const currentPage = Math.min(Math.max(1, page), totalPages || 1);
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end = Math.min(start + ITEMS_PER_PAGE, total);
    return { items: data.slice(start, end), totalPages, start: start + 1, end, total, currentPage };
}

function changePage(pageRef, newPage, totalPages) {
    if (newPage >= 1 && newPage <= totalPages) {
        pageRef.value = newPage;
    }
}

// ── Formatters ─────────────────────────────────────────────────────────────
function fmt(n) {
    return Number(n ?? 0).toLocaleString('fr-FR', { minimumFractionDigits: 0 });
}
function fmtMoney(n) {
    return Number(n ?? 0).toLocaleString('fr-FR', { minimumFractionDigits: 0 }) + ' FCFA';
}
function fmtDate(d) {
    if (!d) return '';
    const date = new Date(d);
    const hasTime = String(d).includes('T') || String(d).includes(':');
    if (hasTime) {
        return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    }
    return date.toLocaleDateString('fr-FR');
}
function daysUntil(date) {
    const diff = new Date(date) - new Date();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

// ── Grouped daily revenue by date ──────────────────────────────────────────
const revenueByDate = computed(() => {
    const map = {};
    for (const row of (props.dailyRevenue ?? [])) {
        if (!map[row.date]) {
            map[row.date] = { date: row.date, total: 0, disbursement_total: 0, net_revenue: 0, depots: {} };
        }
        map[row.date].total += Number(row.revenue ?? 0);
        map[row.date].depots[row.depot_id] = {
            depot_id: row.depot_id,
            depot_name: row.depot_name,
            revenue: Number(row.revenue ?? 0),
            sales_count: row.sales_count,
            disbursement: 0,
            disbursement_count: 0,
        };
    }

    // Add disbursements
    for (const row of (props.dailyDisbursements ?? [])) {
        if (!map[row.date]) {
            map[row.date] = { date: row.date, total: 0, disbursement_total: 0, net_revenue: 0, depots: {} };
        }
        map[row.date].disbursement_total += Number(row.disbursement_total ?? 0);
        if (map[row.date].depots[row.depot_id]) {
            map[row.date].depots[row.depot_id].disbursement = Number(row.disbursement_total ?? 0);
            map[row.date].depots[row.depot_id].disbursement_count = row.disbursement_count ?? 0;
        } else {
            map[row.date].depots[row.depot_id] = {
                depot_id: row.depot_id,
                depot_name: row.depot_name,
                revenue: 0,
                sales_count: 0,
                disbursement: Number(row.disbursement_total ?? 0),
                disbursement_count: row.disbursement_count ?? 0,
            };
        }
    }

    // Calculate net revenue per day
    for (const date in map) {
        map[date].net_revenue = map[date].total - map[date].disbursement_total;
    }

    return Object.values(map).sort((a, b) => b.date.localeCompare(a.date));
});

// ── Transfer stats enriched with quantities ─────────────────────────────────
const transfersEnriched = computed(() =>
    (props.transferStats ?? []).map(t => ({
        ...t,
        total_qty: props.transferQtys?.[t.id] ?? 0,
    }))
);

// ── Near-expiry enriched with location label ────────────────────────────────
const nearExpiryEnriched = computed(() => {
    const depotMap = {};
    for (const d of (props.depots ?? [])) depotMap[d.id] = d.name;
    return (props.nearExpiry ?? []).map(du => ({
        ...du,
        location_label: du.current_location_type === 'pharmacy'
            ? (props.pharmacy?.name ?? 'Pharmacie')
            : (depotMap[du.current_location_id] ?? `Dépôt #${du.current_location_id}`),
        days_left: daysUntil(du.expiration_date),
    }));
});

// ── Paginated computed data ────────────────────────────────────────────────
const paginatedStock = computed(() => paginatedData(props.stockByDrug, stockPage.value));
const paginatedRevenue = computed(() => paginatedData(revenueByDate.value, revenuePage.value));
const paginatedTopDrugs = computed(() => paginatedData(props.topDrugs, topDrugsPage.value));
const paginatedTopSellers = computed(() => paginatedData(props.topSellers, topSellersPage.value));
const paginatedTopDisbursers = computed(() => paginatedData(props.topDisbursers, topDisbursersPage.value));
const paginatedNearExpiry = computed(() => paginatedData(nearExpiryEnriched.value, nearExpiryPage.value));
const paginatedTransfers = computed(() => paginatedData(transfersEnriched.value, transfersPage.value));
</script>

<template>
    <AppLayout title="Statistiques">
        <template #header>
            <div class="page-header-row">
                <div>
                    <h1 class="page-title">Statistiques</h1>
                    <p class="page-subtitle">
                        <span v-if="pharmacy">{{ pharmacy.name }}</span>
                        <span v-if="filters"> &mdash; du {{ fmtDate(filters.from) }} au {{ fmtDate(filters.to) }}</span>
                    </p>
                </div>
                <button class="btn-print no-print" @click="printPage">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Imprimer
                </button>
            </div>
        </template>

        <div class="stats-content">
        <!-- print-only title -->
        <div class="stats-print-title">
            <h1>STATISTIQUES — {{ pharmacy?.name ?? 'Sys E-Dépôt Pharma' }}</h1>
            <p v-if="filters?.from">Période : {{ fmtDate(filters.from) }} → {{ fmtDate(filters.to) }}</p>
        </div>

        <!-- ── Filter bar ── -->
        <div class="filter-bar no-print">
            <div class="filter-group">
                <label class="filter-label">Du</label>
                <input type="datetime-local" v-model="from" class="filter-input" />
            </div>
            <div class="filter-group">
                <label class="filter-label">Au</label>
                <input type="datetime-local" v-model="to" class="filter-input" />
            </div>
            <button class="btn-filter" @click="applyFilters">Filtrer</button>
        </div>

        <!-- ── Totals ── -->
        <div class="totals-grid">
            <div class="total-card total-revenue">
                <div class="total-label">Recettes totales</div>
                <div class="total-value">{{ fmtMoney(totals?.revenue) }}</div>
                <div class="total-sub">{{ fmt(totals?.sales_count) }} transactions</div>
            </div>
            <div class="total-card total-disbursements">
                <div class="total-label">Décaissements</div>
                <div class="total-value text-red">{{ fmtMoney(totals?.disbursements) }}</div>
                <div class="total-sub">{{ fmt(totals?.disbursement_count) }} décaissements</div>
            </div>
            <div class="total-card total-net">
                <div class="total-label">Net (Recettes - Décaissements)</div>
                <div class="total-value" :class="{ 'text-green': (totals?.net_revenue || 0) >= 0, 'text-red': (totals?.net_revenue || 0) < 0 }">{{ fmtMoney(totals?.net_revenue) }}</div>
                <div class="total-sub">Recettes nettes</div>
            </div>
            <div class="total-card total-stock">
                <div class="total-label">Stock total</div>
                <div class="total-value">{{ fmt(totals?.stock_qty) }}</div>
                <div class="total-sub">{{ fmt(totals?.stock_units) }} unités</div>
            </div>
            <div class="total-card total-expiry" :class="{ 'alert': totals?.near_expiry > 0 }">
                <div class="total-label">Proches péremption</div>
                <div class="total-value">{{ fmt(totals?.near_expiry) }}</div>
                <div class="total-sub">dans les 90 jours</div>
            </div>
            <div class="total-card total-transfers">
                <div class="total-label">Transferts</div>
                <div class="total-value">{{ fmt(totals?.transfers) }}</div>
                <div class="total-sub">{{ fmt(totals?.transfer_items) }} articles</div>
            </div>
        </div>

        <!-- ── 1. Stock par médicament ── -->
        <section class="stat-section">
            <h2 class="section-title">Stock par médicament <span class="pagination-info" v-if="paginatedStock.totalPages > 1">({{ paginatedStock.start }}-{{ paginatedStock.end }} / {{ paginatedStock.total }})</span></h2>
            <div class="table-wrapper">
                <table class="stat-table">
                    <thead>
                        <tr>
                            <th>Médicament</th>
                            <th>Forme / Dosage</th>
                            <th>Catégorie</th>
                            <th class="text-right">Pharmacie</th>
                            <th v-for="depot in depots" :key="depot.id" class="text-right">{{ depot.name }}</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="drug in paginatedStock.items" :key="drug.drug_id">
                            <td class="drug-name">{{ drug.drug_name }}</td>
                            <td class="text-muted">{{ drug.form_med }}<span v-if="drug.dosage_med"> · {{ drug.dosage_med }}</span></td>
                            <td class="text-muted">{{ drug.category_name ?? '—' }}</td>
                            <td class="text-right">{{ fmt(drug.qty_pharmacy) }}</td>
                            <td v-for="depot in depots" :key="depot.id" class="text-right">
                                {{ fmt(stockPerDepot?.[drug.drug_id]?.[depot.id] ?? 0) }}
                            </td>
                            <td class="text-right font-bold">{{ fmt(drug.qty_total) }}</td>
                        </tr>
                        <tr v-if="!paginatedStock.items?.length">
                            <td :colspan="4 + (depots?.length ?? 0)" class="empty-row">Aucun stock</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="pagination-controls" v-if="paginatedStock.totalPages > 1">
                <button class="pagination-btn" :disabled="stockPage <= 1" @click="changePage(stockPage, stockPage - 1, paginatedStock.totalPages)">← Précédent</button>
                <span class="pagination-page">Page {{ paginatedStock.currentPage }} / {{ paginatedStock.totalPages }}</span>
                <button class="pagination-btn" :disabled="stockPage >= paginatedStock.totalPages" @click="changePage(stockPage, stockPage + 1, paginatedStock.totalPages)">Suivant →</button>
            </div>
        </section>

        <!-- ── 2. Recettes quotidiennes ── -->
        <section class="stat-section">
            <h2 class="section-title">Recettes quotidiennes par dépôt (avec décaissements) <span class="pagination-info" v-if="paginatedRevenue.totalPages > 1">({{ paginatedRevenue.start }}-{{ paginatedRevenue.end }} / {{ paginatedRevenue.total }})</span></h2>
            <div class="table-wrapper">
                <table class="stat-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Dépôt</th>
                            <th class="text-right">Recettes</th>
                            <th class="text-right">Transactions</th>
                            <th class="text-right text-red">Décaissements</th>
                            <th class="text-right">Nb Décaissements</th>
                            <th class="text-right font-bold">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="day in paginatedRevenue.items" :key="day.date">
                            <tr v-for="(row, idx) in Object.values(day.depots)" :key="row.depot_id" class="revenue-row">
                                <td v-if="idx === 0" :rowspan="Object.values(day.depots).length" class="date-cell">
                                    {{ fmtDate(day.date) }}
                                </td>
                                <td>{{ row.depot_name }}</td>
                                <td class="text-right font-medium">{{ fmtMoney(row.revenue) }}</td>
                                <td class="text-right text-muted">{{ fmt(row.sales_count) }}</td>
                                <td class="text-right text-red">{{ row.disbursement > 0 ? fmtMoney(row.disbursement) : '-' }}</td>
                                <td class="text-right text-muted">{{ row.disbursement_count > 0 ? fmt(row.disbursement_count) : '-' }}</td>
                                <td class="text-right font-bold" :class="{ 'text-green': (row.revenue - row.disbursement) >= 0, 'text-red': (row.revenue - row.disbursement) < 0 }">{{ fmtMoney(row.revenue - row.disbursement) }}</td>
                            </tr>
                            <tr class="day-total-row">
                                <td colspan="2" class="text-right font-semibold text-xs text-gray">Total {{ fmtDate(day.date) }}</td>
                                <td class="text-right font-bold">{{ fmtMoney(day.total) }}</td>
                                <td></td>
                                <td class="text-right font-bold text-red">{{ day.disbursement_total > 0 ? fmtMoney(day.disbursement_total) : '-' }}</td>
                                <td></td>
                                <td class="text-right font-bold" :class="{ 'text-green': day.net_revenue >= 0, 'text-red': day.net_revenue < 0 }">{{ fmtMoney(day.net_revenue) }}</td>
                            </tr>
                        </template>
                        <tr v-if="!paginatedRevenue.items?.length">
                            <td colspan="7" class="empty-row">Aucune vente sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="pagination-controls" v-if="paginatedRevenue.totalPages > 1">
                <button class="pagination-btn" :disabled="revenuePage <= 1" @click="changePage(revenuePage, revenuePage - 1, paginatedRevenue.totalPages)">← Précédent</button>
                <span class="pagination-page">Page {{ paginatedRevenue.currentPage }} / {{ paginatedRevenue.totalPages }}</span>
                <button class="pagination-btn" :disabled="revenuePage >= paginatedRevenue.totalPages" @click="changePage(revenuePage, revenuePage + 1, paginatedRevenue.totalPages)">Suivant →</button>
            </div>
        </section>

        <!-- ── 3. Top médicaments vendus ── -->
        <section class="stat-section">
            <h2 class="section-title">Médicaments les plus vendus <span class="pagination-info" v-if="paginatedTopDrugs.totalPages > 1">({{ paginatedTopDrugs.start }}-{{ paginatedTopDrugs.end }} / {{ paginatedTopDrugs.total }})</span></h2>
            <div class="table-wrapper">
                <table class="stat-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Médicament</th>
                            <th>Forme / Dosage</th>
                            <th class="text-right">Qté vendue</th>
                            <th class="text-right">Recettes</th>
                            <th class="text-right">Transactions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(drug, idx) in paginatedTopDrugs.items" :key="drug.drug_id">
                            <td class="rank">{{ paginatedTopDrugs.start + idx }}</td>
                            <td class="drug-name">{{ drug.drug_name }}</td>
                            <td class="text-muted">{{ drug.form_med }}<span v-if="drug.dosage_med"> · {{ drug.dosage_med }}</span></td>
                            <td class="text-right font-bold">{{ fmt(drug.total_qty) }}</td>
                            <td class="text-right">{{ fmtMoney(drug.total_revenue) }}</td>
                            <td class="text-right text-muted">{{ fmt(drug.sales_count) }}</td>
                        </tr>
                        <tr v-if="!paginatedTopDrugs.items?.length">
                            <td colspan="6" class="empty-row">Aucune vente sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="pagination-controls" v-if="paginatedTopDrugs.totalPages > 1">
                <button class="pagination-btn" :disabled="topDrugsPage <= 1" @click="changePage(topDrugsPage, topDrugsPage - 1, paginatedTopDrugs.totalPages)">← Précédent</button>
                <span class="pagination-page">Page {{ paginatedTopDrugs.currentPage }} / {{ paginatedTopDrugs.totalPages }}</span>
                <button class="pagination-btn" :disabled="topDrugsPage >= paginatedTopDrugs.totalPages" @click="changePage(topDrugsPage, topDrugsPage + 1, paginatedTopDrugs.totalPages)">Suivant →</button>
            </div>
        </section>

        <!-- ── 4. Top vendeurs ── -->
        <section class="stat-section">
            <h2 class="section-title">Vendeurs les plus performants <span class="pagination-info" v-if="paginatedTopSellers.totalPages > 1">({{ paginatedTopSellers.start }}-{{ paginatedTopSellers.end }} / {{ paginatedTopSellers.total }})</span></h2>
            <div class="table-wrapper">
                <table class="stat-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vendeur</th>
                            <th>Dépôt</th>
                            <th class="text-right">Recettes</th>
                            <th class="text-right">Qté vendue</th>
                            <th class="text-right">Transactions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(seller, idx) in paginatedTopSellers.items" :key="seller.user_id">
                            <td class="rank">{{ paginatedTopSellers.start + idx }}</td>
                            <td class="font-medium">{{ seller.user_name }}</td>
                            <td class="text-muted">{{ seller.depot_name }}</td>
                            <td class="text-right font-bold">{{ fmtMoney(seller.total_revenue) }}</td>
                            <td class="text-right">{{ fmt(seller.total_qty) }}</td>
                            <td class="text-right text-muted">{{ fmt(seller.sales_count) }}</td>
                        </tr>
                        <tr v-if="!paginatedTopSellers.items?.length">
                            <td colspan="6" class="empty-row">Aucune vente sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="pagination-controls" v-if="paginatedTopSellers.totalPages > 1">
                <button class="pagination-btn" :disabled="topSellersPage <= 1" @click="changePage(topSellersPage, topSellersPage - 1, paginatedTopSellers.totalPages)">← Précédent</button>
                <span class="pagination-page">Page {{ paginatedTopSellers.currentPage }} / {{ paginatedTopSellers.totalPages }}</span>
                <button class="pagination-btn" :disabled="topSellersPage >= paginatedTopSellers.totalPages" @click="changePage(topSellersPage, topSellersPage + 1, paginatedTopSellers.totalPages)">Suivant →</button>
            </div>
        </section>

        <!-- ── 4b. Top décaisseurs ── -->
        <section class="stat-section">
            <h2 class="section-title">Personnes décaissant le plus <span class="pagination-info" v-if="paginatedTopDisbursers.totalPages > 1">({{ paginatedTopDisbursers.start }}-{{ paginatedTopDisbursers.end }} / {{ paginatedTopDisbursers.total }})</span></h2>
            <div class="table-wrapper">
                <table class="stat-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Personne</th>
                            <th class="text-right">Total décaissé</th>
                            <th class="text-right">Nombre de décaissements</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(disburser, idx) in paginatedTopDisbursers.items" :key="disburser.user_id">
                            <td class="rank">{{ paginatedTopDisbursers.start + idx }}</td>
                            <td class="font-medium">{{ disburser.user_name }}</td>
                            <td class="text-right font-bold text-red">{{ fmtMoney(disburser.total_disbursed) }}</td>
                            <td class="text-right text-muted">{{ fmt(disburser.disbursement_count) }}</td>
                        </tr>
                        <tr v-if="!paginatedTopDisbursers.items?.length">
                            <td colspan="4" class="empty-row">Aucun décaissement sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="pagination-controls" v-if="paginatedTopDisbursers.totalPages > 1">
                <button class="pagination-btn" :disabled="topDisbursersPage <= 1" @click="changePage(topDisbursersPage, topDisbursersPage - 1, paginatedTopDisbursers.totalPages)">← Précédent</button>
                <span class="pagination-page">Page {{ paginatedTopDisbursers.currentPage }} / {{ paginatedTopDisbursers.totalPages }}</span>
                <button class="pagination-btn" :disabled="topDisbursersPage >= paginatedTopDisbursers.totalPages" @click="changePage(topDisbursersPage, topDisbursersPage + 1, paginatedTopDisbursers.totalPages)">Suivant →</button>
            </div>
        </section>

        <!-- ── 5. Médicaments proches de péremption ── -->
        <section class="stat-section">
            <h2 class="section-title">
                Médicaments proches de la péremption
                <span class="expiry-badge" v-if="paginatedNearExpiry.total > 0">{{ paginatedNearExpiry.total }}</span>
                <span class="pagination-info" v-if="paginatedNearExpiry.totalPages > 1">({{ paginatedNearExpiry.start }}-{{ paginatedNearExpiry.end }} / {{ paginatedNearExpiry.total }})</span>
            </h2>
            <div class="table-wrapper">
                <table class="stat-table">
                    <thead>
                        <tr>
                            <th>Médicament</th>
                            <th>Code-barres</th>
                            <th>Catégorie</th>
                            <th>Emplacement</th>
                            <th class="text-right">Qté</th>
                            <th>Date péremption</th>
                            <th class="text-right">Jours restants</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="du in paginatedNearExpiry.items" :key="du.id" :class="{ 'urgent': du.days_left <= 30, 'warning': du.days_left > 30 && du.days_left <= 60 }">
                            <td class="drug-name">{{ du.drug_name }}</td>
                            <td class="text-muted text-mono">{{ du.barcode }}</td>
                            <td class="text-muted">{{ du.category_name ?? '—' }}</td>
                            <td>{{ du.location_label }}</td>
                            <td class="text-right font-bold">{{ fmt(du.quantite_actuelle) }}</td>
                            <td>{{ fmtDate(du.expiration_date) }}</td>
                            <td class="text-right" :class="{ 'text-red': du.days_left <= 30, 'text-orange': du.days_left > 30 && du.days_left <= 60 }">
                                {{ du.days_left }}j
                            </td>
                        </tr>
                        <tr v-if="!paginatedNearExpiry.items?.length">
                            <td colspan="7" class="empty-row">Aucun médicament proche de la péremption</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="pagination-controls" v-if="paginatedNearExpiry.totalPages > 1">
                <button class="pagination-btn" :disabled="nearExpiryPage <= 1" @click="changePage(nearExpiryPage, nearExpiryPage - 1, paginatedNearExpiry.totalPages)">← Précédent</button>
                <span class="pagination-page">Page {{ paginatedNearExpiry.currentPage }} / {{ paginatedNearExpiry.totalPages }}</span>
                <button class="pagination-btn" :disabled="nearExpiryPage >= paginatedNearExpiry.totalPages" @click="changePage(nearExpiryPage, nearExpiryPage + 1, paginatedNearExpiry.totalPages)">Suivant →</button>
            </div>
        </section>

        <!-- ── 6. Transferts ── -->
        <section class="stat-section">
            <h2 class="section-title">Transferts vers les dépôts <span class="pagination-info" v-if="paginatedTransfers.totalPages > 1">({{ paginatedTransfers.start }}-{{ paginatedTransfers.end }} / {{ paginatedTransfers.total }})</span></h2>
            <div class="table-wrapper">
                <table class="stat-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Dépôt</th>
                            <th class="text-right">Articles (unités)</th>
                            <th class="text-right">Quantité totale</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in paginatedTransfers.items" :key="t.id">
                            <td>{{ fmtDate(t.date) }}</td>
                            <td>{{ t.depot_name }}</td>
                            <td class="text-right">{{ fmt(t.items_count) }}</td>
                            <td class="text-right font-bold">{{ fmt(t.total_qty) }}</td>
                            <td>
                                <span class="status-badge" :class="'status-' + t.status">
                                    {{ t.status }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!paginatedTransfers.items?.length">
                            <td colspan="5" class="empty-row">Aucun transfert sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <div class="pagination-controls" v-if="paginatedTransfers.totalPages > 1">
                <button class="pagination-btn" :disabled="transfersPage <= 1" @click="changePage(transfersPage, transfersPage - 1, paginatedTransfers.totalPages)">← Précédent</button>
                <span class="pagination-page">Page {{ paginatedTransfers.currentPage }} / {{ paginatedTransfers.totalPages }}</span>
                <button class="pagination-btn" :disabled="transfersPage >= paginatedTransfers.totalPages" @click="changePage(transfersPage, transfersPage + 1, paginatedTransfers.totalPages)">Suivant →</button>
            </div>
        </section>

        <!-- ── Print-only: All data sections (shown only when printing) ── -->
        <div class="print-only">
            <!-- All Stock for Print -->
            <section class="stat-section">
                <h2 class="section-title">Stock par médicament (complet)</h2>
                <div class="table-wrapper">
                    <table class="stat-table">
                        <thead>
                            <tr>
                                <th>Médicament</th>
                                <th>Forme / Dosage</th>
                                <th>Catégorie</th>
                                <th class="text-right">Pharmacie</th>
                                <th v-for="depot in depots" :key="depot.id" class="text-right">{{ depot.name }}</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="drug in stockByDrug" :key="drug.drug_id">
                                <td class="drug-name">{{ drug.drug_name }}</td>
                                <td class="text-muted">{{ drug.form_med }}<span v-if="drug.dosage_med"> · {{ drug.dosage_med }}</span></td>
                                <td class="text-muted">{{ drug.category_name ?? '—' }}</td>
                                <td class="text-right">{{ fmt(drug.qty_pharmacy) }}</td>
                                <td v-for="depot in depots" :key="depot.id" class="text-right">
                                    {{ fmt(stockPerDepot?.[drug.drug_id]?.[depot.id] ?? 0) }}
                                </td>
                                <td class="text-right font-bold">{{ fmt(drug.qty_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- All Revenue for Print -->
            <section class="stat-section">
                <h2 class="section-title">Recettes quotidiennes (complet)</h2>
                <div class="table-wrapper">
                    <table class="stat-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Dépôt</th>
                                <th class="text-right">Recettes</th>
                                <th class="text-right">Transactions</th>
                                <th class="text-right text-red">Décaissements</th>
                                <th class="text-right">Nb Décaissements</th>
                                <th class="text-right font-bold">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="day in revenueByDate" :key="day.date">
                                <tr v-for="(row, idx) in Object.values(day.depots)" :key="row.depot_id" class="revenue-row">
                                    <td v-if="idx === 0" :rowspan="Object.values(day.depots).length" class="date-cell">{{ fmtDate(day.date) }}</td>
                                    <td>{{ row.depot_name }}</td>
                                    <td class="text-right font-medium">{{ fmtMoney(row.revenue) }}</td>
                                    <td class="text-right text-muted">{{ fmt(row.sales_count) }}</td>
                                    <td class="text-right text-red">{{ row.disbursement > 0 ? fmtMoney(row.disbursement) : '-' }}</td>
                                    <td class="text-right text-muted">{{ row.disbursement_count > 0 ? fmt(row.disbursement_count) : '-' }}</td>
                                    <td class="text-right font-bold">{{ fmtMoney(row.revenue - row.disbursement) }}</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- All Top Drugs for Print -->
            <section class="stat-section">
                <h2 class="section-title">Médicaments les plus vendus (complet)</h2>
                <div class="table-wrapper">
                    <table class="stat-table">
                        <thead><tr><th>#</th><th>Médicament</th><th>Qté vendue</th><th>Recettes</th></tr></thead>
                        <tbody>
                            <tr v-for="(drug, idx) in topDrugs" :key="drug.drug_id">
                                <td class="rank">{{ idx + 1 }}</td>
                                <td class="drug-name">{{ drug.drug_name }}</td>
                                <td class="text-right font-bold">{{ fmt(drug.total_qty) }}</td>
                                <td class="text-right">{{ fmtMoney(drug.total_revenue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- All Top Sellers for Print -->
            <section class="stat-section">
                <h2 class="section-title">Vendeurs les plus performants (complet)</h2>
                <div class="table-wrapper">
                    <table class="stat-table">
                        <thead><tr><th>#</th><th>Vendeur</th><th>Recettes</th><th>Qté vendue</th></tr></thead>
                        <tbody>
                            <tr v-for="(seller, idx) in topSellers" :key="seller.user_id">
                                <td class="rank">{{ idx + 1 }}</td>
                                <td class="font-medium">{{ seller.user_name }}</td>
                                <td class="text-right font-bold">{{ fmtMoney(seller.total_revenue) }}</td>
                                <td class="text-right">{{ fmt(seller.total_qty) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- All Top Disbursers for Print -->
            <section class="stat-section">
                <h2 class="section-title">Personnes décaissant le plus (complet)</h2>
                <div class="table-wrapper">
                    <table class="stat-table">
                        <thead><tr><th>#</th><th>Personne</th><th>Total décaissé</th><th>Nb</th></tr></thead>
                        <tbody>
                            <tr v-for="(disburser, idx) in topDisbursers" :key="disburser.user_id">
                                <td class="rank">{{ idx + 1 }}</td>
                                <td class="font-medium">{{ disburser.user_name }}</td>
                                <td class="text-right font-bold text-red">{{ fmtMoney(disburser.total_disbursed) }}</td>
                                <td class="text-right text-muted">{{ fmt(disburser.disbursement_count) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- All Near Expiry for Print -->
            <section class="stat-section">
                <h2 class="section-title">Médicaments proches de la péremption (complet)</h2>
                <div class="table-wrapper">
                    <table class="stat-table">
                        <thead><tr><th>Médicament</th><th>Emplacement</th><th>Qté</th><th>Date péremption</th><th>Jours</th></tr></thead>
                        <tbody>
                            <tr v-for="du in nearExpiryEnriched" :key="du.id" :class="{ 'urgent': du.days_left <= 30, 'warning': du.days_left > 30 && du.days_left <= 60 }">
                                <td class="drug-name">{{ du.drug_name }}</td>
                                <td>{{ du.location_label }}</td>
                                <td class="text-right font-bold">{{ fmt(du.quantite_actuelle) }}</td>
                                <td>{{ fmtDate(du.expiration_date) }}</td>
                                <td class="text-right">{{ du.days_left }}j</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- All Transfers for Print -->
            <section class="stat-section">
                <h2 class="section-title">Transferts vers les dépôts (complet)</h2>
                <div class="table-wrapper">
                    <table class="stat-table">
                        <thead><tr><th>Date</th><th>Dépôt</th><th>Articles</th><th>Qté totale</th><th>Statut</th></tr></thead>
                        <tbody>
                            <tr v-for="t in transfersEnriched" :key="t.id">
                                <td>{{ fmtDate(t.date) }}</td>
                                <td>{{ t.depot_name }}</td>
                                <td class="text-right">{{ fmt(t.items_count) }}</td>
                                <td class="text-right font-bold">{{ fmt(t.total_qty) }}</td>
                                <td><span class="status-badge" :class="'status-' + t.status">{{ t.status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- ── Print footer ── -->
        <div class="print-footer print-only">
            <p>Sys E-Dépôt Pharma &mdash; Lumière Afrique Group Sarl</p>
            <p>Imprimé le {{ fmtDate(new Date().toISOString()) }}</p>
        </div>
        </div><!-- /.stats-content -->
    </AppLayout>
</template>

<style scoped>
/* ── Layout ── */
.page-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    flex-wrap: wrap;
}
.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
}
.page-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

/* ── Filter bar ── */
.filter-bar {
    display: flex;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.filter-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.filter-input {
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: #111827;
    outline: none;
    transition: border-color 150ms;
}
.filter-input:focus {
    border-color: #3b82f6;
}
.btn-filter {
    padding: 0.5rem 1.25rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 150ms;
}
.btn-filter:hover { opacity: 0.9; }

.btn-print {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    transition: all 150ms;
}
.btn-print:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}
.btn-print svg {
    width: 1.125rem;
    height: 1.125rem;
}

/* ── Totals grid ── */
.totals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.total-card {
    background: white;
    border-radius: 0.75rem;
    padding: 1.25rem 1.5rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.total-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}
.total-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    line-height: 1.2;
}
.total-sub {
    font-size: 0.75rem;
    color: #9ca3af;
    margin-top: 0.25rem;
}
.total-revenue { border-left: 4px solid #10b981; }
.total-disbursements { border-left: 4px solid #ef4444; }
.total-net { border-left: 4px solid #3b82f6; }
.total-stock   { border-left: 4px solid #8b5cf6; }
.total-expiry  { border-left: 4px solid #f59e0b; }
.total-expiry.alert { border-left-color: #ef4444; }
.total-transfers { border-left: 4px solid #6366f1; }

/* Text colors */
.text-green { color: #10b981; }
.text-red { color: #ef4444; }

/* ── Sections ── */
.stat-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    overflow: hidden;
}
.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.expiry-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 9999px;
    padding: 0.125rem 0.5rem;
}

/* ── Tables ── */
.table-wrapper {
    overflow-x: auto;
}
.stat-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.stat-table th {
    padding: 0.625rem 1rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: #f9fafb;
    border-bottom: 1px solid #f3f4f6;
    white-space: nowrap;
}
.stat-table td {
    padding: 0.625rem 1rem;
    color: #374151;
    border-bottom: 1px solid #f9fafb;
    white-space: nowrap;
}
.stat-table tbody tr:last-child td {
    border-bottom: none;
}
.stat-table tbody tr:hover td {
    background: rgba(59, 130, 246, 0.03);
}
.text-right { text-align: right; }
.text-muted { color: #9ca3af; }
.text-mono  { font-family: monospace; }
.font-bold  { font-weight: 700; }
.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.drug-name { font-weight: 600; color: #1f2937; }
.rank { font-weight: 700; color: #6b7280; width: 2rem; }
.empty-row { text-align: center; color: #9ca3af; padding: 2rem; }
.date-cell { font-weight: 600; color: #374151; }

/* Revenue grouping */
.day-total-row td {
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    border-bottom: 2px solid #e5e7eb;
    font-size: 0.75rem;
    color: #6b7280;
}
.text-gray { color: #6b7280; }
.text-xs { font-size: 0.75rem; }

/* Near expiry row highlight */
.urgent td { background: rgba(239, 68, 68, 0.05); }
.warning td { background: rgba(245, 158, 11, 0.05); }
.text-red    { color: #ef4444; font-weight: 700; }
.text-orange { color: #f59e0b; font-weight: 700; }

/* Status badges */
.status-badge {
    display: inline-flex;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}
.status-completed { background: #d1fae5; color: #065f46; }
.status-pending   { background: #fef3c7; color: #92400e; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

/* Pagination */
.pagination-info {
    font-size: 0.75rem;
    font-weight: 400;
    color: #6b7280;
    margin-left: 0.5rem;
}
.pagination-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}
.pagination-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.15s;
}
.pagination-btn:hover:not(:disabled) {
    background: #f3f4f6;
    border-color: #9ca3af;
}
.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.pagination-page {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

/* ── Print ── */
.print-footer { display: none; }
.print-only   { display: none; }
.stats-print-title { display: none; }
</style>

<style>
@media print {
    @page { margin: 0.5cm; size: auto; }

    body.printing-stats { overflow: visible !important; height: auto !important; }
    body.printing-stats * { visibility: hidden !important; }
    body.printing-stats .stats-content,
    body.printing-stats .stats-content * { visibility: visible !important; }
    body.printing-stats .stats-content {
        position: static !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        padding: 10px !important;
        display: block !important;
        overflow: visible !important;
    }
    body.printing-stats .no-print { display: none !important; }
    body.printing-stats .print-only,
    body.printing-stats .print-footer { display: block !important; }
    body.printing-stats .stats-print-title { display: block !important; margin-bottom: 0.5rem; page-break-after: avoid; }
    body.printing-stats .stats-print-title h1 { font-size: 14px; font-weight: 800; color: #1e40af; margin: 0; }
    body.printing-stats .stats-print-title p { font-size: 10px; color: #6b7280; margin: 0.2rem 0 0; }

    /* Pagination - page break before each section */
    body.printing-stats .stat-section {
        border: 1px solid #ccc;
        box-shadow: none;
        page-break-inside: avoid;
        page-break-before: always;
        margin-bottom: 0.5rem;
    }
    /* First section doesn't need page break */
    body.printing-stats .stat-section:first-of-type { page-break-before: auto; }

    body.printing-stats .totals-grid {
        page-break-inside: avoid;
        page-break-after: avoid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    body.printing-stats .total-card {
        padding: 0.5rem;
    }
    body.printing-stats .total-label { font-size: 8px; }
    body.printing-stats .total-value { font-size: 12px; }
    body.printing-stats .total-sub { font-size: 8px; }

    /* Tables - smaller fonts, compact */
    body.printing-stats .table-wrapper { overflow: visible !important; }
    body.printing-stats .stat-table {
        font-size: 9px;
        width: 100%;
    }
    body.printing-stats .stat-table td,
    body.printing-stats .stat-table th {
        padding: 0.2rem 0.4rem;
        font-size: 9px;
    }
    body.printing-stats .section-title {
        font-size: 11px;
        padding: 0.5rem;
        page-break-after: avoid;
    }

    /* Ensure wide tables don't get cut */
    body.printing-stats .stat-section:nth-child(1) .stat-table,
    body.printing-stats .stat-section:nth-child(2) .stat-table {
        font-size: 8px;
    }
    body.printing-stats .stat-section:nth-child(1) .stat-table td,
    body.printing-stats .stat-section:nth-child(1) .stat-table th,
    body.printing-stats .stat-section:nth-child(2) .stat-table td,
    body.printing-stats .stat-section:nth-child(2) .stat-table th {
        padding: 0.15rem 0.3rem;
        font-size: 8px;
    }

    /* Hide pagination in print - show all data */
    body.printing-stats .pagination-controls,
    body.printing-stats .pagination-info { display: none !important; }

    /* Hide paginated sections when printing, show print-only all-data sections */
    body.printing-stats .stats-content > section.stat-section { display: none !important; }
    body.printing-stats .stats-content > .print-only { display: block !important; }
    body.printing-stats .stats-content > .print-only .stat-section { display: block !important; }

    /* Keep totals visible in print, hide filter bar */
    body.printing-stats .totals-grid { display: grid !important; }
    body.printing-stats .filter-bar { display: none !important; }

    body.printing-stats .print-footer {
        display: block !important;
        margin-top: 1rem;
        text-align: center;
        font-size: 8px;
        color: #6b7280;
        border-top: 1px solid #e5e7eb;
        padding-top: 0.5rem;
        page-break-inside: avoid;
    }

    body { background: white; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
