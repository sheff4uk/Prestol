<?
	include "config.php";
	$title = 'Материалы';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_materials', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	if( isset($_GET["isex"]) ) {
		$isexist = $_GET["isex"];
	}
	else {
		$isexist = 0;
	}
	if( isset($_GET["prod"]) ) {
		$product = $_GET["prod"];
	}
	else {
		$product = 1;
	}

	$MT_IDs = implode(",", $_GET["MT_ID"]);
	$MT_IDs = $MT_IDs == "" ? "0" : $MT_IDs;

	// Применение статуса материала
	if( isset($_POST["IsExist"]) )
	{
		foreach( $_POST as $k => $v) 
		{
			$val = $_POST["IsExist"];
			if( strpos($k,"prod") === 0 ) 
			{
				$prodid = (int)str_replace( "prod", "", $k );
				$OrderDate = $_POST["order_date"] ? date( 'Y-m-d', strtotime($_POST["order_date"]) ) : '';
				$ArrivalDate = $_POST["arrival_date"] ? date( 'Y-m-d', strtotime($_POST["arrival_date"]) ) : '';
				if( $product > 0 ) {
					$query = "UPDATE OrdersDataDetail
							  SET IsExist = $val
								 ,order_date = IF('{$OrderDate}' = '', order_date, '{$OrderDate}')
								 ,arrival_date = IF('{$ArrivalDate}' = '', arrival_date, '{$ArrivalDate}')
							  WHERE ODD_ID = {$prodid}";
				}
				else {
					$query = "UPDATE OrdersDataBlank
							  SET IsExist = $val
								 ,order_date = IF('{$OrderDate}' = '', order_date, '{$OrderDate}')
								 ,arrival_date = IF('{$ArrivalDate}' = '', arrival_date, '{$ArrivalDate}')
							  WHERE ODB_ID = {$prodid}";
				}
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
		}
		//header( "Location: ".$_SERVER['REQUEST_URI'] );
		exit ('<meta http-equiv="refresh" content="0; url='.$_SERVER['REQUEST_URI'].'">');
		die;
	}
?>
	
	<form method='get' id='MTfilter'>
		<div>
			<label for='isexist'>Наличие:&nbsp;</label>
			<div class='btnset' id='isexist'>
				<input type='radio' id='isex0' name='isex' value='0' <?= ($isexist =="0" ? "checked" : "") ?>>
					<label for='isex0'>Нет</label>
				<input type='radio' id='isex1' name='isex' value='1' <?= ($isexist =="1" ? "checked" : "") ?>>
					<label for='isex1'>Заказано</label>
				<input type='radio' id='isex2' name='isex' value='2' <?= ($isexist =="2" ? "checked" : "") ?>>
					<label for='isex2'>В наличии</label>
			</div>
		</div>

		<div>
			<label for='material'>Материал:&nbsp;</label>
			<div class='btnset' id='material'>
				<input type='radio' id='prod1' name='prod' value='1' <?= ($product =="1" ? "checked" : "") ?>>
					<label for='prod1'>Ткань</label>
				<input type='radio' id='prod2' name='prod' value='2' <?= ($product =="2" ? "checked" : "") ?>>
					<label for='prod2'>Пластик</label>
				<input type='radio' id='prod0' name='prod' value='0' <?= ($product =="0" ? "checked" : "") ?>>
					<label for='prod0'>Прочее</label>
			</div>
		</div>

		<div>
			<select name="MT_ID[]" multiple style="width: 800px; display: none;">
				<?
				$query = "SELECT MT.MT_ID, MT.Material
							FROM Materials MT
							JOIN (
								SELECT ODD.OD_ID, ODD.MT_ID, ODD.IsExist
								FROM OrdersDataDetail ODD
								UNION
								SELECT ODB.OD_ID, ODB.MT_ID, ODB.IsExist
								FROM OrdersDataBlank ODB
								) ODD_ODB ON ODD_ODB.MT_ID = MT.MT_ID AND ODD_ODB.IsExist = {$isexist}
							LEFT JOIN OrdersData OD ON OD.OD_ID = ODD_ODB.OD_ID
							WHERE MT.PT_ID = {$product} AND OD.ReadyDate IS NULL
							GROUP BY MT.MT_ID
							ORDER BY MT.Material";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = in_array($row["MT_ID"], $_GET["MT_ID"]) ? "selected" : "";
					echo "<option {$selected} value='{$row["MT_ID"]}'>{$row["Material"]}</option>";
				}
				?>
			</select>
		</div>

		<button>Фильтр</button>
	</form>

	<form method='post'>
	<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>
	<table>
		<thead>
		<tr>
			<th></th>
			<th>Материал</th>
			<th>Заказ</th>
			<th>Лакировка</th>
			<th>Цвет</th>
			<th>Заказчик</th>
			<th>Дата приема</th>
			<th>Дата сдачи</th>
			<th>Салон</th>
			<th>№ квитанции</th>
			<th>Примечание</th>
		</tr>
		</thead>
		<tbody>
