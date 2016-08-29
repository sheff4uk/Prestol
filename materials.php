<?
	include "config.php";

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
				$query = "UPDATE OrdersDataDetail
						  SET IsExist = $val
							 ,order_date = IF('{$OrderDate}' = '', order_date, '{$OrderDate}')
							 ,arrival_date = IF('{$ArrivalDate}' = '', arrival_date, '{$ArrivalDate}')
						  WHERE ODD_ID = {$prodid}";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
		}
		header( "Location: ".$_SERVER['REQUEST_URI'] );
		die;
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

	$title = 'Материалы';
	include "header.php";
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
							JOIN OrdersDataDetail ODD ON ODD.MT_ID = MT.MT_ID AND ODD.IsExist = {$isexist}
							JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID AND OD.ReadyDate IS NULL
							WHERE MT.PT_ID = {$product}
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
	$query = "SELECT OD.OD_ID
					,OD.ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(OD.EndDate, '%d.%m.%Y') EndDate
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,OD.OrderNumber
					,OD.Comment
					,COUNT(ODD.ODD_ID) Child

					,GROUP_CONCAT(CONCAT('<span', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, '***'), ' ', IFNULL(CONCAT(ODD.Length, 'х', ODD.Width, IFNULL(CONCAT('/', ODD.PieceAmount, 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', '</span><br>') ORDER BY IFNULL(PM.PT_ID, 2) DESC, ODD.ODD_ID SEPARATOR '') Zakaz

					,OD.IsPainting
					,IFNULL(OD.Color, '<a href=\"/orderdetail.php\">Свободные</a>') Color

					,GROUP_CONCAT(CONCAT(IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span class=\'',
						CASE ODD.IsExist
							WHEN 0 THEN 'bg-red'
							WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
							WHEN 2 THEN 'bg-green'
						END,
					'\'>', IFNULL(MT.Material, ''), '</span><br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Material

					,ODD.MT_ID

					,GROUP_CONCAT(CONCAT('<input type=\'checkbox\' value=\'1\' name=\'prod', ODD.ODD_ID, '\' class=\'chbox\'><br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Checkbox
					,IF(DATEDIFF(OD.EndDate, NOW()) <= 7, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
			  FROM OrdersData OD
			  RIGHT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID IN ({$product})
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
			  WHERE ODD.IsExist IN ({$isexist})";
	if( $MT_IDs ) $query .= " AND ODD.MT_ID IN ({$MT_IDs})";
	$query .= " AND OD.ReadyDate IS NULL
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
            <input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>">
            &nbsp;&nbsp;-&nbsp;&nbsp;
            <input class='date to' type='text' name='arrival_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y", strtotime("+14 days")) ?>">
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
