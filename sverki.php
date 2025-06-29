<?php
include "config.php";

$title = 'Сверки';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Добавление оплаты от контрагента/редактирование оплаты
if( isset($_POST["Pay"]) ) {

	$Comment = convert_str($_POST["Comment"]);
	$Comment = mysqli_real_escape_string( $mysqli, $Comment );

	$money = abs($_POST["Pay"]);
	$category = $_POST["Pay"] >= 0 ? 9 : 8;

	// Редактирование
	if( $_POST["F_ID"] ) {
		$query = "
			UPDATE Finance
			SET money = {$money}
				,FA_ID = {$_POST["account"]}
				,FC_ID = {$category}
				,KA_ID = {$_POST["payer"]}
				,R_ID = {$_POST["R_ID"]}
				,comment = '{$Comment}'
				,author = {$_SESSION['id']}
			WHERE F_ID = {$_POST["F_ID"]}
		";
	}
	// Добавление
	else {
		$query = "
			INSERT INTO Finance
			SET money = {$money}
				,FA_ID = {$_POST["account"]}
				,FC_ID = {$category}
				,KA_ID = {$_POST["payer"]}
				,R_ID = {$_POST["R_ID"]}
				,comment = '{$Comment}'
				,author = {$_SESSION['id']}
		";
	}
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	exit ('<meta http-equiv="refresh" content="0; url='.$_POST["location"].'">');
	die;
}

$location = $_SERVER['REQUEST_URI'];

//Узнаем дефолтный счет для пользователя
$query = "SELECT FA_ID FROM FinanceAccount WHERE USR_ID = {$_SESSION['id']} ORDER BY IFNULL(bank, 0) LIMIT 1";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$account = mysqli_result($res,0,'FA_ID');

// Формируем список контрагентов для дропдауна
$KA_options = "";
$KA_IDs = "0";
if (!$USR_Shop) { // Если не продавец - показываем оптовиков
	$KA_options .= "<optgroup label='Оптовые покупатели:'>";
	$query = "
		SELECT KA.KA_ID
			,KA.R_ID
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
		FROM Kontragenty KA
		JOIN Shops SH ON SH.KA_ID = KA.KA_ID
		JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		".(in_array('sverki_city', $Rights) ? "AND CT.CT_ID = {$USR_City}" : "")."
		GROUP BY KA.KA_ID
		ORDER BY CT.City, KA.Naimenovanie
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$KA_options .= "<option value='{$row["KA_ID"]}' CT_ID='{$row["CT_ID"]}'>{$row["City"]} | {$row["Naimenovanie"]}</option>";
		$KA_IDs .= ",{$row["KA_ID"]}";
		$Kontragenty[$row["KA_ID"]] = array( "R_ID"=>$row["R_ID"], "Naimenovanie"=>$row["Naimenovanie"], "Jur_adres"=>$row["Jur_adres"], "Fakt_adres"=>$row["Fakt_adres"], "Telefony"=>$row["Telefony"], "INN"=>$row["INN"], "OKPO"=>$row["OKPO"], "KPP"=>$row["KPP"], "Pasport"=>$row["Pasport"], "Email"=>$row["Email"], "Schet"=>$row["Schet"], "Bank"=>$row["Bank"], "BIK"=>$row["BIK"], "KS"=>$row["KS"], "Bank_adres"=>$row["Bank_adres"] );
	}
	$KA_options .= "</optgroup>";
}

// Список розничных контрагентов
$KA_options .=  "<optgroup label='Розничные покупатели:'>";
$query = "
	SELECT KA.KA_ID
		,KA.R_ID
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
	FROM Kontragenty KA
	# Исключения для Клёна
	JOIN (SELECT SH_ID, IF(SH_ID = 36, 155, KA_ID) KA_ID FROM OrdersData) OD ON OD.KA_ID = KA.KA_ID
	#JOIN OrdersData OD ON OD.KA_ID = KA.KA_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	JOIN Cities CT ON CT.CT_ID = SH.CT_ID
	".(in_array('sverki_city', $Rights) ? "AND CT.CT_ID = {$USR_City}" : "")."
	GROUP BY KA.KA_ID, CT.CT_ID
	ORDER BY CT.City, KA.Naimenovanie
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$KA_options .= "<option value='{$row["KA_ID"]}' CT_ID='{$row["CT_ID"]}'>{$row["City"]} | {$row["Naimenovanie"]}</option>";
	$KA_IDs .= ",{$row["KA_ID"]}";
	$Kontragenty[$row["KA_ID"]] = array( "R_ID"=>$row["R_ID"], "Naimenovanie"=>$row["Naimenovanie"], "Jur_adres"=>$row["Jur_adres"], "Fakt_adres"=>$row["Fakt_adres"], "Telefony"=>$row["Telefony"], "INN"=>$row["INN"], "OKPO"=>$row["OKPO"], "KPP"=>$row["KPP"], "Pasport"=>$row["Pasport"], "Email"=>$row["Email"], "Schet"=>$row["Schet"], "Bank"=>$row["Bank"], "BIK"=>$row["BIK"], "KS"=>$row["KS"], "Bank_adres"=>$row["Bank_adres"] );
}
$KA_options .= "</optgroup>";

