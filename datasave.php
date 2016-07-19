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
			  FROM OrdersDataDetail ODD
			  LEFT JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
			  WHERE ODD.ODD_ID = {$_GET["oddid"]}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$amount = mysqli_result($res,0,'Amount');
	$inprogress = mysqli_result($res,0,'inprogress');
	$color= mysqli_result($res,0,'Color');
	$ispainting = mysqli_result($res,0,'IsPainting');

	// Обновляем информацию об изделии
	$Model = $_POST["Model"] ? "{$_POST["Model"]}" : "NULL";
	$Form = $_POST["Form"] ? "{$_POST["Form"]}" : "NULL";
	$Mechanism = $_POST["Mechanism"] ? "{$_POST["Mechanism"]}" : "NULL";
	$Length = $_POST["Length"] ? "{$_POST["Length"]}" : "0";
	$Width = $_POST["Width"] ? "{$_POST["Width"]}" : "0";
	$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : 0;
	$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
	$Color = mysqli_real_escape_string( $mysqli,$_POST["Color"] );
	$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
	$OrderDate = $_POST["order_date"] ? date( 'Y-m-d', strtotime($_POST["order_date"]) ) : '';
	$ArrivalDate = $_POST["arrival_date"] ? date( 'Y-m-d', strtotime($_POST["arrival_date"]) ) : '';
	$query = "UPDATE OrdersDataDetail
			  SET PM_ID = {$Model}
				 ,Length = {$Length}
				 ,Width = {$Width}
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

	// Добавляем недостающие этапы после обновления данных
	$query="INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, Tariff)
			SELECT {$_GET["oddid"]}
				  ,ST.ST_ID
				  ,(IFNULL(ST.Tariff, 0) + IFNULL(PMET.Tariff, 0) + IFNULL(PMOT.Tariff, 0) + IFNULL(PSLT.Tariff, 0))
			FROM StepsTariffs ST
			JOIN ProductModelsTariff PMOT ON PMOT.ST_ID = ST.ST_ID AND PMOT.PM_ID = IFNULL({$Model}, 0)
			LEFT JOIN ProductMechanismTariff PMET ON PMET.ST_ID = ST.ST_ID AND PMET.PME_ID = {$Mechanism}
			LEFT JOIN ProductSizeLengthTariff PSLT ON PSLT.ST_ID = ST.ST_ID AND {$Length} BETWEEN PSLT.From AND PSLT.To
			WHERE ST.ST_ID NOT IN( SELECT ST_ID FROM OrdersDataSteps WHERE ODD_ID = {$_GET["oddid"]} )";
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
		$query = "INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, WD_ID, IsReady, Tariff)
				  SELECT {$odd_id}, ST_ID, WD_ID, IsReady, Tariff FROM OrdersDataSteps
				  WHERE ODD_ID = {$_GET["oddid"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$_SESSION["alert"] = 'Изделия отправлены в "Свободные". Пожалуйста, проверьте информацию по этапам производства и параметрам изделий на экране "Свободные" (выделены красным фоном).';
	}

	// TODO: Нужно помечать в базе тарифы измененные вручную
	// пересчитывать тарифы при изменении параметров изделия (модель, форма, размер) кроме ручных
	// сделать возможность пересчитать любой тариф по клику, при этом снимается пометка "ручной ввод"
	
	// Если изменены модель, форма или размер, то пересчитываем тариф
//	$query = "SELECT ODD.PM_ID
//					,ODD.PS_ID
//					,PF.PME_ID
//			  FROM OrdersDataDetail ODD
//			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
//			  WHERE ODD.ODD_ID = {$_GET["oddid"]}";
//	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//	$model = mysqli_result($res,0,'PM_ID');
//	$size = mysqli_result($res,0,'PS_ID');
//	$mechanism = mysqli_result($res,0,'PME_ID');

	header( "Location: ".$_GET["location"]."#".$_GET["oddid"] ); // Перезагружаем экран
	die;
}

// Обновление в базе производственных этапов
if( isset($_POST["ODD_ID"]) )
{
	// Обнуление статуса готовности
	$query = "UPDATE OrdersDataSteps SET IsReady = 0 WHERE ODD_ID = {$_POST["ODD_ID"]}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	foreach( $_POST as $k => $v) 
	{
		$val = $v ? "$v" : "NULL";

		// Обновление работника
		if( strpos($k,"WD_ID") === 0 ) 
		{
			$sid = (int)str_replace( "WD_ID", "", $k );
			$query = "UPDATE OrdersDataSteps SET WD_ID = $val WHERE ODD_ID = {$_POST["ODD_ID"]} AND ST_ID = $sid";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Обновление тарифа
		if( strpos($k,"Tariff") === 0 ) 
		{
			$sid = (int)str_replace( "Tariff", "", $k );
			$tariff = $v ? "$v" : "NULL";
			$query = "UPDATE OrdersDataSteps SET Tariff = $val WHERE ODD_ID = {$_POST["ODD_ID"]} AND ST_ID = $sid";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Обновление статуса готовности
		if( strpos($k,"IsReady") === 0 ) 
		{
			$sid = (int)str_replace( "IsReady", "", $k );
			$query = "UPDATE OrdersDataSteps SET IsReady = $v WHERE ODD_ID = {$_POST["ODD_ID"]} AND ST_ID = $sid";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
	}

	header( "Location: ".$_GET["location"]."#".$_POST["ODD_ID"] );
	die;
}
?>
