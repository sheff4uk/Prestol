<?
session_start();
include "config.php";
include "header.php";

// Обновление параметров изделия
if( $_GET["oddid"] and isset($_POST["Amount"]) )
{
	if( !in_array('order_add', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
	$query = "SELECT ODD.Amount
					,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
					,ODD.PM_ID
					,ODD.PME_ID
					,ODD.Length
			  FROM OrdersDataDetail ODD
			  LEFT JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
			  WHERE ODD.ODD_ID = {$_GET["oddid"]}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$amount = mysqli_result($res,0,'Amount');
	$inprogress = mysqli_result($res,0,'inprogress');
	$Model = mysqli_result($res,0,'PM_ID');
	$Mechanism = mysqli_result($res,0,'PME_ID');
	$Length = mysqli_result($res,0,'Length');

	// Узнаем возможен ли ящик для этой модели с таким механизмом
	if( $_POST["Mechanism"] and $_POST["Model"] ) {
		$query = "
			SELECT box
			FROM ProductModelsMechanism
			WHERE PM_ID = {$_POST["Model"]} AND PME_ID = {$_POST["Mechanism"]}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$box_aval = mysqli_result($res,0,'box');
	}

	// Обновляем информацию об изделии
	$Model = $_POST["Model"] ? "{$_POST["Model"]}" : "NULL";
	$Form = $_POST["Form"] ? "{$_POST["Form"]}" : "NULL";
	$Mechanism = $_POST["Mechanism"] ? "{$_POST["Mechanism"]}" : "NULL";
	$box = ($box_aval == 1 and $_POST["box"] == 1) ? 1 : 0;
	$Length = $_POST["Type"] == 2 ? "{$_POST["Length"]}" : "NULL";
	$Width = $_POST["Width"] ? "{$_POST["Width"]}" : "NULL";
	$PieceAmount = $_POST["PieceAmount"] ? "{$_POST["PieceAmount"]}" : "NULL";
	$PieceSize = $_POST["PieceSize"] ? "{$_POST["PieceSize"]}" : "NULL";
	$IsExist = $_POST["IsExist"];
	$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
	$Shipper = $_POST["Shipper"] ? $_POST["Shipper"] : "NULL";
	$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
	$ptn = $_POST["ptn"];

	// Удаляем лишние пробелы
	$Material = trim($Material);
	$Comment = trim($Comment);
	$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
	$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";

	// Сохраняем в таблицу материалов полученный материал и узнаем его ID
	if( $Material != '' ) {
		$query = "INSERT INTO Materials
					SET
						Material = '{$Material}',
						SH_ID = {$Shipper},
						Count = 0
					ON DUPLICATE KEY UPDATE
						Count = Count + 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$mt_id = mysqli_insert_id( $mysqli );
	}
	else {
		$mt_id = "NULL";
	}

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
			,MT_ID = {$mt_id}
			,IsExist = ".( isset($_POST["IsExist"]) ? $IsExist : "IsExist" )."
			,Amount = {$_POST["Amount"]}
			,Comment = '{$Comment}'
			,order_date = ".( isset($_POST["IsExist"]) ? $OrderDate : "order_date" )."
			,arrival_date = ".( isset($_POST["IsExist"]) ? $ArrivalDate : "arrival_date" )."
			,author = {$_SESSION['id']}
			,ptn = $ptn
		WHERE ODD_ID = {$_GET["oddid"]}
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = mysqli_error( $mysqli );
	}

	// Вычисляем и обновляем стоимость по прайсу
	$query = "CALL Price({$_GET["oddid"]})";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'#prod'.$_GET["oddid"].'">');
	die;
}

// Обновление параметров заготовки или прочего
elseif( $_GET["odbid"] and isset($_POST["Amount"]) )
{
	if( !in_array('order_add', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
	$Blank = $_POST["Blanks"] ? "{$_POST["Blanks"]}" : "NULL";
	$Other = trim($_POST["Other"]);
	$Other = mysqli_real_escape_string( $mysqli, $Other );
	$IsExist = $_POST["IsExist"];
	$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
	$Material = trim($Material);
	$Shipper = $_POST["Shipper"] ? $_POST["Shipper"] : "NULL";
	$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
	$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";
	$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
	$Comment = trim($Comment);
	$ptn = $_POST["ptn"];

	// Сохраняем в таблицу материалов полученный материал и узнаем его ID
	if( $Material != '' ) {
		$query = "INSERT INTO Materials
					SET
						Material = '{$Material}',
						SH_ID = {$Shipper},
						Count = 0
					ON DUPLICATE KEY UPDATE
						Count = Count + 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$mt_id = mysqli_insert_id( $mysqli );
	}
	else {
		$mt_id = "NULL";
	}

	$query = "
		UPDATE OrdersDataBlank
		SET BL_ID = {$Blank}
			,Other = '{$Other}'
			,Amount = {$_POST["Amount"]}
			,Comment = '{$Comment}'
			,MT_ID = {$mt_id}
			,IsExist = ".( isset($_POST["IsExist"]) ? $IsExist : "IsExist" )."
			,order_date = ".( isset($_POST["IsExist"]) ? $OrderDate : "order_date" )."
			,arrival_date = ".( isset($_POST["IsExist"]) ? $ArrivalDate : "arrival_date" )."
			,author = {$_SESSION['id']}
			,ptn = $ptn
		WHERE ODB_ID = {$_GET["odbid"]}
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = mysqli_error( $mysqli );
	}

	exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'#blank'.$_GET["odbid"].'">');
	die;

}

