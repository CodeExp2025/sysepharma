<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Politique de Confidentialité — Sys E-Dépôt Pharma" />
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

            <h1 class="legal-title">Politique de Confidentialité et Protection des Données</h1>
            <p class="legal-updated">Dernière mise à jour : mars 2025</p>

            <section>
                <h2>1. Responsable du traitement</h2>
                <p><strong>Lumière Afrique Group Sarl</strong> est responsable du traitement des données personnelles collectées via la plateforme Sys E-Dépôt Pharma. Pour toute question relative à vos données, vous pouvez nous contacter à l'adresse indiquée dans les mentions légales.</p>
            </section>

            <section>
                <h2>2. Données collectées</h2>
                <p>Dans le cadre de l'utilisation de la plateforme, nous collectons les données suivantes :</p>
                <ul>
                    <li><strong>Données d'identification :</strong> nom, prénom, adresse e-mail ;</li>
                    <li><strong>Données d'activité :</strong> historique des ventes, transferts réalisés, connexions ;</li>
                    <li><strong>Données de gestion :</strong> informations sur les pharmacies et dépôts rattachés.</li>
                </ul>
                <p>Nous ne collectons aucune donnée de santé personnelle relative aux patients.</p>
            </section>

            <section>
                <h2>3. Finalités du traitement</h2>
                <p>Les données sont collectées pour les finalités suivantes :</p>
                <ul>
                    <li>Gestion des comptes utilisateurs et authentification ;</li>
                    <li>Suivi des opérations pharmaceutiques (stock, ventes, transferts) ;</li>
                    <li>Génération de statistiques et rapports d'activité ;</li>
                    <li>Envoi de notifications liées à l'activité (alertes de stock, etc.) ;</li>
                    <li>Sécurité et prévention des fraudes.</li>
                </ul>
            </section>

            <section>
                <h2>4. Base légale</h2>
                <p>Le traitement de vos données repose sur :</p>
                <ul>
                    <li>L'exécution du contrat d'utilisation de la plateforme ;</li>
                    <li>L'intérêt légitime de Lumière Afrique Group Sarl pour la sécurité et la gestion de ses services ;</li>
                    <li>Le consentement explicite de l'utilisateur pour les communications optionnelles.</li>
                </ul>
            </section>

            <section>
                <h2>5. Conservation des données</h2>
                <p>Les données sont conservées pendant toute la durée de l'abonnement actif et pour une durée de <strong>3 ans</strong> après la fin du contrat, conformément aux obligations légales et comptables applicables. Les données d'activité peuvent être conservées plus longtemps si la réglementation l'exige.</p>
            </section>

            <section>
                <h2>6. Partage des données</h2>
                <p>Vos données ne sont ni vendues ni cédées à des tiers. Elles peuvent être partagées avec :</p>
                <ul>
                    <li>Nos prestataires techniques hébergeant la plateforme (sous-traitants) ;</li>
                    <li>Les autorités compétentes sur réquisition judiciaire.</li>
                </ul>
            </section>

            <section>
                <h2>7. Vos droits</h2>
                <p>Conformément à la législation applicable sur la protection des données, vous disposez des droits suivants :</p>
                <ul>
                    <li><strong>Droit d'accès :</strong> obtenir une copie de vos données personnelles ;</li>
                    <li><strong>Droit de rectification :</strong> corriger des données inexactes ;</li>
                    <li><strong>Droit à l'effacement :</strong> demander la suppression de vos données ;</li>
                    <li><strong>Droit d'opposition :</strong> vous opposer au traitement pour des motifs légitimes ;</li>
                    <li><strong>Droit à la portabilité :</strong> récupérer vos données dans un format structuré.</li>
                </ul>
                <p>Pour exercer ces droits, contactez-nous via les coordonnées figurant dans les mentions légales.</p>
            </section>

            <section>
                <h2>8. Sécurité</h2>
                <p>Lumière Afrique Group Sarl met en œuvre des mesures techniques et organisationnelles appropriées pour protéger vos données contre tout accès non autorisé, perte, altération ou divulgation, notamment : chiffrement des mots de passe, contrôle des accès par rôles, journalisation des opérations sensibles.</p>
            </section>

            <div class="legal-nav">
                <Link href="/cgu">CGU</Link>
                <Link href="/mentions-legales">Mentions légales</Link>
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