if( $_GET["year"] and (int)$_GET["year"] > 0 ) {
	$year = $_GET["year"];
}
else {
	$year = date('Y');
}
if ( $USR_KA ) { // Если пользователь - оптовик
	$payer = $USR_KA;
}
elseif ( $_GET["payer"] and (int)$_GET["payer"] > 0 ) {
//	$query = "SELECT 1 FROM Kontragenty WHERE KA_ID IN ({$KA_IDs}) AND KA_ID = {$_GET["payer"]}";
//	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//	if( mysqli_num_rows($res) ) {
		$payer = $_GET["payer"];
//	}
//	else {
//		die('Недостаточно прав для совершения операции');
//	}
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

	$query = "
		INSERT INTO ActSverki
		SET token = '{$hash}'
			,KA_ID = {$_POST["payer"]}
			,R_ID = {$_POST["R_ID"]}
			,date_from = '{$act_date_from}'
			,date_to = '{$act_date_to}'
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	exit ('<meta http-equiv="refresh" content="0; url=?year='.($_GET["year"]).'&payer='.($_GET["payer"]).'">');
	die;
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
<?php
	$query = "
		SELECT YEAR(date) year FROM PrintFormsInvoice GROUP BY YEAR(date)
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
<?php
	if( in_array('sverki_opt', $Rights) ) {
		// Выводим контрагента оптовика
		$query = "
			SELECT KA_ID, Naimenovanie
			FROM Kontragenty
			WHERE KA_ID = {$USR_KA}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]}</option>";
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
	#add_pay_btn {
		text-align: center;
		line-height: 68px;
		color: #fff;
		bottom: 175px;
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
		bottom: 60px;
		cursor: pointer;
		width: 36px;
		height: 36px;
		opacity: .4;
		position: fixed;
		right: 50px;
		z-index: 9;
		border-radius: 50%;
		background-color: #db4437;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}

	#add_act_sverki_btn {
		background: url(../img/print_forms.png) no-repeat scroll center center transparent;
		bottom: 250px;
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

	#add_invoice_btn:hover, #add_invoice_btn_return:hover, #add_act_sverki_btn:hover, #add_pay_btn:hover {
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

<?php
if( !in_array('sverki_opt', $Rights) ) {
	echo "<div id='add_invoice_btn' title='Создать накладную на ОТГРУЗКУ'></div>";
	echo "<div id='add_invoice_btn_return' title='Создать накладную на ВОЗВРАТ'></div>";
	if( $payer ) {
		echo "<div id='add_pay_btn' class='add_pay_btn' location='{$location}' title='Оплата от покупателя'><i class='fas fa-2x fa-money-bill-alt'></i></div>";
		echo "<div id='add_act_sverki_btn' title='Создать новый акт сверки' now_date='{$now_date}' payer='{$payer}'></div>";
	}
}

