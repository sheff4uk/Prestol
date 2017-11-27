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
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		$pt = mysqli_result($res,0,'PT_ID');
		$model = mysqli_result($res,0,'Model');
		$size = mysqli_result($res,0,'Size');
		$form = mysqli_result($res,0,'Form');
		$amount = mysqli_result($res,0,'Amount');
		$product = "<h3><b style=\'font-size: 2em; margin-right: 20px;\'>{$amount}</b>{$model}&nbsp;{$size}&nbsp;{$form}</h3>";

		// Получение информации об этапах производства
		$query = "SELECT ST.ST_ID, ST.Step, ODS.WD_ID, IF(ODS.WD_ID IS NULL, 'disabled', '') disabled, ODS.Tariff, IF (ODS.IsReady, 'checked', '') IsReady, IF(ODS.Visible = 1, 'checked', '') Visible, ODS.Old
				  FROM OrdersDataSteps ODS
				  JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
				  WHERE ODS.ODD_ID = $odd_id
				  ORDER BY ODS.Old DESC, ST.Sort";
		$result = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

		$text = "<input type=\'hidden\' name=\'ODD_ID\' value=\'$odd_id\'>";
	}
	else {
		$query = "SELECT IFNULL(BL.Name, ODB.Other) Name
						,ODB.Amount
				  FROM OrdersDataBlank ODB
				  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
				  WHERE ODB.ODB_ID = $odb_id";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		$model = mysqli_result($res,0,'Name');
		$amount = mysqli_result($res,0,'Amount');
		$product = "<h3><b style=\'font-size: 2em; margin-right: 20px;\'>{$amount}</b>{$model}<h3>";

		// Получение информации об этапах производства
		$query = "SELECT 0 ST_ID, '-' Step, ODS.WD_ID, IF(ODS.WD_ID IS NULL, 'disabled', '') disabled, ODS.Tariff, IF (ODS.IsReady, 'checked', '') IsReady, IF(ODS.Visible = 1, 'checked', '') Visible, ODS.Old
				  FROM OrdersDataSteps ODS
				  WHERE ODS.ODB_ID = $odb_id
				  ORDER BY ODS.Old DESC";
		$result = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

		$text = "<input type=\'hidden\' name=\'ODB_ID\' value=\'$odb_id\'>";
	}

	$text .= $product;
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
						ORDER BY ODS.ODD_ID DESC
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
						ORDER BY ODS.ODB_ID DESC
						LIMIT 100
					  ) ODS ON ODS.WD_ID = WD.WD_ID
					  WHERE WD.Type = 1
					  GROUP BY WD.WD_ID
					  ORDER BY CNT DESC";
		}
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
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
			$text .= "<tr><td class=\'stage\'><b>{$row["Step"]}</b></td>";
			$text .= "<td><select name=\'WD_ID{$row["ST_ID"]}\' id=\'{$row["ST_ID"]}\' class=\'selectwr\'>{$selectworker}</select></td>";
			$text .= "<td><input type=\'number\' min=\'0\' name=\'Tariff{$row["ST_ID"]}\' class=\'tariff\' value=\'{$row["Tariff"]}\'></td>";
			$text .= "<td><input type=\'checkbox\' id=\'IsReady{$row["ST_ID"]}\' name=\'IsReady{$row["ST_ID"]}\' class=\'isready\' value=\'1\' {$row["IsReady"]} {$row["disabled"]}><label for=\'IsReady{$row["ST_ID"]}\'></label></td>";
			$text .= "<td><input type=\'checkbox\' name=\'Visible{$row["ST_ID"]}\' value=\'1\' {$row["Visible"]}></td></tr>";
		}
	}
	$text .= "</tbody></table>";
	echo "window.top.window.$('#formsteps').html('{$text}');";
	break;
///////////////////////////////////////////////////////////////////

// живой поиск в свободных изделиях
case "livesearch":
		
	$pt = $_GET["type"];
		
	// Таблица изделий
	$query = "SELECT ODD.ODD_ID
					,PM.PT_ID
					,ODD.Amount
					,IFNULL(PM.Model, 'Столешница') Model
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
	$query .= " WHERE ODD.OD_ID IS NULL AND ODD.Del = 0 AND IFNULL(PM.PT_ID, 2) = {$pt}";
//	$query .= ( $pt == 1 ) ? " AND PM.PT_ID = {$pt}" : "";
//	$query .= ($_GET["model"] and $_GET["model"] <> "undefined") ? " AND (ODD.PM_ID = {$_GET["model"]} OR ODD.PM_ID IS NULL)" : "";
//	$query .= ($_GET["form"] and $_GET["form"] <> "undefined") ? " AND ODD.PF_ID = {$_GET["form"]}" : "";
//	$query .= ($_GET["mechanism"] and $_GET["mechanism"] <> "undefined") ? " AND ODD.PME_ID = {$_GET["mechanism"]}" : "";
//	$query .= ($_GET["length"] and $_GET["length"] <> "undefined") ? " AND ODD.Length = {$_GET["length"]}" : "";
//	$query .= ($_GET["width"] and $_GET["width"] <> "undefined") ? " AND ODD.Width = {$_GET["width"]}" : "";
//	$query .= ($_GET["material"]) ? " AND MT.Material LIKE '%{$_GET["material"]}%'" : "";
	$query .= " GROUP BY ODD.ODD_ID";
	$query .= " ORDER BY progress DESC";
	
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
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
			case "0":
				$table .= "<span class='bg-red'>";
				break;
			case "1":
				$table .= "{$row["clock"]}<span class='bg-yellow' title='Заказано: {$row["order_date"]} Ожидается: {$row["arrival_date"]}'>";
				break;
			case "2":
				$table .= "<span class='bg-green'>";
				break;
			default:
				$table .= "<span class='bg-gray'>";
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
	echo "if( amount ){ $('#{$_GET["this"]} fieldset').prop('disabled', true); $( '#{$_GET["this"]} #forms, #{$_GET["this"]} #mechanisms' ).buttonset( 'option', 'disabled', true ); $('#{$_GET["this"]} fieldset input[name=\"Amount\"]').val(amount); $('#{$_GET["this"]} input[name=free]').val(1); $('select[name=Model]').select2('enable',false);}";
	echo "else{ $('#{$_GET["this"]} fieldset').prop('disabled', false); $( '#{$_GET["this"]} #forms, #{$_GET["this"]} #mechanisms' ).buttonset( 'option', 'disabled', false ); $('#{$_GET["this"]} fieldset input[name=\"Amount\"]').val(''); $('#{$_GET["this"]} input[name=free]').val(0); $('select[name=Model]').select2('enable');}";
	echo "materialonoff('#{$_GET["this"]}');";
	echo "return false;";
	echo "});";
	break;
