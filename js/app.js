/* ============================================
   VibeSync - Festival Assistant
   Main Application JavaScript (PHP/MySQL backend)
   ============================================ */

// ============================================
// API HELPERS (with static fallback for Vercel / GitHub Pages)
// ============================================
const Api = {
    async post(url, payload = {}) {
        const isStatic = window.location.pathname.endsWith('.html') || 
                         window.location.protocol === 'file:' || 
                         window.location.hostname.includes('vercel.app') ||
                         window.location.hostname.includes('github.io');

        if (isStatic) {
            return this.mock(url, payload);
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });

            if (res.status === 401) {
                showToast('Session expired. Please log in again.', 'warning');
                setTimeout(() => { window.location.href = 'login.html'; }, 1200);
                return { ok: false, error: 'unauthenticated' };
            }

            if (!res.ok) {
                return this.mock(url, payload);
            }

            const data = await res.json().catch(() => null);
            if (!data) return this.mock(url, payload);
            if (!data.ok) {
                showToast(data.error || 'Something went wrong', 'error');
            }
            return data;
        } catch (err) {
            return this.mock(url, payload);
        }
    },

    mock(url, payload) {
        if (url.includes('toggle-favorite')) {
            const id = String(payload.artist_id);
            const isFav = State.favorites.has(id);
            if (isFav) State.favorites.delete(id);
            else State.favorites.add(id);
            try {
                localStorage.setItem('vibesync_favs', JSON.stringify([...State.favorites]));
            } catch (_) {}
            showToast(!isFav ? 'Added to favorites!' : 'Removed from favorites', 'success');
            return { ok: true, is_favorite: !isFav };
        }
        if (url.includes('toggle-schedule')) {
            const id = String(payload.performance_id);
            const isSch = State.scheduled.has(id);
            if (isSch) State.scheduled.delete(id);
            else State.scheduled.add(id);
            try {
                localStorage.setItem('vibesync_sch', JSON.stringify([...State.scheduled]));
            } catch (_) {}
            showToast(!isSch ? 'Added to your lineup!' : 'Removed from lineup', 'success');
            return { ok: true, is_scheduled: !isSch };
        }
        if (url.includes('update-setting')) {
            try {
                const settings = JSON.parse(localStorage.getItem('vibesync_settings') || '{}');
                settings[payload.setting] = payload.value;
                localStorage.setItem('vibesync_settings', JSON.stringify(settings));
            } catch (_) {}
            showToast('Settings updated', 'success');
            return { ok: true };
        }
        if (url.includes('update-profile')) {
            try {
                localStorage.setItem('vibesync_profile', JSON.stringify(payload));
            } catch (_) {}
            showToast('Profile updated!', 'success');
            return { ok: true };
        }
        if (url.includes('add-genre')) {
            showToast('Genre added!', 'success');
            return { ok: true, id: Date.now() };
        }
        if (url.includes('remove-genre')) {
            showToast('Genre removed', 'info');
            return { ok: true };
        }
        if (url.includes('stage-info')) {
            const stageMap = {
                1: { id: 1, name: 'Main Stage', description: 'The biggest stage, headliners only', icon: 'graphic_eq',
                     now_playing: { artist_name: 'Charlotte de Witte', genre: 'Techno', start_time: '23:30', end_time: '01:00', image_url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDe-qY3LOvkiUHaM5yUia8qGBl3zrf_E00L8RVF-0G0ls2KJzJFDmQcy0mrUGwd8MTx7k9oY3jvEk6Mcr2Dbb_96EF3Npe1xQL7esaTYV8nXZZgESbBpnGidNnAZGLCA_qagI1Sf6nJOLwGM933uQXVHje0SZaHfVzb5apjSkO-TuTgWFy2y3Ta5rSXE0qt4pABJcvtNbuVF5Gc7cMpmL_Z_4jWE3RE-Z2hh89rqEo28aV_7OPiv52iCivUlMO6_B81c8y4y1HrPgM' },
                     upcoming: [{ artist_name: 'The Glitch Mob', genre: 'Electronic', start_time: '18:00', end_time: '19:30' }] },
                2: { id: 2, name: 'Neon Garden', description: 'Outdoor stage with light installations', icon: 'speaker',
                     now_playing: { artist_name: 'BICEP', genre: 'Electronic', start_time: '21:00', end_time: '22:30', image_url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCcNQeZ8_lg0R7a96p1nIiEGVvrO_LWOaIqG5URz-BJu6FeBv4S6S0FdjrtocKG0RTo8MiMI4fOUkX3jWWrQLQ1oV4KEP59TDNvaMlnZOJ0EXJCNzMHO5l2a_9efAJpHoeR2yXA_e69n-17sSkGc48ivanrEHjqJcDzxfCLdFRTmC4wIgtPhUueee6oWVYP1N6yZ_wl0L-bEDo3tDB8JPEEUikURGlF_gPJmEF3a7T8mYT75BxAUfHbJIe988mVawAL7UPt0OljFl4' },
                     upcoming: [{ artist_name: 'The Midnight', genre: 'Synthwave', start_time: '22:45', end_time: '00:00' }] },
                3: { id: 3, name: 'Bass Arena', description: 'Heavy bass and dubstep cathedral', icon: 'subwoofer',
                     now_playing: { artist_name: 'Digital Echoes', genre: 'Bass Music', start_time: '21:00', end_time: '22:30' },
                     upcoming: [{ artist_name: 'Bass Phantom', genre: 'Dubstep', start_time: '16:00', end_time: '17:30' }] },
                4: { id: 4, name: 'Circuit Grounds', description: 'Tech-house & techno warehouse', icon: 'electric_bolt',
                     now_playing: { artist_name: 'Peggy Gou', genre: 'House', start_time: '22:15', end_time: '23:45', image_url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCscqzXeE0KOnhHEl5EnsodD3o21EGnBwtMUDpVQiIQwwzpXucqCOMxJYefm21wE5f77Uk375Ji7eWkLyF_dYGkb71IVvhmZsBdB_Aac2cXeg824M5CMovaiPaxJ6xhNVMVQh8uNv3TGGj4SudrNQ06_McYOHWBVM8T5CVePy2-phzU46SsXPRyONIJDw5CxMsPOyd_sU8fcvWdwxd1Y_svIg3-MAZw9B90GMWE93E86jIl451UAN11ImIRSnjDD2pxa-x2KVeWHvE' },
                     upcoming: [] },
                5: { id: 5, name: 'Quantum Valley', description: 'Melodic techno in a forest setting', icon: 'forest',
                     now_playing: { artist_name: 'Tale Of Us', genre: 'Melodic Techno', start_time: '20:45', end_time: '22:15', image_url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAZ-s0_gOx-Wtbtep7G_VdouZptbXY-KiMZWyaeWfZoz1oUEi217bxGmETnGLA2CHDxisdZ-IoOW7h4BofixWjl4Rh7pLH8rOy2tZnBSJj9el58Mz6jtB_VH-FGAusg3_nqWHP5OzITIxVA_CfE7hA-B15eqmGv_UwJdmvvTKGBWMIoUgkJBIwLLRBU4DMUuUhbhGD7OA4FU77iSnzMfiuyD6dCSYO9dt6QIvkN9--HE8hmbC5s1H_SFK_NCX3ZScNoFLhnTG1XSgY' },
                     upcoming: [{ artist_name: 'Aurora Wave', genre: 'Ambient Techno', start_time: '00:30', end_time: '02:00' }] },
            };
            const data = stageMap[payload.stage_id] || stageMap[1];
            return { ok: true, stage: data, now_playing: data.now_playing, upcoming: data.upcoming };
        }
        if (url.includes('search-users')) {
            const q = (payload.q || '').toLowerCase();
            const allUsers = [
                { id: 2, name: 'Maya Lin', email: 'maya@vibesync.app', avatar_url: '', status: 'friends' },
                { id: 3, name: 'Liam Vance', email: 'liam@vibesync.app', avatar_url: '', status: 'friends' },
                { id: 4, name: 'Elena Rostova', email: 'elena@vibesync.app', avatar_url: '', status: 'friends' },
                { id: 5, name: 'Sam Taylor', email: 'sam@vibesync.app', avatar_url: '', status: 'incoming' },
                { id: 7, name: 'Chloe Becker', email: 'chloe@vibesync.app', avatar_url: '', status: 'none' },
                { id: 8, name: 'Jordan Rivera', email: 'jordan@vibesync.app', avatar_url: '', status: 'none' },
            ];
            const matches = allUsers.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
            return { ok: true, users: matches };
        }
        if (url.includes('send-friend-request')) {
            showToast('Friend request sent!', 'success');
            return { ok: true };
        }
        if (url.includes('respond-friend-request')) {
            showToast(payload.action === 'accept' ? 'Friend request accepted!' : 'Request removed', 'info');
            return { ok: true };
        }
        return { ok: true };
    }
};

// ============================================
// LOCAL STATE (hydrated from data attributes & localStorage)
// ============================================
const State = {
    favorites: new Set(),    // numeric artist_id strings
    scheduled: new Set(),    // numeric performance_id strings

    hydrate() {
        const main = document.querySelector('main[data-favorites], main[data-scheduled]');
        if (main) {
            try {
                const favs = JSON.parse(main.dataset.favorites || '[]');
                favs.forEach(id => this.favorites.add(String(id)));
            } catch (_) {}
            try {
                const sch = JSON.parse(main.dataset.scheduled || '[]');
                sch.forEach(id => this.scheduled.add(String(id)));
            } catch (_) {}
        }
        // Load stored favorites and schedule from localStorage
        try {
            const storedFavs = JSON.parse(localStorage.getItem('vibesync_favs') || '[]');
            storedFavs.forEach(id => this.favorites.add(String(id)));
        } catch (_) {}
        try {
            const storedSch = JSON.parse(localStorage.getItem('vibesync_sch') || '[]');
            storedSch.forEach(id => this.scheduled.add(String(id)));
        } catch (_) {}
    },

    isFavorite(artistId) { return this.favorites.has(String(artistId)); },
    isScheduled(performanceId) { return this.scheduled.has(String(performanceId)); },
};

// ============================================
// TOAST NOTIFICATIONS
// ============================================
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const iconMap = { success: 'check_circle', info: 'info', warning: 'warning', error: 'error' };

    toast.innerHTML = `
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">${iconMap[type] || 'info'}</span>
        <span class="toast-message"></span>
    `;
    toast.querySelector('.toast-message').textContent = message;

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2800);
}

// ============================================
// NAVIGATION ACTIVE STATE
// ============================================
function setActiveNav() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const pageMap = {
        'index.php': '', 'index.html': '', '': '',
        'home.php': 'home', 'home.html': 'home',
        'map.php': 'map', 'map.html': 'map',
        'schedule.php': 'schedule', 'schedule.html': 'schedule',
        'artists.php': 'artists', 'artists.html': 'artists',
        'queues.php': 'queues', 'queues.html': 'queues',
        'profile.php': 'profile', 'profile.html': 'profile',
        'friends.php': 'friends', 'friends.html': 'friends',
    };
    const activeId = pageMap[currentPage] || '';

    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        const icon = item.querySelector('.material-symbols-outlined');
        if (icon) icon.style.fontVariationSettings = "'FILL' 0";
        if (item.dataset.page === activeId) {
            item.classList.add('active');
            if (icon) icon.style.fontVariationSettings = "'FILL' 1";
        }
    });
}

