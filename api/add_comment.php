<?php
require __DIR__ . '/_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = current_user_id();
$content = trim($payload['content'] ?? '');
$movie = $payload['movie'] ?? null;

if ($content === '' || !$movie) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing data']);
    exit;
}

$movieId = ensure_movie($pdo, $movie);

$stmt = $pdo->prepare(
    'INSERT INTO comments (user_id, movie_id, content) VALUES (:u, :m, :c)'
);
$stmt->execute([
    ':u' => $userId,
    ':m' => $movieId,
    ':c' => $content
]);

echo json_encode([
    'status' => 'ok',
    'comment' => [
        'id' => $pdo->lastInsertId(),
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ]
]);
