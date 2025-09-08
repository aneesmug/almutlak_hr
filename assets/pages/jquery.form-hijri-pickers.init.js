/**
 * Theme: Highdmin - Responsive Bootstrap 4 Admin Dashboard
 * Author: Coderthemes
 * Form Pickers
 */
jQuery(document).ready(function () {
  
//  $("#iqama_exp_hijri").hijriDatePicker({
//      locale: "ar-sa",
//      hijri:true,
//      showSwitcher:false,
//      hijriFormat:"iDD/iMM/iYYYY",
//      hijriDayViewHeaderFormat: "iMMMM iYYYY",
////        format: "DD-MM-YYYY",
////        hijriFormat:"iYYYY-iMM-iDD",
////        dayViewHeaderFormat: "MMMM YYYY",
////        allowInputToggle: true,
//      showTodayButton: true,
////        useCurrent: true,
////        isRTL: false,
////        viewMode:'months', // months - years
////        keepOpen: false,
////        hijri: false,
////        showClear: true,
////        showTodayButton: true,
////        showClose: true
////        minDate:"2020-01-01",
////        maxDate:"2021-01-01",
//      
//  });
//
    
// $("#iqama_exp_hijri_expierd").hijriDatePicker({
// //        showTodayButton: true,
// //        showClear:true,
// //        useCurrent:false
//         locale: "ar-sa",
//         hijri:true,
//         showSwitcher:false,
//         hijriFormat:"iDD/iMM/iYYYY",
//         hijriDayViewHeaderFormat: "iMMMM iYYYY",
//         showTodayButton: true,
//     });
$("#iqama_exp_hijri").hijriDatePicker({
//        showTodayButton: true,
//        showClear:true,
//        useCurrent:false
        locale: "ar-sa",
        hijri:true,
        showSwitcher:false,
        // hijriFormat:"iDD/iMM/iYYYY",
        hijriFormat:"iYYYY-iMM-iDD",
        hijriDayViewHeaderFormat: "iMMMM iYYYY",
        showTodayButton: true,
    });

$("#b_license_exp_hijri").hijriDatePicker({
//        showTodayButton: true,
//        showClear:true,
//        useCurrent:false
        locale: "ar-sa",
        hijri:true,
        showSwitcher:false,
        hijriFormat:"iYYYY-iMM-iDD",
        hijriDayViewHeaderFormat: "iMMMM iYYYY",
        showTodayButton: true,
});

$("#start_cont_date").hijriDatePicker({
//        showTodayButton: true,
//        showClear:true,
//        useCurrent:false
        locale: "ar-sa",
        hijri:true,
        showSwitcher:false,
        hijriFormat:"iYYYY-iMM-iDD",
        hijriDayViewHeaderFormat: "iMMMM iYYYY",
        showTodayButton: true,
});

$("#end_cont_date").hijriDatePicker({
//        showTodayButton: true,
//        showClear:true,
//        useCurrent:false
        locale: "ar-sa",
        hijri:true,
        showSwitcher:false,
        hijriFormat:"iYYYY-iMM-iDD",
        hijriDayViewHeaderFormat: "iMMMM iYYYY",
        showTodayButton: true,
});


    $("#iqama_exp_hijri").on('dp.change', function (arg) {

        if (!arg.date) {
//            $("#iqama_exp_g").html('');
            $("#iqama_exp_g").val();
            return;
        };
        let date = arg.date;
//        $("#iqama_exp_g").html(date.format("DD/MM/YYYY"));
        $("#iqama_exp_g").val(date.format("YYYY-MM-DD"));
    });
    

});