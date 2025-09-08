<div class="btn-group">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><i class="entypo-mail"></i>
         <?php
			$sql=mysqli_query($conDB, "SELECT * FROM `contact_us` WHERE `read`='No'");
				while($row = mysqli_fetch_array($sql)){
					$read_stus = $row['read'];
			}
			if($read_stus == "No"){
				echo "<span class='new'></span>";
				}
			$sql_unread_msg = mysqli_query($conDB, "SELECT COUNT(*) FROM `contact_us` WHERE `read`='No'");
		$unread_msg = mysqli_fetch_array($sql_unread_msg)[0];
		?>        
        </button>
        <div id="inbox-dropdown" class="dropdown-menu inbox">
          <div class="dropdown-header">Inbox <span class="badge pull-right"><?php echo $unread_msg ?> </span></div>
          <div class="dropdown-container">
            <div class="nano">
              <div class="nano-content">
                <ul class="inbox-dropdown">
                  <?php
                  $sql=mysqli_query($conDB, "SELECT * FROM `contact_us` ORDER BY `id` DESC LIMIT 0 , 10");
					while($row = mysqli_fetch_array($sql)){
						
						$id_st = $row['id'];
						$name_st = $row['name'];
						$email_st = $row['email'];
						$message_st = $row['message'];
						$date_st = $row['date'];
						$read_st = $row['read'];
						
						$message_st = substr($message_st,0,40).'...';
						$date_st_d = date('d', strtotime($date_st));
						$date_st_m = date('M', strtotime($date_st));
						$date_st_t = date('h:i', strtotime($date_st));
						
						if($read_st == "No"){
							$message_st = "<strong style='color:#00A899'>$message_st</strong>";
							}
						
				  ?>
                  <li><a href="message_open.php?tpages=1&page=1&inbox_id=<?php echo $id_st ?>&inbox_email=<?php echo $email_st ?>"> <span class="user-image"><img src="images/logo.png" alt="Gluck Dorris" /></span>
                    <h5><?php echo $name_st ?></h5>
                    <p><?php echo $message_st ?></p>
                    <span class="label label-default"><i class="entypo-clock"></i> <?php echo $date_st_d." ".$date_st_m." ".$date_st_t ?></span> <span class="delete"><i class="entypo-back"></i></span> </a>
                    </li>
                  <?php } ?>
                </ul>
              </div>
            </div>
          </div>
          <div class="dropdown-footer"><a class="btn btn-dark" href="contactus.php?tpages=1&page=1">Show All</a></div>
        </div>
      </div>