<?
//	$from = "admin@fabrikaprestol.ru";
//	$to = "sheff4uk@gmail.com";
//	$subject = "Подтверждение регистрации Престол";
//	$text = "Здравствуйте! Вы зарегистрировались в Корпоративной Информационной Системе ПРЕСТОЛ\nВаш логин: \nДля активации учетной записи свяжитесь с администрацией: admin@fabrikaprestol.ru\n\nС уважением,\nАдминистрация КИС Престол";
//
//	require "PHPMailer/PHPMailerAutoload.php";
//
//	$mail = new PHPMailer(true);
//	$mail->isSMTP();
//	$mail->SMTPAuth = true;
//	$mail->SMTPSecure = "ssl";
//	$mail->Host = "smtp.yandex.ru";
//	$mail->Port = "465";
//	$mail->Username = "admin@fabrikaprestol.ru";
//	$mail->Password = "GmvN6*D%";
//	$mail->CharSet = "UTF-8";
//	$mail->ContentType = 'text/plain';
//	$mail->isHTML(false);
//	$mail->addAddress($to);
//	$mail->addReplyTo($from);
//	$mail->setFrom($from);
//	$mail->Subject = $subject;
//	$mail->Body = $text;
//	$mail->send();
	echo phpinfo();

//include "config.php";
//$query = "SELECT ODD.ODD_ID, ODD.PM_ID, ODD.PME_ID, ODD.Length
//			FROM OrdersDataDetail ODD
//			LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
//			WHERE PM.PT_ID = 1";
//$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//while( $row = mysqli_fetch_array($res) )
//{
//	$odd_id = $row["ODD_ID"] ? $row["ODD_ID"] : 'NULL';
//	$Model = $row["PM_ID"] ? $row["PM_ID"] : 'NULL';
//	$Mechanism = $row["PME_ID"] ? $row["PME_ID"] : 'NULL';
//	$Length = $row["Length"] ? $row["Length"] : 'NULL';
//
//	// Очищаем этапы
//	$query = "DELETE FROM OrdersDataSteps WHERE ODD_ID = {$odd_id}";
//	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//
//	// Вычисляем тарифи для разных этапов и записываем их
//	$query="INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, Tariff)
//			SELECT {$odd_id}
//				  ,ST.ST_ID
//				  ,(IFNULL(ST.Tariff, 0) + IFNULL(PMET.Tariff, 0) + IFNULL(PMOT.Tariff, 0) + IFNULL(PSLT.Tariff, 0))
//			FROM StepsTariffs ST
//			JOIN ProductModelsTariff PMOT ON PMOT.ST_ID = ST.ST_ID AND PMOT.PM_ID = IFNULL({$Model}, 0)
//			LEFT JOIN ProductMechanismTariff PMET ON PMET.ST_ID = ST.ST_ID AND PMET.PME_ID = {$Mechanism}
//			LEFT JOIN ProductSizeLengthTariff PSLT ON PSLT.ST_ID = ST.ST_ID AND {$Length} BETWEEN PSLT.From AND PSLT.To";
//	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//}

//	$query = "SELECT Date FROM PayLog ORDER BY Date";
//	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//	$i = 1;
//	while( $row = mysqli_fetch_array($res) )
//	{
//		$query = "UPDATE PayLog SET PL_ID = {$i} WHERE Date = '{$row["Date"]}'";
//		mysqli_query( $mysqli, $query );
//		$i++;
//	}
?>
