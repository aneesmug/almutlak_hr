/*:::::::::::::::::::::::::::::::HTML HANDLER::::::::::::::::::::::::::::::*/
function item_HTML(sts){
    var statusView = 
    `<div class="form-group col-md-3">
        <label>Status</label><br>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input" name="itmstatus" id="radio5" value="1">
            <label class="custom-control-label" for="radio5">Active</label>
        </div>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input " name="itmstatus" id="radio6" value="0">
            <label class="custom-control-label" for="radio6">Inactive</label>
        </div>
            <!-- <input type="checkbox" name="status" /> -->
    </div>
    `;
    var strView =
    `<form id="submitEditUserForm" enctype="multipart/form-data">
        <div class="form-row customSweetAlertMLR" >
            <div class="form-group col-md-6">
                <label for="name_eng">Name in English</label>
                <input type="text" name="name_eng" id="i_name_eng" class="form-control name_eng">
            </div>
            <div class="form-group col-md-6">
                <label for="name_ar">Name in Arabic</label>
                <input type="text" name="name_ar" id="i_name_ar" class="form-control name_ar">
            </div>
            <div class="form-group col-md-6">
                <label for="price_level">Select Price Type</label>
                <select class="form-control price_level" name="price_level" id="price_level" required="">
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="category_id">Select Category</label>
                <select class="form-control category_id" name="category_id" id="category_id" required="">
                    <option value="">Select</option>
                </select>
            </div>
        </div>
        <div class="form-row customSweetAlertMLR attachmentDIV noneDIV">
            <div class="form-group col-md-3">
                <label for="big_price">Larg Price</label>
                <input type="text" name="big_price" id="i_big_price" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="small_price">Small Price</label>
                <input type="text" name="small_price" id="i_small_price" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="big_cal">Larg Calorie</label>
                <input type="text" name="big_cal" id="i_big_cal" class="form-control">
            </div>
            <div class="form-group col-md-3">
                <label for="small_cal">Small Calorie</label>
                <input type="text" name="small_cal" id="i_small_cal" class="form-control">
            </div>
            <div class="form-group col-md-12">
                <label>Select Item Image</label>
                <div class="input_container" style="margin-top:0 !important;">
                    <input type="file" id="fileupload" />
                </div>
                <input type="hidden" name="iimage" id="iimage" />
            </div>
            ${(sts == 'edit')? statusView :''}
        </div>

            <input type="hidden" id="itemid" name="itemid">
        </div>
    </form>`;
    return strView;
}

function car_HTML(sts){
    var statusView = 
    `<div class="form-group col-md-3">
        <label>Status</label><br>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input" name="status" id="radio5" value="1">
            <label class="custom-control-label" for="radio5">Active</label>
        </div>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input " name="status" id="radio6" value="0">
            <label class="custom-control-label" for="radio6">Inactive</label>
        </div>
            <!-- <input type="checkbox" name="status" /> -->
    </div>
    `;
    var strView =
    `<form id="submitEditUserForm" enctype="multipart/form-data">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="maker_name">Maker Name<span class="text-danger">*</span></label>
                        <select class="form-control" name="maker_name" id="maker_name">
                            <option value="">Select</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="maker_model">Maker Name<span class="text-danger">*</span></label>
                        <select class="form-control" name="maker_model" id="maker_model">
                            <option value="">Select</option>
                        </select>
                    </div>
                    
                    <!--<div class="form-group col-md-3">
                        <label for="model">Model<span class="text-danger">*</span></label>
                        <input type="text" name="model" placeholder="Enter model" class="form-control" id="model">
                    </div>-->

                    <div class="form-group col-md-3">
                        <label for="made_year" >Made Year<span class="text-danger">*</span></label>
                        <input type="text" name="made_year" placeholder="Enter made year" class="form-control" id="made_year">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="type" >Type of car<span class="text-danger">*</span></label>
                        <select class="form-control" name="type" id="type">
                            <option value="">Select</option>
                            <option value="Bus">Bus</option>
                            <option value="Car">Car</option>
                            <option value="Dyna">Dyna</option>
                            <option value="Fork Lift">Fork Lift</option>
                            <option value="Jeep">Jeep</option>
                            <option value="Pick Up">Pick Up</option>
                            <option value="Truck">Truck</option>
                            <option value="Van">Van</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="plate_no" >Plate No.<span class="text-danger">*</span></label>
                        <input type="text" name="plate_no" placeholder="1234-ABC" class="form-control" id="plate_no" autocomplete="off" style="text-transform: uppercase !important;" >
                    </div>
                    <div class="form-group col-md-4">
                        <label for="remarks" >Remarks</label>
                        <input type="text" name="remarks" placeholder="Enter remarks" class="form-control" id="remarks">
                    </div>
                    ${(sts == 'edit')? statusView :''}
                    <input type="hidden" id="carid" name="carid">
                </div>
            </div>
        </div>
    </form>`;
    return strView;
}

