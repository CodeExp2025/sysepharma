<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref } from 'vue';

const props = defineProps({
    pharmacy: Object,
    isAdmin: Boolean,
    isSuperAdmin: Boolean,
    userPharmacies: Array,
    activePharmacyId: Number,
    joinablePharmacies: Array,
});

// --- Pharmacy settings form (admin only) ---
const settingsForm = useForm(
    props.pharmacy
        ? {
              name:    props.pharmacy.name    ?? '',
              address: props.pharmacy.address ?? '',
              phone:   props.pharmacy.phone   ?? '',
              email:   props.pharmacy.email   ?? '',
              nif:     props.pharmacy.nif     ?? '',
              stat:    props.pharmacy.stat    ?? '',
              rcs:     props.pharmacy.rcs     ?? '',
          }
        : {}
);

const saveSettings = () => {
    settingsForm.patch(route('pharmacy.update'), { preserveScroll: true });
};

// --- Create new pharmacy (super_admin only) ---
const createForm = useForm({ name: '', address: '' });

const createPharmacy = () => {
    createForm.post(route('pharmacy.store'), {
        preserveScroll: true,
        onSuccess: () => { createForm.reset(); },
    });
};

// --- Switch active pharmacy ---
const switchForm = useForm({ pharmacy_id: props.activePharmacyId ?? '' });

const switchPharmacy = () => {
    switchForm.post(route('profile.switch-pharmacy'), { preserveScroll: true });
};

// --- Join an existing pharmacy ---
const joinForm = useForm({ pharmacy_id: '' });

const joinPharmacy = () => {
    joinForm.post(route('pharmacy.join'), {
        preserveScroll: true,
        onSuccess: () => { joinForm.reset(); },
    });
};

// --- Delete pharmacy ---
const showDeleteModal = ref(false);
const deleteConfirmName = ref('');
const deleteProcessing = ref(false);
const deleteErrors = ref({});

const deletePharmacy = () => {
    deleteProcessing.value = true;
    deleteErrors.value = {};
    router.delete(route('pharmacy.destroy', props.pharmacy.id), {
        data: { confirm_name: deleteConfirmName.value },
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteConfirmName.value = '';
        },
        onError: (errors) => { deleteErrors.value = errors; },
        onFinish: () => { deleteProcessing.value = false; },
    });
};

// --- Backup pharmacy ---
const backupPharmacy = () => {
    window.location.href = route('pharmacy.backup', props.pharmacy.id);
};

// --- Restore pharmacy ---
const restoreFile = ref(null);
const restoreProcessing = ref(false);

const restorePharmacy = () => {
    if (!restoreFile.value) return;
    restoreProcessing.value = true;
    const formData = new FormData();
    formData.append('backup_file', restoreFile.value);
    router.post(route('pharmacy.restore'), formData, {
        preserveScroll: true,
        onFinish: () => { restoreProcessing.value = false; restoreFile.value = null; },
    });
};
</script>

