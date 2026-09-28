(function () {
    'use strict';

    function boot() {
        if (!window.angular || !window.GameLobby || !window.GameLobby.app) {
            setTimeout(boot, 25);
            return;
        }

        var root = document.getElementById('root');
        if (!root) {
            return;
        }

        if (!root.getAttribute('ng-controller')) {
            root.setAttribute('ng-controller', 'MainController');
        }

        try {
            angular.bootstrap(document, ['GameLobby']);
        } catch (e) {
            if (window.console && console.error) {
                console.error('Travian Kingdoms lobby bootstrap failed:', e);
            }
            root.innerHTML = '<div style="padding:40px;font-family:Arial;color:#fff;background:#6b6b3f">Lobby could not be started. Check the PHP/JavaScript error log.</div>';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}());