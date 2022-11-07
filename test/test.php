<html>
<?php
	if ($c=OCILogon("ora_hanzew25", "a39408695", "dbhost.students.cs.ubc.ca:1522/stu")) {
		echo "Successfully connected to Oracle.\n";
		OCILogoff($c);
	} else {
		$err = OCIError();
		echo "Oracle Connect Error " . $err['message'];
	}
?>
</html>
