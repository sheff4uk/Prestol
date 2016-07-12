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
	$query = "SELECT USR_ID FROM Users WHERE Login='{$login}'";
	$result = mysqli_query( $mysqli, $query ); //извлекаем идентификатор пользователя с данным логином
	$myrow = mysqli_fetch_array($result);
	$mailconfirm = md5($myrow['USR_ID']).md5($login);//создаем такой же код подтверждения
	if ($mailconfirm == $code) {//сравниваем полученный из url и сгенерированный код
		$query = "UPDATE Users SET Mailconfirm = 1 WHERE Login = {$login}";
		mysqli_query( $mysqli, $query ); //если равны, то активируем пользователя
		echo "Ваш E-mail подтвержден! Для активации учетной записи свяжитесь с администрацией: admin@fabrikaprestol.ru";
	}
	else {
		echo "Ошибка! Ваш E-mail не подтвержден!";
		//если же полученный из url и сгенерированный код не равны, то выдаем ошибку
	}
?>
