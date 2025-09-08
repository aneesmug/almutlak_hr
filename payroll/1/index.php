<?php

include("./../includes/db.php");
$datechk = date('m');
?>

<!-- <form action="" method="post" id="ambil_matkul" onsubmit="return validateForm()"> -->
<form action="" method="post" id="submitSalaryForm">
	<table class="tabel table-bordered table-stripped table-responsive" border="1">
		<tr>
			<th width="5%">Check All<br><input type="checkbox" name="sample" class="selectall" /></th>
			<th>Emp_ID</th>
			<th>Name</th>
			<th>Basic</th>
			<th>Housing</th>
			<th>Transport</th>
			<th>Others</th>
			<th>Benefit</th>
			<th>Deduction</th>
			<th>Salary</th>
			<th>Status</th>
		</tr>
		<?php
		$sql = "SELECT 
            `emp`.*,
            `py`.`basic`,
            `py`.`housing`,
            `py`.`transport`,
            `py`.`other_pay`,
            SUM(`pyd`.`deduction`) AS `deduction`,
            SUM(`pyb`.`benefit`) AS `benefit`
        FROM `employees` `emp` 
        LEFT JOIN `payroll` `py` ON `emp`.`emp_id` = `py`.`emp_id` 
        LEFT JOIN `payroll_deductions` `pyd` ON `emp`.`emp_id` = `pyd`.`emp_id` AND `pyd`.`month` = '$datechk' AND `pyd`.`status` = 1
        LEFT JOIN `payroll_benefits` `pyb` ON `emp`.`emp_id` = `pyb`.`emp_id` AND `pyb`.`month` = '$datechk' AND `pyb`.`status` = 1
        WHERE `emp`.`status`=1 AND `emp`.`emp_sup_type` = 'mocha'
        GROUP BY 
            `emp`.`emp_id`,
            `py`.`basic`,
            `py`.`housing`,
            `py`.`transport`,
            `py`.`other_pay`
        ORDER BY `emp`.`sectin_nme` ASC, `emp`.`name` ASC 
        ";
		$result = $conDB->query($sql);
		if ($result->num_rows > 0) {
			$previous_category = "";   // To keep track of the last printed category
			while ($row = $result->fetch_assoc()) {
				$category = $row['sectin_nme'];
				$name = $row['name'];
				$emp_id = $row['emp_id'];
                $basic = $row["basic"];
                $housing = $row["housing"];
                $transport = $row["transport"];
                $other_pay = $row["other_pay"];
                $deduction = $row["deduction"];
                $benefit = $row["benefit"];

				$sqlhk = "SELECT 
                * FROM `payroll` 
                WHERE `emp_id`='" . $emp_id . "' AND `month`='" . date('m') . "' 
                ";
				$resulthk = $conDB->query($sqlhk);
				while ($rowhk = $resulthk->fetch_assoc()) {
					$chkid = $rowhk["emp_id"];
				}

				if ($previous_category !== $category) { // If this category has never been printed, we print it
		?>
					<th style="background: #D1D0D0;">
					<?php if($chkid != $row["emp_id"]){ ?>
						<input type="checkbox" class="cb-selector" id="<?= str_replace(' ', '', $category) ?>" />
					<?php } ?>
					</th>
					<th style="background: #D1D0D0;" colspan='10'><?= $category ?> </th>

				<?php
					$previous_category = $category;
				}
				?>
				<tr>
					<th>
						<?php if($chkid != $row["emp_id"]){ ?>
						<input type="checkbox" class="justone" name="idk[]" value="<?= $row['id']; ?>" id="<?= str_replace(' ', '', $category) ?>" />
						<?php } ?>
						<input type="hidden" name="chk_id[]" value="<?= $row["id"]; ?>" />
						<input type="hidden" name="name[]" value="<?= $row["name"]; ?>">
						<input type="hidden" name="salary[]" value="<?= $row["salary"]; ?>">
						<input type="hidden" name="emp_id[]" value="<?= $row["emp_id"]; ?>">
					</th>
                    <td><?= $row['emp_id']; ?></td>
					<td><?= $row['name']; ?></td>
                    <td><?= isset($basic) ? $basic : '' ?></td>
                    <td><?= isset($housing) ? $housing : '' ?></td>
                    <td><?= isset($transport) ? $transport : '' ?></td>
                    <td><?= isset($other_pay) ? $other_pay : '' ?></td>
                    <td><?= isset($benefit) ? $benefit : '' ?></td>
                    <td><?= isset($deduction) ? $deduction : '' ?></td>
                    <td><?= (isset($basic) && isset($housing) && isset($transport) && isset($other_pay)) ? ($basic + $housing + $transport + $other_pay + $benefit - $deduction) : '';?></td>
					<th width="70	">
						<?php
							if ($chkid == $row["emp_id"]) {
								echo "Generated";
							}
						?>
					</th>
				</tr>
		<?php
			}
		}
        $sqlsum = "SELECT 
            COALESCE(SUM(`py`.`basic`), 0) AS `basic`,
            SUM(`py`.`housing`) AS `housing`,
            SUM(`py`.`transport`) AS `transport`,
            SUM(`py`.`other_pay`) AS `other_pay`,
            COALESCE(SUM(`pyd`.`deduction`), 0) AS `deduction`,
            COALESCE(SUM(`pyb`.`benefit`), 0) AS `benefit`
            FROM `payroll` `py` 
            LEFT JOIN (
                SELECT `emp_id`, SUM(`deduction`) AS `deduction`
                FROM `payroll_deductions`
                WHERE `month` = ? AND `status` = 1
                GROUP BY `emp_id`
            ) `pyd` ON `py`.`emp_id` = `pyd`.`emp_id`
            LEFT JOIN (
                SELECT `emp_id`, SUM(`benefit`) AS `benefit`
                FROM `payroll_benefits`
                WHERE `month` = ? AND `status` = 1
                GROUP BY `emp_id`
            ) `pyb` ON `py`.`emp_id` = `pyb`.`emp_id`
            WHERE `py`.`status` = 1 AND `py`.`month` = ?
        ";
        $stmt = $pdo->prepare($sqlsum);
        $stmt->execute([$datechk, $datechk, $datechk]);
        $result = $stmt->fetch();
		?>
        <tr>
            <th  colspan='3'></th>
            <th><?= $result["basic"]; ?></th>
            <th><?= $result["housing"]; ?></th>
            <th><?= $result["transport"]; ?></th>
            <th><?= $result["other_pay"]; ?></th>
            <th><?= $result["benefit"]; ?></th>
            <th><?= $result["deduction"]; ?></th>
            <th><?= ($result["basic"] + $result["housing"] + $result["transport"] + $result["other_pay"] + $result["benefit"]) - ($result["deduction"]) ?></th>
            <th width="70">TOTAL</th>
        </tr>
	</table>
	<hr />
	<div class="btn-group">
	<?php //if($chkid != $row["emp_id"]){ ?>
		<button class="btn btn-sm btn-success addDrvrAtter" type="submit" name="submit"><i class="fa fa-plus"></i> Submit</button>
	<?php //} ?>
	</div>
