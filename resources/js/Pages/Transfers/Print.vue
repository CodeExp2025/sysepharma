<script setup>
import { onBeforeUnmount, onMounted } from 'vue';

const props = defineProps({
    transfer: Object,
});

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const totalUnits = () => {
    return (props.transfer.items ?? []).reduce((s, i) => s + (i.quantity ?? 1), 0);
};

onMounted(() => {
    document.body.classList.add('printing-transfer');
    window.print();
});

const onAfterPrint = () => document.body.classList.remove('printing-transfer');
window.addEventListener('afterprint', onAfterPrint);
onBeforeUnmount(() => window.removeEventListener('afterprint', onAfterPrint));
</script>

<template>
    <div class="print-page">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>BON DE TRANSFERT</h1>
                <p class="ref">Réf. #{{ transfer.id }}</p>
            </div>
            <div class="header-right">
                <p><strong>Date :</strong> {{ formatDateTime(transfer.created_at) }}</p>
                <p><strong>Pharmacie :</strong> {{ transfer.depot?.pharmacy?.name ?? '—' }}</p>
            </div>
        </div>

        <!-- Parties -->
        <div class="parties">
            <div class="party">
                <h3>EXPÉDITEUR</h3>
                <p>{{ transfer.depot?.pharmacy?.name ?? '—' }}</p>
                <p class="sub">Pharmacie</p>
            </div>
            <div class="arrow">→</div>
            <div class="party">
                <h3>DESTINATAIRE</h3>
                <p>{{ transfer.depot?.name ?? '—' }}</p>
                <p class="sub">{{ transfer.depot?.address ?? '' }}</p>
            </div>
            <div class="party">
                <h3>EFFECTUÉ PAR</h3>
                <p>{{ transfer.user?.name ?? '—' }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Médicament</th>
                    <th>Code-barres</th>
                    <th>Expiration</th>
                    <th>Qté</th>
                    <th>Prix unit.</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, idx) in transfer.items" :key="item.id">
                    <td class="center">{{ idx + 1 }}</td>
                    <td>
                        <strong>{{ item.drug_unit?.drug?.name ?? '—' }}</strong>
                        <span v-if="item.drug_unit?.drug?.category?.name" class="cat"> — {{ item.drug_unit.drug.category.name }}</span>
                    </td>
                    <td class="mono">{{ item.drug_unit?.barcode ?? '—' }}</td>
                    <td class="center">{{ formatDate(item.drug_unit?.expiration_date) }}</td>
                    <td class="center bold">{{ item.quantity ?? 1 }}</td>
                    <td class="right">{{ item.drug_unit?.price ? Number(item.drug_unit.price).toLocaleString('fr-FR') : 0 }} FCFA</td>
                    <td class="right bold">{{ item.drug_unit?.price ? Number(item.drug_unit.price * (item.quantity ?? 1)).toLocaleString('fr-FR') : 0 }} FCFA</td>
                </tr>
                <tr v-if="!transfer.items || transfer.items.length === 0">
                    <td colspan="7" class="center empty">Aucune unité</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="right bold">Totaux</td>
                    <td class="center bold">{{ totalUnits() }}</td>
                    <td></td>
                    <td class="right bold">
                        {{
                            (transfer.items ?? []).reduce((s, i) =>
                                s + (Number(i.drug_unit?.price ?? 0) * (i.quantity ?? 1)), 0
                            ).toLocaleString('fr-FR')
                        }} FCFA
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-box">
                <p class="sig-label">Signature Expéditeur</p>
                <div class="sig-line"></div>
                <p class="sig-name">{{ transfer.user?.name ?? '' }}</p>
            </div>
            <div class="sig-box">
                <p class="sig-label">Signature Destinataire</p>
                <div class="sig-line"></div>
                <p class="sig-name">{{ transfer.depot?.name ?? '' }}</p>
            </div>
        </div>

        <p class="footer">Document généré le {{ formatDateTime(new Date().toISOString()) }} — Transfert #{{ transfer.id }}</p>
    </div>
</template>

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; }

.print-page { max-width: 800px; margin: 0 auto; padding: 30px; }

.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 3px solid #2563eb;
    padding-bottom: 16px;
    margin-bottom: 24px;
}
.header h1 { font-size: 22px; font-weight: 800; color: #1e40af; letter-spacing: 1px; }
.header .ref { font-size: 13px; color: #6b7280; margin-top: 4px; }
.header-right { text-align: right; line-height: 1.8; }

.parties {
    display: flex;
    gap: 16px;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
}
.party { flex: 1; }
.party h3 { font-size: 10px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.party p { font-size: 13px; font-weight: 600; }
.party .sub { font-size: 11px; color: #6b7280; font-weight: 400; margin-top: 2px; }
.arrow { font-size: 24px; color: #2563eb; font-weight: bold; flex: 0; }

table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
th { background: #1e40af; color: #fff; padding: 10px 8px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }
td { padding: 9px 8px; border-bottom: 1px solid #e5e7eb; font-size: 12px; }
tbody tr:nth-child(even) td { background: #f9fafb; }
tfoot td { border-top: 2px solid #2563eb; padding-top: 10px; background: #eff6ff; }
.cat { color: #6b7280; font-weight: 400; font-size: 11px; }
.center { text-align: center; }
.right { text-align: right; }
.bold { font-weight: 700; }
.mono { font-family: monospace; font-size: 11px; }
.empty { padding: 20px; color: #9ca3af; font-style: italic; }

.signatures {
    display: flex;
    gap: 60px;
    margin: 40px 0 24px;
}
.sig-box { flex: 1; }
.sig-label { font-size: 11px; font-weight: 700; color: #374151; margin-bottom: 40px; }
.sig-line { border-bottom: 1px solid #374151; margin-bottom: 6px; }
.sig-name { font-size: 11px; color: #6b7280; }

.footer { text-align: center; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 12px; }

@media print {
    /* Isoler l'impression du bon de transfert */
    body.printing-transfer * { visibility: visible !important; }
    body.printing-transfer { margin: 0; background: white; }
    body.printing-transfer .print-page { padding: 15px; max-width: 100%; }
    @page { margin: 1cm; }
}
</style>
