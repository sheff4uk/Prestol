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

	$location = $_SERVER['REQUEST_URI'];
	$_SESSION["location"] = $location;

	if( in_array('selling_city', $Rights) ) {

	}

	// Добавление в базу нового платежа (или обновление старого)
	if( isset($_POST["payment_date_add"]) )
	{
		$OD_ID = $_POST["OD_ID"];
		$payment_date = date( 'Y-m-d', strtotime($_POST["payment_date_add"]) );
		$payment_sum = $_POST["payment_sum_add"];
		$terminal = $_POST["terminal_add"];
		$terminal_payer = $terminal ? '\''.mysqli_real_escape_string( $mysqli, $_POST["terminal_payer_add"] ).'\'' : 'NULL';

		if( $payment_sum ) {
			$query = "INSERT INTO OrdersPayment
						 SET OD_ID = {$OD_ID}
							,payment_date = '{$payment_date}'
							,payment_sum = {$payment_sum}
							,terminal_payer = {$terminal_payer}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		foreach ($_POST["OP_ID"] as $key => $value) {
			$payment_date = date( 'Y-m-d', strtotime($_POST["payment_date"][$key]) );
			$payment_sum = $_POST["payment_sum"][$key] ? $_POST["payment_sum"][$key] : "NULL";
			$terminal = $_POST["terminal"][$key];
			$terminal_payer = $terminal ? '\''.mysqli_real_escape_string( $mysqli, $_POST["terminal_payer"][$key] ).'\'' : 'NULL';

			$query = "UPDATE OrdersPayment
						 SET payment_date = '{$payment_date}'
							,payment_sum = {$payment_sum}
							,terminal_payer = {$terminal_payer}
						WHERE OP_ID = {$value}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		die;
	}

	// Обновление цены изделий в заказе
	if( isset($_POST["price"]) ) {
		$OD_ID = $_POST["OD_ID"];
		$discount = $_POST["discount"] ? $_POST["discount"] : "NULL";
		// Обновление скидки заказа
		$query = "UPDATE OrdersData SET discount = {$discount} WHERE OD_ID = {$OD_ID}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		foreach ($_POST["PT_ID"] as $key => $value) {
			$price = $_POST["price"][$key] ? $_POST["price"][$key] : "NULL";
			if( $value == 0 ) {
				$query = "UPDATE OrdersDataBlank SET Price = {$price} WHERE ODB_ID = {$_POST["itemID"][$key]}";
			}
			else {
				$query = "UPDATE OrdersDataDetail SET Price = {$price} WHERE ODD_ID = {$_POST["itemID"][$key]}";
			}
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
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
	}
	#selling_report table {
		display: inline-block;
		vertical-align: top;
		margin-right: 20px;
	}
</style>

<form method="get">
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
	<br>

	<!-- КНОПКИ ОТЧЕТОВ -->
	<div style="max-height: 23px;">
		Отчеты:
		<?
		$highlight = ($_GET["year"] == '' or $_GET["month"] == '') ? 'border: 1px solid #fbd850; color: #eb8f00;' : '';
		echo "<a href='?CT_ID={$CT_ID}' class='button' style='{$highlight}'>Все</a> ";

		$query = "SELECT IFNULL(YEAR(OD.StartDate), 0) year, IFNULL(MONTH(OD.StartDate), 0) month FROM OrdersData OD JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1 WHERE OD.Del = 0 AND SH.CT_ID = {$CT_ID} GROUP BY YEAR(OD.StartDate), MONTH(OD.StartDate) ORDER BY OD.StartDate";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$highlight = ($_GET["year"] == $row["year"] and $_GET["month"] == $row["month"]) ? 'border: 1px solid #fbd850; color: #eb8f00;' : '';
			if( $row["year"] == 0 and $row["month"] == 0 ) {
				echo "<a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}' class='button' style='{$highlight}'>Свободные</a> ";
			}
			else {
				echo "<a href='?CT_ID={$CT_ID}&year={$row["year"]}&month={$row["month"]}' class='button' style='{$highlight}'>{$MONTHS[$row["month"]]} - {$row["year"]}</a> ";
			}
		}
		?>
	</div>
	<!-- //КНОПКИ ОТЧЕТОВ -->

	<?
	// ОТЧЕТ ЗА МЕСЯЦ
	if( $_GET["year"] > 0 and $_GET["month"] > 0 ) {
	?>
		<div id='selling_report'>
			<table>
<!--
				<thead>
					<tr>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
-->
				<tbody>
				<?
					$city_price = 0;
					$city_discount = 0;
					$query = "SELECT SH_ID, Shop FROM Shops WHERE CT_ID = {$CT_ID} AND retail = 1 ORDER BY SH_ID";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<tr>";
						echo "<td class='nowrap'>ВЫРУЧКА {$row["Shop"]}:</td>";
						// Получаем сумму выручки по салону
						$query = "SELECT SUM(ODD_ODB.Price) Price
									FROM OrdersData OD
									JOIN (
										SELECT ODD.OD_ID
											,ODD.Price * ODD.Amount Price
										FROM OrdersDataDetail ODD
										UNION
										SELECT ODB.OD_ID
											,ODB.Price * ODB.Amount Price
										FROM OrdersDataBlank ODB
									) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
									WHERE OD.Del = 0 AND YEAR(OD.StartDate) = {$_GET["year"]} AND MONTH(OD.StartDate) = {$_GET["month"]} AND OD.SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_price = mysqli_result($subres,0,'Price');
						$city_price = $city_price + $shop_price;

						// Получаем скидку по салону
						$query = "SELECT SUM(discount) discount FROM OrdersData WHERE YEAR(StartDate) = {$_GET["year"]} AND MONTH(StartDate) = {$_GET["month"]} AND SH_ID = {$row["SH_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$shop_discount = mysqli_result($subres,0,'discount');
						$city_discount = $city_discount + $shop_discount;

						$shop_price = number_format(($shop_price - $shop_discount), 0, '', ' ');
						echo "<td class='txtright'>{$shop_price}</td>";
						echo "</tr>";
					}
					$city_price = number_format(($city_price - $city_discount), 0, '', ' ');
					echo "<thead><tr>";
					echo "<th class='nowrap'><b>ВСЕГО ЗА {$MONTHS[$_GET["month"]]} {$_GET["year"]}:</b></th>";
					echo "<th class='txtright'><b>{$city_price}</b></th>";
					echo "</tr></thead>";

				?>
				</tbody>
			</table>

			<table>
				<tbody>
				<?
					$terminal_sum = 0;
					$query = "SELECT DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
									,OP.payment_sum
									,OP.terminal_payer
								FROM OrdersPayment OP
								JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
								JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.CT_ID = {$CT_ID}
								WHERE terminal_payer IS NOT NULL AND YEAR(OP.payment_date) = {$_GET["year"]} AND MONTH(OP.payment_date) = {$_GET["month"]}";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$format_sum = number_format($row["payment_sum"], 0, '', ' ');
					$terminal_sum = $terminal_sum + $row["payment_sum"];
					echo "<tr>";
					echo "<td>{$row["payment_date"]}</td>";
					echo "<td class='nowrap'>{$row["terminal_payer"]}</td>";
					echo "<td class='txtright'>{$format_sum}</td>";
					echo "</tr>";
				}
				$terminal_sum = number_format($terminal_sum, 0, '', ' ');
				echo "<thead><tr>";
				echo "<th colspan='2' class='nowrap'><b>Оплата по ТЕРМИНАЛУ:</b></th>";
				echo "<th class='txtright'><b>{$terminal_sum}</b></th>";
				echo "</tr></thead>";
				?>
				</tbody>
			</table>

		</div>
	<?
		echo "<script> $(document).ready(function() { $('.wr_main_table_body').css('height', 'calc(100% - 400px)'); }); </script>";
	}
	?>

	<br>
	<table class="main_table">
		<thead>
			<tr>
				<th width="6%">Дата отгрузки</th>
				<th width="51">№ упаковки</th>
				<th width="5%">№ квитанции</th>
				<th width="5%">Заказчик</th>
				<th width="25%">Наименование</th>
				<th width="15%">Материал</th>
				<th width="15%">Цвет</th>
				<th width="40">Кол-во</th>
				<th width="10%">Салон</th>
				<th width="6%">Дата продажи</th>
				<th width="65">Сумма заказа</th>
				<th width="70">Скидка</th>
				<th width="65">Оплата</th>
				<th width="20">Т</th>
				<th width="65">Остаток</th>
				<th width="45"></th>
			</tr>
		</thead>
	</table>
