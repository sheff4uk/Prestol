<?
include "config.php";
include "checkrights.php";

switch( $_GET["do"] )
{
case "shopstags":
	// Автокомплит салонов
	$query = "SELECT Shop FROM (
				SELECT CT.CT_ID, CT.City AS Shop FROM Cities CT
				UNION
				SELECT CT.CT_ID, CONCAT(CT.City, '/', SH.Shop) AS Shop FROM Cities CT JOIN Shops SH ON SH.CT_ID = CT.CT_ID
				UNION
				SELECT 0 CT_ID, 'Свободные' AS Shop) SHT
			  WHERE Shop LIKE '%{$_GET["term"]}%' AND CT_ID IN ({$USR_cities})";
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
	$query = "SELECT Material FROM Materials WHERE PT_ID = 1 AND Material LIKE '%{$_GET["term"]}%' ORDER BY Count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$TextileTags[] = $row["Material"];
	}
	echo json_encode($TextileTags);
		break;

case "plastictags":
	// Автокомплит пластиков
	$query = "SELECT Material FROM Materials WHERE PT_ID = 2 AND Material LIKE '%{$_GET["term"]}%' ORDER BY Count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$PlasticTags[] = $row["Material"];
	}
	echo json_encode($PlasticTags);
	break;

case "textileplastictags":
	// Автокомплит тканей, пластиков и прочего
	$query = "SELECT Material, SUM(Count) Count FROM Materials WHERE Material LIKE '%{$_GET["term"]}%' GROUP BY Material ORDER BY Count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$TextilePlasticTags[] = $row["Material"];
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
}
?>
