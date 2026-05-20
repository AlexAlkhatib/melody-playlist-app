# 🎵 **Symfony Melody — Application musicale collaborative**

**Symfony Melody** est une application web collaborative permettant aux utilisateurs de **composer**, **partager** et **gérer des playlists musicales en ligne**.
Ce projet a été réalisé dans un **cadre personnel**, afin d’approfondir le développement web moderne avec le framework **Symfony** et de comprendre la construction d’une application collaborative complète.

L’objectif : créer une plateforme intuitive où chaque utilisateur peut créer ses playlists, découvrir celles des autres et interagir de manière fluide.


## 🎯 Objectifs du projet

* Développer une application complète avec **Symfony**
* Mettre en place un système de gestion de comptes et d’authentification sécurisé
* Permettre la création, édition et partage de playlists musicales
* Structurer une architecture MVC propre et maintenable
* Manipuler une base de données relationnelle via Doctrine ORM
* Concevoir une interface responsive et moderne


## 🎵 Fonctionnalités principales

### 🔐 Authentification & gestion des comptes

* Inscription et connexion sécurisées
* Gestion des rôles utilisateurs (ex : standard, admin)
* Profil utilisateur (photo, informations, playlists)

### 🎼 Playlists musicales

* Création de playlists personnalisées
* Ajout / modification / suppression de morceaux
* Organisation des titres dans chaque playlist
* Consultation des playlists d’autres utilisateurs

### 🔗 Collaboration & partage

* Partage public de playlists
* Découverte de playlists populaires ou récentes
* Interaction avec les créations des autres utilisateurs

### 🖥️ Interface

* UI responsive basée sur Twig + CSS/Bootstrap
* Navigation fluide et pages générées dynamiquement


## 🧰 Stack Technique

### Backend

* **Symfony** (Framework PHP)
* **Doctrine ORM** : mapping & interactions avec la BDD
* **Security Component** : authentification + gestion des rôles
* **Twig** : moteur de templates

### Base de données

* MySQL / MariaDB
* Entités : User, Playlist, Track, PlaylistTrack
* Migrations intégrées via Symfony

### Frontend

* Twig
* Bootstrap
* CSS personnalisé
* JavaScript pour interactions et actions dynamiques


## 🧠 Compétences démontrées

- ✔ Conception d’une architecture MVC professionnelle avec Symfony
- ✔ Gestion complète d’un système d’authentification sécurisé
- ✔ Manipulation avancée de Doctrine ORM (relations, entités, requêtes)
- ✔ Construction d’une interface propre et responsive avec Twig + Bootstrap
- ✔ Développement d’un système CRUD complet (create, read, update, delete)
- ✔ Gestion d’une application collaborative et de ses flux utilisateurs
- ✔ Organisation d’un projet web structuré, scalable et maintenable


## 📂 Structure du projet (exemple adaptée à Symfony)

```
symfony_melody/
 ├── assets/                # JS/CSS frontend
 ├── config/                # Configuration Symfony
 ├── migrations/            # Migrations Doctrine
 ├── public/                # Fichiers accessibles publiquement
 ├── src/
 │   ├── Controller/        # Logique métier / routes
 │   ├── Entity/            # Entités (User, Playlist, Track…)
 │   ├── Repository/        # Queries personnalisées
 │   └── Security/          # Authentification
 ├── templates/             # Pages Twig
 ├── translations/
 ├── composer.json
 └── README.md
```


## 🚀 Installation & Lancement

### 1️⃣ Cloner le projet

```bash
git clone https://github.com/AlexAlkhatib/symfony_melody.git
cd symfony_melody
```

### 2️⃣ Installer les dépendances PHP

```bash
composer install
```

### 3️⃣ Configurer l’environnement

Créer un fichier `.env.local` :

```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/melody"
```

### 4️⃣ Lancer les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5️⃣ Lancer le serveur Symfony

```bash
symfony server:start
```

Accéder à l’application :
👉 **[http://localhost:8000](http://localhost:8000)**

---

## 🔧 Améliorations possibles

* Interface plus moderne avec Vue.js / React en frontend
* Ajout d’un lecteur audio intégré dans l’app
* Ajout de commentaires sur les playlists
* Recommandations musicales personnalisées
* API REST pour versions mobile / SPA
* Ajout d’un mode “playlist collaborative”


## 👤 À propos

Développeur passionné par les technologies web modernes, j’ai conçu ce projet pour approfondir Symfony et renforcer mes compétences backend et full-stack.

GitHub : **[https://github.com/AlexAlkhatib](https://github.com/AlexAlkhatib)**


## 📄 Licence
MIT License
Copyright (c) 2025 Alex Alkhatib
