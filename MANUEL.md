# Manuel d'utilisation — Sys E-Dépôt Pharma

**Système de gestion de stock pharmaceutique**
LUMIERE AFRIQUE GROUP (Ld'A Group SARL) — Parakou, Bénin

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Connexion et profil](#2-connexion-et-profil)
3. [Super Administrateur](#3-super-administrateur)
4. [Administrateur de Pharmacie](#4-administrateur-de-pharmacie)
5. [Personnel de Pharmacie](#5-personnel-de-pharmacie)
6. [Personnel de Dépôt](#6-personnel-de-dépôt)
7. [Fonctionnalités communes](#7-fonctionnalités-communes)
8. [Impression](#8-impression)

---

## 1. Vue d'ensemble

Sys E-Dépôt Pharma est une application web de gestion de stock pharmaceutique multi-dépôts. Elle permet de :

- Gérer un catalogue de médicaments par pharmacie
- Suivre les unités physiques de stock (entrées, sorties, transferts)
- Enregistrer les ventes et générer des reçus
- Produire des rapports de stock journaliers et des statistiques
- Gérer plusieurs dépôts rattachés à une pharmacie

### Rôles disponibles

| Rôle | Niveau d'accès |
|------|---------------|
| `super_admin` | Accès total à toutes les pharmacies et tous les dépôts |
| `pharmacy_admin` | Gestion complète de sa pharmacie et ses dépôts |
| `pharmacy_staff` | Stock, transferts, ventes — lecture/création |
| `depot_staff` | Stock et ventes de son dépôt uniquement |

---

## 2. Connexion et profil

### Se connecter

1. Accéder à l'URL de l'application
2. Saisir son **email** et son **mot de passe**
3. Cliquer sur **Se connecter**

> Compte par défaut du super admin : `mikbossou@gmail.com` / `Admin@1234`
> **Changer le mot de passe dès la première connexion.**

### Verrouillage de session

Pour des raisons de sécurité, la session se verrouille automatiquement après **15 minutes d'inactivité**.

- Écran de verrouillage s'affiche
- Saisir son mot de passe pour déverrouiller
- **5 tentatives maximum** avant déconnexion forcée
- Le verrouillage persiste même en cas d'actualisation de la page

### Modifier son profil

Accessible via le menu en haut à droite → **Mon Profil**

- Modifier son nom et son email
- Changer son mot de passe
- Changer de pharmacie active (si rattaché à plusieurs)
- **Super Admin uniquement** : Exporter la base de données SQL (ouvre dans un nouvel onglet)

---

### Navigation

Le menu principal est organisé de manière compacte avec icônes uniquement par défaut :

| Icône | Menu | Description |
|-------|------|-------------|
| 🏠 | Tableau de bord | Vue d'ensemble et statistiques |
| 💊 | Médicaments | Catalogue des produits |
| 📦 | Stock | Gestion des unités physiques |
| ↔️ | Transferts | Mouvements entre emplacements |
| 🛒 | Ventes | Historique et enregistrement |
| 📊 | Statistiques | Rapports analytiques |
| 💼 | **Gestion** | Menu déroulant : Décaissements, Catégories, Dépôts |
| ⚙️ | Administration | Menu déroulant : Utilisateurs, Rôles, Permissions |

> **Astuce** : Survolez une icône pour afficher son libellé. Les menus déroulants s'ouvrent au clic et se ferment automatiquement lorsqu'un autre s'ouvre.

---

## 3. Super Administrateur

Le super administrateur a accès à l'intégralité du système, toutes pharmacies confondues.

### Tableau de bord

- **Statistiques dynamiques** : Données réelles des ventes sur 7 jours et répartition par catégorie
- **Stock par emplacement** : Comparaison visuelle entre Pharmacie et Dépôts
- Vue consolidée : stock total, ventes du jour, alertes de stock bas
- Accès à toutes les pharmacies sans restriction

### Gestion des pharmacies

**Menu : Paramètres → Ma Pharmacie**

| Action | Description |
|--------|-------------|
| Créer une pharmacie | Créer une nouvelle entité pharmacie |
| Modifier les infos | Nom, adresse, contact de la pharmacie |
| Rejoindre une pharmacie | Associer son compte à une pharmacie existante |

### Gestion des dépôts

**Menu : Dépôts**

| Action | Description |
|--------|-------------|
| Voir tous les dépôts | Liste de tous les dépôts de toutes les pharmacies |
| Créer un dépôt | Nom, adresse, seuil d'alerte stock bas |
| Modifier un dépôt | Mettre à jour les informations |
| Activer/désactiver les reçus | Autoriser ou non l'impression de reçus pour un dépôt |
| Activer/désactiver les stats | Afficher ou masquer les statistiques pour un dépôt |
| Supprimer un dépôt | Suppression définitive |

### Gestion des utilisateurs

**Menu : Utilisateurs**

| Action | Description |
|--------|-------------|
| Voir la liste | Tous les utilisateurs du système |
| Créer un utilisateur | Nom, email, mot de passe, rôle, pharmacie, dépôt |
| Modifier un utilisateur | Changer le rôle, la pharmacie ou le dépôt affecté |
| Supprimer un utilisateur | Retirer l'accès définitivement |

> Lors de la création : si le rôle est `depot_staff`, choisir d'abord une pharmacie — la liste des dépôts se filtre automatiquement.

### Gestion des rôles et permissions

**Menu : Rôles / Permissions**

| Action | Description |
|--------|-------------|
| Voir les rôles | Liste de tous les rôles définis |
| Créer un rôle | Nouveau rôle avec permissions personnalisées |
| Modifier les permissions d'un rôle | Ajouter ou retirer des permissions |
| Supprimer un rôle | Attention : les utilisateurs perdent leur accès |
| Gérer les permissions | Créer / supprimer des permissions individuelles |

### Catalogue de médicaments

**Menu : Médicaments**

| Action | Description |
|--------|-------------|
| Voir tous les médicaments | Catalogue complet |
| Créer un médicament | Nom, DCI, forme, dosage, catégorie, prix |
| Modifier | Mise à jour de toutes les informations |
| Supprimer | Suppression du catalogue |

### Stock (Unités)

**Menu : Stock**

| Action | Description |
|--------|-------------|
| Voir tout le stock | Toutes pharmacies et dépôts |
| Entrée de stock | Ajouter des unités physiques (lot, quantité, expiration, prix) |
| Rapport journalier | Imprimer stock disponible, vendus du jour, restants |

### Transferts

**Menu : Transferts**

| Action | Description |
|--------|-------------|
| Voir tous les transferts | Historique complet |
| Créer un transfert | Déplacer des médicaments entre pharmacie et dépôts |
| Rechercher par médicament | Trouver un médicament dans le stock avant transfert |
| Imprimer le bon | Bon de transfert imprimable |

### Ventes

**Menu : Ventes**

| Action | Description |
|--------|-------------|
| Voir l'historique | Toutes les ventes enregistrées |
| Enregistrer une vente | Saisie panier + calcul total |
| Scanner un médicament | Recherche par code-barres ou nom |
| Imprimer un reçu | Ticket de caisse détaillé |

### Gestion (Menu déroulant)

**Menu : 💼 Gestion**

Ce menu regroupe les fonctions administratives courantes :

| Sous-menu | Description |
|-----------|-------------|
| **Décaissements** | Suivi des sorties de caisse et dépenses |
| **Catégories** | Gestion des catégories de médicaments |
| **Dépôts** | Administration des dépôts secondaires |

### Statistiques

**Menu : Statistiques**

| Action | Description |
|--------|-------------|
| Tableau de bord analytique | Ventes, stock, revenus par période |
| Filtrer par période | Sélectionner une plage de dates |
| Imprimer le rapport | Export des statistiques à la date choisie |

### Demandes de stock

**Menu : Demandes de stock**

| Action | Description |
|--------|-------------|
| Voir toutes les demandes | Demandes émises par les dépôts |
| Valider / refuser | Approuver ou rejeter une demande |
| Rapport journalier | Imprimer l'état des demandes du jour |

---

## 4. Administrateur de Pharmacie

Accès limité à **sa propre pharmacie** et ses dépôts.

### Ce qu'il peut faire

| Module | Actions disponibles |
|--------|-------------------|
| **Médicaments** | Voir, créer, modifier, supprimer |
| **💼 Gestion** | Accès complet aux sous-menus : Décaissements, Catégories, Dépôts |
| **Stock** | Voir, entrée de stock, rapport journalier |
| **Transferts** | Créer, voir, imprimer bon |
| **Ventes** | Voir historique, enregistrer, imprimer reçu |
| **Utilisateurs** | Créer, modifier, supprimer (de sa pharmacie) |
| **Statistiques** | Voir et imprimer |
| **Demandes de stock** | Voir, valider, refuser |
| **Ma Pharmacie** | Modifier les informations de sa pharmacie |

### Ce qu'il ne peut pas faire

- Accéder aux autres pharmacies
- Gérer les rôles et permissions système
- Voir les données globales toutes pharmacies confondues

---

## 5. Personnel de Pharmacie

Employé travaillant au niveau de la pharmacie principale (pas d'un dépôt spécifique).

### Ce qu'il peut faire

| Module | Actions disponibles |
|--------|-------------------|
| **Médicaments** | Voir, créer *(pas de suppression ni modification)* |
| **💼 Gestion** | Accès limité : voir Catégories, voir Dépôts *(pas de création/modification)* |
| **Stock** | Voir, entrée de stock, rapport journalier |
| **Transferts** | Voir, créer, imprimer bon |
| **Ventes** | Enregistrer une vente, voir l'historique, imprimer reçu |
| **Statistiques** | Voir et imprimer |
| **Demandes de stock** | Voir, créer une demande |

### Ce qu'il ne peut pas faire

- Supprimer ou modifier des médicaments
- Gérer les utilisateurs
- Gérer les dépôts
- Modifier les paramètres de la pharmacie
- Accéder aux rôles et permissions

---

## 6. Personnel de Dépôt

Employé affecté à **un dépôt spécifique**. Accès le plus restreint.

### Ce qu'il peut faire

| Module | Actions disponibles |
|--------|-------------------|
| **Stock** | Voir le stock de **son dépôt uniquement**, rapport journalier |
| **Ventes** | Enregistrer une vente, voir l'historique, imprimer reçu |
| **Demandes de stock** | Créer une demande de réapprovisionnement |
| **Notifications** | Envoyer une alerte de stock bas |

### Restrictions spécifiques

- **"Ma Pharmacie"** n'est pas accessible dans le menu Paramètres
- Voit uniquement les médicaments et unités de son dépôt
- Ne peut pas transférer du stock
- Ne peut pas créer ou modifier des médicaments
- Ne peut pas accéder aux statistiques générales
- Ne peut pas gérer d'autres utilisateurs

### Workflow typique d'un agent de dépôt

```
1. Se connecter → tableau de bord du dépôt
2. Consulter le stock disponible (Menu : Stock)
3. Enregistrer une vente (Menu : Ventes → Nouvelle vente)
4. Si stock bas → Créer une demande de stock (Menu : Demandes)
5. Imprimer rapport journalier en fin de journée (Stock → Imprimer)
```

---

## 7. Fonctionnalités communes

Toutes les sessions donnent accès aux fonctionnalités suivantes.

### Notifications

- Cloche en haut à droite → liste des notifications non lues
- Alertes automatiques : stock bas, demande validée/refusée
- Bouton **Tout marquer comme lu**

### Tableau de bord

Chaque rôle voit un tableau de bord adapté :
- Métriques clés (médicaments en stock, ventes du jour, alertes)
- Accès rapide aux actions courantes

### Pages légales

Accessibles depuis le pied de page (même sans être connecté) :

| Page | Contenu |
|------|---------|
| CGU | Conditions générales d'utilisation |
| Confidentialité | Politique de traitement des données |
| Mentions légales | Informations légales de l'entreprise |
| Contact | Formulaire et coordonnées |
| À propos | Histoire et activités de Ld'A Group |

---

## 8. Impression

### Reçu de vente (Sales)

1. Aller dans **Ventes**
2. Cliquer sur l'icône imprimante à côté d'une transaction
3. Le reçu s'affiche et l'impression démarre automatiquement

**Contenu du reçu :** nom de la pharmacie/dépôt, référence, date, vendeur, liste des articles avec quantité et prix unitaire, total en FCFA.

### Rapport journalier de stock (Stock / Demandes de stock)

1. Aller dans **Stock** ou **Demandes de stock**
2. Cliquer sur **Imprimer rapport du jour**
3. Le rapport du jour s'imprime seul (le reste de l'interface est masqué)

**Contenu :** date et heure, liste des médicaments disponibles avec quantités, total des unités disponibles.

### Bon de transfert (Transferts)

1. Aller dans **Transferts**
2. Cliquer sur un transfert → **Imprimer**
3. Le bon de transfert s'ouvre dans une page dédiée et s'imprime automatiquement

**Contenu :** référence, date, pharmacie source/destination, liste des médicaments transférés, prix en FCFA.

### Rapport de statistiques (Statistiques)

1. Aller dans **Statistiques**
2. Appliquer les filtres de période souhaités
3. Cliquer sur **Imprimer**

**Contenu :** période sélectionnée, tableaux de ventes, stock et revenus.

> **Note technique :** chaque module d'impression est isolé — seul le document concerné s'imprime, le reste de l'application (menus, pied de page) est masqué automatiquement.

---

## Résumé des accès par rôle

| Fonctionnalité | super_admin | pharmacy_admin | pharmacy_staff | depot_staff |
|---------------|:-----------:|:--------------:|:--------------:|:-----------:|
| Voir médicaments | ✅ | ✅ | ✅ | ❌ |
| Créer médicament | ✅ | ✅ | ✅ | ❌ |
| Modifier médicament | ✅ | ✅ | ❌ | ❌ |
| Supprimer médicament | ✅ | ✅ | ❌ | ❌ |
| 💼 **Menu Gestion** | ✅ | ✅ | ✅ (lecture) | ❌ |
| — Décaissements | ✅ | ✅ | ❌ | ❌ |
| — Catégories (créer) | ✅ | ✅ | ❌ | ❌ |
| — Catégories (voir) | ✅ | ✅ | ✅ | ❌ |
| — Dépôts (gérer) | ✅ | ✅ | ❌ | ❌ |
| — Dépôts (voir) | ✅ | ✅ | ✅ | ❌ |
| Voir stock | ✅ | ✅ | ✅ | ✅ (son dépôt) |
| Entrée de stock | ✅ | ✅ | ✅ | ❌ |
| Transfert de stock | ✅ | ✅ | ✅ | ❌ |
| Vendre | ✅ | ✅ | ✅ | ✅ |
| Voir statistiques | ✅ | ✅ | ✅ | ❌ |
| Demandes de stock | ✅ | ✅ | ✅ | ✅ |
| Gérer utilisateurs | ✅ | ✅ | ❌ | ❌ |
| Gérer rôles | ✅ | ❌ | ❌ | ❌ |
| Paramètres pharmacie | ✅ | ✅ | ❌ | ❌ |
| Menu "Ma Pharmacie" | ✅ | ✅ | ✅ | ❌ |

---

*Sys E-Dépôt Pharma — Version 1.0 — LUMIERE AFRIQUE GROUP (Ld'A Group SARL)*
*RCCM RB/PKO/21 B 614 — IFU 3202113259412 — Quartier Tranza, Parakou, Bénin*
