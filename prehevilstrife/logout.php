<?php
	session_start();
	 
	$_SESSION = array();
	session_destroy();
	
	echo "<script> alert('Logout sucessful!\\nMay we meet again, in another time.'); </script>";
	echo "<script> window.top.location.reload(); </script>";
	exit;
?>