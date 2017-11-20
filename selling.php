<?
	include "config.php";
	$title = 'Реализация';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('selling_all', $Rights) and !in_array('selling_city', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$CT_ID = (isset($_GET["CT_ID"]) and (int)$_GET["CT_ID"] > 0) ? $_GET["CT_ID"] : 0;
	$SH_ID = (isset($_GET["SH_ID"]) and (int)$_GET["SH_ID"] > 0) ? $_GET["SH_ID"] : 0;
	$SH_ID = $USR_Shop ? $USR_Shop : $SH_ID;
	$year = (isset($_GET["year"]) and (int)$_GET["year"] >= 0) ? $_GET["year"] : "";
	$month = (isset($_GET["month"]) and (int)$_GET["month"] >= 0) ? $_GET["month"] : "";

	// Проверка прав на доступ к экрану (если пользователю доступен только город)
	if( in_array('selling_city', $Rights) ) {
		if( $CT_ID and $CT_ID != $USR_City ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		else {
			$CT_ID = $USR_City;
		}
	}

	// Формируем выпадающее меню салонов в таблицу
	$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND KA_ID IS NULL ".($USR_Shop ? "AND SH_ID = {$USR_Shop}" : "")." ORDER BY Shop";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$select_shops = "";
	while( $row = mysqli_fetch_array($res) ) {
		$select_shops .= "<option value='{$row["SH_ID"]}'>{$row["Shop"]}</option>";
	}

	$datediff = 60; // Максимальный период отображения данных
//	$datediff = $_SESSION['id'] == 1 ? 600 : 60;

	$location = "selling.php?CT_ID={$CT_ID}".( ($year != "" and $month != "") ? '&year='.$year.'&month='.$month : '' );
	$_SESSION["location"] = $_SERVER['REQUEST_URI'];

	// Добавление в базу нового платежа
	if( isset($_GET["add_payment"]) and $_POST["payment_sum_add"] )
	{
		$OD_ID = $_POST["OD_ID"];
		$payment_date = date( 'Y-m-d', strtotime($_POST["payment_date_add"]) );
		$payment_sum = $_POST["payment_sum_add"];
		$terminal = $_POST["terminal_add"];
		$terminal_payer = $terminal ? '\''.mysqli_real_escape_string( $mysqli, $_POST["terminal_payer_add"] ).'\'' : 'NULL';
		$FA_ID_add = $_POST["FA_ID_add"] ? $_POST["FA_ID_add"] : 'NULL';
		$SH_ID_add = $_POST["FA_ID_add"] ? 'NULL' : $_POST["SH_ID_add"];

		if( $payment_sum ) {
			// Записываем новый платеж в таблицу платежей
			$query = "INSERT INTO OrdersPayment
						 SET OD_ID = {$OD_ID}
							,payment_date = '{$payment_date}'
							,payment_sum = {$payment_sum}
							,terminal_payer = {$terminal_payer}
							,SH_ID = {$SH_ID_add}
							,FA_ID = ".($terminal ? "(SELECT SH.FA_ID FROM Shops SH JOIN OrdersData OD ON OD.SH_ID = SH.SH_ID AND OD.OD_ID = {$OD_ID})" : $FA_ID_add)."
							,author = {$_SESSION['id']}";
			if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["alert"] = mysqli_error( $mysqli ); }
			else {
				// Записываем дату продажи заказа если ее не было
				$query = "UPDATE OrdersData SET StartDate = '{$payment_date}', author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID} AND StartDate IS NULL";
				if( !mysqli_query( $mysqli, $query ) ) {
					$_SESSION["alert"] = mysqli_error( $mysqli );
				}
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		die;
	}

	// Обновление цены изделий в заказе
	if( isset($_GET["add_price"]) ) {
		$OD_ID = $_POST["OD_ID"];
		$discount = $_POST["discount"] ? $_POST["discount"] : "NULL";
		// Обновление скидки заказа
		$query = "UPDATE OrdersData SET discount = {$discount}, author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
		if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["alert"] = mysqli_error( $mysqli ); }

		foreach ($_POST["PT_ID"] as $key => $value) {
			$price = $_POST["price"][$key] ? $_POST["price"][$key] : "NULL";
			if( $value == 0 ) {
				$query = "UPDATE OrdersDataBlank SET Price = {$price}, author = {$_SESSION['id']} WHERE ODB_ID = {$_POST["itemID"][$key]}";
			}
			else {
				$query = "UPDATE OrdersDataDetail SET Price = {$price}, author = {$_SESSION['id']} WHERE ODD_ID = {$_POST["itemID"][$key]}";
			}
			if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["alert"] = mysqli_error( $mysqli ); }
		}
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		die;
	}

	// Добавление/редактирование расхода/прихода
	if( isset($_GET["add_cost"]) )
	{
		$OP_ID = $_POST["OP_ID"];
		$SH_ID = $_POST["SH_ID"];
		$cost_name = mysqli_real_escape_string( $mysqli, $_POST["cost_name"] );
		$cost_date = date( 'Y-m-d', strtotime($_POST["cost_date"]) );
		$cost = $_POST["cost"] ? $_POST["cost"] : 0;
		$cost = ($_POST["sign"] == '-') ? $cost * -1 : $cost;
		$send = $_POST["send"] ? $_POST["send"] : "NULL";

		if( $OP_ID != '' ) { // Редактируем расход
			$query = "UPDATE OrdersPayment SET SH_ID = {$SH_ID}, cost_name = '{$cost_name}', payment_date = '{$cost_date}', payment_sum = {$cost}, send = {$send}, author = {$_SESSION['id']} WHERE OP_ID = {$OP_ID}";
			if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["alert"] = mysqli_error( $mysqli ); }
		}
		else { // Добавляем расход
			if( $cost ) {
				$query = "INSERT INTO OrdersPayment SET SH_ID = {$SH_ID}, cost_name = '{$cost_name}', payment_date = '{$cost_date}', payment_sum = {$cost}, send = {$send}, author = {$_SESSION['id']}";
				if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["alert"] = mysqli_error( $mysqli ); }
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Блокировка отчета
	if( isset($_GET["add_locking_date"]) ) {
		$locking_date = date( 'Y-m-d', strtotime($_POST["locking_date"]) );
		if( $_POST["locking_date"] != '' ) {
			$query = "INSERT INTO OstatkiShops
				SET CT_ID = {$_GET["CT_ID"]}, year = {$_GET["year"]}, month = {$_GET["month"]}, locking_date = '{$locking_date}'
				ON DUPLICATE KEY UPDATE locking_date = '{$locking_date}'";
		}
		else {
			$query = "UPDATE OstatkiShops SET locking_date = NULL WHERE CT_ID = {$_GET["CT_ID"]} AND year = {$_GET["year"]} AND month = {$_GET["month"]}";
		}
		if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["alert"] = mysqli_error( $mysqli ); }

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Отказ/замена
	if( isset($_GET["order_otkaz"]) ) {
		$OD_ID = $_POST["OD_ID"];
		$old_sum = $_POST["old_sum"];
		$type = $_POST["type"];

		// Получаем из базы доп. сведения по заказу
		$query = "SELECT OD.SH_ID, OD.StartDate FROM OrdersData OD WHERE OD.OD_ID = {$OD_ID}";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$SH_ID = mysqli_result($subres,0,'SH_ID');
		$StartDate = mysqli_result($subres,0,'StartDate');

		if( $StartDate ) {
			if( $old_sum ) {
				$query = "INSERT INTO Otkazi
					SET OD_ID = {$OD_ID}, type = {$type}, SH_ID = {$SH_ID}, StartDate = '{$StartDate}', old_sum = {$old_sum}
					ON DUPLICATE KEY UPDATE type = {$type}, old_sum = {$old_sum}";
				if( mysqli_query( $mysqli, $query ) ) {
					$_SESSION["alert"] = "В таблице отказов/замен сделана запись. ";
				}
				else { $_SESSION["alert"] = mysqli_error( $mysqli ); }
			}
			$query = "UPDATE OrdersData SET StartDate = NULL, sell_comment = CONCAT(IFNULL(sell_comment, ''), IF({$type} = 1, ' Замена', ' Отказ')), author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
			if( mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"] .= "Заказ перемещен в \"Свободные\"";
			}
			else {
				$_SESSION["alert"] .= mysqli_error( $mysqli );
			}
		}
		else {
			$_SESSION["alert"] = "Заказ не продан! Установите дату продажи и повторите попытку.";
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		die;
	}

	// Отмена отказа/замены
	if( isset($_GET["del_otkaz"]) ) {
		$OD_ID = $_GET["del_otkaz"];
		$SH_ID = $_GET["SH_ID"];
		$StartDate = $_GET["StartDate"];

		$query = "DELETE FROM Otkazi WHERE OD_ID = {$OD_ID} AND SH_ID = {$SH_ID} AND StartDate = '{$StartDate}'";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	include "forms.php";
?>
<style>
	#selling_report {
		height: 200px;
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		margin-top: 10px;
		z-index: 2;
		position: absolute;
		width: calc( 100% - 40px );
		overflow: auto;
		white-space: nowrap;
	}
	#selling_report:hover {
		//overflow: visible;
	}
	#selling_report:hover table {
		box-shadow: 5px 5px 8px #666;
	}
	#selling_report table {
		display: inline-block;
		vertical-align: top;
		margin-right: 20px;
		transition: .3s;
	}
	#accordion a {
		color: #428bca !important;
	}
	#accordion a:hover {
		color: #D65C4F !important;
	}
