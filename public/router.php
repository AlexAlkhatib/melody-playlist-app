<?php

/**
 * Routeur pour le serveur PHP intégré (développement / démonstration).
 *
 *   php -S 0.0.0.0:3000 -t public public/router.php
 *
 * Les fichiers réellement présents dans public/ sont servis par le serveur
 * intégré (bons types MIME pour CSS, JS, images, audio) ; tout le reste est
 * confié au noyau Symfony.
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__.$path;

if ('/' !== $path && is_file($file)) {
    return false;
}

require __DIR__.'/index.php';
