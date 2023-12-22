<?
include "config.php";

$key = $argv[1];
$days = $argv[2];
$to = $argv[3];

// Проверка доступа
if( $key != $script_key ) die('Access denied!');

// Даты периода выборки в нужном формате
$yesterday = date("Y-m-d", strtotime("-$days DAY"));
$gtCloseDate = $yesterday."T00:00:00.000+03:00";
$ltCloseDate = $yesterday."T23:59:59.999+03:00";

$title = "Отчёт за ".(date("d.m.Y", strtotime("-$days DAY")));
$message = "<h2>{$title}</h2>";

$query = "
	SELECT CB.`name`
		,CB.`storeUuid`
		,CB.`X-Authorization`
		,CB.`deviceUuid`
	FROM `CashBox` CB
	WHERE CB.`deviceUuid` IS NOT NULL
";
$res = mysqli_query( $mysqli, $query );
while( $row = mysqli_fetch_array($res) ) {
	$message .= "
		<b>{$row["name"]}:</b>
		<table cellspacing='0' cellpadding='2' border='1'>
			<tr>
				<td><b>Время</b></td>
				<td><b>Наличными</b></td>
				<td><b>Картой</b></td>
			</tr>
	";
	$deviceUuid = $row["deviceUuid"];
	$storeUuid = $row["storeUuid"];
	$Authorization = $row["X-Authorization"];

	if( $curl = curl_init() ) {
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_URL, 'https://api.evotor.ru/api/v1/inventories/stores/'.$storeUuid.'/documents?deviceUuid='.$deviceUuid.'&gtCloseDate='.$gtCloseDate.'&ltCloseDate='.$ltCloseDate.'&types=PAYBACK,SELL');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'X-Authorization: '.$Authorization
		));
		$out = curl_exec($curl);
		curl_close($curl);
	}
	$documents = json_decode($out, true);
	$sumcash = 0;
	$sumcard = 0;
	foreach($documents as $value) {
		foreach($value["transactions"] as $transactions) {
			if( $transactions["type"] == "PAYMENT" ) {
				if( $transactions["paymentType"] == "CASH" or $transactions["paymentType"] == "CARD" ) {
					// Преобразуем время в нужный формат при помощ mySQL
					$query = "SELECT TIME(CONVERT_TZ(STR_TO_DATE(SUBSTRING_INDEX('{$transactions["creationDate"]}', '.', 1), '%Y-%m-%dT%T'), '+00:00', CONCAT(IF({$transactions["timezone"]} > 0, '+', ''), TIME_FORMAT(SEC_TO_TIME({$transactions["timezone"]} DIV 1000), '%H:%i')))) payment_date";
					$result = mysqli_query( $mysqli, $query );
					$row = mysqli_fetch_array($result);
					$payment_date = $row["payment_date"];

					if( $transactions["paymentType"] == "CASH" ) {
						$cash = $transactions["sum"];
						$card = "";
						$sumcash += $cash;
					}
					else {
						$cash = "";
						$card = $transactions["sum"];
						$sumcard += $card;
					}

					$message .= "
						<tr>
							<td>{$payment_date}</td>
							<td style='text-align: right;'>{$cash}</td>
							<td style='text-align: right;'>{$card}</td>
						</tr>
					";
				}
			}
		}
	}
	if( $sumcash or $sumcard ) {
		$message .= "
			<tr>
				<td style='text-align: right;'><b>Сумма:</b></td>
				<td style='text-align: right;'><b>{$sumcash}</b></td>
				<td style='text-align: right;'><b>{$sumcard}</b></td>
			</tr>
		";
	}
	$message .= "
		</table>
		<br>
	";
}

$subject = "[КИС Престол] {$title}";//тема сообщения
$headers  = "Content-type: text/html; charset=utf-8 \r\n";
$headers .= "From: planner@fabrikaprestol.ru\r\n";

mail($to, $subject, $message, $headers);
?>
