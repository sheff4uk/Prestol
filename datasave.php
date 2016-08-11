<?
session_start();
include "config.php";

// Обновление параметров изделия
if( $_GET["oddid"] )
{
	$query = "SELECT ODD.Amount
					,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
					,OD.Color
					,IFNULL(OD.IsPainting, 0) IsPainting
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
	$color= mysqli_result($res,0,'Color');
	$ispainting = mysqli_result($res,0,'IsPainting');
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
	$Form = $_POST["Form"] ? "{$_POST["Form"]}" : "NULL";
	$Width = $_POST["Type"] == 2 ? "{$_POST["Width"]}" : "NULL";
	$PieceAmount = $_POST["PieceAmount"] ? "{$_POST["PieceAmount"]}" : "NULL";
	$PieceSize = $_POST["PieceSize"] ? "{$_POST["PieceSize"]}" : "NULL";
	$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : 0;
	$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
	$Color = mysqli_real_escape_string( $mysqli,$_POST["Color"] );
	$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
	// Удаляем лишние пробелы
	$Material = trim($Material);
	$Color = trim($Color);
	$Comment = trim($Comment);
	$OrderDate = $_POST["order_date"] ? date( 'Y-m-d', strtotime($_POST["order_date"]) ) : '';
	$ArrivalDate = $_POST["arrival_date"] ? date( 'Y-m-d', strtotime($_POST["arrival_date"]) ) : '';
	$query = "UPDATE OrdersDataDetail
			  SET PM_ID = {$Model}
				 ,Length = {$Length}
				 ,Width = {$Width}
				 ,PieceAmount = {$PieceAmount}
				 ,PieceSize = {$PieceSize}
				 ,PF_ID = {$Form}
				 ,PME_ID = {$Mechanism}
				 ,Material = '{$Material}'
				 ,IsExist = {$IsExist}
				 ,Amount = {$_POST["Amount"]}
				 ,Color = '{$Color}'
				 ,Comment = '{$Comment}'
				 ,is_check = 1
				 ,order_date = IF('{$OrderDate}' = '', order_date, '{$OrderDate}')
				 ,arrival_date = IF('{$ArrivalDate}' = '', arrival_date, '{$ArrivalDate}')
			  WHERE ODD_ID = {$_GET["oddid"]}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Если количество изделий уменьшено и изделие в работе, то переносим их на склад (свободные)
	if( $amount > $_POST["Amount"] and $inprogress == 1)
	{
		// Перемещение на склад лишних изделий
		$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, Length, Width, PF_ID, PME_ID, Material, IsExist, Amount, Color, is_check, order_date, arrival_date)
				  SELECT NULL, PM_ID, Length, Width, PF_ID, PME_ID, Material, IsExist, Amount - {$_POST["Amount"]}, IF({$ispainting} > 1, '{$color}', NULL), 0, order_date, arrival_date FROM OrdersDataDetail WHERE ODD_ID = {$_GET["oddid"]}";
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

	header( "Location: ".$_GET["location"]."#".$_GET["oddid"] ); // Перезагружаем экран
	die;
}

// Обновление параметров заготовки
if( $_GET["odbid"] )
{
	$Blank = $_POST["Blank"] ? "{$_POST["Blank"]}" : "NULL";
	$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
	// Удаляем лишние пробелы
	$Comment = trim($Comment);

	$query = "UPDATE OrdersDataBlank
			  SET BL_ID = {$Blank}
				 ,Amount = {$_POST["Amount"]}
				 ,Comment = '{$Comment}'
			  WHERE ODB_ID = {$_GET["odbid"]}";

	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	header( "Location: ".$_GET["location"]."#".$_GET["odbid"] ); // Перезагружаем экран
	die;

}

// Обновление в базе производственных этапов
if( isset($_POST["ODD_ID"]) )
{
	foreach( $_POST as $k => $v) 
	{
//		$val = $v ? "$v" : "NULL";

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

	header( "Location: ".$_GET["location"]."#".$_POST["ODD_ID"] );
	die;
}
?>