// ============================================
// MODAL SYSTEM
// ============================================
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeAllModals() {
    document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    document.body.style.overflow = '';
}

// ============================================
// VISUAL HELPERS
// ============================================
function paintFavoriteButton(btn, isFav) {
    btn.classList.toggle('favorited', isFav);
    const icon = btn.querySelector('.material-symbols-outlined');
    if (icon) {
        icon.textContent = isFav ? 'favorite' : 'favorite_border';
        icon.style.fontVariationSettings = isFav ? "'FILL' 1" : "'FILL' 0";
    }
    const label = btn.querySelector('.schedule-label');
    if (label && btn.dataset.favoriteBtnPivot !== undefined) {
        label.textContent = isFav ? 'Favorited' : 'Favorite';
    }
}

function paintScheduleButton(btn, isScheduled) {
    btn.classList.toggle('scheduled', isScheduled);
    const label = btn.querySelector('.schedule-label');
    if (label) label.textContent = isScheduled ? 'Scheduled' : 'Schedule';
    const icon = btn.querySelector('.material-symbols-outlined');
    if (icon && btn.classList.contains('schedule-toggle-btn')) {
        icon.textContent = isScheduled ? 'event_available' : 'calendar_add_on';
    }
}

// ============================================
// ARTIST DETAIL MODAL
// ============================================
function showArtistDetail(artist) {
    const modal = document.getElementById('artist-modal');
    if (!modal) return;

    const artistId      = artist.artist_id || artist.id;
    const performanceId = artist.performance_id || null;

    const isFav       = artistId      ? State.isFavorite(artistId)      : false;
    const isScheduled = performanceId ? State.isScheduled(performanceId) : false;

    modal.querySelector('.modal-artist-name').textContent  = artist.name  || '';
    modal.querySelector('.modal-artist-genre').textContent = artist.genre || '';
    modal.querySelector('.modal-artist-stage').textContent = artist.stage || '';
    modal.querySelector('.modal-artist-time').textContent  = artist.time  || '';

    const img = modal.querySelector('.modal-artist-img');
    if (img) {
        if (artist.image) {
            img.src = artist.image;
            img.style.display = '';
        } else {
            img.removeAttribute('src');
            img.style.display = 'none';
        }
    }

    const favBtn = modal.querySelector('.modal-fav-btn');
    if (favBtn) {
        favBtn.dataset.artistId = artistId || '';
        paintFavoriteButton(favBtn, isFav);
    }

    const scheduleBtn = modal.querySelector('.modal-schedule-btn');
    if (scheduleBtn) {
        scheduleBtn.dataset.artist = JSON.stringify(artist);
        if (performanceId) {
            scheduleBtn.style.display = '';
            scheduleBtn.disabled = false;
            scheduleBtn.textContent = isScheduled ? 'In Schedule' : 'Add to Schedule';
            scheduleBtn.classList.toggle('btn-secondary', isScheduled);
            scheduleBtn.classList.toggle('btn-primary', !isScheduled);
        } else {
            scheduleBtn.style.display = 'none';
        }
    }

    openModal('artist-modal');
}