<?
//	if( $product > 0 ) {
	$query = "SELECT OD.OD_ID
					,OD.ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,OD.OrderNumber
					,OD.Comment
					,OD.IsPainting
					,IFNULL(OD.Color, '<a href=\"/orderdetail.php\">Свободные</a>') Color
					,IF(DATEDIFF(OD.EndDate, NOW()) <= 7, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
					,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
					,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
					,GROUP_CONCAT(ODD_ODB.Checkbox SEPARATOR '') Checkbox
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  RIGHT JOIN (
						  SELECT ODD.OD_ID
								,ODD.ODD_ID ItemID
								,IFNULL(PM.PT_ID, 2) PT_ID

								,CONCAT('<span', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), '</span><br>') Zakaz

								,CONCAT(IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span class=\'',
								CASE ODD.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
								'\'>', IFNULL(MT.Material, ''), '</span><br>') Material

								,CONCAT('<input type=\'checkbox\' value=\'1\' name=\'prod', ODD.ODD_ID, '\' class=\'chbox\'><br>') Checkbox

						  FROM OrdersDataDetail ODD
						  LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						  JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						  WHERE ODD.IsExist = {$isexist} AND IFNULL(PM.PT_ID, 2) = {$product}
						  AND (ODD.MT_ID IN ({$MT_IDs}) OR '{$MT_IDs}' = '0')
						  UNION
						  SELECT ODB.OD_ID
								,ODB.ODB_ID ItemID
								,0 PT_ID

								,CONCAT('<span', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), '</span><br>') Zakaz

								,CONCAT(IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0 AND ODB.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), ''), '<span class=\'',
								CASE ODB.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODB.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
								'\'>', IFNULL(MT.Material, ''), '</span><br>') Material

								,CONCAT('<input type=\'checkbox\' value=\'1\' name=\'prod', ODB.ODB_ID, '\' class=\'chbox\'><br>') Checkbox

						  FROM OrdersDataBlank ODB
						  LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						  JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
						  WHERE ODB.IsExist = {$isexist} AND 0 = {$product}
						  AND (ODB.MT_ID IN ({$MT_IDs}) OR '{$MT_IDs}' = '0')
						  ORDER BY PT_ID DESC, ItemID
						  ) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  WHERE OD.ReadyDate IS NULL
			  GROUP BY OD.OD_ID
			  ORDER BY OD.OD_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr>";
		echo "<td>{$row["Checkbox"]}</td>";
		echo "<td><span class='nowrap'>{$row["Material"]}</span></td>";
		echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
			switch ($row["IsPainting"]) {
				case 1:
					echo "<td class='notready' title='Не в работе'></td>";
					break;
				case 2:
					echo "<td class='inwork' title='В работе'></td>";
					break;
				case 3:
					echo "<td class='ready' title='Готово'></td>";
					break;
				default:
					echo "<td></td>";
					break;
			}
		echo "<td>{$row["Color"]}</td>";
		echo "<td>{$row["ClientName"]}</td>";
		echo "<td>{$row["StartDate"]}</td>";
		echo "<td><span class='{$row["Deadline"]}'>{$row["EndDate"]}</span></td>";
		echo "<td style='background: {$row["CTColor"]};'>{$row["Shop"]}</td>";
		echo "<td>{$row["OrderNumber"]}</td>";
		echo "<td>{$row["Comment"]}</td>";
		echo "</tr>";
	}
?>
		</tbody>
	</table>
	<p><input type='checkbox' id='selectallbottom'><label for='selectallbottom'>Выбрать все</label></p>
	<p>
		<div class='btnset radiostatus'>
			<input type='radio' id='radio0' name='IsExist' value='0'>
				<label for='radio0'>Нет</label>
			<input type='radio' id='radio1' name='IsExist' value='1'>
				<label for='radio1'>Заказано</label>
			<input type='radio' id='radio2' name='IsExist' value='2'>
				<label for='radio2'>В наличии</label>
		</div>
        <div class='order_material' style='display: none;'>
            <span>Дата заказа:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>Ожидается:</span><br>
            <input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
            &nbsp;&nbsp;-&nbsp;&nbsp;
            <input class='date to' type='text' name='arrival_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y", strtotime("+14 days")) ?>" readonly>
        </div>
	</p>
	<input type='submit' value='Применить'>
	</form>

</body>
</html>

<script>
	$(document).ready(function(){
		function selectall(ch)
		{
			$('.chbox').prop('checked', ch);
			$('#selectalltop').prop('checked', ch);
			$('#selectallbottom').prop('checked', ch);
			return false;
		}
			
		$(function() {
			$('#selectalltop').change(function(){
				ch = $('#selectalltop').prop('checked');
				selectall(ch);
				return false;
			});

			$('#selectallbottom').change(function(){
				ch = $('#selectallbottom').prop('checked');
				selectall(ch);
				return false;
			});

			$('.chbox').change(function(){
				var checked_status = true;
				$('.chbox').each(function(){
					if( !$(this).prop('checked') )
					{
						checked_status = $(this).prop('checked');
					}
				});
				$('#selectalltop').prop('checked', checked_status);
				$('#selectallbottom').prop('checked', checked_status);
				return false;
			});
		});

		$('#isexist input, #material input').change(function(){
			$('select[name="MT_ID[]"] option').removeAttr('selected');
			$('#MTfilter').submit();
		});

		$('select[name="MT_ID[]"]').select2({
			placeholder: "Выберите интересующие материалы",
			allowClear: true,
			closeOnSelect: false,
			language: "ru"
		});
	});
</script>
