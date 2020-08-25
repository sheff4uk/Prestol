<?
	include "config.php";
	$title = 'Реализация';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('selling_all', $Rights) and !in_array('selling_city', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$CT_ID = (isset($_GET["CT_ID"]) and (int)$_GET["CT_ID"] > 0) ? $_GET["CT_ID"] : $USR_City;
	$SH_ID = (isset($_GET["SH_ID"]) and (int)$_GET["SH_ID"] > 0) ? $_GET["SH_ID"] : 0;
	$year = isset($_GET["year"]) ? $_GET["year"] : date('Y');
	$month = isset($_GET["month"]) ? $_GET["month"] : date('n');

	// Узнаем кол-во доступных пользователю салонов и список идентификаторов
	$query = "SELECT SUM(1) cnt, GROUP_CONCAT(SH.SH_ID) SH_IDs FROM Shops SH WHERE SH.CT_ID = {$CT_ID} AND SH.retail = 1 ".($USR_Shop ? "AND SH_ID IN ({$USR_Shop})" : "")."";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$shop_num_rows = $row["cnt"];
	$SH_IDs = $row["SH_IDs"];

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
	$query = "SELECT SH.SH_ID, SH.Shop FROM Shops SH WHERE SH.SH_ID IN ({$SH_IDs}) ORDER BY SH.Shop";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$select_shops = "";
	while( $row = mysqli_fetch_array($res) ) {
		$select_shops .= "<option value='{$row["SH_ID"]}'>{$row["Shop"]}</option>";
	}

	$location = "selling.php?CT_ID={$CT_ID}&year={$year}&month={$month}";

	// Добавление/редактирование расхода/прихода
	if( isset($_GET["add_cost"]) ) {
		$OP_ID = $_POST["OP_ID"];
		$CB_ID = $_POST["CB_ID"];
		$cost_name = mysqli_real_escape_string( $mysqli, convert_str($_POST["cost_name"]) );
		$cost = $_POST["cost"] ? $_POST["cost"] : 0;
		$cost = ($_POST["sign"] == '-') ? $cost * -1 : $cost;
		$send = $_POST["send"] ? $_POST["send"] : "NULL";

		if( $OP_ID != '' ) { // Редактируем расход
			$query = "UPDATE OrdersPayment SET CB_ID = {$CB_ID}, cost_name = '{$cost_name}', payment_sum = {$cost}, send = {$send}, author = {$_SESSION['id']} WHERE OP_ID = {$OP_ID}";
			if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["error"][] = mysqli_error( $mysqli ); }
		}
		else { // Добавляем расход
			if( $cost ) {
				$query = "INSERT INTO OrdersPayment SET CB_ID = {$CB_ID}, cost_name = '{$cost_name}', payment_sum = {$cost}, send = {$send}, author = {$_SESSION['id']}";
				if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["error"][] = mysqli_error( $mysqli ); }
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
				SET CT_ID = {$CT_ID}, year = {$year}, month = {$month}, locking_date = '{$locking_date}'
				ON DUPLICATE KEY UPDATE locking_date = '{$locking_date}'";
		}
		else {
			$query = "UPDATE OstatkiShops SET locking_date = NULL WHERE CT_ID = {$CT_ID} AND year = {$year} AND month = {$month}";
		}
		if( !mysqli_query( $mysqli, $query ) ) { $_SESSION["error"][] = mysqli_error( $mysqli ); }

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Отказ/замена
	if( isset($_GET["order_otkaz"]) ) {
		$OD_ID = $_POST["OD_ID"];
		$old_sum = $_POST["old_sum"];
		$type = $_POST["type"];

		// Получаем из базы доп. сведения по набору
		$query = "SELECT OD.SH_ID, OD.StartDate, OD.ClientName FROM OrdersData OD WHERE OD.OD_ID = {$OD_ID}";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$SH_ID = mysqli_result($subres,0,'SH_ID');
		$StartDate = mysqli_result($subres,0,'StartDate');
		$ClientName = mysqli_result($subres,0,'ClientName');

		if( $StartDate ) {
			if( $old_sum ) {
				$query = "INSERT INTO Otkazi
					SET OD_ID = {$OD_ID}, type = {$type}, SH_ID = {$SH_ID}, StartDate = '{$StartDate}', old_sum = {$old_sum}, comment = '{$ClientName}'
					ON DUPLICATE KEY UPDATE type = {$type}, old_sum = {$old_sum}";
				if( mysqli_query( $mysqli, $query ) ) {
					$_SESSION["alert"][] = "В таблице отказов/замен сделана запись.";
				}
				else { $_SESSION["alert"][] = mysqli_error( $mysqli ); }
			}
			// Очищаем дату продажи, остальное сделает триггер Clear_client_if_reject
			$query = "UPDATE OrdersData SET StartDate = NULL, sell_comment = CONCAT(IFNULL(sell_comment, ''), IF({$type} = 1, ' Замена ({$ClientName})', ' Отказ ({$ClientName})')), author = {$_SESSION['id']} WHERE OD_ID = {$OD_ID}";
			if( mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"][] = "Набор перемещен в \"Свободные\"";
			}
			else {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}

			// Обновляем автора
			$query = "UPDATE OrdersDataDetail SET author = NULL WHERE OD_ID = {$OD_ID}";
			mysqli_query( $mysqli, $query );

			// Очищаем скидку
			$query = "UPDATE OrdersDataDetail SET discount = NULL WHERE OD_ID = {$OD_ID}";
			if( mysqli_query( $mysqli, $query ) ) {
				// Если были изменения
				if (mysqli_affected_rows($mysqli)) {
					$_SESSION["alert"][] = "Скидка по набору была обнулена.";
				}
			}
			else {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}

			// Обновляем минимальную цену по последнему прайсу (триггер обновит цену при необходимости)
			$query = "UPDATE OrdersDataDetail SET min_price = Price(ODD_ID, 1) WHERE OD_ID = {$OD_ID}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}

			// Пересчитываем стоимость по прайсу
			$query = "UPDATE OrdersDataDetail SET min_price = Price(ODD_ID, 1) WHERE OD_ID = {$OD_ID}";
			mysqli_query( $mysqli, $query );

			// Ставим цену по прайсу
			$query = "UPDATE OrdersDataDetail SET Price = IF(IFNULL(min_price, 0) > 0, min_price, Price) WHERE OD_ID = {$OD_ID}";
			if( mysqli_query( $mysqli, $query ) ) {
				// Если были изменения
				if (mysqli_affected_rows($mysqli)) {
					$_SESSION["alert"][] = "Была установлена стоимость по прайсу.";
				}
			}
			else {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}
		}
		else {
			$_SESSION["error"][] = "Набор не продан! Установите дату продажи и повторите попытку.";
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
		position: relative;
	}
	#selling_report:hover > div {
		height: 300px;
		opacity: 1;
	}
	#selling_report > div {
		background: #fff;
		height: 0px;
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		margin-top: 10px;
		z-index: 2;
		position: absolute;
		top: -10px;
		left: 0px;
		width: 100%;
		overflow: auto;
		white-space: nowrap;
		opacity: 0;
		transition: .3s;
		-webkit-transition: .3s;
		box-shadow: 5px 5px 8px #666;
	}
	#selling_report > div table {
		display: inline-block;
		vertical-align: top;
		margin-right: 20px;
	}
	#accordion a {
		color: #428bca !important;
	}
	#accordion a:hover {
		color: #D65C4F !important;
	}
	#sell_archive {
		display: inline-block;
	}
	#sell_archive > a {
		width: 60px;
		height: 22px;
	}
	#sell_archive > div {
		width: 0px;
		height: 0px;
		opacity: 0;
		background: #fff;
		border: solid 1px #bbb;
		border-radius: 10px;
		overflow-y: auto;
		box-shadow: 5px 5px 10px #bbb;
		transition: .3s;
		-webkit-transition: .3s;
		position: absolute;
		z-index: 14;
	}
	#sell_archive:hover > div {
		width: 200px;
		height: 400px;
		padding: 10px;
		opacity: 1;
	}
	#rep_buttons {
		overflow-x: scroll;
		white-space: nowrap;
	}
