<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import IdleLockScreen from '@/Components/IdleLockScreen.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({ title: String });

const showingNavigationDropdown = ref(false);
const showAdminMenu = ref(false);
const showManagementMenu = ref(false);

// Close other dropdowns when one opens
const toggleAdminMenu = () => {
    showAdminMenu.value = !showAdminMenu.value;
    if (showAdminMenu.value) showManagementMenu.value = false;
};

const toggleManagementMenu = () => {
    showManagementMenu.value = !showManagementMenu.value;
    if (showManagementMenu.value) showAdminMenu.value = false;
};

// Close dropdowns when clicking outside
const closeAllDropdowns = () => {
    showAdminMenu.value = false;
    showManagementMenu.value = false;
};

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => page.props.auth?.roles ?? []);
const permissions = computed(() => page.props.auth?.permissions ?? []);
const displayRole = computed(() => roles.value?.[0] ?? user.value?.role ?? 'Utilisateur');

const hasPermission = (permission) => {
    return permissions.value.includes(permission);
};

const hasAnyPermission = (requiredPermissions) => {
    if (!Array.isArray(requiredPermissions) || requiredPermissions.length === 0) return true;
    return requiredPermissions.some((permission) => hasPermission(permission));
};

// Groupes de navigation
const mainNavItems = [
    { 
        name: 'Dashboard', 
        route: 'dashboard', 
        permissions: [],
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
    },
    { 
        name: 'Médicaments', 
        route: 'drugs.index',
        match: 'drugs.*',
        permissions: ['view_drug'],
        icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'
    },
    { 
        name: 'Stock', 
        route: 'drug-units.index',
        permissions: ['view_stock'],
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
    },
    { 
        name: 'Transferts', 
        route: 'transfers.index',
        permissions: ['transfer_stock'],
        icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'
    },
    {
        name: 'Ventes',
        route: 'sales.index',
        permissions: ['sell_unit'],
        icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'
    },
    {
        name: 'Stats',
        route: 'stats.index',
        permissions: ['view_reports'],
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'
    }
];

// Management items moved to separate dropdown
const managementNavItems = [
    {
        name: 'Décaissements',
        route: 'disbursements.index',
        match: 'disbursements.*',
        permissions: [],
        roles: ['super_admin', 'pharmacy_admin'],
        icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'
    },
    { 
        name: 'Catégories', 
        route: 'categories.index',
        match: 'categories.*',
        permissions: ['view_category', 'create_category'],
        icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'
    },
    { 
        name: 'Dépôts', 
        route: 'depots.index',
        match: 'depots.*',
        permissions: ['create_depot'],
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
    }
];

const adminNavItems = [
    { 
        name: 'Utilisateurs', 
        route: 'users.index',
        match: 'users.*',
        permissions: ['manage_users'],
        icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
    },
    { 
        name: 'Rôles', 
        route: 'roles.index',
        match: 'roles.*',
        permissions: ['manage_roles'],
        icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
    },
    { 
        name: 'Permissions', 
        route: 'permissions.index',
        match: 'permissions.*',
        permissions: ['manage_roles'],
        icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'
    },
    { 
        name: 'Catégories', 
        route: 'categories.index',
        match: 'categories.*',
        permissions: ['view_category', 'create_category'],
        icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'
    },
    { 
        name: 'Dépôts', 
        route: 'depots.index',
        match: 'depots.*',
        permissions: ['create_depot'],
        icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
    }
];

