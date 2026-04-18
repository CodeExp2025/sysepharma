<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    performed_at: new Date().toISOString().slice(0, 16),
    notes: '',
    items: [
        { designation: '', quantite: 1, prix_unitaire: 0 }
    ],
});

const addItem = () => {
    form.items.push({ designation: '', quantite: 1, prix_unitaire: 0 });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (Number(item.quantite) * Number(item.prix_unitaire) || 0);
    }, 0);
});

const submit = () => {
    form.post(route('disbursements.store'));
};
</script>

<template>
    <AppLayout title="Nouveau Décaissement">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">Nouveau Décaissement</h2>
                    <p class="mt-1 text-sm text-gray-600">Enregistrer un nouveau décaissement</p>
                </div>
                <Link
                    :href="route('disbursements.index')"
                    class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-200 transition-colors"
                >
                    ← Retour
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Section Informations Générales -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-900">Informations du Décaissement</h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date et Heure</label>
                                <input
                                    type="datetime-local"
                                    v-model="form.performed_at"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optionnel)</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                    placeholder="Raison ou détails additionnels..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section Lignes de Décaissement -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-sm font-semibold text-gray-900">Lignes de décaissement</h3>
                            <button
                                type="button"
                                @click="addItem"
                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-emerald-700 bg-emerald-100 hover:bg-emerald-200"
                            >
                                + Ajouter une ligne
                            </button>
                        </div>
                        <div class="p-0 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/2">Désignation</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Quantité</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Prix Unitaire</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Montant</th>
                                        <th class="px-3 py-3 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <td class="px-6 py-3">
                                            <input
                                                type="text"
                                                v-model="item.designation"
                                                placeholder="Saisissez la désignation"
                                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                required
                                            >
                                        </td>
                                        <td class="px-6 py-3">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                v-model="item.quantite"
                                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-right"
                                                required
                                            >
                                        </td>
                                        <td class="px-6 py-3">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                v-model="item.prix_unitaire"
                                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-right"
                                                required
                                            >
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            <span class="text-sm font-semibold text-gray-900 whitespace-nowrap">
                                                {{ ((item.quantite * item.prix_unitaire) || 0).toLocaleString('fr-FR') }} FCFA
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <button
                                                v-if="form.items.length > 1"
                                                type="button"
                                                @click="removeItem(index)"
                                                class="text-red-500 hover:text-red-700 p-1"
                                                title="Supprimer la ligne"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 border-t border-gray-200">
                                        <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900 uppercase">
                                            Total
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-lg font-bold text-emerald-700 whitespace-nowrap">
                                                {{ totalAmount.toLocaleString('fr-FR') }} FCFA
                                            </span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Enregistrer -->
                    <div class="flex justify-end gap-3 mt-6">
                        <Link
                            :href="route('disbursements.index')"
                            class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-emerald-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer le Décaissement' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
