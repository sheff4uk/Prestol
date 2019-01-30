<?
include "config.php";

$title = 'Счета';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Обработка полученных данных из формы
if( $_GET["add_bill"] ) {

	// Сохраняем цены и скидки изделий в ODD
	$summa = 0;
	foreach ($_POST["item"] as $key => $value) {
		//$odd_id = $_POST["item"][$key];
		$tovar_cena = $_POST["tovar_cena"][$key];
		$discount = ($_POST["tovar_skidka"][$key] > 0) ? $_POST["tovar_skidka"][$key] : "NULL";

		$query = "UPDATE OrdersDataDetail SET Price = {$tovar_cena}, discount = {$discount}, author = {$_SESSION["id"]} WHERE ODD_ID = {$value}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Если товар из заказа - приписываем вначале код
		if( $_POST["code"][$key] ) {
			$_POST["tovar_name"][$key] = "[{$_POST["code"][$key]}] {$_POST["tovar_name"][$key]}";
		}
		$summa += ($_POST["tovar_cena"][$key] - $_POST["tovar_skidka"][$key]) * $_POST["tovar_kol"][$key];
	}

	// Получаем номер очередного документа
	$year = date('Y');
	$date = date('Y-m-d');
	$query = "SELECT COUNT(1)+1 Cnt FROM PrintFormsBill WHERE YEAR(date) = {$year}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$count = mysqli_result($res,0,'Cnt');
	$nomer = str_pad($count, 8, '0', STR_PAD_LEFT); // Дописываем нули к номеру накладной

	// Обновляем информацию о плательщике
	$platelshik_name = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_name"]));
	$platelshik_adres = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_adres"]));
	$platelshik_tel = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_tel"]));
	$platelshik_inn = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_inn"]));
	$platelshik_okpo = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_okpo"]));
	$platelshik_kpp = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_kpp"]));
	$platelshik_schet = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_schet"]));
	$platelshik_bank = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_bank"]));
	$platelshik_bik = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_bik"]));
	$platelshik_ks = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_ks"]));
	$platelshik_bank_adres = mysqli_real_escape_string($mysqli, convert_str($_POST["platelshik_bank_adres"]));

	if( $_POST["platelshik_id"] ) {
		$query = "UPDATE Kontragenty SET
					 Naimenovanie = '{$platelshik_name}'
					,Jur_adres = IF('{$platelshik_adres}' = '', NULL, '{$platelshik_adres}')
					,Telefony = IF('{$platelshik_tel}' = '', NULL, '{$platelshik_tel}')
					,INN = IF('{$platelshik_inn}' = '', NULL, '{$platelshik_inn}')
					,OKPO = IF('{$platelshik_okpo}' = '', NULL, '{$platelshik_okpo}')
					,KPP = IF('{$platelshik_kpp}' = '', NULL, '{$platelshik_kpp}')
					,Schet = IF('{$platelshik_schet}' = '', NULL, '{$platelshik_schet}')
					,Bank = IF('{$platelshik_bank}' = '', NULL, '{$platelshik_bank}')
					,BIK = IF('{$platelshik_bik}' = '', NULL, '{$platelshik_bik}')
					,KS = IF('{$platelshik_ks}' = '', NULL, '{$platelshik_ks}')
					,Bank_adres = IF('{$platelshik_bank_adres}' = '', NULL, '{$platelshik_bank_adres}')
					WHERE KA_ID = {$_POST["platelshik_id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$platelshik_id = $_POST["platelshik_id"];
	}
	else {
		$query = "INSERT INTO Kontragenty SET
					 Naimenovanie = '{$platelshik_name}'
					,Jur_adres = IF('{$platelshik_adres}' = '', NULL, '{$platelshik_adres}')
					,Telefony = IF('{$platelshik_tel}' = '', NULL, '{$platelshik_tel}')
					,INN = IF('{$platelshik_inn}' = '', NULL, '{$platelshik_inn}')
					,OKPO = IF('{$platelshik_okpo}' = '', NULL, '{$platelshik_okpo}')
					,KPP = IF('{$platelshik_kpp}' = '', NULL, '{$platelshik_kpp}')
					,Schet = IF('{$platelshik_schet}' = '', NULL, '{$platelshik_schet}')
					,Bank = IF('{$platelshik_bank}' = '', NULL, '{$platelshik_bank}')
					,BIK = IF('{$platelshik_bik}' = '', NULL, '{$platelshik_bik}')
					,KS = IF('{$platelshik_ks}' = '', NULL, '{$platelshik_ks}')
					,Bank_adres = IF('{$platelshik_bank_adres}' = '', NULL, '{$platelshik_bank_adres}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$platelshik_id = mysqli_insert_id($mysqli);
	}

	// Сохраняем в POST необходимую информацию о покупателе
	$_POST["pokupatel"] = convert_str($_POST["platelshik_name"]);
	$_POST["pokupatel_adres"] = convert_str($_POST["platelshik_adres"]);
	$_POST["pokupatel_inn"] = convert_str($_POST["platelshik_inn"]);
	$_POST["pokupatel_kpp"] = convert_str($_POST["platelshik_kpp"]);

	// Сохраняем в таблицу информацию по счёту, узнаем его ID.
	$query = "INSERT INTO PrintFormsBill SET count = {$count}, date = '{$date}', pokupatel_id = {$platelshik_id}, summa = {$summa}, USR_ID = {$_SESSION["id"]}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$id = mysqli_insert_id($mysqli);

	$_POST["nomer"] = $count;

	// Информация о продавце
	$query = "SELECT * FROM Rekvizity WHERE R_ID = 1";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$_POST["destination_name"] = mysqli_result($res,0,'Name');
	$_POST["destination_adres"] = mysqli_result($res,0,'Addres');
	$_POST["destination_INN"] = mysqli_result($res,0,'INN');
	$_POST["destination_KPP"] = mysqli_result($res,0,'KPP');
	$_POST["dorector"] = mysqli_result($res,0,'Dir');
	$_POST["destination_szhet"] = mysqli_result($res,0,'RS');
	$_POST["destination_bank"] = mysqli_result($res,0,'Bank');
	$_POST["destination_BIK"] = mysqli_result($res,0,'BIK');
	$_POST["destination_KS"] = mysqli_result($res,0,'KS');

	if( $curl = curl_init() ) {
		curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/buh/schet/blanc.php');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/buh/schet/');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$out = curl_exec($curl);

		$url = $out;
		$url = str_replace("<html><head><meta http-equiv='refresh' content='0; url=", "https://service-online.su", $url);
		$url = str_replace("'></head></html>", "", $url);
		$url = preg_replace("/\xEF\xBB\xBF/", "", $url);
		$url = trim($url);
		$out = file_get_contents($url);

		$filename = 'schet_'.$id.'_'.$nomer.'.pdf';
		file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере

		curl_close($curl);

		exit ('<meta http-equiv="refresh" content="0; url=bills.php">');
		die;
	}
}

if( isset($_GET["year"]) ) {
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
?>
<form>
	<script>
		$(function() {
			$("#year option[value='<?=$year?>']").prop('selected', true);
			$("#payer option[value='<?=$payer?>']").prop('selected', true);
		});
	</script>
	<label for="year">Год:</label>
	<select name="year" id="year" onchange="this.form.submit()">
<?
	$query = "SELECT YEAR(date) year FROM PrintFormsBill GROUP BY YEAR(date)
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
	if( in_array('sverki_opt', $Rights) ) {
		// Выводим контрагентов для оптовиков
		$query = "SELECT KA_ID, Naimenovanie, IFNULL(saldo, 0) saldo
					FROM Kontragenty
					WHERE KA_ID IN ({$KA_IDs})";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$saldo_format = number_format($row["saldo"], 0, '', ' ');
			echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]} ({$saldo})</option>";
			if( $payer == $row["KA_ID"] ) {
				$saldo = $row["saldo"];
			}
		}
	}
	else {
		// Выводим контрагентов для остальных категорий пользователей
		echo "<option value='0''>-=Все контрагенты=-</option>";

		// Выводим должников
		$total = 0; // Сумма дебета
		echo "<optgroup id='debt' label='Должники'>";
		$query = "SELECT KA_ID, Naimenovanie, IFNULL(saldo, 0) saldo
					FROM Kontragenty
					WHERE KA_ID IN ({$KA_IDs}) AND IFNULL(saldo, 0) < 0
					ORDER BY IFNULL(saldo, 0)";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$saldo_format = number_format($row["saldo"], 0, '', ' ');
			echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]} ({$saldo_format})</option>";
			if( $payer == $row["KA_ID"] ) {
				$saldo = $row["saldo"];
			}
			$total += $row["saldo"];
		}
		echo "</optgroup>";
		$saldo_format = number_format($total, 0, '', ' ');
		echo "<script>$('#debt').attr('label', 'Должники ({$saldo_format})');</script>";

		// Выводим кредиторов
		echo "<optgroup id='credit' label='Кредиторы'>";
		$total = 0; // Сумма кредита
		$query = "SELECT KA_ID, Naimenovanie, IFNULL(saldo, 0) saldo
					FROM Kontragenty
					WHERE KA_ID IN ({$KA_IDs}) AND IFNULL(saldo, 0) > 0
					ORDER BY IFNULL(saldo, 0) DESC";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$saldo_format = number_format($row["saldo"], 0, '', ' ');
			echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]} ({$saldo_format})</option>";
			if( $payer == $row["KA_ID"] ) {
				$saldo = $row["saldo"];
			}
			$total += $row["saldo"];
		}
		echo "</optgroup>";
		$saldo_format = number_format($total, 0, '', ' ');
		echo "<script>$('#credit').attr('label', 'Кредиторы ({$saldo_format})');</script>";

		// Выводим нейтральных
		echo "<optgroup label='Нейтральные'>";
		$query = "SELECT KA_ID, Naimenovanie, IFNULL(saldo, 0) saldo
					FROM Kontragenty
					WHERE KA_ID IN ({$KA_IDs}) AND IFNULL(saldo, 0) = 0
					ORDER BY count DESC";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]}</option>";
			if( $payer == $row["KA_ID"] ) {
				$saldo = $row["saldo"];
			}
		}
		echo "</optgroup>";
	}
