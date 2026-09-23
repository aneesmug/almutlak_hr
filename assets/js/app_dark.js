// Dark theme helper, loaded with assets/css/app_dark.css (see includes/theme_dark.php).
// app_dark.css covers the shared Bootstrap/theme classes, but almost every page also
// paints its own cards, rows and boxes white/grey/pastel through page <style> blocks
// and inline styles. Rather than chase each page, this reads what the browser
// actually renders and fixes it:
//  1. Backgrounds: any light background (white, grey, pastel - solid or gradient)
//     is repainted to a dark tone of the SAME hue, so a pale-red box becomes dark
//     red and a plain white card becomes the theme's dark surface. Strong colors
//     (yellow badges, green buttons, purple headers) are left as they are.
//  2. Borders: light borders become the theme border color.
//  3. Text: any text too close to the color behind it is lightened (or darkened)
//     until readable, keeping its hue.
// Everything is written as inline !important so page CSS can't win, remembered, and
// undone before printing so printouts stay white.
(function () {
    var SKIP = 'script, style, noscript, link, meta, br, svg, svg *, canvas, img, video, iframe, object, embed, option, .ad-keep, .ad-keep *';
    var LIGHT_L = 0.82;      // HSL lightness at/above which a background counts as "light"
    var BORDER_L = 0.78;
    var MIN_CONTRAST = 4.5;
    var BORDER = 'rgb(42, 51, 72)'; // --ad-border

    var original = new Map(); // element -> { prop: [value, priority] } for undo
    var bgCache = null;

    // ---------- color helpers ----------
    function parseRgba(str) {
        var m = str && str.match(/rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)(?:\s*[,/]\s*([\d.]+%?))?/);
        if (!m) return null;
        var a = m[4] === undefined ? 1 : (m[4].indexOf('%') > -1 ? parseFloat(m[4]) / 100 : parseFloat(m[4]));
        return [parseFloat(m[1]), parseFloat(m[2]), parseFloat(m[3]), a];
    }
    function lum(rgb) {
        var c = [rgb[0], rgb[1], rgb[2]].map(function (v) {
            v = v / 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
    }
    function contrast(l1, l2) {
        return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
    }
    function toHsl(rgb) {
        var r = rgb[0] / 255, g = rgb[1] / 255, b = rgb[2] / 255;
        var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, h = 0, s = 0;
        if (max !== min) {
            var d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
            h *= 60;
        }
        return [h, s, l];
    }
    function hslToRgb(h, s, l) {
        var c = (1 - Math.abs(2 * l - 1)) * s, x = c * (1 - Math.abs((h / 60) % 2 - 1)), m = l - c / 2, r, g, b;
        if (h < 60) { r = c; g = x; b = 0; } else if (h < 120) { r = x; g = c; b = 0; }
        else if (h < 180) { r = 0; g = c; b = x; } else if (h < 240) { r = 0; g = x; b = c; }
        else if (h < 300) { r = x; g = 0; b = c; } else { r = c; g = 0; b = x; }
        return [Math.round((r + m) * 255), Math.round((g + m) * 255), Math.round((b + m) * 255)];
    }
    // White -> dark surface; slightly darker greys -> slightly lighter dark tones,
    // so light-on-light layering (card > row > chip) survives as dark layering.
    function darkTone(rgb) {
        var hsl = toHsl(rgb);
        var h = hsl[0], s = hsl[1];
        if (s < 0.12) { h = 222; s = 0.3; }      // neutral white/grey -> theme's blue-grey
        else { s = Math.min(s, 0.75) * 0.6; }    // pastel -> muted dark tint of same hue
        var l = Math.min(0.28, 0.12 + (1 - hsl[2]) * 0.7);
        var out = hslToRgb(h, s, l);
        return rgb[3] < 1 ? 'rgba(' + out.join(',') + ',' + rgb[3] + ')' : 'rgb(' + out.join(',') + ')';
    }
    function isLight(rgb, threshold) {
        return rgb && rgb[3] >= 0.5 && toHsl(rgb)[2] >= threshold;
    }

    // ---------- inline overrides with undo ----------
    function setProp(el, prop, value) {
        var rec = original.get(el);
        if (!rec) { rec = {}; original.set(el, rec); }
        if (!(prop in rec)) rec[prop] = [el.style.getPropertyValue(prop), el.style.getPropertyPriority(prop)];
        el.style.setProperty(prop, value, 'important');
    }
    function restore(el) {
        var rec = original.get(el);
        if (!rec) return;
        Object.keys(rec).forEach(function (prop) {
            if (rec[prop][0]) el.style.setProperty(prop, rec[prop][0], rec[prop][1]);
            else el.style.removeProperty(prop);
        });
        original.delete(el);
    }
    function restoreAll() {
        Array.from(original.keys()).forEach(restore);
    }

    // ---------- passes ----------
    function elementsIn(root) {
        var list = Array.prototype.slice.call(root.querySelectorAll('*'));
        if (root !== document.body) list.unshift(root);
        return list.filter(function (el) { return !el.matches(SKIP); });
    }

    function repaint(el) {
        var cs = window.getComputedStyle(el);
        var bg = parseRgba(cs.backgroundColor);
        if (isLight(bg, LIGHT_L)) setProp(el, 'background-color', darkTone(bg));

        var img = cs.backgroundImage;
        if (img && img.indexOf('gradient') > -1) {
            var changed = false;
            var next = img.replace(/rgba?\([^)]+\)/g, function (s) {
                var c = parseRgba(s);
                if (isLight(c, LIGHT_L)) { changed = true; return darkTone(c); }
                return s;
            });
            if (changed) setProp(el, 'background-image', next);
        }

        var sides = ['Top', 'Right', 'Bottom', 'Left'];
        for (var i = 0; i < sides.length; i++) {
            if (parseFloat(cs['border' + sides[i] + 'Width']) > 0 && isLight(parseRgba(cs['border' + sides[i] + 'Color']), BORDER_L)) {
                setProp(el, 'border-color', BORDER);
                break;
            }
        }
    }

    // Luminance of what's really behind el (first painted ancestor). undefined = photo background.
    function ownBackground(cs) {
        var img = cs.backgroundImage;
        if (img && img !== 'none') {
            if (img.indexOf('gradient') === -1) return undefined;
            var stops = img.match(/rgba?\([^)]+\)/g) || [], sum = 0, n = 0;
            stops.forEach(function (s) { var c = parseRgba(s); if (c && c[3] >= 0.5) { sum += lum(c); n++; } });
            if (n) return sum / n;
        }
        var bg = parseRgba(cs.backgroundColor);
        return bg && bg[3] >= 0.5 ? lum(bg) : null;
    }
    function effectiveBackground(el) {
        var chain = [], result = null;
        for (var node = el; node && node.nodeType === 1; node = node.parentElement) {
            if (bgCache.has(node)) { result = bgCache.get(node); break; }
            chain.push(node);
            var own = ownBackground(window.getComputedStyle(node));
            if (own !== null) { result = own; break; }
        }
        if (result === null) result = 0.01; // page background
        chain.forEach(function (n) { bgCache.set(n, result); });
        return result;
    }
    function hasOwnText(el) {
        if (el.matches('input, textarea, select')) return true;
        for (var n = el.firstChild; n; n = n.nextSibling) {
            if (n.nodeType === 3 && n.nodeValue.trim()) return true;
        }
        // Icon fonts (<i class="fa ...">) have no text node but still draw a glyph.
        return el.tagName === 'I' && !el.firstElementChild;
    }
    function readableColor(rgb, bgLum) {
        var target = bgLum < 0.4 ? 255 : 0;
        for (var t = 0.1; t <= 1.0001; t += 0.1) {
            var mixed = [0, 1, 2].map(function (k) { return Math.round(rgb[k] + (target - rgb[k]) * t); });
            if (contrast(lum(mixed), bgLum) >= MIN_CONTRAST) return 'rgb(' + mixed.join(',') + ')';
        }
        return target ? '#e2e8f0' : '#1e293b';
    }
    function fixText(el) {
        if (!hasOwnText(el)) return;
        var cs = window.getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden') return;
        var fg = parseRgba(cs.color);
        if (!fg || fg[3] < 0.3) return;
        var bgLum = effectiveBackground(el);
        if (bgLum === undefined) return;
        // Icons only need to be visible, not body-text contrast.
        var need = el.tagName === 'I' ? 3 : MIN_CONTRAST;
        if (contrast(lum(fg), bgLum) >= need) return;
        setProp(el, 'color', readableColor(fg, bgLum));
    }

    function process(root) {
        var els = elementsIn(root);
        // Transitions off while we work: pages use `transition: all`, and undoing our
        // own inline color to re-read the page's original would otherwise start a
        // transition, making getComputedStyle report the old (dark) value mid-fade.
        // The element then looks "already dark", is skipped, and fades back to white
        // (seen on includes/employee_card.php's .employee-card-modern).
        var html = document.documentElement;
        html.classList.add('ad-busy');
        void html.offsetWidth;          // apply "no transitions" before touching styles
        els.forEach(restore);          // judge each element from its own styles again
        els.forEach(repaint);
        bgCache = new Map();
        els.forEach(fixText);
        bgCache = null;
        void html.offsetWidth;          // settle final colors while transitions are still off
        html.classList.remove('ad-busy');
    }

    // ---------- scheduling: only re-check what changed ----------
    var dirty = new Set();
    var pending = false;
    var observer = new MutationObserver(function (records) {
        records.forEach(function (r) { if (r.target.nodeType === 1) dirty.add(r.target); });
        schedule();
    });
    function observe() {
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'] });
    }
    function schedule() {
        if (pending) return;
        pending = true;
        (window.requestIdleCallback || function (cb) { return setTimeout(cb, 100); })(function () {
            pending = false;
            var roots = Array.from(dirty).filter(function (el) {
                if (!el.isConnected) return false;
                for (var p = el.parentElement; p; p = p.parentElement) if (dirty.has(p)) return false;
                return true;
            });
            dirty.clear();
            original.forEach(function (_, el) { if (!el.isConnected) original.delete(el); });
            observer.disconnect();
            patchStateRules(); // picks up <style> blocks added by scripts/plugins since last run
            roots.forEach(process); // observer is off, so our own writes don't re-trigger
            observe();
        }, { timeout: 300 });
    }
    function runAll() {
        observer.disconnect();
        patchStateRules();
        process(document.body);
        observe();
    }

    // ---------- state rules (:hover, :focus, ...) ----------
    // A hover/focus background never shows up as a DOM change, so the passes above
    // can't catch it: e.g. view_employee.php's `.profile-field:hover { background:
    // #f8fafc }` flashed white behind text already lightened for the dark card.
    // Read those rules straight from the page's stylesheets and add a dark copy of
    // each (screen only, !important) to a stylesheet of our own.
    var STATE_SELECTOR = /:(hover|focus|focus-within|focus-visible|active|checked)\b/;
    var stateSheet = null;
    var seenSheets = new WeakSet();
    var probe = null;

    function resolveColor(value) {
        if (!value || /^(transparent|inherit|initial|unset|currentcolor|none)$/i.test(value.trim())) return null;
        if (!probe) {
            probe = document.createElement('span');
            probe.style.display = 'none';
            document.body.appendChild(probe);
        }
        probe.style.color = '';
        probe.style.color = value;
        if (!probe.style.color) return null; // not a color the browser understands
        return parseRgba(window.getComputedStyle(probe).color);
    }

    function darkStateDeclarations(style) {
        var out = [];
        var bg = resolveColor(style.getPropertyValue('background-color'));
        if (isLight(bg, LIGHT_L)) out.push('background-color:' + darkTone(bg) + '!important');

        var img = style.getPropertyValue('background-image');
        if (img && img.indexOf('gradient') > -1) {
            var changed = false;
            var next = img.replace(/#[0-9a-f]{3,8}\b|rgba?\([^)]+\)|\b(white)\b/gi, function (tok) {
                var c = resolveColor(tok);
                if (isLight(c, LIGHT_L)) { changed = true; return darkTone(c); }
                return tok;
            });
            if (changed) out.push('background-image:' + next + '!important');
        }

        ['border-color', 'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color'].forEach(function (prop) {
            if (isLight(resolveColor(style.getPropertyValue(prop)), BORDER_L)) out.push(prop + ':' + BORDER + '!important');
        });

        // A state that paints dark text would now sit on a dark background.
        var fg = resolveColor(style.getPropertyValue('color'));
        if (fg && out.length && contrast(lum(fg), 0.015) < MIN_CONTRAST) out.push('color:' + readableColor(fg, 0.015) + '!important');
        return out;
    }

    // Split "a:hover, :is(b, c):focus" on top-level commas only.
    function splitSelectors(text) {
        var parts = [], depth = 0, start = 0;
        for (var i = 0; i < text.length; i++) {
            var ch = text[i];
            if (ch === '(' || ch === '[') depth++;
            else if (ch === ')' || ch === ']') depth--;
            else if (ch === ',' && depth === 0) { parts.push(text.slice(start, i).trim()); start = i + 1; }
        }
        parts.push(text.slice(start).trim());
        return parts.filter(Boolean);
    }

    function collectStateRules(rules, out) {
        for (var i = 0; i < rules.length; i++) {
            var rule = rules[i];
            if (rule.cssRules && !rule.selectorText) {           // @media / @supports groups
                if (rule.media && !window.matchMedia(rule.media.mediaText).matches) continue;
                collectStateRules(rule.cssRules, out);
            } else if (rule.selectorText && rule.style && STATE_SELECTOR.test(rule.selectorText)) {
                var decl = darkStateDeclarations(rule.style);
                if (decl.length) {
                    var sel = splitSelectors(rule.selectorText).map(function (s) {
                        return /^html\b/i.test(s) ? s.replace(/^html/i, 'html.app-dark') : 'html.app-dark ' + s;
                    }).join(',');
                    out.push(sel + '{' + decl.join(';') + '}');
                }
            }
        }
    }

    function patchStateRules() {
        var out = [];
        Array.prototype.forEach.call(document.styleSheets, function (sheet) {
            if (seenSheets.has(sheet) || sheet.ownerNode === stateSheet) return;
            var rules;
            try { rules = sheet.cssRules; } catch (e) { return; } // cross-origin CDN sheet - unreadable
            if (!rules) return;
            seenSheets.add(sheet);
            collectStateRules(rules, out);
        });
        if (!out.length) return;
        if (!stateSheet) {
            stateSheet = document.createElement('style');
            stateSheet.media = 'screen';
            stateSheet.id = 'ad-state-rules';
            document.head.appendChild(stateSheet);
        }
        stateSheet.appendChild(document.createTextNode(out.join('\n') + '\n'));
    }

    // Switch the whole theme off/on - for printing, and for pages that screenshot
    // part of themselves into something sent out (send_announcement.php's
    // html2canvas email image), which must stay light:
    //   AppDark.suspend(); html2canvas(...).finally(AppDark.resume);
    var suspended = 0;
    window.AppDark = {
        suspend: function () {
            if (suspended++) return;
            observer.disconnect();
            restoreAll();
            document.documentElement.classList.remove('app-dark');
        },
        resume: function () {
            if (!suspended || --suspended) return;
            document.documentElement.classList.add('app-dark');
            runAll();
        }
    };

    // Printouts stay white.
    window.addEventListener('beforeprint', window.AppDark.suspend);
    window.addEventListener('afterprint', window.AppDark.resume);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', runAll);
    else runAll();
    // Late CSS/fonts/plugins can repaint after DOMContentLoaded.
    window.addEventListener('load', function () { dirty.add(document.body); schedule(); });
})();