///////////////////////////////////////////////////////////////////

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
	$query = "UPDATE OrdersData SET IsPainting = {$val}, WD_ID = NULL, author = {$_SESSION['id']} WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	// Получаем статус лакировки и отгрузку из базы
	$query = "SELECT IsPainting, IFNULL(SHP_ID, 0) SHP_ID, IFNULL(SH_ID, 0) SH_ID FROM OrdersData WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
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
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] .painting_workers').text('');";

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
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		$painting = mysqli_result($res,0,'IsPainting');
		$ready = mysqli_result($res,0,'IsReady');
		$is_orders_ready = ( $painting and $ready ) ? 1 : 0;

		echo "check_shipping({$is_orders_ready}, 1, {$filter});";
	}
	else {
		$html = "";
		//if( $isready == 1 and $archive != 1 and $SHP_ID == 0 ) {
		if( $archive != 1 ) {
			if( $isready == 1 and $val == 3 ) {
				if( in_array('order_ready', $Rights) ) {
					//echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] action').html('<a href=\'#\' class=\'\' ".( $SH_ID == 0 ? 'style=\"display: none;\"' : '')." onclick=\'if(confirm(\"Пожалуйста, подтвердите готовность заказа!\", \"?ready={$id}\")) return false;\' title=\'Готово\'><i style=\'color:red;\' class=\'fa fa-flag-checkered fa-lg\'></i></a>');";
					$html .= "<a href='#' class='shipping' ".( $SH_ID == 0 ? "style='display: none;'" : "")." onclick='confirm(\"Пожалуйста, подтвердите <b>отгрузку</b> заказа.\").then(function(status){if(status) $.ajax({ url: \"ajax.php?do=order_shp&od_id={$id}\", dataType: \"script\", async: false });});' title='Отгрузить'><i style='color:red;' class='fa fa-flag-checkered fa-lg'></i></a>";
				}
			}
//			else {
				if( in_array('order_add', $Rights) ) {
					//echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] action').html('<a href=\'#\' class=\'\' onclick=\'if(confirm(\"<b>Подтвердите удаление заказа!</b>\", \"?del={$id}\")) return false;\' title=\'Удалить\'><i class=\'fa fa-times fa-lg\'></i></a>');";
					if( in_array('order_add_confirm', $Rights) ) {
						$message = "<b>Внимание!</b><br>Заказ отмеченный как покрашенный при удалении будет считаться <b>списанным</b> - это означает, что задействованные заготовки, тоже останутся <b>списанными</b>.<br>В остальных случаях заказ будет считаться <b>отмененным</b> и заготовки <b>вернутся</b> на склад.<br>К тому же этапы производства, отмеченные как <b>выполненные</b>, после удаления останутся таковыми <b>с сохранением денежного начисления работнику</b>.";
					}
					else {
						$message = "Пожалуйста, подтвердите <b>удаление</b> заказа.";
					}
					$html .= "<a href='#' class='deleting' onclick='confirm(\"{$message}\").then(function(status){if(status) $.ajax({ url: \"ajax.php?do=order_del&od_id={$id}\", dataType: \"script\", async: false });});' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
				}
//			}
		}
	}
	// Выводим кнопки удалить и отгрузать
	$html = addslashes($html);
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] action').html('{$html}');";

	if( $val == 3 ) {
		// Формирование дропдауна со списком лакировщиков. Сортировка по релевантности.
		$painting_workers = "<select id='painting_workers' size='10'>";
		$painting_workers .= "<option selected value='0'>-=Выберите работника=-</option>";
		$query = "SELECT WD.WD_ID, WD.Name, SUM(1) CNT
				  FROM WorkersData WD
				  LEFT JOIN (
					SELECT OD.WD_ID
					FROM OrdersData OD
					WHERE OD.WD_ID IS NOT NULL
					ORDER BY OD.OD_ID DESC
					LIMIT 100
				  ) SOD ON SOD.WD_ID = WD.WD_ID
				  WHERE WD.Type = 2
				  GROUP BY WD.WD_ID
				  ORDER BY CNT DESC";

		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$painting_workers .= "<option value='{$row["WD_ID"]}'>{$row["Name"]}</option>";
		}
		$painting_workers .= "</select>";
		// Конец дропдауна со списком лакировщиков
		$painting_workers = addslashes($painting_workers);

		echo "
			noty({
				modal: true,
				timeout: false,
				text: 'Статус лакировки изменен на <b>{$status}</b>. Выберите исполнителя:<br>{$painting_workers}',
				buttons: [
					{addClass: 'btn btn-primary', text: 'Ok', onClick: function (\$noty) {
						\$noty.close();
						var wd_id = \$('#painting_workers').val();
						\$.ajax({ url: 'ajax.php?do=painting_workers&wd_id='+wd_id+'&od_id={$id}', dataType: 'script', async: false });
					}
					}
				],
				type: 'success'
			});
		";
	}
	else {
		echo "noty({timeout: 3000, text: 'Статус лакировки изменен на <b>{$status}</b>', type: 'success'});";
	}
	break;
///////////////////////////////////////////////////////////////////

// Сохранение в базу лакировщика
case "painting_workers":

	$wd_id = $_GET["wd_id"];
	$id = $_GET["od_id"];

	if( $wd_id > 0 ) {
		// Узнаем имя лакировщика
		$query = "SELECT Name FROM WorkersData WHERE WD_ID = {$wd_id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		$Name = mysqli_result($res,0,'Name');

		$query = "UPDATE OrdersData SET WD_ID = {$wd_id}, author = {$_SESSION['id']} WHERE OD_ID = {$id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] .painting_workers').text('{$Name}');";
		echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.painting').attr('title', 'Готово ({$Name})');";
	}

	break;
///////////////////////////////////////////////////////////////////

// Смена статуса принятия заказа
case "confirmed":

	$id = $_GET["od_id"];
	$val = $_GET["val"];
	$val = ($val == 0) ? 1 : 0;

	// Обновляем статус принятия заказа
	$query = "UPDATE OrdersData SET confirmed = {$val}, author = {$_SESSION['id']} WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	// Получаем статус принятия заказа из базы
	$query = "SELECT confirmed FROM OrdersData WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
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
	echo "noty({timeout: 3000, text: 'Статус заказа изменен на <b>{$status}</b>', type: 'success'});";
	break;
///////////////////////////////////////////////////////////////////

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
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	// Получаем статус сообщения
	$query = "SELECT IFNULL(USR_Name(OM.read_user), '') read_user
					,DATE_FORMAT(DATE(OM.read_time), '%d.%m.%y') read_date
					,TIME(OM.read_time) read_time
				FROM OrdersMessage OM
				WHERE OM.OM_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
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
///////////////////////////////////////////////////////////////////

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
///////////////////////////////////////////////////////////////////

