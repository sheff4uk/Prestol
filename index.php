<?
//	session_start();
	include "config.php";

	$datediff = 60; // Максимальный период отображения данных
	
	$location = $_SERVER['REQUEST_URI'];
	
	// Добавление в базу нового заказа
	if( isset($_POST["StartDate"]) )
	{
		$StartDate = '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'';
		$EndDate = $_POST["EndDate"] ? '\''.date( "Y-m-d", strtotime($_POST["EndDate"]) ).'\'' : "NULL";
		$ClientName = mysqli_real_escape_string( $mysqli, $_POST["ClientName"] );
		$Shop = $_POST["Shop"] > 0 ? $_POST["Shop"] : "NULL";
		$OrderNumber = mysqli_real_escape_string( $mysqli, $_POST["OrderNumber"] );
		$Color = mysqli_real_escape_string( $mysqli, $_POST["Color"] );
		$Comment = mysqli_real_escape_string( $mysqli, $_POST["Comment"] );
		// Удаляем лишние пробелы
		$ClientName = trim($ClientName);
		$OrderNumber = trim($OrderNumber);
		$Color = trim($Color);
		$Comment = trim($Comment);
		$query = "INSERT INTO OrdersData(CLientName, StartDate, EndDate, SH_ID, OrderNumber, Color, Comment)
				  VALUES ('{$ClientName}', $StartDate, $EndDate, $Shop, '{$OrderNumber}', '{$Color}', '{$Comment}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		
		// Перенаправление на экран деталей заказа
		$id = mysqli_insert_id( $mysqli );
//		header( "Location: orderdetail.php?id=".$id );
		exit ('<meta http-equiv="refresh" content="0; url=/orderdetail.php?id='.$id.'">');
		die;
	}

	// Удаление заказа
	if( $_GET["del"] )
	{
		$id = (int)$_GET["del"];

//		$query = "DELETE FROM OrdersData WHERE OD_ID={$id}";
		$query = "UPDATE OrdersData SET Del = 1 WHERE OD_ID={$id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		header( "Location: /" ); // Перезагружаем экран
		die;
	}

	// Подтверждение готовности заказа
	if( $_GET["ready"] )
	{
		$id = (int)$_GET["ready"];
		$date = date("Y-m-d");
		$query = "UPDATE OrdersData SET ReadyDate = '{$date}' WHERE OD_ID={$id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		header( "Location: /" ); // Перезагружаем экран
		die;
	}

	$title = 'Престол главная';
	include "header.php";
//	include "autocomplete.php"; //JavaScript
?>
	
	<div id="overlay"></div>
	<? include "forms.php"; ?>

	<p>
		<?
		if( $archive == 1 )
		{
			echo "<a href='/' class='button'>В работе</a>";
		}
		else
		{
			echo "<a href='?archive=1' class='button'>Готовые</a>";
		}
		?>
	</p>

	<!-- Форма добавления заказа -->
	<div id='order_form' class='addproduct' title='Новый заказ' style='display:none;'>
		<form method='post'>
			<fieldset>
				<div>
					<label>Заказчик:</label>
					<input type='text' name='ClientName' size='38'>
				</div>
				<div>
					<label>Дата приема:</label>
					<input required type='text' name='StartDate' class='date from' size='12' value='<?= date("d.m.Y") ?>' autocomplete='off' readonly>
				</div>
				<div>
					<label>Дата сдачи:</label>
					<input type='text' name='EndDate' class='date to' size='12' autocomplete='off' readonly>
				</div>
				<div>
					<label>Салон:</label>
					<select required name='Shop' style="width: 150px;">
						<option value="">-=Выберите салон=-</option>
						<option value="0" style="background: #999;">Свободные</option>
						<?
						$query = "SELECT Shops.SH_ID
										,CONCAT(Cities.City, '/', Shops.Shop) AS Shop
										,Cities.Color
									FROM Shops
									JOIN Cities ON Cities.CT_ID = Shops.CT_ID
									ORDER BY Cities.City, Shops.Shop";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["SH_ID"]}' style='background: {$row["Color"]};'>{$row["Shop"]}</option>";
						}
						?>
					</select>
				</div>
				<div>
					<label>№ квитанции:</label>
					<input type='text' name='OrderNumber' autocomplete='off'>
				</div>
				<div>
					<label>Цвет:</label>
					<input required type='text' class='colortags' name='Color' size='38' autocomplete='off'>
				</div>
				<div>
					<label>Примечание:</label>
					<textarea name='Comment' rows='3' cols='38'></textarea>
				</div>
			</fieldset>
			<div>
				<hr>
				<input type='submit' value='Создать' style='float: right;'>
			</div>
		</form>
	</div>
	
	<div id="add_btn" title="Добавить новый заказ"></div> <!-- Кнопка добавления заказа -->
	
	<div id="print_btn" href="#print_tbl" class="open_modal" title="Распечатать таблицу"> <!-- Кнопка печати -->
		<a id="toprint"></a>
	</div>
	<!-- ФИЛЬТР ГЛАВНОЙ ТАБЛИЦЫ -->
	<table class="main_table">
		<form method='get' action='filter.php'>
		<thead>
		<tr>
			<th width="45"><input type='text' name='f_CD' size='8' value='<?= $_SESSION["f_CD"] ?>' class='<?=($_SESSION["f_CD"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="5%"><input type='text' name='f_CN' size='8' value='<?= $_SESSION["f_CN"] ?>' class='<?=($_SESSION["f_CN"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="5%"><input type='text' name='f_SD' size='8' value='<?= $_SESSION["f_SD"] ?>' class='<?=($_SESSION["f_SD"] != "") ? "filtered" : ""?>'></th>
			<th width="5%"><input type='text' name='f_ED' size='8' value='<?= $_SESSION["f_ED"] ?>' class='<?=($_SESSION["f_ED"] != "") ? "filtered" : ""?>'></th>
			<th width="5%"><input type='text' name='f_SH' size='8' class='shopstags <?=($_SESSION["f_SH"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_SH"] ?>'></th>
			<th width="5%"><input type='text' name='f_ON' size='8' value='<?= $_SESSION["f_ON"] ?>' class="<?=($_SESSION["f_ON"] != "") ? "filtered" : ""?>"></th>
			<th width="15%"><input type='text' name='f_Z' value='<?= $_SESSION["f_Z"] ?>' class="<?=($_SESSION["f_Z"] != "") ? "filtered" : ""?>"></th>
			<th width="15%"><input type='text' name='f_P' size='8' class='plastictags <?=($_SESSION["f_P"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_P"] ?>'></th>
			<th width="15%"><input type='text' name='f_CR' size='8' class='colortags <?=($_SESSION["f_CR"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_CR"] ?>'></th>
			<th width="10%"><input type='text' name='f_PR' size='8' class='workerstags <?=($_SESSION["f_PR"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_PR"] ?>'></th>
			<th width="40">
				<select name="f_X" style="width: 100%;" onchange="this.form.submit()" class="<?=($_SESSION["f_X"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="1" <?= ($_SESSION["f_X"] == 1) ? 'selected' : '' ?>>X</option>
				</select>
			</th>
			<th width="45">
				<style>
					.IsPainting {
						width: 100%;
						font-family: FontAwesome;
					}
				</style>
				<select name="f_IP" class="IsPainting <?=($_SESSION["f_IP"] != "") ? "filtered" : ""?>" onchange="this.form.submit()">
					<option></option>
					<option value="1" <?= ($_SESSION["f_IP"] == 1) ? 'selected' : '' ?> class="notready">&#xf006 - Не в работе</option>
					<option value="2" <?= ($_SESSION["f_IP"] == 2) ? 'selected' : '' ?> class="inwork">&#xf123 - В работе</option>
					<option value="3" <?= ($_SESSION["f_IP"] == 3) ? 'selected' : '' ?> class="ready">&#xf005 - Готово</option>
				</select>
			</th>
			<th width="15%"><input type='text' name='f_T' size='8' class='textiletags <?=($_SESSION["f_T"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_T"] ?>'></th>
			<th width="15%"><input type='text' name='f_N' value='<?= $_SESSION["f_N"] ?>' class="<?=($_SESSION["f_N"] != "") ? "filtered" : ""?>"></th>
			<th width="80"><button title="Фильтр"><i class="fa fa-filter fa-lg"></i></button><a href="filter.php?location=<?=$location?>" class="button" title="Сброс"><i class="fa fa-times fa-lg"></i></a><input type='hidden' name='location' value='<?=$location?>'></th>
		</tr>
		</thead>
		</form>
	</table>
	<!-- //ФИЛЬТР ГЛАВНОЙ ТАБЛИЦЫ -->
<div id="print_tbl">
	<!-- Главная таблица -->
	<form id='printtable'>
	<div class="wr_main_table_head"> <!-- Обертка шапки -->
	<table class="main_table">
		<input type="text" id="print_title" name="print_title" placeholder="Введите заголовок таблицы">
		<thead>
		<tr>
			<th width="45"><input type="checkbox" disabled value="1" checked name="CD" class="print_col" id="CD"><label for="CD">Код</label></th>
			<th width="5%"><input type="checkbox" disabled value="2" checked name="CN" class="print_col" id="CN"><label for="CN">Заказчик</label></th>
			<th width="5%"><input type="checkbox" disabled value="3" checked name="SD" class="print_col" id="SD"><label for="SD">Дата<br>приема</label></th>
			<th width="5%"><input type="checkbox" disabled value="4" checked name="ED" class="print_col" id="ED"><label for="ED">Дата<br>сдачи</label></th>
			<th width="5%"><input type="checkbox" disabled value="5" checked name="SH" class="print_col" id="SH"><label for="SH">Салон</label></th>
			<th width="5%"><input type="checkbox" disabled value="6" checked name="ON" class="print_col" id="ON"><label for="ON">№<br>квитанции</label></th>
			<th width="15%"><input type="checkbox" disabled value="7" checked name="Z" class="print_col" id="Z"><label for="Z">Заказ</label></th>
			<th width="15%"><input type="checkbox" disabled value="8" checked name="P" class="print_col" id="P"><label for="P">Пластик</label></th>
			<th width="15%"><input type="checkbox" disabled value="9" checked name="CR" class="print_col" id="CR"><label for="CR">Цвет<br>краски</label></th>
			<th width="10%"><input type="checkbox" disabled value="10" checked name="PR" class="print_col" id="PR"><label for="PR">Этапы</label></th>
			<th width="40"><input type="checkbox" disabled value="11" checked name="X" class="print_col" id="X"><label for="X">X</label></th>
			<th width="45"><input type="checkbox" disabled value="12" checked name="IP" class="print_col" id="IP"><label for="IP">Лакировка</label></th>
			<th width="15%"><input type="checkbox" disabled value="13" checked name="T" class="print_col" id="T"><label for="T">Ткань</label></th>
			<th width="15%"><input type="checkbox" disabled value="14" checked name="N" class="print_col" id="N"><label for="N">Примечание</label></th>
			<th width="80">Действие</th>
		</tr>
		</thead>
	</table>
	</div>
	<div class="wr_main_table_body"> <!-- Обертка тела таблицы -->
	<table class="main_table">
		<thead style="">
		<tr>
			<th width="45"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="15%"></th>
			<th width="15%"></th>
			<th width="15%"></th>
			<th width="10%"></th>
			<th width="40"></th>
			<th width="45"></th>
			<th width="15%"></th>
			<th width="15%"></th>
			<th width="80"></th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "SELECT OD.OD_ID
					,OD.Code
					,IFNULL(OD.ClientName, '') ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
					,DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') ReadyDate
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,OD.OrderNumber
					,OD.Comment
					,COUNT(ODD.ODD_ID) + COUNT(ODB.ODB_ID) Child
					,CONCAT(IFNULL(GROUP_CONCAT(DISTINCT CONCAT('<a href=\'#\' id=\'', ODD.ODD_ID, '\' location=\'{$location}\' class=\'button edit_product', IFNULL(PM.PT_ID, 2), '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, '***'), ' ', IFNULL(CONCAT(ODD.Length, 'х', ODD.Width, IFNULL(CONCAT('/', ODD.PieceAmount, 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', '</a><br>') ORDER BY IFNULL(PM.PT_ID, 2) DESC, ODD.ODD_ID SEPARATOR ''), ''), IFNULL(GROUP_CONCAT(DISTINCT CONCAT('<a href=\'#\' id=\'', ODB.ODB_ID, '\'', 'class=\'button edit_order_blank\' location=\'{$location}\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', BL.Name, '</a><br>') ORDER BY ODB.ODB_ID SEPARATOR ''), '')) Zakaz
					,OD.Color
					,OD.IsPainting
					,CONCAT(IFNULL(GROUP_CONCAT(DISTINCT CONCAT(IF(PM.PT_ID = 1 AND DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span id=\'t', ODD.ODD_ID, '\' class=\'',
						CASE ODD.IsExist
							WHEN 0 THEN 'bg-red'
							WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
							WHEN 2 THEN 'bg-green'
						END,
					'\'>', IF(PM.PT_ID = 1, IFNULL(ODD.Material, ''), ''), '</span><br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR ''), ''), IFNULL(GROUP_CONCAT(DISTINCT CONCAT('<i id=\'t', ODB.ODB_ID, '\'></i><br>') ORDER BY ODB.ODB_ID SEPARATOR ''), '')) Textile
					,CONCAT(IFNULL(GROUP_CONCAT(DISTINCT CONCAT(IF(IFNULL(PM.PT_ID, 2) = 2 AND DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span id=\'p', ODD.ODD_ID, '\' class=\'',
						CASE ODD.IsExist
							WHEN 0 THEN 'bg-red'
							WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
							WHEN 2 THEN 'bg-green'
						END,
					'\'>', IF(IFNULL(PM.PT_ID, 2) = 2, IFNULL(ODD.Material, ''), ''), '</span><br>') ORDER BY IFNULL(PM.PT_ID, 2) DESC, ODD.ODD_ID SEPARATOR ''), ''), IFNULL(GROUP_CONCAT(DISTINCT CONCAT('<i id=\'p', ODB.ODB_ID, '\'></i><br>') ORDER BY ODB.ODB_ID SEPARATOR ''), '')) Plastic
					,GROUP_CONCAT(CONCAT(ODS_WD.Name, '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Workers
					,IF(DATEDIFF(OD.EndDate, NOW()) <= 7, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
					,BIT_AND(ODS_WD.IsReady) IsReady
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  LEFT JOIN OrdersDataBlank ODB ON ODB.OD_ID = OD.OD_ID
			  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
			  LEFT JOIN (SELECT ODS.ODD_ID, GROUP_CONCAT(WD.Name) Name, BIT_AND(ODS.IsReady) IsReady
						FROM OrdersDataSteps ODS
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						WHERE ODS.Visible = 1
						GROUP BY ODS.ODD_ID) ODS_WD ON ODS_WD.ODD_ID = ODD.ODD_ID
			  WHERE OD.Del = 0";
			  if( $archive ) {
				  $query .= " AND OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}";
			  }
			  else {
				  $query .= " AND OD.ReadyDate IS NULL";
			  }
			  if( $_SESSION["f_CD"] != "" ) {
				  $query .= " AND OD.Code LIKE '%{$_SESSION["f_CD"]}%'";
			  }
			  if( $_SESSION["f_CN"] != "" ) {
				  $query .= " AND OD.ClientName LIKE '%{$_SESSION["f_CN"]}%'";
			  }
			  if( $_SESSION["f_SD"] != "" ) {
				  $query .= " AND DATE_FORMAT(OD.StartDate, '%d.%m.%Y') LIKE '%{$_SESSION["f_SD"]}%'";
			  }
			  if( $_SESSION["f_ED"] != "" ) {
				  if( $archive ) {
				  	$query .= " AND DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') LIKE '%{$_SESSION["f_ED"]}%'";
				  }
				  else {
				  	$query .= " AND DATE_FORMAT(OD.EndDate, '%d.%m.%Y') LIKE '%{$_SESSION["f_ED"]}%'";
				  }
			  }
			  if( $_SESSION["f_SH"] != "" ) {
				  $query .= " AND (CONCAT(CT.City, '/', SH.Shop) LIKE '%{$_SESSION["f_SH"]}%'";
				  if( stripos("Свободные", $_SESSION["f_SH"]) !== false ) {
					  $query .= " OR OD.SH_ID IS NULL";
				  }
				  $query .= ")";
			  }
			  if( $_SESSION["f_ON"] != "" ) {
				  $query .= " AND OD.OrderNumber LIKE '%{$_SESSION["f_ON"]}%'";
			  }
			  if( $_SESSION["f_N"] != "" ) {
				  $query .= " AND OD.Comment LIKE '%{$_SESSION["f_N"]}%'";
			  }
			  if( $_SESSION["f_IP"] != "" ) {
				  $query .= " AND OD.IsPainting = {$_SESSION["f_IP"]}";
			  }
			  if( $_SESSION["f_CR"] != "" ) {
				  $query .= " AND OD.Color LIKE '%{$_SESSION["f_CR"]}%'";
			  }
			  $query .= " GROUP BY OD.OD_ID HAVING TRUE";
			  if( $_SESSION["f_Z"] != "" ) {
				  $query .= " AND Zakaz LIKE '%{$_SESSION["f_Z"]}%'";
			  }
			  if( $_SESSION["f_T"] != "" ) {
				  $query .= " AND Textile LIKE '%{$_SESSION["f_T"]}%'";
			  }
			  if( $_SESSION["f_P"] != "" ) {
				  $query .= " AND Plastic LIKE '%{$_SESSION["f_P"]}%'";
			  }
			  if( $_SESSION["f_PR"] != "" ) {
				  $query .= " AND Workers LIKE '%{$_SESSION["f_PR"]}%'";
			  }
			  if( $_SESSION["f_X"] == "1" ) {
				  $X_ord = '0';
				  foreach( $_SESSION as $k => $v)
				  {
					  if( strpos($k,"X_") === 0 )
					  {
						  $X_ord .= ','.str_replace( "X_", "", $k );
					  }
				  }
				  $query .= " AND OD.OD_ID IN ({$X_ord})";
			  }
              $query .= " ORDER BY OD.OD_ID";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr id='ord{$row["OD_ID"]}'>";
		echo "<td><span class='nowrap'>{$row["Code"]}</span></td>";
		echo "<td><span><input type='checkbox' value='1' checked name='order{$row["OD_ID"]}' class='print_row' id='n{$row["OD_ID"]}'><label for='n{$row["OD_ID"]}'>></label>{$row["ClientName"]}</span></td>";
		echo "<td><span>{$row["StartDate"]}</span></td>";
		if( $archive ) {
			echo "<td><span>{$row["ReadyDate"]}</span></td>";
		}
		else {
			echo "<td><span><span class='{$row["Deadline"]}'>{$row["EndDate"]}</span></span></td>";
		}
		echo "<td><span style='background: {$row["CTColor"]};'>{$row["Shop"]}</span></td>";
		echo "<td><span>{$row["OrderNumber"]}</span></td>";
		echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		echo "<td><span class='nowrap material'>{$row["Plastic"]}</span></td>";
		echo "<td><span>{$row["Color"]}</span></td>";
		
		// Получаем данные по этамам производства
		$query = "SELECT IFNULL(PM.PT_ID, 2) PT_ID
						,ODD.ODD_ID itemID
						,CONCAT('<a href=\'#\' id=\'', ODD.ODD_ID, '\' class=\'edit_steps\' location=\'{$location}\'>', GROUP_CONCAT(CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>') ORDER BY ST.Sort SEPARATOR ''), '</a><br>') Steps
					FROM OrdersDataDetail ODD
					LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
					LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
					WHERE ODD.OD_ID = {$row["OD_ID"]}
					GROUP BY ODD.ODD_ID
					UNION
					SELECT 0 PT_ID, ODB.ODB_ID itemID, '<br>' Steps
					FROM OrdersDataBlank ODB
					WHERE ODB.OD_ID = {$row["OD_ID"]}
					ORDER BY PT_ID DESC, itemID";
		$sub_res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$steps = "";
		while( $sub_row = mysqli_fetch_array($sub_res) )
		{
			$steps .= $sub_row["Steps"];
		}

		echo "<td><span class='nowrap material'>{$steps}</span></td>";
		$checkedX = $_SESSION["X_".$row["OD_ID"]] == 1 ? 'checked' : '';
		echo "<td class='X'><input type='checkbox' {$checkedX} value='1'></td>";
		echo "<td val='{$row["IsPainting"]}'";
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
		echo " class='painting {$class}' title='{$title}'></td>";
		echo "<td><span class='nowrap material'>{$row["Textile"]}</span></td>";
		echo "<td><span>{$row["Comment"]}</span></td>";
		echo "<td><a href='./orderdetail.php?id={$row["OD_ID"]}' class='button' title='Редактировать'><i class='fa fa-pencil fa-lg'></i></a> ";
		if( $row["Child"] ) // Если заказ не пустой
		{
			if( $row["IsReady"] && $row["IsPainting"] == 3 && $archive != 1)
			{
				//echo "<a href='?ready={$row["OD_ID"]}' class='button' onclick='if(confirm(\"Пожалуйста, подтвердите готовность заказа!\")) return true; return false;' title='Готово'><i style='color:red;' class='fa fa-flag-checkered fa-lg'></i></a> ";
				echo "<a class='button' onclick='if(confirm(\"Пожалуйста, подтвердите готовность заказа!\", \"?ready={$row["OD_ID"]}\")) return false;' title='Готово'><i style='color:red;' class='fa fa-flag-checkered fa-lg'></i></a> ";
			}
		}
		else
		{
			//echo "<a href='?del={$row["OD_ID"]}' class='button' onclick='if(confirm(\"Удалить?\")) return true; return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
			echo "<a class='button' onclick='if(confirm(\"Удалить?\", \"?del={$row["OD_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
		}
		echo "</td></tr>";

		// Заполнение массива для JavaScript
		$query = "SELECT ODD.ODD_ID
						,ODD.Amount
						,ODD.PM_ID
						,ODD.PF_ID
						,ODD.PME_ID
						,ODD.Length
						,ODD.Width
						,ODD.PieceAmount
						,ODD.PieceSize
						,ODD.Color
						,ODD.Comment
						,ODD.Material
						,ODD.IsExist
                        ,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
                        ,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
						,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
				  FROM OrdersDataDetail ODD
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
				  WHERE ODD.OD_ID = {$row["OD_ID"]}
				  GROUP BY ODD.ODD_ID";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $sub_row = mysqli_fetch_array($result) )
		{
			$ODD[$sub_row["ODD_ID"]] = array( "amount"=>$sub_row["Amount"], "model"=>$sub_row["PM_ID"], "form"=>$sub_row["PF_ID"], "mechanism"=>$sub_row["PME_ID"], "length"=>$sub_row["Length"], "width"=>$sub_row["Width"], "PieceAmount"=>$sub_row["PieceAmount"], "PieceSize"=>$sub_row["PieceSize"], "color"=>$sub_row["Color"], "comment"=>$sub_row["Comment"], "material"=>$sub_row["Material"], "isexist"=>$sub_row["IsExist"], "inprogress"=>$sub_row["inprogress"], "order_date"=>$sub_row["order_date"], "arrival_date"=>$sub_row["arrival_date"] );
		}

		$query = "SELECT ODB.ODB_ID, ODB.Amount, ODB.BL_ID, ODB.Comment
				  FROM OrdersDataBlank ODB
				  WHERE ODB.OD_ID = {$row["OD_ID"]}";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $sub_row = mysqli_fetch_array($result) )
		{
			$ODB[$sub_row["ODB_ID"]] = array( "amount"=>$sub_row["Amount"], "blank"=>$sub_row["BL_ID"], "comment"=>$sub_row["Comment"] );
		}
	}
?>
	</tbody>
	</table>
	</div>
	</form>
</div>
</body>
</html>

<script>
	$(document).ready(function(){

		// Фильтрация таблицы при автокомплите
		$( ".main_table .shopstags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .plastictags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .colortags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .workerstags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .textiletags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});

		// Открытие диалога печати
		$("#toprint").printPage();

		$(function() {
			// Кнопка добавления заказа
			$('#add_btn').click( function() {
				$('#order_form').dialog({
					width: 500,
					modal: true,
					show: 'blind',
					hide: 'explode',
				});

				// Автокомплит поверх диалога
				$( ".colortags" ).autocomplete( "option", "appendTo", "#order_form" );

				return false;
			});

			$('.print_col, .print_row').change( function() { changelink(); });

			$('#print_btn').click( function() { changelink(); });
			$('#print_title').change( function() { changelink(); });
		});

		$('.painting').click(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			var val = $(this).attr('val');
			$.ajax({ url: "ajax.php?do=ispainting&od_id="+id+"&val="+val, dataType: "script", async: false });
		});

		$('.X input[type="checkbox"]').change(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			if ( this.checked ) {
				var val = 1;
			}
			else {
				var val = 0;
			}
			$.ajax({ url: "ajax.php?do=Xlabel&od_id="+id+"&val="+val, dataType: "script", async: false });
		});

		function changelink() { // Добавляем к ссылке печати столбцы и строки которые будем печатать
			var data = $('#printtable').serialize();
			$("#toprint").attr('href', '/toprint/main.php?' + data);
			return false;
		}

		odd = <?= json_encode($ODD) ?>;
		odb = <?= json_encode($ODB) ?>;
	});
</script>