function request_line_HTML(){
    var strView = 
    `<form id="submitEditLineForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-4">
                <label for="item_name">Item Name</label>
                <input type="text" name="item_name" class="form-control item_name"  >
            </div>
            <div class="form-group col-md-3">
                <label for="location">Location</label>
                <select id="location" class="form-control" name="location"><option value="">Select</option></select>
            </div>
            <div class="form-group col-md-1">
                <label for="quantity">Quantity</label>
                <input type="text" name="quantity" class="form-control quantity" id='quantity'>
            </div>
            <div class="form-group col-md-2">
                <label for="product_price">Unit Cost</label>
                <input type="text" name="product_price" class="form-control product_price" id='product_price'>
            </div>
            <div class="form-group col-md-2">
                <label for="vat_rate">Item Value</label>
                <input type='text' id='itmvalue' class="form-control itmvalue" name='itmvalue' readonly />
            </div>
            <div class="form-group col-md-2">
                <label for="vat_rate">Vat Rate %</label>
                <input type="text" name="vat_rate" class="form-control vat_rate" id="vat_rate">
            </div>
            <div class="form-group col-md-2">
                <label for="vat_rate">Vat Val. %</label>
                <input type='text' class="form-control vat_val vat_val" id='vat_val' name='vat_val' readonly />
            </div>
            <div class="form-group col-md-3">
                <label for="vat_rate">Amount</label>
                <input type='text' class="form-control amount amount" id='amount' name='amount' readonly />
            </div>
            <div class="form-group col-md-2">
                <label for="idiscount">Discount</label>
                <input type="text" name="idiscount" class="form-control idiscount" id='idiscount' >
            </div>
            <div class="form-group col-md-3">
                <label for="vat_rate">Total</label>
                <input type='text' class="form-control total_cost total_cost" id='total_cost' name='total_cost' readonly />
            </div>
            <input type="hidden" id="itemid" name="itemid">
        </div>
    </form>`;
    return strView;
}

function request_HTML(){
    var strView = 
    `<form id="submitEditRequestForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="item_name">Sub-Title</label>
                <input class="form-control sub_title" type='text' name="sub_title" />
            </div>
            <div class="form-group col-md-6">
                <label for="sub_type">Sub. Type *</label>
                <select id="sub_type" class="form-control" name="sub_type" required>
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="quantity">Tally ID.</label>
                <input class="form-control tally_id" type='text' name='tally_id'/>
            </div>
            <div class="form-group col-md-6">
                <label for="quantity">Injazat ID.</label>
                 <input class="form-control injazat_id" type='text' name='injazat_id'/>
            </div>
            <div class="form-group col-md-12">
                <label for="quantity">Remarks</label>
                <input class="form-control remarks" type='text' name="remarks"/>
            </div>
            <input type="hidden" id="reqid" name="reqid">
        </div>
    </form>`;
    return strView;
}

