<?php
// api/recommend_movie.php
require __DIR__ . '/_helpers.php';

header('Content-Type: application/json; charset=utf-8');

// verificăm că userul este logat; dacă nu, current_user_id() va da 401 și iese
$currentUserId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?? [];

$genreId    = (int)($payload['genreId'] ?? 0);
$yearFilter = $payload['yearFilter'] ?? 'any';

if ($genreId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing genreId']);
    exit;
}

// determinăm perioada
$today = date('Y-m-d');
$gte = null;
$lte = null;

switch ($yearFilter) {
    case 'before2000':
        $gte = '1900-01-01';
        $lte = '1999-12-31';
        break;
    case '2000_2010':
        $gte = '2000-01-01';
        $lte = '2010-12-31';
        break;
    case '2011_2020':
        $gte = '2011-01-01';
        $lte = '2020-12-31';
        break;
    case 'after2020':
        $gte = '2021-01-01';
        $lte = $today;
        break;
    case 'any':
    default:
        $gte = null;
        $lte = null;
        break;
}

$params = [
    'with_genres' => $genreId,
    'sort_by' => 'vote_average.desc',
    'include_adult' => 'false',
    'vote_count.gte' => 200,
];

// punem perioada doar dacă este setată
if ($gte !== null && $lte !== null) {
    $params['primary_release_date.gte'] = $gte;
    $params['primary_release_date.lte'] = $lte;
}

// alegem o pagină random 1–5 ca să nu tot vină aceleași filme
$params['page'] = rand(1, 5);

$data = tmdb_get('/discover/movie', $params);
$results = $data['results'] ?? [];

if (!$results) {
    // fallback: fără filtru de ani
    unset($params['primary_release_date.gte'], $params['primary_release_date.lte']);
    $params['page'] = 1;
    $data = tmdb_get('/discover/movie', $params);
    $results = $data['results'] ?? [];
}

if (!$results) {
    echo json_encode(['movie' => null]);
    exit;
}

// alegem un film random din listă
$movie = $results[array_rand($results)];

$response = [
    'tmdb_id'      => $movie['id'],
    'title'        => $movie['title'] ?? '',
    'poster_path'  => $movie['poster_path'] ?? null,
    'poster_url'   => !empty($movie['poster_path']) ? TMDB_IMAGE_BASE . $movie['poster_path'] : null,
    'overview'     => $movie['overview'] ?? '',
    'release_date' => $movie['release_date'] ?? '',
    'vote_average' => $movie['vote_average'] ?? null,
];

echo json_encode(['movie' => $response]);