// ============================================
// FAVORITE BUTTONS
// ============================================
function initFavoriteButtons() {
    document.querySelectorAll('.favorite-btn, [data-favorite-btn-pivot]').forEach(btn => {
        if (btn.dataset.favoriteBound === '1') return;
        btn.dataset.favoriteBound = '1';

        // Initial paint based on the rendered data-artist-id (may be empty for modal btn).
        const initialId = btn.dataset.artistId;
        if (initialId && State.isFavorite(initialId)) {
            paintFavoriteButton(btn, true);
        }

        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            e.preventDefault();
            // Re-read at click time — the modal button gets its id only after a card click.
            const artistId = btn.dataset.artistId;
            if (!artistId) return;
            if (btn.dataset.busy === '1') return;
            btn.dataset.busy = '1';

            const wasFav = State.isFavorite(artistId);
            paintFavoriteButton(btn, !wasFav);
            btn.style.transform = 'scale(1.3)';
            setTimeout(() => { btn.style.transform = ''; }, 200);

            const result = await Api.post('api/toggle-favorite.php', { artist_id: Number(artistId) });
            btn.dataset.busy = '0';

            if (!result.ok) {
                paintFavoriteButton(btn, wasFav);
                return;
            }

            if (result.is_favorite) State.favorites.add(String(artistId));
            else State.favorites.delete(String(artistId));

            paintFavoriteButton(btn, result.is_favorite);

            document.querySelectorAll(`.favorite-btn[data-artist-id="${artistId}"], [data-favorite-btn-pivot][data-artist-id="${artistId}"]`)
                .forEach(other => paintFavoriteButton(other, result.is_favorite));

            showToast(result.is_favorite ? 'Added to favorites' : 'Removed from favorites', 'success');
        });
    });
}

