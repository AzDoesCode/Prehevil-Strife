<?php
	session_start();
?>

<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="icon" href="media/img/logos/favicon.ico" type="image/ico">
		<link rel="stylesheet" href="styles/main/global.css">
		<script src="scripts/main/navigation.js"></script>
		<title>Prehevil Strife Beta 2.3</title>
		<script>
			var isLoggedIn = <?php echo ((array_key_exists('loggedin',$_SESSION)) || (!empty($_SESSION["loggedin"]))) ? $_SESSION["loggedin"] : 0; ?>;

			function toggleLogin() {
				if(isLoggedIn == 1){
					document.getElementById("login").classList.add("hidden");
					document.getElementById("account").classList.remove("hidden");
				}
				else {
					document.getElementById("login").classList.remove("hidden");
					document.getElementById("account").classList.add("hidden");
				}	
			}
		</script>
	</head>
	<body onload="toggleLogin()">
		<div id="menu">
			<img width="100" src="media/img/logos/prehevilstrife_logo.png"> Beta 2.3
			<div id="login" class="hidden">
				<button id="btn_login_menu" onclick="open_page('user_login.php')">Login</button>
			</div>
			<div id="account" class="hidden">
				<span id="user_name">
					<?php
						echo $_SESSION['username'];
					?>
					&nbsp &nbsp 
				</span>
				<button id="btn_account_menu" onclick="open_page('manage_account.php')">Account</button>
			</div>
		</div>
		<div id="divider"></div>
		<div id="page_holder">
			<iframe id="page_content" src="home.php"></iframe>	
		</div>
	</body>
</html>