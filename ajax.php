<?
	include "config.php";
	$_GET['ajax'] = 1;
	include "checkrights.php";
	header( "Content-Type: text/html; charset=UTF-8" );

switch( $_GET["do"] )
{
// Генерирование формы этапов производства
case "steps":
	if( isset($_GET["odd_id"]) ) {
		$odd_id = (int)$_GET["odd_id"];
		$other = 0;
	}
	else {
		$odb_id = (int)$_GET["odb_id"];
		$other = 1;
	}
	
	// Получение информации об изделии
	if( $other == 0 ) {
		$query = "SELECT IFNULL(PM.PT_ID, 2) PT_ID
						,PM.Model
						,IFNULL(CONCAT(ODD.Length, 'х', ODD.Width, IFNULL(CONCAT('/', ODD.PieceAmount, 'x', ODD.PieceSize), '')), '') Size
						,CONCAT(PF.Form, ' ', PME.Mechanism) Form
						,ODD.Amount
				  FROM OrdersDataDetail ODD
				  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
				  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
				  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
				  WHERE ODD.ODD_ID = $odd_id";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		$pt = mysqli_result($res,0,'PT_ID');
		$model = mysqli_result($res,0,'Model');
		$size = mysqli_result($res,0,'Size');
		$form = mysqli_result($res,0,'Form');
		$amount = mysqli_result($res,0,'Amount');
		$product = "<img src=\'/img/product_{$pt}.png\'>x{$amount}&nbsp;{$model}&nbsp;{$size}&nbsp;{$form}";

		// Получение информации об этапах производства
		$query = "SELECT ST.ST_ID, ST.Step, ODS.WD_ID, IF(ODS.WD_ID IS NULL, 'disabled', '') disabled, ODS.Tariff, IF (ODS.IsReady, 'checked', '') IsReady, IF(ODS.Visible = 1, 'checked', '') Visible, ODS.Old
				  FROM OrdersDataSteps ODS
				  JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
				  WHERE ODS.ODD_ID = $odd_id
				  ORDER BY ODS.Old DESC, ST.Sort";
		$result = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

		$text = "<input type=\'hidden\' name=\'ODD_ID\' value=\'$odd_id\'>";
	}
	else {
		$query = "SELECT IFNULL(BL.Name, ODB.Other) Name
						,ODB.Amount
				  FROM OrdersDataBlank ODB
				  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
				  WHERE ODB.ODB_ID = $odb_id";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		$model = mysqli_result($res,0,'Name');
		$amount = mysqli_result($res,0,'Amount');
		$product = "<img src=\'/img/product_0.png\'>x{$amount}&nbsp;{$model}";

		// Получение информации об этапах производства
		$query = "SELECT 0 ST_ID, '-' Step, ODS.WD_ID, IF(ODS.WD_ID IS NULL, 'disabled', '') disabled, ODS.Tariff, IF (ODS.IsReady, 'checked', '') IsReady, IF(ODS.Visible = 1, 'checked', '') Visible, ODS.Old
				  FROM OrdersDataSteps ODS
				  WHERE ODS.ODB_ID = $odb_id
				  ORDER BY ODS.Old DESC";
		$result = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

		$text = "<input type=\'hidden\' name=\'ODB_ID\' value=\'$odb_id\'>";
	}

	$text .= "<h3>$product</h3>";
	$text .= "<table><thead>";
	$text .= "<tr><th>Этап</th>";
	$text .= "<th>Работник</th>";
	$text .= "<th>Тариф</th>";
	$text .= "<th>Готовность</th>";
	$text .= "<th title=\'Видимые\'><i class=\'fa fa-eye\' aria-hidden=\'true\'></i></th></tr>";
	$text .= "</thead><tbody>";

	while( $row = mysqli_fetch_array($result) )
	{
		// Формирование дропдауна со списком рабочих. Сортировка по релевантности.
		$selectworker = "<option value=\'\'>-=Выберите работника=-</option>";
		if( $other == 0 ) {
			$query = "SELECT WD.WD_ID, WD.Name, SUM(IFNULL(ODS.Amount, 0)) CNT
					  FROM WorkersData WD
					  LEFT JOIN (
						SELECT ODS.*, ODD.Amount
						FROM OrdersDataSteps ODS
						JOIN OrdersDataDetail ODD ON ODD.ODD_ID = ODS.ODD_ID
						WHERE ODS.WD_ID IS NOT NULL AND IFNULL(ODS.ST_ID, 0) = {$row["ST_ID"]}
						LIMIT 100
					  ) ODS ON ODS.WD_ID = WD.WD_ID
					  WHERE WD.Type = 1
					  GROUP BY WD.WD_ID
					  ORDER BY CNT DESC";
		}
		else {
			$query = "SELECT WD.WD_ID, WD.Name, SUM(IFNULL(ODS.Amount, 0)) CNT
					  FROM WorkersData WD
					  LEFT JOIN (
						SELECT ODS.*, ODB.Amount
						FROM OrdersDataSteps ODS
						JOIN OrdersDataBlank ODB ON ODB.ODB_ID = ODS.ODB_ID
						WHERE ODS.WD_ID IS NOT NULL
						LIMIT 100
					  ) ODS ON ODS.WD_ID = WD.WD_ID
					  WHERE WD.Type = 1
					  GROUP BY WD.WD_ID
					  ORDER BY CNT DESC";
		}
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		while( $subrow = mysqli_fetch_array($res) )
		{
			$selected = ( $row["WD_ID"] == $subrow["WD_ID"] ) ? "selected" : "";
			$selectworker .= "<option {$selected} value=\'{$subrow["WD_ID"]}\'>{$subrow["Name"]}</option>";
		}
		// Конец дропдауна со списком рабочих
		
		if( $row["Old"] == 1 ) {
			$text .= "<tr style=\'background: #999;\'><td><b>{$row["Step"]}</b></td>";
			$text .= "<td><select disabled class=\'selectwr\'>{$selectworker}</select></td>";
			$text .= "<td><input disabled type=\'number\' class=\'tariff\' value=\'{$row["Tariff"]}\'></td>";
			$text .= "<td><input disabled type=\'checkbox\' id=\'OldIsReady{$row["ST_ID"]}\' class=\'isready\' {$row["IsReady"]}><label for=\'OldIsReady{$row["ST_ID"]}\'></label></td>";
			$text .= "<td><input disabled type=\'checkbox\' {$row["Visible"]}></td></tr>";
		}
		else {
			$text .= "<tr><td><b>{$row["Step"]}</b></td>";
			$text .= "<td><select name=\'WD_ID{$row["ST_ID"]}\' id=\'{$row["ST_ID"]}\' class=\'selectwr\'>{$selectworker}</select></td>";
			$text .= "<td><input type=\'number\' min=\'0\' name=\'Tariff{$row["ST_ID"]}\' class=\'tariff\' value=\'{$row["Tariff"]}\'></td>";
			$text .= "<td><input type=\'checkbox\' id=\'IsReady{$row["ST_ID"]}\' name=\'IsReady{$row["ST_ID"]}\' class=\'isready\' value=\'1\' {$row["IsReady"]} {$row["disabled"]}><label for=\'IsReady{$row["ST_ID"]}\'></label></td>";
			$text .= "<td><input type=\'checkbox\' name=\'Visible{$row["ST_ID"]}\' value=\'1\' {$row["Visible"]}></td></tr>";
		}
	}
	$text .= "</tbody></table>";
	echo "window.top.window.$('#formsteps').html('{$text}');";
	break;

// живой поиск в свободных изделиях
case "livesearch":
		
	$pt = $_GET["type"];
		
	// Таблица изделий
	$query = "SELECT ODD.ODD_ID
					,PM.PT_ID
					,ODD.Amount
					,PM.Model
					,CONCAT(PF.Form, ' ', PME.Mechanism) Form
					,IFNULL(CONCAT(ODD.Length, 'х', ODD.Width), '') Size
					,CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')') Material
					,ODD.IsExist
					,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
					,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
					,IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), '') clock
					,IF(ODD.is_check = 1, '', 'attention') is_check
					,SUM(IF(ODS.WD_ID IS NULL, 0, 1)) progress
					,GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR '') Steps
					,IF(SUM(ODS.Old) > 0, ' attention', '') Attention
					,ODD.Comment
			  FROM OrdersDataDetail ODD
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
			  LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
			  LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
			  LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
			  LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID";
	$query .= " WHERE ODD.OD_ID IS NULL";
	$query .= ( $pt == 1 ) ? " AND PM.PT_ID = {$pt}" : "";
	$query .= ($_GET["model"] and $_GET["model"] <> "undefined") ? " AND (ODD.PM_ID = {$_GET["model"]} OR ODD.PM_ID IS NULL)" : "";
	$query .= ($_GET["form"] and $_GET["form"] <> "undefined") ? " AND ODD.PF_ID = {$_GET["form"]}" : "";
	$query .= ($_GET["mechanism"] and $_GET["mechanism"] <> "undefined") ? " AND ODD.PME_ID = {$_GET["mechanism"]}" : "";
