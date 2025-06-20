<?php
	include "config.php";
	$title = 'Касса';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('finance_all', $Rights) and !in_array('finance_account', $Rights) ) {
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
		$account = $_POST["account"];
		$type = $_POST["type"];
		$category = ( $_POST["category"] and ( $type == -1 or $type == 1) ) ? $_POST["category"] : "NULL";
		$to_account = ( $_POST["to_account"] and $type == 0 ) ? $_POST["to_account"] : "NULL";
		$KA_ID = ( $_POST["kontragent"] and $category == 9 ) ? $_POST["kontragent"] : "NULL";
		$comment = convert_str($_POST["comment"]);
		$comment = mysqli_real_escape_string( $mysqli, $comment );

		if( $F_ID != 'add_operation_btn' ) { // Редактируем операцию
			$query = "
				UPDATE Finance
				SET  money = {$sum}
					,FA_ID = {$account}
					,to_account = {$to_account}
					,FC_ID = {$category}
					,KA_ID = {$KA_ID}
					,comment = ".($comment ? "'{$comment}'" : "NULL")."
					,author = {$_SESSION['id']}
				WHERE F_ID = {$F_ID}
			";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}
		}
		else { // Добавляем операцию
			if( $sum > 0 ) {
				$CT_ID = $_POST["CT_ID"];
				$query = "INSERT INTO Finance
							SET  money = {$sum}
								,FA_ID = {$account}
								,to_account = {$to_account}
								,FC_ID = {$category}
								,KA_ID = {$KA_ID}
								,comment = ".($comment ? "'{$comment}'" : "NULL")."
								,author = {$_SESSION['id']}";
				if( !mysqli_query( $mysqli, $query ) ) {
					$_SESSION["error"][] = mysqli_error( $mysqli );
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

		$query = "INSERT INTO Finance (money, date, FA_ID, FC_ID, comment, OP_ID, author)
				SELECT ABS(OP.payment_sum) money
					,NOW() date
					,{$FA_ID} FA_ID
					,3 FC_ID
					,CONCAT(CB.name, ' (', OP.cost_name, ')') comment
					,OP.OP_ID
					,{$_SESSION['id']} author
				FROM OrdersPayment OP
				JOIN CashBox CB ON CB.CB_ID = OP.CB_ID
				WHERE OP.OP_ID = {$OP_ID}";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = mysqli_error( $mysqli );
		}
		else {
			$query = "UPDATE OrdersPayment
					  SET send = 2
					  WHERE OP_ID = {$OP_ID}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}
///////////////////////////////////////////////////////////////////////////////////
// Добавление/редактирование счета
	if( isset($_GET["add_account"]) )
	{
		$FA_ID = $_POST["FA_ID"];
		$bank = ($_POST["bank"] == '1') ? '1' : 'NULL';
		$name = mysqli_real_escape_string( $mysqli, $_POST["name"] );
		$color = $_POST["color"];
		$start_balance = $_POST["start_balance"] ? $_POST["start_balance"] : '0';
		$USR_ID = $_POST["USR_ID"] ? $_POST["USR_ID"] : 'NULL';

		if( $FA_ID != '' ) { //Редактируем счет
			$query = "UPDATE FinanceAccount
						SET  bank = {$bank}
							,name = '{$name}'
							,start_balance = {$start_balance}
							,USR_ID = {$USR_ID}
							,color = '{$color}'
						WHERE FA_ID = {$FA_ID}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}
		}
		else { // Создаем счет
			$query = "INSERT INTO FinanceAccount
						SET  bank = {$bank}
							,name = '{$name}'
							,start_balance = {$start_balance}
							,USR_ID = {$USR_ID}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}
		}
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

///////////////////////////////////////////////////////////////////////////////////
// Добавление/редактирование категории
	if( isset($_GET["add_category"]) )
	{
		$name = convert_str($_POST["name_add"]);
		$name = mysqli_real_escape_string($mysqli, $name);
		$type = $_POST["type_add"];

		// Создаем категорию
		if( $name != '' and $type != '' ) {
			$query = "INSERT INTO FinanceCategory
						SET  name = '{$name}'
							,type = {$type}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}
		}
		else {
			if( $name != '' or $type != '' ) $_SESSION["alert"][] = 'Для добавления новой категории заполнены не все поля!';
		}

		foreach ($_POST["FC_ID"] as $key => $value) {
			$name = convert_str($_POST["name"][$key]);
			$name = mysqli_real_escape_string($mysqli, $name);
			$type = $_POST["type_edit"][$key];

			$query = "UPDATE FinanceCategory
						SET  name = '{$name}'
							,type = {$type}
						WHERE FC_ID = {$value}";
			if( !mysqli_query( $mysqli, $query ) ) {
				$_SESSION["error"][] = mysqli_error( $mysqli );
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

///////////////////////////////////////////////////////////////////////////////////
	// Сброс фильтра
	if( isset($_GET["reset_filter"]) ) {
		$_SESSION["cash_type"] = "";
		$_SESSION["cash_sum_from"] = "";
		$_SESSION["cash_sum_to"] = "";
		$_SESSION["cash_account"] = "";
		$_SESSION["cash_category"] = "";
		$_SESSION["cash_author"] = "";
		$_SESSION["cash_comment"] = "";

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#operations">');
		die;
	}

///////////////////////////////////////////////////////////////////////////////////
?>

<style>
	#wr_send {
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		width: 100%;
		white-space: nowrap;
		display: inline-block;
		overflow: auto;
	}
	#wr_account {
		position: fixed;
		background-color: white;
		left: -280px;
		border: 1px solid #bbb;
		padding: 10px;
		border-radius: 10px;
		width: 300px;
		opacity: .8;
		transition: .3s;
		z-index: 10;
	}
	#wr_account:hover {
		left: 0px;
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

<?php
	//Узнаем дефолтный счет для пользователя
	$query = "SELECT FA_ID FROM FinanceAccount WHERE USR_ID = {$_SESSION['id']} ORDER BY IFNULL(bank, 0) LIMIT 1";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$account = mysqli_result($res,0,'FA_ID');

	// Вычисляем частый тип операции для пользователя
	$query = "
		SELECT IFNULL(FFC.type, 0) type, SUM(1) amount
		FROM (
			SELECT IFNULL(FC.type, 0) type
			FROM Finance F
			LEFT JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
			WHERE F.author = {$_SESSION['id']}
				AND F.USR_ID IS NULL
				AND F.OP_ID IS NULL
				AND F.money != 0
			ORDER BY F.F_ID DESC
			LIMIT 25
		) FFC
		GROUP BY IFNULL(FFC.type, 0)
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$type = -1;
	$amount = 0;
	while( $row = mysqli_fetch_array($res) )
	{
		if( $row['amount'] > $amount ) {
			$type = $row['type'];
			$amount = $row['amount'];
		}
	}

// Вычисляем частый счёт и категорию для каждого типа
	// Расход
	$query = "
		SELECT FFC.FA_ID, FFC.FC_ID, SUM(1) amount
		FROM (
			SELECT F.FA_ID, F.FC_ID
			FROM Finance F
			LEFT JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
			WHERE F.author = {$_SESSION['id']}
				AND F.USR_ID IS NULL
				AND F.OP_ID IS NULL
				AND IFNULL(FC.type, 0) = -1
				AND F.money != 0
			ORDER BY F.F_ID DESC
			LIMIT 15
		) FFC
		GROUP BY FFC.FA_ID, FFC.FC_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$out_account = $account;
	$out_category = 0;
	$amount = 0;
	while( $row = mysqli_fetch_array($res) )
	{
		if( $row['amount'] > $amount ) {
			$out_account = $row['FA_ID'];
			$out_category = $row['FC_ID'];
			$amount = $row['amount'];
		}
	}

	// Приход
	$query = "
		SELECT FFC.FA_ID, FFC.FC_ID, SUM(1) amount
		FROM (
			SELECT F.FA_ID, F.FC_ID
			FROM Finance F
			LEFT JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
			WHERE F.author = {$_SESSION['id']}
				AND F.USR_ID IS NULL
				AND F.OP_ID IS NULL
				AND IFNULL(FC.type, 0) = 1
				AND F.money != 0
			ORDER BY F.F_ID DESC
			LIMIT 15
		) FFC
		GROUP BY FFC.FA_ID, FFC.FC_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$in_account = $account;
	$in_category = 0;
	$amount = 0;
	while( $row = mysqli_fetch_array($res) )
	{
		if( $row['amount'] > $amount ) {
			$in_account = $row['FA_ID'];
			$in_category = $row['FC_ID'];
			$amount = $row['amount'];
		}
	}

	// Перевод
	$query = "
		SELECT FFC.FA_ID, FFC.to_account, SUM(1) amount
		FROM (
			SELECT F.FA_ID, F.to_account
			FROM Finance F
			LEFT JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
			WHERE F.author = {$_SESSION['id']}
			AND F.USR_ID IS NULL
			AND F.OP_ID IS NULL
			AND IFNULL(FC.type, 0) = 0
			AND F.money != 0
			ORDER BY F.F_ID DESC
			LIMIT 15
		) FFC
		GROUP BY FFC.FA_ID, FFC.to_account
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$from_account = $account;
	$to_account = 0;
	$amount = 0;
	while( $row = mysqli_fetch_array($res) )
	{
		if( $row['amount'] > $amount ) {
			$from_account = $row['FA_ID'];
			$to_account = $row['to_account'];
			$amount = $row['amount'];
		}
	}

	echo "<a id='add_operation_btn' href='#' class='add_operation_btn' type='{$type}' account='{$account}' title='Добавить в учёт'></a>";
?>

<div style="width: 1000px; margin: auto;">
	<div id="wr_account">
		<?php
		if( !in_array('finance_account', $Rights) ) {
			echo "<a href='#' class='add_account_btn' style='margin: 10px; display: block; text-align: center;'><b><i class='fa fa-plus'></i> Добавить счет</b></a>";
		}
		?>
		<table class="main_table">
			<tbody>
				<?php
					$total = 0;
					$query = "SELECT FA_ID, name, start_balance, end_balance, USR_ID, bank, color
								FROM FinanceAccount
								WHERE archive = 0
								".(in_array('finance_account', $Rights) ? " AND USR_ID = {$_SESSION['id']}" : "")."
								ORDER BY IFNULL(bank, 0), FA_ID";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						$total = $total + $row["end_balance"];
						$color = $row["end_balance"] < 0 ? '#E74C3C' : '#16A085';
						$money = number_format($row["end_balance"], 0, '', ' ');

						echo "<tr>";
						echo "<td class='account_label'><span style='background-color: {$row["color"]}; border-radius: 20%;'>{$row["name"]}</span>";
						if( !in_array('finance_account', $Rights) ) {
							echo "<a href='#' class='add_account_btn' FA_ID='{$row["FA_ID"]}' bank='{$row["bank"]}' name='{$row["name"]}' color='{$row["color"]}' start_balance='{$row["start_balance"]}' USR_ID='{$row["USR_ID"]}' title='Редактировать'><i class='fa fa-pencil-alt fa-lg'></i></a>";
						}
						echo "</td>";
						echo "<td width='120' class='txtright' style='color: {$color};'><b>{$money}</b></td>";
						echo "</tr>";
					}
					if( !in_array('finance_account', $Rights) ) {
						$color = $total < 0 ? '#E74C3C' : '#16A085';
						$money = number_format($total, 0, '', ' ');

						echo "<tr>";
						echo "<td><h3>Капитал:</h3></td>";
						echo "<td width='120' class='txtright' style='color: {$color};'><h3>{$money}</h3></td>";
						echo "</tr>";
					}
				?>
			</tbody>
		</table>
		<?php
		if( !in_array('finance_account', $Rights) ) {
			echo "<a href='#' class='add_category_btn' style='margin: 10px; display: block; text-align: center;'><b><i class='fa fa-plus'></i> Добавить категорию</b></a>";
		}
		?>
	</div>

	<?php
		// Инкассация
		$query = "
			SELECT OP.OP_ID
					,DATE_FORMAT(OP.payment_date, '%d.%m.%y') payment_date
					,ABS(OP.payment_sum) payment_sum
					,OP.cost_name
					,CB.name
					,IF(CB.CB_ID IN (SELECT CB_ID FROM Shops WHERE CT_ID = {$USR_City} UNION SELECT CB_ID FROM Cities WHERE CT_ID = {$USR_City}), 1, 0) home
			FROM OrdersPayment OP
			JOIN CashBox CB ON CB.CB_ID = OP.CB_ID
				".( !in_array('finance_all', $Rights) ? "AND CB.CB_ID IN (SELECT CB_ID FROM Shops WHERE CT_ID = {$USR_City} UNION SELECT CB_ID FROM Cities WHERE CT_ID = {$USR_City})" : "" )."
			WHERE OP.send = 1
				AND OP.payment_sum < 0
				AND OP.cost_name IS NOT NULL
			ORDER BY OP.payment_date DESC
		";

		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		if( mysqli_num_rows($res) ) {
			echo "
				<div id='wr_send'>
					<table style='width: 100%;'>
						<thead>
							<tr>
								<th colspan='4'>Инкассация:</th>
							</tr>
						</thead>
						<tbody>
			";
			while( $row = mysqli_fetch_array($res) ) {
				$payment_sum = number_format($row["payment_sum"], 0, '', ' ');
				echo "
					<tr>
						<td>{$row["name"]} ({$row["cost_name"]})</td>
						<td>{$row["payment_date"]}</td>
						<td class='txtright'><b>{$payment_sum}</b></td>
						<td>".($row["home"] ? "<a class='button add_send_btn' OP_ID='{$row["OP_ID"]}' payment_sum='{$payment_sum}' cashbox='{$row["name"]}' title='Принять'><i class='fa fa-download fa-lg'></i></a>" : "")."</td>
					</tr>
				";
			}
			echo "
						</tbody>
					</table>
				</div>
			";
		}
	?>

	<div style="text-align: center; margin: 10px; position: relative;">
		<p><b>Период (включительно):</b></p>
		<form method="post" style="font-weight: bold;">
			[
			<input type="text" name="cash_from" class="date from" value="<?=$cash_from?>">
			&nbsp;&ndash;&nbsp;
			<input readonly type="text" name="cash_to" class="date to" value="<?=$cash_to?>">
			 ]
		</form>
		<?php
		if( in_array('finance_all', $Rights) ) {
//			echo "<p style='display: inline-block; margin: 10px;'>Изменение локальное: <b id='cash_change_local'></b></p>";
		}
		?>
		<p style="display: inline-block; margin: 10px;">Изменение: <b id="cash_change"></b></p>
		<a href="?reset_filter=1" style="display: none; position: absolute; right: -10px; bottom: 0;" id="reset_filter">Сбросить фильтры</a>
	</div>

	<style>
		.finance_head {
			text-align: left;
		}
		.finance_head .th_filter {
			cursor: pointer;
		}
		.finance_head th i {
			float: right;
			line-height: 1.1em;
			margin-right: 10px;
		}

		.filter_block {
			display: none;
			position: absolute;
			color: #333;
			background: #fff;
			overflow: auto;
			border: solid 1px #bbb;
			z-index: 3;
			cursor: default;
			padding: 5px;
			box-shadow: 5px 5px 8px #666;
		}

		.filter_block label {
			cursor: pointer;
			display: inline-block;
			width: 100%;
			text-align: left;
		}

		.th_filter .th_name {
			white-space: nowrap;
			max-width: calc(100% - 30px);
			display: inline-block;
			overflow: hidden;
		}

		.th_name strong {
			//color: #16A085;
			color: #fff;
		}
	</style>

	<script>
		$(document).ready(function() {
			$('.th_filter').click(function() {
				$('#filter_overlay').show();
				$(this).find('.filter_block').show('fast');
				$(this).find('input[type=text]').show();
				$(this).find('input[type=text]').focus();
			});

			$('#filter_overlay').click(function() {
				$('#filter_overlay').hide();
				$('.filter_block').hide('fast');
				$('.th_filter input[type=text]').hide();
				$('#filter_form').submit();
			});

			/////////////////////////////////////////////////////
			$(function() {
				$('.select_all').change(function(){
					ch = $(this).prop('checked');
					$(this).parents('.filter_block').find('.chbox').prop('checked', ch);
					$(this).parents('.filter_block').find('.btnset').buttonset("refresh");
					return false;
				});

				$('.filter_block .chbox').change(function(){
					var checked_status = true;
					var select_all = $(this).parents('.filter_block').find('.select_all');
					$(this).parents('.filter_block').find('.chbox').each(function(){
						if( !$(this).prop('checked') )
						{
							checked_status = $(this).prop('checked');
						}
					});
					$(select_all).prop('checked', checked_status);
					$(this).parents('.filter_block').find('.btnset').buttonset("refresh");
					return false;
				});

				// Поиск в категориях при вводе текста
				$('#category_search').on("input", function() {
					var search_text = $(this).val();
					if( search_text ) {
						$(this).parents('.th_filter').find('label').hide();
						$(this).parents('.th_filter').find('input[type=checkbox]').prop('checked', false);
					}
					else {
						$(this).parents('.th_filter').find('label').show();
						$(this).parents('.th_filter').find('input[type=checkbox]').prop('checked', true);
					}
					$(this).parents('.th_filter').find('.chbox_label').each(function() {
						if( $(this).html().toUpperCase().indexOf(search_text.toUpperCase()) + 1 ) {
							$(this).show();
							$('#'+$(this).attr('for')).prop('checked', true);
						}
					});
					$('.btnset').buttonset("refresh");
					return false;
				});

				// Сабмит фильтра при выборе типа операции
				$('#type_filter input').change(function(){
					$('#filter_overlay').click();
				});

				// Очистка диапазона сумм в фильтре
				$('#clear_sum').click(function(){
					$('#sum_filter input').val('');
					$('#filter_overlay').click();
				});

				// Если будет 1, значит задействован фильтр
				var filtered = 0;

				// Обновление названия колонки типа.
				var cash_type = "<?=$_SESSION["cash_type"]?>";
				if( cash_type != "" ) {
					switch( cash_type ) {
						case "-1":
							$('#type_label').html('<strong title="Расход"><i class="fa fa-minus fa-lg"></i></strong>');
						break;
						case "1":
							$('#type_label').html('<strong title="Доход"><i class="fa fa-plus fa-lg"></i></strong>');
						break;
						case "0":
							$('#type_label').html('<strong title="Перевод"><i class="fa fa-exchange-alt fa-lg"></i></strong>');
						break;
					}
				filtered = 1;
				}

				// Заполнение данных о диапазоне сумм из сессии
				if( "<?=$_SESSION["cash_sum_from"]?>" || "<?=$_SESSION["cash_sum_to"]?>" ) {
					var from = "0";
					var to = "&infin;";
					if( "<?=$_SESSION["cash_sum_from"]?>" ) {
						$('#sum_filter input[name=cash_sum_from]').val('<?=$_SESSION["cash_sum_from"]?>');
						from = "<?=$_SESSION["cash_sum_from"]?>";
					}
					if( "<?=$_SESSION["cash_sum_to"]?>" ) {
						$('#sum_filter input[name=cash_sum_to]').val('<?=$_SESSION["cash_sum_to"]?>');
						to = "<?=$_SESSION["cash_sum_to"]?>";
					}
					$('#sum_label').html('<strong title="' + from + ' - ' + to + '">[' + from + ' - ' + to + ']</strong>');
					filtered = 1;
				}

				// Включение чекбоксов в фильтре по счетам. Обновление названия колонки счета.
				if( "<?=$_SESSION["cash_account"]?>" == "" ) {
					$('#account_filter .select_all').prop("checked", true);
					$('#account_filter .select_all').change();
				}
				else {
					var text = "";
					$('#account_filter .chbox').each(function(){
						if( $(this).prop('checked') ) {
							text = text + $('label[for=' + $(this).attr("id") + '] span').html() + ", ";
						}
					});
					text = escapeHtml(text.substr(0, text.length - 2));
					$('#account_label').html('<strong title="' + text + '">[' + text + ']</strong>');
					filtered = 1;
				}

				// Включение чекбоксов в фильтре по категориям. Обновление названия колонки категории.
				if( "<?=$_SESSION["cash_category"]?>" == "" ) {
					$('#category_filter .select_all').prop("checked", true);
					$('#category_filter .select_all').change();
				}
				else {
					var text = "";
					$('#category_filter .chbox').each(function(){
						if( $(this).prop('checked') ) {
							text = text + $('label[for=' + $(this).attr("id") + '] span').html() + ", ";
						}
					});
					text = escapeHtml(text.substr(0, text.length - 2));
					$('#category_label').html('<strong title="' + text + '">[' + text + ']</strong>');
					filtered = 1;
				}

				// Включение чекбоксов в фильтре по авторам. Обновление названия колонки автора.
				if( "<?=$_SESSION["cash_author"]?>" == "" ) {
					$('#author_filter .select_all').prop("checked", true);
					$('#author_filter .select_all').change();
				}
				else {
					var text = "";
					$('#author_filter .chbox').each(function(){
						if( $(this).prop('checked') ) {
							text = text + $('label[for=' + $(this).attr("id") + '] span').html() + ", ";
						}
					});
					text = escapeHtml(text.substr(0, text.length - 2));
					$('#author_label').html('<strong title="' + text + '">[' + text + ']</strong>');
					filtered = 1;
				}

				//Обновление названия колонки комментариев.
				if( "<?=$_SESSION["cash_comment"]?>" ) {
					$('#comment_label').html('<strong title="<?=$_SESSION["cash_comment"]?>">[<?=$_SESSION["cash_comment"]?>]</strong>');
					filtered = 1;
				}

				// Если отфильтрован - показываем кнопку "Сбросить фильтры"
				if( filtered ) $('#reset_filter').show().button();
			});
			///////////////////////////////////////////////////////////////////
		});
	</script>

	<!-- Слой для выхода из режима фильтрации -->
	<div id="filter_overlay" style="z-index: 2; position: fixed; width: 100%; height: 100%; top: 0; left: 0; cursor: pointer; display: none;"></div>

	<!-- Форма фильтрации операций -->
	<form id="filter_form" method="get" action="filter.php"><input type="hidden" name="location" value="<?=$location?>#operations"><input type="hidden" name="do" value="cash"></form>

	<div style="display: flex;" id="operations">
		<table style="width: 100%;" class="main_table">
			<thead class="finance_head">
				<tr>
					<th width="60">Дата</th>
					<th width="60">Время</th>
					<th width="60" class="th_filter">
						<div class="th_name" id="type_label">Тип</div>
						<i class="fa fa-filter fa-lg"></i>
						<div id="type_filter" class="filter_block" style="width: 140px;">
							<div class="btnset">
								<input type="radio" id="ftype" name="cash_type" value="" form="filter_form" <?=( $_SESSION["cash_type"] == "" ? "checked" : "" )?>>
									<label for="ftype">Все типы</label>
								<input type="radio" id="ftype-1" name="cash_type" value="-1" form="filter_form" <?=( $_SESSION["cash_type"] == "-1" ? "checked" : "" )?>>
									<label for="ftype-1"><i class="fa fa-minus fa-lg"></i>Расход</label>
								<input required type="radio" id="ftype1" name="cash_type" value="1" form="filter_form" <?=( $_SESSION["cash_type"] == "1" ? "checked" : "" )?>>
									<label for="ftype1"><i class="fa fa-plus fa-lg"></i>Доход</label>
								<input type="radio" id="ftype0" name="cash_type" value="0" form="filter_form" <?=( $_SESSION["cash_type"] == "0" ? "checked" : "" )?>>
									<label for="ftype0"><i class="fa fa-exchange-alt fa-lg"></i>Перевод</label>
							</div>
						</div>
					</th>

					<th width="90" class="th_filter">
						<div class="th_name" id="sum_label">Сумма</div>
						<i class="fa fa-filter fa-lg"></i>
						<div id="sum_filter" class="filter_block" style="width: 200px;">
							<div style="text-align: center; margin-bottom: 5px;"><button id="clear_sum">Любая сумма</button></div>
							От: <input type="number" min="0" name="cash_sum_from" style="width: 60px; text-align: right;" form="filter_form" autocomplete="off">
							До: <input type="number" min="0" name="cash_sum_to" style="width: 60px; text-align: right;" form="filter_form" autocomplete="off">
						</div>
					</th>

					<th width="105" class="th_filter">
						<div class="th_name" id="account_label">Все счета</div>
						<i class="fa fa-filter fa-lg"></i>
						<div id="account_filter" class="filter_block" style="width: 200px;">
							<div class='btnset'>
								<?php
								echo "<input id='account_select_all' class='select_all' type='checkbox' name='all_accounts' value='1' form='filter_form'><label for='account_select_all'>Все счета</label>";
								$query = "
									SELECT FA_ID
										,name
									FROM FinanceAccount
									WHERE archive = 0
										".(in_array('finance_account', $Rights) ? " AND USR_ID = {$_SESSION["id"]}" : "")."
									ORDER BY IFNULL(bank, 0), FA_ID
								";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								$arr = ($_SESSION["cash_account"] != "") ? $_SESSION["cash_account"] : array();
								while( $row = mysqli_fetch_array($res) ) {
								 	$checked = in_array($row["FA_ID"], $arr) ? "checked" : "";
								 	echo "<input id='account_{$row["FA_ID"]}' class='chbox' {$checked} type='checkbox' name='FA_ID[]' value='{$row["FA_ID"]}' form='filter_form'><label for='account_{$row["FA_ID"]}' style='font-weight: normal;'>{$row["name"]}</label>";
								}
								?>
							</div>
						</div>
					</th>

					<th width="200" class="th_filter">
						<input id="category_search" type="text" style="display: none; position: absolute; width: 160px; z-index: 3;">
						<div class="th_name" id="category_label">Все категории</div>
						<i class="fa fa-filter fa-lg"></i>
						<div id="category_filter" class="filter_block" style="width: 200px; height: 300px;">
							<div class='btnset'>
								<?php
								echo "<input id='category_select_all' class='select_all' type='checkbox' name='all_categories' value='1' form='filter_form'><label for='category_select_all'>Все категории</label>";
								$query = "SELECT FC_ID, name FROM FinanceCategory ORDER BY FC_ID";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								$arr = ($_SESSION["cash_category"] != "") ? $_SESSION["cash_category"] : array();
								while( $row = mysqli_fetch_array($res) )
								{
									$checked = in_array($row["FC_ID"], $arr) ? "checked" : "";
									echo "<input id='category_{$row["FC_ID"]}' class='chbox' {$checked} type='checkbox' name='FC_ID[]' value='{$row["FC_ID"]}' form='filter_form'><label class='chbox_label' for='category_{$row["FC_ID"]}' style='font-weight: normal;'>{$row["name"]}</label>";
								}
								?>
							</div>
						</div>
					</th>

					<th width="115" class="th_filter">
						<div class="th_name" id="author_label">Все авторы</div>
						<i class="fa fa-filter fa-lg"></i>
						<div id="author_filter" class="filter_block" style="width: 200px;">
							<div class='btnset'>
								<?php
								echo "<input id='author_select_all' class='select_all' type='checkbox' name='all_authors' value='1' form='filter_form'><label for='author_select_all'>Все авторы</label>";
								$query = "SELECT USR_ID, USR_Name(USR_ID) Name FROM Users WHERE USR_ID IN (SELECT author FROM Finance) ORDER BY Name";
								$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
								$arr = ($_SESSION["cash_author"] != "") ? $_SESSION["cash_author"] : array();
								while( $row = mysqli_fetch_array($res) )
								{
									$checked = in_array($row["USR_ID"], $arr) ? "checked" : "";
									echo "<input id='author_{$row["USR_ID"]}' class='chbox' {$checked} type='checkbox' name='USR_ID[]' value='{$row["USR_ID"]}' form='filter_form'><label class='chbox_label' for='author_{$row["USR_ID"]}' style='font-weight: normal;'>{$row["Name"]}</label>";
								}
								?>
							</div>
						</div>
					</th>

					<th width="280" class="th_filter">
						<input id="comment_search" type="text" name="cash_comment" form="filter_form" style="display: none; position: absolute; width: 240px; z-index: 3;" value="<?=$_SESSION["cash_comment"]?>">
						<div class="th_name" id="comment_label">Комментарии</div>
						<i class="fa fa-filter fa-lg"></i>
					</th>
					<th width="30"></th>
				</tr>
			</thead>
			<tbody>
			<?php
				// Переменные фильтрации из сессии
				$FA_IDs = $_SESSION["cash_account"] != "" ? implode(",", $_SESSION["cash_account"]) : "";
				$FC_IDs = $_SESSION["cash_category"] != "" ? implode(",", $_SESSION["cash_category"]) : "";
				$USR_IDs = $_SESSION["cash_author"] != "" ? implode(",", $_SESSION["cash_author"]) : "";

				$query = "
					SELECT SF.F_ID
						,SF.date
						,Friendly_date(SF.date) Date
						,DATE_FORMAT(SF.date, '%H:%i') Time
						,SF.type
						,SF.money
						,SF.account
						,SF.color
						,SF.local
						,SF.category
						,SF.comment
						,SF.kontragent
						,SF.KA_ID
						,SF.Name
						,SF.USR_ID
						,SF.sum
						,SF.FA_ID
						,SF.to_account
						,SF.FC_ID
						,SF.is_edit
						,SF.account_filter
						,SF.receipt
						,SF.author_name
						,SF.author
						,SF.archive
					FROM (
						SELECT F.F_ID
							,F.date
							,IFNULL(FC.type, 0) type
							,IFNULL(FC.type, -1) * F.money money
							,FA.name account
							,FA.color
							,FA.local
							,IF(F.to_account IS NULL, FC.name, CONCAT(FA.name, ' <i class=\'fa fa-arrow-right\'></i> ', TFA.name)) category
							,F.comment
							,KA.Naimenovanie kontragent
							,F.KA_ID
							,USR_ShortName(F.USR_ID) Name
							,F.USR_ID
							,F.money sum
							,F.FA_ID
							,F.to_account
							,F.FC_ID
							,IF(F.USR_ID IS NULL AND F.OP_ID IS NULL AND F.KA_ID IS NULL AND DATEDIFF(NOW(), F.date) < 1, 1, 0) is_edit
							,F.FA_ID account_filter
							,0 receipt
							,USR_Icon(F.author) author_name
							,F.author
							,IF(FA.archive = 1 OR IFNULL(TFA.archive, 0) = 1, 1, 0) archive
						FROM Finance F
						LEFT JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
						LEFT JOIN FinanceAccount FA ON FA.FA_ID = F.FA_ID
						LEFT JOIN FinanceAccount TFA ON TFA.FA_ID = F.to_account
						LEFT JOIN Kontragenty KA ON KA.KA_ID = F.KA_ID
						WHERE F.money > 0 AND F.date >= STR_TO_DATE('{$cash_from}', '%d.%m.%Y') AND F.date <= STR_TO_DATE('{$cash_to} 23:59:59', '%d.%m.%Y %T')

						UNION ALL

						SELECT F.F_ID
							,F.date
							,0 type
							,F.money
							,TFA.name account
							,TFA.color
							,TFA.local
							,CONCAT(FA.name, ' <i class=\'fa fa-arrow-right\'></i> ', TFA.name) category
							,F.comment
							,NULL
							,F.KA_ID
							,NULL
							,F.USR_ID
							,F.money sum
							,F.FA_ID
							,F.to_account
							,F.FC_ID
							,0 is_edit
							,F.to_account account_filter
							,1 receipt
							,USR_Icon(F.author) author_name
							,F.author
							,IF(FA.archive = 1 OR IFNULL(TFA.archive, 0) = 1, 1, 0) archive
						FROM Finance F
						LEFT JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
						LEFT JOIN FinanceAccount FA ON FA.FA_ID = F.FA_ID
						LEFT JOIN FinanceAccount TFA ON TFA.FA_ID = F.to_account
						WHERE F.money > 0 AND F.date >= STR_TO_DATE('{$cash_from}', '%d.%m.%Y') AND F.date <= STR_TO_DATE('{$cash_to} 23:59:59', '%d.%m.%Y %T') AND F.to_account IS NOT NULL
					) SF
					WHERE 1
					".($_SESSION["cash_type"] != "" ? "AND SF.type = {$_SESSION["cash_type"]}" : "")."
					".($_SESSION["cash_sum_from"] != "" ? "AND SF.sum >= {$_SESSION["cash_sum_from"]}" : "")."
					".($_SESSION["cash_sum_to"] != "" ? "AND SF.sum <= {$_SESSION["cash_sum_to"]}" : "")."
					".($FA_IDs != "" ? "AND SF.account_filter IN ({$FA_IDs})" : "")."
					".(in_array('finance_account', $Rights) ? "AND SF.account_filter IN(SELECT FA_ID FROM FinanceAccount WHERE USR_ID = {$_SESSION["id"]})" : "")."
					".($FC_IDs != "" ? "AND SF.FC_ID IN ({$FC_IDs})" : "")."
					".($USR_IDs != "" ? "AND SF.author IN ({$USR_IDs})" : "")."
					".($_SESSION["cash_comment"] ? "AND (SF.comment LIKE '%{$_SESSION["cash_comment"]}%' OR SF.kontragent LIKE '%{$_SESSION["cash_comment"]}%' OR SF.Name LIKE '%{$_SESSION["cash_comment"]}%')" : "")."
					ORDER BY SF.date DESC, SF.F_ID DESC
				";

				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$cash_in = 0; // Сумма видимых операций
				$cash_in_local = 0; // Сумма видимых операций локальных
				while( $row = mysqli_fetch_array($res) ) {
					$cash_in = $cash_in + $row["money"];
					$cash_in_local = $cash_in_local + $row["money"] * $row["local"];
					$color = $row["money"] < 0 ? '#E74C3C' : '#16A085';
					$money = number_format($row["money"], 0, '', ' ');
					$type = ($row["type"] == 1 ? '<i class="fa fa-plus" style="color: #16A085;"></i>' : ($row["type"] == -1 ? '<i class="fa fa-minus" style="color: #E74C3C;"></i>' : '<i class="fa fa-exchange-alt"></i>'));

					if( $row["receipt"] == 0 or $FA_IDs != "" or in_array('finance_account', $Rights) ) {
						echo "<tr>";
						echo "<td class='nowrap'><b>{$row["Date"]}</b></td>";
						echo "<td class='nowrap'>{$row["Time"]}</td>";
						echo "<td style='text-align: center;'>{$type}</td>";
						echo "<td class='txtright' style='color: {$color};'><b>{$money}</b></td>";
						echo "<td><span class='nowrap' style='background-color: {$row["color"]}; border-radius: 20%;'>{$row["account"]}</span></td>";
						echo "<td><span class='nowrap'>{$row["category"]}</span></td>";
						echo "<td>{$row["author_name"]}</td>";
						echo "<td class='comment'><span class='nowrap'>{$row["comment"]}".($row["KA_ID"] ? " <a href='sverki.php?payer={$row["KA_ID"]}' target='_blank' title='Перейти в сверки'><b>{$row["kontragent"]}</b></a>" : ($row["USR_ID"] ? " <a href='paylog.php?worker={$row["USR_ID"]}' target='_blank' title='Перейти в зарплату'><b>{$row["Name"]}</b></a>" : ""))."</span></td>";
						if( $row["is_edit"] and $row["archive"] == 0 ) {
							echo "<td><a href='#' class='add_operation_btn' id='{$row["F_ID"]}' sum='{$row["sum"]}' type='{$row["type"]}' account='{$row["FA_ID"]}' category='{$row["FC_ID"]}' to_account='{$row["to_account"]}' kontragent='{$row["KA_ID"]}' title='Изменить операцию'><i class='fa fa-pencil-alt fa-lg'></i></a></td>";
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

				$cash_in_local = number_format($cash_in_local, 0, '', ' ');
				$color_local = $cash_in_local < 0 ? '#E74C3C' : '#16A085';
				$cash_change_local = "<span style='color: {$color_local};'>{$cash_in_local}</span>";
				$cash_change_local = addslashes( $cash_change_local );
			?>
			</tbody>
		</table>
	</div>
</div>

<script>
	$(document).ready(function() {
		$('#cash_change').html('<?=$cash_change?>');
		$('#cash_change_local').html('<?=$cash_change_local?>');
	});
</script>

<style>
	#add_operation .field, #add_send .field{
		display: inline-block;
		margin-right: 20px;
		margin-bottom: 20px;
	}
	#add_account .field{
		display: inline-block;
		margin-right: 20px;
		margin-bottom: 20px;
	}
</style>
<!--/////////////////////////////////////////////////////////////////-->
<!-- Форма добавления/редактирования операции -->
<div id='add_operation' style='display:none' title="ДОБАВИТЬ ОПЕРАЦИЮ">
	<form method='post' action='<?=$location?>?add_operation' onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="F_ID" id="F_ID">
			<div class="field">
				<label for="sum">Сумма:</label><br>
				<input required type="number" name="sum" min="0" id="sum" autocomplete="off" style="width: 100px; text-align: right; font-size: 20px;">
			</div>
			<br>
			<div class="field">
				<label for="type">Тип операции:</label>
				<div class='btnset' id='type'>
					<input type='radio' id='type-1' name='type' value='-1'>
						<label for='type-1'><i class="fa fa-minus fa-lg" title="Расход"></i></label>
					<input required type='radio' id='type1' name='type' value='1'>
						<label for='type1'><i class="fa fa-plus fa-lg" title="Доход"></i></label>
					<input type='radio' id='type0' name='type' value='0'>
						<label for='type0'><i class="fa fa-exchange-alt fa-lg" title="Перевод со счета"></i></label>
				</div>
			</div>
			<div class="field">
				<label for="account">Счёт:</label><br>
				<select required name="account" id="account" style="width: 140px;">
					<option value="">-=Выберите счёт=-</option>
						<?php
						if( !in_array('finance_account', $Rights) ) {
							echo "<optgroup label='Нал'>";
							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 0 AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
							}
							echo "</optgroup>";
							echo "<optgroup label='Безнал'>";

							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 1 AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
							}
							echo "</optgroup>";
						}
						else {
							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE USR_ID = {$_SESSION["id"]} AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
							}
						}
						?>
				</select>
			</div>
			<br>
			<div id="wr_category" class="field"></div> <!-- Заполняется аяксом -->
			<br>
			<div>
				<label for="comment">Комментарии:</label><br>
				<textarea name="comment" id="comment" rows="3" style="width: 300px;"></textarea>
			</div>
			<span style="color: #911;">Отредактировать операцию возможно в течении суток.</span>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!--/////////////////////////////////////////////////////////////////-->
