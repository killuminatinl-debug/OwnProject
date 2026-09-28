(function () {
    'use strict';

    var state = window.__INITIAL_STATE__ || {};
    var cfg = state.config || {};
    var root = document.getElementById('root');
    if (!root) return;

    var base = cfg.backendUrl ? cfg.backendUrl.replace(/\/api\/index\.php$/, '') : (location.origin + '/lobby');
    var uid = state.cache && state.cache['Player:' + ((state.cache && state.cache['Player:0']) ? 0 : '')];

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
        });
    }

    function post(controller, action, params) {
        return fetch(cfg.backendUrl || (location.pathname.replace(/\/[^/]*$/, '') + '/api/index.php'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type':'application/json', 'Accept':'application/json'},
            body: JSON.stringify({controller: controller, action: action, params: params || {}})
        }).then(function (r) {
            return r.text().then(function (txt) {
                var data;
                try { data = JSON.parse(txt); } catch (e) {
                    throw new Error('Lobby API returned invalid JSON.');
                }
                if (!r.ok) throw new Error(data.error || 'Lobby request failed.');
                return data;
            });
        });
    }

    function playerFromState() {
        var cache = state.cache || {};
        for (var k in cache) {
            if (k.indexOf('Player:') === 0 && cache[k] && cache[k].data) return cache[k].data;
        }
        return null;
    }

    function shell(player) {
        var name = player && player.avatarName ? player.avatarName : '';
        var logged = !!(player && parseInt(player.playerId || 0, 10));
        root.innerHTML =
            '<div class="page">' +
              '<div class="header">' +
                '<div class="logo-wrapper"><div class="header-logo"></div></div>' +
                '<div class="header-content container">' +
                  '<div style="float:right;padding-top:17px;">' +
                    (logged
                      ? '<a class="default-button" href="' + esc(cfg.redirectAfterLogout || location.pathname) + '#logout">Abmelden</a>'
                      : '<a class="default-button" href="' + esc((cfg.mellon && cfg.mellon.url) || (location.origin + '/mellon/') + 'login') + '">Anmelden</a>') +
                  '</div>' +
                '</div>' +
              '</div>' +
              '<div class="container" style="padding-top:30px;">' +
                '<div id="nav-sidebar"></div>' +
                '<main id="lobby-main" style="margin-left:321px;min-height:500px;">' +
                  '<div class="page-header"><h1>Game worlds</h1><p class="text-muted">Travian Kingdoms</p></div>' +
                  '<div id="worlds" class="row"></div>' +
                  '<div id="lobby-error" class="alert alert-danger" style="display:none;margin-top:15px;"></div>' +
                '</main>' +
              '</div>' +
            '</div>';

        if (logged) {
            document.getElementById('nav-sidebar').innerHTML =
                '<div class="nav-sidebar-desktop-bg"></div>' +
                '<div class="player-name">' + esc(name) + '</div>' +
                '<div style="padding:12px;text-align:center;color:#777;">Account</div>' +
                '<div class="buttons" style="padding:0 12px;">' +
                  '<a class="default-button navigation-button" style="display:block;margin-bottom:6px;" href="' + esc(cfg.redirectAfterLogout || location.pathname) + '">Home</a>' +
                '</div>';
        } else {
            document.getElementById('nav-sidebar').innerHTML =
                '<div class="nav-sidebar-desktop-bg"></div>' +
                '<div style="padding:20px;text-align:center;">' +
                  '<strong>Travian Kingdoms</strong><br><br>' +
                  '<a class="default-button" href="' + esc((cfg.mellon && cfg.mellon.url) || (location.origin + '/mellon/')) + 'login">Aanmelden</a>' +
                  '<a class="default-button" href="' + esc((cfg.mellon && cfg.mellon.url) || (location.origin + '/mellon/')) + 'register">Registreren</a>' +
                '</div>';
        }
    }

    function renderWorlds(worlds, logged) {
        var wrap = document.getElementById('worlds');
        if (!wrap) return;
        if (!worlds || !worlds.length) {
            wrap.innerHTML = '<div class="col-xs-12"><div class="alert alert-info">Er is nog geen gamewereld geconfigureerd.</div></div>';
            return;
        }
        wrap.innerHTML = worlds.map(function (w) {
            var id = parseInt(w.consumersId || 0, 10);
            var start = w.worldStartTime ? new Date(String(w.worldStartTime).replace(' ', 'T')).toLocaleString() : '-';
            var speed = parseInt(w.speedGame || 1, 10);
            var troops = parseInt(w.speedTroops || 1, 10);
            var button = logged
                ? '<a class="default-button" style="width:100%;margin:0;" href="' + esc((location.pathname.replace(/\/index\.php$/, '') || location.pathname) + '/play.php?sid=' + id) + '">Spelen</a>'
                : '<a class="default-button" style="width:100%;margin:0;" href="' + esc((cfg.mellon && cfg.mellon.url) || (location.origin + '/mellon/')) + 'login">Aanmelden om te spelen</a>';
            return '<div class="col-md-6 col-lg-6" style="margin-bottom:20px;">' +
                '<div class="game-world active-game-world last-active-game-world">' +
                  '<div class="container-header"><div class="header-button ' + (w.status === 2 ? 'active' : '') + '">' +
                    '<span class="game-world-name">' + esc(w.worldName || 'Kingdoms') + '</span>' +
                    '<span class="avatar-name">' + esc(w.identifier || '') + '</span>' +
                  '</div></div>' +
                  '<div class="container-content">' +
                    '<div class="placeholder server" style="height:246px;"></div>' +
                    '<div class="information">' +
                      '<div><span>Wereldsnelheid</span><span>' + speed + 'x</span></div>' +
                      '<div><span>Troepensnelheid</span><span>' + troops + 'x</span></div>' +
                      '<div><span>Gestart</span><span>' + esc(start) + '</span></div>' +
                    '</div>' +
                  '</div>' +
                  '<div class="container-footer"><div style="padding:8px;">' + button + '</div></div>' +
                '</div>' +
              '</div>';
        }).join('');
    }

    function showError(err) {
        var box = document.getElementById('lobby-error');
        if (box) {
            box.style.display = 'block';
            box.textContent = err && err.message ? err.message : String(err);
        }
    }

    var player = playerFromState();
    var logged = !!(player && parseInt(player.playerId || 0, 10));
    shell(player);

    post('gameworld', 'getPossibleNewGameworlds', {}).then(function (data) {
        renderWorlds(data.response && data.response.recommended ? data.response.recommended : [], logged);
    }).catch(function () {
        return post('cache', 'get', {names:['Player:' + (logged ? player.playerId : 0)]})
            .then(function () { return post('gameworld', 'getPossibleNewGameworlds', {}); })
            .then(function (data) {
                renderWorlds(data.response && data.response.recommended ? data.response.recommended : [], logged);
            });
    }).catch(showError);

    if (logged) {
        post('player', 'getLastPlayedGameWorld', {}).catch(function () {});
    }
})();
