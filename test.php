<?
	$from = "admin@fabrikaprestol.ru";
	$to = "sheff4uk@gmail.com";
	$subject = "Подтверждение регистрации Престол";
	$text = "Здравствуйте! Вы зарегистрировались в Корпоративной Информационной Системе ПРЕСТОЛ\nВаш логин: \nДля активации учетной записи свяжитесь с администрацией: admin@fabrikaprestol.ru\n\nС уважением,\nАдминистрация КИС Престол";

	require "PHPMailer/PHPMailerAutoload.php";

	$mail = new PHPMailer(true);
	$mail->isSMTP();
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = "ssl";
	$mail->Host = "smtp.yandex.ru";
	$mail->Port = "465";
	$mail->Username = "admin@fabrikaprestol.ru";
	$mail->Password = "GmvN6*D%";
	$mail->CharSet = "UTF-8";
	$mail->ContentType = 'text/plain';
//	$mail->isHTML(false);
	$mail->addAddress($to);
	$mail->addReplyTo($from);
	$mail->setFrom($from);
	$mail->Subject = $subject;
	$mail->Body = $text;
	$mail->send();
//	echo phpinfo();
?>
