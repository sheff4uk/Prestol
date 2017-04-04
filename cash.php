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

	if( isset($_SESSION["cash_from"]) ) {
		$cash_from = $_SESSION["cash_from"];
		$cash_to = $_SESSION["cash_to"];
	}
	else {
		$cash_from = date('d.m.Y', mktime(0, 0, 0, date("m"), 1, date("Y")));
//		$cash_from = date('d.m.Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
		$cash_to = date('d.m.Y');
	}
///////////////////////////////////////////////////////////////////////
	// Изменение периода отображения
	if( isset($_POST["cash_from"]) ) {
		$_SESSION["cash_from"] = $_POST["cash_from"];
		$_SESSION["cash_to"] = $_POST["cash_to"];
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}
///////////////////////////////////////////////////////////////////////
	// Добавление/редактирование операции
	if( isset($_GET["add_operation"]) )
	{
		$F_ID = $_POST["F_ID"];
		$sum = $_POST["sum"];
		$cost_date = date( 'Y-m-d', strtotime($_POST["cost_date"]) );
		$account = $_POST["account"];
		$type = $_POST["type"];
		$category = ( $_POST["category"] and ( $type == -1 or $type == 1) ) ? $_POST["category"] : "NULL";
		$to_account = ( $_POST["to_account"] and $type == 0 ) ? $_POST["to_account"] : "NULL";
		$KA_ID = ( $_POST["kontragent"] and $type == 1 ) ? $_POST["kontragent"] : "NULL";
		$coment = mysqli_real_escape_string( $mysqli, $_POST["comment"] );

		if( $F_ID != '' ) { // Редактируем операцию
			$query = "UPDATE Finance
						SET  money = {$sum}
							,date = '{$cost_date}'
							,FA_ID = {$account}
							,to_account = {$to_account}
							,FC_ID = {$category}
							,KA_ID = {$KA_ID}
							,comment = '{$coment}'
						WHERE F_ID = {$F_ID}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"] = addslashes(htmlspecialchars(mysqli_error( $mysqli )));
			}
		}
		else { // Добавляем операцию
			if( $sum > 0 ) {
				$CT_ID = $_POST["CT_ID"];
				$query = "INSERT INTO Finance
							SET  money = {$sum}
								,date = '{$cost_date}'
								,FA_ID = {$account}
								,to_account = {$to_account}
								,FC_ID = {$category}
								,KA_ID = {$KA_ID}
								,comment = '{$coment}'";
				if( !mysqli_query( $mysqli, $query ) ) {
					$_SESSION["alert"] = addslashes(htmlspecialchars(mysqli_error( $mysqli )));
				}
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}
////////////////////////////////////////////////////////////////////////////////
	// Принятие выручки
	if( isset($_GET["add_send"]) )
	{
		$OP_ID = $_POST["OP_ID"];
		$FA_ID = $_POST["account"];

		$query = "UPDATE OrdersPayment
				  SET send = 2
				  WHERE OP_ID = {$OP_ID}";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["alert"] = addslashes(htmlspecialchars(mysqli_error( $mysqli )));
		}
		else {
			$query = "INSERT INTO Finance (money, date, FA_ID, FC_ID, comment, OP_ID)

					SELECT ABS(OP.payment_sum) money
						#,OP.payment_date date
						,NOW() date
						,{$FA_ID} FA_ID
						,3 FC_ID
						,CONCAT(CT.City, ' (', OP.cost_name, ')') comment
						,OP.OP_ID
					FROM OrdersPayment OP
					LEFT JOIN Cities CT ON CT.CT_ID = OP.CT_ID
					WHERE OP.OP_ID = {$OP_ID}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["alert"] = addslashes(htmlspecialchars(mysqli_error( $mysqli )));
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}
///////////////////////////////////////////////////////////////////////////////////
?>

<style>
	#wr_send {
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		width: 680px;
		white-space: nowrap;
		display: inline-block;
	}
	#wr_account {
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		width: 300px;
		display: inline-block;
		margin-right: 20px;
	}
	#add_operation_btn {
		background: url(../img/bt_speed_dial_1x.png) no-repeat scroll center center transparent;
		bottom: 100px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 50px;
		z-index: 9;
		border-radius: 50%;
		background-color: #16A085;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}
	#add_operation_btn:hover {
		opacity: 1;
	}
	.account_label {
		position: relative;
	}
	.account_label a {
		position: absolute;
		left: 100px;
		top: 5px;
		opacity: 0;
	}
	.account_label:hover a {
		opacity: 1;
	}
