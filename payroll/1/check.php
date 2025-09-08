<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>Simple "Select all" checkbox for jQuery</title>
<script type="text/javascript" src="http://code.jquery.com/jquery-2.0.2.js"></script>


</head>
<body>
	<!-- I add "\[" to be sure to select the ring[] checkbox and not check unwanted ones (cf. ringabell below) -->
	<label><input type="checkbox" class="cb-selector" data-for="ring\[" /> <strong>One checkbox to rule them all</strong></label><br/>
	<label><input type="checkbox" name="ring[1]" /> Ring 1</label><br/>
	<label><input type="checkbox" name="ring[2]" /> Ring 2</label><br/>
	<label><input type="checkbox" name="ring[3]" /> Ring 3</label><br/>
	<label><input type="checkbox" name="ring[4]" /> Ring 4</label><br/>
	<hr/>
	<label><input type="checkbox" name="ringabell" /> Bad ring, you won't be checked!</label><br/>
	<hr/>
	<label><input type="checkbox" class="cb-selector" id="3" /> <strong>Another checkbox group</strong></label><br/>
	<label><input type="checkbox" name="pill[1]" id="3" /> Blue pill</label><br/>
	<label><input type="checkbox" name="pill[2]" id="3" /> Red pill</label><br/>
	<label><input type="checkbox" name="pill[3]" id="3" /> Green pill</label><br/>
	
<script type='text/javascript'>
//!function($) {
//	$('input[type=checkbox][class=cb-selector]').click(function() {
//		var cb = $(this),
//			name = cb.attr('data-for');
//
//		if(name == null)
//			return false;
//
//		$('input[type=checkbox][name^='+name+']')
//			.prop('checked', cb.prop('checked'))
//			.click(function() {
//				if(!$(this).prop('checked'))
//					cb.prop('checked', false);
//			});
//	});
//}(jQuery);
	
!function($) {
	$('input[type=checkbox][class=cb-selector]').click(function() {
		var cb = $(this),
			name = cb.attr('id');

		if(name == null)
			return false;

		$('input[type=checkbox][id^='+name+']')
			.prop('checked', cb.prop('checked'))
			.click(function() {
				if(!$(this).prop('checked'))
					cb.prop('checked', false);
			});
	});
}(jQuery);
</script>
</body>
</html>