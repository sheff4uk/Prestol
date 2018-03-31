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

	// Формируем список id выбранных заказов из $_GET
	$id_list = '0';
	foreach ($_GET["order"] as $k => $v) {
		$id_list .= ",{$v}";
	}

	$product_types = "-1";
	if(isset($_GET["Tables"])) $product_types .= ",2";
	if(isset($_GET["Chairs"])) $product_types .= ",1";
	if(isset($_GET["Others"])) $product_types .= ",0";

	//Получаем статус заказов (В работе, Свободные, Отгруженные, Удаленные)
	$archive = $_GET["archive"] ? $_GET["archive"] : 0;
?>
	<h3 style="text-align: center;"><?=$_GET["print_title"]?></h3>
	<div class="coupon">
	<table>
		<tbody>
			<tr class="thead">
				<?
					if(isset($_GET["CD"])) echo "<td width='50'>Код</td>";
					if(isset($_GET["CN"])) echo "<td width='9%'>Заказчик<br>Квитанция</td>";
					if(isset($_GET["SD"])) echo "<td width='4%'>Дата продажи</td>";
					if(isset($_GET["ED"])) echo "<td width='4%'>Дата ".($archive == 2 ? "отгрузки" : ($archive == 3 ? "удаления" : "сдачи"))."</td>";
					if(isset($_GET["SH"])) echo "<td width='7%'>Салон</td>";
//					if(isset($_GET["ON"])) echo "<td width='5%'>№ квитанции</td>";
					if(isset($_GET["Z"])) echo "<td width='20'>Кол-во</td>";
					if(isset($_GET["Z"])) echo "<td width='20%'>Заказ</td>";
					if(isset($_GET["M"])) echo "<td width='15%'>Пластик/ткань</td>";
					if(isset($_GET["CR"])) echo "<td width='10%'>Цвет покраски</td>";
					if(isset($_GET["CR"])) echo "<td width='5%'>Патина</td>";
					if(isset($_GET["PR"])) echo "<td width='8%'>Этапы</td>";