</form>
<!--<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>-->
<script src="http://code.jquery.com/jquery-2.0.2.js"></script>
<script type='text/javascript' src="./../plugins/sweet-alert/v11/sweetalert2.js"></script>
<script type='text/javascript' src="./../plugins/sweet-alert/v11/sweetalert2.min.js"></script>
<script type='text/javascript' src="./../plugins/sweet-alert/v11/sweetalert2.all.js"></script>
<script type='text/javascript' src="./../plugins/sweet-alert/v11/sweetalert2.all.min.js"></script>
<!-- <script src="./../assets/js/jquery.app.js"></script> -->
<script type="text/javascript">

$(document).on('click', '.addDrvrAtter', function (e) {
    e.preventDefault();
    var cid = $(this).data('id');
    Swal.fire({
        title: 'Register payroll.',
        text: "You won't be able to revert this!",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Register!',
        showLoaderOnConfirm: true,
        allowOutsideClick: false,
        // width: "30%",
        preConfirm: function() {
            var chk_id = $('input[name="idk[]"]:checked');
            if(chk_id.length === 0){
                Swal.showValidationMessage(`Please select at least one employee to generate payroll.`)
            } 
            return new Promise(function(reject, resolve) {
                if( chk_id.length === 0){
                    reject("Please fill all mendatory(*) fields first!");
                    return false;
                }
                $.ajax({
                    url: './../includes/ajaxFile/ajaxSalary.php',
                    type: 'POST',
                    dataType: "JSON",
                    data: $("#submitSalaryForm").serialize()+'&'+$.param({ajaxType: "add_salary"}),
                })
                .done(function(response){
                    // console.log(response);
                    Swal.fire({
                        title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                })
                .fail(function(){
                    Swal.fire(response.title, response.message, response.type);
                });
            });
        },
    })
});

function validateForm() {
    // Check if at least one checkbox is checked
    var checkboxes = document.getElementsByClassName('justone');
    var checked = false;
    
    for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            checked = true;
            break;
        }
    }
    
    if (!checked) {
        alert('Please select at least one employee to generate payroll.');
        return false;
    }
    
    return true;
}
	$('input[type=checkbox][class=cb-selector]').click(function() {
		var cb = $(this),
			name = cb.attr('id');
		if (name == null)
			return false;
		$('input[type=checkbox][id^=' + name + ']').prop('checked', cb.prop('checked')).click(function() {
				if (!$(this).prop('checked'))
					cb.prop('checked', false);
			});
	});
	$('.selectall').click(function() {
		if ($(this).is(':checked')) {
			$('input:checkbox').prop('checked', true);
		} else {
			$('input:checkbox').prop('checked', false);
		}
	});
	$("input[type='checkbox'].justone").change(function() {
		var a = $("input[type='checkbox'].justone");
		if (a.length == a.filter(":checked").length) {
			$('.selectall').prop('checked', true);
		} else {
			$('.selectall').prop('checked', false);
		}
	});
</script>