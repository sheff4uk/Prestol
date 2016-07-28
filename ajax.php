<?
	include "config.php";
	header( "Content-Type: text/html; charset=UTF-8" );

switch( $_GET["do"] )
{
// Генерирование формы этапов производства
case "steps":
	$odd_id = (int)$_GET["odd_id"];
	
	// Получение информации об изделии
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
	$query = "SELECT ST.ST_ID, ST.Step, ODS.WD_ID, IF(ODS.WD_ID IS NULL, 'disabled', '') disabled, ODS.Tariff, IF (ODS.IsReady, 'checked', '') IsReady, IF(ODS.Visible = 1, 'checked', '') Visible
			  FROM OrdersDataSteps ODS
			  JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
			  WHERE ODS.ODD_ID = $odd_id
			  ORDER BY ST.Sort";
	$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$text = "<input type=\'hidden\' name=\'ODD_ID\' value=\'$odd_id\'>";
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
		$selectworker = "<select name=\'WD_ID{$row["ST_ID"]}\' id=\'{$row["ST_ID"]}\' class=\'selectwr\'>";
		$selectworker .= "<option value=\'\'>-=Выберите работника=-</option>";
		$query = "SELECT WD.WD_ID, WD.Name, SUM(IFNULL(ODS.Amount, 0)) CNT
				  FROM WorkersData WD
				  LEFT JOIN (
					SELECT ODS.*, ODD.Amount 
					FROM OrdersDataSteps ODS
					JOIN OrdersDataDetail ODD ON ODD.ODD_ID = ODS.ODD_ID
					WHERE ODS.WD_ID IS NOT NULL AND ODS.ST_ID = {$row["ST_ID"]}
					LIMIT 100
				  ) ODS ON ODS.WD_ID = WD.WD_ID
				  WHERE WD.Hourly = 0
				  GROUP BY WD.WD_ID
				  ORDER BY CNT DESC";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subrow = mysqli_fetch_array($res) )
		{
			$selected = ( $row["WD_ID"] == $subrow["WD_ID"] ) ? "selected" : "";
			$selectworker .= "<option {$selected} value=\'{$subrow["WD_ID"]}\'>{$subrow["Name"]}</option>";
		}
		$selectworker .= "</select>";
		// Конец дропдауна со списком рабочих
		
		$text .= "<tr><td><b>{$row["Step"]}</b></td>";
		$text .= "<td>{$selectworker}</td>";
		$text .= "<td><input type=\'number\' min=\'0\' name=\'Tariff{$row["ST_ID"]}\' class=\'tariff\' value=\'{$row["Tariff"]}\'></td>";
		$text .= "<td><input type=\'checkbox\' id=\'IsReady{$row["ST_ID"]}\' name=\'IsReady{$row["ST_ID"]}\' class=\'isready\' value=\'1\' {$row["IsReady"]} {$row["disabled"]}><label for=\'IsReady{$row["ST_ID"]}\'></label></td>";
		$text .= "<td><input type=\'checkbox\' name=\'Visible{$row["ST_ID"]}\' value=\'1\' {$row["Visible"]}></td></tr>";
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
					,ODD.Material
					,ODD.IsExist
					,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
					,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
					,IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), '') clock
					,IF(ODD.is_check = 1, '', 'attention') is_check
					,SUM(IF(ODS.WD_ID IS NULL, 0, 1)) progress
			  FROM OrdersDataDetail ODD
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1";
	$query .= " WHERE ODD.OD_ID IS NULL";
	$query .= ( $pt == 1 ) ? " AND PM.PT_ID = {$pt}" : "";
	$query .= ($_GET["model"] and $_GET["model"] <> "undefined") ? " AND (ODD.PM_ID = {$_GET["model"]} OR ODD.PM_ID IS NULL)" : "";
	$query .= ($_GET["form"] and $_GET["form"] <> "undefined") ? " AND ODD.PF_ID = {$_GET["form"]}" : "";
	$query .= ($_GET["mechanism"] and $_GET["mechanism"] <> "undefined") ? " AND ODD.PME_ID = {$_GET["mechanism"]}" : "";
//	$query .= ($_GET["length"] and $_GET["length"] <> "undefined") ? " AND ODD.Length = {$_GET["length"]}" : "";
//	$query .= ($_GET["width"] and $_GET["width"] <> "undefined") ? " AND ODD.Width = {$_GET["width"]}" : "";
//	$query .= ($_GET["color"]) ? " AND ODD.Color LIKE '%{$_GET["color"]}%'" : "";
	$query .= ($_GET["material"]) ? " AND ODD.Material LIKE '%{$_GET["material"]}%'" : "";
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

		// Формируем список этапов
		$query = "SELECT ST.Step
						,ST.Short
						,(30 * ST.Size) Size
						,IFNULL(WD.Name, 'Не назначен!') Name
						,IF(ODS.IsReady, 'checked', '') IsReady
						,ODS.ST_ID
						,IF(ODS.WD_ID IS NULL, 'disabled', '') disabled
				  FROM OrdersDataSteps ODS
				  LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
				  JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
				  WHERE ODS.ODD_ID = {$row["ODD_ID"]} AND ODS.Visible = 1
				  ORDER BY ST.Sort";
		$sub_res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$steps = "<a class='nowrap'>";
		while( $sub_row = mysqli_fetch_array($sub_res) )
		{
			$steps .= "<input type='checkbox' class='checkstatus' {$sub_row["IsReady"]} id='{$row["ODD_ID"]}{$sub_row["ST_ID"]}' {$sub_row["disabled"]}><label class='step' style='width:{$sub_row["Size"]}px;' for='{$row["ODD_ID"]}{$sub_row["ST_ID"]}' title='{$sub_row["Step"]} ({$sub_row["Name"]})'>{$sub_row["Short"]}</label>";
		}
		$steps .= "</a>";
		$table .= "<td>{$steps}</td>";

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
			$background = "background: #fff;";
			$status = "Не в работе";
			break;
		case 2:
			$background = "background: #bd362f;";
			$status = "В работе";
			break;
		case 3:
			$background = "background: #3bec00;";
			$status = "Готово";
			break;
	}

	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.painting').attr('style', '{$background}');";
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.painting').attr('title', '{$status}');";
	echo "window.top.window.$('.main_table tr[id=\"ord{$id}\"] td.painting').attr('val', '{$val}');";
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
