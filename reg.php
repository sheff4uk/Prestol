<?
	include "config.php";
	$title = 'Регистрация';
	include "header.php";
	// Проверяем, не пусты ли переменные логина и id пользователя
	if (!empty($_SESSION['login']) and !empty($_SESSION['id'])) {
		echo '<meta http-equiv="refresh" content="0; url=/">';
	}
?>

	<div id="login">
		<H1>КИС<sup>*</sup> Престол</H1>
		<h3>Регистрация пользователя</h3>

		<form action="save_user.php" method="post">
			<div><label>Имя:</label><input required type="text" name="name" maxlength="255"></div>
			<div><label>Ваш логин:</label><input required type="text" name="login" minlength="3" maxlength="15"></div>
			<div><label>Пароль:</label><input required type="password" name="password" minlength="3" maxlength="15"></div>
			<div><label>Email:</label><input required type="email" name="email" maxlength="255"></div>
			<div><button>Регистрация »</button></div>
		</form>
		<p><sup>*</sup>КИС - корпоративная информационная система</p>
	</div>

</body></html>
