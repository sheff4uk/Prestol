<?
	include "config.php";

	$datediff = 60; // Максимальный период отображения данных
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Екатеринбург</title>
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css">
	<link rel='stylesheet' type='text/css' href='css/style.css'>
	<link rel='stylesheet' type='text/css' href='css/font-awesome.min.css'>
	<link rel='stylesheet' type='text/css' href='css/buttons.css'>
	<link rel='stylesheet' type='text/css' href='css/animate.css'>
	<script src="js/jquery-1.11.3.min.js"></script>
	<script src="js/ui/jquery-ui.js"></script>
	<script src="js/jquery.ui.datepicker-ru.js"></script>
	<script src="js/modal.js"></script>
	<script src="js/script.js" type="text/javascript"></script>
	<script src="js/jquery.printPage.js" type="text/javascript"></script>
	<script src="js/jquery.columnhover.js" type="text/javascript"></script>
	<script type="text/javascript" src="js/noty/packaged/jquery.noty.packaged.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.4.0/clipboard.min.js"></script>

	<script>
		$(document).ready(function(){
			$( 'input[type=submit], .button, button' ).button();
		});
	</script>

<?
	$archive = ($_GET["archive"] == 1) ? 1 : 0;
?>

</head>
<body style='background: <?= ( $archive == 1 ) ? "#bf8" : "#fff" ?>'>
	<nav class="navbar">
		<div class="navbar-header"  id="main">
			<a class="navbar-brand" href="/ekb.php" title="На главную">ПРЕСТОЛ</a>
		</div>
	</nav>

	<p>
		<?
		if( $archive == 1 )
		{
			echo "<a href='/ekb.php' class='button'>К в работе</a>";
		}
		else
		{
			echo "<a href='/ekb.php?archive=1' class='button'>К готовым</a>";
		}
		?>
	</p>

	<div class="wr_main_table_head">
	<table class="main_table">
		<thead>
		<tr>
			<th width="45"><label for="CD">Код</label></th>
			<th width="5%"><label for="CN">Заказчик</label></th>
			<th width="5%"><label for="SD">Дата<br>приема</label></th>
			<th width="5%"><label for="ED">Дата<br>сдачи</label></th>
			<th width="5%"><label for="SH">Салон</label></th>
			<th width="5%"><label for="ON">№<br>квитанции</label></th>
			<th width="15%"><label for="Z">Заказ</label></th>
			<th width="15%"><label for="M">Материал</label></th>
			<th width="15%"><label for="CR">Цвет<br>краски</label></th>
			<th width="100"><label for="PR">Этапы</label></th>
			<th width="45"><label for="IP">Лакировка</label></th>
			<th width="15%"><label for="N">Примечание</label></th>
		</tr>
		</thead>
	</table>
	</div>
	<div class="wr_main_table_body"> <!-- Обертка тела таблицы -->
	<table class="main_table">
		<thead style="">
		<tr>
			<th width="45"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="15%"></th>
			<th width="15%"></th>
			<th width="15%"></th>
			<th width="100"></th>
			<th width="45"></th>
			<th width="15%"></th>
		</tr>
		</thead>
		<tbody>
