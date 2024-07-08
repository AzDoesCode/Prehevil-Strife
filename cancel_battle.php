<?php
	$servername = "localhost";
	$username = "userCommon";
	$password = "daanthefurry";
   	$dbname = "playerset";
   	$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

   	session_start();

    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
        echo "<script> alert('You\'re not logged in!\\nLogin from the Homepage to access the Online Multiplayer.'); </script>";
        echo "<script> parent.open_page('home.php') </script>";
        exit;
    }

    $param_uid = $_SESSION["id"];
    if($stmt = $conn->prepare("DELETE FROM Battles WHERE P1Id = :uid OR P2Id = :uid")) {
		$stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
	    $stmt->execute();
	}

    if($stmt = $conn->prepare("DELETE FROM Lobbies WHERE P1Id = :uid OR P2Id = :uid")) {
		$stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
	    $stmt->execute();
	}
?>