// Редактируем название материала
case "materials":
	$val = mysqli_real_escape_string( $mysqli,$_GET["val"] );
	$oldval = mysqli_real_escape_string( $mysqli,$_GET["oldval"] );
	$val = trim($val);
	$ptid = $_GET["ptid"];
	$removed = $_GET["removed"] == 'true' ? 1 : 0;

	if( $val != $oldval ) {
		$query = "SELECT MT_ID FROM Materials WHERE PT_ID = {$ptid} AND Material = '{$val}'";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		// Если в списке материалов уже есть такое название
		if( mysqli_num_rows($res) ) {
			$mtid = mysqli_result($res,0,'MT_ID');
			$query = "SELECT MT_ID FROM Materials WHERE PT_ID = {$ptid} AND Material = '{$oldval}'";
			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			$oldmtid = mysqli_result($res,0,'MT_ID');

			// У старого материала сохраняем ссылку на новый материал PMT_ID
			$query = "UPDATE Materials SET PMT_ID = {$mtid} WHERE MT_ID = {$oldmtid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query1: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			// Если старый материал был чьим то родителем, то заменяем у его потомков родителя на нового
			$query = "UPDATE Materials SET PMT_ID = {$mtid} WHERE PMT_ID = {$oldmtid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query1: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

			// Меняем в заказах старый id материала на новый
			$query = "UPDATE OrdersDataDetail SET MT_ID = {$mtid}, author = {$_SESSION['id']} WHERE MT_ID = {$oldmtid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			$query = "UPDATE OrdersDataBlank SET MT_ID = {$mtid}, author = {$_SESSION['id']} WHERE MT_ID = {$oldmtid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

			// Меняем на экране старый id материала на новый
			echo "$('.mt{$oldmtid}').addClass('mt{$mtid}');";
			echo "$('.mt{$oldmtid}').attr('mtid', '{$mtid}');";
			echo "$('.mt{$mtid}').removeClass('.mt{$oldmtid}');";
		}
		else {
			$query = "UPDATE Materials SET Material = '{$val}' WHERE Material = '{$oldval}' AND PT_ID = {$ptid}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		}
		echo "noty({timeout: 3000, text: 'Название материала изменено на <b>{$val}</b>', type: 'success'});";
	}
	// Сохранение пометки о выведении
	$query = "SELECT removed FROM Materials WHERE Material = '{$val}' AND PT_ID = {$ptid}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$oldremoved = mysqli_result($res,0,'removed');
	if( $oldremoved != $removed ) {
		$query = "UPDATE Materials SET removed = {$removed} WHERE Material = '{$val}' AND PT_ID = {$ptid}";
		mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		if( $removed ) {
			echo "noty({timeout: 3000, text: 'Материал помечен как выведенный.', type: 'success'});";
		}
		else {
			echo "noty({timeout: 3000, text: 'Снята отметка о выведении.', type: 'success'});";
		}
	}

	break;
///////////////////////////////////////////////////////////////////

// Форма отгрузки
case "shipment":
		$CT_ID = $_GET["CT_ID"] ? $_GET["CT_ID"] : 0;

		// Проверяем права на отгрузку заказа
		if( !in_array('order_ready', $Rights) ) {
			echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
		}
		else {
			$html = "";
			$query = "SELECT SH_ID, Shop
						FROM Shops
						WHERE CT_ID = {$CT_ID}
							".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
							".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "");
			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			while( $row = mysqli_fetch_array($res) ) {
				$html .= "<label for='shop{$row["SH_ID"]}'>{$row["Shop"]}</label><input type='checkbox' id='shop{$row["SH_ID"]}' class='button_shops'>";
			}
			$html .= "<br><br>";

			// Снимаем ограничение в 1024 на GROUP_CONCAT
			$query = "SET @@group_concat_max_len = 10000;";
			mysqli_query( $mysqli, $query );

			$query = "SELECT OD.OD_ID
							,OD.Code
							,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
							,IFNULL(OD.ClientName, '') ClientName
							,IFNULL(DATE_FORMAT(OD.StartDate, '%d.%m'), '...') StartDate
							,IFNULL(DATE_FORMAT(OD.EndDate, '%d.%m'), '...') EndDate
							,OD.Color
							,OD.IsPainting
							,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
							,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
							,GROUP_CONCAT(ODD_ODB.Steps SEPARATOR '') Steps
							,IF(OD.SHP_ID IS NULL, '', 'checked') checked
							,OD.SH_ID
							,SH.Shop
							,OD.confirmed
							,REPLACE(OD.Comment, '\r\n', '<br>') Comment
						FROM OrdersData OD
						JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.CT_ID = {$CT_ID}
						JOIN (
							SELECT ODD.OD_ID
								,IFNULL(PM.PT_ID, 2) PT_ID
								,ODD.ODD_ID itemID
								,CONCAT('<b style=\'line-height: 1.79em;\'><a', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' <b style=\'font-size: 1.3em;\'>', ODD.Amount, '</b> ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('патина (', ODD.patina, ')'), ''), '</a></b><br>') Zakaz

								,CONCAT('<span class=\'wr_mt\'>', IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span ptid=\'', IFNULL(MT.PT_ID, ''), '\' mtid=\'', IFNULL(MT.MT_ID, ''), '\' id=\'m', ODD.ODD_ID, '\' class=\'mt', IFNULL(MT.MT_ID, ''), IF(MT.removed=1, ' removed', ''), ' material ',
									CASE ODD.IsExist
										WHEN 0 THEN 'bg-red'
										WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%y'), ' Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%y'))
										WHEN 2 THEN 'bg-green'
										ELSE 'bg-gray'
									END,
								'\'>', IFNULL(MT.Material, ''), '</span></span><br>') Material

								,CONCAT('<a class=\'nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR ''), '</a><br>') Steps

							FROM OrdersDataDetail ODD
							LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
							LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
							LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
							LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
							LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
							LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
							LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
							WHERE ODD.Del = 0
							GROUP BY ODD.ODD_ID
							UNION ALL
							SELECT ODB.OD_ID
								,0 PT_ID
								,ODB.ODB_ID itemID
								,CONCAT('<b style=\'line-height: 1.79em;\'><a', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODB.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' <b style=\'font-size: 1.3em;\'>', ODB.Amount, '</b> ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('патина (', ODB.patina, ')'), ''), '</a></b><br>') Zakaz

								,CONCAT('<span class=\'wr_mt\'>', IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0 AND ODB.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), ''), '<span ptid=\'', IFNULL(MT.PT_ID, ''), '\' mtid=\'', IFNULL(MT.MT_ID, ''), '\' id=\'m', ODB.ODB_ID, '\' class=\'mt', IFNULL(MT.MT_ID, ''), IF(MT.removed=1, ' removed', ''), ' material ',
									CASE ODB.IsExist
										WHEN 0 THEN 'bg-red'
										WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODB.order_date, '%d.%m.%y'), ' Ожидается: ', DATE_FORMAT(ODB.arrival_date, '%d.%m.%y'))
										WHEN 2 THEN 'bg-green'
										ELSE 'bg-gray'
									END,
								'\'>', IFNULL(MT.Material, ''), '</span></span><br>') Material

								,CONCAT('<a class=\'nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width: 30px;\' title=\'(', IFNULL(WD.Name, 'Не назначен!'), ')\'><i class=\"fa fa-cog\" aria-hidden=\"true\" style=\"line-height: 1.45em;\"></i></div>')) SEPARATOR ''), '</a><br>') Steps

							FROM OrdersDataBlank ODB
							LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
							LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
							LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
							LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
							WHERE ODB.Del = 0
							GROUP BY ODB.ODB_ID
							ORDER BY PT_ID DESC, itemID
						) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
						WHERE OD.Del = 0
							".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
							".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "");
			if( $_GET["shpid"] ) {
				$query .= " AND ((OD.ReadyDate IS NULL AND OD.SHP_ID IS NULL) OR OD.SHP_ID = {$_GET["shpid"]})";
			}
			else {
				$query .= " AND OD.ReadyDate IS NULL AND OD.SHP_ID IS NULL";
			}
			$query .= " GROUP BY OD.OD_ID";
			$query .= " ORDER BY OD.AddDate, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID";

			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			$html .= "<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>";
			$html .= "<table class='main_table' id='to_shipment'><thead><tr>";
			$html .= "<th width='70'>Код<br>Создан</th>";
			$html .= "<th width='20%'>Заказчик [Продажа]-[Сдача]</th>";
			$html .= "<th width='10%'>Салон</th>";
			$html .= "<th width='30%'>Заказ</th>";
			$html .= "<th width='20%'>Материал</th>";
			$html .= "<th width='20%'>Цвет</th>";
			$html .= "<th width='100'>Этапы</th>";
			$html .= "<th width='40'>Принят</th>";
			$html .= "<th width='20%'>Примечание</th>";
			$html .= "</tr></thead><tbody>";
			while( $row = mysqli_fetch_array($res) ) {
				$html .= "<tr class='shop{$row["SH_ID"]}' style='display: none;'>";
				$html .= "<td><input {$row["checked"]} type='checkbox' name='ord_sh[]' id='ord_sh{$row["OD_ID"]}' class='chbox hide' value='{$row["OD_ID"]}'>";
				$html .= "<label for='ord_sh{$row["OD_ID"]}'".($row["checked"] == 'checked' ? "style='color: red;'" : "")."><b class='code'>{$row["Code"]}</b></label><br><span>{$row["AddDate"]}</span></td>";
				$html .= "<td><span class='nowrap'>{$row["ClientName"]}<br>[{$row["StartDate"]}]-[{$row["EndDate"]}]</span></td>";
				$html .= "<td><span class='nowrap'>{$row["Shop"]}</span></td>";
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
				$html .= "<td><span class='nowrap'>{$row["Material"]}</span></td>";
				$html .= "<td class='{$class}' title='{$title}'>{$row["Color"]}</td>";
				$html .= "<td><span class='nowrap material'>{$row["Steps"]}</span></td>";
					// Если заказ принят
					if( $row["confirmed"] == 1 ) {
						$class = 'confirmed';
						$title = 'Принят в работу';
					}
					else {
						$class = 'not_confirmed';
						$title = 'Не принят в работу';
					}
				$html .= "<td class='{$class}' title='{$title}'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";
				$html .= "<td>{$row["Comment"]}</td>";
				$html .= "</tr>";
			}
			$html .= "</tbody></table>";
			$html .= "<p><input type='checkbox' id='selectallbottom'><label for='selectallbottom'>Выбрать все</label></p>";
			$html = addslashes($html);
			echo "window.top.window.$('#orders_to_shipment').html('{$html}');";
			echo "window.top.window.$('.button_shops').button();";

			// Если на экране отгрузки - включаем задействованные салоны
			if( $_GET["shpid"] ) {
				$query = "SELECT SH_ID FROM OrdersData WHERE SHP_ID = {$_GET["shpid"]} GROUP BY SH_ID";
				$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
				while( $row = mysqli_fetch_array($res) ) {
					echo "$('#add_shipment_form #shop".$row["SH_ID"]."').prop('checked', true).change();";
				}
			}
			else {
				echo "$('.button_shops').prop('checked', true).change();";
			}
			//echo "window.top.window.$('.chbox').button();";
		}

	break;
///////////////////////////////////////////////////////////////////

