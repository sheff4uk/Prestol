<?
	include "config.php";
	$title = 'Престол главная';
	include "header.php";

	$datediff = 60; // Максимальный период отображения данных
	
	$location = $_SERVER['REQUEST_URI'];
	$_SESSION["location"] = $location;
	
	// Формируем выпадающее меню салонов в таблицу
	$query = "SELECT Shops.SH_ID
					,CONCAT(Cities.City, '/', Shops.Shop) AS Shop
					,Cities.Color
				FROM Shops
				JOIN Cities ON Cities.CT_ID = Shops.CT_ID
				WHERE Cities.CT_ID IN ({$USR_cities}) OR Shops.SH_ID IN ({$USR_shops})
				ORDER BY Cities.City, Shops.Shop";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$select_shops = "<select class='select_shops'>";
	$select_shops .= "<option value='0' style='background: #999;'>Свободные</option>";
	while( $row = mysqli_fetch_array($res) ) {
		$select_shops .= "<option value='{$row["SH_ID"]}' style='background: {$row["Color"]};'>{$row["Shop"]}</option>";
	}
	$select_shops .= "</select>";
	$select_shops = addslashes($select_shops);

	// Добавление в базу нового заказа
	if( isset($_POST["Shop"]) )
	{
		if( !in_array('order_add', $Rights) ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$AddDate = date("Y-m-d");
		$StartDate = $_POST["StartDate"] ? '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'' : "NULL";
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
		$confirmed = in_array('order_add_confirm', $Rights) ? 1 : 0;
		$query = "INSERT INTO OrdersData(CLientName, AddDate, StartDate, EndDate, SH_ID, OrderNumber, Color, Comment, creator, confirmed)
				  VALUES ('{$ClientName}', '{$AddDate}', $StartDate, $EndDate, $Shop, '{$OrderNumber}', '{$Color}', '{$Comment}', {$_SESSION['id']}, {$confirmed})";
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
		if( !in_array('order_add', $Rights) ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$id = (int)$_GET["del"];

//		$query = "DELETE FROM OrdersData WHERE OD_ID={$id}";
		$query = "UPDATE OrdersData SET Del = 1, author = {$_SESSION['id']} WHERE OD_ID={$id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		//header( "Location: /" ); // Перезагружаем экран
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}

	// Подтверждение готовности заказа
	if( $_GET["ready"] )
	{
		if( !in_array('order_ready', $Rights) ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$id = (int)$_GET["ready"];
		$date = date("Y-m-d");
		$query = "UPDATE OrdersData SET ReadyDate = '{$date}', IsPainting = 3, author = {$_SESSION['id']} WHERE OD_ID={$id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		//header( "Location: /" ); // Перезагружаем экран
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}

	// Добавление отгрузки
	if( $_POST["CT_ID"] ) {
		//$shipping_date = date( 'Y-m-d', strtotime($_POST["shipping_date"]) );
		$shp_title = mysqli_real_escape_string( $mysqli, $_POST["shp_title"] );
		if( isset($_GET["shpid"]) ) {
			$query = "UPDATE Shipment SET title='{$shp_title}' WHERE SHP_ID = {$_GET["shpid"]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$query = "UPDATE OrdersData SET SHP_ID = NULL WHERE SHP_ID = {$_GET["shpid"]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$SHP_ID = $_GET["shpid"];
		}
		else {
			$query = "INSERT INTO Shipment SET title='{$shp_title}', CT_ID={$_POST["CT_ID"]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$SHP_ID = mysqli_insert_id( $mysqli );
		}

		foreach ($_POST["ord_sh"] as $key => $value) {
			$query = "UPDATE OrdersData SET SHP_ID = {$SHP_ID} WHERE OD_ID = {$value}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Перенаправление на экран этой отгрузки
		exit ('<meta http-equiv="refresh" content="0; url=/index.php?shpid='.$SHP_ID.'">');
		die;
	}

	// Сохранение даты отгрузки
	if( isset($_POST["shipping_date"]) ) {
		$shipping_date = $_POST[shipping_date] ? '\''.date( "Y-m-d", strtotime($_POST["shipping_date"]) ).'\'' : "NULL";
		// Записываем дату отгрузки в Shipping
		$query = "UPDATE Shipment SET shipping_date = {$shipping_date} WHERE SHP_ID = {$_GET["shpid"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Помечаем заказы как отгруженные
		$query = "UPDATE OrdersData SET ReadyDate = {$shipping_date}, IsPainting = 3, author = {$_SESSION['id']} WHERE SHP_ID = {$_GET["shpid"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Перенаправление на экран этой отгрузки
		exit ('<meta http-equiv="refresh" content="0; url=/index.php?shpid='.$_GET["shpid"].'">');
		die;
	}

	// Разделение заказа
	if( isset($_POST["prod_amount_left"]) ) {
		$OD_ID = $_POST["OD_ID"];
		$location = $_POST["location"];
		$left_sum = array_sum($_POST["prod_amount_left"]);
		$right_sum = array_sum($_POST["prod_amount_right"]);

		if( $left_sum != 0 and $right_sum != 0 ) {
			// Создание копии заказа
			$query = "INSERT INTO OrdersData(SHP_ID, Code, SH_ID, ClientName, AddDate, StartDate, EndDate, ReadyDate, OrderNumber, Color, IsPainting, Comment, Progress, IsReady, Del, creator, confirmed)
			SELECT SHP_ID, Code, SH_ID, ClientName, AddDate, StartDate, EndDate, ReadyDate, OrderNumber, Color, IsPainting, Comment, Progress, IsReady, Del, {$_SESSION['id']}, confirmed FROM OrdersData WHERE OD_ID = {$OD_ID}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$newOD_ID = mysqli_insert_id($mysqli);

			// Цикл по содержимому заказа (используются данные из формы)
			foreach ($_POST["itemID"] as $key => $value) {
				$left = $_POST["prod_amount_left"][$key];
				$right = $_POST["prod_amount_right"][$key];
				if( $left == 0 ) {
					if( $_POST["PT_ID"][$key] == 0 ) {
						$query = "UPDATE OrdersDataBlank SET OD_ID = {$newOD_ID} WHERE ODB_ID = {$value}";
					}
					else {
						$query = "UPDATE OrdersDataDetail SET OD_ID = {$newOD_ID} WHERE ODD_ID = {$value}";
					}
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
				elseif( $right > 0 ) {
					if( $_POST["PT_ID"][$key] == 0 ) {
						// Меняем количество изделий в исходном заказе
						$query = "UPDATE OrdersDataBlank SET Amount = {$left}, author = NULL WHERE ODB_ID = {$value}";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						// Вставляем в новый заказ переносимые изделия
						$query = "INSERT INTO OrdersDataBlank(OD_ID, BL_ID, Other, MT_ID, Amount, Comment, IsExist, order_date, arrival_date, Price, sister_ID, creator)
						SELECT {$newOD_ID}, BL_ID, Other, MT_ID, {$right}, Comment, IsExist, order_date, arrival_date, Price, {$value}, {$_SESSION['id']} FROM OrdersDataBlank WHERE ODB_ID = {$value}";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					}
					else {
						// Меняем количество изделий в исходном заказе
						$query = "UPDATE OrdersDataDetail SET Amount = {$left}, author = NULL WHERE ODD_ID = {$value}";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						// Вставляем в новый заказ переносимые изделия
						$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, PF_ID, PME_ID, Length, Width, PieceAmount, PieceSize, MT_ID, IsExist, Amount, Color, Comment, is_check, order_date, arrival_date, Price, sister_ID, creator)
						SELECT {$newOD_ID}, PM_ID, PF_ID, PME_ID, Length, Width, PieceAmount, PieceSize, MT_ID, IsExist, {$right}, Color, Comment, is_check, order_date, arrival_date, Price, {$value}, {$_SESSION['id']} FROM OrdersDataDetail WHERE ODD_ID = {$value}";
							mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					}
				}
			}
		}

		// Перенаправление на исходный экран
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		die;
	}
?>
	
	<div id="overlay"></div>
	<? include "forms.php"; ?>

	<?
	if( isset($_GET["shpid"]) ) {
		$query = "SELECT SHP.title, CT.CT_ID, CT.City, CT.Color, DATE_FORMAT(SHP.shipping_date, '%d.%m.%Y') shipping_date
					FROM Shipment SHP
					JOIN Cities CT ON CT.CT_ID = SHP.CT_ID
					WHERE SHP_ID = {$_GET["shpid"]}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		if( mysqli_num_rows($res) > 0 ) {
			$shipping_date = mysqli_result($res,0,'shipping_date');
			$CT_ID = mysqli_result($res,0,'CT_ID');
			$shp_title = mysqli_result($res,0,'title');
			$City = mysqli_result($res,0,'City');
			$Color = mysqli_result($res,0,'Color');

			// Проверка прав на город
			if( in_array('shipment_view_city', $Rights) and $CT_ID != $USR_City ) {
				die('Недостаточно прав для совершения операции');
			}

			echo "<h3>Отгрузка на <span style='background: {$Color};'>{$City}</span>".($shp_title != '' ? ' ('.$shp_title.')' : '')."</h3>";

			if( in_array('add_shipment', $Rights) ) {
				echo "<div id='wr_shipping_date'><form method='post'><label>Отгрузка состоялась: <input type='text' name='shipping_date' value='{$shipping_date}' class='date'></label><button style='margin-left: 10px;'>Сoхранить</button>";
				echo "<font style='display: none;' color='red'></font></form></div>";
			}
			else {
				echo "Дата отгрузки: {$shipping_date}<br>";
			}
			echo "<br>";

			// Вычисляем объем и количество коробок
			$query = "SELECT PT.PT_ID
							,ROUND(SUM(IFNULL(PMS.space, 0)), 2) space
							,CEIL(SUM(IFNULL(PMS.space, 0) / PT.box_space)) boxes
						FROM ProductTypes PT
						LEFT JOIN (
							SELECT PM.PT_ID, PM.space * ODD.Amount space
							FROM OrdersData OD
							JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
							JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
							WHERE OD.SHP_ID = {$_GET["shpid"]}
						) PMS ON PMS.PT_ID = PT.PT_ID
						GROUP BY PT.PT_ID
						ORDER BY PT.PT_ID";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$chair_space = mysqli_result($res,0,'space');
			$chair_boxes = mysqli_result($res,0,'boxes');
			$table_space = mysqli_result($res,1,'space');
			$table_boxes = mysqli_result($res,1,'boxes');
			echo "
				<div style='position: absolute; right: 200px; top: 60px; border: 1px solid #bbb; padding: 10px; border-radius: 10px;'>
					<b class='nowrap'>Объем стульев: {$chair_space} м<sup>3</sup> (Коробок: {$chair_boxes})</b>
					<br>
					<b class='nowrap'>Объем столов: {$table_space} м<sup>3</sup> (Коробок: {$table_boxes})</b>
				</div>
			";
		}
		else {
			die("<h1>Отгрузка не найдена!</h1>");
		}
	}
	else {
	?>
		<p>
			<form method="get">
				<select name="archive" onchange="this.form.submit()">
					<option value="0" <?=($archive == 0) ? "selected" : ""?>>В работе</option>
					<option value="1" <?=($archive == 1) ? "selected" : ""?>>Готовые</option>
					<option value="2" <?=($archive == 2) ? "selected" : ""?>>Все</option>
				</select>
			</form>
		</p>
	<?
	}

	if( in_array('shipment_view', $Rights) or in_array('shipment_view_city', $Rights) ) {
	?>
	<div id="shipment_list">
		<a class="button" href="#">Отгрузки</a>
		<div>
			<table class="main_table">
				<thead>
					<tr>
						<th width="20%">Город</th>
						<th width="40%">Комментарий</th>
						<th width="20%">Дата отгрузки</th>
<!--						<th width="20%">Дата поступления</th>-->
						<th width="30"></th>
					</tr>
				</thead>
				<tbody>
					<?
					$query = "SELECT SHP.SHP_ID
					,				CT.City
									,CT.Color
									,SHP.title
									,DATE_FORMAT(SHP.shipping_date, '%d.%m.%Y') shipping_date_format
									,DATE_FORMAT(SHP.arrival_date, '%d.%m.%Y') arrival_date_format
								FROM Shipment SHP
								JOIN Cities CT ON CT.CT_ID = SHP.CT_ID".(in_array('shipment_view_city', $Rights) ? " AND CT.CT_ID = {$USR_City}" : "")."
								WHERE SHP.shipping_date IS NULL OR DATEDIFF(NOW(), SHP.shipping_date) <= {$datediff}
								ORDER BY SHP.SHP_ID DESC";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<tr>";
						echo "<td><span style='background: {$row["Color"]}'>{$row["City"]}</span></td>";
						echo "<td>{$row["title"]}</td>";
						echo "<td><span>{$row["shipping_date_format"]}</span></td>";
//						echo "<td><span>{$row["arrival_date_format"]}</span></td>";
						echo "<td><a href='/?shpid={$row["SHP_ID"]}'><i class='fa fa-truck fa-lg' aria-hidden='true'></i></a></td>";
						echo "</tr>";
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?
	}
	?>

	<!-- Форма добавления заказа -->
	<div id='order_form' class='addproduct' title='Новый заказ' style='display:none;'>
		<form method='post'>
			<fieldset>
				<div>
					<label>Заказчик:</label>
					<input type='text' class='clienttags' name='ClientName' size='38'>
				</div>
				<div>
					<label>Дата продажи:</label>
					<input type='text' name='StartDate' class='date from' size='12' value='<?= date("d.m.Y") ?>' date='<?= date("d.m.Y") ?>' autocomplete='off'>
				</div>
				<div>
					<label>Дата сдачи:</label>
					<input type='text' name='EndDate' class='date to' size='12' autocomplete='off'>
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
									WHERE Cities.CT_ID IN ({$USR_cities}) OR Shops.SH_ID IN ({$USR_shops})
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


<?
	// Кнопка добавления заказа
	if( in_array('order_add', $Rights) and !isset($_GET["shpid"]) ) {
		echo "<div id='add_btn' title='Добавить новый заказ'></div>";
	}

	// Кнопка печати
	if( in_array('order_print', $Rights) ) {
		echo '<div id="print_btn" href="#print_tbl" class="open_modal" title="Распечатать таблицу">';
		echo '<a id="toprint"></a>';
		echo '</div>';
	}

	// Кнопка отгрузки
	if( in_array('add_shipment', $Rights) and $shipping_date == '' ) {
		echo '<div id="add_shipment" title="Сформировать список на отгрузку"></div>';
	}

	// Копирование ссылки на таблицу в буфер
	if( in_array('order_link', $Rights) ) {
		echo '<input id="post-link" style="position: absolute; z-index: -1;">';
		echo '<div id="copy_link" data-clipboard-target="#post-link" style="display: none;">';
		echo '<a id="copy-button" data-clipboard-target="#post-link" style="display: block; height: 100%" title="Скопировать ссылку в буфер обмена"></a>';
		echo '</div>';
	}

	// Кнопка печати документов
	if( in_array('print_forms', $Rights) ) {
		echo '<div id="print_forms" title="Печатные формы" style="display: none;">';
		echo '<a id="forms" target="_blank"></a>';
		echo '</div>';
	}

	// Кнопка печати этикеток на упаковку
	if( in_array('print_label_box', $Rights) ) {
		echo '<div id="print_labelsbox" title="Распечатать этикетки на упаковку" style="display: none;">';
		echo '<a id="labelsbox" target="_blank"></a>';
		echo '</div>';
	}
?>

	<!-- ФИЛЬТР ГЛАВНОЙ ТАБЛИЦЫ -->
	<?
	// Если зашли в отгрузку - не показываем фильтр
	if( !isset($_GET["shpid"]) ) {
	?>
	<table class="main_table">
		<form method='get' action='filter.php'>
		<thead>
		<tr>
			<th width="53"><input type='text' name='f_CD' size='8' value='<?= $_SESSION["f_CD"] ?>' class='<?=($_SESSION["f_CD"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="5%"><input type='text' name='f_CN' size='8' value='<?= $_SESSION["f_CN"] ?>' class='clienttags <?=($_SESSION["f_CN"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="5%"><input type='text' name='f_SD' size='8' value='<?= $_SESSION["f_SD"] ?>' class='<?=($_SESSION["f_SD"] != "") ? "filtered" : ""?>'></th>
			<th width="5%"><input type='text' name='f_ED' size='8' value='<?= $_SESSION["f_ED"] ?>' class='<?=($_SESSION["f_ED"] != "") ? "filtered" : ""?>'></th>
			<th width="5%"><input type='text' name='f_SH' size='8' class='shopstags <?=($_SESSION["f_SH"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_SH"] ?>'></th>
			<th width="5%"><input type='text' name='f_ON' size='8' value='<?= $_SESSION["f_ON"] ?>' class="<?=($_SESSION["f_ON"] != "") ? "filtered" : ""?>"></th>
			<th width="25%"><input type='text' name='f_Z' value='<?= $_SESSION["f_Z"] ?>' class="<?=($_SESSION["f_Z"] != "") ? "filtered" : ""?>"></th>
			<th width="15%" id="MT_filter"><div><select name="MT_ID[]" multiple style="width: 100%; display: none;"></select></div></th>
			<th width="15%" style="font-size: 0;">
				<style>
					.IsPainting {
						width: 100%;
						font-family: FontAwesome;
					}
				</style>
				<input type='text' name='f_CR' style='width: calc(100% - 40px);' class='colortags <?=($_SESSION["f_CR"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_CR"] ?>'>
				<select name="f_IP" style='width: 40px;' class="IsPainting <?=($_SESSION["f_IP"] != "") ? "filtered" : ""?>" onchange="this.form.submit()">
					<option></option>
					<option value="1" <?= ($_SESSION["f_IP"] == 1) ? 'selected' : '' ?> class="notready">&#xf006 - Не в работе</option>
					<option value="2" <?= ($_SESSION["f_IP"] == 2) ? 'selected' : '' ?> class="inwork">&#xf123 - В работе</option>
					<option value="3" <?= ($_SESSION["f_IP"] == 3) ? 'selected' : '' ?> class="ready">&#xf005 - Готово</option>
				</select>
			</th>
			<th width="100" style="font-size: 0;">
				<select name="f_PR" style="width: 70%;" onchange="this.form.submit()" class="<?=($_SESSION["f_PR"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="0" <?= ($_SESSION["f_PR"] === "0") ? 'selected' : '' ?>>Не назначен!</option>
					<option value="02" <?= ($_SESSION["f_PR"] === "02") ? 'selected' : '' ?>>Не назначен! - Столы</option>
					<option value="01" <?= ($_SESSION["f_PR"] === "01") ? 'selected' : '' ?>>Не назначен! - Стулья</option>
					<option value="00" <?= ($_SESSION["f_PR"] === "00") ? 'selected' : '' ?>>Не назначен! - Прочее</option>
					<?
						$query = "SELECT ODS.WD_ID, WD.Name, COUNT(1) Cnt
									FROM OrdersDataSteps ODS
									LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
									WHERE ODS.WD_ID IS NOT NULL
									GROUP BY ODS.WD_ID
									ORDER BY Cnt DESC";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["WD_ID"]}' ";
							if( $_SESSION["f_PR"] == $row["WD_ID"] ) echo "selected";
							echo ">{$row["Name"]}</option>";
						}
					?>
				</select>
				<select name="f_ST" style="width:30%;" onchange="this.form.submit()" class="<?=($_SESSION["f_ST"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="0" <?= ($_SESSION["f_ST"] == "0") ? 'selected' : '' ?> class="inwork">В работе</option>
					<option value="1" <?= ($_SESSION["f_ST"] == "1") ? 'selected' : '' ?> class="ready">Готово</option>
				</select>
			</th>
			<th width="40">
				<select name="f_CF" style="width: 100%;" onchange="this.form.submit()" class="<?=($_SESSION["f_CF"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="0" <?= ($_SESSION["f_CF"] == "0") ? 'selected' : '' ?>>Нет</option>
					<option value="1" <?= ($_SESSION["f_CF"] == "1") ? 'selected' : '' ?>>Да</option>
				</select>
			</th>
			<th width="40">
				<select name="f_X" style="width: 100%;" onchange="this.form.submit()" class="<?=($_SESSION["f_X"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="1" <?= ($_SESSION["f_X"] == 1) ? 'selected' : '' ?>>X</option>
				</select>
			</th>
			<th width="15%"><input type='text' name='f_N' value='<?= $_SESSION["f_N"] ?>' class="<?=($_SESSION["f_N"] != "") ? "filtered" : ""?>"></th>
			<th width="80"><button title="Фильтр"><i class="fa fa-filter fa-lg"></i></button><a href="filter.php?location=<?=$location?>" class="button" title="Сброс"><i class="fa fa-times fa-lg"></i></a><input type='hidden' name='location' value='<?=$location?>'></th>
		</tr>
		</thead>
		</form>
	</table>
	<?
	}
	?>
	<!-- //ФИЛЬТР ГЛАВНОЙ ТАБЛИЦЫ -->

<div id="print_tbl">
	<!-- Главная таблица -->
	<form id='printtable'>
	<div class="wr_main_table_head"> <!-- Обертка шапки -->
	<table class="main_table">
		<input type="text" id="print_title" name="print_title" placeholder="Введите заголовок таблицы">
		<div id="print_products">
			<input type="checkbox" value="1" checked name="Tables" id="Tables" class="print_products"><label for="Tables">Печатать столы</label>
			<input type="checkbox" value="1" checked name="Chairs" id="Chairs" class="print_products"><label for="Chairs">Печатать стулья</label>
			<input type="checkbox" value="1" checked name="Others" id="Others" class="print_products"><label for="Others">Печатать заготовки и прочее</label>
		</div>
		<thead>
		<tr>
			<th width="53"><input type="checkbox" disabled value="1" checked name="CD" class="print_col" id="CD"><label for="CD">Код</label></th>
			<th width="5%"><input type="checkbox" disabled value="2" name="CN" class="print_col" id="CN"><label for="CN">Заказчик</label></th>
			<th width="5%"><input type="checkbox" disabled value="3" name="SD" class="print_col" id="SD"><label for="SD">Дата<br>продажи</label></th>
			<th width="5%"><input type="checkbox" disabled value="4" checked name="ED" class="print_col" id="ED"><label for="ED">Дата<br>сдачи</label></th>
			<th width="5%"><input type="checkbox" disabled value="5" checked name="SH" class="print_col" id="SH"><label for="SH">Салон</label></th>
			<th width="5%"><input type="checkbox" disabled value="6" name="ON" class="print_col" id="ON"><label for="ON">№<br>квитанции</label></th>
			<th width="25%"><input type="checkbox" disabled value="7" checked name="Z" class="print_col" id="Z"><label for="Z">Заказ</label></th>
			<th width="15%"><input type="checkbox" disabled value="8" checked name="M" class="print_col" id="M"><label for="M">Материал</label></th>
			<th width="15%"><input type="checkbox" disabled value="9" checked name="CR" class="print_col" id="CR"><label for="CR">Цвет<br>краски</label></th>
			<th width="100"><input type="checkbox" disabled value="10" name="PR" class="print_col" id="PR"><label for="PR">Этапы</label></th>
			<th width="40"><input type="checkbox" disabled value="11" name="CF" class="print_col" id="CF"><label for="CF">Принят</label></th>
			<th width="40"><input type="checkbox" disabled value="12" name="X" class="print_col" id="X"><label for="X">X</label></th>
			<th width="15%"><input type="checkbox" disabled value="13" checked name="N" class="print_col" id="N"><label for="N">Примечание</label></th>
			<th width="80">Действие</th>
		</tr>
		</thead>
	</table>
	</div>
	<div class="wr_main_table_body"> <!-- Обертка тела таблицы -->
	<table class="main_table">
		<thead style="">
		<tr>
			<th width="53"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="25%"></th>
			<th width="15%"></th>
			<th width="15%"></th>
			<th width="100"></th>
			<th width="40"></th>
			<th width="40"></th>
			<th width="15%"></th>
			<th width="80"></th>
		</tr>
		</thead>
		<tbody>
<?
	$MT_IDs = $_SESSION["f_M"] != "" ? implode(",", $_SESSION["f_M"]) : "";

	if( $_SESSION["f_PR"] != "" and $_SESSION["f_ST"] != "" and !isset($_GET["shpid"]) ) {
		if( $_SESSION["f_PR"] === "0" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL, ' ss', '')";
			$SelectStepODB = "IF(ODS.WD_ID IS NULL, ' ss', '')";
		}
		elseif( $_SESSION["f_PR"] === "02" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND IFNULL(PM.PT_ID, 2) = 2 AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "0 PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL AND IFNULL(PM.PT_ID, 2) = 2, ' ss', '')";
			$SelectStepODB = "''";
		}
		elseif( $_SESSION["f_PR"] === "01" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND PM.PT_ID = 1 AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "0 PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL AND PM.PT_ID = 1, ' ss', '')";
			$SelectStepODB = "''";
		}
		elseif( $_SESSION["f_PR"] === "00" ) {
			$PRfilterODD = "0 PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "''";
			$SelectStepODB = "IF(ODS.WD_ID IS NULL, ' ss', '')";
		}
		else {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]}, ' ss', '')";
			$SelectStepODB = "IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]}, ' ss', '')";
		}
	}
	elseif( $_SESSION["f_PR"] != "" and $_SESSION["f_ST"] == "" and !isset($_GET["shpid"]) ) {
		if( $_SESSION["f_PR"] === "0" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL, ' ss', '')";
			$SelectStepODB = "IF(ODS.WD_ID IS NULL, ' ss', '')";
		}
		elseif( $_SESSION["f_PR"] === "02" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND IFNULL(PM.PT_ID, 2) = 2 AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "0 PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL AND IFNULL(PM.PT_ID, 2) = 2, ' ss', '')";
			$SelectStepODB = "''";
		}
		elseif( $_SESSION["f_PR"] === "01" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND PM.PT_ID = 1 AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "0 PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL AND PM.PT_ID = 1, ' ss', '')";
			$SelectStepODB = "''";
		}
		elseif( $_SESSION["f_PR"] === "00" ) {
			$PRfilterODD = "0 PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "''";
			$SelectStepODB = "IF(ODS.WD_ID IS NULL, ' ss', '')";
		}
		else {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID = {$_SESSION["f_PR"]}, ' ss', '')";
			$SelectStepODB = "IF(ODS.WD_ID = {$_SESSION["f_PR"]}, ' ss', '')";
		}
	}
	elseif( $_SESSION["f_PR"] == "" and $_SESSION["f_ST"] != "" and !isset($_GET["shpid"]) ) {
		$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
		$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
		$SelectStepODD = "IF(ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]}, ' ss', '')";
		$SelectStepODB = "IF(ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]}, ' ss', '')";
	}
	else {
		$PRfilterODD = "1 PRfilter";
		$PRfilterODB = "1 PRfilter";
		$SelectStepODD = "''";
		$SelectStepODB = "''";
	}

	$is_orders_ready = 1;	// Собираем готовые заказы чтобы можно ставить дату отгрузки (когда все готовы должна получиться 1)
	$orders_count = 0;		// Счетчик готовых заказов
	$orders_IDs = "0";		// Список ID заказов для Select2 материалов

	$query = "SELECT OD.OD_ID
					,OD.Code
					,IFNULL(OD.ClientName, '') ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(IFNULL(OD.ReadyDate, OD.EndDate), '%d.%m.%Y') EndDate
					,DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') ReadyDate
					,IF(OD.ReadyDate IS NOT NULL, 1, 0) Archive
					,IFNULL(OD.SH_ID, 0) SH_ID
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,OD.OrderNumber
					,OD.Comment
					,COUNT(ODD_ODB.itemID) Child
					,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
					,GROUP_CONCAT(ODD_ODB.Zakaz_lock SEPARATOR '') Zakaz_lock
					,OD.Color
					,OD.IsPainting
					,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
					,GROUP_CONCAT(ODD_ODB.Steps SEPARATOR '') Steps
					,BIT_OR(IFNULL(ODD_ODB.PRfilter, 1)) PRfilter
					,BIT_OR(IFNULL(ODD_ODB.MTfilter, 1)) MTfilter
					,IF(DATEDIFF(OD.EndDate, NOW()) <= 7 AND OD.ReadyDate IS NULL, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
					,BIT_AND(ODD_ODB.IsReady) IsReady
					,IFNULL(OD.SHP_ID, 0) SHP_ID
					,IF(OS.ostatok IS NOT NULL, 1, 0) is_lock
					,OD.confirmed
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
			  LEFT JOIN (SELECT ODD.OD_ID
			  				   ,{$PRfilterODD}
							   ,BIT_AND(IF(ODS.Visible = 1 AND ODS.Old = 0, ODS.IsReady, 1)) IsReady
							   ,IFNULL(PM.PT_ID, 2) PT_ID
							   ,ODD.ODD_ID itemID
							   ,".( $MT_IDs != "" ? "IF(ODD.MT_ID IN ({$MT_IDs}), 1, 0)" : "1" )." MTfilter

							   ,CONCAT('<b style=\'line-height: 1.79em;\'><a ".(in_array('order_add', $Rights) ? "href=\'#\'" : "")." id=\'prod', ODD.ODD_ID, '\' location=\'{$location}\' class=\'".(in_array('order_add', $Rights) ? "edit_product', IFNULL(PM.PT_ID, 2), '" : "")."\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', '</a></b><br>') Zakaz

							   ,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', '</i></b><br>') Zakaz_lock

							   ,CONCAT('<span class=\'wr_mt\'>', IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span ptid=\'', IFNULL(MT.PT_ID, ''), '\' mtid=\'', IFNULL(MT.MT_ID, ''), '\' id=\'m', ODD.ODD_ID, '\' class=\'mt', IFNULL(MT.MT_ID, ''), ".( $MT_IDs != "" ? "IF(ODD.MT_ID IN ({$MT_IDs}), ' ss', ''), " : "" )."IF(MT.removed=1, ' removed', ''), ' material ".(in_array('screen_materials', $Rights) ? " mt_edit " : "")."',
								CASE ODD.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
							   '\'>', IFNULL(MT.Material, ''), '</span><input type=\'text\' class=\'materialtags_', IFNULL(MT.PT_ID, ''), '\' style=\'display: none;\' title=\'Для отмены изменений нажмите клавишу ESC\'><input type=\'checkbox\' style=\'display: none;\' title=\'Выведен\'></span><br>') Material

							   ,CONCAT('<a ".(in_array('step_update', $Rights) ? "href=\'#\'" : "")." id=\'', ODD.ODD_ID, '\' class=\'".(in_array('step_update', $Rights) ? "edit_steps " : "")."nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\' location=\'{$location}\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, {$SelectStepODD}, ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR ''), '</a><br>') Steps

						FROM OrdersDataDetail ODD
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
						GROUP BY ODD.ODD_ID
						UNION
						SELECT ODB.OD_ID
							  ,{$PRfilterODB}
							  ,BIT_AND(IF(ODS.Visible = 1 AND ODS.Old = 0, ODS.IsReady, 1)) IsReady
							  ,0 PT_ID
							  ,ODB.ODB_ID itemID
							   ,".( $MT_IDs != "" ? "IF(ODB.MT_ID IN ({$MT_IDs}), 1, 0)" : "1" )." MTfilter

							  ,CONCAT('<b style=\'line-height: 1.79em;\'><a ".(in_array('order_add', $Rights) ? "href=\'#\'" : "")." id=\'blank', ODB.ODB_ID, '\'', 'class=\'".(in_array('order_add', $Rights) ? "edit_order_blank" : "")."\' location=\'{$location}\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), '</a></b><br>') Zakaz

							  ,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), '</i></b><br>') Zakaz_lock

							   ,CONCAT('<span class=\'wr_mt\'>', IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0 AND ODB.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), ''), '<span ptid=\'', IFNULL(MT.PT_ID, ''), '\' mtid=\'', IFNULL(MT.MT_ID, ''), '\' id=\'m', ODB.ODB_ID, '\' class=\'mt', IFNULL(MT.MT_ID, ''), ".( $MT_IDs != "" ? "IF(ODB.MT_ID IN ({$MT_IDs}), ' ss', ''), " : "" )."IF(MT.removed=1, ' removed', ''), ' material ".(in_array('screen_materials', $Rights) ? " mt_edit " : "")."',
								CASE ODB.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODB.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
							   '\'>', IFNULL(MT.Material, ''), '</span><input type=\'text\' class=\'materialtags_', IFNULL(MT.PT_ID, ''), '\' style=\'display: none;\' title=\'Для отмены изменений нажмите клавишу ESC\'><input type=\'checkbox\' style=\'display: none;\' title=\'Выведен\'></span><br>') Material

							  ,CONCAT('<a ".(in_array('step_update', $Rights) ? "href=\'#\'" : "")." odbid=\'', ODB.ODB_ID, '\' class=\'".(in_array('step_update', $Rights) ? "edit_steps " : "")."nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\' location=\'{$location}\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, {$SelectStepODB}, ' unvisible'), '\' style=\'width: 30px;\' title=\'(', IFNULL(WD.Name, 'Не назначен!'), ')\'><i class=\"fa fa-cog\" aria-hidden=\"true\" style=\"line-height: 1.45em;\"></i></div>')) SEPARATOR ''), '</a><br>') Steps

			  			FROM OrdersDataBlank ODB
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
						LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						GROUP BY ODB.ODB_ID
						ORDER BY PT_ID DESC, itemID
						) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  WHERE OD.Del = 0 AND (IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) OR IFNULL(SH.SH_ID, 0) IN ({$USR_shops}))";

			if( !isset($_GET["shpid"]) ) { // Если не в отгрузке
			  switch ($archive) {
				case 0:
					$query .= " AND OD.ReadyDate IS NULL";
					break;
				case 1:
					$query .= " AND OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}";
					break;
				case 2:
					$query .= " AND ((OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}) OR (OD.ReadyDate IS NULL))";
					break;
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
			  	$query .= " AND DATE_FORMAT(OD.EndDate, '%d.%m.%Y') LIKE '%{$_SESSION["f_ED"]}%'";
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
			  if( $_SESSION["f_CF"] != "" ) {
				  $query .= " AND OD.confirmed = {$_SESSION["f_CF"]}";
			  }
			}
			else {  // Если в отгрузке - показываем список этой отгрузки
				$query .= " AND OD.SHP_ID = {$_GET["shpid"]}";
			}

			$query .= " GROUP BY OD.OD_ID HAVING PRfilter AND MTfilter";

			if( !isset($_GET["shpid"]) ) { // Если не в отгрузке
			  if( $_SESSION["f_Z"] != "" ) {
				  $query .= " AND Zakaz LIKE '%{$_SESSION["f_Z"]}%'";
			  }
//			  if( $_SESSION["f_M"] != "" ) {
//				  $query .= " AND Material LIKE '%{$_SESSION["f_M"]}%'";
//			  }
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
			}

			$query .= " ORDER BY OD.AddDate, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$orders_IDs .= ",".$row["OD_ID"]; // Собираем ID видимых заказов для фильтра материалов
		echo "<tr id='ord{$row["OD_ID"]}'>";
		echo "<td".($row["Archive"] == 1 ? " style='background: #bf8;'" : "")."><span class='nowrap'>{$row["Code"]}</span></td>";
		echo "<td><span><input type='checkbox' value='1' checked name='order{$row["OD_ID"]}' class='print_row' id='n{$row["OD_ID"]}'><label for='n{$row["OD_ID"]}'>></label>{$row["ClientName"]}</span></td>";
		echo "<td><span>{$row["StartDate"]}</span></td>";
		echo "<td><span><span class='{$row["Deadline"]}'>{$row["EndDate"]}</span></span></td>";
		echo "<td class='".( ( in_array('order_add', $Rights) and !( $row["is_lock"] or ( $row["confirmed"] and !in_array('order_add_confirm', $Rights) ) ) ) ? "shop_cell" : "" )."' id='{$row["OD_ID"]}' SH_ID='{$row["SH_ID"]}'><span style='background: {$row["CTColor"]};'>{$row["Shop"]}</span></td>";
		echo "<td><span>{$row["OrderNumber"]}</span></td>";
		if( $row["is_lock"] or ( $row["confirmed"] and !in_array('order_add_confirm', $Rights) ) ) {
			echo "<td><span class='nowrap'>{$row["Zakaz_lock"]}</span></td>";
		}
		else {
			echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		}
		echo "<td class='nowrap'>{$row["Material"]}</td>";
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
		echo " class='".(in_array('order_add_confirm', $Rights) ? "painting " : "")."{$class}' title='{$title}' isready='{$row["IsReady"]}' archive='{$row["Archive"]}'>{$row["Color"]}</td>";
		echo "<td class='td_step ".($row["confirmed"] == 1 ? "step_confirmed" : "")."'><span class='nowrap material'>{$row["Steps"]}</span></td>";
		$checkedX = $_SESSION["X_".$row["OD_ID"]] == 1 ? 'checked' : '';
		// Если заказ принят
		if( $row["confirmed"] == 1 ) {
			$class = 'confirmed';
			$title = 'Принят в работу';
		}
		else {
			$class = 'not_confirmed';
			$title = 'Не принят в работу';
		}
		if( in_array('order_add_confirm', $Rights) ) {
			$class = $class." edit_confirmed";
		}
		echo "<td val='{$row["confirmed"]}' class='{$class}' title='{$title}'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";
		echo "<td class='X'><input type='checkbox' {$checkedX} value='1'></td>";
		echo "<td>{$row["Comment"]}</td>";
		echo "<td>";

		if( $row["is_lock"] ) {
			echo "<i class='fa fa-lock fa-lg' aria-hidden='true' title='Отчетный период закрыт. Заказ нельзя отредактировать.'></i> ";
		}
		else if( in_array('order_add', $Rights) ) {
			echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Редактировать'><i class='fa fa-pencil fa-lg'></i></a> ";
			echo "<a href='#' id='{$row["OD_ID"]}' class='order_cut' title='Разделить заказ' location='{$location}'><i class='fa fa-sliders fa-lg'></i></a> ";
		}

		echo "<action>";
		if( $row["Child"] ) // Если заказ не пустой
		{
			if( $row["SHP_ID"] == 0 )
			{
				if( in_array('order_ready', $Rights) and !isset($_GET["shpid"]) and $row["Archive"] == 0 and $row["IsReady"] ) {
					echo "<a href='#' class='' ".(($row["SH_ID"] == 0) ? "style='display: none;'" : "")." onclick='if(confirm(\"Пожалуйста, подтвердите готовность заказа!\", \"?ready={$row["OD_ID"]}\")) return false;' title='Готово'><i style='color:red;' class='fa fa-flag-checkered fa-lg'></i></a>";
				}
			}
			else {
				echo "<a href='/?shpid={$row["SHP_ID"]}' title='К списку отгрузки'><i class='fa fa-truck fa-lg' aria-hidden='true'></i></a>";
			}
			if( !$row["IsReady"] ) {
				$is_orders_ready = 0;
			}
			$orders_count++;
		}
		else
		{
			if( in_array('order_add', $Rights) ) {
				echo "<a href='#' class='' onclick='if(confirm(\"Удалить?\", \"?del={$row["OD_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
			}
		}
		echo "</action>";

		echo "</td></tr>";

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
				  FROM OrdersDataDetail ODD
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
				  LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
				  WHERE ODD.OD_ID = {$row["OD_ID"]}
				  GROUP BY ODD.ODD_ID";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $sub_row = mysqli_fetch_array($result) )
		{
			$ODD[$sub_row["ODD_ID"]] = array( "amount"=>$sub_row["Amount"], "price"=>$sub_row["Price"], "model"=>$sub_row["PM_ID"], "form"=>$sub_row["PF_ID"], "mechanism"=>$sub_row["PME_ID"], "length"=>$sub_row["Length"], "width"=>$sub_row["Width"], "PieceAmount"=>$sub_row["PieceAmount"], "PieceSize"=>$sub_row["PieceSize"], "color"=>$sub_row["Color"], "comment"=>$sub_row["Comment"], "material"=>$sub_row["Material"], "shipper"=>$sub_row["Shipper"], "isexist"=>$sub_row["IsExist"], "inprogress"=>$sub_row["inprogress"], "order_date"=>$sub_row["order_date"], "arrival_date"=>$sub_row["arrival_date"] );
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
				  FROM OrdersDataBlank ODB
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID AND ODS.Visible = 1
				  LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
				  WHERE ODB.OD_ID = {$row["OD_ID"]}
				  GROUP BY ODB.ODB_ID";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $sub_row = mysqli_fetch_array($result) )
		{
			$ODB[$sub_row["ODB_ID"]] = array( "amount"=>$sub_row["Amount"], "price"=>$sub_row["Price"], "blank"=>$sub_row["BL_ID"], "other"=>$sub_row["Other"], "comment"=>$sub_row["Comment"], "material"=>$sub_row["Material"], "shipper"=>$sub_row["Shipper"], "isexist"=>$sub_row["IsExist"], "inprogress"=>$sub_row["inprogress"], "order_date"=>$sub_row["order_date"], "arrival_date"=>$sub_row["arrival_date"], "MPT_ID"=>$sub_row["MPT_ID"] );
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

<?
	// Генерируем Select2 для фильтра материалов
	$MT_filter = '';
	$query = "SELECT MT.MT_ID, CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')') Material
				FROM Materials MT
				LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
				JOIN (
					SELECT ODD.OD_ID, ODD.MT_ID, ODD.IsExist
					FROM OrdersDataDetail ODD
					UNION
					SELECT ODB.OD_ID, ODB.MT_ID, ODB.IsExist
					FROM OrdersDataBlank ODB
					) ODD_ODB ON ODD_ODB.MT_ID = MT.MT_ID AND ODD_ODB.OD_ID IN ({$orders_IDs})
				GROUP BY MT.MT_ID
				ORDER BY MT.Material";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$selected = in_array($row["MT_ID"], $_SESSION["f_M"]) ? "selected" : "";
		$MT_filter .= "<option {$selected} value='{$row["MT_ID"]}'>{$row["Material"]}</option>";
	}
	$MT_filter = addslashes($MT_filter);

?>

<!-- Форма добавления наименования отгрузки -->
<div id='add_shipment_form' title='Параметры отгрузки' style='display:none'>
	<form method='post'>
		<fieldset style="text-align: center;">
			<div>
				<label>Город:</label>
				<select name="CT_ID" required>
					<?=$_GET["shpid"] ? '' : '<option value="">-=Выберите город=-</option>'?>
					<?
					$query = "SELECT CT_ID, City, Color
								FROM Cities".(isset($_GET["shpid"]) ? ' WHERE CT_ID = '.$CT_ID : '')."
								ORDER BY Cities.City";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<option value='{$row["CT_ID"]}' style='background: {$row["Color"]};'>{$row["City"]}</option>";
					}
					?>
				</select>
			</div>
			<br>
			<div class="accordion">
				<h3>Список заказов на отгрузку</h3>
				<div id="orders_to_shipment" style='text-align: left;'></div>
			</div>
			<br>
			<div>
				<label>Комментарий к отгрузке:</label>
				<input type='text' style='width: 350px;' name='shp_title' autocomplete="off">
			</div>
			<div>
				<hr>
				<input type='submit' value='Сохранить' style='float: right;'>
			</div>
		</fieldset>
	</form>
</div>

<script>
	$(document).ready(function(){

		// Фильтр по материалам (инициализация)
		$('#MT_filter > div > select').html('<?=$MT_filter?>');
		$('#MT_filter select').select2({
			placeholder: "Выберите интересующие материалы",
			allowClear: true,
			closeOnSelect: false,
			language: "ru"
		}).on("select2:select", function() { $('.select2-selection li').attr('title', ''); });
		$('.select2-selection li').attr('title', '');
		// Добавляем класс filtered если отфильтровано по материалам
		<?=( $_SESSION["f_M"] != "" ? "$('.select2-selection ul').addClass('filtered');" : "" )?>

		if(!<?=$is_orders_ready?> || !<?=$orders_count?>) {
			$('#wr_shipping_date input[name="shipping_date"]').prop('disabled', true);
			$('#wr_shipping_date button').hide('fast');
			$('#wr_shipping_date font').show('fast');
			if( !<?=$orders_count?> ) {
				$('#wr_shipping_date font').html('&nbsp;&nbsp;Список пуст!');
			}
			else {
				$('#wr_shipping_date font').html('&nbsp;&nbsp;В списке присутствуют незавершенные этапы!');
			}
		}

		$( ".shopstags" ).autocomplete({ // Автокомплит салонов
			source: "autocomplete.php?do=shopstags"
		});

		$( ".colortags" ).autocomplete({ // Автокомплит цветов
			source: "autocomplete.php?do=colortags"
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

		$( ".clienttags" ).autocomplete({ // Автокомплит заказчиков
			source: "autocomplete.php?do=clienttags"
		});

		new Clipboard('#copy-button'); // Копирование ссылки в буфер

		$('.print_products').button();

		$('select[name="f_PR"]').attr('title', $('select[name="f_PR"] option:selected').html()); // Подсказка выбранного работника в фильтре
		$('select[name="f_ST"]').attr('title', $('select[name="f_ST"] option:selected').html()); // Подсказка статуса этапа в фильтре

		// Фильтрация таблицы при автокомплите
		$( ".main_table .clienttags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .shopstags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .textileplastictags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .colortags" ).on( "autocompleteselect", function( event, ui ) {
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
					closeText: 'Закрыть'
				});

				// Автокомплит поверх диалога
				$( ".colortags" ).autocomplete( "option", "appendTo", "#order_form" );

				return false;
			});

			$('.print_col, .print_row, .print_products').change( function() { changelink(); });

			$('#print_btn').click( function() { changelink(); });
			$('#print_title').change( function() { changelink(); });
		});

		// Смена статуса лакировки аяксом
		$('.painting').click(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			var val = $(this).attr('val');
			var isready = $(this).attr('isready');
			var archive = $(this).attr('archive');
			$.ajax({ url: "ajax.php?do=ispainting&od_id="+id+"&val="+val+"&isready="+isready+"&archive="+archive, dataType: "script", async: false });
		});

		// Смена статуса принятия аяксом
		$('.edit_confirmed').click(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			var val = $(this).attr('val');
			$.ajax({ url: "ajax.php?do=confirmed&od_id="+id+"&val="+val, dataType: "script", async: false });
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
			$("#post-link").val('http://<?=$_SERVER['HTTP_HOST']?>/toprint/main.php?' + data);
			$("#print_forms > a").attr('href', '/print_forms.php?' + data);
			$("#labelsbox").attr('href', '/labels_box.php?' + data);
			return false;
		}
		$("#copy-button").click(function() {
			noty({timeout: 3000, text: 'Ссылка на таблицу скопирована в буфер обмена', type: 'success'});
		});

		// Форма формирования отгрузки
		$('#add_shipment').click(function() {
			//$('select[name="CT_ID"]').val('');
			//$('#orders_to_shipment').html('');
			//$('#add_shipment_form .accordion').accordion( "option", "active", 1 );

			$('#add_shipment_form').dialog({
				position: { my: "center top", at: "center top", of: window },
				draggable: false,
				width: 800,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
		});

		// Динамическая подгрузка заказов при выборе города (в форме отгрузки)
		$('select[name="CT_ID"]').on('change', function() {
			var CT_ID = $(this).val();
			$.ajax({ url: "ajax.php?do=shipment&CT_ID="+CT_ID, dataType: "script", async: false });
			$('#add_shipment_form .accordion').accordion( "option", "active", 0 );
		});

		<?
		if( isset($_GET["shpid"]) ) { // Если в отгрузке - заполняем форму отгрузки
			echo "$('select[name=CT_ID]').val('{$CT_ID}');";
			echo "$('input[name=shp_title]').val('{$shp_title}');";
			echo '$.ajax({ url: "ajax.php?do=shipment&CT_ID='.$CT_ID.'&shpid='.$_GET["shpid"].'", dataType: "script", async: false });';
			echo "$('#add_shipment_form .accordion').accordion( 'option', 'active', 0 );";
		}
		?>

		// Редактирование салона
		$('.shop_cell').dblclick(function() {
			var SH_ID = $(this).attr('SH_ID');
			var shop_span = $(this).html();
			$(this).html('<?=$select_shops?>');

			$(this).find('select').val(SH_ID).focus().on('change', function() {
				var OD_ID = $(this).parents('td').attr('id');
				var val = $(this).val();
				$.ajax({ url: "ajax.php?do=update_shop&OD_ID="+OD_ID+"&SH_ID="+val, dataType: "script", async: false });
			});

			$(this).find('select').blur(function() {
				$(this).parents('.shop_cell').html(shop_span);
			});
		});

		// В форме добавления заказа если выбираем Свободные - дата продажи пустая
		$('#order_form select[name="Shop"]').on("change", function() {
			var StartDate = $('#order_form input[name="StartDate"]').attr('date');
			if( $(this).val() === '0' ) {
				$('#order_form input[name="StartDate"]').val('');
			}
			else {
				$('#order_form input[name="StartDate"]').val(StartDate);
			}
		});

		$('#order_form input[name="StartDate"]').on("change", function() {
			$(this).attr('date', $(this).val());
		});

		odd = <?= json_encode($ODD) ?>;
		odb = <?= json_encode($ODB) ?>;
	});
</script>