//	$query .= ($_GET["length"] and $_GET["length"] <> "undefined") ? " AND ODD.Length = {$_GET["length"]}" : "";
//	$query .= ($_GET["width"] and $_GET["width"] <> "undefined") ? " AND ODD.Width = {$_GET["width"]}" : "";
//	$query .= ($_GET["material"]) ? " AND MT.Material LIKE '%{$_GET["material"]}%'" : "";
	$query .= " GROUP BY ODD.ODD_ID";
	$query .= " ORDER BY progress DESC";
	
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	if( mysqli_num_rows($res) > 0) {
		$hidden = "<input type='hidden' name='free'>";
		$table = "<table><thead><tr>";
		$table .= "<th></th>";
		$table .= "<th class='nowrap'>Кол-во</th>";
		$table .= "<th>Модель</th>";
		if( $pt == 2 ) {
			$table .= "<th>Форма</th>";
			$table .= "<th>Размер</th>";
		}
		$table .= "<th>Этапы</th>";
		$table .= ($pt == 1) ? "<th>Ткань</th>" : "<th>Пластик</th>";
		$table .= "<th>Примечание</th>";
		$table .= "</tr></thead><tbody>";
	}
		
	$count = 0;
	while( $row = mysqli_fetch_array($res) )
	{
		$count = $count + $row["Amount"];
		$table .= "<tr class='{$row["is_check"]} nowrap free-amount'>";
		$table .= "<td><input type='checkbox' value='1' class='chbox'><span><input type='number' disabled min='1' max='{$row["Amount"]}' value='{$row["Amount"]}' name='amount{$row["ODD_ID"]}' autocomplete='off' title='Пожалуйста укажите требуемое количество изделий.'> из</span></td>";
		$table .= "<td>{$row["Amount"]}</td>";
		$table .= "<td>{$row["Model"]}</td>";
		if( $pt == 2 ) {
			$table .= "<td>{$row["Form"]}</td>";
			$table .= "<td>{$row["Size"]}</td>";
		}
		$table .= "<td><a class='edit_steps nowrap shadow{$row["Attention"]}'>{$row["Steps"]}</a></td>";
		$table .= "<td>";
		switch ($row["IsExist"]) {
			case 0:
				$table .= "<span class='bg-red'>";
				break;
			case 1:
				$table .= "{$row["clock"]}<span class='bg-yellow' title='Заказано: {$row["order_date"]}&emsp;Ожидается: {$row["arrival_date"]}'>";
				break;
			case 2:
				$table .= "<span class='bg-green'>";
				break;
		}
		$table .= "{$row["Material"]}</span></td>";
		$table .= "<td>{$row["Comment"]}</td></tr>";
	}

	$table .= "</tbody></table>";
	$table = addcslashes($table, "'");
	$hidden = addcslashes($hidden, "'");

	echo "window.top.window.$('#{$_GET["this"]} .accordion div').html('{$hidden}{$table}');";
	echo "window.top.window.$('#{$_GET["this"]} .accordion h3 span').html('{$count}');";
	// Скрипт блокировки формы при выборе изделия из списка "Свободных"
	echo "window.top.window.$('.accordion .chbox, .accordion input[type=\"number\"]').change(function(){";
	echo "var amount = 0;";
	echo "$('#{$_GET["this"]} .accordion .chbox').each(function(){";
	echo "if( $(this).prop('checked') ) { amount += parseInt($('~ span > input', this).val()); $('~ span > input', this).prop( 'disabled', false );}";
	echo "else { $('~ span > input', this).prop( 'disabled', true ); }});";
	echo "if( amount ){ $('#{$_GET["this"]} fieldset').prop('disabled', true); $( '#{$_GET["this"]} #forms, #{$_GET["this"]} #mechanisms' ).buttonset( 'option', 'disabled', true ); $('#{$_GET["this"]} fieldset input[name=\"Amount\"]').val(amount); $('#{$_GET["this"]} input[name=free]').val(1);}";
	echo "else{ $('#{$_GET["this"]} fieldset').prop('disabled', false); $( '#{$_GET["this"]} #forms, #{$_GET["this"]} #mechanisms' ).buttonset( 'option', 'disabled', false ); $('#{$_GET["this"]} fieldset input[name=\"Amount\"]').val(1); $('#{$_GET["this"]} input[name=free]').val(0);}";
	echo "materialonoff('#{$_GET["this"]}');";
	echo "return false;";
	echo "});";
	break;

