<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/tmdb.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare(
        'UPDATE users SET name = :n, email = :e, phone = :p, bio = :b WHERE id = :id'
    );
    $stmt->execute([
        ':n' => $_POST['name'] ?? '',
        ':e' => $_POST['email'] ?? '',
        ':p' => $_POST['phone'] ?? '',
        ':b' => $_POST['bio'] ?? '',
        ':id' => $userId
    ]);
    header('Location: profile.php');
    exit;
}

$st = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$st->execute([':id' => $userId]);
$u = $st->fetch(PDO::FETCH_ASSOC);

// favourites
$sqlFavs = 'SELECT m.tmdb_id, m.title, m.poster_path, m.release_date
            FROM favorites f
            JOIN movies m ON m.id = f.movie_id
            WHERE f.user_id = :uid
            ORDER BY f.created_at DESC';
$stFavs = $pdo->prepare($sqlFavs);
$stFavs->execute([':uid' => $userId]);
$favs = $stFavs->fetchAll(PDO::FETCH_ASSOC);

// ratings
$sqlRatings = 'SELECT m.tmdb_id, m.title, m.poster_path, m.release_date, r.rating
               FROM ratings r
               JOIN movies m ON m.id = r.movie_id
               WHERE r.user_id = :uid
               ORDER BY r.updated_at DESC';
$stR = $pdo->prepare($sqlRatings);
$stR->execute([':uid' => $userId]);
$ratings = $stR->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile – MovieHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page">
<header class="topbar">
    <div class="topbar-left">
        <div class="logo">MovieHub</div>
        <nav class="nav">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="recommend.php" class="nav-link">Recommend me</a>
            <a href="profile.php" class="nav-link active">Profile</a>
            <a href="logout.php" class="nav-link">Logout</a>
        </nav>
    </div>
</header>

<main class="content profile">
    <section class="profile-card">
        <h1>Your profile</h1>
        <form method="post" class="profile-form">
            <label>
                Name
                <input type="text" name="name"
                       value="<?php echo htmlspecialchars($u['name'] ?? ''); ?>">
            </label>
            <label>
                Email
                <input type="email" name="email"
                       value="<?php echo htmlspecialchars($u['email'] ?? ''); ?>">
            </label>
            <label>
                Phone
                <input type="text" name="phone"
                       value="<?php echo htmlspecialchars($u['phone'] ?? ''); ?>">
            </label>
            <label>
                Bio
                <textarea name="bio" rows="3"><?php echo htmlspecialchars($u['bio'] ?? ''); ?></textarea>
            </label>
            <button class="btn-primary">Save</button>
        </form>
    </section>

    <section class="profile-favs">
        <h2>Your favourite movies</h2>
        <div class="masonry-grid">
            <?php foreach ($favs as $m): ?>
                <a href="movie.php?id=<?php echo (int)$m['tmdb_id']; ?>" class="card">
                    <?php if (!empty($m['poster_path'])): ?>
                        <img src="<?php echo htmlspecialchars(TMDB_IMAGE_BASE . $m['poster_path']); ?>" alt="">
                    <?php endif; ?>
                    <div class="card-overlay">
                        <h3><?php echo htmlspecialchars($m['title']); ?></h3>
                        <span><?php echo htmlspecialchars($m['release_date']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (!$favs): ?>
                <p>No favourites yet.</p>
            <?php endif; ?>
        </div>

        <h2>Your ratings</h2>
        <div class="masonry-grid">
            <?php foreach ($ratings as $m): ?>
                <a href="movie.php?id=<?php echo (int)$m['tmdb_id']; ?>" class="card">
                    <?php if (!empty($m['poster_path'])): ?>
                        <img src="<?php echo htmlspecialchars(TMDB_IMAGE_BASE . $m['poster_path']); ?>" alt="">
                    <?php endif; ?>
                    <div class="card-overlay">
                        <h3><?php echo htmlspecialchars($m['title']); ?></h3>
                        <span><?php echo htmlspecialchars($m['release_date']); ?></span>
                        <span>Rating: <?php echo (int)$m['rating']; ?>/5</span>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (!$ratings): ?>
                <p>No ratings yet.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
