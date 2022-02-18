<?
include_once "config.php";
include_once "checkrights.php";
$_GET['ajax'] = 1;
header( "Content-Type: text/html; charset=UTF-8" );

switch( $_GET["do"] )
{
// Генерирование формы этапов производства
case "steps":
	$odd_id = (int)$_GET["odd_id"];
	
	// Получение информации об изделии
	$query = "
		SELECT ODD.Amount
			,Zakaz(ODD.ODD_ID) Zakaz
			,OD.ReadyDate
		FROM OrdersDataDetail ODD
		LEFT JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
		WHERE ODD.ODD_ID = $odd_id
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$amount = mysqli_result($res,0,'Amount');
	$zakaz = mysqli_result($res,0,'Zakaz');
	$ready_date = mysqli_result($res,0,'ReadyDate');
	$product = "<h3><b style=\'font-size: 2em; margin-right: 20px;\'>{$amount}</b>{$zakaz}</h3>";

	// Получение информации об этапах производства
	$query = "
		SELECT IFNULL(ODS.ST_ID, 0) ST_ID
			,IFNULL(ST.Step, '-') Step
			,ODS.USR_ID
			,IF(ODS.USR_ID IS NULL, 'disabled', '') disabled
			,ODS.Tariff
			,IF (ODS.IsReady, 'checked', '') IsReady
			,IF(ODS.Visible = 1, 'checked', '') Visible
			,ODS.Old
		FROM OrdersDataSteps ODS
		LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
		WHERE ODS.ODD_ID = $odd_id
		ORDER BY ODS.Old DESC, ST.Sort
	";
	$result = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

	$text = "<input type=\'hidden\' name=\'ODD_ID\' value=\'$odd_id\'>";

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
		$selectworker = $ready_date ? "" : "<option value=\'\' selected>-=Работник не выбран=-</option>";
		//$selectworker .= "<optgroup label=\'Работающие\'>";
		$query = "
			SELECT USR.USR_ID
				,USR_ShortName(USR.USR_ID) Name
				,SUM(IFNULL(ODS.Amount, 0)) CNT
			FROM Users USR
			LEFT JOIN (
				SELECT ODS.USR_ID, ODD.Amount
				FROM OrdersDataSteps ODS
				JOIN OrdersDataDetail ODD ON ODD.ODD_ID = ODS.ODD_ID
				WHERE ODS.USR_ID IS NOT NULL AND IFNULL(ODS.ST_ID, 0) = {$row["ST_ID"]}
				ORDER BY ODS.ODD_ID DESC
				LIMIT 100
			) ODS ON ODS.USR_ID = USR.USR_ID
			WHERE USR.RL_ID = 10 AND USR.act = 1
			GROUP BY USR.USR_ID
			ORDER BY CNT DESC
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $subrow = mysqli_fetch_array($res) )
		{
			$selected = ( $row["USR_ID"] == $subrow["USR_ID"] ) ? "selected" : "";
			$selectworker .= "<option {$selected} value=\'{$subrow["USR_ID"]}\'>{$subrow["Name"]}</option>";
		}
		//$selectworker .= "</optgroup>";
		$selectworker .= "<optgroup label=\'Уволенные\'>";
		$query = "
			SELECT USR.USR_ID
				,USR_ShortName(USR.USR_ID) Name
			FROM Users USR
			WHERE USR.RL_ID = 10 AND USR.act = 0
			ORDER BY Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $subrow = mysqli_fetch_array($res) )
		{
			$selected = ( $row["USR_ID"] == $subrow["USR_ID"] ) ? "selected" : "";
			$selectworker .= "<option {$selected} value=\'{$subrow["USR_ID"]}\'>{$subrow["Name"]}</option>";
		}
		$selectworker .= "</optgroup>";
		// Конец дропдауна со списком рабочих
		
		if( $row["Old"] == 1 ) {
			$text .= "<tr style=\'background: #999;\'><td><b>{$row["Step"]}</b></td>";
			$text .= "<td><select disabled class=\'selectwr\'>{$selectworker}</select></td>";
			$text .= "<td><input disabled type=\'number\' class=\'tariff txtright\' value=\'{$row["Tariff"]}\'></td>";
			$text .= "<td><input disabled type=\'checkbox\' id=\'OldIsReady{$row["ST_ID"]}\' class=\'isready\' {$row["IsReady"]}><label for=\'OldIsReady{$row["ST_ID"]}\'></label></td>";
			$text .= "<td><input disabled type=\'checkbox\' {$row["Visible"]}></td></tr>";
		}
		else {
			$text .= "<tr><td class=\'stage\'><b>{$row["Step"]}</b></td>";
			$text .= "<td><select name=\'USR_ID{$row["ST_ID"]}\' id=\'{$row["ST_ID"]}\' class=\'selectwr\' size=\'5\'>{$selectworker}</select></td>";
			$text .= "<td><input type=\'number\' min=\'0\' name=\'Tariff{$row["ST_ID"]}\' class=\'tariff txtright\' value=\'{$row["Tariff"]}\'></td>";
			$text .= "<td><input ".($ready_date ? "onclick=\'return false;\'" : "")." type=\'checkbox\' id=\'IsReady{$row["ST_ID"]}\' name=\'IsReady{$row["ST_ID"]}\' class=\'isready\' value=\'1\' {$row["IsReady"]} {$row["disabled"]}><label for=\'IsReady{$row["ST_ID"]}\'></label></td>";
			$text .= "<td><input ".($ready_date ? "onclick=\'return false;\'" : "")." type=\'checkbox\' name=\'Visible{$row["ST_ID"]}\' value=\'1\' {$row["Visible"]}></td></tr>";
		}
	}
	$text .= "<tr><td></td>";
	$text .= "<td><h3 class=\'txtright\'>Общая сумма:</h3></td>";
	$text .= "<td><h3 id=\'steps_sum\' class=\'txtright\'></h3></td>";
	$text .= "<td></td>";
	$text .= "<td></td></tr>";

	$text .= "</tbody></table>";
	echo "$('#formsteps').html('{$text}');";
	break;
///////////////////////////////////////////////////////////////////

// Смена статуса лакировки
case "ispainting":

	$id = $_GET["od_id"];
	$isready = $_GET["isready"];
	$val = $_GET["val"];
	$val = ($val == 3) ? 1 : $val + 1;
	$shpid = $_GET["shpid"];
	$filter = $_GET["filter"];

	// Обновляем статус лакировки
	$query = "UPDATE OrdersData SET IsPainting = {$val}, paint_date = IF({$val} = 3, NOW(), NULL), author = {$_SESSION['id']} WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

	// Получаем статус лакировки, отгрузку, лакировщиков и тарифы
	$query = "SELECT IsPainting, IFNULL(SHP_ID, 0) SHP_ID, USR_ID, patina_USR_ID, tariff, patina_tariff FROM OrdersData WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$val = mysqli_result($res,0,'IsPainting');
	$SHP_ID = mysqli_result($res,0,'SHP_ID');
	$USR_ID = mysqli_result($res,0,'USR_ID');
	$patina_USR_ID = mysqli_result($res,0,'patina_USR_ID');
	$tariff = mysqli_result($res,0,'tariff');
	$patina_tariff = mysqli_result($res,0,'patina_tariff');

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

	echo "$('.main_table tr[id=\"ord{$id}\"] td.painting').removeClass('notready inwork ready');";
	echo "$('.main_table tr[id=\"ord{$id}\"] td.painting').addClass('{$class}');";
	echo "$('.main_table tr[id=\"ord{$id}\"] td.painting').attr('title', '{$status}');";
	echo "$('.main_table tr[id=\"ord{$id}\"] td.painting').attr('val', '{$val}');";
	echo "$('.main_table tr[id=\"ord{$id}\"] .painting_workers').text('');";

	// Если из отгрузки
	if( $shpid > 0 ) {
		// Узнаем все ли этапы завершены
		$query = "
			SELECT BIT_AND(IF(OD.IsPainting = 3 OR OD.CL_ID IS NULL, 1, 0)) IsPainting
				,BIT_AND(IFNULL(ODS.IsReady, 1)) IsReady
			FROM OrdersData OD
			LEFT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1 AND ODS.Old = 0
			WHERE OD.DelDate IS NULL AND OD.SHP_ID = {$shpid}
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$painting = mysqli_result($res,0,'IsPainting');
		$ready = mysqli_result($res,0,'IsReady');
		$is_orders_ready = ( $painting and $ready ) ? 1 : 0;

		echo "check_shipping({$is_orders_ready}, 1, {$filter});";
	}
	else {
		if( $isready == 1 and $val == 3 ) {
			echo "$('.main_table tr[id=\"ord{$id}\"] .shipping').show('fast');";
		}
		else {
			echo "$('.main_table tr[id=\"ord{$id}\"] .shipping').hide('fast');";
		}
	}
	if ($val == 1) {
		if ($tariff == "0" or $patina_tariff == "0") {
			if ($tariff == "0") {
				// Узнаём имя лакировщика
				$query = "SELECT USR_ShortName({$USR_ID}) Name";
				$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
				$painting_name = mysqli_result($res,0,'Name');
				// Форма лакировщика
				$painting_form = "<fieldset style=\"width: 50%; text-align: left; border: 1px solid darkred;\"><legend>Лакировал:</legend><b>{$painting_name}</b><div><label>Сумма вычета: </label><input type=\"number\" value=\"\" min=\"0\" id=\"tariff\" style=\"width: 70px;\"></div></fieldset>";
			}
			else {
				$painting_form = "<fieldset style=\"width: 50%; text-align: left; border: 1px solid darkred;\"></fieldset>";
			}
			if ($patina_tariff == "0") {
				// Узнаём имя патинировщика
				$query = "SELECT USR_ShortName({$patina_USR_ID}) Name";
				$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
				$patina_name = mysqli_result($res,0,'Name');
				// Форма патинировщика
				$patina_form = "<fieldset style=\"width: 50%; text-align: left; border: 1px solid darkred;\"><legend>Наносил патину:</legend><b>{$patina_name}</b><div><label>Сумма вычета: </label><input type=\"number\" value=\"\" min=\"0\" id=\"patina_tariff\" style=\"width: 70px;\"></div></fieldset>";
			}
			else {
				$patina_form = "<fieldset style=\"width: 50%; text-align: left; border: 1px solid darkred;\"></fieldset>";
			}

			echo "
				noty({
					modal: true,
					timeout: false,
					text: 'Статус лакировки изменен на <b>{$status}</b>.<br>Внимание! Набор был разделен. Пожалуйста укажите сумму вычета из баланса работника после отмены лакировки.<div style=\"display: flex;\">{$painting_form}{$patina_form}</div>',
					buttons: [
						{addClass: 'btn btn-primary', text: 'Ok', onClick: function (\$noty) {
							\$noty.close();
							var tariff = \$('#tariff').val();
							var patina_tariff = \$('#patina_tariff').val();
							\$.ajax({ url: 'ajax.php?do=painting_workers_reject&usr_id={$USR_ID}&tariff='+tariff+'&patina_usr_id={$patina_USR_ID}&patina_tariff='+patina_tariff+'&od_id={$id}', dataType: 'script', async: false });
						}
						}
					],
					type: 'error'
				});
			";
		}
		else {
			// Если отмена лакировки - убираем из начислений
			if ($USR_ID > 0 and $tariff > 0) {
				$query = "
					INSERT INTO PayLog(OD_ID, USR_ID, Pay, Comment, author)
					VALUES ({$id}, {$USR_ID}, -1 * {$tariff}, 'Лакировка', {$_SESSION['id']})
				";
				mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			}
			if ($patina_USR_ID > 0 and $patina_tariff > 0) {
				$query = "
					INSERT INTO PayLog(OD_ID, USR_ID, Pay, Comment, author)
					VALUES ({$id}, {$patina_USR_ID}, -1 * {$patina_tariff}, 'Патинирование', {$_SESSION['id']})
				";
				mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			}

			echo "noty({timeout: 3000, text: 'Статус лакировки изменен на <b>{$status}</b>', type: 'success'});";
			echo "$('.main_table tr[id=\"ord{$id}\"] td.painting #paint_color').prop('disabled', false);";
		}
	}
	elseif ($val == 3) {
		// Узнаём есть ли патина в наборе
		$query = "
			SELECT SUM(1) cnt FROM `OrdersDataDetail` WHERE OD_ID = {$id} AND P_ID IS NOT NULL
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$row = mysqli_fetch_array($res);
		$patina_cnt = $row["cnt"];

		// Формирование дропдауна со списком лакировщиков. Сортировка по релевантности.
		$painting_workers = "<select id='painting_usr_id' size='10'>";
		$painting_workers .= "<option selected value='0'>-=Выберите работника=-</option>";
		$painting_workers .= "<optgroup label='Работающие'>";
		$query = "
			SELECT USR.USR_ID, USR_ShortName(USR.USR_ID) Name, SUM(1) CNT
			FROM Users USR
			LEFT JOIN (
				SELECT OD.USR_ID
				FROM OrdersData OD
				WHERE OD.USR_ID IS NOT NULL
				ORDER BY OD.OD_ID DESC
				LIMIT 100
			) SOD ON SOD.USR_ID = USR.USR_ID
			WHERE USR.RL_ID = 12 AND USR.act = 1
			GROUP BY USR.USR_ID
			ORDER BY CNT DESC
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$painting_workers .= "<option ".($row["USR_ID"] == $USR_ID ? "selected" : "")." value='{$row["USR_ID"]}'>{$row["Name"]}</option>";
		}
		$painting_workers .= "</optgroup>";
		$painting_workers .= "<optgroup label='Уволенные'>";
		$query = "
			SELECT USR.USR_ID, USR_ShortName(USR.USR_ID) Name
			FROM Users USR
			WHERE USR.RL_ID = 12 AND USR.act = 0
			ORDER BY Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$painting_workers .= "<option ".($row["USR_ID"] == $USR_ID ? "selected" : "")." value='{$row["USR_ID"]}'>{$row["Name"]}</option>";
		}
		$painting_workers .= "</optgroup>";
		$painting_workers .= "</select>";
		$painting_workers = addslashes($painting_workers);
		// Конец дропдауна со списком лакировщиков

		// Формирование дропдауна со списком патинировщиков. Сортировка по релевантности.
		$patina_workers = "<select id='patina_usr_id' size='10'>";
		$patina_workers .= "<option selected value='0'>-=Выберите работника=-</option>";
		$patina_workers .= "<optgroup label='Работающие'>";
		$query = "
			SELECT USR.USR_ID, USR_ShortName(USR.USR_ID) Name, SUM(1) CNT
			FROM Users USR
			LEFT JOIN (
				SELECT OD.patina_USR_ID
				FROM OrdersData OD
				WHERE OD.patina_USR_ID IS NOT NULL
				ORDER BY OD.OD_ID DESC
				LIMIT 100
			) SOD ON SOD.patina_USR_ID = USR.USR_ID
			WHERE USR.RL_ID = 12 AND USR.act = 1
			GROUP BY USR.USR_ID
			ORDER BY CNT DESC
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$patina_workers .= "<option ".($row["USR_ID"] == $patina_USR_ID ? "selected" : "")." value='{$row["USR_ID"]}'>{$row["Name"]}</option>";
		}
		$patina_workers .= "</optgroup>";
		$patina_workers .= "<optgroup label='Уволенные'>";
		$query = "
			SELECT USR.USR_ID, USR_ShortName(USR.USR_ID) Name
			FROM Users USR
			WHERE USR.RL_ID = 12 AND USR.act = 0
			ORDER BY Name
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$patina_workers .= "<option ".($row["USR_ID"] == $patina_USR_ID ? "selected" : "")." value='{$row["USR_ID"]}'>{$row["Name"]}</option>";
		}
		$patina_workers .= "</optgroup>";
		$patina_workers .= "</select>";
		$patina_workers = addslashes($patina_workers);
		// Конец дропдауна со списком патинировщиков

		$painting_form = "<fieldset style=\"width: 50%; text-align: left; border: 1px solid darkgreen;\"><legend>Лакировал:</legend>{$painting_workers}<div><label>Тариф: </label><input type=\"number\" value=\"{$tariff}\" min=\"0\" id=\"tariff\" style=\"width: 70px;\"></div></fieldset>";
		if ($patina_cnt) {
			$patina_form = "<fieldset style=\"width: 50%; text-align: left; border: 1px solid darkgreen;\"><legend>Наносил патину:</legend>{$patina_workers}<div><label>Тариф: </label><input type=\"number\" value=\"{$patina_tariff}\" min=\"0\" id=\"patina_tariff\" style=\"width: 70px;\"></div></fieldset>";
		}
		else {
			$patina_form = "";
		}

		echo "
			noty({
				modal: true,
				timeout: false,
				text: 'Статус лакировки изменен на <b>{$status}</b>.<div style=\"display: flex;\">{$painting_form}{$patina_form}</div>',
				buttons: [
					{addClass: 'btn btn-primary', text: 'Ok', onClick: function (\$noty) {
						\$noty.close();
						var usr_id = \$('#painting_usr_id').val();
						var tariff = \$('#tariff').val();
						var patina_usr_id = \$('#patina_usr_id').val();
						var patina_tariff = \$('#patina_tariff').val();
						\$.ajax({ url: 'ajax.php?do=painting_workers&usr_id='+usr_id+'&tariff='+tariff+'&patina_usr_id='+patina_usr_id+'&patina_tariff='+patina_tariff+'&od_id={$id}', dataType: 'script', async: false });
					}
					}
				],
				type: 'success'
			});
		";

		echo "$('.main_table tr[id=\"ord{$id}\"] td.painting #paint_color').prop('disabled', true);";
	}
	else {
		echo "noty({timeout: 3000, text: 'Статус лакировки изменен на <b>{$status}</b>', type: 'success'});";
	}
	break;
