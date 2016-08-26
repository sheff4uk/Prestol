<?
	include "config.php";
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
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
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
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$text = "<input type=\'hidden\' name=\'ODD_ID\' value=\'$odd_id\'>";
	}
	else {
		$query = "SELECT IFNULL(BL.Name, ODB.Other) Name
						,ODB.Amount
				  FROM OrdersDataBlank ODB
				  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
				  WHERE ODB.ODB_ID = $odb_id";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$model = mysqli_result($res,0,'Name');
		$amount = mysqli_result($res,0,'Amount');
		$product = "<img src=\'/img/product_0.png\'>x{$amount}&nbsp;{$model}";

		// Получение информации об этапах производства
		$query = "SELECT 0 ST_ID, '-' Step, ODS.WD_ID, IF(ODS.WD_ID IS NULL, 'disabled', '') disabled, ODS.Tariff, IF (ODS.IsReady, 'checked', '') IsReady, IF(ODS.Visible = 1, 'checked', '') Visible, ODS.Old
				  FROM OrdersDataSteps ODS
				  WHERE ODS.ODB_ID = $odb_id
				  ORDER BY ODS.Old DESC";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

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
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
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
					,ODD.Color
					,MT.Material
					,ODD.IsExist
					,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
					,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
					,IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), '') clock
					,IF(ODD.is_check = 1, '', 'attention') is_check
					,SUM(IF(ODS.WD_ID IS NULL, 0, 1)) progress
					,GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR '') Steps
					,IF(SUM(ODS.Old) > 0, ' attention', '') Attention
			  FROM OrdersDataDetail ODD
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
			  LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
			  LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
			  LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID";
	$query .= " WHERE ODD.OD_ID IS NULL";
	$query .= ( $pt == 1 ) ? " AND PM.PT_ID = {$pt}" : "";
	$query .= ($_GET["model"] and $_GET["model"] <> "undefined") ? " AND (ODD.PM_ID = {$_GET["model"]} OR ODD.PM_ID IS NULL)" : "";
	$query .= ($_GET["form"] and $_GET["form"] <> "undefined") ? " AND ODD.PF_ID = {$_GET["form"]}" : "";
	$query .= ($_GET["mechanism"] and $_GET["mechanism"] <> "undefined") ? " AND ODD.PME_ID = {$_GET["mechanism"]}" : "";
//	$query .= ($_GET["length"] and $_GET["length"] <> "undefined") ? " AND ODD.Length = {$_GET["length"]}" : "";
//	$query .= ($_GET["width"] and $_GET["width"] <> "undefined") ? " AND ODD.Width = {$_GET["width"]}" : "";
//	$query .= ($_GET["color"]) ? " AND ODD.Color LIKE '%{$_GET["color"]}%'" : "";
//	$query .= ($_GET["material"]) ? " AND MT.Material LIKE '%{$_GET["material"]}%'" : "";
	$query .= " GROUP BY ODD.ODD_ID";
	$query .= " ORDER BY progress DESC";
	
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
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
		$table .= "<th>Цвет</th>";
		$table .= ($pt == 1) ? "<th>Ткань</th>" : "<th>Пластик</th>";
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
		$table .= "<td>{$row["Color"]}</td>";
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
		$table .= "{$row["Material"]}</span></td></tr>";
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

	// Обновляем статус лакировки
	$query = "UPDATE OrdersData SET IsPainting = {$val} WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Получаем статус лакировки из базы
	$query = "SELECT IsPainting FROM OrdersData WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$val = mysqli_result($res,0,'IsPainting');

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
	if( $isready == 1 and $archive != 1 ) {
		if( $val == 3 ) {
			echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] span.action').html('<a class=\"button\" onclick=\'if(confirm(\"Пожалуйста, подтвердите готовность заказа!\", \"?ready={$id}\")) return false;\' title=\'Готово\'><i style=\'color:red;\' class=\'fa fa-flag-checkered fa-lg\'></i></a>');";
			echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] span.action a').button();";
		}
		else {
			echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] span.action').html('');";
		}
	}
	echo "noty({timeout: 3000, text: 'Статус лакировки изменен на \"{$status}\"', type: 'success'});";
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

}
?>