<?
	$OD_IDs = "0"; // Сюда будем записывать список выбранных ID заказов для автокомплита

	// Снимаем ограничение в 1024 на GROUP_CONCAT
	$query = "SET @@group_concat_max_len = 10000;";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	$query = "SELECT OD.OD_ID
					,OD.Code
					,IFNULL(OD.ClientName, '') ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
					,DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') ReadyDate
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,OD.OrderNumber
					,OD.Comment
					,COUNT(ODD_ODB.itemID) Child
					,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
					,OD.Color
					,OD.IsPainting
					,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
					,GROUP_CONCAT(ODD_ODB.Steps SEPARATOR '') Steps
					,BIT_OR(IFNULL(ODD_ODB.PRfilter, 1)) PRfilter
					,IF(DATEDIFF(OD.EndDate, NOW()) <= 7, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline

					,BIT_AND(ODD_ODB.IsReady) IsReady
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN (SELECT ODD.OD_ID
							   ,1 PRfilter
			  				   ,BIT_AND(ODS.IsReady) IsReady
							   ,IFNULL(PM.PT_ID, 2) PT_ID
							   ,ODD.ODD_ID itemID

							   ,CONCAT('<span', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', '</span><br>') Zakaz

							   ,CONCAT(IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span id=\'m', ODD.ODD_ID, '\' class=\'',
								CASE ODD.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
							   '\'>', IFNULL(MT.Material, ''), '</span><br>') Material

							   ,CONCAT('<a class=\'edit_steps nowrap shadow\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR ''), '</a><br>') Steps

						FROM OrdersDataDetail ODD
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
						GROUP BY ODD.ODD_ID
						UNION
						SELECT ODB.OD_ID
							  ,1 PRfilter
							  ,BIT_AND(ODS.IsReady) IsReady
							  ,0 PT_ID
							  ,ODB.ODB_ID itemID

							  ,CONCAT('<span', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), '</span><br>') Zakaz

							  ,CONCAT(IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0 AND ODB.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), ''), '<span id=\'m', ODB.ODB_ID, '\' class=\'',
								CASE ODB.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODB.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
							  '\'>', IFNULL(MT.Material, ''), '</span><br>') Material

							  ,CONCAT('<a class=\'edit_steps nowrap shadow\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width: 30px;\' title=\'(', IFNULL(WD.Name, 'Не назначен!'), ')\'><i class=\"fa fa-cog\" aria-hidden=\"true\" style=\"line-height: 1.45em;\"></i></div>')) SEPARATOR ''), '</a><br>') Steps

			  			FROM OrdersDataBlank ODB
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						GROUP BY ODB.ODB_ID
						ORDER BY PT_ID DESC, itemID
						) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  WHERE OD.Del = 0";
			  $query .= " AND (CT.CT_ID = 2 OR OD.SH_ID IS NULL)";
			  if( $archive ) {
				  $query .= " AND OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}";
			  }
			  else {
				  $query .= " AND OD.ReadyDate IS NULL";
			  }
			  $query .= " GROUP BY OD.OD_ID HAVING PRfilter";
			  $query .= " ORDER BY OD.OD_ID";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$OD_IDs .= ",".$row["OD_ID"];
		echo "<tr id='ord{$row["OD_ID"]}'>";
		echo "<td><span class='nowrap'>{$row["Code"]}</span></td>";
		echo "<td><span><input type='checkbox' value='1' checked name='order{$row["OD_ID"]}' class='print_row' id='n{$row["OD_ID"]}'><label for='n{$row["OD_ID"]}'>></label>{$row["ClientName"]}</span></td>";
		echo "<td><span>{$row["StartDate"]}</span></td>";
		if( $archive ) {
			echo "<td><span>{$row["ReadyDate"]}</span></td>";
		}
		else {
			echo "<td><span><span class='{$row["Deadline"]}'>{$row["EndDate"]}</span></span></td>";
		}
		echo "<td><span style='background: {$row["CTColor"]};'>{$row["Shop"]}</span></td>";
		echo "<td><span>{$row["OrderNumber"]}</span></td>";
		echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		echo "<td><span class='nowrap material'>{$row["Material"]}</span></td>";
		echo "<td><span>{$row["Color"]}</span></td>";
		echo "<td><span class='nowrap material'>{$row["Steps"]}</span></td>";
		echo "<td val='{$row["IsPainting"]}'";
			switch ($row["IsPainting"]) {
				case 1:
					$class = "notready";
					$title = "Не в работе";
					break;
				case 2:
					$class = "inwork";
					$title = "В работе";
					break;
				case 3:
					$class = "ready";
					$title = "Готово";
					break;
			}
		echo " class='{$class}' title='{$title}'></td>";
		echo "<td><span>{$row["Comment"]}</span></td>";
		echo "</tr>";
	}
?>
		</tbody>
	</table>
	</div>
</body>
</html>
