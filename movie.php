<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/tmdb.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

$tmdbId = (int)($_GET['id'] ?? 0);
if ($tmdbId <= 0) {
    http_response_code(400);
    echo "Missing id";
    exit;
}

// mic helper pentru TMDB
function tmdb_get_simple($endpoint, $params = []) {
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
    curl_close($ch);
    return json_decode($result, true) ?? [];
}

$movie = tmdb_get_simple("/movie/$tmdbId");

$title      = $movie['title'] ?? 'Movie';
$posterPath = $movie['poster_path'] ?? '';
$poster     = $posterPath ? TMDB_IMAGE_BASE . $posterPath : '';
$overview   = $movie['overview'] ?? '';
$release    = $movie['release_date'] ?? '';

// rating info inițial (din DB, dacă există)
$avgRating   = 0.0;
$ratingCount = 0;
$userRating  = 0;
$movieRowId  = null;

// găsim filmul în DB
$stmt = $pdo->prepare('SELECT id FROM movies WHERE tmdb_id = :t');
$stmt->execute([':t' => $tmdbId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $movieRowId = (int)$row['id'];

    // medie + count
    $agg = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM ratings WHERE movie_id = :m');
    $agg->execute([':m' => $movieRowId]);
    $a = $agg->fetch(PDO::FETCH_ASSOC);
    if ($a && $a['avg_rating'] !== null) {
        $avgRating   = round((float)$a['avg_rating'], 1);
        $ratingCount = (int)$a['cnt'];
    }

    // rating-ul userului curent
    $ur = $pdo->prepare('SELECT rating FROM ratings WHERE movie_id = :m AND user_id = :u');
    $ur->execute([':m' => $movieRowId, ':u' => $userId]);
    if ($r = $ur->fetch(PDO::FETCH_ASSOC)) {
        $userRating = (int)$r['rating'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?> – MovieHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page">
<header class="topbar">
    <div class="topbar-left">
        <div class="logo">MovieHub</div>
        <nav class="nav">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="recommend.php" class="nav-link">Recommend me</a>
            <a href="profile.php" class="nav-link">Profile</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </nav>
    </div>
</header>

<main class="content movie-detail">
    <section class="movie-hero">
        <?php if ($poster): ?>
            <img src="<?php echo htmlspecialchars($poster); ?>" class="movie-poster-lg" alt="">
        <?php endif; ?>
        <div class="movie-meta">
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p class="movie-release"><?php echo htmlspecialchars($release); ?></p>
            <p class="movie-overview"><?php echo htmlspecialchars($overview); ?></p>
            <button id="favBtn" class="btn-primary"
                    data-tmdb-id="<?php echo $tmdbId; ?>"
                    data-title="<?php echo htmlspecialchars($title, ENT_QUOTES); ?>"
                    data-poster="<?php echo htmlspecialchars($posterPath, ENT_QUOTES); ?>"
                    data-overview="<?php echo htmlspecialchars($overview, ENT_QUOTES); ?>"
                    data-release="<?php echo htmlspecialchars($release, ENT_QUOTES); ?>">
                Add / remove favourite
            </button>

            <section class="rating-section">
                <h2>Your rating</h2>
                <div id="ratingWidget"
                     class="rating-widget"
                     data-current="<?php echo (int)$userRating; ?>">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <span class="rating-star <?php echo $i <= $userRating ? 'filled' : ''; ?>"
                              data-value="<?php echo $i; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <p id="ratingSummary" class="rating-summary">
                    <?php if ($ratingCount > 0): ?>
                        Average <?php echo htmlspecialchars($avgRating); ?>/5 from
                        <?php echo htmlspecialchars($ratingCount); ?> ratings
                    <?php else: ?>
                        No ratings yet.
                    <?php endif; ?>
                </p>
            </section>
        </div>
    </section>

    <section class="comments">
        <h2>Discussion</h2>
        <form id="commentForm" class="comment-form">
            <textarea id="commentInput" rows="3" placeholder="Share your thoughts..."></textarea>
            <button type="submit" class="btn-secondary">Post</button>
        </form>
        <ul id="commentList" class="comment-list">
            <!-- comments by JS -->
        </ul>
    </section>
</main>

<script>
    const MOVIE_DATA = {
        id: <?php echo $tmdbId; ?>,
        title: "<?php echo htmlspecialchars($title, ENT_QUOTES); ?>",
        poster_path: "<?php echo htmlspecialchars($posterPath, ENT_QUOTES); ?>",
        overview: "<?php echo htmlspecialchars($overview, ENT_QUOTES); ?>",
        release_date: "<?php echo htmlspecialchars($release, ENT_QUOTES); ?>",
    };
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
