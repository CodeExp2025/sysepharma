<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    depot: Object,
});

const page = usePage();
const auth = page.props.auth;
const isAdmin = auth.roles?.includes('super_admin') || auth.roles?.includes('pharmacy_admin');

const toggling = ref(false);

const toggleReceipts = async () => {
    toggling.value = true;
    try {
        await window.axios.post(route('depots.toggle-receipts', props.depot.uuid));
        router.reload({ only: ['depot'] });
    } catch (e) {
        // silently fail — page will reload on next navigation
    } finally {
        toggling.value = false;
    }
};
</script>

<template>
    <AppLayout :title="`Dépôt — ${depot.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        {{ depot.name }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ depot.pharmacy?.name ?? '—' }} · {{ depot.address || 'Adresse non renseignée' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        v-if="isAdmin"
                        :href="route('depots.edit', depot.uuid)"
                        class="px-4 py-2 bg-indigo-600 text-white border border-indigo-700 rounded-lg font-medium text-sm hover:bg-indigo-700 transition-all duration-150"
                    >
                        Modifier
                    </Link>
                    <Link
                        :href="route('depots.index')"
                        class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-200 transition-all duration-150"
                    >
                        ← Retour
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 border-b border-indigo-200">
                        <h3 class="text-lg font-bold text-indigo-900 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Informations
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nom</p>
                            <p class="text-sm font-semibold text-gray-900">{{ depot.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Pharmacie</p>
                            <p class="text-sm font-semibold text-gray-900">{{ depot.pharmacy?.name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Adresse</p>
                            <p class="text-sm text-gray-700">{{ depot.address || '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Créé le</p>
                            <p class="text-sm text-gray-700">
                                {{ depot.created_at ? new Date(depot.created_at).toLocaleDateString('fr-FR') : '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Receipt Visibility (admin only) -->
                <div v-if="isAdmin" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-amber-50 to-amber-100 border-b border-amber-200">
                        <h3 class="text-lg font-bold text-amber-900">Paramètres d'affichage</h3>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Afficher les recettes au responsable de dépôt</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Lorsqu'activé, le responsable voit le chiffre d'affaires journalier de ce dépôt.
                                    La pharmacie voit toujours les recettes.
                                </p>
                                <p class="text-xs mt-2 font-semibold" :class="depot.show_receipts ? 'text-green-600' : 'text-gray-400'">
                                    Statut actuel : {{ depot.show_receipts ? 'Activé' : 'Désactivé' }}
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="toggleReceipts"
                                :disabled="toggling"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-60"
                                :class="depot.show_receipts ? 'bg-green-500' : 'bg-gray-300'"
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200"
                                    :class="depot.show_receipts ? 'translate-x-6' : 'translate-x-1'"
                                />
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
