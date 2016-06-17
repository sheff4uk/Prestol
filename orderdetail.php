<?
	session_start();
	include "config.php";

	if( (int)$_GET["id"] > 0 )
	{
		$id = (int)$_GET["id"];
		$location = "orderdetail.php?id=".$id;
		$free = 0;
	}
	else
	{
		$id = "NULL";
		$location = "orderdetail.php";
		$free = 1;
	}

	// Обновление основной информации о заказе
	if( isset($_POST["StartDate"]) )
	{
		$StartDate = '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'';
		$EndDate = $_POST[EndDate] ? '\''.date( "Y-m-d", strtotime($_POST["EndDate"]) ).'\'' : "NULL";
		$ClientName = mysqli_real_escape_string( $mysqli,$_POST["ClientName"] );
		$Shop = $_POST["Shop"] <> "" ? $_POST["Shop"] : "NULL";
		$OrderNumber = mysqli_real_escape_string( $mysqli,$_POST["OrderNumber"] );
		$Color = mysqli_real_escape_string( $mysqli,$_POST["Color"] );
		$IsPainting = $_POST["IsPainting"];
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		$query = "UPDATE OrdersData
				  SET CLientName = '{$ClientName}'
				     ,StartDate = $StartDate
				     ,EndDate = $EndDate
				     ,SH_ID = $Shop
				     ,OrderNumber = '{$OrderNumber}'
				     ,Color = '{$Color}'
				     ,IsPainting = $IsPainting
				     ,Comment = '{$Comment}'
				  WHERE OD_ID = {$id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		
		header( "Location: ".$location );
		die;
	}

	// Добавление в базу нового изделия. Заполнение этапов.
	if ( $_GET["add"] )
	{
		// Добавление в заказ свободных изделий
		if( $_POST["free"] ) {
			foreach( $_POST as $k => $v)
			{
				if( strpos($k,"amount") === 0 )
				{
					$prodid = (int)str_replace( "amount", "", $k );

					// Узнаем общее количество свободных изделий в группе
					$query = "SELECT Amount FROM OrdersDataDetail WHERE ODD_ID = {$prodid}";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$amount = mysqli_result($res,0,'Amount');

					if( $amount == $v ) {
						$query = "UPDATE OrdersDataDetail SET OD_ID = {$id} WHERE ODD_ID = {$prodid}";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					}
					else {
						// Изменяем количество изделий в свободных
						$query = "UPDATE OrdersDataDetail SET Amount = {$amount} - {$v} WHERE ODD_ID = {$prodid}";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

						// Добавляем указанное количество изделий в заказ
						$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, Length, Width, PF_ID, PME_ID, Material, IsExist, Amount, Color, order_date, arrival_date)
								SELECT {$id}, PM_ID, Length, Width, PF_ID, PME_ID, Material, IsExist, {$v}, Color, order_date, arrival_date FROM OrdersDataDetail WHERE ODD_ID = {$prodid}";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$odd_id = mysqli_insert_id( $mysqli );

						// Копируем этапы
						$query = "INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, WD_ID, IsReady, Tariff)
									SELECT {$odd_id}, ST_ID, WD_ID, IsReady, Tariff
									FROM OrdersDataSteps
									WHERE ODD_ID = {$prodid}";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$prodid = $odd_id;
					}
				}
			}
			header( "Location: ".$location."#".$prodid );
			die;
		}
		else {
			// Добавление в базу нового изделия
			$Model = $_POST["Model"] ? "{$_POST["Model"]}" : "NULL";
			$Form = $_POST["Form"] ? "{$_POST["Form"]}" : "NULL";
			$Mechanism = $_POST["Mechanism"] ? "{$_POST["Mechanism"]}" : "NULL";
			$Length = $_POST["Length"] ? "{$_POST["Length"]}" : "NULL";
			$Width = $_POST["Width"] ? "{$_POST["Width"]}" : "NULL";
			$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : 0;
			$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
			$Color = mysqli_real_escape_string( $mysqli,$_POST["Color"] );
			$OrderDate = $_POST["order_date"] ? date( 'Y-m-d', strtotime($_POST["order_date"]) ) : '';
			$ArrivalDate = $_POST["arrival_date"] ? date( 'Y-m-d', strtotime($_POST["arrival_date"]) ) : '';

			$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, Length, Width, PF_ID, PME_ID, Material, IsExist, Amount, Color, order_date, arrival_date)
					  VALUES ({$id}, {$Model}, {$Length}, {$Width}, {$Form}, {$Mechanism}, '{$Material}', {$IsExist}, {$_POST["Amount"]}, '{$Color}', '{$OrderDate}', '{$ArrivalDate}')";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$odd_id = mysqli_insert_id( $mysqli );

			// Вычисляем тарифи для разных этапов и записываем их
			if( $_POST["Model"] ) {
				$query="INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, Tariff)
						SELECT {$odd_id}
							  ,ST.ST_ID
							  ,(IFNULL(ST.Tariff, 0) + IFNULL(PMET.Tariff, 0) + IFNULL(PMOT.Tariff, 0) + IFNULL(PSLT.Tariff, 0))
						FROM StepsTariffs ST
						JOIN ProductModelsTariff PMOT ON PMOT.ST_ID = ST.ST_ID AND PMOT.PM_ID = IFNULL({$Model}, 0)
						LEFT JOIN ProductMechanismTariff PMET ON PMET.ST_ID = ST.ST_ID AND PMET.PME_ID = {$Mechanism}
						LEFT JOIN ProductSizeLengthTariff PSLT ON PSLT.ST_ID = ST.ST_ID AND {$Length} BETWEEN PSLT.From AND PSLT.To";
			}
			// Если модель не указана - присваиваем дефолтные этапы
			else {
				$query="INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, Tariff)
						SELECT {$odd_id}
							  ,ST.ST_ID
							  ,(IFNULL(ST.Tariff, 0) + IFNULL(PMET.Tariff, 0) + IFNULL(PMOT.Tariff, 0) + IFNULL(PSLT.Tariff, 0))
						FROM StepsTariffs ST
						LEFT JOIN ProductModelsTariff PMOT ON PMOT.ST_ID = ST.ST_ID AND PMOT.PM_ID = IFNULL({$Model}, 0)
						LEFT JOIN ProductMechanismTariff PMET ON PMET.ST_ID = ST.ST_ID AND PMET.PME_ID = {$Mechanism}
						LEFT JOIN ProductSizeLengthTariff PSLT ON PSLT.ST_ID = ST.ST_ID AND {$Length} BETWEEN PSLT.From AND PSLT.To
						WHERE ST.Default = 1";
			}
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			$_SESSION["odd_id"] = $odd_id; // Cохраняем в сессию id вставленной записи
			header( "Location: ".$location."#".$odd_id ); // Перезагружаем экран
			die;
		}
	}
	else
	{
		$odd_id = $_SESSION["odd_id"]; // Читаем из сессии id вставленной записи
		unset($_SESSION["odd_id"]); // Очищаем сессию
	}

	// Удаление изделия (перемещение в свободные)
	if( $_GET["del"] )
	{
		$odd_id = (int)$_GET["del"];

        $query = "SELECT IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress, OD.Color, IFNULL(OD.IsPainting, 0) IsPainting
                  FROM OrdersDataDetail ODD
				  LEFT JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
                  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
                  WHERE ODD.ODD_ID = {$odd_id}";
        $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
        $inprogress = mysqli_result($res,0,'inprogress');
        $color = mysqli_result($res,0,'Color');
        $ispainting = mysqli_result($res,0,'IsPainting');

        if( $inprogress == 0 ) { // Если не приступили, то удаляем. Иначе - переносим в свободные.
            $query = "DELETE FROM OrdersDataSteps WHERE ODD_ID={$odd_id}";
            mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

            $query = "DELETE FROM OrdersDataDetail WHERE ODD_ID={$odd_id}";
            mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
        }
        else {
            $query = "UPDATE OrdersDataDetail SET OD_ID = NULL, is_check = 0, Color = IF({$ispainting} > 1, '{$color}', NULL) WHERE ODD_ID={$odd_id}";
            mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
            
            $_SESSION["alert"] = 'Изделия отправлены в "Свободные". Пожалуйста, проверьте информацию по этапам производства и параметрам изделий на экране "Свободные" (выделены красным фоном).';
        }

		header( "Location: ".$location ); // Перезагружаем экран
		die;
	}

	if( $id != "NULL" )
	{
		$title = 'Детали заказа';
	}
	else
	{
		$title = 'Свободные изделия';
	}
	include "header.php";
	include "autocomplete.php"; //JavaScript
