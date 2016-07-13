<?
	include "config.php";
	$title = 'Вход в личный кабинет';
	include "header.php";

	if (isset($_POST['login'])) { $login = $_POST['login']; if ($login == '') { unset($login);} } //заносим введенный пользователем логин в переменную $login, если он пустой, то уничтожаем переменную
	if (isset($_POST['password'])) { $passwd=$_POST['password']; if ($passwd =='') { unset($passwd);} } //заносим введенный пользователем пароль в переменную $passwd, если он пустой, то уничтожаем переменную
	if (isset($_POST['name'])) { $name=$_POST['name']; if ($name =='') { unset($name);} }
	if (isset($_POST['email'])) { $email=$_POST['email']; if ($email =='') { unset($email);} }

	if (empty($login) or empty($passwd) or empty($name) or empty($email)) //если пользователь не ввел логин или пароль, то выдаем ошибку и останавливаем скрипт
	{
		exit ("Вы ввели не всю информацию, вернитесь назад и заполните все поля!");
	}
	if (!preg_match("/[0-9a-z_]+@[0-9a-z_^\.]+\.[a-z]{2,3}/i", $email)) //проверка е-mail адреса регулярными выражениями на корректность
	{
		exit ("Неверно введен е-mail!");
	}

	//если логин и пароль введены, то обрабатываем их, чтобы теги и скрипты не работали, мало ли что люди могут ввести
	$login = stripslashes($login);
	$login = htmlspecialchars($login);
	$passwd = stripslashes($passwd);
	$passwd = htmlspecialchars($passwd);
	$name = stripslashes($name);
	$name = htmlspecialchars($name);
	$email = stripslashes($email);
	$email = htmlspecialchars($email);

	//удаляем лишние пробелы
	$login = trim($login);
	$passwd = trim($passwd);
	$name = trim($name);
	$email = trim($email);

	//добавляем проверку на длину логина и пароля
	if (strlen($login) < 3 or strlen($login) > 15) {
		exit ("Логин должен состоять не менее чем из 3 символов и не более чем из 15.");
	}
	if (strlen($passwd) < 3 or strlen($passwd) > 15) {
		exit ("Пароль должен состоять не менее чем из 3 символов и не более чем из 15.");
	}

	$passwd = md5($passwd);//шифруем пароль
	$passwd = strrev($passwd);// для надежности добавим реверс
	$passwd = $passwd."9di63";

	// проверка на существование пользователя с таким же логином
	$query = "SELECT USR_ID FROM Users WHERE Login='{$login}'";
	$result = mysqli_query( $mysqli, $query );
	$myrow = mysqli_fetch_array($result);
	if (!empty($myrow['USR_ID'])) {
		exit ("Извините, введённый вами логин уже зарегистрирован. Введите другой логин.");
	}
	// если такого нет, то сохраняем данные
	$query = "INSERT INTO Users (Login, Password, Name, Email, Date) VALUES('{$login}', '{$passwd}', '{$name}', '{$email}', NOW())";
	$result2 = mysqli_query( $mysqli, $query );
	// Проверяем, есть ли ошибки
	if ($result2=='TRUE')
	{
		$query = "SELECT USR_ID FROM Users WHERE Login='{$login}'";
		$result3 = mysqli_query( $mysqli, $query ); //извлекаем идентификатор пользователя. Благодаря ему у нас и будет уникальный код активации, ведь двух одинаковых идентификаторов быть не может.
		$myrow3 = mysqli_fetch_array($result3);
		$mailconfirm = md5($myrow3['USR_ID']).md5($login);//код подтверждения почты. Зашифруем через функцию md5 идентификатор и логин.
		$from = "admin@fabrikaprestol.ru";
		$subject = "[КИС Престол] подтверждение E-mail";//тема сообщения
		$message = "Здравствуйте! Вы зарегистрировались в Корпоративной Информационной Системе ПРЕСТОЛ\nВаш логин: {$login}\nПерейдите по ссылке, чтобы подтвердить Ваш E-mail:\nhttps://kis.fabrikaprestol.ru/mailconfirm.php?login={$login}&code={$mailconfirm}\n\nДля активации учетной записи свяжитесь с администрацией: admin@fabrikaprestol.ru\n\nС уважением,\nАдминистрация КИС Престол";//содержание сообщения

		// Отправляем письмо на указанный ящик пользователя через PHPMailer
//		require "PHPMailer/PHPMailerAutoload.php";
		$mail = new PHPMailer(true);
		$mail->isSMTP();
		$mail->SMTPAuth = true;
		$mail->SMTPSecure = "tls";
		$mail->Host = "smtp.yandex.ru";
		$mail->Port = "25";
		$mail->Username = "admin@fabrikaprestol.ru";
		$mail->Password = "GmvN6*D%";
		$mail->CharSet = "UTF-8";
		$mail->ContentType = 'text/plain';
		$mail->addAddress($email);
		$mail->addReplyTo($from);
		$mail->setFrom($from);
		$mail->Subject = $subject;
		$mail->Body = $message;

			echo "На Ваш E-mail {$email} выслано письмо со cсылкой, для подтверждения регистрации. Внимание! Ссылка действительна 1 час. <a href='/'>Главная страница</a>"; //говорим об отправленном письме пользователю
		if ($mail->send()) {//отправляем сообщение
			echo "На Ваш E-mail {$email} выслано письмо со cсылкой, для подтверждения регистрации. Внимание! Ссылка действительна 1 час. <a href='/'>Главная страница</a>"; //говорим об отправленном письме пользователю
		}
		else {
			exit ("Ошибка! Письмо не отправлено. Свяжитесь с администрацией admin@fabrikaprestol.ru");
		}
	}
	else {
		echo "Ошибка! Вы не зарегистрированы.";
	}
?>
