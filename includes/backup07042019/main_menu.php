<div class="user-box">
	<div class="user-img">
		<img src="assets/images/users/avatar-1.jpg" alt="user-img" title="Mat Helme" class="rounded-circle img-fluid">
	</div>
	<h5><a href="javascript:voif(0);"><?php echo $userwel ?></a> </h5>
	<p class="text-muted"><?php echo $usracc ?></p>
</div>

<div id="sidebar-menu">

	<ul class="metismenu" id="side-menu">

		<li class="menu-title">Navigation</li>


		<li>
			<a href="dashboard.php" class="<?php echo dashboard($current_page) ?>" >
				<i class="fi-air-play"></i>
				<span>Dashboard</span>
			</a>
		</li>
		<li>
			<a href="reg_employee.php" class="<?php echo reg_employee($current_page) ?>" >
				<i class="mdi mdi-account-multiple"></i>
				<span>All Employees</span>
			</a>
		</li>
		<li>
			<a href="new_employee.php" class="<?php echo new_employee($current_page) ?>" >
				<i class="mdi mdi-account-plus"></i>
				<span>New Employee</span>
			</a>
		</li>
		<li>
			<a href="all_cars.php" class="<?php echo all_cars($current_page) ?>" >
				<i class="mdi mdi-car-wash"></i>
				<span>All Cars</span>
			</a>
		</li>
		<li>
			<a href="add_car.php" class="<?php echo add_car($current_page) ?>" >
				<i class="mdi mdi-car-sports"></i>
				<span>Add New Car</span>
			</a>
		</li>
		<?php if($user_type == $access1){ ?>
		<li>
			<a href="log_activity.php" class="<?php echo log_activity($current_page) ?>" >
				<i class="mdi mdi-tumblr-reblog"></i>
				<span>Log Activity</span>
			</a>
		</li>
		<?php } ?>

	</ul>

</div>