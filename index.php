<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/tmdb.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MovieHub – Discover</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page">
<header class="topbar">
    <div class="topbar-left">
        <div class="logo">MovieHub</div>
        <nav class="nav">
            <a href="index.php" class="nav-link active">Dashboard</a>
            <a href="recommend.php" class="nav-link">Recommend me</a>
            <a href="profile.php" class="nav-link">Profile</a>
        </nav>
    </div>
    <form id="searchForm" class="search">
        <input id="searchInput" type="text" placeholder="Search movies..." autocomplete="off">
        <button type="submit">Search</button>
    </form>
</header>

<main class="content">
    <section class="section-header">
        <h1>Trending movies</h1>
        <p class="subtitle">Click a poster to comment or add to favourites.</p>
    </section>

    <section id="grid" class="masonry-grid">
        <!-- cards injected by JS -->
    </section>

    <div id="loader" class="loader hidden">Loading...</div>
</main>

<script>
    const TMDB_IMAGE_BASE = "<?php echo htmlspecialchars(TMDB_IMAGE_BASE, ENT_QUOTES); ?>";
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