// Смена статуса лакировки
case "ispainting":

	$id = $_GET["od_id"];
	$isready = $_GET["isready"];
	$archive = $_GET["archive"];
	$val = $_GET["val"];
	$val = ($val == 3) ? 1 : $val + 1;
	$shpid = $_GET["shpid"];
	$filter = $_GET["filter"];

	// Обновляем статус лакировки
	$query = "UPDATE OrdersData SET IsPainting = {$val}, author = {$_SESSION['id']} WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

	// Получаем статус лакировки и отгрузку из базы
	$query = "SELECT IsPainting, IFNULL(SHP_ID, 0) SHP_ID, IFNULL(SH_ID, 0) SH_ID FROM OrdersData WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$val = mysqli_result($res,0,'IsPainting');
	$SHP_ID = mysqli_result($res,0,'SHP_ID');
	$SH_ID = mysqli_result($res,0,'SH_ID');

	switch ($val) {
		case 1:
			$class = "notready";
			$status = "Не в работе";
			break;
		case 2:
			$class = "inwork";
			$status = "В работе";
			break;
		case 3:
			$class = "ready";
			$status = "Готово";
			break;
	}

	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.painting').removeClass('notready inwork ready');";
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.painting').addClass('{$class}');";
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.painting').attr('title', '{$status}');";
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.painting').attr('val', '{$val}');";

	// Если из отгрузки
	if( $shpid > 0 ) {
		// Узнаем все ли этапы завершены
		$query = "SELECT BIT_AND(IF(OD.IsPainting = 3, 1, 0)) IsPainting, BIT_AND(ODD_ODB.IsReady) IsReady
					FROM OrdersData OD
					JOIN (
						SELECT ODD.OD_ID, ODS.IsReady
						FROM OrdersDataDetail ODD
						JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1 AND ODS.Old = 0
						UNION ALL
						SELECT ODB.OD_ID, ODS.IsReady
						FROM OrdersDataBlank ODB
						JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID AND ODS.Visible = 1 AND ODS.Old = 0
					) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
					WHERE OD.SHP_ID = {$shpid}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		$painting = mysqli_result($res,0,'IsPainting');
		$ready = mysqli_result($res,0,'IsReady');
		$is_orders_ready = ( $painting and $ready ) ? 1 : 0;

		echo "check_shipping({$is_orders_ready}, 1, {$filter});";
	}
	elseif( $isready == 1 and $archive != 1 and $SHP_ID == 0 ) {
		if( $val == 3 and in_array('order_ready', $Rights) ) {
			echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] action').html('<a  href=\"#\" class=\"\" ".( $SH_ID == 0 ? 'style=\"display: none;\"' : '')." onclick=\'if(confirm(\"Пожалуйста, подтвердите готовность заказа!\", \"?ready={$id}\")) return false;\' title=\'Готово\'><i style=\'color:red;\' class=\'fa fa-flag-checkered fa-lg\'></i></a>');";
//			echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] span.action a').button();";
		}
		else {
			echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] action').html('');";
		}
	}
	echo "noty({timeout: 3000, text: 'Статус лакировки изменен на \"{$status}\"', type: 'success'});";
	break;

