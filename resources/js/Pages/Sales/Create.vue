<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const form = useForm({ items: [] });

const page           = usePage();
const authUser       = computed(() => page.props.auth?.user);
const activePharmacy = computed(() => page.props.auth?.active_pharmacy ?? null);

const cartItems      = ref([]);
const barcodeInput   = ref('');
const scanError      = ref('');
const scanLoading    = ref(false);
const pendingUnit    = ref(null);
const quantityInput  = ref(1);
const lastSaleTotal  = ref(null);
const lastSaleItems  = ref([]);
const lastSaleDate   = ref(null);

const scanUnit = async () => {
    const barcode = String(barcodeInput.value ?? '').trim();
    if (!barcode) return;

    scanError.value   = '';
    scanLoading.value = true;
    pendingUnit.value = null;

    try {
        const resp = await window.axios.get(route('sales.scan'), { params: { barcode } });
        const unit = resp.data;

        // Check if already in cart — if so, just increment
        const existing = cartItems.value.find(i => i.drug_unit_id === unit.id);
        if (existing) {
            if (existing.quantity < unit.quantite_actuelle) {
                existing.quantity++;
                barcodeInput.value = '';
            } else {
                scanError.value = `Stock maximum atteint pour ${unit.drug?.name ?? barcode} (${unit.quantite_actuelle} disponible(s)).`;
            }
            return;
        }

        pendingUnit.value   = unit;
        quantityInput.value = 1;
        barcodeInput.value  = '';
    } catch (err) {
        scanError.value = err.response?.data?.error ?? 'Unité introuvable ou non disponible.';
    } finally {
        scanLoading.value = false;
    }
};

const confirmAddToCart = () => {
    if (!pendingUnit.value) return;
    const qty = parseInt(quantityInput.value, 10);
    if (qty < 1 || qty > pendingUnit.value.quantite_actuelle) {
        scanError.value = `Quantité invalide. Maximum disponible : ${pendingUnit.value.quantite_actuelle}`;
        return;
    }
    cartItems.value.push({
        drug_unit_id:      pendingUnit.value.id,
        barcode:           pendingUnit.value.barcode,
        drug_name:         pendingUnit.value.drug?.name ?? '—',
        expiration_date:   pendingUnit.value.expiration_date,
        price:             Number(pendingUnit.value.price),
        quantite_actuelle: pendingUnit.value.quantite_actuelle,
        quantity:          qty,
    });
    pendingUnit.value   = null;
    quantityInput.value = 1;
    scanError.value     = '';
};

const cancelPending = () => {
    pendingUnit.value   = null;
    quantityInput.value = 1;
    scanError.value     = '';
};

const removeItem   = (index) => { cartItems.value.splice(index, 1); };
const incrementQty = (item)  => { if (item.quantity < item.quantite_actuelle) item.quantity++; };
const decrementQty = (item)  => { if (item.quantity > 1) item.quantity--; };

const grandTotal = computed(() =>
    cartItems.value.reduce((s, i) => s + i.price * i.quantity, 0)
);

const submit = () => {
    form.items = cartItems.value.map(i => ({
        drug_unit_id: i.drug_unit_id,
        quantity:     i.quantity,
    }));
    form.post(route('sales.store'), {
        onSuccess: () => {
            lastSaleTotal.value = grandTotal.value;
            lastSaleItems.value = [...cartItems.value];
            lastSaleDate.value  = new Date();
            cartItems.value     = [];
            barcodeInput.value  = '';
            scanError.value     = '';
        },
    });
};

const printReceipt = () => { window.print(); };
</script>