///////////////////////////////////////////////////////////////////

// Сохранение в базу лакировщика
case "painting_workers":

	$id = $_GET["od_id"];
	$usr_id = $_GET["usr_id"];
	$patina_usr_id = $_GET["patina_usr_id"];
	$tariff = $_GET["tariff"];
	$patina_tariff = $_GET["patina_tariff"];

	if( $usr_id > 0 ) {
		// Узнаем имя лакировщика
		$query = "SELECT USR_ShortName({$usr_id}) Name";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$Name = mysqli_result($res,0,'Name');

		$query = "UPDATE OrdersData SET USR_ID = {$usr_id}, tariff = ".($tariff > 0 ? $tariff : "NULL").", author = {$_SESSION['id']} WHERE OD_ID = {$id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$painting_workers = $Name;

		if ($tariff > 0) {
			$query = "
				INSERT INTO PayLog(OD_ID, USR_ID, Pay, Comment, author)
				VALUES ({$id}, {$usr_id}, {$tariff}, 'Лакировка', {$_SESSION['id']})
			";
			mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		}
	}


	if( $patina_usr_id > 0 ) {
		// Узнаем имя лакировщика
		$query = "SELECT USR_ShortName({$patina_usr_id}) Name";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$Name = mysqli_result($res,0,'Name');

		$query = "UPDATE OrdersData SET patina_USR_ID = {$patina_usr_id}, patina_tariff = ".($patina_tariff > 0 ? $patina_tariff : "NULL").", author = {$_SESSION['id']} WHERE OD_ID = {$id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$painting_workers .= " + {$Name}";

		if ($patina_tariff > 0) {
			$query = "
				INSERT INTO PayLog(OD_ID, USR_ID, Pay, Comment, author)
				VALUES ({$id}, {$patina_usr_id}, {$patina_tariff}, 'Патинирование', {$_SESSION['id']})
			";
			mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		}
	}

	if ($painting_workers) {
		echo "$('.main_table tr[id=\"ord{$id}\"] .painting_workers').text('{$painting_workers}');";
	}

	break;
///////////////////////////////////////////////////////////////////

// Вычитание из баланса при отмене лакировки (если набор был разделён)
case "painting_workers_reject":

	$id = $_GET["od_id"];
	$usr_id = $_GET["usr_id"];
	$tariff = $_GET["tariff"];
	$patina_usr_id = $_GET["patina_usr_id"];
	$patina_tariff = $_GET["patina_tariff"];

	if( $usr_id > 0 ) {
		$query = "UPDATE OrdersData SET USR_ID = {$usr_id}, tariff = ".($tariff > 0 ? $tariff : "NULL").", author = {$_SESSION['id']} WHERE OD_ID = {$id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

		if ($tariff > 0) {
			$query = "
				INSERT INTO PayLog(OD_ID, USR_ID, Pay, Comment, author)
				VALUES ({$id}, {$usr_id}, -1 * {$tariff}, 'Лакировка', {$_SESSION['id']})
			";
			mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		}
	}


	if( $patina_usr_id > 0 ) {
		$query = "UPDATE OrdersData SET patina_USR_ID = {$patina_usr_id}, patina_tariff = ".($patina_tariff > 0 ? $patina_tariff : "NULL").", author = {$_SESSION['id']} WHERE OD_ID = {$id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

		if ($patina_tariff > 0) {
			$query = "
				INSERT INTO PayLog(OD_ID, USR_ID, Pay, Comment, author)
				VALUES ({$id}, {$patina_usr_id}, -1 * {$patina_tariff}, 'Патинирование', {$_SESSION['id']})
			";
			mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		}
	}

	break;
///////////////////////////////////////////////////////////////////

// Смена статуса принятия набора
case "confirmed":

	$id = $_GET["od_id"];
	$val = $_GET["val"];
	$val = ($val == 0) ? 1 : 0;

	// Обновляем статус принятия набора
	$query = "UPDATE OrdersData SET confirmed = {$val}, author = {$_SESSION['id']} WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

	// Получаем статус принятия набора из базы
	$query = "SELECT confirmed FROM OrdersData WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$val = mysqli_result($res,0,'confirmed');

	if( $val == 1) {
		$class = 'confirmed';
		$status = 'Принят в работу';
		//echo "$('.main_table tr[id=\"ord{$id}\"] td.td_step').addClass('step_confirmed');";
		echo "$('.main_table .td_step').addClass('step_confirmed');";
		echo "$('#order_in_work_label').show('fast');";
	}
	else {
		$class = 'not_confirmed';
		$status = 'Не принят в работу';
		//echo "$('.main_table tr[id=\"ord{$id}\"] td.td_step').removeClass('step_confirmed');";
		echo "$('.main_table .td_step').removeClass('step_confirmed');";
		echo "$('#order_in_work_label').hide('fast');";
	}

	echo "$('.main_table tr[id=\"ord{$id}\"] td.edit_confirmed').removeClass('confirmed not_confirmed');";
	echo "$('.main_table tr[id=\"ord{$id}\"] td.edit_confirmed').addClass('{$class}');";
	echo "$('.main_table tr[id=\"ord{$id}\"] td.edit_confirmed').attr('val', '{$val}');";
	echo "noty({timeout: 3000, text: 'Статус набора изменен на <b>{$status}</b>', type: 'success'});";
	break;
///////////////////////////////////////////////////////////////////

// Смена статуса получения клиентом набора
case "taken":

	$id = $_GET["od_id"];
	$val = $_GET["val"];
	$val = ($val == 0) ? 1 : 0;

	// Обновляем статус получения набора
	$query = "UPDATE OrdersData SET taken = {$val}, author = {$_SESSION['id']} WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

	// Получаем статус получения набора из базы
	$query = "SELECT taken FROM OrdersData WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$val = mysqli_result($res,0,'taken');

	if( $val == 1) {
		$class = 'confirmed';
		$status = 'Клиент забрал набор';
	}
	else {
		$class = 'not_confirmed';
		$status = 'Клиент НЕ забрал набор';
	}

	echo "$('.main_table tr[id=\"ord{$id}\"] .taken_confirmed').removeClass('confirmed not_confirmed');";
	echo "$('.main_table tr[id=\"ord{$id}\"] .taken_confirmed').addClass('{$class}');";
	echo "$('.main_table tr[id=\"ord{$id}\"] .taken_confirmed').attr('val', '{$val}');";
	echo "noty({timeout: 3000, text: 'Статус набора изменен на <b>{$status}</b>', type: 'success'});";
	break;
///////////////////////////////////////////////////////////////////

