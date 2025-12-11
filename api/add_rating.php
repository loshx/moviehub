<?php
// api/add_rating.php
require __DIR__ . '/_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = current_user_id();

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$movie   = $payload['movie'] ?? null;
$rating  = (int)($payload['rating'] ?? 0);

if (!$movie || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// creează / găsește filmul în tabela movies
$movieId = ensure_movie($pdo, $movie);

// upsert rating
$sql = "
INSERT INTO ratings (user_id, movie_id, rating)
VALUES (:u, :m, :r)
ON CONFLICT(user_id, movie_id)
DO UPDATE SET rating = excluded.rating,
              updated_at = CURRENT_TIMESTAMP
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':u' => $userId,
    ':m' => $movieId,
    ':r' => $rating
]);

// recalc medie + count
$agg = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM ratings WHERE movie_id = :m');
$agg->execute([':m' => $movieId]);
$row = $agg->fetch(PDO::FETCH_ASSOC);

$avg  = $row && $row['avg_rating'] !== null ? round((float)$row['avg_rating'], 1) : 0.0;
$cnt  = $row ? (int)$row['cnt'] : 0;

echo json_encode([
    'status'      => 'ok',
    'user_rating' => $rating,
    'avg_rating'  => $avg,
    'count'       => $cnt,
]);
