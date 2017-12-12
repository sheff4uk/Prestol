<?
include "config.php";

$title = 'Акты сверок';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

if( $_GET["year"] and (int)$_GET["year"] > 0 ) {
	$year = $_GET["year"];
}
else {
	$year = date('Y');
}
if( $_GET["payer"] and (int)$_GET["payer"] > 0 ) {
	$query = "SELECT 1 FROM Kontragenty WHERE KA_ID IN ({$KA_IDs}) AND KA_ID = {$_GET["payer"]}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	if( mysqli_num_rows($res) ) {
		$payer = $_GET["payer"];
	}
	else {
		die('Недостаточно прав для совершения операции');
	}
}
else {
	$payer = "";
}

// Создание акта сверки
if( isset($_GET["add_act"]) ) {
	// Функция проверки уникальности токена
	function hashExists($hash, $mysqli) {
		$query = "SELECT * FROM ActSverki WHERE token = '{$hash}'";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		return mysqli_num_rows($res);
	}

	do {
		$hash = md5(rand(0, PHP_INT_MAX));
	} while (hashExists($hash, $mysqli));

	$act_date_from = date( 'Y-m-d', strtotime($_POST["act_date_from"]) );
	$act_date_to = date( 'Y-m-d', strtotime($_POST["act_date_to"]) );

	$query = "INSERT INTO ActSverki
				SET token = '{$hash}'
					,KA_ID = {$_POST["payer"]}
					,date_from = '{$act_date_from}'
					,date_to = '{$act_date_to}'";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	exit ('<meta http-equiv="refresh" content="0; url=?year='.($_GET["year"]).'&payer='.($_GET["payer"]).'">');
	die;
}

// Если вернулся только один контрагент - выбираем его в селекте
if( $KA_num_rows == 1 ) {
	$payer = $KA_IDs;
}

$now_date = date('d.m.Y');

// Удаление накладной
if( isset($_GET["del"]) )
{
	$PFI_ID = (int)$_GET["del"];

	$query = "UPDATE PrintFormsInvoice SET del = 1 WHERE PFI_ID = {$PFI_ID}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$location = "sverki.php?year={$year}&payer={$payer}";
	exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
	die;
}

?>
<form style="font-size: 1.2em;">
	<script>
		$(function() {
			$("#year option[value='<?=$year?>']").prop('selected', true);
			$("#payer option[value='<?=$payer?>']").prop('selected', true);
		});
	</script>
	<label for="year">Год:</label>
	<select name="year" id="year" onchange="this.form.submit()">
<?
	$query = "SELECT year FROM PrintForms WHERE IFNULL(summa, 0) > 0 GROUP BY year
				UNION
				SELECT YEAR(NOW())";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<option value='{$row["year"]}'>{$row["year"]}</option>";
	}
?>
	</select>
	&nbsp;&nbsp;
	<label for="payer">Контрагент:</label>
	<select name="payer" id="payer" onchange="this.form.submit()">
<?
	if( !in_array('sverki_opt', $Rights) ) {
		echo "<option value='0''>-=Все контрагенты=-</option>";
	}
	$query = "SELECT KA_ID, Naimenovanie, IFNULL(saldo, 0) saldo
				FROM Kontragenty
				WHERE KA_ID IN ({$KA_IDs})
				ORDER BY count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]} ({$row["saldo"]})</option>";
		if( $payer == $row["KA_ID"] ) {
			$saldo = $row["saldo"];
		}
	}
?>
	</select>
	&nbsp;&nbsp;
	<?
	if( $payer ) {
		$saldo_format = number_format($saldo, 0, '', ' ');
		echo "Сальдо: <b style='color: ".(($saldo < 0) ? "#E74C3C;" : "#16A085;")."'>{$saldo_format}</b>";
	}
	?>
</form>
<br>