function category_HTML(sts){
    var statusView = 
    `<div class="form-group col-md-6">
        <label>Status</label><br>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input" name="status" id="radio5" value="1">
            <label class="custom-control-label" for="radio5">Active</label>
        </div>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input " name="status" id="radio6" value="0">
            <label class="custom-control-label" for="radio6">Inactive</label>
        </div>
    </div>
    `;
    var strView = 
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="name_eng">Name in English</label>
                <input type="text" name="name_eng" class="form-control name_eng">
            </div>
            <div class="form-group col-md-6">
                <label for="name_ar">Name in Arabic</label>
                <input type="text" name="name_ar" class="form-control name_ar">
            </div>
            <div class="form-group col-md-6">
                <label for="desc_eng">Description in English</label>
                <input type="text" name="desc_eng" class="form-control desc_eng">
            </div>
            <div class="form-group col-md-6">
                <label for="desc_ar">Description in Arabic</label>
                <input type="text" name="desc_ar" class="form-control desc_ar">
            </div>
            <div class="form-group col-md-12">
                <label for="category_type">Category Type</label>
                <select class="form-control" name="category_type" id="category_type" class="category_type">
                    <option value="">Select</option>
                </select>
            </div>
            ${(sts == 'edit')? statusView :''}
            <input type="hidden" class="smid" name="smid">
        </div>
    </form>`;
    return strView;
}

function location_HTML(sts){
    var statusView = 
    `<div class="form-group col-md-3">
        <label>Status</label><br>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input" name="status" id="radio5" value="1">
            <label class="custom-control-label" for="radio5">Active</label>
        </div>
        <div class="d-inline-block custom-control custom-radio mr-1">
            <input type="radio" class="custom-control-input " name="status" id="radio6" value="0">
            <label class="custom-control-label" for="radio6">Inactive</label>
        </div>
    </div>
    `;
    var strView = 
    `<form id="submitlocationForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-2">
                <label for="section_name">Location Name<span class="text-danger">*</span></label>
                <input type="text" name="section_name" placeholder="Enter section name" class="form-control section_name" >
            </div>
            <div class="form-group col-md-2">
                <label for="latitude">Latitude<span class="text-danger">*</span></label>
                <input type="text" name="latitude" placeholder="Enter google latitude" class="form-control latitude" >
            </div>
            <div class="form-group col-md-2">
                <label for="longitude">Longitude<span class="text-danger">*</span></label>
                <input type="text" name="longitude" placeholder="Enter google longitude" class="form-control longitude" >
            </div>
            <div class="form-group col-md-2">
                <label for="b_license_exp_hijri">Balady License Exp.<span class="text-danger">*</span></label>
                <input type="text" name="b_license_exp" placeholder="Enter Balady License Exp." class="form-control b_license_exp_hijri" id="b_license_exp_hijri">
            </div>
            <div class="form-group col-md-2">
                <label for="b_license_no">Balady License No.<span class="text-danger">*</span></label>
                <input type="text" name="b_license_no" placeholder="Enter Balady License No." class="form-control b_license_no" >
            </div>                      
            <div class="form-group col-md-2">
                <label for="dept">Select Department<span class="text-danger">*</span></label>
                <select class="form-control" name="dept" id="dept">
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label for="camera_in">Camera (IN)</label>
                <input type="text" name="camera_in" placeholder="Enter camera Inside" class="form-control camera_in" >
            </div>
            <div class="form-group col-md-2">
                <label for="camera_out">Camera (OUT)</label>
                <input type="text" name="camera_out" placeholder="Enter camera outside" class="form-control camera_out" >
            </div>
            <div class="form-group col-md-2">
                <label for="t_bulding_size">Total Bulding Size (M)</label>
                <input type="text" name="t_bulding_size" placeholder="Enter total bulding base in metters" class="form-control t_bulding_size">
            </div>
            <div class="form-group col-md-2">
                <label for="bulding_base">Bulding Base</label>
                <input type="text" name="bulding_base" placeholder="Enter bulding base" class="form-control bulding_base" >
            </div>
            <div class="form-group col-md-2">
                <label for="bulding_size">Bulding Size (L * W)</label>
                <input type="text" name="bulding_size" placeholder="Enter Bulding Size (L * W)" class="form-control bulding_size" >
            </div>                    
            <div class="form-group col-md-2">
                <label for="location_dist">District<span class="text-danger">*</span></label>
                <input type="text" name="location_dist" placeholder="Enter District" class="form-control location_dist" >
            </div>                      
            <div class="form-group col-md-2">
                <label for="municipality">Municipality</label>
                <input type="text" name="municipality" placeholder="Enter Municipality name" class="form-control municipality" >
            </div>                      
            <div class="form-group col-md-2">
                <label for="sub_municipality">Sub-municipality</label>
                <input type="text" name="sub_municipality" placeholder="Enter sub municipality name" class="form-control "sub_municipality>
            </div>
            <div class="form-group col-md-5">
                <label for="loc_address">Location Address</label>
                <input type="text" name="loc_address" placeholder="Enter location address" class="form-control loc_address">
            </div>
            ${(sts == 'edit')? statusView :''}
            <input type="hidden" class="smid" name="smid">
        </div>
    </form>`;
    return strView;
}

function maintenance_HTML(){
    var strView = 
    `<form id="submitMaintenanceForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="car_user">Select Driver<span class="text-danger">*</span></label>
                <select class="form-control" name="car_user" id="car_user">
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="name_eng">Select Date</label>
                <input type="text" name="date" class="form-control" id="date">
            </div>
            <div class="form-group col-md-3">
                <label for="meter">New Meter Reading</label>
                <input type="text" name="meter" class="form-control meter" placeholder="12345678">
            </div>
            <div class="form-group col-md-3">
                <label for="oldmeter">Old Meter Reading</label>
                <input type="text" name="oldmeter" readonly class="form-control oldmeter" id="oldmeter" value="">
            </div>
            <div class="form-group col-md-3">
                <label for="diffmeter">Diff. Meter Reading</label>
                <input type="text" name="diffmeter" readonly class="form-control diffmeter" id="diffmeter">
            </div>
            <div class="form-group col-md-2">
                <label for="type">Select Type<span class="text-danger">*</span></label>
                <select class="form-control" name="type" id="type">
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-1">
                <label for="type">Add</label>
                <a href="javascript:void(0);" class="form-control btn btn-success btn-small addTypeAtter" id="addTypeAtter">
                    <i class="fa fa-plus"></i>
                </a>
            </div>
            <div class="form-group col-md-6">
                <label for="details">Description for maintenance</label>
                <input type="text" name="details" class="form-control details">
            </div>
            <div class="form-group col-md-6">
                <label for="remarks">Remarks</label>
                <input type="text" name="remarks" class="form-control remarks">
            </div>
            <input type="hidden" class="cid" name="cid">
        </div>
    </form>`;
    return strView;
}

function addType_HTML(){
    var strView = 
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="name_eng">Type name<span class="text-danger">*</span></label>
                <input type="text" name="type" class="form-control">
            </div>
        </div>
    </form>`;
    return strView;
}

