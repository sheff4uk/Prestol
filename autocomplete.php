<?
include "config.php";
include "checkrights.php";

switch( $_GET["do"] )
{
case "shopstags":
	// Автокомплит салонов
	$query = "SELECT Shop FROM (
				SELECT CT.CT_ID, CT.City AS Shop FROM Cities CT WHERE CT.CT_ID IN ({$USR_cities})
				UNION
				SELECT CT.CT_ID, CONCAT(CT.City, '/', SH.Shop) AS Shop FROM Cities CT JOIN Shops SH ON SH.CT_ID = CT.CT_ID WHERE CT.CT_ID IN ({$USR_cities}) OR SH.SH_ID IN ({$USR_shops})
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
	$query = "SELECT Color FROM OrdersData WHERE Color LIKE '%{$_GET["term"]}%' GROUP BY Color ORDER BY COUNT(1) DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$ColorTags[] = $row["Color"];
	}
	echo json_encode($ColorTags);
	break;

case "textiletags":
	// Автокомплит тканей
	$query = "SELECT MT.Material
					,MT.SH_ID
					,CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')') Label
					,MT.removed
			  FROM Materials MT
			  LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
			  WHERE MT.PT_ID = 1 AND MT.Material LIKE '%{$_GET["term"]}%' ORDER BY MT.Count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		//$TextileTags[] = $row["Material"];
		$TextileTags[] = array( "label"=>$row["Label"], "value"=>$row["Material"], "SH_ID"=>$row["SH_ID"], "removed"=>$row["removed"] );
	}
	echo json_encode($TextileTags);
		break;

case "plastictags":
	// Автокомплит пластиков
	$query = "SELECT MT.Material
					,MT.SH_ID
					,CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')') Label
					,MT.removed
			  FROM Materials MT
			  LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
			  WHERE MT.PT_ID = 2 AND MT.Material LIKE '%{$_GET["term"]}%' ORDER BY MT.Count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		//$PlasticTags[] = $row["Material"];
		$PlasticTags[] = array( "label"=>$row["Label"], "value"=>$row["Material"], "SH_ID"=>$row["SH_ID"], "removed"=>$row["removed"] );
	}
	echo json_encode($PlasticTags);
	break;

case "textileplastictags":
	// Автокомплит тканей, пластиков и прочего
	$query = "SELECT MT.Material
					,MT.SH_ID
					,CONCAT(MT.Material, ' (', IFNULL(SH.Shipper, '-=Другой=-'), ')') Label
					,MT.removed
			  FROM Materials MT
			  LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
			  WHERE MT.Material LIKE '%{$_GET["term"]}%' GROUP BY MT.Material ORDER BY SUM(MT.Count) DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		//$TextilePlasticTags[] = $row["Material"];
		$TextilePlasticTags[] = array( "label"=>$row["Label"], "value"=>$row["Material"], "SH_ID"=>$row["SH_ID"], "removed"=>$row["removed"] );
	}
	echo json_encode($TextilePlasticTags);
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
		$query = "SELECT ODD.Price, CONCAT(ODD.Price, IFNULL(CONCAT(' (', ODD.Length, 'x', ODD.Width, IFNULL(CONCAT('/', ODD.PieceAmount, 'x', ODD.PieceSize), ''), ')'), '')) Label
					FROM OrdersDataDetail ODD
					JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
					JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = {$_GET["retail"]}
					WHERE ODD.Price IS NOT NULL AND ODD.PM_ID = {$_GET["PM_ID"]} AND ODD.PME_ID {$mechanism}
					GROUP BY ODD.Price, ODD.Length, ODD.Width, ODD.PieceAmount, ODD.PieceSize, ODD.PME_ID
					ORDER BY MAX(ODD.ODD_ID) DESC";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) )
		{
			$PriceTags[] = array( "label"=>$row["Label"], "value"=>$row["Price"] );
		}
		echo json_encode($PriceTags);
	}
	break;
}
?>