if( $payer ) {
	// Узнаем дефолтного продавца для покупателя
	$query = "
		SELECT KA.R_ID
		FROM Kontragenty KA
		WHERE KA.KA_ID = {$payer}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
	$R_ID = $row["R_ID"];

	echo "<h1>Акты сверок:</h1>";

	// Получаем список организаций, с которыми взаимодействовал контрагент
	$query = "
		SELECT R.R_ID
			,R.Name
			,IFNULL(KS.saldo, 0) saldo
		FROM KontragentySaldo KS
		JOIN Rekvizity R ON R.R_ID = KS.R_ID
		WHERE KS.KA_ID = {$payer}
	"; 
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$saldo = $row["saldo"];
		$saldo_format = number_format($row["saldo"], 0, '', ' ');

		echo "
			<table style='display: inline-block; margin-right: 30px;'>\n
				<thead>\n
					<tr>\n
						<th colspan='3'>{$row["Name"]}<br>Сальдо: <b style='color: ".(($saldo < 0) ? "#E74C3C;" : "#16A085;")."'>{$saldo_format}</b></th>\n
					</tr>\n
					<tr>\n
						<th>Дата</th>\n
						<th>Период</th>\n
						<th>Сальдо</th>\n
					</tr>\n
				</thead>\n
				<tbody>\n
		";
		$query = "
			SELECT token
				,Friendly_date(date_from) date_from_format
				,Friendly_date(date_to) date_to_format
				,date_to
			FROM ActSverki
			WHERE KA_ID = {$payer}
				AND R_ID = {$row["R_ID"]}
				AND YEAR(date_to) = {$year}
			ORDER BY date_to DESC
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $subrow = mysqli_fetch_array($subres) ) {
			// Вычисление оборота за период
			$query = "
				SELECT SUM(SUB.debet) debet
					,SUM(SUB.kredit) kredit
				FROM (
					SELECT IF(PFI.rtrn = 1, PFI.summa * -1, PFI.summa) debet
						,NULL kredit
					FROM PrintFormsInvoice PFI
					WHERE PFI.date > '{$subrow["date_to"]}'
						AND PFI.platelshik_id = {$payer}
						AND PFI.R_ID = {$row["R_ID"]}
						AND PFI.del = 0
	
					UNION ALL
	
					SELECT NULL debet
						,F.money * FC.type kredit
					FROM Finance F
					JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
					WHERE F.date > '{$subrow["date_to"]} 23:59:59'
						AND F.KA_ID = {$payer}
						AND F.R_ID = {$row["R_ID"]}
				) SUB
			";
			$subsubres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$debet_profit_now = mysqli_result($subsubres,0,'debet'); // Дебетовый оборот с конечной даты по сегодня
			$kredit_profit_now = mysqli_result($subsubres,0,'kredit'); // Кредитовый оборот с конечной даты по сегодня
	
			$end_saldo = $saldo + $debet_profit_now - $kredit_profit_now;
			$end_saldo_format = number_format($end_saldo, 0, '', ' ');
	
			echo "<tr>";
			echo "<td><b><a href='/toprint/act_sverki.php?t={$subrow["token"]}' target='_blank'>{$subrow["date_to_format"]}</a></b></td>";
			echo "<td>[{$subrow["date_from_format"]} - {$subrow["date_to_format"]}]</td>";
			echo "<td><b style='color: ".(($end_saldo < 0) ? "#E74C3C;" : "#16A085;")."'>{$end_saldo_format}</b></td>";
			echo "</tr>";
		}
		echo "
				</tbody>\n
			</table>\n
		";
	}

	echo "<h1>Журнал операций:</h1>";
}
?>

<table>
	<thead>
		<tr>
			<th>Дебет</th>
			<th>Кредит</th>
			<th>Скидка</th>
			<th>Дата</th>
			<th>Покупатель</th>
			<th>Продавец</th>
			<th>Операция/Документ</th>
<!--			<th>Примечание</th>-->
			<th>Файл</th>
			<th>Автор</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php
if( $payer ) {
	$query = "
		SELECT PFI.PFI_ID
			,IF(PFI.rtrn = 1, PFI.summa * -1, PFI.summa) debet
			,NULL kredit
			,KA.KA_ID
			,KA.Naimenovanie
			,R.Name seller
			,R.R_ID
			,IF(PFI.rtrn = 1, CONCAT('Возврат товара, накладная <b>№', PFI.count, '</b>'), CONCAT('Реализация, накладная <b>№', PFI.count, '</b>')) document
			,PFI.count
			,Friendly_date(PFI.date) date_format
			,PFI.date
			,USR_Icon(PFI.USR_ID) Name
			,PFI.del
			,PFI.rtrn
			,PFI.comment
			,ROUND((PFI.discount / (PFI.summa + PFI.discount)) * 100, 1) discount
			,NULL Account
			,NULL FA_ID
			,NULL color
			,NULL F_ID
			,NULL comment
		FROM PrintFormsInvoice PFI
		JOIN Kontragenty KA ON KA.KA_ID = PFI.platelshik_id
			AND KA.KA_ID = {$payer}
		JOIN Rekvizity R ON R.R_ID = PFI.R_ID
		WHERE YEAR(PFI.date) = {$year}

		UNION ALL

		SELECT NULL
			,NULL debet
			,F.money * FC.type kredit
			,KA.KA_ID
			,KA.Naimenovanie
			,R.Name seller
			,R.R_ID
			,CONCAT('Оплата от покупателя <b>', IFNULL(F.comment, ''), '</b>') document
			,NULL
			,Friendly_date(F.date) date_format
			,F.date
			,USR_Icon(F.author) Name
			,NULL
			,1
			,NULL
			,0
			,FA.name
			,FA.FA_ID
			,FA.color
			,F.F_ID
			,F.comment
		FROM Finance F
		JOIN FinanceAccount FA ON FA.FA_ID = F.FA_ID
		JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
		JOIN Kontragenty KA ON KA.KA_ID = F.KA_ID
			AND KA.KA_ID = {$payer}
		JOIN Rekvizity R ON R.R_ID = F.R_ID
		WHERE YEAR(F.date) = {$year}
		AND F.money != 0

		ORDER BY date DESC, PFI_ID DESC
	";
}
else {
	$query = "
		SELECT PFI.PFI_ID
			,IF(PFI.rtrn = 1, PFI.summa * -1, PFI.summa) debet
			,NULL kredit
			,KA.KA_ID
			,KA.Naimenovanie
			,R.Name seller
			,IF(PFI.rtrn = 1, CONCAT('Возврат товара, накладная <b>№', PFI.count, '</b>'), CONCAT('Реализация, накладная <b>№', PFI.count, '</b>')) document
			,PFI.count
			,Friendly_date(PFI.date) date_format
			,PFI.date
			,USR_Icon(PFI.USR_ID) Name
			,PFI.del
			,PFI.rtrn
			,PFI.comment
			,ROUND((PFI.discount / (PFI.summa + PFI.discount)) * 100, 1) discount
			,NULL Account
			,NULL color
		FROM PrintFormsInvoice PFI
		JOIN Kontragenty KA ON KA.KA_ID = PFI.platelshik_id
			AND KA.KA_ID IN ({$KA_IDs})
		JOIN Rekvizity R ON R.R_ID = PFI.R_ID
		WHERE YEAR(PFI.date) = {$year}
		ORDER BY PFI.date DESC, PFI.PFI_ID DESC
	";
}
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$debet = ($row["debet"] != '') ? number_format($row["debet"], 0, '', ' ') : '';
	$kredit = ($row["kredit"] != '') ? number_format($row["kredit"], 0, '', ' ') : '';
	$discount = ($row["discount"] != 0) ? "<b class='invoice_discount'>{$row["discount"]}%</b>" : "";
	echo "<tr ".($row["del"] ? "class='del'" : "").">";
	echo "<td class='txtright nowrap'><b style='color: ".($row["debet"] < 0 ? "#E74C3C;" : "#16A085;")."'>{$debet}</b></td>";
	echo "<td class='txtright nowrap'><b style='color: ".($row["kredit"] < 0 ? "#E74C3C;" : "#16A085;")."'>{$kredit}</b><br><span style='font-size: .8em; font-weight: bold; background: {$row["color"]};'>{$row["Account"]}</span></td>";
	echo "<td class='txtright'>{$discount}</td>";
	echo "<td><b>{$row["date_format"]}</b></td>";
	echo "<td><a href='sverki.php?year={$year}&payer={$row["KA_ID"]}'>{$row["Naimenovanie"]}</a></td>";
	echo "<td>{$row["seller"]}</td>";
	echo "<td>{$row["document"]}</td>";

