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
    <title>Recommend me – MovieHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page">
<header class="topbar">
    <div class="topbar-left">
        <div class="logo">MovieHub</div>
        <nav class="nav">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="recommend.php" class="nav-link active">Recommend me</a>
            <a href="profile.php" class="nav-link">Profile</a>
        </nav>
    </div>
</header>

<main class="content recommend-page">
    <section class="section-header">
        <h1>Smart recommendations</h1>
        <p class="subtitle">Alege genul și perioada. Îți propunem un film random pe gustul tău. Dacă nu îți place, apasă refresh.</p>
    </section>

    <div class="recommend-layout">
        <!-- partea stângă: întrebări -->
        <section class="recommend-questions">
            <div class="recommend-block">
                <h2>1. Alege genul</h2>
                <div class="chip-group" id="genreGroup">
                    <button type="button" class="chip chip-genre" data-genre-id="35">Comedy</button>
                    <button type="button" class="chip chip-genre" data-genre-id="27">Horror</button>
                    <button type="button" class="chip chip-genre" data-genre-id="53">Thriller</button>
                    <button type="button" class="chip chip-genre" data-genre-id="28">Action</button>
                    <button type="button" class="chip chip-genre" data-genre-id="18">Drama</button>
                    <button type="button" class="chip chip-genre" data-genre-id="10749">Romance</button>
                    <button type="button" class="chip chip-genre" data-genre-id="878">Sci-Fi</button>
                    <button type="button" class="chip chip-genre" data-genre-id="16">Animation</button>
                </div>
            </div>

            <div class="recommend-block">
                <h2>2. Alege perioada</h2>
                <div class="chip-group" id="yearGroup">
                    <button type="button" class="chip chip-year" data-year-filter="before2000">clasic &lt; 2000</button>
                    <button type="button" class="chip chip-year" data-year-filter="2000_2010">2000 – 2010</button>
                    <button type="button" class="chip chip-year" data-year-filter="2011_2020">2011 – 2020</button>
                    <button type="button" class="chip chip-year" data-year-filter="after2020">&gt; 2020</button>
                    <button type="button" class="chip chip-year" data-year-filter="any">nu contează</button>
                </div>
            </div>

            <div class="recommend-actions">
                <button type="button" id="recommendBtn" class="btn-primary" disabled>
                    Recommend a movie
                </button>
                <button type="button" id="refreshBtn" class="btn-secondary" disabled>
                    Refresh
                </button>
                <p id="recommendStatus" class="recommend-status"></p>
            </div>
        </section>

        <!-- partea dreaptă: rezultatul -->
        <section class="recommend-result">
            <div id="recommendCard" class="recommend-result-card recommend-result-card--empty">
                <div class="recommend-empty">
                    <h2>No recommendation yet</h2>
                    <p>Selectează un gen și o perioadă, apoi apasă “Recommend a movie”.</p>
                </div>
            </div>
        </section>
    </div>
</main>

<script>
    let selectedGenreId = null;
    let selectedYearFilter = null;
    let currentMovie = null;

    const genreButtons = document.querySelectorAll('.chip-genre');
    const yearButtons  = document.querySelectorAll('.chip-year');
    const recommendBtn = document.getElementById('recommendBtn');
    const refreshBtn   = document.getElementById('refreshBtn');
    const statusEl     = document.getElementById('recommendStatus');
    const cardEl       = document.getElementById('recommendCard');

    genreButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            genreButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedGenreId = parseInt(btn.dataset.genreId, 10);
            updateActionsState();
        });
    });

    yearButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            yearButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedYearFilter = btn.dataset.yearFilter;
            updateActionsState();
        });
    });

    function updateActionsState() {
        const ready = !!selectedGenreId && !!selectedYearFilter;
        recommendBtn.disabled = !ready;
        refreshBtn.disabled   = !ready || !currentMovie;
        if (!ready) {
            statusEl.textContent = '';
        }
    }

    async function fetchRecommendation() {
        if (!selectedGenreId || !selectedYearFilter) return;
        statusEl.textContent = 'Searching a good match...';
        cardEl.classList.add('recommend-result-card--loading');

        try {
            const res = await fetch('api/recommend_movie.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    genreId: selectedGenreId,
                    yearFilter: selectedYearFilter
                })
            });
            if (!res.ok) throw new Error('Server error');
            const data = await res.json();
            if (!data.movie) {
                statusEl.textContent = 'No movie found for these filters. Try other options.';
                cardEl.classList.remove('recommend-result-card--loading');
                return;
            }
            currentMovie = data.movie;
            renderMovieCard(currentMovie);
            statusEl.textContent = 'Here is a match for you.';
            refreshBtn.disabled = false;
        } catch (e) {
            console.error(e);
            statusEl.textContent = 'Error while recommending. Try again.';
        } finally {
            cardEl.classList.remove('recommend-result-card--loading');
        }
    }

    function renderMovieCard(movie) {
        cardEl.classList.remove('recommend-result-card--empty');
        const year = movie.release_date ? movie.release_date.slice(0, 4) : '';
        const vote = movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A';
        const poster = movie.poster_url || '';

        cardEl.innerHTML = `
            <div class="recommend-result-inner">
                <div class="recommend-poster-wrapper">
                    ${poster ? `<img src="${poster}" alt="">` : `<div class="recommend-poster-placeholder">No image</div>`}
                </div>
                <div class="recommend-info">
                    <h2>${movie.title}</h2>
                    <div class="recommend-meta">
                        <span>${year}</span>
                        <span>Rating TMDB: ${vote}</span>
                    </div>
                    <p class="recommend-overview">${movie.overview || 'No overview available.'}</p>
                    <div class="recommend-buttons">
                        <button type="button" class="btn-primary" id="favFromRecommendBtn">
                            Add / remove favourite
                        </button>
                        <a href="https://www.themoviedb.org/movie/${movie.tmdb_id}" class="btn-secondary" target="_blank" rel="noopener">
                            Open on TMDB
                        </a>
                    </div>
                </div>
            </div>
        `;

        const favBtn = document.getElementById('favFromRecommendBtn');
        favBtn.addEventListener('click', async () => {
            try {
                const payload = {
                    movie: {
                        id: movie.tmdb_id,
                        title: movie.title,
                        poster_path: movie.poster_path,
                        overview: movie.overview,
                        release_date: movie.release_date
                    }
                };
                const res = await fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (!res.ok) throw new Error('Request failed');
                const data = await res.json();
                favBtn.textContent = data.status === 'added'
                    ? 'Remove from favourites'
                    : 'Add / remove favourite';
            } catch (e) {
                console.error(e);
            }
        });
    }

    recommendBtn.addEventListener('click', () => {
        fetchRecommendation();
    });

    refreshBtn.addEventListener('click', () => {
        fetchRecommendation();
    });

    updateActionsState();
</script>
</body>
</html>
