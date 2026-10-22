/*
 * Skeleton loader on every logged-in page (CSS: assets/css/sr_skeleton.css).
 * Injected into <head> by includes/app_datepicker_head.php - pages do not link it.
 *
 * 1. Page load: the content area (.content-page > .content) shows a shimmering skeleton
 *    (page title, 4 stat tiles, a table) until the DOM is ready and the page's own
 *    scripts have run (DataTables init etc.), then the real content fades in.
 * 2. Navigation: clicking an internal link or submitting a normal form swaps the content
 *    area for the skeleton (plus a thin progress line at the top) while the server builds
 *    the next page. Downloads/exports, new tabs, "#"/javascript: links and AJAX forms
 *    (preventDefault) are ignored; a timer clears it if the page never unloads.
 * 3. DataTables (server-side / AJAX): skeleton rows while the first page of data loads,
 *    dimmed rows while later pages load.
 * 4. Stat values that still show a placeholder ("–", "...") shimmer until filled.
 *
 * Opt out: add data-no-skeleton to a link, form, table, or <body> (whole page).
 */
(function () {
    'use strict';

    var html = document.documentElement;
    var NAV_DELAY = 120;        // fast pages never flash the skeleton
    var NAV_TIMEOUT = 15000;    // page did not unload (download, blocked) - give the content back
    var EXPORT_URL = /(export|excel|xlsx|xls|csv|pdf|print|download|logout|\.zip|\.docx?)(\b|_|$)/i;
    var PLACEHOLDER_SELECTOR = '.sr-scard-value, .sr-stat-value, .sr-tile-value, .dash-kpi-value, .sr-count';
    var PLACEHOLDER_TEXT = /^\s*(–|—|-|\.\.\.|…)?\s*$/;

    function skeletonMarkup() {
        var tiles = '', rows = '', widths = [[70, 85, 55, 60, 40], [55, 70, 70, 40, 55], [85, 60, 40, 70, 40], [60, 85, 55, 55, 55]];
        for (var t = 0; t < 4; t++) {
            tiles += '<div class="sr-sk-card sr-sk-tile"><div><span class="sr-sk-b h-10 w-55"></span>'
                + '<span class="sr-sk-b h-26 w-40"></span><span class="sr-sk-b h-6 w-100"></span></div>'
                + '<span class="sr-sk-b sr-sk-icon"></span></div>';
        }
        rows += '<div class="sr-sk-row is-head">' + [40, 55, 40, 40, 40].map(function (w) {
            return '<span class="sr-sk-b w-' + w + '"></span>';
        }).join('') + '</div>';
        for (var r = 0; r < 7; r++) {
            rows += '<div class="sr-sk-row">' + widths[r % 4].map(function (w) {
                return '<span class="sr-sk-b h-12 w-' + w + '"></span>';
            }).join('') + '</div>';
        }
        return '<div class="sr-sk-head"><div><span class="sr-sk-b h-22 w-30"></span><span class="sr-sk-b h-12 w-15"></span></div>'
            + '<span class="sr-sk-b sr-sk-btn"></span></div>'
            + '<div class="sr-sk-tiles">' + tiles + '</div>'
            + '<div class="sr-sk-card"><div class="sr-sk-toolbar"><span class="sr-sk-b h-34 w-25"></span>'
            + '<span class="sr-sk-b h-34 w-15"></span></div>' + rows + '</div>';
    }

    // Puts the skeleton into the content area once it exists. Returns false when the page
    // has no app layout (login, print, popups).
    function mount() {
        var content = document.querySelector('.content-page > .content');
        if (!content) return false;
        if (!content.querySelector(':scope > .sr-sk')) {
            var sk = document.createElement('div');
            sk.className = 'sr-sk';
            sk.setAttribute('aria-hidden', 'true');
            sk.innerHTML = skeletonMarkup();
            content.insertBefore(sk, content.firstChild);
        }
        if (!document.querySelector('.sr-sk-topbar')) {
            var bar = document.createElement('div');
            bar.className = 'sr-sk-topbar';
            document.body.appendChild(bar);
        }
        return true;
    }

    function reveal() {
        if (!html.classList.contains('sr-sk-on')) return;
        html.classList.remove('sr-sk-on', 'sr-sk-nav');
        html.classList.add('sr-sk-done');
        setTimeout(function () { html.classList.remove('sr-sk-done'); }, 400);
        // sr_count_up.js starts the counter / bar animations it held back.
        try { window.dispatchEvent(new Event('sr:skeleton-done')); } catch (e) { /* old browsers */ }
        // Charts / DataTables that measured a hidden container re-measure now.
        try { window.dispatchEvent(new Event('resize')); } catch (e) { /* old browsers */ }
    }

    /* ---------------- 1. Page load ---------------- */
    html.classList.add('sr-sk-on');

    // Mount as soon as the content area is parsed, so the skeleton paints instead of a blank area.
    var mountObserver = new MutationObserver(function () {
        if (document.body && mount()) mountObserver.disconnect();
    });
    mountObserver.observe(html, { childList: true, subtree: true });

    document.addEventListener('DOMContentLoaded', function () {
        mountObserver.disconnect();
        if (!mount() || document.body.hasAttribute('data-no-skeleton')) {
            html.classList.remove('sr-sk-on');
            return;
        }
        // Two frames: lets the page's own DOMContentLoaded/jQuery-ready code finish first.
        requestAnimationFrame(function () { requestAnimationFrame(reveal); });
    });
    // Back/forward cache restores the old page with the navigation skeleton still on.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { clearTimeout(navTimer); html.classList.add('sr-sk-on'); reveal(); }
    });

    /* ---------------- 2. Navigation ---------------- */
    var navTimer = null, navShowTimer = null;

    function startNav() {
        if (!document.querySelector('.content-page > .content') || document.body.hasAttribute('data-no-skeleton')) return;
        clearTimeout(navShowTimer);
        clearTimeout(navTimer);
        navShowTimer = setTimeout(function () {
            mount();
            html.classList.remove('sr-sk-done');
            html.classList.add('sr-sk-on', 'sr-sk-nav');
            window.scrollTo(0, 0);
        }, NAV_DELAY);
        navTimer = setTimeout(function () { clearTimeout(navShowTimer); reveal(); }, NAV_TIMEOUT);
    }

    function isSkippableUrl(url) {
        if (!url) return true;
        var a = document.createElement('a');
        a.href = url;
        if (a.origin !== location.origin) return true;
        if (!/^https?:$/.test(a.protocol)) return true;
        if (a.pathname === location.pathname && a.search === location.search && a.hash) return true; // in-page anchor
        return EXPORT_URL.test(a.pathname + a.search);
    }

    // Window-level bubble listeners run after the page's own handlers, so links/forms that
    // page JS handles itself (preventDefault) are already marked.
    window.addEventListener('click', function (e) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || a.hasAttribute('download') || a.closest('[data-no-skeleton]')) return;
        if (a.target && a.target !== '_self') return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(href)) return;
        if (a.hasAttribute('data-toggle') || a.hasAttribute('data-bs-toggle')) return;
        if (isSkippableUrl(a.href)) return;
        startNav();
    });

    window.addEventListener('submit', function (e) {
        var form = e.target;
        if (e.defaultPrevented || !form || form.closest('[data-no-skeleton]')) return;
        if (form.target && form.target !== '_self') return;
        // getAttribute: form.action is shadowed when the form has an input named "action".
        if (isSkippableUrl(form.getAttribute('action') || location.href)) return;
        startNav();
    });

    window.addEventListener('pagehide', function () { clearTimeout(navTimer); });

    /* ---------------- 3. DataTables ---------------- */
    function skeletonRows(table) {
        var cols = table.querySelectorAll('thead tr:last-child th').length || 5;
        var widths = [70, 85, 55, 60, 40, 70, 55];
        var out = '';
        for (var r = 0; r < 6; r++) {
            out += '<tr class="sr-sk-tr">';
            for (var c = 0; c < cols; c++) {
                out += '<td><span class="sr-sk-b w-' + widths[(r + c) % widths.length] + '" style="width:' + widths[(r + c) % widths.length] + '%"></span></td>';
            }
            out += '</tr>';
        }
        return out;
    }

    function hookDataTables() {
        var $ = window.jQuery;
        if (!$ || !$.fn || !$.fn.dataTable) return;
        $(document).on('preXhr.dt', function (e, settings) {
            var table = settings && settings.nTable;
            if (!table || table.closest('[data-no-skeleton]')) return;
            var body = table.tBodies[0];
            if (!body) return;
            var hasData = body.querySelector('tr:not(.sr-sk-tr) td:not(.dataTables_empty)');
            if (hasData) {
                table.classList.add('sr-sk-busy');
            } else {
                body.innerHTML = skeletonRows(table);
            }
        });
        $(document).on('xhr.dt draw.dt', function (e, settings) {
            var table = settings && settings.nTable;
            if (table) table.classList.remove('sr-sk-busy');
        });
    }

    /* ---------------- 4. Placeholder stat values ---------------- */
    function markPlaceholders(root) {
        if (!root.querySelectorAll) return;
        var list = root.matches && root.matches(PLACEHOLDER_SELECTOR) ? [root] : [];
        Array.prototype.push.apply(list, root.querySelectorAll(PLACEHOLDER_SELECTOR));
        list.forEach(function (el) {
            var empty = PLACEHOLDER_TEXT.test(el.textContent) && !el.querySelector('i, img, svg') && el.textContent.length > 0;
            el.classList.toggle('sr-sk-inline', empty);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        hookDataTables();
        markPlaceholders(document.body);
        new MutationObserver(function (records) {
            var seen = new Set();
            records.forEach(function (r) {
                var t = r.target.nodeType === 1 ? r.target : r.target.parentElement;
                if (!t || seen.has(t)) return;
                seen.add(t);
                var tile = t.closest(PLACEHOLDER_SELECTOR);
                if (tile) markPlaceholders(tile);
                else if (r.type === 'childList' && r.addedNodes.length) {
                    Array.prototype.forEach.call(r.addedNodes, function (n) { if (n.nodeType === 1) markPlaceholders(n); });
                }
            });
        }).observe(document.body, { childList: true, subtree: true, characterData: true });
    });
})();
