/* sr-* tables scroll horizontally inside their card (.sr-table-wrap { overflow-x: auto },
   assets/css/smart_request.css). A scroll box also clips anything that pokes out of it, so a
   row's ⋮ dropdown menu would be cut off / add a scrollbar. While a menu inside a scroll box
   is open it is moved to <body> (Bootstrap 4's Popper then positions it there), and put back
   in place when it closes. Injected on every logged-in page by includes/app_datepicker_head.php. */
(function () {
    'use strict';

    var SCROLL_BOX = '.sr-table-wrap, .sr-list-wrap, .aca-table-wrap';

    function bind($) {
        $(document).on('show.bs.dropdown', function (e) {
            var parent = e.target;
            if (!parent || !$(parent).closest(SCROLL_BOX).length) return;
            var menu = parent.querySelector(':scope > .dropdown-menu');
            if (!menu) return;
            // Keeps the page's .sr-page token colors for the moved menu
            var holder = document.createElement('div');
            holder.className = 'sr-page sr-dd-holder';
            document.body.appendChild(holder);
            holder.appendChild(menu);
            menu.classList.add('sr-dd-floating');
            $(parent).data('srDdMenu', menu).data('srDdHolder', holder);
        });

        $(document).on('hidden.bs.dropdown', function (e) {
            var parent = e.target;
            var menu = $(parent).data('srDdMenu');
            if (!menu) return;
            menu.classList.remove('sr-dd-floating');
            menu.removeAttribute('style');
            menu.removeAttribute('x-placement');
            parent.appendChild(menu);
            var holder = $(parent).data('srDdHolder');
            if (holder && holder.parentNode) holder.parentNode.removeChild(holder);
            $(parent).removeData('srDdMenu').removeData('srDdHolder');
        });
    }

    // jQuery/Bootstrap load at the end of <body>, after this file (in <head>)
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery) bind(window.jQuery);
    });
})();
