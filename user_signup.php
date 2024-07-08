<?php
	require_once "connect.php";
	$stmt = $conn->query("USE playerset");

	$username = $password = $confirm_password = "";
	$username_err = $password_err = $confirm_password_err = "";
	 
	if($_SERVER["REQUEST_METHOD"] == "POST") {

	    if(empty(trim($_POST["username"]))) {
	        $username_err = "Please enter a valid username!";
	    }
	    elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
	        $username_err = "Usernames can only contain letters, numbers, and underscores.";
	    }
	    else {
	        $sql = "SELECT UserId FROM users WHERE UserName = :username";
	        
	        if($stmt = $conn->prepare($sql)) {
	            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
	            
	            $param_username = trim($_POST["username"]);
	            
	            if($stmt->execute()) {
	                if($stmt->rowCount() == 1) {
	                    $username_err = "The name ".$username." is already taken!";
	                } else{
	                    $username = trim($_POST["username"]);
	                }
	            }
	            else {
	                echo "<script> alert('Oops! Something went wrong. Please try again later.'); </script>";
	            }

	            unset($stmt);
	        }
	    }
	    
	    if(empty(trim($_POST["password"]))) {
	        $password_err = "Please enter a valid password!";     
	    }
	    elseif(strlen(trim($_POST["password"])) < 6) {
	        $password_err = "Passwords must contain atleast 6 characters.";
	    }
	    else{
	        $password = trim($_POST["password"]);
	    }
	    
	    if(empty(trim($_POST["confirm_password"]))) {
	        $confirm_password_err = "Please confirm your password!";     
	    }
	    else {
	        $confirm_password = trim($_POST["confirm_password"]);
	        if(empty($password_err) && ($password != $confirm_password)){
	            $confirm_password_err = "Passwords did not match!";
	        }
	    }
	    
	    if(empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
	        
	        $sql = "INSERT INTO users (UserName, UserPassword) VALUES (:username, :password)";
	         
	        if($stmt = $conn->prepare($sql)) {
	            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
	            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
	            
	            $param_username = $username;
	            $param_password = password_hash($password, PASSWORD_DEFAULT);
	            
	            if($stmt->execute()) {
	            	echo "<script> alert('Account created sucessfully!'); </script>";
	                echo "<script> parent.open_page('home.php'); </script>";
	            }
	            else {
	                echo "<script> alert('Oops! Something went wrong. Please try again later.'); </script>";
	            }

	            unset($stmt);
	        }
	    }

	    unset($conn);
	}
?>
<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
	    <title>Signup</title>
	    <link rel="stylesheet" href="styles/auth_pages/form.css">
	</head>
	<body>
	    <div class="breaker_line"></div>
            <h1>&nbsp The Registry</h1>
        <div class="breaker_line"></div>
        <div id="form_page">
	        <br>
            &nbsp A new Contestant aproaches the Festival?<br>
            &nbsp &nbsp Signup now!

	        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
	            <br>
                <div class="field">
	                &nbsp <img src="media/img/ui/icons/hexen_soul.png"> Username:&nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp 
	                &nbsp <input type="text" name="username" id="username">
	                <span class="error"><?php echo $username_err; ?></span>
	            </div>    
	            <div class="field">
	                &nbsp <img src="media/img/ui/icons/hexen_soul.png"> Password:&nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp &nbsp&nbsp
	                &nbsp <input type="password" name="password" id="password">
	                <span class="error"><?php echo $password_err; ?></span>
	            </div>
	            <div class="field">
	                &nbsp <img src="media/img/ui/icons/hexen_soul.png"> Confirm Password:
	                &nbsp <input type="password" name="confirm_password" id="confirm_password">
	                <span class="error"><?php echo $confirm_password_err; ?></span>
	            </div>
	            <br>
                <div class="sub_btn">
                    <input type="submit" value="Create Account">
                </div>
            </form>

            <br><br>
            &nbsp Already a Contestant in the <b>Festival of Termina</b>?
            <br><br>
	        &nbsp <img src="media/img/ui/icons/hexen_soul.png"> <button id="btn_login" onclick="parent.open_page('user_login.php')">Login now!</button>
	        <br><br>
	        &nbsp <img src="media/img/ui/icons/hexen_soul.png"> <button id="btn_return" onclick="parent.open_page('home.php')">Main Menu</button>   
	    </div>    
	</body>
</html>