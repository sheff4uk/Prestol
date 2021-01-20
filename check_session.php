<?
session_start();
include_once "config.php";

if( empty($_SESSION['id']) ) {
	$act = 0;
}
else {
	$query = "SELECT act FROM Users WHERE USR_ID = {$_SESSION['id']}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$act = mysqli_result($res,0,'act');
}

if( !$act ) {
	if( $_GET["script"] == 1 ) {
		echo "location.reload();";
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/login.php">');
	}
}
?>
