<?
include "config.php";
$title = 'Вход в личный кабинет';
include "header.php";
// Если пользователь авторизован - отправляем на главную
if( !empty($_SESSION['id']) ) {
	exit ('<meta http-equiv="refresh" content="0; url=/">');
	die;
}

// Если введен СМС-код
if( isset($_GET["sms"]) ) {
	// Если код верный - сохраняем в сессию пользователя и покидаем экран
	if( $_POST["sms_code"] == $_SESSION["sms_code"] ) {
		$query = "SELECT USR_ID, last_url FROM Users WHERE phone={$_SESSION['phone']}";
		$result = mysqli_query( $mysqli, $query );
		$myrow = mysqli_fetch_array($result);
		$_SESSION['id'] = $myrow['USR_ID'];

		exit ('<meta http-equiv="refresh" content="0; url='.$myrow["last_url"].'">');
		die;
	}
	else {
		$_SESSION["error"][] = "Вы ввели неверный код.{$_POST["sms_code"]}_{$_SESSION["sms_code"]}";
		exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
		die;
	}
}

// Веден номер телефона
if (isset($_POST['user_mt'])) {

	// проверяем, сущестует ли пользователь с таким телефоном
	$chars = array("+", " ", "(", ")"); // Символы, которые требуется удалить из строки с телефоном
	$mtel = str_replace($chars, "", $_POST["user_mt"]);

	$query = "SELECT USR_ID, Activation, last_url FROM Users WHERE phone='{$mtel}'";
	$result = mysqli_query( $mysqli, $query );
	if (mysqli_num_rows($result)) {

		$myrow = mysqli_fetch_array($result);

		if (!$myrow["Activation"]) {
			$_SESSION["error"][] = "Ваша учетная запись не активна! Свяжитесь с администрацией.";
		}

		if(count($_SESSION["error"]) == 0) {

			//Дожидаемся звонка от пользователя
			$body = file_get_contents("https://sms.ru/callcheck/add?api_id=".($api_id)."&phone=".($mtel)."&json=1");
			$json = json_decode($body);
			if ($json) { // Получен ответ от сервера
				if ($json->status == "OK") { // Запрос выполнился
					// Сохраняем check_id
					$check_id = $json->check_id;
					// В цикле опрашиваем SMS.RU чтобы узнать статус звонка
					for( $i=0; $i<24; $i++ ) {
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
						$sms_code = rand(1000, 9999);
						$body = file_get_contents("https://sms.ru/sms/send?api_id=".($api_id)."&to=".($mtel)."&msg=Пароль:+".($sms_code)."&json=1");
						$json = json_decode($body);
						if ($json) { // Получен ответ от сервера
							if ($json->status == "OK") { // Запрос выполнился
								// Рисуем форму для принятия СМС-кода
								echo "
									<form method='post' action='?sms'>
										<input type='text' name='sms_code' placeholder='SMS-код' autocomplete='off'>
										<button>ОК</button>
									</form>
								";
								$_SESSION['sms_code'] = $sms_code;
								$_SESSION['phone'] = $mtel;
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

			if(count($_SESSION["error"]) == 0) {
				$_SESSION['id'] = $myrow['USR_ID'];

				exit ('<meta http-equiv="refresh" content="0; url='.$myrow["last_url"].'">');
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
		$_SESSION["error"][] = "Пользователя с таким телефоном не существует!";

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
		.seconds {
			font-size: 1.5em;
		}
	</style>

	<script>
		$(document).ready(function() {

			$('input[name="subbut"]').click(function() {
				$('#mtel').attr('readonly', true);
				$('#call_msg').show();
				const time = $('.seconds');
				intervalId = setInterval(timerDecrement, 1000);

				function timerDecrement() {
					const newTime = time.text() - 1;
					time.text(newTime);
					if(newTime === 0) clearInterval(intervalId);
				}
			});
		});
	</script>

	<div id="login">
		<img src="/img/logo.png" style="width: 171px; margin: auto; display: block;">
		<H1>КИС<sup>*</sup> Престол</H1>
		<h3>Вход в личный кабинет</h3>

		<form method="POST" onsubmit="JavaScript:this.subbut.disabled=true;
	this.subbut.value='Ожидание звонка';">
			<div>
				<label>Телефон</label>
				<input type="text" name="user_mt" id="mtel" style="font-size: 1.5em;" value="" autocomplete="on" placeholder="Моб. телефон">
				<div id="call_msg" style="display: none;">Оставшееся время: <span class="seconds">120</span> секунд.</div>
			</div>
			<div style="text-align: right;"><input type="submit" name="subbut" value="Войти »"></div>
		</form>
		<p><sup>*</sup>КИС - корпоративная информационная система</p>
	</div>

<?
include "footer.php";
?>
