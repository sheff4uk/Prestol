<?
	include "../config.php";
    $query = "SELECT Name FROM WorkersData WHERE WD_ID = {$_GET["worker"]}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$Worker = mysqli_result($res,0,'Name');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Каркас+Сборка - <?= $Worker ?></title>
    <style>
        body, td {
            margin: 20px;
            color: #333;
            font-family: Verdana;
            font-size: 10pt;
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
    </style>
</head>
<body>
    <h3>Каркас+Сборка - <span style="text-transform: uppercase;"><?= $Worker ?></span></h3>
    <table>
        <thead>
            <tr>
                <th>Заказчик</th>
                <th>Кол-во</th>
                <th>Стол</th>
                <th>Пластик</th>
                <th>Цвет</th>
                <th width="40%">Примечание</th>
            </tr>
        </thead>
        <tbody>
    <?
    $query = "SELECT WD.Name
					,CONCAT(CT.City, '/', SH.Shop) AS Shop
                    ,ODD.Amount
                    ,PM.Model
                    ,CONCAT(PF.Form, ' ', PME.Mechanism) Form
                    ,CONCAT(ODD.Length, 'х', ODD.Width) Size
                    ,MT.Material
                    ,OD.Color
                    ,OD.Comment
                    ,SUM(IF(ODS.ST_ID = 7, ODS.WD_ID, 0)) Step7
                    ,GROUP_CONCAT(IF(ODS.ST_ID = 7, WD.Name, '') SEPARATOR '') worker7
                    ,GROUP_CONCAT(IF(ODS.ST_ID = 8, WD.Name, '') SEPARATOR '') worker8
                FROM OrdersData OD
                RIGHT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			    LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			    LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
                LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
                LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
				LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
				LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
                JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.ST_ID IN(7,8) AND ODS.IsReady != 1
                JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
                JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
                WHERE ODD.is_check = 1
                GROUP BY ODD.ODD_ID
                HAVING Step7 = {$_GET["worker"]}
                ORDER BY OD.OD_ID DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
        $comment = "";
        if( $row["worker7"] <> $row["worker8"] and $row["worker8"] > "" ) {
			$comment = "<b>Сборка: {$row["worker8"]}</b>";
		}
        echo "<tr><td>{$row["Shop"]}</td>";
        echo "<td>{$row["Amount"]}</td>";
        echo "<td>{$row["Model"]} {$row["Form"]} {$row["Size"]}<br>{$comment}</td>";
        echo "<td>{$row["Material"]}</td>";
        echo "<td>{$row["Color"]}</td>";
        echo "<td></td></tr>";
    }
    ?>
        </tbody>
    </table>
</body>
</html>