<template>
    <AppLayout title="Point de Vente">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        Point de Vente (POS)
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">Scanner les produits pour composer le panier</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="px-3 py-1.5 bg-green-100 border border-green-300 rounded-lg flex items-center gap-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm font-semibold text-green-800">Système actif</span>
                    </div>
                    <Link :href="route('sales.index')" class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-200 transition-all duration-150">
                        Historique
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

                <!-- Success banner -->
                <div v-if="lastSaleTotal !== null" class="mb-6 bg-green-50 border border-green-300 rounded-xl px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-green-900">Vente enregistrée avec succès !</p>
                            <p class="text-sm text-green-700">Total encaissé : <strong>{{ lastSaleTotal.toLocaleString('fr-FR') }} FCFA</strong></p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="printReceipt" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition-all duration-150">
                            🖨 Imprimer reçu
                        </button>
                        <button @click="lastSaleTotal = null" class="px-3 py-2 text-green-700 hover:text-green-900 text-sm">✕</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: Scanner + Cart -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Scanner Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-blue-200">
                                <h3 class="text-lg font-bold text-blue-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    Scanner un Produit
                                </h3>
                            </div>
                            <div class="p-6">
                                <!-- Scan input -->
                                <div class="flex gap-2">
                                    <input
                                        v-model="barcodeInput"
                                        @keyup.enter.prevent="scanUnit"
                                        type="text"
                                        placeholder="Scanner ou saisir le code-barres..."
                                        class="flex-grow px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-150 text-lg"
                                        :disabled="!!pendingUnit"
                                        autofocus
                                    >
                                    <button
                                        type="button"
                                        @click="scanUnit"
                                        :disabled="scanLoading || !barcodeInput || !!pendingUnit"
                                        class="px-5 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150"
                                    >
                                        <svg v-if="scanLoading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Appuyez sur Entrée ou cliquez pour rechercher. Si le produit est déjà dans le panier, la quantité est incrémentée.</p>

                                <!-- Error -->
                                <p v-if="scanError" class="mt-3 text-sm text-red-600 bg-red-50 border border-red-200 px-3 py-2 rounded-lg">
                                    {{ scanError }}
                                </p>

                                <!-- Pending unit -->
                                <div v-if="pendingUnit" class="mt-4 p-4 bg-blue-50 border border-blue-300 rounded-lg">
                                    <div class="flex items-start justify-between mb-3">
                                        <div>
                                            <p class="font-bold text-gray-900 text-lg">{{ pendingUnit.drug?.name ?? '—' }}</p>
                                            <p class="text-xs text-gray-500 font-mono mt-0.5">{{ pendingUnit.barcode }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Exp. : {{ pendingUnit.expiration_date ?? 'N/A' }}
                                                &nbsp;·&nbsp;
                                                Disponible : <span class="font-semibold text-blue-700">{{ pendingUnit.quantite_actuelle }}</span>
                                            </p>
                                        </div>
                                        <span class="text-sm font-bold text-blue-800 bg-blue-200 px-3 py-1 rounded-full whitespace-nowrap">
                                            {{ Number(pendingUnit.price).toLocaleString('fr-FR') }} FCFA
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-3 mb-4">
                                        <span class="text-sm font-semibold text-gray-700">Quantité :</span>
                                        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                            <button type="button" @click="quantityInput = Math.max(1, quantityInput - 1)" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 font-bold transition-colors">−</button>
                                            <input v-model.number="quantityInput" type="number" min="1" :max="pendingUnit.quantite_actuelle" class="w-16 text-center py-2 border-0 focus:ring-0 text-sm font-semibold">
                                            <button type="button" @click="quantityInput = Math.min(pendingUnit.quantite_actuelle, quantityInput + 1)" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 font-bold transition-colors">+</button>
                                        </div>
                                        <span class="text-sm text-gray-600">= <strong>{{ (Number(pendingUnit.price) * quantityInput).toLocaleString('fr-FR') }} FCFA</strong></span>
                                    </div>

                                    <div class="flex gap-2">
                                        <button type="button" @click="confirmAddToCart" class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-all duration-150">
                                            Ajouter au panier
                                        </button>
                                        <button type="button" @click="cancelPending" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-all duration-150">
                                            Annuler
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cart -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-bold text-gray-900">
                                        Panier
                                        <span v-if="cartItems.length" class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-800 text-sm font-bold rounded-full">{{ cartItems.length }}</span>
                                    </h3>
                                    <button v-if="cartItems.length" type="button" @click="cartItems = []" class="text-xs text-red-600 hover:text-red-700 font-medium">
                                        Vider le panier
                                    </button>
                                </div>
                            </div>

                            <div class="divide-y divide-gray-100">
                                <div v-for="(item, index) in cartItems" :key="item.drug_unit_id" class="flex items-center gap-4 px-6 py-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-900 truncate">{{ item.drug_name }}</p>
                                        <p class="text-xs text-gray-500 font-mono">{{ item.barcode }}</p>
                                        <p class="text-xs text-gray-400">Exp. : {{ item.expiration_date ?? 'N/A' }}</p>
                                    </div>

                                    <!-- Qty stepper -->
                                    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden shrink-0">
                                        <button type="button" @click="decrementQty(item)" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 font-bold text-sm transition-colors">−</button>
                                        <span class="w-10 text-center text-sm font-bold">{{ item.quantity }}</span>
                                        <button type="button" @click="incrementQty(item)" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 font-bold text-sm transition-colors">+</button>
                                    </div>

                                    <div class="text-right shrink-0 w-24">
                                        <p class="text-sm font-bold text-gray-900">{{ (item.price * item.quantity).toLocaleString('fr-FR') }} FCFA</p>
                                        <p class="text-xs text-gray-400">{{ item.price.toLocaleString('fr-FR') }} × {{ item.quantity }}</p>
                                    </div>

                                    <button type="button" @click="removeItem(index)" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-150 shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Empty cart -->
                                <div v-if="!cartItems.length && !pendingUnit" class="text-center py-12 text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <p class="text-sm">Panier vide</p>
                                    <p class="text-xs mt-1">Scannez un produit pour commencer</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Summary -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                            <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-green-200">
                                <h3 class="text-lg font-bold text-green-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                                    </svg>
                                    Ticket
                                </h3>
                            </div>

                            <div class="p-6 space-y-3">
                                <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Articles</span>
                                    <span class="font-semibold">{{ cartItems.length }}</span>
                                </div>
                                <div class="flex justify-between text-sm py-2 border-b border-gray-100">
                                    <span class="text-gray-600">Total unités</span>
                                    <span class="font-semibold">{{ cartItems.reduce((s,i) => s + i.quantity, 0) }}</span>
                                </div>

                                <div v-for="item in cartItems" :key="item.drug_unit_id" class="flex justify-between text-xs text-gray-500 py-0.5">
                                    <span class="truncate mr-2">{{ item.drug_name }} ×{{ item.quantity }}</span>
                                    <span class="shrink-0">{{ (item.price * item.quantity).toLocaleString('fr-FR') }}</span>
                                </div>

                                <div class="flex justify-between py-3 border-t-2 border-green-300 mt-2">
                                    <span class="font-bold text-gray-900 text-base">TOTAL</span>
                                    <span class="font-bold text-green-700 text-lg">{{ grandTotal.toLocaleString('fr-FR') }} FCFA</span>
                                </div>

                                <!-- Errors -->
                                <p v-if="form.errors.items" class="text-sm text-red-600 bg-red-50 border border-red-200 px-3 py-2 rounded-lg">{{ form.errors.items }}</p>

                                <button
                                    type="button"
                                    @click="submit"
                                    :disabled="form.processing || !cartItems.length"
                                    class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 border border-transparent rounded-lg font-bold text-sm text-white shadow-md hover:shadow-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span class="flex items-center justify-center gap-2">
                                        <svg v-if="!form.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg v-else class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                        </svg>
                                        {{ form.processing ? 'Enregistrement...' : 'Valider la vente' }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ── Print receipt (hidden on screen, visible on print) ── -->
        <div v-if="lastSaleTotal !== null" class="receipt-print">
            <div class="receipt-header">
                <div class="receipt-logo">⚕</div>
                <div class="receipt-brand">{{ activePharmacy?.name ?? 'Pharmacie' }}</div>
                <div class="receipt-sub">Sys E-Dépôt Pharma — Lumière Afrique Group Sarl</div>
                <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
                <div class="receipt-meta">
                    <span>Date : {{ lastSaleDate ? new Date(lastSaleDate).toLocaleString('fr-FR') : '' }}</span>
                    <span>Vendeur : {{ authUser?.name ?? '—' }}</span>
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
                    <tr v-for="item in lastSaleItems" :key="item.drug_unit_id">
                        <td>
                            <div class="item-name">{{ item.drug_name }}</div>
                            <div class="item-barcode">{{ item.barcode }}</div>
                        </td>
                        <td class="text-center">{{ item.quantity }}</td>
                        <td class="text-right">{{ item.price.toLocaleString('fr-FR') }}</td>
                        <td class="text-right">{{ (item.price * item.quantity).toLocaleString('fr-FR') }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-total">
                <span>TOTAL</span>
                <span>{{ lastSaleTotal.toLocaleString('fr-FR') }} FCFA</span>
            </div>
            <div class="receipt-divider">- - - - - - - - - - - - - - - - - - - -</div>
            <div class="receipt-footer">Merci de votre confiance !</div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes spin { to { transform: rotate(360deg); } }
.animate-spin { animation: spin 1s linear infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
.animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

/* Receipt hidden on screen (scoped) */
.receipt-print { display: none; }
</style>

<style>
/* ── Print receipt: global styles (no scoped hash) ── */
@media print {
    body * { visibility: hidden !important; }
    .receipt-print,
    .receipt-print * { visibility: visible !important; }
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
