<?
	include "config.php";

	$title = 'Производство';
	include "header.php";
?>

	<!-- форма фильтрации таблицы -->
	<form method='get' style='display: flex;'>
<?
		// Формирование дропдауна со списком рабочих.
		$selectworker = "<label for='worker'>Испонитель:</label>";
		$selectworker .= "<select name='worker' id='worker'>";
		$selectworker .= "<option value='0'>Не назначен</option>";
		$query = "SELECT WD.WD_ID, WD.Name, IFNULL(SUM(ODD.Amount), 0) Amount
				  FROM WorkersData WD
				  LEFT JOIN OrdersDataSteps ODS ON ODS.WD_ID = WD.WD_ID AND ODS.IsReady = 0
				  LEFT JOIN OrdersDataDetail ODD ON ODD.ODD_ID = ODS.ODD_ID
				  GROUP BY WD.WD_ID";
		$res = mysql_query($query) or die("Invalid query: " . mysql_error());
		while( $row = mysql_fetch_array($res) )
		{
			if( $_GET["worker"] == $row["WD_ID"] ) {$selected = "selected";}
			else {$selected = "";}
			$selectworker .= "<option value='{$row["WD_ID"]}' {$selected}>{$row["Name"]} ({$row["Amount"]})</option>";
		}
		$selectworker .= "</select>";
		echo $selectworker;
		echo "<div class='spase'></div>";

		// Формирование радио с типами продукции.
		echo "<label for='type'>Тип продукции:</label>";
		echo "<div class='btnset' id='type'>";
		$query = "SELECT PT_ID, Type FROM ProductTypes";
		$res = mysql_query($query) or die("Invalid query: " . mysql_error());
		while( $row = mysql_fetch_array($res) )
		{
			if( $_GET["type"] == $row["PT_ID"] ) {$checked = "checked";}
			else {$checked = "";}
			echo "<input type='radio' id='radiotype{$row["PT_ID"]}' name='type' value='{$row["PT_ID"]}' {$checked}>";
			echo "<label for='radiotype{$row["PT_ID"]}'>{$row["Type"]}</label>";
		}
		$selecttype .= "</select>";
		echo "</div>";
		echo "<div class='spase'></div>";
?>
		<label for='status'>Готовность:</label>
		<div class='btnset' id='status'>
			<input type='radio' id='radio0' name='isready' value='0,1' <?= ($_GET["isready"] =="0,1" ? "checked" : "") ?>>
				<label for='radio0'>Все</label>
			<input type='radio' id='radio1' name='isready' value='0' <?= ($_GET["isready"] =="0" ? "checked" : "") ?>>
				<label for='radio1'>Не готово</label>
			<input type='radio' id='radio2' name='isready' value='1' <?= ($_GET["isready"] =="1" ? "checked" : "") ?>>
				<label for='radio2'>Готово</label>
		</div>
		<div class='spase'></div>

		<input type='submit' value='Фильтр'>
	</form>

	<p>
		<table>
			<thead>
			<tr>
				<th>Этап</th>
				<th>Работник</th>
				<th>Тариф</th>
				<th>Готовность</th>
				<th>Заказ</th>
				<th>Цвет</th>
				<th>Ткань/Пластик</th>
				<th>Заказчик</th>
				<th>Дата приема</th>
				<th>Дата испонения</th>
				<th>Салон</th>
				<th>№ квитанции</th>
				<th>Примечание</th>
			</tr>
			</thead>
			<tbody>