</style>

<form method="get" style="display: inline-block;">
	<select name="CT_ID" onchange="this.form.submit()">
		<?
		$query = "SELECT CT.CT_ID, CONCAT(CT.City, ' (', GROUP_CONCAT(SH.Shop), ')') City, CT.Color
					FROM Cities CT
					JOIN Shops SH ON SH.CT_ID = CT.CT_ID AND SH.KA_ID IS NULL
					".(in_array('selling_city', $Rights) ? 'WHERE CT.CT_ID = '.$USR_City : '')."
					GROUP BY CT.CT_ID
					ORDER BY CT.City";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) )
		{
			echo "<option ".($CT_ID == $row["CT_ID"] ? "selected" : "")." value='{$row["CT_ID"]}' style='background: {$row["Color"]};'>{$row["City"]}</option>";
		}
		?>
	</select>
	<input type="hidden" name="year" value="<?=$year?>">
	<input type="hidden" name="month" value="<?=$month?>">
</form>

<?
	if( !$CT_ID ) die;
	// Узнаем общий остаток наличных
	$query = "SELECT SUM(IFNULL(MSIO.pay_in,0)) - SUM(IFNULL(MSIO.pay_out,0)) ostatok, SH.Shop
				FROM MonthlySellInOut MSIO
				JOIN Shops SH ON SH.SH_ID = MSIO.SH_ID";
	if( $SH_ID ) {
		$query .= " WHERE MSIO.SH_ID = {$SH_ID}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$label = "Касса ".mysqli_result($res,0,'Shop').":";
	}
	else {
		$query .= " WHERE MSIO.SH_ID IN(SELECT SH_ID FROM Shops WHERE CT_ID = {$CT_ID})";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$label = "Остаток наличных:";
	}
	$ostatok = mysqli_result($res,0,'ostatok');
	$format_ostatok = number_format($ostatok, 0, '', ' ');
	$now_date = date('d.m.Y');
	if( $CT_ID ) {
		echo "<h3 style='display: inline-block; margin: 10px 20px;'>{$label} {$format_ostatok}</h3>";
		echo "<a href='#' class='add_cost_btn' shop='".($USR_Shop ? $USR_Shop : "")."' cost_name='' cost='' cost_date='{$now_date}' sign='+' CT_ID='{$CT_ID}' title='Внести приход'><i class='fa fa-plus fa-lg' style='color: white; background: green; border-radius: 5px; line-height: 24px; width: 24px; text-align: center; vertical-align: text-bottom;'></i></a>&nbsp;";
		echo "<a href='#' class='add_cost_btn' shop='".($USR_Shop ? $USR_Shop : "")."' cost_name='' cost='' cost_date='{$now_date}' sign='-' CT_ID='{$CT_ID}' title='Внести расход'><i class='fa fa-minus fa-lg' style='color: white; background: red; border-radius: 5px; line-height: 24px; width: 24px; text-align: center; vertical-align: text-bottom;'></i></a>&nbsp;";
		echo "<a href='#' class='add_cost_btn' shop='".($USR_Shop ? $USR_Shop : "")."' cost_name='' cost='' cost_date='{$now_date}' sign='' CT_ID='{$CT_ID}' title='Внести отправку денег'><i class='fa fa-exchange fa-lg' style='color: white; background: #428bca; border-radius: 5px; line-height: 24px; width: 24px; text-align: center; vertical-align: text-bottom;'></i></a>";
	}
	else {
		echo "<h3 style='display: inline-block; margin: 10px 20px;'>&nbsp;</h3>";
	}
