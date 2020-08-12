<?
include "config.php";

$title = 'Счета';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Формируем список контрагентов для дропдауна
$KA_options = "";
$KA_IDs = "0";
if (!$USR_Shop) { // Если не продавец - показываем оптовиков
	$KA_options .= "<optgroup label='Оптовые покупатели:'>";
	$query = "
		SELECT KA.KA_ID
			,CT.CT_ID
			,CT.City
			,KA.Naimenovanie
			,IFNULL(Jur_adres, '') Jur_adres
			,IFNULL(Fakt_adres, '') Fakt_adres
			,IFNULL(Telefony, '') Telefony
			,IFNULL(INN, '') INN
			,IFNULL(OKPO, '') OKPO
			,IFNULL(KPP, '') KPP
			,IFNULL(Pasport, '') Pasport
			,IFNULL(Email, '') Email
			,IFNULL(Schet, '') Schet
			,IFNULL(Bank, '') Bank
			,IFNULL(BIK, '') BIK
			,IFNULL(KS, '') KS
			,IFNULL(Bank_adres, '') Bank_adres
			,IFNULL(KA.saldo, 0) saldo
		FROM Kontragenty KA
		JOIN Shops SH ON SH.KA_ID = KA.KA_ID
		JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		".(in_array('sverki_city', $Rights) ? "AND CT.CT_ID = {$USR_City}" : "")."
		GROUP BY KA.KA_ID
		ORDER BY CT.City, KA.Naimenovanie
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$saldo_format = number_format($row["saldo"], 0, '', ' ');
		$KA_options .= "<option value='{$row["KA_ID"]}' CT_ID='{$row["CT_ID"]}'>{$row["City"]} | {$row["Naimenovanie"]} (Сальдо: {$saldo_format})</option>";
		$KA_IDs .= ",{$row["KA_ID"]}";
		$Kontragenty[$row["KA_ID"]] = array( "Naimenovanie"=>$row["Naimenovanie"], "Jur_adres"=>$row["Jur_adres"], "Fakt_adres"=>$row["Fakt_adres"], "Telefony"=>$row["Telefony"], "INN"=>$row["INN"], "OKPO"=>$row["OKPO"], "KPP"=>$row["KPP"], "Pasport"=>$row["Pasport"], "Email"=>$row["Email"], "Schet"=>$row["Schet"], "Bank"=>$row["Bank"], "BIK"=>$row["BIK"], "KS"=>$row["KS"], "Bank_adres"=>$row["Bank_adres"] );
	}
	$KA_options .= "</optgroup>";
}

// Список розничных контрагентов
$KA_options .=  "<optgroup label='Розничные покупатели:'>";
$query = "
	SELECT KA.KA_ID
		,CT.CT_ID
		,CT.City
		,KA.Naimenovanie
		,IFNULL(Jur_adres, '') Jur_adres
		,IFNULL(Fakt_adres, '') Fakt_adres
		,IFNULL(Telefony, '') Telefony
		,IFNULL(INN, '') INN
		,IFNULL(OKPO, '') OKPO
		,IFNULL(KPP, '') KPP
		,IFNULL(Pasport, '') Pasport
		,IFNULL(Email, '') Email
		,IFNULL(Schet, '') Schet
		,IFNULL(Bank, '') Bank
		,IFNULL(BIK, '') BIK
		,IFNULL(KS, '') KS
		,IFNULL(Bank_adres, '') Bank_adres
		,IFNULL(KA.saldo, 0) saldo
	FROM Kontragenty KA
	JOIN OrdersData OD ON OD.KA_ID = KA.KA_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	JOIN Cities CT ON CT.CT_ID = SH.CT_ID
	".(in_array('sverki_city', $Rights) ? "AND CT.CT_ID = {$USR_City}" : "")."
	GROUP BY KA.KA_ID, CT.CT_ID
	ORDER BY CT.City, KA.Naimenovanie
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$saldo_format = number_format($row["saldo"], 0, '', ' ');
	$KA_options .= "<option value='{$row["KA_ID"]}' CT_ID='{$row["CT_ID"]}'>{$row["City"]} | {$row["Naimenovanie"]} ({$saldo_format})</option>";
	$KA_IDs .= ",{$row["KA_ID"]}";
	$Kontragenty[$row["KA_ID"]] = array( "Naimenovanie"=>$row["Naimenovanie"], "Jur_adres"=>$row["Jur_adres"], "Fakt_adres"=>$row["Fakt_adres"], "Telefony"=>$row["Telefony"], "INN"=>$row["INN"], "OKPO"=>$row["OKPO"], "KPP"=>$row["KPP"], "Pasport"=>$row["Pasport"], "Email"=>$row["Email"], "Schet"=>$row["Schet"], "Bank"=>$row["Bank"], "BIK"=>$row["BIK"], "KS"=>$row["KS"], "Bank_adres"=>$row["Bank_adres"] );
}
$KA_options .= "</optgroup>";

