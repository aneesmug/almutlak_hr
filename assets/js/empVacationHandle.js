$(document).on('click', '.applyvacationAtter', function (e) {
    e.preventDefault();
    var empid = $(this).data('empid');
    var deptId = $(this).data('dept');
    var country = $(this).data('country');
    Swal.fire({
        title: __('apply_vacation_info_title'),
        html: vacationApply_HTML(country),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: __('yes_register'),
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        // width: "50%",
        willOpen: () => {
            // Date picker initialization
            /*$('#start_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: '+0d'
            }).on('changeDate', function(e) {
                var startDate = e.date;
                var maxEndDate = new Date(startDate);
                maxEndDate.setDate(maxEndDate.getDate() + 20.00); // Add 20 days
                // Set end date to same as start date initially
                $('#end_date').datepicker('setStartDate', startDate);
                $('#end_date').datepicker('setEndDate', maxEndDate);
                $('#end_date').datepicker('update', startDate); // Auto-set end date to start date
            });
            $('#end_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true
            }).on('changeDate', function(e) {
                // Prevent start date from being after end date
                $('#start_date').datepicker('setEndDate', e.date); 
                // Calculate if end date is more than 20 days from start
                var startDate = $('#start_date').datepicker('getDate');
                if (startDate) {
                    var maxAllowedDate = new Date(startDate);
                    maxAllowedDate.setDate(maxAllowedDate.getDate() + 20.00);
                    if (e.date > maxAllowedDate) {
                        $('#end_date').datepicker('update', maxAllowedDate);
                        alert(__('max_20_days_range_alert'));
                    }
                }
            });*/
            $('#start_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true,
                startDate: '+0d'
            }).on('changeDate', function (e) {
                var startDate = e.date;
                $('#end_date').datepicker('setStartDate', startDate); // Prevent end date before start
            });

            $('#end_date').datepicker({
                format: "yyyy-mm-dd",
                todayHighlight: true,
                autoclose: true
            }).on('changeDate', function (e) {
                var endDate = e.date;
                $('#start_date').datepicker('setEndDate', endDate); // Prevent start date after end
            });
            // Initialize Select2 for replacement person dropdown
            $("#replacement_per").select2();
            // Load replacement persons
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_department", dept: deptId},
                success: function(res) {
                    if (res.status == 200) {
                        let options = '';
                        for (let i in res.data) {
                            options += `<option value="${res.data[i].emp_id}">${res.data[i].name.split(' ')[0]+' '+res.data[i].name.split(' ')[1]}</option>`;
                        }
                        $('#replacement_per').append(options);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e);
                },
            });
            // Load employee data
            $.ajax({
                url: './includes/ajaxFile/ajaxEmployee.php',
                dataType: 'JSON',
                type: 'POST',
                data: {ajaxType: "emp_data", empid: empid},
                success: function(res) {
                    if (res.status == 200) {
                        $('input[name="name"]').val(res.data[0].name);
                        $('input[name="empid"]').val(res.data[0].emp_id);
                    }
                },
                error: function(j, e) {
                    errorHandling(j, e);
                },
            });
            // Toggle fields based on vacation type selection
            function toggleVacationFields() {
                const selectedVac = document.querySelector('input[name="vac_type"]:checked');
                // Hide all by default
                $('#flyTypeSection, #replacementSection, #date_select, #notesSection').addClass('d-none');
                if (!selectedVac) return;
                const vacValue = selectedVac.value;
                if (vacValue === 'Local Vacation' || vacValue === 'Fly') {
                    $('#flyTypeSection').removeClass('d-none');
                    // Check if any fly_type is already selected
                    const selectedFlyType = document.querySelector('input[name="fly_type"]:checked');
                    if (selectedFlyType) {
                        const flyVal = selectedFlyType.value;
                        if (flyVal === 'annual' || flyVal === 'emergency') {
                            $('#replacementSection, #date_select').removeClass('d-none');
                        }
                    }
                    // Attach fly_type listener to trigger section toggle
                    document.querySelectorAll('input[name="fly_type"]').forEach(flyRadio => {
                        flyRadio.addEventListener('change', function () {
                            const flyVal = this.value;
                            if (flyVal === 'annual' || flyVal === 'emergency') {
                                $('#replacementSection, #date_select').removeClass('d-none');
                            } else {
                                $('#replacementSection, #date_select').addClass('d-none');
                            }
                        });
                    });
                }
            }
            // Initialize date picker and fields when form is created
            function initVacationForm() {
                document.querySelectorAll('input[name="vac_type"]').forEach(radio => {
                    radio.addEventListener('change', toggleVacationFields);
                });
                toggleVacationFields(); // trigger once on load
            }
            initVacationForm();
        },
        preConfirm: function() {
            const formElement = document.getElementById('submitVacationApplyForm');
            const formData = new FormData(formElement);
            formData.append("ajaxType", "applyVacation");
            const selectedRadio = $('input[name="vac_type"]:checked').val();
            if (!selectedRadio) {
                Swal.showValidationMessage(__('select_vacation_type_validation'));
                return false;
            }
            // Validation for "Local Vacation" or "Fly"
            if (selectedRadio === 'Local Vacation' || selectedRadio === 'Fly') {
                const flyType = $('input[name="fly_type"]:checked').val();
                if (!flyType) {
                    Swal.showValidationMessage(__('select_vacation_type'));
                    return false;
                }
                if (flyType === 'annual' || flyType === 'emergency') {
                    const startDate = $('#start_date').val();
                    const endDate = $('#end_date').val();
                    const replacement = $('#replacement_per').val();
                    if (!startDate || !endDate) {
                        Swal.showValidationMessage(__('start_return_date_required_validation'));
                        return false;
                    }
                    if (!replacement) {
                        Swal.showValidationMessage(__('replacement_person_required_validation'));
                        return false;
                    }
                }
            }
            // No extra validation needed for "Encashed"
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxVacation.php',
                    type: 'POST',
                    dataType: "JSON",
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: formData,
                })
                .done(function (response) {
                    Swal.fire({
                        title: response.title,
                        text: response.message,
                        icon: response.type,
                        allowOutsideClick: false
                    }).then(function (isConfirm) {
                        if (isConfirm) location.reload();
                    });
                })
                .fail(function (jqXHR, textStatus, errorThrown) {
                    reject(handleAjaxFailure(jqXHR, textStatus).message);
                });
            });
        }

    })
});
