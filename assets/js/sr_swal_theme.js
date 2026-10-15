/**
 * Global SweetAlert2 theme - gives every Swal.fire() in the app the same look as the
 * redesigned modals (compact icon + title header, left-aligned body, consistent buttons),
 * without touching the 1,000+ individual call sites.
 *
 * Injected into <head> of every page by includes/app_datepicker_head.php (and linked
 * directly on the login pages). Works whether SweetAlert2 loads before or after this
 * file: if window.Swal isn't there yet, a setter on window catches the assignment.
 *
 * Skipped (left exactly as the caller wrote it):
 *  - toasts and Swal.mixin() instances (their options are merged inside SweetAlert2)
 *  - popups that already carry one of the hand-designed classes (pv/pa/dbi/dex-popup,
 *    or anything with "srs-skip")
 *  - calls that pass iconHtml (custom icon on purpose)
 */
(function () {
    'use strict';

    // Inline SVG (not an icon font) so the header icon renders on every page, including
    // login pages that don't load icons.css / Font Awesome.
    function svg(body) {
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + body + '</svg>';
    }
    var ICONS = {
        success: { svg: svg('<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.8"/>'), tone: 'green', label: 'Success' },
        error: { svg: svg('<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>'), tone: 'red', label: 'Error' },
        warning: { svg: svg('<path d="M12 3.5L2.8 19.5h18.4L12 3.5z"/><path d="M12 10v4.2M12 17.2v.1"/>'), tone: 'amber', label: 'Warning' },
        info: { svg: svg('<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.1"/>'), tone: 'sky', label: 'Information' },
        question: { svg: svg('<circle cx="12" cy="12" r="9"/><path d="M9.6 9.3a2.5 2.5 0 0 1 4.8 1c0 1.7-2.4 2.2-2.4 3.7M12 17.2v.1"/>'), tone: 'indigo', label: 'Please confirm' }
    };

    // Old Bootstrap / SweetAlert default button colors -> the app palette
    var COLOR_MAP = {
        '#3085d6': '#6366f1', '#7066e0': '#6366f1', '#007bff': '#6366f1', '#2563eb': '#6366f1', '#4a90e2': '#6366f1',
        '#d33': '#dc2626', '#dd3333': '#dc2626', '#dc3545': '#dc2626', '#e74c3c': '#dc2626', '#f44336': '#dc2626',
        '#28a745': '#16a34a', '#2ecc71': '#16a34a', '#4caf50': '#16a34a',
        '#aaa': '#64748b', '#6e7881': '#64748b', '#6c757d': '#64748b'
    };

    // Tone of the most recent themed popup - read by Swal.update() when it runs before didOpen
    var currentTone = '';

    var SKIP_CLASS = /(^|\s)(pv-popup|pa-popup|dbi-popup|dex-popup|srs-skip)(\s|$)/;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function t(key, fallback) {
        try {
            if (typeof window.__ === 'function') {
                var v = window.__(key, fallback);
                if (v && v !== key) return v;
            }
        } catch (e) { /* translations not loaded yet */ }
        return fallback;
    }

    function headerHtml(meta, titleHtml) {
        return '<span class="srs-head"><span class="srs-icon tone-' + meta.tone + '">' + meta.svg + '</span>' +
            '<span class="srs-head-text">' + titleHtml + '</span></span>';
    }

    function mapColor(c) {
        if (!c || typeof c !== 'string') return c;
        return COLOR_MAP[c.trim().toLowerCase()] || c;
    }

    function popupClassOf(opts) {
        var cc = opts.customClass;
        if (!cc) return '';
        if (typeof cc === 'string') return cc;
        return String(cc.popup || '');
    }

    function addPopupClass(opts, cls) {
        var cc = opts.customClass;
        if (!cc) {
            opts.customClass = { popup: cls };
        } else if (typeof cc === 'string') {
            opts.customClass = { popup: cc + ' ' + cls };
        } else {
            var copy = {};
            for (var k in cc) if (Object.prototype.hasOwnProperty.call(cc, k)) copy[k] = cc[k];
            copy.popup = (copy.popup ? copy.popup + ' ' : '') + cls;
            opts.customClass = copy;
        }
    }

    function normalizeArgs(args) {
        var a = args[0];
        if (a && typeof a === 'object' && !(a instanceof Node) && !Array.isArray(a)) {
            var copy = {};
            for (var k in a) if (Object.prototype.hasOwnProperty.call(a, k)) copy[k] = a[k];
            return copy;
        }
        // Shorthand Swal.fire(title, html, icon)
        var o = {};
        if (args.length > 0 && args[0] !== undefined) o.title = args[0];
        if (args.length > 1 && args[1] !== undefined) o.html = args[1];
        if (args.length > 2 && args[2] !== undefined) o.icon = args[2];
        return o;
    }

    function themeOptions(opts) {
        if (opts.toast || opts.iconHtml) return opts;
        if (SKIP_CLASS.test(popupClassOf(opts))) return opts;

        addPopupClass(opts, 'sr-swal');

        var meta = ICONS[opts.icon];
        var hasTitle = opts.title !== undefined && opts.title !== null && opts.title !== '';
        var titleHtml = '';
        if (opts.titleText) {
            titleHtml = esc(opts.titleText);
            delete opts.titleText;
            hasTitle = true;
        } else if (hasTitle) {
            titleHtml = (opts.title instanceof Node) ? opts.title.outerHTML : String(opts.title);
        }

        if (meta) {
            if (!hasTitle) titleHtml = esc(t(opts.icon, meta.label));
            opts.title = headerHtml(meta, titleHtml);
            opts._srsTone = opts.icon;
            delete opts.icon;
        } else if (hasTitle) {
            opts.title = '<span class="srs-head is-plain"><span class="srs-head-text">' + titleHtml + '</span></span>';
        }

        // Plain `text` keeps working; it just renders in the left-aligned body
        opts.confirmButtonColor = mapColor(opts.confirmButtonColor) || '#6366f1';
        opts.cancelButtonColor = mapColor(opts.cancelButtonColor) || '#64748b';
        if (opts.denyButtonColor) opts.denyButtonColor = mapColor(opts.denyButtonColor);
        else opts.denyButtonColor = '#dc2626';

        // Remember the tone so a later Swal.update({ title }) keeps the icon header
        var userDidOpen = opts.didOpen;
        var tone = opts._srsTone || '';
        delete opts._srsTone;
        currentTone = tone;
        opts.didOpen = function (popup) {
            if (popup && tone) popup.setAttribute('data-srs-tone', tone);
            if (typeof userDidOpen === 'function') return userDidOpen.apply(this, arguments);
        };
        return opts;
    }

    function patch(Swal) {
        if (!Swal || Swal.__srsPatched || typeof Swal.fire !== 'function') return;
        var origFire = Swal.fire;
        var BaseSwal = Swal;

        Swal.fire = function () {
            // Swal.mixin() subclasses inherit this static: leave them alone
            if (this && this !== BaseSwal && this !== window && this !== undefined) {
                return origFire.apply(this, arguments);
            }
            var opts;
            try {
                opts = themeOptions(normalizeArgs(arguments));
            } catch (e) {
                return origFire.apply(BaseSwal, arguments);
            }
            return origFire.call(BaseSwal, opts);
        };

        if (typeof Swal.update === 'function') {
            var origUpdate = Swal.update;
            Swal.update = function (params) {
                try {
                    var popup = Swal.getPopup && Swal.getPopup();
                    if (params && popup && popup.classList.contains('sr-swal')) {
                        var copy = {};
                        for (var k in params) if (Object.prototype.hasOwnProperty.call(params, k)) copy[k] = params[k];
                        if (copy.icon && ICONS[copy.icon]) {
                            popup.setAttribute('data-srs-tone', copy.icon);
                            currentTone = copy.icon;
                            delete copy.icon;
                            if (copy.title === undefined) {
                                var cur = popup.querySelector('.srs-head-text');
                                copy.title = cur ? cur.innerHTML : '';
                            }
                        }
                        if (copy.title !== undefined && !/srs-head/.test(String(copy.title))) {
                            var meta = ICONS[popup.getAttribute('data-srs-tone') || currentTone];
                            copy.title = meta
                                ? headerHtml(meta, String(copy.title))
                                : '<span class="srs-head is-plain"><span class="srs-head-text">' + String(copy.title) + '</span></span>';
                        }
                        return origUpdate.call(this, copy);
                    }
                } catch (e) { /* fall through */ }
                return origUpdate.apply(this, arguments);
            };
        }

        Swal.__srsPatched = true;
    }

    // ---- Legacy SweetAlert2 v6 (plugins/sweet-alert/sweetalert2.min.js, `swal()` API) ----
    // Used by a few old pages (customers, orders, file manager, invoices). Options use
    // `type` instead of `icon` and customClass is a string; the popup is .swal2-modal, so
    // "swal2-popup" is added to it to pick up the same theme CSS.
    function themeV6Options(o) {
        if (o.toast) return o;
        if (SKIP_CLASS.test(String(o.customClass || ''))) return o;
        o.customClass = (o.customClass ? o.customClass + ' ' : '') + 'sr-swal swal2-popup';
        var meta = ICONS[o.type];
        var hasTitle = o.title !== undefined && o.title !== null && o.title !== '';
        var titleHtml = o.titleText ? esc(o.titleText) : (hasTitle ? String(o.title) : '');
        if (o.titleText) delete o.titleText;
        if (meta) {
            if (!titleHtml) titleHtml = esc(t(o.type, meta.label));
            o.title = headerHtml(meta, titleHtml);
            o.type = null;
        } else if (titleHtml) {
            o.title = '<span class="srs-head is-plain"><span class="srs-head-text">' + titleHtml + '</span></span>';
        }
        o.confirmButtonColor = mapColor(o.confirmButtonColor) || '#6366f1';
        o.cancelButtonColor = mapColor(o.cancelButtonColor) || '#64748b';
        return o;
    }

    function wrapV6(fn) {
        if (!fn || fn.__srsPatched || typeof fn !== 'function') return fn;
        var wrapped = function () {
            var args = arguments;
            try {
                var o;
                var a = args[0];
                if (a && typeof a === 'object' && !Array.isArray(a)) {
                    o = {};
                    for (var k in a) if (Object.prototype.hasOwnProperty.call(a, k)) o[k] = a[k];
                } else {
                    o = {};
                    if (args.length > 0 && args[0] !== undefined) o.title = args[0];
                    if (args.length > 1 && args[1] !== undefined) o.html = args[1];
                    if (args.length > 2 && args[2] !== undefined) o.type = args[2];
                }
                return fn.call(this, themeV6Options(o));
            } catch (e) {
                return fn.apply(this, args);
            }
        };
        for (var key in fn) {
            if (Object.prototype.hasOwnProperty.call(fn, key)) wrapped[key] = fn[key];
        }
        wrapped.__srsPatched = true;
        return wrapped;
    }

    function isV6(v) {
        return typeof v === 'function' && typeof v.fire !== 'function' && /^[1-6]\./.test(String(v.version || ''));
    }

    function trapGlobal(name, onSet) {
        var current = window[name];
        if (current) current = onSet(current);
        try {
            Object.defineProperty(window, name, {
                configurable: true,
                enumerable: true,
                get: function () { return current; },
                set: function (v) { current = onSet(v); }
            });
        } catch (e) { /* non-configurable - leave it */ }
    }

    trapGlobal('swal', function (v) { return isV6(v) ? wrapV6(v) : v; });
    trapGlobal('sweetAlert', function (v) { return isV6(v) ? wrapV6(v) : v; });

    // Catch SweetAlert2 whenever it lands on window (before or after this file)
    function install() {
        if (window.Swal) patch(window.Swal);
        try {
            var current = window.Swal;
            Object.defineProperty(window, 'Swal', {
                configurable: true,
                enumerable: true,
                get: function () { return current; },
                set: function (v) { current = v; patch(v); }
            });
        } catch (e) {
            // Fallback: poll briefly
            var tries = 0;
            var timer = setInterval(function () {
                if (window.Swal) { patch(window.Swal); clearInterval(timer); }
                if (++tries > 200) clearInterval(timer);
            }, 50);
        }
    }

    install();
})();