<!-- Форма принятия выручки -->
<div id='add_send' style='display:none' title="ПРИНЯТЬ ВЫРУЧКУ">
	<form method='post' action='<?=$location?>?add_send' onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" id="OP_ID" name="OP_ID">
			<div style="text-align: center;">
				<div class='btnset'>
					<p>Принять <b id="payment_sum"></b> из кассы <b id="cashbox"></b> в кассу:</p>
					<select size="4" name="account" style="width: 100%;" required>
						<?php
						$query = "SELECT FA_ID, name FROM FinanceAccount WHERE USR_ID = {$_SESSION["id"]} AND IFNULL(bank, 0) = 0 AND archive = 0";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$account_cnt = mysqli_num_rows($res);
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option ".(($account_cnt == 1) ? "selected" : "")." value='{$row["FA_ID"]}'>{$row["name"]}</option>";
						}
						?>
					</select>
				</div>
			</div>
			<p style="color: red; text-align: center;"><b>Внимание!</b> Данную операцию отменить невозможно.</p>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Принять' style='float: right;'>
		</div>
	</form>
</div>
<!--/////////////////////////////////////////////////////////////////-->
<!-- Форма добавления/редактирования счета -->
<div id='add_account' style='display:none' title="ИЗМЕНИТЬ СЧЕТ">
	<form method='post' action='<?=$location?>?add_account' onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" id="FA_ID" name="FA_ID">
			<div class="field">
				<label for="bank">Тип счета:</label><br>
				<div class="btnset">
					<input required type="radio" name="bank" id="bank0" value="">
						<label for="bank0">Наличные</label>
					<input required type="radio" name="bank" id="bank1" value="1">
						<label for="bank1">Банковский счет</label>
				</div>
			</div>
			<div class="field">
				<label for="name">Название:</label><br>
				<input required type="text" name="name" id="name" autocomplete="off" style="width: 200px;">
			</div>
			<div class="field">
				<label for="сcolor">Цвет:</label><br>
				<input required type="color" name="color" id="color">
			</div>
			<div class="field">
				<label for="start_balance">Начальный баланс:</label><br>
				<input type="number" name="start_balance" autocomplete="off" id="start_balance" style="width: 100px; text-align: right;">
			</div>
			<div class="field">
				<label for="USR_ID">Пользователь:</label><br>
				<select name="USR_ID" id="USR_ID" style="width: 150px;">
					<option value="">-=Выберите пользователя=-</option>
					<?php
						$query = "SELECT USR_ID, USR_Name(USR_ID) Name FROM Users WHERE act = 1 AND KA_ID IS NULL ORDER BY Name";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["USR_ID"]}'>{$row["Name"]}</option>";
						}
					?>
				</select>
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!--/////////////////////////////////////////////////////////////////-->
<!-- Форма добавления/редактирования категории -->
<div id='add_category' style='display:none;' title="ИЗМЕНИТЬ КАТЕГОРИЮ">
	<form method='post' action='<?=$location?>?add_category' onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<table>
				<thead>
					<tr>
						<th>Название категории</th>
						<th>Тип</th>
					</tr>
				</thead>
				<tbody>
					<?php
					echo "<tr style='background: #6f6;'>";
					echo "<td><input type='text' name='name_add' autocomplete='off' style='width: 250px;'></td>";
					echo "<td><div class='btnset'>";
						echo "<input type='radio' id='ctype' name='type_add' value='1'>";
						echo "<label for='ctype'><i class='fa fa-plus fa-lg' title='Доходная'></i></label>";
						echo "<input type='radio' id='ctype-' name='type_add' value='-1'>";
						echo "<label for='ctype-'><i class='fa fa-minus fa-lg' title='Расходная'></i></label>";
					echo "</div></td>";
					echo "</tr>";

					$query = "SELECT FC_ID, name, type FROM FinanceCategory ORDER BY type DESC";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<tr>";
						echo "<td>";
							echo "<input type='hidden' name='FC_ID[]' value='{$row["FC_ID"]}'>";
							echo "<input type='hidden' name='type_edit[]' class='type_edit' value='{$row["type"]}'>";
							echo "<input type='text' name='name[]' value='{$row["name"]}' autocomplete='off' style='width: 250px;'>";
						echo "</td>";
						echo "<td><div class='btnset type_set'>";
							echo "<input ".($row["type"]==1 ? "checked" : "")." type='radio' id='ctype{$row["FC_ID"]}' name='type{$row["FC_ID"]}' value='1'>";
							echo "<label for='ctype{$row["FC_ID"]}'><i class='fa fa-plus fa-lg' title='Доходная'></i></label>";
							echo "<input ".($row["type"]==-1 ? "checked" : "")." type='radio' id='ctype-{$row["FC_ID"]}' name='type{$row["FC_ID"]}' value='-1'>";
							echo "<label for='ctype-{$row["FC_ID"]}'><i class='fa fa-minus fa-lg' title='Расходная'></i></label>";
						echo "</div></td>";
						echo "</tr>";
					}
					?>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!--/////////////////////////////////////////////////////////////////-->
