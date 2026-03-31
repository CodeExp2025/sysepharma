<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    userPharmacies: Array,
    activePharmacyId: Number,
});

const page = usePage();
const isDepotStaff = computed(() => (page.props.auth?.roles ?? []).includes('depot_staff'));

const switchForm = useForm({
    pharmacy_id: props.activePharmacyId ?? '',
});

const switchPharmacy = () => {
    switchForm.post(route('profile.switch-pharmacy'));
};
</script>

<template>
    <Head title="Profil" />

    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Profil</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">

                <!-- Profile Info -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <!-- Pharmacy Switcher — hidden for depot_staff -->
                <div v-if="!isDepotStaff && userPharmacies && userPharmacies.length > 1"
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <div class="max-w-xl">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">Pharmacie active</h3>
                        <p class="text-sm text-gray-600 mb-5">
                            Vous êtes membre de {{ userPharmacies.length }} pharmacies. Sélectionnez celle sur laquelle vous souhaitez travailler.
                        </p>

                        <div v-if="switchForm.recentlySuccessful" class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 px-3 py-2 rounded-lg">
                            Pharmacie active mise à jour.
                        </div>

                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1" for="pharmacy_switch">
                                    Choisir une pharmacie
                                </label>
                                <select
                                    v-model="switchForm.pharmacy_id"
                                    id="pharmacy_switch"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                                >
                                    <option v-for="p in userPharmacies" :key="p.id" :value="p.id">
                                        {{ p.name }}<template v-if="p.id === activePharmacyId"> (active)</template>
                                    </option>
                                </select>
                                <p v-if="switchForm.errors.pharmacy_id" class="mt-1 text-sm text-red-600">{{ switchForm.errors.pharmacy_id }}</p>
                            </div>
                            <button
                                type="button"
                                @click="switchPharmacy"
                                :disabled="switchForm.processing || switchForm.pharmacy_id === activePharmacyId"
                                class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-150"
                            >
                                Changer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Password -->
                <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

            </div>
        </div>
    </AppLayout>
</template>
