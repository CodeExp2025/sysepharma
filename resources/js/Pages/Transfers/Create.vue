<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

// Toast notification helper
const showNotification = (message, type = 'success') => {
    const existing = document.getElementById('toast-notification');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.id = 'toast-notification';
    toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
    }`;
    toast.innerHTML = `
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success'
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
                }
            </svg>
            <span class="font-medium">${message}</span>
        </div>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-100%)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
};

const props = defineProps({
    depots: Array,
});

const form = useForm({
    depot_id: '',
    items: [],
});

const cartItems      = ref([]);
const barcodeInput   = ref('');
const lookupError    = ref('');
const lookupLoading  = ref(false);
const quantityInput  = ref(1);
const pendingUnit    = ref(null);
const submitCooldown = ref(false);

// Reset cooldown when depot changes
watch(() => form.depot_id, () => {
    submitCooldown.value = false;
});

const lookupUnit = async () => {
    const barcode = String(barcodeInput.value ?? '').trim();
    if (!barcode) return;
    lookupError.value   = '';
    lookupLoading.value = true;
    pendingUnit.value   = null;

    try {
        const resp = await window.axios.get(route('transfers.lookup'), { params: { barcode } });
        const unit = resp.data;

        if (unit.status !== 'en_stock') {
            lookupError.value = `L'unité ${barcode} n'est pas en stock.`;
            return;
        }
        if (unit.current_location_type !== 'pharmacy') {
            lookupError.value = `L'unité ${barcode} ne se trouve pas à la pharmacie.`;
            return;
        }
        if (cartItems.value.some(i => i.drug_unit_id === unit.id)) {
            lookupError.value = `L'unité ${barcode} est déjà dans la liste.`;
            return;
        }
        pendingUnit.value   = unit;
        quantityInput.value = unit.quantite_actuelle;
        barcodeInput.value  = '';
    } catch (err) {
        lookupError.value = err.response?.data?.error ?? 'Erreur lors de la recherche.';
    } finally {
        lookupLoading.value = false;
    }
};

const confirmAddToCart = () => {
    if (!pendingUnit.value) return;
    const qty = parseInt(quantityInput.value, 10);
    if (qty < 1 || qty > pendingUnit.value.quantite_actuelle) {
        lookupError.value = `Quantité invalide. Disponible: ${pendingUnit.value.quantite_actuelle}`;
        return;
    }
    cartItems.value.push({
        drug_unit_id:      pendingUnit.value.id,
        barcode:           pendingUnit.value.barcode,
        drug_name:         pendingUnit.value.drug?.name ?? '—',
        expiration_date:   pendingUnit.value.expiration_date,
        quantite_actuelle: pendingUnit.value.quantite_actuelle,
        price:             pendingUnit.value.price,
        quantity:          qty,
    });
    pendingUnit.value   = null;
    quantityInput.value = 1;
    lookupError.value   = '';
};

const cancelPending = () => {
    pendingUnit.value   = null;
    quantityInput.value = 1;
    lookupError.value   = '';
};

const removeItem = (index) => { cartItems.value.splice(index, 1); };
const clearAll   = () => { cartItems.value = []; pendingUnit.value = null; };

const submit = () => {
    if (submitCooldown.value) return;

    form.items = cartItems.value.map(i => ({
        drug_unit_id: i.drug_unit_id,
        quantity:     i.quantity,
    }));

    // Activate cooldown
    submitCooldown.value = true;

    form.post(route('transfers.store'), {
        onSuccess: (response) => {
            cartItems.value = [];
            barcodeInput.value = '';

            // Show success notification
            const warnings = response?.props?.warnings;
            if (warnings && warnings.length > 0) {
                showNotification(`Transfert réussi avec ${warnings.length} unité(s) périmée(s) marquée(s) à détruire.`, 'success');
            } else {
                showNotification('Transfert effectué avec succès !', 'success');
            }

            // Reset cooldown after 10 seconds
            setTimeout(() => {
                submitCooldown.value = false;
            }, 10000);
        },
        onError: () => {
            // Reset cooldown immediately on error
            submitCooldown.value = false;
        },
    });
};

const selectedDepot = computed(() =>
    form.depot_id ? props.depots.find(d => d.id == form.depot_id) : null
);
const totalItems = computed(() => cartItems.value.reduce((s, i) => s + i.quantity, 0));

