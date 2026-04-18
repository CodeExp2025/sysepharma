<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    disbursement: Object,
});

const isPrinting = ref(false);

const printDisbursement = () => {
    isPrinting.value = true;
    document.body.classList.add('printing-single-disbursement');
    setTimeout(() => {
        window.print();
    }, 100);
};

const onAfterPrint = () => {
    isPrinting.value = false;
    document.body.classList.remove('printing-single-disbursement');
};

window.addEventListener('afterprint', onAfterPrint);

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'long', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const formatMoney = (amount) => {
    return Number(amount || 0).toLocaleString('fr-FR') + ' FCFA';
};
</script>

<template>
    <AppLayout :title="`Décaissement DEC-${String(disbursement.id).padStart(4, '0')}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        Décaissement DEC-{{ String(disbursement.id).padStart(4, '0') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Date : {{ formatDate(disbursement.performed_at) }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="printDisbursement"
                        class="px-4 py-2 bg-emerald-600 text-white border border-emerald-700 rounded-lg font-medium text-sm hover:bg-emerald-700 shadow-sm transition-all duration-150"
                    >
                        Imprimer
                    </button>
                    <Link
                        :href="route('disbursements.index')"
                        class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-200 transition-all duration-150"
                    >
                        ← Retour
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Recap Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-emerald-50 to-emerald-100 border-b border-emerald-200 flex justify-between items-center">
                        <div class="text-sm text-emerald-900 font-bold">Informations Générales</div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 ring-1 ring-gray-600/20">
                            {{ disbursement.items?.length || 0 }} ligne(s)
                        </span>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Initié par</div>
                                <div class="text-sm font-semibold text-gray-900">{{ disbursement.initiator?.name || '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Montant Total</div>
                                <div class="text-lg font-bold text-emerald-700">{{ formatMoney(disbursement.total) }}</div>
                            </div>
                            <div class="md:col-span-1">
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Notes</div>
                                <div class="text-sm text-gray-800 italic">{{ disbursement.notes || '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lignes de Décaissement -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-sm text-gray-900 font-bold">Détails du Décaissement</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Désignation</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Quantité</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Prix Unitaire</th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-emerald-700 uppercase tracking-wider">Montant</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="(item, i) in disbursement.items" :key="item.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ item.designation }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ Number(item.quantite).toLocaleString('fr-FR') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ formatMoney(item.prix_unitaire) }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-emerald-700 text-right">{{ formatMoney(item.montant) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 border-t border-gray-200">
                                    <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900 uppercase">
                                        Total Général
                                    </td>
                                    <td class="px-6 py-4 text-right text-lg font-bold text-emerald-700 whitespace-nowrap">
                                        {{ formatMoney(disbursement.total) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Printable Area -->
        <div v-if="isPrinting" class="print-single">
            <div class="print-header">
                <div>
                    <h1>DÉCAISSEMENT</h1>
                    <p class="print-meta">N° DEC-{{ String(disbursement.id).padStart(4, '0') }}</p>
                    <p class="print-meta">Date : {{ formatDate(disbursement.performed_at) }}</p>
                </div>
                <div class="print-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Sys E-Dépôt Pharma
                </div>
            </div>

            <div class="print-info-section">
                <div class="print-info-block">
                    <strong>Initié par :</strong> {{ disbursement.initiator?.name || '—' }}
                </div>
                <div v-if="disbursement.notes" class="print-info-block">
                    <strong>Notes :</strong> {{ disbursement.notes }}
                </div>
            </div>

            <table class="print-table">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th class="text-right">Qté</th>
                        <th class="text-right">Prix Unitaire</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in disbursement.items" :key="item.id">
                        <td>{{ item.designation }}</td>
                        <td class="text-right">{{ Number(item.quantite).toLocaleString('fr-FR') }}</td>
                        <td class="text-right">{{ formatMoney(item.prix_unitaire) }}</td>
                        <td class="text-right font-bold">{{ formatMoney(item.montant) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right font-bold sum-row">TOTAL DÉCAISSEMENT</td>
                        <td class="text-right font-bold sum-row sum-value">{{ formatMoney(disbursement.total) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="print-signatures">
                <div class="print-sig-box">
                    <div class="print-sig-title">Initié par</div>
                    <div class="print-sig-name">{{ disbursement.initiator?.name || '—' }}</div>
                    <div class="print-sig-line">Signature : ____________________</div>
                </div>
                <div class="print-sig-box">
                    <div class="print-sig-title">Validé par (Direction)</div>
                    <div class="print-sig-name">&nbsp;</div>
                    <div class="print-sig-line">Signature : ____________________</div>
                </div>
            </div>

            <div class="print-footer">
                Sys E-Dépôt Pharma — Lumière Afrique Group Sarl
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.print-single {
    display: none;
}

.text-right {
    text-align: right;
}

.font-bold {
    font-weight: 700;
}

.print-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 3px solid #059669;
    padding-bottom: 12px;
    margin-bottom: 20px;
    font-family: 'Segoe UI', Arial, sans-serif;
}

.print-header h1 {
    font-size: 22px;
    font-weight: 800;
    color: #065f46;
    letter-spacing: .5px;
    margin-bottom: 4px;
}

.print-meta {
    font-size: 13px;
    color: #374151;
    margin: 2px 0;
}

.print-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 700;
    color: #059669;
}

.print-logo svg {
    width: 24px;
    height: 24px;
}

.print-info-section {
    margin-bottom: 24px;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 13px;
    color: #1f2937;
    background: #f9fafb;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.print-info-block {
    margin-bottom: 4px;
}

.print-info-block strong {
    color: #374151;
}

.print-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 32px;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 12px;
}

.print-table th {
    background: #059669;
    color: white;
    padding: 8px 10px;
    text-transform: uppercase;
    font-size: 11px;
    text-align: left;
    border: 1px solid #059669;
}

.print-table th.text-right {
    text-align: right;
}

.print-table td {
    padding: 8px 10px;
    border: 1px solid #e5e7eb;
    color: #374151;
}

.print-table tbody tr:nth-child(even) td {
    background: #f9fafb;
}

.print-table .sum-row {
    background: #ecfdf5 !important;
    border-top: 2px solid #059669;
    padding-top: 10px;
    padding-bottom: 10px;
    text-transform: uppercase;
    font-size: 12px;
    color: #065f46;
}

.print-table .sum-value {
    font-size: 14px;
}

.print-signatures {
    display: flex;
    justify-content: space-around;
    margin-top: 40px;
    margin-bottom: 40px;
    font-family: 'Segoe UI', Arial, sans-serif;
}

.print-sig-box {
    text-align: center;
    width: 40%;
}

.print-sig-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    color: #6b7280;
    margin-bottom: 6px;
}

.print-sig-name {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 30px;
    min-height: 20px;
}

.print-sig-line {
    font-size: 12px;
    color: #9ca3af;
}

.print-footer {
    text-align: center;
    font-size: 10px;
    color: #9ca3af;
    border-top: 1px solid #e5e7eb;
    padding-top: 12px;
    font-family: 'Segoe UI', Arial, sans-serif;
    position: absolute;
    bottom: 20px;
    width: 100%;
}
</style>

<style>
@media print {
    body.printing-single-disbursement * {
        visibility: hidden !important;
    }
    body.printing-single-disbursement .print-single,
    body.printing-single-disbursement .print-single * {
        visibility: visible !important;
    }
    body.printing-single-disbursement .print-single {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        display: block !important;
        padding: 20px !important;
        margin: 0 !important;
    }
    body {
        margin: 0;
        background: white;
    }
    @page {
        margin: 1.5cm;
    }
}
</style>
