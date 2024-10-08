<?php
	include "config.php";
	$title = 'Подготовка этикеток для упаковок';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('print_label_box', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	// Формируем список id выбранных наборов из $_GET
	$id_list = '0';
	foreach ($_GET["order"] as $k => $v) {
		$id_list .= ",{$v}";
	}

	$product_types = "-1";
	if(isset($_GET["Tables"])) $product_types .= ",2";
	if(isset($_GET["Chairs"])) $product_types .= ",1";
	if(isset($_GET["Others"])) $product_types .= ",0";
?>
<style>
	.main {
		margin-bottom: 50px;
	}
	#title {
		width:730px;
		max-width:730px;
		margin: auto;
		position: relative;
	}
	#date {
		width: 100px;
	}
	#wr-date {
		text-align: right;
		position: absolute;
		top: 0;
		right: 0;
	}
	#tab1 {
		table-layout: fixed;
		width: 100%;
	}
	#tab1 td {
		white-space: nowrap;
		padding: 5px 10px;
	}
	#prod, #color, #code {
		width: 100%;
	}
	#mat_label {
		width: 25%;
	}
	#mat {
		width: 75%;
	}
	#amount_label, #amount {
		width: 50%;
	}
</style>

<div class="main">
	<form action="" method="post" id="printlabels">
		<div id="title">
			<h1>Этикетки для упаковок</h1>
			<div id="wr-date">
				<label for="date">Дата изготовления:</label>
				<input type="text" class="date" name="date" id="date" value="<?= date("d.m.Y") ?>" readonly>
			</div>
		</div>
		<table id="tab1">
			<thead>
				<tr>
					<th width="28%">Наименование</th>
					<th width="25%">Материал</th>
					<th width="20%">Цвет</th>
					<th width="20%">Кол-во</th>
					<th width="7%">Код</th>
					<th width="40"></th>
				</tr>
			</thead>
			<tbody>
<!--
				<tr>
					<td>
						<input type="text" name="prod[]" id="prod" value="" autocomplete="off">
					</td>
					<td>
						<input type="text" name="mat_label[]" id="mat_label" value="" autocomplete="off">
						<input type="text" name="mat[]" id="mat" value="" autocomplete="off">
					</td>
					<td>
						<input type="text" name="color[]" id="color" value="" autocomplete="off">
					</td>
					<td>
						<input type="text" name="amount_label[]" id="amount_label" value="" autocomplete="off">
						<input type="text" name="amount[]" id="amount" value="" autocomplete="off">
					</td>
					<td>
						<input type="text" name="code[]" id="code" value="" autocomplete="off">
					</td>
					<td>
						<i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i>
					</td>
				</tr>
-->
			</tbody>
			<tbody>
				<tr>
					<td colspan="6">
						<i class="fa fa-plus-square fa-2x" style="color: green;" onclick="addRow();"></i>
						<span onclick="addRow();"><font><font> Добавить строку</font></font></span>
					</td>
				</tr>
			</tbody>
		</table>
	<br>
	<div align="center">
<!--		<a class="button" id="print">Печать</a>-->
		<input type="submit" value="Печать" class="button" onclick="set_target('html', 'report');">
	</div>
	</form>
</div>

