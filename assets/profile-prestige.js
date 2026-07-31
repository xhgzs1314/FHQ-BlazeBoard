(function () {
    'use strict';

    var MOTE_COUNT = 10;          // 浮尘数量
    var reduceMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function rand(min, max) {
        return Math.random() * (max - min) + min;
    }

    /* apex */
    function decorateApex(root) {
        var avatar = root.querySelector('.profile-avatar-lg');
        if (avatar && !avatar.querySelector('.pg-crown')) {
            avatar.style.position = 'relative';
            var crown = document.createElement('i');
            crown.className = 'fas fa-crown pg-crown';
            crown.setAttribute('aria-hidden', 'true');
            avatar.appendChild(crown);
        }

        if (reduceMotion) return;
        var aura = root.querySelector('.pg-aura');
        if (!aura || aura.querySelector('.pg-mote')) return;

        var frag = document.createDocumentFragment();
        for (var i = 0; i < MOTE_COUNT; i++) {
            var m = document.createElement('span');
            m.className = 'pg-mote';
            m.style.left = rand(4, 96).toFixed(1) + '%';
            m.style.animationDuration = rand(6, 12).toFixed(1) + 's';
            m.style.animationDelay = '-' + rand(0, 8).toFixed(1) + 's';
            m.style.setProperty('--mr', rand(70, 130).toFixed(0) + 'px');
            m.style.setProperty('--md', rand(-16, 16).toFixed(0) + 'px');
            m.style.setProperty('--mo', rand(0.35, 0.8).toFixed(2));
            frag.appendChild(m);
        }
        aura.appendChild(frag);
    }

    /* 数字滚动 */
    function countUpRank(root) {
        var el = root.querySelector('.pg-rank-value');
        if (!el || el.classList.contains('unranked')) return;
        var target = parseInt(root.getAttribute('data-rank'), 10);
        if (!target || target < 1) return;

        var hash = el.querySelector('.pg-rank-hash');
        var prefix = hash ? hash.outerHTML : '';
        if (reduceMotion) return;

        var start = null;
        var dur = 900;
        function step(ts) {
            if (start === null) start = ts;
            var p = Math.min(1, (ts - start) / dur);
            var eased = 1 - Math.pow(1 - p, 3);   
            el.innerHTML = prefix + Math.max(1, Math.round(target * eased));
            if (p < 1) requestAnimationFrame(step);
            else el.innerHTML = prefix + target;
        }
        el.innerHTML = prefix + '0';
        requestAnimationFrame(step);
    }

    function init() {
        var root = document.querySelector('[data-prestige]');
        if (!root) return;
        var tier = root.getAttribute('data-prestige');
        if (!tier) return;               // 未达标

        if (tier === 'apex') decorateApex(root);
        countUpRank(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();