<?
	include "config.php";
	$title = 'Подтверждение E-mail';
	include "header.php";

	$query = "DELETE FROM Users WHERE Mailconfirm = 0 AND UNIX_TIMESTAMP() - UNIX_TIMESTAMP(Date) > 3600";
	mysqli_query( $mysqli, $query ); //удаляем пользователей из базы
	if (isset($_GET['code'])) {
		$code = $_GET['code']; //код подтверждения
	}
	else {
		exit("Вы зашли на страницу без кода подтверждения!"); //если не указали code, то выдаем ошибку
	}
	if (isset($_GET['login'])) {
		$login = $_GET['login']; //логин,который нужно активировать
	}
	else {
		exit("Вы зашли на страницу без логина!"); //если не указали логин, то выдаем ошибку
	}
	$query = "SELECT USR_ID, Name, Email FROM Users WHERE Login = '{$login}'";
	$result = mysqli_query( $mysqli, $query ); //извлекаем идентификатор пользователя с данным логином
	$myrow = mysqli_fetch_array($result);
	$mailconfirm = md5($myrow['USR_ID']).md5($login);//создаем такой же код подтверждения
	if ($mailconfirm == $code) {//сравниваем полученный из url и сгенерированный код
		$query = "UPDATE Users SET Mailconfirm = 1 WHERE Login = '{$login}'";
		mysqli_query( $mysqli, $query ); //если равны, то активируем пользователя
		echo "Ваш E-mail подтвержден! Для активации учетной записи свяжитесь с администрацией: <b>admin@fabrikaprestol.ru</b>";

		// АКТИВАЦИЯ ПОЛЬЗОВАТЕЛЯ
		$activation = md5($myrow['USR_ID'].$login).md5($login.$myrow['USR_ID']);//код активации аккаунта. Зашифруем через функцию md5 идентификатор + логин и логин + идентификатор.
		$from = "admin@fabrikaprestol.ru";
		$subject = "[КИС Престол] Регистрация нового пользователя";//тема сообщения
		$message = "В Корпоративной Информационной Системе ПРЕСТОЛ зарегистрирован новый пользователь:\n\nИмя пользователя: {$myrow['Name']}\n\nЛогин: {$login}\n\nE-mail: {$myrow['Email']}\n\nПерейдите по ссылке, чтобы активировать этого пользователя:\nhttps://kis.fabrikaprestol.ru/activation.php?login={$login}&code={$activation}";//содержание сообщения

		// Отправляем письмо администратору для активации пользователя через PHPMailer
		require "PHPMailer/PHPMailerAutoload.php";
		$mail = new PHPMailer(true);
		$mail->isSMTP();
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "ssl";
		$mail->Host = $mail_Host;
		$mail->Port = $mail_Port;
		$mail->Username = $mail_Username;
		$mail->Password = $mail_Password;
		$mail->CharSet = "UTF-8";
		$mail->ContentType = 'text/plain';
		$mail->addAddress($from);
		$mail->addReplyTo($from);
		$mail->setFrom($from);
		$mail->Subject = $subject;
		$mail->Body = $message;

		$mail->send(); //отправляем сообщение
	}
	else {
		echo "Ошибка! Ваш E-mail не подтвержден!";
		//если же полученный из url и сгенерированный код не равны, то выдаем ошибку
	}
?>

<?
	include "footer.php";
?>