<script>
	function set_target(action, target) {
		//if target is not empty form is submitted into a new window
		var frm = document.getElementById('printlabels');
		frm.action = 'label_tmp.php';
		frm.target = target;
	}

	var d = document;

	function addRow(name, mat_label, mat, color, amount_label, amount, code)
	{

		// Находим нужную таблицу
		var tbody = d.getElementById('tab1').getElementsByTagName('TBODY')[0];

		// Создаем строку таблицы и добавляем ее
		var row = d.createElement("TR");
		tbody.appendChild(row);

		// Создаем ячейки в вышесозданной строке
		// и добавляем тх
		var td1 = d.createElement("TD");
		var td2 = d.createElement("TD");
		var td3 = d.createElement("TD");
		var td4 = d.createElement("TD");
		var td5 = d.createElement("TD");
		var td6 = d.createElement("TD");

		row.appendChild(td1);
		row.appendChild(td2);
		row.appendChild(td3);
		row.appendChild(td4);
		row.appendChild(td5);
		row.appendChild(td6);
		// Наполняем ячейки
		if( typeof name === "undefined" ) {
			name = '';
		}
		if( typeof mat_label === "undefined" ) {
			mat_label = '';
		}
		if( typeof mat === "undefined" ) {
			mat = '';
		}
		if( typeof color === "undefined" ) {
			color = '';
		}
		if( typeof amount_label === "undefined" ) {
			amount_label = '';
		}
		if( typeof amount === "undefined" ) {
			amount = '';
		}
		if( typeof code === "undefined" ) {
			code = '';
		}
		td1.innerHTML = '<input type="text" name="prod[]" id="prod" value="'+name+'" autocomplete="off">';
		td2.innerHTML = '<input type="text" name="mat_label[]" id="mat_label" value="'+mat_label+'" autocomplete="off"><input type="text" name="mat[]" id="mat" value="'+mat+'" autocomplete="off">';
		td3.innerHTML = '<input type="text" name="color[]" id="color" value="'+color+'" autocomplete="off">';
		td4.innerHTML = '<input type="text" name="amount_label[]" id="amount_label" value="'+amount_label+'" autocomplete="off"><input type="text" name="amount[]" id="amount" value="'+amount+'" autocomplete="off">';
		td5.innerHTML = '<input type="text" name="code[]" id="code" value="'+code+'" autocomplete="off">';
		td6.innerHTML = '<i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);">';
	}

	function deleteRow(r)
	{
		var i=r.parentNode.parentNode.rowIndex;
		document.getElementById('tab1').deleteRow(i);
	}

$(document).ready(function() {
<?php
	$query = "
		SELECT Color_print(OD.CL_ID) Color
			,OD.Code
			,ODD.ODD_ID
			,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
			,IF(SHP.mtype = 1, 'Ткань', IF(SHP.mtype = 2, 'Пластик', '')) mat_label
			,IF(PM.PT_ID = 2, 'Упаковка №', 'Кол-во в упаковке') amount_label
			,CONCAT(MT.Material, IFNULL(CONCAT(' (', SHP.Shipper, ')'), '')) Material
			,ODD.Amount
			,IFNULL(PM.InTheBox, 1) InTheBox
			,IF(ODD.PME_ID = 2, 3, IFNULL(PM.BoxOnItem, 0)) BoxOnItem
			,Zakaz(ODD.ODD_ID) Zakaz
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		LEFT JOIN Shippers SHP ON SHP.SH_ID = MT.SH_ID
		WHERE OD.DelDate IS NULL AND OD.OD_ID IN ({$id_list})
		HAVING PTID IN({$product_types})
		ORDER BY OD.OD_ID, PTID DESC, ODD.ODD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$Zakaz = htmlspecialchars($row["Zakaz"]);
		$Material = htmlspecialchars($row["Material"]);
		$Color = htmlspecialchars($row["Color"]);
		$Amount = $row["Amount"];

		if( $row["BoxOnItem"] ) {
			for ($i = 1; $i <= $Amount; $i++) {
				for ($j = 1; $j <= $row["BoxOnItem"]; $j++) {
					echo "addRow('{$Zakaz}', '{$row["mat_label"]}', '{$Material}', '{$Color}', '{$row["amount_label"]}', '{$j} (из {$row["BoxOnItem"]})', '{$row["Code"]}');";
				}
			}
		}
		elseif( $row["InTheBox"] ) {
			$d = $Amount / $row["InTheBox"];
			$b = $Amount % $row["InTheBox"];
			for ($i = 1; $i <= $d; $i++) {
				echo "addRow('{$Zakaz}', '{$row["mat_label"]}', '{$Material}', '{$Color}', '{$row["amount_label"]}', '{$row["InTheBox"]} шт.', '{$row["Code"]}');";
			}
			if( $b ) {
				echo "addRow('{$Zakaz}', '{$row["mat_label"]}', '{$Material}', '{$Color}', '{$row["amount_label"]}', '{$b} шт.', '{$row["Code"]}');";
			}
		}
	}
?>
});
</script>

<?php
	include "footer.php";
?>
