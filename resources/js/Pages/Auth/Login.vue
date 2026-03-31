<script setup>
import { ref, computed } from 'vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const isLoading = ref(false);

const submit = () => {
    isLoading.value = true;
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            isLoading.value = false;
        },
    });
};

// Validation en temps réel
const emailValid = computed(() => {
    if (!form.email) return null;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(form.email);
});

const passwordStrength = computed(() => {
    if (!form.password) return null;
    if (form.password.length < 6) return 'weak';
    if (form.password.length < 10) return 'medium';
    return 'strong';
});
</script>

<template>
        <Head title="Connexion - Système de Gestion Pharmaceutique" />

        <div class="login-container">
            <!-- Header Section -->
            <div class="login-header">
                <div class="logo-section">
                    <div class="logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <div class="logo-text">
                        <h1>Sys E-Dépôt Pharma</h1>
                        <p>Système de Gestion Pharmaceutique</p>
                    </div>
                </div>
                
                <div class="system-info">
                    <div class="info-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Accès Sécurisé</span>
                    </div>
                </div>
            </div>

            <!-- Status Message -->
            <div v-if="status" class="status-message">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ status }}</span>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="login-form">
                <div class="welcome-text">
                    <h2>Bienvenue</h2>
                    <p>Connectez-vous pour accéder au système de traçabilité pharmaceutique</p>
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <InputLabel for="email" value="Adresse email" />
                    
                    <div class="input-wrapper" :class="{ 
                        'has-error': form.errors.email,
                        'has-success': emailValid === true
                    }">
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                            </svg>
                        </div>
                        
                        <TextInput
                            id="email"
                            type="email"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="exemple@hopital.ma"
                        />
                        
                        <div v-if="emailValid === true" class="validation-icon success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </div>
                    
                    <InputError class="error-message" :message="form.errors.email" />
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <InputLabel for="password" value="Mot de passe" />
                    
                    <div class="input-wrapper" :class="{ 'has-error': form.errors.password }">
                        <div class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        
                        <TextInput
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="Entrez votre mot de passe"
                        />
                        
                        <button
                            type="button"
                            class="toggle-password"
                            @click="showPassword = !showPassword"
                            :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        >
                            <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    
                    <InputError class="error-message" :message="form.errors.password" />
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    <label class="remember-checkbox">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        <span>Se souvenir de moi</span>
                    </label>
                    
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="forgot-password"
                    >
                        Mot de passe oublié ?
                    </Link>
                </div>

                <!-- Submit Button -->
                <PrimaryButton
                    type="submit"
                    class="submit-button"
                    :class="{ 'loading': form.processing }"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="spinner" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                    <span>{{ form.processing ? 'Connexion en cours...' : 'Se connecter' }}</span>
                </PrimaryButton>
            </form>

            <!-- Footer Info -->
            <div class="login-footer">
                <div class="info-cards">
                    <div class="info-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div>
                            <strong>Traçabilité</strong>
                            <span>Suivi des médicaments</span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <strong>Péremption</strong>
                            <span>Gestion des dates</span>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <div>
                            <strong>Statistiques</strong>
                            <span>Rapports détaillés</span>
                        </div>
                    </div>
                </div>
                
                <div class="copyright">
                    <p>© 2025 PharmaTrac - Système de Gestion Pharmaceutique</p>
                    <p class="security-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Connexion sécurisée SSL/TLS - Données protégées
                    </p>
                </div>
            </div>
        </div>
</template>

<style scoped>
.login-container {
    max-width: 28rem;
    margin: 0 auto;
    padding: 2rem 1rem;
}

/* Header */
.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.logo-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.logo-icon {
    width: 4rem;
    height: 4rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.logo-icon svg {
    width: 2.5rem;
    height: 2.5rem;
}

.logo-text h1 {
    font-size: 1.875rem;
    font-weight: 700;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
}

.logo-text p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0.25rem 0 0 0;
}

.system-info {
    display: flex;
    justify-content: center;
}

.info-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.1));
    border: 1px solid rgba(34, 197, 94, 0.2);
    border-radius: 9999px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #15803d;
}

.info-badge svg {
    width: 1rem;
    height: 1rem;
}

/* Status Message */
.status-message {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(16, 185, 129, 0.05));
    border: 1.5px solid rgba(34, 197, 94, 0.3);
    border-radius: 0.75rem;
    color: #15803d;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
}

.status-message svg {
    width: 1.25rem;
    height: 1.25rem;
    flex-shrink: 0;
}

/* Form */
.login-form {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.welcome-text {
    text-align: center;
    margin-bottom: 2rem;
}

.welcome-text h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.welcome-text p {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    margin-top: 0.5rem;
}

.input-wrapper.has-error :deep(input) {
    border-color: #ef4444;
}

.input-wrapper.has-success :deep(input) {
    border-color: #22c55e;
}

.input-icon {
    position: absolute;
    left: 1rem;
    width: 1.25rem;
    height: 1.25rem;
    color: #9ca3af;
    pointer-events: none;
    z-index: 1;
}

.input-icon svg {
    width: 100%;
    height: 100%;
}

.input-wrapper :deep(input) {
    padding-left: 3rem !important;
    padding-right: 3rem !important;
}

.validation-icon {
    position: absolute;
    right: 1rem;
    width: 1.25rem;
    height: 1.25rem;
    pointer-events: none;
}

.validation-icon.success {
    color: #22c55e;
}

.validation-icon svg {
    width: 100%;
    height: 100%;
}

.toggle-password {
    position: absolute;
    right: 1rem;
    width: 1.25rem;
    height: 1.25rem;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    transition: color 150ms;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toggle-password:hover {
    color: #6b7280;
}

.toggle-password svg {
    width: 100%;
    height: 100%;
}

.error-message {
    margin-top: 0.5rem;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.remember-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    user-select: none;
}

.remember-checkbox span {
    font-size: 0.875rem;
    color: #4b5563;
}

.forgot-password {
    font-size: 0.875rem;
    color: #3b82f6;
    font-weight: 500;
    text-decoration: none;
    transition: color 150ms;
}

.forgot-password:hover {
    color: #2563eb;
    text-decoration: underline;
}

.submit-button {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
    padding: 0.875rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    border: none;
    border-radius: 0.75rem;
    color: white;
    cursor: pointer;
    transition: all 150ms;
    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
}

.submit-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
}

.submit-button:active:not(:disabled) {
    transform: translateY(0);
}

.submit-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.submit-button svg {
    width: 1.25rem;
    height: 1.25rem;
}

.submit-button.loading svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Footer */
.login-footer {
    margin-top: 2rem;
}

.info-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.info-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 0.75rem;
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 0.75rem;
    text-align: center;
}

.info-card svg {
    width: 1.5rem;
    height: 1.5rem;
    color: #3b82f6;
}

.info-card div {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.info-card strong {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1f2937;
}

.info-card span {
    font-size: 0.6875rem;
    color: #9ca3af;
}

.copyright {
    text-align: center;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.copyright p {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 0 0 0.5rem 0;
}

.security-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    color: #6b7280 !important;
}

.security-note svg {
    width: 0.875rem;
    height: 0.875rem;
}

/* Responsive */
@media (max-width: 640px) {
    .login-container {
        padding: 1rem 0.75rem;
    }

    .login-form {
        padding: 1.5rem 1rem;
    }

    .info-cards {
        grid-template-columns: 1fr;
    }

    .form-options {
        flex-direction: column;
        align-items: flex-start;
    }

    .logo-text h1 {
        font-size: 1.5rem;
    }
}
</style>