?>
	</select>
</form>
<br>

<style>
	#add_bill_btn {
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

	#add_bill_btn:hover {
		opacity: 1;
	}

	.forms input {
		width: 99%;
	}

	.forms .left {
		width: 250px;
	}
	.comment {
		width: 95%;
		max-width: 95%;
		min-height: 100px;
		margin: 2%;
		border-radius: 5px;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
	}
</style>

<?
if( !in_array('sverki_opt', $Rights) ) {
	echo "<a id='add_bill_btn' href='#' title='Создать счёт'></a>";
}
?>

<table>
	<thead>
		<tr>
			<th>Сумма</th>
			<th>Дата</th>
			<th>Покупатель</th>
			<th>Номер</th>
			<th>Файл</th>
			<th>Автор</th>
		</tr>
	</thead>
	<tbody>
<?
$query = "SELECT PFB.PFB_ID
				,PFB.summa
				,KA.Naimenovanie pokupatel
				,KA.KA_ID
				,PFB.count
				,DATE_FORMAT(PFB.date, '%d.%m.%y') date_format
				,USR_Icon(PFB.USR_ID) Name
			FROM PrintFormsBill PFB
			LEFT JOIN Kontragenty KA ON KA.KA_ID = PFB.pokupatel_id
			WHERE YEAR(PFB.date) = {$year}
				AND KA.KA_ID IN ({$KA_IDs})
				".($payer ? "AND KA.KA_ID = {$payer}" : "")."
			ORDER BY PFB.PFB_ID DESC";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$summa = number_format($row["summa"], 0, '', ' ');
	$number = str_pad($row["count"], 8, '0', STR_PAD_LEFT);
	echo "<tr>";
	echo "<td class='txtright nowrap' style='color: #16A085;'><b>{$summa}</b></td>";
	echo "<td><b>{$row["date_format"]}</b></td>";
	echo "<td><a href='bills.php?year={$year}&payer={$row["KA_ID"]}'>{$row["pokupatel"]}</a></td>";
	echo "<td><b>{$row["count"]}</b></td>";
	echo "<td><b><a href='open_print_form.php?type=schet&PFB_ID={$row["PFB_ID"]}&number={$number}' target='_blank'><i class='fa fa-file-pdf fa-2x'></a></b></td>";
	echo "<td>{$row["Name"]}</td>";
	echo "</tr>";
}
?>
	</tbody>
