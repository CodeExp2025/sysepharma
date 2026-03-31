<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ status: String });

const form = useForm({});
const submit = () => form.post(route('verification.send'));
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Vérification e-mail — Sys E-Dépôt Pharma" />

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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/>
                </svg>
            </div>
            <h2 class="card-title">Vérifiez votre e-mail</h2>
            <p class="card-desc">
                Merci pour votre inscription ! Avant de commencer, veuillez vérifier votre adresse e-mail en cliquant sur le lien que nous vous avons envoyé. Si vous n'avez pas reçu l'e-mail, nous pouvons vous en envoyer un nouveau.
            </p>

            <!-- Success -->
            <div v-if="verificationLinkSent" class="status-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
            </div>

            <form @submit.prevent="submit" class="auth-form">
                <button type="submit" class="auth-btn" :disabled="form.processing">
                    <svg v-if="form.processing" class="spinner" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    {{ form.processing ? 'Envoi...' : 'Renvoyer l\'e-mail de vérification' }}
                </button>
            </form>

            <div class="card-footer">
                <Link :href="route('logout')" method="post" as="button" class="logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Se déconnecter
                </Link>
            </div>
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
.card-desc { font-size: 0.875rem; color: #6b7280; text-align: center; line-height: 1.6; margin: 0 0 1.25rem; }
.status-success {
    display: flex; align-items: flex-start; gap: 0.625rem;
    padding: 0.75rem 1rem; margin-bottom: 1.25rem;
    background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3);
    border-radius: 0.75rem; color: #15803d; font-size: 0.875rem; font-weight: 500; line-height: 1.4;
}
.status-success svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; margin-top: 1px; }
.auth-form { display: flex; flex-direction: column; }
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
.card-footer { margin-top: 1.25rem; text-align: center; }
.logout-btn {
    display: inline-flex; align-items: center; gap: 0.375rem;
    font-size: 0.875rem; color: #6b7280; font-weight: 500;
    background: none; border: none; cursor: pointer; text-decoration: none;
    transition: color 150ms;
}
.logout-btn:hover { color: #ef4444; }
.logout-btn svg { width: 1rem; height: 1rem; }
.auth-copy { margin-top: 1.5rem; font-size: 0.75rem; color: #9ca3af; text-align: center; }
</style>
