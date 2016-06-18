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

	$isexist = $_GET["isex"];
	$product = $_GET["prod"];

	$title = 'Ткань/пластик';
	include "header.php";
	include "autocomplete.php"; //JavaScript
?>
	
	<form method='get' style='display: flex;'>
		<label for='isexist'>Наличие:</label>
		<div class='btnset' id='isexist'>
			<input type='radio' id='isex0' name='isex' value='0' <?= ($_GET["isex"] =="0" ? "checked" : "") ?> onchange="this.form.submit()">
				<label for='isex0'>Нет</label>
			<input type='radio' id='isex1' name='isex' value='1' <?= ($_GET["isex"] =="1" ? "checked" : "") ?> onchange="this.form.submit()">
				<label for='isex1'>Заказано</label>
			<input type='radio' id='isex2' name='isex' value='2' <?= ($_GET["isex"] =="2" ? "checked" : "") ?> onchange="this.form.submit()">
				<label for='isex2'>В наличии</label>
		</div>

		<div class='spase'></div>

		<label for='material'>Материал:</label>
		<div class='btnset' id='material'>
			<input type='radio' id='prod1' name='prod' value='1' <?= ($_GET["prod"] =="1" ? "checked" : "") ?> onchange="this.form.submit()">
				<label for='prod1'>Ткань</label>
			<input type='radio' id='prod2' name='prod' value='2' <?= ($_GET["prod"] =="2" ? "checked" : "") ?> onchange="this.form.submit()">
				<label for='prod2'>Пластик</label>
		</div>
		<div class='spase'></div>
		Название:
		<input type="text" name="material" class="textileplastictags" value="<?=$_GET["material"]?>" style="height: 18px;" autocomplete="off">
		<div class='spase'></div>
		<input type='submit' value='Фильтр'>
	</form>

	<form method='post'>
	<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>
	<table>
		<thead>
		<tr>
			<th></th>
			<th>Ткань/пластик</th>
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
					,CONCAT(CT.City, '/', SH.Shop) AS Shop
					,OD.OrderNumber
					,OD.Comment
					,COUNT(ODD.ODD_ID) Child
					,GROUP_CONCAT(CONCAT_WS(' ', ODD.Amount, PM.Model, IFNULL(PF.Form, ''), IFNULL(PME.Mechanism, ''), IFNULL(CONCAT(ODD.Length, 'х', ODD.Width), ''), '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Zakaz
					,OD.IsPainting
					,IFNULL(OD.Color, '<a href=\"/orderdetail.php\">Свободные</a>') Color
					,GROUP_CONCAT(CONCAT(ODD.Color, '<br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Color_archive
					,GROUP_CONCAT(CONCAT(IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span class=\'',
						CASE ODD.IsExist
							WHEN 0 THEN 'bg-red'
							WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
							WHEN 2 THEN 'bg-green'
						END,
					'\'>', IFNULL(ODD.Material, ''), '</span><br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Material
					,GROUP_CONCAT(CONCAT('<input type=\'checkbox\' value=\'1\' name=\'prod', ODD.ODD_ID, '\' class=\'chbox\'><br>') ORDER BY PM.PT_ID DESC, ODD.ODD_ID SEPARATOR '') Checkbox
					,IF(DATEDIFF(OD.EndDate, NOW()) <= 7, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
			  FROM OrdersData OD
			  RIGHT JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID IN ({$product})
			  LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
			  LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
			  WHERE IFNULL(ODD.Material, '') <> ''
			  	AND ODD.IsExist IN ({$isexist})
				AND ODD.Material LIKE '%{$_GET["material"]}%'
				AND OD.ReadyDate IS NULL
			  GROUP BY OD.OD_ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr>";
		echo "<td>{$row["Checkbox"]}</td>";
		echo "<td><span class='nowrap'>{$row["Material"]}</span></td>";
		echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		echo "<td>";
			switch ($row["IsPainting"]) {
				case 1:
					echo "<i class='fa fa-star-o fa-lg' title='Не в работе'></i>";
					break;
				case 2:
					echo "<i class='fa fa-star-half-o fa-lg' title='В работе'></i>";
					break;
				case 3:
					echo "<i class='fa fa-star fa-lg' title='Готово'></i>";
					break;
			}
		echo "</td>";
		echo "<td>{$row["Color"]}</td>";
		echo "<td>{$row["ClientName"]}</td>";
		echo "<td>{$row["StartDate"]}</td>";
		echo "<td><span class='{$row["Deadline"]}'>{$row["EndDate"]}</span></td>";
		echo "<td>{$row["Shop"]}</td>";
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
	});
</script>
