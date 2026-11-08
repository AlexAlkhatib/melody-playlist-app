/* ─────────────────────────────────────────────────────────────────────────────
   Melody — comportements de l'interface
   - navigation mobile / menu utilisateur / toasts
   - lecteur audio persistant (file d'attente, lecture, volume, raccourcis)
   - reprise de la dernière écoute et historique local
   ───────────────────────────────────────────────────────────────────────────── */
(function () {
    'use strict';

    var STORE_LAST = 'melody:last';
    var STORE_RECENT = 'melody:recent';
    var STORE_VOLUME = 'melody:volume';

    /* ── Navigation ─────────────────────────────────────────────────────────── */
    var sidebar = document.querySelector('.sidebar');
    var scrim = document.querySelector('.scrim');
    var menuBtn = document.querySelector('[data-menu-toggle]');

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('is-open');
        if (scrim) scrim.classList.remove('is-visible');
    }

    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', function () {
            sidebar.classList.toggle('is-open');
            if (scrim) scrim.classList.toggle('is-visible', sidebar.classList.contains('is-open'));
        });
    }
    if (scrim) scrim.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    /* ── Toasts ─────────────────────────────────────────────────────────────── */
    document.querySelectorAll('.toast').forEach(function (toast) {
        var close = toast.querySelector('.toast__close');
        var remove = function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(function () { toast.remove(); }, 200);
        };
        if (close) close.addEventListener('click', remove);
        setTimeout(remove, 6000);
    });

    /* ── Lecteur ────────────────────────────────────────────────────────────── */
    var player = document.getElementById('player');
    if (!player) return;

    var audio = new Audio();
    audio.preload = 'metadata';

    var ui = {
        cover: document.getElementById('player-cover'),
        title: document.getElementById('player-title'),
        artist: document.getElementById('player-artist'),
        play: document.getElementById('player-play'),
        prev: document.getElementById('player-prev'),
        next: document.getElementById('player-next'),
        shuffle: document.getElementById('player-shuffle'),
        repeat: document.getElementById('player-repeat'),
        seek: document.getElementById('player-seek'),
        current: document.getElementById('player-current'),
        duration: document.getElementById('player-duration'),
        volume: document.getElementById('player-volume'),
        mute: document.getElementById('player-mute'),
        link: document.getElementById('player-link')
    };

    var state = { queue: [], index: -1, shuffle: false, repeat: 'off' };

    /* Icônes injectées via le sprite inline du template. */
    function icon(name, size) {
        var el = document.createElement('svg');
        el.setAttribute('width', size || 18);
        el.setAttribute('height', size || 18);
        el.setAttribute('aria-hidden', 'true');
        var use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttributeNS('http://www.w3.org/1999/xlink', 'href', '#i-' + name);
        el.appendChild(use);
        return el;
    }

    function setPlayIcon(playing) {
        if (!ui.play) return;
        ui.play.innerHTML = '';
        ui.play.appendChild(icon(playing ? 'pause' : 'play', 20));
        ui.play.setAttribute('aria-label', playing ? 'Mettre en pause' : 'Lire');
    }

    function fmt(seconds) {
        if (!isFinite(seconds) || seconds < 0) return '0:00';
        var m = Math.floor(seconds / 60);
        var s = Math.floor(seconds % 60);
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function readJson(key, fallback) {
        try {
            var raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function writeJson(key, value) {
        try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* stockage plein ou bloqué */ }
    }

    function remember(track) {
        writeJson(STORE_LAST, track);
        var recent = readJson(STORE_RECENT, []);
        recent = recent.filter(function (item) { return String(item.id) !== String(track.id); });
        recent.unshift(track);
        writeJson(STORE_RECENT, recent.slice(0, 6));
        renderResume();
        renderRecent();
    }

    function paintCover(el, seed, src) {
        if (!el) return;
        el.className = 'cover cover--' + (seed % 8) + ' player__cover';
        el.innerHTML = '';
        if (src) {
            var img = document.createElement('img');
            img.alt = '';
            img.src = src;
            el.appendChild(img);
        } else {
            el.appendChild(icon('note', 22));
        }
    }

    function load(track, opts) {
        opts = opts || {};
        audio.src = track.src;
        if (ui.title) ui.title.textContent = track.title || 'Titre inconnu';
        if (ui.artist) ui.artist.textContent = track.artist || 'Artiste inconnu';
        paintCover(ui.cover, parseInt(track.cover || 0, 10), track.image || null);
        if (ui.link) ui.link.href = track.url || '#';
        if (ui.seek) { ui.seek.value = 0; ui.seek.style.setProperty('--p', '0%'); }
        if (ui.current) ui.current.textContent = '0:00';
        if (ui.duration) ui.duration.textContent = '0:00';
        player.classList.remove('is-idle');

        markPlaying(track.id);
        if (opts.autoplay !== false) {
            audio.play().catch(function () { setPlayIcon(false); });
        }
        remember(track);
    }

    function markPlaying(id) {
        document.querySelectorAll('[data-track-id]').forEach(function (el) {
            var row = el.closest('.track-row, .track-card');
            if (!row) return;
            row.classList.toggle('is-playing', String(row.dataset.trackId) === String(id));
        });
    }

    function collectQueue(container) {
        var scope = container || document;
        return Array.prototype.map.call(scope.querySelectorAll('[data-play]'), function (btn) {
            return {
                id: btn.dataset.trackId,
                title: btn.dataset.title,
                artist: btn.dataset.artist,
                src: btn.dataset.src,
                cover: btn.dataset.cover,
                image: btn.dataset.image || '',
                url: btn.dataset.url || ''
            };
        });
    }

    function playIndex(i, opts) {
        if (!state.queue.length) return;
        state.index = (i + state.queue.length) % state.queue.length;
        load(state.queue[state.index], opts);
    }

    function nextIndex() {
        if (!state.queue.length) return 0;
        if (state.shuffle && state.queue.length > 1) {
            var candidate = state.index;
            while (candidate === state.index) {
                candidate = Math.floor(Math.random() * state.queue.length);
            }
            return candidate;
        }
        return state.index + 1;
    }

    function playQueue(container, startId, autoplay) {
        state.queue = collectQueue(container);
        var start = 0;
        if (startId !== undefined && startId !== null) {
            state.queue.forEach(function (track, i) {
                if (String(track.id) === String(startId)) start = i;
            });
        }
        playIndex(start, { autoplay: autoplay !== false });
    }

    /* Délégation : boutons de lecture dans les cartes et les listes. */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-play]');
        if (btn) {
            e.preventDefault();
            var trackId = btn.dataset.trackId;
            var scope = btn.closest('[data-queue]');
            var isSame = state.queue[state.index] && String(state.queue[state.index].id) === String(trackId);

            if (isSame && !audio.paused) {
                audio.pause();
                return;
            }
            if (isSame && audio.paused && audio.src) {
                audio.play().catch(function () { setPlayIcon(false); });
                return;
            }
            playQueue(scope, trackId, true);
            return;
        }

        var all = e.target.closest('[data-play-all]');
        if (all) {
            e.preventDefault();
            var container = all.closest('[data-queue]') || document.querySelector('[data-queue]');
            playQueue(container, null, true);
        }
    });

    if (ui.play) {
        ui.play.addEventListener('click', function () {
            if (!audio.src) {
                var container = document.querySelector('[data-queue]');
                if (container) playQueue(container, null, true);
                return;
            }
            if (audio.paused) {
                audio.play().catch(function () { setPlayIcon(false); });
            } else {
                audio.pause();
            }
        });
    }

    if (ui.next) ui.next.addEventListener('click', function () { playIndex(nextIndex()); });
    if (ui.prev) {
        ui.prev.addEventListener('click', function () {
            if (audio.currentTime > 3) { audio.currentTime = 0; return; }
            playIndex(state.index - 1);
        });
    }

    if (ui.shuffle) {
        ui.shuffle.addEventListener('click', function () {
            state.shuffle = !state.shuffle;
            ui.shuffle.classList.toggle('is-active', state.shuffle);
            ui.shuffle.setAttribute('aria-pressed', String(state.shuffle));
        });
    }

    if (ui.repeat) {
        ui.repeat.addEventListener('click', function () {
            state.repeat = state.repeat === 'off' ? 'all' : (state.repeat === 'all' ? 'one' : 'off');
            ui.repeat.classList.toggle('is-active', state.repeat !== 'off');
            ui.repeat.dataset.mode = state.repeat;
            ui.repeat.setAttribute('aria-pressed', String(state.repeat !== 'off'));
            var label = state.repeat === 'one' ? 'Répéter le titre' : (state.repeat === 'all' ? 'Répéter la file' : 'Répétition désactivée');
            ui.repeat.setAttribute('aria-label', label);
        });
    }

    audio.addEventListener('play', function () { setPlayIcon(true); });
    audio.addEventListener('pause', function () { setPlayIcon(false); });

    audio.addEventListener('loadedmetadata', function () {
        if (ui.duration) ui.duration.textContent = fmt(audio.duration);
    });

    audio.addEventListener('timeupdate', function () {
        if (!ui.seek) return;
        var pct = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
        ui.seek.value = pct;
        ui.seek.style.setProperty('--p', pct + '%');
        if (ui.current) ui.current.textContent = fmt(audio.currentTime);
    });

    audio.addEventListener('ended', function () {
        if (state.repeat === 'one') {
            audio.currentTime = 0;
            audio.play();
            return;
        }
        var last = state.index === state.queue.length - 1;
        if (last && state.repeat === 'off') {
            setPlayIcon(false);
            return;
        }
        playIndex(nextIndex());
    });

    if (ui.seek) {
        ui.seek.addEventListener('input', function () {
            if (!audio.duration) return;
            audio.currentTime = (ui.seek.value / 100) * audio.duration;
        });
    }

    var savedVolume = parseFloat(localStorage.getItem(STORE_VOLUME) || '0.8');
    audio.volume = isNaN(savedVolume) ? 0.8 : Math.min(1, Math.max(0, savedVolume));

    function paintVolume() {
        if (!ui.volume) return;
        ui.volume.value = audio.muted ? 0 : audio.volume;
        ui.volume.style.setProperty('--p', (ui.volume.value * 100) + '%');
        if (ui.mute) {
            ui.mute.innerHTML = '';
            ui.mute.appendChild(icon(audio.muted || audio.volume === 0 ? 'volume-off' : 'volume', 18));
        }
    }

    if (ui.volume) {
        ui.volume.addEventListener('input', function () {
            audio.muted = false;
            audio.volume = parseFloat(ui.volume.value);
            localStorage.setItem(STORE_VOLUME, String(audio.volume));
            paintVolume();
        });
    }

    if (ui.mute) {
        ui.mute.addEventListener('click', function () {
            audio.muted = !audio.muted;
            paintVolume();
        });
    }

    /* Raccourcis clavier globaux (hors champs de saisie). */
    document.addEventListener('keydown', function (e) {
        var tag = (e.target.tagName || '').toLowerCase();
        if (['input', 'textarea', 'select'].indexOf(tag) !== -1) return;

        if (e.code === 'Space') {
            e.preventDefault();
            if (audio.paused) audio.play().catch(function () {});
            else audio.pause();
        } else if (e.code === 'ArrowRight' && e.shiftKey) {
            audio.currentTime = Math.min(audio.duration || 0, audio.currentTime + 5);
        } else if (e.code === 'ArrowLeft' && e.shiftKey) {
            audio.currentTime = Math.max(0, audio.currentTime - 5);
        }
    });

    /* ── Reprise et historique ──────────────────────────────────────────────── */
    function renderResume() {
        var card = document.getElementById('resume-card');
        if (!card) return;
        var last = readJson(STORE_LAST, null);
        if (!last || !last.src) { card.hidden = true; return; }
        card.hidden = false;
        var title = card.querySelector('[data-resume-title]');
        var artist = card.querySelector('[data-resume-artist]');
        var cover = card.querySelector('[data-resume-cover]');
        if (title) title.textContent = last.title || '';
        if (artist) artist.textContent = last.artist || '';
        paintCover(cover, parseInt(last.cover || 0, 10), last.image || null);
    }

    function renderRecent() {
        var holder = document.getElementById('recent-tracks');
        if (!holder) return;
        var recent = readJson(STORE_RECENT, []);
        holder.innerHTML = '';
        if (!recent.length) { holder.closest('section').hidden = true; return; }
        holder.closest('section').hidden = false;
        recent.forEach(function (track) {
            var card = document.createElement('article');
            card.className = 'track-card';
            card.dataset.trackId = track.id;
            card.innerHTML =
                '<div class="track-card__media">' +
                    '<div class="cover cover--' + (parseInt(track.cover || 0, 10) % 8) + '"></div>' +
                    '<button class="track-card__play" data-play data-track-id="' + track.id + '"' +
                        ' data-title="' + escapeAttr(track.title) + '" data-artist="' + escapeAttr(track.artist) + '"' +
                        ' data-src="' + escapeAttr(track.src) + '" data-cover="' + (track.cover || 0) + '"' +
                        ' data-url="' + escapeAttr(track.url || '') + '" aria-label="Lire"></button>' +
                '</div>' +
                '<div class="track-card__body">' +
                    '<div class="track-card__title">' + escapeHtml(track.title) + '</div>' +
                    '<div class="track-card__meta">' + escapeHtml(track.artist) + '</div>' +
                '</div>';
            card.querySelector('.track-card__play').appendChild(icon('play', 18));
            holder.appendChild(card);
        });
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function escapeAttr(value) { return escapeHtml(value); }

    if (ui.repeat) {
        ui.repeat.dataset.mode = 'off';
    }

    setPlayIcon(false);
    paintVolume();
    renderResume();
    renderRecent();

    /* Bouton « Reprendre » de la section d'accueil. */
    document.addEventListener('click', function (e) {
        var resume = e.target.closest('[data-resume]');
        if (!resume) return;
        var last = readJson(STORE_LAST, null);
        if (!last) return;
        state.queue = [last];
        state.index = 0;
        load(last);
    });

    /* API Media Session : contrôles depuis le système / les écouteurs. */
    if ('mediaSession' in navigator) {
        audio.addEventListener('play', function () {
            var track = state.queue[state.index];
            if (!track || !('mediaSession' in navigator)) return;
            navigator.mediaSession.metadata = new window.MediaMetadata({
                title: track.title || '',
                artist: track.artist || 'Melody',
                album: 'Melody'
            });
            navigator.mediaSession.setActionHandler('play', function () { audio.play(); });
            navigator.mediaSession.setActionHandler('pause', function () { audio.pause(); });
            navigator.mediaSession.setActionHandler('nexttrack', function () { playIndex(nextIndex()); });
            navigator.mediaSession.setActionHandler('previoustrack', function () { playIndex(state.index - 1); });
        });
    }
})();
