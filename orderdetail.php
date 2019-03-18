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
				,IF(OD.ReadyDate IS NOT NULL, 1, 0) Archive
			FROM OrdersData OD
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) AND OD_ID = {$_GET["id"]}
				".($USR_Shop ? "AND (SH.SH_ID = {$USR_Shop} OR (OD.StartDate IS NULL AND SH.retail = 1) OR OD.SH_ID IS NULL)" : "")."
				".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "")
		;
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$OD_ID = mysqli_result($res,0,'OD_ID');
		$Code = mysqli_result($res,0,'Code');
		$Del = mysqli_result($res,0,'Del');
		$Archive = mysqli_result($res,0,'Archive');
		$is_lock = mysqli_result($res,0,'is_lock');
		$start_year = mysqli_result($res,0,'start_year');
		$start_month = mysqli_result($res,0,'start_month');
		$confirmed = mysqli_result($res,0,'confirmed');

		// В заголовке страницы выводим код набора
		$title = $Code;
		include "header.php";

		// Запрет на редактирование
		$disabled = !( in_array('order_add', $Rights) and ($confirmed == 0 or in_array('order_add_confirm', $Rights)) and !$is_lock and !$Archive and !$Del );

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
		$StartDate = $_POST["StartDate"] ? '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'' : "NULL";
		$EndDate = $_POST[EndDate] ? '\''.date( "Y-m-d", strtotime($_POST["EndDate"]) ).'\'' : "NULL";
		if( $_POST["ul"] ) {
			if( $_POST["ClientName"] ) {
				$ul = "1";
			}
			else {
				$ul = "0";
				$_SESSION["alert"][] = "Ведите название юр. лица в поле \"Клиент\".";
			}
		}
		$ul = ($_POST["ClientName"] and $_POST["ul"]) ? "1" : "0";
		$chars = array("+", " ", "(", ")"); // Символы, которые трубуется удалить из строки с телефоном
		$mtel = $_POST["mtel"] ? '\''.str_replace($chars, "", $_POST["mtel"]).'\'' : 'NULL';
		$Shop = $_POST["Shop"] > 0 ? $_POST["Shop"] : "NULL";
		// Обработка строк
		$ClientName = convert_str($_POST["ClientName"]);
		$ClientName = mysqli_real_escape_string($mysqli, $ClientName);
		$OrderNumber = convert_str($_POST["OrderNumber"]);
		$OrderNumber = mysqli_real_escape_string($mysqli, $OrderNumber);
		$Color = convert_str($_POST["Color"]);
		$Color = mysqli_real_escape_string($mysqli, $Color);
		$Comment = convert_str($_POST["Comment"]);
		$Comment = mysqli_real_escape_string($mysqli, $Comment);
		$address = convert_str($_POST["address"]);
		$address = mysqli_real_escape_string($mysqli, $address);

		// Сохраняем в таблицу цветов полученный цвет и узнаем его ID
		if( $Color != '' ) {
			// Если с цветом передана прозрачность - обновляем цвет
			if( isset($_POST["clear"]) ) {
				$clear = $_POST["clear"];
				$query = "
					INSERT INTO Colors
					SET
						color = '{$Color}',
						clear = {$clear},
						count = 0
					ON DUPLICATE KEY UPDATE
						count = count + 1
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$cl_id = mysqli_insert_id( $mysqli );
			}
			// Если с цветом не передана прозрачность выводим предупреждение
			else {
				$_SESSION["alert"][] = "Пожалуйста укажите тип покрытия \"Прозрачный\" или \"Эмаль\".";
			}
		}
		else {
			$cl_id = "NULL";
			$_POST["clear"] = "0"; //Чтобы сработало условие в следующем запросе когда цвет удален
		}

		$query = "UPDATE OrdersData
					SET author = {$_SESSION['id']}
						".(isset($_POST["ClientName"]) ? ",CLientName = '$ClientName'" : "")."
						".(isset($_POST["ClientName"]) ? ",ul = $ul" : "")."
						".(isset($_POST["mtel"]) ? ",mtel = $mtel" : "")."
						".(isset($_POST["address"]) ? ",address = '$address'" : "")."
						".(isset($_POST["StartDate"]) ? ",StartDate = $StartDate" : "")."
						".(isset($_POST["EndDate"]) ? ",EndDate = $EndDate" : "")."
						".(isset($_POST["Shop"]) ? ",SH_ID = $Shop" : "")."
						".(isset($_POST["OrderNumber"]) ? ",OrderNumber = '$OrderNumber'" : "")."
						".((isset($_POST["Color"]) and isset($_POST["clear"])) ? ",CL_ID = $cl_id" : "")."
						".(isset($_POST["Comment"]) ? ",Comment = '$Comment'" : "")."
					WHERE OD_ID = {$id}";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = mysqli_error( $mysqli );
		}
		else {
			// Узнаем изменилась ли запись
			if( mysqli_affected_rows( $mysqli ) ) {
				$_SESSION["success"][] = "Данные сохранены.";

				// Узнаем есть ли платежи по кассе другого салона
				$query = "SELECT CheckPayment({$OD_ID}) attention";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$attention = mysqli_result($res,0,'attention');
				if( $attention ) {
					$_SESSION["alert"][] = "У этого набора имеются платежи, внесённые в кассу другого салона! Проверьте оплату в реализации.";
				}
			}
			else {
				$_SESSION["error"][] = "Данные не были сохранены.";
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Добавление в базу нового изделия. Заполнение этапов.
	if ( isset($_GET["add"]) and !$disabled )
	{
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
		// Обработка строк
		$Material = convert_str($_POST["Material"]);
		$Material = mysqli_real_escape_string($mysqli, $Material);
		$edge = convert_str($_POST["edge"]);
		$edge = mysqli_real_escape_string($mysqli, $edge);
		$Comment = convert_str($_POST["Comment"]);
		$Comment = mysqli_real_escape_string($mysqli, $Comment);
		$edge = ($edge != '') ? "'$edge'" : "NULL";
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

		$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, BL_ID, Other, edge, Length, Width, PieceAmount, PieceSize, piece_stored, PF_ID, PME_ID, box, MT_ID, IsExist, Amount, Comment, order_date, arrival_date, author, ptn)
				  VALUES (IF({$id} > 0, {$id}, NULL), {$Model}, {$Blank}, {$Other}, {$edge}, {$Length}, {$Width}, {$PieceAmount}, {$PieceSize}, {$piece_stored}, {$Form}, {$Mechanism}, {$box}, {$mt_id}, {$IsExist}, {$_POST["Amount"]}, {$Comment}, {$OrderDate}, {$ArrivalDate}, {$_SESSION['id']}, $ptn)";
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
		else {
			$_SESSION["error"][] = "Прежде чем удалить изделие переведите производственные этапы в статус не выполненных.";
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
		$Message = convert_str($_POST["message"]);
		$Message = mysqli_real_escape_string($mysqli, $Message);
		$query = "INSERT INTO OrdersMessage
					 SET OD_ID = {$id}
						,Message = '{$Message}'
						,priority = {$_POST["priority"]}
						,author = {$_SESSION['id']}
						,destination = ".( in_array('order_add_confirm', $Rights) ? "0" : "1" );
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = mysqli_error( $mysqli );
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	include "forms.php";

	echo "<p><a href='{$_SESSION["location"]}#ord{$_GET["id"]}' class='button'><< Вернуться</a></p>";

	$query = "
		SELECT OD.OD_ID
			,OD.Code
			,OD.ClientName
			,OD.ul
			,OD.mtel
			,OD.address
			,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
			,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
			,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
			,DATE_FORMAT(OD.ReadyDate, '%d.%m.%y') ReadyDate
			,DATE_FORMAT(OD.DelDate, '%d.%m.%y') DelDate
			,IF((SH.KA_ID IS NULL AND SH.SH_ID IS NOT NULL AND OD.StartDate IS NULL), '<br><b style=\'background-color: silver;\'>Выставка</b>', '') showing
			,IFNULL(OD.SH_ID, 0) SH_ID
			,IFNULL(SH.KA_ID, 0) KA_ID
			,OD.OrderNumber
			,CL.color Color
			,CL.clear
			,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
			,WD.Name
			,OD.Comment
			,IF(OD.SH_ID IS NULL, '#999', IFNULL(CT.Color, '#fff')) CTColor
			,IF((SH.KA_ID IS NULL AND SH.SH_ID IS NOT NULL), 1, 0) retail
			,SH.CT_ID
			,IFNULL(OD.SHP_ID, 0) SHP_ID
			,IF(PFI.rtrn = 1, NULL, OD.PFI_ID) PFI_ID
			,PFI.count
			,PFI.platelshik_id
			,Ord_price(OD.OD_ID) - Ord_discount(OD.OD_ID) Price
			,Ord_opt_price(OD.OD_ID) opt_price
			,Payment_sum(OD.OD_ID) payment_sum
			,CheckPayment(OD.OD_ID) attention
		FROM OrdersData OD
		LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
		LEFT JOIN WorkersData WD ON WD.WD_ID = OD.WD_ID
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
		WHERE OD.OD_ID = {$id}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);

	$Code = $row['Code'];
	$ClientName = $row['ClientName'];
	$ul = $row['ul'];
	$mtel = $row['mtel'];
	$address = $row['address'];
	$AddDate = $row['AddDate'];
	$StartDate = $row['StartDate'];
	$EndDate = $row['EndDate'];
	$ReadyDate = $row['ReadyDate'];
	$DelDate = $row['DelDate'];
	$showing = $row['showing'];
	$SH_ID = $row['SH_ID'];
	$KA_ID = $row['KA_ID'];
	$OrderNumber = $row['OrderNumber'];
	$Color = $row['Color'];
	$clear = $row['clear'];
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

	// Если пользователю доступен только один салон в регионе или оптовик или свободный набор и нет админских привилегий, то нельзя редактировать общую информацию набора.
	$editable = (!($USR_Shop and $SH_ID and $USR_Shop != $SH_ID) and !($USR_KA and $SH_ID and $USR_KA != $KA_ID) and !($SH_ID == 0 and !in_array('order_add_confirm', $Rights)));
?>
	<form method='post' id='order_form' action='<?=$location?>&order_update'>
	<table class="main_table">
		<thead>
		<tr class='nowrap'>
			<th width="90">Код<br>Создан</th>
			<?
			if( $retail ) {
				echo "<th width='125'>Клиент<br>Квитанция<br>Телефон</th>";
				echo "<th width='20%'>Адрес доставки</th>";
			}
			?>
			<th width="95">Дата продажи</th>
			<?= ($ReadyDate ? "<th width='95'>Отгружено</th>" : ($DelDate ? "<th width='95'>Удалено</th>" : "<th width='95'>Дата сдачи</th>")) ?>
			<th width="125">Подразделение</th>
			<th width="170">Цвет краски</th>
			<th width="40">Принят</th>
			<th width="65">Стоимость<br>набора</th>
			<?
			if( $retail ) {
				echo "<th width='65'>Оплата</th>";
			}
			?>
			<th width="20%">Примечание</th>
			<th width="70">Действие</th>
		</tr>
		</thead>
		<tbody>
		<tr class='ord_log_row' lnk='*OD_ID<?=$id?>*' id='ord<?=$id?>'>
			<td class="nowrap"><h1><?=$Code?></h1><?=$AddDate?></td>
<?
		if( $retail ) {
			echo "
				<td>
					<input type='text' class='clienttags' name='ClientName' style='width: 120px;' value='$ClientName' ".((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")." placeholder='Клиент'>
					<br>
					<input type='checkbox' id='ul' name='ul' ".($ul == 1 ? "checked" : "")." ".((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")." title='Поставьте галочку если требуется накладная.' ".($PFI_ID ? "onclick='return false;'" : "").">
					<label for='ul'>юр. лицо</label>
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
			$title="Чтобы стереть дату продажи анулируйте накладную в актах сверки, затем перейдите в реализацию и нажмите на символ ладошки справа.";
		}
		else {
			$invoice = "";
			$title = "Чтобы стереть дату продажи перейдите в реализацию и нажмите на символ ладошки справа.";
		}
		echo "<td><input type='text' name='StartDate' class='date' value='{$StartDate}' date='{$StartDate}' ".((in_array('order_add', $Rights) and !$is_lock and !$Del and $retail and $editable) ? "" : "disabled")." readonly ".( (in_array('order_add', $Rights) and !$is_lock and !$Del and $retail and $editable and $StartDate) ? "title='{$title}'" : "" ).">{$invoice}{$showing}</td>";