?>

	<!-- КНОПКИ ОТЧЕТОВ -->
	<div style="max-height: 23px;">
		Отчеты:
		<?
		$highlight = ($year == '' or $month == '') ? 'border: 1px solid #fbd850; color: #eb8f00;' : '';
		echo "<a href='?CT_ID={$CT_ID}' class='button' style='{$highlight}'>Все</a> ";

		$query = "SELECT year
						,month
						,IF(locking_date IS NULL, 0, 1) is_lock
						,IF(DATEDIFF(NOW(), IFNULL(locking_date, NOW())) <= {$datediff}, 1, 0) is_visible
					FROM OstatkiShops
					WHERE CT_ID = {$CT_ID} AND ( pay_in > 0 OR pay_out > 0 )

					UNION

					SELECT IFNULL(YEAR(OD.StartDate), 0) year
						,IFNULL(MONTH(OD.StartDate), 0) month
						,IF(OS.locking_date IS NULL, 0, 1) is_lock
						,IF(DATEDIFF(NOW(), IFNULL(OS.locking_date, NOW())) <= {$datediff}, 1, 0) is_visible
					FROM OrdersData OD
					JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.KA_ID IS NULL
					LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = {$CT_ID}
					WHERE OD.Del = 0 AND SH.CT_ID = {$CT_ID}
					GROUP BY YEAR(OD.StartDate), MONTH(OD.StartDate)

					UNION

					SELECT YEAR(NOW()), MONTH(NOW()), 0, 1
					ORDER BY year, month";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		if( $CT_ID ) {
			while( $row = mysqli_fetch_array($res) ) {
				$highlight = ($year == $row["year"] and $month == $row["month"]) ? 'border: 1px solid #fbd850; color: #eb8f00;' : '';
				if( $row["year"] == 0 and $row["month"] == 0 ) {
					echo "<a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}' class='button' style='{$highlight}'>Свободные</a> ";
					$OTCHET_MONTHS[] = "{$row["year"]}-{$row["month"]}";
				}
				else {
					if( $row["is_visible"] ) {
						echo "<a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}' class='button' style='{$highlight}' ".($row["is_lock"] == 1 ? "title='Месяц закрыт'" : "").">{$MONTHS[$row["month"]]} - {$row["year"]}".($row["is_lock"] == 1 ? " <i class='fa fa-lock' aria-hidden='true'></i>" : "")."</a> ";
						$OTCHET_MONTHS[] = "{$row["year"]}-{$row["month"]}";
					}
				}
			}
		}

		// Проверяем доступен ли месяц из GET в списке отчетных периодов
		if( ($year != '' or $month != '') and !in_array("{$year}-{$month}", $OTCHET_MONTHS) ) {
			if( $year == 0 and $month == 0 ) {
				die('<h3>Список свободных изделий пуст.<h3>');
			}
			else {
				die('<h3>'.$MONTHS["{$month}"].'-'.$year.' отсутствует в списке доступных отчетов.<h3>');
			}
			//exit ('<meta http-equiv="refresh" content="0; url=selling.php?CT_ID='.$CT_ID.'">');
			//die;
		}
		?>
	</div>
	<!-- //КНОПКИ ОТЧЕТОВ -->

	<?
	// ОТЧЕТ ЗА МЕСЯЦ
	if( $year > 0 and $month > 0 ) {
		// Узнаем дату закрытия месяца
		$query = "SELECT DATE_FORMAT(locking_date, '%d.%m.%Y') locking_date
					FROM OstatkiShops
					WHERE CT_ID = {$_GET["CT_ID"]} AND year = {$_GET["year"]} AND month = {$_GET["month"]}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$locking = mysqli_result($res,0,'locking_date') ? 1 : 0;
		$locking_date = mysqli_result($res,0,'locking_date');
	?>
		<div id='selling_report'>
			<div style="display: inline-block; vertical-align:top;">

			<?
				$locking_form = "
					<form method='post' action='{$location}&add_locking_date=1' id='locking_form'>
						<input type='text' class='date' name='locking_date' value='{$locking_date}' placeholder='Дата закрытия' autocomplete='off'>
						<button>Cохранить</button>
					</form>
					<br>
				";
				if( date('Y') != $_GET["year"] or date('n') != $_GET["month"] ) {
					if( in_array('selling_all', $Rights) ) {
						echo $locking_form;
					}
					else if( $locking_date != '' ) {
						echo "<h3>Месяц закрыт: {$locking_date}</h3>";
					}
				}
			?>

			<table>
				<thead>
					<tr>
						<th>Салон</th>
						<th>Продажи<i class="fa fa-question-circle" aria-hidden="true" title="Не включает суммы отказных заказов."></i></th>
						<th>Скидки</th>
						<th>Отказы</th>
						<th>Дебиторка</th>
						<th>Касса</th>
					</tr>
				</thead>
				<tbody>
				<?
					$city_price = 0;
					$city_discount = 0;
					$city_otkaz = 0;
					$city_debt = 0;
					$city_cash = 0;
					$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND KA_ID IS NULL ".($USR_Shop ? "AND SH_ID = {$USR_Shop}" : "")." ORDER BY SH_ID";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						// Получаем сумму выручки по салону
						$query = "SELECT SUM(ODD_ODB.Price) Price
									FROM OrdersData OD
									JOIN (
										SELECT ODD.OD_ID ,ODD.Price * ODD.Amount Price
										FROM OrdersDataDetail ODD
										WHERE ODD.Del = 0
										UNION ALL
										SELECT ODB.OD_ID ,ODB.Price * ODB.Amount Price
										FROM OrdersDataBlank ODB
										WHERE ODB.Del = 0
									) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
									WHERE OD.Del = 0 AND YEAR(OD.StartDate) = {$_GET["year"]} AND MONTH(OD.StartDate) = {$_GET["month"]} AND OD.SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_price = mysqli_result($subres,0,'Price');
						$city_price = $city_price + $shop_price;
						// Получаем сумму выручки по салону по накладным (чтобы получить дебиторку)
						$query = "SELECT SUM(ODD_ODB.Price) Price
									FROM OrdersData OD
									JOIN (
										SELECT ODD.OD_ID ,ODD.Price * ODD.Amount Price
										FROM OrdersDataDetail ODD
										WHERE ODD.Del = 0
										UNION ALL
										SELECT ODB.OD_ID ,ODB.Price * ODB.Amount Price
										FROM OrdersDataBlank ODB
										WHERE ODB.Del = 0
									) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
									WHERE OD.PFI_ID IS NOT NULL AND OD.Del = 0 AND YEAR(OD.StartDate) = {$_GET["year"]} AND MONTH(OD.StartDate) = {$_GET["month"]} AND OD.SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_price_inv = mysqli_result($subres,0,'Price');

						// Получаем скидку по салону
						$query = "SELECT SUM(discount) discount FROM OrdersData OD WHERE OD.Del = 0 AND YEAR(StartDate) = {$_GET["year"]} AND MONTH(StartDate) = {$_GET["month"]} AND SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_discount = mysqli_result($subres,0,'discount');
						$city_discount = $city_discount + $shop_discount;
						// Получаем скидку по салону по накладным (чтобы получить дебиторку)
						$query = "SELECT SUM(discount) discount FROM OrdersData OD WHERE OD.PFI_ID IS NOT NULL AND OD.Del = 0 AND YEAR(StartDate) = {$_GET["year"]} AND MONTH(StartDate) = {$_GET["month"]} AND SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_discount_inv = mysqli_result($subres,0,'discount');

						// Получаем сумму отказов по салону
						$query = "SELECT SUM(OT.old_sum) Price
									FROM Otkazi OT
									WHERE YEAR(OT.StartDate) = {$_GET["year"]} AND MONTH(OT.StartDate) = {$_GET["month"]} AND OT.SH_ID = {$row["SH_ID"]} AND OT.type = 2";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_otkaz = mysqli_result($subres,0,'Price');
						$city_otkaz = $city_otkaz + $shop_otkaz;

						// Вычисляем дебиторку по салону
						$query = "SELECT SUM(OP.payment_sum) payment_sum
									FROM OrdersData OD
									JOIN OrdersPayment OP ON OP.OD_ID = OD.OD_ID
									WHERE OD.Del = 0 AND YEAR(OD.StartDate) = {$_GET["year"]} AND MONTH(OD.StartDate) = {$_GET["month"]} AND OD.SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$month_payment_sum = mysqli_result($subres,0,'payment_sum');
						$shop_debt = ($shop_price - $shop_price_inv) - ($shop_discount - $shop_discount_inv) - $month_payment_sum;
						$city_debt = $city_debt + $shop_debt;

						// Узнаем остаток в кассе по салону
						$query = "SELECT SUM(IFNULL(pay_in,0)) - SUM(IFNULL(pay_out,0)) ostatok FROM MonthlySellInOut WHERE SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_cash = mysqli_result($subres,0,'ostatok');
						$city_cash = $city_cash + $shop_cash;

						$shop_percent = round($shop_discount / ($shop_price - $shop_discount) * 100, 2);
						$format_shop_price = number_format(($shop_price - $shop_discount), 0, '', ' ');
						$format_shop_discount = number_format($shop_discount, 0, '', ' ');
						$format_shop_otkaz = number_format($shop_otkaz, 0, '', ' ');
						$format_shop_debt = number_format($shop_debt, 0, '', ' ');
						$format_shop_cash = number_format($shop_cash, 0, '', ' ');

						echo "<tr>";
						echo "<td class='nowrap'><a href='{$location}&SH_ID={$row["SH_ID"]}' ".( $SH_ID == $row["SH_ID"] ? "style='color: #D65C4F;'" : "" ).">{$row["Shop"]}:</a></td>";
						echo "<td class='txtright'>{$format_shop_price}</td>";
						echo "<td class='txtright'>{$format_shop_discount}<i class='fa fa-question-circle' aria-hidden='true' title='{$shop_percent}%'></i></td>";
						echo "<td class='txtright' style='color: #911;'>{$format_shop_otkaz}</td>";
						echo "<td class='txtright'>{$format_shop_debt}</td>";
						echo "<td class='txtright'>{$format_shop_cash}</td>";
						echo "</tr>";
					}
					if( !$USR_Shop ) {
						$city_percent = round($city_discount / ($city_price - $city_discount) * 100, 2);
						$format_city_price = number_format($city_price - $city_discount, 0, '', ' ');
						$format_city_discount = number_format($city_discount, 0, '', ' ');
						$format_city_otkaz = number_format($city_otkaz, 0, '', ' ');
						$format_city_debt = number_format($city_debt, 0, '', ' ');
						$format_city_cash = number_format($city_cash, 0, '', ' ');
						echo "<tr>";
						echo "<td class='nowrap'><b><a href='{$location}' ".( $SH_ID ? "" : "style='color: #D65C4F;'" ).">ВСЕГО:</a></b></td>";
						echo "<td class='txtright'><b>{$format_city_price}</b></td>";
						echo "<td class='txtright'><b>{$format_city_discount}</b><i class='fa fa-question-circle' aria-hidden='true' title='{$city_percent}%'></td>";
						echo "<td class='txtright' style='color: #911;'><b>{$format_city_otkaz}</b></td>";
						echo "<td class='txtright'><b>{$format_city_debt}</b></td>";
						echo "<td class='txtright'><b>{$format_city_cash}</b></td>";
						echo "</tr>";
					}
				?>
				</tbody>
			</table>
			</div>

		<div id="accordion" style="display: inline-block; width: 400px;">
			<h3 id="section1"></h3>
			<div>
			<table class="main_table" style="margin: 0; display: table;">
				<tbody>
				<?
					$query = "SELECT OP.OP_ID
									,DATE_FORMAT(OP.payment_date, '%d.%m') payment_date_short
									,DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
									,OP.cost_name
									,OP.payment_sum
									,OD.Code
									,YEAR(OD.StartDate) year
									,MONTH(OD.StartDate) month
									,OD.OD_ID
									,OP.SH_ID
									,SH.Shop
								FROM OrdersPayment OP
								JOIN Shops SH ON SH.SH_ID = OP.SH_ID AND ".($SH_ID ? "SH.SH_ID = {$SH_ID}" : "SH.CT_ID = {$CT_ID}")."
								LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
								WHERE YEAR(OP.payment_date) = {$_GET["year"]} AND MONTH(OP.payment_date) = {$_GET["month"]} AND IFNULL(OP.payment_sum, 0) > 0 AND OP.terminal_payer IS NULL
								ORDER BY OP.payment_date DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$cache_sum = 0;
				while( $row = mysqli_fetch_array($res) ) {
					$format_sum = number_format($row["payment_sum"], 0, '', ' ');
					$cache_sum = $cache_sum + $row["payment_sum"];
					$cache_name = ( $row["Code"] ) ? "<b><a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}'>{$row["Code"]}</a></b>" : "<span>{$row["cost_name"]}</span>";
					echo "<tr>";
					echo "<td width='49'>{$row["payment_date_short"]}</td>";
					echo "<td width='70' class='txtright'><b>{$format_sum}</b></td>";
					echo "<td width='60'><span>{$row["Shop"]}</span></td>";
					echo "<td width='180'>{$cache_name}</td>";
					echo "<td width='22'>";
						if( $locking == 0 and $row["Code"] == '' ) { // Если месяц не закрыт
							echo "<a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' shop='{$row["SH_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' cost_date='{$row["payment_date"]}' sign='+' title='Изменить приход'><i class='fa fa-pencil fa-lg'></i></a>";
						}
					echo "</td>";
					echo "</tr>";
				}
				$format_cache_sum = number_format($cache_sum, 0, '', ' ');
				?>
				</tbody>
			</table>
			</div>

			<h3 id="section2"></h3>
			<div>
			<table class="main_table" style="margin: 0; display: table;">
				<tbody>
				<?
					$query = "SELECT OP.OP_ID
									,DATE_FORMAT(OP.payment_date, '%d.%m') payment_date_short
									,DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
									,OP.cost_name
									,ABS(OP.payment_sum) payment_sum
									,OD.Code
									,YEAR(OD.StartDate) year
									,MONTH(OD.StartDate) month
									,OD.OD_ID
									,OP.SH_ID
									,SH.Shop
								FROM OrdersPayment OP
								JOIN Shops SH ON SH.SH_ID = OP.SH_ID AND ".($SH_ID ? "SH.SH_ID = {$SH_ID}" : "SH.CT_ID = {$CT_ID}")."
								LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
								WHERE YEAR(OP.payment_date) = {$_GET["year"]} AND MONTH(OP.payment_date) = {$_GET["month"]} AND IFNULL(OP.payment_sum, 0) < 0 AND OP.terminal_payer IS NULL AND send IS NULL
								ORDER BY OP.payment_date DESC";

					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$sum_cost = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$sum_cost = $sum_cost + $row["payment_sum"];
						$format_cost = number_format($row["payment_sum"], 0, '', ' ');
						$cost_name = ( $row["Code"] ) ? "<b><a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}'>{$row["Code"]}</a></b>" : "<span>{$row["cost_name"]}</span>";
						echo "<tr>";
						echo "<td width='49'>{$row["payment_date_short"]}</td>";
						echo "<td width='70' class='txtright'><b>{$format_cost}</b></td>";
						echo "<td width='60'><span>{$row["Shop"]}</span></td>";
						echo "<td width='180'>{$cost_name}</td>";
						echo "<td width='22'>";
						if( $locking == 0 and $row["Code"] == '' ) { // Если месяц не закрыт
							echo "<a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' shop='{$row["SH_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' cost_date='{$row["payment_date"]}' sign='-' title='Изменить расход'><i class='fa fa-pencil fa-lg'></i></a>";
						}
						echo "</td>";
						echo "</tr>";
					}
					$format_sum_cost = number_format($sum_cost, 0, '', ' ');
				?>
				</tbody>
			</table>
			</div>

			<h3 id="section3"></h3>
			<div>
			<table class="main_table" style="margin: 0; display: table;">
				<tbody>
				<?
					$query = "SELECT OP.OP_ID
									,DATE_FORMAT(OP.payment_date, '%d.%m') payment_date_short
									,DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
									,OP.cost_name
									,ABS(OP.payment_sum) payment_sum
									,OD.Code
									,YEAR(OD.StartDate) year
									,MONTH(OD.StartDate) month
									,OD.OD_ID
									,OP.SH_ID
									,SH.Shop
									,OP.send
								FROM OrdersPayment OP
								JOIN Shops SH ON SH.SH_ID = OP.SH_ID AND ".($SH_ID ? "SH.SH_ID = {$SH_ID}" : "SH.CT_ID = {$CT_ID}")."
								LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
								WHERE YEAR(OP.payment_date) = {$_GET["year"]} AND MONTH(OP.payment_date) = {$_GET["month"]} AND IFNULL(OP.payment_sum, 0) < 0 AND OP.terminal_payer IS NULL AND send IS NOT NULL
								ORDER BY OP.payment_date DESC";

					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$sum_send = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$sum_send = $sum_send + $row["payment_sum"];
						$format_cost = number_format($row["payment_sum"], 0, '', ' ');
						echo "<tr>";
						echo "<td width='49'>{$row["payment_date_short"]}</td>";
						echo "<td width='70' class='txtright'><b>{$format_cost}</b></td>";
						echo "<td width='60'><span>{$row["Shop"]}</span></td>";
						echo "<td width='180'><span>{$row["cost_name"]}</span></td>";
						echo "<td width='22'>";
						if( $locking == 0 and $row["send"] != 2 ) { // Если месяц не закрыт
							echo "<a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' shop='{$row["SH_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' cost_date='{$row["payment_date"]}' sign='' title='Изменить отправку'><i class='fa fa-pencil fa-lg'></i></a>";
						}
						echo "</td>";
						echo "</tr>";
					}
					$format_sum_send = number_format($sum_send, 0, '', ' ');
				?>
				</tbody>
			</table>
			</div>

			<h3 id="section4"></h3>
			<div>
			<table class="main_table" style="margin: 0; display: table;">
				<tbody>
				<?
					$query = "SELECT DATE_FORMAT(OP.payment_date, '%d.%m') payment_date
									,OP.payment_sum
									,OP.terminal_payer
									,OD.Code
									,YEAR(OD.StartDate) year
									,MONTH(OD.StartDate) month
									,OD.OD_ID
									,SH.Shop
								FROM OrdersPayment OP
								JOIN Shops SH ON SH.SH_ID = OP.SH_ID AND ".($SH_ID ? "SH.SH_ID = {$SH_ID}" : "SH.CT_ID = {$CT_ID}")."
								JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID AND OD.Del = 0
								WHERE YEAR(OP.payment_date) = {$_GET["year"]} AND MONTH(OP.payment_date) = {$_GET["month"]} AND IFNULL(OP.payment_sum, 0) != 0 AND OP.terminal_payer IS NOT NULL
								ORDER BY OP.payment_date DESC";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$terminal_sum = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$format_sum = number_format($row["payment_sum"], 0, '', ' ');
						$terminal_sum = $terminal_sum + $row["payment_sum"];
						echo "<tr>";
						echo "<td width='49'>{$row["payment_date"]}</td>";
						echo "<td width='70' class='txtright'><b>{$format_sum}</b></td>";
						echo "<td width='60'><span>{$row["Shop"]}</span></td>";
						echo "<td width='60'><b><a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}'>{$row["Code"]}</a></b></td>";
						echo "<td width='140' class='nowrap'>{$row["terminal_payer"]}</td>";
						echo "</tr>";
					}
					$format_terminal_sum = number_format($terminal_sum, 0, '', ' ');
				?>
				</tbody>
			</table>
			</div>
			<h3 id="section5"></h3>
			<div>
			<table class="main_table" style="margin: 0; display: table;">
				<tbody>
					<?
					$query = "SELECT DATE_FORMAT(OT.StartDate, '%d.%m') reject_date
									,OD.OD_ID
									,OD.Code
									,SH.Shop
									,OT.old_sum
									,IF(OT.type = 1, 'Замена', 'Отказ') comment
									,OT.StartDate
									,OT.SH_ID
								FROM OrdersData OD
								JOIN Otkazi OT ON OT.OD_ID = OD.OD_ID
								JOIN Shops SH ON SH.SH_ID = OT.SH_ID ".($SH_ID ? "AND SH.SH_ID = {$SH_ID}" : "AND SH.CT_ID = {$CT_ID}")."
								WHERE YEAR(OT.StartDate) = {$_GET["year"]} AND MONTH(OT.StartDate) = {$_GET["month"]}";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$reject_count = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$format_old_price = number_format($row["old_sum"], 0, '', ' ');
						++$reject_count;
						echo "<tr>";
						echo "<td width='49'><span class='nowrap'>{$row["reject_date"]}</span></td>";
						echo "<td width='70' class='txtright'><b>{$format_old_price}</b></td>";
						echo "<td width='60'><span>{$row["Shop"]}</span></td>";
						echo "<td width='60'><b><a href='?CT_ID={$CT_ID}#ord{$row["OD_ID"]}'>{$row["Code"]}</a></b></td>";
						echo "<td width='120' style='color: #911;'>{$row["comment"]}</td>";
						echo "<td width='22'><a href='#' onclick='if(confirm(\"Убрать заказ <b>{$row["Code"]}</b> из списка отмененных/замененных?\", \"?del_otkaz={$row["OD_ID"]}&StartDate={$row["StartDate"]}&SH_ID={$row["SH_ID"]}&CT_ID={$CT_ID}&year={$year}&month={$month}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a></td>";
						echo "</tr>";
					}
					?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
	<?
		echo "<script>
			$(document).ready(function() {
				$('.wr_main_table_body').css('height', 'calc(100vh - 430px)');
				$('#MT_header').css('margin-top','210px');
				$('#section1').html('ПРИХОД наличных: {$format_cache_sum}');
				$('#section2').html('РАСХОД наличных: {$format_sum_cost}');
				$('#section3').html('ОТПРАВКА наличных: {$format_sum_send}');
				$('#section4').html('Оплата по ТЕРМИНАЛУ: {$format_terminal_sum}');
				$('#section5').html('Отказы/замены: {$reject_count}');
			});
		</script>";
	}
	?>
	<!--Форма для диалога печати-->
	<form id="print_selling"></form>

	<!--Кнопка печати-->
	<div id="print_btn" title="Распечатать таблицу">
		<a id="toprint" style="display: block;"></a>
	</div>

	<br>
	<table class="main_table" id="MT_header">
		<form method="get">
		<thead>
			<tr>
				<th width="55">Дата отгрузки</th>
				<th width="59">Код</th>
				<th width="5%">Заказчик<br>Квитанция</th>
				<th width="25%">Наименование</th>
				<th width="15%">Материал</th>
				<th width="15%">Цвет</th>
				<th width="40">Кол-во</th>
				<th width="10%">Салон<br>
					<?
					if( !$USR_Shop ) {
					?>
					<input type="hidden" name="CT_ID" value="<?=$_GET['CT_ID']?>">
					<input type="hidden" name="year" value="<?=$_GET['year']?>">
					<input type="hidden" name="month" value="<?=$_GET['month']?>">
					<select style="width: 100%;" name="SH_ID" id="filter_shop" onchange="this.form.submit()" <?=( $_GET["SH_ID"] ? "class='filtered'" : "" )?>>
						<option></option>
						<?
							$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND KA_ID IS NULL ORDER BY SH_ID";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) ) {
								echo "<option value='{$row['SH_ID']}' ".( $row['SH_ID'] == $_GET['SH_ID'] ? 'selected' : '' ).">{$row['Shop']}</option>";
							}
						?>
					</select>
					<?
					}
					?>
				</th>
				<th width="100">Примечание</th>
				<th width="100">Дата продажи</th>
				<th width="65">Сумма заказа</th>
				<th width="70">Скидка</th>
				<th width="65">Оплата</th>
				<th width="20">Т</th>
				<th width="65">Остаток</th>
