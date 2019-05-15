<?
include "config.php";
$title = 'Вход в личный кабинет';
include "header.php";
// Если пользователь авторизован - отправляем на главную
if (!empty($_SESSION['login']) and !empty($_SESSION['id'])) {
	exit ('<meta http-equiv="refresh" content="0; url=/">');
	die;
}

function set_end_date() {
	// Отсчитываем дату сдачи - 30 раб. дней и записываем в сессию
	if( !isset($_SESSION["end_date"]) or $_SESSION["today"] != date('d.m.Y') ) {
		$_SESSION["today"] = date('d.m.Y');
		$end_date = date_create(date('Y-m-d'));
		$working_days = 0;
		$year = 0;
		while ($working_days < 30) {
			date_modify($end_date, '+1 day');
			// Если при подсчете рабочих дней изменился год, то получаем новый календарь
			if( $year != date('Y', strtotime(date_format($end_date, 'd.m.Y'))) ) {
				$year = date('Y', strtotime(date_format($end_date, 'd.m.Y')));
				$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
				$json = json_encode($xml);
				$data = json_decode($json,TRUE);
			}
			$day_of_week = date('N', strtotime(date_format($end_date, 'd.m.Y')));
			$month = date('m', strtotime(date_format($end_date, 'd.m.Y')));
			$day = date('d', strtotime(date_format($end_date, 'd.m.Y')));
			// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
			$t = 0;
			foreach( $data["days"]["day"] as $key=>$value ) {
				if( $value["@attributes"]["d"] == $month.".".$day) {
					$t = $value["@attributes"]["t"];
				}
			}
			// Если очередной день - рабочий, то увеличиваем счетчик
			if ( !(($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) {
				++$working_days;
			}
		}
		$_SESSION["end_date"] = date_format($end_date, 'd.m.Y');
	}
}

// Если введен СМС-код
if( isset($_GET["sms"]) ) {
	// Если код верный - сохраняем в сессию пользователя и покидаем экран
	if( $_POST["sms_code"] == $_SESSION["sms_code"] ) {
		$query = "SELECT Login, Name FROM Users WHERE USR_ID={$_SESSION['id']}";
		$result = mysqli_query( $mysqli, $query );
		$myrow = mysqli_fetch_array($result);
		$_SESSION['login'] = $myrow['Login'];

		// Обнуляем неудачные попытки входа
		mysqli_query ($mysqli, "UPDATE Users SET try = 0 WHERE USR_ID = {$_SESSION['id']}");

		set_end_date();

		if( $_GET["location"] ) {
			exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'">');
		}
		else {
			exit ('<meta http-equiv="refresh" content="0; url=/">');
		}
		die;
	}
	else {
		$_SESSION["error"][] = "Вы ввели неверный код.";
		exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
		die;
	}
}

if (isset($_POST['submit'])) {

	// проверяем, сущестует ли пользователь с таким логином
	$query = "SELECT USR_ID, Login, Mailconfirm, Activation, password_hash, phone, try FROM Users WHERE Login='".mysqli_real_escape_string($mysqli, $_POST['login'])."'";
	$result = mysqli_query( $mysqli, $query );
	if (mysqli_num_rows($result)) {

		$passwd = md5($_POST['password']);//шифруем пароль
		$passwd = strrev($passwd);// для надежности добавим реверс
		$passwd = $passwd."9di63";

		$myrow = mysqli_fetch_array($result);

		if (!$myrow["Mailconfirm"]) {
			$_SESSION["error"][] = "Ваш адрес электронной почты не подтвержден. Пожалуйста перейдите по ссылке в письме.";
		}
		elseif (!$myrow["Activation"]) {
			$_SESSION["error"][] = "Ваша учетная запись не активна. Свяжитесь с администрацией.";
		}
		elseif (!password_verify($_POST['password'], $myrow["password_hash"]) and $myrow["password_hash"] != $passwd) {
			// Увеличиваем счетчик неверных попыток
			$try = $myrow["try"] + 1;
			mysqli_query ($mysqli, "UPDATE Users SET try = {$try} WHERE USR_ID = {$myrow['USR_ID']}");

			if ($try > 4) {
				$_SESSION["error"][] = "Вы ввели неверный пароль 5 раз подряд. Ваша учетная запись заблокирована. Свяжитесь с администрацией.";
				mysqli_query ($mysqli, "UPDATE Users SET Activation = 0, try = 0 WHERE USR_ID = {$myrow['USR_ID']}");
			}
			else {
				$_SESSION["error"][] = "Вы ввели неверный пароль. Осталось ".(5 - $try)." попытки до блокировки.";
			}
		}

		if(count($_SESSION["error"]) == 0) {
			// Если старый шифр пароля - меняем на новый
			if ($myrow["password_hash"] == $passwd) {
				$password = password_hash($_POST['password'], PASSWORD_BCRYPT);
				mysqli_query ($mysqli, "UPDATE Users SET password_hash = '{$password}' WHERE USR_ID = {$myrow['USR_ID']}");
			}

			//если у пользователя указан телефон - нужно дождаться с него звонка
			if($myrow['phone']) {
				$body = file_get_contents("https://sms.ru/callcheck/add?api_id=".($api_id)."&phone=".($myrow['phone'])."&json=1");
				$json = json_decode($body);
				if ($json) { // Получен ответ от сервера
					if ($json->status == "OK") { // Запрос выполнился
						// Сохраняем check_id
						$check_id = $json->check_id;
						// В цикле опрашиваем SMS.RU чтобы узнать статус звонка
						for( $i=0; $i<12; $i++ ) {
							sleep(5);
							// Проверка статуса звонка
							$body = file_get_contents("https://sms.ru/callcheck/status?api_id=".($api_id)."&check_id=".($check_id)."&json=1");
							$json = json_decode($body);
							if ($json) { // Получен ответ от сервера
								if ($json->status == "OK") { // Запрос выполнился
									$check_status = $json->check_status;
									if( $check_status == 401 ) break;
								}
							}
						}
						// Если нужный ответ не был получен - отправляем код по СМС
						if( $check_status != 401 ) {
							$sms_code = rand(100000, 999999);
							$body = file_get_contents("https://sms.ru/sms/send?api_id=".($api_id)."&to=".($myrow['phone'])."&msg=Пароль:+".($sms_code)."&json=1");
							$json = json_decode($body);
							if ($json) { // Получен ответ от сервера
								if ($json->status == "OK") { // Запрос выполнился
									// Рисуем форму для принятия СМС-кода
									echo "
										<form method='post' action='?sms=1?location={$_GET["location"]}'>
											<input type='text' name='sms_code'>
										</form>
									";
									$_SESSION['sms_code'] = $sms_code;
									$_SESSION['id'] = $myrow['USR_ID'];
									die;
								}
								else {
									$_SESSION["error"][] = "Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...) Код ошибки: $json->status_code Текст ошибки: $json->status_text";
								}
							}
						}
					}
					else {
						$_SESSION["error"][] = "Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...) Код ошибки: $json->status_code Текст ошибки: $json->status_text";
					}
				} else {
					$_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";
				}
			}

			if(count($_SESSION["error"]) == 0) {
				$_SESSION['login'] = $myrow['Login'];
				$_SESSION['id'] = $myrow['USR_ID'];

				// Обнуляем неудачные попытки входа
				mysqli_query ($mysqli, "UPDATE Users SET try = 0 WHERE USR_ID = {$myrow['USR_ID']}");

				set_end_date();

				if( isset($_GET["location"]) ) {
					exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'">');
				}
				else {
					exit ('<meta http-equiv="refresh" content="0; url=/">');
				}
				die;
			}
			else {
				exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
				die;
			}
		}
		else {
			exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
			die;
		}
	}
	else {
		$_SESSION["error"][] = "Пользователя с таким логином не существует.";

		// Проверка на подбор паролей
		$ip = getenv("HTTP_X_FORWARDED_FOR");
		if (empty($ip) || $ip=='unknown') {//извлекаем ip
			$ip = getenv("REMOTE_ADDR");
		}
		mysqli_query ($mysqli, "DELETE FROM LoginErrors WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(date) > 900");//удаляем ip-адреса ошибавшихся при входе пользователей старше 15 минут.
		$result = mysqli_query($mysqli, "SELECT col FROM LoginErrors WHERE ip='{$ip}'");// извлекаем из базы количество неудачных попыток входа с данного ip
		$myrow = mysqli_fetch_array($result);
		if ($myrow['col'] > 2) {
			//если ошибок больше двух, т.е три, то выдаем сообщение.
			$_SESSION["error"][] = "Вы совершили 3 неудачных попытки входа. Подождите 15 минут до следующей попытки.";
		}
		else {
			// Иначе увеличиваем счетчик для этого ip
			mysqli_query( $mysqli, "INSERT INTO LoginErrors (ip, date, col) VALUES ('{$ip}', NOW(), '1') ON DUPLICATE KEY UPDATE date=NOW(), col=col+1" );
		}

		exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
		die;
	}
}
?>
	<style>
		body {
			background: url(img/curve-wood-texture-tekstura.jpg) center !important;
/*			background: url(img/den-svyatogo-valentina.jpg) center !important;*/
/*			background: url(img/oboi-ng-2018-7.jpg) center !important;*/
			background-size: cover!important;
			height: 100vh;
		}
	</style>

	<div id="login">
		<img src="/img/logo.png" style="width: 171px; margin: auto; display: block;">
		<H1>КИС<sup>*</sup> Престол</H1>
		<h3>Вход в личный кабинет</h3>

		<form method="POST">
			<div><label>Логин</label><input type="text" name="login" minlength="3" maxlength="15" autocomplete="off"></div>
			<div><label>Пароль</label><input type="password" name="password" minlength="3" maxlength="15"></div>
			<div style="text-align: right;"><input name="submit" type="submit" value="Войти »"></div>
		</form>
		<a href="reg.php"><b>Зарегистрироваться</b></a>
		<p><sup>*</sup>КИС - корпоративная информационная система</p>
	</div>

<?
include "footer.php";
?>
