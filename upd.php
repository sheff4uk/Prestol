<?php
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
		if( $shop == 36 ) {
			$query = "
				UPDATE OrdersDataDetail
				SET opt_price = ({$value} - IFNULL({$discount}, 0))
					,author = {$_SESSION["id"]}
				WHERE ODD_ID = {$odd_id}
			";
		}
		else {
			$query = "
				UPDATE OrdersDataDetail
				SET Price = {$value}
					,discount = {$discount}
					,author = {$_SESSION["id"]}
				WHERE ODD_ID = {$odd_id}
			";
		}
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	else {
		$_SESSION["error"][] = "При создании накладной возникла ошибка. Пожалуйста, повторите попытку.";
		exit ('<meta http-equiv="refresh" content="0; url=sverki.php?year='.($_GET["year"]).'&payer='.($_GET["payer"]).'">');
		die;
	}
}

// Обновляем информацию о покупателе
$pokupatel_name = mysqli_real_escape_string($mysqli, convert_str($_POST["pokupatel_name"]));
$pokupatel_adres = mysqli_real_escape_string($mysqli, convert_str($_POST["pokupatel_adres"]));
$pokupatel_inn = mysqli_real_escape_string($mysqli, convert_str($_POST["pokupatel_inn"]));
$pokupatel_kpp = mysqli_real_escape_string($mysqli, convert_str($_POST["pokupatel_kpp"]));

