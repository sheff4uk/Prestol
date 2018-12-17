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
		$query = "
			INSERT INTO OrdersData(CLientName, AddDate, StartDate, EndDate, SH_ID, OrderNumber, CL_ID, Comment, author, confirmed)
			SELECT CLientName, '{$AddDate}', NULL, IF(SH_ID IS NULL, NULL, '".date('Y-m-d', strtotime($_SESSION["end_date"]))."'), SH_ID, OrderNumber, CL_ID, Comment, {$_SESSION['id']}, {$_GET["confirmed"]}
			FROM OrdersData
			WHERE OD_ID = {$_GET["id"]}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$id = mysqli_insert_id( $mysqli );

		// Узнаем категорию подразделения (розница, опт, региональный опт) чтобы вычислить цену
		$query = "
			SELECT IF(SH.retail = 1, 1, IF(SH.reg = 1, 3, IF(SH.retail IS NOT NULL, 2, 0))) type
			OrdersData OD
			LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE OD.OD_ID = {$id}
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$row = mysqli_fetch_array($res);
		$type = $row["type"];

		$query = "
			INSERT INTO OrdersDataDetail(OD_ID, PM_ID, BL_ID, Other, PF_ID, PME_ID, box, Length, Width, PieceAmount, PieceSize, piece_stored, edge, MT_ID, Amount, Comment, min_price, Price, author, ptn)
			SELECT {$id}, PM_ID, BL_ID, Other, PF_ID, PME_ID, box, Length, Width, PieceAmount, PieceSize, piece_stored, edge, MT_ID, Amount, Comment, Price(ODD_ID, {$type}), Price(ODD_ID, {$type}), {$_SESSION['id']}, ptn
			FROM OrdersDataDetail
			WHERE OD_ID = {$_GET["id"]} AND Del = 0
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$odd_id = mysqli_insert_id( $mysqli );

		// Вычисляем и записываем стоимость по прайсу
		$query = "CALL Price({$odd_id})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url=orderdetail.php?id='.$id.'">');
		die;
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}
?>
