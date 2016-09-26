<?
include "config.php";
session_start();

switch( $_GET["do"] )
{
case "torg12":
	// Записываем в журнал информацию по накладной, очищаем переменные в сессии
	if( !empty($_SESSION["torg_year"]) and !empty($_SESSION["torg_count"]) ) {
		$nomer = mysqli_real_escape_string( $mysqli, $_POST["nomer"] );
		$date = mysqli_real_escape_string( $mysqli, $_POST["date"] );
		$gruzootpravitel_name = mysqli_real_escape_string( $mysqli, $_POST["gruzootpravitel_name"] );
		$platelshik_name = mysqli_real_escape_string( $mysqli, $_POST["platelshik_name"] );
		$query = "UPDATE NakladnayaCount SET Number = '{$nomer}', Date = '{$date}', gruzootpravitel = '{$gruzootpravitel_name}', platelshik = '{$platelshik_name}' WHERE Year = {$_SESSION["torg_year"]} AND Count = {$_SESSION["torg_count"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		unset($_SESSION["torg_year"]);
		unset($_SESSION["torg_count"]);
	}

	// Обновляем цены товаров
	foreach ($_POST["tovar_tcena"] as $key => $value) {
		if( $_POST["pt"][$key] > 0 ) {
			$query = "UPDATE OrdersDataDetail SET Price = {$value} WHERE ODD_ID = {$_POST["item"][$key]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		elseif( $_POST["pt"][$key] == '0' ) {
			$query = "UPDATE OrdersDataBlank SET Price = {$value} WHERE ODB_ID = {$_POST["item"][$key]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
	}

	// Обновляем информацию по контрагентам
	if( $_POST["platelshik_id"] ) {
		$query = "UPDATE Kontragenty SET
					 Naimenovanie = '{$_POST["platelshik_name"]}'
					,Jur_adres = IF('{$_POST["platelshik_adres"]}' = '', NULL, '{$_POST["platelshik_adres"]}')
					,Telefony = IF('{$_POST["platelshik_tel"]}' = '', NULL, '{$_POST["platelshik_tel"]}')
					,INN = IF('{$_POST["platelshik_inn"]}' = '', NULL, '{$_POST["platelshik_inn"]}')
					,OKPO = IF('{$_POST["platelshik_okpo"]}' = '', NULL, '{$_POST["platelshik_okpo"]}')
					,KPP = IF('{$_POST["platelshik_kpp"]}' = '', NULL, '{$_POST["platelshik_kpp"]}')
					,Schet = IF('{$_POST["platelshik_schet"]}' = '', NULL, '{$_POST["platelshik_schet"]}')
					,Bank = IF('{$_POST["platelshik_bank"]}' = '', NULL, '{$_POST["platelshik_bank"]}')
					,BIK = IF('{$_POST["platelshik_bik"]}' = '', NULL, '{$_POST["platelshik_bik"]}')
					,KS = IF('{$_POST["platelshik_ks"]}' = '', NULL, '{$_POST["platelshik_ks"]}')
					,Bank_adres = IF('{$_POST["platelshik_bank_adres"]}' = '', NULL, '{$_POST["platelshik_bank_adres"]}')
					WHERE KA_ID = {$_POST["platelshik_id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	elseif( $_POST["platelshik_name"] ) {
		$query = "INSERT INTO Kontragenty SET
					 Naimenovanie = '{$_POST["platelshik_name"]}'
					,Jur_adres = IF('{$_POST["platelshik_adres"]}' = '', NULL, '{$_POST["platelshik_adres"]}')
					,Telefony = IF('{$_POST["platelshik_tel"]}' = '', NULL, '{$_POST["platelshik_tel"]}')
					,INN = IF('{$_POST["platelshik_inn"]}' = '', NULL, '{$_POST["platelshik_inn"]}')
					,OKPO = IF('{$_POST["platelshik_okpo"]}' = '', NULL, '{$_POST["platelshik_okpo"]}')
					,KPP = IF('{$_POST["platelshik_kpp"]}' = '', NULL, '{$_POST["platelshik_kpp"]}')
					,Schet = IF('{$_POST["platelshik_schet"]}' = '', NULL, '{$_POST["platelshik_schet"]}')
					,Bank = IF('{$_POST["platelshik_bank"]}' = '', NULL, '{$_POST["platelshik_bank"]}')
					,BIK = IF('{$_POST["platelshik_bik"]}' = '', NULL, '{$_POST["platelshik_bik"]}')
					,KS = IF('{$_POST["platelshik_ks"]}' = '', NULL, '{$_POST["platelshik_ks"]}')
					,Bank_adres = IF('{$_POST["platelshik_bank_adres"]}' = '', NULL, '{$_POST["platelshik_bank_adres"]}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( $curl = curl_init() ) {
		curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/buh/tovarnaya-nakladnaya/blanc.php');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/buh/tovarnaya-nakladnaya/');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
		$out = curl_exec($curl);
		$filename = 'tovarnaya-nakladnaya.pdf';
		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen( $out ));
		header('Content-disposition: inline; filename="' . $filename . '"');
		header('Cache-Control: public, must-revalidate, max-age=0');
		print $out;

		curl_close($curl);
	}
	break;

case "schet":
	// Записываем в журнал информацию по счету, очищаем переменные в сессии
	if( !empty($_SESSION["schet_year"]) and !empty($_SESSION["schet_count"]) ) {
		$nomer = mysqli_real_escape_string( $mysqli, $_POST["nomer"] );
		$date = mysqli_real_escape_string( $mysqli, $_POST["date"] );
		$gruzootpravitel_name = mysqli_real_escape_string( $mysqli, $_POST["destination_name"] );
		$platelshik_name = mysqli_real_escape_string( $mysqli, $_POST["pokupatel"] );
		$query = "UPDATE SchetCount SET Number = '{$nomer}', Date = '{$date}', gruzootpravitel = '{$gruzootpravitel_name}', platelshik = '{$platelshik_name}' WHERE Year = {$_SESSION["schet_year"]} AND Count = {$_SESSION["schet_count"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		unset($_SESSION["schet_year"]);
		unset($_SESSION["schet_count"]);
	}

	// Обновляем цены товаров
	foreach ($_POST["tovar_cena"] as $key => $value) {
//		$value = substr($value, 0, -3);
		if( $_POST["pt"][$key] > 0 ) {
			$query = "UPDATE OrdersDataDetail SET Price = {$value} WHERE ODD_ID = {$_POST["item"][$key]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		elseif( $_POST["pt"][$key] == '0' ) {
			$query = "UPDATE OrdersDataBlank SET Price = {$value} WHERE ODB_ID = {$_POST["item"][$key]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
	}

	// Обновляем информацию по контрагентам
	if( $_POST["pokupatel_id"] ) {
		$query = "UPDATE Kontragenty SET
					 Naimenovanie = '{$_POST["pokupatel"]}'
					,Jur_adres = IF('{$_POST["pokupatel_adres"]}' = '', NULL, '{$_POST["pokupatel_adres"]}')
					,INN = IF('{$_POST["pokupatel_inn"]}' = '', NULL, '{$_POST["pokupatel_inn"]}')
					,KPP = IF('{$_POST["pokupatel_kpp"]}' = '', NULL, '{$_POST["pokupatel_kpp"]}')
					WHERE KA_ID = {$_POST["pokupatel_id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	elseif( $_POST["pokupatel"] ) {
		$query = "INSERT INTO Kontragenty SET
					 Naimenovanie = '{$_POST["pokupatel"]}'
					,Jur_adres = IF('{$_POST["pokupatel_adres"]}' = '', NULL, '{$_POST["pokupatel_adres"]}')
					,INN = IF('{$_POST["pokupatel_inn"]}' = '', NULL, '{$_POST["pokupatel_inn"]}')
					,KPP = IF('{$_POST["pokupatel_kpp"]}' = '', NULL, '{$_POST["pokupatel_kpp"]}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}

	if( $curl = curl_init() ) {
		curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/buh/schet/blanc.php');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/buh/schet/');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
		$out = curl_exec($curl);
		$filename = 'schet.pdf';
		header('Content-Type: application/pdf');
		header('Content-Length: '.strlen( $out ));
		header('Content-disposition: inline; filename="' . $filename . '"');
		header('Cache-Control: public, must-revalidate, max-age=0');
		print $out;

		curl_close($curl);
	}
	break;
}
?>
