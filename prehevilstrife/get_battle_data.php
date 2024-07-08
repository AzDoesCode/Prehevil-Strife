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

    $player = 0;
    $p1Team = null;
    $p2Team = null;
    $param_uid = $_SESSION["id"];

    if($stmt = $conn->prepare("SELECT COUNT(1) FROM Lobbies WHERE P2Id = :uid;")) {
        $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
        if($stmt->execute()) $player = $stmt->fetch();
    }

    if($stmt = $conn->prepare("SELECT * FROM Lobbies WHERE P1Id = :uid OR P2Id = :uid;")) {
        $stmt->bindParam(":uid", $param_uid, PDO::PARAM_STR);
        if($stmt->execute()) $teams = $stmt->fetch();
        $lobbyId = $teams[0]; 
        $p1Id = $teams[1];
        $p2Id = $teams[2];
        $p1Team = $teams[3];
        $p2Team = $teams[4];
    }

    if($stmt = $conn->prepare("SELECT COUNT(1) FROM Battles WHERE LobbyId = :lid;")) {
        $stmt->bindParam(":lid", $lobbyId, PDO::PARAM_STR);
        if($stmt->execute()) $firstTurn = $stmt->fetch();
    }

    if($firstTurn[0] == 0) {
        if($stmt = $conn->prepare("INSERT INTO Battles (LobbyId, P1Id, P2Id, P1Action, P2Action) VALUES (:lid, :u1id, :u2id, NULL, NULL)")) {
            $stmt->bindParam(":lid", $lobbyId, PDO::PARAM_STR);
            $stmt->bindParam(":u1id", $p1Id, PDO::PARAM_STR);
            $stmt->bindParam(":u2id", $p2Id, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    $data = array($player[0], $lobbyId, $p1Id, $p2Id, $p1Team, $p2Team);
    echo json_encode($data);
?>