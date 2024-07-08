<?php
    session_start();
     
    if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
        echo "<script> window.top.location.reload(); </script>";
        exit;
    }

    require_once "connect.php";
    $stmt = $conn->query("USE playerset");
 
    $username = $password = "";
    $username_err = $password_err = $login_err = "";
 
    if($_SERVER["REQUEST_METHOD"] == "POST") {

        if(empty(trim($_POST["username"]))) 
            $username_err = "Please enter username!";
        else
            $username = trim($_POST["username"]);

        if(empty(trim($_POST["password"])))
            $password_err = "Please enter your password!";
        else
            $password = trim($_POST["password"]);
        
        if(empty($username_err) && empty($password_err)) {
            $sql = "SELECT UserId, UserName, UserPassword FROM users WHERE UserName = :username";
            
            if($stmt = $conn->prepare($sql)) {
                $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
                
                $param_username = trim($_POST["username"]);
                
                if($stmt->execute()) {
                    if($stmt->rowCount() == 1) {
                        if($row = $stmt->fetch()) {
                            $id = $row["UserId"];
                            $username = $row["UserName"];
                            $hashed_password = $row["UserPassword"];
                            if(password_verify($password, $hashed_password)) {
                                
                                $_SESSION["loggedin"] = true;
                                $_SESSION["id"] = $id;
                                $_SESSION["username"] = $username;                            
                                
                                echo "<script> alert('Login sucessful!\\nWelcome back.'); </script>";
                                echo "<script> window.top.location.reload(); </script>";
                            } 
                            else {
                                $login_err = "Invalid username or password!";
                            }
                        }
                    }
                    else {
                        $login_err = "Invalid username or password!";
                    }
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
        <title>Login</title>
        <link rel="stylesheet" href="styles/auth_pages/form.css">
    </head>
    <body>
        <div class="breaker_line"></div>
            <h1>&nbsp The Registry</h1>
        <div class="breaker_line"></div>
        <div id="form_page">
            <br>
            &nbsp Welcome back to the Festival!<br>
            &nbsp &nbsp Please introduce your login info.

            <?php 
                if(!empty($login_err)) {
                    echo '<br><br><div class="warning"> WARNING: ' . $login_err . '</div>';
                }        
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <br>
                <div class="field">
                    &nbsp <img src="media/img/ui/icons/hexen_soul.png"> Username:
                    &nbsp <input type="text" name="username" id="username">
                    <span class="error"><?php echo "&nbsp".$username_err; ?></span>
                </div>    
                <div class="field">
                    &nbsp <img src="media/img/ui/icons/hexen_soul.png"> Password:&nbsp
                    &nbsp <input type="password" name="password" id="password">
                    <span class="error"><?php echo "&nbsp".$password_err; ?></span>
                </div>
                <br>
                <div class="sub_btn">
                    <input type="submit" value="Login">
                </div>
            </form>

            <br><br>
            &nbsp Don't have a <b>Prehevil Strife</b> account?
            <br><br>
            &nbsp <img src="media/img/ui/icons/hexen_soul.png"> <button id="btn_signup" onclick="parent.open_page('user_signup.php')">Create one now!</button>
            <br><br>
            &nbsp <img src="media/img/ui/icons/hexen_soul.png"> <button id="btn_return" onclick="parent.open_page('home.php')">Main Menu</button>
        </div>
    </body>
</html>