<!--				<th width="65">Отказ</th>-->
				<th width="70">Действие</th>
			</tr>
		</thead>
		</form>
	</table>
<div class="wr_main_table_body" style="display: none;">
	<table class="main_table">
		<thead>
			<tr>
				<th width="55"></th>
				<th width="59"></th>
				<th width="5%"></th>
				<th width="25%"></th>
				<th width="15%"></th>
				<th width="15%"></th>
				<th width="40"></th>
				<th width="10%"></th>
				<th width="100"></th>
				<th width="100"></th>
				<th width="65"></th>
				<th width="70"></th>
				<th width="65"></th>
				<th width="22"></th>
				<th width="65"></th>
<!--				<th width="65"></th>-->
				<th width="70"></th>
			</tr>
		</thead>
		<tbody>
		<?
		$query = "SELECT OD.OD_ID
						,OD.Code
						,IFNULL(OD.ClientName, '') ClientName
						,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
						,DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') ReadyDate
						,OD.sell_comment
						,OD.ReadyDate RD
						,SH.SH_ID
						,OD.OrderNumber
						,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
						,GROUP_CONCAT(ODD_ODB.Amount SEPARATOR '') Amount
						,OD.Color
						,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
						,SUM(ODD_ODB.Price) - IFNULL(OD.discount, 0) Price
						,SUM(ODD_ODB.opt_price) opt_price
						,IFNULL(OD.discount, 0) discount
						,ROUND(IFNULL(OD.discount, 0) / SUM(ODD_ODB.Price) * 100) percent
						,IFNULL(OP.payment_sum, 0) payment_sum
						,OP.terminal_payer
						,IF(IFNULL(OP.check_payment, 0) > 0, CheckPayment(OD.OD_ID), 0) attention
						,IF(OS.locking_date IS NOT NULL, 1, 0) is_lock
						,OD.confirmed
						,IF(PFI.rtrn = 1, NULL, OD.PFI_ID) PFI_ID
						,PFI.count
						#,IFNULL(OT.type, 0) type
						#,IFNULL(OT.comment, '') comment
						#,IFNULL(OT.old_SH_ID, OD.SH_ID) old_SH_ID
						#,IFNULL(OT.old_StartDate, OD.StartDate) old_StartDate
					FROM OrdersData OD
					JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.KA_ID IS NULL
						".( $SH_ID ? " AND SH.SH_ID = {$SH_ID}" : "" )."
					LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
					LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
					LEFT JOIN (
						SELECT OP.OD_ID
							,SUM(OP.payment_sum) payment_sum
							,GROUP_CONCAT(OP.terminal_payer) terminal_payer
							,SUM(IF(OD.SH_ID != OP.SH_ID AND OP.terminal_payer IS NULL, 1, 0)) check_payment
						FROM OrdersPayment OP
						JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
						WHERE IFNULL(payment_sum, 0) != 0
						GROUP BY OP.OD_ID
					) OP ON OP.OD_ID = OD.OD_ID
					#LEFT JOIN (SELECT OD_ID, SUM(payment_sum) payment_sum, GROUP_CONCAT(terminal_payer) terminal_payer FROM OrdersPayment WHERE IFNULL(payment_sum, 0) != 0 GROUP BY OD_ID) OP ON OP.OD_ID = OD.OD_ID
					LEFT JOIN (
						SELECT ODD.OD_ID
								,IFNULL(PM.PT_ID, 2) PT_ID
								,ODD.ODD_ID itemID
								,ODD.Price * ODD.Amount Price
								,ODD.opt_price * ODD.Amount opt_price

								,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('патина (', ODD.patina, ')'), ''), '</i></b><br>') Zakaz

								,CONCAT(IFNULL(CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')'), ''), '<br>') Material
								,CONCAT(ODD.Amount, '<br>') Amount

						FROM OrdersDataDetail ODD
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
						WHERE ODD.Del = 0
						GROUP BY ODD.ODD_ID
						UNION ALL
						SELECT ODB.OD_ID
								,0 PT_ID
								,ODB.ODB_ID itemID
								,ODB.Price * ODB.Amount Price
								,ODB.opt_price * ODB.Amount opt_price

								,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('патина (', ODB.patina, ')'), ''), '</i></b><br>') Zakaz

								,CONCAT(IFNULL(CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')'), ''), '<br>') Material
								,CONCAT(ODB.Amount, '<br>') Amount

						FROM OrdersDataBlank ODB
						LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
						LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
						WHERE ODB.Del = 0
						GROUP BY ODB.ODB_ID
						ORDER BY PT_ID DESC, itemID
						) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
					WHERE OD.Del = 0 AND SH.CT_ID = {$CT_ID}
					".(($year != '' and $month != '') ? (($year == 0 and $month == 0) ? ' AND OD.StartDate IS NULL' : ' AND MONTH(OD.StartDate) = '.$month.' AND YEAR(OD.StartDate) = '.$year) : '')."
					GROUP BY OD.OD_ID
					#HAVING Price - payment_sum <> 0 OR Price IS NULL OR DATEDIFF(NOW(), RD) <= {$datediff}
					#".(($year != '' and $month != '') ? "" : "HAVING Price - payment_sum <> 0 OR is_lock = 0")."
					".(($year != '' and $month != '') ? "" : "HAVING is_lock = 0")."
					ORDER BY IFNULL(OD.ReadyDate, '9999-01-01') ASC, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID ASC";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$is_lock = $row["is_lock"];			// Месяц закрыт в реализации
