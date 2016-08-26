<?
	include "../config.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Лакировка+Обивка+Упаковка</title>
    <style>
        body, td {
            margin: 20px;
            color: #333;
            font-family: Verdana;
            font-size: 9pt;
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
    <h3>Лакировка+Обивка+Упаковка</h3>
    <table>
        <thead>
            <tr>
                <th>Заказчик</th>
                <th>Заказ</th>
                <th>Сборщик</th>
                <th>Пластик</th>
                <th>Цвет</th>
                <th>Ткань</th>
                <th width="20%">Примечание</th>
            </tr>
        </thead>
        <tbody>
	<?
	$query = "SELECT OD.OD_ID
					,OD.ClientName
					,CONCAT(CT.City, '/', SH.Shop) AS Shop
					,OD.Comment
					,GROUP_CONCAT(CONCAT('<span class=\'', IF(ODS_WD.IsReady = 1, 'line', ''), '\'>', ODD.Amount, ' ', PM.Model, ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', IFNULL(CONCAT(ODD.Length, 'х', ODD.Width), ''), '</span><br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Zakaz
                    
					,GROUP_CONCAT(CONCAT(ODD.Color, '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Color
					
                    ,GROUP_CONCAT(CONCAT(IF(PM.PT_ID = 1, IFNULL(MT.Material, ''), ''),
                        IF(PM.PT_ID = 1,
						CASE ODD.IsExist
							WHEN 0 THEN '(Нет)'
							WHEN 1 THEN '(Зак.)'
							WHEN 2 THEN '(Есть)'
						END, ''),
					'<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Textile
					
                    ,GROUP_CONCAT(CONCAT(IF(PM.PT_ID = 2, IFNULL(MT.Material, ''), ''),
                        IF(PM.PT_ID = 2,
						CASE ODD.IsExist
							WHEN 0 THEN '(Нет)'
							WHEN 1 THEN '(Зак.)'
							WHEN 2 THEN '(Есть)'
						END, ''),
					'<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Plastic
                    ,GROUP_CONCAT(CONCAT(ODS_WD.fitter, '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Fitters
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
			  LEFT JOIN (SELECT ODS.ODD_ID, BIT_AND(ODS.IsReady) IsReady, GROUP_CONCAT(IF(ODS.ST_ID IN (2,8), WD.Name, '') SEPARATOR '') fitter
						FROM OrdersDataSteps ODS
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						GROUP BY ODS.ODD_ID) ODS_WD ON ODS_WD.ODD_ID = ODD.ODD_ID

			  WHERE OD.IsReady = 0
              GROUP BY OD.OD_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
        echo "<tr><td>{$row["ClientName"]}<br>{$row["Shop"]}</td>";
        echo "<td class='nowrap'>{$row["Zakaz"]}</td>";
        echo "<td class='nowrap'>{$row["Fitters"]}</td>";
        echo "<td class='nowrap'>{$row["Plastic"]}</td>";
        echo "<td class='nowrap'>{$row["Color"]}</td>";
        echo "<td class='nowrap'>{$row["Textile"]}</td>";
        echo "<td></td></tr>";
	}
    ?>
        </tbody>
    </table>
</body>
</html>
