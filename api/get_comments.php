<?php
require __DIR__ . '/_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$tmdbId = (int)($_GET['tmdb_id'] ?? 0);
if ($tmdbId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing tmdb_id']);
    exit;
}

// găsim filmul în DB dacă există
$stmt = $pdo->prepare('SELECT id FROM movies WHERE tmdb_id = :t');
$stmt->execute([':t' => $tmdbId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['comments' => []]);
    exit;
}

$movieId = (int)$row['id'];

$q = $pdo->prepare(
    'SELECT c.content, c.created_at, u.name, u.id AS user_id
     FROM comments c
     JOIN users u ON u.id = c.user_id
     WHERE c.movie_id = :m
     ORDER BY c.created_at DESC'
);
$q->execute([':m' => $movieId]);

echo json_encode(['comments' => $q->fetchAll(PDO::FETCH_ASSOC)]);
