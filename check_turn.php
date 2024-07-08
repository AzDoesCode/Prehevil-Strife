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

    $turn = $_POST["turn"];

    if($stmt = $conn->prepare("SELECT COUNT(1) FROM Battles WHERE TurnId = :trn AND P1Action IS NOT NULL AND P2Action IS NOT NULL;")) {
        $stmt->bindParam(":trn", $turn, PDO::PARAM_STR);
        if($stmt->execute()) $isReady = $stmt->fetch();
    }

    if($isReady[0] == 1) {
    	if($stmt = $conn->prepare("SELECT P1Action, P2Action FROM Battles WHERE TurnId = :trn;")) {
	        $stmt->bindParam(":trn", $turn, PDO::PARAM_STR);
	        if($stmt->execute()) $actions = $stmt->fetch();
	    }
	    $data = array(1, $actions[0], $actions[1]);
	    echo json_encode($data);
    }
    else {
    	$data = array(0, NULL, NULL);
    	echo json_encode($data);
    }
?>