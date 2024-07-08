<?php
	$servername = "localhost";
	$username = "userCommon";
	$password = "daanthefurry";
   	$dbname = "playerset";
   	$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);

	switch($_POST["id"]) {
		case '1':
			$sql = "UPDATE pendingturn SET p1 = (:aCode) WHERE battleId = 1;";
			break;
		default:
			$sql = "UPDATE pendingturn SET p2 = (:aCode) WHERE battleId = 1;";
			break;
	}

	if($stmt = $conn->prepare($sql)) {
	    $param_action = $_POST["action"];
	    $stmt->bindParam(":aCode", $param_action, PDO::PARAM_STR);
	    $stmt->execute();
	}

	$turn = $conn->query("SELECT p1, p2 FROM pendingturn WHERE battleId = 1")->fetch();
    if(!empty($turn[0]) && !empty($turn[1])) {
	    $conn->prepare("INSERT INTO testturn (action1, action2) SELECT p1, p2 FROM pendingturn WHERE battleId = 1; UPDATE pendingturn SET p1 = NULL, p2 = NULL WHERE battleId = 1;")->execute();
	}
?>