if( isset($_GET["year"]) ) {
	$year = $_GET["year"];
}
else {
	$year = date('Y');
}
if ( $USR_KA ) { // Если пользователь - оптовик
	$payer = $USR_KA;
}
elseif ( $_GET["payer"] and (int)$_GET["payer"] > 0 ) {
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

// Обработка полученных данных из формы
if( isset($_GET["add_bill"]) ) {

	$_POST["tovar_kod_status"] = '1'; // Чтобы появилась колонка с кодом

	// Скрипт возвратит массив $orders_to_bill со списком кандидатов на добавление в счет
	$_GET["do"] = "bill";
	$_GET["KA_ID"] = $_POST["KA_ID"];
	$_GET["CT_ID"] = $_POST["CT_ID"];
	include "ajax.php";

	// Сохраняем цены и скидки изделий в OrdersDataDetail. Привязываем контрагента к набору
	foreach ($_POST["price"] as $key => $value) {
		$odd_id = $_POST["odd_id"][$key];
		$discount = ($_POST["discount"][$key] > 0) ? $_POST["discount"][$key] : "NULL";
		$odid = $_POST["odid"][$key];

		// Набор должен быть в списке кандидатов на добавление в счёт
		if( in_array($odid, $orders_to_bill) ) {
			$query = "UPDATE OrdersDataDetail SET Price = {$value}, discount = {$discount}, author = {$_SESSION["id"]} WHERE ODD_ID = {$odd_id}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		else {
			$_SESSION["error"][] = "При создании счёта возникла ошибка. Пожалуйста, повторите попытку.";
			exit ('<meta http-equiv="refresh" content="0; url=bills.php?year='.($year).'&payer='.($payer).'">');
			die;
		}
	}

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

	if( $_POST["KA_ID"] ) {
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
					WHERE KA_ID = {$_POST["KA_ID"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$platelshik_id = $_POST["KA_ID"];
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

	// Получаем номер очередного документа
	$year = date('Y');
	$query = "SELECT MAX(count)+1 count FROM PrintFormsBill WHERE YEAR(date) = {$year}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$count = mysqli_result($res,0,'count') ? mysqli_result($res,0,'count') : 1;

	// Сохраняем в POST необходимую информацию о покупателе
	$_POST["pokupatel"] = convert_str($_POST["platelshik_name"]);
	$_POST["pokupatel_adres"] = convert_str($_POST["platelshik_adres"]);
	$_POST["pokupatel_inn"] = convert_str($_POST["platelshik_inn"]);
	$_POST["pokupatel_kpp"] = convert_str($_POST["platelshik_kpp"]);

	// Сохраняем в таблицу информацию по счёту, узнаем его ID.
	$date = date( 'Y-m-d', strtotime($_POST["date"]) );
	$query = "INSERT INTO PrintFormsBill SET summa = {$_POST["summa"]}, discount = {$_POST["total_discount"]}, pokupatel_id = {$platelshik_id}, count = {$count}, date = '{$date}', USR_ID = {$_SESSION["id"]}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$id = mysqli_insert_id($mysqli);

	// Сохраняем у набора контрагента
	$id_list = "0";
	foreach ($_POST["ord"] as $key => $value) {
		$query = "
			UPDATE OrdersData OD
			JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			SET OD.KA_ID = IF(SH.KA_ID IS NULL, {$platelshik_id}, NULL), OD.author = {$_SESSION["id"]}
			WHERE OD.OD_ID = {$value}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$id_list .= ",".$value;
	}

	//Записываем в массив POST данные по товарам
	$query = "
		SELECT OD.OD_ID
			,OD.Code
			,ODD.ODD_ID
			,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
			,ODD.Amount
			,ODD.Price - IFNULL(ODD.discount, 0) Price
			,Zakaz(ODD.ODD_ID) Zakaz
		FROM OrdersData OD
		LEFT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE OD.OD_ID IN ({$id_list})
		ORDER BY OD.OD_ID, PTID DESC, ODD.ODD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$Counter = 0;
	while( $row = mysqli_fetch_array($res) ) {
		$_POST["tovar_name"][$Counter] = $row["Zakaz"];
		$_POST["tovar_kod"][$Counter] = $row["Code"];
		$_POST["tovar_ed"][$Counter] = "шт";
		$_POST["tovar_kol"][$Counter] = $row["Amount"];
		$_POST["tovar_cena"][$Counter] = $row["Price"];
		$Counter++;
	}

	$_POST["nomer"] = $count;

	// Информация о продавце
	$query = "SELECT * FROM Rekvizity WHERE R_ID = ".($_GET["CT_ID"] == 24 ? "3" : "1");
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

//	$_POST["seller"] = 2;
//	$_POST["schet_add_stamp_and_signatures"] = 1;

	$data = http_build_query($_POST);
	$referer = "https://service-online.su/forms/auto/ttn/";
	$headers = stream_context_create(array(
		'http' => array(
			'method' => 'POST',
			'header' => array(
//				'Cookie: login=sheff4uk%40gmail.com; password=68d9d2e6dd2d5655b85684d989c884eb',
				'Referer: https://service-online.su/forms/buh/schet/'
			),
			'content' => $data
		)
	));
	$out = file_get_contents('https://service-online.su/forms/buh/schet/blanc.php', false, $headers);
	$filename = 'schet_'.$id.'_'.$_POST["nomer"].'.pdf';
	file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере

	exit ('<meta http-equiv="refresh" content="0; url=bills.php?year='.($year).'&payer='.($payer).'">');
	die;
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
	$query = "
		SELECT YEAR(date) year FROM PrintFormsBill GROUP BY YEAR(date)
		UNION
		SELECT YEAR(NOW())
		ORDER BY year
	";
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
		// Выводим контрагента оптовика
		$query = "
			SELECT KA_ID, Naimenovanie, IFNULL(saldo, 0) saldo
			FROM Kontragenty
			WHERE KA_ID = {$USR_KA}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		$saldo_format = number_format($row["saldo"], 0, '', ' ');
		echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]} ({$saldo_format})</option>";
	}
	else {
		// Выводим дропдаун
		echo "<option value='0'>-=Все контрагенты=-</option>";
		echo $KA_options;
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

	#orders_to_bill input[type="number"] {
		width: 100%;
		text-align: right;
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
	echo "<div id='add_bill_btn' title='Создать счёт'></div>";
}
?>

<table>
	<thead>
		<tr>
			<th>Сумма</th>
			<th>Скидка</th>
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
				,Friendly_date(PFB.date) date_format
				,USR_Icon(PFB.USR_ID) Name
				,ROUND((PFB.discount / (PFB.summa + PFB.discount)) * 100, 1) discount
			FROM PrintFormsBill PFB
			LEFT JOIN Kontragenty KA ON KA.KA_ID = PFB.pokupatel_id
			WHERE YEAR(PFB.date) = {$year}
				AND KA.KA_ID IN ({$KA_IDs})
				".($payer ? "AND KA.KA_ID = {$payer}" : "")."
			ORDER BY PFB.PFB_ID DESC";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$summa = number_format($row["summa"], 0, '', ' ');
	$discount = ($row["discount"] != 0) ? "<b class='invoice_discount'>{$row["discount"]}%</b>" : "";
	echo "<tr>";
	echo "<td class='txtright nowrap' style='color: #16A085;'><b>{$summa}</b></td>";
	echo "<td class='txtright'>{$discount}</td>";
	echo "<td><b>{$row["date_format"]}</b></td>";
	echo "<td><a href='bills.php?year={$year}&payer={$row["KA_ID"]}'>{$row["pokupatel"]}</a></td>";
	echo "<td><b>{$row["count"]}</b></td>";
	echo "<td><b><a href='open_print_form.php?type=schet&PFB_ID={$row["PFB_ID"]}&number={$row["count"]}' target='_blank'><i class='fa fa-file-pdf fa-2x'></a></b></td>";
	echo "<td>{$row["Name"]}</td>";
	echo "</tr>";
}
?>
	</tbody>
</table>

<!-- Форма подготовки счёта -->
<div id='add_bill_form' style='display:none' title="Счёт на оплату">
	<h1>Счёт на оплату</h1>
	<form action="?add_bill&year=<?=$year?>&payer=<?=$payer?>" method="post" id="formdiv" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset id="wr_platelshik" style="text-align: left;">
			<legend>Информация о покупателе:</legend>
			<select name="KA_ID" id="kontragenty" style="width: 100%;">
				<?
				echo "<option value=''></option>";
				// Список регионов для добавления новых розничных контрагентов
				$query = "
					SELECT CT.CT_ID, CT.City
					FROM Cities CT
					WHERE CT.CT_ID IN (SELECT CT_ID FROM Shops WHERE retail = 1)
					".(in_array('sverki_city', $Rights) ? "AND CT.CT_ID = {$USR_City}" : "")."
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<option value='0' CT_ID='{$row["CT_ID"]}'>-- Новый покупатель из {$row["City"]} --</option>";
				}

				// Выводим дропдаун
				echo $KA_options;
				?>
			</select>
			<input type="hidden" name="CT_ID">
			<table width="100%" class="forms">
				<tbody>
					<tr>
						<td width="200" align="left" valign="top">Название ООО или ИП:</td>
						<td align="left" valign="top"><input type="text" required autocomplete="off" name="platelshik_name" id="platelshik_name" class="forminput"></td>
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

		<fieldset style="text-align: left;">
			<legend>Список наборов:</legend>
			<div id="orders_to_bill" style='text-align: left;'></div>
		</fieldset>

		<fieldset style="text-align: left;">
			<legend>Сообщение для клиента:</legend>
			<textarea name="text" class="comment">Внимание! Оплата данного счета означает согласие с условиями поставки товара. Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе. Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и паспорта.</textarea>
		</fieldset>

		<input name="n" type="hidden" value="1">

		<div>
			<hr>
			<h3 style="display: inline-block; margin: 10px;">Сумма счёта: <span id="bill_total" style="color: #16A085;"></span></h3>
			<h3 style="display: inline-block; margin: 10px;">Сумма скидки: <span id="bill_discount" style="color: #16A085;"></span></h3>
			<h3 style="display: inline-block; margin: 10px;">Процент скидки: <span id="bill_percent" style="color: #16A085;"></span></h3>
			<input type="hidden" name="summa" value="0">
			<input type="hidden" name="total_discount" value="0">
			<input type='submit' name="subbut" value='Создать счет' style='float: right;'>
			<input type="text" name="date" id="date" value="<?=date('d.m.Y')?>" class="date" style="float: right; margin: 4px 10px; width: 90px;" readonly>
		</div>
	</form>
