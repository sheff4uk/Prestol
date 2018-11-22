<?
include "config.php";
$title = 'Регистрация';
include "header.php";
// Проверяем, не пусты ли переменные логина и id пользователя
if (!empty($_SESSION['login']) and !empty($_SESSION['id'])) {
	exit ('<meta http-equiv="refresh" content="0; url=/">');
	die;
}

if(isset($_POST['submit'])) {
	// проверям логин
	if(!preg_match("/^[a-zA-Z0-9]+$/",$_POST['login']))
	{
		$_SESSION["error"][] = "Логин может состоять только из букв английского алфавита и цифр";
	}

	if(strlen($_POST['login']) < 3 or strlen($_POST['login']) > 30)
	{
		$_SESSION["error"][] = "Логин должен быть не меньше 3-х символов и не больше 30";
	}

	//проверка е-mail адреса регулярными выражениями на корректность
	if (!preg_match("/[0-9a-z_]+@[0-9a-z_^\.]+\.[a-z]{2,3}/i", $_POST['email']))
	{
		$_SESSION["error"][] = "Неверно введен е-mail";
	}

	// проверяем, не сущестует ли пользователя с таким именем
	$query = "SELECT USR_ID FROM Users WHERE Login='".mysqli_real_escape_string($mysqli, $_POST['login'])."'";
	$result = mysqli_query( $mysqli, $query );
	if(mysqli_num_rows($result))
	{
		$_SESSION["error"][] = "Пользователь с таким логином уже существует";
	}

	// Если нет ошибок, то добавляем в БД нового пользователя
	if(count($_SESSION["error"]) == 0)
	{

		$login = $_POST['login'];
		$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

		// Обработка строк
		$name = convert_str($_POST["name"]);
		$name = mysqli_real_escape_string($mysqli, $name);
		$surname = convert_str($_POST["surname"]);
		$surname = mysqli_real_escape_string($mysqli, $surname);
		$email = convert_str($_POST["email"]);
		$email = mysqli_real_escape_string($mysqli, $email);

		// Сохраняем нового пользователя в БД
		$query = "INSERT INTO Users (Login, password_hash, Name, Surname, Email, Date) VALUES('{$login}', '{$password}', '{$name}', '{$surname}', '{$email}', NOW())";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		//узнаем идентификатор пользователя. Благодаря ему у нас и будет уникальный код активации, ведь двух одинаковых идентификаторов быть не может.
		$USR_ID = mysqli_insert_id($mysqli);
		$mailconfirm = md5($USR_ID).md5($login);//код подтверждения почты. Зашифруем через функцию md5 идентификатор и логин.
		$from = "admin@fabrikaprestol.ru";
		$subject = "[КИС Престол] подтверждение E-mail";//тема сообщения
		$message = "Здравствуйте, {$name}!\nВы зарегистрировались в Корпоративной Информационной Системе ПРЕСТОЛ\nВаш логин: {$login}\nПерейдите по ссылке, чтобы подтвердить Ваш E-mail:\nhttps://kis.fabrikaprestol.ru/mailconfirm.php?login={$login}&code={$mailconfirm}\n\nПосле этого дождитесь уведомления об активации учетной записи от администрации.\n\nС уважением,\nАдминистрация КИС Престол";//содержание сообщения

		// Отправляем письмо на указанный ящик пользователя через PHPMailer
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
		$mail->addAddress($email);
		$mail->addReplyTo($from);
		$mail->setFrom($from);
		$mail->Subject = $subject;
		$mail->Body = $message;

		if ($mail->send()) {
			$_SESSION["success"][] = "Поздравляем! На Ваш E-mail {$email} выслано письмо с дальнейшими инструкциями.";
		}
		else {
			$_SESSION["error"][] = "Ошибка! Письмо не отправлено. Свяжитесь с администрацией admin@fabrikaprestol.ru";
		}

		exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
		die;
	}
	else
	{
		exit ('<meta http-equiv="refresh" content="0; url=/reg.php">');
		die;
	}
}
?>
	<style>
		body {
			background: url(img/parquet_flooring-wide.jpg) center !important;
			background-size: cover!important;
			height: 100vh;
		}
	</style>

	<div id="login">
		<H1>КИС<sup>*</sup> Престол</H1>
		<h3>Регистрация пользователя</h3>

		<form method="POST">
			<div><label>Имя</label><input required type="text" name="name"></div>
			<div><label>Фамилия</label><input required type="text" name="surname"></div>
			<div><label title="Логин может состоять только из букв английского алфавита и цифр. Логин должен быть не меньше 3-х символов и не больше 30-ти."><i class="fas fa-question-circle"></i>Логин</label><input required type="text" name="login" minlength="3" maxlength="30"></div>
			<div><label title="Пароль должен быть не меньше 6-ти символов."><i class="fas fa-question-circle"></i>Пароль</label><input required type="password" name="password" minlength="6"></div>
			<div><label title="На указанный адрес электронной почты придет письмо для подтверждения регистрации."><i class="fas fa-question-circle"></i>Email</label><input required type="email" name="email"></div>
			<div style="text-align: right;"><input name="submit" type="submit" value="Зарегистрироваться »"></div>
		</form>
		<p><sup>*</sup>КИС - корпоративная информационная система</p>
	</div>

<?
	include "footer.php";
?>
