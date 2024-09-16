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
?>