// Смена статуса принятия заказа
case "confirmed":

	$id = $_GET["od_id"];
	$val = $_GET["val"];
	$val = ($val == 0) ? 1 : 0;

	// Обновляем статус принятия заказа
	$query = "UPDATE OrdersData SET confirmed = {$val}, author = {$_SESSION['id']} WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

	// Получаем статус принятия заказа из базы
	$query = "SELECT confirmed FROM OrdersData WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$val = mysqli_result($res,0,'confirmed');

	if( $val == 1) {
		$class = 'confirmed';
		$status = 'Принят в работу';
		echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.td_step').addClass('step_confirmed');";
	}
	else {
		$class = 'not_confirmed';
		$status = 'Не принят в работу';
		echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.td_step').removeClass('step_confirmed');";
	}

	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.edit_confirmed').removeClass('confirmed not_confirmed');";
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.edit_confirmed').addClass('{$class}');";
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.edit_confirmed').attr('title', '{$status}');";
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.edit_confirmed').attr('val', '{$val}');";
	echo "noty({timeout: 3000, text: 'Статус заказа изменен на \"{$status}\"', type: 'success'});";
	break;

// Смена статуса прочитанного собщения в заказе
case "read_message":

	$id = $_GET["om_id"];
	$val = $_GET["val"];
	$val = ($val == 0) ? 1 : 0;

	// Обновляем статус сообщения
	if( $val == 1 ) {
		$query = "UPDATE OrdersMessage SET read_user = {$_SESSION['id']}, read_time = NOW() WHERE OM_ID = {$id}";
	}
	else {
		$query = "UPDATE OrdersMessage SET read_user = NULL, read_time = NULL WHERE OM_ID = {$id}";
	}
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

	// Получаем статус статус сообщения
	$query = "SELECT IFNULL(RUSR.Name, '') read_user
					,DATE_FORMAT(DATE(OM.read_time), '%d.%m.%Y') read_date
					,TIME(OM.read_time) read_time
				FROM OrdersMessage OM
				LEFT JOIN Users RUSR ON RUSR.USR_ID = OM.read_user
				WHERE OM_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$read_user = mysqli_result($res,0,'read_user');
	$read_date = mysqli_result($res,0,'read_date');
	$read_time = mysqli_result($res,0,'read_time');

	if( $read_user != '') {
		$html = "<i class='fa fa-envelope fa-2x' aria-hidden='true' style='color: green;' title='Прочитано: {$read_user} {$read_date} {$read_time}'>";
		$status = "ПРОЧИТАННОЕ";
	}
	else {
		$html = "<i class='fa fa-envelope fa-2x' aria-hidden='true' style='color: red;'>";
		$status = "НЕ ПРОЧИТАННОЕ";
	}
	$html = addslashes($html);
	echo "window.top.window.$('#msg{$id}').html('{$html}');";
	echo "window.top.window.$('#msg{$id}').attr('val', '{$val}');";

	echo "noty({timeout: 3000, text: 'Сообщение отмечено как {$status}', type: 'success'});";
	break;

// Помечаем X в главной таблице
case "Xlabel":

	session_start();
	$id = $_GET["od_id"];
	$val = $_GET["val"];
	if ($val == 1) {
		$_SESSION["X_".$id] = $val;
	}
	else {
		unset($_SESSION["X_".$id]);
	}
	break;

// Редактируем название материала
case "materials":
	$val = mysqli_real_escape_string( $mysqli,$_GET["val"] );
	$oldval = mysqli_real_escape_string( $mysqli,$_GET["oldval"] );
	$val = trim($val);
	$ptid = $_GET["ptid"];
	$removed = $_GET["removed"] == 'true' ? 1 : 0;

	if( $val != $oldval ) {
		$query = "SELECT MT_ID FROM Materials WHERE PT_ID = {$ptid} AND Material = '{$val}'";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		// Если в списке материалов уже есть такое название
		if( mysqli_num_rows($res) ) {
			$mtid = mysqli_result($res,0,'MT_ID');
			$query = "SELECT MT_ID, Count FROM Materials WHERE PT_ID = {$ptid} AND Material = '{$oldval}'";
			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
			$oldmtid = mysqli_result($res,0,'MT_ID');
			$oldcount = mysqli_result($res,0,'Count');

			// Меняем в заказах старый id материала на новый
			$query = "UPDATE OrdersDataDetail SET MT_ID = {$mtid}, author = {$_SESSION['id']} WHERE MT_ID = {$oldmtid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

			$query = "UPDATE OrdersDataBlank SET MT_ID = {$mtid}, author = {$_SESSION['id']} WHERE MT_ID = {$oldmtid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

			// Удаляем старый материал из списка
			$query = "DELETE FROM Materials WHERE MT_ID = {$oldmtid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 13000, text: 'Invalid query1: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

			// Прибавляем старый счетчик к новому
			$query = "UPDATE Materials SET Count = Count + {$oldcount} WHERE Material = '{$val}' AND PT_ID = {$ptid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");

			// Меняем на экране старый id материала на новый
			echo "$('.mt{$oldmtid}').addClass('mt{$mtid}');";
			echo "$('.mt{$oldmtid}').attr('mtid', '{$mtid}');";
			echo "$('.mt{$mtid}').removeClass('.mt{$oldmtid}');";
		}
		else {
			$query = "UPDATE Materials SET Material = '{$val}' WHERE Material = '{$oldval}' AND PT_ID = {$ptid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		}
		echo "noty({timeout: 3000, text: 'Название материала изменено на \"{$val}\"', type: 'success'});";
	}
	$query = "SELECT removed FROM Materials WHERE Material = '{$val}' AND PT_ID = {$ptid}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$oldremoved = mysqli_result($res,0,'removed');
	if( $oldremoved != $removed ) {
		$query = "UPDATE Materials SET removed = {$removed} WHERE Material = '{$val}' AND PT_ID = {$ptid}";
		mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		if( $removed ) {
			echo "noty({timeout: 3000, text: 'Материал помечен как выведенный.', type: 'success'});";
		}
		else {
			echo "noty({timeout: 3000, text: 'Снята отметка о выведении.', type: 'success'});";
		}
	}

	break;