if( $_POST["KA_ID"] ) {
	$query = "
		UPDATE Kontragenty
		SET R_ID = {$_POST["R_ID"]}
			,Naimenovanie = '{$pokupatel_name}'
			,Jur_adres = IF('{$pokupatel_adres}' = '', NULL, '{$pokupatel_adres}')
			,INN = IF('{$pokupatel_inn}' = '', NULL, '{$pokupatel_inn}')
			,KPP = IF('{$pokupatel_kpp}' = '', NULL, '{$pokupatel_kpp}')
		WHERE KA_ID = {$_POST["KA_ID"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$platelshik_id = $_POST["KA_ID"];
}
else {
	$query = "
		INSERT INTO Kontragenty
		SET R_ID = {$_POST["R_ID"]}
			,Naimenovanie = '{$pokupatel_name}'
			,Jur_adres = IF('{$pokupatel_adres}' = '', NULL, '{$pokupatel_adres}')
			,INN = IF('{$pokupatel_inn}' = '', NULL, '{$pokupatel_inn}')
			,KPP = IF('{$pokupatel_kpp}' = '', NULL, '{$pokupatel_kpp}')
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$platelshik_id = mysqli_insert_id($mysqli);
}

// Если возвратная накладная, проверяем чтобы совпадала организация в накладных
if( $return ) {
	$alert_codes = "";
	foreach ($_POST["ord"] as $key => $value) {
		$query = "
			SELECT CONCAT(OD.Code, ', ') Code
			FROM PrintFormsInvoice PFI
			JOIN OrdersData OD ON OD.PFI_ID = PFI.PFI_ID AND OD.OD_ID = {$value}
			WHERE PFI.rtrn = 0
				AND PFI.R_ID != {$_POST["R_ID"]}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array( $res );
		$alert_codes .= $row["Code"];
	}
	if( $alert_codes <> "" ) {
		$_SESSION["error"][] = "При создании УПД возникла ошибка. У наборов {$alert_codes} отличается организация исходном УПД.";
		exit ('<meta http-equiv="refresh" content="0; url=sverki.php?year='.($_GET["year"]).'&payer='.($_GET["payer"]).'">');
		die;
	}
}

// Получаем номер документа
$year = date( 'Y', strtotime($_POST["date"]) );
$query = "SELECT MAX(count) + 1 count FROM PrintFormsInvoice WHERE YEAR(date) = {$year}";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$count = mysqli_result($res,0,'count') ? mysqli_result($res,0,'count') : 1;

// Сохраняем в таблицу PrintFormsInvoice данные по накладной
$date = date( 'Y-m-d', strtotime($_POST["date"]) );
$query = "
	INSERT INTO PrintFormsInvoice
	SET R_ID = {$_POST["R_ID"]}
		,summa = {$_POST["summa"]}
		,discount = {$_POST["total_discount"]}
		,platelshik_id = {$platelshik_id}
		,count = {$count}
		,date = '{$date}'
		,USR_ID = {$_SESSION["id"]}
		,rtrn = {$return}
";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$id = mysqli_insert_id($mysqli);

// Сохраняем у набора дату продажи и ID накладной
$id_list = "0";
foreach ($_POST["ord"] as $key => $value) {
	// Если возвратная накладная - помечаем исходные отгрузочные накладные как измененные (чтобы их нельзя было удалить)
	if( $return ) {
		$query = "
			UPDATE PrintFormsInvoice PFI
			JOIN OrdersData OD ON OD.PFI_ID = PFI.PFI_ID AND OD.OD_ID = {$value}
			SET PFI.rtrn = 2
			WHERE PFI.rtrn = 0
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	$query = "
		UPDATE OrdersData OD
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		SET OD.StartDate = IF((SH.KA_ID IS NULL AND OD.StartDate IS NOT NULL), OD.StartDate, ".($return ? "NULL" : "'{$date}'")."), OD.PFI_ID = {$id}, OD.author = {$_SESSION["id"]}
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
		#Исключение для Клена
		,IF(OD.SH_ID IN (36), ODD.opt_price, (ODD.Price - IFNULL(ODD.discount, 0))) Price
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
	$_POST["tovar_okei"][$Counter] = "796";
	$_POST["tovar_kol"][$Counter] = $row["Amount"];
	$_POST["tovar_cena"][$Counter] = $row["Price"];
	# НДС 5%
	if( $year >= 2025 ) {
		$_POST["tovar_nds"][$Counter] = "5";	
	}
	$Counter++;
}

$_POST["nomer"] = $count;
$_POST["status"] = "1";
$_POST["valyuta"] = "0";
$_POST["version"] = "20241001";

// Информация о продавце
$query = "
	SELECT *
	FROM Rekvizity
	WHERE R_ID = {$_POST["R_ID"]}
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
if( $row["prodavets_type"] == "ip" ) {
    $_POST["prodavets_type"] = $row["prodavets_type"];
    $_POST["prodavets_name_ip"] = $row["Name"];
    $_POST["prodavets_adres_ip"] = $row["Addres"];
    $_POST["prodavets_inn_ip"] = $row["INN"];
    $_POST["prodavets_svidetelstvo_ip"] = $row["svidetelstvo_ip"];
}
if( $row["prodavets_type"] == "ooo" ) {
    $_POST["prodavets_type"] = $row["prodavets_type"];
    $_POST["prodavets_name_ooo"] = $row["Name"];
    $_POST["prodavets_adres_ooo"] = $row["Addres"];
    $_POST["prodavets_inn_ooo"] = $row["INN"];
    $_POST["prodavets_kpp_ooo"] = $row["KPP"];
    $_POST["prodavets_director_ooo"] = $row["Dir"];
    $_POST["prodavets_buhgalter_ooo"] = $row["Dir"];
}
$_POST["gruzootpravitel"] = 1;

// Если накладная на возврат меняем грузоотправителя и получателя местами
if( $return ) {
	$_POST["prodavets_type"] = "ooo";

	$temp = $_POST["prodavets_name_ooo"];
	$_POST["prodavets_name_ooo"] = $_POST["pokupatel_name"];
	$_POST["pokupatel_name"] = $temp;

	$temp = $_POST["prodavets_adres_ooo"];
	$_POST["prodavets_adres_ooo"] = $_POST["pokupatel_adres"];
	$_POST["pokupatel_adres"] = $temp;

	$temp = $_POST["prodavets_inn_ooo"];
	$_POST["prodavets_inn_ooo"] = $_POST["pokupatel_inn"];
	$_POST["pokupatel_inn"] = $temp;

	$temp = $_POST["prodavets_kpp_ooo"];
	$_POST["prodavets_kpp_ooo"] = $_POST["pokupatel_kpp"];
	$_POST["pokupatel_kpp"] = $temp;

    $_POST["prodavets_director_ooo"] = '';
    $_POST["prodavets_buhgalter_ooo"] = '';
	$_POST["gruzopoluchatel"] = 1;
}
# НДС 5%
if( $year >= 2025 ) {
	$_POST["nds"] = 1;
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
		'header' => array('Referer: https://service-online.su/forms/buh/upd/'),
		'content' => $data
	)
));
$path = file_get_contents('https://service-online.su/forms/buh/upd/blanc.php', false, $headers);
$path = strstr($path, '/blank/');
$path = strstr($path, '.pdf', true);
$out = file_get_contents('https://service-online.su'.$path.'.pdf', false, null);
$filename = 'invoice_'.$id.'_'.$_POST["nomer"].'.pdf';
file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере

exit ('<meta http-equiv="refresh" content="0; url=sverki.php?year='.($year).'&payer='.($_GET["payer"]).'">');
die;

?>
