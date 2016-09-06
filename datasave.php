<?
session_start();
include "config.php";

// Обновление параметров изделия
if( $_GET["oddid"] )
{
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

	// Если изменения затрагивают этапы то создаем новые этапы, а старые помечаем
	if( $Model != $_POST["Model"] or $Mechanism != $_POST["Mechanism"] or $Length != $_POST["Length"] ) {
		// Удаляем видимые этапы без работника
		$query = "DELETE FROM OrdersDataSteps WHERE ODD_ID = {$_GET["oddid"]} AND WD_ID IS NULL AND Visible = 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Оставшиеся этапы помечаются архивом (Old)
		$query = "UPDATE OrdersDataSteps SET Old = 1 WHERE ODD_ID = {$_GET["oddid"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Добавляем заново все этапы
		$Model = $_POST["Model"] ? "{$_POST["Model"]}" : "NULL";
		$Mechanism = $_POST["Mechanism"] ? "{$_POST["Mechanism"]}" : "NULL";
		$Length = $_POST["Type"] == 2 ? "{$_POST["Length"]}" : "NULL";
		$query="INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, Tariff)
				SELECT {$_GET["oddid"]}
					  ,ST.ST_ID
					  ,(IFNULL(ST.Tariff, 0) + IFNULL(PMET.Tariff, 0) + IFNULL(PMOT.Tariff, 0) + IFNULL(PSLT.Tariff, 0))
				FROM StepsTariffs ST
				JOIN ProductModelsTariff PMOT ON PMOT.ST_ID = ST.ST_ID AND PMOT.PM_ID = IFNULL({$Model}, 0)
				LEFT JOIN ProductMechanismTariff PMET ON PMET.ST_ID = ST.ST_ID AND PMET.PME_ID = {$Mechanism}
				LEFT JOIN ProductSizeLengthTariff PSLT ON PSLT.ST_ID = ST.ST_ID AND {$Length} BETWEEN PSLT.From AND PSLT.To";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	// Обновляем информацию об изделии
	$Price = ($_POST["Price"] !== '') ? "{$_POST["Price"]}" : "NULL";
	$Model = $_POST["Model"] ? "{$_POST["Model"]}" : "NULL";
	$Mechanism = $_POST["Mechanism"] ? "{$_POST["Mechanism"]}" : "NULL";
	$Length = $_POST["Type"] == 2 ? "{$_POST["Length"]}" : "NULL";
	$Form = $_POST["Form"] ? "{$_POST["Form"]}" : "NULL";
	$Width = $_POST["Type"] == 2 ? "{$_POST["Width"]}" : "NULL";
	$PieceAmount = $_POST["PieceAmount"] ? "{$_POST["PieceAmount"]}" : "NULL";
	$PieceSize = $_POST["PieceSize"] ? "{$_POST["PieceSize"]}" : "NULL";
	$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : 0;
	$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
	$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
	// Удаляем лишние пробелы
	$Material = trim($Material);
	$Comment = trim($Comment);
	$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
	$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";

	// Узнаем какой материал был ранее
	$query = "SELECT IFNULL(MT.Material, '') Material, ODD.MT_ID
			  FROM OrdersDataDetail ODD
			  JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
			  WHERE ODD.ODD_ID = {$_GET["oddid"]}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$OldMaterial = mysqli_result($res,0,'Material');

	// Если материалы не совпадают
	if( $OldMaterial != $Material ) {
		$query = "UPDATE Materials SET Count = Count - 1 WHERE Material = '{$OldMaterial}' AND PT_ID = {$_POST["Type"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		if( $Material != '' ) { // Сохраняем в таблицу материалов полученный материал и узнаем его ID
			$query = "INSERT INTO Materials
						SET
							PT_ID = {$_POST["Type"]},
							Material = '{$Material}',
							Count = 1
						ON DUPLICATE KEY UPDATE
							Count = Count + 1";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$mt_id = mysqli_insert_id( $mysqli );
		}
		else {
			$mt_id = "NULL";
		}
	}
	else {
		if( $Material != '' ) {
			$mt_id = mysqli_result($res,0,'MT_ID');
		}
		else {
			$mt_id = "NULL";
		}
	}

	$query = "UPDATE OrdersDataDetail
			  SET PM_ID = {$Model}
				 ,Length = {$Length}
				 ,Width = {$Width}
				 ,PieceAmount = {$PieceAmount}
				 ,PieceSize = {$PieceSize}
				 ,PF_ID = {$Form}
				 ,PME_ID = {$Mechanism}
				 ,MT_ID = {$mt_id}
				 ,IsExist = {$IsExist}
				 ,Amount = {$_POST["Amount"]}
				 ,Price = {$Price}
				 ,Comment = '{$Comment}'
				 ,is_check = 1
				 ,order_date = {$OrderDate}
				 ,arrival_date = {$ArrivalDate}
			  WHERE ODD_ID = {$_GET["oddid"]}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Если количество изделий уменьшено и изделие в работе, то переносим их на склад (свободные)
	if( $amount > $_POST["Amount"] and $inprogress == 1)
	{
		// Перемещение на склад лишних изделий
		$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, Length, Width, PF_ID, PME_ID, Material, IsExist, Amount, Price, is_check, order_date, arrival_date)
				  SELECT NULL, PM_ID, Length, Width, PF_ID, PME_ID, Material, IsExist, Amount - {$_POST["Amount"]}, {$Price}, 0, order_date, arrival_date FROM OrdersDataDetail WHERE ODD_ID = {$_GET["oddid"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$odd_id = mysqli_insert_id( $mysqli );

		// Копирование производственных этапов
		$query = "INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, WD_ID, IsReady, Tariff, Visible)
				  SELECT {$odd_id}, ST_ID, WD_ID, IsReady, Tariff, Visible FROM OrdersDataSteps
				  WHERE ODD_ID = {$_GET["oddid"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Обновляем этапы чтобы сработал триггер для движения денег
		$query = "UPDATE OrdersDataSteps SET Old = Old WHERE ODD_ID IN ({$_GET["oddid"]}, {$odd_id})";

		$_SESSION["alert"] = 'Изделия отправлены в "Свободные". Пожалуйста, проверьте информацию по этапам производства и параметрам изделий на экране "Свободные" (выделены красным фоном).';
	}

	header( "Location: ".$_GET["location"]."#prod".$_GET["oddid"] ); // Перезагружаем экран
	die;
}

// Обновление параметров заготовки или прочего
if( $_GET["odbid"] )
{
	$Price = ($_POST["Price"] !== '') ? "{$_POST["Price"]}" : "NULL";
	$Blank = $_POST["Blanks"] ? "{$_POST["Blanks"]}" : "NULL";
	$Other = trim($_POST["Other"]);
	$Other = mysqli_real_escape_string( $mysqli, $Other );
	$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : 0;
	$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
	$Material = trim($Material);
	$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
	$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";
	$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
	$Comment = trim($Comment);

	// Узнаем какой материал был ранее
	$query = "SELECT IFNULL(MT.Material, '') Material, ODB.MT_ID
			  FROM OrdersDataBlank ODB
			  JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
			  WHERE ODB.ODB_ID = {$_GET["odbid"]}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$OldMaterial = mysqli_result($res,0,'Material');

	// Если материалы не совпадают
	if( $OldMaterial != $Material ) {
		$query = "UPDATE Materials SET Count = Count - 1 WHERE Material = '{$OldMaterial}' AND PT_ID = 0";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		if( $Material != '' ) { // Сохраняем в таблицу материалов полученный материал и узнаем его ID
			$query = "INSERT INTO Materials
						SET
							PT_ID = 0,
							Material = '{$Material}',
							Count = 1
						ON DUPLICATE KEY UPDATE
							Count = Count + 1";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$mt_id = mysqli_insert_id( $mysqli );
		}
		else {
			$mt_id = "NULL";
		}
	}
	else {
		if( $Material != '' ) {
			$mt_id = mysqli_result($res,0,'MT_ID');
		}
		else {
			$mt_id = "NULL";
		}
	}

	$query = "UPDATE OrdersDataBlank
			  SET BL_ID = {$Blank}
				 ,Other = '{$Other}'
				 ,Amount = {$_POST["Amount"]}
				 ,Price = {$Price}
				 ,Comment = '{$Comment}'
				 ,MT_ID = {$mt_id}
				 ,IsExist = {$IsExist}
				 ,order_date = {$OrderDate}
				 ,arrival_date = {$ArrivalDate}
			  WHERE ODB_ID = {$_GET["odbid"]}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Обновление этапов чтобы сработал триггер
	$query = "UPDATE OrdersDataSteps SET Old = Old WHERE ODB_ID = {$_GET["odbid"]}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	header( "Location: ".$_GET["location"]."#blank".$_GET["odbid"] ); // Перезагружаем экран
	die;

}

// Обновление в базе производственных этапов
if( isset($_POST["ODD_ID"]) )
{
	foreach( $_POST as $k => $v) 
	{
		if( strpos($k,"Tariff") === 0 ) {
			$sid = (int)str_replace( "Tariff", "", $k ); // ID этапа
			$tariff = $v ? "$v" : "NULL";
			$worker = $_POST["WD_ID".$sid] ? $_POST["WD_ID".$sid] : "NULL";
			$isready = $_POST["IsReady".$sid] ? $_POST["IsReady".$sid] : 0;
			$visible = $_POST["Visible".$sid] ? $_POST["Visible".$sid] : 0;
			$query = "UPDATE OrdersDataSteps
					  SET WD_ID = {$worker}, Tariff = {$tariff}, IsReady = {$isready}, Visible = {$visible}
					  WHERE ODD_ID = {$_POST["ODD_ID"]} AND ST_ID = $sid";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Удаление архивных этапов
		$query = "DELETE FROM OrdersDataSteps WHERE ODD_ID = {$_POST["ODD_ID"]} AND Old = 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( isset($_GET["plid"]) and $_GET["plid"] !== "" ) {
		header( "Location: ".$_GET["location"]."#pl".$_GET["plid"] );
	}
	else {
		header( "Location: ".$_GET["location"]."#prod".$_POST["ODD_ID"] );
	}
	die;
}

// Обновление производственных этапов для прочего
if( isset($_POST["ODB_ID"]) )
{
	foreach( $_POST as $k => $v)
	{
		if( strpos($k,"Tariff") === 0 ) {
			$sid = 0;
			$tariff = $v ? "$v" : "NULL";
			$worker = $_POST["WD_ID".$sid] ? $_POST["WD_ID".$sid] : "NULL";
			$isready = $_POST["IsReady".$sid] ? $_POST["IsReady".$sid] : 0;
			$visible = $_POST["Visible".$sid] ? $_POST["Visible".$sid] : 0;
			$query = "UPDATE OrdersDataSteps
					  SET WD_ID = {$worker}, Tariff = {$tariff}, IsReady = {$isready}, Visible = {$visible}
					  WHERE ODB_ID = {$_POST["ODB_ID"]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Удаление архивных этапов
		$query = "DELETE FROM OrdersDataSteps WHERE ODB_ID = {$_POST["ODB_ID"]} AND Old = 1";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( isset($_GET["plid"]) and $_GET["plid"] !== "" ) {
		header( "Location: ".$_GET["location"]."#pl".$_GET["plid"] );
	}
	else {
		header( "Location: ".$_GET["location"]."#blank".$_POST["ODB_ID"] );
	}
	die;
}
?>