// ============================================
// SCHEDULE BUTTONS
// ============================================
function initScheduleButtons() {
    document.querySelectorAll('.schedule-toggle-btn').forEach(btn => {
        if (btn.dataset.scheduleBound === '1') return;
        btn.dataset.scheduleBound = '1';

        const performanceId = btn.dataset.performanceId;
        if (!performanceId) return;

        if (State.isScheduled(performanceId)) paintScheduleButton(btn, true);

        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            e.preventDefault();
            if (btn.dataset.busy === '1') return;
            btn.dataset.busy = '1';

            const wasScheduled = State.isScheduled(performanceId);
            paintScheduleButton(btn, !wasScheduled);

            const result = await Api.post('api/toggle-schedule.php', { performance_id: Number(performanceId) });
            btn.dataset.busy = '0';

            if (!result.ok) {
                paintScheduleButton(btn, wasScheduled);
                return;
            }

            if (result.is_scheduled) State.scheduled.add(String(performanceId));
            else State.scheduled.delete(String(performanceId));

            paintScheduleButton(btn, result.is_scheduled);

            const artistName = btn.dataset.artistName || 'Performance';
            showToast(
                result.is_scheduled ? `${artistName} added to schedule` : `${artistName} removed from schedule`,
                result.is_scheduled ? 'success' : 'info'
            );
        });
    });

    const modalScheduleBtn = document.querySelector('.modal-schedule-btn');
    if (modalScheduleBtn && modalScheduleBtn.dataset.bound !== '1') {
        modalScheduleBtn.dataset.bound = '1';
        modalScheduleBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const artistData = modalScheduleBtn.dataset.artist;
            if (!artistData) return;
            let artist;
            try { artist = JSON.parse(artistData); } catch (_) { return; }
            const performanceId = artist.performance_id;
            if (!performanceId) return;

            const result = await Api.post('api/toggle-schedule.php', { performance_id: Number(performanceId) });
            if (!result.ok) return;

            if (result.is_scheduled) {
                State.scheduled.add(String(performanceId));
                showToast(`${artist.name} added to schedule`, 'success');
            } else {
                State.scheduled.delete(String(performanceId));
                showToast(`${artist.name} removed from schedule`, 'info');
            }

            document.querySelectorAll(`.schedule-toggle-btn[data-performance-id="${performanceId}"]`)
                .forEach(b => paintScheduleButton(b, result.is_scheduled));

            closeAllModals();
        });
    }
}