<div class="wr_main_table_body" style="display: none;">
	<table class="main_table">
		<thead>
			<tr>
				<th width="6%"></th>
				<th width="51"></th>
				<th width="5%"></th>
				<th width="5%"></th>
				<th width="25%"></th>
				<th width="15%"></th>
				<th width="15%"></th>
				<th width="40"></th>
				<th width="10%"></th>
				<th width="6%"></th>
				<th width="65"></th>
				<th width="70"></th>
				<th width="65"></th>
				<th width="20"></th>
				<th width="65"></th>
				<th width="45"></th>
			</tr>
		</thead>
		<tbody>
		<?
		$query = "SELECT OD.OD_ID
						,OD.Code
						,IFNULL(OD.ClientName, '') ClientName
						,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
						,DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') ReadyDate
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
				  FROM OrdersData OD
				  JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
				  LEFT JOIN (SELECT OD_ID, SUM(payment_sum) payment_sum, GROUP_CONCAT(terminal_payer) terminal_payer FROM OrdersPayment GROUP BY OD_ID) OP ON OP.OD_ID = OD.OD_ID
				  LEFT JOIN (SELECT ODD.OD_ID
								   ,IFNULL(PM.PT_ID, 2) PT_ID
								   ,ODD.ODD_ID itemID
								   ,ODD.Price * ODD.Amount Price

								   ,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', '</i></b><br>') Zakaz

								   ,CONCAT(IFNULL(CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')'), ''), '<br>') Material
								   ,CONCAT(ODD.Amount, '<br>') Amount

							FROM OrdersDataDetail ODD
							LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
							LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
							LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
							LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
							LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
							GROUP BY ODD.ODD_ID
							UNION
							SELECT ODB.OD_ID
								  ,0 PT_ID
								  ,ODB.ODB_ID itemID
								  ,ODB.Price * ODB.Amount Price

								  ,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'blank', ODB.ODB_ID, '\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', IFNULL(BL.Name, ODB.Other), '</i></b><br>') Zakaz

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
					ORDER BY IFNULL(OD.ReadyDate, '9999-01-01') ASC, OD.AddDate ASC, OD.OD_ID ASC";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$format_price = number_format($row["Price"], 0, '', ' ');
			$format_payment = number_format($row["payment_sum"], 0, '', ' ');
			$format_discount = number_format($row['discount'], 0, '', ' ');
			$format_diff = number_format($row["Price"] - $row["payment_sum"], 0, '', ' ');
			$diff_color = ($row["Price"] == $row["payment_sum"]) ? "#6f6" : (($row["Price"] < $row["payment_sum"]) ? "#f66" : "#fff");
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
					<td><span id='{$row["OD_ID"]}'>{$select_shops}</span></td>
					<td><span>{$row["StartDate"]}</span></td>
					<td><a style='width: 100%; text-align: right;' class='update_price_btn button nowrap' id='{$row["OD_ID"]}'>{$format_price}</a></td>
					<td class='txtright nowrap'>{$format_discount} p.<br>{$row["percent"]} %</td>
					<td><a style='width: 100%; text-align: right;' class='add_payment_btn button nowrap' id='{$row["OD_ID"]}'>{$format_payment}</a></td>
					<td>".($row["terminal_payer"] ? "<i title='Оплата по терминалу' class='fa fa-credit-card' aria-hidden='true'></i>" : "")."</td>
					<td class='txtright' style='background: {$diff_color}'>{$format_diff}</td>
					<td>";
			if( in_array('order_add', $Rights) ) {
				echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Редактировать'><i class='fa fa-pencil fa-lg'></i></a> ";
				echo "<a href='#' id='{$row["OD_ID"]}' class='order_cut' title='Разделить заказ' location='{$location}'><i class='fa fa-sliders fa-lg'></i></a> ";
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
	<form method='post'>
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
	<form method='post'>
		<fieldset>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы редактирования суммы заказа -->

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
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			$('input[name=payment_sum_add]').focus();
			$('input.date').datepicker();
			$('#add_payment .terminal').change(function() {
				var ch = $(this).prop('checked');
				var terminal_payer = $(this).parents('tr').find('.terminal_payer');
				if( ch ) {
					$(terminal_payer).prop('disabled', false);
					$(terminal_payer).prop('required', true);
				}
				else {
					$(terminal_payer).prop('disabled', true);
					$(terminal_payer).prop('required', false);
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
				prod_total = prod_total.format();
				$('#prod_total').html(prod_total);
				$('#discount span').html(percent);
			}

			updprice();

			$('.prod_price input, #discount input').on('input', function() {
				updprice();
			});

			return false;
		});

		// Редактирование салона
		$('.select_shops').on('change', function() {
			var OD_ID = $(this).parent().attr('id');
			var val = $(this).val();
			$.ajax({ url: "ajax.php?do=update_shop&OD_ID="+OD_ID+"&SH_ID="+val, dataType: "script", async: false });
		});
	});
</script>
