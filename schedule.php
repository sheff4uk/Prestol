<?
include "config.php";

$query = "DELETE FROM ExhibitionCostLog WHERE date = CURRENT_DATE()";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

$query = "
	INSERT INTO ExhibitionCostLog
	SELECT CURRENT_DATE() date, OD.SH_ID, SUM((ODD.Price - IFNULL(ODD.discount, 0))*ODD.Amount) cost
	FROM OrdersDataDetail ODD
	JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
		AND OD.DelDate IS NULL
		AND OD.StartDate IS NULL
		AND OD.ReadyDate IS NOT NULL
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	GROUP BY OD.SH_ID
	HAVING cost > 0
";
mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
?>
