<!DOCTYPE html>
<html lang="en-US">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Title Page</title>
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
        <link href="./bootstrap-datepicker-1.10.0/dist/css/bootstrap-datepicker.css" rel="stylesheet">
        <link href="./bootstrap-datepicker-1.10.0/dist/css/bootstrap-datepicker.min.css" rel="stylesheet">
    </head>
    <body style="padding:20px;">
        <div class="calc">
        <p>The end of service benefits of worker’s rights on the employer in the case of termination of the employment contract, and it is obligatory on the employer to pay the worker at the end of the contract of employment, whether it is a fixed-term contract or indefinite. That’s the easy way to estimate end of service benefits expense, the maturity date of the reward, and how to calculate it according to the rules of the Saudi Labor Law</p>
        </div>
        <div class="calculator my-5">
            <div class="head" style="margin-left: 20px;">
                <h2><p value="0" id="resultCalc">0</p></h2>
            </div>
            <div class="questions">
                <form id="calculatorForm">
                    <div class="form-row">
                        <div class="form-group col-lg-6 col-sm-12">
                            <label>Type of Contract</label>
                            <select id="inputPeriod" required class="form-control" >
                                <option selected value="">Select type</option>
                                <option value="47">Fixed time</option>
                                <option value="48">Unlimited period</option>
                            </select>
                        </div>
                        <div class="form-group col-lg-6 col-sm-12">
                            <label>End of Service Reason</label>
                            <select id="inputState" required class="form-control">
                                <option selected value="">Select reason</option>
                            </select>
                        </div>
                        <div id="event_period">
                            <div class="form-group col-lg-3 col-sm-12">
                                <label for="joining_date" class="col-form-label">Join Date</label>
                                <input type="text" name="joining_date" class="form-control" id="joining_date" value="2018-09-30">
                            </div>
                            <div class="form-group col-lg-3 col-sm-12">
                                <label for="end_date" class="col-form-label">End Date</label>
                                <input type="text" name="end_date" class="form-control" id="end_date">
                            </div>
                        </div>
                        <div class="form-group col-lg-3 col-sm-12">
                            <label>Salary</label>
                            <input type="text" required class="form-control onlyNumbers" step=any id="salary" value="5000">
                        </div>
                        <div class="form-group col-lg-3 col-sm-12">
                            <label>Duration of service (years) </label>
                            <input type="text" class="form-control onlyNumbers" id="yearsPeriod">
                        </div>
                    
                        <div class="form-group col-lg-6 col-sm-12">
                            <label>Number of months <span>(optional)</span></label>
                            <input type="text" class="form-control onlyNumbers" id="monthsPeriod">
                        </div>
                        <div class="form-group col-lg-6 col-sm-12">
                            <label>Number of days<span>(optional)</span></label>
                            <input type="text" class="form-control onlyNumbers" id="daysPeriod">
                        </div>

                    </div>
                    <div class="form-group row">
                        <div class="col-sm-12">
                            <button type="submit" class="btn btn-primary">Calcuclate</button>
                            <button type="submit" class="btn btn-danger btn-reset">Reset</button>
                        </div>
                    </div>                            
                </form>
            </div>
        </div>
        <!-- jQuery -->
        <!-- <script src="assets/js/jquery.min.js"></script> -->
        <script src="//code.jquery.com/jquery.js"></script>
        <!-- Bootstrap JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>

        <script src="./bootstrap-datepicker-1.10.0/dist/js/bootstrap-datepicker.js"></script>
        <script src="./bootstrap-datepicker-1.10.0/dist/js/bootstrap-datepicker.min.js"></script>
        <script src="./bootstrap-datepicker-1.10.0/dist/locales/bootstrap-datepicker.ar.min.js"></script>
        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
         <script type="text/javascript">
            window.addEventListener('DOMContentLoaded', (event) => {
                /*jQuery('#joining_date').datepicker({
                    format: "yyyy-mm-dd",
                    autoclose: true,
                    todayHighlight: true,
                    endDate: '+0d',
                });
                jQuery('#end_date').datepicker({
                    format: "yyyy-mm-dd",
                    autoclose: true,
                    todayHighlight: true,
                    todayBtn: true,
                    showOnFocus: true,
                    language: "ar",
                });*/
                $('#event_period').datepicker({
                    format: "yyyy-mm-dd",
                    todayHighlight: true,
                    inputs: [$('#joining_date'),$('#end_date')],
                    todayBtn: true,
                });

                jQuery(document).ready(function(){
                    $('#salary').on('invalid', function() {
                        var input = $(this);
                        if(input.val() == ''){
                            this.setCustomValidity('This field is required');
                        } else if(input.val() > 99999 || input.val() < 1){
                            this.setCustomValidity('Value Must be gerater than 1 and less than 99999');
                        } else{
                            this.setCustomValidity('');
                        }
                        return true;
                    });
                    /*$('#yearsPeriod').on('invalid', function() {
                        var input = $(this);
                        if(input.val() == ''){
                            this.setCustomValidity('This field is required');
                        } else if(input.val() > 90 || input.val() < 0){
                            this.setCustomValidity('Value Must be gerater than 1 and less than 90');
                        } else{
                            this.setCustomValidity('');
                        }
                        return true;
                    });
                    $('#inputPeriod').on('invalid', function() {
                        var input = $('#inputPeriod option:selected').val();
                        if(input == ''){
                            this.setCustomValidity('This field is required');
                        } else{
                            this.setCustomValidity('');
                        }
                        return true;
                    });*/
                    $("#inputPeriod").on("change", function() {
                        $.ajax({
                            type: "GET", 
                            url: "./includes/RuleSelect.php",
                            data: "pid="+$("#inputPeriod").val(),
                            success: function(html) {
                                $("#inputState").html(html);
                            }
                        });
                    });
                    $(".btn-reset").click(function(e) {
                        e.preventDefault();
                        clearCalc();
                    });         
                    $( "#calculatorForm" ).submit(function( event ) {
                        var form = document.getElementById('calculatorForm');
                        event.preventDefault();
                            /*var datePeriod = $('#joining_date').val();
                            var endDatePeriod = $('#end_date').val();*/
                            /*console.log( dateDiff(datePeriod, endDatePeriod) );
                            console.log( dateDiffDay(datePeriod, endDatePeriod) );
                            console.log( dateDiffMonth(datePeriod, endDatePeriod) );
                            console.log( dateDiffYear(datePeriod, endDatePeriod) );*/
                        if (form[0].checkValidity() === true) {
                            var inputPeriod = $('#inputPeriod option:selected').val();
                            var inputState = $('#inputState option:selected').val();
                            var salary = $('#salary').val();
                            var yearsPeriod = $('#yearsPeriod').val();
                            var monthsPeriod = $('#monthsPeriod').val();
                            var daysPeriod = $('#daysPeriod').val();
                            sumbitCalc(inputPeriod,inputState,salary,Number(yearsPeriod),Number(monthsPeriod),Number(daysPeriod)); 
                        } 
                    });

                    // $("#calculatorForm").on('blur', '#joining_date,#end_date', function() {
                    $("#calculatorForm").on('blur', '#end_date', function() {
                        getTotalCost($(this).attr("for"));
                    });
                    function getTotalCost(ind) {
                        var datePeriod = $('#joining_date').val();
                        var endDatePeriod = $('#end_date').val();
                        $('#yearsPeriod').val( dateDiffYear(datePeriod, endDatePeriod) );
                        $('#monthsPeriod').val( dateDiffMonth(datePeriod, endDatePeriod) );
                        $('#daysPeriod').val( dateDiffDay(datePeriod, endDatePeriod) );
                        // calculateSubTotal();
                    }

                });

                function sumbitCalc(inputPeriod,inputState,salary,yearsPeriod,monthsPeriod,daysPeriod) {
                    var totalDays = getTotalDays(yearsPeriod,monthsPeriod,daysPeriod);
                    let result = 0;
                    if(inputState === '39' || inputState === '43' || inputState === '45'){
                        result = 0;
                    } else if (inputPeriod == '47' && inputState === '46') {
                        result = 0;
                    } else if (inputPeriod == '48' && inputState === '46') {
                        if (totalDays < 2 * 360 ){
                            result = 0;
                        } else if (totalDays >= 2 * 360 && totalDays <= 5 * 360){
                            result = (salary / 6) * totalDays;
                        } else if (totalDays > 5 * 360 && totalDays < 10 * 360){
                            var resultFirstFiveYears =  (salary / 3) * ( 5 * 360 );
                            var yearsGreaterThanFive  = totalDays - ( 5 * 360 );
                            var resultGreaterFiveYears = ((salary / 3) * 2 ) * yearsGreaterThanFive;
                            result = resultFirstFiveYears + resultGreaterFiveYears;
                        } else if (totalDays >= 10 * 360){
                            var resultFirstFiveYears =  (salary / 2) * ( 5 * 360 );
                            var yearsGreaterThanFive  = totalDays - ( 5 * 360 );
                            var resultGreaterFiveYears = salary * yearsGreaterThanFive;
                            result = resultFirstFiveYears + resultGreaterFiveYears;
                        }
                    } else if((inputPeriod == '47' || inputPeriod == '48') && totalDays <= 5 * 360){
                        result = (salary / 2) * totalDays;
                    } else if((inputPeriod == '47' || inputPeriod == '48') && totalDays > 5 * 360){
                        var resultFirstFiveYears =  (salary / 2) * ( 5 * 360 );
                        var yearsGreaterThanFive  = totalDays - ( 5 * 360 );
                        var resultGreaterFiveYears = salary * yearsGreaterThanFive;
                        result = resultFirstFiveYears + resultGreaterFiveYears;
                    }
                    var Final_result = result / 360;
                    // console.log(Final_result);
                    $('#resultCalc').val(Final_result.toFixed(2));
                    $('#resultCalc').html(Final_result.toFixed(2));
                }
                function getTotalDays(years,months,days) {
                    let result = 0;
                    result += years * 360;
                    result += months * 30;
                    result += days;
                    // console.log(result);
                    return result;
                }
                
                function clearCalc() {
                    var $states = $("#inputState");
                    $states.find('option').remove().end();
                    $states.append($("<option />").val('').text('Select reason').attr('disabled',true));
                    $('#resultCalc').val(0);
                    $('#resultCalc').html(0);
                    $('.onlyNumbers.is-invalid').removeClass('is-invalid');
                    $('#calculatorForm').find("input[type=number], select").val("");
                    $('#joining_date').val(" ");
                    $('#end_date').val(" ");
                    $('#yearsPeriod').val(" ");
                    $('#monthsPeriod').val(" ");
                    $('#daysPeriod').val(" ");
                }
                function validateNumber(event) {
                    var target = event.target;
                    var key = window.event ? event.keyCode : event.which;
                    if (event.keyCode === 8 || event.keyCode === 46 || event.keyCode === 13 || event.keyCode === 9 || event.keyCode === 37 || event.keyCode === 39) {
                        return true;
                    } else if ( key < 48 || key > 57 ) {
                        $(target).addClass('is-invalid');
                        alert('Sorry Dear Customer , This field only accepts numbers ');
                        event.preventDefault();
                    } else {
                        $(target).removeClass('is-invalid');
                        return true;
                    }
                }

                function dateDiffDay(startingDate, endingDate) {
                    let startDate = new Date(new Date(startingDate).toISOString().substr(0, 10));
                    if (!endingDate) {
                        endingDate = new Date().toISOString().substr(0, 10); // need date in YYYY-MM-DD format
                    }
                    let endDate = new Date(endingDate);
                    if (startDate > endDate) {
                        const swap = startDate;
                        startDate = endDate;
                        endDate = swap;
                    }
                    const startYear = startDate.getFullYear();
                    const february = (startYear % 4 === 0 && startYear % 100 !== 0) || startYear % 400 === 0 ? 29 : 28;
                    const daysInMonth = [31, february, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];                    
                    let dayDiff = endDate.getDate() - startDate.getDate();
                    if (dayDiff < 0) {
                        dayDiff += daysInMonth[startDate.getMonth()];
                    }
                    return dayDiff/* + ' Days'*/;
                }

                function dateDiffMonth(startingDate, endingDate) {
                    let startDate = new Date(new Date(startingDate).toISOString().substr(0, 10));
                    if (!endingDate) {
                        endingDate = new Date().toISOString().substr(0, 10); // need date in YYYY-MM-DD format
                    }
                    let endDate = new Date(endingDate);
                    if (startDate > endDate) {
                        const swap = startDate;
                        startDate = endDate;
                        endDate = swap;
                    }
                    const startYear = startDate.getFullYear();
                    const february = (startYear % 4 === 0 && startYear % 100 !== 0) || startYear % 400 === 0 ? 29 : 28;
                    const daysInMonth = [31, february, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                    let monthDiff = endDate.getMonth() - startDate.getMonth();
                    if (monthDiff < 0) {
                        monthDiff += 12;
                    }
                    let dayDiff = endDate.getDate() - startDate.getDate();
                    if (dayDiff < 0) {
                        if (monthDiff > 0) {
                            monthDiff--;
                        } else {
                            monthDiff = 11;
                        }
                    }
                    return monthDiff/* + ' Months'*/;
                }

                function dateDiffYear(startingDate, endingDate) {
                    let startDate = new Date(new Date(startingDate).toISOString().substr(0, 10));
                    if (!endingDate) {
                        endingDate = new Date().toISOString().substr(0, 10); // need date in YYYY-MM-DD format
                    }
                    let endDate = new Date(endingDate);
                    if (startDate > endDate) {
                        const swap = startDate;
                        startDate = endDate;
                        endDate = swap;
                    }
                    const startYear = startDate.getFullYear();
                    const february = (startYear % 4 === 0 && startYear % 100 !== 0) || startYear % 400 === 0 ? 29 : 28;
                    const daysInMonth = [31, february, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                    let yearDiff = endDate.getFullYear() - startYear;
                    let monthDiff = endDate.getMonth() - startDate.getMonth();
                    if (monthDiff < 0) {
                        yearDiff--;
                        monthDiff += 12;
                    }
                    let dayDiff = endDate.getDate() - startDate.getDate();
                    if (dayDiff < 0) {
                        if (monthDiff > 0) {
                        } else {
                            yearDiff--;
                        }
                    }
                    return yearDiff/* + ' Years'*/;
                }



            });
            </script>
    </body>
</html>


