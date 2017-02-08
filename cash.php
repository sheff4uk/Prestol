<?
	include "config.php";
	$title = 'Касса';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_paylog', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$location = "cash.php";

	if( $_SESSION["cash_from"] ) {
		$cash_from = $_SESSION["cash_from"];
		$cash_to = $_SESSION["cash_to"];
	}
	else {
		$cash_from = date('d.m.Y', mktime(0, 0, 0, date("m"), 1, date("Y")));
//		$cash_from = date('d.m.Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$cash_to = date('d.m.Y');
	}

	// Изменение периода отображения
	if( isset($_POST["cash_from"]) ) {
		$_SESSION["cash_from"] = $_POST["cash_from"];
		$_SESSION["cash_to"] = $_POST["cash_to"];
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Принятие платежа
	if( isset($_GET["cost_name"]) )
	{
		$OP_ID = $_GET["OP_ID"];
		$payment_sum = $_GET["payment_sum"];
		$cost_name = mysqli_real_escape_string( $mysqli, $_GET["cost_name"]);

		$query = "UPDATE OrdersPayment
				  SET send = 2
				  WHERE OP_ID = {$OP_ID}";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["alert"] = mysqli_error( $mysqli );
		}
		else {
			$query = "INSERT INTO OrdersPayment
						SET payment_date = NOW(),
							payment_sum = {$payment_sum},
							cost_name = '{$cost_name}',
							send = 2";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"] = mysqli_error( $mysqli );
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
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
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"] = mysqli_error( $mysqli );
			}
		}
		else { // Добавляем расход
			if( $cost ) {
				$CT_ID = $_POST["CT_ID"];
				$query = "INSERT INTO OrdersPayment SET cost_name = '{$cost_name}', payment_date = '{$cost_date}', payment_sum = {$cost}";
				if( !mysqli_query( $mysqli, $query ) ) {
					$_SESSION["alert"] = mysqli_error( $mysqli );
				}
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}
?>

<style>
	#wr_send {
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		margin-top: 10px;
		z-index: 2;
		width: 800px;
		margin: auto;
		overflow: auto;
		white-space: nowrap;
	}
</style>

<?
	// Узнаем общий остаток наличных
	$query = "SELECT SUM(pay_in) - SUM(pay_out) ostatok FROM OstatkiShops WHERE CT_ID = 0";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$ostatok = mysqli_result($res,0,'ostatok');
	$format_ostatok = number_format($ostatok, 0, '', ' ');
	$now_date = date('d.m.Y');
?>