function documents_HTML(){
    var strView = 
    `<form id="submitDocumentsForm" enctype="multipart/form-data">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-4">
                <label for="doc_type">Type of document<span class="text-danger">*</span></label>
                <select class="form-control" name="doc_type" id="doc_type">
                    <option value="">Select</option>
                    <option value="Licence">Licence</option>
                    <option value="Insurance">Insurance</option>
                    <option value="MVPI">MVPI</option>
                </select>
            </div>
            <div class="form-group col-md-8 input-daterange" id="date_select">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="issue_date">Issue Date<span class="text-danger">*</span></label>
                        <input type="text" name="issue_date" placeholder="Select issue date" class="form-control" id="issue_date">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="exp_date">Expiry Date<span class="text-danger">*</span></label>
                        <input type="text" name="exp_date" required placeholder="Select expiry date" class="form-control" id="exp_date">
                    </div>
                </div>
            </div>
            <div class="form-group col-md-12">
                <label class="radioalign">Attachment<span class="text-danger">*</span></label>
                
                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="inlineRadio3" value="yes" name="attach" class="showAttachment">
                    <label for="inlineRadio3" class="atch"><i class="mdi mdi-paperclip"></i> Have Attachments</label>
                </div>

                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="inlineRadio2" value="no" name="attach" class="hideAttachment">
                    <label for="inlineRadio2" class="atch"><i class="mdi mdi-clippy"></i> No Attachment</label>
                </div>

                <!--<label class="noneDIV attachmentDIV" for="checkatt">Browse files</label>-->
                <div class="input_container noneDIV attachmentDIV">
                    <input type="file" id="checkatt" class="checkatt">
                </div>
            </div>
            <input type="hidden" class="cid" name="cid">
        </div>
    </form>`;
    return strView;
}

function driver_HTML(){
    var strView = 
    `<form id="submitDriverForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="doc_type">Select driver name<span class="text-danger">*</span></label>
                <select class="form-control" name="car_user" id="car_user">
                    <option>Select</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="name_eng">Select Date</label>
                <input type="text" name="rcv_date" class="form-control" id="date">
            </div>
            <input type="hidden" class="cid" name="cid">
        </div>
    </form>`;
    return strView;
}

function customer_HTML(){
    var strView = 
    `<form id="submitCustomerForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-4">
                <label for="full_name">Customer Name<span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" id="full_name" autocomplete="off" style="text-transform: uppercase !important;" >
            </div>
            <div class="form-group col-md-4">
                <label for="injazat_no">Injazat No.<span class="text-danger">*</span></label>
                <input type="text" name="injazat_no" data-v-max="999999" data-v-min="0" parsley-trigger="change" class="form-control autonumber" id="injazat_no" autocomplete="off">
            </div>
            <div class="form-group col-md-4">
                <label for="mobile">Mobile No.<span class="text-danger">*</span></label>
                <input type="text" name="mobile" data-mask="0599999999" parsley-trigger="change" class="form-control" id="mobile" autocomplete="off">
            </div>
            <div class="form-group col-md-4">
                <label for="acc_no">Account No.<span class="text-danger">*</span></label>
                <input type="text" name="acc_no" parsley-trigger="change" class="form-control" id="acc_no" autocomplete="off" style="text-transform: uppercase !important;" >
            </div>
            <div class="form-group col-md-4">
                <label for="card_exp">Card Expire<span class="text-danger">*</span></label>
                <input type="text" name="card_exp" parsley-trigger="change" class="form-control" id="card_exp" autocomplete="off">
            </div>
            <div class="form-group col-md-4">
                <label for="location">For Shop<span class="text-danger">*</span></label>
                <select class="form-control" name="location" id="location">
                    <option value="">Select</option>
                </select>
            </div>
            <input type="hidden" name="id">
        </div>
    </form>`;
    return strView;
}

