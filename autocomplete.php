<?
include "config.php";
include "checkrights.php";

switch( $_GET["do"] )
{
case "shopstags":
	// Автокомплит салонов
	$query = "SELECT Shop FROM (
				SELECT CT.CT_ID, CT.City AS Shop
				FROM Cities CT JOIN Shops SH ON SH.CT_ID = CT.CT_ID
				WHERE CT.CT_ID IN ({$USR_cities})
				".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR SH.stock = 1)" : "")."
				GROUP BY CT.City
				UNION
				SELECT CT.CT_ID, CONCAT(CT.City, '/', SH.Shop) AS Shop
				FROM Cities CT JOIN Shops SH ON SH.CT_ID = CT.CT_ID
				WHERE CT.CT_ID IN ({$USR_cities})
				".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR SH.stock = 1)" : "")."
				UNION
				SELECT 0, 'Свободные' AS Shop) SHT
			  WHERE Shop LIKE '%{$_GET["term"]}%'";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$ShopsTags[] = $row["Shop"];
	}
	echo json_encode($ShopsTags);
	break;

case "colortags":
	// Автокомплит цветов
	$query = "SELECT color
					,clear
					,Color(CL_ID) label
				FROM Colors
				WHERE color LIKE '%{$_GET["term"]}%'
					AND clear IS NOT NULL
					AND count > 0
				ORDER BY count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$ColorTags[] = array( "label"=>$row["label"], "value"=>$row["color"], "clear"=>$row["clear"] );
	}
	echo json_encode($ColorTags);
	break;

case "textiletags":
	// Автокомплит тканей
	$query = "
		SELECT MT.Material
			,MT.SH_ID
			,CONCAT(MT.Material, ' (', SH.Shipper, ')') Label
			,MT.removed
		FROM Materials MT
		JOIN Shippers SH ON SH.SH_ID = MT.SH_ID AND SH.mtype = 1
		LEFT JOIN OrdersDataDetail ODD ON ODD.MT_ID = MT.MT_ID
		WHERE (
			MT.Material LIKE '%{$_GET["term"]}%'
			OR
			MT.MT_ID IN (SELECT PMT_ID FROM Materials WHERE Material LIKE '%{$_GET["term"]}%' AND PMT_ID IS NOT NULL)
		)
		".(($_GET["etalon"] == "1") ? "AND MT.PMT_ID IS NULL" : "" )."
		GROUP BY MT.MT_ID
		ORDER BY SUM(IFNULL(ODD.Amount, 0)) DESC
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$TextileTags[] = array( "label"=>$row["Label"], "value"=>$row["Material"], "SH_ID"=>$row["SH_ID"], "removed"=>$row["removed"] );
	}
	echo json_encode($TextileTags);
		break;

case "plastictags":
	// Автокомплит пластиков
	$query = "
		SELECT MT.Material
			,MT.SH_ID
			,CONCAT(MT.Material, ' (', SH.Shipper, ')') Label
			,MT.removed
		FROM Materials MT
		JOIN Shippers SH ON SH.SH_ID = MT.SH_ID AND SH.mtype = 2
		LEFT JOIN OrdersDataDetail ODD ON ODD.MT_ID = MT.MT_ID
		WHERE (
			MT.Material LIKE '%{$_GET["term"]}%'
			OR
			MT.MT_ID IN (SELECT PMT_ID FROM Materials WHERE Material LIKE '%{$_GET["term"]}%' AND PMT_ID IS NOT NULL)
		)
		".(($_GET["etalon"] == "1") ? "AND MT.PMT_ID IS NULL" : "" )."
		GROUP BY MT.MT_ID
		ORDER BY SUM(IFNULL(ODD.Amount, 0)) DESC
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$PlasticTags[] = array( "label"=>$row["Label"], "value"=>$row["Material"], "SH_ID"=>$row["SH_ID"], "removed"=>$row["removed"] );
	}
	echo json_encode($PlasticTags);
	break;

case "clienttags":
	// Автокомплит заказчиков
	$query = "SELECT ClientName FROM OrdersData WHERE IFNULL(ClientName, '') != '' AND ClientName LIKE '%{$_GET["term"]}%' GROUP BY ClientName";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$ClientTags[] = $row["ClientName"];
	}
	echo json_encode($ClientTags);
	break;

case "price":
	// Автокомплит цены изделий
	if( $_GET["PM_ID"] != '' ) {
		$mechanism = $_GET["PME_ID"] != '' ? '= '.$_GET["PME_ID"] : 'IS NULL';
		$query = "SELECT ODD.Price, CONCAT(ODD.Price, IFNULL(CONCAT(IF(ODD.Width > 0, ' ', ' Ø'), ODD.Length, IFNULL(CONCAT('(+', IFNULL(CONCAT(ODD.PieceAmount, 'x'), ''), ODD.PieceSize, ')'), ''), IFNULL(CONCAT('х', ODD.Width), '')), '')) Label
					FROM OrdersDataDetail ODD
					JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
					JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND IF(SH.KA_ID IS NULL, 1, 0) = {$_GET["retail"]}
					WHERE ODD.Price IS NOT NULL AND ODD.PM_ID = {$_GET["PM_ID"]} AND ODD.PME_ID {$mechanism}
					AND DATEDIFF(NOW(),OD.AddDate) <= 90
					AND ODD.Del = 0
					GROUP BY ODD.Price, ODD.Length, ODD.Width, ODD.PieceAmount, ODD.PieceSize, ODD.PME_ID
					ORDER BY MAX(ODD.ODD_ID) DESC
					LIMIT 8";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) )
		{
			$PriceTags[] = array( "label"=>$row["Label"], "value"=>$row["Price"] );
		}
		echo json_encode($PriceTags);
	}
	break;

case "passport":
	// Автокомплит паспортных данных для доверенности
	$query = "SELECT PD_ID, fio, pasport_seriya, pasport_nomer, pasport_vidan_kem, pasport_vidan_data FROM PassportData WHERE fio LIKE '%{$_GET["term"]}%'";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$PassportTags[] = array( "PD_ID"=>$row["PD_ID"], "value"=>$row["fio"], "pasport_seriya"=>$row["pasport_seriya"], "pasport_nomer"=>$row["pasport_nomer"], "pasport_vidan_kem"=>$row["pasport_vidan_kem"], "pasport_vidan_data"=>$row["pasport_vidan_data"] );
	}
	echo json_encode($PassportTags);
	break;
}
?>
