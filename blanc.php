<?
include "config.php";
session_start();

	// Обновляем цены товаров и сохраняем данные товаров пришедшие из формы
	$id = $_POST["pfid"];
	$Counter = 1;
	$summa = 0;

	// Очищаем список товаров чтобы занести его снова
	$query = "DELETE FROM PrintFormsProducts WHERE PF_ID={$id}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	foreach ($_POST["tovar_tcena"] as $key => $value) {
		if( $_POST["pt"][$key] > 0 ) {
			$query = "UPDATE OrdersDataDetail SET Price = {$value} WHERE ODD_ID = {$_POST["item"][$key]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		elseif( $_POST["pt"][$key] == '0' ) {
			$query = "UPDATE OrdersDataBlank SET Price = {$value} WHERE ODB_ID = {$_POST["item"][$key]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Сохраняем информацию по товарам
		$tovar_name = mysqli_real_escape_string( $mysqli,$_POST["tovar_name"][$key] );
		$tovar_ed = mysqli_real_escape_string( $mysqli,$_POST["tovar_ed"][$key] );
		$tovar_okei = mysqli_real_escape_string( $mysqli,$_POST["tovar_okei"][$key] );
		$tovar_massa = mysqli_real_escape_string( $mysqli,$_POST["tovar_massa"][$key] );
		$item = ($_POST["item"][$key]) ? $_POST["item"][$key] : "NULL";
		$pt = ($_POST["pt"][$key]) ? $_POST["pt"][$key] : "NULL";
		$query = "INSERT INTO PrintFormsProducts(PF_ID, sort, ItemID, PT_ID, Amount, Price, Zakaz, tovar_ed, tovar_okei, tovar_massa)
				  VALUES ({$id}, {$Counter}, {$item}, {$pt}, {$_POST["tovar_kolvo"][$key]}, {$_POST["tovar_tcena"][$key]}, '{$tovar_name}', '{$tovar_ed}', '{$tovar_okei}', '{$tovar_massa}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$Counter++;
		$summa = $summa + $_POST["tovar_tcena"][$key] * $_POST["tovar_kolvo"][$key];
	}

	// Обновляем информацию по контрагентам
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
	elseif( $_POST["platelshik_name"] ) {
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
	else {
		$platelshik_id = "NULL";
	}

	$gruzopoluchatel_name = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_name"] ));
	$gruzopoluchatel_adres = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_adres"] ));
	$gruzopoluchatel_tel = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_tel"] ));
	$gruzopoluchatel_inn = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_inn"] ));
	$gruzopoluchatel_okpo = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_okpo"] ));
	$gruzopoluchatel_kpp = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_kpp"] ));
	$gruzopoluchatel_schet = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_schet"] ));
	$gruzopoluchatel_bank = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_bank"] ));
	$gruzopoluchatel_bik = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_bik"] ));
	$gruzopoluchatel_ks = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_ks"] ));
	$gruzopoluchatel_bank_adres = trim(mysqli_real_escape_string( $mysqli,$_POST["gruzopoluchatel_bank_adres"] ));
	if( $_POST["gruzopoluchatel_id"] ) {
		$query = "UPDATE Kontragenty SET
					 Naimenovanie = '{$gruzopoluchatel_name}'
					,Jur_adres = IF('{$gruzopoluchatel_adres}' = '', NULL, '{$gruzopoluchatel_adres}')
					,Telefony = IF('{$gruzopoluchatel_tel}' = '', NULL, '{$gruzopoluchatel_tel}')
					,INN = IF('{$gruzopoluchatel_inn}' = '', NULL, '{$gruzopoluchatel_inn}')
					,OKPO = IF('{$gruzopoluchatel_okpo}' = '', NULL, '{$gruzopoluchatel_okpo}')
					,KPP = IF('{$gruzopoluchatel_kpp}' = '', NULL, '{$gruzopoluchatel_kpp}')
					,Schet = IF('{$gruzopoluchatel_schet}' = '', NULL, '{$gruzopoluchatel_schet}')
					,Bank = IF('{$gruzopoluchatel_bank}' = '', NULL, '{$gruzopoluchatel_bank}')
					,BIK = IF('{$gruzopoluchatel_bik}' = '', NULL, '{$gruzopoluchatel_bik}')
					,KS = IF('{$gruzopoluchatel_ks}' = '', NULL, '{$gruzopoluchatel_ks}')
					,Bank_adres = IF('{$gruzopoluchatel_bank_adres}' = '', NULL, '{$gruzopoluchatel_bank_adres}')
					WHERE KA_ID = {$_POST["gruzopoluchatel_id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$gruzopoluchatel_id = $_POST["gruzopoluchatel_id"];
	}
	elseif( $_POST["gruzopoluchatel_name"] ) {
		$query = "INSERT INTO Kontragenty SET
					 Naimenovanie = '{$gruzopoluchatel_name}'
					,Jur_adres = IF('{$gruzopoluchatel_adres}' = '', NULL, '{$gruzopoluchatel_adres}')
					,Telefony = IF('{$gruzopoluchatel_tel}' = '', NULL, '{$gruzopoluchatel_tel}')
					,INN = IF('{$gruzopoluchatel_inn}' = '', NULL, '{$gruzopoluchatel_inn}')
					,OKPO = IF('{$gruzopoluchatel_okpo}' = '', NULL, '{$gruzopoluchatel_okpo}')
					,KPP = IF('{$gruzopoluchatel_kpp}' = '', NULL, '{$gruzopoluchatel_kpp}')
					,Schet = IF('{$gruzopoluchatel_schet}' = '', NULL, '{$gruzopoluchatel_schet}')
					,Bank = IF('{$gruzopoluchatel_bank}' = '', NULL, '{$gruzopoluchatel_bank}')
					,BIK = IF('{$gruzopoluchatel_bik}' = '', NULL, '{$gruzopoluchatel_bik}')
					,KS = IF('{$gruzopoluchatel_ks}' = '', NULL, '{$gruzopoluchatel_ks}')
					,Bank_adres = IF('{$gruzopoluchatel_bank_adres}' = '', NULL, '{$gruzopoluchatel_bank_adres}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$gruzopoluchatel_id = mysqli_insert_id($mysqli);
	}
	else {
		$gruzopoluchatel_id = "NULL";
	}

	$postavshik_name = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_name"] ));
	$postavshik_adres = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_adres"] ));
	$postavshik_tel = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_tel"] ));
	$postavshik_inn = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_inn"] ));
	$postavshik_okpo = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_okpo"] ));
	$postavshik_kpp = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_kpp"] ));
	$postavshik_schet = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_schet"] ));
	$postavshik_bank = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_bank"] ));
	$postavshik_bik = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_bik"] ));
	$postavshik_ks = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_ks"] ));
	$postavshik_bank_adres = trim(mysqli_real_escape_string( $mysqli,$_POST["postavshik_bank_adres"] ));
	if( $_POST["postavshik_id"] ) {
		$query = "UPDATE Kontragenty SET
					 Naimenovanie = '{$postavshik_name}'
					,Jur_adres = IF('{$postavshik_adres}' = '', NULL, '{$postavshik_adres}')
					,Telefony = IF('{$postavshik_tel}' = '', NULL, '{$postavshik_tel}')
					,INN = IF('{$postavshik_inn}' = '', NULL, '{$postavshik_inn}')
					,OKPO = IF('{$postavshik_okpo}' = '', NULL, '{$postavshik_okpo}')
					,KPP = IF('{$postavshik_kpp}' = '', NULL, '{$postavshik_kpp}')
					,Schet = IF('{$postavshik_schet}' = '', NULL, '{$postavshik_schet}')
					,Bank = IF('{$postavshik_bank}' = '', NULL, '{$postavshik_bank}')
					,BIK = IF('{$postavshik_bik}' = '', NULL, '{$postavshik_bik}')
					,KS = IF('{$postavshik_ks}' = '', NULL, '{$postavshik_ks}')
					,Bank_adres = IF('{$postavshik_bank_adres}' = '', NULL, '{$postavshik_bank_adres}')
					WHERE KA_ID = {$_POST["postavshik_id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$postavshik_id = $_POST["postavshik_id"];
	}
	elseif( $_POST["postavshik_name"] ) {
		$query = "INSERT INTO Kontragenty SET
					 Naimenovanie = '{$postavshik_name}'
					,Jur_adres = IF('{$postavshik_adres}' = '', NULL, '{$postavshik_adres}')
					,Telefony = IF('{$postavshik_tel}' = '', NULL, '{$postavshik_tel}')
					,INN = IF('{$postavshik_inn}' = '', NULL, '{$postavshik_inn}')
					,OKPO = IF('{$postavshik_okpo}' = '', NULL, '{$postavshik_okpo}')
					,KPP = IF('{$postavshik_kpp}' = '', NULL, '{$postavshik_kpp}')
					,Schet = IF('{$postavshik_schet}' = '', NULL, '{$postavshik_schet}')
					,Bank = IF('{$postavshik_bank}' = '', NULL, '{$postavshik_bank}')
					,BIK = IF('{$postavshik_bik}' = '', NULL, '{$postavshik_bik}')
					,KS = IF('{$postavshik_ks}' = '', NULL, '{$postavshik_ks}')
					,Bank_adres = IF('{$postavshik_bank_adres}' = '', NULL, '{$postavshik_bank_adres}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$postavshik_id = mysqli_insert_id($mysqli);
	}
	else {
		$postavshik_id = "NULL";
	}

	// Получаем номер и год для документов
	$query = "SELECT IFNULL(year, 0) year, IFNULL(count, 0) count FROM PrintForms WHERE PF_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	if( mysqli_result($res,0,'year') and mysqli_result($res,0,'count') ) {
		$year = mysqli_result($res,0,'year');
		$count = mysqli_result($res,0,'count');
	}
	else {
		$year = date('Y');
		$query = "SELECT COUNT(1)+1 Cnt FROM PrintForms WHERE year = {$year}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$count = mysqli_result($res,0,'Cnt');
	}

	// Обновляем в таблице PrintForms ID контрагентов, номер с годом и сумму
	$query = "UPDATE PrintForms SET
				 summa = {$summa}
				,platelshik_id = {$platelshik_id}
				,gruzopoluchatel = {$_POST["gruzopoluchatel"]}
				,gruzopoluchatel_id = {$gruzopoluchatel_id}
				,postavshik = {$_POST["postavshik"]}
				,postavshik_id = {$postavshik_id}
				,year = {$year}
				,count = {$count}
			  WHERE PF_ID = {$id}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$_POST["nomer"] = str_pad($count, 8, '0', STR_PAD_LEFT); // Дописываем нули к номеру накладной

// Удаляем старые файлы
$expire_time = 63072000; // Время через которое файл считается устаревшим (в сек.)

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

switch( $_GET["do"] )
{
case "torg12":
	$_POST["date"] = $_POST["date_torg12"];

	// Сохраняем дату накладной
	$query = "UPDATE PrintForms SET nakladnaya_date = '{$_POST["date_torg12"]}'
			  WHERE PF_ID = {$id}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	if( $curl = curl_init() ) {
		curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/buh/tovarnaya-nakladnaya/blanc.php');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/buh/tovarnaya-nakladnaya/');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
		$out = curl_exec($curl);
		$filename = 'nakladnaya_'.$id.'_'.$_POST["nomer"].'.pdf';
		file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере
		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen( $out ));
		header('Content-disposition: inline; filename="' . $filename . '"');
		header('Cache-Control: public, must-revalidate, max-age=0');
		print $out;

		curl_close($curl);
	}
	break;

case "schet":
	$_POST["date"] = $_POST["date_schet"];
	$_POST["destination_name"] = $_POST["gruzootpravitel_name"];
	$_POST["destination_adres"] = $_POST["gruzootpravitel_adres"];
	$_POST["destination_INN"] = $_POST["gruzootpravitel_inn"];
	$_POST["destination_KPP"] = $_POST["gruzootpravitel_kpp"];
	$_POST["dorector"] = $_POST["gruzootpravitel_director"];
	$_POST["bux"] = $_POST["gruzootpravitel_buhgalter"];
	$_POST["destination_szhet"] = $_POST["gruzootpravitel_schet"];
	$_POST["destination_bank"] = $_POST["gruzootpravitel_bank"];
	$_POST["destination_BIK"] = $_POST["gruzootpravitel_bik"];
	$_POST["destination_KS"] = $_POST["gruzootpravitel_ks"];
	$_POST["pokupatel"] = $_POST["platelshik_name"];
	$_POST["pokupatel_adres"] = $_POST["platelshik_adres"];
	$_POST["pokupatel_inn"] = $_POST["platelshik_inn"];
	$_POST["pokupatel_kpp"] = $_POST["platelshik_kpp"];

	foreach ($_POST["tovar_tcena"] as $key => $value) {
		$_POST["tovar_kol"][$key] = $_POST["tovar_kolvo"][$key];
		$_POST["tovar_cena"][$key] = $_POST["tovar_tcena"][$key];
	}

	// Сохраняем дату счета
	$query = "UPDATE PrintForms SET schet_date = '{$_POST["date_schet"]}'
			  WHERE PF_ID = {$id}";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	if( $curl = curl_init() ) {
		curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/buh/schet/blanc.php');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/buh/schet/');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
		$out = curl_exec($curl);
		$filename = 'schet_'.$id.'_'.$_POST["nomer"].'.pdf';
		file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере
		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen( $out ));
		header('Content-disposition: inline; filename="' . $filename . '"');
		header('Cache-Control: public, must-revalidate, max-age=0');
		print $out;

		curl_close($curl);
	}
	break;

default:
	exit ('<meta http-equiv="refresh" content="0; url=/print_forms.php?pfid='.$id.'">');
	break;
}
?>