<style>
	#add_invoice_btn {
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

	#add_invoice_btn_return {
		background: url(../img/bt_speed_dial_1x.png) no-repeat scroll center center transparent;
		bottom: 170px;
		cursor: pointer;
		width: 36px;
		height: 36px;
		opacity: .4;
		position: fixed;
		right: 60px;
		z-index: 9;
		border-radius: 50%;
		background-color: #db4437;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}

	#add_act_sverki_btn {
		background: url(../img/print_forms.png) no-repeat scroll center center transparent;
		bottom: 240px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 50px;
		z-index: 9;
		border-radius: 50%;
		background-color: #db4437;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}

	#add_invoice_btn:hover, #add_invoice_btn_return:hover, #add_act_sverki_btn:hover {
		opacity: 1;
	}

	#orders_to_invoice input[type="number"] {
		width: 100%;
		text-align: right;
	}
	.forms input[type="text"] {
		width: 99%;
	}
</style>

<?
if( !in_array('sverki_opt', $Rights) ) {
	echo "<a id='add_invoice_btn' href='#' title='Создать накладную на ОТГРУЗКУ'></a>";
	echo "<a id='add_invoice_btn_return' href='#' title='Создать накладную на ВОЗВРАТ'></a>";
	if( $payer ) {
		echo "<a id='add_act_sverki_btn' href='#' title='Создать новый акт сверки' now_date='{$now_date}' payer='{$payer}'></a>";
	}
}

if( $payer ) {
	echo "<h1>Акты сверок:</h1>";
	echo "
		<table>
			<thead>
				<tr>
					<th>Дата</th>
					<th>Период</th>
				</tr>
			</thead>
			<tbody>
	";
	$query = "SELECT token
					,DATE_FORMAT(date_from, '%d.%m.%y') date_from
					,DATE_FORMAT(date_to, '%d.%m.%y') date_to
				FROM ActSverki
				WHERE KA_ID = {$payer} AND YEAR(date_to) = {$year}
				ORDER BY date_to DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<tr>";
		echo "<td><b><a href='/toprint/act_sverki.php?t={$row["token"]}' target='_blank'>{$row["date_to"]}</a></b></td>";
		echo "<td>[{$row["date_from"]} - {$row["date_to"]}]</td>";
		echo "</tr>";
	}
	echo "
			</tbody>
		</table>
	";
	echo "<h1>Журнал операций:</h1>";
}
?>

<table>
	<thead>
		<tr>
			<th>Дебет</th>
			<th>Кредит</th>
			<th>Контрагент</th>
			<th>Операция/Документ</th>
			<th>Дата</th>
			<th>Автор</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?
