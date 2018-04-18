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
					,CONCAT('<i>', IF(SH.mtype = 1, CONCAT(ROUND(ODD_ODB.MT_amount, 1), '<br>м.п.'), ODD_ODB.Name), '</i>') MT_amount
					,ODD_ODB.Amount
					,ODD_ODB.zakaz
					,ODD_ODB.Code
				FROM Materials MT
				JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
				JOIN (
					SELECT ODD.MT_ID
							,ODD.MT_amount
							,ODD.Amount
							,Zakaz(ODD.ODD_ID) Zakaz
							,OD.Code
							,WD.Name
					FROM OrdersDataDetail ODD
					JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
					LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
					LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
									AND ODS.Visible = 1
									AND ODS.Old != 1
									AND ODS.ST_ID IN(SELECT ST_ID FROM StepsTariffs WHERE Short LIKE '%Ст%')
					LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
					WHERE ODD.ODD_ID IN ($ODD_IDs)
					UNION ALL
					SELECT ODB.MT_ID
							,ODB.MT_amount
							,ODB.Amount
							,ZakazB(ODB.ODB_ID) Zakaz
							,OD.Code
							,WD.Name
					FROM OrdersDataBlank ODB
					JOIN OrdersData OD ON OD.OD_ID = ODB.OD_ID
					LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
					LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
									AND ODS.Visible = 1
									AND ODS.Old != 1
					LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
					WHERE ODB.ODB_ID IN ($ODB_IDs)
				) ODD_ODB ON ODD_ODB.MT_ID = MT.MT_ID
				ORDER BY MT.Material";
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
					<b><b style='font-size: 1.3em;'>{$row["Amount"]}</b> {$row["zakaz"]}</b>
				</td>
				<td>
					{$row["MT_amount"]}
				</td>
				<td>
					<img src='https://chart.googleapis.com/chart?chs=82x82&cht=qr&chl=https://kis.fabrikaprestol.ru/orderdetail.php?id=3409&choe=UTF-8' alt='QR code'>
				</td>
			</tr>
		";
	}
?>
		</tbody>
	</table>
</body>
</html>
