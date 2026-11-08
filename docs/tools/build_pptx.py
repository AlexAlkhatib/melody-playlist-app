#!/usr/bin/env python3
"""Génère la présentation PowerPoint de Melody (python-pptx)."""

from pathlib import Path

from pptx import Presentation
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.util import Emu, Inches, Pt

OUT = Path(__file__).resolve().parents[1] / "Melody-Presentation.pptx"

PRIMARY = RGBColor(0x6D, 0x28, 0xD9)
DARK = RGBColor(0x12, 0x08, 0x1F)
ACCENT = RGBColor(0xEC, 0x48, 0x99)
LIGHT = RGBColor(0xF4, 0xF1, 0xFB)
WHITE = RGBColor(0xFF, 0xFF, 0xFF)
INK = RGBColor(0x1F, 0x29, 0x33)
GREY = RGBColor(0x5B, 0x64, 0x72)

W, H = Inches(13.333), Inches(7.5)


def blank(prs):
    return prs.slides.add_slide(prs.slide_layouts[6])


def rect(slide, x, y, w, h, color, line=None):
    from pptx.enum.shapes import MSO_SHAPE

    shape = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, x, y, w, h)
    shape.fill.solid()
    shape.fill.fore_color.rgb = color
    if line is None:
        shape.line.fill.background()
    else:
        shape.line.color.rgb = line
    shape.shadow.inherit = False
    return shape


def text(slide, x, y, w, h, value, size=18, color=INK, bold=False, align=PP_ALIGN.LEFT,
         anchor=MSO_ANCHOR.TOP, font="Calibri", line_spacing=1.15):
    box = slide.shapes.add_textbox(x, y, w, h)
    frame = box.text_frame
    frame.word_wrap = True
    frame.vertical_anchor = anchor
    paragraph = frame.paragraphs[0]
    paragraph.alignment = align
    paragraph.line_spacing = line_spacing
    run = paragraph.add_run()
    run.text = value
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.color.rgb = color
    run.font.name = font
    return box


def background(slide, color):
    fill = slide.background.fill
    fill.solid()
    fill.fore_color.rgb = color


def header(slide, title, subtitle=None, index=None):
    background(slide, WHITE)
    rect(slide, 0, 0, Inches(0.22), H, PRIMARY)
    text(slide, Inches(0.75), Inches(0.55), Inches(11.2), Inches(0.8), title,
         size=32, color=DARK, bold=True)
    if subtitle:
        text(slide, Inches(0.78), Inches(1.3), Inches(11.4), Inches(0.5), subtitle,
             size=14, color=GREY)
    rect(slide, Inches(0.78), Inches(1.85), Inches(1.6), Inches(0.06), ACCENT)
    if index is not None:
        text(slide, Inches(11.8), Inches(6.85), Inches(1.0), Inches(0.35), f"{index:02d}",
             size=11, color=GREY, align=PP_ALIGN.RIGHT)


def bullets(slide, items, x=Inches(0.85), y=Inches(2.1), w=Inches(11.6), h=Inches(4.6),
            size=17, gap=8):
    box = slide.shapes.add_textbox(x, y, w, h)
    frame = box.text_frame
    frame.word_wrap = True
    first = True
    for item in items:
        if isinstance(item, tuple):
            label, detail = item
        else:
            label, detail = item, None
        paragraph = frame.paragraphs[0] if first else frame.add_paragraph()
        first = False
        paragraph.space_after = Pt(gap)
        run = paragraph.add_run()
        run.text = f"▸  {label}"
        run.font.size = Pt(size)
        run.font.bold = True
        run.font.color.rgb = DARK
        run.font.name = "Calibri"
        if detail:
            paragraph2 = frame.add_paragraph()
            paragraph2.space_after = Pt(gap + 6)
            run2 = paragraph2.add_run()
            run2.text = f"     {detail}"
            run2.font.size = Pt(size - 3)
            run2.font.color.rgb = GREY
            run2.font.name = "Calibri"
    return box


def table(slide, rows, x, y, widths, height=Inches(3.6), size=12):
    shape = slide.shapes.add_table(len(rows), len(rows[0]), x, y,
                                   sum(widths, Emu(0)), height)
    tbl = shape.table
    for col, width in enumerate(widths):
        tbl.columns[col].width = width
    for row_index, row in enumerate(rows):
        for col_index, value in enumerate(row):
            cell = tbl.cell(row_index, col_index)
            cell.text = str(value)
            cell.margin_left = Inches(0.08)
            cell.margin_right = Inches(0.08)
            cell.vertical_anchor = MSO_ANCHOR.MIDDLE
            cell.fill.solid()
            cell.fill.fore_color.rgb = PRIMARY if row_index == 0 else (
                WHITE if row_index % 2 else LIGHT)
            for paragraph in cell.text_frame.paragraphs:
                for run in paragraph.runs:
                    run.font.size = Pt(size)
                    run.font.name = "Calibri"
                    run.font.bold = row_index == 0
                    run.font.color.rgb = WHITE if row_index == 0 else INK
    return tbl


