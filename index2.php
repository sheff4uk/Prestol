<?
	include "config.php";
	if( isset($_GET["shpid"]) ) {
		$title = 'Отгрузка';
	}
	else {
		$title = 'Престол главная';
	}
	include "header2.php";
	echo "<h1>TEST</h1>";
	include "footer.php";
?>