<?
		$query = "SELECT OD.OD_ID
						,OD.ClientName
						,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
						,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
						,CONCAT(CT.City, '/', SH.Shop) AS Shop
						,OD.OrderNumber
						,OD.Comment
						,ODS.ST_ID
						,COUNT(1) Count
						,GROUP_CONCAT(CONCAT('<span ', IF(ODS.IsReady = 1, 'class=\'isready\' title=\'Готово\'', ''), '>', ST.Step, '</span><br>') ORDER BY PM.PT_ID, ODD.ODD_ID SEPARATOR '') Steps
						,GROUP_CONCAT(CONCAT('s:', CHAR_LENGTH(ODS.ODD_ID)+CHAR_LENGTH(ODS.ST_ID)+1, ':\"', ODS.ODD_ID, '_', ODS.ST_ID, '\";', 's:', CHAR_LENGTH(ODS.Tariff), ':\"', ODS.Tariff, '\";') ORDER BY PM.PT_ID, ODD.ODD_ID SEPARATOR '') arrTariff
						,GROUP_CONCAT(
							CONCAT('s:'
								  ,CHAR_LENGTH(ODS.ODD_ID)+CHAR_LENGTH(ODS.ST_ID)+1
								  ,':\"'
								  ,ODS.ODD_ID
								  ,'_'
								  ,ODS.ST_ID
								  ,'\";'
								  ,'a:2:{i:0;s:'
								  ,IF(ODS.WD_ID IS NULL, '8', '0')
								  ,':\"'
								  ,IF(ODS.WD_ID IS NULL, 'disabled', '')
								  ,'\";i:1;s:'
								  ,IF (ODS.IsReady, '7', '0')
								  ,':\"'
								  ,IF (ODS.IsReady, 'checked', '')
								  ,'\";}') ORDER BY PM.PT_ID, ODD.ODD_ID SEPARATOR '') arrIsReady
						,GROUP_CONCAT(CONCAT_WS(' ', ODD.Amount, PM.Model, IFNULL(PF.Form, ''), IFNULL(PME.Mechanism, ''), IFNULL(CONCAT(ODD.Length, 'х', ODD.Width), ''), '<br>') ORDER BY PM.PT_ID, ODD.ODD_ID SEPARATOR '') Zakaz
						,GROUP_CONCAT(CONCAT(IFNULL(ODD.Color, ''), '<br>') ORDER BY PM.PT_ID, ODD.ODD_ID SEPARATOR '') Color
						,GROUP_CONCAT(CONCAT('<span class=\'',
							CASE ODD.IsExist
								WHEN 0 THEN 'bg-red'
								WHEN 1 THEN 'bg-yellow'
								WHEN 2 THEN 'bg-green'
							END,
						'\'>', IFNULL(ODD.Material, ''), '</span><br>') ORDER BY PM.PT_ID, ODD.ODD_ID SEPARATOR '') Material
				  FROM OrdersData OD
				  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
				  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
				  JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
				  JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
										AND IFNULL(ODS.WD_ID, 0) = {$_GET["worker"]}
										AND ODS.IsReady IN ({$_GET["isready"]})
				  JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
				  JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = {$_GET["type"]}
				  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
				  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
				  GROUP BY OD.OD_ID, ODS.ST_ID
				  ORDER BY ST.Sort";
		$res = mysql_query( $query ) or die("Invalid query: " . mysql_error());
		if( mysql_num_rows($res) > 0 )
		{
			while( $row = mysql_fetch_array($res) )
			{
				if( isset($stid) and $stid != $row["ST_ID"])
				{
					echo "<tr><th colspan=13></th></tr>"; // Добавление разделительной черты (деление по этапам)
				}
				$stid = $row["ST_ID"];

				echo "<tr>";
				echo "<td><span class='nowrap'>{$row["Steps"]}</span></td>";
				echo "<td>";

					$query = "SELECT ODS.WD_ID
							FROM OrdersDataDetail ODD
							JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
													AND IFNULL(ODS.WD_ID, 0) = {$_GET["worker"]}
													AND ODS.IsReady IN ({$_GET["isready"]})
													AND ODS.ST_ID = {$row["ST_ID"]}
							JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = {$_GET["type"]}
							WHERE ODD.OD_ID = {$row["OD_ID"]}
							ORDER BY PM.PT_ID, ODD.ODD_ID";

					$subres = mysql_query( $query ) or die("Invalid query: " . mysql_error());
					while( $subrow = mysql_fetch_array($subres) )
					{
						// Формирование дропдауна со списком рабочих. Сортировка по релевантности.
						$selectworker = "<select name='WD_ID{$row["ST_ID"]}' id='{$row["ST_ID"]}' class='selectwr'>";
						$selectworker .= "<option value=''>-=Выберите работника=-</option>";
						$query = "SELECT WD.WD_ID, WD.Name, SUM(IFNULL(ODS.Amount, 0)) CNT
								  FROM WorkersData WD
								  LEFT JOIN (
									SELECT ODS.*, ODD.Amount 
									FROM OrdersDataSteps ODS
									JOIN OrdersDataDetail ODD ON ODD.ODD_ID = ODS.ODD_ID
									WHERE ODS.WD_ID IS NOT NULL AND ODS.ST_ID = {$row["ST_ID"]}
									LIMIT 100
								  ) ODS ON ODS.WD_ID = WD.WD_ID
								  GROUP BY WD.WD_ID
								  ORDER BY CNT DESC";
						$subsubres = mysql_query($query) or die("Invalid query: " . mysql_error());
						while( $subsubrow = mysql_fetch_array($subsubres) )
						{
							$selected = ( $subrow["WD_ID"] == $subsubrow["WD_ID"] ) ? "selected" : "";
							$selectworker .= "<option {$selected} value='{$subsubrow["WD_ID"]}'>{$subsubrow["Name"]}</option>";
						}
						$selectworker .= "</select>";
						// Конец дропдауна со списком рабочих

						echo $selectworker;
					}
				echo "</td>";
				echo "<td>";
					// Обратная сериализация стоки из запроса в массив
					// Вывод значений массива циклом
					$string = "a:{$row["Count"]}:{{$row["arrTariff"]}}";
					$arr = unserialize($string);
					foreach ($arr as $k => $v) {
						echo "<input type='number' min='0' step='10' class='tariff' name='Tariff{$k}' value='{$v}'><br>";
					}
				echo "</td>";
				echo "<td>";
					// Обратная сериализация стоки из запроса в массив
					// Вывод значений массива циклом
					$string = "a:{$row["Count"]}:{{$row["arrIsReady"]}}";
					$arr = unserialize($string);
					foreach ($arr as $k => $v) {
						echo "<input type='checkbox' id='IsReady{$k}' name='IsReady{$k}' class='isready' value='1' {$row["IsReady"]} {$row["disabled"]}";
						foreach ($v as $sv) {
							echo " $sv";
						}
						echo "><label for='IsReady{$k}' class='isready'></label><br>";
					}
				echo "</td>";
				echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
				echo "<td><span class='nowrap'>{$row["Color"]}</span></td>";
				echo "<td><span class='nowrap'>{$row["Material"]}</span></td>";
				echo "<td>{$row["ClientName"]}</td>";
				echo "<td>{$row["StartDate"]}</td>";
				echo "<td>{$row["EndDate"]}</td>";
				echo "<td>{$row["Shop"]}</td>";
				echo "<td>{$row["OrderNumber"]}</td>";
				echo "<td>{$row["Comment"]}</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
			echo "</p>";
		}
?>		
</body>
</html>
