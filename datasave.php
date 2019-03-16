<?
session_start();
include "config.php";
include "header.php";

// Обновление параметров изделия
if ($_GET["oddid"] and isset($_POST["Amount"])) {
	if (!in_array('order_add', $Rights)) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	// Узнаем возможен ли ящик для этой модели с таким механизмом
	if ($_POST["Mechanism"] and $_POST["Model"]) {
		$query = "
			SELECT box
			FROM ProductModelsMechanism
			WHERE PM_ID = {$_POST["Model"]} AND PME_ID = {$_POST["Mechanism"]}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$box_aval = mysqli_result($res,0,'box');
	}

	// Обновляем информацию об изделии
	if (isset($_POST["Blanks"]) or isset($_POST["Other"])) {
		$Blank = $_POST["Blanks"] ? "{$_POST["Blanks"]}" : "NULL";
		$Other = convert_str($_POST["Other"]);
		$Other = mysqli_real_escape_string($mysqli, $Other);
		$Other = ($Other != '') ? "'$Other'" : "NULL";
	}
	else {
		$Blank = "NULL";
		$Other = "NULL";
	}
	$Model = $_POST["Model"] ? "{$_POST["Model"]}" : "NULL";
	$Form = $_POST["Form"] ? "{$_POST["Form"]}" : "NULL";
	$Mechanism = $_POST["Mechanism"] ? "{$_POST["Mechanism"]}" : "NULL";
	$box = ($box_aval == 1 and $_POST["box"] == 1) ? 1 : 0;
	$Length = $_POST["Type"] == 2 ? "{$_POST["Length"]}" : "NULL";
	$Width = $_POST["Width"] ? "{$_POST["Width"]}" : "NULL";
	$PieceAmount = $_POST["PieceAmount"] ? "{$_POST["PieceAmount"]}" : "NULL";
	$PieceSize = $_POST["PieceSize"] ? "{$_POST["PieceSize"]}" : "NULL";
	$piece_stored = $_POST["piece_stored"] ? "{$_POST["piece_stored"]}" : "NULL";
	$IsExist = $_POST["IsExist"];
	$Shipper = $_POST["Shipper"] ? $_POST["Shipper"] : "NULL";
	$ptn = $_POST["ptn"];
	$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
	$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";
	// Обработка строк
	$Material = convert_str($_POST["Material"]);
	$Material = mysqli_real_escape_string($mysqli, $Material);
	$edge = convert_str($_POST["edge"]);
	$edge = mysqli_real_escape_string($mysqli, $edge);
	$Comment = convert_str($_POST["Comment"]);
	$Comment = mysqli_real_escape_string($mysqli, $Comment);
	$edge = ($edge != '') ? "'$edge'" : "NULL";
	$Comment = ($Comment != '') ? "'$Comment'" : "NULL";

	// Узнаем прошлого поставщика и ID заказа
	$query = "
		SELECT ODD.OD_ID
			,IFNULL(MT.SH_ID, 'NULL') SH_ID
			,ODD.Price - IFNULL(ODD.discount, 0) price
		FROM OrdersDataDetail ODD
		LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		WHERE ODD.ODD_ID = {$_GET["oddid"]}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$old_Shipper = $row["SH_ID"];
	$od_id = $row["OD_ID"];
	$price = $row["price"];

	// Сохраняем в таблицу материалов полученный материал и узнаем его ID
	if ($Material != '') {
		$Material = mysqli_real_escape_string($mysqli, $Material);
		$query = "
			SELECT MT_ID FROM Materials WHERE Material LIKE '{$Material}' AND SH_ID = {$Shipper}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		if ($row["MT_ID"]) {
			$mt_id = $row["MT_ID"];
		}
		else {
			$query = "
				INSERT INTO Materials SET Material = '{$Material}', SH_ID = {$Shipper}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$mt_id = mysqli_insert_id( $mysqli );
		}
	}
	else {
		$mt_id = "NULL";
	}

	// Обновляем записи не влияющие на цену
	$query = "
		UPDATE OrdersDataDetail
		SET BL_ID = {$Blank}
			,Other = {$Other}
			,edge = {$edge}
			,piece_stored = {$piece_stored}
			,MT_ID = {$mt_id}
			,IsExist = ".( isset($_POST["IsExist"]) ? $IsExist : "IsExist" )."
			,Comment = {$Comment}
			,order_date = ".( isset($_POST["IsExist"]) ? $OrderDate : "order_date" )."
			,arrival_date = ".( isset($_POST["IsExist"]) ? $ArrivalDate : "arrival_date" )."
			,author = {$_SESSION['id']}
		WHERE ODD_ID = {$_GET["oddid"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Обновляем количество
	$query = "
		UPDATE OrdersDataDetail
		SET Amount = {$_POST["Amount"]}
		WHERE ODD_ID = {$_GET["oddid"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	if (mysqli_affected_rows($mysqli) and $price) {
		$query = "
			UPDATE OrdersData SET change_price = 1, author = {$_SESSION['id']} WHERE OD_ID = {$od_id}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	//Обновляем записи, влияющие на цену
	$query = "
		UPDATE OrdersDataDetail
		SET PM_ID = {$Model}
			,Length = {$Length}
			,Width = {$Width}
			,PieceAmount = {$PieceAmount}
			,PieceSize = {$PieceSize}
			,PF_ID = {$Form}
			,PME_ID = {$Mechanism}
			,box = {$box}
			,ptn = $ptn
		WHERE ODD_ID = {$_GET["oddid"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Если были изменения обновляем цену
	if (mysqli_affected_rows($mysqli) or $old_Shipper != $Shipper) {
		$query = "CALL Price({$_GET["oddid"]})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'#prod'.$_GET["oddid"].'">');
	die;
}

// Обновление цены изделий в заказе
elseif (isset($_GET["add_price"])) {
	foreach ($_POST["ODD_ID"] as $key => $value) {
		$price = $_POST["price"][$key] ? $_POST["price"][$key] : "NULL";
		$discount = $_POST["discount"][$key] ? $_POST["discount"][$key] : "NULL";
		$query = "UPDATE OrdersDataDetail SET Price = {$price}, discount = {$discount}, author = {$_SESSION['id']} WHERE ODD_ID = {$value}";
		if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["error"][] = mysqli_error( $mysqli ); }
	}
	exit ('<meta http-equiv="refresh" content="0; url='.$_POST["location"].'#ord'.$_GET["OD_ID"].'">');
	die;
}

// Добавление в базу нового платежа к заказу
elseif( isset($_GET["add_payment"]) and $_POST["payment_sum_add"] ) {
	$OD_ID = $_GET["OD_ID"];
	$payment_date = date( 'Y-m-d', strtotime($_POST["payment_date_add"]) );
	$payment_sum = $_POST["payment_sum_add"];
	$terminal = $_POST["terminal_add"];
	$terminal_payer = $terminal ? '\''.mysqli_real_escape_string( $mysqli, convert_str($_POST["terminal_payer_add"]) ).'\'' : 'NULL';
	$FA_ID_add = $_POST["FA_ID_add"] ? $_POST["FA_ID_add"] : 'NULL';
	$SH_ID_add = $_POST["FA_ID_add"] ? 'NULL' : $_POST["SH_ID_add"];

	if( $payment_sum ) {
		// Записываем новый платеж в таблицу платежей
		$query = "INSERT INTO OrdersPayment
					 SET OD_ID = {$OD_ID}
						,payment_date = '{$payment_date}'
						,payment_sum = {$payment_sum}
						,terminal_payer = {$terminal_payer}
						,SH_ID = {$SH_ID_add}
						,FA_ID = ".($terminal ? "(SELECT SH.FA_ID FROM Shops SH JOIN OrdersData OD ON OD.SH_ID = SH.SH_ID AND OD.OD_ID = {$OD_ID})" : $FA_ID_add)."
						,author = {$_SESSION['id']}";
		if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["error"][] = mysqli_error( $mysqli ); }
		else {
			// Записываем дату продажи заказа если ее не было
			$query = "UPDATE OrdersData SET StartDate = '{$payment_date}', author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID} AND StartDate IS NULL";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}
		}
	}

	exit ('<meta http-equiv="refresh" content="0; url='.$_POST["location"].'#ord'.$OD_ID.'">');
	die;
}

// Обновление в базе производственных этапов
elseif (isset($_POST["ODD_ID"])) {
	if (!in_array('step_update', $Rights)) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
	foreach ($_POST as $k => $v)
	{
		if (strpos($k,"Tariff") === 0) {
			$sid = (int)str_replace( "Tariff", "", $k ); // ID этапа
			$tariff = $v ? "$v" : "NULL";
			$worker = $_POST["WD_ID".$sid] ? $_POST["WD_ID".$sid] : "NULL";
			$isready = $_POST["IsReady".$sid] ? $_POST["IsReady".$sid] : 0;
			$visible = $_POST["Visible".$sid] ? $_POST["Visible".$sid] : 0;
			$query = "
				UPDATE OrdersDataSteps
				SET WD_ID = {$worker}, Tariff = {$tariff}, IsReady = {$isready}, Visible = {$visible}, author = {$_SESSION['id']}
				WHERE ODD_ID = {$_POST["ODD_ID"]} AND IFNULL(ST_ID, 0) = {$sid}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Удаление архивных этапов
		$query = "DELETE FROM OrdersDataSteps WHERE ODD_ID = {$_POST["ODD_ID"]} AND Old = 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'#prod'.$_POST["ODD_ID"].'">');
	die;
}

else {
	exit ('<meta http-equiv="refresh" content="0; url=/">');
	die;

}
?>
