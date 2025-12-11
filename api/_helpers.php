<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../config/tmdb.php';

function current_user_id(): int {
    if (isset($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    // For API calls we just return 401 JSON
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

function ensure_movie(PDO $pdo, array $movie): int {
    $stmt = $pdo->prepare('SELECT id FROM movies WHERE tmdb_id = :tmdb');
    $stmt->execute([':tmdb' => $movie['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return (int)$row['id'];
    }

    $insert = $pdo->prepare(
        'INSERT INTO movies (tmdb_id, title, poster_path, overview, release_date)
         VALUES (:tmdb_id, :title, :poster_path, :overview, :release_date)'
    );
    $insert->execute([
        ':tmdb_id' => $movie['id'],
        ':title' => $movie['title'] ?? $movie['name'] ?? '',
        ':poster_path' => $movie['poster_path'] ?? '',
        ':overview' => $movie['overview'] ?? '',
        ':release_date' => $movie['release_date'] ?? $movie['first_air_date'] ?? ''
    ]);

    return (int)$pdo->lastInsertId();
}

function tmdb_get(string $endpoint, array $params = []): array {
    $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . TMDB_READ_TOKEN,
            'Content-Type: application/json;charset=utf-8'
        ]
    ]);
    $result = curl_exec($ch);
    if ($result === false) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'TMDB request failed']);
        exit;
    }
    curl_close($ch);
    return json_decode($result, true) ?? [];
}