//	if( $row["PFI_ID"] and !in_array('sverki_opt', $Rights) ) {
//		echo "<td id='{$row["PFI_ID"]}'><input class='sverki_comment' type='text' value='{$row["comment"]}'></td>";
//	}
//	else {
//		echo "<td>{$row["comment"]}</td>";
//	}

	if( $row["PFI_ID"] ) {
		echo "<td><a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'><i class='fa fa-file-pdf fa-2x'></i></a></td>";
	}
	else {
		echo "<td></td>";
	}

	echo "<td>{$row["Name"]}</td>";

	if( $row["del"] == "0" and $row["rtrn"] == "0" and !in_array('sverki_opt', $Rights) ) {
		echo "<td><button class='del_invoice' pfi_id='{$row["PFI_ID"]}' count='{$row["count"]}' title='Удалить'><i class='fa fa-times fa-lg'></i></button></td>";
	}
	elseif( $row["FA_ID"] ) {
		echo "<td><a href='#' class='add_pay_btn' location='{$location}' F_ID='{$row["F_ID"]}' FA_ID='{$row["FA_ID"]}' R_ID='{$row["R_ID"]}' Pay='{$row["kredit"]}' Comment='{$row["comment"]}'><i class='fa fa-pencil-alt fa-lg'></i></a></td>";
	}
	else {
		echo "<td></td>";
	}
	echo "</tr>";
}
?>
	</tbody>
</table>

<script>
	$(function() {
		// Удаление накладной
		$('.del_invoice').on('click', function() {
			var count = $(this).attr('count');
			var pfi_id = $(this).attr('pfi_id');
			confirm("Удалить накладную <b>№"+count+"</b>?", "?del="+pfi_id+"&year=<?=$year?>&payer=<?=$payer?>");
			return false;
		});
	});
</script>