function cust_upd_HTML(){
    var strView = 
    `<form id="submitCustomerCardUpdForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="card_exp">Card Expire</label>
                <input type="text" name="card_exp" parsley-trigger="change" class="form-control" autocomplete="off" id="card_exp">
            </div>
            <div class="form-group col-md-6">
                <label for="location">For Shop</label>
                <select class="form-control" name="location" id="location">
                    <option value="">Select</option>
                </select>
            </div>
        </div>
        <input type="hidden" name="id">
        <input type="hidden" name="injazat_no">
    </form>`;
    return strView;
}

function cust_add_HTML(){
    var strView = 
    `<form id="submitCustomerCardAddForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="injazat_no">New Injazat No.</label>
                <input type="text" name="injazat_no" data-v-max="999999" data-v-min="0" parsley-trigger="change" class="form-control" autocomplete="off">
            </div>
            <div class="form-group col-md-6">
                <label for="acc_no">Account No.</label>
                <input type="text" name="acc_no" parsley-trigger="change" class="form-control" >
            </div>
            <div class="form-group col-md-6">
                <label for="card_exp">Card Expire</label>
                <input type="text" name="card_exp" parsley-trigger="change" class="form-control"autocomplete="off" id="card_exp">
            </div>
            <div class="form-group col-md-6">
                <label for="location">For Shop</label>
                <select class="form-control" name="location" id="location">
                    <option value="">Select</option>
                </select>
            </div>
        </div>
        <input type="hidden" name="id">
    </form>`;
    return strView;
}

function loc_contract_HTML(){
    var strView = 
    `<form id="submitlocationContractForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-3">
                <label for="owner_name">Location Owner Name<span class="text-danger">*</span></label>
                <input type="text" name="owner_name" placeholder="Enter owner name" class="form-control" id="owner_name" >
            </div>
            <div class="form-group col-md-3">
                <label for="owner_number">Owner Number<span class="text-danger">*</span></label>
                <input type="text" name="owner_number" placeholder="Enter Owner number" class="form-control" id="owner_number" parsley-trigger="change" data-mask="0599999999" >
            </div>
            <div class="form-group col-md-3">
                <label for="owner_email">Owner Email<span class="text-danger">*</span></label>
                <input type="text" name="owner_email" placeholder="Enter owner email" class="form-control" id="owner_email" autocomplete="off" >
            </div>
            <div class="form-group col-md-3">
                <label for="contract_no">Contract No.<span class="text-danger">*</span></label>
                <input type="text" name="contract_no" placeholder="Enter contract no" class="form-control" id="contract_no" autocomplete="off">
            </div>
            <div class="form-group col-md-3">
                <label for="start_cont_date">Contract Starting Date<span class="text-danger">*</span></label>
                <input type="text" name="start_cont_date" placeholder="Enter Contract Start Date" class="form-control" id="start_cont_date"  autocomplete="off" required>
            </div>
            <div class="form-group col-md-3">
                <label for="end_cont_date">Contract Ending Date<span class="text-danger">*</span></label>
                <input type="text" name="end_cont_date" placeholder="Enter Contract Ending Date" class="form-control" id="end_cont_date"  autocomplete="off" required>
            </div>
            <div class="form-group col-md-3">
                <label for="rent">Amount of Rent<span class="text-danger">*</span></label>
                <input type="text" name="rent" placeholder="Enter Amount of Rent" class="form-control autonumber" id="rent" autocomplete="off" required>
            </div>
            <div class="form-group col-md-3">
                <label for="service">Amount of Services</label>
                <input type="text" name="service" placeholder="Enter Amount of Services" class="form-control autonumber" id="service" autocomplete="off">
            </div>                    
            <div class="form-group col-md-3">
                <label for="elect_prc">Amount of Electric City</label>
                <input type="text" name="elect_prc" placeholder="Enter Amount of Electric City" class="form-control autonumber" id="elect_prc" autocomplete="off" >
            </div>                      
            <div class="form-group col-md-3">
                <label for="water_prc">Amount of Water</label>
                <input type="text" name="water_prc" placeholder="Enter Balady License No." class="form-control autonumber" id="water_prc" autocomplete="off">
            </div>                      
            <div class="form-group col-md-3">
                <label for="incuranse_prc">Amount of Incuranse<span class="text-danger">*</span></label>
                <input type="text" name="incuranse_prc" placeholder="Enter Amount of Incuranse" class="form-control autonumber" id="incuranse_prc" autocomplete="off">
            </div>                      
            <div class="form-group col-md-3">
                <label for="others">Others</label>
                <input type="text" name="others" placeholder="Enter others" class="form-control autonumber" id="others" autocomplete="off" >
            </div> 
        </div>
        <input type="hidden" name="locid">
    </form>`;
    return strView;
}

