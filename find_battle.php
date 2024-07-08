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

    $hasGame = 0;
    $hasLobby = null;
    $isHosting = 0;
    $team = $_POST["team"];
    $param_format = $_POST["format"];
    $param_uid = $_SESSION["id"];

    if($stmt = $conn->prepare("SELECT COUNT(1) FROM Lobbies WHERE (P1Id = :uid AND P2Id IS NOT NULL) OR (P2Id = :uid) AND Format = :ufor;")) {
        $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
        $stmt->bindParam(":ufor", $param_format, PDO::PARAM_STR);
        if($stmt->execute()) $hasGame = $stmt->fetch();
    }

	if($hasGame[0] == 1) {
		echo json_encode(1);
	}
	else {
		if($stmt = $conn->prepare("SELECT LobbyId FROM Lobbies WHERE P1Id != :uid AND P2Id IS NULL AND Format = :ufor LIMIT 1;")) {
		    $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
		    $stmt->bindParam(":ufor", $param_format, PDO::PARAM_STR);
		    if($stmt->execute()) $hasLobby = $stmt->fetch();
		}
		if(isset($hasLobby[0])) {
			if($stmt = $conn->prepare("DELETE FROM Lobbies WHERE P1Id = :uid")) {
				$stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
		        $stmt->execute();
	    	}

			if($stmt = $conn->prepare("UPDATE Lobbies SET P2Id = :uid, P2Team = :uteam WHERE LobbyId = :lid")) {
			    $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
			    $stmt->bindParam(":uteam", $team, PDO::PARAM_STR);
			    $stmt->bindParam(":lid", $hasLobby[0], PDO::PARAM_STR);
			    $stmt->execute();
			}
			echo json_encode(2);
		}
		else {
			if($stmt = $conn->prepare("SELECT COUNT(1) FROM Lobbies WHERE P1Id = :uid AND P2Id IS NULL AND Format = :ufor;")) {
		        $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
		        $stmt->bindParam(":ufor", $param_format, PDO::PARAM_STR);
		        if($stmt->execute()) $isHosting = $stmt->fetch();
	    	}
	        if($isHosting[0] == 0 ) {
	        	if($stmt = $conn->prepare("INSERT INTO Lobbies(P1Id, P1Team, Format) VALUES (:uid,:uteam,:ufor);")) {
	        	    $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
	        	    $stmt->bindParam(":uteam", $team, PDO::PARAM_STR);
	        	    $stmt->bindParam(":ufor", $param_format, PDO::PARAM_STR);
	        	    if($stmt->execute());
	        	}
	        	echo json_encode(-1);
	        }
	        else{
	        	echo json_encode(0);
	        }
		}
	}
?>