<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from './LegalLayout.vue';
</script>

<template>
    <Head title="À propos — Sys E-Dépôt Pharma" />

    <LegalLayout title="À propos" subtitle="LUMIERE AFRIQUE GROUP — Ld'A Group SARL">

        <!-- Mission banner -->
        <div class="mission-banner">
            <div class="mission-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div>
                <p class="mission-title">Notre mission</p>
                <p class="mission-text">Mettre la technologie au service de la santé en Afrique — digitaliser la gestion pharmaceutique pour les officines, dépôts et groupements, avec des outils simples, fiables et accessibles.</p>
            </div>
        </div>

        <!-- Timeline -->
        <section>
            <h2>Notre histoire</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2021</div>
                        <div class="timeline-title">Création de Lumière d'Afrique Group SARL</div>
                        <p>Immatriculée le 3 septembre 2021 au Greffe du Tribunal de Commerce de Parakou (N° RCCM RB/PKO/21 B 614), la société est fondée avec un capital de 2 000 000 FCFA par M. CODJO MAWUSSE UNNEL FRANCKLIN ASSOGBA.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2023 – 2025</div>
                        <div class="timeline-title">Développement des activités</div>
                        <p>Extension des activités vers le conseil en systèmes d'information pour le secteur médical et pharmaceutique. Conception des premières solutions de gestion de stock pour pharmacies.</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot active"></div>
                    <div class="timeline-content">
                        <div class="timeline-year">2026</div>
                        <div class="timeline-title">Lancement de Sys E-Dépôt Pharma</div>
                        <p>Déploiement de la plateforme de gestion pharmaceutique intégrée — gestion de stock, transferts, ventes, statistiques et demandes de réapprovisionnement en temps réel.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Activities -->
        <section>
            <h2>Nos activités</h2>
            <div class="activities-grid">
                <div class="activity-card">
                    <div class="activity-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <div class="activity-title">Pharmacie & Médical</div>
                    <p>Achats et revente de consommables médicaux et matériaux médico-techniques. Gestion numérique de dépôts pharmaceutiques.</p>
                </div>
                <div class="activity-card">
                    <div class="activity-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
                        </svg>
                    </div>
                    <div class="activity-title">Informatique</div>
                    <p>Achat, vente et maintenance de matériels informatiques. Développement de solutions logicielles sur mesure pour les entreprises.</p>
                </div>
                <div class="activity-card">
                    <div class="activity-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="activity-title">BTP & Contrôle technique</div>
                    <p>Contrôle technique pour travaux de construction de bâtiments et de génie civil. Commerce général.</p>
                </div>
            </div>
        </section>

        <!-- Identité légale -->
        <section>
            <h2>Identité légale</h2>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Raison sociale</span>
                    <span class="info-value">LUMIERE AFRIQUE GROUP (Ld'A Group SARL)</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fondateur & Gérant</span>
                    <span class="info-value">M. CODJO MAWUSSE UNNEL FRANCKLIN ASSOGBA</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date de création</span>
                    <span class="info-value">3 septembre 2021 — Parakou, Bénin</span>
                </div>
                <div class="info-row">
                    <span class="info-label">RCCM</span>
                    <span class="info-value">RB/PKO/21 B 614</span>
                </div>
                <div class="info-row">
                    <span class="info-label">IFU</span>
                    <span class="info-value">3202113259412</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Capital social</span>
                    <span class="info-value">2 000 000 FCFA</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Siège social</span>
                    <span class="info-value">Quartier Tranza, Parakou 3, Borgou — Bénin</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Durée de vie</span>
                    <span class="info-value">99 ans (03/09/2021 – 03/09/2120)</span>
                </div>
            </div>
        </section>

    </LegalLayout>
</template>

<style scoped>
h2 {
    font-size: 1.0625rem;
    font-weight: 700;
    color: #1e40af;
    margin: 0 0 1.25rem;
    padding-bottom: 0.375rem;
    border-bottom: 2px solid #dbeafe;
}
p { color: #4b5563; line-height: 1.75; margin-bottom: 0; font-size: 0.9375rem; }
section { margin-bottom: 2.5rem; }

/* Mission */
.mission-banner {
    display: flex;
    gap: 1.25rem;
    align-items: flex-start;
    padding: 1.5rem;
    background: linear-gradient(135deg, #eff6ff, #f5f3ff);
    border: 1px solid #c7d2fe;
    border-radius: 0.875rem;
    margin-bottom: 2.5rem;
}
.mission-icon {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}
.mission-icon svg { width: 1.5rem; height: 1.5rem; }
.mission-title { font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem; }
.mission-text { font-size: 0.9375rem; color: #1e3a8a; font-weight: 500; line-height: 1.65; }

/* Timeline */
.timeline { position: relative; padding-left: 2rem; }
.timeline::before {
    content: '';
    position: absolute;
    left: 0.5625rem;
    top: 0.5rem;
    bottom: 0.5rem;
    width: 2px;
    background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
    opacity: 0.3;
}
.timeline-item { position: relative; margin-bottom: 1.75rem; }
.timeline-item:last-child { margin-bottom: 0; }
.timeline-dot {
    position: absolute;
    left: -1.9375rem;
    top: 0.3125rem;
    width: 0.875rem;
    height: 0.875rem;
    border-radius: 50%;
    background: white;
    border: 2px solid #93c5fd;
}
.timeline-dot.active {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    border-color: transparent;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
}
.timeline-year {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #3b82f6;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.25rem;
}
.timeline-title { font-size: 1rem; font-weight: 700; color: #1f2937; margin-bottom: 0.375rem; }
.timeline-content p { font-size: 0.875rem; color: #6b7280; }

/* Activities */
.activities-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
.activity-card {
    padding: 1.25rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    background: #f9fafb;
}
.activity-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, rgba(59,130,246,0.12), rgba(139,92,246,0.12));
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3b82f6;
    margin-bottom: 0.875rem;
}
.activity-icon svg { width: 1.25rem; height: 1.25rem; }
.activity-title { font-size: 0.9375rem; font-weight: 700; color: #1f2937; margin-bottom: 0.5rem; }
.activity-card p { font-size: 0.8125rem; color: #6b7280; line-height: 1.6; }

/* Info grid */
.info-grid { border: 1px solid #e5e7eb; border-radius: 0.625rem; overflow: hidden; }
.info-row { display: flex; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; }
.info-row:last-child { border-bottom: none; }
.info-label {
    min-width: 11rem;
    padding: 0.7rem 1rem;
    background: #f8faff;
    font-weight: 600;
    color: #6b7280;
    border-right: 1px solid #e5e7eb;
    font-size: 0.8125rem;
}
.info-value { padding: 0.7rem 1rem; color: #1f2937; flex: 1; }

@media (max-width: 640px) {
    .activities-grid { grid-template-columns: 1fr; }
    .info-row { flex-direction: column; }
    .info-label { border-right: none; border-bottom: 1px solid #e5e7eb; min-width: 0; }
    .mission-banner { flex-direction: column; }
}
</style>
