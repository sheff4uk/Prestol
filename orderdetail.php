<?
//	session_start();
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
	if( isset($_POST["Shop"]) )
	{
//		$StartDate = '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'';
		$EndDate = $_POST[EndDate] ? '\''.date( "Y-m-d", strtotime($_POST["EndDate"]) ).'\'' : "NULL";
		$ClientName = mysqli_real_escape_string( $mysqli,$_POST["ClientName"] );
		$Shop = $_POST["Shop"] > 0 ? $_POST["Shop"] : "NULL";
		$OrderNumber = mysqli_real_escape_string( $mysqli,$_POST["OrderNumber"] );
		$Color = mysqli_real_escape_string( $mysqli,$_POST["Color"] );
		$IsPainting = $_POST["IsPainting"];
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		// Удаляем лишние пробелы
		$ClientName = trim($ClientName);
		$OrderNumber = trim($OrderNumber);
		$Color = trim($Color);
		$Comment = trim($Comment);
		$query = "UPDATE OrdersData
				  SET CLientName = '{$ClientName}'
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
						$query = "INSERT INTO OrdersDataSteps(ODD_ID, ST_ID, WD_ID, IsReady, Tariff, Visible)
									SELECT {$odd_id}, ST_ID, WD_ID, IsReady, Tariff, Visible
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
			$Length = $_POST["Type"] == 2 ? "{$_POST["Length"]}" : "NULL";
			$Width = $_POST["Type"] == 2 ? "{$_POST["Width"]}" : "NULL";
			$PieceAmount = $_POST["PieceAmount"] ? "{$_POST["PieceAmount"]}" : "NULL";
			$PieceSize = $_POST["PieceSize"] ? "{$_POST["PieceSize"]}" : "NULL";
			$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : 0;
			$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
			$Color = mysqli_real_escape_string( $mysqli,$_POST["Color"] );
			$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
			// Удаляем лишние пробелы
			$Material = trim($Material);
			$Color = trim($Color);
			$Comment = trim($Comment);
			$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
			$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";

			$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, Length, Width, PieceAmount, PieceSize, PF_ID, PME_ID, Material, IsExist, Amount, Color, Comment, order_date, arrival_date)
					  VALUES ({$id}, {$Model}, {$Length}, {$Width}, {$PieceAmount}, {$PieceSize}, {$Form}, {$Mechanism}, '{$Material}', {$IsExist}, {$_POST["Amount"]}, '{$Color}', '{$Comment}', {$OrderDate}, {$ArrivalDate})";
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
	else {
		$odd_id = $_SESSION["odd_id"]; // Читаем из сессии id вставленной записи
		unset($_SESSION["odd_id"]); // Очищаем сессию
	}

	// Добавление к заказу заготовки или прочего
	if ( $_GET["addblank"] ) {
		$Blank = $_POST["Blanks"] ? "{$_POST["Blanks"]}" : "NULL";
		$Other = trim($_POST["Other"]);
		$Other = mysqli_real_escape_string( $mysqli, $Other );
		$IsExist = $_POST["IsExist"] ? "{$_POST["IsExist"]}" : 0;
		$Material = mysqli_real_escape_string( $mysqli,$_POST["Material"] );
		$Material = trim($Material);
		$OrderDate = $_POST["order_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["order_date"]) ).'\'' : "NULL";
		$ArrivalDate = $_POST["arrival_date"] ? '\''.date( 'Y-m-d', strtotime($_POST["arrival_date"]) ).'\'' : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		$Comment = trim($Comment);

		$query = "INSERT INTO OrdersDataBlank(OD_ID, BL_ID, Other, Amount, Comment, Material, IsExist, order_date, arrival_date)
				  VALUES ({$id}, {$Blank}, '{$Other}', {$_POST["Amount"]}, '{$Comment}', '{$Material}', {$IsExist}, {$OrderDate}, {$ArrivalDate})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$odb_id = mysqli_insert_id( $mysqli );

		// Если "Прочее" - добавляем этап производства
		if( $Blank == "NULL" ) {
			$query="INSERT INTO OrdersDataSteps SET ODB_ID = {$odb_id}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		$_SESSION["odb_id"] = $odb_id; // Cохраняем в сессию id вставленной записи
		header( "Location: ".$location."#".$odb_id ); // Перезагружаем экран
		die;
	}
	else {
		$odb_id = $_SESSION["odb_id"]; // Читаем из сессии id вставленной записи
		unset($_SESSION["odb_id"]); // Очищаем сессию
	}

	// Удаление изделия (перемещение в свободные)
	if( $_GET["del"] )
	{
		$odd_id = (int)$_GET["del"];

        $query = "SELECT IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress, OD.Color, IFNULL(OD.IsPainting, 0) IsPainting
                  FROM OrdersDataDetail ODD
				  LEFT JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
                  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
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

	// Удаление заготовки из заказа
	if( $_GET["delblank"] ) {
		$odb_id = (int)$_GET["delblank"];

		$query = "DELETE FROM OrdersDataSteps WHERE ODB_ID={$odb_id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		$query = "DELETE FROM OrdersDataBlank WHERE ODB_ID={$odb_id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

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
//	include "autocomplete.php"; //JavaScript
?>

	<? include "forms.php"; ?>

	<p><a href='/#ord<?= $_GET["id"] ?>' class='button'><< На главную</a></p>

<?
	if( $id != "NULL" )
	{
?>
	<form method='post'>
	<table>
		<thead>
		<tr>
			<th>Код</th>
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
	$query = "SELECT OD.Code
					,OD.ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
					,IFNULL(OD.SH_ID, 0) SH_ID
					,OD.OrderNumber
					,OD.Color
					,OD.IsPainting
					,OD.Comment
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  WHERE OD_ID = {$id}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$Code = mysqli_result($res,0,'Code');
	$ClientName = mysqli_result($res,0,'ClientName');
	$StartDate = mysqli_result($res,0,'StartDate');
	$EndDate = mysqli_result($res,0,'EndDate');
	$Shop = mysqli_result($res,0,'SH_ID');
	$OrderNumber = mysqli_result($res,0,'OrderNumber');
	$Color = mysqli_result($res,0,'Color');
	$IsPainting = mysqli_result($res,0,'IsPainting');
	$Comment = mysqli_result($res,0,'Comment');
	$CTColor = mysqli_result($res,0,'CTColor');
?>
		<tbody>
		<tr>
			<td class="nowrap"><?=$Code?></td>
			<td><input type='text' class='clienttags' name='ClientName' size='10' value='<?=$ClientName?>'></td>
			<td><input required type='text' name='StartDate' size='8' class='date from' value='<?=$StartDate?>' readonly></td>
			<td><input type='text' name='EndDate' size='8' class='date to' value='<?=$EndDate?>' readonly></td>
			<td style='background: <?=$CTColor?>;'>
				<select required name='Shop'>
					<option value="">-=Выберите салон=-</option>
					<option value="0" selected style="background: #999;">Свободные</option>
					<?
					$query = "SELECT Shops.SH_ID
									,CONCAT(Cities.City, '/', Shops.Shop) AS Shop
									,IF(Shops.SH_ID = {$Shop}, 'selected', '') AS selected
									,Cities.Color CTColor
								FROM Shops
								JOIN Cities ON Cities.CT_ID = Shops.CT_ID
								ORDER BY Cities.City, Shops.Shop";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<option value='{$row["SH_ID"]}' {$row["selected"]} style='background: {$row["CTColor"]};'>{$row["Shop"]}</option>";
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
	</table>
	</form>
<?
	}
?>
	<p>
		<button class='edit_product1'<?=($id == 'NULL')?' id=\'0\'':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить стулья</button>
		<button class='edit_product2'<?=($id == 'NULL')?' id=\'0\'':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить столы</button>
		<?
		if( $id != "NULL" ) {
			?>
			<button class='edit_order_blank'<?=($id == 'NULL')?' id=\'0\'':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить заготовки/прочее</button>
			<?
		}
		?>
	</p>

	<!-- Таблица изделий -->
	<table>
		<thead>
		<tr>
			<th>Кол-во</th>
			<th>Модель</th>
			<th>Размер</th>
			<th>Форма</th>
			<th>Механизм</th>
			<th>Этапы</th>
			<th>Ткань/пластик</th>
			<?= ($id == "NULL") ? "<th>Цвет</th>" : "" ?>
			<th>Примечание</th>
			<th>Действие</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "SELECT ODD.ODD_ID
					,IFNULL(PM.PT_ID, 2) PT_ID
					,PM.Model
					,CONCAT(ODD.Length, 'х', ODD.Width, IFNULL(CONCAT('/', ODD.PieceAmount, 'x', ODD.PieceSize), '')) Size
					,PF.Form
					,PME.Mechanism
					,ODD.PM_ID
					,ODD.Length
					,ODD.Width
					,ODD.PieceAmount
					,ODD.PieceSize
					,ODD.PF_ID
					,ODD.PME_ID
					,ODD.Material
					,ODD.IsExist
					,ODD.Amount
					,ODD.Color
					,ODD.Comment
					,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
					,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
					,IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), '') clock
					,IF(ODD.is_check = 1, '', 'attention') is_check
					,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
					,GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR '') Steps
					,IF(SUM(ODS.Old) > 0, ' attention', '') Attention
			  FROM OrdersDataDetail ODD
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
			  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
			  LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID";
	if( $id != "NULL" )
	{
		$query .= " WHERE ODD.OD_ID = {$id}";
	}
	else
	{
		$query .= " WHERE ODD.OD_ID IS NULL";
	}
	$query .= " GROUP BY ODD.ODD_ID ORDER BY IFNULL(PM.PT_ID, 2) DESC, PM.Model, ODD.ODD_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr class='{$row["is_check"]}' id='{$row["ODD_ID"]}'>";
		echo "<td><img src='/img/product_{$row["PT_ID"]}.png' style='height:16px'>x{$row["Amount"]}</td>";
		echo "<td>{$row["Model"]}</td>";
		echo "<td>{$row["Size"]}</td>";
		echo "<td>{$row["Form"]}</td>";
		echo "<td>{$row["Mechanism"]}</td>";
		echo "<td><a href='#' id='{$row["ODD_ID"]}' class='edit_steps nowrap shadow{$row["Attention"]}' location='{$location}'>{$row["Steps"]}</a></td>";
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
		echo "<td>{$row["Comment"]}</td>";
		echo "<td><a href='#' id='{$row["ODD_ID"]}' free='{$free}' class='button edit_product{$row["PT_ID"]}' location='{$location}' title='Редактировать изделие'><i class='fa fa-pencil fa-lg'></i></a>";
		
		// Не показываем кнопку "Удалить" только в свободных если прогресс не 0
		if( !($id == "NULL" && $row["inprogress"] != 0) )
		{
			$delmessage = "Удалить {$row["Model"]}({$row["Amount"]} шт.) {$row["Form"]} {$row["Mechanism"]} {$row["Size"]}?";
			echo "<a class='button' onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&del={$row["ODD_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
		}
		echo "<img hidden='true' src='/img/attention.png' class='attention' title='Требуется проверка данных после переноса изделий в \"Свободные\".'></td></tr>";

		$ODD[$row["ODD_ID"]] = array( "amount"=>$row["Amount"], "model"=>$row["PM_ID"], "form"=>$row["PF_ID"], "mechanism"=>$row["PME_ID"], "length"=>$row["Length"], "width"=>$row["Width"], "PieceAmount"=>$row["PieceAmount"], "PieceSize"=>$row["PieceSize"], "color"=>$row["Color"], "comment"=>$row["Comment"], "material"=>$row["Material"], "isexist"=>$row["IsExist"], "inprogress"=>$row["inprogress"], "order_date"=>$row["order_date"], "arrival_date"=>$row["arrival_date"] );
	}
?>
		</tbody>
	</table>
	<!-- Конец таблицы изделий -->

	<!-- Таблица заготовок -->
<?
	$query = "SELECT ODB.ODB_ID
					,ODB.Amount
					,ODB.BL_ID
					,IFNULL(BL.Name, ODB.Other) Name
					,ODB.Other
					,ODB.Comment
					,ODB.Material
					,ODB.IsExist
					,DATE_FORMAT(ODB.order_date, '%d.%m.%Y') order_date
					,DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y') arrival_date
					,IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), '') clock
					,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
					,GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, '', ' unvisible'), '\' style=\'width: 30px;\' title=\'(', IFNULL(WD.Name, 'Не назначен!'), ')\'><i class=\"fa fa-cog\" aria-hidden=\"true\" style=\"line-height: 1.45em;\"></i></div>')) SEPARATOR '') Steps
					,IF(SUM(ODS.Old) > 0, ' attention', '') Attention
			  FROM OrdersDataBlank ODB
			  LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
			  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
			  LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID";
	if( $id != "NULL" )
	{
		$query .= " WHERE ODB.OD_ID = {$id}";
	}
	else
	{
		$query .= " WHERE ODB.OD_ID IS NULL";
	}
	$query .= " GROUP BY ODB.ODB_ID ORDER BY ODB.ODB_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	if( mysqli_num_rows($res) ) {
	?>
		<br><br>
		<table>
			<thead>
			<tr>
				<th>Кол-во</th>
				<th>Заготовка/прочее</th>
				<th>Этапы</th>
				<th>Материал</th>
				<th>Примечание</th>
				<th>Действие</th>
			</tr>
			</thead>
			<tbody>
	<?
	}

	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr id='{$row["ODB_ID"]}'>";
		echo "<td>{$row["Amount"]}</td>";
		echo "<td>{$row["Name"]}</td>";
		echo "<td><a href='#' odbid='{$row["ODB_ID"]}' class='edit_steps nowrap shadow{$row["Attention"]}' location='{$location}'>{$row["Steps"]}</a></td>";
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
		echo "<td>{$row["Comment"]}</td>";
		echo "<td><a href='#' id='{$row["ODB_ID"]}' class='button edit_order_blank' location='{$location}' title='Редактировать'><i class='fa fa-pencil fa-lg'></i></a>";
		if( $row["inprogress"] == 0 ) {
			$delmessage = "Удалить {$row["Name"]}({$row["Amount"]} шт.)?";
			echo "<a class='button' onclick='if(confirm(\"{$delmessage}\", \"?id={$id}&delblank={$row["ODB_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
		}

		$ODB[$row["ODB_ID"]] = array( "amount"=>$row["Amount"], "blank"=>$row["BL_ID"], "other"=>$row["Other"], "comment"=>$row["Comment"], "material"=>$row["Material"], "isexist"=>$row["IsExist"], "inprogress"=>$row["inprogress"], "order_date"=>$row["order_date"], "arrival_date"=>$row["arrival_date"] );
	}
?>
		</tbody>
	</table>
	<!-- Конец таблицы заготовок -->

	<p>
		<button class='edit_product1'<?=($id == 'NULL')?' id="0"':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить стулья</button>
		<button class='edit_product2'<?=($id == 'NULL')?' id="0"':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить столы</button>
		<?
		if( $id != "NULL" ) {
			?>
			<button class='edit_order_blank'<?=($id == 'NULL')?' id=\'0\'':''?><?=($id == 'NULL') ? '' : ' odid="'.$id.'"'?> free='<?=$free?>'>Добавить заготовки/прочее</button>
			<?
		}
		?>
	</p>

</body>
</html>

<script>
	$(document).ready(function(){
//		odid = <?= ($id == 'NULL') ? 0 : $id ?>;

		$('.attention img').show();

		odd = <?= json_encode($ODD); ?>;
		odb = <?= json_encode($ODB); ?>;

//		$("input.from[name='StartDate']").datepicker("disable");
//		$( "input.to" ).datepicker( "option", "minDate", "<?=$StartDate?>" );
	});
</script>
