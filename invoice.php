<?
 include "config.php";
include "checkrights.php";
session_start();

// Узнаем тип накладной ОТГРУЗКА/ВОЗВРАТ
$return = $_POST["shipping_year"] ? 1 : 0;

// Скрипт возвратит массив $orders_to_bill со списком кандидатов на добавление в счет
$_GET["do"] = "invoice";
$_GET["KA_ID"] = $_POST["KA_ID"];
$_GET["CT_ID"] = $_POST["CT_ID"];
$_GET["shipping_year"] = $_POST["shipping_year"] ? $_POST["shipping_year"] : 0;
include "ajax.php";

// Сохраняем цены изделий в ODD
foreach ($_POST["price"] as $key => $value) {
	$odd_id = $_POST["odd_id"][$key];
	$discount = ($_POST["discount"][$key] > 0) ? $_POST["discount"][$key] : "NULL";
	$odid = $_POST["odid"][$key];

	// Набор должен быть в списке кандидатов на добавление в накладную
	if( in_array($odid, $orders_to_invoice) ) {
		// Исключение для Клена
		$query = "SELECT OD.SH_ID FROM OrdersData OD WHERE OD.OD_ID = {$odid}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$shop = mysqli_result($res,0,'SH_ID') ? mysqli_result($res,0,'SH_ID') : 1;

		// Если Клен - цену записываем в opt_price
		if ($shop == 36) {
			$query = "UPDATE OrdersDataDetail SET opt_price = ".($value - $discount).", author = {$_SESSION["id"]} WHERE ODD_ID = {$odd_id}";
		}
		else {
			$query = "UPDATE OrdersDataDetail SET Price = {$value}, discount = {$discount}, author = {$_SESSION["id"]} WHERE ODD_ID = {$odd_id}";
		}
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	else {
		$_SESSION["error"][] = "При создании накладной возникла ошибка. Пожалуйста, повторите попытку.";
		exit ('<meta http-equiv="refresh" content="0; url=sverki.php?year='.($_GET["year"]).'&payer='.($_GET["payer"]).'">');
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

// Получаем номер документа
$year = date( 'Y', strtotime($_POST["date"]) );
$query = "SELECT MAX(count) + 1 count FROM PrintFormsInvoice WHERE YEAR(date) = {$year}";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$count = mysqli_result($res,0,'count') ? mysqli_result($res,0,'count') : 1;

// Сохраняем в таблицу PrintFormsInvoice данные по накладной
$date = date( 'Y-m-d', strtotime($_POST["date"]) );
$query = "INSERT INTO PrintFormsInvoice SET summa = {$_POST["summa"]}, discount = {$_POST["total_discount"]}, platelshik_id = {$platelshik_id}, count = {$count}, date = '{$date}', USR_ID = {$_SESSION["id"]}, rtrn = {$return}";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$id = mysqli_insert_id($mysqli);

// Сохраняем у набора дату продажи и ID накладной
$id_list = "0";
foreach ($_POST["ord"] as $key => $value) {
	// Если возвратная накладная - помечаем исходные отгрузочные накладные как измененные (чтобы их нельзя было удалить)
	if( $return ) {
		$query = "UPDATE PrintFormsInvoice PFI
					JOIN OrdersData OD ON OD.PFI_ID = PFI.PFI_ID AND OD.OD_ID = {$value}
					SET PFI.rtrn = 2
					WHERE PFI.rtrn = 0";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	$query = "UPDATE OrdersData OD
				JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				SET OD.StartDate = IF((SH.KA_ID IS NULL AND OD.StartDate IS NOT NULL), OD.StartDate, ".($return ? "NULL" : "'{$date}'")."), OD.PFI_ID = {$id}, OD.author = {$_SESSION["id"]} WHERE OD.OD_ID = {$value}";
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
		#Исключение для Клена
		,IF(OD.SH_ID IN (36), ODD.opt_price, (ODD.Price - IFNULL(ODD.discount, 0))) Price
		,Zakaz(ODD.ODD_ID) Zakaz
		,ODD.boxes
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
	$_POST["tovar_okei"][$Counter] = "796";
	$_POST["tovar_kol"][$Counter] = $row["Amount"];
	$_POST["tovar_cena"][$Counter] = $row["Price"];
	$_POST["tovar_km"][$Counter] = $row["boxes"];
	$Counter++;
}

$_POST["nomer"] = $count;

// Информация о грузоотправителе
$query = "SELECT * FROM Rekvizity WHERE R_ID = ".($_GET["CT_ID"] == 24 ? "3" : "1");
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$_POST["gruzootpravitel_name"] = mysqli_result($res,0,'Name');
$_POST["gruzootpravitel_inn"] = mysqli_result($res,0,'INN');
$_POST["gruzootpravitel_kpp"] = mysqli_result($res,0,'KPP');
$_POST["gruzootpravitel_okpo"] = '';
$_POST["gruzootpravitel_adres"] = mysqli_result($res,0,'Addres');
$_POST["gruzootpravitel_director"] = mysqli_result($res,0,'Dir');
$_POST["gruzootpravitel_tel"] = mysqli_result($res,0,'Phone');
$_POST["gruzootpravitel_schet"] = mysqli_result($res,0,'RS');
$_POST["gruzootpravitel_bank"] = mysqli_result($res,0,'Bank');
$_POST["gruzootpravitel_bik"] = mysqli_result($res,0,'BIK');
$_POST["gruzootpravitel_ks"] = mysqli_result($res,0,'KS');
$_POST["postavshik"] = 1;

// Если накладная на возврат меняем грузоотправителя и получателя местами
if( $return ) {
	$platelshik_name = $_POST["platelshik_name"];
	$platelshik_inn = $_POST["platelshik_inn"];
	$platelshik_kpp = $_POST["platelshik_kpp"];
	$platelshik_okpo = $_POST["platelshik_okpo"];
	$platelshik_adres = $_POST["platelshik_adres"];
	$platelshik_tel = $_POST["platelshik_tel"];
	$platelshik_schet = $_POST["platelshik_schet"];
	$platelshik_bank = $_POST["platelshik_bank"];
	$platelshik_bik = $_POST["platelshik_bik"];
	$platelshik_ks = $_POST["platelshik_ks"];
	$platelshik_bank_adres = $_POST["platelshik_bank_adres"];

	$_POST["platelshik_name"] = $_POST["gruzootpravitel_name"];
	$_POST["platelshik_inn"] = $_POST["gruzootpravitel_inn"];
	$_POST["platelshik_kpp"] = $_POST["gruzootpravitel_kpp"];
	$_POST["platelshik_okpo"] = $_POST["gruzootpravitel_okpo"];
	$_POST["platelshik_adres"] = $_POST["gruzootpravitel_adres"];
	$_POST["platelshik_tel"] = $_POST["gruzootpravitel_tel"];
	$_POST["platelshik_schet"] = $_POST["gruzootpravitel_schet"];
	$_POST["platelshik_bank"] = $_POST["gruzootpravitel_bank"];
	$_POST["platelshik_bik"] = $_POST["gruzootpravitel_bik"];
	$_POST["platelshik_ks"] = $_POST["gruzootpravitel_ks"];
	$_POST["platelshik_bank_adres"] = $_POST["gruzootpravitel_bank_adres"];

	$_POST["gruzootpravitel_name"] = $platelshik_name;
	$_POST["gruzootpravitel_inn"] = $platelshik_inn;
	$_POST["gruzootpravitel_kpp"] = $platelshik_kpp;
	$_POST["gruzootpravitel_okpo"] = $platelshik_okpo;
	$_POST["gruzootpravitel_adres"] = $platelshik_adres;
	$_POST["gruzootpravitel_tel"] = $platelshik_tel;
	$_POST["gruzootpravitel_schet"] = $platelshik_schet;
	$_POST["gruzootpravitel_bank"] = $platelshik_bank;
	$_POST["gruzootpravitel_bik"] = $platelshik_bik;
	$_POST["gruzootpravitel_ks"] = $platelshik_ks;
	$_POST["gruzootpravitel_bank_adres"] = $platelshik_bank_adres;
	$_POST["gruzootpravitel_director"] = '';
}

// Удаляем старые файлы
$expire_time = 2*365*24*60*60; // Время через которое файл считается устаревшим (в сек.)
$dir = $_SERVER['DOCUMENT_ROOT']."/print_forms/";
// проверяем, что $dir - каталог
if (is_dir($dir)) {
	// открываем каталог
	if ($dh = opendir($dir)) {
		// читаем и выводим все элементы
		// от первого до последнего
		while (($file = readdir($dh)) !== false) {
			// текущее время
			$time_sec=time();
			// время изменения файла
			$time_file=filemtime($dir . $file);
			// тепрь узнаем сколько прошло времени (в секундах)
			$time=$time_sec-$time_file;

			$unlink = $dir.$file;

			if (is_file($unlink)){
				if ($time>$expire_time){
					unlink($unlink);
				}
			}
		}
		// закрываем каталог
		closedir($dh);
	}
}

$data = http_build_query($_POST);
$headers = stream_context_create(array(
	'http' => array(
		'method' => 'POST',
		'header' => array('Referer: https://service-online.su/forms/buh/tovarnaya-nakladnaya/'),
		'content' => $data
	)
));
$path = file_get_contents('https://service-online.su/forms/buh/tovarnaya-nakladnaya/blanc.php', false, $headers);
$path = strstr($path, '/blank/');
$path = strstr($path, '.pdf', true);
$out = file_get_contents('https://service-online.su'.$path.'.pdf', false, null);
$filename = 'invoice_'.$id.'_'.$_POST["nomer"].'.pdf';
file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере

exit ('<meta http-equiv="refresh" content="0; url=sverki.php?year='.($_GET["year"]).'&payer='.($_GET["payer"]).'">');
die;

?>
