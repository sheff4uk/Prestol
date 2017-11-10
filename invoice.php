<?
include "config.php";
session_start();

// Сохраняем оптовые цены изделий в ODD/ODB
foreach ($_POST["opt_price"] as $key => $value) {
	$tbl = $_POST["tbl"][$key];
	$tbl_id = $_POST["tbl_id"][$key];

	if( $tbl == "odd" ) {
		$query = "UPDATE OrdersDataDetail SET opt_price = {$value}, author = {$_SESSION["id"]} WHERE ODD_ID = {$tbl_id}";
	}
	elseif( $tbl == "odb" ) {
		$query = "UPDATE OrdersDataBlank SET opt_price = {$value}, author = {$_SESSION["id"]} WHERE ODB_ID = {$tbl_id}";
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
$query = "INSERT INTO PrintFormsInvoice SET summa = {$_POST["summa"]}, platelshik_id = {$platelshik_id}, count = {$count}, date = '{$date}', USR_ID = {$_SESSION["id"]}";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$id = mysqli_insert_id($mysqli);

// В таблице OrdersData записываем ID накладной, сохраняем дату продажи
$id_list = "0";
foreach ($_POST["ord"] as $key => $value) {
	$query = "UPDATE OrdersData OD
				JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				SET OD.StartDate = IF(SH.KA_ID IS NULL, OD.StartDate, '{$date}'), OD.PFI_ID = {$id}, OD.author = {$_SESSION["id"]} WHERE OD.OD_ID = {$value}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$id_list .= ",".$value;
}

//Записываем в массив POST данные по товарам
$query = "SELECT ODD_ODB.OD_ID
				,ODD_ODB.ItemID
				,ODD_ODB.PT_ID
				,OD.Code
				,ODD_ODB.Amount
				,ODD_ODB.Price
				,ODD_ODB.Zakaz
		  FROM (SELECT ODD.OD_ID
					  ,ODD.ODD_ID ItemID
					  ,IFNULL(PM.PT_ID, 2) PT_ID
					  ,ODD.Amount
					  ,ODD.opt_price Price
					  ,CONCAT(IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), '')) Zakaz
				FROM OrdersDataDetail ODD
				LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
				LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
				LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
				WHERE ODD.Del = 0
				UNION ALL
				SELECT ODB.OD_ID
					  ,ODB.ODB_ID ItemID
					  ,0 PT_ID
					  ,ODB.Amount
					  ,ODB.opt_price Price
					  ,CONCAT(IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), '')) Zakaz
				FROM OrdersDataBlank ODB
				LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
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

//$_POST["nomer"] = str_pad($count, 8, '0', STR_PAD_LEFT); // Дописываем нули к номеру накладной
$_POST["nomer"] = $count;

// Информация о грузоотправителе
$query = "SELECT * FROM Rekvizity LIMIT 1";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$_POST["gruzootpravitel_name"] = mysqli_result($res,0,'Name');
$_POST["gruzootpravitel_inn"] = mysqli_result($res,0,'INN');
$_POST["gruzootpravitel_kpp"] = mysqli_result($res,0,'KPP');
$_POST["gruzootpravitel_adres"] = mysqli_result($res,0,'Addres');
$_POST["gruzootpravitel_director"] = mysqli_result($res,0,'Dir');
$_POST["gruzootpravitel_tel"] = mysqli_result($res,0,'Phone');
$_POST["gruzootpravitel_schet"] = mysqli_result($res,0,'RS');
$_POST["gruzootpravitel_bank"] = mysqli_result($res,0,'Bank');
$_POST["gruzootpravitel_bik"] = mysqli_result($res,0,'BIK');
$_POST["gruzootpravitel_ks"] = mysqli_result($res,0,'KS');
$_POST["gruzopoluchatel"] = 0;
$_POST["postavshik"] = 1;


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

	exit ('<meta http-equiv="refresh" content="0; url=sverki.php">');
	die;
}
?>