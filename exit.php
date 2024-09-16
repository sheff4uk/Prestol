<?php
	session_start();
	unset($_SESSION['id']);// уничтожаем переменные в сессиях
	unset($_SESSION['end_date']);
	unset($_SESSION['cash_from']);
	unset($_SESSION['cash_to']);
	exit("<html><head><meta http-equiv='Refresh' content='0; URL=/'></head></html>");
	// отправляем пользователя на главную страницу.
?>
