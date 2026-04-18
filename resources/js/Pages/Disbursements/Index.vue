<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    disbursements: Object,
    disbursementsForPrint: Array,
    filters: Object,
});

const page = usePage();
const roles = computed(() => page.props.auth?.roles ?? []);
const canManage = computed(() => roles.value.includes('super_admin') || roles.value.includes('pharmacy_admin'));

const search    = ref(props.filters?.search    || '');
const sort      = ref(props.filters?.sort      || 'performed_at');
const direction = ref(props.filters?.direction || 'desc');
const fromDate  = ref(props.filters?.fromDate  || '');
const toDate    = ref(props.filters?.toDate    || '');

const applyFilters = () => {
    router.get(route('disbursements.index'), {
        search:    search.value || undefined,
        sort:      sort.value,
        direction: direction.value,
        from_date: fromDate.value || undefined,
        to_date:   toDate.value   || undefined,
    }, { preserveState: true, replace: true, preserveScroll: true });
};

const debouncedSearch = debounce(() => applyFilters(), 300);
watch(search, () => debouncedSearch());
watch([sort, direction], () => applyFilters());
watch([fromDate, toDate], () => {
    if ((!fromDate.value && !toDate.value) || (fromDate.value && toDate.value)) applyFilters();
});

const setSort = (col) => {
    if (sort.value === col) direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    else { sort.value = col; direction.value = 'desc'; }
};

const formatDate = (d) => new Date(d).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
});
const formatMoney = (v) => Number(v ?? 0).toLocaleString('fr-FR') + ' FCFA';

const totalAmount = computed(() => props.disbursements.data.reduce((s, d) => s + (d.total || 0), 0));

const deleteDisbursement = (uuid) => {
    if (confirm('Supprimer ce décaissement ? Cette action est irréversible.')) {
        router.delete(route('disbursements.destroy', uuid), { preserveScroll: true });
    }
};

// ── Period print ─────────────────────────────────────────────────────────────
const isPrinting = ref(false);
const canPrint   = computed(() => fromDate.value && toDate.value && props.disbursementsForPrint?.length > 0);

const disbByDay = computed(() => {
    if (!props.disbursementsForPrint) return [];
    const groups = {};
    for (const d of props.disbursementsForPrint) {
        const day = new Date(d.performed_at).toLocaleDateString('fr-FR', {
            weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
        });
        if (!groups[day]) groups[day] = [];
        groups[day].push(d);
    }
    return Object.entries(groups).map(([day, items]) => ({ day, items }));
});