// Форма отгрузки
case "shipment":
		$CT_ID = $_GET["CT_ID"] ? $_GET["CT_ID"] : 0;

		$html = "";
		$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		while( $row = mysqli_fetch_array($res) ) {
			$html .= "<label for='shop{$row["SH_ID"]}'>{$row["Shop"]}</label><input type='checkbox' id='shop{$row["SH_ID"]}' class='button_shops'>";
		}
		$html .= "<br><br>";

		// Снимаем ограничение в 1024 на GROUP_CONCAT
		$query = "SET @@group_concat_max_len = 10000;";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$query = "SELECT OD.OD_ID
						,OD.Code
						,OD.Color
						,OD.IsPainting
						,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
						,GROUP_CONCAT(ODD_ODB.Steps SEPARATOR '') Steps
						,IF(OD.SHP_ID IS NULL, '', 'checked') checked
						,OD.SH_ID
						,SH.Shop
				  FROM OrdersData OD
				  JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.CT_ID = {$CT_ID}
				  JOIN (
					  SELECT ODD.OD_ID
							,IFNULL(PM.PT_ID, 2) PT_ID
							,ODD.ODD_ID itemID
							,CONCAT('<b style=\'line-height: 1.79em;\'><a', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), ''), '</a></b><br>') Zakaz

							,CONCAT('<a class=\'nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR ''), '</a><br>') Steps

						FROM OrdersDataDetail ODD
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
						GROUP BY ODD.ODD_ID
						UNION ALL
						SELECT ODB.OD_ID
							  ,0 PT_ID
							  ,ODB.ODB_ID itemID
							  ,CONCAT('<b style=\'line-height: 1.79em;\'><a', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODB.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), ''), '</a></b><br>') Zakaz

							  ,CONCAT('<a class=\'nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width: 30px;\' title=\'(', IFNULL(WD.Name, 'Не назначен!'), ')\'><i class=\"fa fa-cog\" aria-hidden=\"true\" style=\"line-height: 1.45em;\"></i></div>')) SEPARATOR ''), '</a><br>') Steps

						FROM OrdersDataBlank ODB
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						GROUP BY ODB.ODB_ID
						ORDER BY PT_ID DESC, itemID
						) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
				  WHERE OD.Del = 0";
		if( $_GET["shpid"] ) {
			$query .= " AND ((OD.ReadyDate IS NULL AND OD.SHP_ID IS NULL) OR OD.SHP_ID = {$_GET["shpid"]})";
		}
		else {
			$query .= " AND OD.ReadyDate IS NULL AND OD.SHP_ID IS NULL";
		}
		$query .= " GROUP BY OD.OD_ID ORDER BY OD.OD_ID";

		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
		$html .= "<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>";
		$html .= "<table class='main_table' id='to_shipment'><thead><tr><th width='70'>Код</th><th width='10%'>Салон</th><th width='30%'>Заказ</th><th width='20%'>Цвет</th><th width='130'>Этапы</th></tr></thead><tbody>";
		while( $row = mysqli_fetch_array($res) ) {
			$html .= "<tr class='shop{$row["SH_ID"]}' style='display: none;'>";
			$html .= "<td><input {$row["checked"]} type='checkbox' name='ord_sh[]' id='ord_sh{$row["OD_ID"]}' class='chbox hide' value='{$row["OD_ID"]}'>";
			$html .= "<label for='ord_sh{$row["OD_ID"]}'".($row["checked"] == 'checked' ? "style='color: red;'" : "").">{$row["Code"]}</label></td>";
			$html .= "<td>{$row["Shop"]}</td>";
			$html .= "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
			switch ($row["IsPainting"]) {
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
					break;
			}
			$html .= "<td class='{$class}' title='{$title}'>{$row["Color"]}</td>";
			$html .= "<td><span class='nowrap material'>{$row["Steps"]}</span></td>";
			$html .= "</tr>";
		}
		$html .= "</tbody></table>";
		$html .= "<p><input type='checkbox' id='selectallbottom'><label for='selectallbottom'>Выбрать все</label></p>";
		$html = addslashes($html);
		echo "window.top.window.$('#orders_to_shipment').html('{$html}');";
		$js = "
			function selectall(ch) {
				$('.chbox.show').prop('checked', ch);
				$('#selectalltop').prop('checked', ch);
				$('#selectallbottom').prop('checked', ch);
				return false;
			}

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
					$('.chbox.show').each(function(){
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
		";
		$js .= "
			$('.button_shops').button();

			$('.button_shops').on('change', function() {
				var id = $(this).attr('id');
				if( $(this).prop('checked') ) {
					$('#to_shipment .'+id).show('fast');
					$('#to_shipment .'+id+' input[type=checkbox]').removeClass('hide');
					$('#to_shipment .'+id+' input[type=checkbox]').addClass('show');
					$('#to_shipment .'+id+' input[type=checkbox]').change();
				}
				else {
					$('#to_shipment .'+id+' input[type=checkbox]').prop('checked', false);
					$('#to_shipment .'+id).hide('fast');
					$('#to_shipment .'+id+' input[type=checkbox]').removeClass('show');
					$('#to_shipment .'+id+' input[type=checkbox]').addClass('hide');
					$('#to_shipment .'+id+' input[type=checkbox]').change();
				}
			});
		";
		echo $js;

		// Если на экране отгрузки - включаем задействованные салоны
		if( $_GET["shpid"] ) {
			$query = "SELECT SH_ID FROM OrdersData WHERE SHP_ID = {$_GET["shpid"]} GROUP BY SH_ID";
			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
			while( $row = mysqli_fetch_array($res) ) {
				echo "$('#shop".$row["SH_ID"]."').prop('checked', true).change();";
			}
		}
		else {
			echo "$('.button_shops').prop('checked', true).change();";
		}

	break;

// Форма добавления платежа к заказу
case "add_payment":
	$OD_ID = $_GET["OD_ID"];

	// Узнаем фамилию заказчика и дату продажи
	$query = "SELECT ClientName, DATE_FORMAT(StartDate, '%d.%m.%Y') StartDate FROM OrdersData WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$ClientName = mysqli_result($res,0,'ClientName');
	$StartDate = mysqli_result($res,0,'StartDate');

	$html = "<input type='hidden' name='OD_ID' value='{$OD_ID}'>";
	$html .= "<table><thead><tr>";
	$html .= "<th>Дата</th>";
	$html .= "<th>Сумма</th>";
	$html .= "<th>Терминал</th>";
	$html .= "<th>Фамилия</th>";
//	$html .= "<th>Возврат</th>";
	$html .= "</tr></thead><tbody>";

	$query = "SELECT OP_ID
					,DATE_FORMAT(payment_date, '%d.%m.%Y') payment_date
					,payment_sum
					,IF(IFNULL(terminal_payer, '') = '', 0, 1) terminal
					,terminal_payer
					,return_terminal
				FROM OrdersPayment
				WHERE OD_ID = {$OD_ID} AND IFNULL(payment_sum, 0) != 0
				ORDER BY OP_ID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$payment_count = 0; // Счетчик кол-ва платежей
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<tr>";
		if( $row["return_terminal"] == 0 ) {
			$html .= "<td><input type='hidden' name='OP_ID[]' value='{$row["OP_ID"]}'><input type='text' class='date' name='payment_date[]' value='{$row["payment_date"]}' readonly></td>";
			$html .= "<td><input type='number' class='payment_sum' name='payment_sum[]' value='{$row["payment_sum"]}'></td>";
			$html .= "<td><input ".($row["terminal"] ? 'checked' : '')." type='checkbox' class='terminal'></td>";
			$html .= "<td><input type='text' class='terminal_payer' value='{$row["terminal_payer"]}'><input type='hidden' class='terminal_payer' name='terminal_payer[]' value='{$row["terminal_payer"]}'></td>";
//			$html .= "<td><input type='checkbox' class='return_terminal' ".($row["return_terminal"] == 1 ? "checked" : "")."><input type='hidden' class='return_terminal' name='return_terminal[]' value='{$row["return_terminal"]}'></td>";
			$payment_count++;
		}
		else {
			$html .= "<td>{$row["payment_date"]}</td>";
			$html .= "<td>{$row["payment_sum"]}</td>";
			$html .= "<td>".($row["terminal"] ? "<i title='Оплата по терминалу' class='fa fa-credit-card' aria-hidden='true'></i>" : "")."</td>";
			$html .= "<td>{$row["terminal_payer"]}</td>";
//			$html .= "<td>Возврат</td>";
		}
		$html .= "</tr>";
	}
	if( $payment_count == 0 and $StartDate != '' ) {
		$payment_date = $StartDate;
	}
	else {
		$payment_date = date('d.m.Y');
	}
	$html .= "<tr>";
	$html .= "<td><input type='text' class='date' name='payment_date_add' value='{$payment_date}' readonly></td>";
	$html .= "<td><input type='number' class='payment_sum' name='payment_sum_add'></td>";
	$html .= "<td><input type='checkbox' class='terminal' name='terminal_add' value='1'></td>";
	$html .= "<td><input type='text' class='terminal_payer' name='terminal_payer_add' value='{$ClientName}'></td>";
//	$html .= "<td></td>";
	$html .= "</tr></tbody></table>";

	$html = addslashes($html);
	echo "window.top.window.$('#add_payment fieldset').html('{$html}');";

	break;

// Форма редактирования цены заказа
case "update_price":
	$OD_ID = $_GET["OD_ID"];

	// Узнаем скидку заказа
	$query = "SELECT discount FROM OrdersData WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$discount = mysqli_result($res,0,'discount');

	$js = '';

	$html = "<input type='hidden' name='OD_ID' value='{$OD_ID}'>";
	$html .= "<table class='main_table'><thead><tr>";
	$html .= "<th>Наименование</th>";
	$html .= "<th width='75'>Цена за шт.</th>";
	$html .= "<th width='50'>Кол-во</th>";
	$html .= "<th width='75'>Сумма</th>";
	$html .= "</tr></thead><tbody>";

	$query = "SELECT ODD.OD_ID
					,IFNULL(PM.PT_ID, 2) PT_ID
					,ODD.ODD_ID itemID
					,ODD.Price
					,ODD.Amount
					,ODD.PM_ID
					,ODD.PME_ID

					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), ''), '</i></b><br>') Zakaz

			  FROM OrdersDataDetail ODD
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  WHERE ODD.OD_ID = {$OD_ID}
			  GROUP BY ODD.ODD_ID
			  UNION ALL
			  SELECT ODB.OD_ID
					,0 PT_ID
					,ODB.ODB_ID itemID
					,ODB.Price
					,ODB.Amount
					,0 PM_ID
					,0 PME_ID

					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODB.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), ''), '</i></b><br>') Zakaz

			  FROM OrdersDataBlank ODB
			  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
			  WHERE ODB.OD_ID = {$OD_ID}
			  GROUP BY ODB.ODB_ID
			  ORDER BY PT_ID DESC, itemID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<tr>";
		$html .= "<input type='hidden' name='PT_ID[]' value='{$row["PT_ID"]}'>";
		$html .= "<input type='hidden' name='itemID[]' value='{$row["itemID"]}'>";
		$html .= "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		$html .= "<td class='prod_price'><input type='number' id='prod_price{$row["itemID"]}' min='1' name='price[]' value='{$row["Price"]}' style='width: 70px; text-align: right;'></td>";
		$html .= "<td class='prod_amount' style='text-align: center;'>{$row["Amount"]}</td>";
		$html .= "<td class='prod_sum' style='text-align: right;'></td>";
		$html .= "</tr>";
		if( $row["PT_ID"] > 0 ) {
			$js .= "window.top.window.$( '#prod_price{$row["itemID"]}' ).autocomplete({ source: 'autocomplete.php?do=price&retail=1&PM_ID={$row["PM_ID"]}&PME_ID={$row["PME_ID"]}' });";
		}
	}
	$html .= "<tr style='text-align: right; font-weight: bold;'><td colspan='2' id='discount'>Скидка: <input type='number' min='1' name='discount' value='{$discount}' style='width: 70px; text-align: right;'> руб. (<span></span> %)</td><td>Итог:</td><td id='prod_total'><input type='number' style='width: 70px; text-align: right;'></td></tr>";
	$html .= "</tbody></table>";

	$html = addslashes($html);
	echo "window.top.window.$('#update_price fieldset').html('{$html}');";
	echo $js;

	break;

