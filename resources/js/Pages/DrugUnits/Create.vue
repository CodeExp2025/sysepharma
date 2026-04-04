<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, watch, onMounted, computed } from 'vue';

const props = defineProps({
    drugs: Array,
});

const form = useForm({
    drug_id: '',
    barcode: '',
    expiration_date: '',
    price: '',
    quantite_contenu: 1,
    quantity: 1,
});

const selectedDrug = ref(null);
const barcodeInput = ref(null);
const showSuccessMessage = ref(false);
const recentEntries = ref([]);
const searchDrug = ref('');

// Min date for expiration (today)
const minDate = computed(() => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
});

// Filter drugs based on search
const filteredDrugs = computed(() => {
    if (!searchDrug.value) return props.drugs;
    const search = searchDrug.value.toLowerCase();
    return props.drugs.filter(drug => 
        drug.name.toLowerCase().includes(search) ||
        drug.form_med?.toLowerCase().includes(search) ||
        drug.dosage_med?.toLowerCase().includes(search)
    );
});

// Auto-fill price when drug is selected
watch(() => form.drug_id, (newVal) => {
    selectedDrug.value = props.drugs.find(d => d.id === newVal);
    if (selectedDrug.value) {
        form.price = selectedDrug.value.prix_med;
    }
});

// Focus barcode input on mount
onMounted(() => {
    if (barcodeInput.value) {
        barcodeInput.value.focus();
    }
});

const submit = () => {
    form.post(route('drug-units.store'), {
        onSuccess: () => {
            // Add to recent entries
            recentEntries.value.unshift({
                drug: selectedDrug.value,
                barcode: form.barcode,
                time: new Date().toLocaleTimeString('fr-FR'),
            });
            if (recentEntries.value.length > 5) {
                recentEntries.value.pop();
            }

            // Show success message
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 2000);

            // Reset form but keep drug selection for rapid entry
            const drugId = form.drug_id;
            const price = form.price;
            form.reset();
            form.drug_id = drugId;
            form.price = price;

            // Refocus barcode input
            if (barcodeInput.value) {
                barcodeInput.value.focus();
            }
        },
    });
};

// Quick keyboard shortcuts
const handleKeyPress = (e) => {
    // Alt + S to submit
    if (e.altKey && e.key === 's') {
        e.preventDefault();
        submit();
    }
    // Alt + C to clear
    if (e.altKey && e.key === 'c') {
        e.preventDefault();
        form.reset();
        if (barcodeInput.value) {
            barcodeInput.value.focus();
        }
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyPress);
});
</script>

