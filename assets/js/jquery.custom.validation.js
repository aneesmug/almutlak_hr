// JavaScript Document
$(document).ready(function(){
  $("#registration").validate({
    // Specify validation rules
    rules: {
		name: "required",
		emp_id: {
			required: true,
			// digits: true,
		},
		iqama: {
			required: true,
			digits: true,
			minlength: 10,
			maxlength: 10,
		},
		iqama_exp: {
			required: true,
		},
		card_exp: {
			required: true,
		},
		// passport_number: "required",
		// passport_exp: "required",
		mobile: {
			required: true,
			digits: true,
			minlength: 10,
			maxlength: 10,
		},
		emg_mobile: {
			required: true,
		},
		emg_name:"required",
		country: "required",
		department: "required",
		sectin_nme: "required",
		emptype: "required",
		joining_date: "required",
		dob: "required",
		blood_type: "required",
		t_shirt_size: "required",
		sex: "required",
		mar_status: "required",
		vac_period: "required",
		salary: "required",
		vacation_days: "required",
		bank_name: "required",
		iban: "required",
		// emp_email: {required: true,email: true,},
		address: "required",
		// insurance_no: "required",
		// insurance_exp: "required",
    },
    messages: {
	name: {
		required: "Enter Emplyee Name",
	},
	emp_id: {
		required: "Enter ID.",
		digits: "Enter valid number",
	},
	iqama: {
		required: "Enter ID / Iqama No.",
		digits: "Enter valid number",
		minlength: "Number field accept only 10 digits",
		maxlength: "Number field accept only 10 digits",
    },
	iqama_exp: {
			required: "Select Hijri Date",
		},
	passport_number: {
		required: "Enter Passport No.",
	},
	passport_exp: {
		required: "Select Passport Expiry",
	},
	card_exp: {
		required: "Select Card Expiry",
	},
	mobile: {
		required: "Enter Mobile No.",
		digits: "Enter valid number",
		minlength: "Number field accept only 10 digits",
		maxlength: "Number field accept only 10 digits",
    },
	emg_mobile:{
		required: "Enter Emergency Mobile No."
	},
	emg_name:{
		required: "Enter Emergency Contact Name",
	},
	country:{
		required: "Select Nationality",
	},
	department:{
		required: "Select Department",
	},
	sectin_nme:{
		required: "Select Section",
	},
	emptype:{
		required: "Select Employee Type",
	},
    joining_date:{
		required: "Select Joining Date",
	},
	dob:{
		required: "Select Date of Birth",
	},
	blood_type:{
		required: "Select Blood Type",
	},
	t_shirt_size:{
		required: "Enter T-Shirt Size",
	},
	sex:{
		required: "Select Gender",
	},
	mar_status:{
		required: "Marital Status",
	},
	vac_period:{
		required: "Select Contract Period",
	},
	salary:{
		required: "Enter Salary",
	},
	vacation_days:{
		required: "Enter Vacation Days",
	},
	bank_name:{
		required: "Select Bank Name",
	},
	iban:{
		required: "Enter IBAN No.",
	}, 
	emp_email: {
		required: "Enter email address",
		email: "Enter a valid email address.",
	}, 
	address: {
		required: "Enter Employee address",
	}, 
	insurance_no: {
		required: "Enter Insurance No.",
	},
	insurance_exp: {
		required: "Select Insurance Expiry Date",
	},

},
  
  });
});