// Смена статуса прочитанного собщения в наборе
case "read_message":

	$om_id = $_GET["om_id"];

	// Узнаем есть ли право менять статус сообщения
	$query = "
		SELECT 1
		FROM OrdersMessage OM
		JOIN OrdersData OD ON OD.OD_ID = OM.OD_ID
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		WHERE OM.OM_ID = {$om_id} AND IFNULL(SH.CT_ID, 0) IN ({$USR_cities})
		".(in_array('order_add_free', $Rights) ? "AND 0" : "")."
		".($USR_Shop ? "AND (SH.SH_ID IN ({$USR_Shop}) OR OD.SH_ID IS NULL)" : "")."
		".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR OD.SH_ID IS NULL)" : "")."
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

	if (mysqli_num_rows($res)) {
		$val = $_GET["val"];
		$val = ($val == 0) ? 1 : 0;

		// Обновляем статус сообщения
		if( $val == 1 ) {
			$query = "UPDATE OrdersMessage SET read_user = {$_SESSION['id']}, read_time = NOW() WHERE OM_ID = {$om_id}";
		}
		else {
			$query = "UPDATE OrdersMessage SET read_user = NULL, read_time = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE OM_ID = {$om_id}";
		}
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

		// Получаем статус сообщения
		$query = "SELECT IFNULL(USR_Name(OM.read_user), 'СИСТЕМА') read_user
						,Friendly_date(OM.read_time) read_date
						,DATE_FORMAT(OM.read_time, '%H:%i') read_time
						,IF(OM.read_time > NOW(), 0, 1) is_read
						,USR_Icon(OM.read_user) read_user_icon
					FROM OrdersMessage OM
					WHERE OM.OM_ID = {$om_id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$read_user = mysqli_result($res,0,'read_user');
		$read_date = mysqli_result($res,0,'read_date');
		$read_time = mysqli_result($res,0,'read_time');
		$is_read = mysqli_result($res,0,'is_read');
		$read_user_icon = mysqli_result($res,0,'read_user_icon');

		if( $is_read ) {
			$html = "<i class='fa fa-envelope fa-2x' aria-hidden='true' style='color: green;' title='Прочитано: {$read_user} {$read_date} {$read_time}'>";
			$status = "ПРОЧИТАННОЕ";
			echo "$('#wfm{$om_id}').addClass('wf_is_read');";
			echo "$('#wfm{$om_id} td:last-child').html('{$read_user_icon}');";
		}
		else {
			$html = "<i class='fa fa-envelope fa-2x' aria-hidden='true' style='color: red;'>";
			$status = "НЕ ПРОЧИТАННОЕ";
			echo "$('#wfm{$om_id}').removeClass('wf_is_read');";
			echo "$('#wfm{$om_id} td:last-child').html('');";
		}
		$html = addslashes($html);
		echo "$('#msg{$om_id}').html('{$html}');";
		echo "$('#msg{$om_id}').attr('val', '{$val}');";

		echo "noty({timeout: 3000, text: 'Сообщение отмечено как {$status}', type: 'success'});";
	}
	else {
		echo "noty({timeout: 3000, text: 'Вы не можете менять статус сообщений в этом наборе.', type: 'alert'});";
	}

	break;
///////////////////////////////////////////////////////////////////

// Помечаем X в главной таблице
case "Xlabel":

	session_start();
	$od_id = $_GET["od_id"];
	$val = $_GET["val"];
	if ($val == 1) {
		$_SESSION["X_".$od_id] = $val;
	}
	else {
		unset($_SESSION["X_".$od_id]);
	}
	break;
///////////////////////////////////////////////////////////////////

// Редактируем название материала
case "materials":
	$val = convert_str($_GET["val"]);
	$val = mysqli_real_escape_string($mysqli, $val);
	$mtid = $_GET["mtid"]; // id материала
	$shid = $_GET["shid"]; // Поставщик
	$removed = $_GET["removed"] == 'true' ? 1 : 0;

	// Узнаем старое название
	$query = "SELECT Material FROM Materials WHERE MT_ID = {$mtid}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$oldval = mysqli_result($res,0,'Material');

	if ($val != $oldval) {
		echo "$('.mt{$mtid}').hide('fast');";
		// Узнаем есть ли материал с новым названием
		$query = "SELECT MT_ID FROM Materials WHERE SH_ID = {$shid} AND Material LIKE '{$val}'";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

		if( mysqli_num_rows($res) ) { // Если в списке материалов уже есть такое название
			$new_mtid = mysqli_result($res,0,'MT_ID');

			// У старого материала сохраняем ссылку на новый материал PMT_ID
			$query = "UPDATE Materials SET PMT_ID = {$new_mtid} WHERE MT_ID = {$mtid}";
			mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			// Если старый материал был чьим то родителем, то заменяем у его потомков родителя на нового
			$query = "UPDATE Materials SET PMT_ID = {$new_mtid} WHERE PMT_ID = {$mtid}";
			mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

			// Меняем на экране старый id материала на новый
			echo "$('.mt{$mtid}').addClass('mt{$new_mtid}');";
			echo "$('.mt{$mtid}').attr('mtid', '{$new_mtid}');";

			// Меняем текущий идентификатор материала
			$mtid = $new_mtid;
		}
		else { // Обновляем название
			$query = "UPDATE Materials SET Material = '{$val}' WHERE MT_ID = {$mtid}";
			mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		}
		// Узнаем название поставщика
		$query = "SELECT Shipper FROM Shippers WHERE SH_ID = {$shid}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$shipper = mysqli_result($res,0,'Shipper');

		echo "noty({timeout: 3000, text: 'Название материала изменено на: {$val} <b>{$shipper}</b>', type: 'success'});";
		echo "$('.mt{$mtid}').html('{$val} <b>{$shipper}</b>');";
	}
	// Сохранение пометки о выведении в том числе для потомков
	$query = "SELECT removed FROM Materials WHERE MT_ID = {$mtid}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$oldremoved = mysqli_result($res,0,'removed');
	if( $removed != $oldremoved ) {
		echo "$('.mt{$mtid}').hide('fast');";
		$query = "UPDATE Materials SET removed = {$removed} WHERE MT_ID = {$mtid} OR PMT_ID = {$mtid}";
		mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		if( $removed ) {
			echo "noty({timeout: 3000, text: 'Материал помечен как выведенный.', type: 'success'});";
			echo "$('.mt{$mtid}').addClass('removed');";
		}
		else {
			echo "noty({timeout: 3000, text: 'Снята отметка о выведении.', type: 'success'});";
			echo "$('.mt{$mtid}').removeClass('removed');";
		}
	}
	echo "$('.mt{$mtid}').show('fast');";

	break;
///////////////////////////////////////////////////////////////////

// Форма отгрузки
case "shipment":
		$CT_ID = $_GET["CT_ID"] ? $_GET["CT_ID"] : 0;

		// Проверяем права на отгрузку набора
		if( !in_array('order_ready', $Rights) ) {
			echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
		}
		else {
			$html = "";
			$query = "
				SELECT SH.SH_ID, SH.Shop
				FROM Shops SH
				WHERE SH.CT_ID = {$CT_ID}
			";
			$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			while( $row = mysqli_fetch_array($res) ) {
				$html .= "<label for='shop{$row["SH_ID"]}'>{$row["Shop"]}</label><input type='checkbox' id='shop{$row["SH_ID"]}' class='button_shops'>";
			}
			$html .= "<br><br>";

			$query = "
				SELECT OD.OD_ID
					,OD.Code
					,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
					,IFNULL(OD.ClientName, '') ClientName
					,KA.Naimenovanie
					,IFNULL(DATE_FORMAT(OD.StartDate, '%d.%m'), '...') StartDate
					,IFNULL(DATE_FORMAT(OD.EndDate, '%d.%m'), '...') EndDate
					,Color(OD.CL_ID) Color
					,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
					,IF(OD.SHP_ID IS NULL, '', 'checked') checked
					,OD.SH_ID
					,SH.Shop
					,OD.confirmed
					,REPLACE(OD.Comment, '\r\n', '<br>') Comment
				FROM OrdersData OD
				LEFT JOIN Kontragenty KA ON KA.KA_ID = OD.KA_ID
				JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.CT_ID = {$CT_ID}
				WHERE OD.DelDate IS NULL
			";
			if( $_GET["shpid"] ) {
				$query .= " AND ((OD.ReadyDate IS NULL AND OD.SHP_ID IS NULL) OR OD.SHP_ID = {$_GET["shpid"]})";
			}
			else {
				$query .= " AND OD.ReadyDate IS NULL AND OD.SHP_ID IS NULL";
			}
			$query .= " GROUP BY OD.OD_ID";
			$query .= " ORDER BY OD.AddDate, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID";

			$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			$html .= "<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>";
			$html .= "<table class='main_table' id='to_shipment'><thead><tr>";
			$html .= "<th width='75'>Код набора</th>";
			$html .= "<th width='20%'>Клиент [Продажа]-[Сдача]</th>";
			$html .= "<th width='10%'>Подразделение</th>";
			$html .= "<th width='30%'>Набор</th>";
			$html .= "<th width='20%'>Материал</th>";
			$html .= "<th width='20%'>Цвет</th>";
			$html .= "<th width='100'>Этапы</th>";
			$html .= "<th width='40'>Принят</th>";
			$html .= "<th width='20%'>Примечание</th>";
			$html .= "</tr></thead><tbody>";
			while( $row = mysqli_fetch_array($res) ) {
				// Получаем содержимое набора
				$query = "
					SELECT ODD.ODD_ID
						,ODD.Amount
						,Zakaz(ODD.ODD_ID) zakaz
						,REPLACE(ODD.Comment, '\r\n', ' ') Comment
						,DATEDIFF(ODD.arrival_date, NOW()) outdate
						,ODD.IsExist
						,Friendly_date(ODD.order_date) order_date
						,Friendly_date(ODD.arrival_date) arrival_date
						,IFNULL(MT.Material, '') Material
						,IF(MT.removed=1, 'removed', '') removed
						,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
						,Steps_button(ODD.ODD_ID, 0) Steps
					FROM OrdersDataDetail ODD
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
					WHERE ODD.OD_ID = {$row["OD_ID"]}
					ORDER BY PTID DESC, ODD.ODD_ID
				";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				// Формируем подробности набора
				$zakaz = '';
				$material = '';
				$color = '';
				$steps = '';
				while( $subrow = mysqli_fetch_array($subres) ) {
					// Если есть примечание
					if ($subrow["Comment"]) {
						$zakaz .= "<b class='material'><a title='{$subrow["Comment"]}'><i class='fa fa-comment'></i> <b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
					}
					else {
						$zakaz .= "<b class='material'><a><b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
					}

					if ($subrow["IsExist"] == "0") {
						$color = "bg-red";
					}
					elseif ($subrow["IsExist"] == "1") {
						$color = "bg-yellow' html='Заказано:&nbsp;&nbsp;&nbsp;&nbsp;<b>{$subrow["order_date"]}</b><br>Ожидается:&nbsp;<b>{$subrow["arrival_date"]}</b>";
					}
					elseif ($subrow["IsExist"] == "2") {
						$color = "bg-green";
					}
					else {
						$color = "bg-gray";
					}
					$material .= "<span class='wr_mt'>".(($subrow["outdate"] <= 0 and $subrow["IsExist"] == 1) ? "<i class='fas fa-exclamation-triangle' style='color: #E74C3C;' title='{$subrow["outdate"]} дн.'></i>" : "")."<span class='{$subrow["removed"]} material {$color}'>{$subrow["Material"]}</span></span><br>";

					$steps .= "<a>{$subrow["Steps"]}</a><br>";
				}

				$html .= "<tr class='shop{$row["SH_ID"]}' style='display: none;'>";
				$html .= "<td><input {$row["checked"]} type='checkbox' name='ord_sh[]' id='ord_sh{$row["OD_ID"]}' class='chbox hide' value='{$row["OD_ID"]}'>";
				$html .= "<label for='ord_sh{$row["OD_ID"]}'".($row["checked"] == 'checked' ? "style='color: red;'" : "")."><b class='code'>{$row["Code"]}</b></label><br><span>{$row["AddDate"]}</span></td>";
				$html .= "<td><span class='nowrap'>".($row["Naimenovanie"] ? "<n class='ul'>{$row["Naimenovanie"]}</n><br>" : "")."{$row["ClientName"]}<br>[{$row["StartDate"]}]-[{$row["EndDate"]}]</span></td>";
				$html .= "<td><span class='nowrap'>{$row["Shop"]}</span></td>";
				$html .= "<td><span class='nowrap'>{$zakaz}</span></td>";
				switch ($row["IsPainting"]) {
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
				$html .= "<td><span class='nowrap'>{$material}</span></td>";
				$html .= "<td class='{$class}'>{$row["Color"]}</td>";
				$html .= "<td><span class='nowrap material'>{$steps}</span></td>";
					// Если набор принят
					if( $row["confirmed"] == 1 ) {
						$class = 'confirmed';
					}
					else {
						$class = 'not_confirmed';
					}
				$html .= "<td class='{$class}'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";
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
				$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
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

// Список наборов для формы накладной
case "invoice":
		$KA_ID = $_GET["KA_ID"] ? $_GET["KA_ID"] : 0;
		$CT_ID = $_GET["CT_ID"] ? $_GET["CT_ID"] : 0;
		$shipping_year = $_GET["shipping_year"]; // Если не пусто, то это накладная на возврат

		// Проверяем права на акты сверок
		if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
			echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
		}
		else {
			// Список подразделений, связанных с оптовиком
			$query = "
				SELECT GROUP_CONCAT(SH_ID) SH_IDs FROM Shops WHERE KA_ID = {$KA_ID}
			";
			$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			$row = mysqli_fetch_array($res);
			$SH_IDs = $row["SH_IDs"];

			$query = "
				SELECT OD.OD_ID
					,OD.Code
					,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
					,IFNULL(OD.ClientName, '') ClientName
					,KA.Naimenovanie
					,IFNULL(DATE_FORMAT(OD.StartDate, '%d.%m'), '...') StartDate
					,IFNULL(DATE_FORMAT(OD.EndDate, '%d.%m'), '...') EndDate
					,Color(OD.CL_ID) Color
					,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
					,OD.SH_ID
					,SH.Shop
					,REPLACE(OD.Comment, '\r\n', '<br>') Comment
				FROM OrdersData OD
				LEFT JOIN Kontragenty KA ON KA.KA_ID = OD.KA_ID
				JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.CT_ID = {$CT_ID}
				LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID AND PFI.del = 0 AND PFI.rtrn != 1
				WHERE OD.DelDate IS NULL
					AND OD.ReadyDate IS NOT NULL
					AND Payment_sum(OD.OD_ID) = 0
					AND OD.is_lock = 0
					".($shipping_year ? "AND PFI.PFI_ID IS NOT NULL AND YEAR(OD.ReadyDate) = {$shipping_year}" : "AND PFI.PFI_ID IS NULL")."
					# Для оптовика показываем его подразделения, для розницы показываем розничные наборы связанные с KA_ID с датой продажи из открытого месяца
					".($SH_IDs ? "AND SH.SH_ID IN ({$SH_IDs})" : "AND SH.retail = 1 AND IF(SH.SH_ID = 36, 155, OD.KA_ID) = {$KA_ID} AND OD.StartDate IS NOT NULL AND OD.is_lock = 0")."
					# Для продавца только его салоны
					".($USR_Shop ? "AND SH.SH_ID IN ({$USR_Shop})" : "")."
				GROUP BY OD.OD_ID
				ORDER BY OD.ReadyDate ".($shipping_year ? "DESC" : "ASC")."
			";

			$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

			// Если вызван аяксом
			if ($_GET['from_js']) {

				$html = "<font color='#911'><b>Критерии для отображения:</b></font>";

				if ($shipping_year) { // Если возвратная накладная
					$html .= "<ul>";
					$html .= "<li>Набор не удалён;</li>";
					$html .= "<li>Есть товарная накладная с этим набором;</li>";
					$html .= "</ul>";
				}
				else {
					if ($SH_IDs) { // Если оптовик
						$html .= "<ul>";
						$html .= "<li>Набор не удалён;</li>";
						$html .= "<li>Нет товарной накладной с этим набором;</li>";
						$html .= "</ul>";
					}
					else { // Розница
						$html .= "<ul>";
						$html .= "<li>Набор не удалён;</li>";
						$html .= "<li>Есть счёт с этим набором;</li>";
						$html .= "<li>Нет товарной накладной с этим набором;</li>";
						$html .= "</ul>";
					}
				}


				$html .= "<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>";
				$html .= "<table class='main_table' id='to_invoice'><thead><tr>";
				$html .= "<th width='75'>Код набора</th>";
				$html .= "<th width='20%'>Клиент [Продажа]-[Сдача]</th>";
				$html .= "<th width='10%'>Подразделение</th>";
				$html .= "<th width='70'>Цена за шт.</th>";
				$html .= "<th width='70'>Скидка за шт.</th>";
				$html .= "<th width='30%'>Набор</th>";
				$html .= "<th width='20%'>Материал</th>";
				$html .= "<th width='20%'>Цвет</th>";
				$html .= "<th width='20%'>Примечание</th>";
				$html .= "</tr></thead><tbody>";
				while( $row = mysqli_fetch_array($res) ) {
					// Получаем содержимое набора
					$query = "
						SELECT ODD.ODD_ID
							,ODD.Amount
							,Zakaz(ODD.ODD_ID) zakaz
							,REPLACE(ODD.Comment, '\r\n', ' ') Comment
							,DATEDIFF(ODD.arrival_date, NOW()) outdate
							,ODD.IsExist
							,Friendly_date(ODD.order_date) order_date
							,Friendly_date(ODD.arrival_date) arrival_date
							,IFNULL(MT.Material, '') Material
							,IF(MT.removed=1, 'removed', '') removed
							,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
							,IFNULL(ODD.min_price, 0) min_price
							,IFNULL(ODD.Price, ".($shipping_year ? "0" : "''").") price
							,IFNULL((ODD.Price - IFNULL(ODD.discount, 0)), ".($shipping_year ? "0" : "''").") opt_price
							,IFNULL(ODD.discount, ".($shipping_year ? "0" : "''").") discount
							,IF(ODD.opt_price IS NOT NULL, (ODD.Price - IFNULL(ODD.discount, 0) - ODD.opt_price), '') opt_discount
						FROM OrdersDataDetail ODD
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						WHERE ODD.OD_ID = {$row["OD_ID"]}
						ORDER BY PTID DESC, ODD.ODD_ID
					";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

					// Формируем подробности набора
					$zakaz = '';
					$material = '';
					$color = '';
					$price = '';
					$discount = '';
					while( $subrow = mysqli_fetch_array($subres) ) {
						// Если есть примечание
						if ($subrow["Comment"]) {
							$zakaz .= "<b class='material'><a title='{$subrow["Comment"]}'><i class='fa fa-comment'></i> <b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
						}
						else {
							$zakaz .= "<b class='material'><a><b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
						}

						if ($subrow["IsExist"] == "0") {
							$color = "bg-red";
						}
						elseif ($subrow["IsExist"] == "1") {
							$color = "bg-yellow' html='Заказано:&nbsp;&nbsp;&nbsp;&nbsp;<b>{$subrow["order_date"]}</b><br>Ожидается:&nbsp;<b>{$subrow["arrival_date"]}</b>";
						}
						elseif ($subrow["IsExist"] == "2") {
							$color = "bg-green";
						}
						else {
							$color = "bg-gray";
						}
						$material .= "<span class='wr_mt'>".(($subrow["outdate"] <= 0 and $subrow["IsExist"] == 1) ? "<i class='fas fa-exclamation-triangle' style='color: #E74C3C;' title='{$subrow["outdate"]} дн.'></i>" : "")."<span class='{$subrow["removed"]} material {$color}'>{$subrow["Material"]}</span></span><br>";

						// Исключение для клена
						if ($row["SH_ID"] == 36) {
							$price .= "<input type='hidden' name='odid[]' value='{$row["OD_ID"]}'><input type='hidden' name='odd_id[]' value='{$subrow["ODD_ID"]}'><input ".($shipping_year ? "readonly" : "")." required type='number' min='0' name='price[]' value='{$subrow["opt_price"]}' amount='{$subrow["Amount"]}'><br>";
							$discount .= "<input ".($shipping_year ? "readonly" : "")." type='number' min='0' name='discount[]' value='{$subrow["opt_discount"]}' amount='{$subrow["Amount"]}'><br>";
						}
						else {
							$price .= "<input type='hidden' name='odid[]' value='{$row["OD_ID"]}'><input type='hidden' name='odd_id[]' value='{$subrow["ODD_ID"]}'><input ".($shipping_year ? "readonly" : "")." required type='number' min='{$subrow["min_price"]}' name='price[]' value='{$subrow["price"]}' amount='{$subrow["Amount"]}' ".(($subrow["min_price"] > 0) ? "title='Вычисленная стоимость по прайсу: {$subrow["min_price"]}'" : "")."><br>";
							$discount .= "<input ".($shipping_year ? "readonly" : "")." type='number' min='0' name='discount[]' value='{$subrow["discount"]}' amount='{$subrow["Amount"]}'><br>";
						}
					}

					$html .= "<tr class='shop{$row["SH_ID"]}'>";
					$html .= "<td><input type='checkbox' name='ord[]' id='ord_{$row["OD_ID"]}' class='chbox' value='{$row["OD_ID"]}'>";
					$html .= "<label for='ord_{$row["OD_ID"]}'><b class='code'>{$row["Code"]}</b></label><br><span>{$row["AddDate"]}</span></td>";
					$html .= "<td><span class='nowrap'>".($row["Naimenovanie"] ? "<n class='ul'>{$row["Naimenovanie"]}</n><br>" : "")."{$row["ClientName"]}<br>[{$row["StartDate"]}]-[{$row["EndDate"]}]</span></td>";
					$html .= "<td><span class='nowrap'>{$row["Shop"]}</span></td>";
					$html .= "<td>{$price}</td>";
					$html .= "<td>{$discount}</td>";
					$html .= "<td><span class='nowrap'>{$zakaz}</span></td>";
					switch ($row["IsPainting"]) {
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
					$html .= "<td><span class='nowrap'>{$material}</span></td>";
					$html .= "<td class='{$class}'>{$row["Color"]}</td>";
					$html .= "<td>{$row["Comment"]}</td>";
					$html .= "</tr>";
				}
				$html .= "</tbody></table>";
				$html .= "<p><input type='checkbox' id='selectallbottom'><label for='selectallbottom'>Выбрать все</label></p>";
				$html = addslashes($html);
				echo "$('#orders_to_invoice').html('{$html}');";
				echo "$('#orders_to_invoice input[type=\"number\"], #orders_to_invoice input[type=\"hidden\"]').attr('disabled', true);";
				echo "$('#orders_to_invoice input[type=\"number\"]').hide();";
				echo "$('#orders_to_invoice input[name=\"price[]\"]').attr('placeholder', 'цена');";
				echo "$('#orders_to_invoice input[name=\"discount[]\"]').attr('placeholder', 'скидка');";
				echo "invoice_total();";
			}
			else {
				while( $row = mysqli_fetch_array($res) ) {
					$orders_to_invoice[] = $row["OD_ID"];
				}
			}
		}

	break;
///////////////////////////////////////////////////////////////////

// Список наборов для формы счета
case "bill":
		$KA_ID = $_GET["KA_ID"] ? $_GET["KA_ID"] : 0;
		$CT_ID = $_GET["CT_ID"] ? $_GET["CT_ID"] : 0;

		// Проверяем права на акты сверок
		if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) ) {
			echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
		}
		else {
			// Список подразделений, связанных с оптовиком
			$query = "
				SELECT GROUP_CONCAT(SH_ID) SH_IDs FROM Shops WHERE KA_ID = {$KA_ID}
			";
			$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			$row = mysqli_fetch_array($res);
			$SH_IDs = $row["SH_IDs"];

			$query = "
				SELECT OD.OD_ID
					,OD.Code
					,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
					,IFNULL(OD.ClientName, '') ClientName
					,KA.Naimenovanie
					,IFNULL(DATE_FORMAT(OD.StartDate, '%d.%m'), '...') StartDate
					,IFNULL(DATE_FORMAT(OD.EndDate, '%d.%m'), '...') EndDate
					,Color(OD.CL_ID) Color
					,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
					,OD.SH_ID
					,SH.Shop
					,REPLACE(OD.Comment, '\r\n', '<br>') Comment
				FROM OrdersData OD
				LEFT JOIN Kontragenty KA ON KA.KA_ID = OD.KA_ID
				JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID AND PFI.del = 0 AND PFI.rtrn != 1
				WHERE OD.DelDate IS NULL
					AND Payment_sum(OD.OD_ID) = 0
					AND PFI.PFI_ID IS NULL
					# Для оптовика показываем его подразделения, для розницы показываем розничные наборы в регионе с датой продажи из открытого месяца
					".($SH_IDs ? "AND SH.SH_ID IN ({$SH_IDs})" : "AND SH.retail = 1 AND SH.CT_ID = {$CT_ID} AND OD.StartDate IS NOT NULL AND OD.is_lock = 0")."
					# Для продавца только его салоны
					".($USR_Shop ? "AND SH.SH_ID IN ({$USR_Shop})" : "")."
					# Есть стоимость
					#AND Ord_price(OD.OD_ID) - Ord_discount(OD.OD_ID) > 0
					AND OD.SH_ID != 36 #Исключение для Клёна
				GROUP BY OD.OD_ID
				ORDER BY SH.Shop, OD.StartDate DESC, OD.OD_ID DESC
			";

			$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

			// Если вызван аяксом
			if ($_GET['from_js']) {

				$html = "<font color='#911'><b>Критерии для отображения:</b></font>";

				if ($SH_IDs) { // Если оптовик
					$html .= "<ul>";
					$html .= "<li>Набор не удалён;</li>";
					$html .= "<li>Набор связан с каким либо подразделением (не в \"Свободных\");</li>";
					$html .= "<li>Нет товарной накладной с этим набором;</li>";
					$html .= "</ul>";
				}
				else { // Розница
					$html .= "<ul>";
					$html .= "<li>Набор не удалён;</li>";
					$html .= "<li>Нет товарной накладной с этим набором;</li>";
					$html .= "<li>У набора указана дата заключения договора и месяц в реализации открыт;</li>";
					$html .= "<li>За набором не числится оплата;</li>";
					$html .= "<li>У набора не нулевая стоимость;</li>";
					$html .= "</ul>";
				}

				$html .= "<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>";
				$html .= "<table class='main_table' id='to_invoice'><thead><tr>";
				$html .= "<th width='75'>Код набора</th>";
				$html .= "<th width='20%'>Клиент [Продажа]-[Сдача]</th>";
				$html .= "<th width='10%'>Подразделение</th>";
				$html .= "<th width='70'>Цена за шт.</th>";
				$html .= "<th width='70'>Скидка за шт.</th>";
				$html .= "<th width='30%'>Набор</th>";
				$html .= "<th width='20%'>Материал</th>";
				$html .= "<th width='20%'>Цвет</th>";
				$html .= "<th width='20%'>Примечание</th>";
				$html .= "</tr></thead><tbody>";

				while( $row = mysqli_fetch_array($res) ) {
					// Получаем содержимое набора
					$query = "
						SELECT ODD.ODD_ID
							,ODD.Amount
							,Zakaz(ODD.ODD_ID) zakaz
							,REPLACE(ODD.Comment, '\r\n', ' ') Comment
							,DATEDIFF(ODD.arrival_date, NOW()) outdate
							,ODD.IsExist
							,Friendly_date(ODD.order_date) order_date
							,Friendly_date(ODD.arrival_date) arrival_date
							,IFNULL(MT.Material, '') Material
							,IF(MT.removed=1, 'removed', '') removed
							,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
							,IFNULL(ODD.min_price, 0) min_price
							,IFNULL(ODD.Price, '') price
							,IFNULL((ODD.Price - IFNULL(ODD.discount, 0)), '') opt_price
							,IFNULL(ODD.discount, '') discount
							,IF(ODD.opt_price IS NOT NULL, (ODD.Price - IFNULL(ODD.discount, 0) - ODD.opt_price), '') opt_discount
						FROM OrdersDataDetail ODD
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						WHERE ODD.OD_ID = {$row["OD_ID"]}
						ORDER BY PTID DESC, ODD.ODD_ID
					";
					$subres = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

					// Формируем подробности набора
					$zakaz = '';
					$material = '';
					$color = '';
					$price = '';
					$discount = '';
					while( $subrow = mysqli_fetch_array($subres) ) {
						// Если есть примечание
						if ($subrow["Comment"]) {
							$zakaz .= "<b class='material'><a title='{$subrow["Comment"]}'><i class='fa fa-comment'></i> <b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
						}
						else {
							$zakaz .= "<b class='material'><a><b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
						}

						if ($subrow["IsExist"] == "0") {
							$color = "bg-red";
						}
						elseif ($subrow["IsExist"] == "1") {
							$color = "bg-yellow' html='Заказано:&nbsp;&nbsp;&nbsp;&nbsp;<b>{$subrow["order_date"]}</b><br>Ожидается:&nbsp;<b>{$subrow["arrival_date"]}</b>";
						}
						elseif ($subrow["IsExist"] == "2") {
							$color = "bg-green";
						}
						else {
							$color = "bg-gray";
						}
						$material .= "<span class='wr_mt'>".(($subrow["outdate"] <= 0 and $subrow["IsExist"] == 1) ? "<i class='fas fa-exclamation-triangle' style='color: #E74C3C;' title='{$subrow["outdate"]} дн.'></i>" : "")."<span class='{$subrow["removed"]} material {$color}'>{$subrow["Material"]}</span></span><br>";

						$price .= "<input type='hidden' name='odid[]' value='{$row["OD_ID"]}'><input type='hidden' name='odd_id[]' value='{$subrow["ODD_ID"]}'><input ".($shipping_year ? "readonly" : "")." required type='number' min='{$subrow["min_price"]}' name='price[]' value='{$subrow["price"]}' amount='{$subrow["Amount"]}' ".(($subrow["min_price"] > 0) ? "title='Вычисленная стоимость по прайсу: {$subrow["min_price"]}'" : "")."><br>";
						$discount .= "<input ".($shipping_year ? "readonly" : "")." type='number' min='0' name='discount[]' value='{$subrow["discount"]}' amount='{$subrow["Amount"]}'><br>";
					}

					$html .= "<tr class='shop{$row["SH_ID"]}'>";
					$html .= "<td><input type='checkbox' name='ord[]' id='ord_{$row["OD_ID"]}' class='chbox' value='{$row["OD_ID"]}'>";
					$html .= "<label for='ord_{$row["OD_ID"]}'><b class='code'>{$row["Code"]}</b></label><br><span>{$row["AddDate"]}</span></td>";
					$html .= "<td><span class='nowrap'>".($row["Naimenovanie"] ? "<n class='ul'>{$row["Naimenovanie"]}</n><br>" : "")."{$row["ClientName"]}<br>[{$row["StartDate"]}]-[{$row["EndDate"]}]</span></td>";
					$html .= "<td><span class='nowrap'>{$row["Shop"]}</span></td>";
					$html .= "<td>{$price}</td>";
					$html .= "<td>{$discount}</td>";
					$html .= "<td><span class='nowrap'>{$zakaz}</span></td>";
					switch ($row["IsPainting"]) {
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
					$html .= "<td><span class='nowrap'>{$material}</span></td>";
					$html .= "<td class='{$class}'>{$row["Color"]}</td>";
					$html .= "<td>{$row["Comment"]}</td>";
					$html .= "</tr>";
				}
				$html .= "</tbody></table>";
				$html .= "<p><input type='checkbox' id='selectallbottom'><label for='selectallbottom'>Выбрать все</label></p>";
				$html = addslashes($html);
				echo "$('#orders_to_bill').html('{$html}');";
				echo "$('#orders_to_bill input[type=\"number\"], #orders_to_bill input[type=\"hidden\"]').attr('disabled', true);";
				echo "$('#orders_to_bill input[type=\"number\"]').hide();";
				echo "$('#orders_to_bill input[name=\"price[]\"]').attr('placeholder', 'цена');";
				echo "$('#orders_to_bill input[name=\"discount[]\"]').attr('placeholder', 'скидка');";
				echo "bill_total();";
			}
			else {
				while( $row = mysqli_fetch_array($res) ) {
					$orders_to_bill[] = $row["OD_ID"];
				}
			}
		}

	break;
///////////////////////////////////////////////////////////////////

// Инкассация из облака ЭВОТОР
case "cashe_outcome":
	$CT_ID = $_GET["CT_ID"];

	// Узнаем кассы, доступные пользователю в выбранном регионе
	$query = "
		SELECT CB.CB_ID
			,CB.name
			,deviceUuid
			,gtCloseDate_out
		FROM CashBox CB
		WHERE CB.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID IN ({$USR_Shop})" : "SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID} AND CT_ID = {$USR_City}").")
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	while( $row = mysqli_fetch_array($res) ) {
		$CB_ID = $row["CB_ID"];
		$CashBox = $row["name"];
		$deviceUuid = $row["deviceUuid"];
		$gtCloseDate_out = $row["gtCloseDate_out"];

		// Если у кассы есть deviceUuid и gtCloseDate_out - пробуем получить документы из облака ЭВОТОР
		if( $deviceUuid and $gtCloseDate_out ) {
			// Узнаём storeUuid и X-Authorization магазина
			$query = "
				SELECT `storeUuid`, `X-Authorization`
				FROM Rekvizity
				WHERE R_ID = (SELECT R_ID FROM CashBox WHERE CB_ID = {$CB_ID})
			";
			$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			$storeUuid = mysqli_result($res,0,'storeUuid');
			$Authorization = mysqli_result($res,0,'X-Authorization');

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/'.$storeUuid.'/documents?deviceUuid='.$deviceUuid.'&gtCloseDate='.$gtCloseDate_out.'&types=CASH_OUTCOME');
			curl_setopt($curl, CURLOPT_HTTPHEADER, array(
				'X-Authorization: '.$Authorization
			));
			$out = curl_exec($curl);
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Получаем HTTP-код
			curl_close($curl);
echo "noty({timeout: 10000, text: 'http_code: ".$http_code."', type: 'success'});";
			// Если вернулся код 200 - записываем в базу документы
			if( $http_code == "200" ) {
				$rows = 0; // Счётчик полученных документов
				$documents = json_decode($out, true);
				foreach($documents as $value) {
					if( $value["type"] == "CASH_OUTCOME" ) {
						$query = "
							INSERT INTO OrdersPayment
							SET payment_date = CONVERT_TZ(STR_TO_DATE(SUBSTRING_INDEX('{$value["closeDate"]}', '.', 1), '%Y-%m-%dT%T'), '+00:00', CONCAT(IF({$value["transactions"][0]["timezone"]} > 0, '+', ''), TIME_FORMAT(SEC_TO_TIME({$value["transactions"][0]["timezone"]} DIV 1000), '%H:%i')))
								,payment_sum = 0 - {$value["closeSum"]}
								,uuid = '{$value["uuid"]}'
								,CB_ID = {$CB_ID}
								,send = 1
							ON DUPLICATE KEY UPDATE
								payment_sum = 0 - {$value["closeSum"]}
						";
						mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
						$rows += mysqli_affected_rows( $mysqli );
					}
					$gtCloseDate_out = str_replace(".000+0000", ".001+0000", $value["closeDate"]);
				}
				if( $rows ) {
					echo "noty({timeout: 10000, text: 'Из облака ЭВОТОР получены новые документы.', type: 'success'});";
					// Обновляем дату последнего документа у кассы
					$query = "
						UPDATE CashBox
						SET gtCloseDate_out = '{$gtCloseDate_out}'
						WHERE CB_ID = {$CB_ID}
					";
					mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
				}
			}
			else { // Иначе выводим алерт с кодом ошибки
				echo "noty({text: 'Не удалось получить документы из облака ЭВОТОР. Свяжитесь с администратором. Код ошибки: ".$http_code."', type: 'error'});";
			}

		}
	}

	// Заполняем форму полученным документом
	$query = "
		SELECT OP.OP_ID
			,(OP.payment_sum * -1) payment_sum
			,OP.CB_ID
		FROM OrdersPayment OP
		WHERE OP.send = 1
			AND OP.uuid IS NOT NULL
			AND OP.cost_name IS NULL
			AND OP.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID IN ({$USR_Shop})" : "SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID} AND CT_ID = {$USR_City}").")
		ORDER BY OP.payment_date
		LIMIT 1
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	while ($row = mysqli_fetch_array($res)) {
		echo "$('#add_cost input[name=OP_ID]').val({$row["OP_ID"]});";
		echo "$('#add_cost input[name=cost]').val({$row["payment_sum"]}).prop('readonly', true);";
		echo "$('#add_cost select[name=CB_ID]').val({$row["CB_ID"]});";
		echo "$('#add_cost select[name=CB_ID] option:not(:selected)').attr('disabled', 'disabled')";
	}


	break;
