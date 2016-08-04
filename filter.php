<?
	// Фильтрация главной таблицы
	session_start();
	include "config.php";
	
	// Запись в сессию параметров фильтра
	$_SESSION["f_CD"] = $_GET["f_CD"];	// Код
	$_SESSION["f_CN"] = $_GET["f_CN"];	// Заказчик
	$_SESSION["f_SD"] = $_GET["f_SD"];	// дата приема
	$_SESSION["f_ED"] = $_GET["f_ED"];	// Дата сдачи
	$_SESSION["f_SH"] = $_GET["f_SH"];	// Салон
	$_SESSION["f_ON"] = $_GET["f_ON"];	// № квитанции
	$_SESSION["f_N"] = $_GET["f_N"];	// Примечание
	$_SESSION["f_Z"] = $_GET["f_Z"];	// Заказ
	$_SESSION["f_X"] = $_GET["f_X"];	// Пометка X
	$_SESSION["f_IP"] = $_GET["f_IP"];	// Cтатус лакировки
	$_SESSION["f_CR"] = $_GET["f_CR"];	// Цвет
	$_SESSION["f_T"] = $_GET["f_T"];	// Ткань
	$_SESSION["f_P"] = $_GET["f_P"];	// Пластик
	$_SESSION["f_PR"] = $_GET["f_PR"];	// Работник

	header( "Location: ".$_GET["location"] ); // Перезагружаем экран
	die;
?>