//			$confirmed = $row["confirmed"];		// Заказ принят в работу
//			// Запрет на редактирование
//			$disabled = !( in_array('order_add', $Rights) and ($confirmed == 0 or in_array('order_add_confirm', $Rights)) and $is_lock == 0 and $row["Del"] == 0 );
			$format_price = number_format($row["Price"], 0, '', ' ');
			$format_opt_price = number_format($row["opt_price"], 0, '', ' ');
			$format_payment = number_format($row["payment_sum"], 0, '', ' ');
			$format_discount = number_format($row['discount'], 0, '', ' ');
			$format_diff = number_format($row["Price"] - $row["payment_sum"], 0, '', ' ');
			$diff_color = ($row["Price"] == $row["payment_sum"]) ? "#6f6" : (($row["Price"] < $row["payment_sum"]) ? "#f66" : "#fff");
			$otkaz_cell = ($row["type"] == 1) ? "<b>Замена</b><br>{$row["comment"]}" : (($row["type"] == 2) ? "<b>Отказ</b><br>{$row["comment"]}" : "");
			echo "
				<tr id='ord{$row["OD_ID"]}'>
					<td>
						<input type='hidden' name='OD_ID[]' form='print_selling' value='{$row["OD_ID"]}'>
						<span>{$row["ReadyDate"]}</span>
					</td>
					<td>{$row["Code"]}</td>
					<td><span>{$row["ClientName"]}<br><b>{$row["OrderNumber"]}</b></span></td>
					<td><span class='nowrap'>{$row["Zakaz"]}</span></td>
					<td><span class='nowrap material'>{$row["Material"]}</span></td>
					<td>{$row["Color"]}</td>
					<td class='material'><b style='font-size: 1.3em;'>{$row["Amount"]}</b></td>
					<td id='{$row["OD_ID"]}'><span><select style='width: 100%;' ".(($is_lock or $USR_Shop) ? "disabled" : "class='select_shops'").">{$select_shops}</select></span></td>
					<td id='{$row["OD_ID"]}'><input type='text' class='sell_comment' value='". htmlspecialchars($row["sell_comment"], ENT_QUOTES) ."'></td>";

					// Если заказ в накладной - под датой продажи ссылка на накладную, под ценой стоимость по накладной
					if( $row["PFI_ID"] ) {
						$invoice = "<br><b><a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'>Накладная</a></b>";
						$invoice_price = "<br><b title='Стоимость по накладной'>{$format_opt_price}<i class='fa fa-question-circle' aria-hidden='true'></i></b>";
					}
					else {
						$invoice = "";
						$invoice_price = "";
					}

			echo "<td id='{$row["OD_ID"]}'><input ".($is_lock ? "disabled" : "")." type='text' class='date sell_date' value='{$row["StartDate"]}' readonly ".(($row["StartDate"] and !$is_lock) ? "title='Чтобы стереть дату продажи нажмите на символ ладошки справа.'" : "").">{$invoice}</td>
					<td class='txtright'><button style='width: 100%;' class='update_price_btn button nowrap txtright' id='{$row["OD_ID"]}'>{$format_price}</button>{$invoice_price}</td>
					<td class='txtright nowrap'>{$format_discount} p.<br>{$row["percent"]} %</td>
					<td><button ".($row["PFI_ID"] ? "disabled" : "")." style='width: 100%;' class='add_payment_btn button nowrap txtright ".($row["attention"] ? "attention" : "")."' id='{$row["OD_ID"]}' ".($row["attention"] ? "title='Имеются платежи, внесённые в кассу другого салона!'" : "").">{$format_payment}</button></td>
					<td>".($row["terminal_payer"] ? "<i title='Оплата по терминалу' class='fa fa-credit-card' aria-hidden='true'></i>" : "")."</td>";

					// Если в накладной - не показываем остаток
					if( $row["PFI_ID"] ) {
						echo "<td></td>";
					}
					else {
						echo "<td class='txtright' style='background: {$diff_color}'>{$format_diff}</td>";
					}
