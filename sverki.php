<?
include "config.php";

$title = 'Акты сверок';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Получаем список id доступных контрагентов
$KA_IDs = "0";
$query = "SELECT KA_ID FROM Kontragenty";
// Подставляем условие в зависимости от разрешения пользователя
if( in_array('sverki_opt', $Rights) ) {
	$query .= " WHERE KA_ID IN ({$USR_KA})";
}
elseif( in_array('sverki_city', $Rights) ) {
	if( $USR_Shop ) {
		$query .= " WHERE KA_ID IN (
						SELECT KA.KA_ID
						FROM PrintFormsInvoice PFI
						JOIN OrdersData OD ON OD.PFI_ID = PFI.PFI_ID AND OD.SH_ID = {$USR_Shop}
						JOIN Kontragenty KA ON KA.KA_ID = PFI.platelshik_id
					)";
	}
	else {
		$query .= " WHERE KA_ID IN (
						SELECT KA.KA_ID
						FROM Kontragenty KA
						JOIN Shops SH ON SH.KA_ID = KA.KA_ID
						WHERE SH.CT_ID = {$USR_City}
						UNION
						SELECT KA.KA_ID
						FROM PrintFormsInvoice PFI
						JOIN OrdersData OD ON OD.PFI_ID = PFI.PFI_ID
						JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.CT_ID = {$USR_City}
						JOIN Kontragenty KA ON KA.KA_ID = PFI.platelshik_id
					)";
	}
}
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
// Узнаем сколько вернулось строк для дальнейшей проверки
$num_rows = mysqli_num_rows($res);
if( $num_rows == 1 ) {
	$KA_IDs = mysqli_result($res,0,'KA_ID');
}
else {
	while( $row = mysqli_fetch_array($res) ) {
		$KA_IDs .= ",{$row["KA_ID"]}";
	}
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

// Если вернулся только один контрагент - выбираем его в селекте
if( $num_rows == 1 ) {
	$payer = $KA_IDs;
}

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
	<label for="payer">Плательщик:</label>
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

	#add_invoice_btn:hover {
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
	echo "<a id='add_invoice_btn' href='#' title='Создать накладную'></a>";
}
?>

<table>
	<thead>
		<tr>
			<th>Сумма</th>
			<th>Плательщик</th>
			<th>Номер<br>накладной</th>
			<th>Дата</th>
			<th>Автор</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?
if( $payer ) {
	$query = "SELECT PFI.PFI_ID
					,PFI.summa
					,KA.KA_ID
					,KA.Naimenovanie
					,PFI.count
					,DATE_FORMAT(PFI.date, '%d.%m.%Y') date_format
					,PFI.date
					,USR.Name
					,PFI.del
				FROM PrintFormsInvoice PFI
				LEFT JOIN Users USR ON USR.USR_ID = PFI.USR_ID
				LEFT JOIN Kontragenty KA ON KA.KA_ID = PFI.platelshik_id
				WHERE YEAR(PFI.date) = {$year} AND KA.KA_ID = {$payer}

				UNION ALL

				SELECT NULL
					,F.money
					,KA.KA_ID
					,KA.Naimenovanie
					,NULL
					,DATE_FORMAT(F.date, '%d.%m.%Y') date_format
					,F.date
					,USR.Name
					,NULL
				FROM Finance F
				LEFT JOIN Users USR ON USR.USR_ID = F.author
				LEFT JOIN Kontragenty KA ON KA.KA_ID = F.KA_ID
				WHERE YEAR(F.date) = {$year} AND KA.KA_ID = {$payer}

				ORDER BY date DESC";
}
else {
	$query = "SELECT PFI.PFI_ID
					,PFI.summa
					,KA.KA_ID
					,KA.Naimenovanie
					,PFI.count
					,DATE_FORMAT(PFI.date, '%d.%m.%Y') date_format
					,PFI.date
					,USR.Name
					,PFI.del
				FROM PrintFormsInvoice PFI
				LEFT JOIN Users USR ON USR.USR_ID = PFI.USR_ID
				LEFT JOIN Kontragenty KA ON KA.KA_ID = PFI.platelshik_id
				WHERE YEAR(PFI.date) = {$year} AND KA.KA_ID IN ({$KA_IDs})
				ORDER BY PFI.date DESC";
}
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$summa = number_format($row["summa"], 0, '', ' ');
//	$number = str_pad($row["count"], 8, '0', STR_PAD_LEFT);
	echo "<tr ".($row["del"] ? "class='del'" : "").">";
	echo "<td class='txtright' style='color: ".($row["PFI_ID"] ? "#E74C3C" : "#16A085").";'><b>{$summa}</b></td>";
	echo "<td><a href='sverki.php?year={$year}&payer={$row["KA_ID"]}'>{$row["Naimenovanie"]}</a></td>";
	if( $row["PFI_ID"] ) {
		echo "<td class='txtright'><b>{$row["count"]}</b></td>";
		echo "<td><a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><b>{$row["date_format"]}</b></a></td>";
	}
	else {
		echo "<td>Оплата</td>";
		echo "<td><b>{$row["date_format"]}</b></td>";
	}
	echo "<td>{$row["Name"]}</td>";
	if( $row["del"] == "0" and !in_array('sverki_opt', $Rights) ) {
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
<div id='add_invoice_form' title='Создание накладной' style='display:none'>
	<form method='post' action="invoice.php">
		<fieldset style="text-align: center;">
			<div>
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
								<td colspan="2"><strong>Информация о плательщике:</strong></td>
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
								<td colspan="2" align="left" valign="top"><strong>Банковские реквизиты плательщика:</strong></td>
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
			<br>
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
		$('#add_invoice_btn').click(function() {
			// Очистка
			$('select[name="KA_ID"]').val('').change();
			$('#orders_to_invoice').html('');
			$('#add_invoice_form .accordion').accordion( "option", "active", 1 );
			$('#date').val('<?=( date('d.m.Y') )?>');

			$('#add_invoice_form').dialog({
				position: { my: "center top", at: "center top", of: window },
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

		// Динамическая подгрузка заказов при выборе контрагента (в форме накладной)
		$('select[name="KA_ID"]').on('change', function() {
			var KA_ID = $(this).val();
			var CT_ID = $(this).find('option:selected').attr('CT_ID');
			$.ajax({ url: "ajax.php?do=invoice&KA_ID="+KA_ID+"&CT_ID="+CT_ID, dataType: "script", async: false });
			if( KA_ID ) {
				$('#add_invoice_form .accordion').accordion( "option", "active", 0 );
			}
			else {
				$('#add_invoice_form .accordion').accordion( "option", "active", 1 );
			}
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