?>

		<td style='text-align: center;'><?= ($ReadyDate ? $ReadyDate : ($DelDate ? $DelDate : ($showing ? "" : "<input type='text' name='EndDate' class='date' value='{$EndDate}' ".((!$disabled and $editable and $SH_ID and in_array('order_add_confirm', $Rights)) ? "" : "disabled").">"))) ?></td>
		<td>
		<div class='shop_cell' id='<?=$id?>' style='box-shadow: 0px 0px 10px 10px <?=$CTColor?>;'>
			<select name='Shop' class='select_shops' <?=((in_array('order_add', $Rights) and !$is_lock and !$Del and $editable) ? "" : "disabled")?> style="width: 100%;">
			</select>
			</div>
		</td>

<?
		switch ($IsPainting) {
			case 0:
				$class = "empty";
				//$title = "Без покраски";
				break;
			case 1:
				$class = "notready";
				//$title = "Не в работе";
				break;
			case 2:
				$class = "inwork";
				//$title = "В работе";
				break;
			case 3:
				$class = "ready";
				//$title = "Готово";
				//if($Name) $title .= " ({$Name})";
				break;
		}
		echo "
			<td val='{$IsPainting}' class='painting_cell ".(( in_array('order_add_confirm', $Rights) and $Archive == 0 and $Del == 0 and $IsPainting != 0 ) ? "painting " : "")." {$class}'>
				<div class='painting_workers'>{$Name}</div>
				<div style='background: lightgrey; cursor: auto;'>
					<div class='btnset'>
						<input type='radio' id='clear1' name='clear' value='1' ".($clear == "1" ? "checked" : "").">
							<label for='clear1'>Прозрачный</label>
						<input type='radio' id='clear0' name='clear' value='0' ".($clear == "0" ? "checked" : "").">
							<label for='clear0'>Эмаль</label>
					</div>
					<input type='text' id='paint_color' class='colortags' name='Color' style='width: 160px;' ".((!$disabled and $editable) ? "" : "disabled")." value='{$Color}'>
					<i class='fa fa-question-circle' style='margin: 5px;' title='Прозрачное поктытие - это покрытие, при котором просматривается структура дерева (в том числе лак, тонированный эмалью). Эмаль - это непрозрачное покрытие.'>Подсказка</i>
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
		if( in_array('order_add_confirm', $Rights) and $Archive == 0 and $Del == 0 ) {
			$class = $class." edit_confirmed";
		}
		echo "<td val='{$confirmed}' class='{$class}' style='text-align: center;'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";

		// СУММА ЗАКАЗА
		// Если свободные - ячейка пуста
		if( $SH_ID == 0 ) {
			$price = "";
		}
		// Если розница и есть доступ к реализации или опт и есть доступ к сверкам то цена редактируемая
		elseif( ($retail and (in_array('selling_all', $Rights) or in_array('selling_city', $Rights))) or (!$retail and(in_array('sverki_all', $Rights) or in_array('sverki_city', $Rights))) ) {
			// Если набор в накладной - сумма набора ведет в накладную, цена не редактируется
			if( $row["PFI_ID"] ) {
				// Исключение для Клена
				if ($row["SH_ID"] == 36) {
					$price = "<button style='width: 100%;' class='update_price_btn button nowrap txtright' id='{$row["OD_ID"]}' location='{$location}'>{$format_price}</button><br><a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><b title='Стоимость по накладной'>{$format_opt_price}<i class='fa fa-question-circle'></i></b></a>";
				}
				else {
					$price = "<a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><b title='Стоимость по накладной'>{$format_price}<i class='fa fa-question-circle'></i></b></a>";
				}
			}
			else {
				$price = "<button style='width: 100%;' class='update_price_btn button nowrap txtright' id='{$row["OD_ID"]}' location='{$location}'>{$format_price}</button>";
			}
		}
		else {
			$price = "<p class='price'>$format_price</p>";
		}

		echo "<td class='txtright'>{$price}</td>";
		if( $retail ) {
			echo "<td><button ".($row["ul"] ? "disabled" : "")." style='width: 100%;' class='add_payment_btn button nowrap txtright ".($row["attention"] ? "attention" : "")."' id='{$row["OD_ID"]}' location='{$location}' ".($row["attention"] ? "title='Имеются платежи, внесённые в кассу другого салона!'" : "").">{$format_payment}</button></td>";
		}
