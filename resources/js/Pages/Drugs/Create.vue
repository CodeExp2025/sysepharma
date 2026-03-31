<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    categories: Array,
});

const form = useForm({
    category_id: '',
    name: '',
    effet_s_med: '',
    dosage_med: '',
    form_med: '',
    prix_med: '',
    description: '',
});

const formTypes = [
    'Comprimé',
    'Gélule',
    'Sirop',
    'Solution injectable',
    'Pommade',
    'Crème',
    'Suppositoire',
    'Collyre',
    'Autre'
];

const showSuccessMessage = ref(false);

const submit = () => {
    form.post(route('drugs.store'), {
        onSuccess: () => {
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 3000);
        }
    });
};
</script>

<template>
    <AppLayout title="Nouveau Médicament">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                        Ajouter un Médicament
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Remplissez les informations du nouveau médicament
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
                <!-- Success Message -->
                <transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 transform -translate-y-2"
                    enter-to-class="opacity-100 transform translate-y-0"
                    leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="showSuccessMessage" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm font-medium text-green-800">Médicament ajouté avec succès !</p>
                        </div>
                    </div>
                </transition>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <form @submit.prevent="submit">
                        <div class="p-8">
                            <!-- Section: Informations générales -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Informations générales
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Name -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2" for="name">
                                            Nom du médicament <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            v-model="form.name" 
                                            id="name" 
                                            type="text" 
                                            placeholder="Ex: Paracétamol"
                                            class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                                        >
                                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ form.errors.name }}
                                        </p>
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2" for="category">
                                            Catégorie <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            v-model="form.category_id" 
                                            id="category" 
                                            class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                            :class="{ 'border-red-500 focus:ring-red-500': form.errors.category_id }"
                                        >
                                            <option value="" disabled>Sélectionner une catégorie</option>
                                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                                {{ category.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ form.errors.category_id }}
                                        </p>
                                    </div>

                                    <!-- Price -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2" for="price">
                                            Prix (FCFA) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input 
                                                v-model="form.prix_med" 
                                                id="price" 
                                                type="number" 
                                                step="0.01"
                                                placeholder="0.00"
                                                class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.prix_med }"
                                            >
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 text-sm">FCFA</span>
                                            </div>
                                        </div>
                                        <p v-if="form.errors.prix_med" class="mt-1 text-sm text-red-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ form.errors.prix_med }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Caractéristiques -->
                            <div class="mb-8 pb-8 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Caractéristiques
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Form -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2" for="form">
                                            Forme pharmaceutique
                                        </label>
                                        <select 
                                            v-model="form.form_med" 
                                            id="form" 
                                            class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        >
                                            <option value="">Sélectionner une forme</option>
                                            <option v-for="type in formTypes" :key="type" :value="type">
                                                {{ type }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Dosage -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2" for="dosage">
                                            Dosage
                                        </label>
                                        <input 
                                            v-model="form.dosage_med" 
                                            id="dosage" 
                                            type="text"
                                            placeholder="Ex: 500mg, 10ml"
                                            class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                        >
                                    </div>

                                    <!-- Effet secondaire -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2" for="effet">
                                            Effet secondaire principal
                                        </label>
                                        <textarea 
                                            v-model="form.effet_s_med" 
                                            id="effet" 
                                            rows="3"
                                            placeholder="Décrivez les principaux effets secondaires..."
                                            class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                                        ></textarea>
                                        <p class="mt-1 text-xs text-gray-500">Décrivez brièvement les effets secondaires les plus courants</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between">
                                <Link 
                                    :href="route('drugs.index')" 
                                    class="inline-flex items-center px-4 py-2 text-gray-700 hover:text-gray-900 font-medium transition-colors"
                                >
                                    Annuler
                                </Link>
                                <div class="flex gap-3">
                                    <button 
                                        type="submit" 
                                        class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                        :disabled="form.processing"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Help Section -->
                <div class="mt-6 bg-blue-50 border border-blue-100 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800 mb-1">Conseils pour remplir ce formulaire</h4>
                            <ul class="text-xs text-blue-700 space-y-1">
                                <li>• Les champs marqués d'un astérisque (*) sont obligatoires</li>
                                <li>• Assurez-vous que le nom du médicament est correct et complet</li>
                                <li>• Le prix doit être indiqué en francs CFA</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>