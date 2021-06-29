<?
include "config.php";
include "checkrights.php";

//Добавление/редактирование пользователя
if( isset($_POST["USR_ID"]) ) {
	session_start();

	$chars = array("+", " ", "(", ")"); // Символы, которые требуется удалить из строки с телефоном
	$phone = $_POST["phone"] ? '\''.str_replace($chars, "", $_POST["phone"]).'\'' : 'NULL';
	$act = $_POST["act"] ? 1 : 0;
	$head = $_POST["head"] ? $_POST["head"] : 'NULL';
	$KA_ID = $_POST["KA_ID"] ? $_POST["KA_ID"] : 'NULL';
	$tariff = $_POST["tariff"] ? $_POST["tariff"] : 'NULL';

	// Обработка строк
	$Surname = convert_str($_POST["Surname"]);
	$Surname = mysqli_real_escape_string($mysqli, $Surname);
	$Name = convert_str($_POST["Name"]);
	$Name = mysqli_real_escape_string($mysqli, $Name);

	// Салоны
	$SH_ID = "";
	foreach( $_POST["SH_ID"] as $key => $value ) {
		$SH_ID .= $key.",";
	}
	$SH_ID = $SH_ID ? "'".substr($SH_ID,0,-1)."'" : "NULL";

	if( $_POST["USR_ID"] == "add" ) {
		$query = "
			INSERT INTO Users
			SET Surname = '{$Surname}'
				,Name = '{$Name}'
				,act = {$act}
				,head = {$head}
				,phone = {$phone}
				,CT_ID = {$_POST["CT_ID"]}
				,RL_ID = {$_POST["RL_ID"]}
				,KA_ID = {$KA_ID}
				,tariff = {$tariff}
				,SH_ID = {$SH_ID}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$USR_ID = mysqli_insert_id( $mysqli );
		}
	}
	else {
		$query = "
			UPDATE Users
			SET Surname = '{$Surname}'
				,Name = '{$Name}'
				,act = {$act}
				,head = {$head}
				,phone = {$phone}
				,CT_ID = {$_POST["CT_ID"]}
				,RL_ID = {$_POST["RL_ID"]}
				,KA_ID = {$KA_ID}
				,tariff = {$tariff}
				,SH_ID = {$SH_ID}
			WHERE USR_ID = {$_POST["USR_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$USR_ID = $_POST["USR_ID"];
	}

	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = $add ? "Новая запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление
	exit ('<meta http-equiv="refresh" content="0; url=#'.$USR_ID.'">');
}

$title = 'Пользователи';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('users', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}
?>

<style>
	.not_act td {
		background: rgb(150,0,0, .3);
	}
</style>

<!--Таблица с пользавателями-->
<table class="MN_table">
	<thead>
		<tr>
			<th></th>
			<th>Фамилия</th>
			<th>Имя</th>
			<th>Телефон</th>
			<th>Роль</th>
			<th>Регион</th>
			<th>Салон</th>
			<th>Контрагент</th>
			<th>Тариф</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
		<?
		$query = "
			SELECT USR.USR_ID
				,USR_Icon(USR.USR_ID) icon
				,USR.Surname
				,USR.Name
				,USR.head
				,USR.phone
				,USR.RL_ID
				,USR.CT_ID
				,RL.Role
				,CT.City
				,IFNULL(USR.SH_ID, 0) SH_ID
				,USR.act
				,USR.KA_ID
				,KA.Naimenovanie
				,USR.tariff
				,USR.Balance
				,(SELECT COUNT(1) FROM Users WHERE head = USR.USR_ID AND act = 1) is_head
			FROM Users USR
			JOIN Roles RL ON RL.RL_ID = USR.RL_ID
			JOIN Cities CT ON CT.CT_ID = USR.CT_ID
			LEFT JOIN Kontragenty KA ON KA.KA_ID = USR.KA_ID
			ORDER BY USR.RL_ID
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			//формируем массив для JSON данных
			$users_data[$row["USR_ID"]] = $row;

			//Список салонов продавца
			$query = "
				SELECT SH_ID, Shop
				FROM Shops
				WHERE SH_ID IN({$row["SH_ID"]})
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$USR_shops = "";
			while( $subrow = mysqli_fetch_array($subres) ) {
				$users_data[$row["USR_ID"]]["shops"][] = $subrow["SH_ID"];
				$USR_shops .= $subrow["Shop"]."<br>";
			}

			$rowstyle = $row["act"] ? "" : "background: rgb(150,0,0, .3);";
			echo "
				<tr id='{$row["USR_ID"]}' class='".($row["act"] ? "" : "not_act")."'>
					<td>{$row["icon"]}</td>
					<td>{$row["Surname"]}</td>
					<td>{$row["Name"]}</td>
					<td>{$row["phone"]}</td>
					<td>{$row["Role"]}</td>
					<td>{$row["City"]}</td>
					<td>{$USR_shops}</td>
					<td>{$row["Naimenovanie"]}</td>
					<td>{$row["tariff"]}</td>
					<td><a href='#' class='add_user' usr='{$row["USR_ID"]}' title='Изменить данные пользователя'><i class='fa fa-pencil-alt fa-lg'></i></td>
				</tr>
			";
		}
		?>
	</tbody>