const settingsItems = computed(() => {
    const items = [
        { name: 'Mon Profil', route: 'profile.edit', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
        { name: 'Ma Pharmacie', route: 'pharmacy.edit', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' }
    ];
    if (roles.value.includes('depot_staff')) {
        return items.filter(i => i.name !== 'Ma Pharmacie');
    }
    return items;
});

const visibleMainNavItems = computed(() => {
    return mainNavItems.filter((item) => {
        if (item.roles && !item.roles.some(r => roles.value.includes(r))) return false;
        return hasAnyPermission(item.permissions);
    });
});

const visibleAdminNavItems = computed(() => {
    return adminNavItems.filter((item) => {
        if (item.roles && !item.roles.some(r => roles.value.includes(r))) return false;
        return hasAnyPermission(item.permissions);
    });
});

const visibleManagementNavItems = computed(() => {
    return managementNavItems.filter((item) => {
        if (item.roles && !item.roles.some(r => roles.value.includes(r))) return false;
        return hasAnyPermission(item.permissions);
    });
});

const notifications = ref([]);
const unreadNotificationsCount = ref(0);
const notificationsReady = ref(false);
const notificationsPulse = ref(false);
let notificationsPollIntervalId = null;

const formatNotificationTime = (isoDate) => {
    if (!isoDate) return '';
    const date = new Date(isoDate);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString();
};

const playNotificationSound = async () => {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (!AudioContextClass) return;

    const audioContext = new AudioContextClass();
    if (audioContext.state === 'suspended') {
        try {
            await audioContext.resume();
        } catch {
            return;
        }
    }

    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();

    oscillator.type = 'sine';
    oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
    gain.gain.setValueAtTime(0.0001, audioContext.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.08, audioContext.currentTime + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.2);

    oscillator.connect(gain);
    gain.connect(audioContext.destination);

    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.22);

    oscillator.onended = () => {
        audioContext.close();
    };
};

const audioEnabled = ref(false);
const enableAudioOnce = () => {
    audioEnabled.value = true;
};

const fetchNotifications = async () => {
    try {
        const response = await window.axios.get(route('notifications.index'), {
            params: { limit: 8 },
        });

        const payload = response?.data ?? {};
        const nextNotifications = Array.isArray(payload.notifications) ? payload.notifications : [];
        const nextUnreadCount = Number(payload.unread_count ?? 0) || 0;

        const previousFirstId = notifications.value?.[0]?.id;
        const nextFirstId = nextNotifications?.[0]?.id;

        notifications.value = nextNotifications;
        unreadNotificationsCount.value = nextUnreadCount;

        if (notificationsReady.value && nextFirstId && nextFirstId !== previousFirstId) {
            notificationsPulse.value = true;
            if (audioEnabled.value) {
                await playNotificationSound();
            }
            window.setTimeout(() => {
                notificationsPulse.value = false;
            }, 1200);
        }

        notificationsReady.value = true;
    } catch {
        notificationsReady.value = true;
    }
};

onMounted(() => {
    window.addEventListener('pointerdown', enableAudioOnce, { once: true });
    fetchNotifications();
    notificationsPollIntervalId = window.setInterval(fetchNotifications, 15000);
});

onUnmounted(() => {
    window.removeEventListener('pointerdown', enableAudioOnce);
    if (notificationsPollIntervalId) {
        window.clearInterval(notificationsPollIntervalId);
        notificationsPollIntervalId = null;
    }
});
</script>

<template>
    <Head :title="props.title ? props.title + ' — Sys E-Dépôt Pharma' : 'Sys E-Dépôt Pharma'" />
    <div class="app-container">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50/30 to-purple-50/20 flex flex-col">
            <!-- Navbar -->
            <nav class="navbar">
                <div class="navbar-container">
                    <div class="navbar-content">
                        <!-- Logo & Brand -->
                        <div class="navbar-left">
                            <Link :href="route('dashboard')" class="brand">
                                <div class="brand-logo">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                </div>
                                <div class="brand-text">
                                    <span class="brand-name">SE-D Pharma</span>
                                    <span class="brand-subtitle">Lumière Afrique Group Sarl</span>
                                </div>
                            </Link>
                        </div>

                        <!-- Desktop Navigation - Centered/Right aligned -->
                        <div class="navbar-nav" @click="closeAllDropdowns">
                            <div class="desktop-nav">
                                <NavLink 
                                    v-for="item in visibleMainNavItems" 
                                    :key="item.route"
                                    :href="route(item.route)" 
                                    :active="route().current(item.match || item.route)"
                                    class="nav-link"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                                    </svg>
                                    <span>{{ item.name }}</span>
                                </NavLink>

                                <!-- Management Dropdown -->
                                <div v-if="visibleManagementNavItems.length" class="admin-dropdown management-dropdown" @click.stop>
                                    <button 
                                        @click="toggleManagementMenu"
                                        class="nav-link admin-trigger"
                                        :class="{ 'active': showManagementMenu }"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span>Gestion</span>
                                        <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div v-if="showManagementMenu" class="admin-dropdown-menu">
                                        <NavLink 
                                            v-for="item in visibleManagementNavItems" 
                                            :key="item.route"
                                            :href="route(item.route)" 
                                            :active="route().current(item.match || item.route)"
                                            class="dropdown-item"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                                            </svg>
                                            <span>{{ item.name }}</span>
                                        </NavLink>
                                    </div>
                                </div>

                                <!-- Admin Dropdown -->
                                <div v-if="visibleAdminNavItems.length" class="admin-dropdown" @click.stop>
                                    <button 
                                        @click="toggleAdminMenu"
                                        class="nav-link admin-trigger"
                                        :class="{ 'active': showAdminMenu }"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span>Administration</span>
                                        <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div v-if="showAdminMenu" class="admin-dropdown-menu">
                                        <NavLink 
                                            v-for="item in visibleAdminNavItems" 
                                            :key="item.route"
                                            :href="route(item.route)" 
                                            :active="route().current(item.match || item.route)"
                                            class="dropdown-item"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                                            </svg>
                                            <span>{{ item.name }}</span>
                                        </NavLink>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Menu -->
                        <div class="navbar-right">
                            <Dropdown align="right" width="64">
                                <template #trigger>
                                    <button class="notif-trigger" :class="{ 'notif-pulse': notificationsPulse }">
                                        <svg class="notif-bell" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                                        </svg>
                                        <span v-if="unreadNotificationsCount > 0" class="notif-badge">
                                            {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
                                        </span>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="notif-dropdown">
                                        <div class="notif-header">
                                            <div class="notif-title">Notifications</div>
                                            <Link
                                                v-if="unreadNotificationsCount > 0"
                                                :href="route('notifications.read-all')"
                                                method="post"
                                                as="button"
                                                class="notif-mark-all"
                                            >
                                                Tout marquer comme lu
                                            </Link>
                                        </div>

                                        <div class="notif-list">
                                            <div v-if="notifications.length === 0" class="notif-empty">
                                                Aucune notification
                                            </div>

                                            <Link
                                                v-for="notification in notifications"
                                                :key="notification.id"
                                                :href="route('notifications.go', notification.id)"
                                                class="notif-item"
                                                :class="{ unread: !notification.read_at }"
                                            >
                                                <div class="notif-message">
                                                    {{ notification.data?.message || 'Notification' }}
                                                </div>
                                                <div class="notif-time">
                                                    {{ formatNotificationTime(notification.created_at) }}
                                                </div>
                                            </Link>
                                        </div>
                                    </div>
                                </template>
                            </Dropdown>

                            <Dropdown align="right" width="64">
                                <template #trigger>
                                    <button class="user-menu-trigger">
                                        <div class="user-avatar">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name">{{ user.name }}</span>
                                            <span class="user-role">{{ displayRole }}</span>
                                        </div>
                                        <svg class="dropdown-arrow" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="dropdown-header">
                                        <div class="dropdown-user-info">
                                            <div class="dropdown-avatar">{{ user.name.charAt(0).toUpperCase() }}</div>
                                            <div>
                                                <div class="dropdown-name">{{ user.name }}</div>
                                                <div class="dropdown-email">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="dropdown-divider"></div>

                                    <DropdownLink
                                        v-for="item in settingsItems"
                                        :key="item.route"
                                        :href="route(item.route)"
                                        class="dropdown-link"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                                        </svg>
                                        <span>{{ item.name }}</span>
                                    </DropdownLink>

                                    <!-- Export Database SQL (super_admin only) -->
                                    <a
                                        v-if="roles.includes('super_admin')"
                                        :href="route('profile.export-database-sql')"
                                        target="_blank"
                                        class="dropdown-link"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                                        </svg>
                                        <span>Exporter la base SQL</span>
                                    </a>

                                    <div class="dropdown-divider"></div>

                                    <DropdownLink :href="route('logout')" method="post" as="button" class="dropdown-link logout">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        <span>Déconnexion</span>
                                    </DropdownLink>
                                </template>
                            </Dropdown>

                            <!-- Mobile Menu Toggle -->
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="mobile-menu-toggle"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Navigation -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="mobile-nav">
                    <div class="mobile-nav-section">
                        <div class="mobile-nav-title">Navigation</div>
                        <ResponsiveNavLink 
                            v-for="item in visibleMainNavItems" 
                            :key="item.route"
                            :href="route(item.route)" 
                            :active="route().current(item.match || item.route)"
                            class="mobile-nav-link"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                            </svg>
                            <span>{{ item.name }}</span>
                        </ResponsiveNavLink>
                    </div>

                    <div class="mobile-nav-divider"></div>

                    <div v-if="visibleAdminNavItems.length" class="mobile-nav-section">
                        <div class="mobile-nav-title">Administration</div>
                        <ResponsiveNavLink 
                            v-for="item in visibleAdminNavItems" 
                            :key="item.route"
                            :href="route(item.route)" 
                            :active="route().current(item.match || item.route)"
                            class="mobile-nav-link"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"/>
                            </svg>
                            <span>{{ item.name }}</span>
                        </ResponsiveNavLink>
                    </div>

                    <div class="mobile-nav-divider"></div>

                    <div class="mobile-user-section">
                        <div class="mobile-user-info">
                            <div class="mobile-user-avatar">{{ user.name.charAt(0).toUpperCase() }}</div>
                            <div>
                                <div class="mobile-user-name">{{ user.name }}</div>
                                <div class="mobile-user-email">{{ user.email }}</div>
                            </div>
                        </div>

                        <div class="mobile-nav-section">
                            <ResponsiveNavLink :href="route('profile.edit')" class="mobile-nav-link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span>Mon Profil</span>
                            </ResponsiveNavLink>
                            <a
                                v-if="roles.includes('super_admin')"
                                :href="route('profile.export-database-sql')"
                                target="_blank"
                                class="mobile-nav-link"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                                </svg>
                                <span>Exporter la base SQL</span>
                            </a>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button" class="mobile-nav-link logout">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span>Déconnexion</span>
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Header -->
            <header class="page-header" v-if="$slots.header">
                <div class="page-header-container">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="main-content">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="app-footer">
                <div class="footer-container">
                    <!-- Brand & Legal -->
                    <div class="footer-brand">
                        <!-- Logo placeholder — remplacer /images/logo-lda.png quand disponible -->
                        <div class="footer-logo">
                            <img
                                src="/images/logo-lda.png"
                                alt="Lumière d'Afrique Group"
                                class="footer-logo-img"
                                @error="(e) => e.target.style.display = 'none'"
                            />
                            <div class="footer-logo-fallback">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="footer-company">
                            <p class="footer-company-name">Lumière d'Afrique Group <span class="footer-sarl">SARL</span></p>
                            <p class="footer-company-sub">Sys E-Dépôt Pharma</p>
                        </div>
                    </div>

                    <!-- Legal Info -->
                    <div class="footer-legal-grid">                        
                        <div class="footer-legal-item">
                            <span class="footer-legal-label">IFU</span>
                            <span class="footer-legal-value">3202113259412</span>
                        </div>                        
                        <div class="footer-legal-item">
                            <span class="footer-legal-label">Siège</span>
                            <span class="footer-legal-value">Tranza, Parakou, Bénin</span>
                        </div>
                        <div class="footer-legal-item">
                            <span class="footer-legal-label">Tél.</span>
                            <span class="footer-legal-value">+229 01 97 11 55 91</span>
                        </div>
                        <div class="footer-legal-item">
                            <span class="footer-legal-label">Email</span>
                            <a href="mailto:ufras@yahoo.fr" class="footer-legal-link">ufras@yahoo.fr</a>
                        </div>
                    </div>

                    <!-- Links -->
                    <nav class="footer-links">
                        <a href="/cgu" class="footer-link">CGU</a>
                        <span class="footer-sep">·</span>
                        <a href="/confidentialite" class="footer-link">Confidentialité</a>
                        <span class="footer-sep">·</span>
                        <a href="/mentions-legales" class="footer-link">Mentions légales</a>
                        <span class="footer-sep">·</span>
                        <a href="/contact" class="footer-link">Contact</a>
                        <span class="footer-sep">·</span>
                        <a href="/a-propos" class="footer-link">À propos</a>
                    </nav>

                    <!-- Copyright -->
                    <p class="footer-copy">
                        © {{ new Date().getFullYear() }} Lumière d'Afrique Group SARL — Tous droits réservés
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <!-- Idle lock screen — mounts once, listens to all activity events -->
    <IdleLockScreen :user="user" :timeout-mins="10" />
</template>

<style scoped>
/* Design Tokens - CSS Variables for consistency */
:root {
    --nav-primary: #3b82f6;
    --nav-primary-light: rgba(59, 130, 246, 0.08);
    --nav-primary-gradient: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.15));
    --nav-text: #4b5563;
    --nav-text-muted: #6b7280;
    --nav-bg-hover: rgba(59, 130, 246, 0.06);
    --nav-radius: 0.5rem;
    --nav-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    --nav-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
    
    /* Animation easings */
    --ease-out-quart: cubic-bezier(0.25, 1, 0.5, 1);
    --ease-out-quint: cubic-bezier(0.22, 1, 0.36, 1);
    --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
}

