<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    pharmacy:      Object,
    depots:        Array,
    stockByDrug:   Array,
    stockPerDepot: Object,
    dailyRevenue:  Array,
    topDrugs:      Array,
    topSellers:    Array,
    nearExpiry:    Array,
    transferStats: Array,
    transferQtys:  Object,
    totals:        Object,
    filters:       Object,
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
        if (!map[row.date]) map[row.date] = { date: row.date, total: 0, depots: [] };
        map[row.date].total += Number(row.revenue ?? 0);
        map[row.date].depots.push(row);
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
            <h2 class="section-title">Stock par médicament</h2>
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
                        <tr v-if="!stockByDrug?.length">
                            <td :colspan="4 + (depots?.length ?? 0)" class="empty-row">Aucun stock</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── 2. Recettes quotidiennes ── -->
        <section class="stat-section">
            <h2 class="section-title">Recettes quotidiennes par dépôt</h2>
            <div class="table-wrapper">
                <table class="stat-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Dépôt</th>
                            <th class="text-right">Recettes</th>
                            <th class="text-right">Transactions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="day in revenueByDate" :key="day.date">
                            <tr v-for="(row, idx) in day.depots" :key="row.depot_id" class="revenue-row">
                                <td v-if="idx === 0" :rowspan="day.depots.length" class="date-cell">
                                    {{ fmtDate(day.date) }}
                                </td>
                                <td>{{ row.depot_name }}</td>
                                <td class="text-right font-medium">{{ fmtMoney(row.revenue) }}</td>
                                <td class="text-right text-muted">{{ fmt(row.sales_count) }}</td>
                            </tr>
                            <tr class="day-total-row">
                                <td colspan="2" class="text-right font-semibold text-xs text-gray">Total {{ fmtDate(day.date) }}</td>
                                <td class="text-right font-bold">{{ fmtMoney(day.total) }}</td>
                                <td></td>
                            </tr>
                        </template>
                        <tr v-if="!revenueByDate.length">
                            <td colspan="4" class="empty-row">Aucune vente sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── 3. Top médicaments vendus ── -->
        <section class="stat-section">
            <h2 class="section-title">Médicaments les plus vendus</h2>
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
                        <tr v-for="(drug, idx) in topDrugs" :key="drug.drug_id">
                            <td class="rank">{{ idx + 1 }}</td>
                            <td class="drug-name">{{ drug.drug_name }}</td>
                            <td class="text-muted">{{ drug.form_med }}<span v-if="drug.dosage_med"> · {{ drug.dosage_med }}</span></td>
                            <td class="text-right font-bold">{{ fmt(drug.total_qty) }}</td>
                            <td class="text-right">{{ fmtMoney(drug.total_revenue) }}</td>
                            <td class="text-right text-muted">{{ fmt(drug.sales_count) }}</td>
                        </tr>
                        <tr v-if="!topDrugs?.length">
                            <td colspan="6" class="empty-row">Aucune vente sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── 4. Top vendeurs ── -->
        <section class="stat-section">
            <h2 class="section-title">Vendeurs les plus performants</h2>
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
                        <tr v-for="(seller, idx) in topSellers" :key="seller.user_id">
                            <td class="rank">{{ idx + 1 }}</td>
                            <td class="font-medium">{{ seller.user_name }}</td>
                            <td class="text-muted">{{ seller.depot_name }}</td>
                            <td class="text-right font-bold">{{ fmtMoney(seller.total_revenue) }}</td>
                            <td class="text-right">{{ fmt(seller.total_qty) }}</td>
                            <td class="text-right text-muted">{{ fmt(seller.sales_count) }}</td>
                        </tr>
                        <tr v-if="!topSellers?.length">
                            <td colspan="6" class="empty-row">Aucune vente sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── 5. Médicaments proches de péremption ── -->
        <section class="stat-section">
            <h2 class="section-title">
                Médicaments proches de la péremption
                <span class="expiry-badge" v-if="nearExpiryEnriched.length">{{ nearExpiryEnriched.length }}</span>
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
                        <tr v-for="du in nearExpiryEnriched" :key="du.id" :class="{ 'urgent': du.days_left <= 30, 'warning': du.days_left > 30 && du.days_left <= 60 }">
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
                        <tr v-if="!nearExpiryEnriched.length">
                            <td colspan="7" class="empty-row">Aucun médicament proche de la péremption</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── 6. Transferts ── -->
        <section class="stat-section">
            <h2 class="section-title">Transferts vers les dépôts</h2>
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
                        <tr v-for="t in transfersEnriched" :key="t.id">
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
                        <tr v-if="!transfersEnriched.length">
                            <td colspan="5" class="empty-row">Aucun transfert sur la période</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

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
.total-stock   { border-left: 4px solid #3b82f6; }
.total-expiry  { border-left: 4px solid #f59e0b; }
.total-expiry.alert { border-left-color: #ef4444; }
.total-transfers { border-left: 4px solid #8b5cf6; }

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

/* ── Print ── */
.print-footer { display: none; }
.print-only   { display: none; }
.stats-print-title { display: none; }
</style>

<style>
@media print {
    body.printing-stats * { visibility: hidden !important; }
    body.printing-stats .stats-content,
    body.printing-stats .stats-content * { visibility: visible !important; }
    body.printing-stats .stats-content {
        position: fixed !important;
        top: 0 !important; left: 0 !important;
        width: 100% !important;
        background: white !important;
        padding: 20px !important;
        display: block !important;
    }
    body.printing-stats .no-print { display: none !important; }
    body.printing-stats .print-only,
    body.printing-stats .print-footer { display: block !important; }
    body.printing-stats .stats-print-title { display: block !important; margin-bottom: 1rem; }
    body.printing-stats .stats-print-title h1 { font-size: 18px; font-weight: 800; color: #1e40af; }
    body.printing-stats .stats-print-title p { font-size: 12px; color: #6b7280; }
    body.printing-stats .stat-section { border: 1px solid #ccc; box-shadow: none; page-break-inside: avoid; margin-bottom: 1rem; }
    body.printing-stats .totals-grid { page-break-inside: avoid; }
    body.printing-stats .stat-table td,
    body.printing-stats .stat-table th { padding: 0.4rem 0.75rem; font-size: 0.8rem; }
    body.printing-stats .print-footer { display: block !important; margin-top: 2rem; text-align: center; font-size: 0.75rem; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 1rem; }
    body { background: white; margin: 0; }
    @page { margin: 1cm; }
}
</style>
