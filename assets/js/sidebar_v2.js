/* Sidebar v2 behaviour: accordion, icon rail (desktop), off-canvas drawer (mobile).
   Vanilla JS. Loaded synchronously from includes/main_menu.php so body.sbv2 is set before first paint. */
(function () {
    'use strict';
    var body = document.body;
    if (!body || body.classList.contains('sbv2')) return;
    body.classList.add('sbv2');

    // The legacy script toggles/keeps `body.enlarged` (icon rail + hover flyouts). It's replaced by
    // sbv2-rail below, so drop that class whenever something adds it.
    function stripEnlarged() { if (body.classList.contains('enlarged')) body.classList.remove('enlarged'); }
    stripEnlarged();
    if (window.matchMedia("(min-width: 992px)").matches) body.classList.add("sbv2-rail"); // no wide flash before init
    new MutationObserver(stripEnlarged).observe(body, { attributes: true, attributeFilter: ['class'] });

    var mq = window.matchMedia("(min-width: 992px)");
    var backdrop;

    function isDesktop() { return mq.matches; }
    // desktop: sidebar is always the narrow icon rail; peek = temporary wide overlay (content does not move)
    function setRail(on) { body.classList.toggle("sbv2-rail", !!on && isDesktop()); if (!on) setPeek(false); }
    function isRail() { return body.classList.contains("sbv2-rail"); }
    function setPeek(on) { body.classList.toggle("sbv2-peek", !!on && isDesktop()); }
    function isPeek() { return body.classList.contains("sbv2-peek"); }
    function setDrawer(open) { body.classList.toggle("sbv2-drawer", !!open && !isDesktop()); }
    function setDrawer(open) { body.classList.toggle('sbv2-drawer', !!open && !isDesktop()); }

    function hasSub(li) { return !!(li && li.querySelector(':scope > ul')); }

    function openLi(li, open) {
        li.classList.toggle('sbv2-open', open);
        var ul = li.querySelector(':scope > ul');
        if (ul) ul.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function closeSiblings(li) {
        var parent = li.parentElement;
        if (!parent) return;
        Array.prototype.forEach.call(parent.children, function (sib) {
            if (sib !== li && sib.classList.contains('sbv2-open')) openLi(sib, false);
        });
    }

    function markCurrent() {
        var here = location.pathname.replace(/\/+$/, '');
        var found = null;
        var links = document.querySelectorAll('#sidebar-menu li > a[href]');
        Array.prototype.forEach.call(links, function (a) {
            var href = a.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#' || /^javascript:/i.test(href)) return;
            var u;
            try { u = new URL(a.href, location.href); } catch (e) { return; }
            if (u.origin !== location.origin) return;
            if (u.pathname.replace(/\/+$/, '') !== here) return;
            // a link with a query string only matches the same query
            if (u.search && u.search !== location.search) return;
            if (!found || (u.search && u.search === location.search)) found = a;
        });
        if (found) {
            var li = found.parentElement;
            li.classList.add('sbv2-current');
            for (var p = li.parentElement; p && p.id !== 'sidebar-menu'; p = p.parentElement) {
                if (p.tagName === 'LI') { p.classList.add('sbv2-current-branch'); openLi(p, true); }
            }
            return found;
        }
        // fall back to the server-rendered active branch
        Array.prototype.forEach.call(document.querySelectorAll('#sidebar-menu li.mm-active'), function (li) {
            if (hasSub(li)) openLi(li, true);
        });
        return null;
    }

    function labelFor(a) {
        var s = a.querySelector(':scope > span:not(.fa):not(.badge):not(.badgez)');
        return ((s || a).textContent || '').replace(/\s+/g, ' ').trim();
    }

    function init() {
        // stop slimScroll fighting the CSS scroll container
        try {
            if (window.jQuery && jQuery.fn.slimScroll) jQuery('.slimscroll-menu').slimScroll({ destroy: true });
        } catch (e) { /* not initialised */ }
        try {
            if (window.jQuery && jQuery.fn.metisMenu) jQuery('#side-menu').metisMenu('dispose');
        } catch (e) { /* not initialised */ }

        backdrop = document.createElement('div');
        backdrop.className = 'sbv2-backdrop';
        document.body.appendChild(backdrop);
        backdrop.addEventListener('click', function () { setDrawer(false); });

        Array.prototype.forEach.call(document.querySelectorAll('#sidebar-menu li > a'), function (a) {
            var t = labelFor(a);
            if (t && !a.title) a.title = t;
        });

        var current = markCurrent();
        setRail(true);
        if (current) {
            // make sure the current item is visible in the scroll area
            setTimeout(function () { try { current.scrollIntoView({ block: 'nearest' }); } catch (e) { /* old browser */ } }, 60);
        }
    }

    // Capture phase + stopPropagation: legacy metisMenu / button handlers never see these clicks.
    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.closest) return;

        var burger = t.closest('.button-menu-mobile');
        if (burger) {
            e.preventDefault();
            e.stopPropagation();
            if (isDesktop()) setPeek(!isPeek()); else setDrawer(!body.classList.contains('sbv2-drawer'));
            return;
        }

        var a = t.closest('#sidebar-menu li > a');
        if (!a) { if (isPeek() && !t.closest('.left.side-menu')) setPeek(false); return; }
        var li = a.parentElement;

        if (hasSub(li)) {
            e.preventDefault();
            e.stopPropagation();
            if (isRail() && !isPeek()) { setPeek(true); closeSiblings(li); openLi(li, true); return; }
            var willOpen = !li.classList.contains('sbv2-open');
            if (willOpen) closeSiblings(li);
            openLi(li, willOpen);
            if (willOpen) { try { li.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch (err) { /* old browser */ } }
            return;
        }

        // leaf link: close the drawer on mobile (navigation follows normally)
        if (!isDesktop()) setDrawer(false); else setPeek(false);
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { setDrawer(false); setPeek(false); }
    });

    var onChange = function () {
        if (isDesktop()) { setDrawer(false); setRail(true); }
        else { body.classList.remove('sbv2-rail'); }
    };
    if (mq.addEventListener) mq.addEventListener('change', onChange); else if (mq.addListener) mq.addListener(onChange);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
    // slimScroll/metisMenu initialise at the end of jquery.app.js - undo them once everything has loaded
    window.addEventListener('load', function () {
        try { if (window.jQuery && jQuery.fn.slimScroll) jQuery('.slimscroll-menu').slimScroll({ destroy: true }); } catch (e) { /* ignore */ }
        try { if (window.jQuery && jQuery.fn.metisMenu) jQuery('#side-menu').metisMenu('dispose'); } catch (e) { /* ignore */ }
        stripEnlarged();
    });
})();
