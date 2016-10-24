<?
	include "config.php";
	$title = 'Реализация';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('selling_all', $Rights) and !in_array('selling_city', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$SH_ID = $_GET["SH_ID"] ? $_GET["SH_ID"] : 0;
	// Узнаем в каком городе находится выбранный салон
	if( $SH_ID ) {
		$query = "SELECT CT_ID FROM Shops WHERE SH_ID = {$SH_ID}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$CT_ID = mysqli_result($res,0,'CT_ID');

		// Проверка прав на доступ к экрану (если пользователю доступен только город)
		if( in_array('selling_city', $Rights) and $CT_ID != $USR_City ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
	}

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
			$payment_sum = $_POST["payment_sum"][$key];
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
		foreach ($_POST["PT_ID"] as $key => $value) {
			if( $value == 0 ) {
				$query = "UPDATE OrdersDataBlank SET Price = {$_POST["price"][$key]} WHERE ODB_ID = {$_POST["itemID"][$key]}";
			}
			else {
				$query = "UPDATE OrdersDataDetail SET Price = {$_POST["price"][$key]} WHERE ODD_ID = {$_POST["itemID"][$key]}";
			}
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		die;
	}

	include "forms.php";
?>

<form method="get">
	<select name="SH_ID" onchange="this.form.submit()">
		<option value="">-=Выберите салон=-</option>
		<?
		$query = "SELECT SH.SH_ID, CONCAT(CT.City, '/', SH.Shop) Shop, CT.Color
					FROM Cities CT
					JOIN Shops SH ON SH.CT_ID = CT.CT_ID AND SH.retail = 1
					".(in_array('selling_city', $Rights) ? 'WHERE CT.CT_ID = '.$USR_City : '')."
					ORDER BY CT.City, SH.Shop";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) )
		{
			echo "<option ".($_GET["SH_ID"] == $row["SH_ID"] ? "selected" : "")." value='{$row["SH_ID"]}' style='background: {$row["Color"]};'>{$row["Shop"]}</option>";
		}
		?>
	</select>
</form>
	<br>
	<table class="main_table">
		<thead>
			<tr>
				<th width="5%">Дата поступления</th>
				<th width="51">№ упаковки</th>
				<th width="5%">№ квитанции</th>
				<th width="5%">Заказчик</th>
				<th width="25%">Наименование</th>
				<th width="15%">Материал</th>
				<th width="15%">Цвет</th>
				<th width="40">Кол-во</th>
				<th width="5%">Дата продажи</th>
				<th width="65">Сумма заказа</th>
				<th width="65">Оплата</th>
				<th width="20">Т</th>
				<th width="5%">Салон</th>
			</tr>
		</thead>
	</table>
<div class="wr_main_table_body">
	<table class="main_table">
		<thead>
			<tr>
				<th width="5%"></th>
				<th width="51"></th>
				<th width="5%"></th>
				<th width="5%"></th>
				<th width="25%"></th>
				<th width="15%"></th>
				<th width="15%"></th>
				<th width="40"></th>
				<th width="5%"></th>
				<th width="65"></th>
				<th width="65"></th>
				<th width="20"></th>
				<th width="5%"></th>
			</tr>
		</thead>
		<tbody>
		<?
		$query = "SELECT OD.OD_ID
						,OD.Code
						,IFNULL(OD.ClientName, '') ClientName
						,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
						,SH.Shop
						,OD.OrderNumber
						,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
						,GROUP_CONCAT(ODD_ODB.Amount SEPARATOR '') Amount
						,OD.Color
						,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
						,SUM(ODD_ODB.Price) Price
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
					WHERE OD.Del = 0 AND OD.ReadyDate IS NOT NULL AND OD.SH_ID = {$SH_ID}
					GROUP BY OD.OD_ID
					ORDER BY OD.OD_ID DESC";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$format_price = number_format($row["Price"], 0, '', ' ');
			$format_payment = number_format($row["payment_sum"], 0, '', ' ');
			echo "
				<tr id='ord{$row["OD_ID"]}'>
					<td><span></span></td>
					<td>{$row["Code"]}</td>
					<td><span>{$row["OrderNumber"]}</span></td>
					<td><span>{$row["ClientName"]}</span></td>
					<td><span class='nowrap'>{$row["Zakaz"]}</span></td>
					<td><span class='nowrap material'>{$row["Material"]}</span></td>
					<td>{$row["Color"]}</td>
					<td>{$row["Amount"]}</td>
					<td><span>{$row["StartDate"]}</span></td>
					<td><a style='width: 100%; text-align: right;' class='update_price_btn button nowrap' id='{$row["OD_ID"]}'>{$format_price}</a></td>
					<td><a style='width: 100%; text-align: right;' class='add_payment_btn button nowrap' id='{$row["OD_ID"]}'>{$format_payment}</a></td>
					<td>".($row["terminal_payer"] ? "<i title='Оплата по терминалу' class='fa fa-credit-card' aria-hidden='true'></i>" : "")."</td>
					<td><span>{$row["Shop"]}</span></td>
				</tr>
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

		// Кнопка добавления заказа
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

		// Кнопка добавления заказа
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
				$('.prod_price').each(function(){
					var prod_price = $(this).find('input').val();
					var prod_amount = $(this).parents('tr').find('.prod_amount').html();
					var prod_sum = prod_price * prod_amount;
					prod_total = prod_total + prod_sum;
					prod_sum = prod_sum.format();
					$(this).parents('tr').find('.prod_sum').html(prod_sum);
				});
				prod_total = prod_total.format();
				$('#prod_total').html(prod_total);
			}

			updprice();

			$('.prod_price input').on('input', function() {
				updprice();
			});

			return false;
		});
	});
</script>