// Редактирование салона
case "update_shop":
	$OD_ID = $_GET["OD_ID"];
	$SH_ID = $_GET["SH_ID"] ? $_GET["SH_ID"] : "NULL";

	// Узнаем название старого салона
	$query = "SELECT SH.Shop FROM Shops SH JOIN OrdersData OD ON OD.SH_ID = SH.SH_ID AND OD.OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$old_shop = mysqli_result($res,0,'Shop') ? mysqli_result($res,0,'Shop') : 'Свободные';

	// Меняем салон в заказе
	$query = "UPDATE OrdersData SET SH_ID = {$SH_ID}, author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
	mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: '".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'alert'});");

	// Узнаем название нового салона
	$query = "SELECT IFNULL(SH.Shop, 'Свободные') Shop
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS ShopCity
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,IFNULL(OD.SH_ID, 0) SH_ID
				FROM OrdersData OD
				LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
				WHERE OD.OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$new_shop = mysqli_result($res,0,'Shop');
	$CTColor = mysqli_result($res,0,'CTColor');
	$ShopCity = mysqli_result($res,0,'ShopCity');
	$SH_ID = mysqli_result($res,0,'SH_ID');
	$shop_span = "<span style='background: {$CTColor};'>{$ShopCity}</span>";
	$shop_span = addslashes($shop_span);

	echo "$('.shop_cell[id={$OD_ID}]').html('{$shop_span}');";
	echo "$('.shop_cell[id={$OD_ID}]').attr('SH_ID', '{$SH_ID}');";
	echo "noty({timeout: 3000, text: 'Салон изменен с \"{$old_shop}\" на \"{$new_shop}\"', type: 'success'});";
	if( $SH_ID == 0 ) {
		echo "window.top.window.$('.main_table tr[id=\"ord{$OD_ID}\"] action a').css('display', 'none');";
	}
	else {
		echo "window.top.window.$('.main_table tr[id=\"ord{$OD_ID}\"] action a').css('display', '');";
	}

	break;

