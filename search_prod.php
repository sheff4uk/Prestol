<?php
	// Автокомплит изделий для экрана подготовки печатных форм
	include "config.php";
	include "checkrights.php";

	$datediff = 730; // Максимальный период отображения данных

	$term = convert_str($_GET["term"]);
	$term = mysqli_real_escape_string($mysqli, $term);

	$query = "
		SELECT OD.OD_ID
			,OD.Code
			,ODD.ODD_ID
			,Zakaz(ODD.ODD_ID) Zakaz
			,ODD.Amount
			,IFNULL(ODD.min_price, 0) min_price
			,ODD.Price
			,ODD.discount
			,CONCAT('[', OD.Code, '] ', ODD.Amount, ' ', Zakaz(ODD.ODD_ID) ) Label
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		WHERE OD.DelDate IS NULL AND OD.Code LIKE '%{$term}%' AND IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) AND ((OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}) OR (OD.ReadyDate IS NULL))
	";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$Products[] = array( "id"=>$row["ODD_ID"], "label"=>$row["Label"], "value"=>$row["Zakaz"], "Amount"=>$row["Amount"], "min_price"=>$row["min_price"], "Price"=>$row["Price"], "discount"=>$row["discount"], "odid"=>$row["OD_ID"], "code"=>$row["Code"] );
	}
	echo json_encode($Products);
?>
