<?
	// Автокомплит изделий для экрана подготовки печатных форм
	include "config.php";
	include "checkrights.php";

	$datediff = 60; // Максимальный период отображения данных

	$query = "SELECT OD.OD_ID
					,OD.Code
					,ODD_ODB.ItemID
					,ODD_ODB.PT_ID
					,ODD_ODB.Zakaz
					,ODD_ODB.Amount
					,ODD_ODB.Price
					,CONCAT('[', OD.Code, '] ', ODD_ODB.Amount, ' ', ODD_ODB.Zakaz ) Label
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  JOIN (
				SELECT ODD.OD_ID
					  ,ODD.ODD_ID ItemID
					  ,IFNULL(PM.PT_ID, 2) PT_ID
					  ,Zakaz(ODD.ODD_ID) Zakaz
					  ,ODD.Amount
					  ,IFNULL(ODD.opt_price, ODD.Price) Price
				FROM OrdersDataDetail ODD
				LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
				WHERE ODD.Del = 0
				UNION ALL
				SELECT ODB.OD_ID
					  ,ODB.ODB_ID ItemID
					  ,0 PT_ID
					  ,ZakazB(ODB.ODB_ID) Zakaz
					  ,ODB.Amount
					  ,IFNULL(ODB.opt_price, ODB.Price) Price
				FROM OrdersDataBlank ODB
				WHERE ODB.Del = 0
			  ) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  WHERE OD.Del = 0 AND OD.Code LIKE '%{$_GET["term"]}%' AND IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) AND ((OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}) OR (OD.ReadyDate IS NULL))";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$Products[] = array( "id"=>$row["ItemID"], "label"=>$row["Label"], "value"=>$row["Zakaz"], "PT"=>$row["PT_ID"], "Amount"=>$row["Amount"], "Price"=>$row["Price"], "odid"=>$row["OD_ID"], "code"=>$row["Code"] );
	}
	echo json_encode($Products);
?>