// Редактирование даты продажи
case "update_sell_date":
	$OD_ID = $_GET["OD_ID"];
	$StartDate = $_GET["StartDate"] ? '\''.date( 'Y-m-d', strtotime($_GET["StartDate"]) ).'\'' : "NULL";

	// Узнаем старую дату продажи
	$query = "SELECT DATE_FORMAT(StartDate, '%d.%m.%Y') StartDate FROM OrdersData WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$old_StartDate = mysqli_result($res,0,'StartDate');

	// Меняем дату продажи
	$query = "UPDATE OrdersData SET StartDate = {$StartDate}, author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
	if( !mysqli_query( $mysqli, $query ) ) {
		echo "$('td#{$OD_ID} .sell_date').val('{$old_StartDate}');";
		die("noty({timeout: 10000, text: '".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'alert'});");
	}

	echo "noty({timeout: 3000, text: 'Дата продажи изменена с \"{$old_StartDate}\" на \"{$_GET["StartDate"]}\"', type: 'success'});";

	break;

// Разделение заказа
case "order_cut":
	$OD_ID = $_GET["OD_ID"];

	$html = "<input type='hidden' name='OD_ID' value='{$OD_ID}'>";
	$html .= "<input type='hidden' name='location'>";
	$html .= "<div id='slider' style='text-align: center;'>";

	$query = "SELECT ODD.OD_ID
					,IFNULL(PM.PT_ID, 2) PT_ID
					,ODD.ODD_ID itemID
					,ODD.Amount

					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), ''), '</i></b><br>') Zakaz

			  FROM OrdersDataDetail ODD
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  WHERE ODD.OD_ID = {$OD_ID}
			  GROUP BY ODD.ODD_ID
			  UNION ALL
			  SELECT ODB.OD_ID
					,0 PT_ID
					,ODB.ODB_ID itemID
					,ODB.Amount

					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODB.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), ''), '</i></b><br>') Zakaz

			  FROM OrdersDataBlank ODB
			  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
			  WHERE ODB.OD_ID = {$OD_ID}
			  GROUP BY ODB.ODB_ID
			  ORDER BY PT_ID DESC, itemID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<div>";
		$html .= "<div>{$row["Zakaz"]}</div>";
		$html .= "<input type='hidden' name='PT_ID[]' value='{$row["PT_ID"]}'>";
		$html .= "<input type='hidden' name='itemID[]' value='{$row["itemID"]}'>";
		$html .= "<input type='hidden' name='prod_amount_left[]' value='{$row["Amount"]}'>";
		$html .= "<input type='hidden' name='prod_amount_right[]' value='0'>";
		$html .= "<div><b><left>{$row["Amount"]}</left> - <right>0</right></b></div>";
		$html .= "<span style='display: block;'>{$row["Amount"]}</span>";
		$html .= "<br></div>";
	}
	$html .= "<div>";
	$html = addslashes($html);
	echo "window.top.window.$('#order_cut fieldset').html('{$html}');";

	break;