// Форма накладной
case "invoice":
		$KA_ID = $_GET["KA_ID"] ? $_GET["KA_ID"] : 0;
		$CT_ID = $_GET["CT_ID"] ? $_GET["CT_ID"] : 0;
		$num_rows = $_GET["num_rows"];

		// Проверяем права на акты сверок
		if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
			echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
		}
		else {
			$html = "";
			// Если доступен только город и у пользователя указан салон - показываем только его
			if( in_array('sverki_city', $Rights) and $USR_Shop ) {
				$query = "SELECT SH_ID, Shop FROM Shops WHERE SH_ID = {$USR_Shop}";
			}
			else {
				$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND KA_ID".($KA_ID ? " = {$KA_ID}" : " IS NULL");
			}
			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			while( $row = mysqli_fetch_array($res) ) {
				$html .= "<label for='shop{$row["SH_ID"]}'>{$row["Shop"]}</label><input type='checkbox' id='shop{$row["SH_ID"]}' class='button_shops'>";
			}
			$html .= "<br><br>";

			// Снимаем ограничение в 1024 на GROUP_CONCAT
			$query = "SET @@group_concat_max_len = 10000;";
			mysqli_query( $mysqli, $query );

			$query = "SELECT OD.OD_ID
							,OD.Code
							,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
							,IFNULL(OD.ClientName, '') ClientName
							,IFNULL(DATE_FORMAT(OD.StartDate, '%d.%m'), '...') StartDate
							,IFNULL(DATE_FORMAT(OD.EndDate, '%d.%m'), '...') EndDate
							,OD.Color
							,OD.IsPainting
							,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
							,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
							,GROUP_CONCAT(ODD_ODB.Steps SEPARATOR '') Steps
							,OD.SH_ID
							,SH.Shop
							,GROUP_CONCAT(ODD_ODB.Price SEPARATOR '') Price
							,OD.confirmed
							,REPLACE(OD.Comment, '\r\n', '<br>') Comment
							,IFNULL(OP.payment_sum, 0) payment_sum
							,IF(OS.locking_date IS NOT NULL AND IF(SH.KA_ID IS NULL, 1, 0), 1, 0) is_lock
						FROM OrdersData OD
						JOIN Shops SH ON SH.SH_ID = OD.SH_ID
						LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
						JOIN (
							SELECT ODD.OD_ID
								,IFNULL(PM.PT_ID, 2) PT_ID
								,ODD.ODD_ID itemID

								,CONCAT('<input type=\'hidden\' name=\'tbl[]\' value=\'odd\'><input type=\'hidden\' name=\'tbl_id[]\' value=\'', ODD.ODD_ID, '\'><input ".($num_rows > 0 ? "readonly" : "")." required type=\'number\' min=\'0\' name=\'opt_price[]\' value=\'', IFNULL(ODD.opt_price, IFNULL(ODD.Price, ".($num_rows > 0 ? "0" : "''").")), '\' amount=\'', ODD.Amount, '\'><br>') Price

								,CONCAT('<b style=\'line-height: 1.79em;\'><a', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' <b style=\'font-size: 1.3em;\'>', ODD.Amount, '</b> ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('патина (', ODD.patina, ')'), ''), '</a></b><br>') Zakaz

								,CONCAT('<span class=\'wr_mt\'>', IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span ptid=\'', IFNULL(MT.PT_ID, ''), '\' mtid=\'', IFNULL(MT.MT_ID, ''), '\' id=\'m', ODD.ODD_ID, '\' class=\'mt', IFNULL(MT.MT_ID, ''), IF(MT.removed=1, ' removed', ''), ' material ',
									CASE ODD.IsExist
										WHEN 0 THEN 'bg-red'
										WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%y'), ' Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%y'))
										WHEN 2 THEN 'bg-green'
										ELSE 'bg-gray'
									END,
								'\'>', IFNULL(MT.Material, ''), '</span></span><br>') Material

								,CONCAT('<a class=\'nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR ''), '</a><br>') Steps

							FROM OrdersDataDetail ODD
							LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
							LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
							LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
							LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
							LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
							LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
							LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
							WHERE ODD.Del = 0
							GROUP BY ODD.ODD_ID
							UNION ALL
							SELECT ODB.OD_ID
								,0 PT_ID
								,ODB.ODB_ID itemID

								,CONCAT('<input type=\'hidden\' name=\'tbl[]\' value=\'odb\'><input type=\'hidden\' name=\'tbl_id[]\' value=\'', ODB.ODB_ID, '\'><input ".($num_rows > 0 ? "readonly" : "")." required type=\'number\' min=\'0\' name=\'opt_price[]\' value=\'', IFNULL(ODB.opt_price, IFNULL(ODB.Price, ".($num_rows > 0 ? "0" : "''").")), '\' amount=\'', ODB.Amount, '\'><br>') Price

								,CONCAT('<b style=\'line-height: 1.79em;\'><a', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODB.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' <b style=\'font-size: 1.3em;\'>', ODB.Amount, '</b> ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('патина (', ODB.patina, ')'), ''), '</a></b><br>') Zakaz

								,CONCAT('<span class=\'wr_mt\'>', IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0 AND ODB.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), ''), '<span ptid=\'', IFNULL(MT.PT_ID, ''), '\' mtid=\'', IFNULL(MT.MT_ID, ''), '\' id=\'m', ODB.ODB_ID, '\' class=\'mt', IFNULL(MT.MT_ID, ''), IF(MT.removed=1, ' removed', ''), ' material ',
									CASE ODB.IsExist
										WHEN 0 THEN 'bg-red'
										WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODB.order_date, '%d.%m.%y'), ' Ожидается: ', DATE_FORMAT(ODB.arrival_date, '%d.%m.%y'))
										WHEN 2 THEN 'bg-green'
										ELSE 'bg-gray'
									END,
								'\'>', IFNULL(MT.Material, ''), '</span></span><br>') Material

								,CONCAT('<a class=\'nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width: 30px;\' title=\'(', IFNULL(WD.Name, 'Не назначен!'), ')\'><i class=\"fa fa-cog\" aria-hidden=\"true\" style=\"line-height: 1.45em;\"></i></div>')) SEPARATOR ''), '</a><br>') Steps

							FROM OrdersDataBlank ODB
							LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
							LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
							LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
							LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
							WHERE ODB.Del = 0
							GROUP BY ODB.ODB_ID
							ORDER BY PT_ID DESC, itemID
							) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
						LEFT JOIN (
							SELECT OD_ID, SUM(payment_sum) payment_sum
							FROM OrdersPayment
							GROUP BY OD_ID
						) OP ON OP.OD_ID = OD.OD_ID
						WHERE SH.CT_ID = {$CT_ID}
							".($KA_ID ? "AND SH.KA_ID = {$KA_ID}" : "AND SH.KA_ID IS NULL")."
							".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
							AND OD.Del = 0
							".($num_rows > 0 ? "AND (OD.StartDate IS NOT NULL OR (SH.KA_ID IS NULL AND OD.PFI_ID IS NOT NULL))" : "AND (OD.StartDate IS NULL OR (SH.KA_ID IS NULL AND OD.PFI_ID IS NULL))")."
							AND OD.ReadyDate IS NOT NULL
							AND IFNULL(OP.payment_sum, 0) = 0
							AND NOT (OS.locking_date IS NOT NULL AND SH.KA_ID IS NULL)
						GROUP BY OD.OD_ID
						ORDER BY OD.ReadyDate ".($num_rows > 0 ? "DESC LIMIT {$num_rows}" : "ASC");

			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			$html .= "<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>";
			$html .= "<table class='main_table' id='to_invoice'><thead><tr>";
			$html .= "<th width='70'>Код<br>Создан</th>";
			$html .= "<th width='20%'>Заказчик [Продажа]-[Сдача]</th>";
			$html .= "<th width='10%'>Салон</th>";
			$html .= "<th width='70'>Цена за единицу</th>";
			$html .= "<th width='30%'>Заказ</th>";
			$html .= "<th width='20%'>Материал</th>";
			$html .= "<th width='20%'>Цвет</th>";
			$html .= "<th width='100'>Этапы</th>";
			$html .= "<th width='40'>Принят</th>";
			$html .= "<th width='20%'>Примечание</th>";
			$html .= "</tr></thead><tbody>";
			while( $row = mysqli_fetch_array($res) ) {
				$html .= "<tr class='shop{$row["SH_ID"]}'>";
				$html .= "<td><input type='checkbox' name='ord[]' id='ord_{$row["OD_ID"]}' class='chbox' value='{$row["OD_ID"]}'>";
				$html .= "<label for='ord_{$row["OD_ID"]}'><b class='code'>{$row["Code"]}</b></label><br><span>{$row["AddDate"]}</span></td>";
				$html .= "<td><span class='nowrap'>{$row["ClientName"]}<br>[{$row["StartDate"]}]-[{$row["EndDate"]}]</span></td>";
				$html .= "<td><span class='nowrap'>{$row["Shop"]}</span></td>";
				$html .= "<td>{$row["Price"]}</td>";
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
				$html .= "<td><span class='nowrap'>{$row["Material"]}</span></td>";
				$html .= "<td class='{$class}' title='{$title}'>{$row["Color"]}</td>";
				$html .= "<td><span class='nowrap material'>{$row["Steps"]}</span></td>";
					// Если заказ принят
					if( $row["confirmed"] == 1 ) {
						$class = 'confirmed';
						$title = 'Принят в работу';
					}
					else {
						$class = 'not_confirmed';
						$title = 'Не принят в работу';
					}
				$html .= "<td class='{$class}' title='{$title}'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";
				$html .= "<td>{$row["Comment"]}</td>";
				$html .= "</tr>";
			}
			$html .= "</tbody></table>";
			$html .= "<p><input type='checkbox' id='selectallbottom'><label for='selectallbottom'>Выбрать все</label></p>";
			$html = addslashes($html);
			echo "window.top.window.$('#orders_to_invoice').html('{$html}');";
			echo "window.top.window.$('#orders_to_invoice input[type=\"number\"], #orders_to_invoice input[type=\"hidden\"]').attr('disabled', true);";
			echo "window.top.window.$('#orders_to_invoice input[type=\"number\"]').hide();";
			echo "window.top.window.$('#orders_to_invoice input[type=\"number\"]').attr('placeholder', 'цена');";
			echo "window.top.window.$('.button_shops').button();";
			echo "$('.button_shops').prop('checked', true).change();";
			//echo "window.top.window.$('.chbox').button();";
		}

	break;
///////////////////////////////////////////////////////////////////