<!-- Форма подготовки УПД -->
<div id='add_invoice_form' style='display:none'>
	<form method='post' action="upd.php?year=<?=$year?>&payer=<?=$payer?>" onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<div>
			<fieldset id="wr_platelshik" style="text-align: left;">
				<legend id="KA_info"></legend>
				<select name="KA_ID" id="kontragenty" style="width: 100%;">
					<?php
					echo "<option value=''></option>";
					// Выводим дропдаун
					echo $KA_options;
					?>
				</select>
				<input type="hidden" name="CT_ID">
				<table width="100%" class="forms">
					<tbody>
						<tr>
							<td width="200" align="left" valign="top">Название ООО или ФИО ИП:</td>
							<td align="left" valign="top">
								<input required type="text" autocomplete="off" name="pokupatel_name" id="pokupatel_name" class="forminput" placeholder="">
							</td>
						</tr>
						<tr>
							<td align="left" valign="top">Адрес:</td>
							<td align="left" valign="top"><input type="text" autocomplete="off" name="pokupatel_adres" id="pokupatel_adres" class="forminput" placeholder=""></td>
						</tr>
						<tr>
							<td align="left" valign="top">ИНН:</td>
							<td align="left" valign="top"><input type="text" autocomplete="off" name="pokupatel_inn" id="pokupatel_inn" class="forminput" placeholder=""></td>
						</tr>
						<tr>
							<td align="left" valign="top">КПП:</td>
							<td align="left" valign="top"><input type="text" autocomplete="off" name="pokupatel_kpp" id="pokupatel_kpp" class="forminput" placeholder=""></td>
						</tr>
					</tbody>
				</table>
			</fieldset>
			<br>
			<fieldset>
				<table style="width: 100%" border="0" cellspacing="4" class="forms">
					<tbody>
						<tr>
							<td>Продавец:</td>
							<td valign="top">
								<select name="R_ID" id="R_ID" required>
									<option value=""></option>
									<?php
									$query = "SELECT R_ID, Name FROM Rekvizity";

									$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
									while( $row = mysqli_fetch_array($res) ) {
										echo "<option value='{$row["R_ID"]}'>{$row["Name"]}</option>";
									}
									?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</fieldset>
			<br>
			<fieldset id="wr_gruzopoluchatel" style="text-align: left;">
				<legend>Информация о грузополучателе:</legend>
				<table width="100%" class="forms">
					<tbody>
						<tr class="forms">
							<td width="200" align="left" valign="top">Грузополучатель:</td>
							<td valign="top" class="btnset">
								<input type="radio" name="gruzopoluchatel" value="1" id="gruzopoluchatel_1">
								<label for="gruzopoluchatel_1">Покупатель</label>
								<input type="radio" name="gruzopoluchatel" value="2" id="gruzopoluchatel_2">
								<label for="gruzopoluchatel_2">Сторонняя организация</label>
							</td>
						</tr>
					</tbody>
				</table>
				<table width="100%" class="forms" id="gruzopoluchatel2">
					<tbody>
						<tr>
							<td width="200" align="left" valign="top">Название грузополучателя:</td>
							<td align="left" valign="top">
								<input type="text" name="gruzopoluchatel_name" id="gruzopoluchatel_name" class="forminput" placeholder="" autocomplete="off">
							</td>
						</tr>
						<tr>
							<td align="left" valign="top">ИНН:</td>
							<td align="left" valign="top"><input type="text" name="gruzopoluchatel_inn" id="gruzopoluchatel_inn" class="forminput" placeholder="" autocomplete="off"></td>
						</tr>
						<tr>
							<td align="left" valign="top">КПП:</td>
							<td align="left" valign="top"><input type="text" name="gruzopoluchatel_kpp" id="gruzopoluchatel_kpp" class="forminput" placeholder="" autocomplete="off"></td>
						</tr>
						<tr>
							<td align="left" valign="top">Адрес:</td>
							<td align="left" valign="top"><input type="text" name="gruzopoluchatel_adres" id="gruzopoluchatel_adres" class="forminput" placeholder="" autocomplete="off"></td>
						</tr>
					</tbody>
				</table>
			</fieldset>
			<br>
			<fieldset>
				<table width="100%" class="forms">
					<tbody>
						<tr>
							<td width="200" align="left">Основание передачи / получения:</td>
							<td align="left">
								<input type="text" name="osnovanie[]" id="osnovanie" style="width: 490px;" placeholder="" value="" autocomplete="off">&nbsp;
								<input type="text" name="osnovanie_nomer[]" id="osnovanie_nomer" style="width: 100px;" placeholder="номер" value="" autocomplete="off">&nbsp;
								<input type="text" name="osnovanie_date[]" id="osnovanie_date" class="date" style="width: 90px;" placeholder="дата" value="" autocomplete="off">
							</td>
						</tr>
					</tbody>
				</table>
			</fieldset>
		</div>
		<div id="shipping_year" style="display: none;">
			Год отгрузки
			<select name="shipping_year">
			<?php
				$query = "
					SELECT YEAR(date) year FROM PrintFormsInvoice GROUP BY YEAR(date)
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
		</div>
		<fieldset style="text-align: left;">
			<legend>Список наборов:</legend>
			<div id="orders_to_invoice" style='text-align: left;'></div>
		</fieldset>
		<br>
		<div>
			<hr>
			<h3 style="display: inline-block; margin: 10px;">Сумма накладной: <span id="invoice_total" style="color: #16A085;"></span></h3>
			<h3 style="display: inline-block; margin: 10px;">Сумма скидки: <span id="invoice_discount" style="color: #16A085;"></span></h3>
			<h3 style="display: inline-block; margin: 10px;">Процент скидки: <span id="invoice_percent" style="color: #16A085;"></span></h3>
			<input type="hidden" name="summa" value="0">
			<input type="hidden" name="total_discount" value="0">
			<input type='submit' name="subbut" id="invoice_subbut" value='Создать накладную' style='float: right;'>
			<input type="text" name="date" id="date" class="date" style="float: right; margin: 4px 10px; width: 90px;" readonly>
		</div>
		<h3 id="return_message" style="color: #911; display: none;">ВНИМАНИЕ! Накладную на возврат товара отменить не возможно.</h3>
	</form>
