<?
include "config.php";
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('order_add', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

	if( isset($_GET["id"]) ) {
		$AddDate = date("Y-m-d");
		$query = "INSERT INTO OrdersData(CLientName, AddDate, StartDate, EndDate, SH_ID, OrderNumber, CL_ID, Comment, author, confirmed)
				  SELECT CLientName, '{$AddDate}', NULL, IF(SH_ID IS NULL, NULL, '".date('Y-m-d', strtotime($_SESSION["end_date"]))."'), SH_ID, OrderNumber, CL_ID, Comment, {$_SESSION['id']}, {$_GET["confirmed"]}
				  FROM OrdersData
				  WHERE OD_ID = {$_GET["id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$id = mysqli_insert_id( $mysqli );

		$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, PF_ID, PME_ID, box, Length, Width, PieceAmount, PieceSize, MT_ID, Amount, Comment, author, ptn)
				  SELECT {$id}, PM_ID, PF_ID, PME_ID, box, Length, Width, PieceAmount, PieceSize, MT_ID, Amount, Comment, {$_SESSION['id']}, ptn
				  FROM OrdersDataDetail
				  WHERE OD_ID = {$_GET["id"]} AND Del = 0";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$odd_id = mysqli_insert_id( $mysqli );

		// Вычисляем и записываем стоимость по прайсу
		$query = "CALL Price({$odd_id})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$query = "INSERT INTO OrdersDataBlank(OD_ID, BL_ID, Other, MT_ID, Amount, Comment, author, ptn)
				  SELECT {$id}, BL_ID, Other, MT_ID, Amount, Comment, {$_SESSION['id']}, ptn
				  FROM OrdersDataBlank
				  WHERE OD_ID = {$_GET["id"]} AND Del = 0";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url=orderdetail.php?id='.$id.'">');
		die;
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}
?>