?>

	<? include "forms.php"; ?>

	<p><a href='/#<?= $_GET["id"] ?>' class='button'><< На главную</a></p>

<?
	if( $id != "NULL" )
	{
?>
	<table>
		<thead>
		<tr>
			<th>Заказчик</th>
			<th>Дата&nbsp;приема</th>
			<th>Дата&nbsp;сдачи</th>
			<th>Салон</th>
			<th>№&nbsp;квитанции</th>
			<th>Цвет</th>
			<th>Лакировка</th>
			<th>Примечание</th>
			<th>Действие</th>
		</tr>
		</thead>
<?
	$query = "SELECT ClientName
					,DATE_FORMAT(StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(EndDate, '%d.%m.%Y') EndDate
					,IFNULL(SH_ID, 0) SH_ID
					,OrderNumber
					,Color
					,IsPainting
					,Comment
			  FROM OrdersData
			  WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$ClientName = mysqli_result($res,0,'ClientName');
	$StartDate = mysqli_result($res,0,'StartDate');
	$EndDate = mysqli_result($res,0,'EndDate');
	$Shop = mysqli_result($res,0,'SH_ID');
	$OrderNumber = mysqli_result($res,0,'OrderNumber');
	$Color = mysqli_result($res,0,'Color');
	$IsPainting = mysqli_result($res,0,'IsPainting');
	$Comment = mysqli_result($res,0,'Comment');
?>
		<form method='post'>
		<tbody>
		<tr>
			<td><input type='text' name='ClientName' size='10' value='<?=$ClientName?>'></td>
			<td><input required type='text' name='StartDate' size='8' class='date' value='<?=$StartDate?>'></td>
			<td><input type='text' name='EndDate' size='8' class='date' value='<?=$EndDate?>'></td>
			<td>
				<select name='Shop' style="width: 150px;">
					<option value="">-=Выберите салон=-</option>
					<?
					$query = "SELECT Shops.SH_ID, CONCAT(Cities.City, '/', Shops.Shop) AS Shop, IF(Shops.SH_ID = {$Shop}, 'selected', '') AS selected
								FROM Shops
								JOIN Cities ON Cities.CT_ID = Shops.CT_ID
								ORDER BY Cities.City, Shops.Shop";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<option value='{$row["SH_ID"]}' {$row["selected"]}>{$row["Shop"]}</option>";
					}
					?>
				</select>
			</td>
			<td><input type='text' name='OrderNumber' size='10' value='<?=$OrderNumber?>'></td>
			<td><input required type='text' class='colortags' name='Color' size='20' value='<?=$Color?>' autocomplete='off'></td>
			<td>
				<div id="IsPainting" class="btnset nowrap">
					<input type="radio" id="IsP1" name="IsPainting" <?=($IsPainting == 1 ? "checked" : "")?> value="1"><label for="IsP1" title="Не в работе"><i class="fa fa-star-o fa-lg"></i></label>
					<input type="radio" id="IsP2" name="IsPainting" <?=($IsPainting == 2 ? "checked" : "")?> value="2"><label for="IsP2" title="В работе"><i class="fa fa-star-half-o fa-lg"></i></label>
					<input type="radio" id="IsP3" name="IsPainting" <?=($IsPainting == 3 ? "checked" : "")?> value="3"><label for="IsP3" title="Готово"><i class="fa fa-star fa-lg"></i></label>
				</div>
			</td>
			<td><textarea name='Comment' rows='6' cols='15'><?=$Comment?></textarea></td>
			<td><input type='submit' value='Сохранить'></td>
		</tr>
		</tbody>
		</form>
	</table>
<?
	}
