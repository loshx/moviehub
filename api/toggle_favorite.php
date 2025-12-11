<?php
require __DIR__ . '/_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$userId = current_user_id();

if (empty($payload['movie'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing movie']);
    exit;
}

$movie = $payload['movie'];
$movieId = ensure_movie($pdo, $movie);

$check = $pdo->prepare(
    'SELECT 1 FROM favorites WHERE user_id = :u AND movie_id = :m'
);
$check->execute([':u' => $userId, ':m' => $movieId]);

if ($check->fetch()) {
    $del = $pdo->prepare(
        'DELETE FROM favorites WHERE user_id = :u AND movie_id = :m'
    );
    $del->execute([':u' => $userId, ':m' => $movieId]);
    $status = 'removed';
} else {
    $ins = $pdo->prepare(
        'INSERT INTO favorites (user_id, movie_id) VALUES (:u, :m)'
    );
    $ins->execute([':u' => $userId, ':m' => $movieId]);
    $status = 'added';
}

echo json_encode(['status' => $status]);
