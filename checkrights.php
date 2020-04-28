<?
	ini_set("session.gc_maxlifetime",10);
	session_start();

	// Функция обрабатывает строки перед сохранением в БД
	function convert_str($src) {
		$src = trim($src);
		$src = str_replace('\\', '/', $src);
		return $src;
	}

	// Проверяем, активирована ли сессия
	if( empty($_SESSION['id']) ) {
		if( !strpos($_SERVER["REQUEST_URI"], 'login.php') ) {
			if( $_GET["ajax"] == 1 ) {
				echo "noty({timeout: 3000, text: 'Вы не авторизованы! Пожалуйста, перезагрузите страницу.', type: 'error'});";
			}
			else {
				exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
			}
			die;
		}
	}
	else {
		// Узнаем город, роль и иконку пользователя
		$query = "
			SELECT IFNULL(USR.CT_ID, 0) CT_ID
				,USR.RL_ID
				,USR_Icon(USR.USR_ID) USR_Icon
				,CT.timezone
			FROM Users USR
			LEFT JOIN Cities CT ON CT.CT_ID = USR.CT_ID
			WHERE USR_ID = {$_SESSION['id']}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$USR_City = mysqli_result($res,0,'CT_ID');
		$USR_Role = mysqli_result($res,0,'RL_ID');
		$USR_Icon = mysqli_result($res,0,'USR_Icon');
		$timezone = mysqli_result($res,0,'timezone');

		// Устанавливаем часовой пояс
		date_default_timezone_set($timezone);
		mysqli_query($mysqli, "SET `time_zone`='".date('P')."'");

		// Получаем права пользователя
		$query = "SELECT RT_ID FROM Role_Rights WHERE RL_ID = {$USR_Role}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$Rights[] = $row["RT_ID"];
		}

		// Если в сверках разрешение для оптовика - сохраняем ID контрагента
		if( in_array('sverki_opt', $Rights) ) {
			$query = "SELECT KA_ID FROM Users WHERE USR_ID = {$_SESSION['id']}";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$USR_KA = mysqli_result($res,0,'KA_ID');
		}

		// Если в реализации или сверках доступен только город и у пользователя указан салон, то сохраняем салон.
		if( in_array('selling_city', $Rights) or in_array('sverki_city', $Rights) ) {
			$query = "SELECT SH_ID FROM Users WHERE USR_ID = {$_SESSION['id']}";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$USR_Shop = mysqli_result($res,0,'SH_ID');
		}

		// Получаем список доступных пользователю городов или салонов чтобы видеть наборы
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

		// Получаем список подчиненных работников из дерева
		$USR_tree = "{$_SESSION['id']}";
		$query = "SELECT USR_tree({$_SESSION['id']}) array";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		if( $row["array"] ) $USR_tree .= ','.$row["array"];
	}
?>
