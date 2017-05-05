<?
	include "../config.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel='stylesheet' type='text/css' href='../css/font-awesome.min.css'>
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
			font-family: Verdana, Trebuchet MS, Tahoma, Arial, sans-serif;
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
			border: 2px solid black;
			border-bottom: none;
		}
		.nowrap {
			white-space: nowrap;
		}
	</style>
</head>
<body>
<?

	// Формируем список строк для печати
	$id_list = '0';
	foreach ($_GET["OD_ID"] as $key => $value) {
		$id_list .= ','.$value;
	}
	//echo $id_list;
	//$id_list = 3220;

?>
	<h3 style="text-align: center;"><?=$_GET["print_title"]?></h3>
	<div class="coupon">
	<table>
		<tbody>
			<tr class="thead">
				<?
					echo "<td width='4%'>Дата отгрузки</td>";
					echo "<td width='40'>Код</td>";
					echo "<td width='5%'>Квитанция</td>";
					echo "<td width='9%'>Заказчик</td>";
					echo "<td width='20%'>Заказ</td>";
					echo "<td width='15%'>Пластик/ткань</td>";
					echo "<td width='10%'>Цвет покраски</td>";
					echo "<td width='30'>Кол-во</td>";
					echo "<td width='7%'>Салон</td>";
					echo "<td width='10%'>Примечание</td>";
					echo "<td width='4%'>Дата продажи</td>";

				?>
			</tr>
	<?
	// Снимаем ограничение в 1024 на GROUP_CONCAT
	$query = "SET @@group_concat_max_len = 10000;";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$query = "SELECT OD.OD_ID
					,IFNULL(OD.Code, '') Code
					,IFNULL(OD.ClientName, '') ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m<br>%Y') StartDate
					,DATE_FORMAT(OD.ReadyDate, '%d.%m<br>.%Y') ReadyDate
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,OD.OrderNumber
					,ODD_ODB.Zakaz
					,ODD_ODB.Amount
					,OD.Color
					,ODD_ODB.Material
					,IFNULL(OD.sell_comment, '') Comment
					,IF(OD.SH_ID IS NULL, 1, 0) is_free
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN (SELECT ODD.OD_ID
			  				   ,ODD.ODD_ID itemID
			  				   ,IFNULL(PM.PT_ID, 2) PT_ID
							   ,CONCAT(IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT('+ патина (', ODD.patina, ')'), ''), IF(IFNULL(ODD.Comment, '') = '', '', CONCAT(' <b>(', ODD.Comment, ')</b>'))) Zakaz
							   ,ODD.Amount
							   ,IFNULL(CONCAT(MT.Material, IFNULL(CONCAT(' (', SH.Shipper, ')'), '')), '') Material
						FROM OrdersDataDetail ODD
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1 AND ODS.Old = 0
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
						WHERE ODD.Del = 0
						GROUP BY ODD.ODD_ID
						UNION ALL
						SELECT ODB.OD_ID
							  ,ODB.ODB_ID itemID
							  ,0 PT_ID
							  ,CONCAT(IFNULL(BL.Name, ODB.Other), ' ', IFNULL(CONCAT('+ патина (', ODB.patina, ')'), ''), IF(IFNULL(ODB.Comment, '') = '', '', CONCAT(' <b>(', ODB.Comment, ')</b>'))) Zakaz
							  ,ODB.Amount
							  ,IFNULL(CONCAT(MT.Material, IFNULL(CONCAT(' (', SH.Shipper, ')'), '')), '') Material
						FROM OrdersDataBlank ODB
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID AND ODS.Visible = 1 AND ODS.Old = 0
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
						LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
						WHERE ODB.Del = 0
						GROUP BY ODB.ODB_ID
						) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  WHERE OD.OD_ID IN ({$id_list})
			  GROUP BY ODD_ODB.itemID
			  #ORDER BY is_free, OD.AddDate, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID, ODD_ODB.PT_ID DESC, ODD_ODB.itemID
			  ORDER BY IFNULL(OD.ReadyDate, '9999-01-01') ASC, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID ASC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Снимаем ограничение в 1024 на GROUP_CONCAT
	$query = "SET @@group_concat_max_len = 10000;";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Получаем количество изделий в заказе для группировки ячеек
	$query = "SELECT IFNULL(COUNT(1), 1) Cnt, OD.OD_ID, IF(OD.SH_ID IS NULL, 1, 0) is_free
				FROM OrdersData OD
				LEFT JOIN (
					SELECT ODD.OD_ID, IFNULL(PM.PT_ID, 2) PT_ID
					FROM OrdersDataDetail ODD
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					WHERE ODD.Del = 0
					UNION ALL
					SELECT ODB.OD_ID, 0 PT_ID
					FROM OrdersDataBlank ODB
					WHERE ODB.Del = 0
				) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
				WHERE OD.OD_ID IN ({$id_list})
				GROUP BY OD.OD_ID
				#ORDER BY is_free, OD.AddDate, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID
				ORDER BY IFNULL(OD.ReadyDate, '9999-01-01') ASC, SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID ASC";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$odid = 0;
	while( $row = mysqli_fetch_array($res) )
	{
		if( $odid != $row["OD_ID"] ) {
			$subrow = mysqli_fetch_array($subres);
			$cnt = $subrow["Cnt"];
			$odid = $row["OD_ID"];
			$span = 1;
			//$border = "border-top: 3px solid black;";
		}
		else {
			$span = 0;
			$border = "";
		}

		if( $span ) {
			echo "</tbody></table></div><div class='coupon'><table><tbody><tr>";
		}
		else {
			echo "<tr>";
		}

		if($span) echo "<td width='4%' style='{$border}' rowspan='{$cnt}'>{$row["ReadyDate"]}</td>";
		if($span) echo "<td width='40' style='{$border}' rowspan='{$cnt}' class='nowrap'><b>{$row["Code"]}</b><br>{$cnt}<br>({$row["OD_ID"]}-{$subrow["OD_ID"]})</td>";
		if($span) echo "<td width='5%' style='{$border}' rowspan='{$cnt}'>{$row["OrderNumber"]}</td>";
		if($span) echo "<td width='9%' style='{$border}' rowspan='{$cnt}'>{$row["ClientName"]}</td>";
		echo "<td width='20%' style='{$border} font-size: 16px;'>{$row["Zakaz"]}</td>";
		echo "<td width='15%' style='{$border}'>{$row["Material"]}</td>";
		if($span) echo "<td width='10%' style='{$border}' rowspan='{$cnt}'>{$row["Color"]}</td>";
		echo "<td width='30' style='{$border} font-size: 16px; text-align: right;'>{$row["Amount"]}</td>";
		if($span) echo "<td width='7%' style='{$border}' rowspan='{$cnt}'>{$row["Shop"]}</td>";
		if($span) echo "<td width='10%' style='{$border}' rowspan='{$cnt}'>{$row["Comment"]}</td>";
		if($span) echo "<td width='4%' style='{$border}' rowspan='{$cnt}'>{$row["StartDate"]}</td>";
		echo "</tr>";
	}
    ?>
        </tbody>
    </table>
	</div>
</body>
</html>
