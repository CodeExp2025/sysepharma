<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

defineProps({
    categories: Array,
});

const formTypes = [
    'Comprimé', 'Gélule', 'Sirop', 'Solution injectable',
    'Pommade', 'Crème', 'Suppositoire', 'Collyre', 'Autre',
];

const emptyDrug = () => ({
    name: '',
    form_med: '',
    dosage_med: '',
    prix_med: '',
    effet_s_med: '',
    description: '',
});

const form = useForm({
    category_id: '',
    drugs: [emptyDrug()],
});

const addDrug = () => form.drugs.push(emptyDrug());

const removeDrug = (index) => {
    if (form.drugs.length > 1) form.drugs.splice(index, 1);
};

const submit = () => {
    form.post(route('drugs.store'));
};
</script>

<template>
    <AppLayout title="Nouveau Médicament">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                        Ajouter des Médicaments
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Créez un ou plusieurs médicaments dans la même catégorie
                    </p>
                </div>
                <Link
                    :href="route('drugs.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Catégorie partagée -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Catégorie (commune à tous les médicaments)
                        </h3>
                        <select
                            v-model="form.category_id"
                            class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :class="{ 'border-red-500': form.errors.category_id }"
                        >
                            <option value="" disabled>Sélectionner une catégorie</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
                    </div>

                    <!-- Liste des médicaments -->
                    <div
                        v-for="(drug, index) in form.drugs"
                        :key="index"
                        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-semibold text-gray-900">
                                Médicament {{ form.drugs.length > 1 ? `#${index + 1}` : '' }}
                            </h3>
                            <button
                                v-if="form.drugs.length > 1"
                                type="button"
                                @click="removeDrug(index)"
                                class="inline-flex items-center px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Supprimer
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Nom -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="drug.name"
                                    type="text"
                                    placeholder="Ex: Paracétamol"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    :class="{ 'border-red-500': form.errors[`drugs.${index}.name`] }"
                                >
                                <p v-if="form.errors[`drugs.${index}.name`]" class="mt-1 text-sm text-red-600">{{ form.errors[`drugs.${index}.name`] }}</p>
                            </div>

                            <!-- Forme -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Forme pharmaceutique</label>
                                <select
                                    v-model="drug.form_med"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Sélectionner une forme</option>
                                    <option v-for="type in formTypes" :key="type" :value="type">{{ type }}</option>
                                </select>
                            </div>

                            <!-- Dosage -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                                <input
                                    v-model="drug.dosage_med"
                                    type="text"
                                    placeholder="Ex: 500mg, 10ml"
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>

                            <!-- Prix -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Prix (FCFA) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="drug.prix_med"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="block w-full px-4 py-2.5 pr-16 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        :class="{ 'border-red-500': form.errors[`drugs.${index}.prix_med`] }"
                                    >
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm">FCFA</span>
                                    </div>
                                </div>
                                <p v-if="form.errors[`drugs.${index}.prix_med`]" class="mt-1 text-sm text-red-600">{{ form.errors[`drugs.${index}.prix_med`] }}</p>
                            </div>

                            <!-- Effet secondaire -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Effet secondaire principal</label>
                                <input
                                    v-model="drug.effet_s_med"
                                    type="text"
                                    placeholder="Nausées, maux de tête..."
                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Ajouter un médicament -->
                    <button
                        type="button"
                        @click="addDrug"
                        class="w-full inline-flex items-center justify-center px-4 py-3 border-2 border-dashed border-blue-300 rounded-xl text-blue-600 hover:border-blue-500 hover:bg-blue-50 font-medium transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajouter un autre médicament
                    </button>

                    <!-- Actions -->
                    <div class="flex items-center justify-between">
                        <Link
                            :href="route('drugs.index')"
                            class="inline-flex items-center px-4 py-2 text-gray-700 hover:text-gray-900 font-medium transition-colors"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ form.processing ? 'Enregistrement...' : `Enregistrer ${form.drugs.length > 1 ? form.drugs.length + ' médicaments' : ''}` }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
