<?
	include "config.php";
	$title = 'Активация пользователя';
	include "header.php";

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
	$query = "SELECT USR_ID, USR_Name(USR_ID) Name FROM Users WHERE Login = '{$login}'";
	$result = mysqli_query( $mysqli, $query ); //извлекаем идентификатор пользователя с данным логином
	$myrow = mysqli_fetch_array($result);
	$activation = md5($myrow['USR_ID'].$login).md5($login.$myrow['USR_ID']);//код активации аккаунта. Зашифруем через функцию md5 идентификатор + логин и логин + идентификатор.
	if ($activation == $code) {//сравниваем полученный из url и сгенерированный код
		$query = "UPDATE Users SET Activation = 1 WHERE Login = '{$login}'";
		mysqli_query( $mysqli, $query ); //если равны, то активируем пользователя
		echo "Учетная запись пользователя <b>{$myrow['Name']}</b> активирована!";
	}
	else {
		echo "Ошибка! Учетная запись НЕ активирована!";
		//если же полученный из url и сгенерированный код не равны, то выдаем ошибку
	}
?>

<?
	include "footer.php";
?>
