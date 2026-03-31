<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    description: '',
});

const showSuccessMessage = ref(false);
const predefinedCategories = [
    { name: 'Analgésiques', icon: '💊' },
    { name: 'Antibiotiques', icon: '🦠' },
    { name: 'Antihypertenseurs', icon: '❤️' },
    { name: 'Antidiabétiques', icon: '🩸' },
    { name: 'Antipaludiques', icon: '🦟' },
    { name: 'Vitamines', icon: '🌟' },
    { name: 'Antiallergiques', icon: '🤧' },
    { name: 'Digestifs', icon: '🫄' },
];

const usePredefinedCategory = (category) => {
    form.name = category.name;
    form.description = `Catégorie des médicaments ${category.name.toLowerCase()}`;
};

const submit = () => {
    form.post(route('categories.store'), {
        onSuccess: () => {
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 3000);
            form.reset();
        }
    });
};
</script>

<template>
    <AppLayout title="Nouvelle Catégorie">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                        Créer une Catégorie
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Organisez vos médicaments par catégorie
                    </p>
                </div>
                <Link 
                    :href="route('categories.index')" 
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
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
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
                            <p class="text-sm font-medium text-green-800">Catégorie créée avec succès !</p>
                        </div>
                    </div>
                </transition>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Form -->
                    <div class="lg:col-span-2">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                            <form @submit.prevent="submit">
                                <div class="p-8">
                                    <!-- Section: Informations de base -->
                                    <div class="mb-8">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                            Informations de la catégorie
                                        </h3>

                                        <div class="space-y-6">
                                            <!-- Name -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2" for="name">
                                                    Nom de la catégorie <span class="text-red-500">*</span>
                                                </label>
                                                <input 
                                                    v-model="form.name" 
                                                    id="name" 
                                                    type="text" 
                                                    placeholder="Ex: Analgésiques, Antibiotiques..."
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

                                            <!-- Description -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2" for="description">
                                                    Description
                                                </label>
                                                <textarea 
                                                    v-model="form.description" 
                                                    id="description" 
                                                    rows="4"
                                                    placeholder="Décrivez le type de médicaments dans cette catégorie..."
                                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                                                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.description }"
                                                ></textarea>
                                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ form.errors.description }}
                                                </p>
                                                <p class="mt-1 text-xs text-gray-500">
                                                    Optionnel - Ajoutez une description pour aider à identifier cette catégorie
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                                        <Link 
                                            :href="route('categories.index')" 
                                            class="inline-flex items-center px-4 py-2 text-gray-700 hover:text-gray-900 font-medium transition-colors"
                                        >
                                            Annuler
                                        </Link>
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
                                            {{ form.processing ? 'Création en cours...' : 'Créer la catégorie' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Examples Section -->
                        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-gray-800 mb-1">Conseils pour les catégories</h4>
                                    <ul class="text-xs text-gray-600 space-y-1">
                                        <li>• Utilisez des noms clairs et descriptifs</li>
                                        <li>• Une catégorie doit regrouper des médicaments similaires</li>
                                        <li>• Évitez les catégories trop larges ou trop spécifiques</li>
                                        <li>• La description aide à différencier les catégories similaires</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar - Quick Templates -->
                    <div class="lg:col-span-1">
                        <div class="bg-white border border-gray-200 rounded-lg p-4 sticky top-6">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Catégories courantes
                            </h4>
                            <p class="text-xs text-gray-600 mb-3">Cliquez pour remplir automatiquement</p>
                            
                            <div class="space-y-2">
                                <button
                                    v-for="category in predefinedCategories"
                                    :key="category.name"
                                    type="button"
                                    @click="usePredefinedCategory(category)"
                                    class="w-full text-left px-3 py-2 bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 rounded-lg transition-colors group"
                                >
                                    <div class="flex items-center">
                                        <span class="text-lg mr-2">{{ category.icon }}</span>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700">
                                                {{ category.name }}
                                            </p>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex items-start">
                                    <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="ml-2 text-xs text-gray-600">
                                        Ces modèles sont personnalisables après insertion
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Card -->
                        <div class="mt-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-xs font-medium text-blue-900">Organisation optimale</p>
                                    <p class="text-xs text-blue-700 mt-1">
                                        Les catégories facilitent la gestion et la recherche
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>