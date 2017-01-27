<?
	include "config.php";
	$title = 'Реализация';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('selling_all', $Rights) and !in_array('selling_city', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$CT_ID = $_GET["CT_ID"] ? $_GET["CT_ID"] : 0;
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
	$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND retail = 1 ORDER BY Shop";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$select_shops = "<select class='select_shops'>";
	while( $row = mysqli_fetch_array($res) ) {
		$select_shops .= "<option value='{$row["SH_ID"]}'>{$row["Shop"]}</option>";
	}
	$select_shops .= "</select>";

	$datediff = 60; // Максимальный период отображения данных
//	$datediff = $_SESSION['id'] == 1 ? 600 : 60;

	//$location = $_SERVER['REQUEST_URI'];
	$location = "selling.php?CT_ID={$_GET["CT_ID"]}".( ($_GET["year"] != '' and $_GET["month"] != '') ? '&year='.$_GET["year"].'&month='.$_GET["month"] : '' );
	//$_SESSION["location"] = $location;
	$_SESSION["location"] = $_SERVER['REQUEST_URI'];

	// Добавление в базу нового платежа (или обновление старого)
	if( isset($_GET["add_payment"]) )
	{
		$OD_ID = $_POST["OD_ID"];
		$payment_date = date( 'Y-m-d', strtotime($_POST["payment_date_add"]) );
		$payment_sum = $_POST["payment_sum_add"];
		$terminal = $_POST["terminal_add"];
		$terminal_payer = $terminal ? '\''.mysqli_real_escape_string( $mysqli, $_POST["terminal_payer_add"] ).'\'' : 'NULL';

		if( $payment_sum ) {
			// Записываем новый платеж в таблицу платежей
			$query = "INSERT INTO OrdersPayment
						 SET OD_ID = {$OD_ID}
							,payment_date = '{$payment_date}'
							,payment_sum = {$payment_sum}
							,terminal_payer = {$terminal_payer}
							,CT_ID = ".(in_array('order_add_confirm', $Rights) ? 'NULL' : $CT_ID);
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"] = mysqli_error( $mysqli );
			}
			else {
				// Записываем дату продажи заказа если ее не было
				$query = "UPDATE OrdersData SET StartDate = '{$payment_date}', author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID} AND StartDate IS NULL";
				if( !mysqli_query( $mysqli, $query ) ) {
					$_SESSION["alert"] = mysqli_error( $mysqli );
				}
			}
		}

		foreach ($_POST["OP_ID"] as $key => $value) {
			$payment_date = date( 'Y-m-d', strtotime($_POST["payment_date"][$key]) );
			$payment_sum = ($_POST["payment_sum"][$key] != '') ? $_POST["payment_sum"][$key] : 'NULL';
			$terminal_payer = ($_POST["terminal_payer"][$key] != '') ? '\''.mysqli_real_escape_string( $mysqli, $_POST["terminal_payer"][$key] ).'\'' : 'NULL';

			$query = "UPDATE OrdersPayment
						 SET payment_date = '{$payment_date}'
							,payment_sum = {$payment_sum}
							,terminal_payer = {$terminal_payer}
						WHERE OP_ID = {$value}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"] = mysqli_error( $mysqli );
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
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["alert"] = mysqli_error( $mysqli );
		}

		foreach ($_POST["PT_ID"] as $key => $value) {
			$price = $_POST["price"][$key] ? $_POST["price"][$key] : "NULL";
			if( $value == 0 ) {
				$query = "UPDATE OrdersDataBlank SET Price = {$price}, author = {$_SESSION['id']} WHERE ODB_ID = {$_POST["itemID"][$key]}";
			}
			else {
				$query = "UPDATE OrdersDataDetail SET Price = {$price}, author = {$_SESSION['id']} WHERE ODD_ID = {$_POST["itemID"][$key]}";
			}
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"] = mysqli_error( $mysqli );
			}
		}
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		die;
	}

	// Добавление/редактирование расхода/прихода
	if( isset($_GET["add_cost"]) )
	{
		$OP_ID = $_POST["OP_ID"];
		$cost_name = mysqli_real_escape_string( $mysqli, $_POST["cost_name"] );
		$cost_date = date( 'Y-m-d', strtotime($_POST["cost_date"]) );
		$cost = $_POST["cost"] ? $_POST["cost"] : 0;
		$cost = ($_POST["sign"] == '-') ? $cost * -1 : $cost;

		if( $OP_ID != '' ) { // Редактируем расход
			$query = "UPDATE OrdersPayment SET cost_name = '{$cost_name}', payment_date = '{$cost_date}', payment_sum = {$cost} WHERE OP_ID = {$OP_ID}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		else { // Добавляем расход
			if( $cost ) {
				$CT_ID = $_POST["CT_ID"];
				$query = "INSERT INTO OrdersPayment SET CT_ID = {$CT_ID}, cost_name = '{$cost_name}', payment_date = '{$cost_date}', payment_sum = {$cost}";
				if( !mysqli_query( $mysqli, $query ) ) {
					$_SESSION["alert"] = mysqli_error( $mysqli );
				}
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Блокировка отчета
	if( isset($_GET["add_locking_date"]) ) {
		$locking_date = date( 'Y-m-d', strtotime($_POST["locking_date"]) );
		if( $_POST["locking_date"] != '' ) {
//			$query = "INSERT INTO OstatkiShops
//				SET CT_ID = {$_GET["CT_ID"]}, year = {$_GET["year"]}, month = {$_GET["month"]}, ostatok = {$_POST["ostatok"]}, locking_date = '{$locking_date}'
//				ON DUPLICATE KEY UPDATE ostatok = {$_POST["ostatok"]}, locking_date = '{$locking_date}'";
			$query = "INSERT INTO OstatkiShops
				SET CT_ID = {$_GET["CT_ID"]}, year = {$_GET["year"]}, month = {$_GET["month"]}, locking_date = '{$locking_date}'
				ON DUPLICATE KEY UPDATE locking_date = '{$locking_date}'";
		}
		else {
//			$query = "UPDATE OstatkiShops SET ostatok = NULL, locking_date = NULL WHERE CT_ID = {$_GET["CT_ID"]} AND year = {$_GET["year"]} AND month = {$_GET["month"]}";
			$query = "UPDATE OstatkiShops SET locking_date = NULL WHERE CT_ID = {$_GET["CT_ID"]} AND year = {$_GET["year"]} AND month = {$_GET["month"]}";
		}
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Отказ
	if( isset($_GET["order_otkaz"]) ) {
		$OD_ID = $_POST["OD_ID"];
		$old_SH_ID = $_POST["old_SH_ID"];
		$old_StartDate = ($_POST["old_StartDate"] != '') ? '\''.$_POST["old_StartDate"].'\'' : 'NULL';
		$old_sum = $_POST["old_sum"];
		$type = $_POST["type"];
		$comment = ($_POST["comment"] != '') ? '\''.mysqli_real_escape_string( $mysqli, $_POST["comment"] ).'\'' : 'NULL';

		if( $type > 0 ) {
			$query = "INSERT INTO Otkazi
				SET OD_ID = {$OD_ID}, type = {$type}, comment = {$comment}, old_SH_ID = {$old_SH_ID}, old_StartDate = {$old_StartDate}, old_sum = {$old_sum}
				ON DUPLICATE KEY UPDATE type = {$type}, comment = {$comment}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$query = "UPDATE OrdersData SET StartDate = NULL, author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$_SESSION["alert"] = "Заказ перемещен в \"Свободные\"";
		}
		else {
			$query = "DELETE FROM Otkazi WHERE OD_ID = {$OD_ID}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$query = "UPDATE OrdersData SET StartDate = {$old_StartDate}, author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
			//mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			mysqli_query( $mysqli, $query );
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
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
</style>

<form method="get" style="display: inline-block;">
	<select name="CT_ID" onchange="this.form.submit()">
		<option value="">-=Выберите город=-</option>
		<?
		$query = "SELECT CT.CT_ID, CT.City, CT.Color
					FROM Cities CT
					JOIN Shops SH ON SH.CT_ID = CT.CT_ID AND SH.retail = 1
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
</form>

<?
	// Узнаем общий остаток наличных
	$query = "SELECT SUM(pay_in) - SUM(pay_out) ostatok FROM `OstatkiShops` WHERE CT_ID = {$CT_ID}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$ostatok = mysqli_result($res,0,'ostatok');
	$format_ostatok = number_format($ostatok, 0, '', ' ');
	$now_date = date('d.m.Y');
	if( $CT_ID ) {
?>
<h3 style="display: inline-block; margin: 10px 20px;">Остаток наличных: <?=$format_ostatok?></h3>
<a href='#' class='add_cost_btn' cost_date='<?=$now_date?>' sign='+' CT_ID='<?=$CT_ID?>' title='Внести приход'><i class='fa fa-plus-square fa-2x' style='color: green;'></i></a>
<a href='#' class='add_cost_btn' cost_date='<?=$now_date?>' sign='-' CT_ID='<?=$CT_ID?>' title='Внести расход'><i class='fa fa-minus-square fa-2x' style='color: red;'></i></a>
<?
				 }
else {
	echo "<h3 style='display: inline-block; margin: 10px 20px;'>&nbsp;</h3>";
}
?>

	<!-- КНОПКИ ОТЧЕТОВ -->
	<div style="max-height: 23px;">
		Отчеты:
		<?
		$highlight = ($_GET["year"] == '' or $_GET["month"] == '') ? 'border: 1px solid #fbd850; color: #eb8f00;' : '';
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
					JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
					LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = {$CT_ID}
					WHERE OD.Del = 0 AND SH.CT_ID = {$CT_ID}
					GROUP BY YEAR(OD.StartDate), MONTH(OD.StartDate)

					UNION

					SELECT YEAR(NOW()), MONTH(NOW()), 0, 1
					ORDER BY year, month";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		if( $CT_ID ) {
			while( $row = mysqli_fetch_array($res) ) {
				$highlight = ($_GET["year"] == $row["year"] and $_GET["month"] == $row["month"]) ? 'border: 1px solid #fbd850; color: #eb8f00;' : '';
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
		if( ($_GET["year"] != '' or $_GET["month"] != '') and !in_array("{$_GET["year"]}-{$_GET["month"]}", $OTCHET_MONTHS) ) {
			die('<h3>'.$MONTHS["{$_GET["month"]}"].'-'.$_GET["year"].' отсутствует в списке доступных отчетов.<h3>');
		}
		?>
	</div>
	<!-- //КНОПКИ ОТЧЕТОВ -->

	<?
	// ОТЧЕТ ЗА МЕСЯЦ
	if( $_GET["year"] > 0 and $_GET["month"] > 0 ) {
		// Узнаем дату закрытия месяца
		$query = "SELECT DATE_FORMAT(locking_date, '%d.%m.%Y') locking_date
					FROM OstatkiShops
					WHERE CT_ID = {$_GET["CT_ID"]} AND year = {$_GET["year"]} AND month = {$_GET["month"]}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$locking = mysqli_result($res,0,'locking_date') ? 1 : 0;
		$locking_date = mysqli_result($res,0,'locking_date');

//		// Узнаем закрыт ли следующий месяц
//		$query = "SELECT locking_date
//					FROM OstatkiShops
//					WHERE CT_ID = {$_GET["CT_ID"]} AND year = {$nextyear} AND month = {$nextmonth}";
//		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//		$locking_next = mysqli_result($res,0,'locking_date') ? 1 : 0;
	?>
		<div id='selling_report'>
			<div style="display: inline-block; vertical-align:top;">

			<?
				$locking_form = "
					<form method='post' action='{$location}&add_locking_date=1' id='locking_form'>
						<input type='text' class='date' name='locking_date' value='{$locking_date}' placeholder='Дата закрытия' autocomplete='off'>
						<input type='hidden' name='ostatok' value='{$ostatok}'>
						<button>Cохранить</button>
						<img src='/img/attention.png' class='attention' title='Разблокировать отчет невозможно так как следующий месяц закрыт.' style='display: none;'>
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
						<th>Продажи</th>
						<th>Отказы</th>
						<th>Дебиторка</th>
					</tr>
				</thead>
				<tbody>
				<?
					$city_price = 0;
					$city_discount = 0;
					$city_otkaz = 0;
					$city_debt = 0;
					$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND retail = 1 ORDER BY SH_ID";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						// Получаем сумму выручки по салону
						$query = "SELECT SUM(ODD_ODB.Price) Price
									FROM OrdersData OD
									JOIN (
										SELECT ODD.OD_ID
											,ODD.Price * ODD.Amount Price
										FROM OrdersDataDetail ODD
										UNION ALL
										SELECT ODB.OD_ID
											,ODB.Price * ODB.Amount Price
										FROM OrdersDataBlank ODB
									) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
									WHERE OD.Del = 0 AND YEAR(OD.StartDate) = {$_GET["year"]} AND MONTH(OD.StartDate) = {$_GET["month"]} AND OD.SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_price = mysqli_result($subres,0,'Price');
						$city_price = $city_price + $shop_price;

						// Получаем скидку по салону
						$query = "SELECT SUM(discount) discount FROM OrdersData OD WHERE OD.Del = 0 AND YEAR(StartDate) = {$_GET["year"]} AND MONTH(StartDate) = {$_GET["month"]} AND SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_discount = mysqli_result($subres,0,'discount');
						$city_discount = $city_discount + $shop_discount;

						// Получаем сумму отказов по салону
						$query = "SELECT SUM(OT.old_sum) Price
									FROM OrdersData OD
									JOIN Otkazi OT ON OT.OD_ID = OD.OD_ID
									WHERE OD.Del = 0 AND YEAR(OT.old_StartDate) = {$_GET["year"]} AND MONTH(OT.old_StartDate) = {$_GET["month"]} AND OT.old_SH_ID = {$row["SH_ID"]} AND OT.type = 2";
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
						$shop_debt = $shop_price - $shop_discount - $month_payment_sum;
						$city_debt = $city_debt + $shop_debt;

						$format_shop_price = number_format(($shop_price - $shop_discount + $shop_otkaz), 0, '', ' ');
						$format_shop_otkaz = number_format($shop_otkaz, 0, '', ' ');
						$format_shop_debt = number_format($shop_debt, 0, '', ' ');

						echo "<tr>";
						echo "<td class='nowrap'><a href='{$location}&SH_ID={$row["SH_ID"]}' ".( $_GET["SH_ID"] == $row["SH_ID"] ? "style='color: #D65C4F;'" : "" ).">{$row["Shop"]}:</a></td>";
						echo "<td class='txtright'>{$format_shop_price}</td>";
						echo "<td class='txtright' style='color: #911;'>{$format_shop_otkaz}</td>";
						echo "<td class='txtright'>{$format_shop_debt}</td>";
						echo "</tr>";
					}
					$format_city_price = number_format($city_price - $city_discount + $city_otkaz, 0, '', ' ');
					$format_city_otkaz = number_format($city_otkaz, 0, '', ' ');
					$format_city_debt = number_format($city_debt, 0, '', ' ');
					echo "<tr>";
					echo "<td class='nowrap'><b><a href='{$location}' ".( $_GET["SH_ID"] ? "" : "style='color: #D65C4F;'" ).">ВСЕГО:</a></b></td>";
					echo "<td class='txtright'><b>{$format_city_price}</b></td>";
					echo "<td class='txtright' style='color: #911;'><b>{$format_city_otkaz}</b></td>";
					echo "<td class='txtright'><b>{$format_city_debt}</b></td>";
					echo "</tr>";

				?>
				</tbody>
			</table>
			<br>
			<br>
			<b>Список отказов:</b><br>
			<table>
				<thead>
					<tr>
						<th>Код</th>
						<th>Салон</th>
						<th>Сумма</th>
						<th>Примечание</th>
					</tr>
				</thead>
				<tbody>
					<?
					$query = "SELECT OD.Code, SH.Shop, OT.old_sum, CONCAT(IF(OT.type = 1, 'Замена', 'Отказ'), '<br>', IFNULL(OT.comment, '')) comment
								FROM OrdersData OD
								JOIN Otkazi OT ON OT.OD_ID = OD.OD_ID
								JOIN Shops SH ON SH.SH_ID = OT.old_SH_ID AND CT_ID = {$CT_ID} AND retail = 1
								WHERE OD.Del = 0 AND YEAR(OT.old_StartDate) = {$_GET["year"]} AND MONTH(OT.old_StartDate) = {$_GET["month"]}";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						$format_old_price = number_format($row["old_sum"], 0, '', ' ');
						echo "<tr>";
						echo "<td>{$row["Code"]}</td>";
						echo "<td>{$row["Shop"]}</td>";
						echo "<td class='txtright'>{$format_old_price}</td>";
						echo "<td style='color: #911;'>{$row["comment"]}</td>";
						echo "</tr>";
					}
					?>
				</tbody>
			</table>
			</div>

			<table>
				<tbody>
				<?
					$terminal_sum = 0;
					$query = "SELECT DATE_FORMAT(OP.payment_date, '%d.%m') payment_date
									,OP.payment_sum
									,OP.terminal_payer
									,OD.Code
									,YEAR(OD.StartDate) year
									,MONTH(OD.StartDate) month
									,OD.OD_ID
								FROM OrdersPayment OP
								JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID AND OD.Del = 0
								WHERE YEAR(OP.payment_date) = {$_GET["year"]} AND MONTH(OP.payment_date) = {$_GET["month"]} AND OP.CT_ID = {$CT_ID} AND IFNULL(OP.payment_sum, 0) != 0 AND OP.terminal_payer IS NOT NULL
								ORDER BY OP.payment_date DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$format_sum = number_format($row["payment_sum"], 0, '', ' ');
					$terminal_sum = $terminal_sum + $row["payment_sum"];
					echo "<tr>";
					echo "<td title='№ упаковки'><b><a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}'>{$row["Code"]}</a></b></td>";
					echo "<td>{$row["payment_date"]}</td>";
					echo "<td class='nowrap'>{$row["terminal_payer"]}</td>";
					echo "<td class='txtright'>{$format_sum}</td>";
					echo "</tr>";
				}
				$format_terminal_sum = number_format($terminal_sum, 0, '', ' ');
				?>
				</tbody>
				<thead>
					<tr>
						<th colspan='3' class='nowrap'><b>Оплата по ТЕРМИНАЛУ:</b></th>
						<th class='txtright'><b><?=$format_terminal_sum?></b></th>
					</tr>
				</thead>
			</table>

			<table>
				<tbody>
				<?
					$cache_sum = 0;
					$query = "SELECT OP.OP_ID
									,DATE_FORMAT(OP.payment_date, '%d.%m') payment_date_short
									,DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
									,OP.cost_name
									,OP.payment_sum
									,OD.Code
									,YEAR(OD.StartDate) year
									,MONTH(OD.StartDate) month
									,OD.OD_ID
								FROM OrdersPayment OP
								LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
								WHERE YEAR(OP.payment_date) = {$_GET["year"]} AND MONTH(OP.payment_date) = {$_GET["month"]} AND OP.CT_ID = {$CT_ID} AND IFNULL(OP.payment_sum, 0) > 0 AND OP.terminal_payer IS NULL
								ORDER BY OP.payment_date DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$format_sum = number_format($row["payment_sum"], 0, '', ' ');
					$cache_sum = $cache_sum + $row["payment_sum"];
					$cache_name = ( $row["Code"] ) ? "<b><a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}'>{$row["Code"]}</a></b>" : $row["cost_name"];
					echo "<tr>";
					echo "<td>{$cache_name}</td>";
					echo "<td>{$row["payment_date_short"]}</td>";
					echo "<td class='txtright'>{$format_sum}</td>";
					echo "<td>";
						if( $locking == 0 and $row["Code"] == '' ) { // Если месяц не закрыт
							echo "<a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' cost_date='{$row["payment_date"]}' sign='+' title='Изменить приход'><i class='fa fa-pencil fa-lg'></i></a>";
						}
					echo "</td>";
					echo "</tr>";
				}
				$format_cache_sum = number_format($cache_sum, 0, '', ' ');
				?>
				</tbody>
				<thead>
					<tr>
						<th colspan='2' class='nowrap'>ПРИХОД наличных:</th>
						<th class="txtright"><?=$format_cache_sum?></th>
						<th></th>
					</tr>
				</thead>
			</table>

			<table>
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
								FROM OrdersPayment OP
								LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
								WHERE YEAR(OP.payment_date) = {$_GET["year"]} AND MONTH(OP.payment_date) = {$_GET["month"]} AND OP.CT_ID = {$CT_ID} AND IFNULL(OP.payment_sum, 0) < 0 AND OP.terminal_payer IS NULL
								ORDER BY OP.payment_date DESC";

					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$sum_cost = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$sum_cost = $sum_cost + $row["payment_sum"];
						$format_cost = number_format($row["payment_sum"], 0, '', ' ');
						$cost_name = ( $row["Code"] ) ? "<b><a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}'>{$row["Code"]}</a></b>" : $row["cost_name"];
						echo "<tr>";
						echo "<td>{$cost_name}</td>";
						echo "<td>{$row["payment_date_short"]}</td>";
						echo "<td class='txtright'>{$format_cost}</td>";
						echo "<td>";
						if( $locking == 0 and $row["Code"] == '' ) { // Если месяц не закрыт
							echo "<a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' cost_date='{$row["payment_date"]}' sign='-' title='Изменить расход'><i class='fa fa-pencil fa-lg'></i></a>";
						}
						echo "</td>";
						echo "</tr>";
					}
					$format_sum_cost = number_format($sum_cost, 0, '', ' ');
				?>
				</tbody>
				<thead>
					<tr>
						<th colspan="2" class="nowrap">РАСХОД наличных:</th>
						<th class="txtright"><?=$format_sum_cost?></th>
						<th></th>
					</tr>
				</thead>
			</table>

			<div style="display: inline-block; vertical-align:top;">
			<?
//				// Вычисляем дебиторку
//				$query = "SELECT SUM(OP.payment_sum) payment_sum
//							FROM OrdersData OD
//							JOIN OrdersPayment OP ON OP.OD_ID = OD.OD_ID
//							WHERE OD.Del = 0 AND YEAR(OD.StartDate) = {$_GET["year"]} AND MONTH(OD.StartDate) = {$_GET["month"]} AND OP.CT_ID = {$CT_ID}";
//				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//				$month_payment_sum = mysqli_result($res,0,'payment_sum');
//				$format_debt = number_format($city_price - $city_discount - $month_payment_sum, 0, '', ' ');

//				$format_last_ostatok = number_format($last_ostatok, 0, '', ' ');
//				$format_cache_sum = number_format($cache_sum, 0, '', ' ');
//				$ostatok = $cache_sum + $last_ostatok - $sum_cost;
//				$format_ostatok = number_format($ostatok, 0, '', ' ');

//				// Узнаем приход за месяц
//				$query = "SELECT IFNULL(pay_in, 0) pay_in, IFNULL(pay_out, 0) pay_out FROM OstatkiShops WHERE CT_ID = {$_GET["CT_ID"]} AND year = {$_GET["year"]} AND month = {$_GET["month"]}";
//				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//				if( mysqli_num_rows($res) ) {
//					$pay_in = mysqli_result($res,0,'pay_in');
//					$pay_out = mysqli_result($res,0,'pay_out');
//				}
//				else {
//					$pay_in = 0;
//					$pay_out = 0;
//				}
//				$format_pay_in = number_format($pay_in, 0, '', ' ');

//				echo "<table><thead>";
//				echo "<tr><th class='txtleft'>Дебиторка {$MONTHS[$_GET["month"]]} {$_GET["year"]}:</th><th class='txtright'>{$format_debt}</th></tr>";
//				echo "<tr><th class='txtleft'>Приход наличных:</th><th class='txtright'>{$format_pay_in}</th></tr>";
//				echo "<tr><th class='txtleft'>Остаток {$MONTHS[$lastmonth]} {$lastyear}:</th><th class='txtright'>{$format_last_ostatok}</th></tr>";
//				echo "<tr><th class='txtleft'>Остаток {$MONTHS[$_GET["month"]]} {$_GET["year"]}:</th><th class='txtright'>{$format_ostatok}</th></tr>";
//				echo "</thead></table><br><br>";
			?>
<!--
			<script>
				$('#locking_form').ready(function() {
					if( <?=$locking_next?> == 1 ) {
						$('#locking_form img').css('display', 'inline-block');
						$('#locking_form input').prop('disabled', true);
						$('#locking_form button').button( "option", "disabled", true );
					}
				});
			</script>
-->
			</div>
		</div>
	<?
		echo "<script> $(document).ready(function() { $('.wr_main_table_body').css('height', 'calc(100% - 400px)'); $('#MT_header').css('margin-top','210px'); }); </script>";
	}
	?>

	<br>
	<table class="main_table" id="MT_header">
		<form method="get">
		<thead>
			<tr>
				<th width="55">Дата отгрузки</th>
				<th width="51">№ упаковки</th>
				<th width="5%">№ квитанции</th>
				<th width="5%">Заказчик</th>
				<th width="25%">Наименование</th>
				<th width="15%">Материал</th>
				<th width="15%">Цвет</th>
				<th width="40">Кол-во</th>
				<th width="10%">Салон<br>
					<input type="hidden" name="CT_ID" value="<?=$_GET['CT_ID']?>">
					<input type="hidden" name="year" value="<?=$_GET['year']?>">
					<input type="hidden" name="month" value="<?=$_GET['month']?>">
					<select name="SH_ID" id="filter_shop" onchange="this.form.submit()" <?=( $_GET["SH_ID"] ? "class='filtered'" : "" )?>>
						<option></option>
						<?
							$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND retail = 1 ORDER BY SH_ID";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) ) {
								echo "<option value='{$row['SH_ID']}' ".( $row['SH_ID'] == $_GET['SH_ID'] ? 'selected' : '' ).">{$row['Shop']}</option>";
							}
						?>
					</select>
				</th>
				<th width="100">Примечание</th>
				<th width="100">Дата продажи</th>
				<th width="65">Сумма заказа</th>
				<th width="70">Скидка</th>
				<th width="65">Оплата</th>
				<th width="20">Т</th>
				<th width="65">Остаток</th>
				<th width="65">Отказ</th>
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
				<th width="51"></th>
				<th width="5%"></th>
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
				<th width="20"></th>
				<th width="65"></th>
				<th width="65"></th>
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
						,IFNULL(OD.discount, 0) discount
						,ROUND(IFNULL(OD.discount, 0) / SUM(ODD_ODB.Price) * 100) percent
						,IFNULL(OP.payment_sum, 0) payment_sum
						,OP.terminal_payer
						,IF(OS.locking_date IS NOT NULL, 1, 0) is_lock
						,IFNULL(OT.type, 0) type
						,IFNULL(OT.comment, '') comment
						,IFNULL(OT.old_SH_ID, OD.SH_ID) old_SH_ID
						,IFNULL(OT.old_StartDate, OD.StartDate) old_StartDate
				  FROM OrdersData OD
				  JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1".( $_GET["SH_ID"] ? " AND SH.SH_ID = {$_GET["SH_ID"]}" : "" )."
				  LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
				  LEFT JOIN (SELECT OD_ID, SUM(payment_sum) payment_sum, GROUP_CONCAT(terminal_payer) terminal_payer FROM OrdersPayment WHERE IFNULL(payment_sum, 0) != 0 GROUP BY OD_ID) OP ON OP.OD_ID = OD.OD_ID
				  LEFT JOIN Otkazi OT ON OT.OD_ID = OD.OD_ID
				  LEFT JOIN (SELECT ODD.OD_ID
								   ,IFNULL(PM.PT_ID, 2) PT_ID
								   ,ODD.ODD_ID itemID
								   ,ODD.Price * ODD.Amount Price

								   ,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), ''), '</i></b><br>') Zakaz

								   ,CONCAT(IFNULL(CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')'), ''), '<br>') Material
								   ,CONCAT(ODD.Amount, '<br>') Amount

							FROM OrdersDataDetail ODD
							LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
							LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
							LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
							LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
							LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
							GROUP BY ODD.ODD_ID
							UNION ALL
							SELECT ODB.OD_ID
								  ,0 PT_ID
								  ,ODB.ODB_ID itemID
								  ,ODB.Price * ODB.Amount Price

								  ,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), ''), '</i></b><br>') Zakaz

								  ,CONCAT(IFNULL(CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')'), ''), '<br>') Material
								  ,CONCAT(ODB.Amount, '<br>') Amount

							FROM OrdersDataBlank ODB
							LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
							LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
							LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
							GROUP BY ODB.ODB_ID
							ORDER BY PT_ID DESC, itemID
							) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
					WHERE OD.Del = 0 AND SH.CT_ID = {$CT_ID}
					".(($_GET["year"] != '' and $_GET["month"] != '') ? (($_GET["year"] == 0 and $_GET["month"] == 0) ? ' AND OD.StartDate IS NULL' : ' AND MONTH(OD.StartDate) = '.$_GET["month"].' AND YEAR(OD.StartDate) = '.$_GET["year"]) : '')."
					GROUP BY OD.OD_ID
					#HAVING Price - payment_sum <> 0 OR Price IS NULL OR DATEDIFF(NOW(), RD) <= {$datediff}
					#".(($_GET["year"] != '' and $_GET["month"] != '') ? "" : "HAVING Price - payment_sum <> 0 OR is_lock = 0")."
					".(($_GET["year"] != '' and $_GET["month"] != '') ? "" : "HAVING is_lock = 0")."
					ORDER BY IFNULL(OD.ReadyDate, '9999-01-01') ASC, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID ASC";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$format_price = number_format($row["Price"], 0, '', ' ');
			$format_payment = number_format($row["payment_sum"], 0, '', ' ');
			$format_discount = number_format($row['discount'], 0, '', ' ');
			$format_diff = number_format($row["Price"] - $row["payment_sum"], 0, '', ' ');
			$diff_color = ($row["Price"] == $row["payment_sum"]) ? "#6f6" : (($row["Price"] < $row["payment_sum"]) ? "#f66" : "#fff");
			$otkaz_cell = ($row["type"] == 1) ? "<b>Замена</b><br>{$row["comment"]}" : (($row["type"] == 2) ? "<b>Отказ</b><br>{$row["comment"]}" : "");
			echo "
				<tr id='ord{$row["OD_ID"]}'>
					<td><span>{$row["ReadyDate"]}</span></td>
					<td>{$row["Code"]}</td>
					<td><span>{$row["OrderNumber"]}</span></td>
					<td><span>{$row["ClientName"]}</span></td>
					<td><span class='nowrap'>{$row["Zakaz"]}</span></td>
					<td><span class='nowrap material'>{$row["Material"]}</span></td>
					<td>{$row["Color"]}</td>
					<td class='material'>{$row["Amount"]}</td>
					<td id='{$row["OD_ID"]}'><span>{$select_shops}</span></td>
					<td id='{$row["OD_ID"]}'><input type='text' class='sell_comment' value='". htmlspecialchars($row["sell_comment"], ENT_QUOTES) ."'></td>
					<td id='{$row["OD_ID"]}'><input type='text' class='date sell_date' value='{$row["StartDate"]}'></td>
					<td><a style='width: 100%; text-align: right;' class='update_price_btn button nowrap' id='{$row["OD_ID"]}'>{$format_price}</a></td>
					<td class='txtright nowrap'>{$format_discount} p.<br>{$row["percent"]} %</td>
					<td><a style='width: 100%; text-align: right;' class='add_payment_btn button nowrap' id='{$row["OD_ID"]}'>{$format_payment}</a></td>
					<td>".($row["terminal_payer"] ? "<i title='Оплата по терминалу' class='fa fa-credit-card' aria-hidden='true'></i>" : "")."</td>
					<td class='txtright' style='background: {$diff_color}'>{$format_diff}</td>
					<td><span style='color: #911;'>{$otkaz_cell}</span></td>
					<td>";
			if( $row["is_lock"] ) {
				echo "<i class='fa fa-lock fa-lg' aria-hidden='true' title='Отчетный период закрыт. Заказ нельзя отредактировать.'></i> ";
			}
			else {
				if( in_array('order_add', $Rights) ) {
					echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Редактировать'><i class='fa fa-pencil fa-lg'></i></a> ";
				}
				echo "<a href='#' id='{$row["OD_ID"]}' class='order_cut' title='Разделить заказ' location='{$location}'><i class='fa fa-sliders fa-lg'></i></a> ";
				echo "<a href='#' id='{$row["OD_ID"]}' class='order_otkaz_btn' location='{$location}' payment='{$row["payment_sum"]}' type='{$row["type"]}' comment='{$row["comment"]}' old_SH_ID='{$row["old_SH_ID"]}' old_StartDate='{$row["old_StartDate"]}' old_sum='{$row["Price"]}' title='Пометить как отказ'><i class='fa fa-hand-paper-o fa-lg' aria-hidden='true'></i></a>";
			}
			echo "
					</td>
				</tr>
				<script>
					$('#ord{$row["OD_ID"]} select').val('{$row["SH_ID"]}');
				</script>
			";
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
			<input type="hidden" name="CT_ID" id="CT_ID">
			<input type="hidden" name="sign" id="sign">
			<div style="width: 200px; display: inline-block; margin-right: 15px;">
				<label for="cost_name">Наименование:</label><br>
				<input type="text" name="cost_name" id="cost_name" style="width: 100%;">
			</div>
			<div style="width: 90px; display: inline-block; margin-right: 15px;">
				<label for="cost_name">Дата:</label><br>
				<input readonly type="text" name="cost_date" class="date" id="cost_date" style="width: 100%;">
			</div>
			<div style="width: 100px; display: inline-block;">
				<label for="cost">Сумма:</label><br>
				<input type="number" name="cost" min="1" id="cost" style="width: 100%; text-align: right;">
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
<div id='order_otkaz' title='Статус отказа' style='display:none'>
	<form method='post' action="<?=$location?>&order_otkaz=1">
		<div style="display: inline-block;">
			<i class='fa fa-hand-paper-o fa-4x' aria-hidden='true'></i>
		</div>
		<fieldset style="display: inline-block; width: calc(100% - 65px);">
			<input type="hidden" name="OD_ID">
			<input type="hidden" name="old_SH_ID">
			<input type="hidden" name="old_StartDate">
			<input type="hidden" name="old_sum">
			<label for='type'>Тип отказа:</label>
			<div class='btnset' id="type" style="display: inline-block;">
				<label for="otkaz0" title="Отменить отказ">Отмена</label>
				<input type="radio" name="type" id="otkaz0" value="0">
				<label for="otkaz1">Замена</label>
				<input type="radio" name="type" id="otkaz1" value="1">
				<label for="otkaz2">Отказ</label>
				<input type="radio" name="type" id="otkaz2" value="2">
			</div>
			<br>
			<br>
			<label for="otkaz_comment">Комментарий:</label>
			<input type="text" name="comment" id="otkaz_comment">
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
		//$('.wr_main_table_body').show('slow');
		$('.wr_main_table_body').css('display', 'block');

		$( ".button" ).button( "option", "classes.ui-button", "highlight" );

		// Кнопка добавления платежа
		$('.add_payment_btn').click( function() {
			var OD_ID = $(this).attr('id');
			$.ajax({ url: "ajax.php?do=add_payment&OD_ID="+OD_ID, dataType: "script", async: false });

			$('#add_payment').dialog({
				width: 550,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			$('input[name=payment_sum_add]').focus();
			$('input.date').datepicker();

			$('#add_payment .terminal').change(function() {
				var ch = $(this).prop('checked');
				var terminal_payer = $(this).parents('tr').find('input[type="text"].terminal_payer');
				var terminal_payer_hidden = $(this).parents('tr').find('input[type="hidden"].terminal_payer');
				if( ch ) {
					$(terminal_payer).prop('disabled', false);
					$(terminal_payer).prop('required', true);
					$(terminal_payer_hidden).val( $(terminal_payer).val() );
				}
				else {
					$(terminal_payer).prop('disabled', true);
					$(terminal_payer).prop('required', false);
					$(terminal_payer_hidden).val('');
				}
			});

			$('#add_payment .terminal_payer').change(function() {
				$(this).parent('td').find('input[type="hidden"]').val($(this).val());
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

			// Форматирование числа в денежный формат
			Number.prototype.format = function(n, x) {
				var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
				return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$& ');
			};

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

			$('#add_cost #OP_ID').val('');
			$('#add_cost #CT_ID').val('');
			$('#add_cost #sign').val(sign);
			$('#add_cost #cost_name').val('');
			$('#add_cost #cost_date').val(cost_date);
			$('#add_cost #cost').val('');

			var OP_ID = $(this).attr('id');

			if( OP_ID > 0 ) {
				var cost_name = $(this).attr('cost_name');
				var cost = $(this).attr('cost');
				$('#add_cost #OP_ID').val(OP_ID);
				$('#add_cost #cost_name').val(cost_name);
				$('#add_cost #cost').val(cost);
			}
			else {
				var CT_ID = $(this).attr('CT_ID');
				$('#add_cost #CT_ID').val(CT_ID);
			}

			$('#add_cost').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			$( "#cost_date" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );

			if (sign == '-') {
				$('#add_cost').dialog('option', 'title', 'РАСХОД');
			}
			else {
				$('#add_cost').dialog('option', 'title', 'ПРИХОД');
			}
			return false;
		});

		// Кнопка изменения статуса отказа
		$('.order_otkaz_btn').click( function() {
			var OD_ID = $(this).attr('id');
			var old_SH_ID = $(this).attr('old_SH_ID');
			var old_StartDate = $(this).attr('old_StartDate');
			var old_sum = $(this).attr('old_sum');
			var type = $(this).attr('type');
			var comment = $(this).attr('comment');
			var payment = $(this).attr('payment');

			// Заполнение формы
			$('#order_otkaz #otkaz'+type).prop('checked', true);
			$('#order_otkaz input[type="radio"]').button('refresh');
			$('#order_otkaz input[name="comment"]').val(comment);
			$('#order_otkaz input[name="OD_ID"]').val(OD_ID);
			$('#order_otkaz input[name="old_SH_ID"]').val(old_SH_ID);
			$('#order_otkaz input[name="old_StartDate"]').val(old_StartDate);
			$('#order_otkaz input[name="old_sum"]').val(old_sum);

			if( payment != 0 ) {
				$(this).parents('tr').find('.add_payment_btn span').effect( 'shake', 1000 );
				noty({timeout: 3000, text: 'Прежде чем пометить заказ как "отказной", обнулите приход по нему.', type: 'error'});
			}
			else {
				$('#order_otkaz').dialog({
					width: 500,
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
