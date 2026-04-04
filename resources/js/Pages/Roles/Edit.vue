<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    role: Object,
    permissions: Array,
    rolePermissions: Array,
});

const form = useForm({
    name: props.role.name,
    permissions: props.rolePermissions || [],
});

const submit = () => {
    form.put(route('roles.update', props.role.uuid));
};

// Group permissions by category (assuming permission names are formatted like "category.action")
const groupedPermissions = computed(() => {
    const groups = {};
    props.permissions.forEach(permission => {
        const parts = permission.name.split('.');
        const category = parts.length > 1 ? parts[0] : 'Autres';
        
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(permission);
    });
    return groups;
});

const toggleCategory = (category) => {
    const categoryPermissions = groupedPermissions.value[category].map(p => p.name);
    const allSelected = categoryPermissions.every(p => form.permissions.includes(p));
    
    if (allSelected) {
        form.permissions = form.permissions.filter(p => !categoryPermissions.includes(p));
    } else {
        const newPermissions = [...form.permissions];
        categoryPermissions.forEach(p => {
            if (!newPermissions.includes(p)) {
                newPermissions.push(p);
            }
        });
        form.permissions = newPermissions;
    }
};

const isCategorySelected = (category) => {
    const categoryPermissions = groupedPermissions.value[category].map(p => p.name);
    return categoryPermissions.every(p => form.permissions.includes(p));
};

const isCategoryPartiallySelected = (category) => {
    const categoryPermissions = groupedPermissions.value[category].map(p => p.name);
    const selectedCount = categoryPermissions.filter(p => form.permissions.includes(p)).length;
    return selectedCount > 0 && selectedCount < categoryPermissions.length;
};
</script>

<template>
    <AppLayout title="Modifier Rôle">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight tracking-tight">
                        Modifier le Rôle : {{ role.name }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Modifiez les informations et permissions du rôle
                    </p>
                </div>
                <Link
                    :href="route('roles.index')"
                    class="px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-150"
                >
                    ← Retour
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <!-- Role Name Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                        <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-purple-100 border-b border-purple-200">
                            <h3 class="text-lg font-bold text-purple-900 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                Informations du Rôle
                            </h3>
                        </div>
                        
                        <div class="p-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2" for="name">
                                    Nom du rôle
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                    </div>
                                    <input 
                                        v-model="form.name" 
                                        id="name" 
                                        type="text"
                                        placeholder="Ex: Administrateur, Manager, etc."
                                        class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-150"
                                        :class="{ 'border-red-500 ring-2 ring-red-200': form.errors.name }"
                                    >
                                </div>
                                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ form.errors.name }}
                                </p>
                                <p v-else class="mt-2 text-xs text-gray-500">Nom du rôle à modifier</p>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-green-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-green-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    Permissions
                                </h3>
                                <span class="text-sm font-semibold text-green-700">
                                    {{ form.permissions.length }} / {{ permissions.length }} sélectionnée(s)
                                </span>
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <!-- Grouped Permissions -->
                            <div class="space-y-4">
                                <div 
                                    v-for="(perms, category) in groupedPermissions" 
                                    :key="category"
                                    class="border border-gray-200 rounded-lg overflow-hidden"
                                >
                                    <!-- Category Header -->
                                    <div 
                                        @click="toggleCategory(category)"
                                        class="px-4 py-3 bg-gray-50 cursor-pointer hover:bg-gray-100 transition-colors duration-150 flex items-center justify-between"
                                    >
                                        <div class="flex items-center">
                                            <div class="relative mr-3">
                                                <input 
                                                    type="checkbox"
                                                    :checked="isCategorySelected(category)"
                                                    :indeterminate="isCategoryPartiallySelected(category)"
                                                    class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500 cursor-pointer"
                                                    @click.stop="toggleCategory(category)"
                                                >
                                            </div>
                                            <span class="font-semibold text-gray-900 capitalize">
                                                {{ category }}
                                            </span>
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 bg-white px-2 py-1 rounded-full">
                                            {{ perms.length }} permission(s)
                                        </span>
                                    </div>

                                    <!-- Permissions List -->
                                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 bg-white">
                                        <label 
                                            v-for="permission in perms" 
                                            :key="permission.id"
                                            :for="'perm-'+permission.id"
                                            class="flex items-center p-3 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50 cursor-pointer transition-all duration-150"
                                            :class="{ 'bg-purple-50 border-purple-300': form.permissions.includes(permission.name) }"
                                        >
                                            <input 
                                                type="checkbox" 
                                                :value="permission.name" 
                                                v-model="form.permissions" 
                                                :id="'perm-'+permission.id" 
                                                class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500 mr-3"
                                            >
                                            <span class="text-sm text-gray-700 font-medium">
                                                {{ permission.name }}
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">À propos des permissions</h3>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <p>Modifiez les permissions pour ce rôle. Vous pouvez cliquer sur une catégorie pour sélectionner/désélectionner toutes les permissions de cette catégorie.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex items-center justify-end gap-3">
                        <Link
                            :href="route('roles.index')"
                            class="px-5 py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-150"
                        >
                            Annuler
                        </Link>
                        <button 
                            type="submit" 
                            class="group px-6 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 border border-transparent rounded-lg font-semibold text-sm text-white shadow-md hover:shadow-lg hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transform transition-all duration-200 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                            :disabled="form.processing"
                        >
                            <span class="flex items-center gap-2">
                                <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Mise à jour...' : 'Mettre à jour' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Custom animations */
@keyframes spin {
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}

/* Indeterminate checkbox style */
input[type="checkbox"]:indeterminate {
    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M4 8h8'/%3e%3c/svg%3e");
    border-color: #9333ea;
    background-color: #9333ea;
}
</style>