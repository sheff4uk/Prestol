<?
	include "../config.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel='stylesheet' type='text/css' href='../css/font-awesome.min.css'>
    <title>Версия для печати</title>
    <style>
        body, td {
            margin: 20px;
            color: #333;
            font-family: Verdana, Trebuchet MS, Tahoma, Arial, sans-serif;
            font-size: 12pt;
        }
        table {
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
		.line {
			text-decoration: line-through;
		}
    </style>
</head>
<body>
<?

	// Формируем список строк для печати
	$id_list = '0';
	foreach( $_GET as $k => $v) 
	{
		if( strpos($k,"order") === 0 ) 
		{
			$orderid = (int)str_replace( "order", "", $k );
			$id_list .= ','.$orderid;
		}
	}
	$product_types = "-1";
	if(isset($_GET["Tables"])) $product_types .= ",2";
	if(isset($_GET["Chairs"])) $product_types .= ",1";
	if(isset($_GET["Others"])) $product_types .= ",0";
//	$product_types = substr($product_types, 1);
?>
	<h3 style="text-align: center;"><?=$_GET["print_title"]?></h3>
	<table>
		<thead>
			<tr>
				<?
					if(isset($_GET["CD"])) echo "<th>Код</th>";
					if(isset($_GET["CN"])) echo "<th>Заказчик</th>";
					if(isset($_GET["SD"])) echo "<th>Дата приема</th>";
					if(isset($_GET["ED"])) echo "<th>Дата сдачи</th>";
					if(isset($_GET["SH"])) echo "<th>Салон</th>";
					if(isset($_GET["ON"])) echo "<th>№ квитанции</th>";
					if(isset($_GET["Z"])) echo "<th width='50%'>Заказ</th>";
					if(isset($_GET["M"])) echo "<th>Материал</th>";
					if(isset($_GET["CR"])) echo "<th>Цвет</th>";
					if(isset($_GET["PR"])) echo "<th>Этапы</th>";
					if(isset($_GET["IP"])) echo "<th>Лак.</th>";
					if(isset($_GET["N"])) echo "<th>Примечание</th>";
				?>
			</tr>
		</thead>
		<tbody>
	<?
	$query = "SELECT OD.OD_ID
					,IFNULL(OD.Code, '') Code
					,IFNULL(OD.ClientName, '') ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m<br>%Y') StartDate
					,DATE_FORMAT(OD.EndDate, '%d.%m<br>%Y') EndDate
					,DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') ReadyDate
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,OD.OrderNumber
					,ODD_ODB.Zakaz
					,OD.Color
					,OD.IsPainting
					,ODD_ODB.Material
					,IFNULL(ODD_ODB.Steps, '') Steps
					,IFNULL(OD.Comment, '') Comment
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN (SELECT ODD.OD_ID
			  				   ,ODD.ODD_ID itemID
			  				   ,IFNULL(PM.PT_ID, 2) PT_ID
							   ,CONCAT(ODD.Amount, ' ', IFNULL(PM.Model, '***'), ' ', IFNULL(CONCAT(ODD.Length, 'х', ODD.Width, IFNULL(CONCAT('/', ODD.PieceAmount, 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), IF(IFNULL(ODD.Comment, '') = '', '', CONCAT(' <b>(', ODD.Comment, ')</b>'))) Zakaz
							   ,IFNULL(CONCAT(ODD.Material,
							   		IF(PM.PT_ID = 1 AND IFNULL(ODD.Material, '') != '',
										CASE ODD.IsExist
											WHEN 0 THEN ' <b>(нет)</b>'
											WHEN 1 THEN ' <b>(заказано)</b>'
											WHEN 2 THEN ' <b>(есть)</b>'
										END,
									'')
							   ), '') Material
			  				   ,GROUP_CONCAT(CONCAT(IF(ODS.IsReady, CONCAT('<b>', ST.Short, '</b>'), ST.Short), '(<i>', IFNULL(IF(IFNULL(WD.ShortName, '') = '', WD.Name, WD.ShortName), '---'), '</i>)') ORDER BY ST.Sort SEPARATOR '<br>') Steps
						FROM OrdersDataDetail ODD
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1 AND ODS.Old = 0
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
						GROUP BY ODD.ODD_ID
						UNION
						SELECT ODB.OD_ID
							  ,ODB.ODB_ID itemID
							  ,0 PT_ID
							  ,CONCAT(ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), IF(IFNULL(ODB.Comment, '') = '', '', CONCAT(' <b>(', ODB.Comment, ')</b>'))) Zakaz
							  ,IFNULL(CONCAT(ODB.Material,
							  		IF(IFNULL(ODB.Material, '') != '',
										CASE ODB.IsExist
											WHEN 0 THEN ' <b>(нет)</b>'
											WHEN 1 THEN ' <b>(заказано)</b>'
											WHEN 2 THEN ' <b>(есть)</b>'
										END,
									'')
							  ), '') Material
			  				  ,GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT(IF(ODS.IsReady, '<b>Этап</b>', 'Этап'), '(<i>', IFNULL(IF(IFNULL(WD.ShortName, '') = '', WD.Name, WD.ShortName), '---'), '</i>)')) SEPARATOR '<br>') Steps
						FROM OrdersDataBlank ODB
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID AND ODS.Visible = 1 AND ODS.Old = 0
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						GROUP BY ODB.ODB_ID
						) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  WHERE OD.OD_ID IN ({$id_list})
			  AND ODD_ODB.PT_ID IN({$product_types})
			  GROUP BY ODD_ODB.itemID
			  ORDER BY OD.OD_ID, ODD_ODB.PT_ID DESC, ODD_ODB.itemID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Получаем количество изделий в заказе для группировки ячеек
	$query = "SELECT IFNULL(COUNT(1), 1) Cnt, OD.OD_ID
				FROM OrdersData OD
				LEFT JOIN (
					SELECT ODD.OD_ID, IFNULL(PM.PT_ID, 2) PT_ID
					FROM `OrdersDataDetail` ODD
					LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
					UNION ALL
					SELECT ODB.OD_ID, 0 PT_ID
					FROM `OrdersDataBlank` ODB
				) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
				WHERE OD.OD_ID IN ({$id_list})
				AND ODD_ODB.PT_ID IN({$product_types})
				GROUP BY OD.OD_ID
				ORDER BY OD.OD_ID";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$odid = 0;
	while( $row = mysqli_fetch_array($res) )
	{
		if( $odid != $row["OD_ID"] ) {
			$subrow = mysqli_fetch_array($subres);
			$cnt = $subrow["Cnt"];
			$odid = $row["OD_ID"];
			$span = 1;
			$border = " style='border-top: 3px solid black;'";
		}
		else {
			$span = 0;
			$border = "";
		}
		echo "<tr>";
		if(isset($_GET["CD"]) and $span) echo "<td{$border} rowspan='{$cnt}' class='nowrap'>{$row["Code"]}</td>";
		if(isset($_GET["CN"]) and $span) echo "<td{$border} rowspan='{$cnt}'>{$row["ClientName"]}</td>";
		if(isset($_GET["SD"]) and $span) echo "<td{$border} rowspan='{$cnt}'>{$row["StartDate"]}</td>";
		if(isset($_GET["ED"]) and $span) {
			if( $archive ) {
				echo "<td{$border} rowspan='{$cnt}'>{$row["ReadyDate"]}</td>";
			}
			else {
				echo "<td{$border} rowspan='{$cnt}'>{$row["EndDate"]}</td>";
			}
		}
		if(isset($_GET["SH"]) and $span) echo "<td{$border} rowspan='{$cnt}'>{$row["Shop"]}</td>";
		if(isset($_GET["ON"]) and $span) echo "<td{$border} rowspan='{$cnt}'>{$row["OrderNumber"]}</td>";
		if(isset($_GET["Z"])) echo "<td{$border}>{$row["Zakaz"]}</td>";
		if(isset($_GET["M"])) echo "<td{$border}>{$row["Material"]}</td>";
		if(isset($_GET["CR"]) and $span) echo "<td{$border} rowspan='{$cnt}'>{$row["Color"]}</td>";
		if(isset($_GET["PR"])) echo "<td{$border}><span class='nowrap'>{$row["Steps"]}</span></td>";
		if(isset($_GET["IP"]) and $span) {
			echo "<td{$border} rowspan='{$cnt}'>";
				switch ($row["IsPainting"]) {
					case 1:
						echo "<i class='fa fa-star-o fa-lg'></i>";
						break;
					case 2:
						echo "<i class='fa fa-star-half-o fa-lg'></i>";
						break;
					case 3:
						echo "<i class='fa fa-star fa-lg'></i>";
						break;
				}
			echo "</td>";
		}
		if(isset($_GET["N"]) and $span) echo "<td{$border} rowspan='{$cnt}'>{$row["Comment"]}</td>";
	}
    ?>
        </tbody>
    </table>
</body>
</html>
