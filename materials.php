<?
	include "config.php";
	$title = 'Материалы';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_materials', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$location = $_SERVER['REQUEST_URI'];
	$_SESSION["location"] = $location;

	if( isset($_GET["isex"]) ) {
		$isexist = $_GET["isex"];
	}
	else {
		$isexist = 0;
	}

	if( isset($_GET["prod"]) ) {
		$product = $_GET["prod"];
	}
	else {
		$product = 1;
	}

	if( $_GET["ready"] == "on" ) unset( $_GET["ready"] );
	if( $_GET["WD_ID"] == "" ) unset( $_GET["WD_ID"] );

	$MT_IDs = implode(",", $_GET["MT_ID"]);
	$MT_IDs = $MT_IDs == "" ? "0" : $MT_IDs;

	// Применение статуса материала или смена поставщика
	if( isset($_POST["isex"]) )
	{
		foreach( $_POST as $k => $v) 
		{
			$val = $_POST["IsExist"];
			$OrderDate = $_POST["order_date"] ? date( 'Y-m-d', strtotime($_POST["order_date"]) ) : '';
			$ArrivalDate = $_POST["arrival_date"] ? date( 'Y-m-d', strtotime($_POST["arrival_date"]) ) : '';
			$Shipper = $_POST["Shipper"] ? $_POST["Shipper"] : "NULL";
			if( strpos($k,"prod") === 0 )
			{
				$prodid = (int)str_replace( "prod", "", $k );
				if( isset($_POST["IsExist"]) ) {
					$query = "UPDATE OrdersDataDetail
							  SET IsExist = $val
								 ,order_date = IF('{$OrderDate}' = '', order_date, '{$OrderDate}')
								 ,arrival_date = IF('{$ArrivalDate}' = '', arrival_date, '{$ArrivalDate}')
								 ,author = {$_SESSION['id']}
							  WHERE ODD_ID = {$prodid}";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
				if( $_POST["Shipper"] != '' ) {
					// Обновляем постовщика у материала
					$query = "UPDATE Materials SET SH_ID = {$Shipper} WHERE MT_ID = (SELECT MT_ID FROM OrdersDataDetail WHERE ODD_ID = {$prodid})";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
			}
			elseif( strpos($k,"other") === 0 ) {
				$prodid = (int)str_replace( "other", "", $k );
				if( isset($_POST["IsExist"]) ) {
					$query = "UPDATE OrdersDataBlank
							  SET IsExist = $val
								 ,order_date = IF('{$OrderDate}' = '', order_date, '{$OrderDate}')
								 ,arrival_date = IF('{$ArrivalDate}' = '', arrival_date, '{$ArrivalDate}')
								 ,author = {$_SESSION['id']}
							  WHERE ODB_ID = {$prodid}";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
				if( $_POST["Shipper"] != '' ) {
					// Обновляем постовщика у материала
					$query = "UPDATE Materials SET SH_ID = {$Shipper} WHERE MT_ID = (SELECT MT_ID FROM OrdersDataBlank WHERE ODB_ID = {$prodid})";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
			}
		}
		//header( "Location: ".$_SERVER['REQUEST_URI'] );
		exit ('<meta http-equiv="refresh" content="0; url='.$_SERVER['REQUEST_URI'].'">');
		die;
	}

	include "forms.php";
?>
	
	<form method='get' id='MTfilter'>
		<div>
			<label for='isexist'>Наличие:&nbsp;</label>
			<div class='btnset' id='isexist'>
				<input type='radio' id='isex0' name='isex' value='0' <?= ($isexist =="0" ? "checked" : "") ?>>
					<label for='isex0'>Нет</label>
				<input type='radio' id='isex1' name='isex' value='1' <?= ($isexist =="1" ? "checked" : "") ?>>
					<label for='isex1'>Заказано</label>
				<input type='radio' id='isex2' name='isex' value='2' <?= ($isexist =="2" ? "checked" : "") ?>>
					<label for='isex2'>В наличии</label>
			</div>
		</div>

		<div>
			<label for='material'>Материал:&nbsp;</label>
			<div class='btnset' id='material'>
				<input type='radio' id='prod1' name='prod' value='1' <?= ($product =="1" ? "checked" : "") ?>>
					<label for='prod1'>Ткань</label>
				<input type='radio' id='prod2' name='prod' value='2' <?= ($product =="2" ? "checked" : "") ?>>
					<label for='prod2'>Пластик</label>
			</div>
		</div>

		<div>
			<label for='ready'>Готовность:&nbsp;</label>
			<div class='btnset' id='ready'>
				<input type='radio' id='ready_all' name='ready' checked >
					<label for='ready_all'>Все</label>
				<input type='radio' id='ready_0' name='ready' value='0' <?= ($_GET["ready"] == "0" ? "checked" : "") ?>>
					<label for='ready_0'>В работе</label>
				<input type='radio' id='ready_1' name='ready' value='1' <?= ($_GET["ready"] == "1" ? "checked" : "") ?>>
					<label for='ready_1'>Готово</label>
			</div>
		</div>

		<div>
			<label for='worker'>Работник:&nbsp;</label><br>
			<select name="WD_ID" id="worker">
				<option value>Все</option>
				<option <?= ($_GET["WD_ID"] === "0") ? "selected" : "" ?> value="0">Не назначен</option>
				<?
				$query = "SELECT WD.WD_ID, WD.Name
							FROM WorkersData WD
							JOIN (
								SELECT ODS.WD_ID
								FROM OrdersDataSteps ODS
								JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID AND ST.Short LIKE '%".( $product == 1 ? "Об" : "Ст" )."%'
								UNION
								SELECT ODS.WD_ID
								FROM OrdersDataSteps ODS
								JOIN OrdersDataBlank ODB ON ODB.ODB_ID = ODS.ODB_ID
								JOIN Materials MT ON MT.MT_ID = ODB.MT_ID AND MT.PT_ID = {$product}
							) ODS ON ODS.WD_ID = WD.WD_ID";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["WD_ID"] == $_GET["WD_ID"]) ? "selected" : "";
					echo "<option {$selected} value='{$row["WD_ID"]}'>{$row["Name"]}</option>";
				}
				?>
			</select>
		</div>

		<div>
			<select name="MT_ID[]" multiple style="width: 800px; display: none;">
				<?
				$query = "SELECT MT.MT_ID, CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')') Material
							FROM Materials MT
							LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
							JOIN (
								SELECT ODD.OD_ID, ODD.MT_ID, ODD.IsExist, IFNULL(ODS_ST.WD_ID, 0) WD_ID, IF(ODS_ST.IsReady = 1, 1, IF(ODS_ST.IsReady = 0 AND ODS_ST.WD_ID IS NOT NULL, 0, NULL)) IsReady
								FROM OrdersDataDetail ODD
								LEFT JOIN (
									SELECT ODS.ODD_ID, ODS.WD_ID, ODS.IsReady
									FROM OrdersDataSteps ODS
									JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID AND (ST.Short LIKE '%Ст%' OR ST.Short LIKE '%Об%')
									WHERE ODS.Visible = 1 AND ODS.Old != 1 AND ODS.ODD_ID IS NOT NULL
									GROUP BY ODS.ODD_ID
								) ODS_ST ON ODS_ST.ODD_ID = ODD.ODD_ID
								UNION
								SELECT ODB.OD_ID, ODB.MT_ID, ODB.IsExist, IFNULL(ODS_ST.WD_ID, 0) WD_ID, IF(ODS_ST.IsReady = 1, 1, IF(ODS_ST.IsReady = 0 AND ODS_ST.WD_ID IS NOT NULL, 0, NULL)) IsReady
								FROM OrdersDataBlank ODB
								LEFT JOIN (
									SELECT ODS.ODB_ID, ODS.WD_ID, ODS.IsReady
									FROM OrdersDataSteps ODS
									WHERE ODS.Visible = 1 AND ODS.Old != 1 AND ODS.ODB_ID IS NOT NULL
									GROUP BY ODS.ODB_ID
								) ODS_ST ON ODS_ST.ODB_ID = ODB.ODB_ID
							) ODD_ODB ON ODD_ODB.MT_ID = MT.MT_ID AND ODD_ODB.IsExist = {$isexist}".( isset( $_GET["ready"] ) ? " AND ODD_ODB.IsReady = {$_GET["ready"]}" : "" ).( isset( $_GET["WD_ID"] ) ? " AND ODD_ODB.WD_ID = {$_GET["WD_ID"]}" : "" )."
							LEFT JOIN OrdersData OD ON OD.OD_ID = ODD_ODB.OD_ID
							WHERE MT.PT_ID = {$product} AND OD.ReadyDate IS NULL
							GROUP BY MT.MT_ID
							ORDER BY MT.Material";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = in_array($row["MT_ID"], $_GET["MT_ID"]) ? "selected" : "";
					echo "<option {$selected} value='{$row["MT_ID"]}'>{$row["Material"]}</option>";
				}
				?>
			</select>
		</div>

		<button>Фильтр</button>
	</form>

	<form method='post' style='position: relative;'>
		<a id="copy-button" class="button" data-clipboard-target="#materials_name" style="position: absolute; left: 150px;" title="Скопировать список материалов в буфер обмена">Скопировать</a>
	<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>
	<table>
		<thead>
		<tr class="nowrap">
			<th></th>
			<th>Материал</th>
			<th>Поставщик</th>
			<?=($product == 1 ? "<th>Метраж</th>" : "")?>
			<th>Код</th>
			<th>Принят</th>
			<th>Работник</th>
			<th>Заказ</th>
			<th>Цвет</th>
			<th>Заказчик<br>Дата продажи - Дата сдачи<br>Салон (№ квитанции)</th>
			<th>Примечание</th>
		</tr>
		</thead>
		<tbody>
<?
//	if( $product > 0 ) {
	$oddids = ""; // Будем собирать ID видимых изделий
	$odbids = ""; // Будем собирать ID видимых заготовок
	$query = "SELECT OD.OD_ID
					,OD.Code
					,OD.ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,OD.OrderNumber
					,OD.Comment
					,OD.IsPainting
					,IFNULL(OD.Color, '<a href=\"/orderdetail.php\">Свободные</a>') Color
					,IF(DATEDIFF(OD.EndDate, NOW()) <= 7, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
					,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
					,GROUP_CONCAT(ODD_ODB.Zakaz_lock SEPARATOR '') Zakaz_lock
					,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
					,GROUP_CONCAT(ODD_ODB.Shipper SEPARATOR '') Shipper
					,GROUP_CONCAT(ODD_ODB.MT_amount SEPARATOR '') MT_amount
					,GROUP_CONCAT(ODD_ODB.Checkbox SEPARATOR '') Checkbox
					,GROUP_CONCAT(ODD_ODB.ODD_ID SEPARATOR ',') ODD_ID
					,GROUP_CONCAT(ODD_ODB.ODB_ID SEPARATOR ',') ODB_ID
					,WD.Name
					,IF(ODD_ODB.IsReady = 1, 'ready', 'inwork') ready_status
					,IF(OS.ostatok IS NOT NULL, 1, 0) is_lock
					,OD.confirmed
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
			  RIGHT JOIN (
						  SELECT ODD.OD_ID
								,ODD.ODD_ID ItemID
								,ODD.ODD_ID
								,0 ODB_ID
								,IFNULL(PM.PT_ID, 2) PT_ID

							   ,CONCAT('<b style=\'line-height: 1.79em;\'><a ".(in_array('order_add', $Rights) ? "href=\'#\'" : "")." id=\'prod', ODD.ODD_ID, '\' location=\'{$location}\' class=\'".(in_array('order_add', $Rights) ? "edit_product', IFNULL(PM.PT_ID, 2), '" : "")."\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), ''), '</a></b><br>') Zakaz

							   ,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), ''), '</i></b><br>') Zakaz_lock

							   #,CONCAT('<div', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), '</div>') Zakaz

								,CONCAT('<div class=\'wr_mt\'>', IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span ptid=\'{$product}\' mtid=\'', ODD.MT_ID, '\' class=\'mt', ODD.MT_ID, IF(MT.removed = 1, ' removed', ''), ' material ".(in_array('screen_materials', $Rights) ? " mt_edit " : "")."',
								CASE ODD.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
								'\'>', IFNULL(MT.Material, ''), '</span><input type=\'text\' class=\'materialtags_', IFNULL(MT.PT_ID, ''), '\' style=\'display: none;\' title=\'Для отмены изменений нажмите клавишу ESC\'><input type=\'checkbox\' style=\'display: none;\' title=\'Выведен\'></div>') Material

								,CONCAT( '<div>', IFNULL(SH.Shipper, '-=Другой=-'), '</div>' ) Shipper

								,CONCAT( '<input class=\'footage\' type=\'number\' step=\'0.1\' min=\'0\' style=\'width: 50px; height: 19px;\' value=\'', IFNULL(ODD.MT_amount, ''), '\' oddid=\'', ODD.ODD_ID, '\'>' ) MT_amount

								,CONCAT('<input type=\'checkbox\' value=\'1\' name=\'prod', ODD.ODD_ID, '\' class=\'chbox\'><br>') Checkbox

								,IFNULL(ODS_ST.WD_ID, 0) WD_ID
								#,IFNULL(ODS_ST.IsReady, 0) IsReady
								,IF(ODS_ST.IsReady = 1, 1, IF(ODS_ST.IsReady = 0 AND ODS_ST.WD_ID IS NOT NULL, 0, NULL)) IsReady

						  FROM OrdersDataDetail ODD
						  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						  JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						  LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
						  LEFT JOIN (
							  SELECT ODS.ODD_ID, ODS.WD_ID, ODS.IsReady
							  FROM OrdersDataSteps ODS
							  JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID AND (ST.Short LIKE '%Ст%' OR ST.Short LIKE '%Об%')
							  WHERE ODS.Visible = 1 AND ODS.Old != 1 AND ODS.ODD_ID IS NOT NULL
							  GROUP BY ODS.ODD_ID
						  ) ODS_ST ON ODS_ST.ODD_ID = ODD.ODD_ID
						  WHERE ODD.IsExist = {$isexist} AND IFNULL(PM.PT_ID, 2) = {$product}
						  AND (ODD.MT_ID IN ({$MT_IDs}) OR '{$MT_IDs}' = '0')
						  UNION ALL
						  SELECT ODB.OD_ID
								,ODB.ODB_ID ItemID
								,0 ODD_ID
								,ODB.ODB_ID
								,0 PT_ID

								,CONCAT('<b style=\'line-height: 1.79em;\'><a ".(in_array('order_add', $Rights) ? "href=\'#\'" : "")." id=\'blank', ODB.ODB_ID, '\'', 'class=\'".(in_array('order_add', $Rights) ? "edit_order_blank" : "")."\' location=\'{$location}\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), ''), '</a></b><br>') Zakaz

								,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), ''), '</i></b><br>') Zakaz_lock

								#,CONCAT('<div', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), '</div>') Zakaz

								,CONCAT('<div class=\'wr_mt\'>', IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0 AND ODB.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), ''), '<span ptid=\'{$product}\' mtid=\'', ODB.MT_ID, '\' class=\'mt', ODB.MT_ID, IF(MT.removed = 1, ' removed', ''), ' material ".(in_array('screen_materials', $Rights) ? " mt_edit " : "")."',
								CASE ODB.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODB.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
								'\'>', IFNULL(MT.Material, ''), '</span><input type=\'text\' class=\'materialtags_', IFNULL(MT.PT_ID, ''), '\' style=\'display: none;\' title=\'Для отмены изменений нажмите клавишу ESC\'><input type=\'checkbox\' style=\'display: none;\' title=\'Выведен\'></div>') Material

								,CONCAT( '<div>', IFNULL(SH.Shipper, '-=Другой=-'), '</div>' ) Shipper

								,CONCAT( '<input class=\'footage\' type=\'number\' step=\'0.1\' min=\'0\' style=\'width: 50px; height: 19px;\' value=\'', IFNULL(ODB.MT_amount, ''), '\' odbid=\'', ODB.ODB_ID, '\'>' ) MT_amount

								,CONCAT('<input type=\'checkbox\' value=\'1\' name=\'other', ODB.ODB_ID, '\' class=\'chbox\'><br>') Checkbox

								,IFNULL(ODS_ST.WD_ID, 0) WD_ID
								#,IFNULL(ODS_ST.IsReady, 0) IsReady
								,IF(ODS_ST.IsReady = 1, 1, IF(ODS_ST.IsReady = 0 AND ODS_ST.WD_ID IS NOT NULL, 0, NULL)) IsReady

						  FROM OrdersDataBlank ODB
						  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						  JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
						  LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
						  LEFT JOIN (
							  SELECT ODS.ODB_ID, ODS.WD_ID, ODS.IsReady
							  FROM OrdersDataSteps ODS
							  WHERE ODS.Visible = 1 AND ODS.Old != 1 AND ODS.ODB_ID IS NOT NULL
							  GROUP BY ODS.ODB_ID
						  ) ODS_ST ON ODS_ST.ODB_ID = ODB.ODB_ID
						  WHERE ODB.IsExist = {$isexist} AND MT.PT_ID = {$product}
						  AND (ODB.MT_ID IN ({$MT_IDs}) OR '{$MT_IDs}' = '0')
						  ORDER BY PT_ID DESC, ItemID
						  ) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  LEFT JOIN WorkersData WD ON WD.WD_ID = ODD_ODB.WD_ID
			  WHERE OD.ReadyDate IS NULL ".( isset( $_GET["ready"] ) ? "AND ODD_ODB.IsReady = {$_GET["ready"]}" : "" ).( isset( $_GET["WD_ID"] ) ? " AND ODD_ODB.WD_ID = {$_GET["WD_ID"]}" : "" )."
			  GROUP BY OD.OD_ID
			  ORDER BY OD.OD_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$oddids .= $row["ODD_ID"].","; // Собираем ID видимых изделий
		$odbids .= $row["ODB_ID"].","; // Собираем ID видимых заготовок
		echo "<tr id='ord{$row["OD_ID"]}'>";
		echo "<td>{$row["Checkbox"]}</td>";
		echo "<td><span class='nowrap'>{$row["Material"]}</span></td>";
		echo "<td><span class='nowrap'>{$row["Shipper"]}</span></td>";
		if( $product == 1 ) echo "<td>{$row["MT_amount"]}</td>";
		echo "<td><a href='orderdetail.php?id={$row["OD_ID"]}' class='nowrap'>{$row["Code"]}</a></td>";
		// Если заказ принят
		if( $row["confirmed"] == 1 ) {
			$class = 'confirmed';
			$title = 'Принят в работу';
		}
		else {
			$class = 'not_confirmed';
			$title = 'Не принят в работу';
		}
		echo "<td class='{$class}' title='{$title}'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";
		echo "<td><span class='{$row["ready_status"]}'>{$row["Name"]}</td>";

		if( $row["is_lock"] or ( $row["confirmed"] and !in_array('order_add_confirm', $Rights) ) ) {
			echo "<td><span class='nowrap'>{$row["Zakaz_lock"]}</span></td>";
		}
		else {
			echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		}

		switch ($row["IsPainting"]) {
			case 1:
				echo "<td class='notready' title='Не в работе'>{$row["Color"]}</td>";
				break;
			case 2:
				echo "<td class='inwork' title='В работе'>{$row["Color"]}</td>";
				break;
			case 3:
				echo "<td class='ready' title='Готово'>{$row["Color"]}</td>";
				break;
			default:
				echo "<td></td>";
				break;
		}