function edit_password_HTML(){
    var strView =
    `<form class="contact-input" id="validatedForm" class="submitEditUserPassForm">
        <div class="modal-body">
            <div class="form-row">
            <div class="form-group col-md-6">
                <label for="name">Enter new password</label>
                <input type="password" id="password" name="password" class="form-control">
            </div>
            <div class="form-group col-md-6">
                <label for="name">Confirm password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control">
            </div>
        </div>
    </form>
    `;
    return strView;
}

function edit_user_HTML(){
    var strView =
    `<form id="submitEditUserForm">
    <div class="form-row customSweetAlertMLR">
        <div class="form-row customSweetAlertMLR">
        <div class="form-group col-md-4">
            <label for="name">Full Name</label>
            <input type="text" id="fullname" name="fullname" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">Username</label>
            <input type="text" id="username" name="username" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">Department</label>
            <input type="text" id="dept" name="dept" class="form-control" readonly="">
        </div>
        <div class="form-group col-md-4">
            <label for="name">Type of Permission</label>
            <select class="custom-select" name="user_type" id="user_type" required="">
                <option value="administrator">Administrator</option>
                <option value="dept_user">Department Manager</option>
                <option value="employee">Employee</option>
                <option value="gm">Grneran Manager</option>
                <option value="hr">Human Resource</option>
            </select>
        </div>
        <div class="form-group col-md-4">
            <label for="name">Email</label>
            <input type="text" id="email" name="email" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">Email Password</label>
            <input type="text" id="email_pass" name="email_pass" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">Mobile</label>
            <input type="text" id="mobile" name="mobile" class="form-control">
        </div>
        <div class="form-group col-md-4">
            <label for="name">Changing Password</label>
            <a href="javascript:void:(0);" class="btn bt-sm btn-warning updatePasswordAjax" id="idpass" >Update Password</a>
        </div>
        <div class="form-group col-md-4">
            <br><br>
            <div class="d-inline-block custom-control custom-radio mr-1">
                <input type="radio" class="custom-control-input" name="status" id="radio1" value="1">
                <label class="custom-control-label" for="radio1">Active</label>
            </div>
            <div class="d-inline-block custom-control custom-radio mr-1">
                <input type="radio" class="custom-control-input" name="status" id="radio2" value="0">
                <label class="custom-control-label" for="radio2">Inactive</label>
            </div>
        </div>
    <input type="hidden" id="iduser" name="id"></div>
    <input type="hidden" id="oldpass" name="oldpass"></div></form>
    `;
    return strView;
}

function endOfService_HTML(){
    var strView =
    `<form id="calculatorForm">
            <h1><p value="0" id="resultCalc">0</p></h1>
            <!--<input type="text" class="form-control" id="resultCalc">-->
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-lg-6 col-sm-12">
                <label>Type of Contract<span class="text-danger">*</span></label>
                <select id="inputPeriod" required class="form-control" >
                    <option selected value="">Select type</option>
                    <option value="47">Fixed time</option>
                    <option value="48">Unlimited period</option>
                </select>
            </div>
            <div class="form-group col-lg-6 col-sm-12">
                <label>End of Service Reason<span class="text-danger">*</span></label>
                <select id="inputState" required class="form-control">
                    <option selected value="">Select reason</option>
                </select>
            </div>
            <div class="form-group col-md-8" id="event_period">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="joining_date">Joining Date<span class="text-danger">*</span></label>
                        <input type="text" name="joining_date" placeholder="Select Join Date" class="form-control" id="joining_date">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="end_date">End of Service Date<span class="text-danger">*</span></label>
                        <input type="text" name="end_date" required placeholder="Select end date" class="form-control" id="end_date">
                    </div>
                </div>
            </div>
            <div class="form-group col-lg-4">
                <label>Salary</label>
                <input type="text" required class="form-control" id="salary" name="salary" readonly>
            </div>
            <div class="form-group col-lg-4">
                <label>Duration of service (years) </label>
                <input type="text" class="form-control" id="yearsPeriod" readonly>
            </div>
        
            <div class="form-group col-lg-4">
                <label>Number of months</label>
                <input type="text" class="form-control" id="monthsPeriod" readonly>
            </div>
            <div class="form-group col-lg-4">
                <label>Number of day</label>
                <input type="text" class="form-control" id="daysPeriod" readonly>
                <input type="hidden" id="finalAmount" readonly>
            </div>

        </div>
    <input type="hidden" id="empid" name="empid"></div>
    </form>
    `;
    return strView;
}