<div style="width: 1000px; margin: auto;">
	<h2 style="text-align: center;">Касса: <?=$format_ostatok?></h2>

	<div id="wr_send">
	<table style="width: 100%;">
		<thead>
			<tr>
				<th colspan="4">Отправлено</th>
			</tr>
		</thead>
		<tbody>
	<?
		$query = "SELECT OP.OP_ID
						,DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
						,ABS(OP.payment_sum) payment_sum
						,OP.cost_name
						,CT.City
				FROM OrdersPayment OP
				JOIN Cities CT ON CT.CT_ID = OP.CT_ID
				WHERE send = 1 AND payment_sum < 0
				ORDER BY OP.payment_date DESC";

		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		while( $row = mysqli_fetch_array($res) ) {
			$payment_sum = number_format($row["payment_sum"], 0, '', ' ');
			$delmessage = addslashes("Принять <b>{$payment_sum}р</b> из {$row["City"]} ({$row["cost_name"]})?<br><b>Внимание!</b> Данную операцию отменить невозможно.");

			echo "<tr>";
			echo "<td>{$row["City"]} ({$row["cost_name"]})</td>";
			echo "<td>{$row["payment_date"]}</td>";
			echo "<td class='txtright'><b>{$payment_sum}</b></td>";
			echo "<td><a class='button' onclick='if(confirm(\"{$delmessage}\", \"?OP_ID={$row["OP_ID"]}&payment_sum={$row["payment_sum"]}&cost_name=Отправка из {$row["City"]} ({$row["cost_name"]})\")) return false;' title='Принять'><i class='fa fa-download fa-lg'></i></a></td>";
			echo "</tr>";
		}
	?>
		</tbody>
	</table>
	</div>

	<div style="text-align: center; margin: 10px;">
		<p><b>Период (включительно):</b></p>
		<form method="post">
			<input type="text" name="cash_from" class="date from" value="<?=$cash_from?>" title="Начальная дата">
			&nbsp;&nbsp;-&nbsp;&nbsp;
			<input type="text" name="cash_to" class="date to" value="<?=$cash_to?>" title="Конечная дата">
		</form>
	</div>

	<div style="position: relative;">
		<div style="width: 49%; position: absolute; left: 0;">
		<table style="width: 100%;">
			<thead>
				<tr>
					<th>ПРИХОД наличных:</th>
					<th colspan="2" class='txtright' id=cash_in></th>
					<th><a href="#" class="add_cost_btn" cost_date="<?=$now_date?>" sign="+" title="Внести приход"><i class="fa fa-plus-square fa-2x" style="color: green;"></i></a></th>
				</tr>
			</thead>
			<tbody>
			<?
				$query = "SELECT OP.OP_ID
								,DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
								,OP.payment_sum
								,IFNULL(OP.cost_name, CONCAT('Доплата <b>', OD.Code, '</b> (', CT.City, ')')) cost_name
								,IF(OP.send OR OP.PL_ID OR OP.OD_ID, 0, 1) is_edit
							FROM OrdersPayment OP
							LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
							LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
							LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
							WHERE OP.CT_ID IS NULL AND OP.payment_sum > 0 AND OP.payment_date >= STR_TO_DATE('{$cash_from}', '%d.%m.%Y') AND OP.payment_date <= STR_TO_DATE('{$cash_to}', '%d.%m.%Y')
							ORDER BY OP.payment_date DESC, OP.OP_ID DESC";

				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$cash_in = 0;
				while( $row = mysqli_fetch_array($res) ) {
					$cash_in = $cash_in + $row["payment_sum"];
					$payment_sum = number_format($row["payment_sum"], 0, '', ' ');
					echo "<tr>";
					echo "<td>{$row["cost_name"]}</td>";
					echo "<td>{$row["payment_date"]}</td>";
					echo "<td class='txtright'><b>{$payment_sum}</b></td>";
					if( $row["is_edit"] ) {
						echo "<td><a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' cost_date='{$row["payment_date"]}' sign='+' title='Изменить приход'><i class='fa fa-pencil fa-lg'></i></a></td>";
					}
					else {
						echo "<td></td>";
					}
					echo "</tr>";
				}
				$cash_in = number_format($cash_in, 0, '', ' ');
			?>
			</tbody>
		</table>
		</div>

		<div style="width: 49%; position: absolute; right: 0;">
		<table style="width: 100%;">
			<thead>
				<tr>
					<th>РАСХОД наличных:</th>
					<th colspan="2" class='txtright' id='cash_out'></th>
					<th><a href="#" class="add_cost_btn" cost_date="<?=$now_date?>" sign="-" title="Внести расход"><i class="fa fa-minus-square fa-2x" style="color: red;"></i></a></th>
				</tr>
			</thead>
			<tbody>
			<?
				$query = "SELECT OP.OP_ID
								,DATE_FORMAT(OP.payment_date, '%d.%m.%Y') payment_date
								,ABS(OP.payment_sum) payment_sum
								,OP.cost_name
								,IF(OP.send OR OP.PL_ID OR OP.OD_ID, 0, 1) is_edit
							FROM OrdersPayment OP
							LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
							WHERE OP.CT_ID IS NULL AND OP.payment_sum < 0 AND OP.payment_date >= STR_TO_DATE('{$cash_from}', '%d.%m.%Y') AND OP.payment_date <= STR_TO_DATE('{$cash_to}', '%d.%m.%Y')
							ORDER BY OP.payment_date DESC, OP.OP_ID DESC";

				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$cash_out = 0;
				while( $row = mysqli_fetch_array($res) ) {
					$cash_out = $cash_out + $row["payment_sum"];
					$payment_sum = number_format($row["payment_sum"], 0, '', ' ');
					echo "<tr>";
					echo "<td>{$row["cost_name"]}</td>";
					echo "<td>{$row["payment_date"]}</td>";
					echo "<td class='txtright'><b>{$payment_sum}</b></td>";
					if( $row["is_edit"] ) {
						echo "<td><a href='#' class='add_cost_btn' id='{$row["OP_ID"]}' cost_name='{$row["cost_name"]}' cost='{$row["payment_sum"]}' cost_date='{$row["payment_date"]}' sign='-' title='Изменить расход'><i class='fa fa-pencil fa-lg'></i></a></td>";
					}
					else {
						echo "<td></td>";
					}
					echo "</tr>";
				}
				$cash_out = number_format($cash_out, 0, '', ' ');
			?>
			</tbody>
		</table>
		</div>
	</div>
</div>

<!-- Форма добавления/редактирования расхода/прихода -->
<div id='add_cost' style='display:none'>
	<form method='post' action='<?=$location?>?add_cost=1'>
		<fieldset>
			<input type="hidden" name="OP_ID" id="OP_ID">
			<input type="hidden" name="sign" id="sign">
			<div style="width: 230px; display: inline-block; margin-right: 15px; vertical-align: top;">
				<label for="cost_name">Наименование:</label><br>
				<input type="text" name="cost_name" id="cost_name" style="width: 100%;">
			</div>
			<div style="width: 90px; display: inline-block; margin-right: 15px; vertical-align: top;">
				<label for="cost_name">Дата:</label><br>
				<input readonly type="text" name="cost_date" class="date" id="cost_date" style="width: 100%;">
			</div>
			<div style="width: 100px; display: inline-block; vertical-align: top;">
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

<script>
	$(document).ready(function() {
		// Отображаем суммы прихода/расхода за период
		$('#cash_in').html('<?=$cash_in?>');
		$('#cash_out').html('<?=$cash_out?>');

		// Сабмитаем форму выбора периода при изменении даты
		$( "input.date.from, input.date.to" ).datepicker( 'option', 'onClose', function(date) { $(this).parent('form').submit(); } );

		// Кнопка добавления/редактирования расхода
		$('.add_cost_btn').click( function() {
			var sign = $(this).attr('sign');
			var cost_date = $(this).attr('cost_date');

			// Очистка диалога
			$('#add_cost #OP_ID').val('');
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

			$('#add_cost').dialog({
				width: 550,
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
	});
</script>