</style>

<?
	// Узнаем общий остаток наличных
	$query = "SELECT SUM(end_balance) ostatok FROM `FinanceAccount`";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$ostatok = mysqli_result($res,0,'ostatok');
	$format_ostatok = number_format($ostatok, 0, '', ' ');

	$now_date = date('d.m.Y');
	//Узнает дефолтный счет для пользователя
	$query = "SELECT FA_ID FROM FinanceAccount WHERE USR_ID = {$_SESSION['id']} LIMIT 1";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$account = mysqli_result($res,0,'FA_ID');

	echo "<a id='add_operation_btn' href='#' class='add_operation_btn' type='-1' cost_date='{$now_date}' account='{$account}' title='Добавить в учёт'></a>";
?>

<div style="width: 1000px; margin: auto;">
	<div style="display: flex;">
		<div id="wr_account">
			<table class="main_table">
				<tbody>
					<?
						$total = 0;
						$query = "SELECT FA_ID, name, end_balance FROM FinanceAccount ORDER BY IFNULL(bank, 0), FA_ID";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							$total = $total + $row["end_balance"];
							$color = $row["end_balance"] < 0 ? '#E74C3C' : '#16A085';
							$money = number_format($row["end_balance"], 0, '', ' ');

							echo "<tr>";
							echo "<td class='account_label'>{$row["name"]}<a href='#'><i class='fa fa-pencil fa-lg'></i></a></td>";
							echo "<td width='100' class='txtright' style='color: {$color};'><b>{$money}</b></td>";
							echo "</tr>";
						}
						$color = $total < 0 ? '#E74C3C' : '#16A085';
						$money = number_format($total, 0, '', ' ');

						echo "<tr>";
						echo "<td><h3>Итого:</h3></td>";
						echo "<td width='100' class='txtright' style='color: {$color};'><h3>{$money}</h3></td>";
						echo "</tr>";
					?>
				</tbody>
			</table>
		</div>

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
				echo "<td><a class='button add_send_btn' OP_ID='{$row["OP_ID"]}' title='Принять'><i class='fa fa-download fa-lg'></i></a></td>";
				echo "</tr>";
			}
		?>
			</tbody>
		</table>
		</div>
	</div>

	<div style="text-align: center; margin: 10px;">
		<p><b>Период (включительно):</b></p>
		<form method="post" style="font-weight: bold;">
			[
			<input type="text" name="cash_from" class="date from" value="<?=$cash_from?>">
			&nbsp;&ndash;&nbsp;
			<input readonly type="text" name="cash_to" class="date to" value="<?=$cash_to?>">
			 ]
		</form>
		<p>Изменение: <b id="cash_change"></b></p>
	</div>

	<div style="display: flex;">
		<table style="width: 100%;" class="main_table">
			<thead>
				<tr>
					<th width="50">Дата</th>
					<th width="50">Тип</th>
					<th width="100">Сумма</th>
					<th width="125">Счет</th>
					<th width="125">Категория</th>
					<th width="200">Контрагент</th>
					<th width="300">Комментарии</th>
					<th width="50"></th>
				</tr>
			</thead>
			<tbody>
			<?
				$query = "SELECT SF.F_ID
								,SF.date_sort
								,SF.date
								,SF.type
								,SF.money
								,SF.account
								,SF.category
								,SF.kontragent
								,SF.comment
								,SF.sum
								,SF.FA_ID
								,SF.to_account
								,SF.FC_ID
								,SF.KA_ID
								,SF.is_edit
								,SF.account_filter
								,SF.receipt
							FROM (
								SELECT F.F_ID
									,F.date date_sort
									,DATE_FORMAT(F.date, '%d.%m.%Y') date
									,IFNULL(FC.type, 0) type
									,IFNULL(FC.type, -1) * F.money money
									,FA.name account
									,IF(F.to_account IS NULL, FC.name, CONCAT(FA.name, ' => ', TFA.name)) category
									,KA.Naimenovanie kontragent
									,F.comment
									,F.money sum
									,F.FA_ID
									,F.to_account
									,F.FC_ID
									,F.KA_ID
									,IF(F.PL_ID IS NULL AND F.OP_ID IS NULL, 1, 0) is_edit
									,F.FA_ID account_filter
									,0 receipt
								FROM Finance F
								LEFT JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
								LEFT JOIN FinanceAccount FA ON FA.FA_ID = F.FA_ID
								LEFT JOIN FinanceAccount TFA ON TFA.FA_ID = F.to_account
								LEFT JOIN Kontragenty KA ON KA.KA_ID = F.KA_ID
								WHERE F.money > 0 AND F.date >= STR_TO_DATE('{$cash_from}', '%d.%m.%Y') AND F.date <= STR_TO_DATE('{$cash_to}', '%d.%m.%Y')

								UNION ALL

								SELECT F.F_ID
									,F.date date_sort
									,DATE_FORMAT(F.date, '%d.%m.%Y') date
									,0 type
									,F.money
									,TFA.name account
									,CONCAT(FA.name, ' => ', TFA.name) category
									,NULL kontragent
									,F.comment
									,F.money sum
									,F.FA_ID
									,F.to_account
									,F.FC_ID
									,F.KA_ID
									,IF(F.PL_ID IS NULL AND F.OP_ID IS NULL, 1, 0) is_edit
									,F.to_account account_filter
									,1 receipt
								FROM Finance F
								LEFT JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
								LEFT JOIN FinanceAccount FA ON FA.FA_ID = F.FA_ID
								LEFT JOIN FinanceAccount TFA ON TFA.FA_ID = F.to_account
								WHERE F.money > 0 AND F.date >= STR_TO_DATE('{$cash_from}', '%d.%m.%Y') AND F.date <= STR_TO_DATE('{$cash_to}', '%d.%m.%Y') AND F.to_account IS NOT NULL
							) SF
							WHERE 1
							#AND SF.type = 1
							#AND SF.sum >= 200
							#AND SF.sum <= 300
							#AND SF.account_filter IN (1,2)
							#AND SF.FC_ID IN (1,4)
							#AND SF.comment LIKE '%возврат%'
							#AND SF.kontragent LIKE '%авто%'
							ORDER BY SF.date_sort DESC, SF.F_ID DESC";

				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$cash_in = 0; // Сумма видимых операций
				while( $row = mysqli_fetch_array($res) ) {
					$cash_in = $cash_in + $row["money"];
					$color = $row["money"] < 0 ? '#E74C3C' : '#16A085';
					$money = number_format($row["money"], 0, '', ' ');
					$type = ($row["type"] == 1 ? '<i class="fa fa-plus" style="color: #16A085;"></i>' : ($row["type"] == -1 ? '<i class="fa fa-minus" style="color: #E74C3C;"></i>' : '<i class="fa fa-exchange"></i>'));

					if( $row["receipt"] == 0 or 1 ) {
						echo "<tr>";
						echo "<td>{$row["date"]}</td>";
						echo "<td style='text-align: center;'>{$type}</td>";
						echo "<td class='txtright' style='color: {$color};'><b>{$money}</b></td>";
						echo "<td><span>{$row["account"]}</span></td>";
						echo "<td><span>{$row["category"]}</span></td>";
						echo "<td><span class='nowrap'>{$row["kontragent"]}</span></td>";
						echo "<td class='comment'><span class='nowrap'>{$row["comment"]}</span></td>";
						if( $row["is_edit"] ) {
							echo "<td><a href='#' class='add_operation_btn' id='{$row["F_ID"]}' sum='{$row["sum"]}' type='{$row["type"]}' cost_date='{$row["date"]}' account='{$row["FA_ID"]}' category='{$row["FC_ID"]}' to_account='{$row["to_account"]}' kontragent='{$row["KA_ID"]}' title='Изменить операцию'><i class='fa fa-pencil fa-lg'></i></a></td>";
						}
						else {
							echo "<td></td>";
						}
						echo "</tr>";
					}
				}
				$cash_in = number_format($cash_in, 0, '', ' ');
				$color = $cash_in < 0 ? '#E74C3C' : '#16A085';
				$cash_change = "<span style='color: {$color};'>{$cash_in}</span>";
				$cash_change = addslashes( $cash_change );
			?>
			</tbody>
		</table>
	</div>
