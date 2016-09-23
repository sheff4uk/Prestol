<?
	ini_set("session.gc_maxlifetime",10);
	session_start();
	// Проверяем, пусты ли переменные логина и id пользователя
	if (empty($_SESSION['login']) or empty($_SESSION['id'])) {
		if( !strpos($_SERVER["REQUEST_URI"], 'login.php') and !strpos($_SERVER["REQUEST_URI"], 'reg.php') and !strpos($_SERVER["REQUEST_URI"], 'save_user.php') and !strpos($_SERVER["REQUEST_URI"], 'mailconfirm.php') and !strpos($_SERVER["REQUEST_URI"], 'activation.php') ) {
			$location = $_SERVER['REQUEST_URI'];
			header('Location: login.php?location='.$location);
		}
	}
	else {
		// Узнаем город и роль пользователя
		$query = "SELECT CT_ID, RL_ID FROM Users WHERE USR_ID = {$_SESSION['id']}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$USR_City = mysqli_result($res,0,'CT_ID');
		$USR_Role = mysqli_result($res,0,'RL_ID');

		// Получаем права пользователя
		$query = "SELECT RT_ID FROM Role_Rights WHERE RL_ID = {$USR_Role}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$Rights[] = $row["RT_ID"];
		}

		// Получаем список доступных пользователю городов
		$USR_cities = '0';
		if( in_array('order_view_city', $Rights) ) {
			$query = "SELECT CT_ID FROM Cities WHERE CT_ID = {$USR_City}";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				$USR_cities .= ','.$row["CT_ID"];
			}
		}
		elseif( in_array('order_view', $Rights) ) {
			$query = "SELECT CT_ID FROM Cities";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				$USR_cities .= ','.$row["CT_ID"];
			}
		}
	}
?>