#!/usr/bin/env python3
"""Génère la documentation PDF de Melody (reportlab)."""

from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_JUSTIFY, TA_CENTER
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import (
    BaseDocTemplate,
    KeepTogether,
    Frame,
    NextPageTemplate,
    PageBreak,
    PageTemplate,
    Paragraph,
    Preformatted,
    Spacer,
    Table,
    TableStyle,
)

OUT = Path(__file__).resolve().parents[1] / "Melody-Documentation.pdf"

PRIMARY = colors.HexColor("#6D28D9")
DARK = colors.HexColor("#12081F")
ACCENT = colors.HexColor("#EC4899")
LIGHT = colors.HexColor("#F4F1FB")
GREY = colors.HexColor("#5B6472")
INK = colors.HexColor("#1F2933")

W = A4[0] - 40 * mm  # largeur utile

styles = getSampleStyleSheet()

body = ParagraphStyle(
    "body", parent=styles["BodyText"], fontName="Helvetica", fontSize=9.6,
    leading=14.5, alignment=TA_JUSTIFY, textColor=INK, spaceAfter=6,
)
h1 = ParagraphStyle(
    "h1", parent=styles["Heading1"], fontName="Helvetica-Bold", fontSize=17,
    leading=21, textColor=PRIMARY, spaceBefore=4, spaceAfter=10,
)
h2 = ParagraphStyle(
    "h2", parent=styles["Heading2"], fontName="Helvetica-Bold", fontSize=11.5,
    leading=15, textColor=DARK, spaceBefore=9, spaceAfter=5,
)
bullet = ParagraphStyle("bullet", parent=body, leftIndent=12, bulletIndent=2, spaceAfter=2.5)
cell = ParagraphStyle("cell", parent=body, fontSize=8.2, leading=11, textColor=INK,
                      alignment=0, spaceAfter=0)
th = ParagraphStyle("th", parent=cell, fontName="Helvetica-Bold", textColor=colors.white)
small = ParagraphStyle("small", parent=body, fontSize=8.2, leading=11, textColor=GREY)
code = ParagraphStyle(
    "code", parent=styles["Code"], fontName="Courier", fontSize=8.2, leading=11.5,
    textColor=colors.HexColor("#1B1B2F"), backColor=LIGHT, borderPadding=8,
    leftIndent=4, spaceBefore=4, spaceAfter=8,
)
cover_title = ParagraphStyle(
    "cover_title", parent=styles["Title"], fontName="Helvetica-Bold", fontSize=42,
    leading=46, textColor=colors.white, alignment=TA_CENTER,
)
cover_sub = ParagraphStyle(
    "cover_sub", parent=body, fontSize=13, leading=19,
    textColor=colors.HexColor("#E9D5FF"), alignment=TA_CENTER,
)
cover_small = ParagraphStyle(
    "cover_small", parent=body, fontSize=9.5, textColor=colors.HexColor("#C4B5FD"),
    alignment=TA_CENTER,
)


def header_footer(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(PRIMARY)
    canvas.rect(0, A4[1] - 8 * mm, A4[0], 8 * mm, stroke=0, fill=1)
    canvas.setFillColor(colors.white)
    canvas.setFont("Helvetica-Bold", 8)
    canvas.drawString(20 * mm, A4[1] - 5.6 * mm, "MELODY — DOCUMENTATION TECHNIQUE")
    canvas.setFont("Helvetica", 8)
    canvas.drawRightString(A4[0] - 20 * mm, A4[1] - 5.6 * mm, "Version 2.1")

    canvas.setStrokeColor(colors.HexColor("#E5E7EB"))
    canvas.setLineWidth(0.6)
    canvas.line(20 * mm, 14 * mm, A4[0] - 20 * mm, 14 * mm)
    canvas.setFillColor(GREY)
    canvas.setFont("Helvetica", 7.6)
    canvas.drawString(20 * mm, 9.5 * mm, "Symfony 6.4 LTS · PHP 8.2+ · MySQL 8")
    canvas.drawRightString(A4[0] - 20 * mm, 9.5 * mm, f"Page {doc.page}")
    canvas.restoreState()


def table(rows, ratios):
    widths = [W * r for r in ratios]
    data = []
    for index, row in enumerate(rows):
        style = th if index == 0 else cell
        data.append([Paragraph(str(value), style) for value in row])

    t = Table(data, colWidths=widths, repeatRows=1, hAlign="LEFT")
    t.setStyle(TableStyle([
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#E5E7EB")),
        ("LEFTPADDING", (0, 0), (-1, -1), 5),
        ("RIGHTPADDING", (0, 0), (-1, -1), 5),
        ("TOPPADDING", (0, 0), (-1, -1), 3.5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 3.5),
        ("BACKGROUND", (0, 0), (-1, 0), PRIMARY),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, LIGHT]),
    ]))
    return t


