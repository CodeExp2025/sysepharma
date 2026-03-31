<script setup>
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
    <Head title="Réinitialisation — Sys E-Dépôt Pharma" />

    <div class="auth-page">
        <!-- Header -->
        <div class="auth-header">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <h1 class="brand-name">Sys E-Dépôt Pharma</h1>
            <p class="brand-sub">Système de Gestion Pharmaceutique</p>
        </div>

        <!-- Card -->
        <div class="auth-card">
            <div class="card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <h2 class="card-title">Nouveau mot de passe</h2>
            <p class="card-desc">Choisissez un nouveau mot de passe sécurisé pour votre compte.</p>

            <form @submit.prevent="submit" class="auth-form">
                <!-- Email (read-only) -->
                <div class="field-group">
                    <label class="field-label" for="email">Adresse e-mail</label>
                    <div class="field-wrap">
                        <div class="field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        <input
                            id="email" type="email" v-model="form.email"
                            required autocomplete="username" readonly
                            class="auth-input readonly"
                        />
                    </div>
                    <InputError :message="form.errors.email" class="mt-1.5" />
                </div>

                <!-- New password -->
                <div class="field-group">
                    <label class="field-label" for="password">Nouveau mot de passe</label>
                    <div class="field-wrap">
                        <div class="field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            required autofocus autocomplete="new-password"
                            placeholder="Nouveau mot de passe"
                            :class="['auth-input', { 'has-error': form.errors.password }]"
                        />
                        <button type="button" class="toggle-btn" @click="showPassword = !showPassword">
                            <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password" class="mt-1.5" />
                </div>

                <!-- Confirm password -->
                <div class="field-group">
                    <label class="field-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <div class="field-wrap">
                        <div class="field-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <input
                            id="password_confirmation"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password_confirmation"
                            required autocomplete="new-password"
                            placeholder="Confirmer le mot de passe"
                            :class="['auth-input', { 'has-error': form.errors.password_confirmation }]"
                        />
                    </div>
                    <InputError :message="form.errors.password_confirmation" class="mt-1.5" />
                </div>

                <button type="submit" class="auth-btn" :disabled="form.processing">
                    <svg v-if="form.processing" class="spinner" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ form.processing ? 'Enregistrement...' : 'Réinitialiser le mot de passe' }}
                </button>
            </form>
        </div>

        <p class="auth-copy">© 2025 Sys E-Dépôt Pharma — Lumière Afrique Group Sarl</p>
    </div>
</template>

<style scoped>
.auth-page {
    min-height: 100vh; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 2rem 1rem;
    background: linear-gradient(135deg, #f0f4ff 0%, #f8f0ff 100%);
}
.auth-header { text-align: center; margin-bottom: 2rem; }
.logo-icon {
    width: 3.5rem; height: 3.5rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 1rem; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 0.75rem;
    box-shadow: 0 10px 25px rgba(59,130,246,0.3);
    animation: float 3s ease-in-out infinite;
}
.logo-icon svg { width: 2rem; height: 2rem; color: white; }
@keyframes float { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
.brand-name {
    font-size: 1.5rem; font-weight: 700; margin: 0;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.brand-sub { font-size: 0.8125rem; color: #6b7280; margin: 0.25rem 0 0; }
.auth-card {
    width: 100%; max-width: 26rem; background: white; border-radius: 1rem; padding: 2rem;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    border: 1px solid rgba(0,0,0,0.05);
}
.card-icon {
    width: 3rem; height: 3rem;
    background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(139,92,246,0.1));
    border-radius: 0.75rem; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem;
}
.card-icon svg { width: 1.5rem; height: 1.5rem; color: #3b82f6; }
.card-title { font-size: 1.25rem; font-weight: 700; color: #111827; text-align: center; margin: 0 0 0.5rem; }
.card-desc { font-size: 0.875rem; color: #6b7280; text-align: center; line-height: 1.5; margin: 0 0 1.75rem; }
.auth-form { display: flex; flex-direction: column; gap: 1.25rem; }
.field-group { display: flex; flex-direction: column; }
.field-label { font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.375rem; }
.field-wrap { position: relative; }
.field-icon {
    position: absolute; left: 0.875rem; top: 50%; transform: translateY(-50%);
    width: 1.125rem; height: 1.125rem; color: #9ca3af; pointer-events: none;
}
.field-icon svg { width: 100%; height: 100%; }
.auth-input {
    width: 100%; padding: 0.75rem 2.75rem 0.75rem 2.75rem;
    border: 1.5px solid #d1d5db; border-radius: 0.625rem;
    font-size: 0.9375rem; color: #111827; outline: none;
    transition: border-color 150ms, box-shadow 150ms; box-sizing: border-box;
}
.auth-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.auth-input.has-error { border-color: #ef4444; }
.auth-input.readonly { background: #f9fafb; color: #6b7280; cursor: default; }
.toggle-btn {
    position: absolute; right: 0.875rem; top: 50%; transform: translateY(-50%);
    width: 1.125rem; height: 1.125rem;
    background: none; border: none; color: #9ca3af; cursor: pointer;
    padding: 0; display: flex; align-items: center; transition: color 150ms;
}
.toggle-btn:hover { color: #6b7280; }
.toggle-btn svg { width: 100%; height: 100%; }
.auth-btn {
    display: flex; align-items: center; justify-content: center; gap: 0.5rem;
    padding: 0.875rem 1.5rem; width: 100%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white; font-size: 0.9375rem; font-weight: 600;
    border: none; border-radius: 0.75rem; cursor: pointer;
    box-shadow: 0 4px 6px -1px rgba(59,130,246,0.3); transition: all 150ms;
}
.auth-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 15px -3px rgba(59,130,246,0.4); }
.auth-btn:disabled { opacity: 0.7; cursor: not-allowed; }
.auth-btn svg { width: 1.125rem; height: 1.125rem; }
.spinner { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.auth-copy { margin-top: 1.5rem; font-size: 0.75rem; color: #9ca3af; text-align: center; }
</style>
