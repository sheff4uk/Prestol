<?
	include "config.php";

	if( isset($_GET["id"]) ) {
		$query = "INSERT INTO OrdersData(CLientName, StartDate, EndDate, SH_ID, OrderNumber, Color, Comment)
				  SELECT CLientName, StartDate, EndDate, SH_ID, OrderNumber, Color, Comment
				  FROM OrdersData
				  WHERE OD_ID = {$_GET["id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$id = mysqli_insert_id( $mysqli );

		$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, Length, Width, PieceAmount, PieceSize, PF_ID, PME_ID, MT_ID, IsExist, Amount, Price, Comment, order_date, arrival_date)
				  SELECT {$id}, PM_ID, Length, Width, PieceAmount, PieceSize, PF_ID, PME_ID, MT_ID, IsExist, Amount, Price, Comment, order_date, arrival_date
				  FROM OrdersDataDetail
				  WHERE OD_ID = {$_GET["id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$query = "INSERT INTO OrdersDataBlank(OD_ID, BL_ID, Other, Amount, Price, Comment, MT_ID, IsExist, order_date, arrival_date)
				  SELECT {$id}, BL_ID, Other, Amount, Price, Comment, MT_ID, IsExist, order_date, arrival_date
				  FROM OrdersDataBlank
				  WHERE OD_ID = {$_GET["id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url=orderdetail.php?id='.$id.'">');
		die;
	}
	else {
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}
?>