def cover_art(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(DARK)
    canvas.rect(0, 0, A4[0], A4[1], stroke=0, fill=1)
    canvas.setFillColor(PRIMARY)
    canvas.circle(A4[0] * 0.86, A4[1] * 0.84, 58 * mm, stroke=0, fill=1)
    canvas.setFillColor(ACCENT)
    canvas.circle(A4[0] * 0.14, A4[1] * 0.18, 38 * mm, stroke=0, fill=1)
    canvas.setFillColor(DARK)
    canvas.restoreState()


def build() -> None:
    doc = BaseDocTemplate(
        str(OUT), pagesize=A4, title="Melody — Documentation technique",
        author="Alex Alkhatib", subject="Application musicale collaborative Symfony",
        leftMargin=20 * mm, rightMargin=20 * mm, topMargin=20 * mm, bottomMargin=20 * mm,
    )
    frame = Frame(doc.leftMargin, doc.bottomMargin, doc.width, doc.height, id="normal")
    doc.addPageTemplates([
        PageTemplate(id="cover", frames=[frame], onPage=cover_art),
        PageTemplate(id="body", frames=[frame], onPage=header_footer),
    ])

    story = []
    # ── Couverture ────────────────────────────────────────────────────────────
    story += [
        Spacer(1, 46 * mm),
        Paragraph("MELODY", cover_title),
        Spacer(1, 4 * mm),
        Paragraph("Application musicale collaborative", cover_sub),
        Paragraph("Documentation technique et guide de déploiement", cover_sub),
        Spacer(1, 24 * mm),
        Paragraph("Version 2.1", ParagraphStyle(
            "v", parent=cover_small, fontSize=15, textColor=colors.white)),
        Spacer(1, 3 * mm),
        Paragraph("Symfony 6.4 LTS · PHP 8.2+ · Doctrine ORM · MySQL 8 · Docker", cover_small),
        Spacer(1, 42 * mm),
        Paragraph("Alex Alkhatib", ParagraphStyle(
            "a", parent=cover_small, fontSize=11, textColor=colors.white)),
        Paragraph("MIT License", cover_small),
        NextPageTemplate("body"),
        PageBreak(),
    ]

    # ── Sommaire ──────────────────────────────────────────────────────────────
    story += [
        Paragraph("Sommaire", h1),
        Paragraph(
            "1. Présentation et objectifs · 2. Fonctionnalités · 3. Stack technique · "
            "4. Architecture · 5. Modèle de données · 6. Sécurité · 7. Installation locale · "
            "8. Configuration · 9. Tests · 10. Déploiement Docker · 11. Interface et lecteur · "
            "12. Refonte 2.0 · 13. Roadmap · 14. Licence", body),

        Paragraph("1. Présentation et objectifs", h1),
        Paragraph(
            "Melody est une application web de gestion de <b>playlists musicales collaboratives</b>. "
            "Les utilisateurs alimentent un catalogue commun de morceaux (titre, artiste, genre, "
            "pochette, fichier audio) puis composent leurs propres playlists. Un back-office "
            "d'administration permet de gérer le catalogue, les comptes et les messages reçus.", body),
        Paragraph("Objectifs du projet", h2),
        Paragraph("• Concevoir une application Symfony complète et maintenable (MVC en couches).", bullet),
        Paragraph("• Mettre en place une authentification et une gestion de rôles robustes.", bullet),
        Paragraph("• Manipuler Doctrine ORM : relations N-N, ManyToOne, migrations versionnées.", bullet),
        Paragraph("• Livrer une interface responsive, accessible et cohérente.", bullet),
        Paragraph("• Industrialiser : tests automatisés, image Docker, documentation.", bullet),

        Paragraph("2. Fonctionnalités", h1),
        table([
            ["Domaine", "Fonctionnalités"],
            ["Comptes", "Inscription, connexion, « se souvenir de moi », limitation des tentatives, "
                        "déconnexion sécurisée, profil et changement de mot de passe."],
            ["Catalogue", "Ajout de morceaux avec pochette et audio, recherche plein texte, pagination, "
                          "fiche détaillée, lecteur intégré, modification et suppression par le propriétaire."],
            ["Playlists", "Création, renommage, suppression, sélection multiple de morceaux, "
                          "compteur automatique, accès restreint au propriétaire."],
            ["Contact", "Formulaire validé, message persisté puis envoyé par email "
                        "(expéditeur applicatif + Reply-To visiteur), consultation dans le back-office."],
            ["Back-office", "Tableau de bord chiffré, CRUD morceaux, genres, playlists et utilisateurs, "
                            "messages en lecture seule, accès ROLE_ADMIN."],
        ], [0.24, 0.76]),

        Paragraph("3. Stack technique", h1),
        table([
            ["Composant", "Version", "Rôle"],
            ["PHP", ">= 8.2 (Docker : 8.3)", "Runtime"],
            ["Symfony", "6.4 LTS", "Framework applicatif"],
            ["Doctrine ORM", "2.20", "Persistance et mapping"],
            ["Doctrine Migrations", "3.x", "Schéma versionné"],
            ["Twig", "3.8", "Moteur de templates"],
            ["EasyAdmin", "4.29", "Back-office"],
            ["VichUploaderBundle", "2.9", "Uploads (pochettes, audio)"],
            ["KnpPaginatorBundle", "6.3", "Pagination"],
            ["Design system", "CSS natif + JS vanilla", "Interface sombre responsive"],
            ["MySQL / MariaDB", "8.0+", "Base de données"],
            ["PHPUnit", "9.6", "Tests fonctionnels"],
        ], [0.26, 0.30, 0.44]),

        Paragraph("4. Architecture", h1),
        Paragraph(
            "Architecture MVC en couches. La requête traverse le pare-feu de sécurité, puis le "
            "contrôleur (validation, autorisations) qui s'appuie sur les formulaires, les entités, "
            "les repositories et Twig pour le rendu.", body),
        Preformatted(
            "Requete HTTP\n"
            "   |\n"
            "   +-- public/index.php (front controller)\n"
            "   |\n"
            "   +-- Securite : pare-feu 'main' + access_control\n"
            "   |\n"
            "   +-- Controller --+-- Form (validation)\n"
            "   |                +-- Entity (modele + contraintes)\n"
            "   |                +-- Repository (requetes Doctrine)\n"
            "   |\n"
            "   +-- Twig (templates heritant de base.html.twig)", code),
        table([
            ["Dossier", "Contenu"],
            ["src/Controller", "Home, Security, Registration, Morceaux, Playlist, Profile, Contact"],
            ["src/Controller/Admin", "Dashboard et 5 contrôleurs CRUD EasyAdmin"],
            ["src/Entity", "User, Morceaux, Playlist, Genre, Contact"],
            ["src/Form", "6 formulaires typés (inscription, morceau, playlist, contact, profil, mot de passe)"],
            ["src/Repository", "Requêtes : recherche, propriétaire, compteurs"],
            ["src/Security", "LoginAuthenticator (email + mot de passe)"],
            ["templates", "Partials, CRUD, profil, sécurité, admin, pages d'erreur"],
            ["tests/Functional", "Suite de bout en bout sur base SQLite jetable"],
        ], [0.30, 0.70]),

        KeepTogether([
            Paragraph("5. Modèle de données", h1),
            table([
            ["Table", "Colonnes principales", "Relations"],
            ["user", "id, email (unique), roles, password, nom, prenom", "1-N playlist, 1-N morceaux"],
            ["genre", "id, nom_genre", "1-N morceaux"],
            ["morceaux", "id, nom_morceaux, artiste, date_sortie, image_name, music_name, genreid_id, user_id",
             "N-1 genre, N-1 user, N-N playlist"],
            ["playlist", "id, nom_playlist, nb_morceaux, userid_id", "N-1 user, N-N morceaux"],
            ["playlist_morceaux", "playlist_id, morceaux_id", "Table de jointure (cascade)"],
            ["contact", "id, prenom, email, subject, message, created_at", "—"],
            ], [0.18, 0.46, 0.36]),
        ]),

        KeepTogether([
            Paragraph("6. Sécurité", h1),
            table([
            ["Risque", "Mesure en place"],
            ["Accès et privilèges", "access_control explicite ; /admin réservé à ROLE_ADMIN ; plus d'édition publique des rôles"],
            ["Mots de passe", "Hachage auto (bcrypt/argon2) et re-hachage automatique"],
            ["Force brute", "login_throttling : 5 tentatives par 15 minutes"],
            ["CSRF et suppressions", "Jetons Symfony partout ; suppressions en POST uniquement"],
            ["IDOR", "isOwnedBy() / isEditableBy() vérifiés côté serveur, puis 403"],
            ["Uploads, XSS, cookies", "Contraintes MIME et taille, dossier media non exécuté, auto-échappement Twig, HttpOnly + SameSite"],
            ], [0.28, 0.72]),
        ]),

        Paragraph("7. Installation locale", h1),
        Preformatted(
            "# 1. Dependances et configuration locale\n"
            "composer install\n\n"
            "cp .env .env.local      # adapter DATABASE_URL / MAILER_DSN / APP_SECRET", code),
        Preformatted(
            "# 2. Base de donnees\n"
            "php bin/console doctrine:database:create\n"
            "php bin/console doctrine:migrations:migrate\n\n"
            "# 3. Donnees de demonstration (optionnel)\n"
            "php bin/console doctrine:fixtures:load\n\n"
            "# 4. Serveur de developpement\n"
            "php -S 0.0.0.0:8000 -t public", code),
        Paragraph(
            "Comptes de démonstration : <b>admin@melody.test</b> (administrateur), "
            "<b>user@melody.test</b> et <b>lea@melody.test</b> (utilisateurs), mot de passe "
            "<b>Password123!</b>.", body),

        Paragraph("8. Configuration", h1),
        table([
            ["Variable", "Description"],
            ["APP_ENV / APP_DEBUG", "Environnement (dev, prod, test) et mode debug"],
            ["APP_SECRET", "Clé de signature des sessions, jetons CSRF et cookies"],
            ["APP_URL", "URL publique de l'application"],
            ["DATABASE_URL", "DSN Doctrine (MySQL 8, charset utf8mb4)"],
            ["MAILER_DSN", "Transport email (SMTP en production, null://null en développement)"],
            ["MAILER_FROM", "Expéditeur des emails applicatifs"],
            ["CONTACT_TO_EMAIL", "Destinataire du formulaire de contact"],
        ], [0.30, 0.70]),

        Paragraph("9. Tests", h1),
        Paragraph(
            "La suite s'exécute sur une base SQLite jetable : aucun serveur MySQL n'est requis. "
            "9 tests et 24 assertions, tous au vert.", body),
        Preformatted("php bin/phpunit                 # suite complete\n"
                     "php bin/console lint:yaml config\n"
                     "php bin/console lint:twig templates", code),
        table([
            ["Test", "Vérification"],
            ["Page d'accueil publique", "Rendu du hero et statut 200"],
            ["Pages publiques", "/login, /register et /contact accessibles"],
            ["Redirection anonyme", "/morceaux renvoie vers /login"],
            ["Connexion valide", "Accès au catalogue après authentification"],
            ["Mot de passe erroné", "Message d'erreur affiché"],
            ["Création de morceau", "Formulaire, persistance et propriétaire"],
            ["Playlist d'autrui", "Réponse 403 (protection IDOR)"],
            ["Back-office", "403 pour ROLE_USER, 200 pour ROLE_ADMIN"],
            ["Formulaire de contact", "Message persisté et confirmation affichée"],
        ], [0.36, 0.64]),

        Paragraph("10. Déploiement Docker (production)", h1),
        Paragraph(
            "La pile fournie contient PHP-FPM (Symfony), nginx et MySQL 8, avec un volume partagé "
            "pour les fichiers publics et les uploads.", body),
        Preformatted(
            "# Variables de production (ne pas committer)\n"
            "cat > .env.prod <<'EOF'\n"
            "APP_SECRET=...\n"
            "APP_URL=https://melody.example.com\n"
            "MYSQL_DATABASE=melody\n"
            "MYSQL_USER=melody\n"
            "MYSQL_PASSWORD=...\n"
            "MAILER_DSN=smtp://user:pass@smtp.example.com:587\n"
            "EOF\n\n"
            "# Demarrage (migrations appliquees au boot)\n"
            "docker compose --env-file .env.prod up -d --build\n\n"
            "# Verifications\n"
            "docker compose logs -f app\n"
            "curl -I http://localhost:8080", code),
        Paragraph("Points d'attention en production", h2),
        Paragraph("• Placer l'application derrière un reverse proxy TLS (Traefik, Caddy, nginx, Cloudflare).", bullet),
        Paragraph("• Sauvegarder le volume MySQL et le volume des uploads.", bullet),
        Paragraph("• Configurer un vrai transport SMTP via MAILER_DSN.", bullet),
        Paragraph("• Restreindre /admin (VPN ou liste d'IP) si nécessaire.", bullet),
        Paragraph("• Surveiller var/log/prod.log (JSON sur stderr).", bullet),

        Paragraph("11. Interface et lecteur audio", h1),
        Paragraph("Le front a été reconstruit sans framework CSS : design system natif, thème sombre, "
                  "sprite SVG, pochettes générées et barre de lecture persistante sur toutes les pages.", body),
        table([
            ["Élément", "Mise en œuvre"],
            ["Design system", "public/assets/css/app.css : tokens, grille fluide, 3 paliers responsive"],
            ["Lecteur", "File d'attente, aléatoire, répétition, volume, raccourcis clavier"],
            ["Pochettes", "8 dégradés déterministes et mosaïques 2x2 pour les playlists"],
            ["Assets", "Sprite SVG inline, zéro CDN, zéro police distante"],
            ["Audio de démo", "12 pistes WAV générées par docs/tools/make_demo_audio.py"],
        ], [0.22, 0.78]),

        Paragraph("12. Refonte 2.0 — corrections majeures", h1),
        table([
            ["Problème initial", "Correction apportée"],
            ["encore_entry_link_tags sans WebpackEncore : toutes les pages plantaient",
             "Assets Bootstrap 5 auto-hébergés"],
            ["Extension Twig non autoloadable (namespace et PSR-4)", "Supprimée, responsive géré en CSS"],
            ["Morceaux::setImageFile écrivait un champ inexistant", "Corrigé"],
            ["User::getPassword() pouvait retourner null", "Signature ?string conforme à l'interface"],
            ["Suppression de morceau en GET sans CSRF", "POST, jeton CSRF et contrôle du propriétaire"],
            ["Formulaire de création sans isValid()", "Validation complète"],
            ["Templates appelant des routes inexistantes", "Routes unifiées morceaux.*"],
            ["Page de contact vide", "Page complète"],
            ["Fixtures invalides (arguments, flush manquant)", "Fixtures réécrites"],
            ["Email de contact avec From = visiteur", "MAILER_FROM + Reply-To"],
            ["access_control commenté : tout était public", "Matrice d'accès explicite"],
            ["Deux authentificateurs dont un TODO", "Un seul LoginAuthenticator"],
            ["/user exposait rôles et mots de passe", "/profil et back-office ROLE_ADMIN"],
            ["Docker en PostgreSQL pour des migrations MySQL", "Pile MySQL 8, nginx et PHP-FPM"],
            ["Symfony 6.1 en fin de vie", "Symfony 6.4 LTS"],
            ["EasyAdmin installé mais inutilisé", "Back-office complet"],
            ["Uploads en collision avec la route /morceaux/{id}", "Uploads sous /media/"],
            ["Validation métier quasi absente", "Contraintes Assert sur toutes les entités"],
        ], [0.52, 0.48]),

        Paragraph("13. Roadmap", h1),
        Paragraph("• Playlists publiques ou privées avec lien de partage.", bullet),
        Paragraph("• Lecteur audio persistant (lecture continue).", bullet),
        Paragraph("• API REST / JSON pour un client mobile.", bullet),
        Paragraph("• Recommandations par genre et historique d'écoute.", bullet),
        Paragraph("• CI GitHub Actions : tests et build Docker.", bullet),
        Paragraph("• Traductions EN / ES.", bullet),

        Paragraph("14. Licence", h1),
        Paragraph(
            "MIT License — Copyright (c) 2025 Alex Alkhatib. Projet personnel réalisé pour "
            "approfondir Symfony, Doctrine ORM et la conception d'applications web sécurisées.", body),
        Spacer(1, 8 * mm),
        Paragraph("Document généré depuis le README du projet (docs/tools/build_pdf.py).", small),
    ]

    doc.build(story)


if __name__ == "__main__":
    build()
    print(f"PDF genere : {OUT} ({OUT.stat().st_size // 1024} Ko)")
