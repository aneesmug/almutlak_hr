// Main function to handle user creation
$(document).on('click', '.createUserDeptAjax', function(e) {
    e.preventDefault();
    var emp_id = $(this).data('emp_id');
    let hasUserInteracted = false;

    Swal.fire({
        title: 'Create New User',
        html: create_user_HTML(),
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Create User',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => {
            if (hasUserInteracted) {return false;}
            return !Swal.isLoading();
        },
        didOpen: () => {
            setupInputValidations();
            const fields = [
                { id: 'email', event: 'input', validation: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value), requiredMessage: 'Please enter a valid email address.' },
                { id: 'user_type',  event: 'change', validation: (value) => value !== "", requiredMessage: 'Please select an employee type.' }
            ];
            const onFirstInteraction = () => { hasUserInteracted = true; };
            setupDynamicValidation(fields, onFirstInteraction);
        },
        preConfirm: () => {
            return $.ajax({
                url: './includes/ajaxFile/ajaxUser.php',
                type: 'POST',
                data: {
                    emp_id: emp_id,
                    email: $('#email').val(),
                    user_type: $('#user_type').val(),
                    ajaxType: 'create_user'
                },
                cache: false,
                dataType: "json",
            })
            .done(function(response){
                Swal.fire({
                    title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                }).then(function(isConfirm){(isConfirm)?location.reload():""});
            })
            .fail(function(jqXHR, textStatus) {
                const error = handleAjaxFailure(jqXHR, textStatus);
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    })
});

function create_user_HTML() {
    // This function now returns only the form elements, without any custom error containers.
    return `
    <form class="contact-input" id="createUserForm" style="text-align: left;">
        <div class="modal-body">
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="email">Email *</label>
                    <input type="text" id="email" name="email" class="form-control email-validation">
                </div>
                <div class="form-group col-md-12">
                    <label for="user_type">Employee Type *</label>
                    <select id="user_type" name="user_type" class="form-control">
                        <option value="">Select Type</option>
                        <option value="Manager">Manager</option>
                        <option value="Assistant">Assistant</option>
                    </select>
                </div>
            </div>
        </div>
    </form>`;
}

function setupInputValidations() {
    // Sets numeric input mode for specific fields for better mobile UX.
    document.querySelectorAll('.amount-validation, .numeric-only, .saudi-mobile-number').forEach(input => {
        input.setAttribute('inputmode', 'numeric');
        input.setAttribute('pattern', '[0-9]*');
    });

    // Main event listener for real-time input formatting.
    document.body.addEventListener('input', function(event) {
        const input = event.target;
        // Validates and formats amounts to allow decimals.
        if (input.classList.contains('amount-validation')) {
            let value = input.value.replace(/[^0-9.]/g, '');
            const parts = value.split('.');
            if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
            if (parts[1] && parts[1].length > 2) {
                parts[1] = parts[1].substring(0, 2);
                value = parts.join('.');
            }
            input.value = value;
        // Ensures only numbers are entered.
        } else if (input.classList.contains('numeric-only')) {
            input.value = input.value.replace(/\D/g, '');
        // Formats and validates Saudi mobile numbers.
        } else if (input.classList.contains('saudi-mobile-number')) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 1 && value[0] !== '0') value = '0' + value;
            if (value.length >= 2 && value.substring(0, 2) !== '05') value = '05' + value.substring(2);
            input.value = value.substring(0, 10);
        }
    });

    // Event listener for validation after a user leaves an input field.
    document.body.addEventListener('focusout', function(event) {
        const input = event.target;
        if (input.classList.contains('amount-validation')) {
            let value = parseFloat(input.value);
            if (!isNaN(value)) input.value = value.toFixed(2);
        } else if (input.classList.contains('saudi-mobile-number')) {
            const value = input.value;
            const isValid = /^05\d{8}$/.test(value);
            input.classList.toggle('is-invalid', value && !isValid);
        } else if (input.classList.contains('email-validation')) {
            const value = input.value;
            const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            input.classList.toggle('is-invalid', value && !isValid);
        }
    });
}

function setupDynamicValidation(fieldsToValidate, onFirstInteraction = null) {
    const confirmButton = Swal.getConfirmButton();
    let interactionFired = false;

    const elements = new Map(
        fieldsToValidate.map(field => [field.id, document.getElementById(field.id)])
    );

    const validateAll = () => {
        const allValid = fieldsToValidate.every(field => {
            const element = elements.get(field.id);
            return field.validation(element.value);
        });
        confirmButton.disabled = !allValid;
    };

    // This function now uses Swal.showValidationMessage to display errors.
    const updateValidationMessages = () => {
        let invalidFieldsMessages = [];

        // Check each field and collect messages for invalid ones.
        fieldsToValidate.forEach(field => {
            const element = elements.get(field.id);
            const isValid = field.validation(element.value);
            // Highlight the individual invalid field
            element.classList.toggle('is-invalid', !isValid);
            if (!isValid) {
                invalidFieldsMessages.push(field.requiredMessage);
            }
        });

        // If there are errors, join them and display using SweetAlert's function.
        if (invalidFieldsMessages.length > 0) {
            // **UPDATED**: Join messages with a <br> tag to force a line break.
            const htmlMessages = invalidFieldsMessages.join('<br>');
            Swal.showValidationMessage(htmlMessages);
        } else {
            // If all fields are valid, clear the validation message.
            Swal.resetValidationMessage();
        }
    };


    // Attach event listeners to each specified field.
    fieldsToValidate.forEach(field => {
        const element = elements.get(field.id);
        if (element) {
            const handleInteraction = () => {
                if (onFirstInteraction && !interactionFired) {
                    onFirstInteraction();
                    interactionFired = true;
                }
                updateValidationMessages(); // Update the combined error message list
                validateAll(); // Update the button state
            };

            // Validate as the user types or changes a selection.
            element.addEventListener(field.event, handleInteraction);
            
            // Use 'blur' to trigger validation when the user leaves a field.
            element.addEventListener('blur', handleInteraction);
        }
    });

    // Run initial check to disable the button, but don't show messages yet.
    validateAll();
}
