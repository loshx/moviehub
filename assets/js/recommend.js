// assets/js/recommend.js

// select / deselect pentru "pill" buttons
function setupPillToggle(row, multi = true) {
    const buttons = Array.from(row.querySelectorAll('.pill'));
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            if (!multi) {
                buttons.forEach(b => b.classList.remove('pill-selected'));
                btn.classList.add('pill-selected');
            } else {
                btn.classList.toggle('pill-selected');
            }
        });
    });
}

// init
const moodRow = document.getElementById('moodRow');
const eraRow  = document.getElementById('eraRow');
const btnGenerate = document.getElementById('recoGenerate');
const btnRefresh  = document.getElementById('recoRefresh');
const resultBox   = document.getElementById('recoResult');
const onlyHighRated = document.getElementById('onlyHighRated');
const onlyEnglish   = document.getElementById('onlyEnglish');

if (moodRow && eraRow && btnGenerate && resultBox) {
    setupPillToggle(moodRow, true);
    setupPillToggle(eraRow, false);

    let lastFilters = null;

    async function fetchMovie(filters) {
        const res = await fetch('api/recommend_movie.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(filters)
        });
        if (!res.ok) throw new Error('Request failed');
        return await res.json();
    }

    function getFilters() {
        const moods = Array.from(moodRow.querySelectorAll('.pill.pill-selected'))
            .map(btn => parseInt(btn.dataset.genre, 10))
            .filter(Boolean);

        const eraBtn = eraRow.querySelector('.pill.pill-selected');
        const era = eraBtn ? eraBtn.dataset.era : '90s';

        return {
            moods,
            era,
            onlyHighRated: !!onlyHighRated.checked,
            onlyEnglish: !!onlyEnglish.checked
        };
    }

    function renderMovie(movie) {
        if (!movie) {
            resultBox.innerHTML = `
                <div class="reco-placeholder">
                    <p>N-am găsit nimic pe filtrele tale. Mai relaxează criteriile și încearcă din nou.</p>
                </div>
            `;
            btnRefresh.disabled = true;
            return;
        }

        resultBox.innerHTML = `
            <article class="reco-movie-card">
                <div class="reco-movie-poster">
                    <img src="${movie.poster_url}" alt="">
                </div>
                <div class="reco-movie-meta">
                    <h2>${movie.title}</h2>
                    <p class="reco-movie-sub">
                        ${movie.release_date ? movie.release_date.substring(0, 4) : '—'}
                        ${movie.vote_average ? ` • ★ ${movie.vote_average.toFixed(1)}` : ''}
                    </p>
                    <p class="reco-movie-overview">${movie.overview || 'No description.'}</p>
                </div>
            </article>
        `;
        btnRefresh.disabled = false;
    }

    async function handleGenerate(isRefresh = false) {
        try {
            btnGenerate.disabled = true;
            btnRefresh.disabled = true;
            resultBox.classList.add('reco-loading');
            resultBox.innerHTML = `<div class="reco-placeholder"><p>Se caută un film bun pentru tine...</p></div>`;

            if (!isRefresh) {
                lastFilters = getFilters();
            }
            if (!lastFilters) {
                lastFilters = getFilters();
            }

            const data = await fetchMovie(lastFilters);
            renderMovie(data.movie || null);
        } catch (e) {
            console.error(e);
            resultBox.innerHTML = `<div class="reco-placeholder"><p>Eroare la recomandare. Reîncearcă.</p></div>`;
        } finally {
            btnGenerate.disabled = false;
            resultBox.classList.remove('reco-loading');
        }
    }

    btnGenerate.addEventListener('click', () => handleGenerate(false));
    btnRefresh.addEventListener('click', () => handleGenerate(true));
}
