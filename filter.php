<?
	// Фильтрация главной таблицы
	session_start();
	include "config.php";

switch( $_GET["do"] )
{
	// Фильтр таблици денежных операций
	case "cash":
		if( $_GET["all_accounts"] ) {							// Счета
			$_SESSION["cash_account"] = "";
		}
		else {
			$_SESSION["cash_account"] = $_GET["FA_ID"];
		}
		$_SESSION["cash_type"] = $_GET["cash_type"];			// Тип
		$_SESSION["cash_sum_from"] = $_GET["cash_sum_from"];	// Сумма от
		$_SESSION["cash_sum_to"] = $_GET["cash_sum_to"];		// Сумма до
		if( $_GET["all_categories"] ) {							// Категории
			$_SESSION["cash_category"] = "";
		}
		else {
			$_SESSION["cash_category"] = $_GET["FC_ID"];
		}
	break;
	
	// Фильтр главной таблицы
	default:
		// Запись в сессию параметров фильтра
		$_SESSION["f_CD"] = $_GET["f_CD"];			// Код
		$_SESSION["f_CN"] = $_GET["f_CN"];			// Заказчик
		$_SESSION["f_SD"] = $_GET["f_SD"];			// дата приема
		$_SESSION["f_ED"] = $_GET["f_ED"];			// Дата сдачи
		$_SESSION["f_SH"] = $_GET["f_SH"];			// Салон
		$_SESSION["f_ON"] = $_GET["f_ON"];			// № квитанции
		$_SESSION["f_N"] = $_GET["f_N"];			// Примечание
		$_SESSION["f_Z"] = $_GET["f_Z"];			// Заказ
		$_SESSION["f_X"] = $_GET["f_X"];			// Пометка X
		$_SESSION["f_IP"] = $_GET["f_IP"];			// Cтатус лакировки
		$_SESSION["f_CR"] = $_GET["f_CR"];			// Цвет
//		$_SESSION["f_M"] = $_GET["f_M"];			// Материал
		$_SESSION["f_M"] = $_GET["MT_ID"];			// Материал
		$_SESSION["f_PR"] = $_GET["f_PR"];			// Работник
		$_SESSION["f_CF"] = $_GET["f_CF"];			// Статус принятия заказа
		if( substr($_GET["f_PR"], 0, 1) === "0" ) {	// Статус этапа
			$_SESSION["f_ST"] = "";
		}
		else {
			$_SESSION["f_ST"] = $_GET["f_ST"];
		}
	break;
}

	header( "Location: ".$_GET["location"] ); // Перезагружаем экран
	die;
?>