</div>

<!-- Форма подготовки акта сверки -->
<div id='add_act_sverki_form' style='display:none' title="Акт сверки">
	<form method='post' action="?add_act=1&year=<?=$year?>&payer=<?=$payer?>" onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<div>
				Период:&nbsp;[&nbsp;
				<input type="text" name="act_date_from" required class="date from" autocomplete="off">
				&nbsp;-&nbsp;
				<input type="text" name="act_date_to" required class="date to" autocomplete="off">
				&nbsp;]
				<input type="hidden" name="payer">
				<br>
				<br>
				Продавец:&nbsp;
				<select name="R_ID" required>
					<option value=""></option>
					<?php
					if( $payer ) {
						// Получаем список организаций, с которыми взаимодействовал контрагент
						$query = "
							SELECT R.R_ID
								,R.Name
							FROM KontragentySaldo KS
							JOIN Rekvizity R ON R.R_ID = KS.R_ID
							WHERE KS.KA_ID = {$payer}
						"; 
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["R_ID"]}'>{$row["Name"]}</option>\n";
						}
					}
					?>
				</select>
			</div>
			<div>
				<hr>
				<input type='submit' name="subbut" value='Создать акт сверки' style='float: right;'>
			</div>
		</fieldset>
	</form>
</div>
<!-- Конец формы подготовки акта сверки -->

<!-- Форма добавления платежа -->
<div id='addpay' class="addproduct" style='display:none' title='Внесение оплаты'>
	<form method="post" onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type='hidden' name='F_ID'>
			<input type='hidden' name='location' value='<?=$location?>'>
			<input type='hidden' name='payer' value='<?=$payer?>'>
			<div>
				<label>Сумма:</label>
				<input required type='number' name='Pay' style="text-align:right; width: 100px; font-size: 20px;">
			</div>
			<div id="wr_account">
				<label>Счёт:</label>
				<select name="account" id="account" required>
					<option value="">-=Выберите счёт=-</option>
						<?php
						if( !in_array('finance_account', $Rights) ) {
							echo "<optgroup label='Нал'>";
							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 0 AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}' ".($row["FA_ID"] == $account ? "selected" : "").">{$row["name"]}</option>";
							}
							echo "</optgroup>";
							echo "<optgroup label='Безнал'>";

							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 1 AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}' ".($row["FA_ID"] == $account ? "selected" : "").">{$row["name"]}</option>";
							}
							echo "</optgroup>";
						}
						else {
							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE USR_ID = {$_SESSION["id"]} AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}' ".($row["FA_ID"] == $account ? "selected" : "").">{$row["name"]}</option>";
							}
						}
						?>
				</select>
			</div>
			<div>
			<br>
				<label>Продавец:</label>
				<select name="R_ID" required>
					<option value=""></option>
					<?php
					if( $payer ) {
						// Получаем список организаций, с которыми взаимодействовал контрагент
						$query = "
							SELECT R.R_ID
								,R.Name
							FROM KontragentySaldo KS
							JOIN Rekvizity R ON R.R_ID = KS.R_ID
							WHERE KS.KA_ID = {$payer}
						"; 
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["R_ID"]}'>{$row["Name"]}</option>\n";
						}
					}
					?>
				</select>
			</div>
			<div>
				<label>Примечание:</label>
				<input type='text' name='Comment' style="width: 100%;" autocomplete="off">
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!-- Конец формы добавления платежа -->

