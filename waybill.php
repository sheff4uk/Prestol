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
		,ODD.boxes
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
	$_POST["tovar_km"][$Counter] = $row["boxes"];
	$Counter++;
}

// Информация о грузоотправителе и грузополучателе
$query = "
	SELECT R.*
	FROM Shipment SHP
	JOIN Cities CT ON CT.CT_ID = SHP.CT_ID
	JOIN Rekvizity R ON R.R_ID = CT.R_ID
	WHERE SHP.SHP_ID = {$_GET["shpid"]}
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

//$_POST["gruzopoluchatel_name"] = $row["Name"];
//$_POST["gruzopoluchatel_inn"] = $row["INN"];
//$_POST["gruzopoluchatel_kpp"] = $row["KPP"];
//$_POST["gruzopoluchatel_okpo"] = '';
//$_POST["gruzopoluchatel_adres"] = $row["Addres"];
//$_POST["gruzopoluchatel_buhgalter"] = $row["Dir"];
//$_POST["gruzopoluchatel_tel"] = $row["Phone"];
//$_POST["gruzopoluchatel_schet"] = $row["RS"];
//$_POST["gruzopoluchatel_bank"] = $row["Bank"];
//$_POST["gruzopoluchatel_bik"] = $row["BIK"];
//$_POST["gruzopoluchatel_ks"] = $row["KS"];
//
//$_POST["gruzopoluchatel"] = 1;

$_POST["gruzopoluchatel"] = 0;

$query = "
	SELECT K.*
	FROM Kontragenty K
	WHERE K.KA_ID = 800
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$row = mysqli_fetch_array($res);
$_POST["platelshik_name"] = $row["Naimenovanie"];
$_POST["platelshik_inn"] = $row["INN"];
$_POST["platelshik_kpp"] = $row["KPP"];
$_POST["platelshik_okpo"] = $row["OKPO"];
$_POST["platelshik_adres"] = $row["Jur_adres"];
$_POST["platelshik_tel"] = $row["Telefony"];
$_POST["platelshik_schet"] = $row["Schet"];
$_POST["platelshik_bank"] = $row["Bank"];
$_POST["platelshik_bik"] = $row["BIK"];
$_POST["platelshik_ks"] = $row["KS"];
$_POST["platelshik_bank_adres"] = $row["Bank_adres"];

$data = http_build_query($_POST);
$headers = stream_context_create(array(
	'http' => array(
		'method' => 'POST',
		'header' => array('Referer: https://service-online.su/forms/auto/ttn/'),
		'content' => $data
	)
));
$path = file_get_contents('https://service-online.su/forms/auto/ttn/blanc.php', false, $headers);
$path = strstr($path, '/blank/');
$path = strstr($path, '.pdf', true);
header('Content-Type: application/pdf');
echo file_get_contents('https://service-online.su'.$path.'.pdf', false, null);
?>
