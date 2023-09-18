<?
$token = random_bytes(15);
//$token = 'vcxjesgfruweb3geu36tc4yu';
echo bin2hex($token); // ffa7a910ca2dfce501b0d548605aaf
die();
//phpinfo();
include "config.php";

$query = "SELECT `X-Authorization` FROM Rekvizity WHERE R_ID = 1";
$result = mysqli_query( $mysqli, $query );
$row = mysqli_fetch_array($result);
$Authorization = $row["X-Authorization"];

if( $curl = curl_init() ) {
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/devices/search');
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'X-Authorization: '.$Authorization
	));
	$out = curl_exec($curl);
	curl_close($curl);
}
$terminals = json_decode($out, true);
foreach($terminals as $value) {
	echo $value["uuid"]."<br>";
	$storeUuid = $value["storeUuid"];
}
?>
