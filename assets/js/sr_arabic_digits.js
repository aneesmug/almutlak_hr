/*
 * Arabic-Indic digits (٠١٢٣٤٥٦٧٨٩) for the Arabic UI, plus Arabic month / weekday names
 * and ص/م for dates and times that PHP date() or JS 'en-US' formatting printed in English.
 * Injected by includes/app_datepicker_head.php only when the user's language is AR.
 *
 * Only displayed text nodes are rewritten - input values, attributes and data are never
 * touched, so forms, AJAX and DataTables sorting/search/export (cached before the first
 * pass runs) keep working with Western digits.
 *
 * Opt out: add class "latin-digits" or attribute data-latin-digits to any element.
 * Already skipped: inputs, code/pre/kbd, contenteditable, and any text that looks like a
 * URL or email. IDs/iqama (.sr-mono, .copyToClipboard) are converted for display; copying
 * them puts Western digits on the clipboard (see SRArabicDigits.toLatin / copy handler).
 */
(function () {
    'use strict';

    // App Settings > General > "Numbers in Arabic UI" = Normal -> data-digits="0": keep 0-9,
    // still translate month / weekday names and AM/PM.
    var USE_DIGITS = !(document.currentScript && document.currentScript.getAttribute('data-digits') === '0');
    var DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    var HAS_DIGIT = /[0-9]/;
    var SKIP_TEXT = /(https?:\/\/|www\.|@)/i;
    var SKIP_TAGS = { SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, TEXTAREA: 1, INPUT: 1, SELECT: 1, OPTION: 1, CODE: 1, PRE: 1, KBD: 1, SAMP: 1, IFRAME: 1 };
    var SKIP_SELECTOR = '.latin-digits, [data-latin-digits], [contenteditable=""], [contenteditable="true"]';
    var hasClosest = typeof Element !== 'undefined' && !!Element.prototype.closest;

    function toArabic(str) {
        return str.replace(/[0-9]/g, function (d) { return DIGITS[d]; });
    }

    function toLatin(str) {
        return String(str).replace(/[٠-٩]/g, function (d) { return d.charCodeAt(0) - 0x0660; });
    }

    // Any copy (Ctrl+C, copy buttons using execCommand) of converted text gets Western
    // digits, so pasted IDs/iqama/amounts work in other systems and search boxes.
    document.addEventListener('copy', function (e) {
        if (e.defaultPrevented || !e.clipboardData || !window.getSelection) return;
        var sel = String(window.getSelection());
        if (!/[٠-٩]/.test(sel)) return;
        e.clipboardData.setData('text/plain', toLatin(sel));
        e.preventDefault();
    });

    function skipElement(el) {
        if (!el || el.nodeType !== 1) return true;
        if (SKIP_TAGS[el.tagName]) return true;
        return hasClosest && el.closest(SKIP_SELECTOR) !== null;
    }

    // English month / weekday names and AM/PM printed by PHP date() and JS 'en-US' / 'en-GB'
    // date formatting. Month names (and short weekday names) are only replaced next to a
    // number ("21 Oct 2026", "October 21, 2026", "Wed 21") so words like "May" or "Sun"
    // elsewhere are left alone; full weekday names are unambiguous and always replaced.
    var MONTHS = {
        January: 'يناير', February: 'فبراير', March: 'مارس', April: 'أبريل', May: 'مايو', June: 'يونيو',
        July: 'يوليو', August: 'أغسطس', September: 'سبتمبر', October: 'أكتوبر', November: 'نوفمبر', December: 'ديسمبر',
        Jan: 'يناير', Feb: 'فبراير', Mar: 'مارس', Apr: 'أبريل', Jun: 'يونيو', Jul: 'يوليو',
        Aug: 'أغسطس', Sep: 'سبتمبر', Sept: 'سبتمبر', Oct: 'أكتوبر', Nov: 'نوفمبر', Dec: 'ديسمبر'
    };
    var DAYS = {
        Sunday: 'الأحد', Monday: 'الاثنين', Tuesday: 'الثلاثاء', Wednesday: 'الأربعاء', Thursday: 'الخميس', Friday: 'الجمعة', Saturday: 'السبت',
        Sun: 'الأحد', Mon: 'الاثنين', Tue: 'الثلاثاء', Wed: 'الأربعاء', Thu: 'الخميس', Fri: 'الجمعة', Sat: 'السبت'
    };
    var NUM = '[0-9٠-٩]';
    var SHORT_NAMES = Object.keys(MONTHS).concat(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']).sort(function (a, b) { return b.length - a.length; }).join('|');
    var NEAR_NUMBER = new RegExp('(?:(?<=' + NUM + '[\\s,./-]{0,3})\\b(' + SHORT_NAMES + ')\\b\\.?|\\b(' + SHORT_NAMES + ')\\b\\.?(?=[\\s,./-]{0,3}' + NUM + '))', 'g');
    var FULL_DAYS = /\b(Sunday|Monday|Tuesday|Wednesday|Thursday|Friday|Saturday)\b/g;
    var AM_PM = new RegExp('(?<=' + NUM + '\\s?)(AM|PM|am|pm)\\b', 'g');
    var HAS_NAME = /\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec|Sun|Mon|Tue|Wed|Thu|Fri|Sat|AM|PM|am|pm)/;

    function localizeNames(str) {
        return str
            .replace(FULL_DAYS, function (d) { return DAYS[d]; })
            .replace(NEAR_NUMBER, function (m, a, b) { var k = a || b; return MONTHS[k] || DAYS[k] || m; })
            .replace(AM_PM, function (m) { return m.toUpperCase() === 'AM' ? 'ص' : 'م'; })
            .replace(/([؀-ۿ]),(?=\s)/g, '$1،');
    }

    function convertNode(node) {
        var text = node.nodeValue;
        if (!text || SKIP_TEXT.test(text)) return;
        var hasDigit = USE_DIGITS && HAS_DIGIT.test(text), hasName = HAS_NAME.test(text);
        if (!hasDigit && !hasName) return;
        if (skipElement(node.parentNode)) return;
        var out = hasName ? localizeNames(text) : text;
        if (hasDigit) out = toArabic(out);
        if (out !== text) node.nodeValue = out;
    }

    function walk(root) {
        if (!root) return;
        if (root.nodeType === 3) { convertNode(root); return; }
        if (root.nodeType !== 1 && root.nodeType !== 9 && root.nodeType !== 11) return;
        if (root.nodeType === 1 && skipElement(root)) return;
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
        var node, batch = [];
        while ((node = walker.nextNode())) batch.push(node);
        for (var i = 0; i < batch.length; i++) convertNode(batch[i]);
    }

    // Mutations are queued and flushed once per frame so DataTables redraws, AJAX
    // fragments and SweetAlert popups are converted without per-node overhead.
    var pending = [], scheduled = false;
    function flush() {
        scheduled = false;
        var list = pending; pending = [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].isConnected !== false) walk(list[i]);
        }
    }
    function queue(node) {
        pending.push(node);
        if (!scheduled) {
            scheduled = true;
            (window.requestAnimationFrame || window.setTimeout)(flush);
        }
    }

    function start() {
        walk(document.body);
        if (!window.MutationObserver) return;
        new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.type === 'characterData') {
                    queue(m.target);
                } else {
                    for (var j = 0; j < m.addedNodes.length; j++) queue(m.addedNodes[j]);
                }
            }
        }).observe(document.body, { childList: true, subtree: true, characterData: true });
    }

    window.SRArabicDigits = { enabled: USE_DIGITS, convert: function (s) { s = localizeNames(String(s)); return USE_DIGITS ? toArabic(s) : s; }, toLatin: toLatin, apply: walk };

    // First pass waits until jQuery ready handlers (async in jQuery 3) have run, so
    // DataTables built from the DOM cache Western digits for sorting, search and export.
    function boot() { setTimeout(start, 60); }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
