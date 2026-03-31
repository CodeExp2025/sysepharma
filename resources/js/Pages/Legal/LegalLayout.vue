<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ title: String, subtitle: { type: String, default: '' } });
</script>

<template>
    <div class="legal-page">
        <div class="legal-container">
            <!-- Brand header -->
            <div class="legal-brand">
                <div class="brand-logo">
                    <img
                        src="/images/logo-lda.png"
                        alt="Lumière d'Afrique Group"
                        class="brand-logo-img"
                        @error="(e) => e.target.style.display = 'none'"
                    />
                    <svg class="brand-logo-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <div>
                    <div class="brand-name">Sys E-Dépôt Pharma</div>
                    <div class="brand-sub">Lumière d'Afrique Group SARL — Bénin</div>
                </div>
            </div>

            <!-- Page title -->
            <h1 class="legal-title">{{ title }}</h1>
            <p v-if="subtitle" class="legal-subtitle">{{ subtitle }}</p>

            <!-- Slot content -->
            <slot />

            <!-- Nav footer -->
            <div class="legal-nav">
                <Link href="/cgu">CGU</Link>
                <Link href="/confidentialite">Confidentialité</Link>
                <Link href="/mentions-legales">Mentions légales</Link>
                <Link href="/contact">Contact</Link>
                <Link href="/a-propos">À propos</Link>
                <Link :href="route('login')" class="nav-back">← Application</Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.legal-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f9ff 0%, #faf5ff 100%);
    padding: 3rem 1rem;
    font-family: system-ui, -apple-system, sans-serif;
}
.legal-container {
    max-width: 52rem;
    margin: 0 auto;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    padding: 2.5rem 3rem;
}
.legal-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}
.brand-logo {
    width: 2.75rem;
    height: 2.75rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
    overflow: hidden;
    position: relative;
}
.brand-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    position: absolute;
    inset: 0;
}
.brand-logo-svg { width: 1.5rem; height: 1.5rem; }
.brand-name {
    font-size: 1rem;
    font-weight: 700;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.brand-sub { font-size: 0.75rem; color: #6b7280; margin-top: 0.1rem; }
.legal-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.375rem;
    line-height: 1.3;
}
.legal-subtitle {
    font-size: 0.875rem;
    color: #9ca3af;
    margin: 0 0 2rem;
}
.legal-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 2.5rem;
}
.legal-nav a {
    color: #3b82f6;
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    padding: 0.3rem 0.75rem;
    border: 1px solid #bfdbfe;
    border-radius: 0.5rem;
    transition: all 150ms;
    background: #f0f9ff;
}
.legal-nav a:hover { background: #dbeafe; border-color: #3b82f6; }
.legal-nav .nav-back {
    margin-left: auto;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
    border-color: transparent;
}
.legal-nav .nav-back:hover { opacity: 0.9; }
@media (max-width: 640px) {
    .legal-container { padding: 1.5rem 1.25rem; }
    .legal-title { font-size: 1.375rem; }
    .legal-nav .nav-back { margin-left: 0; }
}
</style>