</div>

<script>
	// Выбрать все в форме
	function selectall(ch) {
		$('#orders_to_bill .chbox').prop('checked', ch).change();
		$('#orders_to_bill #selectalltop').prop('checked', ch);
		$('#orders_to_bill #selectallbottom').prop('checked', ch);
		return false;
	}

	// Подсчет суммы счёта
	function bill_total() {
		var arr_price = Array
					.from(document.querySelectorAll('#orders_to_bill input[name="price[]"]')) // собираем массив из нод
					.map((item) => {
						var item_price = (item.getAttribute('disabled') == "disabled") ? 0 : item.value;
						var item_amount = item.getAttribute('amount');
						return item_price * item_amount // трансформируем массив в массив содержащий уже не ноды, а их содержимое
					})
					.map(Number); // приводим к числовому типу

		if (arr_price.length != 0) {
			var total_price = arr_price.reduce((sum, item) => {
				return sum+item; // считаем сумму массива
			});
		}
		else {
			var total_price = 0;
		}

		var arr_discount = Array
					.from(document.querySelectorAll('#orders_to_bill input[name="discount[]"]')) // собираем массив из нод
					.map((item) => {
						var item_price = (item.getAttribute('disabled') == "disabled") ? 0 : item.value;
						var item_amount = item.getAttribute('amount');
						return item_price * item_amount // трансформируем массив в массив содержащий уже не ноды, а их содержимое
					})
					.map(Number); // приводим к числовому типу

		if (arr_discount.length != 0) {
			var total_discount = arr_discount.reduce((sum, item) => {
				return sum+item; // считаем сумму массива
			});
		}
		else {
			var total_discount = 0;
		}

		var total = total_price - total_discount;
		$('input[name="summa"]').val(total);
		$('input[name="total_discount"]').val(total_discount);
		total_percent = (total_discount / total_price * 100).toFixed(1);
		total = total.format();
		total_discount = total_discount.format();
		$('#bill_total').html(total);
		$('#bill_discount').html(total_discount);
		$('#bill_percent').html(total_percent);
	}

	$(function() {
		// Деактивируем сабмит
		$('input[name="subbut"]').prop('disabled', true).button('refresh');

		// Обнуляем сумму счёта
		bill_total();

		// Деактивируем форму с информацией по контрагенту
		$('#wr_platelshik input').attr('disabled', true);

		// Массив контрагентов
		Kontragenty = <?= json_encode($Kontragenty); ?>;

		$('#orders_to_bill').html('<div class=\"lds-ripple\"><div></div><div></div></div>'); // Показываем спиннер

		// Форма составления счёта
		$('#add_bill_btn').click(function() {
			<?=($payer ? "$('select[name=\"KA_ID\"]').val('{$payer}').trigger('change');" : "")?>

			$('#add_bill_form').dialog({
				resizable: false,
				draggable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});
		});

		// Заполнение формы и динамическая подгрузка наборов при выборе контрагента
		$('select[name="KA_ID"]').on('change', function() {
			$('#wr_platelshik input').attr('disabled', false);
			var KA_ID = $(this).val();
			var CT_ID = $(this).find('option:selected').attr('CT_ID');
			$('input[name="CT_ID"]').val(CT_ID);
			if (KA_ID > 0) {
				var KA_data = Kontragenty[KA_ID];
				$('#platelshik_name').val(KA_data["Naimenovanie"]);
				$('#platelshik_inn').val(KA_data["INN"]);
				$('#platelshik_kpp').val(KA_data["KPP"]);
				$('#platelshik_okpo').val(KA_data["OKPO"]);
				$('#platelshik_adres').val(KA_data["Jur_adres"]);
				$('#platelshik_tel').val(KA_data["Telefony"]);
				$('#platelshik_schet').val(KA_data["Schet"]);
				$('#platelshik_bank').val(KA_data["Bank"]);
				$('#platelshik_bik').val(KA_data["BIK"]);
				$('#platelshik_ks').val(KA_data["KS"]);
				$('#platelshik_bank_adres').val(KA_data["Bank_adres"]);
				noty({timeout: 5000, text: 'ВНИМАНИЕ<br>Чтобы очистить форму - выберите в выпадающем меню "-- Новый покупатель из ..."', type: 'alert'});
			}
			else {
				$('#platelshik_name').val('');
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
				$("#kontragenty").val('');
				noty({timeout: 5000, text: 'ВНИМАНИЕ<br>Перед добавлением нового покупателя - пожалуйста, убедитесь, что его нет в списке контрагентов.', type: 'alert'});
			}
			$('#orders_to_bill').html('<div class=\"lds-ripple\"><div></div><div></div></div>'); // Показываем спиннер
			$.ajax({ url: "ajax.php?do=bill&KA_ID="+KA_ID+"&CT_ID="+CT_ID+"&from_js=1", dataType: "script", async: true });
		});

		// Обработчики чекбоксов в списке наборов
		$('#orders_to_bill').on('change', '#selectalltop', function(){
			ch = $('#selectalltop').prop('checked');
			selectall(ch);
			return false;
		});
		$('#orders_to_bill').on('change', '#selectallbottom', function(){
			ch = $('#selectallbottom').prop('checked');
			selectall(ch);
			return false;
		});
		$('#orders_to_bill').on('change', '.chbox', function(){
			var checked_status = true;
			var checked_status_submit = true;
			$('.chbox').each(function(){
				if( !$(this).prop('checked') ) {
					checked_status = $(this).prop('checked');
				}
				if( $(this).prop('checked') ) {
					checked_status_submit = !$(this).prop('checked');
				}
			});
			$('#selectalltop').prop('checked', checked_status);
			$('#selectallbottom').prop('checked', checked_status);
			$('input[name="subbut"]').prop('disabled', checked_status_submit).button('refresh');
			return false;
		});
		// Конец обработчиков чекбоксов

		// При включении чекбокса отображается инпут цены
		$('#orders_to_bill').on('change', '.chbox', function() {
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
		$('#orders_to_bill').on('change', 'input[type="number"]', function() {
			bill_total();
		});
		$('#orders_to_bill').on('change', '.chbox', function() {
			bill_total();
		});

		$('#payer').select2({ placeholder: 'Выберите контрагента', language: 'ru' });
		$('#kontragenty').select2({ placeholder: '-=контрагенты=-', language: 'ru' });

		// Костыль для Select2 чтобы работал поиск
		$.ui.dialog.prototype._allowInteraction = function (e) {
			return true;
		};

		$( "#date" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );
	});
</script>

<?
	include "footer.php";
?>