<template>
    <AppLayout title="Entrée de Stock">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                        Entrée de Stock
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Scannez les codes-barres pour ajouter rapidement au stock
                    </p>
                </div>
                <Link 
                    :href="route('drug-units.index')" 
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    Voir le Stock
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Form -->
                    <div class="lg:col-span-2">
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
                                    <p class="text-sm font-medium text-green-800">Article ajouté au stock avec succès !</p>
                                </div>
                            </div>
                        </transition>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                            <form @submit.prevent="submit">
                                <div class="p-8">
                                    <!-- Drug Selection Section -->
                                    <div class="mb-8">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                            Sélection du médicament
                                        </h3>

                                        <!-- Search Drug -->
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Rechercher un médicament
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                    </svg>
                                                </div>
                                                <input 
                                                    v-model="searchDrug" 
                                                    type="text" 
                                                    placeholder="Nom, forme ou dosage..." 
                                                    class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                >
                                            </div>
                                        </div>

                                        <!-- Drug Selection -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2" for="drug">
                                                Médicament <span class="text-red-500">*</span>
                                            </label>
                                            <select 
                                                v-model="form.drug_id" 
                                                id="drug" 
                                                class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.drug_id }"
                                            >
                                                <option value="" disabled>Choisir un médicament...</option>
                                                <option v-for="drug in filteredDrugs" :key="drug.id" :value="drug.id">
                                                    {{ drug.name }} ({{ drug.form_med }} {{ drug.dosage_med }})
                                                </option>
                                            </select>
                                            <p v-if="form.errors.drug_id" class="mt-1 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ form.errors.drug_id }}
                                            </p>
                                        </div>

                                        <!-- Drug Info Display -->
                                        <div v-if="selectedDrug" class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                                            <div class="flex items-start">
                                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <div class="ml-3 flex-1">
                                                    <h4 class="text-sm font-medium text-blue-800">{{ selectedDrug.name }}</h4>
                                                    <p class="text-xs text-blue-700 mt-1">
                                                        Catégorie: {{ selectedDrug.category?.name }} | 
                                                        Prix suggéré: {{ selectedDrug.prix_med }} FCFA
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stock Details Section -->
                                    <div class="mb-8 pb-8 border-b border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                            </svg>
                                            Informations de stock
                                        </h3>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <!-- Barcode -->
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2" for="barcode">
                                                    <div class="flex items-center justify-between">
                                                        <span>Code-barres <span class="text-red-500">*</span></span>
                                                        <span class="text-xs text-gray-500 font-normal">Scannez ou saisissez</span>
                                                    </div>
                                                </label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                                        </svg>
                                                    </div>
                                                    <input 
                                                        ref="barcodeInput"
                                                        v-model="form.barcode" 
                                                        id="barcode" 
                                                        type="text" 
                                                        placeholder="Positionnez le curseur ici et scannez"
                                                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                        :class="{ 'border-red-500 focus:ring-red-500': form.errors.barcode }"
                                                        autofocus
                                                    >
                                                </div>
                                                <p v-if="form.errors.barcode" class="mt-1 text-sm text-red-600 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ form.errors.barcode }}
                                                </p>
                                            </div>

                                            <!-- Expiration Date -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2" for="expiration">
                                                    Date de péremption <span class="text-red-500">*</span>
                                                </label>
                                                <input 
                                                    v-model="form.expiration_date" 
                                                    id="expiration" 
                                                    type="date" 
                                                    :min="minDate"
                                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.expiration_date }"
                                                >
                                                <p v-if="form.errors.expiration_date" class="mt-1 text-sm text-red-600 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ form.errors.expiration_date }}
                                                </p>
                                            </div>

                                            <!-- Price -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2" for="price">
                                                    Prix de vente unitaire <span class="text-red-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <input
                                                        v-model="form.price"
                                                        id="price"
                                                        type="number"
                                                        step="0.01"
                                                        placeholder="0.00"
                                                        class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                        :class="{ 'border-red-500 focus:ring-red-500': form.errors.price }"
                                                    >
                                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 text-sm">FCFA</span>
                                                    </div>
                                                </div>
                                                <p v-if="form.errors.price" class="mt-1 text-sm text-red-600 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ form.errors.price }}
                                                </p>
                                            </div>

                                            <!-- Quantite Contenu -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2" for="quantite_contenu">
                                                    Quantité par boîte <span class="text-red-500">*</span>
                                                </label>
                                                <input
                                                    v-model.number="form.quantite_contenu"
                                                    id="quantite_contenu"
                                                    type="number"
                                                    min="1"
                                                    placeholder="ex: 4 plaquettes"
                                                    class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.quantite_contenu }"
                                                >
                                                <p class="mt-1 text-xs text-gray-500">Nombre de plaquettes/unités dans cette boîte</p>
                                                <p v-if="form.errors.quantite_contenu" class="mt-1 text-sm text-red-600 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ form.errors.quantite_contenu }}
                                                </p>
                                            </div>

                                            <!-- Nombre d'exemplaires (lot) -->
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 mb-2" for="quantity">
                                                    Nombre d'unités à créer
                                                    <span class="ml-1 text-xs text-blue-600 font-normal">(même code-barres → suffixe auto)</span>
                                                </label>
                                                <div class="flex items-center gap-3">
                                                    <input
                                                        v-model.number="form.quantity"
                                                        id="quantity"
                                                        type="number"
                                                        min="1"
                                                        max="500"
                                                        class="w-32 px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                                    >
                                                    <span class="text-sm text-gray-500">
                                                        <template v-if="form.quantity > 1">
                                                            → <strong>{{ form.quantity }}</strong> unités créées avec suffixe (-001, -002…)
                                                        </template>
                                                        <template v-else>
                                                            → 1 unité créée
                                                        </template>
                                                    </span>
                                                </div>
                                                <p v-if="form.errors.quantity" class="mt-1 text-sm text-red-600">{{ form.errors.quantity }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <button 
                                            type="button"
                                            @click="form.reset(); if (barcodeInput) barcodeInput.focus();"
                                            class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
                                        >
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Réinitialiser (Alt+C)
                                        </button>

                                        <button 
                                            type="submit" 
                                            class="inline-flex items-center px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                            :disabled="form.processing"
                                        >
                                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                            {{ form.processing ? 'Ajout en cours...' : 'Ajouter au Stock (Alt+S)' }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Quick Guide -->
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-blue-800 mb-2">Guide rapide</h4>
                                    <ul class="text-xs text-blue-700 space-y-1.5">
                                        <li class="flex items-start">
                                            <span class="font-semibold mr-1">1.</span>
                                            <span>Sélectionnez le médicament</span>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="font-semibold mr-1">2.</span>
                                            <span>Scannez le code-barres</span>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="font-semibold mr-1">3.</span>
                                            <span>Vérifiez la date et le prix</span>
                                        </li>
                                        <li class="flex items-start">
                                            <span class="font-semibold mr-1">4.</span>
                                            <span>Cliquez sur "Ajouter"</span>
                                        </li>
                                    </ul>
                                    <div class="mt-3 pt-3 border-t border-blue-200">
                                        <p class="text-xs font-semibold text-blue-800 mb-1">Raccourcis clavier:</p>
                                        <p class="text-xs text-blue-700">Alt + S : Soumettre</p>
                                        <p class="text-xs text-blue-700">Alt + C : Réinitialiser</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Entries -->
                        <div v-if="recentEntries.length > 0" class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Entrées récentes
                            </h4>
                            <div class="space-y-2">
                                <div 
                                    v-for="(entry, index) in recentEntries" 
                                    :key="index"
                                    class="p-2 bg-green-50 rounded border border-green-100"
                                >
                                    <p class="text-xs font-medium text-green-900">{{ entry.drug.name }}</p>
                                    <p class="text-xs text-green-700">Code: {{ entry.barcode }}</p>
                                    <p class="text-xs text-green-600">{{ entry.time }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Statistiques</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Session actuelle</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ recentEntries.length }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>