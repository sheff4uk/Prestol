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
		$category = ( $_POST["category"] and ( $type == 1 or $type == 2) ) ? $_POST["category"] : "NULL";
		$to_account = ( $_POST["to_account"] and $type == 0 ) ? $_POST["to_account"] : "NULL";
		$KA_ID = ( $_POST["kontragent"] and $type == 2 ) ? $_POST["kontragent"] : "NULL";
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
						,OP.cost_name comment
						,OP.OP_ID
					FROM OrdersPayment OP
					LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
					LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
					LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
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
	//Узнает дефолтный счет для пользователя
	$query = "SELECT FA_ID FROM FinanceAccount WHERE USR_ID = {$_SESSION['id']} LIMIT 1";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$account = mysqli_result($res,0,'FA_ID');

	echo "<a href='#' class='add_operation_btn' type='1' cost_date='{$now_date}' account='{$account}' title='Добавить операцию'><i class='fa fa-pencil fa-lg'></i></a>";
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
//			echo "<td><a class='button' onclick='if(confirm(\"{$delmessage}\", \"?OP_ID={$row["OP_ID"]}&payment_sum={$row["payment_sum"]}&cost_name=Отправка из {$row["City"]} ({$row["cost_name"]})\")) return false;' title='Принять'><i class='fa fa-download fa-lg'></i></a></td>";
			echo "<td><a class='button add_send_btn' OP_ID='{$row["OP_ID"]}' title='Принять'><i class='fa fa-download fa-lg'></i></a></td>";
			echo "</tr>";
		}
	?>
		</tbody>
	</table>
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
							FROM (
								SELECT F.F_ID
									,F.date date_sort
									,DATE_FORMAT(F.date, '%d.%m.%Y') date
									,IFNULL(FC.type, 0) type
									,IF(FC.type = 2, 1, -1) * F.money money
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
				$cash_in = 0;
				while( $row = mysqli_fetch_array($res) ) {
					$cash_in = $cash_in + $row["money"];
					$color = $row["money"] < 0 ? '#E74C3C' : '#16A085';
					$money = number_format($row["money"], 0, '', ' ');
					$type = ($row["type"] == 2 ? '<i class="fa fa-plus" style="color: #16A085;"></i>' : ($row["type"] == 1 ? '<i class="fa fa-minus" style="color: #E74C3C;"></i>' : '<i class="fa fa-exchange"></i>'));

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
				$cash_in = number_format($cash_in, 0, '', ' ');
			?>
			</tbody>
		</table>
	</div>
</div>

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
					<input type='radio' id='type1' name='type' value='1'>
						<label for='type1'><i class="fa fa-minus fa-lg" title="Расход"></i></label>
					<input required type='radio' id='type2' name='type' value='2'>
						<label for='type2'><i class="fa fa-plus fa-lg" title="Доход"></i></label>
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
			<div class="field">
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
			if( type == 2 ) {
				$('#wr_kontragent').show();
			}
			else {
				$('#wr_kontragent').hide();
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