///////////////////////////////////////////////////////////////////

// Форма добавления платежа к набору
case "add_payment":
	$OD_ID = $_GET["OD_ID"];

	$html = "";

	// Узнаем фамилию клиента, салон, счет терминала в салоне, закрыт ли месяц
	$query = "
		SELECT OD.ClientName
			,OD.SH_ID
			,SH.CT_ID
			,OD.is_lock
			,IF(OD.DelDate IS NOT NULL, 1, 0) is_del
			,OD.StartDate
		FROM OrdersData OD
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		WHERE OD.OD_ID = {$OD_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$ClientName = mysqli_result($res,0,'ClientName');
	$SH_ID = mysqli_result($res,0,'SH_ID');
	$CT_ID = mysqli_result($res,0,'CT_ID');
	$is_lock = mysqli_result($res,0,'is_lock');
	$is_del = mysqli_result($res,0,'is_del');
	$StartDate = mysqli_result($res,0,'StartDate');

	// Узнаем нативные кассы для набора по салону
	$query = "
		SELECT GROUP_CONCAT(CB.CB_ID) CB_IDs
		FROM CashBox CB
		WHERE CB.CB_ID IN (SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID} UNION SELECT CB_ID FROM Shops WHERE SH_ID = {$SH_ID})
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$CB_IDs = mysqli_result($res,0,'CB_IDs');

	// Узнаем кассу, доступную пользователю по выбранному набору
	$query = "
		SELECT CB.CB_ID
			,CB.name
			,deviceUuid
			,gtCloseDate
		FROM CashBox CB
		WHERE CB.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID = {$SH_ID}" : "SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID} AND CT_ID = {$USR_City}").")
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$CB_ID = mysqli_result($res,0,'CB_ID');
	$CashBox = mysqli_result($res,0,'name');
	$deviceUuid = mysqli_result($res,0,'deviceUuid');
	$gtCloseDate = mysqli_result($res,0,'gtCloseDate');

	// Если у кассы есть deviceUuid и gtCloseDate - пробуем получить документы из облака ЭВОТОР
	if( $deviceUuid and $gtCloseDate ) {
		// Узнаём storeUuid и X-Authorization магазина
		$query = "
			SELECT `storeUuid`, `X-Authorization`
			FROM Rekvizity
			#WHERE R_ID = (SELECT R_ID FROM Cities WHERE CT_ID = {$CT_ID})
			WHERE R_ID = (SELECT R_ID FROM CashBox WHERE CB_ID = {$CB_ID})
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$storeUuid = mysqli_result($res,0,'storeUuid');
		$Authorization = mysqli_result($res,0,'X-Authorization');

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/'.$storeUuid.'/documents?deviceUuid='.$deviceUuid.'&gtCloseDate='.$gtCloseDate.'&types=PAYBACK,SELL');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'X-Authorization: '.$Authorization
		));
		$out = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Получаем HTTP-код
		curl_close($curl);

		// Если вернулся код 200 - записываем в базу платежи
		if( $http_code == "200" ) {
			$rows = 0; // Счётчик полученных документов
			$documents = json_decode($out, true);
			foreach($documents as $value) {
				foreach($value["transactions"] as $transactions) {
					if( $transactions["type"] == "PAYMENT" ) {
						if( $transactions["paymentType"] == "CASH" or $transactions["paymentType"] == "CARD" ) {
							$query = "
								INSERT INTO OrdersPayment
								SET payment_date = CONVERT_TZ(STR_TO_DATE(SUBSTRING_INDEX('{$transactions["creationDate"]}', '.', 1), '%Y-%m-%dT%T'), '+00:00', CONCAT(IF({$transactions["timezone"]} > 0, '+', ''), TIME_FORMAT(SEC_TO_TIME({$transactions["timezone"]} DIV 1000), '%H:%i')))
									,payment_sum = {$transactions["sum"]}
									,terminal = ".($transactions["paymentType"] == "CARD" ? "1" : "0")."
									,uuid = '{$transactions["uuid"]}'
									,CB_ID = {$CB_ID}
								ON DUPLICATE KEY UPDATE
									payment_sum = payment_sum + ({$transactions["sum"]})
							";
							mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
							$rows += mysqli_affected_rows( $mysqli );
						}
						$gtCloseDate = str_replace(".000+0000", ".001+0000", $transactions["creationDate"]);
					}
				}
			}
			if( $rows ) {
				echo "noty({timeout: 10000, text: 'Из облака ЭВОТОР получены новые документы.', type: 'success'});";
				// Обновляем дату последнего документа у кассы
				$query = "
					UPDATE CashBox
					SET gtCloseDate = '{$gtCloseDate}'
					WHERE CB_ID = {$CB_ID}
				";
				mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			}
		}
		else { // Иначе выводим алерт с кодом ошибки
			echo "noty({text: 'Не удалось получить документы из облака ЭВОТОР. Свяжитесь с администратором. Код ошибки: ".$http_code."', type: 'error'});";
		}

	}

	$html .= "<div class='accordion'>";
	$html .= "<h3>Памятка по внесению оплаты</h3>";
	$html .= "<div><ul>";
	$html .= "<li>Ранее добавленные платежи <b>не редактируются</b>. Если нужно изменить или отменить предыдущую запись, то создайте новую корректирующую операцию с отрицательной суммой.</li>";
	$html .= "<li>Если нужно совершить возврат денег по набору, он так же вносится со знаком минус.</li>";
	$html .= "<li><b>Для переноса платежа с одного набора на другой: поставьте галку справа от платежа, который требуется перенести, и нажмите \"Сохранить\", затем перейдите к заказу в который нужно принять платёж, в форме добавления оплаты поставьте галку напротив платежа, который нужно принять <span style='border: 2px dashed #911;'>выделен красной рамкой</span>, и нажмите \"Сохранить\".</b></li>";
	$html .= "</ul></div>";
	$html .= "</div>";

	$html .= "<input type='hidden' name='OD_ID' value='{$OD_ID}'>";

	$html .= "<table><thead><tr>";
	$html .= "<th style='width: 56px;'>Касса</th>";
	$html .= "<th>Дата</th>";
	$html .= "<th>Сумма</th>";
	$html .= "<th>Тип оплаты</th>";
	$html .= "<th>Автор</th>";
	$html .= "<th><i class='fas fa-exchange-alt' title='Перенести оплату с этого набора на другой.'></i></th>";
	$html .= "</tr></thead><tbody>";

	// Выводим список ранее внесенных платежей
	$query = "
		#Открепленные платежи
		SELECT OP.OD_ID
			,OP.OP_ID
			,Friendly_date(OP.payment_date) payment_date
			,DATE_FORMAT(OP.payment_date, '%H:%i') Time
			,OP.payment_sum
			,OP.terminal
			,OP.uuid
			,USR_Icon(OP.author) Name
			,CB.name
			,IF({$CT_ID} = {$USR_City}, 1, 0) checkbox
			,IF(CB.CB_ID IN ({$CB_IDs}), 1, 0) native
			,OP.payment_date p_date
			,'Прикрепить' label
		FROM OrdersPayment OP
		JOIN CashBox CB ON CB.CB_ID = OP.CB_ID AND CB.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID IN ({$USR_Shop})" : "SELECT CB_ID FROM Shops WHERE CT_ID = {$CT_ID} UNION SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID}").")
		WHERE OP.OD_ID IS NULL
			AND OP.cost_name IS NULL
			AND IFNULL(OP.payment_sum, 0) != 0
			AND OP.send IS NULL

		UNION ALL

		SELECT OP.OD_ID
			,OP.OP_ID
			,Friendly_date(OP.payment_date) payment_date
			,DATE_FORMAT(OP.payment_date, '%H:%i') Time
			,OP.payment_sum
			,OP.terminal
			,OP.uuid
			,USR_Icon(OP.author) Name
			,CB.name
			,IF(CB.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID IN ({$USR_Shop})" : "SELECT CB_ID FROM Shops WHERE CT_ID = {$CT_ID} AND CT_ID = {$USR_City} UNION SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID} AND CT_ID = {$USR_City}")."), 1, 0) checkbox
			,IF(CB.CB_ID IN ({$CB_IDs}), 1, 0) native
			,OP.payment_date p_date
			,'Открепить' label
		FROM OrdersPayment OP
		LEFT JOIN CashBox CB ON CB.CB_ID = OP.CB_ID
		WHERE OP.OD_ID = {$OD_ID} AND IFNULL(OP.payment_sum, 0) != 0

		ORDER BY OD_ID, p_date
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

	while( $row = mysqli_fetch_array($res) ) {
		if( $row["OD_ID"] ) {
			$html .= "<tr>";
		}
		else {
			$html .= "<tr style='border: 2px dashed #911;'>";
		}
		$html .= "<td class='nowrap' style='position: relative;'>".($row["OD_ID"] ? "" : "<div style='position: absolute; color: #911; top: -5px; font-size: .8em;'>Откреплённая денежная операция!</div>").($row["native"] ? "<b>{$row["name"]}</b>" : "<b style='color: #911;' title='Эта касса не связана с салоном данного набора!'>{$row["name"]}<i class='fa fa-question-circle'></i></b>")."</td>";
		$html .= "<td class='txtleft'>{$row["payment_date"]}<br>{$row["Time"]}".($row["uuid"] ? " <i class='fas fa-cloud-download-alt' title='Запись получена автоматически'></i>" : "")."</td>";
		$format_payment_sum = number_format($row["payment_sum"], 0, '', ' ');
		$color = $row["payment_sum"] > 0 ? "#16A085" : "#E74C3C";
		$html .= "<td class='txtright'><b style='color: {$color};'>{$format_payment_sum}</b></td>";
		$html .= "<td>".($row["terminal"] ? "<i title='Оплата картой' class='fas fa-credit-card fa-lg'></i>" : "<i title='Наличными' class='fas fa-wallet fa-lg'></i>")."</td>";
		$html .= "<td>{$row["Name"]}</td>";
		// Чекбокс перемещения платежа
		if( $is_del or $is_lock or !$row["checkbox"] ) {
			$html .= "<td></td>";
		}
		elseif( !$StartDate ) {
			$html .= "<td style='color: #911;'>Не продан.<br>Действие не возможно.</td>";
		}
		else {
			$html .= "<td><label><input type='checkbox' name='move_payment[]' value='{$row["OP_ID"]}'>{$row["label"]}</label></td>";
		}
		$html .= "</tr>";
	}

	if( $CB_ID ) { //Если доступна касса
		if( $deviceUuid and $gtCloseDate ) {
			$html .= "<tr style='background: #6f6;'><td colspan='7'><b>Информация об оплате импортируется автоматически.</b></td></tr>";
		}
		elseif( $is_del ) {
			$html .= "<tr style='background: #6f6;'><td colspan='7'><b>Набор удалён. Внесение оплаты невозможно.</b></td></tr>";
		}
		elseif( $is_lock ) {
			$html .= "<tr style='background: #6f6;'><td colspan='7'><b>Отчетный период закрыт. Внесение оплаты невозможно.</b></td></tr>";
		}
		else { // Если набор не закрыт и не удален то можно добавить оплату
			$payment_date = date('d.m.y');
			$html .= "<tr style='background: #6f6;'>";
			$html .= "<td><b>{$CashBox}</b><input type='hidden' name='cb_id' value='{$CB_ID}'></td>";
			$html .= "<td>{$payment_date}</td>";
			$html .= "<td><input type='number' class='payment_sum' name='payment_sum_add'></td>";
			$html .= "<td><label><input type='checkbox' class='terminal' name='terminal_add' value='1'>Оплата картой</label></td>";
			$html .= "<td>{$USR_Icon}</td>";
			$html .= "<td></td>";
			$html .= "</tr>";
		}
	}
	$html .= "</tbody></table>";

	$html = addslashes($html);
	echo "$('#add_payment fieldset div').html('{$html}');";
	// Инициируем акордион
	echo "$('#add_payment .accordion').accordion({collapsible: true, heightStyle: 'content', active: false});";

	break;