// Форма добавления платежа к заказу
case "add_payment":
	$OD_ID = $_GET["OD_ID"];
	$html = "";

	// Узнаем фамилию заказчика, салон, счет терминала в салоне, закрыт ли месяц
	$query = "SELECT OD.ClientName
					,SH.SH_ID
					,SH.Shop
					,SH.FA_ID
					,IF(OS.locking_date IS NOT NULL, 1, 0) is_lock
				FROM OrdersData OD
				JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
				WHERE OD.OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$ClientName = mysqli_result($res,0,'ClientName');
	$SH_ID = mysqli_result($res,0,'SH_ID');
	$Shop = mysqli_result($res,0,'Shop');
	$FA_ID = mysqli_result($res,0,'FA_ID');
	$is_lock = mysqli_result($res,0,'is_lock');

	$html .= "<input type='hidden' name='OD_ID' value='{$OD_ID}'>";
	$html .= "<table><thead><tr>";
	$html .= "<th style='width: 56px;'>Касса</th>";
	$html .= "<th>Дата</th>";
	$html .= "<th>Сумма</th>";
	$html .= "<th>Терминал</th>";
	$html .= "<th>Фамилия</th>";
	$html .= "<th>Автор</th>";
	$html .= "</tr></thead><tbody>";

	// Выводим список ранее внесенных платежей
	$query = "SELECT OP.OP_ID
					,DATE_FORMAT(OP.payment_date, '%d.%m.%y') payment_date
					,OP.payment_sum
					,IF(IFNULL(OP.terminal_payer, '') = '', 0, 1) terminal
					,OP.terminal_payer
					,IFNULL(OP.FA_ID, 0) FA_ID
					,USR_Name(OP.author) Name
					,IF(OP.FA_ID IS NOT NULL AND OP.terminal_payer IS NULL, FA.name, '') account
					,SH.Shop
				FROM OrdersPayment OP
				LEFT JOIN FinanceAccount FA ON FA.FA_ID = OP.FA_ID
				LEFT JOIN Shops SH ON SH.SH_ID = OP.SH_ID
				WHERE OD_ID = {$OD_ID} AND IFNULL(payment_sum, 0) != 0
				ORDER BY OP_ID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<tr>";
		if( $row["account"] ) {
			$html .= "<td class='nowrap'><b>{$row["account"]}</b></td>";
		}
		else {
			$html .= "<td class='nowrap'>".($row["terminal"] ? "" : $row["Shop"])."</td>";
		}
		$html .= "<td>{$row["payment_date"]}</td>";
		$format_payment_sum = number_format($row["payment_sum"], 0, '', ' ');
		$color = $row["payment_sum"] > 0 ? "#16A085" : "#E74C3C";
		$html .= "<td class='txtright'><b style='color: {$color};'>{$format_payment_sum}</b></td>";
		$html .= "<td>".($row["terminal"] ? "<i title='Оплата по терминалу' class='fa fa-credit-card' aria-hidden='true'></i>" : "")."</td>";
		$html .= "<td>{$row["terminal_payer"]}</td>";
		$html .= "<td>{$row["Name"]}</td>";
		$html .= "</tr>";
	}
	if( !$is_lock ) { // Если заказ не закрыт то можно добавить оплату
		$payment_date = date('d.m.Y');
		$html .= "<tr style='background: #6f6;'>";
		$html .= "<td><select style='width: 50px;' class='account' name='FA_ID_add'>";
		$html .= "<option value=''>{$Shop}</option>";
		if( in_array('finance_all', $Rights) or in_array('finance_account', $Rights) ) {
			$query = "SELECT FA.FA_ID, FA.name, IF(FA.USR_ID = {$_SESSION["id"]}, 'selected', '') selected FROM FinanceAccount FA";
			$query .= in_array('finance_account', $Rights) ? " WHERE FA.USR_ID = {$_SESSION["id"]}" : "";
			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			while( $row = mysqli_fetch_array($res) ) {
				// Если на производстве, то по дефолту касса пользователя
				if( in_array('order_add_confirm', $Rights) ) {
					$query = "SELECT ";
				}
					$html .= "<option ".(in_array('order_add_confirm', $Rights) ? $row["selected"] : "")." value='{$row["FA_ID"]}'>{$row["name"]}</option>";
			}
		}
		$html .= "</select>";
		$html .= "<input type='hidden' name='SH_ID_add' value='{$SH_ID}'></td>";

		$html .= "<td><input type='text' class='' style='width: 90px; text-align: center;' name='payment_date_add' value='{$payment_date}' readonly></td>";

		$html .= "<td><input type='number' class='payment_sum' name='payment_sum_add'></td>";

		if( $FA_ID ) {
			$html .= "<td><input type='checkbox' class='terminal' name='terminal_add' value='1'></td>";
			$html .= "<td><input type='text' class='terminal_payer' name='terminal_payer_add' value='{$ClientName}'></td>";
		}
		else {
			$html .= "<td><input style='display: none;' type='checkbox' class='terminal' name='terminal_add' value='1'></td>";
			$html .= "<td><input style='display: none;' type='text' class='terminal_payer' name='terminal_payer_add' value='{$ClientName}'></td>";
		}

		$html .= "<td>{$_SESSION['name']}</td>";
		$html .= "</tr>";
	}
	else {
		$html .= "<tr style='background: #6f6;'><td colspan='6'><b>Отчетный период закрыт. Внесение оплаты невозможно.</b></td></tr>";
	}
	$html .= "</tbody></table>";

	$html .= "<div class='accordion'>";
	$html .= "<h3>Памятка по внесению оплаты</h3>";
	$html .= "<div><ul>";
	$html .= "<li>Ранее добавленные платежи <b>не редактируются</b>. Если нужно изменить или отменить предыдущую запись, то создайте новую корректирующую операцию с отрицательной суммой.</li>";
	$html .= "<li>Если нужно совершить возврат денег по заказу, он так же вносится со знаком минус.</li>";
	$html .= "<li>Для переноса платежа с одного заказа на другой: сначала сделайте возврат платежа на первом заказе, затем внесите эту сумму на второй заказ.</li>";
	$html .= "</ul></div>";
	$html .= "</div>";

	$html = addslashes($html);
	echo "window.top.window.$('#add_payment fieldset').html('{$html}');";
	// Инициируем акордион
	echo "window.top.window.$('#add_payment .accordion').accordion({collapsible: true, heightStyle: 'content', active: false});";

	break;
///////////////////////////////////////////////////////////////////

// Форма редактирования цены заказа
case "update_price":
	$OD_ID = $_GET["OD_ID"];

	// Узнаем скидку заказа
	$query = "SELECT discount FROM OrdersData WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
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

					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('патина (', ODD.patina, ')'), ''), '</i></b><br>') Zakaz

			  FROM OrdersDataDetail ODD
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  WHERE ODD.OD_ID = {$OD_ID} AND ODD.Del = 0
			  GROUP BY ODD.ODD_ID
			  UNION ALL
			  SELECT ODB.OD_ID
					,0 PT_ID
					,ODB.ODB_ID itemID
					,ODB.Price
					,ODB.Amount
					,0 PM_ID
					,0 PME_ID

					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODB.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('патина (', ODB.patina, ')'), ''), '</i></b><br>') Zakaz

			  FROM OrdersDataBlank ODB
			  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
			  WHERE ODB.OD_ID = {$OD_ID} AND ODB.Del = 0
			  GROUP BY ODB.ODB_ID
			  ORDER BY PT_ID DESC, itemID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<tr>";
		$html .= "<input type='hidden' name='PT_ID[]' value='{$row["PT_ID"]}'>";
		$html .= "<input type='hidden' name='itemID[]' value='{$row["itemID"]}'>";
		$html .= "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		$html .= "<td class='prod_price'><input type='number' id='prod_price{$row["itemID"]}' min='1' name='price[]' value='{$row["Price"]}' style='width: 70px; text-align: right;'></td>";
		$html .= "<td class='prod_amount' style='text-align: center; font-size: 1.3em; font-weight: bold;'>{$row["Amount"]}</td>";
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
///////////////////////////////////////////////////////////////////

