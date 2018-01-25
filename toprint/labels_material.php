<?
	include "../config.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel='stylesheet' type='text/css' href='../css/font-awesome.min.css'>
	<title>Бирки для материалов</title>

	<style>
		body, td {
			margin: 20px;
			color: #333;
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 14pt;
		}
		table {
			table-layout: fixed;
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		td, th {
			padding: 3px;
			border: 1px solid black;
			line-height: 1.45em;
		}
		.nowrap {
			white-space: nowrap;
		}
		.code {
			background: #333;
			color: #fff;
			border-radius: 10px;
			border: 4px solid #333;
			font-size: 2em;
			font-weight: bold;
		}
		td > div {
			display: inline-block;
			margin-right: 15px;
		}
	</style>
</head>
<body>
<?
	// Собираем идентификаторы изделий и прочего
	$ODD_IDs = 0;
	$ODB_IDs = 0;

	foreach ($_GET["prod"] as $k => $v) {
		$ODD_IDs .= ",{$v}";
	}
	foreach ($_GET["other"] as $k => $v) {
		$ODB_IDs .= ",{$v}";
	}
?>
	<table>
		<tbody>
	<?
	$query = "SELECT MT.Material
					,CONCAT('<i style=\'border: 1px solid;\'>', ROUND(ODD_ODB.MT_amount, 1), ' м.п.</i>') MT_amount
					,ODD_ODB.Amount
					,ODD_ODB.zakaz
					,ODD_ODB.Code
				FROM Materials MT
				JOIN (
					SELECT ODD.MT_ID
							,ODD.MT_amount
							,ODD.Amount
							,CONCAT(IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, '')) zakaz
							,OD.Code
					FROM OrdersDataDetail ODD
					JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
					LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
					WHERE ODD_ID IN ($ODD_IDs)
					UNION ALL
					SELECT ODB.MT_ID
							,ODB.MT_amount
							,ODB.Amount
							,IFNULL(BL.Name, ODB.Other) zakaz
							,OD.Code
					FROM OrdersDataBlank ODB
					JOIN OrdersData OD ON OD.OD_ID = ODB.OD_ID
					LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
					WHERE ODB_ID IN ($ODB_IDs)
				) ODD_ODB ON ODD_ODB.MT_ID = MT.MT_ID
				ORDER BY MT.Material";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr>";
		echo "<td>";
		echo "<div class='code'>{$row["Code"]}</div>";
		echo "<div>{$row["Material"]}<br><b><b style='font-size: 1.3em;'>{$row["Amount"]}</b> {$row["zakaz"]}</b> {$row["MT_amount"]}</div>";
		echo "</td>";
	}
?>
		</tbody>
	</table>
</body>
</html>