//					if(isset($_GET["IP"])) echo "<td width='2%'>Лак.</td>";
					if(isset($_GET["N"])) echo "<td width='15%'>Примечание</td>";
				?>
			</tr>
	<?
	// Снимаем ограничение в 1024 на GROUP_CONCAT
	$query = "SET @@group_concat_max_len = 10000;";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$query = "SELECT OD.OD_ID
					,IFNULL(OD.Code, '') Code
					,IFNULL(OD.ClientName, '') ClientName
					,CONCAT('<br>', OD.mtel) mtel
					,CONCAT('<br>', OD.address) address
					,DATE_FORMAT(OD.StartDate, '%d.%m<br>%Y') StartDate
					,DATE_FORMAT(IFNULL(OD.DelDate, IFNULL(OD.ReadyDate, OD.EndDate)), '%d.%m<br>%Y') EndDate
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,OD.OrderNumber
					,ODD_ODB.Zakaz
					,ODD_ODB.Amount
					,ODD_ODB.Patina
					,Color(OD.CL_ID) Color
					,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
					,ODD_ODB.Material
					,IFNULL(ODD_ODB.Steps, '') Steps
					,IFNULL(OD.Comment, '') Comment
					,IF(OD.SH_ID IS NULL, 1, 0) is_free
				FROM OrdersData OD
				LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
				LEFT JOIN (
					SELECT ODD.OD_ID
						,ODD.ODD_ID itemID
						,IFNULL(PM.PT_ID, 2) PT_ID
						,CONCAT(Zakaz(ODD.ODD_ID), IF(IFNULL(ODD.Comment, '') = '', '', CONCAT(' <b>(', ODD.Comment, ')</b>'))) Zakaz
						,CONCAT('<b>', ODD.Amount, '</b>') Amount
						,Patina(ODD.ptn) Patina
						,IFNULL(CONCAT(MT.Material, IFNULL(CONCAT(' (', SH.Shipper, ')'), ''),
							IF(IFNULL(MT.Material, '') != '',
								CASE IFNULL(ODD.IsExist, -1)
									WHEN -1 THEN ' <b>(неизвестно)</b>'
									WHEN 0 THEN ' <b>(нет)</b>'
									WHEN 1 THEN ' <b>(заказано)</b>'
									WHEN 2 THEN ' <b>(есть)</b>'
								END,
							'')
						), '') Material
						,GROUP_CONCAT(CONCAT(IF(ODS.IsReady, CONCAT('<b>', ST.Short, '</b>'), ST.Short), '(<i>', IFNULL(WD.Name, '---'), '</i>)') ORDER BY ST.Sort SEPARATOR '<br>') Steps
					FROM OrdersDataDetail ODD
					LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1 AND ODS.Old = 0
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
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
						,CONCAT(ZakazB(ODB.ODB_ID), IF(IFNULL(ODB.Comment, '') = '', '', CONCAT(' <b>(', ODB.Comment, ')</b>'))) Zakaz
						,CONCAT('<b>', ODB.Amount, '</b>') Amount
						,Patina(ODB.ptn) Patina
						,IFNULL(CONCAT(MT.Material, IFNULL(CONCAT(' (', SH.Shipper, ')'), ''),
							IF(IFNULL(MT.Material, '') != '',
								CASE IFNULL(ODB.IsExist, -1)
									WHEN -1 THEN ' <b>(неизвестно)</b>'
									WHEN 0 THEN ' <b>(нет)</b>'
									WHEN 1 THEN ' <b>(заказано)</b>'
									WHEN 2 THEN ' <b>(есть)</b>'
								END,
							'')
						), '') Material
						,GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT(IF(ODS.IsReady, '<b>Этап</b>', 'Этап'), '(<i>', IFNULL(WD.Name, '---'), '</i>)')) SEPARATOR '<br>') Steps
					FROM OrdersDataBlank ODB
					LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID AND ODS.Visible = 1 AND ODS.Old = 0
					LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
					LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
					LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
					WHERE ODB.Del = 0
					GROUP BY ODB.ODB_ID
				) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
				WHERE OD.OD_ID IN ({$id_list})
				AND ODD_ODB.PT_ID IN({$product_types})";

				if($archive == "2") {
					$query .= " ORDER BY OD.ReadyDate DESC, ";
				}
				elseif($archive == "3") {
					$query .= " ORDER BY OD.DelDate DESC, ";
				}
				else {
					$query .= " ORDER BY OD.AddDate, ";
				}

				$query .= "SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID, ODD_ODB.PT_ID DESC, ODD_ODB.itemID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

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
				AND ODD_ODB.PT_ID IN({$product_types})
				GROUP BY OD.OD_ID";

				if($archive == "2") {
					$query .= " ORDER BY OD.ReadyDate DESC, ";
				}
				elseif($archive == "3") {
					$query .= " ORDER BY OD.DelDate DESC, ";
				}
				else {
					$query .= " ORDER BY OD.AddDate, ";
				}

				$query .= "SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID";
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

		if(isset($_GET["CD"]) and $span) echo "<td width='50' style='{$border}' rowspan='{$cnt}' class='nowrap'><b>{$row["Code"]}</b></td>";
		if(isset($_GET["CN"]) and $span) echo "<td width='9%' style='{$border}' rowspan='{$cnt}'>{$row["ClientName"]}<br><b>{$row["OrderNumber"]}</b>{$row["mtel"]}{$row["address"]}</td>";
		if(isset($_GET["SD"]) and $span) echo "<td width='4%' style='{$border}' rowspan='{$cnt}'>{$row["StartDate"]}</td>";
		if(isset($_GET["ED"]) and $span) echo "<td width='4%' style='{$border}' rowspan='{$cnt}'>{$row["EndDate"]}</td>";
		if(isset($_GET["SH"]) and $span) echo "<td width='7%' style='{$border}' rowspan='{$cnt}'>{$row["Shop"]}</td>";
//		if(isset($_GET["ON"]) and $span) echo "<td width='5%' style='{$border}' rowspan='{$cnt}'>{$row["OrderNumber"]}</td>";
		if(isset($_GET["Z"])) echo "<td width='20' style='{$border} font-size: 20px; text-align: center;'>{$row["Amount"]}</td>";
		if(isset($_GET["Z"])) echo "<td width='20%' style='{$border} font-size: 16px;'>{$row["Zakaz"]}</td>";
		if(isset($_GET["M"])) echo "<td width='15%' style='{$border}'>{$row["Material"]}</td>";
		if(isset($_GET["CR"]) and $span) echo "<td width='10%' style='{$border}' rowspan='{$cnt}'>{$row["Color"]}</td>";

		if(isset($_GET["CR"])) echo "<td width='5%' style='{$border}'>{$row["Patina"]}</td>";

		if(isset($_GET["PR"])) echo "<td width='8%' style='{$border}'><span class='nowrap'>{$row["Steps"]}</span></td>";
//		if(isset($_GET["IP"]) and $span) {
//			echo "<td width='2%' style='{$border}' rowspan='{$cnt}'>";
//			switch ($row["IsPainting"]) {
//				case 1:
//					echo "<i class='fa fa-star-o fa-lg'></i>";
//					break;
//				case 2:
//					echo "<i class='fa fa-star-half-o fa-lg'></i>";
//					break;
//				case 3:
//					echo "<i class='fa fa-star fa-lg'></i>";
//					break;
//			}
//			echo "</td>";
//		}
		if(isset($_GET["N"]) and $span) echo "<td width='15%' style='{$border}' rowspan='{$cnt}'>{$row["Comment"]}</td>";
		echo "</tr>";
	}
    ?>
        </tbody>
    </table>
	</div>
</body>
</html>