// Формирование дропдауна со списком салонов
case "create_shop_select":
	$OD_ID = $_GET["OD_ID"];
	$SH_ID = $_GET["SH_ID"] ? $_GET["SH_ID"] : 0;
	$html = "";

	// Узнаём отгрузку у заказа, дату отгрузки, регион, накладную, плательщика
	$query = "SELECT IFNULL(OD.SHP_ID, 0) SHP_ID
					,OD.ReadyDate
					,SH.CT_ID
					,OD.PFI_ID
					,PFI.platelshik_id
					,IF((SH.KA_ID IS NULL AND SH.SH_ID IS NOT NULL), 1, 0) retail
				FROM OrdersData OD
				LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
				WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$SHP_ID = mysqli_result($res,0,'SHP_ID');
	$ReadyDate = mysqli_result($res,0,'ReadyDate');
	$CT_ID = mysqli_result($res,0,'CT_ID');
	$PFI_ID = mysqli_result($res,0,'PFI_ID');
	$platelshik_id = mysqli_result($res,0,'platelshik_id');
	$retail = mysqli_result($res,0,'retail');

	// Формируем элементы дропдауна
	if( $PFI_ID ) {
		$query = "SELECT SH.SH_ID
						,CONCAT(CT.City, '/', SH.Shop) AS Shop
						,'selected' selected
						,CT.Color
					FROM Shops SH
					JOIN Cities CT ON CT.CT_ID = SH.CT_ID
					WHERE SH.SH_ID = {$SH_ID}

					UNION

					SELECT SH.SH_ID
						,CONCAT(CT.City, '/', SH.Shop) AS Shop
						,IF(SH.SH_ID = {$SH_ID}, 'selected', '') AS selected
						,CT.Color
					FROM Shops SH
					JOIN Cities CT ON CT.CT_ID = SH.CT_ID
					WHERE ".($retail ? "CT.CT_ID = {$CT_ID} AND SH.KA_ID IS NULL" : "SH.KA_ID = {$platelshik_id}")."
						".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
						".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."

					ORDER BY Shop";
	}
	elseif( $SHP_ID or $ReadyDate ) {
		if( in_array('order_add_confirm', $Rights) or $SH_ID == 0 ) {
			$html .= "<option value='0' selected style='background: #999;'>Свободные</option>";
		}
		$query = "SELECT SH.SH_ID
						,CONCAT(CT.City, '/', SH.Shop) AS Shop
						,'selected' selected
						,CT.Color
					FROM Shops SH
					JOIN Cities CT ON CT.CT_ID = SH.CT_ID
					WHERE SH.SH_ID = {$SH_ID}

					UNION

					SELECT SH.SH_ID
						,CONCAT(CT.City, '/', SH.Shop) AS Shop
						,IF(SH.SH_ID = {$SH_ID}, 'selected', '') AS selected
						,CT.Color
					FROM Shops SH
					JOIN Cities CT ON CT.CT_ID = SH.CT_ID
					WHERE CT.CT_ID = {$CT_ID}
						".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
						".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."

					ORDER BY Shop";
	}
	else {
		if( in_array('order_add_confirm', $Rights) or $SH_ID == 0 ) {
			$html .= "<option value='0' selected style='background: #999;'>Свободные</option>";
		}
		$query = "SELECT SH.SH_ID
						,CONCAT(CT.City, '/', SH.Shop) AS Shop
						,'selected' selected
						,CT.Color
					FROM Shops SH
					JOIN Cities CT ON CT.CT_ID = SH.CT_ID
					WHERE SH.SH_ID = {$SH_ID}

					UNION

					SELECT SH.SH_ID
						,CONCAT(CT.City, '/', SH.Shop) AS Shop
						,IF(SH.SH_ID = {$SH_ID}, 'selected', '') AS selected
						,CT.Color
					FROM Shops SH
					JOIN Cities CT ON CT.CT_ID = SH.CT_ID
					WHERE CT.CT_ID IN ({$USR_cities})
						".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
						".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."

					ORDER BY Shop";
	}
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	while( $row = mysqli_fetch_array($res) )
	{
		$html .= "<option value='{$row["SH_ID"]}' {$row["selected"]} style='background: {$row["Color"]};'>{$row["Shop"]}</option>";
	}

	$html = addslashes($html);
	echo "window.top.window.$('.shop_cell#{$OD_ID} .select_shops').html('{$html}');";

	break;
///////////////////////////////////////////////////////////////////

// Редактирование салона
case "update_shop":
	$OD_ID = $_GET["OD_ID"];
	$SH_ID = $_GET["SH_ID"] ? $_GET["SH_ID"] : "NULL";

	// Узнаем название старого салона
	$query = "SELECT SH.Shop FROM Shops SH JOIN OrdersData OD ON OD.SH_ID = SH.SH_ID AND OD.OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$old_shop = mysqli_result($res,0,'Shop') ? mysqli_result($res,0,'Shop') : 'Свободные';

	// Меняем салон в заказе
	$query = "UPDATE OrdersData SET SH_ID = {$SH_ID}, author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
	mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: '".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	// Узнаем название нового салона
	$query = "SELECT IFNULL(SH.Shop, 'Свободные') Shop
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS ShopCity
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,IFNULL(OD.SH_ID, 0) SH_ID
					,CheckPayment(OD.OD_ID) attention
				FROM OrdersData OD
				LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
				WHERE OD.OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$new_shop = mysqli_result($res,0,'Shop');
	$CTColor = mysqli_result($res,0,'CTColor');
	$ShopCity = mysqli_result($res,0,'ShopCity');
	$SH_ID = mysqli_result($res,0,'SH_ID');
	$ShopCity = addslashes($ShopCity);
	$attention = mysqli_result($res,0,'attention');

	echo "$('.shop_cell[id={$OD_ID}] span').html('{$ShopCity}');";
	echo "$('.shop_cell[id={$OD_ID}] span').attr('style', 'background: {$CTColor};');";
	echo "$('.shop_cell[id={$OD_ID}]').attr('SH_ID', '{$SH_ID}');";
	// Если есть оплата в кассу другого салона
	if( $attention ) {
		echo "$('.add_payment_btn[id={$OD_ID}]').addClass('attention');";
		echo "$('.add_payment_btn[id={$OD_ID}]').attr('title', 'Имеются платежи, внесённые в кассу другого салона!');";
		echo "noty({timeout: 10000, text: 'У этого заказа имеются платежи, внесённые в кассу другого салона! Проверьте оплату в реализации.', type: 'error'});";
	}
	else {
		echo "$('.add_payment_btn[id={$OD_ID}]').removeClass('attention');";
		echo "$('.add_payment_btn[id={$OD_ID}]').removeAttr('title');";
	}

	echo "noty({timeout: 3000, text: 'Салон изменен с <b>{$old_shop}</b> на <b>{$new_shop}</b>', type: 'success'});";
	if( $SH_ID == 0 ) {
//		echo "window.top.window.$('.main_table tr[id=\"ord{$OD_ID}\"] action a.shipping').hide();";
		echo "window.top.window.$('.main_table tr[id=\"ord{$OD_ID}\"]').hide('fast');";
		echo "noty({timeout: 4000, text: 'Заказ перемещен в <b>СВОБОДНЫЕ</b>', type: 'alert'});";
	}
//	else {
//		echo "window.top.window.$('.main_table tr[id=\"ord{$OD_ID}\"] action a.shipping').show();";
//	}

	break;
///////////////////////////////////////////////////////////////////

// Редактирование комментариев
case "update_comment":
	$OD_ID = $_GET["OD_ID"];
	$comment = mysqli_real_escape_string( $mysqli, $_GET["val"] );

	// Обновляем комментарий
	$query = "UPDATE OrdersData SET Comment = '{$comment}', author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
	mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: '".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	echo "$('.comment_cell[id={$OD_ID}] span').html('{$comment}');";
	echo "noty({timeout: 3000, text: 'Комментарий был обновлен.', type: 'success'});";

	break;
///////////////////////////////////////////////////////////////////

// Редактирование даты продажи
case "update_sell_date":
	$OD_ID = $_GET["OD_ID"];
	$StartDate = $_GET["StartDate"] ? '\''.date( 'Y-m-d', strtotime($_GET["StartDate"]) ).'\'' : "NULL";

	// Узнаем старую дату продажи
	$query = "SELECT DATE_FORMAT(StartDate, '%d.%m.%Y') StartDate FROM OrdersData WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$old_StartDate = mysqli_result($res,0,'StartDate');

	// Меняем дату продажи
	$query = "UPDATE OrdersData SET StartDate = {$StartDate}, author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
	if( !mysqli_query( $mysqli, $query ) ) {
		echo "$('td#{$OD_ID} .sell_date').val('{$old_StartDate}');";
		die("noty({timeout: 10000, text: '".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	}

	echo "noty({timeout: 3000, text: 'Дата продажи изменена с <b>{$old_StartDate}</b> на <b>{$_GET["StartDate"]}</b>', type: 'success'});";

	break;
///////////////////////////////////////////////////////////////////

// Редактирование примечания к реализации
case "update_sell_comment":
	$OD_ID = $_GET["OD_ID"];
	$sell_comment = trim( mysqli_real_escape_string( $mysqli, $_GET["sell_comment"] ) );

	// Узнаем старое примечание
	$query = "SELECT sell_comment FROM OrdersData WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$old_sell_comment = mysqli_result($res,0,'sell_comment');

	// Меняем примечание
	$query = "UPDATE OrdersData SET sell_comment = '{$sell_comment}', author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
	if( !mysqli_query( $mysqli, $query ) ) {
		echo "$('td#{$OD_ID} .sell_comment').val('{$old_sell_comment}');";
		die("noty({timeout: 10000, text: '".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	}

	$old_sell_comment = htmlspecialchars($old_sell_comment, ENT_QUOTES);
	$sell_comment = htmlspecialchars($sell_comment, ENT_QUOTES);
	echo "noty({timeout: 3000, text: 'Комментарий изменен с <b>{$old_sell_comment}</b> на <b>{$sell_comment}</b>', type: 'success'});";

	break;
///////////////////////////////////////////////////////////////////

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

					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('патина (', ODD.patina, ')'), ''), '</i></b><br>') Zakaz

			  FROM OrdersDataDetail ODD
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  WHERE ODD.OD_ID = {$OD_ID} AND ODD.Del = 0
			  GROUP BY ODD.ODD_ID
			  UNION ALL
			  SELECT ODB.OD_ID
					,0 PT_ID
					,ODB.ODB_ID itemID
					,ODB.Amount

					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODB.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('патина (', ODB.patina, ')'), ''), '</i></b><br>') Zakaz

			  FROM OrdersDataBlank ODB
			  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
			  WHERE ODB.OD_ID = {$OD_ID} AND ODB.Del = 0
			  GROUP BY ODB.ODB_ID
			  ORDER BY PT_ID DESC, itemID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
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
///////////////////////////////////////////////////////////////////

