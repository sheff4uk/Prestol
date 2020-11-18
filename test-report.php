<?
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
include "config.php";

$query = "SELECT `X-Authorization` FROM Rekvizity WHERE R_ID = 2";
$result = mysqli_query( $mysqli, $query );
$row = mysqli_fetch_array($result);
$Authorization = $row["X-Authorization"];

// Даты периода выборки в нужном формате
//$days = 1;
//$yesterday = date("Y-m-d", strtotime("-$days DAY"));
//$gtCloseDate = $yesterday."T00:00:00.000+03:00";
//$ltCloseDate = $yesterday."T23:59:59.999+03:00";
$gtCloseDate = "2020-02-01T00:00:00.000+03:00";
$ltCloseDate = "2020-02-29T23:59:59.999+03:00";

//$title = "Отчёт за ".(date("d.m.Y", strtotime("-$days DAY")));
//$message = "<h2>{$title}</h2>";

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
	$message .= "
		<b>{$value["name"]}:</b>
		<table cellspacing='0' cellpadding='2' border='1'>
			<tr>
				<td><b>Время</b></td>
				<td><b>Наличными</b></td>
				<td><b>Картой</b></td>
			</tr>
	";
	$deviceUuid = $value["uuid"];
	$storeUuid = $value["storeUuid"];

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
					$query = "SELECT (CONVERT_TZ(STR_TO_DATE(SUBSTRING_INDEX('{$transactions["creationDate"]}', '.', 1), '%Y-%m-%dT%T'), '+00:00', CONCAT(IF({$transactions["timezone"]} > 0, '+', ''), TIME_FORMAT(SEC_TO_TIME({$transactions["timezone"]} DIV 1000), '%H:%i')))) payment_date";
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

//$email = "fabrikaprestol@gmail.com";
//$from = "admin@fabrikaprestol.ru";
//$subject = "[КИС Престол] {$title}";//тема сообщения
//
//// Отправляем письмо на указанный ящик пользователя через PHPMailer
//require "PHPMailer/PHPMailerAutoload.php";
//$mail = new PHPMailer(true);
//$mail->isSMTP();
//$mail->SMTPAuth = true;
//$mail->SMTPSecure = "ssl";
//$mail->Host = $mail_Host;
//$mail->Port = $mail_Port;
//$mail->Username = $mail_Username;
//$mail->Password = $mail_Password;
//$mail->CharSet = "UTF-8";
//$mail->ContentType = 'text/plain';
//$mail->addAddress($email);
//$mail->addReplyTo($from);
//$mail->setFrom($from);
//$mail->Subject = $subject;
//$mail->Body = $message;
//$mail->IsHTML(true);
//
//$mail->send();

echo $message;
?>