def card(slide, x, y, w, h, value, label, color=PRIMARY):
    rect(slide, x, y, w, h, LIGHT)
    rect(slide, x, y, w, Inches(0.07), color)
    text(slide, x, y + Inches(0.45), w, Inches(0.9), value, size=40, color=color,
         bold=True, align=PP_ALIGN.CENTER)
    text(slide, x, y + Inches(1.35), w, Inches(0.5), label, size=13, color=GREY,
         align=PP_ALIGN.CENTER)


def build() -> None:
    prs = Presentation()
    prs.slide_width, prs.slide_height = W, H

    # 1 — Couverture
    s = blank(prs)
    background(s, DARK)
    rect(s, Inches(8.4), Inches(-1.6), Inches(6), Inches(6), PRIMARY)
    rect(s, Inches(-1.8), Inches(5.6), Inches(4), Inches(4), ACCENT)
    text(s, Inches(0.9), Inches(2.3), Inches(9.5), Inches(1.4), "MELODY", size=76,
         color=WHITE, bold=True)
    text(s, Inches(0.95), Inches(3.7), Inches(9.0), Inches(1.0),
         "Application musicale collaborative", size=30, color=RGBColor(0xE9, 0xD5, 0xFF))
    text(s, Inches(0.98), Inches(4.6), Inches(9.0), Inches(0.6),
         "Symfony 6.4 LTS · PHP 8.2+ · MySQL 8 · Docker", size=15,
         color=RGBColor(0xC4, 0xB5, 0xFD))
    text(s, Inches(0.98), Inches(6.5), Inches(8.0), Inches(0.4),
         "Version 2.1 · Alex Alkhatib · MIT License", size=12, color=RGBColor(0xA7, 0x8B, 0xFA))

    # 2 — Sommaire
    s = blank(prs)
    header(s, "Sommaire", "Ce que nous allons voir", 2)
    bullets(s, [
        ("Le projet en 30 secondes", "Objectif, public, promesse"),
        ("Fonctionnalités clés", "Comptes, catalogue, playlists, contact, back-office"),
        ("Architecture et modèle de données", "MVC en couches, 6 tables, 3 migrations"),
        ("Sécurité", "Authentification, CSRF, autorisations, uploads"),
        ("Refonte 2.0", "18 corrections, dépendances à jour, tests"),
        ("Déploiement", "Docker, nginx, migrations automatiques"),
    ], size=18, gap=10)

    # 3 — Le projet
    s = blank(prs)
    header(s, "Le projet en 30 secondes", "Playlists musicales collaboratives", 3)
    text(s, Inches(0.85), Inches(2.1), Inches(11.4), Inches(1.4),
         "Melody permet d'alimenter un catalogue musical commun (titre, artiste, genre, pochette, "
         "audio) puis de composer, organiser et écouter ses propres playlists.",
         size=20, color=INK, line_spacing=1.3)
    bullets(s, [
        ("Pour les utilisateurs", "Créer un compte, ajouter des morceaux, bâtir des playlists"),
        ("Pour la communauté", "Un catalogue partagé, consultable et écoutable"),
        ("Pour les administrateurs", "Un back-office pour gérer contenu, comptes et messages"),
    ], y=Inches(3.6), size=17, gap=6)

    # 4 — Fonctionnalités
    s = blank(prs)
    header(s, "Fonctionnalités clés", "Cinq domaines, une expérience cohérente", 4)
    items = [
        ("🔐  Comptes", "Inscription, connexion, remember-me, anti-force brute, profil, mot de passe"),
        ("🎵  Catalogue", "Upload pochette + audio, recherche, pagination, lecteur intégré"),
        ("📝  Playlists", "CRUD, sélection multiple, compteur automatique, accès propriétaire"),
        ("✉️  Contact", "Formulaire validé, message persisté et envoyé par email"),
        ("🛡️  Back-office", "Tableau de bord, CRUD, messages, réservé aux administrateurs"),
    ]
    y = Inches(2.15)
    for title, detail in items:
        rect(s, Inches(0.85), y, Inches(11.6), Inches(0.82), LIGHT)
        text(s, Inches(1.05), y + Inches(0.09), Inches(2.8), Inches(0.5), title, size=15,
             bold=True, color=PRIMARY)
        text(s, Inches(3.6), y + Inches(0.11), Inches(8.6), Inches(0.6), detail, size=13,
             color=INK)
        y += Inches(0.95)

    # 5 — Architecture
    s = blank(prs)
    header(s, "Architecture", "MVC en couches, responsabilités séparées", 5)
    layers = [
        ("HTTP", "public/index.php — front controller"),
        ("Sécurité", "Pare-feu « main » + access_control"),
        ("Contrôleurs", "Validation, autorisations, redirections"),
        ("Métier", "Entités + contraintes, repositories, formulaires"),
        ("Rendu", "Twig (base.html.twig) + Bootstrap 5"),
    ]
    y = Inches(2.1)
    for i, (label, detail) in enumerate(layers):
        rect(s, Inches(0.85), y, Inches(2.6), Inches(0.62), PRIMARY)
        text(s, Inches(0.95), y + Inches(0.13), Inches(2.4), Inches(0.4), label, size=14,
             bold=True, color=WHITE)
        rect(s, Inches(3.45), y, Inches(8.5), Inches(0.62), LIGHT)
        text(s, Inches(3.6), y + Inches(0.14), Inches(8.2), Inches(0.4), detail, size=13,
             color=INK)
        y += Inches(0.78)
    text(s, Inches(0.85), Inches(6.25), Inches(11.6), Inches(0.6),
         "src/ : Controller (+Admin), Entity, Form, Repository, Security  ·  tests/Functional",
         size=12, color=GREY)

    # 6 — Interface et lecteur
    s = blank(prs)
    header(s, "Interface et lecteur audio", "Design system natif, écoute continue", 6)
    text(s, Inches(0.85), Inches(1.85), Inches(11.6), Inches(0.9),
         "Front reconstruit sans framework CSS : thème sombre, sidebar, cartes, pochettes générées "
         "et barre de lecture persistante sur toutes les pages.", size=14)
    card(s, Inches(0.85), Inches(3.05), Inches(3.6), Inches(2.1), "0", "dépendance front")
    card(s, Inches(4.75), Inches(3.05), Inches(3.6), Inches(2.1), "8", "palettes de pochettes")
    card(s, Inches(8.65), Inches(3.05), Inches(3.6), Inches(2.1), "31", "templates Twig")

    # 7 — Lecteur persistant
    s = blank(prs)
    header(s, "Lecteur audio persistant", "La musique ne s'arrête plus", 7)
    text(s, Inches(0.85), Inches(1.85), Inches(11.6), Inches(0.9),
         "Lecture continue depuis n'importe quelle liste, contrôle au clavier, reprise de la "
         "dernière écoute et historique local.", size=14)
    card(s, Inches(0.85), Inches(3.05), Inches(2.7), Inches(2.1), "File", "d'attente par page")
    card(s, Inches(3.75), Inches(3.05), Inches(2.7), Inches(2.1), "⌘", "raccourcis clavier")
    card(s, Inches(6.65), Inches(3.05), Inches(2.7), Inches(2.1), "12", "pistes de démo WAV")
    card(s, Inches(9.55), Inches(3.05), Inches(2.7), Inches(2.1), "1", "lecteur global")

    # 8 — Modèle de données
    s = blank(prs)
    header(s, "Modèle de données", "6 tables, relations ManyToOne et ManyToMany", 8)
    table(s, [
        ["Table", "Rôle", "Relations"],
        ["user", "Comptes, rôles, mot de passe haché", "1-N playlist, 1-N morceaux"],
        ["genre", "Référentiel des genres musicaux", "1-N morceaux"],
        ["morceaux", "Catalogue : titre, artiste, audio, pochette", "N-1 genre, N-1 user, N-N playlist"],
        ["playlist", "Playlists personnelles", "N-1 user, N-N morceaux"],
        ["playlist_morceaux", "Table de jointure (cascade)", "N-N"],
        ["contact", "Messages du formulaire de contact", "—"],
    ], Inches(0.85), Inches(2.05),
        [Inches(2.5), Inches(4.9), Inches(4.2)], height=Inches(3.5), size=13)
    text(s, Inches(0.85), Inches(5.85), Inches(11.6), Inches(0.6),
         "3 migrations versionnées · contraintes d'intégrité · suppression en cascade propre",
         size=13, color=GREY)

    # 7 — Sécurité
    s = blank(prs)
    header(s, "Sécurité", "Défense en profondeur", 9)
    rows = [
        ["Risque", "Mesure"],
        ["Accès non autorisé", "access_control explicite (public, ROLE_USER, ROLE_ADMIN)"],
        ["Mots de passe", "Hachage auto (bcrypt/argon2) + re-hachage automatique"],
        ["Force brute", "5 tentatives / 15 minutes (login_throttling)"],
        ["CSRF", "Jetons sur tous les formulaires et sur chaque suppression"],
        ["IDOR", "isOwnedBy() / isEditableBy() -> 403"],
        ["Uploads", "MIME + taille, nommage aléatoire, dossier non exécutable"],
        ["XSS / cookies", "Auto-échappement Twig · HttpOnly, SameSite=Lax, Secure"],
    ]
    table(s, rows, Inches(0.85), Inches(2.05), [Inches(3.2), Inches(8.4)],
          height=Inches(4.0), size=13)

    # 8 — Back-office
    s = blank(prs)
    header(s, "Back-office administrateur", "EasyAdmin 4, accessible sur /admin", 10)
    bullets(s, [
        ("Tableau de bord", "5 indicateurs : morceaux, playlists, genres, utilisateurs, messages"),
        ("Catalogue", "CRUD complet sur les morceaux et les genres"),
        ("Communauté", "Playlists et utilisateurs ; mots de passe hachés automatiquement"),
        ("Messages", "Consultation en lecture seule des demandes de contact"),
        ("Contrôle d'accès", "Réservé à ROLE_ADMIN, vérifié par access_control et attribut IsGranted"),
    ], y=Inches(2.15), size=17, gap=9)

    # 9 — Refonte 2.0
    s = blank(prs)
    header(s, "Refonte 2.0", "18 corrections, du bloquant à la qualité", 11)
    rows = [
        ["Avant", "Après"],
        ["encore_entry_link_tags sans Encore : pages cassées", "Assets Bootstrap auto-hébergés"],
        ["Extension Twig non autoloadable", "Supprimée, responsive en CSS"],
        ["Suppression de morceau en GET sans CSRF", "POST + jeton + propriétaire"],
        ["access_control commenté : tout public", "Matrice d'accès explicite"],
        ["/user exposait rôles et mots de passe", "/profil + back-office ROLE_ADMIN"],
        ["Symfony 6.1 (fin de vie)", "Symfony 6.4 LTS"],
        ["Docker PostgreSQL / migrations MySQL", "Pile MySQL 8 + nginx + PHP-FPM"],
        ["EasyAdmin installé mais inutilisé", "Back-office complet"],
        ["Validation métier quasi absente", "Contraintes Assert partout"],
    ]
    table(s, rows, Inches(0.85), Inches(2.0), [Inches(5.9), Inches(5.7)],
          height=Inches(4.3), size=11.5)

    # 10 — Qualité et tests
    s = blank(prs)
    header(s, "Qualité et tests", "Vérifié, pas supposé", 12)
    card(s, Inches(0.85), Inches(2.1), Inches(2.6), Inches(2.0), "9", "tests fonctionnels")
    card(s, Inches(3.65), Inches(2.1), Inches(2.6), Inches(2.0), "24", "assertions")
    card(s, Inches(6.45), Inches(2.1), Inches(2.6), Inches(2.0), "0", "test en échec", ACCENT)
    card(s, Inches(9.25), Inches(2.1), Inches(3.2), Inches(2.0), "SQLite", "base jetable en CI")
    bullets(s, [
        ("Couverture", "Pages publiques, connexion, création de morceau, IDOR, admin, contact"),
        ("Lints", "lint:twig, lint:yaml, doctrine:schema:validate, composer validate"),
        ("Dépréciations", "Zéro dépréciation provenant du code applicatif"),
    ], y=Inches(4.5), size=15, gap=6)

    # 11 — Stack
    s = blank(prs)
    header(s, "Stack technique", "Des versions supportées", 13)
    table(s, [
        ["Couche", "Technologie", "Version"],
        ["Runtime", "PHP", "8.2+ (Docker 8.3)"],
        ["Framework", "Symfony", "6.4 LTS"],
        ["Persistance", "Doctrine ORM + Migrations", "2.20 / 3.x"],
        ["Templates", "Twig", "3.8"],
        ["Admin", "EasyAdmin", "4.29"],
        ["Uploads", "VichUploaderBundle", "2.9"],
        ["UI", "Design system CSS natif + JS", "sans dépendance"],
        ["Base", "MySQL / MariaDB", "8.0+"],
        ["Tests", "PHPUnit", "9.6"],
    ], Inches(0.85), Inches(1.95), [Inches(2.6), Inches(5.6), Inches(4.4)],
        height=Inches(4.6), size=12.5)

    # 12 — Installation
    s = blank(prs)
    header(s, "Installation locale", "En cinq commandes", 14)
    rect(s, Inches(0.85), Inches(2.05), Inches(11.6), Inches(3.9), DARK)
    text(s, Inches(1.2), Inches(2.25), Inches(11.0), Inches(3.5),
         "composer install\n\n"
         "cp .env .env.local            # DATABASE_URL / MAILER_DSN / APP_SECRET\n\n"
         "php bin/console doctrine:database:create\n"
         "php bin/console doctrine:migrations:migrate\n"
         "php bin/console doctrine:fixtures:load      # démonstration\n\n"
         "php -S 0.0.0.0:8000 -t public     #  http://localhost:8000",
         size=15, color=RGBColor(0xE9, 0xD5, 0xFF), font="Consolas", line_spacing=1.25)
    text(s, Inches(0.85), Inches(6.15), Inches(11.6), Inches(0.6),
         "Comptes de démonstration : admin@melody.test (admin) · user@melody.test · mot de passe Password123!",
         size=13, color=GREY)

    # 13 — Déploiement
    s = blank(prs)
    header(s, "Déploiement Docker", "Production reproductible", 15)
    bullets(s, [
        ("Une commande", "docker compose --env-file .env.prod up -d --build"),
        ("Trois services", "PHP-FPM (Symfony) · nginx · MySQL 8"),
        ("Migrations automatiques", "L'entrypoint attend la base puis applique les migrations"),
        ("Volumes persistants", "db-data (MySQL) et public-data (assets + uploads)"),
        ("Durcissement", "OPcache, display_errors=Off, uploads limités, /media non exécuté"),
        ("Reverse proxy TLS", "Traefik, Caddy, nginx ou Cloudflare devant l'application"),
    ], y=Inches(2.1), size=16, gap=7)

    # 14 — Roadmap
    s = blank(prs)
    header(s, "Roadmap", "Prochaines étapes", 16)
    bullets(s, [
        ("Playlists publiques / privées", "Lien de partage et visibilité configurable"),
        ("Lecteur audio persistant", "Lecture continue pendant la navigation"),
        ("API REST / JSON", "Préparer un client mobile"),
        ("Recommandations", "Suggestions par genre et historique d'écoute"),
        ("CI GitHub Actions", "Tests et build Docker à chaque push"),
        ("Internationalisation", "Traductions EN / ES"),
    ], y=Inches(2.1), size=17, gap=9)

    # 15 — Chiffres clés
    s = blank(prs)
    header(s, "Le projet en chiffres", "Une base saine pour la suite", 17)
    card(s, Inches(0.85), Inches(2.2), Inches(2.6), Inches(2.1), "6", "entités métier")
    card(s, Inches(3.65), Inches(2.2), Inches(2.6), Inches(2.1), "11", "contrôleurs", ACCENT)
    card(s, Inches(6.45), Inches(2.2), Inches(2.6), Inches(2.1), "3", "migrations")
    card(s, Inches(9.25), Inches(2.2), Inches(3.2), Inches(2.1), "26", "templates Twig")
    bullets(s, [
        ("Code organisé", "Contrôleurs fins, règles métier dans les entités, requêtes dans les repositories"),
        ("Prêt à déployer", "Dockerfile, compose, entrypoint, Makefile, documentation PDF"),
    ], y=Inches(4.7), size=15, gap=6)

    # 16 — Merci
    s = blank(prs)
    background(s, DARK)
    rect(s, Inches(9.2), Inches(4.6), Inches(5.5), Inches(5.5), PRIMARY)
    rect(s, Inches(-1.5), Inches(-1.5), Inches(4), Inches(4), ACCENT)
    text(s, Inches(1.0), Inches(2.6), Inches(9.5), Inches(1.2), "Merci !", size=64,
         color=WHITE, bold=True)
    text(s, Inches(1.05), Inches(3.9), Inches(9.0), Inches(1.2),
         "Melody — playlists musicales collaboratives", size=22,
         color=RGBColor(0xE9, 0xD5, 0xFF))
    text(s, Inches(1.05), Inches(4.7), Inches(9.0), Inches(0.8),
         "github.com/AlexAlkhatib · MIT License", size=14,
         color=RGBColor(0xC4, 0xB5, 0xFD))

    prs.save(OUT)


if __name__ == "__main__":
    build()
    print(f"PPTX genere : {OUT} ({OUT.stat().st_size // 1024} Ko)")
