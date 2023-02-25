<?
include_once "config.php";
include_once "checkrights.php";

switch( $_GET["do"] ) {
	// Отправка номера телефона и получение кода
	case "add":
		unset($_SESSION['code']);
		unset($_SESSION['mtel']);

		if( $_GET['mtel'] ) {
			$chars = array("+", " ", "(", ")"); // Символы, которые требуется удалить из строки с телефоном
			$mtel = str_replace($chars, "", $_GET['mtel']);

			// проверяем, сущестует ли пользователь с таким телефоном
			$query = "
				SELECT act
					,Surname
					,Name
					,IFNULL(chatid, '217756119') chatid
				FROM Users
				WHERE phone='{$mtel}'
			";
			$result = mysqli_query( $mysqli, $query );
			if( mysqli_num_rows($result) ) {
				$myrow = mysqli_fetch_array($result);
				// Пользователь актевен?
				if( $myrow["act"] ) {

					// Отправляем телефон на который поступит звонок
					//$body = file_get_contents("https://sms.ru/code/call?api_id=".($api_id)."&phone=".($mtel)."&ip=".$_SERVER["REMOTE_ADDR"]);

					$ch = curl_init("https://sms.ru/code/call");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_TIMEOUT, 30);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
						"phone" => $mtel, // номер телефона пользователя
						"ip" => $_SERVER["REMOTE_ADDR"], // ip адрес пользователя
						"api_id" => $api_id
					)));
					$body = curl_exec($ch);
					curl_close($ch);

					$json = json_decode($body);
					if( $json ) { // Получен ответ от сервера
						if( $json->status == "OK" ) { // Запрос выполнился
							// Сохраняем код в сессию для дальнейшей проверки
							$_SESSION["code"] = $json->code;
						}
						else {
							$_SESSION["error"][] = "Звонок не может быть выполнен. Текст ошибки: $json->status_text";
							//$_SESSION["code"] = $json->code;
							$_SESSION["code"] = rand(1000, 9999);
							message_to_telegram($myrow["Surname"]." ".$myrow["Name"]." ".$_SESSION["code"], $myrow["chatid"]);
						}
					} else {
						$_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";
					}
				}
				else $_SESSION["error"][] = "Ваша учетная запись не активна! Свяжитесь с администрацией.";
			}
			else $_SESSION["error"][] = "Пользователя с таким телефоном не существует!";
		}
		else $_SESSION["error"][] = "Вы не ввели номер телефона!";

		// Если возвращен код, показываем форму для ввода кода
		if( $_SESSION["code"] > 0 ) {
			$_SESSION['mtel'] = $mtel;
			echo "
				$('#send_code_form').dialog({
					dialogClass: 'no-close',
					resizable: false,
					draggable: false,
					width: 300,
					modal: true,
					closeOnEscape: false
				});
			";
			if( !isset($_SESSION["error"])  ) {
				echo "noty({text: '<h1>Поступит звонок со случайного номера.<br><br>Введите последние 4 цифры определившегося номера.</h1>', type: 'alert'});";
			}
			else {
				foreach ($_SESSION["error"] as $value) {
					$value = str_replace("\n", "", addslashes($value));
					echo "noty({text: '{$value}', type: 'error'});";
				}
				unset($_SESSION["error"]);
				//echo "noty({text: '<h1>Чтобы узнать код, свяжитесь с администратором.</h1>', type: 'alert'});";
				echo "noty({text: '<h1>Воспользуйтесь кнопкой альтернативного входа.</h1>', type: 'alert'});";
			}
		}
		// Иначе перезагружаем страницу
		else echo "location.reload();";
	break;
	//////////////////////////////////////////////
	// Отправка номера телефона и получение идентификатора
	case "alternative_login":

		$mtel = $_SESSION['mtel'];
		// Отправляем телефон на ожидиние звонка
		$body = file_get_contents("https://sms.ru/callcheck/add?api_id=".($api_id)."&phone=".($mtel)."&json=1");
		$json = json_decode($body);
		if( $json ) { // Получен ответ от сервера
			if( $json->status == "OK" ) { // Запрос выполнился
				// Сохраняем check_id и call_phone_html
				$check_id = $json->check_id;
				$call_phone_html = $json->call_phone_html;
				$call_phone_html = str_replace("callto:", "tel:", $call_phone_html);
			}
			else $_SESSION["error"][] = "Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...) Код ошибки: $json->status_code Текст ошибки: $json->status_text";
		} else $_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";

		// Если не было ошибок - сохраняем check_id в JS переменную
		if( !isset($_SESSION["error"])  ) {
			echo "var check_id = '{$check_id}';";
			echo "noty({text: '<h1>Позвоните по этому номеру для авторизации: {$call_phone_html}</h1>', type: 'alert'});";
		}
		// Иначе перезагружаем страницу
		else echo "location.reload();";
	break;
	//////////////////////////////////////////////
	// Проверка статуса звонка
	case "status":
		$body = file_get_contents("https://sms.ru/callcheck/status?api_id=".($api_id)."&check_id=".($_GET['check_id'])."&json=1");
		$json = json_decode($body);
		if( $json ) { // Получен ответ от сервера
			if( $json->status == "OK" ) { // Запрос выполнился
				// Сохраняем check_status
				$check_status = $json->check_status;
				echo "check_status = {$check_status};";
			}
			else $_SESSION["error"][] = "Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...) Код ошибки: $json->status_code Текст ошибки: $json->check_status_text";
		}
		else $_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";

		// Если не было ошибок - проверяем check_status
		if( !isset($_SESSION["error"])  ) {
			// Если звонок поступил - активируем сессию и заходим в систему
			if( $check_status == 401 ) {
				$query = "SELECT USR_ID, last_url FROM Users WHERE phone='{$_SESSION['mtel']}'";
				$result = mysqli_query( $mysqli, $query );
				$myrow = mysqli_fetch_array($result);
				$_SESSION["id"] = $myrow["USR_ID"];
				unset($_SESSION['code']);
				unset($_SESSION['mtel']);
				echo "location.href = '{$myrow["last_url"]}';";
			}
		}
		// Иначе перезагружаем страницу
		else echo "location.reload();";
	break;
	//////////////////////////////////////////////	// Экран входа
	default:

		$title = 'Вход в личный кабинет';
		include "header.php";
		// Если пользователь авторизован - отправляем на главную
		if( isset($_SESSION['id']) ) {
			exit ('<meta http-equiv="refresh" content="0; url=/">');
			die;
		}

		// Если введен код
		if( isset($_POST["code"]) ) {
			$code = str_replace(" ", "", $_POST['code']);
			// Если код верный - сохраняем в сессию пользователя и покидаем экран
			if( $code == $_SESSION["code"] ) {
				$query = "SELECT USR_ID, last_url FROM Users WHERE phone='{$_SESSION['mtel']}'";
				$result = mysqli_query( $mysqli, $query );
				$myrow = mysqli_fetch_array($result);
				$_SESSION['id'] = $myrow['USR_ID'];
				unset($_SESSION['code']);
				unset($_SESSION['mtel']);

				exit ('<meta http-equiv="refresh" content="0; url='.$myrow["last_url"].'">');
				die;
			}
			else {
				$_SESSION["error"][] = "Вы ввели неверный код!";
				exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
				die;
			}
		}

