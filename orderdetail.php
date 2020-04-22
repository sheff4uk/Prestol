<?
	include "config.php";
	include "checkrights.php";

	if( isset($_GET["id"]) )
	{
		// Проверка прав на доступ к экрану
		// Проверка города
		$query = "
			SELECT OD.OD_ID
				,OD.Code
				,IFNULL(YEAR(OD.StartDate), 0) start_year
				,IFNULL(MONTH(OD.StartDate), 0) start_month
				,OD.is_lock
				,OD.confirmed
				,IF(OD.DelDate IS NULL, 0, 1) Del
				,IF(OD.ReadyDate IS NULL, 0, 1) Archive
				,IF(OD.SH_ID IS NULL, 1, 0) Free
				,OD.IsPainting
			FROM OrdersData OD
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) AND OD_ID = {$_GET["id"]}
				".($USR_Shop ? "AND (SH.SH_ID IN ({$USR_Shop}) OR (OD.StartDate IS NULL AND SH.retail = 1) OR OD.SH_ID IS NULL)" : "")."
				".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "")
		;
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$OD_ID = mysqli_result($res,0,'OD_ID');
		$Code = mysqli_result($res,0,'Code');
		$Del = mysqli_result($res,0,'Del');
		$Archive = mysqli_result($res,0,'Archive');
		$Free = mysqli_result($res,0,'Free');
		$is_lock = mysqli_result($res,0,'is_lock');
		$start_year = mysqli_result($res,0,'start_year');
		$start_month = mysqli_result($res,0,'start_month');
		$confirmed = mysqli_result($res,0,'confirmed');
		$IsPainting = mysqli_result($res,0,'IsPainting');
		// Категория набора (в работе/свободные/отгруженные/удаленные)
		if ($Del) {
			$arch = 3;
		}
		elseif ($Archive) {
			$arch = 2;
		}
		elseif ($Free) {
			$arch = 1;
		}
		else {
			$arch = 0;
		}

		// В заголовке страницы выводим код набора
		$title = $Code;
		include "header.php";

		// Запрет на редактирование
		$disabled = !( in_array('order_add', $Rights) and ($confirmed == 0 or in_array('order_add_confirm', $Rights)) and !$is_lock and !$Archive );

		if( !$OD_ID and (int)$_GET["id"] > 0) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$id = (int)$_GET["id"];
		$location = "orderdetail.php?id=".$id;
	}
	else
	{
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}

	// Обновление основной информации о наборе
	if( isset($_GET["order_update"]) )
	{
		// Узнаем, была ли дата заключения договора
		$query = "
			SELECT OD.StartDate
			FROM OrdersData OD
			WHERE OD.OD_ID = {$id}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		$old_StartDate = $row["StartDate"];

		$StartDate = $_POST["StartDate"] ? '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'' : "NULL";
		// Если продали с выставки, то присваиваем дату сдачи
		if (!$old_StartDate and $_POST["StartDate"]) {
			$_POST["EndDate"] = $_SESSION["end_date"];
		}
		$EndDate = $_POST["EndDate"] ? '\''.date( "Y-m-d", strtotime($_POST["EndDate"]) ).'\'' : "NULL";

		$chars = array("+", " ", "(", ")"); // Символы, которые требуется удалить из строки с телефоном
		$mtel = $_POST["mtel"] ? '\''.str_replace($chars, "", $_POST["mtel"]).'\'' : 'NULL';
		$Shop = $_POST["Shop"] > 0 ? $_POST["Shop"] : "NULL";

		// Обработка строк
		$ClientName = convert_str($_POST["ClientName"]);
		$ClientName = mysqli_real_escape_string($mysqli, $ClientName);
		$OrderNumber = convert_str($_POST["OrderNumber"]);
		$OrderNumber = mysqli_real_escape_string($mysqli, $OrderNumber);
		$Comment = convert_str($_POST["Comment"]);
		$Comment = mysqli_real_escape_string($mysqli, $Comment);
		$address = convert_str($_POST["address"]);
		$address = mysqli_real_escape_string($mysqli, $address);

		$query = "
			UPDATE OrdersData
			SET author = {$_SESSION['id']}
				".(isset($_POST["ClientName"]) ? ",CLientName = '$ClientName'" : "")."
				".(isset($_POST["mtel"]) ? ",mtel = $mtel" : "")."
				".(isset($_POST["address"]) ? ",address = '$address'" : "")."
				".(isset($_POST["StartDate"]) ? ",StartDate = $StartDate" : "")."
				".(isset($_POST["EndDate"]) ? ",EndDate = $EndDate" : "")."
				".(isset($_POST["Shop"]) ? ",SH_ID = $Shop" : "")."
				".(isset($_POST["OrderNumber"]) ? ",OrderNumber = '$OrderNumber'" : "")."
				#".((isset($_POST["Color"]) and isset($_POST["clear"])) ? ",CL_ID = $cl_id" : "")."
				".(isset($_POST["Comment"]) ? ",Comment = '$Comment'" : "")."
			WHERE OD_ID = {$id}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = mysqli_error( $mysqli );
		}
		else {
			// Узнаем изменилась ли запись
			if( mysqli_affected_rows( $mysqli ) ) {
				$_SESSION["success"][] = "Данные сохранены.";
			}
			else {
				$_SESSION["error"][] = "Данные не были сохранены.";
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$id.'">');
		die;
	}

	// Обновление цвета покраски
	if( isset($_GET["paint_color"]) )
	{
		// Если набор уже покрашен - предупреждение, что нельзя изменить цвет
		if ($IsPainting == 3) {
			$_SESSION["error"][] = "Набор покрашен! Изменить цвет не возможно.";
			exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		}

		$color = convert_str($_POST["color"]);
		$color = mysqli_real_escape_string($mysqli, $color);
		$NCS = $_POST["NCS"];
		$clear = $_POST["clear"];

		// Если цвет не указан - выводим предупреждение
		if( $color == '' and $NCS == 0) {
			$_SESSION["error"][] = "Пожалуйста укажите цвет краски.";
		}
		else {
			$query = "
				INSERT INTO Colors
				SET
					color = '{$color}',
					NCS_ID = {$NCS},
					clear = {$clear},
					count = 0
				ON DUPLICATE KEY UPDATE
					count = count + 1
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$cl_id = mysqli_insert_id( $mysqli );

			$query = "
				UPDATE OrdersData
				SET author = {$_SESSION['id']}
					,CL_ID = $cl_id
				WHERE OD_ID = {$id}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$id.'">');
		die;
	}

	// Отмена лакировки
	if( isset($_GET["paint_reject"]) ) {
		// Если набор уже покрашен - предупреждение, что нельзя отменить лакировку
		if ($IsPainting == 3) {
			$_SESSION["error"][] = "Набор покрашен! Отменить лакировку не возможно.";
			exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		}

		$query = "
			UPDATE OrdersData
			SET author = {$_SESSION['id']}
				,CL_ID = NULL
			WHERE OD_ID = {$id}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$id.'">');
		die;
	}

	// Добавление в базу нового изделия. Заполнение этапов.
	if ( isset($_GET["add"]) and !$disabled and !$Del)
	{
		// Если набор уже покрашен - предупреждение, что нельзя добавить изделие
		if ($IsPainting == 3) {
			$_SESSION["error"][] = "Набор покрашен! Добавление изделий не возможно.";
			exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		}

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

		// Добавление в базу нового изделия
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
		$IsExist = isset($_POST["IsExist"]) ? "{$_POST["IsExist"]}" : "NULL";
		$Shipper = $_POST["Shipper"] ? $_POST["Shipper"] : "NULL";
		$ptn = $_POST["ptn"];
		$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
		$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";
		$sidebar = isset($_POST["sidebar"]) ? $_POST["sidebar"] : "NULL";
		$PVC_ID = $_POST["PVC_ID"] ? $_POST["PVC_ID"] : "NULL";
		// Обработка строк
		$Material = convert_str($_POST["Material"]);
		$Material = mysqli_real_escape_string($mysqli, $Material);
		$Comment = convert_str($_POST["Comment"]);
		$Comment = mysqli_real_escape_string($mysqli, $Comment);
		$Comment = ($Comment != '') ? "'$Comment'" : "NULL";

		// Сохраняем в таблицу материалов полученный материал и узнаем его ID
		if( $Material != '' ) {
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

		$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, BL_ID, Other, PVC_ID, sidebar, Length, Width, PieceAmount, PieceSize, piece_stored, PF_ID, PME_ID, box, MT_ID, IsExist, Amount, Comment, order_date, arrival_date, author, ptn)
				  VALUES (IF({$id} > 0, {$id}, NULL), {$Model}, {$Blank}, {$Other}, {$PVC_ID}, {$sidebar}, {$Length}, {$Width}, {$PieceAmount}, {$PieceSize}, {$piece_stored}, {$Form}, {$Mechanism}, {$box}, {$mt_id}, {$IsExist}, {$_POST["Amount"]}, {$Comment}, {$OrderDate}, {$ArrivalDate}, {$_SESSION['id']}, $ptn)";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		if ($id > 0) {
			$odd_id = mysqli_insert_id( $mysqli );

			$_SESSION["odd_id"] = $odd_id; // Cохраняем в сессию id вставленной записи

			// Вычисляем и записываем стоимость по прайсу
			$query = "CALL Price({$odd_id})";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			exit ('<meta http-equiv="refresh" content="0; url='.$location.'#'.$odd_id.'">');
		}
		else {
			exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'">');
		}
		die;
	}
	else {
		$odd_id = isset($_SESSION["odd_id"]) ? $_SESSION["odd_id"] : ""; // Читаем из сессии id вставленной записи
		unset($_SESSION["odd_id"]); // Очищаем сессию
	}

	// Удаление изделия
	if( isset($_GET["del"]) and !$disabled and !$Del )
	{
		$odd_id = (int)$_GET["del"];

		// Узнаём статус лакировки набора
		$query = "
			SELECT OD.IsPainting
			FROM OrdersData OD
			JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.ODD_ID = {$odd_id}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$IsPainting = mysqli_result($res,0,'IsPainting');

		// Узнаем приступили ли к производству изделия
		$query = "
			SELECT IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
			FROM OrdersDataDetail ODD
			LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
			WHERE ODD.ODD_ID = {$odd_id}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$inprogress = mysqli_result($res,0,'inprogress');

		if ($IsPainting == 3) { // Если набор покрашен
			$_SESSION["error"][] = "Набор покрашен. Удаление не возможно.";
		}
		if ($inprogress > 0) { // Если изделие в работе
			$_SESSION["error"][] = "Изделие дано в работу. Удаление не возможно.";
		}

		if (count($_SESSION["error"]) == 0) { // Если нет препятствий то удаляем
			// Создание копии набора
			$query = "INSERT INTO OrdersData(PFI_ID, Code, SH_ID, ClientName, KA_ID, mtel, address, AddDate, StartDate, EndDate, DelDate, OrderNumber, CL_ID, IsPainting, WD_ID, Comment, IsReady, author, confirmed)
			SELECT PFI_ID, Code, SH_ID, ClientName, KA_ID, mtel, address, AddDate, StartDate, EndDate, NOW(), OrderNumber, CL_ID, IF(IsPainting = 2, 1, IsPainting), WD_ID, Comment, IsReady, {$_SESSION['id']}, confirmed FROM OrdersData WHERE OD_ID = {$id}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$newOD_ID = mysqli_insert_id($mysqli);

			// Записываем в журнал событие разделения набора удаление дубликата
			$query = "INSERT INTO OrdersChangeLog SET OD_ID = {$id}, OFN_ID = 1, old_value = '{$newOD_ID}', new_value = '', author = {$_SESSION['id']}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$query = "INSERT INTO OrdersChangeLog SET OD_ID = {$newOD_ID}, OFN_ID = 1, old_value = '{$id}', new_value = '', author = {$_SESSION['id']}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$query = "INSERT INTO OrdersChangeLog SET OD_ID = {$newOD_ID}, OFN_ID = 18, old_value = '', new_value = '', author = {$_SESSION['id']}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			// Переносим удаляемое изделие в отделенный контейнер
			$query = "UPDATE OrdersDataDetail SET OD_ID = {$newOD_ID} WHERE ODD_ID = {$odd_id}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$_SESSION["success"][] = "Изделие отделено от набора и перемещено в <a href='/orderdetail.php?id={$newOD_ID}' target='_blank'>удалённые</a>.";
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Удаление файла
	if( isset($_GET["delfile"]) and !$disabled and !$Del ) {
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
		$Message = convert_str($_POST["message"]);
		$Message = mysqli_real_escape_string($mysqli, $Message);
		$query = "INSERT INTO OrdersMessage
					 SET OD_ID = {$id}
						,Message = '{$Message}'
						,priority = {$_POST["priority"]}
						,author = {$_SESSION['id']}
						,read_time = DATE_ADD(NOW(), INTERVAL 1 MONTH)
						,destination = ".( in_array('order_add_confirm', $Rights) ? "0" : "1" );
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = mysqli_error( $mysqli );
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	include "forms.php";

	echo "<p><a href='/?archive={$arch}#ord{$_GET["id"]}' class='button'><< На главную</a></p>";

	$query = "
		SELECT OD.OD_ID
			,OD.Code
			,OD.ClientName
			,IFNULL(KA1.Naimenovanie, KA.Naimenovanie) Naimenovanie
			,IFNULL(KA1.saldo, KA.saldo) saldo
			,IFNULL(SH.KA_ID, OD.KA_ID) KA_ID
			,OD.mtel
			,OD.address
			,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
			,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
			,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
			,DATE_FORMAT(OD.ReadyDate, '%d.%m.%y') ReadyDate
			,DATE_FORMAT(OD.DelDate, '%d.%m.%y') DelDate
			,OD.StartDate StD
			,IF(OD.ReadyDate, DATE_FORMAT(OD.EndDate, '%d.%m.%y'), '') format_EndDate
			,IF(OD.EndDate AND OD.ReadyDate, IF(DATEDIFF(OD.EndDate, OD.ReadyDate) <= 7, IF(DATEDIFF(OD.EndDate, OD.ReadyDate) <= 0, 'bg-red', 'bg-yellow'), 'bg-green'), '') date_diff_color
			,IF((SH.retail AND OD.StartDate IS NULL), '<br><b style=\'background-color: silver;\'>Выставка</b>', '') showing
			,IFNULL(OD.SH_ID, 0) SH_ID
			,OD.OrderNumber
			,Color(OD.CL_ID) Color
			,CL.color
			,CL.clear
			,IFNULL(CL.NCS_ID, 0) NCS_ID
			,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
			,IF(OD.IsPainting = 3, CONCAT(WD.Name, IF(OD.patina_WD_ID IS NOT NULL, CONCAT(' + ', pWD.Name), '')), '') Name
			,OD.Comment
			,IF(OD.SH_ID IS NULL, '#999', IFNULL(CT.Color, '#fff')) CTColor
			,IFNULL(SH.retail, 0) retail
			,SH.CT_ID
			,IFNULL(OD.SHP_ID, 0) SHP_ID
			,IF(PFI.rtrn = 1, NULL, OD.PFI_ID) PFI_ID
			,PFI.count
			,PFI.platelshik_id
			,Ord_price(OD.OD_ID) - Ord_discount(OD.OD_ID) Price
			,Ord_opt_price(OD.OD_ID) opt_price
			,Payment_sum(OD.OD_ID) payment_sum
		FROM OrdersData OD
		LEFT JOIN Kontragenty KA ON KA.KA_ID = OD.KA_ID
		LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
		LEFT JOIN WorkersData WD ON WD.WD_ID = OD.WD_ID
		LEFT JOIN WorkersData pWD ON pWD.WD_ID = OD.patina_WD_ID
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		LEFT JOIN Kontragenty KA1 ON KA1.KA_ID = SH.KA_ID
		LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
		WHERE OD.OD_ID = {$id}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);

	$Code = $row['Code'];
	$ClientName = $row['ClientName'];
	$Naimenovanie = $row['Naimenovanie'];
	$saldo = $row['saldo'];
	$KA_ID = $row['KA_ID'];
	$mtel = $row['mtel'];
	$address = $row['address'];
	$AddDate = $row['AddDate'];
	$StartDate = $row['StartDate'];
	$EndDate = $row['EndDate'];
	$ReadyDate = $row['ReadyDate'];
	$DelDate = $row['DelDate'];
	$StD = $row['StD'];
	$format_EndDate = $row['format_EndDate'];
	$date_diff_color = $row['date_diff_color'];
	$showing = $row['showing'];
	$SH_ID = $row['SH_ID'];
	$OrderNumber = $row['OrderNumber'];
	$Color = $row['Color'];
	$color = $row['color'];
	$clear = $row['clear'];
	$NCS_ID = $row['NCS_ID'];
	$IsPainting = $row['IsPainting'];
	$Name = $row['Name'];
	$Comment = $row['Comment'];
	$CTColor = $row['CTColor'];
	$retail = $row['retail'];
	$CT_ID = $row['CT_ID'];
	$SHP_ID = $row['SHP_ID'];
	$PFI_ID = $row['PFI_ID'];
	$count = $row['count'];
	$platelshik_id = $row['platelshik_id'];
	$format_price = number_format($row['Price'], 0, '', ' ');
	$format_opt_price = number_format($row["opt_price"], 0, '', ' ');
	$format_payment = number_format($row["payment_sum"], 0, '', ' ');

	// Находим наборы из группы
	if ($StartDate and $ClientName) {
		$GroupOrders = "";
		$query = "
			SELECT OD.Code
				,OD.OD_ID
			FROM OrdersData OD
			JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
			WHERE OD.DelDate IS NULL AND OD.StartDate = '{$StD}' AND OD.ClientName LIKE '{$ClientName}' AND OD.SH_ID = {$SH_ID} AND IFNULL(OD.PFI_ID, 0) = ".($PFI_ID ? $PFI_ID : 0)." AND OD.OD_ID != {$id}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$GroupOrders .= "<i class='fas fa-plus'></i><a href='?id={$row["OD_ID"]}' title='Группа из нескольких наборов'><b class='code'>{$row["Code"]}</b></a><br>";
		}
	}

	// Если пользователю доступен только один салон в регионе или оптовик или свободный набор и нет админских привилегий, то нельзя редактировать общую информацию набора.
	$editable = (!($USR_Shop and $SH_ID and !in_array($SH_ID, explode(",", $USR_Shop))) and !($USR_KA and $SH_ID and $USR_KA != $KA_ID) and ($SH_ID or in_array('order_add_confirm', $Rights)));
?>
	<form method='post' id='order_form' action='<?=$location?>&order_update'>
	<table class="main_table">
		<thead>
		<tr class='nowrap'>
			<th width="90">Код набора</th>
			<?
			if ($retail and $StartDate) {
				echo "<th width='125'>Клиент<br>Квитанция<br>Телефон</th>";
				echo "<th width='20%'>Адрес доставки</th>";
			}
			?>
			<th width="95">Договор от</th>
			<?= ($ReadyDate ? "<th width='95'>Отгружено<br><i style='font-size: .8em;'>Сдача</i></th>" : ($DelDate ? "<th width='95'>Удалено</th>" : ($showing ? "" : "<th width='95'>Сдача</th>"))) ?>
			<th width="125">Подразделение</th>
			<th width="170">Цвет краски <i class="fa fa-question-circle" html="<b>Цветовой статус лакировки:</b><br><span class='empty'>Покраска не требуется</span><br><span class='notready'>Не дано в покраску</span><br><span class='inwork'>Дано в покраску</span><br><span class='ready'>Покрашено</span>"></i></th>
			<th width="40">Принят</th>
			<th width="65">Стоимость<br>набора</th>
			<?
			// Если розница и набор свой - видна оплата
			if( $retail and $editable ) {
				echo "<th width='65'>Оплата</th>";
			}
			?>
			<th width="20%">Примечание</th>
			<th width="70">Действие</th>
		</tr>
		</thead>
		<tbody>
		<tr class='ord_log_row' lnk='*OD_ID<?=$id?>*' id='ord<?=$id?>'>
			<td class="nowrap"><b class='code' style='font-size: 1.6em;'><?=$Code?></b><br><?=$GroupOrders?><br><?=$AddDate?></td>
<?
		if ($retail and $StartDate) {
			echo "
				<td>
					<input type='text' class='clienttags' name='ClientName' style='width: 120px;' value='$ClientName' ".((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")." placeholder='ФИО'>
					<br>
					<input type='text' name='OrderNumber' style='width: 120px;' value='$OrderNumber' ".((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")." autocomplete='off' placeholder='Квитанция'>
					<br>
					<input type='text' name='mtel' id='mtel' style='width: 120px;' value='$mtel' ".((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")." autocomplete='off' placeholder='Моб. телефон'>
				</td>
				<td>
					<textarea name='address' rows='6' ".((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")." style='width: 100%;'>$address</textarea>
				</td>
			";
		}

		// Если набор в накладной - под датой продажи ссылка на накладную
		if( $PFI_ID ) {
			$invoice = "<br><b><a href='open_print_form.php?type=invoice&PFI_ID={$PFI_ID}&number={$count}' target='_blank'>Накладная</a></b>";
			$title="Чтобы стереть дату продажи анулируйте накладную в Сверках, затем перейдите в Реализацию и нажмите на символ ладошки справа.";
		}
		else {
			$invoice = "";
			$title = "Чтобы стереть дату продажи перейдите в реализацию и нажмите на символ ладошки справа.";
		}
		if (in_array('order_add', $Rights) and !$is_lock and !$Del and $retail and $editable) {
			echo "<td><input type='text' name='StartDate' class='date' value='{$StartDate}' date='{$StartDate}' readonly ".( $StartDate ? "title='{$title}'" : "" ).">{$invoice}{$showing}</td>";
		}
		else {
			echo "<td><input type='text' name='StartDate' class='date' value='{$StartDate}' date='{$StartDate}' disabled readonly>{$invoice}{$showing}</td>";
		}
		if ($showing and !$ReadyDate and !$DelDate) {
			echo "";
		}
		else {
			echo "<td style='text-align: center;'>";
			echo ($ReadyDate ? "<font class='{$date_diff_color}'>{$ReadyDate}</font><br><i style='font-size: .8em;'>{$format_EndDate}</i>" : ($DelDate ? $DelDate : "<input type='text' name='EndDate' class='date' value='{$EndDate}' autocomplete='off' ".((!$disabled and !$Del and $editable and $SH_ID and in_array('order_add_confirm', $Rights)) ? "" : "disabled").">"));
				// Если отгружен, не в накладной и есть право отгружать - показываем кнопку отмены отгрузки
				if ($ReadyDate and !$PFI_ID and in_array('order_ready', $Rights)) {
					echo "<br><a href='#' class='undo_shipping' od_id='{$id}' title='Отменить отгрузку'><i style='color:#333;' class='fas fa-flag-checkered fa-2x'></i></a> ";
				}
			echo "</td>";
		}
