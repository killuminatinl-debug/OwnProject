(function () {
    'use strict';

    function boot() {
        var state = window.__INITIAL_STATE__ || {};
        var cache = state.cache || {};
        var uid = 0;
        var sessionId = '';

        Object.keys(cache).forEach(function (key) {
            if (key.indexOf('Player:') === 0) {
                uid = parseInt(key.substring(7), 10) || 0;
            }
            if (key.indexOf('Session:') === 0 && cache[key].data) {
                sessionId = cache[key].data.sessionId || '';
            }
        });

        if (!window.angular || !window.GameLobby || !window.GameLobby.app) {
            setTimeout(boot, 25);
            return;
        }

        GameLobby.config = state.config || {};
        GameLobby.globals = {
            sessionId: sessionId,
            playerId: uid,
            selectedCountry: state.country || 'us'
        };
        GameLobby.txt = (state.intl && state.intl.messages) || {};
        GameLobby.htmlFilters = GameLobby.htmlFilters || {};
        GameLobby.Player = null;

        try {
            angular.bootstrap(document, ['GameLobby']);
        } catch (e) {
            if (window.console && console.error) {
                console.error('Travian Kingdoms lobby bootstrap failed:', e);
            }
            var root = document.getElementById('root');
            if (root) {
                root.innerHTML = '<div style="padding:40px;font-family:Arial;color:#fff;background:#6b6b3f">Lobby could not be started. Check the PHP/JavaScript error log.</div>';
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}());