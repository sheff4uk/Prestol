<?
include "config.php";
include "checkrights.php";

// Проверка прав на печать товарно-транспортной накладной
if (!in_array('add_shipment', $Rights)) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

//Записываем в массив POST данные по товарам
$query = "
	SELECT OD.Code
		,ODD.Amount
		,ODD.Price - IFNULL(ODD.discount, 0) Price
		,Zakaz(ODD.ODD_ID) Zakaz
	FROM OrdersData OD
	LEFT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.SHP_ID = {$_GET["shpid"]}
	ORDER BY OD.OD_ID, IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) DESC, ODD.ODD_ID
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

// Информация о грузоотправителе и грузополучателе
$query = "
	SELECT R.*
	FROM Shipment SHP
	JOIN Cities CT ON CT.CT_ID = SHP.CT_ID
	JOIN Rekvizity R ON R.R_ID = CT.R_ID
	WHERE SHP.SHP_ID = 593
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$_POST["gruzootpravitel_name"] = $row["Name"];
$_POST["gruzootpravitel_inn"] = $row["INN"];
$_POST["gruzootpravitel_kpp"] = $row["KPP"];
$_POST["gruzootpravitel_okpo"] = '';
$_POST["gruzootpravitel_adres"] = $row["Addres"];
$_POST["gruzootpravitel_buhgalter"] = $row["Dir"];
$_POST["gruzootpravitel_tel"] = $row["Phone"];
$_POST["gruzootpravitel_schet"] = $row["RS"];
$_POST["gruzootpravitel_bank"] = $row["Bank"];
$_POST["gruzootpravitel_bik"] = $row["BIK"];
$_POST["gruzootpravitel_ks"] = $row["KS"];

$_POST["gruzopoluchatel_name"] = $row["Name"];
$_POST["gruzopoluchatel_inn"] = $row["INN"];
$_POST["gruzopoluchatel_kpp"] = $row["KPP"];
$_POST["gruzopoluchatel_okpo"] = '';
$_POST["gruzopoluchatel_adres"] = $row["Addres"];
$_POST["gruzopoluchatel_buhgalter"] = $row["Dir"];
$_POST["gruzopoluchatel_tel"] = $row["Phone"];
$_POST["gruzopoluchatel_schet"] = $row["RS"];
$_POST["gruzopoluchatel_bank"] = $row["Bank"];
$_POST["gruzopoluchatel_bik"] = $row["BIK"];
$_POST["gruzopoluchatel_ks"] = $row["KS"];

$_POST["gruzopoluchatel"] = 1;

if( $curl = curl_init() ) {
	curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/auto/ttn/blanc.php');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/auto/ttn/');
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

//	$filename = 'invoice_'.$id.'_'.$_POST["nomer"].'.pdf';
//	file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере
	header('Content-Type: application/pdf');
	header('Content-Length: '.strlen( $out ));
	header('Content-disposition: inline; filename="' . $filename . '"');
	header('Cache-Control: public, must-revalidate, max-age=0');
	print $out;

	curl_close($curl);

//	exit ('<meta http-equiv="refresh" content="0; url=sverki.php?year='.($_POST["year"]).'&payer='.($_POST["payer"]).'">');
//	die;
}
?>