</style>

<div id="rep_buttons">
<form method="get" style="display: inline-block;">
	<select name="CT_ID" onchange="this.form.submit()">
		<?
		$query = "
			SELECT CT.CT_ID, CT.City, CT.Color
			FROM Cities CT
			JOIN Shops SH ON SH.CT_ID = CT.CT_ID AND SH.retail = 1
			".(in_array('selling_city', $Rights) ? 'WHERE CT.CT_ID = '.$USR_City : '')."
			GROUP BY CT.CT_ID
			ORDER BY CT.City
		";
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
	if (!$CT_ID) die;

	// Кнопки движения денег
	echo "<a href='#' class='add_cost_btn' cost_name='' cost='' sign='+' CT_ID='{$CT_ID}' title='Внести приход'><i class='fa fa-plus fa-lg' style='color: white; background: green; border-radius: 5px; line-height: 24px; width: 24px; text-align: center; vertical-align: text-bottom;'></i></a>&nbsp;";
	echo "<a href='#' class='add_cost_btn' cost_name='' cost='' sign='-' CT_ID='{$CT_ID}' title='Внести расход'><i class='fa fa-minus fa-lg' style='color: white; background: red; border-radius: 5px; line-height: 24px; width: 24px; text-align: center; vertical-align: text-bottom;'></i></a>&nbsp;";
	echo "<a href='#' class='add_cost_btn' cost_name='' cost='' sign='' CT_ID='{$CT_ID}' title='Сдать выручку'><i class='fa fa-exchange-alt fa-lg' style='color: white; background: #428bca; border-radius: 5px; line-height: 24px; width: 24px; text-align: center; vertical-align: text-bottom;'></i></a>";

	// Выводим остатки по кассам
	$query = "
		SELECT CB.name
			,SUM(IFNULL(MSIO.pay_in,0)) - SUM(IFNULL(MSIO.pay_out,0)) ostatok
			,SOP.CB_ID
		FROM CashBox CB
		LEFT JOIN MonthlySellInOut MSIO ON MSIO.CB_ID = CB.CB_ID
		LEFT JOIN (
			SELECT CB_ID
			FROM OrdersPayment
			WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 100 DAY)
				AND CB_ID IS NOT NULL
			GROUP BY CB_ID
		) SOP ON SOP.CB_ID = CB.CB_ID
		WHERE CB.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID IN ({$USR_Shop})" : "SELECT CB_ID FROM Shops WHERE CT_ID = {$CT_ID} UNION SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID}").")
		GROUP BY CB.CB_ID
		#Показываем непустые кассы или если было движение за последние 10 дней
		HAVING ostatok != 0 OR SOP.CB_ID IS NOT NULL
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$label = "{$row["name"]}:";
		$ostatok = $row["ostatok"];
		$format_ostatok = number_format($ostatok, 0, '', ' ');
		echo "<h3 style='display: inline-block; margin: 10px 20px;'>{$label} {$format_ostatok}</h3>";
	}