// Обновление в базе производственных этапов
elseif( isset($_POST["ODD_ID"]) )
{
	if( !in_array('step_update', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
	foreach( $_POST as $k => $v) 
	{
		if( strpos($k,"Tariff") === 0 ) {
			$sid = (int)str_replace( "Tariff", "", $k ); // ID этапа
			$tariff = $v ? "$v" : "NULL";
			$worker = $_POST["WD_ID".$sid] ? $_POST["WD_ID".$sid] : "NULL";
			$isready = $_POST["IsReady".$sid] ? $_POST["IsReady".$sid] : 0;
			$visible = $_POST["Visible".$sid] ? $_POST["Visible".$sid] : 0;
			$query = "UPDATE OrdersDataSteps
					  SET WD_ID = {$worker}, Tariff = {$tariff}, IsReady = {$isready}, Visible = {$visible}, author = {$_SESSION['id']}
					  WHERE ODD_ID = {$_POST["ODD_ID"]} AND ST_ID = $sid";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Удаление архивных этапов
		$query = "DELETE FROM OrdersDataSteps WHERE ODD_ID = {$_POST["ODD_ID"]} AND Old = 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( isset($_GET["plid"]) and $_GET["plid"] !== "" ) {
		exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'#pl'.$_GET["plid"].'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'#prod'.$_POST["ODD_ID"].'">');
	}
	die;
}

// Обновление производственных этапов для прочего
elseif( isset($_POST["ODB_ID"]) )
{
	if( !in_array('step_update', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
	foreach( $_POST as $k => $v)
	{
		if( strpos($k,"Tariff") === 0 ) {
			$sid = 0;
			$tariff = $v ? "$v" : "NULL";
			$worker = $_POST["WD_ID".$sid] ? $_POST["WD_ID".$sid] : "NULL";
			$isready = $_POST["IsReady".$sid] ? $_POST["IsReady".$sid] : 0;
			$visible = $_POST["Visible".$sid] ? $_POST["Visible".$sid] : 0;
			$query = "UPDATE OrdersDataSteps
					  SET WD_ID = {$worker}, Tariff = {$tariff}, IsReady = {$isready}, Visible = {$visible}, author = {$_SESSION['id']}
					  WHERE ODB_ID = {$_POST["ODB_ID"]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Удаление архивных этапов
		$query = "DELETE FROM OrdersDataSteps WHERE ODB_ID = {$_POST["ODB_ID"]} AND Old = 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( isset($_GET["plid"]) and $_GET["plid"] !== "" ) {
		exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'#pl'.$_GET["plid"].'">');
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'#blank'.$_POST["ODB_ID"].'">');
	}
	die;
}

// Обновление цены изделий в заказе
elseif( isset($_GET["add_price"]) ) {
	$OD_ID = $_POST["OD_ID"];

	foreach ($_POST["PT_ID"] as $key => $value) {
		$price = $_POST["price"][$key] ? $_POST["price"][$key] : "NULL";
		$discount = $_POST["discount"][$key] ? $_POST["discount"][$key] : "NULL";
		if( $value == 0 ) {
			$query = "UPDATE OrdersDataBlank SET Price = {$price}, discount = {$discount}, author = {$_SESSION['id']} WHERE ODB_ID = {$_POST["itemID"][$key]}";
		}
		else {
			$query = "UPDATE OrdersDataDetail SET Price = {$price}, discount = {$discount}, author = {$_SESSION['id']} WHERE ODD_ID = {$_POST["itemID"][$key]}";
		}
		if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["error"][] = mysqli_error( $mysqli ); }
	}
	exit ('<meta http-equiv="refresh" content="0; url='.$_POST["location"].'#ord'.$OD_ID.'">');
	die;
}

else {
	exit ('<meta http-equiv="refresh" content="0; url=/">');
	die;

}
?>
