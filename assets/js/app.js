// assets/js/app.js

// helper pentru API
async function jsonFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: { 'Content-Type': 'application/json' },
        ...options
    });
    if (!res.ok) throw new Error('Request failed');
    return await res.json();
}

/* ===================== DASHBOARD ===================== */

const gridEl = document.getElementById('grid');
const searchForm = document.getElementById('searchForm');
const searchInput = document.getElementById('searchInput');
const loader = document.getElementById('loader');

let currentPage = 1;
let currentQuery = '';

async function loadMovies(reset = false) {
    if (!gridEl) return;

    loader.classList.remove('hidden');

    if (reset) {
        gridEl.innerHTML = '';
        currentPage = 1;
    }

    try {
        const data = await jsonFetch(
            `api/search_movies.php?q=${encodeURIComponent(currentQuery)}&page=${currentPage}`
        );
        (data.results || []).forEach(renderMovieCard);
    } catch (e) {
        console.error(e);
    } finally {
        loader.classList.add('hidden');
    }
}

function renderMovieCard(movie) {
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML = `
        <img src="${movie.poster_url}" alt="">
        <div class="card-overlay">
            <h3>${movie.title}</h3>
            <span>${movie.release_date || ''}</span>
        </div>
    `;
    card.addEventListener('click', () => {
        window.location.href = `movie.php?id=${movie.id}`;
    });
    gridEl.appendChild(card);
}

if (searchForm && gridEl) {
    searchForm.addEventListener('submit', e => {
        e.preventDefault();
        currentQuery = searchInput.value.trim();
        loadMovies(true);
    });

    // prima încărcare
    loadMovies(true);

    // infinite scroll
    window.addEventListener('scroll', () => {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) {
            currentPage += 1;
            loadMovies(false);
        }
    });
}

/* ===================== FILM: FAV + COMENTARII + RATING ===================== */

const favBtn = document.getElementById('favBtn');

if (typeof MOVIE_DATA !== 'undefined' && favBtn) {
    // toggle favourite
    favBtn.addEventListener('click', async () => {
        try {
            const payload = { movie: MOVIE_DATA };
            const res = await jsonFetch('api/toggle_favorite.php', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
            favBtn.textContent =
                res.status === 'added' ? 'Remove from favourites' : 'Add to favourites';
        } catch (e) {
            console.error(e);
        }
    });

    // comentarii
    const commentForm = document.getElementById('commentForm');
    const commentInput = document.getElementById('commentInput');
    const commentList = document.getElementById('commentList');

    async function loadComments() {
        try {
            const data = await jsonFetch(`api/get_comments.php?tmdb_id=${MOVIE_DATA.id}`);
            commentList.innerHTML = '';
            (data.comments || []).forEach(c => {
                const li = document.createElement('li');
                li.className = 'comment-item';
                li.innerHTML = `
                    <div class="comment-header">
                        <strong>
                          <a href="user.php?id=${c.user_id}" class="comment-user">
                            ${c.name || 'User'}
                          </a>
                        </strong>
                        <span>${c.created_at}</span>
                    </div>
                    <p>${c.content}</p>
                `;
                commentList.appendChild(li);
            });
        } catch (e) {
            console.error(e);
        }
    }

    if (commentForm) {
        commentForm.addEventListener('submit', async e => {
            e.preventDefault();
            const text = commentInput.value.trim();
            if (!text) return;
            try {
                await jsonFetch('api/add_comment.php', {
                    method: 'POST',
                    body: JSON.stringify({
                        content: text,
                        movie: MOVIE_DATA
                    })
                });
                commentInput.value = '';
                await loadComments();
            } catch (e) {
                console.error(e);
            }
        });
    }

    loadComments();

    // rating
    const ratingWidget = document.getElementById('ratingWidget');
    const ratingSummary = document.getElementById('ratingSummary');

    function paintStars(value) {
        if (!ratingWidget) return;
        const stars = ratingWidget.querySelectorAll('.rating-star');
        stars.forEach(star => {
            const v = Number(star.dataset.value);
            star.classList.toggle('filled', v <= value);
        });
    }

    if (ratingWidget) {
        let currentRating = Number(ratingWidget.dataset.current || 0);
        paintStars(currentRating);

        ratingWidget.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', async () => {
                const value = Number(star.dataset.value || 0);
                if (!value) return;

                try {
                    const res = await jsonFetch('api/add_rating.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            movie: MOVIE_DATA,
                            rating: value
                        })
                    });
                    currentRating = res.user_rating;
                    paintStars(currentRating);

                    if (res.count && res.avg_rating) {
                        ratingSummary.textContent =
                            `Average ${res.avg_rating}/5 from ${res.count} ratings`;
                    } else {
                        ratingSummary.textContent = 'No ratings yet.';
                    }
                } catch (e) {
                    console.error(e);
                }
            });
        });
    }
}
