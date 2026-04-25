<?php
// config/tmdb.php
// Loads TMDB credentials from a local, untracked config file or environment variables.

$localConfig = __DIR__ . '/tmdb.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}

if (!defined('TMDB_API_KEY')) {
    define('TMDB_API_KEY', getenv('TMDB_API_KEY') ?: '');
}

if (!defined('TMDB_READ_TOKEN')) {
    define('TMDB_READ_TOKEN', getenv('TMDB_READ_TOKEN') ?: '');
}

define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/w500');
