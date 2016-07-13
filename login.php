<?
	include "config.php";
	$title = 'Вход в личный кабинет';
	include "header.php";
	header('Location: /reg.php');
	// Проверяем, не пусты ли переменные логина и id пользователя
	if (!empty($_SESSION['login']) and !empty($_SESSION['id'])) {
		header('Location: /');
	}

	if (isset($_POST['login']) and isset($_POST['password'])) {
		$login = $_POST['login']; if ($login == '') { unset($login);} //заносим введенный пользователем логин в переменную $login, если он пустой, то уничтожаем переменную
		$passwd=$_POST['password']; if ($passwd =='') { unset($passwd);} //заносим введенный пользователем пароль в переменную $passwd, если он пустой, то уничтожаем переменную
		if (empty($login) or empty($passwd)) //если пользователь не ввел логин или пароль, то выдаем ошибку и останавливаем скрипт
		{
			exit ("Вы ввели не всю информацию, вернитесь назад и заполните все поля!");
		}
		//если логин и пароль введены,то обрабатываем их, чтобы теги и скрипты не работали, мало ли что люди могут ввести
		$login = stripslashes($login);
		$login = htmlspecialchars($login);
		$passwd = stripslashes($passwd);
		$passwd = htmlspecialchars($passwd);
		//удаляем лишние пробелы
		$login = trim($login);
		$passwd = trim($passwd);
		$passwd = md5($passwd);//шифруем пароль
		$passwd = strrev($passwd);// для надежности добавим реверс
		$passwd = $passwd."9di63";

		$query = "SELECT * FROM Users WHERE Login='{$login}' AND Activation = 1";
		$result = mysqli_query( $mysqli, $query ); //извлекаем из базы все данные о пользователе с введенным логином
		$myrow = mysqli_fetch_array($result);
		if (empty($myrow['Password']))
		{
			//если пользователя с введенным логином не существует
			exit ("Извините, введённый Вами логин неверный. Или Ваша учетная запись не активирована. <a href='/'>Главная страница</a>");
		}
		else {
			//если существует, то сверяем пароли
			if ($myrow['Password']==$passwd) {
				//если пароли совпадают, то запускаем пользователю сессию
				$_SESSION['login']=$myrow['Login'];
				$_SESSION['id']=$myrow['USR_ID'];
				$_SESSION['name']=$myrow['Name'];
//				echo "Вы успешно вошли на сайт! <a href='/'>Главная страница</a>";
				header('Location: /');
			}
			else {
				//если пароли не сошлись
				exit ("Извините, введённый Вами пароль неверный. <a href='/'>Главная страница</a>");
			}
		}
	}
?>

	<div id="login">
		<H1>КИС<sup>*</sup> Престол</H1>
		<h3>Вход в личный кабинет</h3>

		<form method="post">
			<div><label>Ваш логин:</label><input required type="text" name="login" minlength="3" maxlength="15"></div>
			<div><label>Пароль:</label><input required type="password" name="password" minlength="3" maxlength="15"></div>
			<div><button>Вход »</button></div>
		</form>
		<a href="reg.php">Зарегистрироваться</a>
		<p><sup>*</sup>КИС - корпоративная информационная система</p>
	</div>

</body></html>
