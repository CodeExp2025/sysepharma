# Guide d'installation — Sys E-Dépôt Pharma

**Stack :** Laravel 12 · Vue 3 · Inertia.js · Tailwind CSS · Spatie RBAC

---

## Prérequis

| Outil | Version minimale |
|-------|-----------------|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| Base de données | MySQL 8 / PostgreSQL 14 / SQLite 3 |

---

## Installation rapide (recommandée)

```bash
# 1. Cloner le dépôt
git clone <url-du-depot> pharma-stock
cd pharma-stock

# 2. Copier et configurer l'environnement
cp .env.example .env
# → Éditer .env (voir section Configuration ci-dessous)

# 3. Tout installer en une commande
composer setup
# Équivaut à : composer install + key:generate + migrate + npm install + npm run build

# 4. Créer les rôles, permissions et le compte super admin
php artisan db:seed
```

---

## Installation manuelle (étape par étape)

### 1. Dépendances PHP

```bash
composer install
```

### 2. Fichier d'environnement

```bash
cp .env.example .env
```

Éditer `.env` — variables essentielles :

```env
APP_NAME="Sys E-Dépôt Pharma"
APP_ENV=local
APP_KEY=                        # généré à l'étape suivante
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de données (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pharma_stock
DB_USERNAME=root
DB_PASSWORD=

# Base de données (SQLite — développement uniquement)
# DB_CONNECTION=sqlite
# DB_DATABASE=/chemin/absolu/vers/database.sqlite

# Sessions et cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### 3. Clé d'application

```bash
php artisan key:generate
```

### 4. Base de données

#### MySQL — créer la base en premier :
```sql
CREATE DATABASE pharma_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

#### SQLite — créer le fichier :
```bash
touch database/database.sqlite
```

#### Lancer les migrations :
```bash
php artisan migrate
```

> Une seule migration crée tout le schéma :
> `2026_00_00_000000_create_full_database_schema.php`
>
> **Tables créées :** pharmacies, users, depots, categories, drugs, drug_units,
> transfers, transfer_items, stock_movements, sales, stock_requests,
> stock_request_items, notifications, audit_logs + tables Spatie RBAC + Laravel système

### 5. Seeders

```bash
php artisan db:seed
```

**Ce qui est créé :**

#### Rôles et permissions (`RolesAndPermissionsSeeder`)

| Rôle | Accès |
|------|-------|
| `super_admin` | Tout |
| `pharmacy_admin` | Médicaments, catégories, dépôts, stock, utilisateurs, rapports |
| `pharmacy_staff` | Voir/créer médicaments, stock, transferts, ventes, rapports |
| `depot_staff` | Voir stock, vendre uniquement |

#### Compte super administrateur (`AdminUserSeeder`)

| Champ | Valeur |
|-------|--------|
| Email | `mikbossou@gmail.com` |
| Mot de passe | `Admin@1234` *(bcrypt pré-hashé dans le seeder)* |
| Rôle | `super_admin` |

> **Important :** Changer le mot de passe immédiatement après la première connexion via **Mon Profil**.

### 6. Dépendances Node.js

```bash
npm install
```

### 7. Compilation des assets

**Développement (avec hot-reload) :**
```bash
npm run dev
```

**Production :**
```bash
npm run build
```

---

## Démarrage

### Développement (serveur intégré)

```bash
# Lance en parallèle : serveur PHP + queue + logs + Vite
composer dev
```

Accéder à : **http://localhost:8000**

### Ou séparément

```bash
# Terminal 1 — Serveur Laravel
php artisan serve

# Terminal 2 — Vite (hot-reload)
npm run dev

# Terminal 3 — Worker de queue (optionnel)
php artisan queue:listen --tries=1
```

---

## Configuration production

### 1. Variables d'environnement

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.com
```

### 2. Optimisation

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

### 3. Permissions dossiers

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Serveur web (Nginx — exemple)

```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/pharma-stock/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Structure des rôles et accès

```
super_admin
  └── Accès total : toutes pharmacies, tous dépôts, admin utilisateurs

pharmacy_admin
  └── Gestion complète de sa pharmacie et ses dépôts

pharmacy_staff
  └── Stock, transferts, ventes de sa pharmacie

depot_staff
  └── Stock et ventes de son dépôt uniquement
      └── "Ma Pharmacie" masquée dans le menu
```

---

## Commandes utiles

```bash
# Vider tous les caches
php artisan optimize:clear

# Relancer les migrations (⚠ supprime les données)
php artisan migrate:fresh --seed

# Créer un utilisateur manuellement
php artisan tinker
>>> User::create(['name'=>'...','email'=>'...','password'=>bcrypt('...')])
>>> $user->assignRole('pharmacy_admin')

# Voir les routes
php artisan route:list

# Lancer les tests
composer test
```

---

## Résolution de problèmes

| Problème | Solution |
|---------|----------|
| Page blanche après install | `php artisan optimize:clear` + vérifier `APP_KEY` dans `.env` |
| Erreur SQLSTATE | Vérifier `DB_*` dans `.env` et que la base existe |
| Assets non chargés | Relancer `npm run build` ou `npm run dev` |
| Permission denied (storage) | `chmod -R 775 storage bootstrap/cache` |
| Rôles non trouvés | `php artisan permission:cache-reset` |
| Queue non traitée | Lancer `php artisan queue:work` |

---

## Données de test (optionnel)

Si vous souhaitez des données fictives pour tester :

```bash
php artisan tinker
>>> \Database\Seeders\DatabaseSeeder::class  // voir les seeders disponibles
```

Ou créer un `DemoDataSeeder` dans `database/seeders/` selon vos besoins.

---

*Sys E-Dépôt Pharma — LUMIERE AFRIQUE GROUP (Ld'A Group SARL) — Parakou, Bénin*