// Обновление метража
case "footage":
	$oddid = $_GET["oddid"];
	$odbid = $_GET["odbid"];
	$val = $_GET["val"] ? $_GET["val"] : "NULL";

	if( $oddid ) {
		$query = "UPDATE OrdersDataDetail SET MT_amount = {$val} WHERE ODD_ID = {$oddid}";
	}
	else {
		$query = "UPDATE OrdersDataBlank SET MT_amount = {$val} WHERE ODB_ID = {$odbid}";
	}
	mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: '".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'alert'});");

	echo "noty({timeout: 3000, text: 'Метраж обновлен на: <b>\"{$val}\"</b>', type: 'success'});";

	break;

// Формирование списка материалов для заказа
case "material_list":
	$oddids = $_GET["oddids"];
	$odbids = $_GET["odbids"];
	$materials_name = "";

	// Находим строку с максимальной длиной
	$query = "SELECT MAX(CHAR_LENGTH(MT.Material)) length
				FROM Materials MT
				JOIN (
					SELECT MT_ID, MT_amount
					FROM OrdersDataDetail
					WHERE ODD_ID IN ($oddids)
					UNION ALL
					SELECT MT_ID, MT_amount
					FROM OrdersDataBlank
					WHERE ODB_ID IN ($odbids)
				) ODD_ODB ON ODD_ODB.MT_ID = MT.MT_ID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	$length = mysqli_result($res,0,'length') ? mysqli_result($res,0,'length') : 0;

	$query = "SELECT RPAD(MT.Material, {$length}, ' ') Material, CONCAT('- ', GROUP_CONCAT(ROUND(ODD_ODB.MT_amount, 1) SEPARATOR '+'), ' м.п.') MT_amount
				FROM Materials MT
				JOIN (
					SELECT MT_ID, MT_amount
					FROM OrdersDataDetail
					WHERE ODD_ID IN ($oddids)
					UNION ALL
					SELECT MT_ID, MT_amount
					FROM OrdersDataBlank
					WHERE ODB_ID IN ($odbids)
				) ODD_ODB ON ODD_ODB.MT_ID = MT.MT_ID
				GROUP BY MT.MT_ID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 3000, text: 'Invalid query: ".addslashes(htmlspecialchars(mysqli_error( $mysqli )))."', type: 'error'});");
	while( $row = mysqli_fetch_array($res) ) {
		$materials_name .= $row["Material"]."\\t".$row["MT_amount"]."\\r\\n";
	}
	//$materials_name = addslashes( $materials_name );
	echo "window.top.window.$('#materials_name').html('{$materials_name}');";

	break;
}
?>