//					echo "<td><span style='color: #911;'>{$otkaz_cell}</span></td>";
					echo "<td>";

			// Если есть права на редактирование заказа и заказ не закрыт, то показываем карандашь, кнопку разделения и отказа
			if( in_array('order_add', $Rights) and !$is_lock ) {
				echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Редактировать'><i class='fa fa-pencil fa-lg'></i></a> ";
				echo "<a href='#' id='{$row["OD_ID"]}' class='order_cut' title='Разделить заказ' location='{$location}'><i class='fa fa-sliders fa-lg'></i></a> ";
				echo "<a href='#' id='{$row["OD_ID"]}' class='order_otkaz_btn' invoice={$row["PFI_ID"]} location='{$location}' payment='{$row["payment_sum"]}' old_sum='{$row["Price"]}' title='Пометить как отказ/замена.'><i class='fa fa-hand-paper-o fa-lg' aria-hidden='true'></i></a>";
			}
			else {
				echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Посмотреть'><i class='fa fa-eye fa-lg'></i></a> ";
			}

			echo "</td></tr>";
			echo "<script>";
			echo "$('#ord{$row["OD_ID"]} select').val('{$row["SH_ID"]}');";
			echo "</script>";
		}
		?>
		</tbody>
	</table>
</div>

<!-- Форма добавления оплаты -->
<style>
	#add_payment table {
		text-align: center;
	}
	#add_payment input.payment_sum {
		width: 70px;
		text-align: right;
	}
	#add_payment input.terminal_payer {
		width: 180px;
	}
