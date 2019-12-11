<?
include "config.php";
include "checkrights.php";

if( $_GET["PFB_ID"] ) {
	// Проверка прав на доступ к экрану
	if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
	$filename = $_GET["type"].'_'.$_GET["PFB_ID"].'_'.$_GET["number"].'.pdf';
}
elseif( $_GET["PFI_ID"] ) {
	// Проверка прав на доступ к экрану
	if( !in_array('sverki_all', $Rights) and !in_array('sverki_city', $Rights) and !in_array('sverki_opt', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
	$filename = $_GET["type"].'_'.$_GET["PFI_ID"].'_'.$_GET["number"].'.pdf';
}
elseif( $_GET["PFD_ID"] ) {
	// Проверка прав на доступ к экрану
	if( !in_array('doverennost', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
	$filename = $_GET["type"].'_'.$_GET["PFD_ID"].'_'.$_GET["number"].'.pdf';
}

if( $out = file_get_contents('print_forms/'.$filename) ) {
	header('Content-Type: application/pdf');
	header('Content-Length: '.strlen( $out ));
	header('Content-disposition: inline; filename="' . $filename . '"');
	header('Cache-Control: public, must-revalidate, max-age=0');
	print $out;
}
else {
	print "<html><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>";
	print "<h1 style='text-align: center;'>Файл не найден!</h1>";
}
?>