</div>

<script>
	$(document).ready(function() {
		$('#cash_change').html('<?=$cash_change?>');
	});
</script>

<style>
	#add_operation .field, #add_send .field{
		display: inline-block;
		margin-right: 20px;
		margin-bottom: 20px;
	}
</style>

<!-- Форма добавления/редактирования операции -->
<div id='add_operation' style='display:none' title="ДОБАВИТЬ ОПЕРАЦИЮ">
	<form method='post' action='<?=$location?>?add_operation=1'>
		<fieldset>
			<input type="hidden" name="F_ID" id="F_ID">
			<div class="field">
				<label for="sum">Сумма:</label><br>
				<input required type="number" name="sum" min="0" id="sum" style="width: 100px; text-align: right;">
			</div>
			<div class="field">
				<label for="cost_date">Дата:</label><br>
				<input required readonly type="text" name="cost_date" class="date" id="cost_date" style="width: 90px;">
			</div>
			<br>
			<div class="field">
				<label for="account">Счет:</label><br>
				<select required name="account" id="account" style="width: 140px;">
					<option value="">-=Выберите счёт=-</option>
					<optgroup label="Нал">
						<?
						$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 0";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
						}
						?>
					</optgroup>
					<optgroup label="Безнал">
						<?
						$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 1";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
						}
						?>
					</optgroup>
				</select>
			</div>
			<div class="field">
				<label for="type">Тип операции:</label>
				<div class='btnset' id='type'>
					<input type='radio' id='type-1' name='type' value='-1'>
						<label for='type-1'><i class="fa fa-minus fa-lg" title="Расход"></i></label>
					<input required type='radio' id='type1' name='type' value='1'>
						<label for='type1'><i class="fa fa-plus fa-lg" title="Доход"></i></label>
					<input type='radio' id='type0' name='type' value='0'>
						<label for='type0'><i class="fa fa-exchange fa-lg" title="Перевод со счета"></i></label>
				</div>
			</div>
			<br>
			<div id="wr_category" class="field"></div> <!-- Заполняется аяксом -->
			<br>
			<div class="field" id="wr_kontragent">
				<label for="kontragent">Контрагент:</label><br>
				<select name="kontragent" id="kontragent" style="width: 300px;">
					<option value=""></option>
					<?
					$query = "SELECT KA_ID, Naimenovanie FROM Kontragenty ORDER BY count DESC";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]}</option>";
					}
					?>
				</select>
			</div>
			<div>
				<label for="comment">Комментарии:</label><br>
				<textarea name="comment" id="comment" rows="3" style="width: 300px;"></textarea>
			</div>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы добавления/редактирования расхода/прихода -->