///////////////////////////////////////////////////////////////////

// Форма редактирования цены набора
case "update_price":
	$OD_ID = $_GET["OD_ID"];

	$html = "<input type='hidden' name='location'>";

	$html .= "<div class='accordion'>";
	$html .= "<h3>Памятка по изменению стоимости набора</h3>";
	$html .= "<div><ul>";
	$html .= "<li>Стоимость изделий вычисляется автоматически согласно прайса и может быть изменена только в большую сторону.</li>";
	$html .= "<li>Для уменьшения стоимости воспользуйтесь скидкой. Размер скидки указывается в рублях за единицу товара.</li>";
	$html .= "</ul></div>";
	$html .= "</div>";

	$html .= "<table class='main_table'><thead><tr>";
	$html .= "<th>Наименование</th>";
	$html .= "<th width='75'>По прайсу</th>";
	$html .= "<th width='75'>Цена за шт.</th>";
	$html .= "<th width='75'>Скидка за шт.</th>";
	$html .= "<th width='50'>%</th>";
	$html .= "<th width='50'>Кол-во</th>";
	$html .= "<th width='75'>Сумма</th>";
	$html .= "</tr></thead><tbody>";

	$query = "
		SELECT ODD.OD_ID
			,ODD.ODD_ID
			,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
			,IFNULL(ODD.min_price, 0) min_price
			,ODD.Price
			,ODD.discount
			,ODD.Amount

			,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', Zakaz(ODD.ODD_ID), '</i></b><br>') Zakaz

		FROM OrdersDataDetail ODD
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE ODD.OD_ID = {$OD_ID}
		ORDER BY PTID DESC, ODD.ODD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	while( $row = mysqli_fetch_array($res) ) {
		$format_min_price = number_format($row["min_price"], 0, '', ' ');
		$html .= "<tr>";
		$html .= "<input type='hidden' name='ODD_ID[]' value='{$row["ODD_ID"]}'>";
		$html .= "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		$html .= "<td class='txtright'><span class='price'>{$format_min_price}</span></td>";
		$html .= "<td class='prod_price'><input type='number' min='{$row["min_price"]}' name='price[]' value='{$row["Price"]}' style='width: 70px; text-align: right;'></td>";
		$html .= "<td class='prod_discount'><input type='number' min='0' name='discount[]' value='{$row["discount"]}' style='width: 70px; text-align: right;'></td>";
		$html .= "<td><span class='prod_percent'></span>%</td>";
		$html .= "<td class='prod_amount' style='text-align: center; font-size: 1.3em; font-weight: bold;'>{$row["Amount"]}</td>";
		$html .= "<td class='prod_sum' style='text-align: right;'></td>";
		$html .= "</tr>";
	}
	$html .= "<tr style='text-align: right; font-weight: bold;'><td colspan='5' id='discount'>Скидка: <input disabled type='number' style='width: 70px; text-align: right;'> руб. (<span></span> %)</td><td>Итог:</td><td id='prod_total'><input disabled type='number' style='width: 70px; text-align: right;'></td></tr>";
	$html .= "</tbody></table>";

	$html = addslashes($html);
	echo "$('#update_price fieldset').html('{$html}');";
	// Инициируем акордион
	echo "$('#update_price .accordion').accordion({collapsible: true, heightStyle: 'content', active: false});";

	break;
