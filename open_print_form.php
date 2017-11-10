<?
include "config.php";
include "checkrights.php";

// Проверка прав на доступ к экрану
if( !in_array('print_forms_view_all', $Rights) and !in_array('print_forms_view_author', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

// Проверка автора если есть соответствующее право
if( in_array('print_forms_view_author', $Rights) ) {
	$query = "SELECT USR_ID FROM PrintForms WHERE PF_ID = {$_GET["PF_ID"]}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	if( mysqli_result($res,0,'USR_ID') != $_SESSION['id'] ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}
}

if( $_GET["PF_ID"] ) {
	$filename = $_GET["type"].'_'.$_GET["PF_ID"].'_'.$_GET["number"].'.pdf';
}
elseif( $_GET["PFI_ID"] ) {
	$filename = $_GET["type"].'_'.$_GET["PFI_ID"].'_'.$_GET["number"].'.pdf';
}

if( $out = file_get_contents('print_forms/'.$filename) ) {
	header('Content-Type: application/pdf');
	header('Content-Length: '.strlen( $out ));
	header('Content-disposition: inline; filename="' . $filename . '"');
	header('Cache-Control: public, must-revalidate, max-age=0');
	print $out;
}
else {
	print "<h1 style='text-align: center;'>Файл не найден!</h1>";
}
?>