/* Navbar - More compact and refined */
.navbar {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    box-shadow: var(--nav-shadow);
    position: sticky;
    top: 0;
    z-index: 50;
}

.navbar-container {
    max-width: 90rem;
    margin: 0 auto;
    padding: 0 1.25rem;
}

.navbar-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 3.25rem; /* Further reduced for compactness */
}

.navbar-left {
    display: flex;
    align-items: center;
    gap: 0.75rem; /* Reduced from 1.25rem */
    flex-shrink: 0;
}

/* Navigation container - fills space and aligns right */
.navbar-nav {
    display: none;
    flex: 1;
    justify-content: flex-end;
    align-items: center;
    margin: 0 1rem;
}

@media (min-width: 1024px) {
    .navbar-nav {
        display: flex;
        margin: 0 0.5rem;
    }
}

@media (min-width: 1280px) {
    .navbar-nav {
        margin: 0 1.5rem;
    }
    
    .nav-link {
        padding: 0.5rem 0.375rem;
    }
    
    .nav-link:hover,
    .nav-link.active {
        padding: 0.5rem 0.75rem;
    }
}

@media (min-width: 1536px) {
    .navbar-container {
        padding: 0 2rem;
    }
}

/* Brand - More compact */
.brand {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    text-decoration: none;
    padding: 0.375rem 0.5rem;
    border-radius: var(--nav-radius);
    transition: all 200ms var(--ease-out-quart);
}