<script>
	// Выбрать все в форме отгрузки
	function selectall(ch) {
		$('#orders_to_invoice .chbox').prop('checked', ch).change();
		$('#orders_to_invoice #selectalltop').prop('checked', ch);
		$('#orders_to_invoice #selectallbottom').prop('checked', ch);
		return false;
	}

	// Подсчет суммы накладной
	function invoice_total() {
		var arr_price = Array
					.from(document.querySelectorAll('#orders_to_invoice input[name="price[]"]')) // собираем массив из нод
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
					.from(document.querySelectorAll('#orders_to_invoice input[name="discount[]"]')) // собираем массив из нод
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
		$('#invoice_total').html(total);
		$('#invoice_discount').html(total_discount);
		$('#invoice_percent').html(total_percent);
	}

	$(function() {

//		// Редактирование примечания к накладной
//		$('.sverki_comment').on('change', function() {
//			var PFI_ID = $(this).parents('td').attr('id');
//			var val = $(this).val();
//			$.ajax({ url: "ajax.php?do=update_sverki_comment&PFI_ID="+PFI_ID+"&sverki_comment="+val, dataType: "script", async: false });
//		});

		// Деактивируем форму с информацией по контрагенту
		$('#wr_platelshik input').attr('disabled', true);

		// Массив контрагентов
		Kontragenty = <?= json_encode($Kontragenty); ?>;

		// Форма внесения оплаты от контрагента/редактирование оплаты
		$('.add_pay_btn').click( function() {
			var FA_ID = $(this).attr('FA_ID');

			// Очистка диалога
			$('#addpay input[name="F_ID"]').val('');
			$('#addpay input[name="Pay"]').val('');
			$('#addpay select[name="account"]').val('');
			$('#addpay select[name="R_ID"]').val(<?=$R_ID?>);
			$('#addpay input[name="Comment"]').val('');

			if( FA_ID > 0 ) {
				var F_ID = $(this).attr('F_ID'),
					R_ID = $(this).attr('R_ID'),
					Pay = $(this).attr('Pay'),
					Comment = $(this).attr('Comment');
				$('#addpay input[name="F_ID"]').val(F_ID);
				$('#addpay input[name="Pay"]').val(Pay);
				$('#addpay select[name="account"]').val(FA_ID);
				$('#addpay select[name="R_ID"]').val(R_ID);
				$('#addpay input[name="Comment"]').val(Comment);
			}

			// Вызов формы
			$('#addpay').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});
			return false;
		});

		// Форма составления накладной
		$('#add_invoice_btn, #add_invoice_btn_return').click(function() {

			// Узнаём какая из 2-х кнопок была нажата
			var this_id = $(this).attr('id');
			var title;
			if( this_id == 'add_invoice_btn' ) {
				title = 'Накладная на ОТГРУЗКУ';
				$('#KA_info').html('Информация о плательщике:');
				$('#shipping_year').hide();
				$('#shipping_year select').attr('disabled', true);
				$('#return_message').hide();
				$('#wr_gruzopoluchatel').show();
			}
			else {
				title = 'Накладная на ВОЗВРАТ'
				$('#KA_info').html('Информация о грузоотправителе:');
				$('#shipping_year').show();
				$('#shipping_year select').attr('disabled', false);
				$('#return_message').show();
				$('#wr_gruzopoluchatel').hide();
			}
			// Очистка
			$('select[name="KA_ID"]').val('').change();
			$('#gruzopoluchatel2 input').val('');
			$('#gruzopoluchatel_1').prop('checked', true).button('refresh').change();
			$('#date').val('<?=( date('d.m.Y') )?>');
			$('#shipping_year select').val(<?=date('Y')?>);
			$('#invoice_subbut').prop('disabled', true).button('refresh');
			// Деактивируем форму с информацией по контрагенту
			$('#wr_platelshik input').attr('disabled', true);
			<?=($payer ? "$('select[name=\"KA_ID\"]').val('{$payer}').trigger('change');" : "")?>
			invoice_total();

			$('#add_invoice_form').dialog({
				title: title,
				resizable: false,
				draggable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});
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
			$('#add_act_sverki_form select[name="R_ID"]').val(<?=$R_ID?>);

			$('#add_act_sverki_form').dialog({
				resizable: false,
				draggable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});
		});

		// При выборе сторонней организации отображается форма грузополучателя
		$('#wr_gruzopoluchatel input[name=gruzopoluchatel]').on('change', function() {
			if ($(this).val() == 1){
				$('#gruzopoluchatel2').hide('fast');
			}
			else{
				$('#gruzopoluchatel2').show('fast');
			}
		});

		// Заполнение формы и динамическая подгрузка наборов при выборе контрагента
		$('select[name="KA_ID"]').on('change', function() {
			$('#wr_platelshik input').attr('disabled', false);
			var KA_ID = $(this).val();
			var CT_ID = $(this).find('option:selected').attr('CT_ID');
			if( $('#shipping_year select').is(':enabled') ) {
				var shipping_year = $('#shipping_year select').val();
			}
			else {
				var shipping_year = 0;
			}
			$('input[name="CT_ID"]').val(CT_ID);
			if (KA_ID > 0) {
				var KA_data = Kontragenty[KA_ID];
				$('#R_ID').val(KA_data["R_ID"]);
				$('#pokupatel_name').val(KA_data["Naimenovanie"]);
				$('#pokupatel_adres').val(KA_data["Jur_adres"]);
				$('#pokupatel_inn').val(KA_data["INN"]);
				$('#pokupatel_kpp').val(KA_data["KPP"]);
				// $('#platelshik_name').val(KA_data["Naimenovanie"]);
				// $('#platelshik_inn').val(KA_data["INN"]);
				// $('#platelshik_kpp').val(KA_data["KPP"]);
				// $('#platelshik_okpo').val(KA_data["OKPO"]);
				// $('#platelshik_adres').val(KA_data["Jur_adres"]);
				// $('#platelshik_tel').val(KA_data["Telefony"]);
				// $('#platelshik_schet').val(KA_data["Schet"]);
				// $('#platelshik_bank').val(KA_data["Bank"]);
				// $('#platelshik_bik').val(KA_data["BIK"]);
				// $('#platelshik_ks').val(KA_data["KS"]);
				// $('#platelshik_bank_adres').val(KA_data["Bank_adres"]);
			}
			else {
				$('#R_ID').val('');
				$('#pokupatel_name').val('');
				$('#pokupatel_adres').val('');
				$('#pokupatel_inn').val('');
				$('#pokupatel_kpp').val('');
				// $('#platelshik_name').val('');
				// $('#platelshik_inn').val('');
				// $('#platelshik_kpp').val('');
				// $('#platelshik_okpo').val('');
				// $('#platelshik_adres').val('');
				// $('#platelshik_tel').val('');
				// $('#platelshik_schet').val('');
				// $('#platelshik_bank').val('');
				// $('#platelshik_bik').val('');
				// $('#platelshik_ks').val('');
				// $('#platelshik_bank_adres').val('');
				$("#kontragenty").val('');
			}
			if (CT_ID) {
				$('#orders_to_invoice').html('<div class=\"lds-ripple\"><div></div><div></div></div>'); // Показываем спиннер
				$.ajax({ url: "ajax.php?do=invoice&KA_ID="+KA_ID+"&CT_ID="+CT_ID+"&shipping_year="+shipping_year+"&from_js=1", dataType: "script", async: true });
			}
			else {
				$('#orders_to_invoice').html('<div class=\"lds-ripple\"><div></div><div></div></div>'); // Показываем спиннер
			}
		});

		// При выборе года отгрузки, вызываем аякс для подгрузки наборов
		$('#shipping_year select').on('change', function() {
			var KA_ID = $('select[name="KA_ID"]').val();
			var CT_ID = $('select[name="KA_ID"]').find('option:selected').attr('CT_ID');
			if( $('#shipping_year select').is(':enabled') ) {
				var shipping_year = $('#shipping_year select').val();
			}
			else {
				var shipping_year = 0;
			}
			$('#orders_to_invoice').html('<div class=\"lds-ripple\"><div></div><div></div></div>'); // Показываем спиннер
			$.ajax({ url: "ajax.php?do=invoice&KA_ID="+KA_ID+"&CT_ID="+CT_ID+"&shipping_year="+shipping_year+"&from_js=1", dataType: "script", async: false });
		});

		// Обработчики чекбоксов в списке наборов
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
			$('#invoice_subbut').prop('disabled', checked_status_submit).button('refresh');
			return false;
		});
		// Конец обработчиков чекбоксов

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

