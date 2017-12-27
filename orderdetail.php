<?
	include "config.php";
	include "header.php";

	if( isset($_GET["id"]) and (int)$_GET["id"] > 0 )
	{
		$title = 'Детали заказа';
		// Проверка прав на доступ к экрану
		// Проверка города
		$query = "SELECT OD.OD_ID
						,IF(OS.locking_date IS NOT NULL AND IF(SH.KA_ID IS NULL, 1, 0), 1, 0) is_lock
						,OD.confirmed
						,OD.Del
						,IF(OD.ReadyDate IS NOT NULL, 1, 0) Archive
					FROM OrdersData OD
					LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
					LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
					WHERE IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) AND OD_ID = {$_GET["id"]}
						".($USR_Shop ? "AND (SH.SH_ID = {$USR_Shop} OR (OD.StartDate IS NULL AND IF(SH.KA_ID IS NULL, 1, 0)) OR OD.SH_ID IS NULL)" : "")."
						".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "");
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$OD_ID = mysqli_result($res,0,'OD_ID');
		$Del = mysqli_result($res,0,'Del');
		$Archive = mysqli_result($res,0,'Archive');
		$is_lock = mysqli_result($res,0,'is_lock');
		$confirmed = mysqli_result($res,0,'confirmed');
		// Запрет на редактирование
		$disabled = !( in_array('order_add', $Rights) and ($confirmed == 0 or in_array('order_add_confirm', $Rights)) and !$is_lock and !$Archive and !$Del );

		if( !$OD_ID ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$id = (int)$_GET["id"];
		$location = "orderdetail.php?id=".$id;
		$free = 0;
	}
	else
	{
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
//		// Проверка прав на доступ к экрану
//		if( !in_array('screen_free', $Rights) ) {
//			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
//			die('Недостаточно прав для совершения операции');
//		}
//		$id = "NULL";
//		$location = "orderdetail.php?free=1";
//		$free = 1;
	}

	// Обновление основной информации о заказе
	if( isset($_GET["order_update"]) )
	{
		$StartDate = $_POST["StartDate"] ? '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'' : "NULL";
		$EndDate = $_POST[EndDate] ? '\''.date( "Y-m-d", strtotime($_POST["EndDate"]) ).'\'' : "NULL";
		$ClientName = mysqli_real_escape_string( $mysqli,$_POST["ClientName"] );
		$ul = ($_POST["ClientName"] and $_POST["ul"]) ? "1" : "0";
		$Shop = $_POST["Shop"] > 0 ? $_POST["Shop"] : "NULL";
		$OrderNumber = mysqli_real_escape_string( $mysqli,$_POST["OrderNumber"] );
		$Color = mysqli_real_escape_string( $mysqli,$_POST["Color"] );
		//$IsPainting = $_POST["IsPainting"];
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		// Удаляем лишние пробелы
		$ClientName = trim($ClientName);
		$OrderNumber = trim($OrderNumber);
		$Color = trim($Color);
		$Comment = trim($Comment);
		$query = "UPDATE OrdersData
					SET CLientName = ".(isset($_POST["ClientName"]) ? "'{$ClientName}'" : "CLientName")."
						,ul = ".(isset($_POST["ClientName"]) ? $ul : "ul")."
						,StartDate = ".(isset($_POST["StartDate"]) ? $StartDate : "StartDate")."
						,EndDate = ".(isset($_POST["EndDate"]) ? $EndDate : "EndDate")."
						,SH_ID = ".(isset($_POST["Shop"]) ? $Shop : "SH_ID")."
						,OrderNumber = ".(isset($_POST["OrderNumber"]) ? "'{$OrderNumber}'" : "OrderNumber")."
						,Color = ".(isset($_POST["Color"]) ? "'{$Color}'" : "Color")."
						#,IsPainting = ".( isset($_POST["IsPainting"]) ? $_POST["IsPainting"] : "IsPainting" )."
						,Comment = ".(isset($_POST["Comment"]) ? "'{$Comment}'" : "Comment")."
						,author = {$_SESSION['id']}
					WHERE OD_ID = {$id}";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["alert"] = mysqli_error( $mysqli );
		}
		else {
			// Узнаем есть ли платежи по кассе другого салона
			$query = "SELECT CheckPayment({$OD_ID}) attention";
			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			$attention = mysqli_result($res,0,'attention');
			if( $attention ) {
				$_SESSION["error"] = "У этого заказа имеются платежи, внесённые в кассу другого салона! Проверьте оплату в реализации.";
			}

			$_SESSION["success"] = "Данные сохранены.";
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Добавление в базу нового изделия. Заполнение этапов.
	if ( isset($_GET["add"]) and $_GET["add"] == 1 and !$disabled )
	{
		// Добавление в базу нового изделия
		$Price = ($_POST["Price"] !== '') ? "{$_POST["Price"]}" : "NULL";
		$Model = $_POST["Model"] ? "{$_POST["Model"]}" : "NULL";
		$Form = $_POST["Form"] ? "{$_POST["Form"]}" : "NULL";
		$Mechanism = $_POST["Mechanism"] ? "{$_POST["Mechanism"]}" : "NULL";
		$Length = $_POST["Type"] == 2 ? "{$_POST["Length"]}" : "NULL";
		$Width = $_POST["Type"] == 2 ? "{$_POST["Width"]}" : "NULL";
		$PieceAmount = $_POST["PieceAmount"] ? "{$_POST["PieceAmount"]}" : "NULL";
		$PieceSize = $_POST["PieceSize"] ? "{$_POST["PieceSize"]}" : "NULL";
		$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : "NULL";
		$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
		$Shipper = $_POST["Shipper"] ? $_POST["Shipper"] : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		$patina = mysqli_real_escape_string( $mysqli,$_POST["patina"] );
		// Удаляем лишние пробелы
		$Material = trim($Material);
		$Comment = trim($Comment);
		$patina = trim($patina);
		$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
		$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";

		// Сохраняем в таблицу материалов полученный материал и узнаем его ID
		if( $Material != '' ) {
			$query = "INSERT INTO Materials
						SET
							PT_ID = {$_POST["Type"]},
							Material = '{$Material}',
							SH_ID = {$Shipper},
							Count = 0
						ON DUPLICATE KEY UPDATE
							Count = Count + 1,
							SH_ID = {$Shipper}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$mt_id = mysqli_insert_id( $mysqli );
		}
		else {
			$mt_id = "NULL";
		}

		$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, Length, Width, PieceAmount, PieceSize, PF_ID, PME_ID, MT_ID, IsExist, Amount, Price, Comment, order_date, arrival_date, creator, patina)
				  VALUES ({$id}, {$Model}, {$Length}, {$Width}, {$PieceAmount}, {$PieceSize}, {$Form}, {$Mechanism}, {$mt_id}, {$IsExist}, {$_POST["Amount"]}, {$Price}, '{$Comment}', {$OrderDate}, {$ArrivalDate}, {$_SESSION['id']}, IF('{$patina}' = '', NULL, '{$patina}'))";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$odd_id = mysqli_insert_id( $mysqli );

		$_SESSION["odd_id"] = $odd_id; // Cохраняем в сессию id вставленной записи
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#'.$odd_id.'">');
		die;
	}
	else {
		$odd_id = isset($_SESSION["odd_id"]) ? $_SESSION["odd_id"] : ""; // Читаем из сессии id вставленной записи
		unset($_SESSION["odd_id"]); // Очищаем сессию
	}

	// Добавление к заказу заготовки или прочего
	if ( isset($_GET["addblank"]) and $_GET["addblank"] == 1 and !$disabled ) {
		$Price = ($_POST["Price"] !== '') ? "{$_POST["Price"]}" : "NULL";
		$Blank = $_POST["Blanks"] ? "{$_POST["Blanks"]}" : "NULL";
		$Other = trim($_POST["Other"]);
		$Other = mysqli_real_escape_string( $mysqli, $Other );
		$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : "NULL";
		$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
		$Material = trim($Material);
		$Shipper = $_POST["Shipper"] ? $_POST["Shipper"] : "NULL";
		$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
		$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		$patina = mysqli_real_escape_string( $mysqli,$_POST["patina"] );
		$Comment = trim($Comment);
		$patina = trim($patina);

		// Сохраняем в таблицу материалов полученный материал и узнаем его ID
		if( $Material != '' ) {
			$query = "INSERT INTO Materials
						SET
							PT_ID = {$_POST["MPT_ID"]},
							Material = '{$Material}',
							SH_ID = {$Shipper},
							Count = 0
						ON DUPLICATE KEY UPDATE
							Count = Count + 1,
							SH_ID = {$Shipper}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$mt_id = mysqli_insert_id( $mysqli );
		}
		else {
			$mt_id = "NULL";
		}

		$query = "INSERT INTO OrdersDataBlank(OD_ID, BL_ID, Other, Amount, Price, Comment, MT_ID, IsExist, order_date, arrival_date, creator, patina)
				  VALUES ({$id}, {$Blank}, '{$Other}', {$_POST["Amount"]}, {$Price}, '{$Comment}', {$mt_id}, {$IsExist}, {$OrderDate}, {$ArrivalDate}, {$_SESSION['id']}, IF('{$patina}' = '', NULL, '{$patina}'))";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$odb_id = mysqli_insert_id( $mysqli );

		$_SESSION["odb_id"] = $odb_id; // Cохраняем в сессию id вставленной записи
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#'.$odb_id.'">');
		die;
	}
	else {
		$odb_id = isset($_SESSION["odb_id"]) ? $_SESSION["odb_id"] : ""; // Читаем из сессии id вставленной записи
		unset($_SESSION["odb_id"]); // Очищаем сессию
	}

	// Удаление изделия
	if( isset($_GET["del"]) and !$disabled )
	{
		$odd_id = (int)$_GET["del"];

		$query = "SELECT IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
				  FROM OrdersDataDetail ODD
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
				  WHERE ODD.ODD_ID = {$odd_id}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$inprogress = mysqli_result($res,0,'inprogress');

		if( $inprogress == 0 ) { // Если не приступили, то удаляем.
			$query = "UPDATE OrdersDataDetail SET Del = 1, author = {$_SESSION['id']} WHERE ODD_ID={$odd_id}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Удаление заготовки
	if( isset($_GET["delblank"]) and !$disabled ) {
		$odb_id = (int)$_GET["delblank"];

		$query = "SELECT IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
				  FROM OrdersDataBlank ODB
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID AND ODS.Visible = 1
				  WHERE ODB.ODB_ID = {$odb_id}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$inprogress = mysqli_result($res,0,'inprogress');

		if( $inprogress == 0 ) { // Если не приступили, то удаляем.
			$query = "UPDATE OrdersDataBlank SET Del = 1, author = {$_SESSION['id']} WHERE ODB_ID={$odb_id}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Удаление файла
	if( isset($_GET["delfile"]) and !$disabled ) {
		$oa_id = (int)$_GET["delfile"];

		// Узнаем имя файла
		$query = "SELECT filename
				  FROM OrdersAttachments
				  WHERE OA_ID = {$oa_id}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$filename = mysqli_result($res,0,'filename');

		// удаляем файл с диска
		$dir = $_SERVER['DOCUMENT_ROOT']."/uploads/";
		unlink($dir.$filename);

		// Удаляем запись в таблице
		$query = "DELETE FROM OrdersAttachments WHERE OA_ID = {$oa_id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'&tabs=2">');
		die;
	}

	// Добавление в базу нового сообщения
	if( isset($_GET["add_message"]) )
	{
		$Message = mysqli_real_escape_string( $mysqli,$_POST["message"] );
		$query = "INSERT INTO OrdersMessage
					 SET OD_ID = {$id}
						,Message = '{$Message}'
						,priority = {$_POST["priority"]}
						,author = {$_SESSION['id']}
						,destination = ".( in_array('order_add_confirm', $Rights) ? "0" : "1" );
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["alert"] = mysqli_error( $mysqli );
		}

		//exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	include "forms.php";

	if( $id != "NULL" )
	{
		echo "<p><a href='{$_SESSION["location"]}#ord{$_GET["id"]}' class='button'><< Вернуться</a></p>";

		$query = "SELECT OD.Code
						,OD.ClientName
						,OD.ul
						,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
						,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
						,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
						,DATE_FORMAT(OD.ReadyDate, '%d.%m.%y') ReadyDate
						,DATE_FORMAT(OD.DelDate, '%d.%m.%y') DelDate
						,IFNULL(OD.SH_ID, 0) SH_ID
						,IFNULL(SH.KA_ID, 0) KA_ID
						,OD.OrderNumber
						,OD.Color
						,OD.IsPainting
						,WD.Name
						,OD.Comment
						,IF(OD.SH_ID IS NULL, '#999', IFNULL(CT.Color, '#fff')) CTColor
						,IF((SH.KA_ID IS NULL AND SH.SH_ID IS NOT NULL), 1, 0) retail
						,SH.CT_ID
						,IFNULL(OD.SHP_ID, 0) SHP_ID
						,IF(PFI.rtrn = 1, NULL, OD.PFI_ID) PFI_ID
						,PFI.count
						,PFI.platelshik_id
				  FROM OrdersData OD
				  LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
				  LEFT JOIN WorkersData WD ON WD.WD_ID = OD.WD_ID
				  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
				  WHERE OD_ID = {$id}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$Code = mysqli_result($res,0,'Code');
		$ClientName = mysqli_result($res,0,'ClientName');
		$ul = mysqli_result($res,0,'ul');
		$AddDate = mysqli_result($res,0,'AddDate');
		$StartDate = mysqli_result($res,0,'StartDate');
		$EndDate = mysqli_result($res,0,'EndDate');
		$ReadyDate = mysqli_result($res,0,'ReadyDate');
		$DelDate = mysqli_result($res,0,'DelDate');
		$SH_ID = mysqli_result($res,0,'SH_ID');
		$KA_ID = mysqli_result($res,0,'KA_ID');
		$OrderNumber = mysqli_result($res,0,'OrderNumber');
		$Color = mysqli_result($res,0,'Color');
		$IsPainting = mysqli_result($res,0,'IsPainting');
		$Name = mysqli_result($res,0,'Name');
		$Comment = mysqli_result($res,0,'Comment');
		$CTColor = mysqli_result($res,0,'CTColor');
		$retail = mysqli_result($res,0,'retail');
		$CT_ID = mysqli_result($res,0,'CT_ID');
		$SHP_ID = mysqli_result($res,0,'SHP_ID');
		$PFI_ID = mysqli_result($res,0,'PFI_ID');
		$count = mysqli_result($res,0,'count');
		$platelshik_id = mysqli_result($res,0,'platelshik_id');
		// Если пользователю доступен только один салон в регионе или оптовик или свободный заказ и нет админских привилегий, то нельзя редактировать общую информация заказа.
		$editable = (!($USR_Shop and $SH_ID and $USR_Shop != $SH_ID) and !($USR_KA and $SH_ID and $USR_KA != $KA_ID) and !($SH_ID == 0 and !in_array('order_add_confirm', $Rights)));
?>
	<form method='post' id='order_form' action='<?=$location?>&order_update=1'>
	<table class="">
		<thead>
		<tr class='nowrap'>
			<th>Код<br>Создан</th>
			<th>Заказчик<br>Квитанция</th>
			<th>Дата продажи</th>
			<?= ($ReadyDate ? "<th>Отгружено</th>" : ($DelDate ? "<th>Удалено</th>" : "<th>Дата сдачи</th>")) ?>
			<th>Салон</th>
			<th>Цвет</th>
			<th>Примечание</th>
			<th>Действие</th>
		</tr>
		</thead>
		<tbody>
		<tr class='ord_log_row' lnk='*OD_ID<?=$id?>*' id='ord<?=$id?>'>
			<td class="nowrap"><h1><?=$Code?></h1><?=$AddDate?></td>
			<td>
				<input type='text' class='clienttags' name='ClientName' style='width: 90px;' value='<?=$ClientName?>' <?=((in_array('order_add', $Rights) and !$is_lock and !$Del and $retail and $editable) ? "" : "disabled")?> placeholder='Заказчик'>
				<br>
				<input type="checkbox" id="ul" name='ul' <?=($ul == 1 ? "checked" : "")?> <?=((in_array('order_add', $Rights) and !$is_lock and !$Del and $retail and $editable) ? "" : "disabled")?> title='Поставьте галочку если требуется накладная.'>
				<label for="ul">юр. лицо</label>
				<br>
				<input type='text' name='OrderNumber' style='width: 90px;' value='<?=$OrderNumber?>' <?=((in_array('order_add', $Rights) and !$is_lock and !$Del and $retail and $editable) ? "" : "disabled")?> placeholder='Квитанция'>
			</td>

			<?
			// Если заказ в накладной - под датой продажи ссылка на накладную
			if( $PFI_ID ) {
				$invoice = "<br><b><a href='open_print_form.php?type=invoice&PFI_ID={$PFI_ID}&number={$count}' target='_blank'>Накладная</a></b>";
				$title="Чтобы стереть дату продажи анулируйте накладную в актах сверки, затем перейдите в реализацию и нажмите на символ ладошки справа.";
			}
			else {
				$invoice = "";
				$title = "Чтобы стереть дату продажи перейдите в реализацию и нажмите на символ ладошки справа.";
			}
			echo "<td><input type='text' name='StartDate' class='date from' value='{$StartDate}' date='{$StartDate}' ".((in_array('order_add', $Rights) and !$is_lock and !$Del and $retail and $editable) ? "" : "disabled")." readonly ".( (in_array('order_add', $Rights) and !$is_lock and !$Del and $retail and $editable and $StartDate) ? "title='{$title}'" : "" ).">{$invoice}</td>";
			?>

			<td style='text-align: center;'><?= ($ReadyDate ? $ReadyDate : ($DelDate ? $DelDate : "<input type='text' name='EndDate' class='date to' value='{$EndDate}' readonly ".((!$disabled and $editable and $SH_ID and in_array('order_add_confirm', $Rights)) ? "" : "disabled").">")) ?></td>
			<td>
			<div class='shop_cell' id='<?=$id?>' style='box-shadow: 0px 0px 10px 10px <?=$CTColor?>;'>
				<select name='Shop' class='select_shops' <?=((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")?>>
					<?
//					if( $SH_ID == "0" or in_array('order_add_confirm', $Rights)) {
//						echo "<option value='0' selected style='background: #999;'>Свободные</option>";
//					}
//					// Если пользователю доступен только один салон в регионе или оптовик, то выводится только текущий салон и нельзя менять его
//					if( ($USR_Shop and $SH_ID and $USR_Shop != $SH_ID) or ($USR_KA and $SH_ID and $USR_KA != $KA_ID) ) {
//						$query = "SELECT SH.SH_ID
//										,CONCAT(CT.City, '/', SH.Shop) AS Shop
//										,IF(SH.SH_ID = {$SH_ID}, 'selected', '') AS selected
//										,CT.Color
//									FROM Shops SH
//									JOIN Cities CT ON CT.CT_ID = SH.CT_ID
//									WHERE SH.SH_ID = {$SH_ID}";
//					}
//					elseif( $PFI_ID ) {
//						$query = "SELECT SH.SH_ID
//										,CONCAT(CT.City, '/', SH.Shop) AS Shop
//										,IF(SH.SH_ID = {$SH_ID}, 'selected', '') AS selected
//										,CT.Color
//									FROM Shops SH
//									JOIN Cities CT ON CT.CT_ID = SH.CT_ID
//									WHERE ".($retail ? "CT.CT_ID = {$CT_ID} AND SH.KA_ID IS NULL" : "SH.KA_ID = {$platelshik_id}")."
//										".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
//										".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
//									ORDER BY CT.City, SH.Shop";
//					}
//					elseif( $SHP_ID or $ReadyDate ) {
//						$query = "SELECT SH.SH_ID
//										,CONCAT(CT.City, '/', SH.Shop) AS Shop
//										,IF(SH.SH_ID = {$SH_ID}, 'selected', '') AS selected
//										,CT.Color
//									FROM Shops SH
//									JOIN Cities CT ON CT.CT_ID = SH.CT_ID
//									WHERE CT.CT_ID = {$CT_ID}
//										".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
//										".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
//									ORDER BY CT.City, SH.Shop";
//					}
//					else {
//						$query = "SELECT SH.SH_ID
//										,CONCAT(CT.City, '/', SH.Shop) AS Shop
//										,IF(SH.SH_ID = {$SH_ID}, 'selected', '') AS selected
//										,CT.Color
//									FROM Shops SH
//									JOIN Cities CT ON CT.CT_ID = SH.CT_ID
//									WHERE CT.CT_ID IN ({$USR_cities})
//										".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
//										".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
//									ORDER BY CT.City, SH.Shop";
//					}
//					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//					while( $row = mysqli_fetch_array($res) )
//					{
//						echo "<option value='{$row["SH_ID"]}' {$row["selected"]} style='background: {$row["Color"]};'>{$row["Shop"]}</option>";
//					}
					?>
				</select>
				</div>
			</td>

			<?
			echo "<td val='{$IsPainting}'";
				switch ($IsPainting) {
					case 1:
						$class = "notready";
						$title = "Не в работе";
						break;
					case 2:
						$class = "inwork";
						$title = "В работе";
						break;
					case 3:
						$class = "ready";
						$title = "Готово";
						if($Name) $title .= " ({$Name})";
						break;
				}
			echo " class='painting_cell ".((!$disabled and false) ? "painting" : "")." {$class}' title='{$title}'><div class='painting_workers'>{$Name}</div><input required type='text' class='colortags' name='Color' style='width: 160px;' value='{$Color}' autocomplete='off' ".((!$disabled and $editable) ? "" : "disabled")."></td>";
			?>

			<td><textarea name='Comment' rows='6' cols='15' <?=((!$disabled and $editable) ? "" : "disabled")?>><?=$Comment?></textarea></td>
			<td style="text-align: center;">
				<?
				// Если есть право редактирования и заказ не чужой - показываем кнопку клонирования
				if( in_array('order_add', $Rights) and $editable ) {
					echo "<p><a href='#' onclick='if(confirm(\"<b>Подтвердите клонирование заказа!</b>\", \"clone_order.php?id={$id}&author={$_SESSION['id']}&confirmed=".(in_array('order_add_confirm', $Rights) ? 1 : 0)."\")) return false;' title='Клонировать'><i class='fa fa-clone fa-2x' aria-hidden='true'></i></a></p>";
				}
				// Если розничный заказ - показываем кнопку перехода в реализацию
				if( $retail and !$is_lock and !$Del and $editable ) {
					echo "<p><a href='/selling.php?CT_ID={$CT_ID}#ord{$id}' title='Перейти в реализацию'><i class='fa fa-money fa-2x' aria-hidden='true'></i></a></p>";
				}
				// Если заказ в отгрузке и заказ не чужой - показываем кнопку перехода в отгрузку
				if( $SHP_ID and $editable ) {
					echo "<p><a href='/?shpid={$SHP_ID}#ord{$id}' title='Перейти в отгрузку'><i class='fa fa-truck fa-2x' aria-hidden='true'></i></a></p>";
				}
				?>
			</td>
		</tr>
		</tbody>
	</table>
	</form>

	<script>
		$(document).ready(function() {
			// Выводится выпадающий список салонов аяксом
			$.ajax({ url: "ajax.php?do=create_shop_select&OD_ID=<?=$id?>&SH_ID=<?=$SH_ID?>", dataType: "script", async: false });

//			$("input.from[name='StartDate']").datepicker("disable");
//			$( "input.from" ).datepicker( "option", "maxDate", "<?=$EndDate?>" );
//			$( "input.to" ).datepicker( "option", "minDate", "<?=$StartDate?>" );

			if("<?=$StartDate?>") {
				$("input[name='StartDate']").hover(function() {
					$("i.fa-money").effect( 'shake', 1000 );
				});
			}
		});
	</script>
<?
		if( $confirmed == 1 ) {
			echo "<div style='position: absolute; top: 77px; left: 140px; font-weight: bold; color: green; font-size: 1.2em;'>Заказ принят в работу.</div>";
		}
		if( $is_lock == 1 ) {
			echo "<div style='position: absolute; top: 77px; left: 340px; font-weight: bold; color: green; font-size: 1.2em;'>Месяц в реализации закрыт (изменения ограничены).</div>";
		}
		if( $Del == 1 ) {
			echo "<div style='position: absolute; top: 160px; left: 100px; font-weight: bold; color: #911; font-size: 5em; opacity: .3; border: 5px solid;'>Заказ удалён</div>";
		}
	}
	else {
		$confirmed = 1;
	}
?>
<div class="halfblock">
	<p>
		<button <?=(($disabled or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_product1'<?=($id == 'NULL')?' id="0"':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить стулья</button>
		<button <?=(($disabled or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_product2'<?=($id == 'NULL')?' id="0"':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить столы</button>
		<button <?=(($disabled or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_order_blank'<?=($id == 'NULL')?' id=\'0\'':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить заготовки/прочее</button>
	</p>

	<!-- Таблица изделий -->
	<table class="main_table">
		<thead>
		<tr>
			<th width="60">Кол-во</th>
			<th width="120">Изделие</th>
			<th width="60">Патина</th>
			<th width="100">Этапы</th>
			<th width="">Материал</th>
			<th width="">Поставщик</th>
			<th width="">Примечание</th>
<!--			<th width="60">Цена</th>-->
			<th width="75">Действие</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "SELECT ODD.ODD_ID
					,IFNULL(PM.PT_ID, 2) PT_ID
					,IFNULL(PM.Model, 'Столешница') Model
					,CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')) Size
					,PF.Form
					,PME.Mechanism
					,IFNULL(ODD.PM_ID, 0) PM_ID
					,ODD.Length
					,ODD.Width
					,ODD.PieceAmount
					,ODD.PieceSize
					,ODD.PF_ID
					,ODD.PME_ID
					,ODD.MT_ID
					,IFNULL(MT.Material, '') Material
					,IF(MT.removed=1, 'removed ', '') removed
					,IF(ODD.MT_ID IS NULL, '', IFNULL(SH.Shipper, '-=Другой=-')) Shipper
					,IFNULL(MT.SH_ID, '') SH_ID
					,ODD.IsExist
					,ODD.Amount
					,ODD.Price
					,ODD.Comment
					,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
					,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
					,IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), '') clock
					,IF(ODD.is_check = 1, '', 'attention') is_check
					,IF(IFNULL(SUM(ODS.WD_ID * ODS.Visible), 0) = 0, 0, 1) inprogress
					,GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR '') Steps
					,IF(SUM(ODS.Old) > 0, ' attention', '') Attention
					,ODD.patina
					,ODD.Del
			  FROM OrdersDataDetail ODD
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
			  LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
			  LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
			  LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID";
	if( $id != "NULL" )
	{
		$query .= " WHERE ODD.OD_ID = {$id}";
	}
	else
	{
		$query .= " WHERE ODD.OD_ID IS NULL";
	}
	$query .= " GROUP BY ODD.ODD_ID ORDER BY IFNULL(PM.PT_ID, 2) DESC, PM.Model, ODD.ODD_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$ODD = array();
	while( $row = mysqli_fetch_array($res) )
	{
		$format_price = ($row["Price"] != '') ? number_format($row["Price"], 0, '', ' ') : '';
		echo "<tr id='prod{$row["ODD_ID"]}' class='ord_log_row ".($row["Del"] == 1 ? 'del' : '')." {$row["is_check"]}' lnk='*ODD_ID{$row["ODD_ID"]}*'>";
		echo "<td><img src='/img/product_{$row["PT_ID"]}.png' style='height:16px'>x{$row["Amount"]}</td>";
		echo "<td><span>{$row["Model"]}<br>".($row["Size"] != "" ? "{$row["Size"]}<br>" : "").($row["Form"] != "" ? "{$row["Form"]}<br>" : "").($row["Mechanism"] != "" ? "{$row["Mechanism"]}<br>" : "")."</span></td>";
		echo "<td>{$row["patina"]}</td>";
		echo "<td class='td_step ".($confirmed == 1 ? "step_confirmed" : "")." ".(!in_array('step_update', $Rights) ? "step_disabled" : "")."'><a href='#' id='{$row["ODD_ID"]}' class='".((in_array('step_update', $Rights) and $row["Del"] == 0) ? "edit_steps" : "")." nowrap shadow{$row["Attention"]}' location='{$location}'>{$row["Steps"]}</a></td>";
		echo "<td><div class='wr_mt'>".($row["IsExist"] == 1 ? $row["clock"] : "")."<span ptid='{$row["PT_ID"]}' mtid='{$row["MT_ID"]}' class='mt{$row["MT_ID"]} {$row["removed"]} material ".((in_array('screen_materials', $Rights) and $row["Del"] == 0) ? " mt_edit " : "");
		switch ($row["IsExist"]) {
			case "0":
				echo "bg-red'>";
				break;
			case "1":
				echo "bg-yellow' title='Заказано: {$row["order_date"]} Ожидается: {$row["arrival_date"]}'>";
				break;
			case "2":
				echo "bg-green'>";
				break;
			default:
				echo "bg-gray'>";
		}
		echo "{$row["Material"]}</span>";
		echo "<input type='text' class='materialtags_{$row["PT_ID"]}' style='display: none;'>";
		echo "<input type='checkbox' style='display: none;' title='Выведен'>";
		echo "</div></td>";
		echo "<td>{$row["Shipper"]}</td>";
		echo "<td>{$row["Comment"]}</td>";
//		echo "<td class='txtright'>{$format_price}</td>";
		echo "<td>";
		
		if( $row["Del"] == 0 ) {
			echo "<button ".(($disabled or $PFI_ID or !$editable) ? 'disabled' : '')." id='{$row["ODD_ID"]}' class='edit_product{$row["PT_ID"]}' location='{$location}' title='Редактировать изделие'><i class='fa fa-pencil fa-lg'></i></button>";

			$delmessage = addslashes("Удалить {$row["Model"]}({$row["Amount"]} шт.) {$row["Form"]} {$row["Mechanism"]} {$row["Size"]}?");
			echo "<button ".(($disabled or $PFI_ID or $row["inprogress"] or !$editable) ? 'disabled' : '')." onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&del={$row["ODD_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></button>";
		}
		echo "</td></tr>";

		$ODD[$row["ODD_ID"]] = array( "amount"=>$row["Amount"], "price"=>$row["Price"], "model"=>$row["PM_ID"], "form"=>$row["PF_ID"], "mechanism"=>$row["PME_ID"], "length"=>$row["Length"], "width"=>$row["Width"], "PieceAmount"=>$row["PieceAmount"], "PieceSize"=>$row["PieceSize"], "comment"=>$row["Comment"], "material"=>$row["Material"], "shipper"=>$row["SH_ID"], "isexist"=>$row["IsExist"], "inprogress"=>$row["inprogress"], "order_date"=>$row["order_date"], "arrival_date"=>$row["arrival_date"], "patina"=>$row["patina"] );
	}
?>
	<!-- Конец таблицы изделий -->

	<!-- Таблица заготовок -->
<?
	$query = "SELECT ODB.ODB_ID
					,ODB.Amount
					,ODB.Price
					,ODB.BL_ID
					,IFNULL(BL.Name, ODB.Other) Name
					,ODB.Other
					,ODB.Comment
					,ODB.MT_ID
					,MT.PT_ID
					,IFNULL(MT.Material, '') Material
					,IF(MT.removed=1, 'removed ', '') removed
					,IF(ODB.MT_ID IS NULL, '', IFNULL(SH.Shipper, '-=Другой=-')) Shipper
					,IFNULL(MT.SH_ID, '') SH_ID
					,ODB.IsExist
					,DATE_FORMAT(ODB.order_date, '%d.%m.%Y') order_date
					,DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y') arrival_date
					,IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), '') clock
					,IF(IFNULL(SUM(ODS.WD_ID * ODS.Visible), 0) = 0, 0, 1) inprogress
					,GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width: 30px;\' title=\'(', IFNULL(WD.Name, 'Не назначен!'), ')\'><i class=\"fa fa-cog\" aria-hidden=\"true\" style=\"line-height: 1.45em;\"></i></div>')) SEPARATOR '') Steps
					,IF(SUM(ODS.Old) > 0, ' attention', '') Attention
					,IFNULL(MT.PT_ID, 0) MPT_ID
					,ODB.patina
					,ODB.Del
			  FROM OrdersDataBlank ODB
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
			  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
			  LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
			  LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
			  LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID";
	if( $id != "NULL" )
	{
		$query .= " WHERE ODB.OD_ID = {$id}";
	}
	else
	{
		$query .= " WHERE ODB.OD_ID IS NULL";
	}
	$query .= " GROUP BY ODB.ODB_ID ORDER BY ODB.ODB_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$ODB = array();
	while( $row = mysqli_fetch_array($res) )
	{
		$format_price = ($row["Price"] != '') ? number_format($row["Price"], 0, '', ' ') : '';
		echo "<tr id='blank{$row["ODB_ID"]}' class='ord_log_row ".($row["Del"] == 1 ? 'del' : '')."' lnk='*ODB_ID{$row["ODB_ID"]}*'>";
		echo "<td>{$row["Amount"]}</td>";
		echo "<td>{$row["Name"]}</td>";
		echo "<td>{$row["patina"]}</td>";
		echo "<td class='td_step ".($confirmed == 1 ? "step_confirmed" : "")." ".(!in_array('step_update', $Rights) ? "step_disabled" : "")."'><a href='#' odbid='{$row["ODB_ID"]}' class='".((in_array('step_update', $Rights) and $row["Del"] == 0) ? "edit_steps " : "")."nowrap shadow{$row["Attention"]}' location='{$location}'>{$row["Steps"]}</a></td>";
		echo "<td><div class='wr_mt'>".($row["IsExist"] == 1 ? $row["clock"] : "")."<span ptid='{$row["PT_ID"]}' mtid='{$row["MT_ID"]}' class='mt{$row["MT_ID"]} {$row["removed"]} material ".((in_array('screen_materials', $Rights) and $row["Del"] == 0) ? " mt_edit " : "");
		switch ($row["IsExist"]) {
			case "0":
				echo "bg-red'>";
				break;
			case "1":
				echo "bg-yellow' title='Заказано: {$row["order_date"]} Ожидается: {$row["arrival_date"]}'>";
				break;
			case "2":
				echo "bg-green'>";
				break;
			default:
				echo "bg-gray'>";
		}
		echo "{$row["Material"]}</span>";
		echo "<input type='text' class='materialtags_{$row["PT_ID"]}' style='display: none;'>";
		echo "<input type='checkbox' style='display: none;' title='Выведен'>";
		echo "</div></td>";
		echo "<td>{$row["Shipper"]}</td>";
		echo "<td>{$row["Comment"]}</td>";
//		echo "<td class='txtright'>{$format_price}</td>";
		echo "<td>";

		if( $row["Del"] == 0 ) {
			echo "<button ".($disabled ? 'disabled' : '')." id='{$row["ODB_ID"]}' class='edit_order_blank' location='{$location}' title='Редактировать'><i class='fa fa-pencil fa-lg'></i></a> ";

			$name = addslashes($row["Name"]);
			$delmessage = addslashes("Удалить {$name} ({$row["Amount"]} шт.)?");
			echo "<button ".(($disabled or $row["inprogress"] != 0) ? 'disabled' : '')." onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&delblank={$row["ODB_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></button>";
		}
		echo "</td></tr>";

		$ODB[$row["ODB_ID"]] = array( "amount"=>$row["Amount"], "price"=>$row["Price"], "blank"=>$row["BL_ID"], "other"=>$row["Other"], "comment"=>$row["Comment"], "material"=>$row["Material"], "shipper"=>$row["SH_ID"], "isexist"=>$row["IsExist"], "inprogress"=>$row["inprogress"], "order_date"=>$row["order_date"], "arrival_date"=>$row["arrival_date"], "MPT_ID"=>$row["MPT_ID"], "patina"=>$row["patina"] );
	}
?>
		</tbody>
	</table>
	<!-- Конец таблицы заготовок -->
</div>

<?
// Узнаем количество вложенных файлов
$query = "SELECT COUNT(1) cnt FROM OrdersAttachments WHERE OD_ID = {$id}";
$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
if( mysqli_result($result,0,'cnt') ) {
	$attacments = " (".mysqli_result($result,0,'cnt').")";
	$attacments_color = "style='background: #911;'";
}
else {
	$attacments = "";
	$attacments_color = "";
}

if( $id != "NULL" ) {
?>
&nbsp;
&nbsp;
<div class="halfblock">
	<div id="wr_order_change_log">
		<ul>
			<li><a href="#order_message">Сообщения</a></li>
			<li><a href="#order_log_table">Журнал изменений в заказе</a></li>
			<li><a href="#attachments" <?=$attacments_color?>>Файлы<?=$attacments?></a></li>
		</ul>
		<div id="order_log_table">
			<table style="width: 100%;">
				<thead>
					<tr>
					<th width="">Название</th>
					<th width="">Старое значение</th>
					<th width=""><i class='fa fa-arrow-right'></i></th>
					<th width="">Новое значение</th>
					<th width="">Дата/Время/Автор</th>
					</tr>
				</thead>
				<tbody>
		<?
			$query = "SELECT OCL.table_key
							,OCL.table_value
							,OCL.field_name
							,OCL.old_value
							,OCL.new_value
							,IFNULL(USR_Name(OCL.author), 'СИСТЕМА') Name
							,DATE_FORMAT(DATE(OCL.date_time), '%d.%m.%y') Date
							,DAY(OCL.date_time) day
							,MONTH(OCL.date_time) month
							,YEAR(OCL.date_time) year
							,TIME(OCL.date_time) Time
						FROM OrdersChangeLog OCL
						WHERE (table_key = 'OD_ID' AND table_value = {$id}) OR (table_key = 'ODD_ID' AND table_value IN (SELECT ODD_ID FROM OrdersDataDetail WHERE OD_ID = {$id})) OR (table_key = 'ODB_ID' AND table_value IN (SELECT ODB_ID FROM OrdersDataBlank WHERE OD_ID = {$id}))
						ORDER BY OCL.OCL_ID DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr class='ord_log_row' lnk='*{$row["table_key"]}{$row["table_value"]}*'>";
				if( $row["old_value"] != "" or $row["new_value"] != "" ) {
					echo "<td><b>{$row["field_name"]}</b></td>";
					echo "<td style='text-align: right;'><i style='background: #ddd; padding: 2px;'>{$row["old_value"]}</i></td>";
					echo "<td><i class='fa fa-arrow-right'></i></td>";
					echo "<td style='text-align: left;'><i style='background: #fd9; padding: 2px;'>{$row["new_value"]}</i></td>";
				}
				else {
					echo "<td colspan='4'><b>{$row["field_name"]}</b></td>";
				}
				echo "<td class='nowrap'><b>{$row["day"]}&nbsp;{$MONTHS_DATE[$row["month"]]}&nbsp;{$row["year"]}</b><br>{$row["Time"]}<br>{$row["Name"]}</td>";
				echo "</tr>";
			}
		?>
				</tbody>
			</table>
		</div>
		<div id="order_message">
<!--			<p style='color: #911;'>Занимательный факт:</p>-->
			<p style='color: #911;'>Если нажать на красный конверт слева от сообщения, то конверт станет зеленым - это означает, что сообщение прочитано. Оно так же исчезнет из уведомлений в верхнем-левом углу и там остануться только самые актуальные сообщения.</p>
			<table style="width: 100%;">
				<thead>
					<tr>
					<th width="40"><a href="#" class="add_message_btn" title="Добавить сообщение"><i class="fa fa-plus-square fa-2x" style="color: green;"></i></a></th>
					<th width="">Сообщение</th>
					<th width="">Дата/Время/Автор</th>
					</tr>
				</thead>
				<tbody>
		<?
			$query = "SELECT OM.OM_ID
							,OM.Message
							,OM.priority
							,USR_Name(OM.author) Name
							,DATE_FORMAT(DATE(OM.date_time), '%d.%m.%y') Date
							,TIME(OM.date_time) Time
							,IFNULL(USR_Name(OM.read_user), '') read_user
							,DATE_FORMAT(DATE(OM.read_time), '%d.%m.%y') read_date
							,TIME(OM.read_time) read_time
							,OM.destination
						FROM OrdersMessage OM
						WHERE OM.OD_ID = {$id}
						ORDER BY OM.OM_ID DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				if( $row["read_user"] != '' ) {
					if( (in_array('order_add_confirm', $Rights) and $row["destination"] == 1) or (!in_array('order_add_confirm', $Rights) and $row["destination"] == 0) ) {
						$letter_btn = "<a href='#' class='read_message_btn' id='msg{$row["OM_ID"]}' val='1'><i class='fa fa-envelope fa-2x' aria-hidden='true' style='color: green;' title='Прочитано: {$row["read_user"]} {$row["read_date"]} {$row["read_time"]}'></a>";
					}
					else {
						$letter_btn = "<i class='fa fa-envelope fa-2x' aria-hidden='true' style='color: green; opacity: .3;' title='Прочитано: {$row["read_user"]} {$row["read_date"]} {$row["read_time"]}'>";
					}
				}
				else {
					if( (in_array('order_add_confirm', $Rights) and $row["destination"] == 1) or (!in_array('order_add_confirm', $Rights) and $row["destination"] == 0) ) {
						$letter_btn = "<a href='#' class='read_message_btn' id='msg{$row["OM_ID"]}' val='0'><i class='fa fa-envelope fa-2x' aria-hidden='true' style='color: red;'></a>";
					}
					else {
						$letter_btn = "<i class='fa fa-envelope fa-2x' aria-hidden='true' style='color: red; opacity: .3;'>";
					}
				}
				echo "<tr".($row["priority"] ? " style='font-weight: bold;'" : "").">";
				echo "<td>{$letter_btn}</td>";
				echo "<td>".(src_url($row["Message"]))."</td>";
				echo "<td>{$row["Date"]}<br>{$row["Time"]}<br>{$row["Name"]}</td>";
				echo "</tr>";
			}
		?>
				</tbody>
			</table>
		</div>
		<div id="attachments">
			<form action=upload.php method=post enctype=multipart/form-data>
				<input type="hidden" name="odid" value="<?=$id?>">
				<input type=file name=uploadfile>
				<input type="text" name="comment" placeholder="Комментарий">
				<input type=submit value=Загрузить>
			</form>
			<p style="color: #911;">Файлы хранятся 1 год с момента загрузки.</p>
			<table style="width: 100%;">
				<thead>
					<tr>
					<th width="">Файл</th>
					<th width="">Комментарий</th>
					<th width=""></th>
					</tr>
				</thead>
				<tbody>
				<?
				$query = "SELECT OA.filename, OA.comment, OA.OA_ID
							FROM OrdersAttachments OA
							WHERE OA.OD_ID = {$id}
							ORDER BY OA.OA_ID DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<tr>";
					echo "<td><a href='/uploads/{$row["filename"]}' target='_blank' class='button'>{$row["filename"]}</a></td>";
					echo "<td>{$row["comment"]}</td>";

					$delmessage = addslashes("Удалить файл <b>{$row["filename"]}</b> ?");
					echo "<td><button onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&delfile={$row["OA_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></button></td>";

					echo "</tr>";
				}
				?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<?
}
?>
<!-- Форма добавления сообщения к заказу -->
<div id='add_message' title='Сообщение' style='display:none'>
	<form method='post' action='<?=$location?>&add_message=1'>
		<fieldset>
			<div>
				<label for="message">Текст сообщения:</label><br>
				<textarea name="message" id="message" style="width: 100%; height: 100px;"></textarea>
			</div>
			<br>
			<div>
				<label for="priority">Приоритет:</label><br>
				<div id='priority' class='btnset'>
					<input type='radio' id='reg_msg' name='priority' value='0' checked>
						<label for='reg_msg'>Обычное</label>
					<input type='radio' id='imp_msg' name='priority' value='1'>
						<label for='imp_msg'>Срочное</label>
				</div>
			</div>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы добавления сообщения к заказу -->

<script>
	$(document).ready(function(){
//		// Select2 для выбора салона
//		$('select[name="Shop"]').select2({
//			placeholder: "Выберите салон",
//			language: "ru"
//		});

		// Сабмит формы заказа при изменении
		$('#order_form input, #order_form select, #order_form textarea').change(function(){
			$('#order_form').submit();
		});

		// Отмечаем письмо как прочитанное аяксом
		$('.read_message_btn').click(function() {
			var id = $(this).attr('id');
			id = id.replace('msg', '');
			var val = $(this).attr('val');
			$.ajax({ url: "ajax.php?do=read_message&om_id="+id+"&val="+val, dataType: "script", async: false });
		});

		// Кнопка добавления сообщения к заказу
		$('.add_message_btn').click( function() {
			$('#add_message').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
		});

		$( "#wr_order_change_log" ).tabs();

		<?
		// Переключаемся на вкладку
		if( isset($_GET["tabs"]) ) {
			echo "$( '#wr_order_change_log' ).tabs( 'option', 'active', {$_GET["tabs"]} );";
		}
		?>

		<?
		if( !in_array('order_add_confirm', $Rights) ) {
			echo "$( '#IsPainting input' ).button( 'option', 'disabled', true );";
		}
		?>

		$('.ord_log_row').hover(function() {
			var lnk = $(this).attr('lnk');
			$('.ord_log_row[lnk="'+lnk+'"] td').css('background', '#ffa');
		}, function() {
			var lnk = $(this).attr('lnk');
			$('.ord_log_row[lnk="'+lnk+'"] td').css('background', 'none');
		});

		$('.attention img').show();

		odd = <?= json_encode($ODD); ?>;
		odb = <?= json_encode($ODB); ?>;
	});
</script>

<?
	include "footer.php";
?>
