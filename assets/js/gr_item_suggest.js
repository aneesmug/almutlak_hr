/* Item Name auto-fill for General Requests (new_general_request.php, view_general_request.php).
   Suggestions come from earlier requests (ajaxGeneralRequest.php action=item_suggest). Free text is
   always allowed: a new name is saved as typed and is suggested from then on.
   Usage: GRItemSuggest.bind($container, { selector, getDept(), onPick(item, $input) })
   The list is fixed-positioned on <body> so it is never clipped inside SweetAlert2 popups.
   Styling: .gr-suggest in assets/css/smart_request.css. Needs jQuery. */
(function (window, $) {
    'use strict';

    var URL = './includes/ajaxFile/ajaxGeneralRequest.php';
    var cache = {};

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }
    // Bold the typed part of the name
    function mark(name, term) {
        var i = String(name).toLowerCase().indexOf(String(term).toLowerCase());
        if (i < 0 || !term) return esc(name);
        return esc(name.slice(0, i)) + '<b>' + esc(name.slice(i, i + term.length)) + '</b>' + esc(name.slice(i + term.length));
    }
    function t(key, fallback) {
        var v = (typeof window.__ === 'function') ? window.__(key) : '';
        return (v && v !== key) ? v : fallback;
    }

    function bind($container, opts) {
        var $list = $('<ul class="sr-page gr-suggest" role="listbox"></ul>').appendTo('body').hide();
        var $input = null, items = [], active = -1, timer = null, seq = 0;

        function place() {
            if (!$input || !$input.is(':visible')) return close();
            var r = $input[0].getBoundingClientRect();
            $list.css({ top: r.bottom + 4, left: r.left, width: r.width });
        }
        function close() {
            $list.hide().empty();
            items = []; active = -1;
        }
        function setActive(i) {
            active = i;
            $list.children('li').removeClass('is-active').eq(i).addClass('is-active');
            var el = $list.children('li')[i];
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
        function render(term) {
            if (!items.length) return close();
            $list.html(items.map(function (it, i) {
                var meta = [];
                if (it.type) meta.push('<span><i class="mdi mdi-tag-outline"></i>' + esc(it.type) + '</span>');
                meta.push('<span><i class="mdi mdi-history"></i>' + it.uses + ' ' + esc(t('times_used', 'times used')) + '</span>');
                return '<li role="option" data-i="' + i + '"><div class="gr-suggest-name">' + mark(it.name, term) + '</div>' +
                    '<div class="gr-suggest-meta">' + meta.join('') + '</div></li>';
            }).join('')).show();
            active = -1;
            place();
        }
        function pick(i) {
            var it = items[i];
            if (!it || !$input) return;
            $input.val(it.name).removeClass('is-invalid').trigger('input');
            if (opts.onPick) opts.onPick(it, $input);
            close();
        }
        function search() {
            var term = $.trim($input.val());
            if (term.length < 1) return close();
            var dept = opts.getDept ? (opts.getDept() || '') : '';
            var key = dept + '|' + term.toLowerCase();
            if (cache[key]) { items = cache[key]; return render(term); }
            var mine = ++seq, $for = $input;
            $.ajax({ url: URL, type: 'POST', dataType: 'json', data: { action: 'item_suggest', term: term, department: dept } })
                .done(function (res) {
                    if (mine !== seq || $for !== $input) return; // a newer keystroke won
                    cache[key] = (res && res.items) || [];
                    items = cache[key];
                    render(term);
                });
        }

        $container
            .on('input.grsuggest', opts.selector, function (e) {
                if (e.isTrigger) return; // our own .trigger('input') after a pick
                $input = $(this);
                clearTimeout(timer);
                timer = setTimeout(search, 220);
            })
            .on('focus.grsuggest', opts.selector, function () { $input = $(this); })
            .on('keydown.grsuggest', opts.selector, function (e) {
                if (!$list.is(':visible')) return;
                var n = items.length;
                if (e.key === 'ArrowDown') { e.preventDefault(); setActive((active + 1) % n); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); setActive((active - 1 + n) % n); }
                else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); e.stopPropagation(); pick(active); }
                else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); close(); }
                else if (e.key === 'Tab') { close(); }
            })
            .on('blur.grsuggest', opts.selector, function () { setTimeout(close, 150); });

        // mousedown (not click) so the pick happens before the input blurs
        $list.on('mousedown', 'li', function (e) { e.preventDefault(); pick(parseInt($(this).data('i'), 10)); })
            .on('mousemove', 'li', function () { var i = parseInt($(this).data('i'), 10); if (i !== active) setActive(i); });

        $(window).on('resize', place);
        document.addEventListener('scroll', function () { if ($list.is(':visible')) place(); }, true);

        return { close: close, destroy: function () { $container.off('.grsuggest'); $list.remove(); } };
    }

    window.GRItemSuggest = { bind: bind };
})(window, jQuery);