//		echo "<td>{$row["ClientName"]}</td>";
//		echo "<td>{$row["StartDate"]}</td>";
//		echo "<td><span class='{$row["Deadline"]}'>{$row["EndDate"]}</span></td>";
//		echo "<td style='background: {$row["CTColor"]};'>{$row["Shop"]}</td>";
//		echo "<td>{$row["OrderNumber"]}</td>";
		echo "<td style='background: {$row["CTColor"]};' class='nowrap'>";
		echo "{$row["ClientName"]}<br>";
		echo "{$row["StartDate"]} - <span class='{$row["Deadline"]}'>{$row["EndDate"]}</span><br>";
		echo "{$row["Shop"]} ({$row["OrderNumber"]})<br>";
		echo "</td>";
		echo "<td>{$row["Comment"]}</td>";
		echo "</tr>";

		// Заполнение массива для JavaScript
		$query = "SELECT ODD.ODD_ID
						,ODD.Amount
						,ODD.Price
						,ODD.PM_ID
						,ODD.PF_ID
						,ODD.PME_ID
						,ODD.Length
						,ODD.Width
						,ODD.PieceAmount
						,ODD.PieceSize
						,ODD.Comment
						,IFNULL(MT.Material, '') Material
						,IFNULL(MT.SH_ID, '') Shipper
						,ODD.IsExist
                        ,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
                        ,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
						,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
						,ODD.patina
				  FROM OrdersDataDetail ODD
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
				  LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
				  WHERE ODD.OD_ID ".( $row["OD_ID"] == "" ? "IS NULL" : "= {$row["OD_ID"]}" )."
				  GROUP BY ODD.ODD_ID";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $sub_row = mysqli_fetch_array($result) )
		{
			$ODD[$sub_row["ODD_ID"]] = array( "amount"=>$sub_row["Amount"], "price"=>$sub_row["Price"], "model"=>$sub_row["PM_ID"], "form"=>$sub_row["PF_ID"], "mechanism"=>$sub_row["PME_ID"], "length"=>$sub_row["Length"], "width"=>$sub_row["Width"], "PieceAmount"=>$sub_row["PieceAmount"], "PieceSize"=>$sub_row["PieceSize"], "color"=>$sub_row["Color"], "comment"=>$sub_row["Comment"], "material"=>$sub_row["Material"], "shipper"=>$sub_row["Shipper"], "isexist"=>$sub_row["IsExist"], "inprogress"=>$sub_row["inprogress"], "order_date"=>$sub_row["order_date"], "arrival_date"=>$sub_row["arrival_date"], "patina"=>$sub_row["patina"] );
		}

		$query = "SELECT ODB.ODB_ID
						,ODB.Amount
						,ODB.Price
						,ODB.BL_ID
						,ODB.Other
						,ODB.Comment
						,IFNULL(MT.Material, '') Material
						,IFNULL(MT.SH_ID, '') Shipper
						,ODB.IsExist
						,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
						,DATE_FORMAT(ODB.order_date, '%d.%m.%Y') order_date
						,DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y') arrival_date
						,IFNULL(MT.PT_ID, 0) MPT_ID
						,ODB.patina
				  FROM OrdersDataBlank ODB
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID AND ODS.Visible = 1
				  LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
				  WHERE ODB.OD_ID ".( $row["OD_ID"] == "" ? "IS NULL" : "= {$row["OD_ID"]}" )."
				  GROUP BY ODB.ODB_ID";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $sub_row = mysqli_fetch_array($result) )
		{
			$ODB[$sub_row["ODB_ID"]] = array( "amount"=>$sub_row["Amount"], "price"=>$sub_row["Price"], "blank"=>$sub_row["BL_ID"], "other"=>$sub_row["Other"], "comment"=>$sub_row["Comment"], "material"=>$sub_row["Material"], "shipper"=>$sub_row["Shipper"], "isexist"=>$sub_row["IsExist"], "inprogress"=>$sub_row["inprogress"], "order_date"=>$sub_row["order_date"], "arrival_date"=>$sub_row["arrival_date"], "MPT_ID"=>$sub_row["MPT_ID"], "patina"=>$sub_row["patina"] );
		}
	}
