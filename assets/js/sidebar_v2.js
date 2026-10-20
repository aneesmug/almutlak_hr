/* Sidebar v2 behaviour: accordion, icon rail with hover flyouts (desktop), bottom tab bar + menu sheet (mobile).
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
        var s = (a.querySelector(':scope > span:not(.fa):not(.badge):not(.badgez)') || a).cloneNode(true);
        // some labels carry their count badge inside the span ("Vacations <span class=badgez>16</span>")
        Array.prototype.forEach.call(s.querySelectorAll('.badge, .badgez, .fa'), function (b) { b.remove(); });
        return (s.textContent || '').replace(/\s+/g, ' ').trim();
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

    // ---------- Mobile (< 992px): bottom tab bar + drill-down bottom sheet ----------
    // Both are built from #side-menu, so they show exactly the items (and badges) the server
    // rendered for this user. The first TAB_COUNT top-level items become tabs; "More" (and the
    // top bar hamburger) opens the sheet with every item. Groups drill down one level per tap.
    var TAB_COUNT = 4;
    var tabbar, sheet, sheetBackdrop, sheetStack = [];

    function topItems() {
        return Array.prototype.filter.call(document.querySelectorAll('#side-menu > li'), function (li) {
            return !!li.querySelector(':scope > a');
        });
    }
    function subItems(ul) {
        return Array.prototype.filter.call(ul.children, function (li) {
            return li.tagName === 'LI' && !li.classList.contains('sbv2-fly-title') && !!li.querySelector(':scope > a');
        });
    }
    function isCurrent(li) { return li.classList.contains('sbv2-current') || li.classList.contains('sbv2-current-branch'); }
    function cloneIcon(a) {
        var i = a.querySelector(':scope > i');
        var c = i ? i.cloneNode(true) : document.createElement('i');
        if (!i) c.className = 'fa-duotone fa-circle-dot';
        c.setAttribute('aria-hidden', 'true');
        return c;
    }
    function badgeOf(a) {
        var b = a.querySelector('.badgez, .badge');
        var n = b ? (b.textContent || '').trim() : '';
        if (!n || n === '0') return null;
        var s = document.createElement('span');
        s.className = 'sbv2-m-badge';
        s.textContent = n;
        return s;
    }
    // leaf -> real <a> (keeps href/target); group -> <button> that drills down
    function itemEl(li, cls) {
        var a = li.querySelector(':scope > a');
        var group = hasSub(li);
        var el = document.createElement(group ? 'button' : 'a');
        el.className = cls + (isCurrent(li) ? ' is-current' : '');
        if (group) { el.type = 'button'; el._sbv2Li = li; }
        else {
            el.href = a.getAttribute('href');
            if (a.target) el.target = a.target;
        }
        var c = a.style.getPropertyValue('--sb-item');
        if (c) el.style.setProperty('--sb-item', c);
        return el;
    }

    function buildTabbar() {
        var items = topItems();
        if (!items.length) return;
        tabbar = document.createElement('nav');
        tabbar.className = 'sbv2-tabbar';
        tabbar.setAttribute('aria-label', 'Main menu');
        var anyCurrent = false, tabbed = items.slice(0, items.length > TAB_COUNT + 1 ? TAB_COUNT : TAB_COUNT + 1);
        tabbed.forEach(function (li) {
            var a = li.querySelector(':scope > a');
            var el = itemEl(li, 'sbv2-tab');
            var ic = document.createElement('span');
            ic.className = 'sbv2-tab-ic';
            ic.appendChild(cloneIcon(a));
            var b = badgeOf(a);
            if (b) ic.appendChild(b);
            var lb = document.createElement('span');
            lb.className = 'sbv2-tab-lb';
            lb.textContent = labelFor(a);
            el.appendChild(ic);
            el.appendChild(lb);
            if (isCurrent(li)) { anyCurrent = true; el.setAttribute('aria-current', 'page'); }
            tabbar.appendChild(el);
        });
        if (items.length > tabbed.length) {
            var more = document.createElement('button');
            more.type = 'button';
            more.className = 'sbv2-tab sbv2-tab-more';
            more._sbv2More = true;
            var rest = items.slice(tabbed.length);
            if (!anyCurrent && rest.some(isCurrent)) more.classList.add('is-current');
            // sum of the badges hidden behind "More"
            var total = rest.reduce(function (n, li) {
                var b = badgeOf(li.querySelector(':scope > a'));
                return n + (b ? (parseInt(b.textContent, 10) || 0) : 0);
            }, 0);
            more.innerHTML = '<span class="sbv2-tab-ic"><i class="fa-solid fa-grid-2" aria-hidden="true"></i>' +
                (total ? '<span class="sbv2-m-badge">' + total + '</span>' : '') + '</span><span class="sbv2-tab-lb"></span>';
            more.querySelector('.sbv2-tab-lb').textContent = moreLabel();
            tabbar.appendChild(more);
        }
        tabbar.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('button.sbv2-tab');
            if (!btn) return;
            e.preventDefault();
            if (btn._sbv2More) openSheet(null); else openSheet(btn._sbv2Li);
        });
        document.body.appendChild(tabbar);
        body.classList.add('sbv2-has-tabbar');
    }
    function moreLabel() { return isRtl() ? 'المزيد' : 'More'; }
    function menuLabel() { return isRtl() ? 'القائمة' : 'Menu'; }

    function buildSheet() {
        sheetBackdrop = document.createElement('div');
        sheetBackdrop.className = 'sbv2-sheet-backdrop';
        sheetBackdrop.addEventListener('click', closeSheet);
        sheet = document.createElement('div');
        sheet.className = 'sbv2-sheet';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-modal', 'true');
        sheet.setAttribute('aria-hidden', 'true');
        sheet.innerHTML =
            '<div class="sbv2-sheet-grip" aria-hidden="true"></div>' +
            '<div class="sbv2-sheet-head">' +
                '<button type="button" class="sbv2-sheet-btn sbv2-sheet-back" aria-label="Back"><i class="fa-solid fa-chevron-left"></i></button>' +
                '<div class="sbv2-sheet-title"></div>' +
                '<button type="button" class="sbv2-sheet-btn sbv2-sheet-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>' +
            '<div class="sbv2-sheet-body"></div>';
        sheet.querySelector('.sbv2-sheet-back').addEventListener('click', function () { sheetStack.pop(); renderSheet(-1); });
        sheet.querySelector('.sbv2-sheet-close').addEventListener('click', closeSheet);
        sheet.querySelector('.sbv2-sheet-body').addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('button[class*="sbv2-sheet-"]');
            if (btn && btn._sbv2Li) { sheetStack.push(btn._sbv2Li); renderSheet(1); return; }
            if (e.target.closest && e.target.closest('a')) closeSheet();
        });
        // drag the grip/header down to dismiss
        var startY = null, dy = 0;
        function down(e) { startY = (e.touches ? e.touches[0] : e).clientY; dy = 0; sheet.style.transition = 'none'; }
        function move(e) {
            if (startY === null) return;
            dy = Math.max(0, (e.touches ? e.touches[0] : e).clientY - startY);
            sheet.style.transform = 'translateY(' + dy + 'px)';
        }
        function up() {
            if (startY === null) return;
            startY = null;
            sheet.style.transition = '';
            sheet.style.transform = '';
            if (dy > 90) closeSheet();
        }
        ['.sbv2-sheet-grip', '.sbv2-sheet-head'].forEach(function (sel) {
            var h = sheet.querySelector(sel);
            h.addEventListener('touchstart', down, { passive: true });
            h.addEventListener('touchmove', move, { passive: true });
            h.addEventListener('touchend', up);
        });
        document.body.appendChild(sheetBackdrop);
        document.body.appendChild(sheet);
    }

    function renderSheet(dir) {
        var level = sheetStack[sheetStack.length - 1]; // null = root
        var bodyEl = sheet.querySelector('.sbv2-sheet-body');
        var title = sheet.querySelector('.sbv2-sheet-title');
        bodyEl.innerHTML = '';
        sheet.classList.toggle('is-root', !level);
        if (!level) {
            title.textContent = menuLabel();
            var grid = document.createElement('div');
            grid.className = 'sbv2-sheet-grid';
            topItems().forEach(function (li) {
                var a = li.querySelector(':scope > a');
                var el = itemEl(li, 'sbv2-sheet-tile');
                var ic = document.createElement('span');
                ic.className = 'sbv2-sheet-ic';
                ic.appendChild(cloneIcon(a));
                var b = badgeOf(a);
                if (b) ic.appendChild(b);
                var lb = document.createElement('span');
                lb.className = 'sbv2-sheet-lb';
                lb.textContent = labelFor(a);
                el.appendChild(ic);
                el.appendChild(lb);
                grid.appendChild(el);
            });
            bodyEl.appendChild(grid);
        } else {
            title.textContent = labelFor(level.querySelector(':scope > a'));
            var list = document.createElement('div');
            list.className = 'sbv2-sheet-list';
            subItems(level.querySelector(':scope > ul')).forEach(function (li) {
                var a = li.querySelector(':scope > a');
                var el = itemEl(li, 'sbv2-sheet-row');
                var ic = document.createElement('span');
                ic.className = 'sbv2-sheet-ic';
                ic.appendChild(cloneIcon(a));
                var lb = document.createElement('span');
                lb.className = 'sbv2-sheet-lb';
                lb.textContent = labelFor(a);
                el.appendChild(ic);
                el.appendChild(lb);
                var b = badgeOf(a);
                if (b) el.appendChild(b);
                if (hasSub(li)) {
                    var ch = document.createElement('i');
                    ch.className = 'fa-solid fa-chevron-right sbv2-sheet-chev';
                    ch.setAttribute('aria-hidden', 'true');
                    el.appendChild(ch);
                }
                list.appendChild(el);
            });
            bodyEl.appendChild(list);
        }
        sheet.setAttribute('aria-label', title.textContent);
        bodyEl.scrollTop = 0;
        bodyEl.classList.remove('sbv2-in-fwd', 'sbv2-in-back');
        if (dir) { void bodyEl.offsetWidth; bodyEl.classList.add(dir > 0 ? 'sbv2-in-fwd' : 'sbv2-in-back'); }
        var first = bodyEl.querySelector('a, button');
        if (first && body.classList.contains('sbv2-sheet-open')) { try { first.focus({ preventScroll: true }); } catch (e) { /* old browser */ } }
    }

    function openSheet(groupLi) {
        if (!sheet) return;
        sheetStack = groupLi ? [groupLi] : [];
        body.classList.add('sbv2-sheet-open');
        sheet.setAttribute('aria-hidden', 'false');
        renderSheet(0);
    }
    function closeSheet() {
        if (!sheet || !body.classList.contains('sbv2-sheet-open')) return;
        body.classList.remove('sbv2-sheet-open');
        sheet.setAttribute('aria-hidden', 'true');
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
        buildTabbar();
        buildSheet();
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
            // mobile: the hamburger opens the full menu sheet (old drawer only if the sheet failed to build)
            if (isDesktop()) setPeek(!isPeek());
            else if (sheet) { if (body.classList.contains('sbv2-sheet-open')) closeSheet(); else openSheet(null); }
            else setDrawer(!body.classList.contains('sbv2-drawer'));
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
        if (e.key === 'Escape') { setDrawer(false); setPeek(false); closeAllFly(); closeSheet(); }
    });

    var onChange = function () {
        if (isDesktop()) { setDrawer(false); closeSheet(); setRail(true); }
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