if( $payer ) {
	$query = "SELECT PFI.PFI_ID
					,IF(PFI.rtrn = 1, NULL, PFI.summa) debet
					,IF(PFI.rtrn = 1, PFI.summa, NULL) kredit
					,KA.KA_ID
					,KA.Naimenovanie
					,IF(PFI.rtrn = 1, CONCAT('Возврат товара, накладная <b>№', PFI.count, '</b>'), CONCAT('Реализация, накладная <b>№', PFI.count, '</b>')) document
					,PFI.count
					,DATE_FORMAT(PFI.date, '%d.%m.%y') date_format
					,PFI.date
					,USR_Name(PFI.USR_ID) Name
					,PFI.del
					,PFI.rtrn
				FROM PrintFormsInvoice PFI
				LEFT JOIN Kontragenty KA ON KA.KA_ID = PFI.platelshik_id
				WHERE YEAR(PFI.date) = {$year} AND KA.KA_ID = {$payer}

				UNION ALL

				SELECT F.F_ID
					,NULL debet
					,F.money kredit
					,KA.KA_ID
					,KA.Naimenovanie
					,CONCAT('Оплата от покупателя, <b>', F.comment, '</b>') document
					,NULL
					,DATE_FORMAT(F.date, '%d.%m.%y') date_format
					,F.date
					,USR_Name(F.author) Name
					,NULL
					,1
				FROM Finance F
				LEFT JOIN Kontragenty KA ON KA.KA_ID = F.KA_ID
				WHERE YEAR(F.date) = {$year} AND KA.KA_ID = {$payer}

				ORDER BY date DESC, PFI_ID DESC";
}
else {
	$query = "SELECT PFI.PFI_ID
					,IF(PFI.rtrn = 1, NULL, PFI.summa) debet
					,IF(PFI.rtrn = 1, PFI.summa, NULL) kredit
					,KA.KA_ID
					,KA.Naimenovanie
					,IF(PFI.rtrn = 1, CONCAT('Возврат товара, накладная <b>№', PFI.count, '</b>'), CONCAT('Реализация, накладная <b>№', PFI.count, '</b>')) document
					,PFI.count
					,DATE_FORMAT(PFI.date, '%d.%m.%y') date_format
					,PFI.date
					,USR_Name(PFI.USR_ID) Name
					,PFI.del
					,PFI.rtrn
				FROM PrintFormsInvoice PFI
				LEFT JOIN Kontragenty KA ON KA.KA_ID = PFI.platelshik_id
				WHERE YEAR(PFI.date) = {$year} AND KA.KA_ID IN ({$KA_IDs})
				ORDER BY PFI.date DESC, PFI.PFI_ID DESC";
}
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$debet = ($row["debet"] != '') ? number_format($row["debet"], 0, '', ' ') : '';
	$kredit = ($row["kredit"] != '') ? number_format($row["kredit"], 0, '', ' ') : '';
//	$number = str_pad($row["count"], 8, '0', STR_PAD_LEFT);
	echo "<tr ".($row["del"] ? "class='del'" : "").">";
	echo "<td class='txtright' style='color: #E74C3C;'><b>{$debet}</b></td>";
	echo "<td class='txtright' style='color: #16A085;'><b>{$kredit}</b></td>";
	echo "<td><a href='sverki.php?year={$year}&payer={$row["KA_ID"]}'>{$row["Naimenovanie"]}</a></td>";
	echo "<td>{$row["document"]}</td>";
	if( $row["PFI_ID"] ) {
		echo "<td><a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><b>{$row["date_format"]}</b></a></td>";
	}
	else {
		echo "<td><b>{$row["date_format"]}</b></td>";
	}
	echo "<td>{$row["Name"]}</td>";
	if( $row["del"] == "0" and $row["rtrn"] == "0" and !in_array('sverki_opt', $Rights) ) {
		$Naimenovanie = addslashes($row["Naimenovanie"]);
		echo "<td><button onclick='if(confirm(\"Удалить накладную <b>№{$row["count"]} ({$Naimenovanie})</b> от <b>{$row["date_format"]}</b>?\", \"?del={$row["PFI_ID"]}&year={$year}&payer={$payer}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></button></td>";
	}
	else {
		echo "<td></td>";
	}
	echo "</tr>";
}
?>
	</tbody>
</table>

