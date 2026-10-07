/**
 * Theme: Highdmin - Responsive Bootstrap 4 Admin Dashboard
 * Author: Coderthemes
 * Form Pickers
 */
jQuery(document).ready(function () {

    // Time Picker
    jQuery('#timepicker').timepicker({
        defaultTIme: false,
        icons: {
            up: 'mdi mdi-chevron-up',
            down: 'mdi mdi-chevron-down'
        }
    });
    jQuery('#timepicker2').timepicker({
        showMeridian: false,
        icons: {
            up: 'mdi mdi-chevron-up',
            down: 'mdi mdi-chevron-down'
        }
    });
    jQuery('#timepicker3').timepicker({
        minuteStep: 15,
        icons: {
            up: 'mdi mdi-chevron-up',
            down: 'mdi mdi-chevron-down'
        }
    });

    //colorpicker start

    /*$('.colorpicker-default').colorpicker({
        format: 'hex'
    });
    $('.colorpicker-rgba').colorpicker();


    // Date Picker
    jQuery('#datepicker').datepicker();
    jQuery('#datepicker-autoclose').datepicker({
        autoclose: true,
        todayHighlight: true
    });
    // Date Picker
    jQuery('#datepickerdob').datepicker();
    jQuery('#datepickerdob-autoclose').datepicker({
        format: "yyyy-mm-dd",
        autoclose: true,
    });*/
/*****************************/
    // Date pickers (AppDate / flatpickr, see assets/js/app_datepicker.js). Runs after every
    // page's own document.ready code and skips inputs a page already set up itself (with
    // AppDate or the old bootstrap-datepicker), so page-specific options always win.
    // minDate/maxDate: earlier/later days are blocked.
    setTimeout(function () {
        if (!window.AppDate) return;
        var pickers = {
            '#joining_date':  { maxDate: 'today' },
            '#dob':           { maxDate: 'today' },
            '#rcv_date':      { minDate: 'today' },
            '#return_dated':  {},
            '#arrived_date':  {},
            '#last_vac_date': { maxDate: 'today' },
            '#next_vac_date': { minDate: 'today' },
            '#passport_exp':  { minDate: 'today' },
            '#insurance_exp': { minDate: 'today' },
            '#return_date_v': { minDate: 'today' },
            '#date_select':   {}
        };
        Object.keys(pickers).forEach(function (sel) {
            var el = document.querySelector(sel);
            if (!el || el.tagName !== 'INPUT' || el._flatpickr || jQuery(el).data('datepicker')) return;
            AppDate.single(el, pickers[sel]);
        });
    }, 0);

    //Clock Picker
    $('.clockpicker').clockpicker({
        donetext: 'Done'
    });

    $('#single-input').clockpicker({
        placement: 'bottom',
        align: 'left',
        autoclose: true,
        'default': 'now'
    });
    $('#check-minutes').click(function (e) {
        // Have to stop propagation here
        e.stopPropagation();
        $("#single-input").clockpicker('show')
            .clockpicker('toggleView', 'minutes');
    });


});