</table>

<!-- Форма подготовки счёта -->
<div id='add_bill_form' style='display:none' title="Счёт на оплату">
	<h1>Счёт на оплату</h1>
	<form action="?add_bill=1" method="post" id="formdiv">
		<fieldset style="text-align: left;">
			<legend>Информация о плательщике:</legend>
			<table width="100%" class="forms">
				<tbody>
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
				</tbody>
			</table>
		</fieldset>

		<input type="hidden" name="nds" value="0">

		<br>

		<table width="100%" border="0" cellspacing="4" class="forms" id="tab1">
			<tbody>
				<tr>
					<th colspan="7" align="left"><strong>Наименование товара, подлежащего оплате:</strong></th>
				</tr>
				<tr>
					<th width="60">Код</th>
					<th width="45%">Наименование товара</th>
					<th width="40">Ед. измерения</th>
					<th width="40">Кол-во</th>
					<th width="80">Цена за шт.</th>
					<th width="80">Скидка за шт.</th>
					<th width="20"><p>&nbsp;</p></th>
				</tr>
			</tbody>

			<tbody>
				<tr>
					<td colspan="7">
						<i class="fa fa-plus-square fa-2x" style="color: green;" onclick="addRow('шт');"></i>
						<span onclick="addRow('шт');"><font><font> Добавить строку</font></font></span>
					</td>
				</tr>
			</tbody>
		</table>