?>

		<td><textarea name='Comment' rows='6' <?=( (in_array('order_add', $Rights) and !$Del and $editable) ? "" : "disabled" )?> style='width: 100%;'><?=$Comment?></textarea></td>
		<td style="text-align: center;">

<?
			// Если есть право редактирования и набор не чужой - показываем кнопку клонирования
			if( in_array('order_add', $Rights) and $editable ) {
				echo "<p><a href='#' onclick='if(confirm(\"<b>Подтвердите клонирование набора!</b>\", \"clone_order.php?id={$id}&confirmed=".(in_array('order_add_confirm', $Rights) ? 1 : 0)."\")) return false;' title='Клонировать'><i class='fa fa-clone fa-2x' aria-hidden='true'></i></a></p>";
			}
			// Если розничный набор (и не удален)- показываем кнопку перехода в реализацию
			if( $retail and $editable and !$Del ) {
				echo "<p><a href='/selling.php?CT_ID={$CT_ID}&year={$start_year}&month={$start_month}#ord{$id}' title='Перейти в реализацию'><i class='fa fa-money-bill-alt fa-2x' aria-hidden='true'></i></a></p>";
			}
			// Если набор в отгрузке и набор не чужой - показываем кнопку перехода в отгрузку
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
		echo "<div id='order_in_work_label' style='position: absolute; top: 77px; left: 140px; font-weight: bold; color: green; font-size: 1.2em; ".(($confirmed == 1) ? "" : "display: none;")."'>Набор принят в работу.</div>";
		if( $is_lock == 1 ) {
			echo "<div style='position: absolute; top: 77px; left: 340px; font-weight: bold; color: green; font-size: 1.2em;'>Месяц в реализации закрыт (изменения ограничены).</div>";
		}
		if( $Del == 1 ) {
			echo "<div style='position: absolute; top: 173px; font-weight: bold; color: #911; font-size: 5em; opacity: .3; border: 5px solid;'>Набор удалён</div>";
		}