?>

	<!-- КНОПКИ ОТЧЕТОВ -->
		<br>Отчеты:
		<?
		if( !$USR_Shop ) {
			echo "<div id='sell_archive'><a href='#' class='button'>Архив</a><div>";
			// Формируем список архивных отчетов
			$query = "
				SELECT OS.year
					,OS.month
				FROM OstatkiShops OS
				WHERE OS.CT_ID = {$CT_ID} AND OS.locking_date IS NOT NULL
				ORDER BY year DESC, month DESC
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				$highlight = ($year == $row["year"] and $month == $row["month"]) ? 'border: 1px solid #fbd850; color: #eb8f00;' : '';
				echo "<a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}' class='button' style='{$highlight}'>{$MONTHS[$row["month"]]} - {$row["year"]} <i class='fas fa-lock'></i></a><br>";
			}
			echo "</div></div>";
		}

		$query = "
			SELECT IFNULL(YEAR(OD.StartDate), 0) year
				,IFNULL(MONTH(OD.StartDate), 0) month
			FROM OrdersData OD
			JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1 AND SH.CT_ID = {$CT_ID}
			WHERE OD.is_lock = 0 AND OD.DelDate IS NULL
			GROUP BY IFNULL(YEAR(OD.StartDate), 0), IFNULL(MONTH(OD.StartDate), 0)

			UNION

			SELECT YEAR(NOW()), MONTH(NOW())
			ORDER BY year, month
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		if( $CT_ID ) {
			while( $row = mysqli_fetch_array($res) ) {
				$highlight = ($year == $row["year"] and $month == $row["month"]) ? 'border: 1px solid #fbd850; color: #eb8f00;' : '';
				if( $row["year"] == 0 and $row["month"] == 0 ) {
					echo "<a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}' class='button' style='{$highlight}'>Свободные</a> ";
				}
				else {
					echo "<a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}' class='button' style='{$highlight}'>{$MONTHS[$row["month"]]} - {$row["year"]}</a> ";
				}
			}
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
					WHERE CT_ID = {$CT_ID} AND year = {$year} AND month = {$month}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$locking = mysqli_result($res,0,'locking_date') ? 1 : 0;
		$locking_date = mysqli_result($res,0,'locking_date');
	?>
	<div id='selling_report'>
		<a href="#" class="button" style="width: 100%; z-index: -1;">ОТЧЁТ: <?=$MONTHS[$month]?> - <?=$year?><?=(($locking_date) ? " <i class='fas fa-lock'></i>" : "")?></a>
		<div>
			<div style="display: inline-block; vertical-align:top;">

			<?
				$locking_form = "
					<form method='post' action='{$location}&add_locking_date=1' id='locking_form'>
						<input type='text' class='date' name='locking_date' value='{$locking_date}' placeholder='Дата закрытия' autocomplete='off'>
						<button>Cохранить</button>
					</form>
					<br>
				";
				if( date('Y') != $year or date('n') != $month ) {
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
						<?
//						if( in_array('selling_all', $Rights) ) {
//							echo "<th></th>";
//						}
						?>
						<th>Продажи<i class="fa fa-question-circle" aria-hidden="true" title="Не включает стоимость отказных наборов."></i></th>
						<th>Скидки</th>
						<th>Отказы</th>
						<th>Дебиторка</th>
					</tr>
				</thead>
				<tbody>
				<?
//					$city_report = 0;
					$city_price = 0;
					$city_discount = 0;
					$city_otkaz = 0;
					$city_debt = 0;
					$query = "SELECT SH.SH_ID, SH.Shop FROM Shops SH WHERE SH.SH_ID IN ({$SH_IDs}) ORDER BY SH.Shop";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
//						// Получаем сумму поступившей налички для отчета
//						$query = "
//							SELECT IFNULL(SUM(SUB.cash_sum), 0) cash_sum
//							FROM (
//								SELECT IFNULL(OP2.cash_sum, 0) cash_sum
//								FROM OrdersData OD
//								JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.FA_ID IS NOT NULL
//								JOIN (
//									SELECT OP.OD_ID
//										,SUM(OP.payment_sum) payment_sum
//										,SUM(IF(OP.terminal = 1, OP.payment_sum, 0)) terminal_sum
//									FROM OrdersPayment OP
//									JOIN Shops SH ON SH.SH_ID = OP.SH_ID AND SH.CT_ID = {$CT_ID}
//									WHERE IFNULL(payment_sum, 0) != 0
//										#AND OP.SH_ID = {$row["SH_ID"]}
//									GROUP BY OP.OD_ID
//								) OP1 ON OP1.OD_ID = OD.OD_ID
//								JOIN (
//									SELECT OP.OD_ID
//										,SUM(OP.payment_sum) cash_sum
//									FROM OrdersPayment OP
//									WHERE IFNULL(payment_sum, 0) != 0
//										AND OP.SH_ID = {$row["SH_ID"]}
//										AND YEAR(OP.payment_date) = {$year}
//										AND MONTH(OP.payment_date) = {$month}
//										AND OP.terminal = 0
//									GROUP BY OP.OD_ID
//								) OP2 ON OP2.OD_ID = OD.OD_ID
//								JOIN (
//									SELECT ODD.OD_ID, SUM((ODD.Price - IFNULL(ODD.discount, 0)) * ODD.Amount) Price
//									FROM OrdersDataDetail ODD
//									GROUP BY ODD.OD_ID
//								) PRICE ON PRICE.OD_ID = OD.OD_ID
//									AND OD.DelDate IS NULL
//								WHERE (PRICE.Price - OP1.payment_sum > 10 AND OD.StartDate IS NOT NULL) OR OP1.terminal_sum > 0
//							) SUB
//						";
//						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//						$sum_cash_report = mysqli_result($subres,0,'cash_sum');
//						$city_report += $sum_cash_report;

						// Получаем сумму выручки и скидки по салону
						$query = "
							SELECT SUM(ODD.Price * ODD.Amount) Price
								,SUM(IFNULL(ODD.discount, 0) * ODD.Amount) discount
							FROM OrdersData OD
							JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
							WHERE OD.DelDate IS NULL AND YEAR(OD.StartDate) = {$year} AND MONTH(OD.StartDate) = {$month} AND OD.SH_ID = {$row["SH_ID"]}
						";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_price = mysqli_result($subres,0,'Price');
						$city_price = $city_price + $shop_price;
						$shop_discount = mysqli_result($subres,0,'discount');
						$city_discount = $city_discount + $shop_discount;

						// Получаем сумму выручки по салону по накладным (чтобы получить дебиторку)
						$query = "
							SELECT SUM(ODD.Price * ODD.Amount) Price
								,SUM(IFNULL(ODD.discount, 0) * ODD.Amount) discount
							FROM OrdersData OD
							JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID AND PFI.del = 0 AND PFI.rtrn != 1
							JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
							WHERE OD.DelDate IS NULL AND YEAR(OD.StartDate) = {$year} AND MONTH(OD.StartDate) = {$month} AND OD.SH_ID = {$row["SH_ID"]}
						";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_price_inv = mysqli_result($subres,0,'Price');
						$shop_discount_inv = mysqli_result($subres,0,'discount');

						// Получаем сумму отказов по салону
						$query = "SELECT SUM(OT.old_sum) Price
									FROM Otkazi OT
									WHERE YEAR(OT.StartDate) = {$year} AND MONTH(OT.StartDate) = {$month} AND OT.SH_ID = {$row["SH_ID"]} AND OT.type = 2";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_otkaz = mysqli_result($subres,0,'Price');
						$city_otkaz = $city_otkaz + $shop_otkaz;

						// Вычисляем дебиторку по салону
						$query = "SELECT SUM(OP.payment_sum) payment_sum
									FROM OrdersData OD
									JOIN OrdersPayment OP ON OP.OD_ID = OD.OD_ID
									WHERE OD.DelDate IS NULL AND YEAR(OD.StartDate) = {$year} AND MONTH(OD.StartDate) = {$month} AND OD.SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$month_payment_sum = mysqli_result($subres,0,'payment_sum');
						$shop_debt = ($shop_price - $shop_price_inv) - ($shop_discount - $shop_discount_inv) - $month_payment_sum;
						$city_debt = $city_debt + $shop_debt;

						$shop_percent = round($shop_discount / $shop_price * 100, 2);
//						$format_shop_report = number_format($sum_cash_report, 0, '', ' ');
						$format_shop_price = number_format(($shop_price - $shop_discount), 0, '', ' ');
						$format_shop_discount = number_format($shop_discount, 0, '', ' ');
						$format_shop_otkaz = number_format($shop_otkaz, 0, '', ' ');
						$format_shop_debt = number_format($shop_debt, 0, '', ' ');

						if ($shop_cash != 0 or $shop_price != 0 or $shop_discount != 0 or $shop_otkaz != 0 or $shop_debt != 0) {
							echo "<tr>";
							echo "<td class='nowrap'><a href='{$location}&SH_ID={$row["SH_ID"]}' ".( $SH_ID == $row["SH_ID"] ? "style='color: #D65C4F;'" : "" ).">{$row["Shop"]}:</a></td>";
//							if( in_array('selling_all', $Rights) ) {
//								echo "<td class='txtright'>{$format_shop_report}</td>";
//							}
							echo "<td class='txtright'>{$format_shop_price}</td>";
							echo "<td class='txtright'>{$format_shop_discount} ({$shop_percent}%)</td>";
							echo "<td class='txtright' style='color: #911;'>{$format_shop_otkaz}</td>";
							echo "<td class='txtright'>{$format_shop_debt}</td>";
							echo "</tr>";
						}
					}
					if( $shop_num_rows > 1 ) {
						$city_percent = round($city_discount / $city_price * 100, 2);
//						$format_city_report = number_format($city_report, 0, '', ' ');
						$format_city_price = number_format($city_price - $city_discount, 0, '', ' ');
						$format_city_discount = number_format($city_discount, 0, '', ' ');
						$format_city_otkaz = number_format($city_otkaz, 0, '', ' ');
						$format_city_debt = number_format($city_debt, 0, '', ' ');
						echo "<tr>";
						echo "<td class='nowrap'><b><a href='{$location}' ".( $SH_ID ? "" : "style='color: #D65C4F;'" ).">ВСЕГО:</a></b></td>";
//						if( in_array('selling_all', $Rights) ) {
//							echo "<td class='txtright'><b>{$format_city_report}</b></td>";
//						}
						echo "<td class='txtright'><b>{$format_city_price}</b></td>";
						echo "<td class='txtright'><b>{$format_city_discount} ({$city_percent}%)</b></td>";
						echo "<td class='txtright' style='color: #911;'><b>{$format_city_otkaz}</b></td>";
						echo "<td class='txtright'><b>{$format_city_debt}</b></td>";
						echo "</tr>";
					}
				?>
				</tbody>
			</table>
		</div>

		<div style="display: inline-block; width: 400px;">
			<h3><?=$MONTHS[$month]?> - <?=$year?></h3>
		<div id="accordion">
			<h3 id="section1"></h3>
			<div>
			<table class="main_table" style="margin: 0; display: table;">
				<tbody>
				<?
//				if( in_array('selling_all', $Rights) ) {
//					$query = "
//						SELECT OP.OP_ID
//							,DATE_FORMAT(OP.payment_date, '%d.%m') payment_date_short
//							,DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
//							,OP.cost_name
//							,OP.payment_sum
//							,OD.Code
//							,IFNULL(YEAR(OD.StartDate), 0) year
//							,IFNULL(MONTH(OD.StartDate), 0) month
//							,OD.OD_ID
//							,OP.SH_ID
//							,SH.Shop
//							,IF(OD.DelDate IS NULL, '', 'del') del
//							,IF(OP.payment_sum < 0, '-', '+') sign
//							,IF(OP1.terminal_sum > 0, 2,IF((PRICE.Price - OP1.payment_sum > 10 AND OD.StartDate IS NOT NULL AND SH.FA_ID IS NOT NULL), 1, 0)) is_terminal
//							,IF(OP.SH_ID != OD.SH_ID, 1, 0) attention
//							,IF(IFNULL(OD.SH_ID, 0) NOT IN ({$SH_IDs}) AND OP.OD_ID IS NOT NULL, 1, 0) other_city
//							,IF(OP.cost_name IS NULL, 1, 0) cost_name_is_null
//						FROM OrdersPayment OP
//						JOIN Shops SH ON SH.SH_ID = OP.SH_ID AND ".($SH_ID ? "SH.SH_ID = {$SH_ID}" : "SH.SH_ID IN ({$SH_IDs})")."
//						LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
//						LEFT JOIN (
//							SELECT OP.OD_ID
//								,SUM(OP.payment_sum) payment_sum
//								,SUM(IF(OP.terminal = 1, OP.payment_sum, 0)) terminal_sum
//							FROM OrdersPayment OP
//							JOIN Shops SH ON SH.SH_ID = OP.SH_ID AND SH.CT_ID = {$CT_ID}
//							WHERE IFNULL(payment_sum, 0) != 0
//							GROUP BY OP.OD_ID
//						) OP1 ON OP1.OD_ID = OD.OD_ID
//						LEFT JOIN (
//							SELECT ODD.OD_ID, SUM((ODD.Price - IFNULL(ODD.discount, 0)) * ODD.Amount) Price
//							FROM OrdersDataDetail ODD
//							GROUP BY ODD.OD_ID
//						) PRICE ON PRICE.OD_ID = OD.OD_ID
//						WHERE YEAR(OP.payment_date) = {$year} AND MONTH(OP.payment_date) = {$month} AND IFNULL(OP.payment_sum, 0) != 0 AND OP.terminal = 0 AND OP.send IS NULL
//						ORDER BY OP.payment_date DESC
//					";
//				}
//				else {
					$query = "
						SELECT OP.OP_ID
							,DATE_FORMAT(OP.payment_date, '%d.%m') payment_date_short
							,OP.cost_name
							,OP.payment_sum
							,OD.Code
							,IFNULL(YEAR(OD.StartDate), 0) year
							,IFNULL(MONTH(OD.StartDate), 0) month
							,OD.OD_ID
							,OP.CB_ID
							,CB.name
							,IF(OD.DelDate IS NULL, '', 'del') del
							,IF(OP.payment_sum < 0, '-', '+') sign
							,IF(IFNULL(OD.SH_ID, 0) NOT IN ({$SH_IDs}) AND OP.OD_ID IS NOT NULL, 1, 0) other_city
							,IF(OP.cost_name IS NULL, 1, 0) cost_name_is_null
							,IF(CB.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID IN ({$SH_IDs})" : "SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID} AND CT_ID = {$USR_City}")."), 1, 0) edit
						FROM OrdersPayment OP
						JOIN CashBox CB ON CB.CB_ID = OP.CB_ID
							AND CB.CB_ID IN (SELECT CB_ID FROM Shops WHERE SH_ID IN ({$SH_IDs})".($USR_Shop ? "" : " UNION SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID}").")
						LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
						WHERE YEAR(OP.payment_date) = {$year} AND MONTH(OP.payment_date) = {$month} AND IFNULL(OP.payment_sum, 0) != 0 AND OP.terminal = 0 AND OP.send IS NULL
						ORDER BY OP.payment_date DESC
					";
