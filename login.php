<?
include_once "config.php";
include_once "checkrights.php";

switch( $_GET["do"] ) {
	// Отправка номера телефона и получение идентификатора
	case "add":
		unset($_SESSION['sms_code']);
		unset($_SESSION['mtel']);

		if( $_GET['mtel'] ) {
			$chars = array("+", " ", "(", ")"); // Символы, которые требуется удалить из строки с телефоном
			$mtel = str_replace($chars, "", $_GET['mtel']);

			// проверяем, сущестует ли пользователь с таким телефоном
			$query = "SELECT act FROM Users WHERE phone='{$mtel}'";
			$result = mysqli_query( $mysqli, $query );
			if( mysqli_num_rows($result) ) {
				$myrow = mysqli_fetch_array($result);
				// Пользователь актевен?
				if( $myrow["act"] ) {

					// Отправляем телефон на ожидиние звонка
					$body = file_get_contents("https://sms.ru/callcheck/add?api_id=".($api_id)."&phone=".($mtel)."&json=1");
					$json = json_decode($body);
					$_SESSION["error"][] = $json;
					if( $json ) { // Получен ответ от сервера
						if( $json->status == "OK" ) { // Запрос выполнился
							// Сохраняем check_id и call_phone_html
							$check_id = $json->check_id;
							//$call_phone_html = "&lt;a href=\"tel:{$json->call_phone}\"&gt;{$json->call_phone_pretty}&lt;\/a&gt;";
							$call_phone_html = $json->call_phone_html;
							$call_phone_html = str_replace("callto:", "tel:", $call_phone_html);
						}
						else $_SESSION["error"][] = "Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...) Код ошибки: $json->status_code Текст ошибки: $json->status_text";
					} else $_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";
				}
				else $_SESSION["error"][] = "Ваша учетная запись не активна! Свяжитесь с администрацией.";
			}
			else $_SESSION["error"][] = "Пользователя с таким телефоном не существует!";
		}
		else $_SESSION["error"][] = "Вы не ввели номер телефона!";

		// Если не было ошибок - сохраняем check_id в JS переменную
		if( count($_SESSION["error"] ) == 0) {
			$_SESSION['mtel'] = $mtel;
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
			else $_SESSION["error"][] = "Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...) Код ошибки: $json->status_code Текст ошибки: $json->status_text";
		}
		else $_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";

		// Если не было ошибок - проверяем check_id
		if( count($_SESSION["error"] ) == 0) {
			// Если звонок поступил - активируем сессию и заходим в систему
			if( $check_status == 401 ) {
				$query = "SELECT USR_ID, last_url FROM Users WHERE phone='{$_SESSION['mtel']}'";
				$result = mysqli_query( $mysqli, $query );
				$myrow = mysqli_fetch_array($result);
				$_SESSION["id"] = $myrow["USR_ID"];
				unset($_SESSION['mtel']);
				echo "location.href = '{$myrow["last_url"]}';";
			}
		}
		// Иначе перезагружаем страницу
		else echo "location.reload();";
	break;
	//////////////////////////////////////////////
	// Отправляем SMS код
	case "smscode":
		if( isset($_SESSION["sms_code"]) ) die();
		$sms_code = rand(1000, 9999);
		$body = file_get_contents("https://sms.ru/sms/send?api_id=".($api_id)."&to=".($_SESSION['mtel'])."&msg=Пароль:+".($sms_code)."&json=1");
		$json = json_decode($body);
		if( $json ) { // Получен ответ от сервера
			if( $json->status == "OK" ) { // Запрос выполнился
				$_SESSION["sms_code"] = $sms_code;
			}
			else $_SESSION["error"][] = "Запрос не выполнился (возможно ошибка авторизации, параметрах, итд...) Код ошибки: $json->status_code Текст ошибки: $json->status_text";
		}
		else $_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";

		// Если не было ошибок - показываем форму ввода пароля
		if( count($_SESSION["error"] ) == 0) {
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
		}
		// Иначе перезагружаем страницу
		else echo "location.reload();";
	break;
	//////////////////////////////////////////////
	// Экран входа
	default:

		$title = 'Вход в личный кабинет';
		include "header.php";
		// Если пользователь авторизован - отправляем на главную
		if( isset($_SESSION['id']) ) {
			exit ('<meta http-equiv="refresh" content="0; url=/">');
			die;
		}

		// Если введен СМС-код
		if( isset($_GET["sms"]) ) {
			// Если код верный - сохраняем в сессию пользователя и покидаем экран
			if( $_POST["sms_code"] == $_SESSION["sms_code"] ) {
				$query = "SELECT USR_ID, last_url FROM Users WHERE phone='{$_SESSION['mtel']}'";
				$result = mysqli_query( $mysqli, $query );
				$myrow = mysqli_fetch_array($result);
				$_SESSION['id'] = $myrow['USR_ID'];
				unset($_SESSION['sms_code']);
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

				var stop_status,
					progressbar = $( "#progressbar" ),
					progressLabel = $( ".progress-label" );
				progressbar.progressbar({
					value: false,
					complete: function() {
						progressLabel.text( "Звонок не поступил" );
						setTimeout( function() { location.reload(); }, 3000 );
						//setTimeout( function() { $('#smscode button').click(); }, 1000 );
					}
				});

				function status(check_id) {
					$.ajax({ url: "login.php?do=status&check_id="+check_id, dataType: "script", async: false });

					var val = progressbar.progressbar( "value" ) || 0;

					progressbar.progressbar( "value", val + 1 );

					if ( val < 100 && stop_status != 1 && check_status != 401 ) {
						setTimeout( function() { status(check_id); }, 3000 );
					}
					// Кнопка СМС появляется через 12 секунд
					if( val == 3) {
						$('#smscode').show('fast');
					}
				}

				$('#login form').on("submit", function(){
					var mtel = $('#mtel').val();
					$('#progressbar').show();
					$('#subbut').hide();
					//$('#smscode').show();
					$('#mtel').attr('readonly', true);

					// Отправляем телефон, с которого должен поступить звонок
					$.ajax({ url: "login.php?do=add&mtel="+mtel, dataType: "script", async: false });

					// Узнаем статус звонка
					setTimeout( function() { status(check_id); }, 2000 );

					// Отправка SMS-кода
					$('#smscode button').click(function() {
						$(this).prop('disabled', true);
						stop_status = 1;

						// Отправляем SMS-код и отображаем форму ввода
						$.ajax({ url: "login.php?do=smscode", dataType: "script", async: true });
						return false;
					});

					return false;
				})
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
						<div id="progressbar" style="display: none;"><div class="progress-label">Ожидание звонка...</div></div>
					</div>
					<div id="subbut" style="text-align: right;"><input type="submit" value="Войти »"></div>
				</form>
				<div id="smscode" style="text-align: center; display: none;">Не удаётся дозвониться?<br><button>Выслать SMS-код »</button></div>
			</div>
			<p><sup>*</sup>КИС - корпоративная информационная система</p>
		</div>

		<div id="send_code_form" style="display: none;">
			<form method='post' action='?sms'>
				<input type='text' name='sms_code' placeholder='SMS-код' autocomplete='off'>
				<button>ОК</button>
			</form>
		</div>

<?
	include "footer.php";
}
?>
