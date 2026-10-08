/**
 * Theme: Highdmin - Responsive Bootstrap 4 Admin Dashboard
 * Form Pickers (Hijri)
 *
 * Hijri fields use AppDate.hijri (assets/js/app_datepicker.js): Umm al-Qura calendar with the
 * same look, RTL support and date blocking as every other AppDate picker. Values stay
 * iYYYY-iMM-iDD. Inputs a page already set up itself are skipped.
 */
jQuery(document).ready(function () {
    if (!window.AppDate) return;

    function hijriField(selector, opts) {
        var el = document.querySelector(selector);
        if (!el || el.tagName !== 'INPUT' || AppDate.get(el)) return null;
        return AppDate.hijri(el, opts || {});
    }

    // Iqama expiry (Hijri) also fills its hidden Gregorian twin #iqama_exp_g
    hijriField('#iqama_exp_hijri', {
        onChange: function (dates) {
            $('#iqama_exp_g').val(dates.length ? AppDate.format(dates[0]) : '');
        }
    });

    // Balady license expiry (Hijri)
    hijriField('#b_license_exp_hijri');
});