?>
		</tbody>
	</table>

	<!-- Список материалов для буфера обмена -->
	<textarea id='materials_name' style='position: absolute; top: 34px; left: 1px; height: 20px; z-index: -1;'></textarea>

	<p><input type='checkbox' id='selectallbottom'><label for='selectallbottom'>Выбрать все</label></p>
	<p>
		<div class='btnset radiostatus'>
			<input type='radio' id='radio0' name='IsExist' value='0'>
				<label for='radio0'>Нет</label>
			<input type='radio' id='radio1' name='IsExist' value='1'>
				<label for='radio1'>Заказано</label>
			<input type='radio' id='radio2' name='IsExist' value='2'>
				<label for='radio2'>В наличии</label>
		</div>
		<div class='order_material' style='display: none;'>
			<span>Дата заказа:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>Ожидается:</span><br>
			<input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
			&nbsp;&nbsp;-&nbsp;&nbsp;
			<input class='date to' type='text' name='arrival_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y", strtotime("+14 days")) ?>" readonly>
		</div>
	</p>
	<p>
		<label for="Shipper">Поставщик:</label>
		<select id="Shipper" name="Shipper" style="width: 110px;" title="Поставщик">
			<option value=""></option>
			<option value="0">-=Другой=-</option>
			<?
			if( $product > 0 ) {
				$query = "SELECT SH_ID, Shipper FROM Shippers WHERE PT_ID = {$product}";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
				}
			}
			else {
				echo "<optgroup label='Ткань'>";
					$query = "SELECT SH_ID, Shipper FROM Shippers WHERE PT_ID = 1";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
					}
				echo "</optgroup>";
				echo "<optgroup label='Пластик'>";
					$query = "SELECT SH_ID, Shipper FROM Shippers WHERE PT_ID = 2";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
					}
				echo "</optgroup>";
			}
			?>
		</select>
	</p>
	<input type="hidden" name="isex" value="1">
	<input type='submit' value='Применить'>
	</form>

