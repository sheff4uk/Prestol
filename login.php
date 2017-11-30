<?
	include "config.php";
	$title = 'Вход в личный кабинет';
	include "header.php";
	// Проверяем, не пусты ли переменные логина и id пользователя
	if (!empty($_SESSION['login']) and !empty($_SESSION['id'])) {
		exit ('<meta http-equiv="refresh" content="0; url=/">');
	}

	if (isset($_POST['login']) and isset($_POST['password'])) {
		$login = $_POST['login']; if ($login == '') { unset($login);} //заносим введенный пользователем логин в переменную $login, если он пустой, то уничтожаем переменную
		$passwd = $_POST['password']; if ($passwd =='') { unset($passwd);} //заносим введенный пользователем пароль в переменную $passwd, если он пустой, то уничтожаем переменную
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

		// минипроверка на подбор паролей
		$ip=getenv("HTTP_X_FORWARDED_FOR");
		if (empty($ip) || $ip=='unknown') {//извлекаем ip
			$ip=getenv("REMOTE_ADDR");
		}
		mysqli_query ($mysqli, "DELETE FROM LoginErrors WHERE UNIX_TIMESTAMP() - UNIX_TIMESTAMP(date) > 900");//удаляем ip-адреса ошибавшихся при входе пользователей через 15 минут.
		$result = mysqli_query($mysqli, "SELECT col FROM LoginErrors WHERE ip='{$ip}'");// извлекаем из базы количество неудачных попыток входа за последние 15 минут у пользователя с данным ip
		$myrow = mysqli_fetch_array($result);

		if ($myrow['col'] > 2) {
			//если ошибок больше двух, т.е три, то выдаем сообщение.
			exit("Вы набрали логин или пароль неверно 3 раза. Подождите 15 минут до следующей попытки.");
		}

		$hash = password_hash($passwd, PASSWORD_BCRYPT);
		$passwd = md5($passwd);//шифруем пароль
		$passwd = strrev($passwd);// для надежности добавим реверс
		$passwd = $passwd."9di63";

		$query = "SELECT * FROM Users WHERE Login='{$login}' AND Activation = 1";
		$result = mysqli_query( $mysqli, $query ); //извлекаем из базы все данные о пользователе с введенным логином
		$myrow = mysqli_fetch_array($result);
		if (empty($myrow['USR_ID']))
		{
			//если пользователя с введенным логином не существует
			exit ("Извините, введённый Вами логин неверный. Или Ваша учетная запись не активирована. <a href='/'>Главная страница</a>");
		}
		else {
			//если существует, то сверяем пароли
			if ($myrow['Password']==$passwd) {
				mysqli_query ($mysqli, "UPDATE Users SET password_hash = '{$hash}' WHERE USR_ID = {$myrow['USR_ID']}");

				//если у пользователя указан телефон - нужно дождаться с него звонка
				if($myrow['phone']) {
					$body = file_get_contents("https://sms.ru/callcheck/add?api_id=AF15C40F-52ED-156A-2013-C33A6A15003E&phone=".($myrow['phone'])."&json=1");
					$json = json_decode($body);
					if ($json) { // Получен ответ от сервера
						if ($json->status == "OK") { // Запрос выполнился
							// Сохраняем check_id
							$check_id = $json->check_id;
							// В цикле опрашиваем SMS.RU чтобы узнать статус звонка
							for( $i=0; $i<12; $i++ ) {
								sleep(5);
								// Проверка статуса звонка
								$body = file_get_contents("https://sms.ru/callcheck/status?api_id=AF15C40F-52ED-156A-2013-C33A6A15003E&check_id=".($check_id)."&json=1");
								$json = json_decode($body);
								if ($json) { // Получен ответ от сервера
									if ($json->status == "OK") { // Запрос выполнился
										$check_status = $json->check_status;
										if( $check_status == 401 ) break;
									}
								}
							}
							// Если нужный ответ не был получен - останавливаем скрипт
							if( $check_status != 401 ) {
								exit ("Время ожидания статуса звонка истекло.");
							}
						}
						else {
							exit("Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...)<br>Код ошибки: $json->status_code<br>Текст ошибки: $json->status_text");
						}
					} else {
						exit("Запрос не выполнился Не удалось установить связь с сервером.");
					}
				}

				$_SESSION['login']=$myrow['Login'];
				$_SESSION['id']=$myrow['USR_ID'];
				$_SESSION['name']=$myrow['Name'];

				if( isset($_GET["location"]) ) {
					exit ('<meta http-equiv="refresh" content="0; url='.$_GET["location"].'">');
				}
				else {
					exit ('<meta http-equiv="refresh" content="0; url=/">');
				}
			}
			else {
				//если пароли не сошлись
				//Делаем запись о том, что данный ip не смог войти.
				$select = mysqli_query ($mysqli, "SELECT ip FROM LoginErrors WHERE ip='{$ip}'");
				$tmp = mysqli_fetch_row ($select);
				if ($ip == $tmp[0]) {//проверяем, есть ли пользователь в таблице "LoginErrors"
					$result52 = mysqli_query($mysqli, "SELECT col FROM LoginErrors WHERE ip='{$ip}'");
					$myrow52 = mysqli_fetch_array($result52);
					$col = $myrow52[0] + 1;//прибавляем еще одну попытку неудачного входа
					mysqli_query ($mysqli, "UPDATE LoginErrors SET col = {$col}, date = NOW() WHERE ip = '{$ip}'");

					//если ошибок 3, то отправляем письмо
					if ($col == 3) {
						$from = "admin@fabrikaprestol.ru";
						$subject = "[КИС Престол] 3 неудачных попытки входа";//тема сообщения
						$message = "Здравствуйте! Было зарегистрировано 3 неудачных попытки входа в учетную запись {$myrow['Login']} с ip адреса: {$ip}";//содержание сообщения

						// Отправляем письмо на указанный ящик пользователя через PHPMailer
						require "PHPMailer/PHPMailerAutoload.php";
						$mail = new PHPMailer(true);
						$mail->isSMTP();
						$mail->SMTPAuth = true;
						$mail->SMTPSecure = "ssl";
						$mail->Host = "smtp.yandex.ru";
						$mail->Port = "465";
						$mail->Username = "admin@fabrikaprestol.ru";
						$mail->Password = "GmvN6*D%";
						$mail->CharSet = "UTF-8";
						$mail->ContentType = 'text/plain';
						$mail->addAddress($myrow['Email']);
						$mail->addReplyTo($from);
						$mail->setFrom($from);
						$mail->Subject = $subject;
						$mail->Body = $message;
						$mail->send(); //отправляем сообщение
					}
				}
				else {
					mysqli_query ($mysqli, "INSERT INTO LoginErrors (ip, date, col) VALUES ('{$ip}', NOW(), '1')");
					//если за последние 15 минут ошибок не было, то вставляем новую запись в таблицу "LoginErrors"
				}
				exit ("Извините, введённый Вами пароль неверный. <a href='/'>Главная страница</a>");
			}
		}
	}
?>

	<div id="login">
		<H1>КИС<sup>*</sup> Престол</H1>
		<h3>Вход в личный кабинет</h3>

		<form method="post">
			<div><label>Ваш логин:</label><input type="text" name="login" minlength="3" maxlength="15" autocomplete="off"></div>
			<div><label>Пароль:</label><input type="password" name="password" minlength="3" maxlength="15"></div>
			<div><button>Вход »</button></div>
		</form>
		<a href="reg.php">Зарегистрироваться</a>
		<p><sup>*</sup>КИС - корпоративная информационная система</p>
	</div>

<?
	include "footer.php";
?>
