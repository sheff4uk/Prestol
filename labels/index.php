<!DOCTYPE html>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Подготовка этикеток</title>
	<link rel="stylesheet" type='text/css' href="../js/ui/jquery-ui.css?v=1">
	<link rel='stylesheet' type='text/css' href='../css/style.css?v=79'>
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v6.2.0/css/all.css">

	<link rel='stylesheet' type='text/css' href='../css/buttons.css'>
	<link rel='stylesheet' type='text/css' href='../css/animate.css'>
	<link rel='stylesheet' type='text/css' href='../plugins/jReject-master/css/jquery.reject.css'>
	<link rel='stylesheet' type='text/css' href='../css/loading.css'>
	<link rel='stylesheet' type='text/css' href='../js/timepicker/jquery-ui-timepicker-addon.css'>
	<script src="../js/jquery-1.11.3.min.js"></script>
	<script src="../js/ui/jquery-ui.js"></script>
	<script src="../js/modal.js?v=11"></script>
	<!-- <script src="../js/script.js?v=57" type="text/javascript"></script> -->
	<script src="../js/jquery.printPage.js" type="text/javascript"></script>
	<script src="../js/jquery.columnhover.js" type="text/javascript"></script>
	<script src="../js/noty/packaged/jquery.noty.packaged.min.js" type="text/javascript"></script>
	<script src="../plugins/jReject-master/js/jquery.reject.js" type="text/javascript"></script>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.4.0/clipboard.min.js"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/i18n/ru.js" type="text/javascript"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>

</head>
<body>

<?php
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
	#prod, #color, #code, #mat {
		width: 100%;
	}
</style>

<div class="main">
	<form action="" method="post" id="printlabels">
		<div id="title">
			<h1>Этикетки для упаковок</h1>
			<div id="wr-date">
				<label for="date">Дата изготовления:</label>
				<input name="date" type="date" value="<?= date("Y-m-d") ?>">
			</div>
		</div>
		<table id="tab1">
			<thead>
				<tr>
					<th width="40%">Изделие</th>
					<th width="25%">Пластик</th>
					<th width="25%">Цвет</th>
					<th width="10%">Код</th>
					<th width="40"></th>
				</tr>
			</thead>
			<tbody>
			</tbody>
			<tbody>
				<tr>
					<td colspan="5">
						<i class="fa fa-plus-square fa-2x" style="color: green;" onclick="addRow();"></i>
						<span onclick="addRow();"><font>Добавить строку</font></span>
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
		frm.action = 'label.php';
		frm.target = target;
	}

	var d = document;

	function addRow()
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

		row.appendChild(td1);
		row.appendChild(td2);
		row.appendChild(td3);
		row.appendChild(td4);
		row.appendChild(td5);
        
		td1.innerHTML = '<select name="prod[]" id="prod"><option value=""></option><option value="Стол &quot;Марио&quot; • 1100х700 • нераздвижной">Стол &quot;Марио&quot; • 1100х700 • нераздвижной</option><option value="Стол &quot;Марио&quot; • 1200(+420)х800 • раздвижной">Стол &quot;Марио&quot; • 1200(+420)х800 • раздвижной</option></select>';
		td2.innerHTML = '<input type="text" name="mat[]" id="mat" value="" autocomplete="off">';
		td3.innerHTML = '<input type="text" name="color[]" id="color" value="" autocomplete="off">';
		td4.innerHTML = '<input type="text" name="code[]" id="code" value="" autocomplete="off">';
		td5.innerHTML = '<i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);">';
	}

	function deleteRow(r)
	{
		var i=r.parentNode.parentNode.rowIndex;
		document.getElementById('tab1').deleteRow(i);
	}

$(document).ready(function() {
    addRow();
});
</script>

</body>
</html>