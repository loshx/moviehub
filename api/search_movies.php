<?php
require __DIR__ . '/_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

if ($q === '') {
    $data = tmdb_get('/discover/movie', [
        'sort_by' => 'popularity.desc',
        'page' => $page,
    ]);
} else {
    $data = tmdb_get('/search/movie', [
        'query' => $q,
        'page' => $page,
        'include_adult' => 'false',
    ]);
}

$results = [];
foreach ($data['results'] ?? [] as $m) {
    if (empty($m['poster_path'])) {
        continue;
    }
    $results[] = [
        'id' => $m['id'],
        'title' => $m['title'] ?? '',
        'poster_url' => TMDB_IMAGE_BASE . $m['poster_path'],
        'poster_path' => $m['poster_path'],
        'overview' => $m['overview'] ?? '',
        'release_date' => $m['release_date'] ?? ''
    ];
}

echo json_encode([
    'results' => $results,
    'page' => $data['page'] ?? 1,
    'total_pages' => $data['total_pages'] ?? 1
]);