.brand:hover {
    background: var(--nav-bg-hover);
    transform: translateY(-1px);
}

.brand-logo {
    width: 2.25rem;
    height: 2.25rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.25);
    transition: transform 200ms var(--ease-out-quart), box-shadow 200ms var(--ease-out-quart);
}

.brand:hover .brand-logo {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
}

.brand-logo svg {
    width: 1.25rem;
    height: 1.25rem;
}

.brand-text {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.brand-name {
    font-size: 1rem;
    font-weight: 700;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1.2;
    letter-spacing: -0.01em;
}

.brand-subtitle {
    font-size: 0.625rem;
    color: var(--nav-text-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

/* Desktop Nav - Compact pill-style navigation */
.desktop-nav {
    display: flex;
    align-items: center;
    gap: 0.125rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--nav-text-muted);
    text-decoration: none;
    border-radius: var(--nav-radius);
    transition: all 200ms var(--ease-out-quart);
    white-space: nowrap;
    position: relative;
    letter-spacing: -0.01em;
    overflow: hidden;
}

.nav-link svg {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    transition: transform 200ms var(--ease-out-quart);
}

.nav-link span {
    max-width: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-width 300ms var(--ease-out-quart), opacity 200ms var(--ease-out-quart), margin-left 200ms var(--ease-out-quart);
    margin-left: 0;
}

.nav-link:hover {
    background: var(--nav-primary-light);
    color: var(--nav-primary);
    transform: translateY(-1px);
    padding: 0.5rem 0.625rem;
}

.nav-link:hover span {
    max-width: 200px;
    opacity: 1;
    margin-left: 0.375rem;
}

.nav-link:hover svg {
    transform: scale(1.1);
}

.nav-link.active {
    background: var(--nav-primary-gradient);
    color: var(--nav-primary);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
    padding: 0.5rem 0.625rem;
}

.nav-link.active span {
    max-width: 200px;
    opacity: 1;
    margin-left: 0.375rem;
}

.nav-link.active::before {
    content: '';
    position: absolute;
    bottom: 2px;
    left: 50%;
    transform: translateX(-50%);
    width: 4px;
    height: 4px;
    background: var(--nav-primary);
    border-radius: 50%;
    opacity: 0.5;
}

/* Admin Dropdown - Enhanced with smooth animations */
.admin-dropdown {
    position: relative;
}

.admin-trigger {
    position: relative;
}

/* Dropdown triggers show text/arrow on hover like other nav links */
.nav-link.admin-trigger .dropdown-arrow {
    max-width: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-width 300ms var(--ease-out-quart), opacity 200ms var(--ease-out-quart), margin-left 200ms var(--ease-out-quart);
    margin-left: 0;
}

.nav-link.admin-trigger:hover .dropdown-arrow,
.nav-link.admin-trigger.active .dropdown-arrow {
    max-width: 200px;
    opacity: 1;
    margin-left: 0.25rem;
}

.dropdown-arrow {
    width: 0.75rem;
    height: 0.75rem;
    margin-left: 0.125rem;
    transition: transform 250ms var(--ease-out-quint);
    flex-shrink: 0;
}

.admin-trigger.active .dropdown-arrow {
    transform: rotate(180deg);
}

.admin-dropdown-menu {
    position: absolute;
    top: calc(100% + 0.375rem);
    right: 0;
    left: auto;
    min-width: 10rem;
    background: rgba(255, 255, 255, 0.98);
    border-radius: 0.625rem;
    box-shadow: var(--nav-shadow-lg);
    border: 1px solid rgba(0, 0, 0, 0.04);
    padding: 0.25rem;
    z-index: 50;
    animation: dropdownEnter 250ms var(--ease-out-quart);
    transform-origin: top right;
}

@keyframes dropdownEnter {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    color: var(--nav-text);
    text-decoration: none;
    border-radius: calc(var(--nav-radius) - 0.125rem);
    transition: all 180ms var(--ease-out-quart);
    width: 100%;
    position: relative;
    overflow: hidden;
}

.dropdown-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%) scaleY(0);
    width: 3px;
    height: 60%;
    background: var(--nav-primary);
    border-radius: 0 2px 2px 0;
    transition: transform 180ms var(--ease-out-quart);
}

