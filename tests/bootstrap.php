<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$varDir = dirname(__DIR__).'/var';
if (!is_dir($varDir)) {
    mkdir($varDir, 0777, true);
}

// Le cache de test doit être reconstruit à chaque exécution : le code change souvent.
$testCache = $varDir.'/cache/test';
if (is_dir($testCache) && !getenv('MELODY_KEEP_TEST_CACHE')) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testCache, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $file) {
        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
    }
    @rmdir($testCache);
}
