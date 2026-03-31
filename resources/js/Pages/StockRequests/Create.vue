<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref } from 'vue';

const props = defineProps({
    drugs: Array,
});

const form = useForm({
    items: [{ drug_id: '', quantity: 1 }],
    notes: '',
});

const addItem = () => {
    form.items.push({ drug_id: '', quantity: 1 });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    form.post(route('stock-requests.store'));
};
</script>

<template>
    <Head title="Nouvelle Demande de Stock" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nouvelle Demande de Stock</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit">
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Médicaments demandés</h3>
                                
                                <div v-for="(item, index) in form.items" :key="index" class="flex gap-4 mb-4 items-end border-b pb-4">
                                    <div class="flex-1">
                                        <InputLabel :for="'drug_' + index" value="Médicament" />
                                        <select
                                            :id="'drug_' + index"
                                            v-model="item.drug_id"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            required
                                        >
                                            <option value="" disabled>Sélectionner un médicament</option>
                                            <option v-for="drug in drugs" :key="drug.id" :value="drug.id">
                                                {{ drug.name }} ({{ drug.dosage_med }} {{ drug.form_med }})
                                            </option>
                                        </select>
                                        <InputError :message="form.errors['items.' + index + '.drug_id']" class="mt-2" />
                                    </div>

                                    <div class="w-32">
                                        <InputLabel :for="'qty_' + index" value="Quantité" />
                                        <TextInput
                                            :id="'qty_' + index"
                                            type="number"
                                            v-model="item.quantity"
                                            class="mt-1 block w-full"
                                            min="1"
                                            required
                                        />
                                        <InputError :message="form.errors['items.' + index + '.quantity']" class="mt-2" />
                                    </div>

                                    <button 
                                        type="button" 
                                        @click="removeItem(index)"
                                        class="mb-1 text-red-600 hover:text-red-900"
                                        v-if="form.items.length > 1"
                                    >
                                        Supprimer
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="addItem"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                >
                                    + Ajouter un autre médicament
                                </button>
                            </div>

                            <div class="mb-6">
                                <InputLabel for="notes" value="Notes (optionnel)" />
                                <textarea
                                    id="notes"
                                    v-model="form.notes"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="3"
                                ></textarea>
                                <InputError :message="form.errors.notes" class="mt-2" />
                            </div>

                            <div class="flex items-center justify-end gap-4">
                                <Link :href="route('stock-requests.index')" class="text-gray-600 hover:text-gray-900">
                                    Annuler
                                </Link>
                                <PrimaryButton :disabled="form.processing">
                                    Soumettre la demande
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
