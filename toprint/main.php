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
		}
		.nowrap {
			white-space: nowrap;
		}
	</style>
</head>
<body>
<?

	// Формируем список id выбранных наборов из $_GET
	$id_list = implode(",", $_GET["order"]);

	$product_types = "-1";
	if(isset($_GET["Tables"])) $product_types .= ",2";
	if(isset($_GET["Chairs"])) $product_types .= ",1";
	if(isset($_GET["Others"])) $product_types .= ",0";

	//Получаем статус наборов (В работе, Свободные, Отгруженные, Удаленные)
	$archive = $_GET["archive"] ? $_GET["archive"] : 0;
?>
	<h3 style="text-align: center;"><?=$_GET["print_title"]?></h3>
	<div class="coupon">
	<table>
		<tbody>
			<tr class="thead">
				<?
					if(isset($_GET["CD"])) echo "<td width='50'>Код</td>";
					if(isset($_GET["CN"])) echo "<td width='9%'>Клиент<br>Квитанция</td>";
					if(isset($_GET["SD"])) echo "<td width='4%'>Дата продажи</td>";
					if(isset($_GET["ED"])) echo "<td width='4%'>Дата ".($archive == 2 ? "отгрузки" : ($archive == 3 ? "удаления" : "сдачи"))."</td>";
					if(isset($_GET["SH"])) echo "<td width='6%'>Подразде-ление</td>";
					if(isset($_GET["Z"])) echo "<td width='20'>Кол-во</td>";
					if(isset($_GET["Z"])) echo "<td width='20%'>Набор</td>";
					if(isset($_GET["M"])) echo "<td width='15%'>Пластик/ткань</td>";
					if(isset($_GET["CR"])) echo "<td width='10%'>Цвет покраски</td>";
					if(isset($_GET["CR"])) echo "<td width='3%'>Пат.</td>";
					if(isset($_GET["PR"])) echo "<td width='8%'>Этапы</td>";
					if(isset($_GET["N"])) echo "<td width='15%'>Примечание</td>";
				?>
			</tr>
	<?
	// Снимаем ограничение в 1024 на GROUP_CONCAT
	$query = "SET @@group_concat_max_len = 10000;";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$query = "
		SELECT OD.OD_ID
			,Zakaz(ODD.ODD_ID) Zakaz
			,ODD.Comment
			,CONCAT('<b>', ODD.Amount, '</b>') Amount
			,LEFT(Patina(ODD.ptn), 3) Patina
			,IFNULL(CONCAT(MT.Material, IFNULL(CONCAT(' (', SHP.Shipper, ')'), ''),
				IF(IFNULL(MT.Material, '') != '',
					CASE IFNULL(ODD.IsExist, -1)
						WHEN -1 THEN ' <b>(неизвестно)</b>'
						WHEN 0 THEN ' <b>(нет)</b>'
						WHEN 1 THEN ' <b>(заказано)</b>'
						WHEN 2 THEN ' <b>(есть)</b>'
					END,
				'')
			), '') Material
			,GROUP_CONCAT(IF(ODS.ODD_ID, CONCAT(IF(ODS.IsReady, CONCAT('<b>', IFNULL(ST.Short, 'Этап'), '</b>'), IFNULL(ST.Short, 'Этап')), '(<i>', IFNULL(WD.Name, '---'), '</i>)'), '') ORDER BY ST.Sort SEPARATOR '<br>') Steps
			,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1 AND ODS.Old = 0
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
		LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
		LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		LEFT JOIN Shippers SHP ON SHP.SH_ID = MT.SH_ID
		WHERE OD.OD_ID IN ({$id_list})
		GROUP BY ODD.ODD_ID
		HAVING PTID IN ({$product_types})
	";

	if($archive == "2") {
		$query .= " ORDER BY OD.ReadyDate DESC, ";
	}
	elseif($archive == "3") {
		$query .= " ORDER BY OD.DelDate DESC, ";
	}
	else {
		$query .= " ORDER BY OD.AddDate, ";
	}

	$query .= "SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID, PTID DESC, ODD.ODD_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Получаем количество изделий в наборе для группировки ячеек
	$query = "
		SELECT SUM(IF(IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) IN ({$product_types}), 1, 0)) Cnt
			,IFNULL(OD.Code, '') Code
			,IF(IFNULL(OD.ClientName, '') != '', CONCAT(OD.ClientName, '<br>'), '') ClientName
			,IF(IFNULL(OD.OrderNumber, '') != '', CONCAT(OD.OrderNumber, '<br>'), '') OrderNumber
			,IF(IFNULL(OD.mtel, '') != '', CONCAT('+', OD.mtel, '<br>'), '') mtel
			,IF(IFNULL(OD.address, '') != '', CONCAT(OD.address, '<br>'), '') address
			,DATE_FORMAT(OD.StartDate, '%d.%m<br>%Y') StartDate
			,DATE_FORMAT(IFNULL(OD.DelDate, IFNULL(OD.ReadyDate, OD.EndDate)), '%d.%m<br>%Y') EndDate
			,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
			,Color(OD.CL_ID) Colors
			,IFNULL(OD.Comment, '') Comment
			,Ord_price(OD.OD_ID) Price
			,Ord_discount(OD.OD_ID) discount
			,Payment_sum(OD.OD_ID) payment_sum
			,IFNULL(SH.retail, 0) retail
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		WHERE OD.OD_ID IN ({$id_list})
		GROUP BY OD.OD_ID
		HAVING Cnt > 0
	";

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
	while( $row = mysqli_fetch_array($res) ) {
		if( $odid != $row["OD_ID"] ) {
			$subrow = mysqli_fetch_array($subres);
			$cnt = $subrow["Cnt"];
			$odid = $row["OD_ID"];
			$span = 1;
			// Сумма доплаты
			if ($subrow["retail"] and $subrow["StartDate"]) {
				$format_diff = "Доплата: <span style='white-space: nowrap;'>".number_format($subrow["Price"] - $subrow['discount'] - $subrow["payment_sum"], 0, '', ' ')."</span>";
			}
			else {
				$format_diff = "";
			}
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

		if(isset($_GET["CD"]) and $span) echo "<td width='50' rowspan='{$cnt}' class='nowrap'><b>{$subrow["Code"]}</b></td>";
		if(isset($_GET["CN"]) and $span) echo "<td width='9%' rowspan='{$cnt}'>{$subrow["ClientName"]}<b>{$subrow["OrderNumber"]}</b>{$subrow["mtel"]}{$subrow["address"]}<b>{$format_diff}</b></td>";
		if(isset($_GET["SD"]) and $span) echo "<td width='4%' rowspan='{$cnt}'>{$subrow["StartDate"]}</td>";
		if(isset($_GET["ED"]) and $span) echo "<td width='4%' rowspan='{$cnt}'>{$subrow["EndDate"]}</td>";
		if(isset($_GET["SH"]) and $span) echo "<td width='6%' rowspan='{$cnt}'>".($subrow["retail"] ? "&bull; " : "")."{$subrow["Shop"]}</td>";
		if(isset($_GET["Z"])) {
			$zakaz = "";
			$options = "";
			$zakaz_arr = explode(" | ", $row["Zakaz"]);
			foreach($zakaz_arr as $key => $value) {
				if ($key == 0) {
					$zakaz = $value;
				}
				else {
					$options .= "{$value}<br>";
				}
			}
			$options .= "<b>{$row["Comment"]}</b>";
			echo "<td width='20' style='font-size: 20px; text-align: center;'>{$row["Amount"]}</td>";
			echo "<td width='7%' style='font-size: 16px;'>{$zakaz}</td>";
			echo "<td width='13%' style='font-size: 16px;'>{$options}</td>";
		}
		if(isset($_GET["M"])) echo "<td width='15%'>{$row["Material"]}</td>";
		if(isset($_GET["CR"]) and $span) echo "<td width='10%' rowspan='{$cnt}'>{$subrow["Colors"]}</td>";
		if(isset($_GET["CR"])) echo "<td width='3%'>{$row["Patina"]}</td>";
		if(isset($_GET["PR"])) echo "<td width='8%'><span class='nowrap'>{$row["Steps"]}</span></td>";
		if(isset($_GET["N"]) and $span) echo "<td width='15%' rowspan='{$cnt}'>{$subrow["Comment"]}</td>";
		echo "</tr>";
	}
	?>
		</tbody>
	</table>
	</div>
</body>
</html>