?>

		<td style="background: <?=$CTColor?>;">
		<div class='shop_cell'>
			<select name='Shop' class='select_shops' <?=((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")?> style="width: 100%;">
				<!--Список салонов выводится аяксом ниже-->
			</select>
			<?
				if ($Naimenovanie) {
					$saldo_format = number_format($saldo, 0, '', ' ');
					echo "<n class='ul'>{$Naimenovanie}</n><br>";
					echo "Сальдо: <b style='color: ".(($saldo < 0) ? "#E74C3C;" : "#16A085;")."'>{$saldo_format}</b><br>";
					echo "<a href='/bills.php?payer={$KA_ID}' target='_blank'>Счета</a>&nbsp;&nbsp;<a href='/sverki.php?payer={$KA_ID}' target='_blank'>Сверки</a>";
				}
			?>
			</div>
		</td>

<?
		switch ($IsPainting) {
			case 0:
				$class = "empty";
				break;
			case 1:
				$class = "notready";
				break;
			case 2:
				$class = "inwork";
				break;
			case 3:
				$class = "ready";
				break;
		}
		$delmessage = addslashes("Покраска не требуется?");
		echo "
			<td val='{$IsPainting}' class='painting_cell ".(( in_array('order_add_confirm', $Rights) and !$Archive and $Del == 0 and $IsPainting != 0 ) ? "painting " : "")." {$class}'>
				<div class='painting_workers'>{$Name}</div>
				<p>{$Color}</p>
				<div style='background: lightgrey; cursor: auto; ".((!$disabled and !$Del and $editable) ? "" : "display: none;")."'>
					<a class='button' id='paint_color_btn' color='{$color}' clear='{$clear}' NCS_ID='{$NCS_ID}' title='Редактировать цвет покраски'><i class='fa fa-pencil-alt fa-lg'></i></a>
					".($Color ? "<button class='button' title='Отменить покраску' onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&paint_reject\")) return false;'><i class='fa fa-times fa-lg'></i></button>" : "")."
				</div>
			</td>
		";

		// Если набор принят
		if( $confirmed == 1 ) {
			$class = 'confirmed';
			//$title = 'Принят в работу';
		}
		else {
			$class = 'not_confirmed';
			//$title = 'Не принят в работу';
		}
		if( in_array('order_add_confirm', $Rights) and !$Archive and $Del == 0 ) {
			$class = $class." edit_confirmed";
		}
		echo "<td val='{$confirmed}' class='{$class}' style='text-align: center;'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";

		// СУММА ЗАКАЗА
		// Если свободные - ячейка пуста
		if( $SH_ID == 0 ) {
			$price = "";
		}
		// Если редактируемый и пользователь не оптовик и есть разрешение на добавление набора, то цена редактируемая
		elseif( $editable and !$USR_KA and in_array('order_add', $Rights) ) {
			// Если набор в накладной - сумма набора ведет в накладную, цена не редактируется
			if( $row["PFI_ID"] ) {
				// Исключение для Клена
				if ($row["SH_ID"] == 36) {
					$price = "<button style='width: 100%;' class='update_price_btn button nowrap txtright' location='{$location}'>{$format_price}</button><br><a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><b title='Стоимость по накладной'>{$format_opt_price}<i class='fa fa-question-circle'></i></b></a>";
				}
				else {
					$price = "<a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><b title='Стоимость по накладной'>{$format_price}<i class='fa fa-question-circle'></i></b></a>";
				}
			}
			else {
				$price = "<button style='width: 100%;' class='update_price_btn button nowrap txtright' location='{$location}'>{$format_price}</button>";
			}
		}
		else {
			$price = "<p class='price'>$format_price</p>";
		}
		echo "<td class='txtright'>{$price}</td>";

		// Если розница и набор свой - можно вносить оплату
		if( $retail and $editable ) {
			echo "<td><button ".(( $KA_ID or !(in_array('selling_all', $Rights) or in_array('selling_city', $Rights)) ) ? "disabled" : "")." style='width: 100%;' class='add_payment_btn button nowrap txtright' location='{$location}'>{$format_payment}</button></td>";
		}
?>

		<td>
			<? if (!in_array('order_add_confirm', $Rights)) echo "<i class='fa fa-question-circle' title='Обо всех дополнительных особенностях набора сообщайте через кнопку \"Сообщение на производство\".'></i>"; ?>
			<textarea name='Comment' rows='6' <?=( (in_array('order_add_confirm', $Rights) and !$Del and $editable) ? "" : "disabled" )?> style='width: 100%;'><?=$Comment?></textarea>
		</td>
		<td style="text-align: center;">

<?
	if ($editable) {
		// Если набор не заблокирован и не удален, то показываем кнопку разделения
		if( !$is_lock and in_array('order_add', $Rights) and !$Del ) {
			echo "<a href='#' id='{$id}' class='order_cut' title='Разделить набор' location='{$location}'><i class='fa fa-sliders-h fa-2x'></i></a><br>";
		}

		// Если розничный набор и не удален и есть права показываем кнопку перехода в реализацию
		if( $retail and !$Del and (in_array('selling_all', $Rights) or in_array('selling_city', $Rights)) ) {
			echo "<a href='/selling.php?CT_ID={$CT_ID}&year={$start_year}&month={$start_month}#ord{$id}' title='Перейти в реализацию'><i class='fas fa-money-bill-alt fa-2x' aria-hidden='true'></i></a><br>";
		}
		// Если набор в отгрузке - показываем кнопку перехода в отгрузку
		if( $SHP_ID ) {
			echo "<a href='/?shpid={$SHP_ID}#ord{$id}' title='Перейти в отгрузку'><i class='fa fa-truck fa-2x' aria-hidden='true'></i></a><br>";
		}
		elseif (!$disabled and !$PFI_ID) {
			if ($Del) {
				echo "<a href='#' class='undo_deleting' od_id='{$id}' ord_scr='1' title='Восстановить'><i class='fas fa-undo-alt fa-2x'></i></a><br>";
			}
			else {
				echo "<a href='#' class='deleting' od_id='{$id}'  ord_scr='1' m_type='".(in_array('order_add_confirm', $Rights) ? "1" : "0")."' title='Удалить набор'><i class='fa fa-times fa-2x'></i></a><br>";
			}
		}
	}

	// Если есть право на добавление набора - показываем кнопку клонирования
	if( in_array('order_add', $Rights) ) {
		echo "<a href='#' class='clone' od_id='{$id}' title='Клонировать набор'><i class='fa fa-clone fa-2x' aria-hidden='true'></i></a><br>";
	}
?>
			</td>
		</tr>
		</tbody>
	</table>
	</form>

	<script>
		$(function() {
			// Выводится выпадающий список салонов аяксом
			$.ajax({ url: "ajax.php?do=create_shop_select&OD_ID=<?=$id?>&SH_ID=<?=$SH_ID?>", dataType: "script", async: false });

			$( 'input[name="StartDate"]' ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );

			if("<?=$StartDate?>") {
				$("input[name='StartDate']").hover(function() {
					$("i.fa-money").effect( 'shake', 1000 );
				});
			}
		});
	</script>
<?
		if ( $confirmed == 1 and !$ReadyDate ) {
			echo "<div id='order_in_work_label' style='position: absolute; top: 77px; left: 140px; font-weight: bold; color: green; font-size: 1.2em;'>Набор принят в работу.</div>";
		}
		if ( $is_lock == 1 ) {
			echo "<div style='position: absolute; top: 77px; left: 340px; font-weight: bold; color: green; font-size: 1.2em;'>Месяц в реализации закрыт (изменения ограничены).</div>";
		}
		if ( $Del == 1 ) {
			echo "<div style='position: absolute; top: 200px; font-weight: bold; color: #911; font-size: 5em; opacity: .3; border: 5px solid; transform: rotate(-45deg);'>Набор удалён</div>";
		}
?>
<div class="halfblock">
	<p>
		<button <?=(($disabled or $Del or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_product1' odid='<?=$id?>'>Добавить стулья</button>
		<button <?=(($disabled or $Del or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_product2' odid='<?=$id?>'>Добавить столы</button>
		<button <?=(($disabled or $Del or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_product0' odid='<?=$id?>'>Добавить заготовки/прочее</button>
	</p>

	<!-- Таблица изделий -->
	<table class="main_table">
		<thead>
		<tr>
			<th width="55"></th>
			<th width="40">Кол-во</th>
			<th width="130">Этапы</th>
			<th width="">Материал <i class="fa fa-question-circle" html="<b>Цветовой статус наличия:</b><br><span class='bg-gray'>Неизвестно</span><br><span class='bg-red'>Нет</span><br><span class='bg-yellow'>Заказано</span><br><span class='bg-green'>В наличии</span><br><span class='bg-red removed'>Выведен</span> - нужно менять"></i></th>
			<th width="">Примечание</th>
			<th width="60">Цена</th>
			<th width="75">Действие</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "
		SELECT ODD.ODD_ID
			,ODD.Amount
			,PM.Model
			,PM.code
			,IF(ODD.discount, ODD.Price, '') old_Price
			,(ODD.Price - IFNULL(ODD.discount, 0)) Price
			,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PT_ID
			,Zakaz(ODD.ODD_ID) Zakaz
			,ODD.PF_ID
			,PF.Form
			,IF(PMF.standart, 'standart', '') form_standart
			,IFNULL(MT.Material, '') Material
			,CONCAT(' <b>', SH.Shipper, '</b>') Shipper
			,DATEDIFF(ODD.arrival_date, NOW()) outdate
			,ODD.MT_ID
			,IF(MT.removed=1, 'removed ', '') removed
			,MT.SH_ID
			,SH.mtype
			,ODD.IsExist
			,ODD.Comment
			,Friendly_date(ODD.order_date) order_date
			,Friendly_date(ODD.arrival_date) arrival_date
			,Steps_button(ODD.ODD_ID, 0) Steps
			,IF(CL.clear = 1 AND PM.enamel = 1, 1, 0) enamel_error
			,CONCAT('<p class=\"price\">+', IFNULL(MT.markup, SH.markup), 'р.</p>') markup
		FROM OrdersDataDetail ODD
		JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
		LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
		LEFT JOIN ProductModelsForms PMF ON PMF.PM_ID = ODD.PM_ID AND PMF.PF_ID = ODD.PF_ID
		LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
		WHERE ODD.OD_ID = {$id}
		GROUP BY ODD.ODD_ID
		ORDER BY IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) DESC, ODD.ODD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	while( $row = mysqli_fetch_array($res) )
	{
		if ($row["IsExist"] == "0") {
			$color = "bg-red";
		}
		elseif ($row["IsExist"] == "1") {
			$color = "bg-yellow' html='Заказано:&nbsp;&nbsp;&nbsp;&nbsp;<b>{$row["order_date"]}</b><br>Ожидается:&nbsp;<b>{$row["arrival_date"]}</b>";
		}
		elseif ($row["IsExist"] == "2") {
			$color = "bg-green";
		}
		else {
			$color = "bg-gray";
		}
		$material = "<span class='wr_mt'>".(($row["outdate"] <= 0 and $row["IsExist"] == 1) ? "<i class='fas fa-exclamation-triangle' style='color: #E74C3C;' title='{$row["outdate"]} дн.'></i>" : "")."<span shid='{$row["SH_ID"]}' mtid='{$row["MT_ID"]}' id='m{$row["ODD_ID"]}' class='mt{$row["MT_ID"]} {$row["removed"]} material ".(in_array('screen_materials', $Rights) ? "mt_edit" : "")." {$color}'>{$row["Material"]}{$row["Shipper"]}</span><input type='text' value='{$row["Material"]}' class='materialtags_{$row["mtype"]}' style='display: none;'><input type='checkbox' ".($row["removed"] ? "checked" : "")." style='display: none;' title='Выведен'></span>";

		$steps = "<a id='{$row["ODD_ID"]}' class='".(in_array('step_update', $Rights) ? "edit_steps " : "")."' location='{$location}'>{$row["Steps"]}</a>";

		$format_old_price = ($row["old_Price"] != '') ? '<p class="old_price">'.number_format($row["old_Price"], 0, '', ' ').'</p>' : '';
		$format_price = ($row["Price"] != '') ? '<p class="price">'.number_format($row["Price"], 0, '', ' ').'</p>' : '';
		echo "<tr id='prod{$row["ODD_ID"]}' class='ord_log_row' lnk='*ODD_ID{$row["ODD_ID"]}*'>";
		echo "<td rowspan='2'>".($row["code"] ? "<img style='width: 50px;' src='https://фабрикастульев.рф/images/prodlist/{$row["code"]}.jpg'/>" : "")."".($row["PF_ID"] ? "<br><img class='form {$row["form_standart"]}' src='/img/form{$row["PF_ID"]}.png' title='{$row["Form"]}'>" : "")."</td>";
		echo "<td colspan='5'><b style='font-size: 1.2em;'>{$row["Zakaz"]}</b></td>";
		echo "<td rowspan='2'>";
			echo "<button ".(($disabled or $Del or $PFI_ID or !$editable) ? 'disabled' : 'title=\'Редактировать изделие\'')." id='{$row["ODD_ID"]}' class='edit_product{$row["PT_ID"]}' location='{$location}'><i class='fa fa-pencil-alt fa-lg'></i></button>";
			$delmessage = addslashes("Удалить {$row["Zakaz"]} ({$row["Amount"]} шт.)?<br><b>Внимание! Для удаления набора воспользуйтесь кнопкой <i class=\"fa fa-times fa-2x\"></i> выше.</b>");
			echo "<button ".(($disabled or $Del or $PFI_ID or !$editable) ? 'disabled' : 'title=\'Удалить изделие из набора\'')." onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&del={$row["ODD_ID"]}\")) return false;'><i class='fas fa-trash-alt fa-lg'></i></button>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td><b style='font-size: 2em;'>{$row["Amount"]}</b></td>";
		echo "<td class='td_step ".($confirmed == 1 ? "step_confirmed" : "")." ".(!in_array('step_update', $Rights) ? "step_disabled" : "")."'>{$steps}</td>";
		echo "<td>{$material}{$row["markup"]}</td>";
		echo "<td>{$row["Comment"]}</td>";
		echo "<td class='txtright'>{$format_old_price}{$format_price}</td>";
		echo "</tr>";

		// Выводим ошибку если прозрачное покрытие пластика
		if( $row["enamel_error"] ) {
			echo "
				<script>
					noty({text: 'Ошибка в наборе: {$row["Model"]} только ПОД ЭМАЛЬ!', type: 'error'});
				</script
			";
		}
	}
?>

		</tbody>
	</table>
	<!-- Конец таблицы изделий -->
</div>

<style>
	.ord_log_row {
		border-left: 4px solid white;
	}
</style>
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
			<li><a href="#order_log_table">Журнал изменений в наборе</a></li>
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
					<th width="">Дата<br>Время</th>
					<th width="">Автор</th>
					</tr>
				</thead>
				<tbody>
		<?
			$query = "SELECT IF(OCL.OD_ID IS NOT NULL, 'OD_ID', 'ODD_ID') table_key
							,IF(OCL.OD_ID IS NOT NULL, OCL.OD_ID, IFNULL(OCL.ODD_ID, ODS.ODD_ID)) table_value
							,CONCAT(OFN.field_name, IFNULL(CONCAT(' \"', ST.Step, '\"'), '')) field_name
							,OCL.OFN_ID
							,OCL.old_value
							,OCL.new_value
							,USR_Icon(OCL.author) Name
							,Friendly_date(OCL.date_time) friendly_date
							,DATE_FORMAT(OCL.date_time, '%H:%i') Time
						FROM OrdersChangeLog OCL
						JOIN OrdersFieldName OFN ON OFN.OFN_ID = OCL.OFN_ID
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODS_ID = OCL.ODS_ID
						LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
						WHERE (OCL.OD_ID = {$id}) OR (OCL.ODD_ID IN (SELECT ODD_ID FROM OrdersDataDetail WHERE OD_ID = {$id})) OR (ODS.ODD_ID IN (SELECT ODD_ID FROM OrdersDataDetail WHERE OD_ID = {$id}))
						ORDER BY OCL.date_time DESC, OCL.OCL_ID DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr class='ord_log_row' lnk='*{$row["table_key"]}{$row["table_value"]}*'>";

				// Если разделение набора
				if ($row["OFN_ID"] == 1) {
					echo "<td><b>{$row["field_name"]}</b></td>";
					// Если хранится OD_ID - выводим ссылку на другую часть набора
					if (is_numeric($row["old_value"])) {
						echo "<td colspan='3' style='text-align: center;'><a href='orderdetail.php?id={$row["old_value"]}' class='button' target='_blank'>другая его часть</a></td>";
					}
					else {
						echo "<td colspan='3' style='text-align: center;'>{$row["old_value"]}</td>";
					}
				}
				elseif( $row["old_value"] != "" or $row["new_value"] != "" ) {
					echo "<td><b>{$row["field_name"]}</b></td>";
					echo "<td style='text-align: right;'><i style='background: #ddd; padding: 2px;'>{$row["old_value"]}</i></td>";
					echo "<td><i class='fa fa-arrow-right'></i></td>";
					echo "<td style='text-align: left;'><i style='background: #fd9; padding: 2px;'>{$row["new_value"]}</i></td>";
				}
				else {
					echo "<td colspan='4'><b>{$row["field_name"]}</b></td>";
				}
				echo "<td class='nowrap'>{$row["friendly_date"]}<br>{$row["Time"]}</td>";
				echo "<td>{$row["Name"]}</td>";
				echo "</tr>";
			}
		?>
				</tbody>
			</table>
		</div>
		<div id="order_message">
			<table style="width: 100%;">
				<thead>
					<tr>
					<th width="40"><i class="fas fa-question-circle fa-lg" html="<p>Если нажать на красный конверт слева от сообщения, то конверт станет зеленым - это означает, что сообщение прочитано. Оно так же исчезнет из уведомлений в верхнем-левом углу и там остануться только самые актуальные сообщения.</p><p>Непрочитанные сообщения спустя месяц автоматически закрываются.</p>"></i></th>
					<th width="">Сообщение
					<?
					if( in_array('order_add', $Rights) and ($editable or !$SH_ID) ) {
						echo "<br><a href='#' class='add_message_btn button'>".(in_array('order_add_confirm', $Rights) ? "Сообщение с производства" : "Сообщение на производство")."</a>";
					}
					?>
					</th>
					<th width="">Дата<br>Время</th>
					<th width="">Автор</th>
					</tr>
				</thead>
				<tbody>
		<?
			$query = "SELECT OM.OM_ID
							,OM.Message
							,OM.priority
							,USR_Icon(OM.author) Name
							,Friendly_date(OM.date_time) friendly_date
							,DATE_FORMAT(OM.date_time, '%H:%i') Time
							,IFNULL(USR_Name(OM.read_user), 'СИСТЕМА') read_user
							,Friendly_date(OM.read_time) read_date
							,DATE_FORMAT(OM.read_time, '%H:%i') read_time
							,OM.destination
							,IF(OM.read_time > NOW(), 0, 1) is_read
						FROM OrdersMessage OM
						WHERE OM.OD_ID = {$id}
						ORDER BY OM.OM_ID DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				if( $row["is_read"] ) {
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
				echo "<td>{$row["friendly_date"]}<br>{$row["Time"]}</td>";
				echo "<td>{$row["Name"]}</td>";
				echo "</tr>";
			}
		?>
				</tbody>
			</table>
		</div>
		<div id="attachments">
			<form action="upload.php" method="post" enctype="multipart/form-data" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
				<input type="hidden" name="odid" value="<?=$id?>">
				<input type=file name=uploadfile>
				<input type="text" name="comment" placeholder="Комментарий">
				<input type=submit name="subbut" value=Загрузить>
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
<!-- Форма добавления сообщения к набору -->
<div id='add_message' title='Сообщение' style='display:none'>
	<form method='post' action='<?=$location?>&add_message' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
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
			<input type='submit' name="subbut" value='Отправить' style='float: right;'>
		</div>
	</form>
</div>
<!-- Конец формы добавления сообщения к набору -->

<!-- Форма изменения цвета краски -->
<div id='paint_color' class="addproduct" title='Цвет краски' style='display:none'>
	<form method='post' action='<?=$location?>&paint_color' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<p style="color: #911;">ВНИМАНИЕ! Обязательно указывайте конкретный цвет. Абстрактные обозначения вроде "в тон пластика" или "по образцу" не допускаются.</p>
			<div>
				<label>Описание:</label>
				<input type="text" name="color" class="colortags" style="width: 300px;" value="" autocomplete="off">
				<i class='fa fa-question-circle' style='margin: 5px;' title='Поле для описания цвета в свободной форме.'></i>
			</div>
			<div>
				<label>Цвет NCS:</label>
				<select name="NCS" style="width: 200px;">
					<?
					$query = "
						SELECT NCS_ID, IFNULL(NCScolor, '-=NCS цвет не указан=-') NCScolor, HTML, IF(R+G+B < 200, 'white', 'black') txt_color
						FROM NCScolors
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value={$row["NCS_ID"]} data-html='{$row["HTML"]}' style='color: {$row["txt_color"]}; background: {$row["HTML"]};'>{$row["NCScolor"]}</option>";
					}
					?>
				</select>
				<div id="NCScolor" style="width: 28px; height: 28px; display: inline-block; border-radius: 50%; margin-left: 5px;"></div>
				<i class='fa fa-question-circle' style='margin: 5px;' title='Натуральная система цвета NCS (Natural Color System) — проприетарная цветовая модель, предложенная шведским Институтом Цвета. Она основана на системе противоположных цветов и нашла широкое применение в промышленности для описания цвета продукции. Сегодня NCS является одной из наиболее широко используемых систем описания цветов в мире, получила международное научное признание, а кроме того, NCS является национальным стандартом в Швеции, Норвегии, Испании, и Южной Африке.'></i>
			</div>
			<div>
				<label>Тип покрытия:</label>
				<div class='btnset'>
					<input type='radio' id='clear1' name='clear' value='1' required>
						<label for='clear1'>Прозрачное</label>
					<input type='radio' id='clear0' name='clear' value='0' required>
						<label for='clear0'>Эмаль</label>
				</div>
				<i class='fa fa-question-circle' style='margin: 5px;' title='Прозрачное покрытие - это покрытие, при котором просматривается структура дерева (в том числе лак, тонированный эмалью). Эмаль - это непрозрачное покрытие.'></i>
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!-- Конец формы добавления сообщения к набору -->

<script>
	$(function(){
		// Select2 для выбора NCS
		function format (state) {
			var originalOption = state.element;
			if (state.id == 0) return state.text;
			return "<div style='display: flex;'><span style='width: 50px; height: 16px; margin-right: 5px; background: " + $(originalOption).data('html') + "; border: 1px solid #333;'/></span><span>" + state.text + "</span><div>";
		};
		$('select[name="NCS"]').select2({
			language: "ru",
			templateResult: format,
			escapeMarkup: function(m) { return m; }
		});

		// При выборе цвета NCS меняется цвет кружочка справа
		$('select[name="NCS"]').change(function(){
			var html = $(this).children('option:selected').attr('data-html');
			$('#NCScolor').css('background', html);
			if (html) {
				$('#NCScolor').css('box-shadow', '0 0 10px #666');
			}
			else {
				$('#NCScolor').css('box-shadow', 'none');
			}
		});

		// Сабмит формы набора при изменении
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

		// Кнопка добавления сообщения к набору
		$('.add_message_btn').click( function() {
			$('#add_message').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});
		});

		// Кнопка изменения цвета краски
		$('#paint_color_btn').click( function() {
			// Очистка радио кнопок
			$('#paint_color input[type="radio"]').prop('checked', false);
			$('#paint_color input[type="radio"]').button('refresh');

			var color = $(this).attr('color');
			var clear = $(this).attr('clear');
			var NCS_ID = $(this).attr('NCS_ID');

			// Заполнение формы
			$('#paint_color input[name="color"]').val(color);
			$('#paint_color select[name="NCS"]').val(NCS_ID).trigger('change');
			$('#paint_color #clear'+clear).prop('checked', true);
			$('#paint_color input[type="radio"]').button('refresh');

			$('#paint_color').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			$( ".colortags" ).autocomplete( "option", "appendTo", "#paint_color" );

			return false;
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
			$('.ord_log_row[lnk="'+lnk+'"]').css('border-left', '4px solid orangered');
		}, function() {
			var lnk = $(this).attr('lnk');
			$('.ord_log_row[lnk="'+lnk+'"]').css('border-left', '4px solid white');
		});

		<?
		// Если пришли из калькулятора - открывается форма редактирования стола
		if ($_GET["odd_id"]) {
			echo "$('#{$_GET["odd_id"]}.edit_product2').click();";
		}
		?>
	});
</script>

<?
	include "footer.php";
?>