// ============================================
// SETTINGS TOGGLES
// ============================================
function initToggles() {
    document.querySelectorAll('.toggle').forEach(toggle => {
        const key = toggle.dataset.setting;
        if (!key || toggle.dataset.bound === '1') return;
        toggle.dataset.bound = '1';

        toggle.addEventListener('change', async () => {
            const result = await Api.post('api/update-setting.php', {
                key,
                value: toggle.checked ? '1' : '0',
            });
            if (!result.ok) {
                toggle.checked = !toggle.checked;
                return;
            }
            showToast(
                `${formatLabel(key)} ${toggle.checked ? 'enabled' : 'disabled'}`,
                'info'
            );
        });
    });
}

function formatLabel(key) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// ============================================
// FILTER CHIPS
// ============================================
function initFilterChips() {
    document.querySelectorAll('.chip-group').forEach(group => {
        const chips = group.querySelectorAll('.chip');
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => {
                    c.classList.remove('chip-active');
                    c.classList.add('chip-inactive');
                });
                chip.classList.remove('chip-inactive');
                chip.classList.add('chip-active');

                const filter = chip.textContent.trim();
                const target = group.dataset.filterTarget;
                if (target) filterCards(target, filter);
            });
        });
    });
}

function filterCards(targetSelector, filter) {
    const cards = document.querySelectorAll(targetSelector);
    const f = filter.toLowerCase();
    cards.forEach(card => {
        const tags = (card.dataset.tags || '').toLowerCase();
        if (f === 'all' || f.startsWith('all ') || (tags && tags.includes(f))) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// ============================================
// TAB TOGGLE
// ============================================
function initTabToggles() {
    document.querySelectorAll('[data-tab-group]').forEach(group => {
        const tabs = group.querySelectorAll('[data-tab]');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('tab-active'));
                tab.classList.add('tab-active');

                const tabId = tab.dataset.tab;
                const groupName = group.dataset.tabGroup;
                document.querySelectorAll(`[data-tab-content="${groupName}"]`).forEach(content => {
                    content.style.display = content.dataset.tabId === tabId ? '' : 'none';
                });
            });
        });
    });
}

// ============================================
// DAY SELECTOR
// ============================================
function initDaySelector() {
    document.querySelectorAll('.day-selector').forEach(selector => {
        const days = selector.querySelectorAll('.day-option');
        days.forEach(day => {
            day.addEventListener('click', () => {
                days.forEach(d => d.classList.remove('day-active'));
                day.classList.add('day-active');
            });
        });
    });
}

// ============================================
// SEARCH INPUT
// ============================================
function initSearch() {
    document.querySelectorAll('[data-search-input]').forEach(input => {
        const targetSelector = input.dataset.searchInput;
        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const items = document.querySelectorAll(targetSelector);
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
}

// ============================================
// ANIMATIONS
// ============================================
function initAnimations() {
    document.querySelectorAll('.animate-on-scroll').forEach((item, index) => {
        item.style.animationDelay = `${index * 0.08}s`;
        item.classList.add('animate-slide-up');
    });
}

// ============================================
// ARTIST CARDS (open modal on click)
// ============================================
function initArtistCards() {
    document.querySelectorAll('[data-artist]').forEach(card => {
        if (card.dataset.artistBound === '1') return;
        card.dataset.artistBound = '1';
        card.addEventListener('click', (e) => {
            if (e.target.closest('button') || e.target.closest('a')) return;
            try {
                const artistData = JSON.parse(card.dataset.artist);
                showArtistDetail(artistData);
            } catch (err) {
                console.error('Invalid artist data', err);
            }
        });
    });
}

// ============================================
// MAP STAGE MARKERS
// ============================================
function initStageMarkers() {
    document.querySelectorAll('[data-stage-id]').forEach(btn => {
        if (btn.dataset.stageBound === '1') return;
        btn.dataset.stageBound = '1';

        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const stageId = Number(btn.dataset.stageId);
            if (!stageId) return;

            btn.classList.add('opacity-60');
            const data = await Api.post('api/stage-info.php', { stage_id: stageId });
            btn.classList.remove('opacity-60');

            if (!data.ok) return;
            renderStageModal(data);
            openModal('stage-modal');
        });
    });
}

