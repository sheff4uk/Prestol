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
			$query = "SELECT act FROM Users WHERE phone='{$mtel}'";
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
						else $_SESSION["error"][] = "Звонок не может быть выполнен. Текст ошибки: $json->status_text";
					} else $_SESSION["error"][] = "Запрос не выполнился Не удалось установить связь с сервером.";
				}
				else $_SESSION["error"][] = "Ваша учетная запись не активна! Свяжитесь с администрацией.";
			}
			else $_SESSION["error"][] = "Пользователя с таким телефоном не существует!";
		}
		else $_SESSION["error"][] = "Вы не ввели номер телефона!";

		// Если не было ошибок, показываем форму для ввода кода
		if( count($_SESSION["error"] ) == 0) {
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
			echo "noty({text: '<h1>Поступит звонок со случайного номера.<br><br>Введите последние 4 цифры определившегося номера.</h1>', type: 'alert'});";
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

		// Если введен код
		if( isset($_POST["code"]) ) {
			// Если код верный - сохраняем в сессию пользователя и покидаем экран
			if( $_POST["code"] == $_SESSION["code"] ) {
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
		</style>

		<script>
			$(document).ready(function() {

				$('#login form').on("submit", function(){
					var mtel = $('#mtel').val();
					$('#subbut').hide();
					$('#mtel').attr('readonly', true);

					// Отправляем телефон, на который должен поступить звонок
					$.ajax({ url: "login.php?do=add&mtel="+mtel, dataType: "script", async: false });

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
					</div>
					<div id="subbut" style="text-align: right;"><input type="submit" value="Войти »"></div>
				</form>
			</div>
			<p><sup>*</sup>КИС - корпоративная информационная система</p>
		</div>

		<div id="send_code_form" style="display: none;">
			<form method='post'>
				<input type='text' name='code' placeholder='последние 4 цифры' autocomplete='off'>
				<button>ОК</button>
			</form>
		</div>

<?
	include "footer.php";
}
?>