.dropdown-item svg {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    color: var(--nav-text-muted);
    transition: all 180ms var(--ease-out-quart);
}

.dropdown-item:hover {
    background: var(--nav-primary-light);
    color: var(--nav-primary);
    padding-left: 0.75rem;
}

.dropdown-item:hover::before {
    transform: translateY(-50%) scaleY(1);
}

.dropdown-item:hover svg {
    color: var(--nav-primary);
    transform: scale(1.1);
}

.dropdown-item.active {
    background: var(--nav-primary-gradient);
    color: var(--nav-primary);
    font-weight: 600;
}

.dropdown-item.active::before {
    transform: translateY(-50%) scaleY(1);
    opacity: 0.5;
}

.dropdown-item.active svg {
    color: var(--nav-primary);
}

/* Notifications - Compact and refined */
.notif-trigger {
    display: none;
    position: relative;
    width: 2.5rem;
    height: 2.5rem;
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: 50%;
    cursor: pointer;
    transition: all 200ms var(--ease-out-quart);
    align-items: center;
    justify-content: center;
}

@media (min-width: 768px) {
    .notif-trigger {
        display: inline-flex;
    }
}

.notif-trigger:hover {
    background: var(--nav-bg-hover);
    border-color: rgba(59, 130, 246, 0.25);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.notif-bell {
    width: 1.125rem;
    height: 1.125rem;
    color: var(--nav-text-muted);
    transition: all 200ms var(--ease-out-quart);
}

.notif-trigger:hover .notif-bell {
    color: var(--nav-primary);
    transform: scale(1.1);
}

.notif-badge {
    position: absolute;
    top: -0.125rem;
    right: -0.125rem;
    min-width: 1.125rem;
    height: 1.125rem;
    padding: 0 0.25rem;
    border-radius: 9999px;
    background: #ef4444;
    color: white;
    font-size: 0.6875rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
    transition: transform 200ms var(--ease-out-quart);
}

.notif-trigger:hover .notif-badge {
    transform: scale(1.05);
}

.notif-pulse .notif-bell {
    animation: bellShake 600ms var(--ease-out-quart);
}

.notif-pulse .notif-badge {
    animation: badgePop 400ms var(--ease-out-quint);
}

@keyframes bellShake {
    0% { transform: rotate(0deg); }
    20% { transform: rotate(10deg); }
    40% { transform: rotate(-10deg); }
    60% { transform: rotate(6deg); }
    80% { transform: rotate(-4deg); }
    100% { transform: rotate(0deg); }
}

@keyframes badgePop {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.notif-dropdown {
    width: 22rem;
    max-width: calc(100vw - 2rem);
}

.notif-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.notif-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #111827;
}

.notif-mark-all {
    font-size: 0.75rem;
    font-weight: 600;
    color: #3b82f6;
    background: transparent;
    border: 0;
    cursor: pointer;
}

.notif-mark-all:hover {
    text-decoration: underline;
}

.notif-list {
    max-height: 22rem;
    overflow: auto;
}

.notif-empty {
    padding: 1rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.notif-item {
    display: block;
    padding: 0.875rem 1rem;
    text-decoration: none;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04);
    transition: background 150ms;
}

.notif-item:hover {
    background: rgba(59, 130, 246, 0.05);
}

.notif-item.unread {
    background: rgba(59, 130, 246, 0.06);
}

.notif-message {
    font-size: 0.875rem;
    color: #111827;
    line-height: 1.35;
}

.notif-time {
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #6b7280;
}

/* User Menu */
.navbar-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-menu-trigger {
    display: none;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.875rem;
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 150ms;
}

@media (min-width: 768px) {
    .user-menu-trigger {
        display: flex;
    }
}

.user-menu-trigger:hover {
    background: rgba(59, 130, 246, 0.05);
    border-color: rgba(59, 130, 246, 0.2);
}

.user-avatar {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
}

.user-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    line-height: 1.2;
}

