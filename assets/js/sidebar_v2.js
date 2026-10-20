/* Sidebar v2 behaviour: accordion, icon rail with hover flyouts (desktop), off-canvas drawer (mobile).
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
    function setPeek(on) { body.classList.toggle("sbv2-peek", !!on && isDesktop()); if (on) closeAllFly(); }
    function isPeek() { return body.classList.contains("sbv2-peek"); }
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
            if (hasSub(li)) { openLi(li, true); li.classList.add('sbv2-current-branch'); }
        });
        return null;
    }

    function labelFor(a) {
        var s = a.querySelector(':scope > span:not(.fa):not(.badge):not(.badgez)');
        return ((s || a).textContent || '').replace(/\s+/g, ' ').trim();
    }

    // ---------- Rail hover flyouts (desktop icon rail, not while peeking) ----------
    // A group's own <ul> is shown position:fixed beside the rail (escapes the sidebar's
    // overflow clipping); nested groups fly out beside their parent flyout the same way.
    var FLY_CLOSE_DELAY = 250; // grace period to cross the gap between rail and flyout
    var tip;

    function flyMode() { return isRail() && !isPeek(); }
    function isRtl() { return (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl'; }

    function clearFlyPos(ul) {
        ['left', 'top', 'visibility'].forEach(function (p) { ul.style.removeProperty(p); });
    }
    function placeFly(li, ul) {
        var topLevel = li.parentElement && li.parentElement.id === 'side-menu';
        // horizontal anchor: the rail for top-level groups, the parent flyout for nested ones
        var anchor = (topLevel ? li.closest('.left.side-menu') : li.parentElement).getBoundingClientRect();
        var row = li.getBoundingClientRect();
        var gap = topLevel ? 8 : 6;
        ul.style.setProperty('visibility', 'hidden', 'important');
        ul.style.setProperty('left', '0px', 'important');
        ul.style.setProperty('top', '0px', 'important');
        var w = ul.offsetWidth, h = ul.offsetHeight;
        var vw = document.documentElement.clientWidth, vh = window.innerHeight;
        var after = anchor.right + gap, before = anchor.left - gap - w;
        var left = isRtl() ? (before >= 8 ? before : after) : (after + w <= vw - 8 ? after : before);
        var top = row.top - (topLevel ? 0 : 8);
        if (top + h > vh - 8) top = Math.max(8, vh - 8 - h);
        ul.style.setProperty('left', Math.max(8, left) + 'px', 'important');
        ul.style.setProperty('top', top + 'px', 'important');
        ul.style.removeProperty('visibility');
        // arrow (li::after) on the flyout edge facing its parent, centred on the parent row
        var ulLeft = Math.max(8, left), toRight = ulLeft >= anchor.right;
        li.style.setProperty('--sbv2-arrow-x', (toRight ? ulLeft : ulLeft + w) + 'px');
        li.style.setProperty('--sbv2-arrow-y', Math.min(Math.max(row.top + row.height / 2, top + 14), top + h - 14) + 'px');
        li.classList.toggle('sbv2-fly-left', !toRight);
    }
    function openFly(li) {
        clearTimeout(li._sbv2T);
        if (li.classList.contains('sbv2-fly-open')) return;
        Array.prototype.forEach.call(li.parentElement.children, function (sib) { if (sib !== li) closeFly(sib); });
        var ul = li.querySelector(':scope > ul');
        if (!ul) return;
        hideTip();
        li.classList.add('sbv2-fly-open');
        placeFly(li, ul);
    }
    function closeFly(li) {
        clearTimeout(li._sbv2T);
        if (!li.classList.contains('sbv2-fly-open')) return;
        Array.prototype.forEach.call(li.querySelectorAll('li.sbv2-fly-open'), closeFly);
        li.classList.remove('sbv2-fly-open');
        var ul = li.querySelector(':scope > ul');
        if (ul) clearFlyPos(ul);
    }
    function closeAllFly() {
        Array.prototype.forEach.call(document.querySelectorAll('#side-menu > li.sbv2-fly-open'), closeFly);
        hideTip();
    }

    function showTip(a) {
        var text = labelFor(a);
        if (!text) return;
        if (!tip) { tip = document.createElement('div'); tip.className = 'sbv2-tip'; tip.setAttribute('aria-hidden', 'true'); document.body.appendChild(tip); }
        tip.textContent = text;
        var r = a.getBoundingClientRect(), rail = a.closest('.left.side-menu').getBoundingClientRect();
        tip.classList.add('show');
        tip.style.left = (isRtl() ? rail.left - 8 - tip.offsetWidth : rail.right + 8) + 'px';
        tip.style.top = (r.top + (r.height - tip.offsetHeight) / 2) + 'px';
    }
    function hideTip() { if (tip) tip.classList.remove('show'); }

    function initFlyouts() {
        // group name heading at the top of each top-level flyout (hidden in the wide/accordion layouts)
        Array.prototype.forEach.call(document.querySelectorAll('#side-menu > li'), function (li) {
            var ul = li.querySelector(':scope > ul'), a = li.querySelector(':scope > a');
            if (!ul || !a || ul.querySelector(':scope > .sbv2-fly-title')) return;
            var h = document.createElement('li');
            h.className = 'sbv2-fly-title';
            h.setAttribute('aria-hidden', 'true');
            h.textContent = labelFor(a);
            ul.insertBefore(h, ul.firstChild);
        });

        Array.prototype.forEach.call(document.querySelectorAll('#sidebar-menu li'), function (li) {
            var a = li.querySelector(':scope > a');
            if (!a) return;
            if (hasSub(li)) {
                li.addEventListener('mouseenter', function () { if (flyMode()) openFly(li); });
                li.addEventListener('mouseleave', function () {
                    if (!flyMode()) return;
                    clearTimeout(li._sbv2T);
                    li._sbv2T = setTimeout(function () { closeFly(li); }, FLY_CLOSE_DELAY);
                });
            } else if (li.parentElement && li.parentElement.id === 'side-menu') {
                // top-level single link: just a name tooltip
                li.addEventListener('mouseenter', function () {
                    if (!flyMode()) return;
                    Array.prototype.forEach.call(li.parentElement.children, function (sib) { if (sib !== li) closeFly(sib); });
                    showTip(a);
                });
                li.addEventListener('mouseleave', hideTip);
            }
        });

        // keyboard: tabbing inside a flyout keeps its ancestor flyouts open; tabbing away closes them
        document.addEventListener('focusin', function (e) {
            if (!flyMode()) return;
            var t = e.target;
            if (!t.closest || !t.closest('#sidebar-menu')) { closeAllFly(); return; }
            var own = t.parentElement, chain = [];
            for (var p = own && own.parentElement; p && p.id !== 'side-menu'; p = p.parentElement) {
                if (p.tagName === 'LI' && hasSub(p)) chain.unshift(p);
            }
            if (!chain.length) { if (!hasSub(own)) closeAllFly(); return; } // a group's own flyout opens on hover/click
            chain.forEach(openFly);
        });

        var nav = document.getElementById('sidebar-menu');
        if (nav) nav.addEventListener('scroll', closeAllFly, { passive: true });
        window.addEventListener('resize', closeAllFly);
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
            // aria-label, not title: a native tooltip would sit on top of the hover flyouts
            var t = labelFor(a);
            if (t && !a.getAttribute('aria-label')) a.setAttribute('aria-label', t);
            // per-item colour (the icon's inline duotone colour) drives the rail's active block
            var i = a.querySelector(':scope > i');
            var c = i && i.style.getPropertyValue('--fa-primary-color');
            if (c) a.style.setProperty('--sb-item', c.trim());
        });
        initFlyouts();

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
        if (!a) {
            if (!t.closest('#sidebar-menu')) closeAllFly();
            if (isPeek() && !t.closest('.left.side-menu')) setPeek(false);
            return;
        }
        var li = a.parentElement;

        if (hasSub(li)) {
            e.preventDefault();
            e.stopPropagation();
            // rail: groups open as hover flyouts; a click (touch / no hover) toggles the same flyout
            if (flyMode()) { if (li.classList.contains('sbv2-fly-open')) closeFly(li); else openFly(li); return; }
            var willOpen = !li.classList.contains('sbv2-open');
            if (willOpen) closeSiblings(li);
            openLi(li, willOpen);
            if (willOpen) { try { li.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); } catch (err) { /* old browser */ } }
            return;
        }

        // leaf link: close the drawer on mobile (navigation follows normally)
        closeAllFly();
        if (!isDesktop()) setDrawer(false); else setPeek(false);
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { setDrawer(false); setPeek(false); closeAllFly(); }
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