//		// Автокомплит плательщика
//		$( "#platelshik_name" ).autocomplete({
//			source: "kontragenty.php",
//			minLength: 2,
//			autoFocus: true,
//			select: function( event, ui ) {
//				$('#platelshik_id').val(ui.item.id);
//				$('#platelshik_inn').val(ui.item.INN);
//				$('#platelshik_kpp').val(ui.item.KPP);
//				$('#platelshik_okpo').val(ui.item.OKPO);
//				$('#platelshik_adres').val(ui.item.Jur_adres);
//				$('#platelshik_tel').val(ui.item.Telefony);
//				$('#platelshik_schet').val(ui.item.Schet);
//				$('#platelshik_bank').val(ui.item.Bank);
//				$('#platelshik_bik').val(ui.item.BIK);
//				$('#platelshik_ks').val(ui.item.KS);
//				$('#platelshik_bank_adres').val(ui.item.Bank_adres);
//			}
//		});
//
//		$( "#platelshik_name" ).on("keyup", function() {
//			if( $( "#platelshik_name" ).val().length < 2 ) {
//				$('#platelshik_id').val('');
//				$('#platelshik_inn').val('');
//				$('#platelshik_kpp').val('');
//				$('#platelshik_okpo').val('');
//				$('#platelshik_adres').val('');
//				$('#platelshik_tel').val('');
//				$('#platelshik_schet').val('');
//				$('#platelshik_bank').val('');
//				$('#platelshik_bik').val('');
//				$('#platelshik_ks').val('');
//				$('#platelshik_bank_adres').val('');
//			}
//		});

		$('#payer').select2({ placeholder: 'Выберите контрагента', language: 'ru' });
		$('#kontragenty').select2({ placeholder: '-=контрагенты=-', language: 'ru' });

		// Костыль для Select2 чтобы работал поиск
		$.ui.dialog.prototype._allowInteraction = function (e) {
			return true;
		};

		$( "#date" ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );
	});
</script>

<?php
	include "footer.php";
?>