/*function eosReportPrint(name,email,idiqama,idiqamaexpiry,passport,passportexpiry,dob,age,gender,mstatus,mobile,country,joining_date,dept,sectin_nme,salary,address,status, yearsPeriod, monthsPeriod, daysPeriod, finalAmount){
    var htmlRpt = `
        <div class="row">
            <div class="col-12">
                <div class="card-box">
                    <table class="table table-hover mb-0" style="width: 100%">
                        <thead class="thead-dark">
                            <tr>
                                <th colspan="4">
                                    <center>
                                        <h1>${finalAmount.value}</h1>
                                        <h2>ﺔﻴﺋﺎﻬﻧ ﺔﺼﻟﺎﺨﻣ</h2>
                                        <h2>FINAL SETTLEMENT</h2>
                                    </center>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th>Name of Employee:</th>
                                <td>${name}</td>
                                <th>Emali Address:</th>
                                <td>${email}</td>
                            </tr>
                            <tr>
                                <th>Iqama / ID:</th>
                                <td>${idiqama}</td>
                                <th>Iqama / ID Expiry:</th>
                                <td>${idiqamaexpiry}</td>
                            </tr>
                            <tr>
                                <th>Passport No.:</th>
                                <td>${passport}</td>
                                <th>Passport Expiry:</th>
                                <td>${passportexpiry}</td>
                            </tr>
                                <tr>
                                <th>Date of Birth:</th>
                                <td>${dob}</td>
                                <th>Age:</th>
                                <td>${age}</td>
                            </tr>
                            <tr>
                                <th>Gender:</th>
                                <td>${gender}</td>
                                <th>Marital Status:</th>
                                <td>${mstatus}</td>
                            </tr>
                            <tr>
                                <th>Mobile No.:</th>
                                <td>${mobile}</td>
                                <th>Country:</th>
                                <td>${country}</td>
                            </tr>
                            <tr>
                                <th>Date Hired:</th>
                                <td>${joining_date}</td>
                                <th>Department:</th>
                                <td>${dept}</td>
                            </tr>
                            <tr>
                                <th>Section Area:</th>
                                <td>${sectin_nme}</td>
                                <th>Current Salary:</th>
                                <td>${salary}</td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td colspan="3">${address}</td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table table-hover mb-0" style="width: 100%">
                        <tbody>
                            <tr>
                                <th>Years:</th>
                                <th>Months:</th>
                                <th>Days:</th>
                            </tr>
                            <tr>
                                <td>${yearsPeriod.value}</td>
                                <td>${monthsPeriod.value}</td>
                                <td>${daysPeriod.value}</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    `
    return htmlRpt;
}*/ // Not Used

function social_add_HTML(){
    var strView = 
    `<form id="submitCustomerCardAddForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="link">Add Link Address</label>
                <input type="text" name="link" class="form-control" >
            </div>
            <div class="form-group col-md-6">
                <label for="social_link">For Shop</label>
                <select class="form-control" name="social_id" id="social_link">
                    <option value="">Select</option>
                </select>
            </div>
        </div>
        <input type="hidden" name="emp_id">
    </form>`;
    return strView;
}

function portfolio_add_HTML(){
    var strView = 
    `<form id="submitCustomerCardAddForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="link">Add Portfolio title (*)</label>
                <input type="text" id="title" class="form-control" >
            </div>
            <div class="form-group col-md-6">
                <label for="link">Select attachment file (*)</label>
                <div class="input_container" style="margin-top:0 !important">
                    <input type="file" id="fileupload" accept="image/*, application/pdf">   
                </div>
            </div>
            <div class="form-group col-md-12">
                <label for="link">Description of portfolio (*)</label>
                <div id="inlineeditor"></div>
            </div>
        </div>
        <input type="hidden" name="emp_id">
    </form>`;
    return strView;
}

