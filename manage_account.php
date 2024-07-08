<?php
    session_start();
     
    if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] === false) {
        echo "<script> alert('You\'re not logged in!\\nLogin from the Homepage to manage your Account.'); </script>";
        echo "<script> parent.open_page('home.php') </script>";
        exit;
    }

    require_once "connect.php";
    $stmt = $conn->query("USE playerset");

    if(!empty($_POST)) {
    	$sql = "UPDATE Users SET UserPfp = :pfp WHERE UserId = :id";

    	if($stmt = $conn->prepare($sql)) {
	    	$param_pfp = $_POST["pfpSelect"];
	    	$param_id = $_SESSION["id"];

	    	$stmt->bindParam(":pfp", $param_pfp, PDO::PARAM_STR);
		    $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);

		    if($stmt->execute()) {
		        echo "<script> alert('Avatar changed sucessfully!'); </script>";
		        echo "<script> parent.open_page('manage_account.php'); </script>";
		    }
		    else {
		        echo "<script> alert('Oops! Something went wrong. Please try again later.'); </script>";
		    }
		    
		    unset($stmt);
	    }
	}

    $sql = "SELECT UserName, UserPfp, UserDate FROM Users WHERE UserId = :id";

    if($stmt = $conn->prepare($sql)) {
        $stmt->bindParam(":id", $param_id, PDO::PARAM_STR);
        
        $param_id = $_SESSION["id"];

        if($stmt->execute()) {
            if($stmt->rowCount() == 1) {
                if($row = $stmt->fetch()) {
                    $username = $row["UserName"];
                    $pfp = $row["UserPfp"];
                    $date = $row["UserDate"];
                }
            }
        }
    }

    $stmt = $conn->query("USE dataset");

    $stmt = $conn->prepare("SELECT * FROM Pfps");
    $stmt->execute();
    $pfps = $stmt->fetchAll();


?>
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
	    <title>Manage Account</title>
	    <link rel="stylesheet" href="styles/auth_pages/form.css">
	    <script>
	    	const publicPfpCount = 9;
	    	var pfpId = <?php echo json_encode($pfp); ?>;
	    	var pfpList = <?php echo json_encode($pfps); ?>;
	    	var pfpSearchId = 0;
	    	var listState = false;

	    	function loadPfp() {
	    		for(var i=0; i < pfpList.length; i++) {
	    			if(pfpList[i][0] == pfpId) pfpSearchId = i;
	    		}

	    		document.getElementById("currentPfp").src = pfpList[pfpSearchId][1];
	    		document.getElementById("pfpSelect").value = pfpList[pfpSearchId][0];
	    	}

	    	function createPfpSelect() {
                var holder = document.getElementById('pfpSelectHolder');

                for (var i = 0; i < publicPfpCount; i++) {
                    var button = document.createElement("button");
                    button.type = "button";
                    button.value = pfpList[i][0];
                    button.onclick = function() {changePfp(this.value); highlightButton(this);};
                    button.style = "height: 350px; width: 350px;";
                    button.innerHTML = "<img src='" + pfpList[i][1] +"' style=\"max-height: 300px; max-width: 300px;\">";

                    holder.appendChild(button);
                }
            }

            function changePfp(newPfpId) {
            	document.getElementById("pfpSelect").value = newPfpId;
            }

            function togglePfpList() {
            	if(!listState) {
            		document.getElementById("pfpSelectHolder").classList.remove("hidden");
            		document.getElementById("btn_toggle_pfp").innerHTML = "Hide Avatars";
            		listState = true;
            	}
            	else {
            		document.getElementById("pfpSelectHolder").classList.add("hidden");
            		document.getElementById("btn_toggle_pfp").innerHTML = "Show Avatars";
            		listState = false;
            	}
            }

            function highlightButton(button) {
				var buttonList = document.getElementsByClassName('highlight');
				if(buttonList[0] != null) buttonList[0].classList.remove("highlight");
				button.classList.add("highlight");
			}
	    </script>
	</head>
	<body>
	    <div class="breaker_line"></div>
            <h1>&nbsp The Registry</h1>
        <div class="breaker_line"></div>
        <div id="form_page">
	        <br>
            &nbsp Manage your Account.<br> 

	        <br>
	        &nbsp <img src="media/img/ui/icons/hexen_soul.png"> Username:&nbsp 
	        &nbsp <span class="data"><?php echo "'".$username."'"; ?></span><br>

	        <br>
	        &nbsp <img src="media/img/ui/icons/hexen_soul.png"> Avatar:&nbsp <br>
	        &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp <img id="currentPfp" style="max-height: 300px; max-width: 300px;" src="https://i.postimg.cc/25CbGzTs/pfp-none.png"><br>

            <br>
	        &nbsp <img src="media/img/ui/icons/hexen_soul.png"> Created:&nbsp 
	        &nbsp <span class="data"><?php echo "'".$date."'"; ?></span><br>
	        <br><br><br>

	        &nbsp Want a different Avatar?<br>
	        <br>
	        &nbsp &nbsp <button id="btn_toggle_pfp" onclick="togglePfpList()">Show Avatars</button>
	        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
	            <input type="hidden" id="pfpSelect" name="pfpSelect" value="0" />
	            <br>
	            <div id="pfpSelectHolder" class="hidden">
	            	<script> createPfpSelect(); </script>
	            	<br><br>
	            	<div class="sub_btn">
                    	<input type="submit" value="Change Avatar">
                	</div>
                	<br>
	            </div>
            </form>

	        &nbsp <img src="media/img/ui/icons/hexen_soul.png"> <button id="btn_logout" onclick="parent.open_page('logout.php')">Logout</button>
	        <br><br>
	        &nbsp <img src="media/img/ui/icons/hexen_soul.png"> <button id="btn_return" onclick="parent.open_page('home.php')">Main Menu</button>
	    </div>    
	</body>
	<script>
        loadPfp();
    </script>
</html>