<?php

if(isset($_POST['submit'])){

	$counter = $_POST['test'];

	if(!empty($_POST['test'])){
		for($x=0;$x<count($counter);$x++){
		echo $counter[$x]."<br>";
	}
//for ($i = 1; $i <= 10; $i++) {
//    echo $i;
//}
	}else{
		echo "Please ";
	}
//$required = $_POST['test'];
//foreach ($required as $req) {
//   $req = trim($req);
//   if (empty($req))
//      echo 'gotcha!';
//}

}

$dd = 	array(5,10,15,20,25,30);
echo "<pre>";
print_r($dd);
echo "</pre>";

?>

<!--
<form action="" method="post">
	Name: <input type='text' name='test[]' /><br>
	Name: <input type='text' name='test[]' /><br><br>
	<input type="submit" value="Submit" name="submit" >
</form>-->
