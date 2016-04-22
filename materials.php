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
				mysql_query( $query ) or die("Invalid query: " . mysql_error());
			}
		}
		header( "Location: ".$_SERVER['REQUEST_URI'] );
		die;
	}

	$isexist = "";
	$product = "";
	if ( $_GET["isex0"] )
	{
		$isexist .= ", 0";
		$ch0 = "checked";
	}
	if ( $_GET["isex1"] )
	{
		$isexist .= ", 1";
		$ch1 = "checked";
	}
	if ( $_GET["isex2"] )
	{
		$isexist .= ", 2";
		$ch2 = "checked";
	}
	if ( $_GET["prod1"] )
	{
		$product .= ", 1";
		$ch3 = "checked";
	}
	if ( $_GET["prod2"] )
	{
		$product .= ", 2";
		$ch4 = "checked";
	}
	$isexist = substr($isexist, 2);
	$product = substr($product, 2);

	$title = 'Ткань/пластик';
	include "header.php";
	include "autocomplete.php"; //JavaScript
?>
	
	<form method='get' style='display: flex;'>
		Наличие:
		<div class='btnset'>
			<input type='checkbox' id='chbox0' name='isex0' value='1' <?=$ch0?>>
				<label for='chbox0'>Нет</label>
			<input type='checkbox' id='chbox1' name='isex1' value='1' <?=$ch1?>>
				<label for='chbox1'>Заказано</label>
			<input type='checkbox' id='chbox2' name='isex2' value='1' <?=$ch2?>>
				<label for='chbox2'>В наличии</label>
		</div>
		<div class='spase'></div>
		Материал:
		<div class='btnset'>
			<input type='checkbox' id='chbox3' name='prod1' value='1' <?=$ch3?>>
				<label for='chbox3'>Ткань</label>
			<input type='checkbox' id='chbox4' name='prod2' value='1' <?=$ch4?>>
				<label for='chbox4'>Пластик</label>
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
					,GROUP_CONCAT(CONCAT(IF(PM.PT_ID = 1 AND DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span class=\'',
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
			  GROUP BY OD.OD_ID";
	$res = mysql_query( $query ) or die("Invalid query: " . mysql_error());
	while( $row = mysql_fetch_array($res) )
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
	<script>
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
	</script>
</html>
