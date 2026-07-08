<?php
	// Выводим собранные в сесии сообщения через noty
	if( isset($_SESSION["error"]) ) {
		foreach ($_SESSION["error"] as $value) {
//			$value = str_replace("\n", "", addslashes(htmlspecialchars($value)));
			$value = str_replace("\n", "", addslashes($value));
			echo "<script>$(document).ready(function() {noty({text: '{$value}', type: 'error'});});</script>";
		}
		unset($_SESSION["error"]);
	}

	if( isset($_SESSION["alert"]) ) {
		foreach ($_SESSION["alert"] as $value) {
//			$value = str_replace("\n", "", addslashes(htmlspecialchars($value)));
			$value = str_replace("\n", "", addslashes($value));
			echo "<script>$(document).ready(function() {noty({timeout: 10000, text: '{$value}', type: 'alert'});});</script>";
		}
		unset($_SESSION["alert"]);
	}

	if( isset($_SESSION["success"]) ) {
		foreach ($_SESSION["success"] as $value) {
//			$value = str_replace("\n", "", addslashes(htmlspecialchars($value)));
			$value = str_replace("\n", "", addslashes($value));
			echo "<script>$(document).ready(function() {noty({timeout: 10000, text: '{$value}', type: 'success'});});</script>";
		}
		unset($_SESSION["success"]);
	}

	// Уведомления для продавцов
	if ( isset($_SESSION["id"]) && !in_array('order_add_confirm', $Rights) ) {
		$USR_ID = $_SESSION["id"];

		// Цикл по непрочитанным уведомлениям
		$query = "
			SELECT
				N.N_ID,
				N.notification,
				Friendly_date(N.notification_time) friendly_notification_time,
				USR_ShortName(N.author) author
			FROM NotificationsUsers NU
			LEFT JOIN Notifications N ON N.N_ID = NU.N_ID
			LEFT JOIN Users U ON U.USR_ID = NU.USR_ID
			WHERE NU.USR_ID = {$USR_ID}
				AND NU.read_time IS NULL
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_assoc($res) ) {
			$N_ID = $row["N_ID"];
			$notification = str_replace(array("\r\n", "\r", "\n"), '\n', $row["notification"]);
			echo "
				<script>
					noty({
						modal: true,
						timeout: false,
						text: '<div style=\'max-height: 300px; overflow-y: scroll;\'><h2 class=\'user-text\'>{$notification}</h2></div><p style=\'text-align: right;\'>{$row["friendly_notification_time"]} <b>{$row["author"]}</b></p>',
						buttons: [
							{addClass: 'btn btn-primary', text: 'Отметить как прочитанное', onClick: function (\$noty) {
								\$(this).attr('disabled', true);
								\$(this).html('<i class=\"fa-solid fa-gear fa-spin\"></i>');
								var tariff = \$('#tariff').val();
								var patina_tariff = \$('#patina_tariff').val();
								\$.ajax({
									url: 'ajax.php?do=notification_read&usr_id={$USR_ID}&n_id={$N_ID}',
									dataType: 'script',
									async: true,
									complete: function(data) {
										\$noty.close();
									}
								});
							}
							},
							{addClass: 'btn btn-danger', text: 'Отмена', onClick: function (\$noty) {
								\$noty.close();
								noty({timeout: 3000, text: 'Вы нажали кнопку \"Отмена\"', type: 'error'});
								self.dfd.resolve(false);
							}
							}
						],
						type: 'alert'
					});
				</script>
			";
		}
	}
?>