<script>
	$(document).ready(function() {
		$('#add_operation form').submit(function() {
			if( $('#account').val() === $('#to_account').val() ) {
				noty({timeout: 3000, text: 'Счёт-отправитель и счёт-получатель должны различаться!', type: 'error'});
				return false;
			}
		});

		$('#kontragent').select2({ placeholder: 'Выберите контрагента', language: 'ru' });

		$('#USR_ID').select2({ placeholder: 'Выберите пользователя', language: 'ru' });

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

		// Кнопка добавления/редактирования операции
		$('.add_operation_btn').click( function() {
			var type = $(this).attr('type');
			var account = $(this).attr('account');
			var F_ID = $(this).attr('id');

			// Очистка диалога
			$('#add_operation #F_ID').val(F_ID);
			$('#add_operation #sum').val('');
			$('#add_operation #account').val(account);
			$('#type'+type).prop('checked', true).button('refresh');
			$('#type > #type'+type).change();
//			$('#add_operation #category').val('').trigger('change');
//			$('#add_operation #to_account').val('').trigger('change');
//			$('#add_operation #kontragent').val('').trigger('change');
			$('#add_operation #comment').val('');

			if( F_ID > 0 ) {
				var sum = $(this).attr('sum');
				var category = $(this).attr('category');
				var to_account = $(this).attr('to_account');
				var kontragent = $(this).attr('kontragent');
				var comment = $(this).parents('tr').find('.comment > span').html();
//				$('#add_operation #F_ID').val(F_ID);
				$('#add_operation #sum').val(sum);
				$('#add_operation #category').val(category).trigger('change');
				$('#add_operation #to_account').val(to_account).trigger('change');
				$('#add_operation #kontragent').val(kontragent).trigger('change');
				$('#add_operation #comment').val(comment);
			}

			$('#add_operation').dialog({
				resizable: false,
				width: 400,
				modal: true,
				closeText: 'Закрыть'
			});
			return false;
		});

		// Кнопка принятия выручки
		$('.add_send_btn').click( function() {
			$('#add_send #payment_sum').html($(this).attr('payment_sum'));
			$('#add_send #cashbox').html($(this).attr('cashbox'));
			$('#add_send #OP_ID').val($(this).attr('OP_ID'));
			$('#add_send select[name="account"]').val('');

			$('#add_send').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});
			return false;
		});

		// Кнопка добавления/редактирования счета
		$('.add_account_btn').click( function() {
			$('#add_account #FA_ID').val('');
			$('#add_account input[name="bank"]').prop('checked', false);
			$('#add_account .btnset').buttonset("refresh");
			$('#add_account #name').val('');
			$('#add_account #color').val('');
			$('#add_account #start_balance').val('');
			$('#add_account #USR_ID').val('').trigger('change');

			var FA_ID = $(this).attr('FA_ID');

			if( FA_ID > 0 ) {
				var bank = $(this).attr('bank');
				var name = $(this).attr('name');
				var color = $(this).attr('color');
				var start_balance = $(this).attr('start_balance');
				var USR_ID = $(this).attr('USR_ID');

				$('#add_account #FA_ID').val(FA_ID);
				if( bank == '1' ) {
					$('#add_account #bank1').prop('checked', true);
				}
				else {
					$('#add_account #bank0').prop('checked', true);
				}
					$('#add_account .btnset').buttonset("refresh");
				$('#add_account #name').val(name);
				$('#add_account #color').val(color);
				$('#add_account #start_balance').val(start_balance);
				$('#add_account #USR_ID').val(USR_ID).trigger('change');
			}

			$('#add_account').dialog({
				resizable: false,
				width: 400,
				modal: true,
				closeText: 'Закрыть'
			});
			return false;
		});

		// Кнопка добавления/редактирования категории
		$('.add_category_btn').click( function() {
			$('.type_set input').change( function(){
				var val = $(this).val();
				$(this).parents('tr').find('.type_edit').val(val);
			});

			$('#add_category').dialog({
				resizable: false,
				width: 400,
				modal: true,
				closeText: 'Закрыть'
			});
			return false;
		});

		// При смене типа операции меняется категория
		$('#type > input').change(function() {
			type = $(this).val();
			F_ID = $('#add_operation #F_ID').val();
			$.ajax({ url: "ajax.php?do=cash_category&type="+type, dataType: "script", async: false });
			$('#wr_kontragent').hide('fast');

			if( F_ID == 'add_operation_btn' ) { // Если новая операция
				if( type == -1 ) {
					$('#add_operation #account').val('<?=$out_account?>');
					$('#add_operation #category').val('<?=$out_category?>').trigger('change');
				}
				else if( type == 1 ) {
					$('#add_operation #account').val('<?=$in_account?>');
					$('#add_operation #category').val('<?=$in_category?>').trigger('change');
				}
				else {
					$('#add_operation #account').val('<?=$from_account?>');
					$('#add_operation #to_account').val('<?=$to_account?>').trigger('change');
				}
			}
			return false;
		});

	});
</script>

<?php
	include "footer.php";
?>