//				}
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$cache_sum = 0;
				while( $row = mysqli_fetch_array($res) ) {
					$format_sum = number_format($row["payment_sum"], 0, '', ' ');
					$cache_sum = $cache_sum + $row["payment_sum"];
					$href = ($row["del"]) ? "orderdetail.php?id={$row["OD_ID"]}' target='_blank" : "?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}";
					$cache_name = ( $row["Code"] ) ? ( $row["other_city"] ? "<b class='code {$row["del"]}' title='Набор перемещен в другой регион'>{$row["Code"]}</b>" : "<b><a href='{$href}'><b class='code {$row["del"]}'>{$row["Code"]}</b></a></b>") : "<span>{$row["cost_name"]}</span>";
//					if( in_array('selling_all', $Rights) ) {
//						$is_terminal = $row["is_terminal"] == 2 ? " <i class='fa fa-credit-card' title='В оплате содержится эквайринг'></i>" : ($row["is_terminal"] == 1 ? " <i class='fa fa-credit-card' style='opacity: .4;' title='Набор оплачен не полностью. Возможен эквайринг.'></i>" : "");
//					}
//					else {
//						$is_terminal = "";
//					}
					if( $row["Code"] or $row["cost_name_is_null"] == 0 ) {
						echo "<tr>";
					}
					else {
						echo "<tr style='opacity: .5;'>";
					}
					echo "<td width='49'>{$row["payment_date_short"]}</td>";
					echo "<td width='60' class='txtright'><b>{$format_sum}</b></td>";
					echo "<td width='60'><span>{$row["name"]}</span></td>";
					echo "<td width='185'>{$cache_name}</td>";
					echo "<td width='25'>";
					// Редактирование записи
					if( $locking == 0 and $row["Code"] == '' and $row["cost_name_is_null"] == 0 and $row["edit"] == 1 ) { // Если месяц не закрыт
						echo "<a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' cb_id='{$row["CB_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' sign='{$row["sign"]}' title='Отредактировать запись'><i class='fa fa-pencil-alt fa-lg'></i></a>";
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
					$query = "
						SELECT DATE_FORMAT(OP.payment_date, '%d.%m') payment_date
							,OP.payment_sum
							,OD.Code
							,IFNULL(YEAR(OD.StartDate), 0) year
							,IFNULL(MONTH(OD.StartDate), 0) month
							,OD.OD_ID
							,CB.name
							,IF(OD.DelDate IS NULL, '', 'del') del
							,IF(IFNULL(OD.SH_ID, 0) NOT IN ({$SH_IDs}) AND OP.OD_ID IS NOT NULL, 1, 0) other_city
						FROM OrdersPayment OP
						JOIN CashBox CB ON CB.CB_ID = OP.CB_ID
							AND CB.CB_ID IN (SELECT CB_ID FROM Shops WHERE SH_ID IN ({$SH_IDs})".($USR_Shop ? "" : " UNION SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID}").")
						LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
						WHERE YEAR(OP.payment_date) = {$year}
							AND MONTH(OP.payment_date) = {$month}
							AND IFNULL(OP.payment_sum, 0) != 0
							AND OP.terminal = 1
							AND OP.cost_name IS NULL
						ORDER BY OP.payment_date DESC
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$terminal_sum = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$format_sum = number_format($row["payment_sum"], 0, '', ' ');
						$terminal_sum = $terminal_sum + $row["payment_sum"];
						$href = ($row["del"]) ? "orderdetail.php?id={$row["OD_ID"]}' target='_blank" : "?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}";
						$cache_name = $row["other_city"] ? "<b class='code {$row["del"]}' title='Набор перемещен в другой регион'>{$row["Code"]}</b>" : ($row["Code"] ? "<b><a href='{$href}'><b class='code {$row["del"]}'>{$row["Code"]}</b></a></b>" : "");
						if($row["Code"]) {
							echo "<tr>";
						}
						else {
							echo "<tr style='opacity: .5;'>";
						}
						echo "<td width='49'>{$row["payment_date"]}</td>";
						echo "<td width='70' class='txtright'><b>{$format_sum}</b></td>";
						echo "<td width='60'><span>{$row["name"]}</span></td>";
						echo "<td width='60'>{$cache_name}</td>";
						echo "</tr>";
					}
					$format_terminal_sum = number_format($terminal_sum, 0, '', ' ');
				?>
				</tbody>
			</table>
			</div>

			<h3 id="section3"></h3>
			<div>
			<table class="main_table" style="margin: 0; display: table;">
				<tbody>
				<?
					$query = "
						SELECT OP.OP_ID
							,DATE_FORMAT(OP.payment_date, '%d.%m') payment_date_short
							,OP.cost_name
							,ABS(OP.payment_sum) payment_sum
							,OP.CB_ID
							,CB.name
							,OP.send
							,IF(CB.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID IN ({$SH_IDs})" : "SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID} AND CT_ID = {$USR_City}")."), 1, 0) edit
						FROM OrdersPayment OP
						JOIN CashBox CB ON CB.CB_ID = OP.CB_ID
							AND CB.CB_ID IN (SELECT CB_ID FROM Shops WHERE SH_ID IN ({$SH_IDs})".($USR_Shop ? "" : " UNION SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID}").")
						WHERE YEAR(OP.payment_date) = {$year} AND MONTH(OP.payment_date) = {$month} AND IFNULL(OP.payment_sum, 0) < 0 AND OP.terminal = 0 AND send IS NOT NULL
						ORDER BY OP.payment_date DESC
					";

					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$sum_send = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$sum_send = $sum_send + $row["payment_sum"];
						$format_cost = number_format($row["payment_sum"], 0, '', ' ');
						echo "<tr>";
						echo "<td width='49'>{$row["payment_date_short"]}</td>";
						echo "<td width='70' class='txtright'><b>{$format_cost}</b></td>";
						echo "<td width='60'><span>{$row["name"]}</span></td>";
						echo "<td width='180'><span>{$row["cost_name"]}</span></td>";
						echo "<td width='25'>";
						//Редактирование записи
						if( $locking == 0 and $row["send"] != 2 and $row["edit"] == 1 ) { // Если месяц не закрыт
							echo "<a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' cb_id='{$row["CB_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' sign='' title='Изменить операцию'><i class='fa fa-pencil-alt fa-lg'></i></a>";
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
					$query = "
						SELECT DATE_FORMAT(OD.ReadyDate, '%d.%m.%y') ReadyDate
							,OD.OD_ID
							,OD.Code
							,IFNULL(YEAR(OD.StartDate), 0) year
							,IFNULL(MONTH(OD.StartDate), 0) month
							,SH.Shop
							,Ord_price(OD.OD_ID) Price
							,Ord_discount(OD.OD_ID) discount
							,Payment_sum(OD.OD_ID) payment_sum
							,IF(PFI.rtrn = 1, NULL, OD.PFI_ID) PFI_ID
							,PFI.count
							,PFI.platelshik_id
						FROM OrdersData OD
						JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.SH_ID IN ({$SH_IDs})
						LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
						WHERE YEAR(OD.StartDate) = {$year}
							AND MONTH(OD.StartDate) = {$month}
							AND OD.DelDate IS NULL
							AND OD.ReadyDate IS NOT NULL
							AND IFNULL(OD.taken, 0) != 1
						ORDER BY OD.StartDate, OD.OD_ID
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$not_taken_count = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$format_old_price = number_format($row["old_sum"], 0, '', ' ');
						++$not_taken_count;
						echo "<tr>";
						echo "<td width='49'><span class='nowrap'>{$row["ReadyDate"]}</span></td>";
						echo "<td width='60'><span>{$row["Shop"]}</span></td>";
						echo "<td width='60'><b><a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}'><b class='code'>{$row["Code"]}</b></a></b></td>";
						// Если в накладной - выводим ссылку на сверки
						if( $row["PFI_ID"] ) {
							echo "<td width='65'><a href='sverki.php?payer={$row["platelshik_id"]}' target='_blank' title='Перейти в сверки'><b>Сверки</b></a></td>";
						}
						else {
							$format_diff = number_format($row["Price"] - $row['discount'] - $row["payment_sum"], 0, '', ' ');
							$diff_color = (($row["Price"] - $row['discount']) == $row["payment_sum"]) ? "#6f6" : ((($row["Price"] - $row['discount']) < $row["payment_sum"]) ? "#f66" : "#fff");
							echo "<td width='65' class='txtright' style='background: {$diff_color}'>{$format_diff}</td>";
						}
						echo "</tr>";
					}
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
									,IFNULL(YEAR(OD.StartDate), 0) year
									,IFNULL(MONTH(OD.StartDate), 0) month
									,SH.Shop
									,OT.old_sum
									,CONCAT(IF(OT.type = 1, 'Замена (', 'Отказ ('), IFNULL(OT.comment, ''), ')') comment
									,OT.StartDate
									,OT.SH_ID
									,IF(OD.DelDate IS NULL, '', 'del') del
									,IF(IFNULL(OD.SH_ID, 0) NOT IN ({$SH_IDs}), 1, 0) other_city
								FROM OrdersData OD
								JOIN Otkazi OT ON OT.OD_ID = OD.OD_ID
								JOIN Shops SH ON SH.SH_ID = OT.SH_ID AND SH.SH_ID IN ({$SH_IDs})
								WHERE YEAR(OT.StartDate) = {$year} AND MONTH(OT.StartDate) = {$month}";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$reject_count = 0;
					while( $row = mysqli_fetch_array($res) ) {
						$format_old_price = number_format($row["old_sum"], 0, '', ' ');
						++$reject_count;
						$href = ($row["del"]) ? "orderdetail.php?id={$row["OD_ID"]}' target='_blank" : "?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}#ord{$row["OD_ID"]}";
						$cache_name = ( $row["other_city"] ) ? "<b class='code {$row["del"]}' title='Набор перемещен в другой регион'>{$row["Code"]}</b>" : "<b><a href='{$href}'><b class='code {$row["del"]}'>{$row["Code"]}</b></a></b>";
						echo "<tr>";
						echo "<td width='49'><span class='nowrap'>{$row["reject_date"]}</span></td>";
						echo "<td width='70' class='txtright'><b>{$format_old_price}</b></td>";
						echo "<td width='60'><span>{$row["Shop"]}</span></td>";
						echo "<td width='60'>{$cache_name}</td>";
						echo "<td width='120'><span>{$row["comment"]}</span></td>";
						//echo "<td width='25'><a href='#' onclick='if(confirm(\"Убрать набор <b class=code>{$row["Code"]}</b> из списка отмененных/замененных?\", \"?del_otkaz={$row["OD_ID"]}&StartDate={$row["StartDate"]}&SH_ID={$row["SH_ID"]}&CT_ID={$CT_ID}&year={$year}&month={$month}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a></td>";
						echo "</tr>";
					}
					?>
				</tbody>
			</table>
			</div>
		</div>
		</div>
		</div>
	</div>
	<?
		echo "<script>
			$(document).ready(function() {
				$('#section1').html('<i class=\'fas fa-money-bill-alt fa-lg\'></i> Наличные: {$format_cache_sum}');
				$('#section2').html('<i class=\'fas fa-credit-card fa-lg\'></i> Эквайринг: {$format_terminal_sum}');
				$('#section3').html('<i class=\'fas fa-exchange-alt fa-lg\'></i> Инкассация: {$format_sum_send}');
				$('#section4').html('<i class=\'fas fa-handshake fa-lg\'></i> Неврученные наборы: {$not_taken_count}');
				$('#section5').html('<i class=\'fas fa-hand-paper fa-lg\'></i> Отказы/замены: {$reject_count}');
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

	<!--Кнопка печати цветных ценников-->
	<div id="print_price_btn" style="display: none;" title="Распечатать цветные ценники">
		<a id="print_price" style="display: block; height: 100%;"></a>
	</div>

	<!--Кнопка печати чёрно-белых ценников-->
	<div id="print_price_new_btn" style="display: none;" title="Распечатать чёрно-белые ценники">
		<a id="print_price_new" style="display: block; height: 100%;"></a>
	</div>

	<br>
	<form method="get">
	<table class="main_table">
		<thead>
			<tr>
				<th width="60">Отгружен <i class="fa fa-question-circle" html="<b>Статус получения набора:</b><br><i class='fas fa-handshake fa-2x not_confirmed'></i> - Клиент НЕ забрал набор<br><i class='fas fa-handshake fa-2x confirmed'></i> - Клиент забрал набор"></i></th>
				<th width="80">Код набора</th>
				<th width="10%">Клиент<br>Квитанция</th>
				<th width="25%">Набор</th>
				<th width="15%">Материал <i class="fa fa-question-circle" html="<b>Цветовой статус наличия:</b><br><span class='bg-gray'>Неизвестно</span><br><span class='bg-red'>Нет</span><br><span class='bg-yellow'>Заказано</span><br><span class='bg-green'>В наличии</span><br><span class='bg-red removed'>Выведен</span> - нужно менять"></i></th>
				<th width="15%">Цвет краски <i class="fa fa-question-circle" html="<b>Цветовой статус лакировки:</b><br><span class='empty'>Покраска не требуется</span><br><span class='notready'>Не дано в покраску</span><br><span class='inwork'>Дано в покраску</span><br><span class='ready'>Покрашено</span>"></i></th>
				<?
				if ($shop_num_rows > 1) {
				?>
				<th width="10%">Салон<br>
					<input type="hidden" name="CT_ID" value="<?=$_GET['CT_ID']?>">
					<input type="hidden" name="year" value="<?=$_GET['year']?>">
					<input type="hidden" name="month" value="<?=$_GET['month']?>">
					<select style="width: 100%;" name="SH_ID" id="filter_shop" onchange="this.form.submit()" <?=( $_GET["SH_ID"] ? "class='filtered'" : "" )?>>
						<option></option>
						<?
							$query = "SELECT SH.SH_ID, SH.Shop FROM Shops SH WHERE SH.SH_ID IN ({$SH_IDs}) ORDER BY SH.Shop";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) ) {
								echo "<option value='{$row['SH_ID']}' ".( $row['SH_ID'] == $_GET['SH_ID'] ? 'selected' : '' ).">{$row['Shop']}</option>";
							}
						?>
					</select>
				</th>
				<?
				}
				?>
				<th width="100">Примечание</th>
				<th width="100">Договор от</th>
				<th width="65">Стоимость набора</th>
				<th width="70">Скидка</th>
				<th width="65">Оплата</th>
				<th width="65">Доплата</th>
				<th width="70">Действие</th>
			</tr>
		</thead>
	</table>
	</form>