const formatPeriod = computed(() => {
    if (!fromDate.value || !toDate.value) return '';
    const f = new Date(fromDate.value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    const t = new Date(toDate.value).toLocaleDateString('fr-FR',   { day: '2-digit', month: 'long', year: 'numeric' });
    return f === t ? f : `${f} au ${t}`;
});

const printGrandTotal = computed(() => props.disbursementsForPrint?.reduce((s, d) => s + (d.total || 0), 0) ?? 0);

const printPeriod = () => {
    isPrinting.value = true;
    document.body.classList.add('printing-disbursements');
    setTimeout(() => window.print(), 80);
};
const onAfterPrint = () => {
    isPrinting.value = false;
    document.body.classList.remove('printing-disbursements');
};
window.addEventListener('afterprint', onAfterPrint);
onBeforeUnmount(() => window.removeEventListener('afterprint', onAfterPrint));
</script>

<template>
    <AppLayout title="Décaissements">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">Décaissements</h2>
                    <p class="mt-1 text-sm text-gray-600">Gestion et suivi des décaissements</p>
                </div>
                <Link :href="route('disbursements.create')"
                    class="group px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 border border-transparent rounded-lg font-semibold text-sm text-white shadow-md hover:shadow-lg hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transform transition-all duration-200 hover:-translate-y-0.5">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nouveau Décaissement
                    </span>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4 border border-emerald-200">
                        <p class="text-sm font-medium text-emerald-700">Total décaissements (page)</p>
                        <p class="text-2xl font-bold text-emerald-900 mt-1">{{ disbursements.data.length }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                        <p class="text-sm font-medium text-blue-700">Montant total (page)</p>
                        <p class="text-2xl font-bold text-blue-900 mt-1">{{ formatMoney(totalAmount) }}</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                        <p class="text-sm font-medium text-purple-700">Lignes de désignation (page)</p>
                        <p class="text-2xl font-bold text-purple-900 mt-1">{{ disbursements.data.reduce((s, d) => s + (d.items_count || 0), 0) }}</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
                    <div class="flex flex-wrap gap-3 items-end">
                        <div class="relative flex-1 min-w-48">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input v-model="search" type="text" placeholder="Rechercher par désignation, initiateur, notes..."
                                class="w-full pl-9 pr-8 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <button v-if="search" @click="search = ''" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Du</label>
                                <input v-model="fromDate" type="date" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Au</label>
                                <input v-model="toDate" type="date" :min="fromDate" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                            <button v-if="fromDate || toDate" @click="fromDate = ''; toDate = ''"
                                class="mt-5 p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <button :disabled="!canPrint" @click="printPeriod"
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="canPrint ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            Imprimer la période
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th @click="setSort('id')" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer hover:text-emerald-700">
                                        N° <span v-if="sort === 'id'">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th @click="setSort('performed_at')" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer hover:text-emerald-700">
                                        Date <span v-if="sort === 'performed_at'">{{ direction === 'asc' ? '↑' : '↓' }}</span>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Initié par</th>
                                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Désignations</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-emerald-700 uppercase tracking-wider">Montant total</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Notes</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="d in disbursements.data" :key="d.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900">DEC-{{ String(d.id).padStart(4, '0') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ formatDate(d.performed_at) }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ d.initiator?.name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                            {{ d.items_count }} ligne(s)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-base font-bold text-emerald-700">{{ formatMoney(d.total) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-48 truncate">{{ d.notes || '—' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="route('disbursements.show', d.uuid)"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Voir
                                            </Link>
                                            <button v-if="canManage" @click="deleteDisbursement(d.uuid)"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="disbursements.data.length === 0">
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 font-medium">Aucun décaissement trouvé</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200" v-if="disbursements.links && disbursements.data.length > 0">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                Affichage de <span class="font-semibold">{{ disbursements.data.length }}</span> décaissement(s)
                            </div>
                            <div class="flex gap-1">
                                <template v-for="(link, key) in disbursements.links" :key="key">
                                    <div v-if="link.url === null" class="px-3 py-2 text-sm text-gray-400 border border-gray-200 rounded-lg bg-white cursor-not-allowed" v-html="link.label" />
                                    <Link v-else class="px-3 py-2 text-sm border rounded-lg transition-all"
                                        :class="link.active ? 'bg-emerald-600 text-white border-emerald-600 font-semibold shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:border-emerald-300 hover:text-emerald-600'"
                                        :href="link.url" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Period Print ── -->
        <div v-if="isPrinting" class="prd-wrapper">
            <div class="prd-header">
                <div>
                    <h1>RAPPORT DES DÉCAISSEMENTS</h1>
                    <p class="prd-period">Période : {{ formatPeriod }}</p>
                </div>
                <div class="prd-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Sys E-Dépôt Pharma
                </div>
            </div>

            <div class="prd-summary">
                <div class="prd-card">
                    <div class="prd-card-label">Décaissements</div>
                    <div class="prd-card-value">{{ disbursementsForPrint?.length ?? 0 }}</div>
                </div>
                <div class="prd-card">
                    <div class="prd-card-label">Jours concernés</div>
                    <div class="prd-card-value">{{ disbByDay.length }}</div>
                </div>
                <div class="prd-card">
                    <div class="prd-card-label">Montant total</div>
                    <div class="prd-card-value-sm">{{ formatMoney(printGrandTotal) }}</div>
                </div>
            </div>

            <div v-for="group in disbByDay" :key="group.day" class="prd-day-group">
                <h2 class="prd-day-title">{{ group.day }}</h2>
                <div v-for="d in group.items" :key="d.id" class="prd-item">
                    <div class="prd-item-header">
                        <span class="prd-item-id">DEC-{{ String(d.id).padStart(4, '0') }}</span>
                        <span class="prd-item-who">Initié par : <strong>{{ d.initiator?.name ?? 'N/A' }}</strong></span>
                        <span v-if="d.notes" class="prd-item-notes">{{ d.notes }}</span>
                    </div>
                    <table class="prd-table">
                        <thead>
                            <tr>
                                <th>Désignation</th>
                                <th class="right">Quantité</th>
                                <th class="right">Prix unitaire</th>
                                <th class="right">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in d.items" :key="i">
                                <td>{{ item.designation }}</td>
                                <td class="right">{{ Number(item.quantite).toLocaleString('fr-FR') }}</td>
                                <td class="right">{{ formatMoney(item.prix_unitaire) }}</td>
                                <td class="right bold">{{ formatMoney(item.montant) }}</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" class="right bold">TOTAL</td>
                                <td class="right bold grand-total">{{ formatMoney(d.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="prd-signature">
                        <div class="prd-sig-box">
                            <div class="prd-sig-label">Initié par</div>
                            <div class="prd-sig-name">{{ d.initiator?.name ?? '—' }}</div>
                            <div class="prd-sig-line">Signature : ___________________</div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="prd-footer">Sys E-Dépôt Pharma — Lumière Afrique Group Sarl — Période : {{ formatPeriod }}</p>
        </div>
    </AppLayout>
</template>

<style scoped>
.prd-wrapper { display: none; }
.prd-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #059669; padding-bottom:14px; margin-bottom:20px; font-family:'Segoe UI',Arial,sans-serif; }
.prd-header h1 { font-size:20px; font-weight:800; color:#065f46; letter-spacing:.5px; }
.prd-period { font-size:12px; color:#6b7280; margin-top:4px; }
.prd-logo { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#059669; }
.prd-logo svg { width:22px; height:22px; }
.prd-summary { display:flex; gap:12px; margin-bottom:22px; }
.prd-card { flex:1; padding:14px; border-radius:8px; background:#ecfdf5; border:1px solid #a7f3d0; text-align:center; font-family:'Segoe UI',Arial,sans-serif; }
.prd-card-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#374151; margin-bottom:5px; }
.prd-card-value { font-size:26px; font-weight:800; color:#065f46; }
.prd-card-value-sm { font-size:18px; font-weight:800; color:#065f46; }
.prd-day-group { margin-bottom:24px; }
.prd-day-title { font-size:13px; font-weight:700; text-transform:capitalize; color:#065f46; background:#ecfdf5; border-left:4px solid #059669; padding:6px 10px; margin-bottom:10px; font-family:'Segoe UI',Arial,sans-serif; }
.prd-item { margin-bottom:18px; }
.prd-item-header { display:flex; align-items:center; gap:14px; font-size:11px; font-family:'Segoe UI',Arial,sans-serif; margin-bottom:5px; }
.prd-item-id { font-weight:700; color:#374151; font-size:12px; }
.prd-item-who { color:#374151; }
.prd-item-notes { color:#6b7280; font-style:italic; flex:1; }
.prd-table { width:100%; border-collapse:collapse; margin-bottom:8px; font-family:'Segoe UI',Arial,sans-serif; font-size:11px; }
.prd-table th { background:#059669; color:white; padding:6px 10px; text-align:left; font-size:10px; text-transform:uppercase; }
.prd-table td { padding:5px 10px; border-bottom:1px solid #e5e7eb; }
.prd-table tbody tr:nth-child(even) td { background:#f9fafb; }
.prd-table .total-row td { border-top:2px solid #059669; background:#ecfdf5; font-weight:700; }
.grand-total { font-size:13px; color:#065f46; }
.prd-signature { display:flex; justify-content:flex-end; margin-top:8px; }
.prd-sig-box { text-align:center; font-family:'Segoe UI',Arial,sans-serif; font-size:11px; }
.prd-sig-label { font-size:10px; font-weight:700; text-transform:uppercase; color:#6b7280; margin-bottom:3px; }
.prd-sig-name { font-weight:700; color:#374151; margin-bottom:20px; }
.prd-sig-line { color:#9ca3af; font-size:11px; }
.right { text-align:right; }
.bold { font-weight:700; }
.prd-footer { text-align:center; font-size:10px; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:10px; margin-top:16px; font-family:'Segoe UI',Arial,sans-serif; }
</style>

<style>
@media print {
    body.printing-disbursements * { visibility: hidden !important; }
    body.printing-disbursements .prd-wrapper,
    body.printing-disbursements .prd-wrapper * { visibility: visible !important; }
    body.printing-disbursements .prd-wrapper {
        position: fixed !important; top: 0 !important; left: 0 !important;
        width: 100% !important; background: white !important;
        display: block !important; padding: 20px !important;
    }
    body { background: white; margin: 0; }
    @page { margin: 1cm; }
    .prd-item { page-break-inside: avoid; }
    .prd-day-group { page-break-inside: avoid; }
}
</style>
