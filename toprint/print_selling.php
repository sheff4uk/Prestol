<?
	include "../config.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<?
	if( $_GET["print_title"] == '' ) {
		echo "<title>Версия для печати</title>";
	}
	else {
		echo "<title>{$_GET["print_title"]}</title>";
	}
	?>
	<style>
		.pagebreak {
			page-break-after: always;
		}
		body, td {
			margin: 20px;
			color: #333;
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 10pt;
		}
		table {
			table-layout: fixed;
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		.thead {
			text-align: center;
			font-weight: bold;
		}
		td, th {
			padding: 3px;
			border: 1px solid black;
			line-height: 1.45em;
		}
		.coupon {
			page-break-inside: avoid;
		}
		.nowrap {
			white-space: nowrap;
		}
	</style>
</head>
<body>
<?

	// Формируем список строк для печати
	$id_list = implode(",", $_GET["OD_ID"]);

?>
	<h3 style="text-align: center;"><?=$_GET["print_title"]?></h3>
	<div class="coupon">
	<table>
		<tbody>
			<tr class="thead">
				<?
					echo "<td width='4%'>Дата отгрузки</td>";
					echo "<td width='50'>Код</td>";
					echo "<td width='9%'>Клиент<br>Квитанция</td>";
					echo "<td width='20%'>Набор</td>";
					echo "<td width='15%'>Пластик/ткань</td>";
					echo "<td width='10%'>Цвет покраски</td>";
					echo "<td width='30'>Кол-во</td>";
					echo "<td width='7%'>Подразде-ление</td>";
					echo "<td width='10%'>Примечание</td>";
					echo "<td width='4%'>Договор от</td>";

				?>
			</tr>
	<?
//	// Снимаем ограничение в 1024 на GROUP_CONCAT
//	$query = "SET @@group_concat_max_len = 10000;";
//	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$query = "
		SELECT OD.OD_ID
				,CONCAT(Zakaz(ODD.ODD_ID), IF(IFNULL(ODD.Comment, '') = '', '', CONCAT(' <b>(', ODD.Comment, ')</b>'))) Zakaz
				,ODD.Amount
				,IFNULL(CONCAT(MT.Material, IFNULL(CONCAT(' (', SHP.Shipper, ')'), '')), '') Material
				,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		LEFT JOIN Shippers SHP ON SHP.SH_ID = MT.SH_ID
		WHERE OD.OD_ID IN ({$id_list})
		ORDER BY IFNULL(OD.StartDate, '9999-01-01') ASC, OD.OD_ID ASC, PTID DESC, ODD.ODD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Получаем количество изделий в наборе для группировки ячеек
	$query = "
		SELECT IFNULL(COUNT(1), 1) Cnt
			,IFNULL(OD.Code, '') Code
			,IFNULL(OD.ClientName, '') ClientName
			,DATE_FORMAT(OD.StartDate, '%d.%m<br>%Y') StartDate
			,DATE_FORMAT(OD.ReadyDate, '%d.%m<br>.%Y') ReadyDate
			,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
			,OD.OrderNumber
			,Color_print(OD.CL_ID) Color
			,IFNULL(OD.sell_comment, '') Comment
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		WHERE OD.OD_ID IN ({$id_list})
		GROUP BY OD.OD_ID
		ORDER BY IFNULL(OD.StartDate, '9999-01-01') ASC, OD.OD_ID ASC
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$odid = 0;
	while( $row = mysqli_fetch_array($res) )
	{
		if( $odid != $row["OD_ID"] ) {
			$subrow = mysqli_fetch_array($subres);
			$cnt = $subrow["Cnt"];
			$odid = $row["OD_ID"];
			$span = 1;
		}
		else {
			$span = 0;
		}

		if( $span ) {
			echo "</tbody></table></div><div class='coupon'><table><tbody><tr>";
		}
		else {
			echo "<tr>";
		}

		if($span) echo "<td width='4%' rowspan='{$cnt}'>{$subrow["ReadyDate"]}</td>";
		if($span) echo "<td width='50' rowspan='{$cnt}' class='nowrap'><b>{$subrow["Code"]}</b></td>";
		if($span) echo "<td width='9%' rowspan='{$cnt}'>{$subrow["ClientName"]}<br><b>{$subrow["OrderNumber"]}</b></td>";
		echo "<td width='20%' style='font-size: 16px;'>{$row["Zakaz"]}</td>";
		echo "<td width='15%'>{$row["Material"]}</td>";
		if($span) echo "<td width='10%' rowspan='{$cnt}'>{$subrow["Color"]}</td>";
		echo "<td width='30' style='font-size: 16px; text-align: right;'><b style='font-size: 1.3em;'>{$row["Amount"]}</b></td>";
		if($span) echo "<td width='7%' rowspan='{$cnt}'>{$subrow["Shop"]}</td>";
		if($span) echo "<td width='10%' rowspan='{$cnt}'>{$subrow["Comment"]}</td>";
		if($span) echo "<td width='4%' rowspan='{$cnt}'>{$subrow["StartDate"]}</td>";
		echo "</tr>";
	}
	?>
		</tbody>
	</table>
	</div>
</body>
</html>
