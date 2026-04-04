<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    drugUnit: Object,
    drugs: Array,
});

const form = useForm({
    drug_id:          props.drugUnit.drug_id,
    barcode:          props.drugUnit.barcode,
    expiration_date:  props.drugUnit.expiration_date?.substring(0, 10) ?? '',
    price:            props.drugUnit.price,
    quantite_contenu: props.drugUnit.quantite_contenu,
    status:           props.drugUnit.status,
});

const statusOptions = [
    { value: 'en_stock',  label: 'En Stock' },
    { value: 'vendue',    label: 'Vendue' },
    { value: 'perimee',   label: 'Périmée' },
    { value: 'retiree',   label: 'Retirée' },
];

const submit = () => {
    form.put(route('drug-units.update', props.drugUnit.uuid));
};
</script>

<template>
    <AppLayout title="Modifier une Unité">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                        Modifier l'Unité #{{ drugUnit.id }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Unité #{{ drugUnit.id }}
                    </p>
                </div>
                <Link :href="route('drug-units.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour au Stock
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Drug -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Médicament <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.drug_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.drug_id }">
                                <option v-for="drug in drugs" :key="drug.id" :value="drug.id">
                                    {{ drug.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.drug_id" class="mt-1 text-sm text-red-600">{{ form.errors.drug_id }}</p>
                        </div>

                        <!-- Barcode -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Code-barres <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.barcode" type="text"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.barcode }">
                            <p class="mt-1 text-xs text-gray-500">Si le code est déjà utilisé par une autre unité, un suffixe (-001, -002…) sera ajouté automatiquement.</p>
                            <p v-if="form.errors.barcode" class="mt-1 text-sm text-red-600">{{ form.errors.barcode }}</p>
                        </div>

                        <!-- Expiration Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Date de péremption <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.expiration_date" type="date"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.expiration_date }">
                            <p v-if="form.errors.expiration_date" class="mt-1 text-sm text-red-600">{{ form.errors.expiration_date }}</p>
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Prix unitaire <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input v-model="form.price" type="number" step="0.01" min="0"
                                    class="w-full px-4 py-2.5 pr-16 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors.price }">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">FCFA</span>
                                </div>
                            </div>
                            <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
                        </div>

                        <!-- Quantite Contenu -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Quantité par boîte <span class="text-red-500">*</span>
                            </label>
                            <input v-model.number="form.quantite_contenu" type="number" min="1"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.quantite_contenu }">
                            <p v-if="form.errors.quantite_contenu" class="mt-1 text-sm text-red-600">{{ form.errors.quantite_contenu }}</p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Statut <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.status"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-500': form.errors.status }">
                                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <Link :href="route('drug-units.index')"
                                class="inline-flex items-center px-4 py-2 text-gray-700 hover:text-gray-900 font-medium transition-colors">
                                Annuler
                            </Link>
                            <button type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors disabled:opacity-50">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ form.processing ? 'Sauvegarde...' : 'Enregistrer les modifications' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