///////////////////////////////////////////////////////////////////

// Формирование дропдауна со списком салонов
case "create_shop_select":
	$OD_ID = $_GET["OD_ID"];
	$SH_ID = $_GET["SH_ID"] ? $_GET["SH_ID"] : 0;
	$html = "";

	// Узнаём отгрузку у набора, дату отгрузки, регион, накладную, плательщика
	$query = "SELECT IFNULL(OD.SHP_ID, 0) SHP_ID
					,OD.StartDate
					,OD.ReadyDate
					,SH.CT_ID
					,OD.PFI_ID
					,PFI.platelshik_id
					,IFNULL(SH.retail, 0) retail
				FROM OrdersData OD
				LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
				WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$SHP_ID = mysqli_result($res,0,'SHP_ID');
	$StartDate = mysqli_result($res,0,'StartDate');
	$ReadyDate = mysqli_result($res,0,'ReadyDate');
	$CT_ID = mysqli_result($res,0,'CT_ID');
	$PFI_ID = mysqli_result($res,0,'PFI_ID');
	$platelshik_id = mysqli_result($res,0,'platelshik_id');
	$retail = mysqli_result($res,0,'retail');

	// Формируем элементы дропдауна
	$query = "
		SELECT SH.SH_ID
			,CONCAT(CT.City, '/', SH.Shop) AS Shop
			,'selected' selected
			,CT.Color
			,SH.retail
		FROM Shops SH
		JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		WHERE SH.SH_ID = {$SH_ID}
	";

	if ($StartDate) {
		$query .= "
			UNION
			SELECT SH.SH_ID
				,CONCAT(CT.City, '/', SH.Shop) AS Shop
				,IF(SH.SH_ID = {$SH_ID}, 'selected', '') AS selected
				,CT.Color
				,SH.retail
			FROM Shops SH
			JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			WHERE ".($retail ? "CT.CT_ID = {$CT_ID} AND SH.retail = 1" : "SH.KA_ID = {$platelshik_id}")."
		";
	}
	else {
		if( ((in_array('order_add_confirm', $Rights) or in_array('order_add_free', $Rights)) and !$ReadyDate) or $SH_ID == 0 ) {
			$html .= "<option value='0' ".($SH_ID == 0 ? "selected" : "")." style='background: #999;'>Свободные</option>";
		}
		$query .= "
			UNION
			SELECT SH.SH_ID
				,CONCAT(CT.City, '/', SH.Shop) AS Shop
				,IF(SH.SH_ID = {$SH_ID}, 'selected', '') selected
				,CT.Color
				,SH.retail
			FROM Shops SH
			JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			WHERE CT.CT_ID IN (".($CT_ID ? $CT_ID : $USR_cities).")
		";
	}
	$query .= "
			".($USR_Shop ? "AND SH.SH_ID IN ({$USR_Shop})" : "")."
			".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
		#ORDER BY Shop
		ORDER BY retail DESC, Shop
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	while( $row = mysqli_fetch_array($res) )
	{
		$html .= "<option value='{$row["SH_ID"]}' {$row["selected"]} style='background: {$row["Color"]};'>".($row["retail"] ? "&bull; " : "")."{$row["Shop"]}</option>";
	}

	$html = addslashes($html);
	echo "$('#ord{$OD_ID} .shop_cell .select_shops').html('{$html}');";

	break;
///////////////////////////////////////////////////////////////////

// Редактирование салона
case "update_shop":
	$OD_ID = $_GET["OD_ID"];
	$OD_IDs = $_GET["OD_IDs"] ? $_GET["OD_IDs"] : $OD_ID;
	$SH_ID = $_GET["SH_ID"] ? $_GET["SH_ID"] : "NULL";

	// Узнаем название старого салона
	$query = "
		SELECT IFNULL(SH.SH_ID, 0) SH_ID, IFNULL(SH.Shop, 'Свободные') Shop
		FROM OrdersData OD
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		WHERE OD.OD_ID = {$OD_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$old_shid = mysqli_result($res,0,'SH_ID');
	$old_shop = mysqli_result($res,0,'Shop');

	// Меняем салон у группы
	$query = "UPDATE OrdersData SET SH_ID = {$SH_ID}, author = {$_SESSION['id']} WHERE OD_ID IN ({$OD_IDs})";
	if( !mysqli_query( $mysqli, $query ) ) {
		echo "$('.main_table td#{$OD_ID} select.select_shops').val({$old_shid});";
		die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	}

	// Узнаем данные нового салона
	$query = "
		SELECT OD.OD_ID
			,IFNULL(SH.Shop, 'Свободные') Shop
			,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS ShopCity
			,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
			,IFNULL(OD.SH_ID, 0) SH_ID
			,IFNULL(SH.retail, 0) retail
		FROM OrdersData OD
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		WHERE OD.OD_ID IN ({$OD_IDs})

	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	while ($row = mysqli_fetch_array($res)) {
		$new_shop = $row["Shop"];
		$ShopCity = $row["ShopCity"];
		$ShopCity = addslashes($ShopCity);
		$retail = $row["retail"];

		echo "$('#ord{$row["OD_ID"]} .shop_cell span').html('".($row["retail"] ? "&bull; " : "")."{$ShopCity}');";
		echo "$('#ord{$row["OD_ID"]} .shop_cell span').attr('style', 'background: {$row["CTColor"]};');";
		echo "$('#ord{$row["OD_ID"]} .shop_cell').attr('SH_ID', '{$row["SH_ID"]}');";
		// Меняем салон на экране
		echo "$('#ord{$row["OD_ID"]} select.select_shops').val('{$row["SH_ID"]}');";
	}

	echo "noty({timeout: 3000, text: 'Салон изменен с <b>{$old_shop}</b> на <b>{$new_shop}</b>', type: 'success'});";
	if( $SH_ID == 0 ) {
		echo "$('.main_table tr[id=\"ord{$OD_ID}\"]').hide('fast');";
		echo "noty({timeout: 4000, text: 'Набор перемещен в <b>СВОБОДНЫЕ</b>', type: 'alert'});";
	}

	// Проверяем отметку об изменении стоимости набора и выводим сообщение
	$query = "SELECT OD.Code FROM OrdersData OD WHERE OD.author = {$_SESSION['id']} AND OD.change_price = 1";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "noty({text: 'Внимание! Ваши действия вызвали изменение стоимости набора {$row['Code']}.', type: 'error'});";
	}
	$query = "UPDATE OrdersData OD SET OD.change_price = 0 WHERE OD.author = {$_SESSION['id']} AND OD.change_price = 1";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	break;
///////////////////////////////////////////////////////////////////

// Редактирование комментариев
case "update_comment":
	$OD_ID = $_GET["OD_ID"];
	$comment = mysqli_real_escape_string( $mysqli, $_GET["val"] );

	// Обновляем комментарий
	$query = "UPDATE OrdersData SET Comment = '{$comment}', author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
	mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

	echo "$('#ord{$OD_ID} .comment_cell span').html('{$comment}');";
	echo "noty({timeout: 3000, text: 'Комментарий был обновлен.', type: 'success'});";

	break;
///////////////////////////////////////////////////////////////////

// Редактирование даты продажи
case "update_sell_date":
	$OD_ID = $_GET["OD_ID"];
	$OD_IDs = $_GET["OD_IDs"];
	$StartDate = $_GET["StartDate"] ? '\''.date( 'Y-m-d', strtotime($_GET["StartDate"]) ).'\'' : "NULL";

	// Узнаем текущую дату продажи и дату отгрузки
	$query = "
		SELECT DATE_FORMAT(StartDate, '%d.%m.%Y') StartDate
			,ReadyDate
		FROM OrdersData
		WHERE OD_ID = {$OD_ID}
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$row = mysqli_fetch_array($res);
	$cur_StartDate = $row["StartDate"];
	$cur_ReadyDate = $row["ReadyDate"];

	// Если продали с выставки неотгруженный товар, то присваиваем дату сдачи
	if( !$cur_StartDate and $_GET["StartDate"] and !$cur_ReadyDate ) {
		$_GET["retail"] = 1;
		include "get_end_date.php";
		$_GET["EndDate"] = date_format($end_date, 'd.m.Y');
	}
	$EndDate = $_GET["EndDate"] ? '\''.date( "Y-m-d", strtotime($_GET["EndDate"]) ).'\'' : "NULL";

	// Меняем дату продажи у группы
	$query = "
		UPDATE OrdersData
		SET StartDate = {$StartDate}
			".(isset($_GET["EndDate"]) ? ",EndDate = $EndDate" : "")."
			,author = {$_SESSION['id']}
		WHERE OD_ID IN ({$OD_IDs})
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		echo "$('tr#ord{$OD_ID} .sell_date').val('{$cur_StartDate}');";
		die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	}

	// Обновляем дату продажи на экране у группы
	$arrOD_IDs = explode(",", $OD_IDs);
	foreach ($arrOD_IDs as $value) {
		echo "$('tr#ord{$value} .sell_date').val('{$_GET["StartDate"]}');";
	}

	if ($cur_StartDate) {
		echo "noty({timeout: 3000, text: 'Дата заключения договора изменена с <b>{$cur_StartDate}</b> на <b>{$_GET["StartDate"]}</b>', type: 'success'});";
	}
	else {
		echo "noty({timeout: 3000, text: 'Установлена дата заключения договора <b>{$_GET["StartDate"]}</b>', type: 'success'});";
	}

	break;
///////////////////////////////////////////////////////////////////

// Редактирование примечания к реализации
case "update_sell_comment":
	$OD_ID = $_GET["OD_ID"];
	$sell_comment = convert_str($_GET["sell_comment"]);
	$sell_comment = mysqli_real_escape_string($mysqli, $sell_comment);

	// Узнаем старое примечание
	$query = "SELECT sell_comment FROM OrdersData WHERE OD_ID = {$OD_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$old_sell_comment = mysqli_result($res,0,'sell_comment');

	// Меняем примечание
	$query = "UPDATE OrdersData SET sell_comment = '{$sell_comment}', author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
	if( !mysqli_query( $mysqli, $query ) ) {
		echo "$('td#{$OD_ID} .sell_comment').val('{$old_sell_comment}');";
		die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	}

	$old_sell_comment = $old_sell_comment ? htmlspecialchars($old_sell_comment, ENT_QUOTES) : "___";
	$sell_comment = $sell_comment ? htmlspecialchars($sell_comment, ENT_QUOTES) : "___";
	echo "noty({timeout: 3000, text: 'Комментарий изменен с <b>{$old_sell_comment}</b> на <b>{$sell_comment}</b>', type: 'success'});";

	break;
