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
//			table-layout: fixed;
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		tr {
			page-break-inside: avoid;
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
//			margin-right: 15px;
		}
	</style>
</head>
<body>
<?
	// Собираем идентификаторы изделий
	$ODD_IDs = implode(",", $_GET["prod"]);
?>
	<table>
		<tbody>
	<?
	$query = "
		SELECT CONCAT(MT.Material, IFNULL(CONCAT(' (', SHP.Shipper, ')'), '')) Material
			,CONCAT('<i>', IF(SHP.mtype = 1, CONCAT(ROUND(ODD.MT_amount, 1), '<br>м.п.'), WD.Name), '</i>') MT_amount
			,ODD.Amount
			,Zakaz(ODD.ODD_ID) Zakaz
			,OD.Code
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.ODD_ID IN ($ODD_IDs)
		JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		JOIN Shippers SHP ON SHP.SH_ID = MT.SH_ID
		LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
						AND ODS.Visible = 1
						AND ODS.Old != 1
						AND (ODS.ST_ID IN(SELECT ST_ID FROM StepsTariffs WHERE Short LIKE 'Ст%') OR ODS.ST_ID IS NULL)
		LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
		ORDER BY OD.OD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "
			<tr>
				<td>
					<div class='code nowrap'>{$row["Code"]}</div>
				</td>
				<td>
					<div style='font-size: 1.5em;'>{$row["Material"]}</div><br>
					<b><b style='font-size: 1.3em;'>{$row["Amount"]}</b> {$row["Zakaz"]}</b>
				</td>
				<td>
					{$row["MT_amount"]}
				</td>
				<!--
				<td>
					<img src='https://chart.googleapis.com/chart?chs=82x82&cht=qr&chl=https://kis.fabrikaprestol.ru/orderdetail.php?id=3409&choe=UTF-8' alt='QR code'>
				</td>
				-->
			</tr>
		";
	}
?>
		</tbody>
	</table>
</body>
</html>