<div class="wr_main_table_body" style="height: calc(100vh - 260px);">
	<form method='post' id="formdiv">
	<table class="main_table">
		<thead>
			<tr>
				<th width="60"></th>
				<th width="80"></th>
				<th width="10%"></th>
				<th width="25%"></th>
				<th width="15%"></th>
				<th width="15%"></th>
				<? if ($shop_num_rows > 1) {echo "<th width='10%'></th>";} ?>
				<th width="100"></th>
				<th width="100"></th>
				<th width="65"></th>
				<th width="70"></th>
				<th width="65"></th>
				<th width="65"></th>
				<th width="70"></th>
			</tr>
		</thead>
		<tbody>
		<?
		$query = "
			SELECT SUM(1) cnt
				,GROUP_CONCAT(OD.OD_ID) OD_IDs
				,SUM(Ord_price(OD.OD_ID)) Price
				,SUM(Ord_discount(OD.OD_ID)) discount
				,SUM(Payment_sum(OD.OD_ID)) payment_sum
			FROM OrdersData OD
			JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1 AND ".( $SH_ID ? "SH.SH_ID = {$SH_ID}" : "SH.SH_ID IN ({$SH_IDs})")."
			LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
			WHERE OD.DelDate IS NULL
			".(($year == 0 and $month == 0) ? ' AND OD.StartDate IS NULL' : ' AND MONTH(OD.StartDate) = '.$month.' AND YEAR(OD.StartDate) = '.$year)."
			GROUP BY OD.StartDate, IF(TRIM(IFNULL(OD.ClientName, '')) LIKE '', MD5(OD.OD_ID), OD.ClientName), IFNULL(IF(PFI.rtrn = 1, NULL, OD.PFI_ID), 0), OD.SH_ID
			ORDER BY IFNULL(OD.StartDate, '9999-01-01'), OD.AddDate, OD.OD_ID
		";
		$rowspan = 0;
		$OD_IDs = "";
		$overres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $overrow = mysqli_fetch_array($overres) ) {
			$rowspan = ($overrow["OD_IDs"] != $OD_IDs ? $overrow["cnt"] : $rowspan);
			$format_diff = number_format($overrow["Price"] - $overrow['discount'] - $overrow["payment_sum"], 0, '', ' ');
			$diff_color = (($overrow["Price"] - $overrow['discount']) == $overrow["payment_sum"]) ? "#6f6" : ((($overrow["Price"] - $overrow['discount']) < $overrow["payment_sum"]) ? "#ff6" : "#fff");

			$query = "
				SELECT OD.OD_ID
					,OD.Code
					,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
					,IFNULL(OD.ClientName, '') ClientName
					,KA.Naimenovanie
					,OD.KA_ID
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(OD.ReadyDate, '%d.%m.%y') ReadyDate
					,OD.sell_comment
					,OD.ReadyDate RD
					,OD.SH_ID
					,OD.OrderNumber
					,Color(OD.CL_ID) Color
					,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
					,Ord_price(OD.OD_ID) Price
					,Ord_discount(OD.OD_ID) discount
					,Ord_opt_price(OD.OD_ID) opt_price
					,Payment_sum(OD.OD_ID) payment_sum
					,Items_count(OD.OD_ID) items
					,OD.is_lock
					,OD.confirmed
					,IF(PFI.rtrn = 1, NULL, OD.PFI_ID) PFI_ID
					,PFI.count
					,PFI.platelshik_id
					,OD.taken
				FROM OrdersData OD
				LEFT JOIN Kontragenty KA ON KA.KA_ID = OD.KA_ID
				LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
				WHERE OD.OD_ID IN ({$overrow["OD_IDs"]})
				ORDER BY OD.OD_ID
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				$is_lock = $row["is_lock"];			// Месяц закрыт в реализации
				$confirmed = $row["confirmed"];		// Набор утвержден
				$format_price = number_format($row["Price"] - $row['discount'], 0, '', ' ');
				$format_opt_price = number_format($row["opt_price"], 0, '', ' ');
				$format_payment = number_format($row["payment_sum"], 0, '', ' ');
				$format_discount = number_format($row['discount'], 0, '', ' ');
//				$format_diff = number_format($row["Price"] - $row['discount'] - $row["payment_sum"], 0, '', ' ');
//				$diff_color = (($row["Price"] - $row['discount']) == $row["payment_sum"]) ? "#6f6" : ((($row["Price"] - $row['discount']) < $row["payment_sum"]) ? "#ff6" : "#fff");
				$percent = round($row["discount"] / $row["Price"] * 100, 1);
				// Подсвечиваем скидку в случае превышения порога
				if( $percent >= 5 ) {$discount_bg = "bg-red";}
				elseif( $percent >= 3 ) {$discount_bg = "bg-yellow";}
				else {$discount_bg = "";}

				echo "
					<tr id='ord{$row["OD_ID"]}'>
						<td>
							<input type='hidden' name='OD_ID[]' form='print_selling' value='{$row["OD_ID"]}'>
							<span>{$row["ReadyDate"]}</span>
				";

				// Если набор у клиента
				if( $row["ReadyDate"] and $row["StartDate"]) {
					if( $row["taken"] == 1 ) {
						$class = 'confirmed';
					}
					else {
						$class = 'not_confirmed';
					}
					if( $row["StartDate"] and in_array('order_add', $Rights) and !$is_lock ) {
						$class = $class." taken_confirmed";
					}
					echo "<span val='{$row["taken"]}' class='{$class}'><i class='fas fa-handshake fa-2x'></i></td>";
				}

				// Получаем содержимое набора
				$query = "
					SELECT ODD.ODD_ID
						,ODD.Amount
						,Zakaz(ODD.ODD_ID) zakaz
						,ODD.Comment
						,DATEDIFF(ODD.arrival_date, NOW()) outdate
						,ODD.IsExist
						,Friendly_date(ODD.order_date) order_date
						,Friendly_date(ODD.arrival_date) arrival_date
						,IFNULL(MT.Material, '') Material
						,CONCAT(' <b>', SH.Shipper, '</b>') Shipper
						,ODD.MT_ID
						,MT.SH_ID
						,SH.mtype
						,IF(MT.removed=1, 'removed', '') removed
						,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
					FROM OrdersDataDetail ODD
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
					LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
					WHERE ODD.OD_ID = {$row["OD_ID"]}
					ORDER BY PTID DESC, ODD.ODD_ID
				";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				// Формируем подробности набора
				$zakaz = '';
				$material = '';
				$color = '';
				$cnt = 0;
				while( $subrow = mysqli_fetch_array($subres) ) {

					// Если в свободных - добавляем чекбокс для печати ценников, узнаем сколько изделий в наборе для ценника на гарнитур
					if (!$row["StartDate"]) {
						$zakaz .= "<input type='checkbox' value='{$subrow["ODD_ID"]}' name='odd[]' class='chbox'>";
						$cnt = $cnt + $subrow["Amount"];
					}
					if ($subrow["Comment"]) {
						$zakaz .= "<b class='material'><i id='prod{$subrow["ODD_ID"]}' title='{$subrow["Comment"]}'><i class='fa fa-comment'></i> <b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</i></b><br>";
					}
					else {
						$zakaz .= "<b class='material'><i id='prod{$subrow["ODD_ID"]}'><b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</i></b><br>";
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
					$material .= "<span class='wr_mt'>".(($subrow["outdate"] <= 0 and $subrow["IsExist"] == 1) ? "<i class='fas fa-exclamation-triangle' style='color: #E74C3C;' title='{$subrow["outdate"]} дн.'></i>" : "")."<span shid='{$subrow["SH_ID"]}' mtid='{$subrow["MT_ID"]}' id='m{$subrow["ODD_ID"]}' class='mt{$subrow["MT_ID"]} {$subrow["removed"]} {$subrow["MTfilter"]} material ".(in_array('screen_materials', $Rights) ? "mt_edit" : "")." {$color}'>{$subrow["Material"]}{$subrow["Shipper"]}</span><input type='text' value='{$subrow["Material"]}' class='materialtags_{$subrow["mtype"]}' style='display: none;'><input type='checkbox' ".($subrow["removed"] ? "checked" : "")." style='display: none;' title='Выведен'></span><br>";
				}

				echo "
					</td>
					<td><span><b class='code'>{$row["Code"]}</b>".(($cnt > 1 and false) ? "<input type='checkbox' value='{$row["OD_ID"]}' name='od[]' class='chbox'>" : "")."<br>{$row["AddDate"]}</span></td>
					<td><span>".($row["Naimenovanie"] ? "<n class='ul'>{$row["Naimenovanie"]}</n><br>" : "")."{$row["ClientName"]}<br><b>{$row["OrderNumber"]}</b></span></td>
					<td><span class='nowrap'>{$zakaz}</span></td>
					<td><span class='nowrap material'>{$material}</span></td>
				";

				echo "<td val='{$row["IsPainting"]}'";
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
				echo " class='painting_cell {$class}'>{$row["Color"]}</td>";

				if ($shop_num_rows > 1) {
					echo "<td><span><select style='width: 100%;' ".(($is_lock) ? "disabled" : "class='select_shops'")." OD_IDs='{$overrow["OD_IDs"]}'>{$select_shops}</select></span></td>";
				}
				//echo "<td id='{$row["OD_ID"]}'><input type='text' class='sell_comment' value='". htmlspecialchars($row["sell_comment"], ENT_QUOTES) ."'></td>";
				echo "<td><textarea class='sell_comment' style='width: 100%; resize: vertical;'>{$row["sell_comment"]}</textarea></td>";

				// Если набор в накладной - стоимость набора ведет в накладную, цена не редактируется
				if( $row["PFI_ID"] ) {
					// Исключение для Клена
					if ($row["SH_ID"] == 36) {
						$price = "<button style='width: 100%;' class='update_price_btn button nowrap txtright' location='{$location}'>{$format_price}</button><br><a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><b title='Стоимость по накладной'>{$format_opt_price}<i class='fa fa-question-circle' aria-hidden='true'></i></b></a>";
					}
					else {
						$price = "<a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><b title='Стоимость по накладной'>{$format_price}<i class='fa fa-question-circle' aria-hidden='true'></i></b></a>";
					}
				}
				else {
					$price = "<button style='width: 100%;' class='update_price_btn button nowrap txtright' location='{$location}'>{$format_price}</button>";
				}

				echo "<td><input ".($is_lock ? "disabled" : "")." type='text' class='date sell_date' value='{$row["StartDate"]}' OD_IDs='{$overrow["OD_IDs"]}' readonly ".(($row["StartDate"] and !$is_lock) ? "title='Чтобы стереть дату продажи нажмите на символ ладошки справа.'" : "")."></td>
				<td class='txtright'>{$price}</td>
				<td class='txtright nowrap'>{$format_discount} p.<br><b class='{$discount_bg}'>{$percent} %</b></td>
				<td><button ".($row["KA_ID"] ? "disabled" : "")." style='width: 100%;' class='add_payment_btn button nowrap txtright' location='{$location}'>{$format_payment}</button></td>";

				// Группировка ячеек с суммой доплаты
				if ($rowspan) {
					// Если в накладной - выводим ссылку на сверки
					if( $row["PFI_ID"] ) {
						echo "<td rowspan='{$rowspan}' style='".($rowspan > 1 ? "border: 2px dotted;" : "")."'><a href='sverki.php?payer={$row["platelshik_id"]}' target='_blank' title='Перейти в сверки'><b>Сверки</b></a></td>";
					}
					else {
						echo "<td rowspan='{$rowspan}' class='txtright' style='background: {$diff_color}; ".($rowspan > 1 ? "border: 2px dotted;" : "")."'>{$format_diff}</td>";
					}
					$rowspan = 0;

					// Собираем ошибки если у проданного набора нет предоплаты
					if ($overrow["Price"] - $overrow['discount'] > 0 and $row["StartDate"] and $overrow["payment_sum"] == 0 and !$row["PFI_ID"] and !$row["KA_ID"] and $row["SH_ID"] != 36) {
						$_SESSION["error"][] = "Набор <a href='#ord{$row["OD_ID"]}'><b class='code'>{$row["Code"]}</b></a> продан {$row["StartDate"]}, но предоплата не внесена!";
					}
				}
				echo "<td>";

				// Если есть права на редактирование набора и набор не закрыт, то показываем карандаш, кнопку разделения и отказа
				if( in_array('order_add', $Rights) and !$is_lock ) {
					echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Редактировать'><i class='fa fa-pencil-alt fa-lg'></i></a> ";
					echo "<a href='#' id='{$row["OD_ID"]}' class='order_cut' title='Разделить набор' location='{$location}'><i class='fa fa-sliders-h fa-lg'></i></a> ";
					echo "<a href='#' id='{$row["OD_ID"]}' class='order_otkaz_btn' invoice={$row["PFI_ID"]} location='{$location}' payment='{$row["payment_sum"]}' old_sum='".($row["Price"] - $row["discount"])."' title='Пометить как отказ/замена.'><i class='fa fa-hand-paper fa-lg' aria-hidden='true'></i></a>";
				}
				else {
					echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Посмотреть'><i class='fa fa-eye fa-lg'></i></a> ";
				}

				echo "</td></tr>";
				echo "<script>";
				echo "$('#ord{$row["OD_ID"]} select').val('{$row["SH_ID"]}');";
				echo "</script>";
			}
		}
		?>
		</tbody>
	</table>
	</form>
