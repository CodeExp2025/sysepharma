<script setup>
import { Head } from '@inertiajs/vue3';
import LegalLayout from './LegalLayout.vue';
import { ref, reactive } from 'vue';

const form = reactive({ name: '', email: '', subject: '', message: '' });
const sending = ref(false);
const sent = ref(false);

const sendMessage = () => {
    sending.value = true;
    const subject = encodeURIComponent(form.subject || 'Contact Sys E-Dépôt Pharma');
    const body = encodeURIComponent(`De : ${form.name} <${form.email}>\n\n${form.message}`);
    window.location.href = `mailto:ufras@yahoo.fr?subject=${subject}&body=${body}`;
    setTimeout(() => {
        sending.value = false;
        sent.value = true;
        Object.assign(form, { name: '', email: '', subject: '', message: '' });
    }, 600);
};
</script>

<template>
    <Head title="Contact — Sys E-Dépôt Pharma" />

    <LegalLayout title="Nous contacter" subtitle="Une question, un signalement, un besoin d'assistance ?">

        <!-- Contact cards -->
        <div class="contact-grid">
            <div class="contact-card">
                <div class="contact-card-icon phone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div class="contact-card-body">
                    <div class="contact-card-label">Téléphone</div>
                    <a href="tel:+22997115591" class="contact-card-value">+229 97 11 55 91</a>
                    <div class="contact-card-hint">Lun – Ven, 8h – 17h (WAT)</div>
                </div>
            </div>

            <div class="contact-card">
                <div class="contact-card-icon email">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="contact-card-body">
                    <div class="contact-card-label">Email</div>
                    <a href="mailto:ufras@yahoo.fr" class="contact-card-value">ufras@yahoo.fr</a>
                    <div class="contact-card-hint">Réponse sous 48h ouvrées</div>
                </div>
            </div>

            <div class="contact-card">
                <div class="contact-card-icon address">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="contact-card-body">
                    <div class="contact-card-label">Adresse</div>
                    <div class="contact-card-value small">Quartier Tranza, Parakou 3<br>Borgou — République du Bénin</div>
                    <div class="contact-card-hint">Siège social · Maison RAOUL BIO KANSI</div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <section class="contact-form-section">
            <h2>Envoyer un message</h2>
            <form class="contact-form" @submit.prevent="sendMessage">
                <div class="form-row">
                    <div class="form-group">
                        <label for="cf-name">Nom complet</label>
                        <input id="cf-name" type="text" v-model="form.name" required placeholder="Votre nom" />
                    </div>
                    <div class="form-group">
                        <label for="cf-email">Adresse e-mail</label>
                        <input id="cf-email" type="email" v-model="form.email" required placeholder="vous@exemple.com" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="cf-subject">Sujet</label>
                    <select id="cf-subject" v-model="form.subject">
                        <option value="">Choisir un sujet…</option>
                        <option value="support">Assistance technique</option>
                        <option value="bug">Signalement d'anomalie</option>
                        <option value="commercial">Renseignement commercial</option>
                        <option value="rgpd">Données personnelles</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cf-message">Message</label>
                    <textarea id="cf-message" v-model="form.message" required rows="5" placeholder="Décrivez votre demande…"></textarea>
                </div>
                <div v-if="sent" class="form-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Message envoyé ! Nous vous répondrons sous 48h ouvrées.
                </div>
                <button type="submit" class="form-btn" :disabled="sending">
                    <svg v-if="sending" class="spinner" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ sending ? 'Envoi…' : 'Envoyer le message' }}
                </button>
            </form>
        </section>

        <!-- Info footer -->
        <div class="contact-info-footer">
            <div class="contact-info-item">
                <span class="info-label">RCCM</span>
                <span>RB/PKO/21 B 614 — Parakou, Bénin</span>
            </div>
            <div class="contact-info-item">
                <span class="info-label">IFU</span>
                <span>3202113259412</span>
            </div>
        </div>

    </LegalLayout>
</template>


<style scoped>
/* Contact cards */
.contact-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2.5rem; }
.contact-card {
    display: flex;
    gap: 1rem;
    padding: 1.25rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    background: #f9fafb;
    transition: box-shadow 150ms;
}
.contact-card:hover { box-shadow: 0 4px 12px rgba(59,130,246,0.1); border-color: #bfdbfe; }
.contact-card-icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.contact-card-icon svg { width: 1.25rem; height: 1.25rem; }
.contact-card-icon.phone { background: rgba(16,185,129,0.1); color: #059669; }
.contact-card-icon.email { background: rgba(59,130,246,0.1); color: #2563eb; }
.contact-card-icon.address { background: rgba(139,92,246,0.1); color: #7c3aed; }
.contact-card-label { font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.25rem; }
.contact-card-value { font-size: 0.9375rem; font-weight: 600; color: #1f2937; text-decoration: none; display: block; }
.contact-card-value.small { font-size: 0.875rem; line-height: 1.5; }
a.contact-card-value:hover { color: #3b82f6; }
.contact-card-hint { font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem; }

/* Form */
.contact-form-section { margin-bottom: 2rem; }
h2 {
    font-size: 1.0625rem;
    font-weight: 700;
    color: #1e40af;
    margin: 0 0 1.25rem;
    padding-bottom: 0.375rem;
    border-bottom: 2px solid #dbeafe;
}
.contact-form { display: flex; flex-direction: column; gap: 1rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.375rem; }
.form-group label { font-size: 0.875rem; font-weight: 600; color: #374151; }
.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.65rem 0.875rem;
    border: 1.5px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.9375rem;
    color: #111827;
    outline: none;
    transition: border-color 150ms, box-shadow 150ms;
    font-family: inherit;
    background: white;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.form-group textarea { resize: vertical; min-height: 6rem; }
.form-success {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.875rem 1rem;
    background: rgba(34,197,94,0.1);
    border: 1px solid rgba(34,197,94,0.3);
    border-radius: 0.625rem;
    color: #15803d;
    font-size: 0.875rem;
    font-weight: 500;
}
.form-success svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
.form-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    font-size: 0.9375rem;
    font-weight: 600;
    border: none;
    border-radius: 0.75rem;
    cursor: pointer;
    box-shadow: 0 4px 6px -1px rgba(59,130,246,0.3);
    transition: all 150ms;
    align-self: flex-start;
}
.form-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 15px -3px rgba(59,130,246,0.4); }
.form-btn:disabled { opacity: 0.7; cursor: not-allowed; }
.form-btn svg { width: 1.125rem; height: 1.125rem; }
.spinner { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Info footer */
.contact-info-footer {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    padding: 1rem 1.25rem;
    background: #f8faff;
    border: 1px solid #e0e7ff;
    border-radius: 0.625rem;
    margin-top: 1.5rem;
}
.contact-info-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: #4b5563; }
.info-label { font-weight: 700; color: #6b7280; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; }

@media (max-width: 640px) {
    .contact-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .form-btn { width: 100%; }
}
</style>