function id_exp_HTML(){
    var strView =
    `<form class="contact-input" id="submitEditEmployeeIDForm">
        <div class="modal-body">
            <div class="form-row">
                <div class="col-md-12">
                    <label for="inlineRadio" class="col-form-label radioalign">Select Date Type<span class="text-danger">*</span></label>
                    <div class="d-inline-block custom-control custom-radio mr-1">
                        <input type="radio" class="custom-control-input" id="hijri" value="hijri" name="note">
                        <label class="custom-control-label" for="hijri" style="cursor:pointer">Hijri Date</label>
                    </div>
                    <div class="d-inline-block custom-control custom-radio mr-1">
                        <input type="radio" class="custom-control-input" id="gregorian" value="gregorian" name="note">
                        <label class="custom-control-label" for="gregorian" style="cursor:pointer">Gregorian Date</label>
                    </div>
                    <div class="form-group col-md-12" id="hijriDiv" style="display:none;">
                        <input type="text" class="form-control mt-2" id="iq_id_exp_hijri" readonly="readonly">
                        <input type="hidden" id="emid" name="id" class="form-control">
                    </div>
                    <div class="form-group col-md-12" id="gregorianDiv" style="display:none;">
                        <input type="text" class="form-control mt-2" id="iq_id_exp_greg" readonly="readonly">
                        <input type="hidden" id="emid" name="id" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </form>
    `;
    return strView;
}

function empDocuments_HTML(){
    var strView = 
    `<form id="submitDocumentsForm" enctype="multipart/form-data">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="docu_typ">Type of document<span class="text-danger">*</span></label>
                <select class="form-control" name="docu_typ" id="docu_typ">
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-12">
                <label for="checkatt">Attachment<span class="text-danger">*</span></label>
                <div class="input_container" style="margin-top: 0 !important">
                    <input type="file" id="checkatt" class="checkatt">
                </div>
            </div>
            <input type="hidden" class="id" name="id">
            <input type="hidden" class="emp_id" name="emp_id">
        </div>
    </form>`;
    return strView;
}

function Voucher_HTML(){
    var strView = 
    `<form id="submitVoucherForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-6">
                <label for="emp_v_user">Select Employee<span class="text-danger">*</span></label>
                <select class="form-control" name="emp_v_user" id="emp_v_user">
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="voucher_type">Select Voucher<span class="text-danger">*</span></label>
                <select class="form-control" name="voucher_type" id="voucher_type">
                    <option value="">Select</option>
                    <option value="receipt">Payment Receipt سندقبض   </option>
                    <option value="payment">Payment Voucher سندصراف   </option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="amount">Amount<span class="text-danger">*</span></label>
                <input type="text" name="amount" id="amount" class="form-control amount" placeholder="12345678" onkeypress="return isNumber(event)">
            </div>
            <div class="form-group col-md-6">
                <label for="details">Details<span class="text-danger">*</span></label>
                <input type="text" name="details" class="form-control details" id="details" value="">
            </div>
            <div class="form-group col-md-6">
                <label for="acc_no">Account No.</label>
                <input type="text" name="acc_no" class="form-control acc_no" id="acc_no">
            </div>
            <div class="form-group col-md-6">
                <label for="chq_no">Cheque No.</label>
                <input type="text" name="chq_no" class="form-control chq_no" id="chq_no">
            </div>
            <div class="form-group col-md-12">
                <label class="radioalign">Attachment<span class="text-danger">*</span></label>
                
                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="inlineRadio3" value="yes" name="attach" class="showAttachment">
                    <label for="inlineRadio3" class="atch"><i class="mdi mdi-paperclip"></i> Have Attachments</label>
                </div>

                <div class="radio radio-info form-check-inline">
                    <input type="radio" id="inlineRadio2" value="no" name="attach" class="hideAttachment">
                    <label for="inlineRadio2" class="atch"><i class="mdi mdi-clippy"></i> No Attachment</label>
                </div>

                <!--<label class="noneDIV attachmentDIV" for="checkatt">Browse files</label>-->
                <div class="input_container noneDIV attachmentDIV">
                    <input type="file" id="checkatt" class="checkatt">
                </div>
            </div>
            <input type="hidden" class="empid" name="empid" id="empid">
        </div>
    </form>`;
    return strView;
}

function addCarModel_HTML(){
    var strView = 
    `<form id="submitEditCategoryForm">
        <div class="form-row customSweetAlertMLR">
            <div class="form-group col-md-12">
                <label for="name_eng">Car Model Name<span class="text-danger">*</span></label>
                <input type="text" name="maker_model" class="form-control">
                <input type="hidden" name="maker_name" class="form-control">
            </div>
        </div>
    </form>`;
    return strView;
}

/*:::::::::::::::::::::::::::::::HTML HANDLER::::::::::::::::::::::::::::::*/