<?
include "config.php";
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

if( $curl = curl_init() ) {
	curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/buh/tovarnaya-nakladnaya/blanc.php');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/buh/tovarnaya-nakladnaya/');
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
	$out = curl_exec($curl);
	$filename = 'tovarnaya-nakladnaya.pdf';
	header('Content-type: application/pdf');
	header('Content-Disposition: inline; filename="' . $filename . '"');
	header('Content-Transfer-Encoding: binary');
	header('Accept-Ranges: bytes');
	echo $out;
	curl_close($curl);
}
?>
