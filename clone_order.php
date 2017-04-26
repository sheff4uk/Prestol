<?
	include "config.php";

	if( isset($_GET["id"]) ) {
		$AddDate = date("Y-m-d");
		$query = "INSERT INTO OrdersData(CLientName, AddDate, StartDate, EndDate, SH_ID, OrderNumber, Color, Comment, creator, confirmed)
				  SELECT CLientName, '{$AddDate}', StartDate, EndDate, SH_ID, OrderNumber, Color, Comment, {$_GET["author"]}, {$_GET["confirmed"]}
				  FROM OrdersData
				  WHERE OD_ID = {$_GET["id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$id = mysqli_insert_id( $mysqli );

		$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, Length, Width, PieceAmount, PieceSize, PF_ID, PME_ID, MT_ID, IsExist, Amount, Price, Comment, order_date, arrival_date, creator)
				  SELECT {$id}, PM_ID, Length, Width, PieceAmount, PieceSize, PF_ID, PME_ID, MT_ID, IsExist, Amount, Price, Comment, order_date, arrival_date, {$_GET["author"]}
				  FROM OrdersDataDetail
				  WHERE OD_ID = {$_GET["id"]} AND Del = 0";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$query = "INSERT INTO OrdersDataBlank(OD_ID, BL_ID, Other, Amount, Price, Comment, MT_ID, IsExist, order_date, arrival_date, creator)
				  SELECT {$id}, BL_ID, Other, Amount, Price, Comment, MT_ID, IsExist, order_date, arrival_date, {$_GET["author"]}
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