///////////////////////////////////////////////////////////////////

// Редактирование примечания к накладным
//case "update_sverki_comment":
//	$PFI_ID = $_GET["PFI_ID"];
//	$sverki_comment = convert_str($_GET["sverki_comment"]);
//	$sverki_comment = mysqli_real_escape_string($mysqli, $sverki_comment);
//
//	// Узнаем старое примечание
//	$query = "SELECT comment FROM PrintFormsInvoice WHERE PFI_ID = {$PFI_ID}";
//	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
//	$old_sverki_comment = mysqli_result($res,0,'comment');
//
//	// Меняем примечание
//	$query = "UPDATE PrintFormsInvoice SET comment = '{$sverki_comment}' WHERE PFI_ID = {$PFI_ID}";
//	if( !mysqli_query( $mysqli, $query ) ) {
//		echo "$('td#{$PFI_ID} .sverki_comment').val('{$old_sverki_comment}');";
//		die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
//	}
//
//	$old_sverki_comment = $old_sverki_comment ? htmlspecialchars($old_sverki_comment, ENT_QUOTES) : "___";
//	$sverki_comment = $sverki_comment ? htmlspecialchars($sverki_comment, ENT_QUOTES) : "___";
//	echo "noty({timeout: 3000, text: 'Комментарий изменен с <b>{$old_sverki_comment}</b> на <b>{$sverki_comment}</b>', type: 'success'});";
//
//	break;
///////////////////////////////////////////////////////////////////

// Разделение набора
case "order_cut":
	$OD_ID = $_GET["OD_ID"];

	$html = "<input type='hidden' name='OD_ID' value='{$OD_ID}'>";
	$html .= "<input type='hidden' name='location'>";
	$html .= "<div id='slider' style='text-align: center;'>";

	$query = "
		SELECT ODD.OD_ID
			,ODD.ODD_ID
			,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
			,ODD.Amount

			,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', REPLACE(ODD.Comment, '\r\n', ' '), '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', Zakaz(ODD.ODD_ID), '</i></b><br>') Zakaz

		FROM OrdersDataDetail ODD
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE ODD.OD_ID = {$OD_ID}
		ORDER BY PTID DESC, ODD.ODD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	while( $row = mysqli_fetch_array($res) ) {
		$html .= "<div>";
		$html .= "<div>{$row["Zakaz"]}</div>";
		$html .= "<input type='hidden' name='PT_ID[]' value='{$row["PT_ID"]}'>";
		$html .= "<input type='hidden' name='ODD_ID[]' value='{$row["ODD_ID"]}'>";
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
	$val = $_GET["val"] ? $_GET["val"] : "NULL";

	$query = "UPDATE OrdersDataDetail SET MT_amount = {$val} WHERE ODD_ID = {$oddid}";
	mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

	echo "noty({timeout: 3000, text: 'Метраж обновлен на: <b>\"{$val}\"</b>', type: 'success'});";

	break;
///////////////////////////////////////////////////////////////////

// Формирование списка материалов для набора
case "material_list":
	$materials_name = "";
	$ODD_IDs = 0;

	// Собираем идентификаторы изделий и прочего
	foreach ($_GET["prod"] as $k => $v) {
		$ODD_IDs .= ",{$v}";
	}

	// Находим строку с максимальной длиной
	$query = "
		SELECT MAX(CHAR_LENGTH(MT.Material)) length
		FROM OrdersDataDetail ODD
		JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		WHERE ODD.ODD_ID IN ($ODD_IDs)
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$length = mysqli_result($res,0,'length') ? mysqli_result($res,0,'length') : 0;

	$query = "
		SELECT RPAD(MT.Material, {$length}, ' ') Material
			,RPAD(CONCAT('- ', ROUND(ODD.MT_amount, 1), ' м.п.'), 12, ' ') MT_amount
			,IF(ODD.IsExist = 1, DATE_FORMAT(ODD.order_date, '%d.%m.%y'), '') order_date
		FROM OrdersDataDetail ODD
		JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		WHERE ODD.ODD_ID IN ($ODD_IDs)
		ORDER BY MT.Material
	";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	while( $row = mysqli_fetch_array($res) ) {
		$materials_name .= $row["Material"]."\\t".$row["MT_amount"]."\\t".$row["order_date"]."\\r\\n";
	}
	if( $materials_name ) {
		echo "$('#copy_link').show();";
		echo "$('#print_btn').show();";
	}
	else {
		echo "$('#copy_link').hide();";
		echo "$('#print_btn').hide();";
	}
	echo "$('#materials_name').html('{$materials_name}');";

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
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
		}
		$html .= "</optgroup>";

		$html .= "<optgroup label='Безнал'>";
		$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 1";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
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

		$query = "SELECT FC_ID, name FROM FinanceCategory WHERE type = {$type}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
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
	$usr_id = $_GET["usr_id"];
	$min_size = 4;
	$html = "";

	if( $usr_id != "null" ) {
		// Список частых заготовок
		$html .= "<optgroup label='Частые'>";
		$query = "SELECT BL.BL_ID, BL.Name
					FROM BlankList BL
					JOIN BlankStock BS ON BS.BL_ID = BL.BL_ID AND IFNULL(BS.USR_ID, 0) = {$usr_id} AND DATEDIFF(NOW(), BS.Date) <= 90
					LEFT JOIN ProductBlank PB ON PB.BL_ID = BL.BL_ID AND PB.BL_ID IS NOT NULL
					LEFT JOIN ProductModels PM ON PM.PM_ID = PB.PM_ID
					WHERE IFNULL(PM.archive, 0) = 0
					GROUP BY BL.BL_ID
					ORDER BY BL.PT_ID, BL.Name";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
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
					LEFT JOIN BlankStock BS ON BS.BL_ID = BL.BL_ID AND IFNULL(BS.USR_ID, 0) = {$usr_id} AND DATEDIFF(NOW(), BS.Date) <= 90
					LEFT JOIN ProductBlank PB ON PB.BL_ID = BL.BL_ID AND PB.BL_ID IS NOT NULL
					LEFT JOIN ProductModels PM ON PM.PM_ID = PB.PM_ID
					WHERE BS.BL_ID IS NULL AND IFNULL(PM.archive, 0) = 0
					GROUP BY BL.BL_ID
					ORDER BY BL.PT_ID, BL.Name";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<option value='{$row["BL_ID"]}'>{$row["Name"]}</option>";
		}
		$html .= "</optgroup>";
		$size = ($size < $min_size) ? $min_size : $size;
		echo "$('#addblank #blank').attr('size', {$size});";
	}
	else {
		echo "$('#addblank #blank').attr('size', {$min_size});";
	}

	$html = addslashes($html);
	echo "$('#addblank #blank').html('{$html}');";

	break;
///////////////////////////////////////////////////////////////////

// Формирование списка задействованых заготовок при выборе заготовки в форме добавления заготовок
case "subblank_dropdown":
	$bl_id = $_GET["bl_id"];
	$usr_id = $_GET["usr_id"];
	$html = "<legend style='text-align: left;'>Задействованы:</legend>";
	$count = 0;

	if( $bl_id != 0 ) {
		// Список частых заготовок
		$query = "SELECT BLL.BLL_ID, BL.Name, BLL.Amount
					FROM BlankLink BLL
					JOIN BlankList BL ON BL.BL_ID = BLL.BLL_ID
					WHERE BLL.BL_ID = {$bl_id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		while( $row = mysqli_fetch_array($res) )
		{
			$html .= "<div style='text-align: left;'><b>{$row["Name"]}:</b></div>";
			$html .= "<input type='hidden' name='amount[]' value='{$row["Amount"]}'>";
			$html .= "<input type='hidden' name='bll_id[]' value='{$row["BLL_ID"]}'>";
			$count++;

			// Формируем выпадающий список со связанными заготовками
			$query = "
				SELECT IFNULL(BC.USR_ID, 0) USR_ID, (BC.count + BC.start_balance) count, IFNULL(USR_ShortName(BC.USR_ID), 'Без работника') Name
				FROM BlankCount BC
				WHERE BC.BL_ID = {$row["BLL_ID"]}
			";
			$subres = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			$select = "<select required name='usr_id[]' style='width: 250px;'>";
			$select .= "<option value=''>-=Выберите вариант из списка=-</option>";
			while( $subrow = mysqli_fetch_array($subres) )
			{
				$selected = ($subrow["count"] > 0 and $subrow["USR_ID"] == $usr_id) ? "selected" : "";
				$select .= "<option {$selected} value='{$subrow["USR_ID"]}'>({$subrow["count"]} шт.) {$subrow["Name"]}</option>";
			}
			$select .= "</select>";

			$html .= $select;
		}
	}

	if( $count ) {
		$html = addslashes($html);
		echo "$('#addblank #subblank').html('{$html}');";
		echo "$('#addblank #subblank').prop('disabled', false);";
		echo "$('#addblank #subblank').show('fast');";
	}
	else {
		echo "$('#addblank #subblank').prop('disabled', true);";
		echo "$('#addblank #subblank').hide('fast');";
	}

	break;
///////////////////////////////////////////////////////////////////

// Обновление начального значения заготовок по рабочим
case "start_balance_worker":
	$usr_id = $_GET["usr_id"];
	$bl_id = $_GET["bl_id"];
	$val = $_GET["val"] ? $_GET["val"] : "0";

	// Узнаем какое было раньше начальное значение у рабочего
	$query = "SELECT start_balance FROM BlankCount WHERE BL_ID = {$bl_id} AND USR_ID = {$usr_id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$start_balance = mysqli_result($res,0,'start_balance');

	// Если значение изменено
	if( $start_balance != $val ) {
		$diff = $val - $start_balance;

		// Добавление в журнал сдачи заготовок информацию о корректировке
		$query = "INSERT INTO BlankStock(BL_ID, USR_ID, Amount, adj, author)
				  VALUES ({$bl_id}, ".($usr_id == 0 ? "NULL" : $usr_id).", {$diff}, 1, {$_SESSION["id"]})";
		mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

		// Обновляем начальное значение заготовки у рабочего
		$query = "UPDATE BlankCount SET start_balance = {$val}, last_date = NOW() WHERE BL_ID = {$bl_id} AND USR_ID = {$usr_id}";
		mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

		// Узнаем общее кол-во заготовки и начальное значение
		$query = "SELECT SUM(BC.count + BC.start_balance) Amount, SUM(BC.start_balance) start_balance
					FROM BlankCount BC
					WHERE BC.BL_ID = {$bl_id}
					GROUP BY BC.BL_ID";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$start_balance = mysqli_result($res,0,'start_balance');
		$blank_amount = mysqli_result($res,0,'Amount');
		$color = ( $blank_amount < 0 ) ? ' bg-red' : '';
		$html = "<b class='{$color}'>{$blank_amount}</b>";
		$html = addslashes($html);

		// Узнаем общее кол-во заготовки у рабочего
		$query = "SELECT (BC.count + BC.start_balance) Amount
					FROM BlankCount BC
					WHERE BC.BL_ID = {$bl_id} AND BC.USR_ID = {$usr_id}";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$worker_amount = mysqli_result($res,0,'Amount');
		$sub_color = ( $worker_amount < 0 ) ? ' bg-red' : '';
		$sub_html = "<i class='{$sub_color}'>{$worker_amount}</i>";
		$sub_html = addslashes($sub_html);

		// Формируем новую строку в таблицу журнала

		echo "$('#exist_blank .sub_blank#{$bl_id} .start_balance').hide('fast');";
		echo "$('#exist_blank .sub_blank#{$bl_id} .blank_amount span').hide('fast');";
		echo "$('#exist_blank #blank_{$bl_id}_{$usr_id} .blank_amount span').hide('fast');";
		echo "$('#exist_blank .sub_blank#{$bl_id} .start_balance').val('{$start_balance}');";
		echo "$('#exist_blank .sub_blank#{$bl_id} .blank_amount span').html('{$html}');";
		echo "$('#exist_blank #blank_{$bl_id}_{$usr_id} .blank_amount span').html('{$sub_html}');";
		echo "$('#exist_blank .sub_blank#{$bl_id} .start_balance').show('fast');";
		echo "$('#exist_blank .sub_blank#{$bl_id} .blank_amount span').show('fast');";
		echo "$('#exist_blank #blank_{$bl_id}_{$usr_id} .blank_amount span').show('fast');";
		echo "noty({timeout: 3000, text: 'Начальное значение обновлено на: <b>\"{$val}\"</b>', type: 'success'});";
		echo "blank_log_table();";	// Обновляем таблицу сдачи заготовок
	}

	break;
///////////////////////////////////////////////////////////////////

// Обновление начального значения заготовок верхнего уровня
case "start_balance_blank":
	$bl_id = $_GET["bl_id"];
	$val = $_GET["val"] ? $_GET["val"] : 0;

	// Узнаем какое было раньше начальное значение
	$query = "SELECT start_balance FROM BlankList WHERE BL_ID = {$bl_id}";
	$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
	$start_balance = mysqli_result($res,0,'start_balance');

	// Если значение изменено
	if( $start_balance != $val ) {
		$diff = $val - $start_balance;

		// Добавление в журнал сдачи заготовок информацию о корректировке
		$query = "INSERT INTO BlankStock(BL_ID, Amount, adj, author)
				  VALUES ({$bl_id}, {$diff}, 1, {$_SESSION["id"]})";
		mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

		// Обновляем начальное значение заготовки верхнего уровня
		$query = "UPDATE BlankList SET start_balance = {$val} WHERE BL_ID = {$bl_id}";
		mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");

		// Узнаем кол-во заготовок верхнего уровня
		$query = "
			SELECT IFNULL(BL.start_balance, 0) + IFNULL(SBS.Amount, 0) - IFNULL(SODD.Ready, 0) - IFNULL(SODB.Ready, 0) total_amount
			FROM BlankList BL
			LEFT JOIN (
				SELECT BS.BL_ID, SUM(BS.Amount) Amount
				FROM BlankStock BS
				WHERE BS.adj = 0
				GROUP BY BS.BL_ID
			) SBS ON SBS.BL_ID = BL.BL_ID
			LEFT JOIN (
				SELECT PB.BL_ID
					,SUM(IF(OD.IsPainting = 3 OR (OD.CL_ID IS NULL AND OD_IsReady(OD.OD_ID)), ODD.Amount, 0) * PB.Amount) Ready
				FROM OrdersDataDetail ODD
				JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
				JOIN ProductBlank PB ON PB.PM_ID = ODD.PM_ID
				GROUP BY PB.BL_ID
			) SODD ON SODD.BL_ID = BL.BL_ID
			LEFT JOIN (
				SELECT ODD.BL_ID
					,SUM(IF(OD.IsPainting = 3 OR (OD.CL_ID IS NULL AND OD_IsReady(OD.OD_ID)), ODD.Amount, 0)) Ready
				FROM OrdersDataDetail ODD
				JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
				WHERE ODD.BL_ID IS NOT NULL
				GROUP BY ODD.BL_ID
			) SODB ON SODB.BL_ID = BL.BL_ID
			WHERE BL.BL_ID = {$bl_id}
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$amount = mysqli_result($res,0,'total_amount');
		$color = ( $amount < 0 ) ? ' bg-red' : '';
		$html = "<b class='{$color}'>{$amount}</b>";
		$html = addslashes($html);

		echo "$('#exist_blank #blank_{$bl_id} b').hide('fast');";
		echo "$('#exist_blank #blank_{$bl_id} b').html('{$html}');";
		echo "$('#exist_blank #blank_{$bl_id} b').show('fast');";
		echo "noty({timeout: 3000, text: 'Начальное значение обновлено c <b>\"{$start_balance}\"</b> на <b>\"{$val}\"</b>', type: 'success'});";
		echo "blank_log_table();";	// Обновляем таблицу сдачи заготовок
	}

	break;
/////////////////////////////////////////////////////////////////////

// Вывод таблицы журнала сдачи заготовок
case "blank_log_table":
	$datediff = 365; // Максимальный период отображения данных

	$html = "
		<h1>Журнал сдачи заготовок</h1>
		<table class='main_table'>
			<thead>
			<tr>
				<th width='20'></th>
				<th width='60'>Дата</th>
				<th width='60'>Время</th>
				<th width='20%'>Работник</th>
				<th width='20%'>Заготовка</th>
				<th width='50'>Кол-во</th>
				<th width='50'>Тариф</th>
				<th width='20%'>Примечание</th>
				<th width='50'>Автор</th>
			</tr>
			</thead>
			<tbody>
	";

			$query = "SELECT BS.BS_ID
							,Friendly_date(BS.Date) friendly_date
							,DATE_FORMAT(BS.Date, '%H:%i') Time
							,DAY(BS.Date) day
							,MONTH(BS.Date) month
							,USR_ShortName(BS.USR_ID) Worker
							,BL.Name Blank
							,BS.Amount
							,BS.Tariff
							,IF(BS.adj = 1, 'Коррекция', REPLACE(BS.Comment, '\r\n', '<br>')) Comment
							,BS.USR_ID
							,BL.BL_ID
							,IF(BLL.BLL_ID IS NULL, 'bold', '') Bold
							,USR_Icon(BS.author) Name
							,PBS.BS_ID is_parent
						FROM BlankStock BS
						LEFT JOIN BlankStock PBS ON PBS.PBS_ID = BS.BS_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = BS.BL_ID
						LEFT JOIN (
							SELECT BL.BL_ID, BLL.BLL_ID
							FROM BlankList BL
							LEFT JOIN BlankLink BLL ON BLL.BLL_ID = BL.BL_ID
							GROUP BY BL.BL_ID
						) BLL ON BLL.BL_ID = BL.BL_ID
						WHERE DATEDIFF(NOW(), BS.Date) <= {$datediff} AND BS.Amount <> 0 AND BS.PBS_ID IS NULL
						GROUP BY BS.BS_ID
						ORDER BY BS.Date DESC, BS.BS_ID";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				$color = ($row["Amount"] < 0) ? "#E74C3C" : "#16A085";
				$html .= "
					<tr class='".($row["is_parent"] ? "is_parent" : "")."'>
					<td>".($row["is_parent"] ? "<i class='fa fa-arrow-right'></i>" : "")."</td>
					<td><b class='nowrap'>{$row["friendly_date"]}</b></td>
					<td>{$row["Time"]}</td>
					<td class='worker nowrap' val='{$row["USR_ID"]}'><span><a href='/paylog.php?worker={$row["USR_ID"]}'>{$row["Worker"]}</a></span></td>
					<td class='blank {$row["Bold"]} nowrap' val='{$row["BL_ID"]}'><span>{$row["Blank"]}</span></td>
					<td class='amount txtright'><b style='font-size: 1.2em; color: {$color};'>{$row["Amount"]}</b></td>
					<td class='tariff txtright'>{$row["Tariff"]}</td>
					<td class='comment nowrap'><span>{$row["Comment"]}</span></td>
					<td>{$row["Name"]}</td>
					</tr>
				";
				if( $row["is_parent"] ) {
					$query = "SELECT GROUP_CONCAT(IFNULL(USR_ShortName(BS.USR_ID), 'Без работника') SEPARATOR '<br>') Worker
									,GROUP_CONCAT(BL.Name SEPARATOR '<br>') Blank
									,GROUP_CONCAT(BS.Amount SEPARATOR '<br>') Amount
									,MAX(BS.Amount) max_amount
								FROM BlankStock BS
								LEFT JOIN BlankList BL ON BL.BL_ID = BS.BL_ID
								WHERE BS.PBS_ID = {$row["BS_ID"]}";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subrow = mysqli_fetch_array($subres) )
					{
						$color = ($subrow["max_amount"] < 0) ? "#E74C3C" : "#16A085";
						$html .= "
							<tr class='auto_record'>
							<td></td>
							<td></td>
							<td></td>
							<td class='nowrap'><span>{$subrow["Worker"]}</span></td>
							<td class='nowrap'><span>{$subrow["Blank"]}</span></td>
							<td class='amount txtright'><b style='font-size: 1.2em; color: {$color};'>{$subrow["Amount"]}</b></td>
							<td class='tariff txtright'></td>
							<td class='comment'><pre></pre></td>
							<td></td>
							</tr>
						";
					}
				}
			}
	$html .= "
			</tbody>
		</table>
	";
	$html = addslashes($html);					// Экранируем кавычки
	$html = str_replace(chr(13), '', $html);	// Убираем переносы строк
	$html = str_replace(chr(10), '', $html);	// Убираем переносы строк
	echo "$('.log-blank').html('{$html}');";
	echo "$('.log-blank').hide().fadeIn();";	// Эффект появления

	break;
