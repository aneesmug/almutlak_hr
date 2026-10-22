/*
 * Odometer / slot-roll animation for KPI / stat tile numbers on every page (dashboard KPIs,
 * sr-stat, sr-tile, sr-scard status cards, tab counters, Swal stat popups ...): each digit
 * is a vertical strip of 0-9 that slides to its value.
 * Injected by includes/app_datepicker_head.php - pages do not link it themselves.
 *
 * - Digits roll up from 0 the first time the tile scrolls into view (hidden tabs roll when
 *   they are shown).
 * - Values filled or changed later by AJAX ("–" -> 12, 12 -> 15) roll from the old value.
 * - Prefix/suffix text (%, SAR, "days"), thousands separators, decimals and Arabic-Indic
 *   digits are kept as printed. Text with more than one number (dates, "3 / 5") and IDs /
 *   codes (EMP-0012, #45, 0045) are left alone.
 * - The real number stays in the DOM as visually-hidden text, so screen readers, copy,
 *   textContent and the Arabic-digit pass still see "1,234".
 *
 * - Stat cards (CARD_SELECTOR) slide up + fade in one after another; the numbers and bars
 *   inside each card start together with their card. Opt in other cards with data-rise.
 *
 * Opt in any other element: add attribute data-count-up.
 * Opt out: add class "no-count-up" or attribute data-no-count-up.
 */