<script type="text/javascript">
	function addRow(ed, name, amount, min_price, price, discount, item, code, odid)
	{

		// Находим нужную таблицу
		var tbody = $('#tab1 tbody:eq(0)');

		// Создаем строку таблицы и добавляем ее
		var row = $("<tr></tr>");
		tbody.append(row);

		// Создаем ячейки в вышесозданной строке и добавляем их
		var td0 = $("<td></td>");
		var td1 = $("<td></td>");
		var td2 = $("<td></td>");
		var td3 = $("<td></td>");
		var td4 = $("<td></td>");
		var td5 = $("<td></td>");
		var td6 = $("<td></td>");

		row.append(td0);
		row.append(td1);
		row.append(td2);
		row.append(td3);
		row.append(td4);
		row.append(td5);
		row.append(td6);

		// Наполняем ячейки
		if( typeof name === "undefined" ) {
			name = '';
		}
		if( typeof ed === "undefined" ) {
			ed = '';
		}
		if( typeof amount === "undefined" ) {
			amount = '';
		}
		if( typeof price === "undefined" ) {
			price = '';
		}
		if( typeof min_price === "undefined" ) {
			min_price = '0';
		}
		if( typeof discount === "undefined" ) {
			discount = '';
		}
		if( typeof item === "undefined" ) {
			item = '';
		}
		if( typeof code === "undefined" ) {
			code = '';
		}
		if( typeof odid === "undefined" ) {
			odid = '';
		}
		td0.html('<b id="code">'+code+'</b><input type="hidden" name="odid[]" id="odid" value="'+odid+'"><input type="hidden" name="code[]" id="icode" value="'+code+'">');
		td1.html('<input required type="text" autocomplete="off" value="'+name+'" name="tovar_name[]" id="tovar_name" class="tovar_name" placeholder="Введите код заказа для поиска товара"/>');
		td2.html('<input required type="text" autocomplete="off" value="'+ed+'" name="tovar_ed[]" id="tovar_ed" class="f3" />');
		td3.html('<input required type="number" autocomplete="off" min="1" value="'+amount+'" name="tovar_kol[]" id="tovar_kol"/>');
		if( min_price > 0 ) {
			td4.html('<input required type="number" autocomplete="off" min="'+min_price+'" value="'+price+'" name="tovar_cena[]" id="tovar_cena" title="Вычисленная стоимость по прайсу: '+min_price+'"><input type="hidden" name="item[]" id="item" value="'+item+'">');

		}
		else {
			td4.html('<input required type="number" autocomplete="off" min="'+min_price+'" value="'+price+'" name="tovar_cena[]" id="tovar_cena"/><input type="hidden" name="item[]" id="item" value="'+item+'">');
		}
		td5.html('<input type="number" autocomplete="off" min="0" value="'+discount+'" name="tovar_skidka[]" id="tovar_skidka"/>');
		td6.html('<i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i>');

		td1.find('.tovar_name').autocomplete({
			source: "search_prod.php",
			minLength: 2,
			select: function( event, ui ) {
				$(this).parents('tr').find('#code').text(ui.item.code);
				$(this).parents('tr').find('#icode').val(ui.item.code);
				$(this).parents('tr').find('#odid').val(ui.item.odid);
				$(this).parents('tr').find('#item').val(ui.item.id);
				$(this).parents('tr').find('#tovar_cena').attr('min', ui.item.min_price);
				if( ui.item.min_price > 0 ) {
					$(this).parents('tr').find('#tovar_cena').attr('title', 'Вычисленная стоимость по прайсу: '+ui.item.min_price);
				}
				$(this).parents('tr').find('#tovar_cena').val(ui.item.Price);
				$(this).parents('tr').find('#tovar_skidka').val(ui.item.discount);
				$(this).parents('tr').find('#tovar_kol').val(ui.item.Amount);
			}
		});

		td1.find('.tovar_name').on("keyup", function() {
			if( $(this).val().length < 2 ) {
				$(this).parents('tr').find('#code').text('');
				$(this).parents('tr').find('#icode').val('');
				$(this).parents('tr').find('#odid').val('');
				$(this).parents('tr').find('#item').val('');
				$(this).parents('tr').find('#tovar_cena').attr('min', '0');
				$(this).parents('tr').find('#tovar_cena').attr('title', '');
				$(this).parents('tr').find('#tovar_cena').val('');
				$(this).parents('tr').find('#tovar_skidka').val('');
				$(this).parents('tr').find('#tovar_kol').val('');
			}
		});
	}

	function deleteRow(r)
	{
		$(r).parents('tr').remove();
	}