</style>
<div id='add_payment' title='Добавление оплаты' style='display:none'>
	<form method='post' action="<?=$location?>&add_payment=1">
		<fieldset>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы добавления оплаты -->

<!-- Форма редактирования суммы заказа -->
<div id='update_price' title='Изменение суммы заказа' style='display:none'>
	<form method='post' action="<?=$location?>&add_price=1">
		<fieldset>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы редактирования суммы заказа -->

<!-- Форма добавления/редактирования расхода/прихода -->
<div id='add_cost' style='display:none'>
	<form method='post' action='<?=$location?>&add_cost=1'>
		<fieldset>
			<input type="hidden" name="OP_ID" id="OP_ID">
			<input type="hidden" name="sign" id="sign">
			<div style="width: 100px; display: inline-block; margin-right: 15px; vertical-align: top;">
				<label for="cost">Сумма:</label><br>
				<input type="number" name="cost" min="0" id="cost" style="width: 100%; text-align: right;">
			</div>
			<div style="width: 90px; display: inline-block; vertical-align: top;">
				<label for="cost_date">Дата:</label><br>
				<input readonly type="text" name="cost_date" class="" id="cost_date" style="width: 100%; text-align: center;">
			</div>
			<br><br>
			<div style="width: 210px; display: inline-block; vertical-align: top;">
				<label for="SH_ID">Касса:</label><br>
				<select size="4" name="SH_ID" id="SH_ID" style="width: 100%;" required>
					<?
					$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND KA_ID IS NULL ".($USR_Shop ? "AND SH_ID = {$USR_Shop}" : "")." ORDER BY SH_ID";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option ".($USR_Shop ? "selected" : "")." value='{$row["SH_ID"]}'>{$row["Shop"]}</option>";
					}
					?>
				</select>
			</div>
			<br><br>
			<div style="width: 210px; display: inline-block; vertical-align: top;">
				<label for="cost_name">Комментарий:</label><br>
				<input type="text" name="cost_name" id="cost_name" style="width: 100%;">
				<div id="wr_send" style="display: none">
					<input type="checkbox" name="send" id="send" value="1">
					<label for="send">Отправка наличных</label>
				</div>
			</div>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы добавления/редактирования расхода/прихода -->

<!-- Форма отказа -->
<div id='order_otkaz' style='display:none'>
	<form method='post' action="<?=$location?>&order_otkaz=1">
		<div style="display: inline-block;">
			<i class='fa fa-hand-paper-o fa-4x' aria-hidden='true'></i>
		</div>
		<fieldset style="display: inline-block; width: calc(100% - 65px);">
			<input type="hidden" name="OD_ID">
			<input type="hidden" name="old_sum">
			<label for='type'>Тип отказа:</label>
			<div class='btnset' id="type" style="display: inline-block;">
				<label for="otkaz1" style="width: 80px;">Замена</label>
				<input required type="radio" name="type" id="otkaz1" value="1">
				<label for="otkaz2" style="width: 80px;">Отказ</label>
				<input required type="radio" name="type" id="otkaz2" value="2">
			</div>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы отказа -->

