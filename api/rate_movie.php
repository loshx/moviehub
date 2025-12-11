<?php
require __DIR__ . '/_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$userId = current_user_id();
$payload = json_decode(file_get_contents('php://input'), true);

$rating = (int)($payload['rating'] ?? 0);
$movie = $payload['movie'] ?? null;

if ($rating < 1 || $rating > 5 || !$movie) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid rating']);
    exit;
}

$movieId = ensure_movie($pdo, $movie);

// Check if user already rated
$stmt = $pdo->prepare('SELECT rating FROM ratings WHERE user_id = :u AND movie_id = :m');
$stmt->execute([':u' => $userId, ':m' => $movieId]);

if ($stmt->fetch()) {
    // Update rating
    $upd = $pdo->prepare('UPDATE ratings SET rating = :r WHERE user_id = :u AND movie_id = :m');
    $upd->execute([':r' => $rating, ':u' => $userId, ':m' => $movieId]);
} else {
    // Insert new
    $ins = $pdo->prepare('INSERT INTO ratings (user_id, movie_id, rating) VALUES (:u, :m, :r)');
    $ins->execute([':u' => $userId, ':m' => $movieId, ':r' => $rating]);
}

// Return new average
$avgQ = $pdo->prepare('SELECT AVG(rating) as avg_rating FROM ratings WHERE movie_id = :m');
$avgQ->execute([':m' => $movieId]);
$avg = round($avgQ->fetchColumn(), 2);

echo json_encode([
    'status' => 'ok',
    'average' => $avg
]);
