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
					  ,CONCAT(IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('патина (', Patina(ODD.ptn), ')'), '')) Zakaz
					  ,ODD.Amount
					  ,IFNULL(ODD.opt_price, ODD.Price) Price
				FROM OrdersDataDetail ODD
				LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
				LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
				LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
				WHERE ODD.Del = 0
				UNION ALL
				SELECT ODB.OD_ID
					  ,ODB.ODB_ID ItemID
					  ,0 PT_ID
					  ,CONCAT(IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('патина (', Patina(ODB.ptn), ')'), '')) Zakaz
					  ,ODB.Amount
					  ,IFNULL(ODB.opt_price, ODB.Price) Price
				FROM OrdersDataBlank ODB
				LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
				WHERE ODB.Del = 0
			  ) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  WHERE OD.Del = 0 AND OD.Code LIKE '%{$_GET["term"]}%' AND IFNULL(SH.CT_ID, 0) IN ({$USR_cities}) AND ((OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}) OR (OD.ReadyDate IS NULL))";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$Products[] = array( "id"=>$row["ItemID"], "label"=>$row["Label"], "value"=>$row["Zakaz"], "PT"=>$row["PT_ID"], "Amount"=>$row["Amount"], "Price"=>$row["Price"], "odid"=>$row["OD_ID"], "code"=>$row["Code"] );
	}
	echo json_encode($Products);
?>