// Обновление метража
case "footage":
	$oddid = $_GET["oddid"];
	$odbid = $_GET["odbid"];
	$val = $_GET["val"] ? $_GET["val"] : "NULL";

	if( $oddid != 'undefined' ) {
		$query = "UPDATE OrdersDataDetail SET MT_amount = {$val} WHERE ODD_ID = {$oddid}";
	}
	else {
		$query = "UPDATE OrdersDataBlank SET MT_amount = {$val} WHERE ODB_ID = {$odbid}";
	}
	mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: '".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	echo "noty({timeout: 3000, text: 'Метраж обновлен на: <b>\"{$val}\"</b>', type: 'success'});";

	break;
///////////////////////////////////////////////////////////////////

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
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$length = mysqli_result($res,0,'length') ? mysqli_result($res,0,'length') : 0;

	$query = "SELECT RPAD(MT.Material, {$length}, ' ') Material
					,RPAD(CONCAT('- ', ROUND(ODD_ODB.MT_amount, 1), ' м.п.'), 12, ' ') MT_amount
					,IF(ODD_ODB.MT_amount, DATE_FORMAT(ODD_ODB.order_date, '%d.%m.%y'), '') order_date
				FROM Materials MT
				JOIN (
					SELECT MT_ID, MT_amount, order_date
					FROM OrdersDataDetail
					WHERE ODD_ID IN ($oddids)
					UNION ALL
					SELECT MT_ID, MT_amount, order_date
					FROM OrdersDataBlank
					WHERE ODB_ID IN ($odbids)
				) ODD_ODB ON ODD_ODB.MT_ID = MT.MT_ID
				ORDER BY MT.Material";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	while( $row = mysqli_fetch_array($res) ) {
		$materials_name .= $row["Material"]."\\t".$row["MT_amount"]."\\t".$row["order_date"]."\\r\\n";
	}
	//$materials_name = addslashes( $materials_name );
	echo "window.top.window.$('#materials_name').html('{$materials_name}');";

	break;
///////////////////////////////////////////////////////////////////

// При смене типа операции меняется категория (в финансах)
case "cash_category":
	$type = $_GET["type"];

	if( $type == 0 ) {
		$html = "<label for='category'>На счет:</label><br>";
		$html .= "<select required name='to_account' id='to_account' style='width: 300px;'>";
		$html .= "<option value=''></option>";

		$html .= "<optgroup label='Нал'>";
		$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 0";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
		}
		$html .= "</optgroup>";

		$html .= "<optgroup label='Безнал'>";
		$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 1";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
		}
		$html .= "</optgroup>";

		$html .= "</select>";
		$html = addslashes($html);
		echo "window.top.window.$('#wr_category').html('{$html}');";
		echo "window.top.window.$('#wr_category select[name=to_account]').select2({ placeholder: 'Выберите счет', language: 'ru' });";
	}
	else {
		$html = "<label for='category'>Категория:</label><br>";
		$html .= "<select required name='category' id='category' style='width: 300px;'>";
		$html .= "<option value=''></option>";

		$query = "SELECT FC_ID, name FROM FinanceCategory WHERE type = {$type} AND FC_ID NOT IN (2,3,4,7,8)";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<option value='{$row["FC_ID"]}'>{$row["name"]}</option>";
		}

		$html .= "</select>";
		$html = addslashes($html);
		echo "window.top.window.$('#wr_category').html('{$html}');";
		echo "window.top.window.$('#wr_category select[name=category]').select2({ placeholder: 'Выберите категорию', language: 'ru' });";
	}

	break;
///////////////////////////////////////////////////////////////////

// Формирование выпадающего списка заготовок при выборе работника в форме добавления заготовок
case "blank_dropdown":
	$wd_id = $_GET["wd_id"];
	$min_size = 4;
	$html = "";

	if( $wd_id != "null" ) {
		// Список частых заготовок
		$html .= "<optgroup label='Частые'>";
		$query = "SELECT BL.BL_ID, BL.Name
					FROM BlankList BL
					JOIN BlankStock BS ON BS.BL_ID = BL.BL_ID AND IFNULL(BS.WD_ID, 0) = {$wd_id} AND DATEDIFF(NOW(), BS.Date) <= 90
					GROUP BY BL.BL_ID
					ORDER BY BL.PT_ID, BL.Name";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		$size = 1;
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<option value='{$row["BL_ID"]}'>{$row["Name"]}</option>";
			$size++;
		}
		$html .= "</optgroup>";

		// Список остальных заготовок
		$html .= "<optgroup label='Остальные'>";
		$query = "SELECT BL.BL_ID, BL.Name
					FROM BlankList BL
					LEFT JOIN BlankStock BS ON BS.BL_ID = BL.BL_ID AND IFNULL(BS.WD_ID, 0) = {$wd_id} AND DATEDIFF(NOW(), BS.Date) <= 90
					WHERE BS.BL_ID IS NULL
					ORDER BY BL.PT_ID, BL.Name";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<option value='{$row["BL_ID"]}'>{$row["Name"]}</option>";
		}
		$html .= "</optgroup>";
		$size = ($size < $min_size) ? $min_size : $size;
		echo "window.top.window.$('#addblank #blank').attr('size', {$size});";
	}
	else {
		echo "window.top.window.$('#addblank #blank').attr('size', {$min_size});";
	}

	$html = addslashes($html);
	echo "window.top.window.$('#addblank #blank').html('{$html}');";

	break;
///////////////////////////////////////////////////////////////////

// Формирование списка задействованых заготовок при выборе заготовки в форме добавления заготовок
case "subblank_dropdown":
	$bl_id = $_GET["bl_id"];
	$wd_id = $_GET["wd_id"];
	$html = "<legend style='text-align: left;'>Задействованы:</legend>";
	$count = 0;

	if( $bl_id != 0 ) {
		// Список частых заготовок
		$query = "SELECT BLL.BLL_ID, BL.Name, BLL.Amount
					FROM BlankLink BLL
					JOIN BlankList BL ON BL.BL_ID = BLL.BLL_ID
					WHERE BLL.BL_ID = {$bl_id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<div style='text-align: left;'><b>{$row["Name"]}:</b></div>";
			$html .= "<input type='hidden' name='amount[]' value='{$row["Amount"]}'>";
			$html .= "<input type='hidden' name='bll_id[]' value='{$row["BLL_ID"]}'>";
			$count++;

			// Формируем выпадающий список со связанными заготовками
			$query = "SELECT IFNULL(BC.WD_ID, 0) WD_ID, (BC.count + BC.start_balance) count, IFNULL(WD.Name, 'Без работника') Name
						FROM BlankCount BC
						LEFT JOIN WorkersData WD ON WD.WD_ID = BC.WD_ID
						WHERE BL_ID = {$row["BLL_ID"]}";
			$subres = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			$select = "<select required name='wd_id[]' style='width: 250px;'>";
			$select .= "<option value=''>-=Выберите вариант из списка=-</option>";
			while( $subrow = mysqli_fetch_array($subres) )
			{
				$selected = ($subrow["count"] > 0 and $subrow["WD_ID"] == $wd_id) ? "selected" : "";
				$select .= "<option {$selected} value='{$subrow["WD_ID"]}'>({$subrow["count"]} шт.) {$subrow["Name"]}</option>";
			}
			$select .= "</select>";

			$html .= $select;
		}
	}

	if( $count ) {
		$html = addslashes($html);
		echo "window.top.window.$('#addblank #subblank').html('{$html}');";
		echo "window.top.window.$('#addblank #subblank').prop('disabled', false);";
		echo "window.top.window.$('#addblank #subblank').show('fast');";
	}
	else {
		echo "window.top.window.$('#addblank #subblank').prop('disabled', true);";
		echo "window.top.window.$('#addblank #subblank').hide('fast');";
	}

	break;
///////////////////////////////////////////////////////////////////