?>
		<style>
			body {
				background: url(img/curve-wood-texture-tekstura.jpg) center !important;
/*				background: url(img/den-svyatogo-valentina.jpg) center !important;*/
/*				background: url(img/oboi-ng-2018-7.jpg) center !important;*/
				background-size: cover!important;
				height: 100vh;
			}

			.ui-progressbar {
				position: relative;
			}
			.progress-label {
				position: absolute;
				left: 25%;
				top: 4px;
				font-weight: bold;
				text-shadow: 1px 1px 0 #fff;
			}
		</style>

		<script>
			$(document).ready(function() {

				var progressbar = $( "#progressbar" ),
					progressLabel = $( ".progress-label" );

				progressbar.progressbar({
					value: false,
					complete: function() {
						progressLabel.text( "Звонок не поступил" );
						setTimeout( function() { location.reload(); }, 3000 );
					}
				});

				function status(check_id) {
					$.ajax({ url: "login.php?do=status&check_id="+check_id, dataType: "script", async: false });

					var val = progressbar.progressbar( "value" ) || 0;

					progressbar.progressbar( "value", val + 1 );

					if ( val < 100 && check_status != 401 ) {
						setTimeout( function() { status(check_id); }, 3000 );
					}
				}

				$('#login form').on("submit", function(){
					var mtel = $('#mtel').val();
					$('#subbut').hide();
					$('#mtel').attr('readonly', true);

					// Отправляем телефон, на который должен поступить звонок
					$.ajax({ url: "login.php?do=add&mtel="+mtel, dataType: "script", async: false });

					$('#alternative_login').on("click", function(){
						$('#progressbar').show();
						$('#alternative_login').hide();

						// Отправляем телефон, с которого должен поступить звонок
						$.ajax({ url: "login.php?do=alternative_login&mtel="+mtel, dataType: "script", async: false });

						// Узнаем статус звонка
						setTimeout( function() { status(check_id); }, 2000 );
					});

					return false;
				})

				$("#code").mask('9 9 9 9', {
					onComplete: function(cep) {
						$('#send_code_form form').submit();
					}
				});
			});
		</script>

		<div id="login">
			<img src="/img/logo.png" style="width: 171px; margin: auto; display: block;">
			<H1>КИС<sup>*</sup> Престол</H1>
			<h3>Вход в личный кабинет</h3>

			<div id="form_wr">
				<form>
					<div>
						<label>Телефон</label>
						<input type="text" id="mtel" style="font-size: 1.5em;" value="" autocomplete="on" placeholder="Моб. телефон">
					</div>
					<div id="subbut" style="text-align: right;"><input type="submit" value="Войти »"></div>
				</form>
			</div>
			<p><sup>*</sup>КИС - корпоративная информационная система</p>
		</div>

		<div id="send_code_form" style="display: none;" title="Последние 4 цифры входящего">
			<form method='post'>
				<input type='text' id='code' name='code' autocomplete='off' style="font-size: 3em; width: 100%; text-align: center;" required>
				<br>
				<br>
<!--				<button style="width: 100%;">Продолжить</button>-->
				<a href="#" id="alternative_login" class="button" style="display: block; text-align: center;">Альтернативный вход</a>
				<div id="progressbar" style="display: none;"><div class="progress-label">Ожидание звонка...</div></div>
			</form>
		</div>

<?
	include "footer.php";
}
?>