?>
<div class="halfblock">
	<p>
		<button <?=(($disabled or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_product1' odid='<?=$id?>'>Добавить стулья</button>
		<button <?=(($disabled or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_product2' odid='<?=$id?>'>Добавить столы</button>
		<button <?=(($disabled or $PFI_ID or !$editable) ? 'disabled' : '')?> class='edit_product0' odid='<?=$id?>'>Добавить заготовки/прочее</button>
	</p>

	<!-- Таблица изделий -->
	<table class="main_table">
		<thead>
		<tr>
			<th width="50"></th>
			<th width="40">Кол-во</th>
			<th width="120">Изделие</th>
			<th width="100">Этапы</th>
			<th width="">Материал</th>
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
			,ODD.Del
			,IF(CL.clear = 1 AND PM.enamel = 1, 1, 0) enamel_error
		FROM OrdersDataDetail ODD
		JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
		LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
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

		$steps = "<a id='{$row["ODD_ID"]}' class='".((in_array('step_update', $Rights) and $row["Del"] == 0) ? "edit_steps " : "")."' location='{$location}'>{$row["Steps"]}</a>";

		$format_old_price = ($row["old_Price"] != '') ? '<p class="old_price">'.number_format($row["old_Price"], 0, '', ' ').'</p>' : '';
		$format_price = ($row["Price"] != '') ? '<p class="price">'.number_format($row["Price"], 0, '', ' ').'</p>' : '';
		echo "<tr id='prod{$row["ODD_ID"]}' class='ord_log_row ".($row["Del"] == 1 ? 'del' : '')."' lnk='*ODD_ID{$row["ODD_ID"]}*'>";
		echo "<td>".($row["code"] ? "<img style='width: 50px;' src='http://фабрикастульев.рф/images/prodlist/{$row["code"]}.jpg'/>" : "")."</td>";
		echo "<td><b style='font-size: 1.3em;'>{$row["Amount"]}</b></td>";
		echo "<td><span>{$row["Zakaz"]}</span></td>";
		echo "<td class='td_step ".($confirmed == 1 ? "step_confirmed" : "")." ".(!in_array('step_update', $Rights) ? "step_disabled" : "")."'>{$steps}</td>";
		echo "<td>{$material}</td>";
		echo "<td>{$row["Comment"]}</td>";
		echo "<td class='txtright'>{$format_old_price}{$format_price}</td>";
		echo "<td>";
		
		if( $row["Del"] == 0 ) {
			echo "<button ".(($disabled or $PFI_ID or !$editable) ? 'disabled' : 'title=\'Редактировать изделие\'')." id='{$row["ODD_ID"]}' class='edit_product{$row["PT_ID"]}' location='{$location}'><i class='fa fa-pencil-alt fa-lg'></i></button>";

			$delmessage = addslashes("Удалить {$row["Model"]}({$row["Amount"]} шт.) {$row["Form"]} {$row["Mechanism"]} {$row["Size"]}?");
			echo "<button ".(($disabled or $PFI_ID or !$editable) ? 'disabled' : 'title=\'Удалить\'')." onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&del={$row["ODD_ID"]}\")) return false;'><i class='fa fa-times fa-lg'></i></button>";
		}
		echo "</td></tr>";

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
			$query = "SELECT OCL.table_key
							,OCL.table_value
							,IF(OCL.OFN_ID IS NOT NULL, OFN.field_name, OCL.field_name) field_name
							,OCL.OFN_ID
							,OCL.old_value
							,OCL.new_value
							,USR_Icon(OCL.author) Name
							,Friendly_date(OCL.date_time) friendly_date
							,TIME(OCL.date_time) Time
						FROM OrdersChangeLog OCL
						LEFT JOIN OrdersFieldName OFN ON OFN.OFN_ID = OCL.OFN_ID
						WHERE (table_key = 'OD_ID' AND table_value = {$id}) OR (table_key = 'ODD_ID' AND table_value IN (SELECT ODD_ID FROM OrdersDataDetail WHERE OD_ID = {$id}))
						ORDER BY OCL.OCL_ID DESC";
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
			<p style='color: #911;'>Если нажать на красный конверт слева от сообщения, то конверт станет зеленым - это означает, что сообщение прочитано. Оно так же исчезнет из уведомлений в верхнем-левом углу и там остануться только самые актуальные сообщения.</p>
			<table style="width: 100%;">
				<thead>
					<tr>
					<th width="40"><a href="#" class="add_message_btn" title="Добавить сообщение"><i class="fa fa-plus-square fa-2x" style="color: green;"></i></a></th>
					<th width="">Сообщение</th>
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
				echo "<td>{$row["friendly_date"]}<br>{$row["Time"]}</td>";
				echo "<td>{$row["Name"]}</td>";
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
<!-- Форма добавления сообщения к набору -->
<div id='add_message' title='Сообщение' style='display:none'>
	<form method='post' action='<?=$location?>&add_message'>
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
<!-- Конец формы добавления сообщения к набору -->

<script>
	$(function(){
//		// Select2 для выбора салона
//		$('select[name="Shop"]').select2({
//			placeholder: "Выберите салон",
//			language: "ru"
//		});

		// Деактивация/активация кнопок типа покраски
		clearonoff('#paint_color');


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

//		$('.attention img').show();
	});
</script>

<?
	include "footer.php";
?>
