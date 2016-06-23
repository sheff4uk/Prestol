<?
	include "config.php";
	$title = 'Вход в личный кабинет';
	include "header.php";
?>

	<div id="login">
		<H1>КИС<sup>*</sup> Престол</H1>
		<h3>Вход в личный кабинет</h3>

		<form method="post">
			<div><label>Пользователь:</label><input required type="text" name="login"></div>
			<div><label>Пароль:</label><input required type="password" name="password"></div>
			<div><button>Вход »</button></div>
		</form>
		<p><sup>*</sup>КИС - корпоративная информационная система</p>
	</div>

</body></html>
