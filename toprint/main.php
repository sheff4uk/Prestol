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
?>
	<h3><?=$_GET["print_title"]?></h3>
	<table>
		<thead>
			<tr>
				<?
					if(isset($_GET["CN"])) echo "<th>Заказчик</th>";
					if(isset($_GET["SD"])) echo "<th>Дата приема</th>";
					if(isset($_GET["ED"])) echo "<th>Дата сдачи</th>";
					if(isset($_GET["SH"])) echo "<th>Салон</th>";
					if(isset($_GET["ON"])) echo "<th>№ квитанции</th>";
					if(isset($_GET["Z"])) echo "<th>Заказ</th>";
					if(isset($_GET["P"])) echo "<th>Пластик</th>";
					if(isset($_GET["CR"])) echo "<th>Цвет</th>";
					if(isset($_GET["PR"])) echo "<th>Этапы</th>";
					if(isset($_GET["IP"])) echo "<th>Лак.</th>";
					if(isset($_GET["T"])) echo "<th>Ткань</th>";
					if(isset($_GET["N"])) echo "<th>Примечание</th>";
				?>
            </tr>
        </thead>
        <tbody>
	<?
	$query = "SELECT IF(IFNULL(OD.ClientName, '') = '', '-', OD.ClientName) ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
					,DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') ReadyDate
					,CONCAT(CT.City, '/', SH.Shop) AS Shop
					,OD.OrderNumber
					,GROUP_CONCAT(CONCAT(ODD.Amount, ' ', PM.Model, ' ', IFNULL(CONCAT(ODD.Length, 'х', ODD.Width, IFNULL(CONCAT('/', ODD.PieceAmount, 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Zakaz
					,GROUP_CONCAT(CONCAT(IF(PM.PT_ID = 2, IFNULL(ODD.Material, ''), ''), '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Plastic
					,OD.Color
					,OD.IsPainting
					,GROUP_CONCAT(CONCAT(IF(PM.PT_ID = 1, IFNULL(ODD.Material, ''), ''), '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Textile
					,GROUP_CONCAT(CONCAT(ODS_WD.Steps, '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Steps
					,IFNULL(OD.Comment, '') Comment
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  LEFT JOIN (SELECT ODS.ODD_ID
			  				   ,GROUP_CONCAT(CONCAT(IF(ODS.IsReady, CONCAT('<b>', ST.Short, '</b>'), ST.Short), '(<i>', IFNULL(SUBSTR(WD.Name, 1, 30), '---'), '</i>)') ORDER BY ST.Sort SEPARATOR ' | ') Steps
						FROM OrdersDataSteps ODS
						LEFT JOIN OrdersDataDetail ODD ON ODD.ODD_ID = ODS.ODD_ID
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
						GROUP BY ODS.ODD_ID
						ORDER BY PM.PT_ID DESC, ODD.ODD_ID) ODS_WD ON ODS_WD.ODD_ID = ODD.ODD_ID
			  WHERE OD.OD_ID IN ({$id_list})
			  GROUP BY OD.OD_ID
			  ORDER BY OD.OD_ID";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr>";
		if(isset($_GET["CN"])) echo "<td>{$row["ClientName"]}</td>";
		if(isset($_GET["SD"])) echo "<td>{$row["StartDate"]}</td>";
		if(isset($_GET["ED"])) {
			if( $archive ) {
				echo "<td>{$row["ReadyDate"]}</td>";
			}
			else {
				echo "<td>{$row["EndDate"]}</td>";
			}
		}
		if(isset($_GET["SH"])) echo "<td>{$row["Shop"]}</td>";
		if(isset($_GET["ON"])) echo "<td>{$row["OrderNumber"]}</td>";
		if(isset($_GET["Z"])) echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		if(isset($_GET["P"])) echo "<td><span class='nowrap'>{$row["Plastic"]}</span></td>";
		if(isset($_GET["CR"])) echo "<td>{$row["Color"]}</td>";
		if(isset($_GET["PR"])) echo "<td><span class='nowrap'>{$row["Steps"]}</span></td>";
		if(isset($_GET["IP"])) {
			echo "<td>";
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
		if(isset($_GET["T"])) echo "<td><span class='nowrap'>{$row["Textile"]}</span></td>";
		if(isset($_GET["N"])) echo "<td>{$row["Comment"]}</td>";
	}
    ?>
        </tbody>
    </table>
</body>
</html>
