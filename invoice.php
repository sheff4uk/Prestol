<?
include "config.php";
session_start();

// Узнаем тип накладной ОТГРУЗКА/ВОЗВРАТ
$return = $_POST["num_rows"] ? 1 : 0;

// Сохраняем цены изделий в ODD/ODB
foreach ($_POST["price"] as $key => $value) {
	$tbl = $_POST["tbl"][$key];
	$tbl_id = $_POST["tbl_id"][$key];
	$discount = ($_POST["discount"][$key] > 0) ? $_POST["discount"][$key] : "NULL";
	$odid = $_POST["odid"][$key];

	// Узнаем нет ли накладной для очередного заказа
	$query = "
		SELECT PFI.PFI_ID
		FROM OrdersData OD
		LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID AND PFI.del = 0 ".($return ? "AND PFI.rtrn = 1" : "AND PFI.rtrn != 1")."
		WHERE OD.OD_ID = {$odid}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$PFI_ID = mysqli_result($res,0,'PFI_ID');
	// Если заказ в накладной - останавливаем, выводим сообщение
	if( $PFI_ID ) {
		$_SESSION["error"][] = "При создании накладной возникла ошибка. Пожалуйста, повторите попытку.";
		exit ('<meta http-equiv="refresh" content="0; url=sverki.php?year='.($_POST["year"]).'&payer='.($_POST["payer"]).'">');
		die;
	}

	// Делаем исключение для Клена
	$query = "SELECT OD.SH_ID FROM OrdersData OD WHERE OD.OD_ID = {$odid}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$shop = mysqli_result($res,0,'SH_ID') ? mysqli_result($res,0,'SH_ID') : 1;

	// Если Клен - цену записываем в opt_price
	if( $shop == 36 ) {
		if( $tbl == "odd" ) {
			$query = "UPDATE OrdersDataDetail SET opt_price = ".($value - $discount).", author = {$_SESSION["id"]} WHERE ODD_ID = {$tbl_id}";
		}
		elseif( $tbl == "odb" ) {
			$query = "UPDATE OrdersDataBlank SET opt_price = ".($value - $discount).", author = {$_SESSION["id"]} WHERE ODB_ID = {$tbl_id}";
		}
	}
	else {
		if( $tbl == "odd" ) {
			$query = "UPDATE OrdersDataDetail SET Price = {$value}, discount = {$discount}, author = {$_SESSION["id"]} WHERE ODD_ID = {$tbl_id}";
		}
		elseif( $tbl == "odb" ) {
			$query = "UPDATE OrdersDataBlank SET Price = {$value}, discount = {$discount}, author = {$_SESSION["id"]} WHERE ODB_ID = {$tbl_id}";
		}
	}

	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
}

// Обновляем информацию о плательщике
$platelshik_name = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_name"] ));
$platelshik_adres = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_adres"] ));
$platelshik_tel = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_tel"] ));
$platelshik_inn = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_inn"] ));
$platelshik_okpo = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_okpo"] ));
$platelshik_kpp = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_kpp"] ));
$platelshik_schet = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_schet"] ));
$platelshik_bank = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_bank"] ));
$platelshik_bik = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_bik"] ));
$platelshik_ks = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_ks"] ));
$platelshik_bank_adres = trim(mysqli_real_escape_string( $mysqli,$_POST["platelshik_bank_adres"] ));
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

// Сохраняем у заказа дату продажи и ID накладной
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
$query = "SELECT ODD_ODB.OD_ID
				,ODD_ODB.ItemID
				,ODD_ODB.PT_ID
				,OD.Code
				,ODD_ODB.Amount
				#Исключение для Клена
				,IF(OD.SH_ID = 36, ODD_ODB.opt_price, ODD_ODB.Price) Price
				,ODD_ODB.Zakaz
		  FROM (SELECT ODD.OD_ID
					  ,ODD.ODD_ID ItemID
					  ,IFNULL(PM.PT_ID, 2) PT_ID
					  ,ODD.Amount
					  ,(ODD.Price - IFNULL(ODD.discount, 0)) Price
					  ,ODD.opt_price
					  ,Zakaz(ODD.ODD_ID) Zakaz
				FROM OrdersDataDetail ODD
				LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
				WHERE ODD.Del = 0
				UNION ALL
				SELECT ODB.OD_ID
					  ,ODB.ODB_ID ItemID
					  ,0 PT_ID
					  ,ODB.Amount
					  ,(ODB.Price - IFNULL(ODB.discount, 0)) Price
					  ,ODB.opt_price
					  ,ZakazB(ODB.ODB_ID) Zakaz
				FROM OrdersDataBlank ODB
				WHERE ODB.Del = 0
				) ODD_ODB
		  JOIN OrdersData OD ON OD.OD_ID = ODD_ODB.OD_ID
		  WHERE ODD_ODB.OD_ID IN ({$id_list})
		  GROUP BY ODD_ODB.itemID
		  ORDER BY ODD_ODB.OD_ID, ODD_ODB.PT_ID DESC, ODD_ODB.itemID";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$Counter = 0;
while( $row = mysqli_fetch_array($res) ) {
	$_POST["tovar_name"][$Counter] = trim($row["Zakaz"]);
	$_POST["tovar_nn"][$Counter] = $row["Code"];
	$_POST["tovar_ed"][$Counter] = "шт";
	$_POST["tovar_okei"][$Counter] = "796";
	$_POST["tovar_kol"][$Counter] = $row["Amount"];
	$_POST["tovar_cena"][$Counter] = $row["Price"];
	$Counter++;
}

$_POST["nomer"] = $count;

// Информация о грузоотправителе
$query = "SELECT * FROM Rekvizity LIMIT 1";
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

if( $curl = curl_init() ) {
	curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/buh/tovarnaya-nakladnaya/blanc.php');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/buh/tovarnaya-nakladnaya/');
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

	$filename = 'invoice_'.$id.'_'.$_POST["nomer"].'.pdf';
	file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере
//	header('Content-Type: application/pdf');
//	header('Content-Length: '.strlen( $out ));
//	header('Content-disposition: inline; filename="' . $filename . '"');
//	header('Cache-Control: public, must-revalidate, max-age=0');
//	print $out;

	curl_close($curl);

	exit ('<meta http-equiv="refresh" content="0; url=sverki.php?year='.($_POST["year"]).'&payer='.($_POST["payer"]).'">');
	die;
}
?>