<script>
	$(document).ready(function() {
		// Данные для печати
		print_data = $('#print_selling').serialize();
		$("#toprint").attr('href', '/toprint/print_selling.php?' + print_data);

		// Открытие диалога печати
		$("#toprint").printPage();

		$( "#accordion" ).accordion({
			active: false,
			collapsible: true,
			heightStyle: "content"
		});

		//$('.wr_main_table_body').show('slow');
		$('.wr_main_table_body').css('display', 'block');

		$( ".button" ).button( "option", "classes.ui-button", "highlight" );

		// Кнопка добавления платежа
		$('.add_payment_btn').click( function() {
			var OD_ID = $(this).attr('id');
			$.ajax({ url: "ajax.php?do=add_payment&OD_ID="+OD_ID, dataType: "script", async: false });

			$('#add_payment').dialog({
				width: 650,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			$('input[name=payment_sum_add]').focus();
			$('input.date').datepicker();
			$( "#add_payment input.date" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );

			$('#add_payment .terminal').change(function() {
				var ch = $(this).prop('checked');
				var terminal_payer = $(this).parents('tr').find('input[type="text"].terminal_payer');
				var terminal_payer_hidden = $(this).parents('tr').find('input[type="hidden"].terminal_payer');
				var account = $(this).parents('tr').find('select.account');
				if( ch ) {
					$(terminal_payer).prop('disabled', false);
					$(terminal_payer).prop('required', true);
					$(terminal_payer_hidden).val( $(terminal_payer).val() );
					$(account).prop('disabled', true);
					$(account).hide('fast');
				}
				else {
					$(terminal_payer).prop('disabled', true);
					$(terminal_payer).prop('required', false);
					$(terminal_payer_hidden).val('');
					$(account).prop('disabled', false);
					$(account).show('fast');
				}
			});

			$('#add_payment .terminal').change();
			return false;
		});

		// Кнопка редактирования суммы заказа
		$('.update_price_btn').click( function() {
			var OD_ID = $(this).attr('id');
			$.ajax({ url: "ajax.php?do=update_price&OD_ID="+OD_ID, dataType: "script", async: false });

			$('#update_price').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			function updprice() {
				var prod_total = 0;
				var discount = $('#discount input').val();
				var percent = 0;
				$('.prod_price').each(function(){
					var prod_price = $(this).find('input').val();
					var prod_amount = $(this).parents('tr').find('.prod_amount').html();
					var prod_sum = prod_price * prod_amount;
					prod_total = prod_total + prod_sum;
					prod_sum = prod_sum.format();
					$(this).parents('tr').find('.prod_sum').html(prod_sum);
				});
				percent = (discount / prod_total * 100).toFixed(2);
				prod_total = prod_total - discount;
				$('#prod_total input').val(prod_total);
				$('#discount span').html(percent);
			}

			function upddiscount() {
				var prod_total = $('#prod_total input').val();
				var discount = prod_total * -1;
				var percent = 0;
				$('.prod_price').each(function(){
					var prod_price = $(this).find('input').val();
					var prod_amount = $(this).parents('tr').find('.prod_amount').html();
					var prod_sum = prod_price * prod_amount;
					discount = discount + prod_sum;
				});
				percent = (discount / prod_total * 100).toFixed(2);
				prod_total = prod_total - discount;
				if( discount == 0 ) {
					discount = '';
				}
				$('#discount input').val(discount);
				$('#discount span').html(percent);
			}

			updprice();

			$('.prod_price input, #discount input').on('input', function() {
				updprice();
			});

			$('.prod_price input').on( "autocompleteselect", function( event, ui ) {
				$(this).val( ui.item.value );
				updprice();
			});

			$('#prod_total input').on('input', function() {
				upddiscount();
			});

			$('#discount input').on('input', function() {
				if( $(this).val() == 0 ) {
					$(this).val('');
				}
			});

			// Раскрытие автокомплита на фокусе
			$( '.prod_price input' ).focus(function(){
				$(this).autocomplete( "search", "1" );
			});
			// Автокомплит поверх диалога
			$( '.prod_price input' ).autocomplete( "option", "appendTo", "#update_price" );

			return false;
		});

		// Кнопка добавления/редактирования расхода
		$('.add_cost_btn').click( function() {
			var sign = $(this).attr('sign');
			var cost_date = $(this).attr('cost_date');
			var shop = $(this).attr('shop');
			var OP_ID = $(this).attr('id');
			var cost_name = $(this).attr('cost_name');
			var cost = $(this).attr('cost');

			$('#add_cost #SH_ID').val(shop);
			$('#add_cost #cost_date').val(cost_date);
			$('#add_cost #OP_ID').val(OP_ID);
			$('#add_cost #cost_name').val(cost_name);
			$('#add_cost #cost').val(cost);
			$('#send').prop('checked', false);

			$('#add_cost').dialog({
				width: 300,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			$( "#cost_date" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );

			if (sign == '+') {
				$('#add_cost').dialog('option', 'title', 'ПРИХОД');
				$('#add_cost fieldset').css('background', '#9f9');
				$('#add_cost #sign').val(sign);
			}
			else if (sign == '-') {
				$('#add_cost').dialog('option', 'title', 'РАСХОД');
				$('#add_cost fieldset').css('background', '#f99');
				$('#add_cost #sign').val(sign);
			}
			else {
				$('#add_cost').dialog('option', 'title', 'ОТПРАВКА ДЕНЕГ');
				$('#add_cost fieldset').css('background', '#99f');
				$('#wr_send input').prop('checked', true);
				$('#add_cost #sign').val('-');
			}
			return false;
		});

		// Кнопка изменения статуса отказа
		$('.order_otkaz_btn').click( function() {
			var invoice = $(this).attr('invoice');
			var OD_ID = $(this).attr('id');
			var old_sum = $(this).attr('old_sum');
			var payment = $(this).attr('payment');

			// Заполнение формы
			$('#order_otkaz input[name="OD_ID"]').val(OD_ID);
			$('#order_otkaz input[name="old_sum"]').val(old_sum);

			if( invoice > 0 ) {
				noty({timeout: 3000, text: 'Прежде чем пометить заказ как "отказной", анулируйте накладную в актах сверки.', type: 'error'});
			}
			else if( payment != 0 ) {
				$(this).parents('tr').find('.add_payment_btn span').effect( 'shake', 1000 );
				noty({timeout: 3000, text: 'Прежде чем пометить заказ как "отказной", обнулите приход по нему.', type: 'error'});
			}
			else {
				$('#order_otkaz').dialog({
					width: 400,
					modal: true,
					show: 'blind',
					hide: 'explode',
					closeText: 'Закрыть'
				});
			}
		});

		// Редактирование салона
		$('.select_shops').on('change', function() {
			var OD_ID = $(this).parents('td').attr('id');
			var val = $(this).val();
			$.ajax({ url: "ajax.php?do=update_shop&OD_ID="+OD_ID+"&SH_ID="+val, dataType: "script", async: false });
		});

		// Редактирование даты продажи
		$('.sell_date').on('change', function() {
			var OD_ID = $(this).parents('td').attr('id');
			var val = $(this).val();
			$.ajax({ url: "ajax.php?do=update_sell_date&OD_ID="+OD_ID+"&StartDate="+val, dataType: "script", async: false });
		});

		// Редактирование примечания к реализации
		$('.sell_comment').on('change', function() {
			var OD_ID = $(this).parents('td').attr('id');
			var val = $(this).val();
			$.ajax({ url: "ajax.php?do=update_sell_comment&OD_ID="+OD_ID+"&sell_comment="+val, dataType: "script", async: false });
		});
	});
</script>

<?
	include "footer.php";
?>
