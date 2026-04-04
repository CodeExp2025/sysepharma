<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    transfer: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <AppLayout :title="`Transfert #${transfer.id}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        Transfert #{{ transfer.id }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Vers {{ transfer.depot?.name || '-' }} • {{ formatDate(transfer.created_at) }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('transfers.print', transfer.uuid)"
                        target="_blank"
                        class="px-4 py-2 bg-indigo-600 text-white border border-indigo-700 rounded-lg font-medium text-sm hover:bg-indigo-700 transition-all duration-150"
                    >
                        Imprimer
                    </Link>
                    <Link
                        :href="route('transfers.index')"
                        class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-200 transition-all duration-150"
                    >
                        ← Retour
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-orange-50 to-orange-100 border-b border-orange-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-orange-900 font-bold">Détails</div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 ring-1 ring-purple-600/20">
                                {{ transfer.items_count || 0 }} unité(s)
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Destination</div>
                                <div class="text-sm font-semibold text-gray-900 mt-1">{{ transfer.depot?.name || '-' }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ transfer.depot?.address || '' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Effectué par</div>
                                <div class="text-sm font-semibold text-gray-900 mt-1">{{ transfer.user?.name || '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Statut</div>
                                <div class="text-sm font-semibold text-gray-900 mt-1">{{ transfer.status || '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <div class="text-sm text-gray-900 font-bold">Unités transférées</div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-white">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Médicament</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Code-barres</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Quantité</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Expiration</th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Prix</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="item in transfer.items" :key="item.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ item.drug_unit?.drug?.name || 'Inconnu' }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ item.drug_unit?.drug?.category?.name || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-mono text-gray-900">{{ item.drug_unit?.barcode || '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">
                                            × {{ item.quantity ?? 1 }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-700">
                                            {{ item.drug_unit?.expiration_date ? new Date(item.drug_unit.expiration_date).toLocaleDateString('fr-FR') : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ item.drug_unit?.price ? Number(item.drug_unit.price).toLocaleString() : 0 }} FCFA
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!transfer.items || transfer.items.length === 0">
                                    <td colspan="5" class="px-6 py-16 text-center text-sm text-gray-500">Aucune unité</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