const fmtDate = (d) => {
    if (!d) return 'N/A';
    return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};
</script>

<template>
    <AppLayout title="Transfert de Stock">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        Transfert de Stock
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Transférez des unités vers un dépôt
                    </p>
                </div>
                <Link
                    :href="route('transfers.index')"
                    class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-200 transition-all duration-150"
                >
                    ← Retour
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div v-if="form.errors.error" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">
                        {{ form.errors.error }}
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column -->
                        <div class="lg:col-span-2 space-y-6">

                            <!-- Depot Selection -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 bg-gradient-to-r from-orange-50 to-orange-100 border-b border-orange-200">
                                    <h3 class="text-lg font-bold text-orange-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        Destination
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2" for="depot">
                                        Dépôt de destination <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select
                                            v-model="form.depot_id"
                                            id="depot"
                                            class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-150 appearance-none bg-white"
                                            :class="{ 'border-red-500 ring-2 ring-red-200': form.errors.depot_id }"
                                        >
                                            <option value="" disabled>Sélectionner un dépôt</option>
                                            <option v-for="depot in depots" :key="depot.id" :value="depot.id">
                                                {{ depot.name }}
                                            </option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.depot_id" class="mt-2 text-sm text-red-600">{{ form.errors.depot_id }}</p>

                                    <div v-if="selectedDepot" class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg text-sm text-orange-800">
                                        <p class="font-semibold">{{ selectedDepot.name }}</p>
                                        <p class="text-xs text-orange-600 mt-0.5">{{ selectedDepot.address || 'Adresse non renseignée' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Barcode Lookup -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-blue-200">
                                    <h3 class="text-lg font-bold text-blue-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                        Scanner une Unité
                                    </h3>
                                </div>
                                <div class="p-6">
                                    <!-- Scan input -->
                                    <div class="flex gap-2">
                                        <input
                                            v-model="barcodeInput"
                                            @keyup.enter.prevent="lookupUnit"
                                            type="text"
                                            placeholder="Scanner ou saisir le code-barres"
                                            class="flex-grow px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-150"
                                            :disabled="!!pendingUnit"
                                            autofocus
                                        >
                                        <button
                                            type="button"
                                            @click="lookupUnit"
                                            :disabled="lookupLoading || !barcodeInput || !!pendingUnit"
                                            class="px-5 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150"
                                        >
                                            <svg v-if="lookupLoading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                            </svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Appuyez sur Entrée pour rechercher</p>

                                    <!-- Error -->
                                    <p v-if="lookupError" class="mt-3 text-sm text-red-600 bg-red-50 border border-red-200 px-3 py-2 rounded-lg">
                                        {{ lookupError }}
                                    </p>

                                    <!-- Pending Unit Card -->
                                    <div v-if="pendingUnit" class="mt-4 p-4 bg-blue-50 border border-blue-300 rounded-lg">
                                        <div class="flex items-start justify-between mb-3">
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ pendingUnit.drug?.name ?? '—' }}</p>
                                                <p class="text-xs text-gray-500 font-mono mt-0.5">{{ pendingUnit.barcode }}</p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    Exp.: {{ fmtDate(pendingUnit.expiration_date) }}
                                                    &nbsp;·&nbsp;
                                                    Disponible: <span class="font-semibold text-blue-700">{{ pendingUnit.quantite_actuelle }}</span>
                                                </p>
                                            </div>
                                            <span class="text-xs font-bold text-blue-800 bg-blue-200 px-2 py-1 rounded-full">
                                                {{ Number(pendingUnit.price).toLocaleString('fr-FR') }} FCFA
                                            </span>
                                        </div>

                                        <!-- Quantity selector -->
                                        <div class="flex items-center gap-3 mb-4">
                                            <span class="text-sm font-semibold text-gray-700">Quantité à transférer :</span>
                                            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                                <button
                                                    type="button"
                                                    @click="quantityInput = Math.max(1, quantityInput - 1)"
                                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold transition-colors"
                                                >−</button>
                                                <input
                                                    v-model.number="quantityInput"
                                                    type="number"
                                                    min="1"
                                                    :max="pendingUnit.quantite_actuelle"
                                                    class="w-16 text-center py-2 border-0 focus:ring-0 text-sm font-semibold"
                                                >
                                                <button
                                                    type="button"
                                                    @click="quantityInput = Math.min(pendingUnit.quantite_actuelle, quantityInput + 1)"
                                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold transition-colors"
                                                >+</button>
                                            </div>
                                            <button
                                                type="button"
                                                @click="quantityInput = pendingUnit.quantite_actuelle"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-semibold underline"
                                            >Tout</button>
                                        </div>

                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                @click="confirmAddToCart"
                                                class="flex-1 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-all duration-150"
                                            >
                                                Ajouter au transfert
                                            </button>
                                            <button
                                                type="button"
                                                @click="cancelPending"
                                                class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-all duration-150"
                                            >
                                                Annuler
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Cart List -->
                                    <div v-if="cartItems.length > 0" class="mt-6">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-semibold text-gray-700">Unités à transférer ({{ cartItems.length }})</span>
                                            <button
                                                type="button"
                                                @click="clearAll"
                                                class="text-xs text-red-600 hover:text-red-700 font-medium"
                                            >Tout effacer</button>
                                        </div>
                                        <div class="max-h-72 overflow-y-auto space-y-2 bg-gray-50 rounded-lg p-3">
                                            <div
                                                v-for="(item, index) in cartItems"
                                                :key="item.drug_unit_id"
                                                class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg"
                                            >
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <span class="w-7 h-7 flex items-center justify-center bg-blue-100 text-blue-700 text-xs font-bold rounded-full shrink-0">
                                                        {{ index + 1 }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ item.drug_name }}</p>
                                                        <p class="text-xs text-gray-500 font-mono truncate">{{ item.barcode }}</p>
                                                        <p class="text-xs text-gray-400">Exp.: {{ fmtDate(item.expiration_date) }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3 shrink-0 ml-2">
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">
                                                        × {{ item.quantity }}
                                                    </span>
                                                    <button
                                                        type="button"
                                                        @click="removeItem(index)"
                                                        class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-150"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty State -->
                                    <div v-else-if="!pendingUnit" class="text-center py-8 mt-4">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                        </div>
                                        <p class="text-gray-500 text-sm">Aucune unité dans le transfert</p>
                                        <p class="text-gray-400 text-xs mt-1">Scannez un code-barres pour commencer</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Summary -->
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden sticky top-6">
                                <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-green-200">
                                    <h3 class="text-lg font-bold text-green-900 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        Résumé
                                    </h3>
                                </div>

                                <div class="p-6 space-y-4">
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Destination</span>
                                        <span class="text-sm font-semibold text-gray-900">{{ selectedDepot?.name || '—' }}</span>
                                    </div>

                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Boîtes</span>
                                        <span class="text-sm font-bold text-blue-600">{{ cartItems.length }}</span>
                                    </div>

                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <span class="text-sm text-gray-600">Total unités</span>
                                        <span class="text-sm font-bold text-blue-800">{{ totalItems }}</span>
                                    </div>

                                    <div class="pt-2">
                                        <button
                                            type="submit"
                                            class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 border border-transparent rounded-lg font-semibold text-sm text-white shadow-md hover:shadow-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed relative overflow-hidden"
                                            :disabled="form.processing || !form.depot_id || cartItems.length === 0 || submitCooldown"
                                        >
                                            <!-- Cooldown overlay -->
                                            <div
                                                v-if="submitCooldown"
                                                class="absolute inset-0 bg-gray-500/30 flex items-center justify-center"
                                            >
                                                <span class="text-xs font-medium">Attendez 10s...</span>
                                            </div>
                                            <span class="flex items-center justify-center gap-2">
                                                <svg v-if="!form.processing && !submitCooldown" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <svg v-else-if="form.processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                                </svg>
                                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ form.processing ? 'Transfert en cours...' : (submitCooldown ? 'Rechargement...' : 'Valider le Transfert') }}
                                            </span>
                                        </button>
                                        <p v-if="submitCooldown" class="mt-2 text-xs text-center text-orange-600">
                                            Bouton grisé pendant 10s pour éviter les doubles validations.
                                            <br>Changer de dépôt pour réinitialiser.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes spin {
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