</table>

<div id='add_btn' class="add_user" title='Добавить пользователя'></div>

<div id='user_form' class='addproduct' title='Данные пользователя' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<div id="USR_ID" style="width: auto; float: right;"></div>
			<input type="hidden" name="USR_ID">
			<div id="act">
				<label>Активен:</label>
				<div>
					<input type='checkbox' name='act' value="1">
				</div>
			</div>
			<div>
				<label>Фамилия:</label>
				<div>
					<input type='text' name='Surname' autocomplete='off' required>
				</div>
			</div>
			<div>
				<label>Имя:</label>
				<div>
					<input type='text' name='Name' autocomplete='off' required>
				</div>
			</div>
			<div>
				<label>Телефон:</label>
				<div>
					<input type="text" name="phone" id="mtel" style="width: 150px;" autocomplete="off" autofocus>
					<br>
					<span style='color: #911; font-size: .8em;'>Необходим для доступа к личному кабинету</span>
				</div>
			</div>
			<div>
				<label>Регион:</label>
				<div>
					<select name="CT_ID" required>
						<option value=""></option>
						<?
						$query = "SELECT CT_ID, City FROM Cities";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["CT_ID"]}'>{$row["City"]}</option>";
						}
						?>
					</select>
				</div>
			</div>
			<div>
				<label>Роль:</label>
				<div>
					<select name="RL_ID" required>
						<option value=""></option>
						<?
						$query = "SELECT RL_ID, Role FROM Roles";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["RL_ID"]}'>{$row["Role"]}</option>";
						}
						?>
					</select>
				</div>
			</div>
			<div id="shops">
				<label>Салоны:</label>
				<div>
					<?
					// Список розничных салонов для продавцов
					$query = "
						SELECT CT_ID, SH_ID, Shop
						FROM Shops
						WHERE retail = 1 AND stock = 0
						ORDER BY CT_ID, SH_ID
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						// Массив для JSON
						$shops[$row["CT_ID"]][] = $row["SH_ID"];

						echo "<label class='shop_label' style='display: block;'><input type='checkbox' name='SH_ID[{$row["SH_ID"]}]' val='1'>{$row["Shop"]}</label>";
					}
					?>
				</div>
			</div>
			<div id="kontragent">
				<label>Контрагент:</label>
				<div>
					<select name="KA_ID" style="width: 300px;">
						<option value=""></option>
						<?
						$query = "SELECT KA_ID, Naimenovanie FROM Kontragenty ORDER BY Naimenovanie";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]}</option>";
						}
						?>
					</select>
				</div>
			</div>
			<div id="tariff">
				<label>Тариф/час:</label>
				<div>
					<input type="number" name="tariff" min="0" max="999">
				</div>
			</div>
			<div id="head">
				<label>Руководитель:</label>
				<div>
					<select name="head" style="width: 300px;">
						<option value=""></option>
						<?
						$query = "SELECT USR_ID, USR_Name(USR_ID) USR_Name FROM Users WHERE RL_ID != 6 AND act = 1 ORDER BY USR_Name";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) ) {
							echo "<option value='{$row["USR_ID"]}'>{$row["USR_Name"]}</option>";
						}
						?>
					</select>
				</div>
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>

