/*
 * Report attachments popup (request_report.php): same cards, file-type icons / colours and viewer as the
 * view_employee.php Documents tab (assets/css/emp_docs.css .vd-*).
 * rptAttachments(items, title) - items from report_attachment_items() in includes/report_layout.php:
 *   [{path, name, ext, icon, color, size, date, missing}, ...]
 * Labels come from window.RPT_ATT_T.
 */
(function () {
    'use strict';

    var IMG = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    var OFFICE = ['doc', 'docx', 'xls', 'xlsx'];

    function t(key) { return (window.RPT_ATT_T || {})[key] || key; }
    function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function meta(it) { return [it.date, it.size].filter(Boolean).join(' · '); }

    function card(it, i) {
        var isImg = IMG.indexOf(it.ext) !== -1 && !it.missing;
        return '<div class="vd-card' + (it.missing ? ' is-missing' : '') + '" style="--doc: ' + esc(it.color) + ';" data-idx="' + i + '">'
            + '<button type="button" class="vd-thumb vd-open" title="' + esc(t('view')) + '">'
            + (isImg ? '<img src="' + esc(it.path) + '" alt="" loading="lazy">' : '<span class="vd-thumb-icon"><i class="fa ' + esc(it.icon) + '"></i></span>')
            + '<span class="vd-ext">' + esc((it.ext || '?').toUpperCase()) + '</span>'
            + '<span class="vd-thumb-hover"><i class="fa fa-eye"></i> ' + esc(t('preview')) + '</span></button>'
            + '<div class="vd-body"><h6 class="vd-name" title="' + esc(it.name) + '">' + esc(it.name) + '</h6><div class="vd-meta">'
            + (it.date ? '<span><i class="fa fa-calendar"></i> ' + esc(it.date) + '</span>' : '')
            + (it.missing ? '<span class="vd-missing"><i class="fa fa-triangle-exclamation"></i> ' + esc(t('missing')) + '</span>'
                : (it.size ? '<span><i class="fa fa-hard-drive"></i> ' + esc(it.size) + '</span>' : ''))
            + '</div></div>'
            + '<div class="vd-actions"><button type="button" class="vd-btn vd-btn-main vd-open"><i class="fa fa-eye"></i> ' + esc(t('view')) + '</button>'
            + (it.missing ? '' : '<a class="vd-btn" href="' + esc(it.path) + '" download title="' + esc(t('download')) + '"><i class="fa fa-download"></i></a>')
            + '</div></div>';
    }

    function viewer(items, title, i) {
        var it = items[i];
        var isImg = IMG.indexOf(it.ext) !== -1;
        var body;
        if (it.missing) {
            body = '<div class="vd-view-empty"><i class="fa fa-triangle-exclamation"></i><p>' + esc(t('missing')) + '</p></div>';
        } else if (it.ext === 'pdf') {
            body = '<iframe src="' + esc(it.path) + '#view=FitH" title="' + esc(it.name) + '"></iframe>';
        } else if (isImg) {
            body = '<div class="vd-view-img"><img src="' + esc(it.path) + '" alt="' + esc(it.name) + '"></div>';
        } else if (OFFICE.indexOf(it.ext) !== -1) {
            body = '<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(window.location.origin + '/' + String(it.path).replace(/^\.\//, '')) + '"></iframe>';
        } else {
            body = '<div class="vd-view-empty"><i class="fa fa-file"></i><p>' + esc(t('no_preview')) + '</p>'
                + '<a href="' + esc(it.path) + '" download class="vd-btn vd-btn-main"><i class="fa fa-download"></i> ' + esc(t('download_file')) + '</a></div>';
        }
        var icon = isImg && !it.missing ? '<img src="' + esc(it.path) + '" alt="">' : '<i class="fa ' + esc(it.icon) + '"></i>';
        var nav = items.length > 1
            ? '<button type="button" class="vd-btn rpt-att-prev"' + (i === 0 ? ' disabled' : '') + '><i class="fa fa-chevron-left"></i></button>'
              + '<button type="button" class="vd-btn rpt-att-next"' + (i === items.length - 1 ? ' disabled' : '') + '><i class="fa fa-chevron-right"></i></button>'
            : '';
        Swal.fire({
            html: '<div class="vd-view" style="--doc:' + esc(it.color) + '">'
                + '<div class="vd-view-head"><span class="vd-view-ic">' + icon + '</span>'
                + '<div class="vd-view-title"><b>' + esc(it.name) + (items.length > 1 ? ' <small>(' + (i + 1) + ' / ' + items.length + ')</small>' : '') + '</b>'
                + '<small>' + esc((it.ext || '').toUpperCase()) + (meta(it) ? ' · ' + esc(meta(it)) : '') + '</small></div>'
                + '<div class="vd-view-tools">'
                + '<button type="button" class="vd-btn rpt-att-back" title="' + esc(t('back')) + '"><i class="fa fa-arrow-left"></i></button>'
                + nav
                + (it.missing ? '' : '<a href="' + esc(it.path) + '" target="_blank" rel="noopener" class="vd-btn" title="' + esc(t('open')) + '"><i class="fa fa-up-right-from-square"></i></a>'
                    + '<a href="' + esc(it.path) + '" download class="vd-btn" title="' + esc(t('download')) + '"><i class="fa fa-download"></i></a>')
                + '<button type="button" class="vd-btn rpt-att-close" title="' + esc(t('close')) + '"><i class="fa fa-xmark"></i></button>'
                + '</div></div><div class="vd-view-body">' + body + '</div></div>',
            showConfirmButton: false,
            allowOutsideClick: false,
            width: 'min(1100px, 96vw)',
            padding: 0,
            customClass: { popup: 'vd-view-swal srs-skip' },
            didOpen: function (popup) {
                popup.querySelector('.rpt-att-close').addEventListener('click', function () { Swal.close(); });
                var back = popup.querySelector('.rpt-att-back');
                back.addEventListener('click', function () { grid(items, title); });
                var prev = popup.querySelector('.rpt-att-prev'), next = popup.querySelector('.rpt-att-next');
                if (prev) prev.addEventListener('click', function () { if (i > 0) viewer(items, title, i - 1); });
                if (next) next.addEventListener('click', function () { if (i < items.length - 1) viewer(items, title, i + 1); });
            }
        });
    }

    function grid(items, title) {
        Swal.fire({
            title: '<div class="rpt-att-head"><span class="docs-head-icon"><i class="fa fa-paperclip"></i></span><div><b>' + esc(title) + '</b>'
                + '<small>' + items.length + ' ' + esc(items.length === 1 ? t('file') : t('files')) + '</small></div></div>',
            html: '<div class="emp-docs rpt-att"><div class="vd-grid">' + items.map(card).join('') + '</div></div>',
            showConfirmButton: false,
            showCloseButton: true,
            allowOutsideClick: false,
            // popup grows with the number of files (1 file = small popup)
            width: 'min(' + [420, 420, 600, 780][Math.min(items.length, 3)] + 'px, 96vw)',
            customClass: { popup: 'rpt-att-swal srs-skip' },
            didOpen: function (popup) {
                popup.querySelectorAll('.vd-open').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        viewer(items, title, parseInt(btn.closest('.vd-card').getAttribute('data-idx'), 10));
                    });
                });
            }
        });
    }

    window.rptAttachments = function (items, title) {
        if (!Array.isArray(items) || !items.length || typeof Swal === 'undefined') return;
        grid(items, title);
    };
})();
