# 🎵 Melody — application musicale collaborative

**Melody** est une application web complète de gestion de **playlists musicales collaboratives**,
construite avec **Symfony 6.4 LTS** et **PHP 8.2+**.

Chaque utilisateur peut ajouter des morceaux au catalogue commun (titre, artiste, genre, pochette,
fichier audio), composer ses propres playlists et écouter les morceaux directement dans le navigateur.
Les administrateurs disposent d'un back-office dédié pour gérer le catalogue, les comptes et les messages.

---

## Table des matières

1. [Fonctionnalités](#-fonctionnalités)
2. [Stack technique](#-stack-technique)
3. [Architecture](#-architecture)
4. [Modèle de données](#-modèle-de-données)
5. [Sécurité](#-sécurité)
6. [Installation locale](#-installation-locale)
7. [Configuration](#-configuration)
8. [Base de données et migrations](#-base-de-données-et-migrations)
9. [Tests](#-tests)
10. [Déploiement Docker (production)](#-déploiement-docker-production)
11. [Déploiement hors Docker](#-déploiement-hors-docker)
12. [Refonte 2.0 — ce qui a changé](#-refonte-20--ce-qui-a-changé)
13. [Raccourcis Makefile](#-raccourcis-makefile)
14. [Roadmap](#-roadmap)
15. [Licence](#-licence)

---

## ✨ Fonctionnalités

### Comptes et authentification
- Inscription avec confirmation du mot de passe et acceptation des conditions
- Connexion par email / mot de passe, **« se souvenir de moi »** (cookie signé)
- **Limitation à 5 tentatives / 15 minutes** contre le bourrage d'identifiants
- Déconnexion avec invalidation de session
- Profil utilisateur : consultation, modification des informations, changement de mot de passe
  (avec vérification de l'ancien mot de passe)
- Rôles : `ROLE_USER`, `ROLE_ADMIN` (hiérarchie : admin ⊃ user)

### Catalogue musical (collaboratif)
- Ajout d'un morceau : titre, artiste, date de sortie, genre, **pochette** (JPG/PNG/WebP ≤ 2 Mo)
  et **fichier audio** (MP3, OGG, WAV, FLAC ≤ 20 Mo)
- Recherche plein texte sur le titre, l'artiste ou le genre + pagination (9 par page)
- Fiche détaillée avec lecteur audio intégré
- Modification / suppression réservées au **propriétaire du morceau** (ou à un administrateur)
- Suppression en `POST` protégée par **jeton CSRF** (jamais en `GET`)

### Playlists
- Création, renommage, suppression de playlists personnelles
- Sélection multiple de morceaux dans le formulaire
- Compteur de morceaux **synchronisé automatiquement**
- Consultation restreinte au propriétaire (contrôle d'accès serveur, réponse `403` sinon)
- Lisibilité : chaque playlist affiche ses morceaux avec lecteur audio

### Contact
- Formulaire de contact validé côté serveur
- Message **persisté en base** puis envoyé par email (`From` = expéditeur applicatif, `Reply-To` = visiteur)
- En cas d'échec SMTP, le message reste consultable dans le back-office

### Back-office (EasyAdmin 4)
- Tableau de bord avec indicateurs (morceaux, playlists, genres, utilisateurs, messages)
- CRUD morceaux, genres, playlists, utilisateurs (mot de passe haché automatiquement)
- Messages de contact en **lecture seule**
- Accès réservé à `ROLE_ADMIN`

### Interface
- Twig + **Bootstrap 5** auto-hébergé (aucun CDN)
- Entièrement responsive (mobile / tablette / desktop)
- Pages d'erreur personnalisées (404 / 500), messages flash, fil d'Ariane
- Accessibilité : labels explicites, `aria-*`, contrastes, navigation clavier

---

## 🧰 Stack technique

| Composant | Version | Rôle |
|---|---|---|
| PHP | ≥ 8.2 (image Docker : 8.3) | Runtime |
| Symfony | 6.4 LTS | Framework |
| Doctrine ORM | 2.20 | Persistance |
| Doctrine Migrations | 3.x | Schéma versionné |
| Twig | 3.8 | Templates |
| EasyAdmin | 4.29 | Back-office |
| VichUploaderBundle | 2.9 | Uploads + nommage |
| KnpPaginatorBundle | 6.3 | Pagination |
| Interface | CSS natif + JS vanilla (aucune dépendance) | Design system sombre, responsive |
| MySQL / MariaDB | 8.0+ | Base de données |
| PHPUnit | 9.6 | Tests fonctionnels |

---

## 🧱 Architecture

Application Symfony monolithique selon une architecture **MVC en couches** :

```
Requête HTTP
   │
   ├─ public/index.php ─── front controller (runtime Symfony)
   │
   ├─ Security (pare-feu « main ») ── LoginAuthenticator + access_control
   │
   ├─ Controller ───────── logique HTTP (validation, autorisations, redirections)
   │     ├─ Form ──────── définition & validation des formulaires
   │     ├─ Entity ────── modèle métier + contraintes de validation
   │     └─ Repository ── requêtes Doctrine (recherche, compteurs, propriétaire)
   │
   └─ Twig ────────────── rendu HTML (hérite de base.html.twig)
```

```
melody/
├── bin/                     # console, phpunit
├── config/
│   ├── packages/            # security, doctrine, twig, vich_uploader…
│   └── routes.yaml          # routage par attributs
├── docker/
│   ├── nginx/default.conf   # serveur web de production
│   ├── php/php.ini          # réglages PHP (uploads, OPcache)
│   └── entrypoint.sh        # migrations + démarrage
├── migrations/              # 3 migrations versionnées
├── public/
│   ├── assets/              # Bootstrap 5 + CSS applicatif (auto-hébergés)
│   ├── media/               # uploads (images / audio) — non versionné
│   └── index.php
├── src/
│   ├── Controller/          # Home, Security, Registration, Morceaux,
│   │   └── Admin/           #   Playlist, Profile, Contact + back-office EasyAdmin
│   ├── DataFixtures/        # jeu de démonstration
│   ├── Entity/              # User, Morceaux, Playlist, Genre, Contact
│   ├── Form/                # 6 formulaires typés
│   ├── Repository/          # 5 repositories
│   └── Security/            # LoginAuthenticator
├── templates/
│   ├── admin/               # tableau de bord EasyAdmin
│   ├── bundles/…/Exception/ # pages d'erreur
│   ├── morceaux/ playlist/  # CRUD (index, show, new, edit, _form, _delete_form)
│   ├── partials/            # header, footer, flash
│   └── profile/ security/ registration/ contact/
├── tests/Functional/        # tests de bout en bout (SQLite)
├── Dockerfile
├── docker-compose.yml
├── Makefile
└── composer.json
```

---

## 🗄 Modèle de données

```
┌──────────────┐        ┌───────────────────┐        ┌──────────────┐
│    user      │ 1    N │     playlist      │ N    M │   morceaux   │
│──────────────│────────│───────────────────│────────│──────────────│
│ id (PK)      │        │ id (PK)           │        │ id (PK)      │
│ email (UQ)   │        │ nom_playlist      │        │ nom_morceaux │
│ roles        │        │ nb_morceaux       │        │ artiste      │
│ password     │        │ userid_id (FK)    │        │ date_sortie  │
│ nom, prenom  │        └───────────────────┘        │ image_name   │
└──────────────┘                 │                   │ music_name   │
        │                        │                   │ genreid_id(FK)│
        │ 1                      │                   │ user_id (FK) │
        │                        │                   └──────────────┘
        │ N                      │                          │ N
        └──── morceaux (créés)   └── playlist_morceaux ──────┘
                                     (PK composite, CASCADE)
                                                │
                                        ┌───────┴───────┐
                                        │     genre     │
                                        │ id, nom_genre │
                                        └───────────────┘

┌──────────────┐
│   contact    │  id, prenom, email, subject, message, created_at
└──────────────┘
```

**Relations clés**
- `playlist.userid` → `user` : une playlist appartient à un seul utilisateur (`SET NULL` à la suppression).
- `playlist_morceaux` : table de jointure N‑N avec suppression en cascade.
- `morceaux.genreid` → `genre` : obligatoire.
- `morceaux.user_id` → `user` : propriétaire du morceau (`SET NULL`, ajouté en 2.0).

---

## 🔐 Sécurité

| Risque | Mesure en place |
|---|---|
| Accès non autorisé | `access_control` explicite : seules `/`, `/login`, `/register`, `/contact` et `/media/*` sont publics ; `/admin` exige `ROLE_ADMIN` ; le reste `ROLE_USER` |
| Mots de passe | Hachage `auto` (bcrypt/argon2), jamais stockés en clair, `PasswordUpgraderInterface` pour re-hacher |
| Force brute | `login_throttling` : 5 tentatives / 15 min par IP + compte |
| CSRF | Tous les formulaires Symfony (jeton automatique) + jetons explicites sur les suppressions |
| Suppression via GET | Suppressions exclusivement en `POST` + CSRF |
| Escalade de privilèges | Plus d'édition publique des rôles/mots de passe ; management via back-office `ROLE_ADMIN` |
| IDOR | Vérification serveur `isOwnedBy()` / `isEditableBy()` sur playlists et morceaux (`403`) |
| Uploads malveillants | Contraintes MIME + taille, nommage aléatoire (`SmartUniqueNamer`), dossier `media/` non exécutable (nga nginx), `onDelete` propre |
| Fuite de secrets | `.env` sans secret réel, `.env.local` ignoré, secrets fournis par l'environnement |
| XSS | Échappement Twig par défaut (`autoescape`) |
| Cookies | `HttpOnly`, `SameSite=Lax`, `Secure` automatique en HTTPS |

---

## 🚀 Installation locale

### Prérequis
- PHP **8.2+** avec les extensions `ctype`, `iconv`, `intl`, `pdo_mysql`, `zip`
- [Composer](https://getcomposer.org) 2
- MySQL 8 / MariaDB 10.6+ (ou Docker)

### Étapes

```bash
# 1. Dépendances
composer install

# 2. Configuration locale (ne jamais committer ce fichier)
cp .env .env.local
#   → adapter DATABASE_URL, MAILER_DSN et APP_SECRET

# 3. Base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 4. Jeu de données de démonstration (optionnel)
php bin/console doctrine:fixtures:load

# 5. Serveur de développement
php -S 0.0.0.0:8000 -t public
#   ou : symfony server:start
```

Application disponible sur **http://localhost:8000**

### Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@melody.test` | `Password123!` |
| Utilisateur | `user@melody.test` | `Password123!` |
| Utilisateur | `lea@melody.test` | `Password123!` |

> Les fixtures contiennent 6 genres, 12 morceaux et 5 playlists.

---

## ⚙️ Configuration

Toutes les variables sont documentées dans `.env` (valeurs de développement) :

| Variable | Description | Exemple |
|---|---|---|
| `APP_ENV` | Environnement | `dev` / `prod` / `test` |
| `APP_DEBUG` | Mode debug | `1` / `0` |
| `APP_SECRET` | Clé de signature (sessions, CSRF, remember-me) | 32 caractères aléatoires |
| `APP_URL` | URL publique | `https://melody.example.com` |
| `DATABASE_URL` | Connexion Doctrine | `mysql://user:pass@127.0.0.1:3306/melody?serverVersion=8.0&charset=utf8mb4` |
| `MAILER_DSN` | Transport email | `smtp://user:pass@smtp.example.com:587` |
| `MAILER_FROM` | Expéditeur des emails applicatifs | `Melody <no-reply@melody.local>` |
| `CONTACT_TO_EMAIL` | Destinataire du formulaire de contact | `contact@melody.local` |

Générer un secret solide :

```bash
php -r "echo bin2hex(random_bytes(16));"
```

En production, **utilisez les variables d'environnement** du conteneur ou de la plateforme,
pas un fichier `.env` versionné.

---

## 🗃 Base de données et migrations

```bash
php bin/console doctrine:migrations:migrate            # appliquer
php bin/console doctrine:migrations:status             # état
php bin/console doctrine:migrations:migrate prev       # revenir en arrière
php bin/console doctrine:schema:validate --skip-sync   # valider le mapping
```

| Migration | Contenu |
|---|---|
| `Version20221104144512` | Création des tables `user`, `genre`, `morceaux`, `playlist`, `playlist_morceaux` |
| `Version20221109201144` | Table `contact` |
| `Version20261108120000` | `morceaux.user_id` (propriétaire) + `music_name` nullable |

---

## ✅ Tests

Les tests fonctionnels s'exécutent sur une base **SQLite jetable** : aucun serveur MySQL n'est requis.

```bash
php bin/phpunit            # suite complète
composer test              # alias
```

Couverture fonctionnelle (9 tests, 24 assertions) :

| Test | Vérifie |
|---|---|
| `testHomePageIsPublicAndShowsHero` | page d'accueil publique et rendue |
| `testPublicPagesAreReachableWithoutLogin` | `/login`, `/register`, `/contact` accessibles |
| `testCatalogueRedirectsAnonymousVisitorsToLogin` | redirection des visiteurs anonymes |
| `testUserCanLogInAndBrowseTheCatalogue` | connexion + navigation catalogue |
| `testWrongPasswordDisplaysAnErrorMessage` | message d'erreur d'authentification |
| `testConnectedUserCanAddATrack` | création d'un morceau via le formulaire |
| `testPlaylistOfAnotherUserIsForbidden` | protection IDOR (`403`) |
| `testAdminAreaRequiresTheAdminRole` | back-office réservé aux admins |
| `testContactFormSendsAMessage` | persistance + envoi du message de contact |

Vérifications statiques :

```bash
php bin/console lint:yaml config
php bin/console lint:twig templates
```

---

## 🐳 Déploiement Docker (production)

La pile fournie contient **PHP-FPM (Symfony) + nginx + MySQL 8**, avec volume partagé pour les uploads.

```bash
# 1. Variables d'environnement (ne pas committer)
cat > .env.prod <<'EOF'
APP_SECRET=remplacez-par-32-caracteres-aleatoires
APP_URL=https://melody.example.com
MYSQL_DATABASE=melody
MYSQL_USER=melody
MYSQL_PASSWORD=un-mot-de-passe-solide
MYSQL_ROOT_PASSWORD=encore-plus-solide
MAILER_DSN=smtp://user:pass@smtp.example.com:587
MAILER_FROM=Melody <no-reply@example.com>
CONTACT_TO_EMAIL=contact@example.com
HTTP_PORT=8080
EOF

# 2. Démarrage (le conteneur applique les migrations au boot)
docker compose --env-file .env.prod up -d --build

# 3. Vérifications
docker compose logs -f app
curl -I http://localhost:8080
```

### Ce que fait l'image

1. Installe PHP 8.3 + extensions (`intl`, `pdo_mysql`, `opcache`, `zip`).
2. `composer install --no-dev --optimize-autoloader`.
3. `assets:install` + `cache:warmup` (cache de production chaud).
4. Au démarrage : amorçage du volume public, attente de MySQL, `doctrine:migrations:migrate`, puis PHP-FPM.

### Mise en production : points d'attention

- Placez l'application derrière un **reverse proxy TLS** (Traefik, Caddy, nginx, Cloudflare…)
  et transmettez `X-Forwarded-Proto` ; Symfony passe alors les cookies en `Secure`.
- **Sauvegardez** le volume MySQL (`db-data`) **et** le volume des uploads (`public-data`).
- Limitez l'accès à `/admin` (VPN ou liste d'IP) si nécessaire.
- Configurez un vrai transport SMTP : `MAILER_DSN=null://null` n'envoie aucun email.
- Surveillez les journaux : `docker compose logs -f app` (`var/log/prod.log`, JSON sur stderr).

---

## 🖥 Déploiement hors Docker

Prérequis : PHP 8.2+ (FPM), MySQL 8, nginx ou Apache.

```bash
composer install --no-dev --optimize-autoloader
php bin/console assets:install public
php bin/console cache:warmup --env=prod
php bin/console doctrine:migrations:migrate --no-interaction
```

- **Racine web** = `public/` (jamais la racine du projet).
- **Permissions** : `var/` et `public/media/` inscriptibles par l'utilisateur PHP.
- Exemple de configuration nginx fourni : `docker/nginx/default.conf`
  (front controller, `client_max_body_size 24M`, cache statique, `media/` non exécuté).

---

## 🎧 Interface et lecteur audio (2.1)

Le front a été entièrement reconstruit : **aucun framework CSS**, un design system maison
(`public/assets/css/app.css`) et un lecteur audio persistant en JavaScript vanilla
(`public/assets/js/app.js`).

- **Coque d'application** : barre latérale, barre de recherche, zone de contenu, barre de lecture fixe.
- **Design system** : tokens CSS (couleurs, rayons, ombres), thème sombre, grille fluide, trois paliers responsive.
- **Pochettes générées** : huit dégradés déterministes, mosaïques 2×2 pour les playlists, aucune image requise.
- **Lecteur continu** : file d'attente, lecture/pause, précédent/suivant, aléatoire, répétition
  (piste ou file), position, volume, raccourcis clavier (`Espace`, `Maj + ←/→`), reprise de la
  dernière écoute et historique local.
- **Accessibilité** : navigation clavier, attributs `aria-*`, focus visibles, `prefers-reduced-motion` respecté.
- **Hors ligne** : zéro CDN, zéro police distante.

### Fichiers concernés

| Fichier | Rôle |
|---|---|
| `public/assets/css/app.css` | Design system complet |
| `public/assets/js/app.js` | Lecteur audio et interactions |
| `templates/partials/_icons.html.twig` | Sprite SVG inline |
| `templates/partials/_ui.html.twig` | Macros pochettes / cartes / lignes |
| `public/router.php` | Serveur PHP intégré avec types MIME corrects |

### Audio de démonstration

`docs/tools/make_demo_audio.py` synthétise douze pistes WAV (≈ 130 Ko chacune, 12 s) référencées
par les fixtures : le lecteur fonctionne dès `doctrine:fixtures:load`, sans upload.

### Serveur local

```bash
php -S 0.0.0.0:3000 -t public public/router.php
```

`router.php` sert les fichiers statiques avec leur type MIME (CSS, JS, WAV) et délègue le reste à Symfony.

### Environnement `preview` (sans MySQL)

```bash
APP_ENV=preview php bin/console doctrine:schema:create
APP_ENV=preview php bin/console doctrine:fixtures:load
```

## 🔄 Refonte 2.1 — ce qui a changé

### Bugs bloquants corrigés

| # | Problème | Correction |
|---|---|---|
| 1 | `encore_entry_link_tags()` appelé sans WebpackEncore → **toutes les pages plantaient** | Assets Bootstrap 5 auto-hébergés, Encore retiré |
| 2 | Extension Twig `AppBundle\Twig\Extension\Mobile` non autoloadable (namespace/PSR‑4) → conteneur KO | Supprimée : responsive géré en CSS |
| 3 | `Morceaux::setImageFile()` écrivait `$this->updatedAt` **inexistant** | Supprimé |
| 4 | `User::getPassword(): string` pouvait retourner `null` → `TypeError` | Signature `?string` (conforme à l'interface) |
| 5 | Suppression d'un morceau en **GET sans CSRF** | `POST` + jeton CSRF + contrôle du propriétaire |
| 6 | `MorceauxController::new()` sans `isValid()` | Validation complète |
| 7 | Templates appelant des routes inexistantes (`app_morceaux_index`, `app_morceaux_edit`) | Routes unifiées `morceaux.*`, templates réécrits |
| 8 | `templates/contact/index.html.twig` **vide** | Page de contact complète |
| 9 | Fixtures : `setArtiste('Artiste #', $i)` (2 arguments) et **aucun `flush()`** | Fixtures réécrites (6 genres, 12 morceaux, 5 playlists) |
| 10 | Email de contact avec `From:` = adresse du visiteur (SPF/DKIM impossibles) | `MAILER_FROM` + `Reply-To` visiteur |
| 11 | `access_control` entièrement commenté → **tout était public** | Matrice d'accès explicite |
| 12 | Deux authentificateurs, l'un levant `TODO: provide a valid redirect` | Un seul `LoginAuthenticator` + redirection |
| 13 | `/user` permettait de modifier rôles et mots de passe de n'importe qui (hash affiché) | `/profil` pour soi, back-office `ROLE_ADMIN` pour les comptes |
| 14 | `docker-compose.yml` en **PostgreSQL** alors que les migrations sont MySQL | Pile MySQL 8 + nginx + PHP‑FPM |
| 15 | Symfony **6.1 (EOL)** | Symfony **6.4 LTS** (support jusqu'en 2027) |
| 16 | EasyAdmin installé mais **aucun dashboard** (dépendance morte) | Back-office complet à `/admin` |
| 17 | Uploads servis depuis `/morceaux` → collision avec la route `/morceaux/{id}` | Uploads déplacés sous `/media/…` |
| 18 | Presque aucune validation métier | Contraintes `Assert` sur toutes les entités + messages en français |

### Améliorations structurelles
- Séparation des responsabilités : `ProfileController` / `Admin\*CrudController` / `MorceauxController`.
- `Playlist::syncNbMorceaux()` : compteur toujours juste.
- `Morceaux::isEditableBy()` / `Playlist::isOwnedBy()` : règles d'accès centralisées dans le modèle.
- Repositories enrichis (`searchQuery`, `findByOwnerQuery`, `count*`) au lieu de `findAll()` dans les contrôleurs.
- Interface repensée : navbar avec avatar, cartes, états vides, pagination, lecteurs audio.
- Formulaires Bootstrap 5 (`form_themes`), libellés français, aides contextuelles.
- Pipeline de déploiement reproductible (Dockerfile multi-étapes logiques, entrypoint, Makefile).
- 9 tests fonctionnels + lints YAML/Twig exécutables en CI.

---

## 🛠 Raccourcis Makefile

```bash
make help        # liste des commandes
make install     # composer install
make start       # serveur de dev (0.0.0.0:8000)
make db          # création de la base
make migrate     # migrations
make fixtures    # données de démonstration
make test        # tests fonctionnels
make lint        # lint Twig + YAML
make docker-up   # pile Docker (http://localhost:8080)
make docker-down
make clean
```

---

## 🗺 Roadmap

- [ ] Playlists publiques / privées avec lien de partage
- [ ] Lecteur audio persistant (lecture continue entre les pages)
- [ ] API REST / JSON (`/api`) pour un futur client mobile
- [ ] Recommandations par genre et historique d'écoute
- [ ] Upload asynchrone avec barre de progression
- [ ] Notifications (email et in-app) lors d'un partage
- [ ] CI GitHub Actions (tests + build Docker)
- [ ] Traductions EN / ES (fichiers `translations/` prêts)

---

## 📄 Licence

MIT License — Copyright (c) 2025 Alex Alkhatib. Voir [LICENSE.md](LICENSE.md).

Projet personnel réalisé pour approfondir **Symfony**, **Doctrine ORM** et la conception
d'applications web sécurisées.

GitHub : [https://github.com/AlexAlkhatib](https://github.com/AlexAlkhatib)
