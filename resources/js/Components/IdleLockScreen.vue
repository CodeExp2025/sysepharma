<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    user:        { type: Object,  required: true },
    timeoutMins: { type: Number,  default: 15 },   // minutes of inactivity before lock
});

// ── State ────────────────────────────────────────────────────────────────────
const locked    = ref(false);
const password  = ref('');
const error     = ref('');
const remaining = ref(5);   // failed attempts left
const loading   = ref(false);
const showPass  = ref(false);

// ── Idle timer ───────────────────────────────────────────────────────────────
let idleTimer = null;
const TIMEOUT_MS = props.timeoutMins * 60 * 1000;

const resetTimer = () => {
    if (locked.value) return;
    clearTimeout(idleTimer);
    idleTimer = setTimeout(lock, TIMEOUT_MS);
};

const EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];

const lock = () => {
    locked.value = true;
    password.value = '';
    error.value = '';
    showPass.value = false;
    clearTimeout(idleTimer);
};

// ── Unlock ───────────────────────────────────────────────────────────────────
const unlock = async () => {
    if (!password.value || loading.value) return;

    loading.value = true;
    error.value   = '';

    try {
        await axios.post(route('session.verify-password'), { password: password.value });
        // Success
        locked.value   = false;
        password.value = '';
        remaining.value = 5;
        resetTimer();
    } catch (err) {
        const data = err.response?.data ?? {};

        if (data.logout) {
            // Force redirect to login
            window.location.href = '/login';
            return;
        }

        remaining.value = data.remaining ?? remaining.value - 1;
        error.value     = data.message ?? 'Mot de passe incorrect.';
        password.value  = '';

        if (remaining.value <= 0) {
            window.location.href = '/login';
        }
    } finally {
        loading.value = false;
    }
};

const onKeydown = (e) => {
    if (locked.value && e.key === 'Enter') unlock();
};

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
    EVENTS.forEach(e => window.addEventListener(e, resetTimer, { passive: true }));
    window.addEventListener('keydown', onKeydown);
    resetTimer();
});

onUnmounted(() => {
    EVENTS.forEach(e => window.removeEventListener(e, resetTimer));
    window.removeEventListener('keydown', onKeydown);
    clearTimeout(idleTimer);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="locked"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/80 backdrop-blur-sm"
                @click.self="() => {}"
            >
                <div class="w-full max-w-sm mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 px-8 py-8 text-center">
                        <!-- Avatar initials -->
                        <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-3 ring-2 ring-white/40">
                            <span class="text-2xl font-bold text-white">
                                {{ (user.name ?? 'U').charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <p class="text-white font-semibold text-lg">{{ user.name }}</p>
                        <p class="text-white/70 text-sm mt-0.5">{{ user.email }}</p>
                    </div>

                    <!-- Body -->
                    <div class="px-8 py-6">
                        <div class="flex items-center gap-2 mb-5 text-gray-500">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <p class="text-sm">Session verrouillée par inactivité. Entrez votre mot de passe pour continuer.</p>
                        </div>

                        <!-- Error -->
                        <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                            <div v-if="error" class="mb-4 px-3 py-2.5 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-sm text-red-700 font-medium">{{ error }}</p>
                                <p v-if="remaining <= 2 && remaining > 0" class="text-xs text-red-500 mt-0.5">
                                    Attention : après {{ remaining }} échec(s) la session sera fermée.
                                </p>
                            </div>
                        </Transition>

                        <!-- Password input -->
                        <div class="relative mb-4">
                            <input
                                :type="showPass ? 'text' : 'password'"
                                v-model="password"
                                placeholder="Mot de passe"
                                autofocus
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                                :class="{ 'border-red-400 focus:ring-red-400': error }"
                                :disabled="loading"
                            >
                            <button
                                type="button"
                                @click="showPass = !showPass"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600"
                            >
                                <svg v-if="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Unlock button -->
                        <button
                            @click="unlock"
                            :disabled="!password || loading"
                            class="w-full py-3 rounded-xl font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2"
                            :class="password && !loading
                                ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm hover:shadow-md'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                        >
                            <svg v-if="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 11V7a4 4 0 018 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                            {{ loading ? 'Vérification...' : 'Déverrouiller' }}
                        </button>

                        <!-- Attempts indicator -->
                        <div class="mt-3 flex justify-center gap-1">
                            <div v-for="i in 5" :key="i"
                                class="w-2 h-2 rounded-full transition-colors duration-200"
                                :class="i <= remaining ? 'bg-green-400' : 'bg-red-300'"
                            />
                        </div>
                        <p class="text-center text-xs text-gray-400 mt-1">{{ remaining }}/5 tentatives restantes</p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
