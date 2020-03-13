<?
	// Фильтрация главной таблицы
	session_start();
	include "config.php";

switch( $_GET["do"] )
{
	// Фильтр таблицы денежных операций
	case "cash":
		$_SESSION["cash_type"] = $_GET["cash_type"];			// Тип
		$_SESSION["cash_sum_from"] = $_GET["cash_sum_from"];	// Сумма от
		$_SESSION["cash_sum_to"] = $_GET["cash_sum_to"];		// Сумма до
		if( $_GET["all_accounts"] ) {							// Счета
			$_SESSION["cash_account"] = "";
		}
		else {
			$_SESSION["cash_account"] = $_GET["FA_ID"];
		}
		if( $_GET["all_categories"] ) {							// Категории
			$_SESSION["cash_category"] = "";
		}
		else {
			$_SESSION["cash_category"] = $_GET["FC_ID"];
		}
		if( $_GET["all_authors"] ) {							// Авторы
			$_SESSION["cash_author"] = "";
		}
		else {
			$_SESSION["cash_author"] = $_GET["USR_ID"];
		}
//		if( $_GET["all_kontragent"] ) {							// Контрагенты
//			$_SESSION["cash_kontragent"] = "";
//		}
//		else {
//			$_SESSION["cash_kontragent"] = $_GET["KA_ID"];
//		}
		$_SESSION["cash_kontragent"] = $_GET["cash_kontragent"];	// Контрагенты
		$_SESSION["cash_comment"] = $_GET["cash_comment"];		// Комментарий
	break;
	
	// Фильтр главной таблицы
	default:
		// Запись в сессию параметров фильтра
		$_SESSION["f_CD"] = trim($_GET["f_CD"]);			// Код
		$_SESSION["f_CN"] = trim($_GET["f_CN"]);			// Клиент
		$_SESSION["f_SD"] = trim($_GET["f_SD"]);			// дата приема
		$_SESSION["f_ED"] = trim($_GET["f_ED"]);			// Дата сдачи
		$_SESSION["f_EndDate"] = $_GET["f_EndDate"];		// Дата сдачи в работе
		$_SESSION["f_SH"] = trim($_GET["f_SH"]);			// Салон
		$_SESSION["f_N"] = trim($_GET["f_N"]);			// Примечание
		$_SESSION["f_Models"] = $_GET["f_Models"];			// Модель
//		$_SESSION["f_Z"] = trim($_GET["f_Z"]);			// Набор
		$_SESSION["f_X"] = $_GET["f_X"];			// Пометка X
		$_SESSION["f_IP"] = $_GET["f_IP"];			// Cтатус лакировки
		$_SESSION["f_CR"] = trim($_GET["f_CR"]);			// Цвет
		$_SESSION["f_M"] = $_GET["MT_ID"];			// Материал
		$_SESSION["f_PR"] = $_GET["f_PR"];			// Работник
		$_SESSION["f_CF"] = $_GET["f_CF"];			// Статус принятия набора
		if( substr($_GET["f_PR"], 0, 1) === "0" ) {	// Статус этапа
			$_SESSION["f_ST"] = "";
			$_SESSION["f_CH"] = "";
		}
		else {
			switch ($_GET["f_ST"]) {
				case "0":
					$_SESSION["f_ST"] = "0";
					$_SESSION["f_CH"] = "0";
				break;
				case "1":
					$_SESSION["f_ST"] = "1";
					$_SESSION["f_CH"] = "0";
				break;
				case "2":
					$_SESSION["f_ST"] = "0";
					$_SESSION["f_CH"] = "1";
				break;
				case "3":
					$_SESSION["f_ST"] = "1";
					$_SESSION["f_CH"] = "1";
				break;
				default:
					$_SESSION["f_ST"] = "";
					$_SESSION["f_CH"] = "";
				break;
			}
		}
	break;
}

	header( "Location: ".$_GET["location"] ); // Перезагружаем экран
	die;
?>