.user-role {
    font-size: 0.75rem;
    color: #9ca3af;
}

/* Dropdown Content */
.dropdown-header {
    padding: 0.875rem 1rem;
}

.dropdown-user-info {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.dropdown-avatar {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
    flex-shrink: 0;
}

.dropdown-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #1f2937;
}

.dropdown-email {
    font-size: 0.8125rem;
    color: #6b7280;
    margin-top: 0.125rem;
}

.dropdown-divider {
    height: 1px;
    background: rgba(0, 0, 0, 0.06);
    margin: 0.5rem 0;
}

.dropdown-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #4b5563;
    text-decoration: none;
    transition: all 150ms;
    width: 100%;
    border: none;
    background: none;
}

.dropdown-link {
display: flex;
align-items: center;
gap: 0.75rem;
padding: 0.75rem 1rem;
font-size: 0.875rem;
color: #4b5563;
text-decoration: none;
transition: all 150ms;
width: 100%;
border: none;
background: none;
cursor: pointer;
text-align: left;
}
.dropdown-link svg {
width: 1.125rem;
height: 1.125rem;
flex-shrink: 0;
}
.dropdown-link:hover {
background: rgba(59, 130, 246, 0.05);
color: #3b82f6;
}
.dropdown-link.logout {
color: #ef4444;
}
.dropdown-link.logout:hover {
background: rgba(239, 68, 68, 0.05);
}
/* Mobile Menu */
.mobile-menu-toggle {
display: flex;
align-items: center;
justify-content: center;
padding: 0.5rem;
border-radius: 0.5rem;
background: none;
border: none;
color: #6b7280;
cursor: pointer;
transition: all 150ms;
}
@media (min-width: 768px) {
.mobile-menu-toggle {
display: none;
}
}
.mobile-menu-toggle:hover {
background: rgba(59, 130, 246, 0.08);
color: #3b82f6;
}
/* Mobile Navigation */
.mobile-nav {
border-top: 1px solid rgba(0, 0, 0, 0.05);
background: white;
}
@media (min-width: 768px) {
.mobile-nav {
display: none !important;
}
}
.mobile-nav-section {
padding: 1rem;
}
.mobile-nav-title {
font-size: 0.75rem;
font-weight: 600;
color: #9ca3af;
text-transform: uppercase;
letter-spacing: 0.05em;
padding: 0.5rem 0.75rem;
}
.mobile-nav-link {
display: flex;
align-items: center;
gap: 0.75rem;
padding: 0.875rem 0.75rem;
font-size: 0.9375rem;
color: #4b5563;
text-decoration: none;
border-radius: 0.5rem;
transition: all 150ms;
margin-bottom: 0.25rem;
}
.mobile-nav-link svg {
width: 1.25rem;
height: 1.25rem;
flex-shrink: 0;
}
.mobile-nav-link:hover {
background: rgba(59, 130, 246, 0.08);
color: #3b82f6;
}
.mobile-nav-link.active {
background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.15));
color: #3b82f6;
font-weight: 600;
}
.mobile-nav-link.logout {
color: #ef4444;
}
.mobile-nav-link.logout:hover {
background: rgba(239, 68, 68, 0.05);
}
.mobile-nav-divider {
height: 1px;
background: rgba(0, 0, 0, 0.06);
margin: 0.5rem 1rem;
}
.mobile-user-section {
padding: 1rem;
background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
}
.mobile-user-info {
display: flex;
align-items: center;
gap: 0.875rem;
padding: 0.875rem;
background: white;
border-radius: 0.75rem;
margin-bottom: 0.75rem;
}
.mobile-user-avatar {
width: 2.75rem;
height: 2.75rem;
border-radius: 50%;
background: linear-gradient(135deg, #3b82f6, #8b5cf6);
color: white;
display: flex;
align-items: center;
justify-content: center;
font-weight: 600;
font-size: 1rem;
flex-shrink: 0;
}
.mobile-user-name {
font-size: 0.9375rem;
font-weight: 600;
color: #1f2937;
}
.mobile-user-email {
font-size: 0.8125rem;
color: #6b7280;
margin-top: 0.125rem;
}
/* Page Header */
.page-header {
background: white;
border-bottom: 1px solid rgba(0, 0, 0, 0.05);
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.page-header-container {
max-width: 90rem;
margin: 0 auto;
padding: 1.5rem 1rem;
}
/* Main Content */
.main-content {
max-width: 90rem;
width: 100%;
margin: 0 auto;
padding: 2rem 1rem;
flex: 1;
}
@media (max-width: 640px) {
.main-content {
padding: 1rem 0.75rem;
}
.brand-text {
    display: none;
}

.brand-logo {
    width: 2.25rem;
    height: 2.25rem;
}

.navbar-content {
    height: 4rem;
}
}

/* ── Footer ──────────────────────────────────────────────── */
.app-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.06);
    background: linear-gradient(135deg, #f8faff 0%, #faf8ff 100%);
    margin-top: auto;
}
.footer-container {
    max-width: 90rem;
    margin: 0 auto;
    padding: 2rem 1rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
}

/* Brand row */
.footer-brand {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}
.footer-logo {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.625rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.footer-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.footer-logo-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
.footer-logo-fallback svg {
    width: 1.5rem;
    height: 1.5rem;
}
.footer-company-name {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.footer-sarl {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    background: #f3f4f6;
    border-radius: 0.25rem;
    padding: 0.1rem 0.35rem;
    margin-left: 0.25rem;
}
.footer-company-sub {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0.125rem 0 0;
}

/* Legal info grid */
.footer-legal-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.5rem 2rem;
}
.footer-legal-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
}
.footer-legal-label {
    font-weight: 600;
    color: #9ca3af;
    text-transform: uppercase;
    font-size: 0.6875rem;
    letter-spacing: 0.04em;
}
.footer-legal-value {
    color: #4b5563;
}
.footer-legal-link {
    color: #3b82f6;
    text-decoration: none;
    transition: color 150ms;
}
.footer-legal-link:hover {
    color: #2563eb;
    text-decoration: underline;
}

/* Links row */
.footer-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}
.footer-link {
    font-size: 0.8125rem;
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
    transition: color 150ms;
    padding: 0.125rem 0;
}
.footer-link:hover {
    color: #3b82f6;
}
.footer-sep {
    color: #d1d5db;
    font-size: 0.875rem;
    line-height: 1;
}

/* Copyright */
.footer-copy {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 0;
    text-align: center;
}
@media (max-width: 640px) {
.footer-legal-grid { gap: 0.375rem 1.25rem; }
.footer-links { gap: 0.2rem; }
}

/* Accessibility - Respect reduced motion preferences */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
    
    .brand:hover,
    .brand:hover .brand-logo,
    .nav-link:hover,
    .nav-link:hover svg,
    .dropdown-item:hover,
    .dropdown-item:hover svg {
        transform: none !important;
    }
    
    .admin-dropdown-menu {
        animation: none !important;
    }
}
</style>