<script>
	$(document).ready(function(){

		new Clipboard('#copy-button'); // Копирование материалов в буфер
		$("#copy-button").click(function() {
			noty({timeout: 3000, text: 'Список материалов скопирована в буфер обмена', type: 'success'});
		});

		function selectall(ch)
		{
			$('.chbox').prop('checked', ch);
			$('#selectalltop').prop('checked', ch);
			$('#selectallbottom').prop('checked', ch);
			return false;
		}

		function material_list() {
			$.ajax({ url: "ajax.php?do=material_list&oddids=<?=$oddids?>0&odbids=<?=$odbids?>0", dataType: "script", async: false });
		}

		material_list();

		$(function() {
			$('#selectalltop').change(function(){
				ch = $('#selectalltop').prop('checked');
				selectall(ch);
				return false;
			});

			$('#selectallbottom').change(function(){
				ch = $('#selectallbottom').prop('checked');
				selectall(ch);
				return false;
			});

			$('.chbox').change(function(){
				var checked_status = true;
				$('.chbox').each(function(){
					if( !$(this).prop('checked') )
					{
						checked_status = $(this).prop('checked');
					}
				});
				$('#selectalltop').prop('checked', checked_status);
				$('#selectallbottom').prop('checked', checked_status);
				return false;
			});
		});

		$('#material input').change(function(){
			$('select[name="WD_ID"] option').removeAttr('selected');
		});

		$('.footage').on('blur', function() {
			var val = $(this).val();
			var oddid = $(this).attr('oddid');
			var odbid = $(this).attr('odbid');
			$.ajax({ url: "ajax.php?do=footage&oddid="+oddid+"&odbid="+odbid+"&val="+val, dataType: "script", async: false });
			material_list();
		});

		$('#isexist input, #material input, #ready input, #worker').change(function(){
			$('select[name="MT_ID[]"] option').removeAttr('selected');
			$('#MTfilter').submit();
		});

		$('select[name="MT_ID[]"]').select2({
			placeholder: "Выберите интересующие материалы",
			allowClear: true,
			closeOnSelect: false,
			language: "ru"
		});

		$( ".textiletags" ).autocomplete({ // Автокомплит тканей
			source: "autocomplete.php?do=textiletags",
			minLength: 2,
			select: function( event, ui ) {
				$('select[name="Shipper"]').val(ui.item.SH_ID);
			},
			create: function() {
				$(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					var listItem = $( "<li>" )
						.append( item.label )
						.appendTo( ul );

					if (item.removed == 1) {
						listItem.addClass( "removed" ).attr( "title", "Выведен!" )
					}

					return listItem;
				}
			}
		});

		$( ".plastictags" ).autocomplete({ // Автокомплит пластиков
			source: "autocomplete.php?do=plastictags",
			minLength: 2,
			select: function( event, ui ) {
				$('select[name="Shipper"]').val(ui.item.SH_ID);
			},
			create: function() {
				$(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					var listItem = $( "<li>" )
						.append( item.label )
						.appendTo( ul );

					if (item.removed == 1) {
						listItem.addClass( "removed" ).attr( "title", "Выведен!" )
					}

					return listItem;
				}
			}
		});

		$( ".textileplastictags" ).autocomplete({ // Автокомплит материалов
			source: "autocomplete.php?do=textileplastictags",
			minLength: 2,
			select: function( event, ui ) {
				$('select[name="Shipper"]').val(ui.item.SH_ID);
			},
			create: function() {
				$(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					var listItem = $( "<li>" )
						.append( item.label )
						.appendTo( ul );

					if (item.removed == 1) {
						listItem.addClass( "removed" ).attr( "title", "Выведен!" )
					}

					return listItem;
				}
			}
		});

		// При очистке поля с материалом - очищаем поставщика
		$( ".textiletags, .plastictags, .textileplastictags" ).on("keyup", function() {
			if( $(this).val().length < 2 ) {
				$('select[name="Shipper"]').val('');
			}
		});

		odd = <?= json_encode($ODD) ?>;
		odb = <?= json_encode($ODB) ?>;
	});
</script>

<?
	include "footer.php";
?>
