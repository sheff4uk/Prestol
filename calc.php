<?
	include "config.php";
	$title = 'Калькулятор стоимости стола';
	include "header.php";
	$page = "calc";
	include "forms.php";
	include "order_form.php";

	// Кнопка добавления стола
	echo "<div id='add_btn' class='edit_product2' odid='0' location='calc.php' title='Рассчитать стоимость стола'></div>";
?>
	<h1>Калькулятор стоимости стола</h1>
	<table class="main_table" id="MT_header">
		<thead>
			<tr>
				<th>Стол</th>
				<th>Пластик</th>
				<th width="100">Розница</th>
				<th width="100">Опт</th>
				<th width="100">Рег. опт</th>
				<th width="55">Автор</th>
				<th width="85">Дата<br>Время</th>
				<th width="70">Действие</th>
			</tr>
		</thead>
	</table>
<div class="wr_main_table_body">
	<table class="main_table">
		<thead>
			<tr>
				<th></th>
				<th></th>
				<th width="100"></th>
				<th width="100"></th>
				<th width="100"></th>
				<th width="55"></th>
				<th width="85"></th>
				<th width="70"></th>
			</tr>
		</thead>
		<tbody>
		<?
		$query = "
			SELECT ODD.ODD_ID
				,Zakaz(ODD.ODD_ID) Zakaz
				,IFNULL(MT.Material, '') Material
				,CONCAT(' <b>', SH.Shipper, '</b>') Shipper
				,IF(MT.removed=1, 'removed ', '') removed
				,Price(ODD.ODD_ID, 1) rozn
				,Price(ODD.ODD_ID, 2) opt
				,Price(ODD.ODD_ID, 3) reg
				,USR_Icon(OCL.author) Name
				,Friendly_date(OCL.date_time) friendly_date
				,TIME(OCL.date_time) Time
			FROM OrdersDataDetail ODD
			JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 2
			JOIN OrdersChangeLog OCL ON OCL.table_value = ODD.ODD_ID AND OCL.table_key LIKE 'ODD_ID' AND OCL.field_name LIKE 'Добавлено изделие'
			LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
			LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
			WHERE ODD.OD_ID IS NULL
			ORDER BY ODD.ODD_ID DESC
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		while( $row = mysqli_fetch_array($res) )
		{
			$material = "<span class='{$row["removed"]}'>{$row["Material"]}{$row["Shipper"]}</span>";

			echo "
				<tr>
					<td><b>{$row["Zakaz"]}</b></td>
					<td>{$material}</td>
					<td class='txtright'><p class='price'>{$row["rozn"]}</p></td>
					<td class='txtright'><p class='price'>{$row["opt"]}</p></td>
					<td class='txtright'><p class='price'>{$row["reg"]}</p></td>
					<td>{$row["Name"]}</td>
					<td>{$row["friendly_date"]}<br>{$row["Time"]}</td>
					<td>
						<a href='#' title='Редактировать стол' id='{$row["ODD_ID"]}' odid='0' class='edit_product2' location='calc.php'><i class='fa fa-pencil-alt fa-lg'></i></a>
						<a href='#' title='Новый заказ с этим столом' odd='{$row["ODD_ID"]}' class='add_order'><i class='fas fa-plus-square fa-lg'></i></a>
					</td>
				</tr>
			";
		}
		?>
		</tbody>
	</table>
</div>

<?
	include "footer.php";
?>