function renderStageModal(data) {
    const modal = document.getElementById('stage-modal');
    if (!modal) return;

    modal.querySelector('.stage-modal-name').textContent = data.stage.name || '';
    modal.querySelector('.stage-modal-desc').textContent = data.stage.description || '';
    modal.querySelector('.stage-modal-icon').textContent = data.stage.icon || 'graphic_eq';

    // Now playing
    const nowWrap = modal.querySelector('.stage-modal-now');
    if (data.now_playing) {
        nowWrap.hidden = false;
        modal.querySelector('.stage-modal-now-name').textContent = data.now_playing.artist_name;
        modal.querySelector('.stage-modal-now-meta').textContent =
            `${data.now_playing.genre} • ${data.now_playing.time_label}`;
        const img = modal.querySelector('.stage-modal-now-img');
        if (data.now_playing.image_url) {
            img.src = data.now_playing.image_url;
            img.style.display = '';
        } else {
            img.removeAttribute('src');
            img.style.display = 'none';
        }
        const card = modal.querySelector('.stage-modal-now-card');
        card.dataset.artist = JSON.stringify({
            id:              data.now_playing.slug,
            artist_id:       data.now_playing.artist_id,
            performance_id:  data.now_playing.performance_id,
            name:            data.now_playing.artist_name,
            genre:           data.now_playing.genre,
            stage:           data.stage.name,
            time:            data.now_playing.time_label,
            image:           data.now_playing.image_url || '',
        });
    } else {
        nowWrap.hidden = true;
    }

    // Up next
    const list  = modal.querySelector('.stage-modal-upcoming-list');
    const empty = modal.querySelector('.stage-modal-empty');
    list.innerHTML = '';

    if (data.upcoming.length === 0) {
        empty.hidden = false;
    } else {
        empty.hidden = true;
        data.upcoming.forEach(p => {
            const row = document.createElement('div');
            row.className = 'glass-card rounded-xl p-3 cursor-pointer flex items-center gap-3';
            row.dataset.artist = JSON.stringify({
                id:              p.slug,
                artist_id:       p.artist_id,
                performance_id:  p.performance_id,
                name:            p.artist_name,
                genre:           p.genre,
                stage:           data.stage.name,
                time:            p.time_label,
                image:           p.image_url || '',
            });

            row.innerHTML = `
                <div class="w-10 h-10 rounded-lg bg-surface-container-highest flex items-center justify-center text-on-surface-variant flex-shrink-0">
                    <span class="material-symbols-outlined text-[18px]">schedule</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-display text-[16px] text-on-surface truncate"></p>
                    <p class="font-body text-label-sm text-on-surface-variant truncate"></p>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
            `;
            row.querySelectorAll('p')[0].textContent = p.artist_name;
            row.querySelectorAll('p')[1].textContent = `${p.genre} • ${p.time_label}`;

            row.addEventListener('click', () => {
                try { showArtistDetail(JSON.parse(row.dataset.artist)); }
                catch (_) {}
            });
            list.appendChild(row);
        });
    }

    // Re-bind the now-playing card to open the artist modal too.
    const nowCard = modal.querySelector('.stage-modal-now-card');
    if (nowCard && nowCard.dataset.bound !== '1') {
        nowCard.dataset.bound = '1';
        nowCard.addEventListener('click', () => {
            try { showArtistDetail(JSON.parse(nowCard.dataset.artist)); }
            catch (_) {}
        });
    }
}

// ============================================
// QUEUE CARDS (queues page) AND MAP QUEUE MARKERS
// ============================================
function initQueueCards() {
    const triggers = document.querySelectorAll('.queue-card[data-queue-id], [data-queue-id]');
    triggers.forEach(card => {
        if (card.dataset.queueBound === '1') return;
        card.dataset.queueBound = '1';
        card.addEventListener('click', (e) => {
            if (e.target.closest('button') || e.target.closest('a')) {
                if (card.tagName !== 'BUTTON') return;
            }
            e.preventDefault();
            e.stopPropagation();
            renderQueueModal(card.dataset);
            openModal('queue-modal');
        });
    });
}

function renderQueueModal(d) {
    const modal = document.getElementById('queue-modal');
    if (!modal) return;

    modal.querySelector('.queue-modal-name').textContent     = d.queueName     || 'Queue';
    modal.querySelector('.queue-modal-location').textContent = d.queueLocation || '';
    modal.querySelector('.queue-modal-icon').textContent     = d.queueIcon     || 'local_bar';

    const wait = parseInt(d.queueWait || '0', 10);
    const cap  = parseInt(d.queueCapacity || '0', 10);

    modal.querySelector('.queue-modal-wait').textContent     = `${wait} min`;
    modal.querySelector('.queue-modal-capacity').textContent = `${cap}%`;

    const bar = modal.querySelector('.queue-modal-bar');
    bar.style.width = `${cap}%`;

    const updated = d.queueUpdated || '';
    let updatedLabel = 'Last updated just now';
    if (updated) {
        const ts = new Date(updated.replace(' ', 'T'));
        if (!isNaN(ts.getTime())) {
            const diffMin = Math.max(0, Math.floor((Date.now() - ts.getTime()) / 60000));
            updatedLabel = diffMin === 0 ? 'Last updated just now' : `Last updated ${diffMin} min ago`;
        }
    }
    modal.querySelector('.queue-modal-updated').textContent = updatedLabel;
}