<!-- Форма подготовки накладной -->
<div id='add_invoice_form' style='display:none'>
	<form method='post' action="invoice.php">
		<fieldset style="text-align: center;">
			<div>
				<input type="hidden" name="year" value="<?=$year?>">
				<input type="hidden" name="payer" value="<?=$payer?>">
				<label>Контрагент:</label>
				<select name="KA_ID" required>
					<?
					echo "<option value='' CT_ID=''>-=Выберите контрагента=-</option>";

					// Если доступен только город - выводим только его
					if( in_array('sverki_city', $Rights) ) {
						$query = "SELECT CT_ID, City, Color FROM Cities WHERE CT_ID = {$USR_City}";
					}
					else {
						$query = "SELECT CT_ID, City, Color FROM Cities ORDER BY CT_ID";
					}
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<optgroup label='{$row["City"]}' style='background: {$row["Color"]};'>";

						// Если доступен только город и у пользователя указан салон - показываем только его
						if( in_array('sverki_city', $Rights) and $USR_Shop ) {
							$query = "SELECT Shop FROM Shops WHERE SH_ID = {$USR_Shop}";
							$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							$Shop = mysqli_result($subres,0,'Shop');
							echo "<option value='0' CT_ID='{$row["CT_ID"]}'>{$Shop}</option>";
						}
						else {
							// Выводим список контрагентов, связанных с салонами этого города
							$query = "SELECT KA.KA_ID, KA.Naimenovanie
										FROM Kontragenty KA
										JOIN Shops SH ON SH.KA_ID = KA.KA_ID
										WHERE SH.CT_ID = {$row["CT_ID"]}
										GROUP BY KA.KA_ID
										ORDER BY KA.KA_ID";
							$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $subrow = mysqli_fetch_array($subres) )
							{
								echo "<option value='{$subrow["KA_ID"]}' CT_ID='{$row["CT_ID"]}'>{$subrow["Naimenovanie"]} ({$row["City"]})</option>";
							}

							// Если в городе есть розница - показываем "Роница/Склад"
							$query = "SELECT 1 FROM Shops WHERE CT_ID = {$row["CT_ID"]} AND KA_ID IS NULL";
							$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							if( mysqli_num_rows($subres) ) {
								echo "<option value='0' CT_ID='{$row["CT_ID"]}'>*Розница/Склад ({$row["City"]})*</option>";
							}
						}
						echo "</optgroup>";
					}
					?>
				</select>
				<br>
				<br>
				<div id="wr_platelshik" style="display: flex;">
					<table width="50%" class="forms" style="border: 2px solid;">
						<tbody>
							<tr align="left">
								<td colspan="2"><strong id="KA_info"></strong></td>
							</tr>
							<tr>
								<td width="200" align="left" valign="top">Название ООО или ИП:</td>
								<td align="left" valign="top">
									<input type="hidden" name="platelshik_id" id="platelshik_id" class="forminput">
									<input required type="text" autocomplete="off" name="platelshik_name" id="platelshik_name" class="forminput" placeholder="Введите минимум 2 символа для поиска контрагента">
								</td>
							</tr>
							<tr>
								<td align="left" valign="top">ИНН:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_inn" id="platelshik_inn" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td align="left" valign="top">КПП:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_kpp" id="platelshik_kpp" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td align="left" valign="top">ОКПО:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_okpo" id="platelshik_okpo" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td align="left" valign="top">Адрес:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_adres" id="platelshik_adres" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td align="left" valign="top">Телефоны:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_tel" id="platelshik_tel" class="forminput" placeholder=""></td>
							</tr>
						</tbody>
					</table>

					<table width="50%" class="forms" style="border: 2px solid;">
						<tbody>
							<tr>
								<td colspan="2" align="left" valign="top"><strong id="KA_bank"></strong></td>
							</tr>
							<tr>
								<td width="200" align="left" valign="top">Расчетный счет:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_schet" id="platelshik_schet" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td align="left" valign="top">Наименование банка:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_bank" id="platelshik_bank" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td align="left" valign="top">БИК:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_bik" id="platelshik_bik" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td align="left" valign="top">Корреспондентский счет:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_ks" id="platelshik_ks" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td align="left" valign="top">Местонахождение банка:</td>
								<td align="left" valign="top"><input type="text" autocomplete="off" name="platelshik_bank_adres" id="platelshik_bank_adres" class="forminput" placeholder=""></td>
							</tr>
							<tr>
								<td height="29"></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div id="num_rows" style="display: none;">
				Количество строк:&nbsp;
				<select style="margin: 10px;">
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="100">100</option>
					<option value="250">250</option>
					<option value="500">500</option>
				</select>
				<input type="hidden" name="num_rows">
			</div>
			<div class="accordion">
				<h3>Список заказов</h3>
				<div id="orders_to_invoice" style='text-align: left;'></div>
			</div>
			<br>
			<div>
				<hr>
				<h3>Сумма накладной: <span id="invoice_total" style="color: #16A085;">0</span></h3>
				<input type="hidden" name="summa" value="0">
				<input type='submit' value='Создать накладную' style='float: right;'>
				<input type="text" name="date" id="date" class="date" style="float: right; margin: 4px 10px; width: 90px;" readonly>
			</div>
			<p id="return_message" style="color: #911; display: none;">ВНИМАНИЕ! Накладную на возврат товара отменить не возможно.</p>
		</fieldset>
	</form>