<template>
    <Head title="Ma Pharmacie" />

    <AppLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-gray-900 leading-tight">Ma Pharmacie</h2>
        </template>

        <div class="py-10">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- ── Create new pharmacy (super_admin only) ── -->
                <div v-if="isSuperAdmin" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-purple-100 border-b border-purple-200">
                        <h3 class="text-lg font-bold text-purple-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Créer une nouvelle pharmacie
                        </h3>
                    </div>
                    <div class="p-6">
                        <div v-if="createForm.recentlySuccessful" class="mb-4 px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 font-medium">
                            Pharmacie créée avec succès.
                        </div>

                        <form @submit.prevent="createPharmacy" class="space-y-4 max-w-xl">
                            <div>
                                <InputLabel for="create_name" value="Nom de la pharmacie" />
                                <TextInput
                                    id="create_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="createForm.name"
                                    placeholder="Ex: Pharmacie du Centre"
                                    required
                                />
                                <InputError class="mt-1" :message="createForm.errors.name" />
                            </div>
                            <div>
                                <InputLabel for="create_address" value="Adresse" />
                                <TextInput
                                    id="create_address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="createForm.address"
                                    placeholder="Ex: 12 Rue de la Santé, Casablanca"
                                    required
                                />
                                <InputError class="mt-1" :message="createForm.errors.address" />
                            </div>
                            <div>
                                <button
                                    type="submit"
                                    :disabled="createForm.processing || !createForm.name || !createForm.address"
                                    class="px-6 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150"
                                >
                                    {{ createForm.processing ? 'Création...' : 'Créer la pharmacie' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ── Pharmacy Switcher ── -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-indigo-100 border-b border-indigo-200">
                        <h3 class="text-lg font-bold text-indigo-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            Pharmacie active
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">
                            Vous êtes membre de <strong>{{ userPharmacies.length }}</strong>
                            pharmacie(s). Sélectionnez celle sur laquelle vous travaillez actuellement.
                        </p>

                        <div v-if="switchForm.recentlySuccessful" class="mb-4 px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 font-medium">
                            Pharmacie active mise à jour.
                        </div>

                        <div v-if="userPharmacies.length === 0" class="text-sm text-gray-500 italic">
                            Vous n'êtes encore associé à aucune pharmacie.
                            <span v-if="isSuperAdmin"> Créez-en une ci-dessus.</span>
                        </div>

                        <div v-else class="space-y-2">
                            <label
                                v-for="p in userPharmacies"
                                :key="p.id"
                                class="flex items-center gap-4 p-4 rounded-lg border cursor-pointer transition-all duration-150"
                                :class="switchForm.pharmacy_id == p.id
                                    ? 'border-indigo-400 bg-indigo-50'
                                    : 'border-gray-200 hover:border-indigo-200 hover:bg-gray-50'"
                            >
                                <input
                                    type="radio"
                                    :value="p.id"
                                    v-model="switchForm.pharmacy_id"
                                    class="text-indigo-600 focus:ring-indigo-500"
                                >
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900">{{ p.name }}</p>
                                </div>
                                <span
                                    v-if="p.id === activePharmacyId"
                                    class="px-2 py-0.5 text-xs font-bold bg-green-100 text-green-700 rounded-full"
                                >
                                    Active
                                </span>
                            </label>

                            <div class="pt-2 flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="switchPharmacy"
                                    :disabled="switchForm.processing || switchForm.pharmacy_id == activePharmacyId"
                                    class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150"
                                >
                                    {{ switchForm.processing ? 'Changement...' : 'Changer de pharmacie' }}
                                </button>
                                <p v-if="switchForm.errors.pharmacy_id" class="text-sm text-red-600">{{ switchForm.errors.pharmacy_id }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Join an existing pharmacy ── -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-teal-50 to-teal-100 border-b border-teal-200">
                        <h3 class="text-lg font-bold text-teal-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Rejoindre une pharmacie existante
                        </h3>
                    </div>
                    <div class="p-6">
                        <div v-if="joinForm.recentlySuccessful" class="mb-4 px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 font-medium">
                            Pharmacie ajoutée à votre profil.
                        </div>

                        <div v-if="joinablePharmacies.length === 0" class="text-sm text-gray-500 italic">
                            Vous êtes déjà membre de toutes les pharmacies disponibles.
                        </div>

                        <div v-else>
                            <p class="text-sm text-gray-600 mb-4">
                                Associez-vous à une pharmacie existante pour pouvoir y travailler.
                            </p>
                            <div class="flex gap-3 items-end">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1" for="join_pharmacy">
                                        Choisir une pharmacie
                                    </label>
                                    <select
                                        v-model="joinForm.pharmacy_id"
                                        id="join_pharmacy"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all appearance-none bg-white"
                                        :class="{ 'border-red-400': joinForm.errors.pharmacy_id }"
                                    >
                                        <option value="" disabled>Sélectionner une pharmacie</option>
                                        <option v-for="p in joinablePharmacies" :key="p.id" :value="p.id">
                                            {{ p.name }}
                                        </option>
                                    </select>
                                    <p v-if="joinForm.errors.pharmacy_id" class="mt-1 text-sm text-red-600">{{ joinForm.errors.pharmacy_id }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click="joinPharmacy"
                                    :disabled="joinForm.processing || !joinForm.pharmacy_id"
                                    class="px-5 py-2 bg-teal-600 text-white text-sm font-semibold rounded-lg hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150"
                                >
                                    {{ joinForm.processing ? 'Ajout...' : 'Rejoindre' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Pharmacy settings (pharmacy_admin / super_admin with active pharmacy) ── -->
                <div v-if="isAdmin && pharmacy" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-orange-50 to-orange-100 border-b border-orange-200">
                        <h3 class="text-lg font-bold text-orange-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Paramètres — {{ pharmacy.name }}
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-6">
                            Coordonnées et informations légales de la pharmacie active.
                        </p>

                        <div v-if="settingsForm.recentlySuccessful" class="mb-4 px-3 py-2 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 font-medium">
                            Informations enregistrées.
                        </div>

                        <form @submit.prevent="saveSettings" class="space-y-5 max-w-xl">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2">
                                    <InputLabel for="name" value="Nom de la pharmacie" />
                                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="settingsForm.name" required />
                                    <InputError class="mt-1" :message="settingsForm.errors.name" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="address" value="Adresse" />
                                    <TextInput id="address" type="text" class="mt-1 block w-full" v-model="settingsForm.address" required />
                                    <InputError class="mt-1" :message="settingsForm.errors.address" />
                                </div>

                                <div>
                                    <InputLabel for="phone" value="Téléphone" />
                                    <TextInput id="phone" type="text" class="mt-1 block w-full" v-model="settingsForm.phone" />
                                    <InputError class="mt-1" :message="settingsForm.errors.phone" />
                                </div>

                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput id="email" type="email" class="mt-1 block w-full" v-model="settingsForm.email" />
                                    <InputError class="mt-1" :message="settingsForm.errors.email" />
                                </div>

                                <div>
                                    <InputLabel for="nif" value="NIF" />
                                    <TextInput id="nif" type="text" class="mt-1 block w-full" v-model="settingsForm.nif" placeholder="Numéro Identification Fiscale" />
                                    <InputError class="mt-1" :message="settingsForm.errors.nif" />
                                </div>

                                <div>
                                    <InputLabel for="stat" value="STAT" />
                                    <TextInput id="stat" type="text" class="mt-1 block w-full" v-model="settingsForm.stat" placeholder="Numéro Statistique" />
                                    <InputError class="mt-1" :message="settingsForm.errors.stat" />
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="rcs" value="RCS" />
                                    <TextInput id="rcs" type="text" class="mt-1 block w-full" v-model="settingsForm.rcs" placeholder="Registre du Commerce et des Sociétés" />
                                    <InputError class="mt-1" :message="settingsForm.errors.rcs" />
                                </div>
                            </div>

                            <div>
                                <button
                                    type="submit"
                                    :disabled="settingsForm.processing"
                                    class="px-6 py-2.5 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150"
                                >
                                    {{ settingsForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ── Backup & Restore ── -->
                <div v-if="isAdmin && pharmacy" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-blue-200">
                        <h3 class="text-lg font-bold text-blue-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Sauvegarde &amp; Restauration
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Backup -->
                        <div>
                            <p class="text-sm text-gray-600 mb-3">
                                Exporter toutes les donnees de <strong>{{ pharmacy.name }}</strong> (medicaments, stock, ventes, transferts, employes) dans un fichier JSON.
                            </p>
                            <button @click="backupPharmacy"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-all duration-150">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Telecharger la sauvegarde
                            </button>
                        </div>

                        <!-- Restore (super_admin only) -->
                        <div v-if="isSuperAdmin" class="border-t border-gray-200 pt-6">
                            <p class="text-sm text-gray-600 mb-3">
                                Restaurer une sauvegarde. Cela cree une <strong>nouvelle pharmacie</strong> avec toutes les donnees du fichier.
                            </p>
                            <div class="flex items-end gap-3">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Fichier de sauvegarde (.json)</label>
                                    <input type="file" accept=".json"
                                        @change="restoreFile = $event.target.files[0]"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                </div>
                                <button @click="restorePharmacy"
                                    :disabled="!restoreFile || restoreProcessing"
                                    class="px-5 py-2.5 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150">
                                    {{ restoreProcessing ? 'Restauration...' : 'Restaurer' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Danger zone — Delete pharmacy ── -->
                <div v-if="isAdmin && pharmacy" class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-red-50 to-red-100 border-b border-red-200">
                        <h3 class="text-lg font-bold text-red-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            Zone dangereuse
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-4">
                            Supprimer definitivement <strong>{{ pharmacy.name }}</strong> et toutes ses donnees :
                            depots, medicaments, stock, ventes, transferts. Les comptes utilisateurs seront dissocies mais conserves.
                        </p>
                        <button @click="showDeleteModal = true"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Supprimer cette pharmacie
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Delete confirmation modal ── -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 z-10">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Supprimer la pharmacie</h3>
                            <p class="text-sm text-gray-500">Cette action est irreversible</p>
                        </div>
                    </div>

                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-red-800 font-medium mb-2">Les donnees suivantes seront supprimees :</p>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li>- Tous les depots de la pharmacie</li>
                            <li>- Tous les medicaments et le stock</li>
                            <li>- Toutes les ventes enregistrees</li>
                            <li>- Tous les transferts</li>
                            <li>- Toutes les demandes de stock</li>
                        </ul>
                        <p class="text-sm text-red-800 mt-2">Les comptes utilisateurs seront dissocies mais <strong>pas supprimes</strong>.</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tapez <strong class="text-red-600">{{ pharmacy?.name }}</strong> pour confirmer
                        </label>
                        <input type="text" v-model="deleteConfirmName"
                            :placeholder="pharmacy?.name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-400': deleteErrors.confirm_name }" />
                        <p v-if="deleteErrors.confirm_name" class="mt-1 text-sm text-red-600">{{ deleteErrors.confirm_name }}</p>
                    </div>

                    <div class="flex items-center gap-3 justify-end">
                        <button @click="showDeleteModal = false; deleteConfirmName = ''"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Annuler
                        </button>
                        <button @click="deletePharmacy"
                            :disabled="deleteProcessing || deleteConfirmName !== pharmacy?.name"
                            class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            {{ deleteProcessing ? 'Suppression...' : 'Supprimer definitivement' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
