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
		$activation = md5($myrow3['USR_ID']).md5($login);//код активации аккаунта. Зашифруем через функцию md5 идентификатор и логин. Такое сочетание пользователь вряд лисможет подобрать вручную через адресную строку.
		$subject = "Подтверждение регистрации Престол";//тема сообщения
		$message = "Здравствуйте! Вы зарегистрировались в Корпоративной Информационной Системе ПРЕСТОЛ\nВаш логин: {$login}\nПерейдите по ссылке, чтобы подтвердить Ваш E-mail:\nhttp://kis.fabrikaprestol.ru/mailconfirm.php?login={$login}&code={$activation}\nДля активации учетной записи свяжитесь с администрацией: admin@fabrikaprestol.ru\nС уважением,\nАдминистрация КИС Престол";//содержание сообщения
		if (mail($email, $subject, $message, "Content-type:text/plane; Charset=windows-1251\r\n")) {//отправляем сообщение
			echo "На Ваш E-mail {$email} выслано письмо со cсылкой, для подтверждения регистрации. Внимание! Ссылка действительна 1 час. <a href='/'>Главная страница</a>"; //говорим о отправленном письме пользователю
		}
		else {
			exit ("Ошибка! Письмо не отправлено. Свяжитесь с администрацией admin@fabrikaprestol.ru");
		}
//		header('Location: /');
	}
	else {
		echo "Ошибка! Вы не зарегистрированы.";
	}
?>