<!-- Форма принятия выручки -->
<div id='add_send' style='display:none' title="ПРИНЯТЬ ВЫРУЧКУ">
	<form method='post' action='<?=$location?>?add_send=1'>
		<fieldset>
			<input type="hidden" id="OP_ID" name="OP_ID">
			<div style="text-align: center;">
				<label for="account">Счет:</label><br>
					<div class='btnset'>
					<?
					$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 0";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<input required type='radio' name='account' id='acc_{$row["FA_ID"]}' value='{$row["FA_ID"]}'>";
						echo "<label for='acc_{$row["FA_ID"]}'>{$row["name"]}</label>";
					}
					?>
				</div>
			</div>
			<p style="color: red; text-align: center;"><b>Внимание!</b> Данную операцию отменить невозможно.</p>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Принять</button>
		</div>
	</form>
</div>
<!-- Конец форма принятия выручки -->

<script>
	$(document).ready(function() {
		$('#add_operation form').submit(function() {
			if( $('#account').val() === $('#to_account').val() ) {
				noty({timeout: 3000, text: 'Счёт-отправитель и счёт-получатель должны различаться!', type: 'error'});
				return false;
			}
		});

		$('#kontragent').select2({ placeholder: 'Выберите контрагента', language: 'ru' });

		// Костыль для Select2 чтобы работал поиск
		$.ui.dialog.prototype._allowInteraction = function (e) {
			return true;
		};

		// Отображаем суммы прихода/расхода за период
		$('#cash_in').html('<?=$cash_in?>');
		$('#cash_out').html('<?=$cash_out?>');

		// Сабмитаем форму выбора периода при изменении даты
		$( "input.date.from, input.date.to" ).datepicker( 'option', 'onClose', function(date) { $(this).parent('form').submit(); } );

		// Ограничиваем период вибора дат для фильтрации
//		$( "input.date.from, input.date.to" ).datepicker( "option", "minDate", "<?=( date('d.m.Y', mktime(0, 0, 0, date("m")-1, 1, date("Y"))) )?>" );
		$( "input.date.from, input.date.to" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );

		// Кнопка добавления/редактирования расхода
		$('.add_operation_btn').click( function() {
			var type = $(this).attr('type');
			var cost_date = $(this).attr('cost_date');
			var account = $(this).attr('account');

			// Очистка диалога
			$('#add_operation #F_ID').val('');
			$('#add_operation #sum').val('');
			$('#add_operation #cost_date').val(cost_date);
			$('#add_operation #account').val(account);
			$('#type'+type).prop('checked', true);
			$('#type > #type'+type).change();
			$('#add_operation #category').val('');
			$('#add_operation #to_account').val('');
			$('#add_operation #kontragent').val('');
			$('#add_operation #comment').val('');

			var F_ID = $(this).attr('id');

			if( F_ID > 0 ) {
				var sum = $(this).attr('sum');
				var category = $(this).attr('category');
				var to_account = $(this).attr('to_account');
				var kontragent = $(this).attr('kontragent');
				var comment = $(this).parents('tr').find('.comment > span').html();
				$('#add_operation #F_ID').val(F_ID);
				$('#add_operation #sum').val(sum);
				$('#add_operation #category').val(category).trigger('change');
				$('#add_operation #to_account').val(to_account).trigger('change');
				$('#add_operation #kontragent').val(kontragent).trigger('change');
				$('#add_operation #comment').val(comment);
			}

			$('#add_operation').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			return false;
		});

		// Кнопка принятия выручки
		$('.add_send_btn').click( function() {
			$('#add_send #OP_ID').val($(this).attr('OP_ID'));
			$('#add_send input[type="radio"]').prop('checked', false);
			$('#add_send .btnset').buttonset("refresh");

			$('#add_send').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			return false;
		});

		// При смене типа операции меняется категория
		$('#type > input').change(function() {
			type = $(this).val();
			$.ajax({ url: "ajax.php?do=cash_category&type="+type, dataType: "script", async: false });
			if( type == 1 ) {
				$('#wr_kontragent').show('fast');
			}
			else {
				$('#wr_kontragent').hide('fast');
			}
			return false;
		});

		//$( "#cost_date" ).datepicker( "option", "minDate", "<?=( date('d.m.Y', mktime(0, 0, 0, date("m")-1, 1, date("Y"))) )?>" );
		$( "#cost_date" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );
	});
</script>

<?
	include "footer.php";
?>