</div>

<!-- Форма подготовки акта сверки -->
<div id='add_act_sverki_form' style='display:none' title="Акт сверки">
	<form method='post' action="?add_act=1&year=<?=$year?>&payer=<?=$payer?>">
		<fieldset>
			<div>
				Период:&nbsp;[&nbsp;
				<input type="text" name="act_date_from" required class="date from" autocomplete="off">
				&nbsp;-&nbsp;
				<input type="text" name="act_date_to" required class="date to" autocomplete="off">
				&nbsp;]
				<input type="hidden" name="payer">
			</div>
			<div>
				<hr>
				<input type='submit' value='Создать акт сверки' style='float: right;'>
			</div>
		</fieldset>
	</form>
</div>

<script>
	// Выбрать все в форме отгрузки
	function selectall(ch) {
		$('#orders_to_invoice .chbox.show').prop('checked', ch).change();
		$('#orders_to_invoice #selectalltop').prop('checked', ch);
		$('#orders_to_invoice #selectallbottom').prop('checked', ch);
		return false;
	}

	// Подсчет суммы накладной
	function invoice_total() {
		let arr = Array
					.from(document.querySelectorAll('#orders_to_invoice input[name="opt_price[]"]')) // собираем массив из нод
					.map((item) => {
//						var item_price = (item.offsetWidth > 0 || item.offsetHeight > 0) ? item.value : 0;
						var item_price = (item.getAttribute('disabled') == "disabled") ? 0 : item.value;
						var item_amount = item.getAttribute('amount');
						return item_price * item_amount // трансформируем массив в массив содержащий уже не ноды, а их содержимое
					})
					.map(Number); // приводим к числовому типу

		let total = arr.reduce((sum, item) => {
			return sum+item; // считаем сумму массива
		});

		$('input[name="summa"]').val(total);
		total = total.format();
		$('#invoice_total').html(total);
	}

	$(function() {
		$('#payer').select2({ placeholder: 'Выберите контрагента', language: 'ru' });

		// Форма составления накладной
		$('#add_invoice_btn, #add_invoice_btn_return').click(function() {
			// Узнаём какая их 2-х кнопок была нажата
			var this_id = $(this).attr('id');
			var title;
			if( this_id == 'add_invoice_btn' ) {
				title = 'Накладная на ОТГРУЗКУ';
				$('#KA_info').html('Информация о ГРУЗОПОЛУЧАТЕЛЕ:');
				$('#KA_bank').html('Банковские реквизиты ГРУЗОПОЛУЧАТЕЛЯ:');
				$('#num_rows').hide();
				$('#num_rows input').val(0);
				$('#return_message').hide();
			}
			else {
				title = 'Накладная на ВОЗВРАТ'
				$('#KA_info').html('Информация о ГРУЗООТПРАВИТЕЛЕ:');
				$('#KA_bank').html('Банковские реквизиты ГРУЗООТПРАВИТЕЛЯ:');
				$('#num_rows').show();
				$('#num_rows input').val(25);
				$('#return_message').show();
			}
			// Очистка
			$('select[name="KA_ID"]').val('').change();
			$('#orders_to_invoice').html('');
			$('#add_invoice_form .accordion').accordion( "option", "active", 1 );
			$('#date').val('<?=( date('d.m.Y') )?>');
			$('#num_rows select').val(25);

			$('#add_invoice_form').dialog({
				position: { my: "center top", at: "center top", of: window },
				title: title,
				draggable: false,
				width: 1000,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			$( "#platelshik_name" ).autocomplete( "option", "appendTo", "#add_invoice_form" );
		});

		// Форма подготовки акта сверки
		$('#add_act_sverki_btn').click(function() {
			var now_date = $(this).attr('now_date');
			var payer = $(this).attr('payer');

			// Очистка диалога
			$('#add_act_sverki_form input[name="payer"]').val(payer);
			$('#add_act_sverki_form .from').val('');
			$('#add_act_sverki_form .to').datepicker( "setDate", now_date );
			$('#add_act_sverki_form .from').datepicker( "option", "maxDate", now_date );
			$('#add_act_sverki_form .to').datepicker( "option", "maxDate", now_date );

			$('#add_act_sverki_form').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
		});

		// Динамическая подгрузка заказов при выборе контрагента (в форме накладной)
		$('select[name="KA_ID"]').on('change', function() {
			var KA_ID = $(this).val();
			var CT_ID = $(this).find('option:selected').attr('CT_ID');
			var num_rows = $('#num_rows input').val();
			$.ajax({ url: "ajax.php?do=invoice&KA_ID="+KA_ID+"&CT_ID="+CT_ID+"&num_rows="+num_rows, dataType: "script", async: false });
			if( KA_ID ) {
				$('#add_invoice_form .accordion').accordion( "option", "active", 0 );
			}
			else {
				$('#add_invoice_form .accordion').accordion( "option", "active", 1 );
			}
		});

		// При смене количества строк записываем значение в скрытое поле и вызываем аякс для подгрузки заказов
		$('#num_rows select').on('change', function() {
			$('#num_rows input').val($(this).val());
			var KA_ID = $('select[name="KA_ID"]').val();
			var CT_ID = $('select[name="KA_ID"]').find('option:selected').attr('CT_ID');
			var num_rows = $('#num_rows input').val();
			$.ajax({ url: "ajax.php?do=invoice&KA_ID="+KA_ID+"&CT_ID="+CT_ID+"&num_rows="+num_rows, dataType: "script", async: false });
		});

		// Обработчики чекбоксов в форме отгрузки
		$('#orders_to_invoice').on('change', '#selectalltop', function(){
			ch = $('#selectalltop').prop('checked');
			selectall(ch);
			return false;
		});
		$('#orders_to_invoice').on('change', '#selectallbottom', function(){
			ch = $('#selectallbottom').prop('checked');
			selectall(ch);
			return false;
		});
		$('#orders_to_invoice').on('change', '.chbox', function(){
			var checked_status = true;
			$('.chbox.show').each(function(){
				if( !$(this).prop('checked') )
				{
					checked_status = $(this).prop('checked');
				}
			});
			$('#selectalltop').prop('checked', checked_status);
			$('#selectallbottom').prop('checked', checked_status);
			return false;
		});
		// Конец обработчиков чекбоксов

		// Фильтр по салонам
		$('#orders_to_invoice').on('change', '.button_shops', function(){
			var id = $(this).attr('id');
			if( $(this).prop('checked') ) {
				$('#to_invoice .'+id).show('fast');
				$('#to_invoice .'+id+' input[type=checkbox]').removeClass('hide');
				$('#to_invoice .'+id+' input[type=checkbox]').addClass('show');
				$('#to_invoice .'+id+' input[type=checkbox]').change();
			}
			else {
				$('#to_invoice .'+id+' input[type=checkbox]').prop('checked', false);
				$('#to_invoice .'+id).hide('fast');
				$('#to_invoice .'+id+' input[type=checkbox]').removeClass('show');
				$('#to_invoice .'+id+' input[type=checkbox]').addClass('hide');
				$('#to_invoice .'+id+' input[type=checkbox]').change();
			}
		});

		// При включении чекбокса отображается инпут цены
		$('#orders_to_invoice').on('change', '.chbox', function() {
			if( $(this).prop('checked') ) {
				$(this).parents('tr').find('input[type="hidden"], input[type="number"]').attr('disabled', false);
				$(this).parents('tr').find('input[type="hidden"], input[type="number"]').show('fast');
			}
			else {
				$(this).parents('tr').find('input[type="hidden"], input[type="number"]').attr('disabled', true);
				$(this).parents('tr').find('input[type="hidden"], input[type="number"]').hide('fast');
			}
		});

		// При редактировании цены или изменении чекбокса пересчитывается сумма накладной
		$('#orders_to_invoice').on('change', 'input[type="number"]', function() {
			invoice_total();
		});
		$('#orders_to_invoice').on('change', '.chbox', function() {
			invoice_total();
		});

		// Автокомплит плательщика
		$( "#platelshik_name" ).autocomplete({
//			source: "kontragenty.php",
			minLength: 2,
			autoFocus: true,
			select: function( event, ui ) {
				$('#platelshik_id').val(ui.item.id);
				$('#platelshik_inn').val(ui.item.INN);
				$('#platelshik_kpp').val(ui.item.KPP);
				$('#platelshik_okpo').val(ui.item.OKPO);
				$('#platelshik_adres').val(ui.item.Jur_adres);
				$('#platelshik_tel').val(ui.item.Telefony);
				$('#platelshik_schet').val(ui.item.Schet);
				$('#platelshik_bank').val(ui.item.Bank);
				$('#platelshik_bik').val(ui.item.BIK);
				$('#platelshik_ks').val(ui.item.KS);
				$('#platelshik_bank_adres').val(ui.item.Bank_adres);
			}
		});

		$( "#platelshik_name" ).on("keyup", function() {
			if( $( "#platelshik_name" ).val().length < 2 ) {
				$('#platelshik_id').val('');
				$('#platelshik_inn').val('');
				$('#platelshik_kpp').val('');
				$('#platelshik_okpo').val('');
				$('#platelshik_adres').val('');
				$('#platelshik_tel').val('');
				$('#platelshik_schet').val('');
				$('#platelshik_bank').val('');
				$('#platelshik_bik').val('');
				$('#platelshik_ks').val('');
				$('#platelshik_bank_adres').val('');
			}
		});

		// При выборе контрагента форма плательщика становится доступной
		$('#add_invoice_form').on('change', 'select[name="KA_ID"]', function() {
			// Очищаем все поля плательщика
			$('#platelshik_id').val('');
			$("#platelshik_name").val('');
			$('#platelshik_inn').val('');
			$('#platelshik_kpp').val('');
			$('#platelshik_okpo').val('');
			$('#platelshik_adres').val('');
			$('#platelshik_tel').val('');
			$('#platelshik_schet').val('');
			$('#platelshik_bank').val('');
			$('#platelshik_bik').val('');
			$('#platelshik_ks').val('');
			$('#platelshik_bank_adres').val('');

			var val = $(this).val();
			if( val ) {
				$('#wr_platelshik input').attr('disabled', false);
				$( "#platelshik_name" ).autocomplete({source: "kontragenty.php?KA_ID="+val});
				if( val > 0 ) { // Если оптовик - выводим список связанных плательщиков
					$( "#platelshik_name" ).autocomplete('search', '##');
				}
				else {
					$( "#platelshik_name" ).autocomplete('search', '');
				}
			}
			else {
				$( "#platelshik_name" ).autocomplete('search', '');
				$('#wr_platelshik input').attr('disabled', true);
				$('#add_invoice_form .accordion').accordion( "option", "active", 1 );
			}
		});

		$( "#date" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );
	});
</script>

<?
	include "footer.php";
?>
