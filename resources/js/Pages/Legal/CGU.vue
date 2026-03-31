<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Conditions Générales d'Utilisation — Sys E-Dépôt Pharma" />
    <div class="legal-page">
        <div class="legal-container">
            <div class="legal-brand">
                <div class="brand-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <div>
                    <div class="brand-name">Sys E-Dépôt Pharma</div>
                    <div class="brand-sub">Lumière Afrique Group Sarl</div>
                </div>
            </div>

            <h1 class="legal-title">Conditions Générales d'Utilisation (CGU)</h1>
            <p class="legal-updated">Dernière mise à jour : mars 2025</p>

            <section>
                <h2>1. Objet</h2>
                <p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'accès et l'utilisation de la plateforme <strong>Sys E-Dépôt Pharma</strong>, logiciel de gestion pharmaceutique édité par <strong>Lumière Afrique Group Sarl</strong>. En accédant à la plateforme, l'utilisateur accepte sans réserve les présentes CGU.</p>
            </section>

            <section>
                <h2>2. Accès à la plateforme</h2>
                <p>L'accès à Sys E-Dépôt Pharma est réservé aux professionnels de santé et au personnel autorisé des pharmacies et dépôts enregistrés. Chaque utilisateur dispose d'identifiants personnels et confidentiels. Toute communication de ces identifiants à un tiers est strictement interdite.</p>
            </section>

            <section>
                <h2>3. Utilisation autorisée</h2>
                <p>La plateforme est mise à disposition dans le seul but de gérer les stocks pharmaceutiques, les transferts, les ventes et les statistiques associées. Toute utilisation à des fins frauduleuses, illicites ou contraires aux règles en vigueur est prohibée.</p>
            </section>

            <section>
                <h2>4. Responsabilités de l'utilisateur</h2>
                <p>L'utilisateur est responsable :</p>
                <ul>
                    <li>De la confidentialité de ses identifiants de connexion ;</li>
                    <li>De l'exactitude des informations saisies dans la plateforme ;</li>
                    <li>Du respect des règles professionnelles et réglementaires applicables à son activité ;</li>
                    <li>De tout dommage résultant d'une utilisation non conforme aux présentes CGU.</li>
                </ul>
            </section>

            <section>
                <h2>5. Propriété intellectuelle</h2>
                <p>Tous les éléments constitutifs de Sys E-Dépôt Pharma (logiciel, interfaces, base de données, marques, logos) sont la propriété exclusive de <strong>Lumière Afrique Group Sarl</strong> et sont protégés par les lois en vigueur sur la propriété intellectuelle. Toute reproduction, modification ou distribution sans autorisation préalable est interdite.</p>
            </section>

            <section>
                <h2>6. Disponibilité du service</h2>
                <p>Lumière Afrique Group Sarl s'efforce d'assurer la disponibilité de la plateforme 24h/24 et 7j/7, sans toutefois pouvoir garantir une continuité de service absolue. Des interruptions de maintenance pourront survenir et seront notifiées dans la mesure du possible.</p>
            </section>

            <section>
                <h2>7. Modification des CGU</h2>
                <p>Lumière Afrique Group Sarl se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront informés des modifications substantielles. La poursuite de l'utilisation de la plateforme après modification vaut acceptation des nouvelles CGU.</p>
            </section>

            <section>
                <h2>8. Droit applicable</h2>
                <p>Les présentes CGU sont soumises au droit en vigueur dans le pays du siège social de Lumière Afrique Group Sarl. Tout litige relatif à leur interprétation ou à leur exécution sera soumis aux juridictions compétentes.</p>
            </section>

            <div class="legal-nav">
                <Link href="/mentions-legales">Mentions légales</Link>
                <Link href="/confidentialite">Données personnelles</Link>
                <Link href="/cookies">Politique cookies</Link>
                <Link :href="route('login')">Retour à l'application</Link>
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
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}
.brand-logo svg { width: 1.5rem; height: 1.5rem; }
.brand-name {
    font-size: 1rem;
    font-weight: 700;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.brand-sub { font-size: 0.75rem; color: #6b7280; }
.legal-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}
.legal-updated {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-bottom: 2rem;
}
section {
    margin-bottom: 2rem;
}
h2 {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.75rem;
    padding-bottom: 0.375rem;
    border-bottom: 2px solid #f3f4f6;
}
p {
    color: #4b5563;
    line-height: 1.75;
    margin-bottom: 0.75rem;
}
ul {
    list-style: disc;
    padding-left: 1.5rem;
    color: #4b5563;
    line-height: 1.75;
}
ul li { margin-bottom: 0.375rem; }
.legal-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 2rem;
}
.legal-nav a {
    color: #3b82f6;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    padding: 0.375rem 0.875rem;
    border: 1px solid #bfdbfe;
    border-radius: 0.5rem;
    transition: all 150ms;
}
.legal-nav a:hover {
    background: #eff6ff;
    border-color: #3b82f6;
}
@media (max-width: 640px) {
    .legal-container { padding: 1.5rem 1.25rem; }
    .legal-title { font-size: 1.375rem; }
}
</style>
