<div class="row">
<?php
	$sqlsup = "SELECT * FROM `employees` WHERE `dept` = '".$dept_get."' AND `emp_id`<>'".$emp_id_get."' AND `status`=1 ";
	$querysup = mysqli_query($conDB, $sqlsup);

	while ($rec = mysqli_fetch_array($querysup)) {
		$id = $rec["id"];
		$name = $rec["name"];
		$emp_id = $rec["emp_id"];
		$iqama = $rec["iqama"];
		$mobile = $rec["mobile"];
		$salary = $rec["salary"];
		$vacation_days = $rec["vacation_days"];
		$joining_date = $rec["joining_date"];
		$date_reg = $rec["date_reg"];
		$emp_avatar = $rec["avatar"];
		$emp_status = $rec["status"];
		$emp_status_fly = $rec["fly"];
		$emptype = $rec["emptype"];
		
		$sql_count = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id."' ");
		$status_cont = mysqli_fetch_array($sql_count)[0];
		
		$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id."' && `note`='Fly' ");
		$cont_fly = mysqli_fetch_array($sql_count_fly)[0];
		
		$sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id."' && `note`='Encashed' ");
		$cont_encashed = mysqli_fetch_array($sql_count_encashed)[0];
		
?>
		<div class="col-lg-3">			
			<div class="text-center card-box <?php if($emp_status == "active" AND $emp_status_fly == "no"){echo "bg-light";}elseif($emp_status_fly == "yes"){echo "bg-warning";}else{echo "bg-danger";} ?>">
				

				<div class="member-card pt-2 pb-2">
					<div class="thumb-lg member-thumb m-b-10 mx-auto">
						<img src="<?php echo $emp_avatar ?>" class="emp_avat_img empfil" alt="profile-image">
					</div>

					<div class=""><br>
						<h4 class="m-b-5"><?php echo $name ?></h4>
					</div>
					<div class="btn-group" role="group" aria-label="Edit Button">
					<a href="view_employee.php?id=<?php echo $id ?>" class="btn btn-primary m-t-20 btn-rounded waves-effect w-md waves-light btn-sm"><i class="mdi mdi-account-search"></i> View Details</a>
					</div><br>
					<span class="badge badge-dark badge-pill">Fly: <?php echo $cont_fly ?> | Encashed: <?php echo $cont_encashed ?></span>

					<div class="mt-4">
						<div class="row">
							<div class="col-4 text-left">
								<div class="mt-3">
									<h4 class="m-b-5"><?php echo $emp_id ?></h4>
									<p class="mb-0">Emp. ID</p>
								</div>
							</div>
							
							<div class="col-4">
								<div class="mt-3">
									<?php if($emptype == "Manager"){?>
										<h5 class="m-b-5"><?php echo $emptype; ?></h5>
<!--									<p class="mb-0 text-muted">Vac. No.</p>-->
									<?php } ?>
								</div>
							</div>
							
							<div class="col-4 text-right">
								<div class="mt-3">
									<h5 class="m-b-5"><?php echo $iqama ?></h5>
									<p class="mb-0">Iqama / ID</p>
								</div>
							</div>
						</div>
					</div>

				</div>

			</div>

		</div> <!-- end col -->

<div class="modal fade del_modal_sm_<?php echo $id ?>" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" style="display: none;">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header" style="background-color: brown !important; color: #fff !important;">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h4 class="modal-title" id="mySmallModalLabel">
					<i class="mdi mdi-delete-circle"></i> 
					Are you sure!
				</h4>
			</div>
			<div class="modal-body">
				<h3>You need to delete!</h3>
				<h4><strong style="font-size: 30px; "><?php echo $name ?></strong> Employee</h4>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light waves-effect" data-dismiss="modal">Close</button>
				<a href="./includes/delete_emp.php?id=<?php echo $id ?>" class="btn btn-danger waves-effect waves-light"><i class="icon-close"></i> Delete</a>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
 <?php } ?>						
</div>