</script>

		<br>
		<div style="text-align: center;">
			<strong>Сообщение для клиента:</strong>
			<br>
			<textarea name="text" class="comment">Внимание! Оплата данного счета означает согласие с условиями поставки товара. Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и паспорта.</textarea>
		</div>

		<input name="n" type="hidden" value="1">

		<div>
			<hr>
			<input type='submit' value='Создать счет' style='float: right;'>
			<input type="text" name="date" id="date" value="<?=date('d.m.Y')?>" class="date" style="float: right; margin: 4px 10px; width: 90px;" readonly>
		</div>
	</form>
</div>

<script language="javascript">
	$(function() {
		// Форма составления счёта
		$('#add_bill_btn').click(function() {
			$('#add_bill_form').dialog({
				position: { my: "center top", at: "center top", of: window },
				draggable: false,
				width: 700,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
		});
<?
	// Получаем список заказов из GET и заполняем таблицу в форме
	if( isset($_GET["Tables"]) or isset($_GET["Chairs"]) or isset($_GET["Others"]) ) {

		// Формируем список id выбранных заказов из $_GET
		$id_list = '0';
		foreach ($_GET["order"] as $k => $v) {
			$id_list .= ",{$v}";
		}

		$product_types = "-1";
		if(isset($_GET["Tables"])) $product_types .= ",2";
		if(isset($_GET["Chairs"])) $product_types .= ",1";
		if(isset($_GET["Others"])) $product_types .= ",0";

		$query = "
			SELECT OD.OD_ID
				,OD.Code
				,ODD.ODD_ID
				,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
				,ODD.Amount
				,IFNULL(ODD.min_price, 0) min_price
				,ODD.Price
				,ODD.discount
				,Zakaz(ODD.ODD_ID) Zakaz
			FROM OrdersData OD
			JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
			LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			WHERE ODD.OD_ID IN ({$id_list})
			HAVING PTID IN({$product_types})
			ORDER BY OD.OD_ID, PTID DESC, ODD.ODD_ID";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			echo "addRow('шт', escapeHtml('{$row["Zakaz"]}'), '{$row["Amount"]}', '{$row["min_price"]}', '{$row["Price"]}', '{$row["discount"]}', '{$row["ODD_ID"]}', '{$row["Code"]}', '{$row["OD_ID"]}');";
		}
		echo "$('#add_bill_btn').click();";
	}
	else {
		echo "addRow('шт');";
	}
?>

		// Автокомплит плательщика
		$( "#platelshik_name" ).autocomplete({
			source: "kontragenty.php",
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

		$('#payer').select2({ placeholder: 'Выберите контрагента', language: 'ru' });

		$( "#date" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );
	});
</script>

<?
	include "footer.php";
?>