<script>
	users_data = <?= json_encode($users_data); ?>;
	shops = <?= json_encode($shops); ?>;

	$(function() {
		// Кнопка добавления набора
		$('.add_user').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			usr = $(this).attr('usr');

			// Очистка формы
			$('#user_form fieldset select[name="head"] option').attr('disabled', false);
			$('#user_form fieldset input:not([type="checkbox"])').val('');
			$('#user_form fieldset select').val('').trigger("change");
			$('#user_form input[name="act"]').prop('checked', true );
			$('#user_form input[name="USR_ID"]').val('add');
			$('#user_form input[name="act"]').attr('is_head', '0');

			if (usr) {
				$('#user_form #USR_ID').html(users_data[usr]['icon']);
				$('#user_form input[name="USR_ID"]').val(usr);
				$('#user_form input[name="act"]').prop('checked', users_data[usr]['act'] == 1 );
				$('#user_form input[name="act"]').attr('is_head', users_data[usr]['is_head']);
				$('#user_form input[name="Surname"]').val(users_data[usr]['Surname']);
				$('#user_form input[name="Name"]').val(users_data[usr]['Name']);
				$('#user_form input[name="phone"]').val(users_data[usr]['phone']);
				$('#user_form select[name="CT_ID"]').val(users_data[usr]['CT_ID']);
				$('#user_form select[name="RL_ID"]').val(users_data[usr]['RL_ID']).trigger("change");
				// Салоны продавца
				if( typeof users_data[usr]["shops"] !== 'undefined' ) {
					$.each(users_data[usr]["shops"], function(k, v) {
						$('#user_form input[name="SH_ID['+v+']"]').prop('checked', true);
					});
				}
				$('#user_form select[name="KA_ID"]').val(users_data[usr]['KA_ID']);
				$('#user_form input[name="tariff"]').val(users_data[usr]['tariff']);
				$('#user_form select[name="head"]').val(users_data[usr]['head']);
			}

			$('#user_form').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// При смене роли меняем содержимое формы
		$('#user_form select[name="RL_ID"]').on("change",function(){
			var RL_ID = $(this).val();

			//Если оптовик, предлагаем выбрать контрагента, иначе выбор руководителя
			if( RL_ID == 6 ) {
				$('#kontragent').show('fast');
				$('#kontragent select[name="KA_ID"]').attr("required", true);

				$('#head').hide('fast');
				$('#head select[name="head"]').val('');
			}
			else {
				$('#kontragent').hide('fast');
				$('#kontragent select[name="KA_ID"]').attr("required", false);
				$('#kontragent select[name="KA_ID"]').val('');

				$('#head').show('fast');
			}

			// Если почасовик, показываем тариф
			if( RL_ID == 11 ) {
				$('#tariff').show('fast');
				$('#tariff input[name="tariff"]').attr("required", true);
			}
			else {
				$('#tariff').hide('fast');
				$('#tariff input[name="tariff"]').attr("required", false);
				$('#tariff input[name="tariff"]').val('');
			}

			// Если продавец, эмулируем смену региона для запуска отображения салонов
			$('#user_form select[name="CT_ID"]').trigger("change");
		});

		// При смене региона выводим список салонов этого региона если роль продавца
		$('#user_form select[name="CT_ID"]').on("change",function(){
			// Узнаем выбранный регион и роль
			var CT_ID = $(this).val(),
				RL_ID = $('#user_form select[name="RL_ID"]').val();
			// Если продавец, выводим список салонов для региона
			if( RL_ID == 5 ) {
				$('#shops').show('fast');
				$('#shops input').prop('checked', false);
				$('#shops .shop_label').hide('fast');
				if( typeof shops[CT_ID] !== 'undefined' ) {
					$.each(shops[CT_ID], function(k, v) {
						$('#user_form input[name="SH_ID['+v+']"]').parent('.shop_label').show('fast');
					});
				}
			}
			else {
				$('#shops input').prop('checked', false);
				$('#shops').hide('fast');
			}
		});

		// При выборе руководителя, проверяем нет ли его среди подчиненных
		$('#user_form select[name="head"]').on("change",function(){
			var head = $(this).val(),
				usr = $('#user_form input[name="USR_ID"]').val();
			tree(usr, head, users_data);
		});

		//При попытке сделать неактивным проверяем есть ли подчиненные
		$('#user_form input[name="act"]').click(function() {
			var is_head = $(this).attr('is_head');
			if( is_head > 0 ) {
				noty({timeout: 3000, text: 'Нельзя выключить пользователя, у которого есть подчиненные!', type: 'error'});
				return false;
			}
		});

		function tree(usr, head, data) {
			if( head > 0 ) {
				if( usr != head ) {
					var head = data[head]["head"];
					tree(usr, head, data);
				}
				else {
					noty({timeout: 3000, text: 'Нельза выбрать руководителя из числа подчиненных или самого себя!', type: 'error'});
					$('#user_form select[name="head"] option:selected').attr('disabled', true);
					$('#user_form select[name="head"]').val('');
				}
			}
		}
	});

</script>
<?
include "footer.php";
?>