/////////////////////////////////////////////////////////////////////

// Удаление набора
case "order_del":
	$od_id = $_GET["od_id"];
	$ord_scr = $_GET["ord_scr"];

	// Проверяем права на удаление набора
	if( !in_array('order_add', $Rights) ) {
		echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
	}
	else {
		// Узнаем есть ли оплата по этому набору
		$query = "SELECT IFNULL((SELECT SUM(payment_sum) FROM OrdersPayment WHERE OD_ID = {$od_id}), 0) order_payments";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$order_payments = mysqli_result($res,0,'order_payments');

		// Если оплата есть, то сообщаем об этом иначе удаляем набор
		if( $order_payments ) {
			// Узнаем город набора, год и месяц продажи
			$query = "
				SELECT SH.CT_ID
					,IFNULL(YEAR(OD.StartDate), 0) start_year
					,IFNULL(MONTH(OD.StartDate), 0) start_month
				FROM OrdersData OD
				LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				WHERE OD_ID = {$od_id}
			";
			$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			$CT_ID = mysqli_result($res,0,'CT_ID');
			$start_year = mysqli_result($res,0,'start_year');
			$start_month = mysqli_result($res,0,'start_month');

			$selling_link = "/selling.php?CT_ID={$CT_ID}&year={$start_year}&month={$start_month}#ord{$od_id}";
			echo "noty({text: 'Набор оплачен! Перейдите в <b><a href=\"{$selling_link}\" target=\"_blank\">реализацию</a></b> и запишите возврат платежа. Затем повторите попытку удаления.', type: 'alert'});";
		}
		else {
			$query = "UPDATE OrdersData SET DelDate = NOW(), author = {$_SESSION['id']} WHERE OD_ID={$od_id}";
			mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
			// Если на экране заказа - перезагружаем страницу, иначе скрываем запись
			if ($ord_scr == 1) {
				$_SESSION["success"][] = "Набор удален!";
				echo "location.reload();";
			}
			else {
				echo "$('.main_table #ord{$od_id}').hide('slow');";
				echo "noty({timeout: 3000, text: 'Набор удален!', type: 'success'});";
			}
		}
	}

	break;
/////////////////////////////////////////////////////////////////////

// Восстановление удаленного набора
case "order_del_undo":
	$od_id = $_GET["od_id"];
	$ord_scr = $_GET["ord_scr"];

	// Проверяем права
	if( !in_array('order_add', $Rights) ) {
		echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
	}
	else {
		$query = "UPDATE OrdersData SET DelDate = NULL, author = {$_SESSION['id']} WHERE OD_ID={$od_id}";
		mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		// Если на экране заказа - перезагружаем страницу, иначе скрываем запись
		if ($ord_scr == 1) {
			$_SESSION["success"][] = "Набор восстановлен!";
			echo "location.reload();";
		}
		else {
			echo "$('.main_table #ord{$od_id}').hide('slow');";
			echo "noty({timeout: 3000, text: 'Набор восстановлен!', type: 'success'});";
		}
	}

	break;
/////////////////////////////////////////////////////////////////////

// Отгрузка набора
case "order_shp":
	$od_id = $_GET["od_id"];

	// Проверяем права на отгрузку набора
	if( !in_array('order_ready', $Rights) ) {
		echo "noty({timeout: 3000, text: 'Недостаточно прав для совершения операции!', type: 'error'});";
	}
	else {
		$query = "UPDATE OrdersData SET ReadyDate = NOW(), author = {$_SESSION['id']} WHERE OD_ID={$od_id}";
		mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		echo "$('.main_table #ord{$od_id}').hide('slow');";
		echo "noty({timeout: 3000, text: 'Набор успешно отгружен!', type: 'success'});";

		// Если это розничный набор, то предлагаем перейти в реализацию
		$query = "
			SELECT IFNULL(SH.retail, 0) retail
				,SH.CT_ID
				,IFNULL(YEAR(OD.StartDate), 0) start_year
				,IFNULL(MONTH(OD.StartDate), 0) start_month
			FROM OrdersData OD
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE OD_ID = {$od_id}
		";
		$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
		$retail = mysqli_result($res,0,'retail');
		if( $retail == "1" ) {
			$CT_ID = mysqli_result($res,0,'CT_ID');
			$start_year = mysqli_result($res,0,'start_year');
			$start_month = mysqli_result($res,0,'start_month');
			$selling_link = "/selling.php?CT_ID={$CT_ID}&year={$start_year}&month={$start_month}#ord{$od_id}";
			echo "noty({text: 'Проверить <b><a href=\"{$selling_link}\" target=\"_blank\">реализацию</a></b>?', type: 'alert'});";
		}
	}

	break;
/////////////////////////////////////////////////////////////////////

// Отмена отгрузки набора (НЕ АЯКС)
case "order_undo_shp":
	$od_id = $_GET["od_id"];

	// Проверяем права на отгрузку набора
	if( in_array('order_ready', $Rights) ) {
		$query = "UPDATE OrdersData SET ReadyDate = NULL, SHP_ID = NULL, author = {$_SESSION['id']} WHERE OD_ID={$od_id}";
		if (mysqli_query( $mysqli, $query )) {
			$_SESSION["success"][] = "Набор возвращен на производство!";
			exit ('<meta http-equiv="refresh" content="0; url=orderdetail.php?id='.$od_id.'">');
		}
		else {
			$_SESSION['error'][] = mysqli_error( $mysqli );
			exit ('<meta http-equiv="refresh" content="0; url=orderdetail.php?id='.$od_id.'">');
		}
	}
	else {
		$_SESSION['error'][] = "Недостаточно прав для совершения операции!";
		exit ('<meta http-equiv="refresh" content="0; url=/?archive=2#ord'.$od_id.'">');
	}

	break;
/////////////////////////////////////////////////////////////////////

// Получаем информацию по изделию чтобы заполнить форму для редактирования
case "odd_data":
	$odd_id = $_GET["id"];

	$query = "
		SELECT ODD.ODD_ID
			,ODD.Amount
			,ODD.Price
			,IFNULL(ODD.PM_ID, 0) PM_ID
			,ODD.BL_ID
			,ODD.Other
			,ODD.PVC_ID
			,ODD.sidebar
			,PM.Model
			,ODD.PF_ID
			,ODD.PME_ID
			,ODD.box
			,ODD.Length
			,ODD.Width
			,ODD.PieceAmount
			,ODD.PieceSize
			,ODD.piece_stored
			,ODD.Comment
			,IFNULL(MT.Material, '') Material
			,IFNULL(MT.SH_ID, '') Shipper
			,ODD.IsExist
			,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
			,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
			,IF(SUM(ODS.USR_ID) IS NULL, 0, 1) inprogress
			,IFNULL(ODD.P_ID, 0) P_ID
			,SH.mtype
		FROM OrdersDataDetail ODD
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
		LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
		WHERE ODD.ODD_ID = {$odd_id}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$odd_data = array( "amount"=>$row["Amount"], "price"=>$row["Price"], "model"=>$row["PM_ID"], "blank"=>$row["BL_ID"], "other"=>$row["Other"], "PVC_ID"=>$row["PVC_ID"], "sidebar"=>$row["sidebar"], "model_name"=>$row["Model"], "form"=>$row["PF_ID"], "mechanism"=>$row["PME_ID"], "box"=>$row["box"], "length"=>$row["Length"], "width"=>$row["Width"], "PieceAmount"=>$row["PieceAmount"], "PieceSize"=>$row["PieceSize"], "piece_stored"=>$row["piece_stored"], "color"=>$row["Color"], "comment"=>$row["Comment"], "material"=>$row["Material"], "shipper"=>$row["Shipper"], "isexist"=>$row["IsExist"], "inprogress"=>$row["inprogress"], "order_date"=>$row["order_date"], "arrival_date"=>$row["arrival_date"], "P_ID"=>$row["P_ID"], "mtype"=>$row["mtype"] );
	}

	echo json_encode($odd_data);

	break;
/////////////////////////////////////////////////////////////////////
}
?>