// ============================================
// EDIT PROFILE MODAL
// ============================================
function initEditProfile() {
    const openBtn = document.getElementById('edit-profile-btn');
    const form    = document.getElementById('edit-profile-form');
    if (!openBtn || !form) return;

    openBtn.addEventListener('click', () => openModal('edit-profile-modal'));

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);
        const payload = {
            name:       String(fd.get('name') || '').trim(),
            location:   String(fd.get('location') || '').trim(),
            avatar_url: String(fd.get('avatar_url') || '').trim(),
        };

        const result = await Api.post('api/update-profile.php', payload);
        if (!result.ok) return;

        const u = result.user || {};
        const nameEl = document.getElementById('profile-name');
        const locEl  = document.getElementById('profile-location');
        const avEl   = document.getElementById('profile-avatar');
        if (nameEl) nameEl.textContent = u.name || payload.name;
        if (locEl)  locEl.textContent  = u.location || payload.location || 'Add your location';
        if (avEl && (u.avatar_url || payload.avatar_url)) avEl.src = u.avatar_url || payload.avatar_url;

        showToast('Profile updated', 'success');
        closeAllModals();
    });
}

// ============================================
// GENRE ADD / REMOVE
// ============================================
function initGenres() {
    const list = document.getElementById('genre-list');
    const form = document.getElementById('add-genre-form');
    if (!list || !form) return;

    const genreClasses = [
        'bg-primary-container/20 text-primary border border-primary/30',
        'bg-secondary-container/20 text-secondary border border-secondary/30',
        'bg-tertiary-container/20 text-tertiary border border-tertiary/30',
    ];

    function renderChip(id, name) {
        const idx = list.querySelectorAll('.genre-chip').length % genreClasses.length;
        const span = document.createElement('span');
        span.className = `genre-chip ${genreClasses[idx]} px-3 py-1.5 rounded-full font-body text-label-sm flex items-center gap-1`;
        span.dataset.genreId = id;
        span.innerHTML = `
            <span></span>
            <button type="button" class="genre-remove ml-1 -mr-1 hover:text-error transition-colors" aria-label="Remove">
                <span class="material-symbols-outlined text-[16px]">close</span>
            </button>
        `;
        span.querySelector('span').textContent = name;
        list.appendChild(span);
        bindRemove(span);
    }

    function bindRemove(chip) {
        const btn = chip.querySelector('.genre-remove');
        if (!btn) return;
        btn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const id = chip.dataset.genreId;
            if (!id) return;
            const result = await Api.post('api/remove-genre.php', { id: Number(id) });
            if (!result.ok) return;
            chip.remove();
            showToast('Genre removed', 'info');
        });
    }

    list.querySelectorAll('.genre-chip').forEach(bindRemove);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = form.querySelector('input[name="name"]');
        const name  = (input.value || '').trim();
        if (!name) return;

        const result = await Api.post('api/add-genre.php', { name });
        if (!result.ok) return;

        if (result.duplicate) {
            showToast('Already in your list', 'info');
        } else {
            renderChip(result.genre.id, result.genre.name);
            showToast('Genre added', 'success');
        }
        input.value = '';
    });
}

// ============================================
// FRIENDS PAGE
// ============================================
function initFriendsPage() {
    const searchInput = document.getElementById('friend-search');
    const resultsBox  = document.getElementById('friend-search-results');
    const emptyMsg    = document.getElementById('friend-search-empty');
    const spinner     = document.getElementById('friend-search-spinner');

    // Bind row-level buttons (incoming/outgoing/accepted) — works whether the
    // page rendered them or they were added live.
    bindFriendRowActions(document);

    if (!searchInput || !resultsBox) return;

    let searchTimer = null;
    let searchSeq   = 0;

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const query = searchInput.value.trim();

        if (query.length < 2) {
            resultsBox.innerHTML = '';
            if (emptyMsg) emptyMsg.hidden = true;
            return;
        }

        searchTimer = setTimeout(async () => {
            const seq = ++searchSeq;
            if (spinner) spinner.hidden = false;

            const result = await Api.post('api/search-users.php', { query });

            if (spinner) spinner.hidden = true;
            if (seq !== searchSeq) return; // a newer search has started; drop this result.

            resultsBox.innerHTML = '';
            if (!result.ok) return;

            if (result.results.length === 0) {
                if (emptyMsg) emptyMsg.hidden = false;
                return;
            }
            if (emptyMsg) emptyMsg.hidden = true;

            result.results.forEach(u => resultsBox.appendChild(buildSearchResultRow(u)));
        }, 250);
    });
}