</div>

<!-- Форма добавления/редактирования расхода/прихода -->
<div id='add_cost' style='display:none'>
	<form method='post' action='<?=$location?>&add_cost=1' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="OP_ID" id="OP_ID">
			<input type="hidden" name="sign" id="sign">
			<div style="width: 100px; display: inline-block; margin-right: 15px; vertical-align: top;">
				<label for="cost">Сумма:</label><br>
				<input type="number" name="cost" min="0" id="cost" style="width: 100%; text-align: right;" required>
			</div>
			<br><br>
			<div style="width: 210px; display: inline-block; vertical-align: top;">
				<label for="CB_ID">Касса:</label><br>
				<select size="4" name="CB_ID" id="CB_ID" style="width: 100%;" required>
					<?
					$query = "
						SELECT CB.CB_ID
							,CB.name
						FROM CashBox CB
						WHERE CB.CB_ID IN (".($USR_Shop ? "SELECT CB_ID FROM Shops WHERE SH_ID IN ({$USR_Shop})" : "SELECT CB_ID FROM Cities WHERE CT_ID = {$CT_ID} AND CT_ID = {$USR_City}").")
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$cashbox_cnt = mysqli_num_rows($res);
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option ".(($cashbox_cnt == 1) ? "selected" : "")." value='{$row["CB_ID"]}'>{$row["name"]}</option>";
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
					<label for="send">Инкассация</label>
				</div>
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!-- Конец формы добавления/редактирования расхода/прихода -->