?>	
	<p>
		<button class='edit_product1'<?=($id == 'NULL')?' id=\'0\'':''?> free='<?=$free?>'>Добавить стулья</button>
		<button class='edit_product2'<?=($id == 'NULL')?' id=\'0\'':''?> free='<?=$free?>'>Добавить столы</button>
	</p>

	<!-- Таблица изделий -->
	<table>
		<thead>
		<tr>
			<th>Кол-во</th>
			<th>Модель</th>
			<th>Форма</th>
			<th>Механизм</th>
			<th>Размер</th>
			<th>Прогресс</th>
			<th>Ткань/пластик</th>
			<?= ($id == "NULL") ? "<th>Цвет</th>" : "" ?>
			<th>Действие</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "SELECT ODD.ODD_ID
					,IFNULL(PM.PT_ID, 2) PT_ID
					,PM.Model
					,CONCAT(ODD.Length, 'х', ODD.Width) Size
					,PF.Form
					,PME.Mechanism
					,ODD.PM_ID
					,ODD.Length
					,ODD.Width
					,ODD.PF_ID
					,ODD.PME_ID
					,ODD.Material
					,ODD.IsExist
					,ODD.Amount
					,ODD.Color
                    ,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
                    ,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
                    ,IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), '') clock
                    ,IF(ODD.is_check = 1, '', 'attention') is_check
					,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
			  FROM OrdersDataDetail ODD
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID";
	if( $id != "NULL" )
	{
		$query .= " WHERE ODD.OD_ID = {$id}";
	}
	else
	{
		$query .= " WHERE ODD.OD_ID IS NULL";
	}
	$query .= " GROUP BY ODD.ODD_ID ORDER BY PT_ID DESC, PM.Model, ODD.ODD_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr class='{$row["is_check"]}' id='{$row["ODD_ID"]}'>";
		echo "<td><img src='/img/product_{$row["PT_ID"]}.png' style='height:16px'>x{$row["Amount"]}</td>";
		echo "<td>{$row["Model"]}</td>";
		echo "<td>{$row["Form"]}</td>";
		echo "<td>{$row["Mechanism"]}</td>";
		echo "<td>{$row["Size"]}</td>";

		// Формируем список этапов
		$query = "SELECT ST.Step
						,ST.Short
						,(30 * ST.Size) Size
						,IFNULL(WD.Name, 'Не назначен!') Name
						,IF(ODS.IsReady, 'checked', '') IsReady
						,ODS.ST_ID
						,IF(ODS.WD_ID IS NULL, 'disabled', '') disabled
				  FROM OrdersDataSteps ODS
				  LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
				  JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
				  WHERE ODD_ID = {$row["ODD_ID"]}
				  ORDER BY ST.Sort";
		$sub_res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$steps = "<a href='#' id='{$row["ODD_ID"]}' class='edit_steps nowrap' location='{$location}'>";
		while( $sub_row = mysqli_fetch_array($sub_res) )
		{
			$steps .= "<input type='checkbox' class='checkstatus' {$sub_row["IsReady"]} id='{$row["ODD_ID"]}{$sub_row["ST_ID"]}' {$sub_row["disabled"]}><label class='step' style='width:{$sub_row["Size"]}px;' for='{$row["ODD_ID"]}{$sub_row["ST_ID"]}' title='{$sub_row["Step"]} ({$sub_row["Name"]})'>{$sub_row["Short"]}</label>";
		}
		$steps .= "</a>";
		echo "<td>{$steps}</td>";

		echo "<td>";
		switch ($row["IsExist"]) {
			case 0:
				echo "<span class='bg-red'>";
				break;
			case 1:
				echo "{$row["clock"]}<span class='bg-yellow' title='Заказано: {$row["order_date"]}&emsp;Ожидается: {$row["arrival_date"]}'>";
				break;
			case 2:
				echo "<span class='bg-green'>";
				break;
		}
		echo "{$row["Material"]}</span></td>";
		if ($id == "NULL") echo "<td>{$row["Color"]}</td>"; // Цвет показываем только в свободных
		echo "<td><a href='#' id='{$row["ODD_ID"]}' free='{$free}' class='button edit_product{$row["PT_ID"]}' location='{$location}' title='Редактировать изделие'><i class='fa fa-pencil fa-lg'></i></a>";
		
		// Не показываем кнопку "Удалить" только в свободных если прогресс не 0
		if( !($id == "NULL" && $row["inprogress"] != 0) )
		{
			$delmessage = "Удалить {$row["Model"]}({$row["Amount"]} шт.) {$row["Form"]} {$row["Mechanism"]} {$row["Size"]}?";
			echo "<a class='button' onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&del={$row["ODD_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
		}
		echo "<img hidden='true' src='/img/attention.png' class='attention' title='Требуется проверка данных после переноса изделий в \"Свободные\".'></td></tr>";

		$ODD[$row["ODD_ID"]] = array( "amount"=>$row["Amount"], "model"=>$row["PM_ID"], "form"=>$row["PF_ID"], "mechanism"=>$row["PME_ID"], "length"=>$row["Length"], "width"=>$row["Width"], "color"=>$row["Color"], "material"=>$row["Material"], "isexist"=>$row["IsExist"], "inprogress"=>$row["inprogress"], "order_date"=>$row["order_date"], "arrival_date"=>$row["arrival_date"] );
	}
?>
		</tbody>
	</table>
	<!-- Конец таблицы изделий -->
	
	<p>
		<button class='edit_product1'<?=($id == 'NULL')?' id=\'0\'':''?> free='<?=$free?>'>Добавить стулья</button>
		<button class='edit_product2'<?=($id == 'NULL')?' id=\'0\'':''?> free='<?=$free?>'>Добавить столы</button>
	</p>

</body>
	<script>
		odid = <?= ($id == 'NULL') ? 0 : $id ?>;
        
		$('.attention img').show();
				
		// Открытие диалога этапов после добавления изделия
//		if( '<?=$odd_id?>' != '' ) {
//			$( document ).ready(function() {
//				makeform(<?= $odd_id ? $odd_id : 0 ?>, '<?=$location?>');
//				return false;
//			});
//		}
	</script>
</html>
<script>
	odd = <?= json_encode($ODD); ?>;
</script>