// Обновление начального значения заготовок по рабочим
case "start_balance_worker":
	$wd_id = $_GET["wd_id"];
	$bl_id = $_GET["bl_id"];
	$val = $_GET["val"] ? $_GET["val"] : "0";

	// Обновляем начальное значение заготовки у рабочего
	$query = "UPDATE BlankCount SET start_balance = {$val}, last_date = NOW() WHERE BL_ID = {$bl_id} AND WD_ID = {$wd_id}";
	mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: '".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	// Узнаем общее кол-во заготовки и начальное значение
	$query = "SELECT SUM(BC.count + BC.start_balance) Amount, SUM(BC.start_balance) start_balance
				FROM BlankCount BC
				WHERE BC.BL_ID = {$bl_id}
				GROUP BY BC.BL_ID";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$start_balance = mysqli_result($res,0,'start_balance');
	$blank_amount = mysqli_result($res,0,'Amount');
	$color = ( $blank_amount < 0 ) ? ' bg-red' : '';
	$html = "<b class='{$color}'>{$blank_amount}</b>";
	$html = addslashes($html);

	// Узнаем общее кол-во заготовки у рабочего
	$query = "SELECT (BC.count + BC.start_balance) Amount
				FROM BlankCount BC
				WHERE BC.BL_ID = {$bl_id} AND BC.WD_ID = {$wd_id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$worker_amount = mysqli_result($res,0,'Amount');
	$sub_color = ( $worker_amount < 0 ) ? ' bg-red' : '';
	$sub_html = "<i class='{$sub_color}'>{$worker_amount}</i>";
	$sub_html = addslashes($sub_html);

	echo "window.top.window.$('#exist_blank .sub_blank#{$bl_id} .start_balance').hide('fast');";
	echo "window.top.window.$('#exist_blank .sub_blank#{$bl_id} .blank_amount span').hide('fast');";
	echo "window.top.window.$('#exist_blank #blank_{$bl_id}_{$wd_id} .blank_amount span').hide('fast');";
	echo "window.top.window.$('#exist_blank .sub_blank#{$bl_id} .start_balance').val('{$start_balance}');";
	echo "window.top.window.$('#exist_blank .sub_blank#{$bl_id} .blank_amount span').html('{$html}');";
	echo "window.top.window.$('#exist_blank #blank_{$bl_id}_{$wd_id} .blank_amount span').html('{$sub_html}');";
	echo "window.top.window.$('#exist_blank .sub_blank#{$bl_id} .start_balance').show('fast');";
	echo "window.top.window.$('#exist_blank .sub_blank#{$bl_id} .blank_amount span').show('fast');";
	echo "window.top.window.$('#exist_blank #blank_{$bl_id}_{$wd_id} .blank_amount span').show('fast');";
	echo "noty({timeout: 3000, text: 'Начальное значение обновлено на: <b>\"{$val}\"</b>', type: 'success'});";

	break;
///////////////////////////////////////////////////////////////////

// Обновление начального значения заготовок верхнего уровня
case "start_balance_blank":
	$bl_id = $_GET["bl_id"];
	$val = $_GET["val"] ? $_GET["val"] : "0";

	// Обновляем начальное значение заготовки верхнего уровня
	$query = "UPDATE BlankList SET start_balance = {$val} WHERE BL_ID = {$bl_id}";
	mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: '".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");

	// Узнаем кол-во заготовок верхнего уровня
	$query = "SELECT IFNULL(BL.start_balance, 0) + IFNULL(SBS.Amount, 0) - IFNULL(SODD.Painting, 0) - IFNULL(SODB.Painting, 0) - IFNULL(SODD.PaintingDeleted, 0) - IFNULL(SODB.PaintingDeleted, 0) AmountBeforePainting
				FROM BlankList BL
				LEFT JOIN (
					SELECT BS.BL_ID, SUM(BS.Amount) Amount
					FROM BlankStock BS
					GROUP BY BS.BL_ID
				) SBS ON SBS.BL_ID = BL.BL_ID
				LEFT JOIN (
					SELECT PB.BL_ID
							,SUM(ODD.Amount * PB.Amount * IF(OD.Del, 0, 1)) Amount
							,SUM(IF(OD.IsPainting = 1, 0, ODD.Amount) * PB.Amount * IF(OD.Del, 0, 1)) Painting
							#,SUM(IF(OD.IsPainting = 2, ODD.Amount, 0) * PB.Amount) InPainting
							,SUM(IF(OD.IsPainting = 3, ODD.Amount, 0) * PB.Amount * OD.Del) PaintingDeleted
					FROM OrdersDataDetail ODD
					JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
					JOIN ProductBlank PB ON PB.PM_ID = ODD.PM_ID
					WHERE ODD.Del = 0
					GROUP BY PB.BL_ID
				) SODD ON SODD.BL_ID = BL.BL_ID
				LEFT JOIN (
					SELECT ODB.BL_ID
							,SUM(ODB.Amount * IF(OD.Del, 0, 1)) Amount
							,SUM(IF(OD.IsPainting = 1, 0, ODB.Amount) * IF(OD.Del, 0, 1)) Painting
							#,SUM(IF(OD.IsPainting = 2, ODB.Amount, 0)) InPainting
							,SUM(IF(OD.IsPainting = 3, ODB.Amount, 0) * OD.Del) PaintingDeleted
					FROM OrdersDataBlank ODB
					JOIN OrdersData OD ON OD.OD_ID = ODB.OD_ID
					WHERE ODB.BL_ID IS NOT NULL
					GROUP BY ODB.BL_ID
				) SODB ON SODB.BL_ID = BL.BL_ID
				WHERE BL.BL_ID = {$bl_id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
	$amount = mysqli_result($res,0,'AmountBeforePainting');
	$color = ( $amount < 0 ) ? ' bg-red' : '';
	$html = "<b class='{$color}'>{$amount}</b>";
	$html = addslashes($html);

	echo "window.top.window.$('#exist_blank #blank_{$bl_id} span').hide('fast');";
	echo "window.top.window.$('#exist_blank #blank_{$bl_id} span').html('{$html}');";
	echo "window.top.window.$('#exist_blank #blank_{$bl_id} span').show('fast');";
	echo "noty({timeout: 3000, text: 'Начальное значение обновлено на: <b>\"{$val}\"</b>', type: 'success'});";

	break;
/////////////////////////////////////////////////////////////////////

// Удаление заказа
case "order_del":
	$od_id = $_GET["od_id"];

	// Проверяем права на удаление заказа
	if( !in_array('order_add', $Rights) ) {
		echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
	}
	else {
		// Узнаем есть ли оплата по этому заказу
		$query = "SELECT IFNULL((SELECT SUM(payment_sum) FROM OrdersPayment WHERE OD_ID = {$od_id}), 0) order_payments";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		$order_payments = mysqli_result($res,0,'order_payments');

		// Если оплата есть, то сообщаем об этом иначе удаляем заказ
		if( $order_payments == 0 ) {
			$query = "UPDATE OrdersData SET Del = 1, DelDate = NOW(), author = {$_SESSION['id']} WHERE OD_ID={$od_id}";
			mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			echo "window.top.window.$('.main_table #ord{$od_id}').hide('slow');";
			echo "noty({timeout: 3000, text: 'Заказ удален!', type: 'success'});";
		}
		else {
			// Узнаем город заказа
			$query = "SELECT SH.CT_ID FROM OrdersData OD LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID WHERE OD_ID = {$od_id}";
			$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
			$CT_ID = mysqli_result($res,0,'CT_ID');
			$selling_link = "/selling.php?CT_ID={$CT_ID}#ord{$od_id}";
			echo "noty({text: 'По заказу внесена оплата <b>{$order_payments}р.</b> Проверьте <b><a href=\"{$selling_link}\" target=\"_blank\">реализацию</a></b> и повторите попытку удаления.', type: 'alert'});";
		}
	}

	break;
/////////////////////////////////////////////////////////////////////

// Отгрузка заказа
case "order_shp":
	$od_id = $_GET["od_id"];

	// Проверяем права на отгрузку заказа
	if( !in_array('order_ready', $Rights) ) {
		echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
	}
	else {
		$query = "UPDATE OrdersData SET ReadyDate = NOW(), author = {$_SESSION['id']} WHERE OD_ID={$od_id}";
		mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		echo "window.top.window.$('.main_table #ord{$od_id}').hide('slow');";
		echo "noty({timeout: 3000, text: 'Заказ успешно отгружен!', type: 'success'});";

		// Если это розничный заказ, то предлагаем перейти в реализацию
		$query = "SELECT IF((SH.KA_ID IS NULL AND SH.SH_ID IS NOT NULL), 1, 0) retail, SH.CT_ID FROM OrdersData OD LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID WHERE OD_ID = {$od_id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({timeout: 10000, text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'alert'});");
		$retail = mysqli_result($res,0,'retail');
		if( $retail == "1" ) {
			$CT_ID = mysqli_result($res,0,'CT_ID');
			$selling_link = "/selling.php?CT_ID={$CT_ID}#ord{$od_id}";
			echo "noty({text: 'Проверить <b><a href=\"{$selling_link}\" target=\"_blank\">реализацию</a></b>?', type: 'alert'});";
		}
	}

	break;
}
?>