<!-- Форма отказа -->
<div id='order_otkaz' style='display:none'>
	<form method='post' action="<?=$location?>&order_otkaz" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<div style="display: inline-block;">
			<i class='fa fa-hand-paper fa-4x' aria-hidden='true'></i>
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
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!-- Конец формы отказа -->

<script>
	$(function() {
		// Данные для печати
		print_data = $('#print_selling').serialize();
		$("#toprint").attr('href', '/toprint/print_selling.php?' + print_data);

		// Открытие диалога печати
		$("#toprint").printPage();
		$("#print_price").printPage();
		$("#print_price_new").printPage();

		$( "#accordion" ).accordion({
			active: false,
			collapsible: true,
			heightStyle: "content"
		});

		$( ".button" ).button( "option", "classes.ui-button", "highlight" );

		// Если выбраны товары - показываем кнопку печати ценников
		$('.chbox').change(function(){
			var checked_status = false;
			$('.chbox').each(function(){
				if( $(this).prop('checked') )
				{
					checked_status = $(this).prop('checked');
				}
			});
			if( checked_status ) {
				$('#print_price_btn').show();
				$('#print_price_new_btn').show();
			}
			else {
				$('#print_price_btn').hide();
				$('#print_price_new_btn').hide();
			}

			var data = $('#formdiv').serialize();
			$("#print_price").attr('href', '/toprint/print_price.php?' + data);
			$("#print_price_new").attr('href', '/toprint/print_price_new.php?' + data);
			return false;
		});

		// Кнопка добавления/редактирования расхода
		$('.add_cost_btn').click( function() {
			var sign = $(this).attr('sign');
			var CB_ID = $(this).attr('cb_id');
			var OP_ID = $(this).attr('id');
			var cost_name = $(this).attr('cost_name');
			var cost = $(this).attr('cost');

			$('#add_cost #cost').val('');
			if (CB_ID) {
				$('#add_cost #CB_ID').val(CB_ID);
				$('#add_cost #cost').val(Math.abs(cost));
			}
			else if (<?=$cashbox_cnt?> > 1) {
				$('#add_cost #CB_ID').val('');
			}
			$('#add_cost #OP_ID').val(OP_ID);
			$('#add_cost #cost_name').val(cost_name);
			$('#send').prop('checked', false);

			$('#add_cost').dialog({
				resizable: false,
				width: 300,
				modal: true,
				closeText: 'Закрыть'
			});

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
				$('#add_cost').dialog('option', 'title', 'ИНКАССАЦИЯ');
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
				noty({timeout: 3000, text: 'Прежде чем пометить набор как "отказной", анулируйте накладную в актах сверки.', type: 'error'});
			}
			else if( payment != 0 ) {
				$(this).parents('tr').find('.add_payment_btn span').effect( 'highlight', {color: 'red'}, 1000 );
				noty({timeout: 3000, text: 'Прежде чем пометить набор как "отказной", обнулите приход по нему.', type: 'error'});
			}
			else {
				$('#order_otkaz').dialog({
					resizable: false,
					width: 400,
					modal: true,
					closeText: 'Закрыть'
				});
			}
		});

		// Редактирование салона
		$('.select_shops').on('change', function() {
			var OD_ID = $(this).parents('tr').attr('id');
			OD_ID = OD_ID.replace('ord', '');
			var OD_IDs = $(this).attr('OD_IDs');
			var val = $(this).val();
			$.ajax({ url: "ajax.php?do=update_shop&OD_ID="+OD_ID+"&SH_ID="+val+"&OD_IDs="+OD_IDs, dataType: "script", async: false });
		});

		// Редактирование даты продажи
		$('.sell_date').on('change', function() {
			var OD_ID = $(this).parents('tr').attr('id');
			OD_ID = OD_ID.replace('ord', '');
			var OD_IDs = $(this).attr('OD_IDs');
			var val = $(this).val();
			$.ajax({ url: "ajax.php?do=update_sell_date&OD_ID="+OD_ID+"&StartDate="+val+"&OD_IDs="+OD_IDs, dataType: "script", async: false });
		});

		// Редактирование примечания к реализации
		$('.sell_comment').on('change', function() {
			var OD_ID = $(this).parents('tr').attr('id');
			OD_ID = OD_ID.replace('ord', '');
			var val = escapeHtml($(this).val());
			val = val.replace(/\n/g, ' '); // Замена переноса строки на пробел
			$.ajax({ url: "ajax.php?do=update_sell_comment&OD_ID="+OD_ID+"&sell_comment="+val, dataType: "script", async: false });
		});

		// При изменении даты продажи нельзя поставить переднее число
		$( '.sell_date' ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );
	});
</script>

<?
	include "footer.php";
?>
