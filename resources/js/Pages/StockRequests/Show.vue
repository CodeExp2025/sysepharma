<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    stockRequest: Object,
});

const form = useForm({
    status: '',
});

const updateStatus = (status) => {
    if (confirm('Êtes-vous sûr de vouloir changer le statut de cette demande ?')) {
        form.status = status;
        form.patch(route('stock-requests.update', props.stockRequest.uuid), {
            preserveScroll: true,
        });
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'approved': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        default: return 'bg-yellow-100 text-yellow-800';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'approved': return 'Approuvée';
        case 'rejected': return 'Rejetée';
        case 'completed': return 'Complétée';
        case 'pending': return 'En attente';
        default: return status;
    }
};
</script>

<template>
    <Head :title="`Demande #${stockRequest.id}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Demande de Stock #{{ stockRequest.id }}</h2>
                <Link :href="route('stock-requests.index')" class="text-gray-600 hover:text-gray-900">
                    Retour à la liste
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Informations</h3>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Demandeur</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ stockRequest.user.name }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Dépôt</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ stockRequest.depot ? stockRequest.depot.name : 'N/A' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ new Date(stockRequest.created_at).toLocaleString() }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Statut</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusClass(stockRequest.status)]">
                                                {{ getStatusLabel(stockRequest.status) }}
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                            
                            <div v-if="stockRequest.notes">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Notes</h3>
                                <p class="text-sm text-gray-600 bg-gray-50 p-4 rounded-md">{{ stockRequest.notes }}</p>
                            </div>
                        </div>

                        <h3 class="text-lg font-medium text-gray-900 mb-4">Articles demandés</h3>
                        <div class="overflow-x-auto mb-8">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Médicament
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Dosage / Forme
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantité
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in stockRequest.items" :key="item.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ item.drug.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ item.drug.dosage_med }} - {{ item.drug.form_med }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ item.quantity }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t pt-6 flex justify-end gap-4" v-if="stockRequest.status === 'pending'">
                            <DangerButton @click="updateStatus('rejected')" :disabled="form.processing">
                                Rejeter
                            </DangerButton>
                            <PrimaryButton @click="updateStatus('approved')" :disabled="form.processing">
                                Approuver
                            </PrimaryButton>
                        </div>
                        <div class="border-t pt-6 flex justify-end gap-4" v-if="stockRequest.status === 'approved'">
                             <PrimaryButton @click="updateStatus('completed')" :disabled="form.processing">
                                Marquer comme Complétée
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
