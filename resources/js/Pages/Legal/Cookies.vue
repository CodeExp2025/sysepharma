<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Politique des Cookies — Sys E-Dépôt Pharma" />
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

            <h1 class="legal-title">Politique des Cookies</h1>
            <p class="legal-updated">Dernière mise à jour : mars 2025</p>

            <section>
                <h2>1. Qu'est-ce qu'un cookie ?</h2>
                <p>Un cookie est un petit fichier texte déposé sur votre appareil (ordinateur, tablette, smartphone) lors de votre connexion à la plateforme Sys E-Dépôt Pharma. Les cookies permettent notamment de mémoriser vos préférences et d'assurer le bon fonctionnement de la plateforme.</p>
            </section>

            <section>
                <h2>2. Cookies utilisés par la plateforme</h2>
                <p>Sys E-Dépôt Pharma utilise exclusivement des cookies <strong>strictement nécessaires</strong> au fonctionnement du service :</p>
                <div class="cookie-table-wrapper">
                    <table class="cookie-table">
                        <thead>
                            <tr>
                                <th>Nom du cookie</th>
                                <th>Finalité</th>
                                <th>Durée</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>pharma_session</code></td>
                                <td>Session d'authentification Laravel (maintient la connexion)</td>
                                <td>Session (fermée à la déconnexion)</td>
                            </tr>
                            <tr>
                                <td><code>XSRF-TOKEN</code></td>
                                <td>Protection contre les attaques CSRF (sécurité des formulaires)</td>
                                <td>Session</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p>Ces cookies ne collectent aucune donnée de navigation externe et ne sont pas partagés avec des tiers.</p>
            </section>

            <section>
                <h2>3. Cookies tiers</h2>
                <p>La plateforme Sys E-Dépôt Pharma <strong>n'utilise pas de cookies tiers</strong> (analyse, publicité, réseaux sociaux). Aucun outil de tracking ou d'analyse externe (Google Analytics, etc.) n'est intégré à la plateforme.</p>
            </section>

            <section>
                <h2>4. Consentement</h2>
                <p>Les cookies strictement nécessaires ne requièrent pas votre consentement préalable car ils sont indispensables au fonctionnement de la plateforme. Sans ces cookies, la connexion et l'utilisation du service seraient impossibles.</p>
            </section>

            <section>
                <h2>5. Gestion des cookies</h2>
                <p>Vous pouvez configurer votre navigateur pour refuser ou supprimer les cookies. Cependant, la désactivation des cookies nécessaires empêchera le fonctionnement de la plateforme.</p>
                <p>Voici les liens vers les instructions des principaux navigateurs :</p>
                <ul>
                    <li>Google Chrome : Paramètres > Confidentialité et sécurité > Cookies</li>
                    <li>Mozilla Firefox : Options > Vie privée et sécurité</li>
                    <li>Microsoft Edge : Paramètres > Confidentialité, recherche et services</li>
                    <li>Safari : Préférences > Confidentialité</li>
                </ul>
            </section>

            <section>
                <h2>6. Mise à jour de la politique</h2>
                <p>Cette politique des cookies peut être mise à jour pour refléter des modifications du service. Toute évolution substantielle sera communiquée aux utilisateurs. Nous vous invitons à consulter régulièrement cette page.</p>
            </section>

            <div class="legal-nav">
                <Link href="/cgu">CGU</Link>
                <Link href="/confidentialite">Données personnelles</Link>
                <Link href="/mentions-legales">Mentions légales</Link>
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
section { margin-bottom: 2rem; }
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
code {
    background: #f3f4f6;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.875em;
    color: #374151;
}
.cookie-table-wrapper {
    overflow-x: auto;
    margin: 1rem 0;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}
.cookie-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.cookie-table th {
    padding: 0.625rem 1rem;
    background: #f9fafb;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}
.cookie-table td {
    padding: 0.75rem 1rem;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}
.cookie-table tbody tr:last-child td { border-bottom: none; }
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
