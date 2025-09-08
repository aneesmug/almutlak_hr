<table border="1">
	<tr>
		<th>File Name</th>
	</tr>
<?php
$filepath = opendir('./soft');
if ($handle = $filepath) {

    while (false !== ($entry = readdir($handle))) {

        if ($entry != "." && $entry != "..") {

            echo "<tr>
				<td><a href='./soft/$entry'>$entry</a></td>
			</tr>";
        }
    }

    closedir($handle);
}

?>
</table>