(function () {
    'use strict';

    if (!window.requestAnimationFrame || !document.querySelectorAll || !Element.prototype.closest) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var SELECTOR = [
        '[data-count-up]',
        '.dash-kpi-value', '.dash-kpi-foot b',
        '.sr-stat-value', '.sr-tile-value', '.sr-scard-value', '.sr-count',
        '.srs-stat-value', '.dbi-stat-value', '.dex-stat-value', '.pir-kpi-value', '.pv-fact-value',
        '.ve-loan-stat-value', '.payroll-import-result-stat-value', '.stats-card-count', '.summary-value',
        // Employee master: header quick stats (includes/emp_top_info.php) + salary card (view_employee.php)
        '.eh-stat b', '.sal-total > b', '.sal-item-text b', '.sal-item-pct',
        '.stats-card-count-circle', '.stats-card-pct', '.dx-count', '.dx-doc-num', '.dash-scope-count', '.dex-stat-sub b', '.vac-sum-tile b', '.vac-sum-bar-head > span:last-child',
        '.et-stat b', '.et-next-head > span', '.et-next-head > b', '.et-eos b'
    ].join(',');
    var SKIP = '.no-count-up, [data-no-count-up]';
    // Stat cards slide up + fade in one after another when they come into view.
    var CARD_SELECTOR = [
        '[data-rise]',
        '.dash-kpi', '.sr-stat', '.sr-tile', '.sr-scard', '.stats-card', '.doc-expiry-card', '.dash-scope-item',
        '.dex-stat', '.eh-stat', '.sal-total', '.sal-item', '.et-stat', '.et-eos',
        // view_employee.php: vacation summary, loan stats/boxes, document list
        '.vac-sum-tile', '.vac-sum-bar', '.ve-loan-stat', '.ve-loan-box', '.doc-item'
    ].join(',');
    var RISE_STAGGER = 80;   // ms between cards
    var RISE_MAX_DELAY = 800;
    // Progress bars on every page grow from 0 to their inline width when they come into view.
    // A bar = thin element with an inline width in % whose own or parent class names a
    // bar / progress / track / meter / gauge (dash-kpi-bar, et-bar, sal-bar, srh-progress,
    // fdb-bar, sr-scard-bar, Bootstrap .progress-bar, gauge-fill ...). Opt in anything else
    // with data-bar-grow.
    var BAR_SELECTOR = '[style*="width"]';
    var BAR_CLASS = /(^|[\s_-])(bar|bars|progress|progress-bar|fill|track|meter|gauge|seg|segment)($|[\s_-])/i;
    var NOT_BAR_CLASS = /(nav|side|tool|top|title|tabs?|search|action|scroll|menu|filter|status|app|button|btn)-?bar/i;
    var BAR_MAX_HEIGHT = 40;
    var BAR_DURATION = 1100;
    var BAR_STAGGER = 150;  // segments of a stacked bar (salary split) fill one after another
    var DURATION = 1100;   // ms for the leftmost digit; each digit to the right takes a bit longer
    var STAGGER = 120;
    var LOOPS = 3;          // the strip holds 0-9 this many times so low digits can spin past 9

    var NUM_GLOBAL = /[0-9٠-٩][0-9٠-٩,٬]*(?:[.٫][0-9٠-٩]+)?/g;
    var NUM_ONE = /[0-9٠-٩][0-9٠-٩,٬]*(?:[.٫][0-9٠-٩]+)?/;
    var AR_DIGITS = '٠١٢٣٤٥٦٧٨٩';

    (function injectCss() {
        var lat = '', ar = '';
        for (var l = 0; l < LOOPS; l++) {
            for (var d = 0; d < 10; d++) {
                lat += d + '\\A ';
                ar += AR_DIGITS.charAt(d) + '\\A ';
            }
        }
        var css = ''
            + '.sr-odo{display:inline-flex;align-items:flex-end;vertical-align:bottom;font-variant-numeric:tabular-nums;white-space:nowrap}'
            + '.sr-odo-sr{position:absolute!important;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0}'
            + '.sr-odo-vis{display:inline-flex;direction:ltr}'
            + '.sr-odo-d{display:inline-block;height:1.2em;line-height:1.2em;overflow:hidden;position:relative;'
            + '-webkit-mask-image:linear-gradient(transparent,#000 14%,#000 86%,transparent);mask-image:linear-gradient(transparent,#000 14%,#000 86%,transparent)}'
            + '.sr-odo-s{display:block;white-space:pre;text-align:center;will-change:transform}'
            + '.sr-odo-s::before{content:"' + lat + '"}'
            + '.sr-odo.is-ar .sr-odo-s::before{content:"' + ar + '"}'
            + '.sr-odo-c{display:inline-block;height:1.2em;line-height:1.2em}'
            + '.sr-odo-c::before{content:attr(data-c)}'
            + '.sr-rise{opacity:0;transform:translateY(18px)}'
            + '.sr-rise.sr-rise-in{animation:srRise .6s cubic-bezier(.22,.9,.3,1) both}'
            + '@keyframes srRise{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}';
        var style = document.createElement('style');
        style.id = 'sr-count-up-css';
        style.appendChild(document.createTextNode(css));
        (document.head || document.documentElement).appendChild(style);
    })();

    function toLatin(s) {
        return s.replace(/[٠-٩]/g, function (d) { return AR_DIGITS.indexOf(d); });
    }

    // Reads the single number shown in el. Returns null when there is none or more than one.
    function parse(el) {
        var all = (el.textContent || '').match(NUM_GLOBAL);
        if (!all || all.length !== 1) return null;
        var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null, false);
        var node;
        while ((node = walker.nextNode())) {
            var m = node.nodeValue.match(NUM_ONE);
            if (!m) continue;
            var raw = m[0], text = node.nodeValue;
            var prev = text.charAt(m.index - 1), next = text.charAt(m.index + raw.length);
            // Codes / IDs (EMP-0012, #45, A12, 12/3, 0045) are not counters.
            var neg = prev === '-' && !/\S/.test(text.charAt(m.index - 2));
            if ((prev === '-' && !neg) || /[A-Za-z؀-ۿ_\/#:.]/.test(prev) || /[_\/:\-]/.test(next)) return null;
            if (/^0[0-9]/.test(toLatin(raw))) return null;
            var decMatch = raw.match(/[.٫]([0-9٠-٩]+)$/);
            var value = parseFloat(toLatin(raw).replace(/[,٬]/g, '').replace('٫', '.'));
            if (isNaN(value)) return null;
            return {
                node: node,
                index: m.index,
                raw: raw,
                value: neg ? -value : value,
                fmt: {
                    decimals: decMatch ? decMatch[1].length : 0,
                    group: /[,٬]/.test(raw),
                    arabic: /[٠-٩]/.test(raw)
                }
            };
        }
        return null;
    }

    // Latin-digit string of n in the printed format (separators as , and .).
    function format(n, fmt) {
        var s = Math.abs(n).toFixed(fmt.decimals);
        if (fmt.group) {
            var parts = s.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            s = parts.join('.');
        }
        return s;
    }

    function digitsOf(s) {
        return s.replace(/\D/g, '');
    }

    // Swaps the number inside p.node for the odometer markup, positioned on `from`,
    // and returns a function that rolls it to p.value.
    function build(el, p, from) {
        var fmt = p.fmt;
        var target = format(p.value, fmt);
        var fromDigits = digitsOf(format(from, fmt));
        var toDigits = digitsOf(target);
        var fresh = from === 0;

        var wrap = document.createElement('span');
        wrap.className = 'sr-odo' + (fmt.arabic ? ' is-ar' : '');
        var sr = document.createElement('span');
        sr.className = 'sr-odo-sr';
        sr.textContent = p.raw;
        var vis = document.createElement('span');
        vis.className = 'sr-odo-vis';
        vis.setAttribute('aria-hidden', 'true');
        wrap.appendChild(sr);
        wrap.appendChild(vis);

        var strips = [];
        var digitIdx = 0;
        for (var i = 0; i < target.length; i++) {
            var ch = target.charAt(i);
            if (/\d/.test(ch)) {
                var posFromRight = toDigits.length - 1 - digitIdx;
                var fd = fromDigits.charAt(fromDigits.length - 1 - posFromRight);
                var startDigit = fd === '' ? 0 : +fd;
                var endDigit = +ch;
                // On first roll the low digits spin extra turns, like a slot reel.
                var loops = fresh ? Math.max(0, Math.min(LOOPS - 1, 2 - Math.floor(posFromRight / 2))) : (endDigit < startDigit ? 1 : 0);
                var box = document.createElement('span');
                box.className = 'sr-odo-d';
                var strip = document.createElement('span');
                strip.className = 'sr-odo-s';
                strip.style.transform = 'translateY(' + (-1.2 * startDigit) + 'em)';
                box.appendChild(strip);
                vis.appendChild(box);
                strips.push({ el: strip, end: endDigit + 10 * loops, order: digitIdx });
                digitIdx++;
            } else {
                var c = document.createElement('span');
                c.className = 'sr-odo-c';
                // Drawn by CSS so textContent stays the plain number.
                c.setAttribute('data-c', fmt.arabic ? (ch === ',' ? '٬' : ch === '.' ? '٫' : ch) : ch);
                vis.appendChild(c);
            }
        }

        var text = p.node.nodeValue;
        var after = text.slice(p.index + p.raw.length);
        var parent = p.node.parentNode;
        p.node.nodeValue = text.slice(0, p.index);
        parent.insertBefore(wrap, p.node.nextSibling);
        if (after) parent.insertBefore(document.createTextNode(after), wrap.nextSibling);

        return function roll() {
            void wrap.offsetHeight; // commit the start position before transitioning
            strips.forEach(function (s) {
                var dur = DURATION + s.order * STAGGER;
                s.el.style.transition = 'transform ' + dur + 'ms cubic-bezier(.17,.84,.32,1)';
                s.el.style.transform = 'translateY(' + (-1.2 * s.end) + 'em)';
            });
        };
    }

    // Replaces any earlier odometer in el with its plain number text.
    function unwrap(el) {
        Array.prototype.forEach.call(el.querySelectorAll('.sr-odo'), function (w) {
            var sr = w.querySelector('.sr-odo-sr');
            w.parentNode.replaceChild(document.createTextNode(sr ? sr.textContent : ''), w);
        });
        el.normalize();
    }

    function rollSoon(roll) {
        requestAnimationFrame(function () { requestAnimationFrame(roll); });
    }

    // Draws the odometer on `from` (0 or the previous value). Rolls now when the tile has
    // been seen, otherwise keeps the roll for the IntersectionObserver.
    function render(el, st) {
        st.busy = true;
        unwrap(el);
        var p = parse(el);
        if (p) {
            var roll = build(el, p, st.seen ? st.value : 0);
            st.value = p.value;
            if (st.seen) { st.roll = null; rollSoon(roll); } else { st.roll = roll; }
        } else {
            st.value = 0;
        }
        // Mutations from our own DOM swap (and the Arabic-digit pass on it) arrive after
        // this tick - ignore them.
        setTimeout(function () { st.busy = false; }, 0);
    }

    // Numbers / bars inside a card that is still sliding in start with that card.
    function riseDelayOf(el) {
        var card = el.closest('.sr-rise');
        return (card && card.__riseDelay) || 0;
    }

    function onVisible(el) {
        var st = el.__countUp;
        if (st) {
            st.seen = true;
            if (st.roll) {
                var roll = st.roll;
                st.roll = null;
                setTimeout(function () { rollSoon(roll); }, riseDelayOf(el));
            }
        } else if (el.__countBar != null) {
            growBar(el);
        }
    }

    function riseCard(card, delay) {
        card.__riseDelay = delay;
        card.style.animationDelay = delay + 'ms';
        card.classList.add('sr-rise-in');
        card.addEventListener('animationend', function done(e) {
            if (e.target !== card) return;
            card.removeEventListener('animationend', done);
            // Drop the classes so the card's own hover transform works again.
            card.classList.remove('sr-rise', 'sr-rise-in');
            card.style.animationDelay = '';
            card.__riseDelay = 0;
        });
    }

    // One IntersectionObserver batch: cards first (in page order, staggered), then the
    // numbers and bars, which pick up their card's delay.
    function onVisibleBatch(list) {
        var cards = list.filter(function (el) { return el.__rise; });
        cards.sort(function (a, b) { return a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1; });
        cards.forEach(function (card, i) { riseCard(card, Math.min(i * RISE_STAGGER, RISE_MAX_DELAY)); });
        list.forEach(function (el) { if (!el.__rise) onVisible(el); });
    }

    // The skeleton loader (sr_skeleton.js) keeps the content visibility:hidden while the page
    // loads - IntersectionObserver still reports it, so hold the animations until it is revealed.
    var waiting = [];
    function flushWaiting() {
        var list = waiting;
        waiting = [];
        onVisibleBatch(list);
    }
    window.addEventListener('sr:skeleton-done', flushWaiting);
    setTimeout(flushWaiting, 6500); // matches the skeleton's CSS fail-safe

    var io = window.IntersectionObserver ? new IntersectionObserver(function (entries) {
        var batch = [];
        entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            io.unobserve(e.target);
            batch.push(e.target);
        });
        if (document.documentElement.classList.contains('sr-sk-on')) Array.prototype.push.apply(waiting, batch);
        else onVisibleBatch(batch);
    }) : null;

    // First sight of a tile, or its text changed (AJAX fill, Arabic digit pass).
    function handle(el) {
        if (el.closest(SKIP) || el.closest('.sr-odo')) return;
        var st = el.__countUp || (el.__countUp = { value: 0, seen: !io, observed: false, roll: null });
        if (st.busy) return;
        var p = parse(el);
        if (!p) { st.value = 0; return; }
        var inOdo = p.node.parentNode && p.node.parentNode.classList && p.node.parentNode.classList.contains('sr-odo-sr');
        if (inOdo && p.value === st.value) return; // our own markup / digit-localised copy of it
        render(el, st);
        if (!st.seen && !st.observed) {
            st.observed = true;
            io.observe(el);
        }
    }

    function isBar(el) {
        if (el.hasAttribute('data-bar-grow')) return true;
        if (!/%\s*$/.test(el.style.width) || /^(TD|TH|TABLE|COL|IMG|INPUT|SELECT|TEXTAREA|IFRAME|CANVAS|SVG)$/i.test(el.tagName)) return false;
        var own = typeof el.className === 'string' ? el.className : '';
        var parent = el.parentElement && typeof el.parentElement.className === 'string' ? el.parentElement.className : '';
        if (!BAR_CLASS.test(own) && !BAR_CLASS.test(parent)) return false;
        if (NOT_BAR_CLASS.test(own) || NOT_BAR_CLASS.test(parent)) return false;
        return el.offsetHeight <= BAR_MAX_HEIGHT; // hidden (0) still counts - it grows when shown
    }

    function initBar(bar) {
        if (bar.__countBar != null || !io || bar.closest(SKIP) || !isBar(bar)) return;
        var w = bar.style.width;
        if (!w || parseFloat(w) === 0) return;
        bar.__countBar = w;
        bar.__countBarTransition = bar.style.transition;
        bar.style.transition = 'none';
        bar.style.animation = 'none'; // e.g. .progress-bar-fill-animated has its own CSS grow
        bar.style.width = '0';
        io.observe(bar);
    }

    function growBar(bar) {
        // Page JS changed the width meanwhile (live progress) - leave it alone.
        if (bar.style.width !== '0px' && bar.style.width !== '0') return restoreBar(bar);
        var delay = Array.prototype.indexOf.call(bar.parentNode.children, bar) * BAR_STAGGER + riseDelayOf(bar);
        void bar.offsetWidth;
        bar.style.transition = 'width ' + BAR_DURATION + 'ms cubic-bezier(.22,.9,.3,1) ' + delay + 'ms';
        bar.style.width = bar.__countBar;
        setTimeout(function () { restoreBar(bar); }, BAR_DURATION + delay + 50);
    }

    function initCard(card) {
        if (card.__rise || !io || card.closest(SKIP)) return;
        card.__rise = true;
        card.classList.add('sr-rise');
        io.observe(card);
    }

    function restoreBar(bar) {
        bar.style.transition = bar.__countBarTransition || '';
    }

    function scan(root) {
        if (root.nodeType !== 1) return;
        if (root.matches(CARD_SELECTOR)) initCard(root);
        Array.prototype.forEach.call(root.querySelectorAll(CARD_SELECTOR), initCard);
        if (root.matches(SELECTOR)) handle(root);
        if (root.matches(BAR_SELECTOR)) initBar(root);
        Array.prototype.forEach.call(root.querySelectorAll(SELECTOR), handle);
        Array.prototype.forEach.call(root.querySelectorAll(BAR_SELECTOR), initBar);
    }

    function start() {
        scan(document.body);
        new MutationObserver(function (records) {
            var touched = new Set();
            records.forEach(function (r) {
                var t = r.target.nodeType === 1 ? r.target : r.target.parentElement;
                var tile = t && t.closest(SELECTOR);
                if (tile) touched.add(tile);
                if (r.type === 'childList') {
                    Array.prototype.forEach.call(r.addedNodes, function (n) {
                        if (n.nodeType === 1 && !(tile && tile.contains(n))) scan(n);
                    });
                }
            });
            touched.forEach(handle);
        }).observe(document.body, { childList: true, subtree: true, characterData: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
