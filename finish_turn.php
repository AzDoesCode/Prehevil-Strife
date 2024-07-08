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

    $p_turn = $_POST["turn"];
    $player = $_POST["side"];
    $actions = $_POST["actions"];
    $param_lid = $_POST["lobby"];
    $param_uid = $_SESSION["id"];

    if($stmt = $conn->prepare("SELECT MAX(TurnId) FROM Battles WHERE LobbyId = :lid;")) {
        $stmt->bindParam(":lid", $param_lid, PDO::PARAM_STR);
        if($stmt->execute()) $turn = $stmt->fetch();
    }

    if($p_turn != $turn[0]) {
    	switch($player) {
    		case 0:
		    	if($stmt = $conn->prepare("UPDATE Battles SET P1Id = :uid, P1Action = :act WHERE TurnId = :trn")) {
				    $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
				    $stmt->bindParam(":act", $actions, PDO::PARAM_STR);
				    $stmt->bindParam(":trn", $turn[0], PDO::PARAM_STR);
				    $stmt->execute();
				}
	    		break;

	    	default:
	    		if($stmt = $conn->prepare("UPDATE Battles SET P2Id = :uid, P2Action = :act WHERE TurnId = :trn")) {
				    $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
				    $stmt->bindParam(":act", $actions, PDO::PARAM_STR);
				    $stmt->bindParam(":trn", $turn[0], PDO::PARAM_STR);
				    $stmt->execute();
				}
	    		break;
		}
    }
    else {
    	switch($player) {
    		case 0:
		    	if($stmt = $conn->prepare("INSERT INTO Battles (LobbyId, P1Id, P1Action) VALUES (:lid, :uid, :act)")) {
					$stmt->bindParam(":lid", $param_lid, PDO::PARAM_STR);
					$stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
					$stmt->bindParam(":act", $actions, PDO::PARAM_STR);
				    $stmt->execute();
	    		}
	    		break;

	    	default:
	    		if($stmt = $conn->prepare("INSERT INTO Battles (LobbyId, P2Id, P2Action) VALUES (:lid, :uid, :act)")) {
					$stmt->bindParam(":lid", $param_lid, PDO::PARAM_STR);
					$stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
					$stmt->bindParam(":act", $actions, PDO::PARAM_STR);
				    $stmt->execute();
	    		}
	    		break;
		}
    }

    if($stmt = $conn->prepare("SELECT MAX(TurnId) FROM Battles WHERE LobbyId = :lid;")) {
        $stmt->bindParam(":lid", $param_lid, PDO::PARAM_STR);
        if($stmt->execute()) $turn = $stmt->fetch();
    }
    echo json_encode($turn[0]);
?>