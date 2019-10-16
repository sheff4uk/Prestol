<?
include "config.php";
include "checkrights.php";

switch( $_GET["do"] )
{
case "shopstags":
	// Автокомплит салонов
	$term = convert_str($_GET["term"]);
	$term = mysqli_real_escape_string($mysqli, $term);

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
			  WHERE Shop LIKE '%{$term}%'";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$ShopsTags[] = $row["Shop"];
	}
	echo json_encode($ShopsTags);
	break;

case "colortags":
	// Автокомплит цветов
	$term = convert_str($_GET["term"]);
	$term = mysqli_real_escape_string($mysqli, $term);

	$query = "SELECT color
					,clear
					,NCS_ID
					,Color_print(CL_ID) label
				FROM Colors
				WHERE Color_print(CL_ID) LIKE '%{$term}%'
					AND clear IS NOT NULL
				ORDER BY count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$ColorTags[] = array( "label"=>$row["label"], "value"=>$row["color"], "clear"=>$row["clear"], "NCS_ID"=>$row["NCS_ID"] );
	}
	echo json_encode($ColorTags);
	break;

case "textiletags":
	// Автокомплит тканей
	$term = convert_str($_GET["term"]);
	$term = mysqli_real_escape_string($mysqli, $term);

	$query = "
		SELECT MT.Material
			,MT.SH_ID
			,CONCAT(MT.Material, ' (', SH.Shipper, ')') Label
			,MT.removed
		FROM Materials MT
		JOIN Shippers SH ON SH.SH_ID = MT.SH_ID AND SH.mtype = 1
		LEFT JOIN OrdersDataDetail ODD ON ODD.MT_ID = MT.MT_ID
		WHERE (
			MT.Material LIKE '%{$term}%'
			OR
			MT.MT_ID IN (SELECT PMT_ID FROM Materials WHERE Material LIKE '%{$term}%' AND PMT_ID IS NOT NULL)
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
	$term = convert_str($_GET["term"]);
	$term = mysqli_real_escape_string($mysqli, $term);

	$query = "
		SELECT MT.Material
			,MT.SH_ID
			,CONCAT(MT.Material, ' (', SH.Shipper, ')') Label
			,MT.removed
			,CONCAT('<b> +', IFNULL(MT.markup, SH.markup), 'р.</b>') markup
		FROM Materials MT
		JOIN Shippers SH ON SH.SH_ID = MT.SH_ID AND SH.mtype = 2
		LEFT JOIN OrdersDataDetail ODD ON ODD.MT_ID = MT.MT_ID
		WHERE (
			MT.Material LIKE '%{$term}%'
			OR
			MT.MT_ID IN (SELECT PMT_ID FROM Materials WHERE Material LIKE '%{$term}%' AND PMT_ID IS NOT NULL)
		)
		".(($_GET["etalon"] == "1") ? "AND MT.PMT_ID IS NULL" : "" )."
		GROUP BY MT.MT_ID
		ORDER BY SUM(IFNULL(ODD.Amount, 0)) DESC
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$PlasticTags[] = array( "label"=>$row["Label"].$row["markup"], "value"=>$row["Material"], "SH_ID"=>$row["SH_ID"], "removed"=>$row["removed"] );
	}
	echo json_encode($PlasticTags);
	break;

case "clienttags":
	// Автокомплит клиентов
	$term = convert_str($_GET["term"]);
	$term = mysqli_real_escape_string($mysqli, $term);

	$query = "SELECT ClientName FROM OrdersData WHERE IFNULL(ClientName, '') != '' AND ClientName LIKE '%{$term}%' GROUP BY ClientName";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$ClientTags[] = $row["ClientName"];
	}
	echo json_encode($ClientTags);
	break;

case "passport":
	// Автокомплит паспортных данных для доверенности
	$term = convert_str($_GET["term"]);
	$term = mysqli_real_escape_string($mysqli, $term);

	$query = "SELECT PD_ID, fio, pasport_seriya, pasport_nomer, pasport_vidan_kem, pasport_vidan_data FROM PassportData WHERE fio LIKE '%{$term}%'";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$PassportTags[] = array( "PD_ID"=>$row["PD_ID"], "value"=>$row["fio"], "pasport_seriya"=>$row["pasport_seriya"], "pasport_nomer"=>$row["pasport_nomer"], "pasport_vidan_kem"=>$row["pasport_vidan_kem"], "pasport_vidan_data"=>$row["pasport_vidan_data"] );
	}
	echo json_encode($PassportTags);
	break;
}
?>