function buildSearchResultRow(u) {
    const row = document.createElement('div');
    row.className = 'glass-card rounded-xl p-4 flex items-center gap-3';
    row.dataset.userId = u.id;
    row.dataset.userName = u.name;

    const avatar = document.createElement('div');
    avatar.className = 'w-12 h-12 rounded-full overflow-hidden bg-surface-container-highest border border-white/10 flex-shrink-0';
    if (u.avatar_url) {
        const img = document.createElement('img');
        img.src = u.avatar_url; img.alt = '';
        img.className = 'w-full h-full object-cover';
        avatar.appendChild(img);
    } else {
        avatar.innerHTML = '<div class="w-full h-full flex items-center justify-center text-on-surface-variant"><span class="material-symbols-outlined">person</span></div>';
    }

    const info = document.createElement('div');
    info.className = 'flex-1 min-w-0';
    const name  = document.createElement('p'); name.className  = 'font-display text-[16px] text-on-surface truncate'; name.textContent  = u.name;
    const email = document.createElement('p'); email.className = 'font-body text-label-sm text-on-surface-variant truncate'; email.textContent = u.location || u.email;
    info.appendChild(name); info.appendChild(email);

    const sendBtn = document.createElement('button');
    sendBtn.type = 'button';
    sendBtn.className = 'btn-primary px-3 py-1.5 text-label-sm';
    sendBtn.innerHTML = '<span class="material-symbols-outlined text-[16px]">person_add</span><span class="ml-1">Add</span>';
    sendBtn.addEventListener('click', async () => {
        sendBtn.disabled = true;
        const result = await Api.post('api/send-friend-request.php', { user_id: Number(u.id) });
        if (!result.ok) { sendBtn.disabled = false; return; }
        if (result.status === 'accepted') {
            showToast(`You and ${u.name} are now friends`, 'success');
        } else {
            showToast(`Request sent to ${u.name}`, 'success');
        }
        // Drop them from search results — the next /search call would also exclude them.
        row.remove();
    });

    row.appendChild(avatar);
    row.appendChild(info);
    row.appendChild(sendBtn);
    return row;
}

function bindFriendRowActions(scope) {
    scope.querySelectorAll('.friend-accept-btn').forEach(btn => {
        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            const row = btn.closest('.friend-row');
            if (!row) return;
            const userId = Number(row.dataset.userId);
            const name   = row.dataset.userName || 'Friend';
            const result = await Api.post('api/respond-friend-request.php', { user_id: userId, action: 'accept' });
            if (!result.ok) return;
            showToast(`You're now friends with ${name}`, 'success');
            setTimeout(() => window.location.reload(), 600);
        });
    });

    scope.querySelectorAll('.friend-decline-btn').forEach(btn => {
        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            const row = btn.closest('.friend-row');
            if (!row) return;
            const userId = Number(row.dataset.userId);
            const result = await Api.post('api/respond-friend-request.php', { user_id: userId, action: 'decline' });
            if (!result.ok) return;
            showToast('Request declined', 'info');
            row.remove();
        });
    });

    scope.querySelectorAll('.friend-cancel-btn').forEach(btn => {
        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            const row = btn.closest('.friend-row');
            if (!row) return;
            const userId = Number(row.dataset.userId);
            const result = await Api.post('api/remove-friend.php', { user_id: userId });
            if (!result.ok) return;
            showToast('Request cancelled', 'info');
            row.remove();
        });
    });

    scope.querySelectorAll('.friend-remove-btn').forEach(btn => {
        if (btn.dataset.bound === '1') return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            const row = btn.closest('.friend-row');
            if (!row) return;
            const userId = Number(row.dataset.userId);
            const name   = row.dataset.userName || 'Friend';
            if (!confirm(`Remove ${name} from your friends?`)) return;
            const result = await Api.post('api/remove-friend.php', { user_id: userId });
            if (!result.ok) return;
            showToast(`Removed ${name}`, 'info');
            row.remove();
        });
    });
}

// ============================================
// MODAL CLOSE HANDLERS
// ============================================
function initModalClose() {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            closeAllModals();
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllModals();
    });
}

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    State.hydrate();
    setActiveNav();
    initFavoriteButtons();
    initScheduleButtons();
    initToggles();
    initFilterChips();
    initTabToggles();
    initDaySelector();
    initSearch();
    initAnimations();
    initArtistCards();
    initStageMarkers();
    initQueueCards();
    initEditProfile();
    initGenres();
    initFriendsPage();
    initModalClose();
});
