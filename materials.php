<?
	include "config.php";
	$title = 'Материалы';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_materials', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$location = $_SERVER['REQUEST_URI'];
	$_SESSION["location"] = $location;

	if( isset($_GET["isex"]) ) {
		$isexist = $_GET["isex"];
	}
	else {
		$isexist = "NULL";
	}

	if( isset($_GET["prod"]) ) {
		$product = $_GET["prod"];
	}
	else {
		$product = 1;
	}

	$MT_ID = isset($_GET["MT_ID"]) ? $_GET["MT_ID"] : array();
	$MT_IDs = implode(",", $MT_ID);

	// Применение статуса материала или смена поставщика
	if( isset($_POST["isex"]) )
	{
		$OrderDate = $_POST["order_date"] ? date( 'Y-m-d', strtotime($_POST["order_date"]) ) : '';
		$ArrivalDate = $_POST["arrival_date"] ? date( 'Y-m-d', strtotime($_POST["arrival_date"]) ) : '';
		$Shipper = $_POST["Shipper"] ? $_POST["Shipper"] : "NULL";
		$ODD_IDs = 0;

		// Собираем идентификаторы изделий
		foreach ($_POST["prod"] as $k => $v) {
			$ODD_IDs .= ",{$v}";
		}

		if( isset($_POST["IsExist"]) ) {
			// Обновляем статус наличия
			$query = "UPDATE OrdersDataDetail
					  SET IsExist = {$_POST["IsExist"]}
						 ,order_date = IF('{$OrderDate}' = '', order_date, '{$OrderDate}')
						 ,arrival_date = IF('{$ArrivalDate}' = '', arrival_date, '{$ArrivalDate}')
						 ,author = {$_SESSION['id']}
					  WHERE ODD_ID IN({$ODD_IDs})";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		if( $_POST["Shipper"] != '' ) {
			// Обновляем поставщика
			$query = "SELECT ODD.ODD_ID, MT.Material
						FROM OrdersDataDetail ODD
						JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						WHERE ODD.ODD_ID IN($ODD_IDs)";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				$query = "INSERT INTO Materials
							SET
								Material = '{$row["Material"]}',
								SH_ID = $Shipper,
								Count = 0
							ON DUPLICATE KEY UPDATE
								Count = Count + 1";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$mt_id = mysqli_insert_id( $mysqli );

				$query = "UPDATE OrdersDataDetail SET MT_ID = $mt_id, author = {$_SESSION['id']} WHERE ODD_ID = {$row["ODD_ID"]}";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$_SERVER['REQUEST_URI'].'">');
		die;
	}

	include "forms.php";
?>
	
	<form method='get' id='MTfilter'>
		<div>
			<label for='isexist'>Наличие:&nbsp;</label>
			<div class='btnset' id='isexist'>
				<input type='radio' id='isex' name='isex' value='NULL' <?= ($isexist =="NULL" ? "checked" : "") ?>>
					<label for='isex'>Неизвестно</label>
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
			</div>
		</div>

		<div>
			<select name="MT_ID[]" multiple style="width: 800px; display: none;">
				<?
				$query = "
					SELECT MT.MT_ID, CONCAT(MT.Material, ' (', SH.Shipper, ')') Material
					FROM Materials MT
					JOIN Shippers SH ON SH.SH_ID = MT.SH_ID AND SH.mtype = {$product}
					JOIN OrdersDataDetail ODD ON ODD.MT_ID = MT.MT_ID AND ODD.Del = 0 AND ODD.IsExist ".( $isexist == "NULL" ? "IS NULL" : "= ".$isexist )."
					JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID AND OD.DelDate IS NULL AND OD.ReadyDate IS NULL
					GROUP BY MT.MT_ID
					ORDER BY MT.Material
				";
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

	<!--Кнопка печати-->
	<div id="print_btn" style="display: none;">
		<a id="toprint" style="display: block;" title="Распечатать бирки"></a>
	</div>

	<!--Копирование материалов в буфер-->
	<div id="copy_link" style="display: none;">
		<a id="copy-button" data-clipboard-target="#materials_name" style="display: block; height: 100%" title="Скопировать список материалов в буфер обмена"></a>
	</div>

<form method='post' id="formdiv" style='position: relative;'>
	<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>
	<table>
		<thead>
		<tr class="nowrap">
			<th></th>
			<th>Материал</th>
			<th>Поставщик</th>
			<?=($product == 1 ? "<th width='50'>Метраж</th>" : "")?>
			<th>Код</th>
			<th>Принят</th>
			<th>Работник</th>
			<th>Заказ</th>
			<th>Цвет</th>
			<th>Заказчик<br>Дата продажи - Дата сдачи<br>Подразделение (№ квитанции)</th>
			<th>Примечание</th>
		</tr>
		</thead>
		<tbody>
<?
//	if( $product > 0 ) {
	$query = "
		SELECT OD.OD_ID
			,OD.Code
			,OD.ClientName
			,OD.ul
			,DATE_FORMAT(OD.StartDate, '%d.%m.%y') StartDate
			,DATE_FORMAT(OD.EndDate, '%d.%m.%y') EndDate
			,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
			,IF((SH.KA_ID IS NULL AND SH.SH_ID IS NOT NULL AND OD.StartDate IS NULL), '<br><b style=\'background-color: silver;\'>Выставка</b>', '') showing
			,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
			,OD.OrderNumber
			,OD.Comment
			,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
			,Color(OD.CL_ID) Color
			,IF(DATEDIFF(OD.EndDate, NOW()) <= 7, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline

			,GROUP_CONCAT(
				IF(OS.locking_date IS NOT NULL AND SH.retail
					,CONCAT('<b style=\'line-height: 1.79em;\'><i id=\'prod', ODD.ODD_ID, '\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' <b style=\'font-size: 1.3em;\'>', ODD.Amount, '</b> ', Zakaz(ODD.ODD_ID), '</i></b><br>')
					,CONCAT('<b style=\'line-height: 1.79em;\'><a id=\'prod', ODD.ODD_ID, '\' location=\'{$location}\' ".(in_array('order_add', $Rights) ? "href=\'#\' class=\'edit_product', IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0), '" : "")."\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' <b style=\'font-size: 1.3em;\'>', ODD.Amount, '</b> ', Zakaz(ODD.ODD_ID), '</a></b><br>')
				)
				ORDER BY IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) DESC, ODD.ODD_ID
				SEPARATOR ''
			) Zakaz

			,GROUP_CONCAT(
				CONCAT('<div class=\'wr_mt\'>', IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span shid=\'', IFNULL(MT.SH_ID, ''), '\' mtid=\'', ODD.MT_ID, '\' class=\'mt', ODD.MT_ID, IF(MT.removed = 1, ' removed', ''), ' material ".(in_array('screen_materials', $Rights) ? " mt_edit " : "")."',
				CASE ODD.IsExist
					WHEN 0 THEN 'bg-red'
					WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%y'), ' Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%y'))
					WHEN 2 THEN 'bg-green'
					ELSE 'bg-gray'
				END,
				'\'>', IFNULL(MT.Material, ''), '</span><input type=\'text\' class=\'materialtags_', IFNULL(SHP.mtype, ''), '\' style=\'display: none;\'><input type=\'checkbox\' style=\'display: none;\' title=\'Выведен\'></div>')
				ORDER BY IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) DESC, ODD.ODD_ID
				SEPARATOR ''
			) Material

			,GROUP_CONCAT(
				CONCAT( '<div>', SHP.Shipper, '</div>' )
				ORDER BY IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) DESC, ODD.ODD_ID
				SEPARATOR ''
			) Shipper

			,GROUP_CONCAT(
				CONCAT( '<input class=\'footage\' type=\'number\' step=\'0.1\' min=\'0\' style=\'width: 50px; height: 19px;\' value=\'', IFNULL(ODD.MT_amount, ''), '\' oddid=\'', ODD.ODD_ID, '\'>' )
				ORDER BY IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) DESC, ODD.ODD_ID
				SEPARATOR ''
			) MT_amount

			,GROUP_CONCAT(
				CONCAT('<input type=\'checkbox\' value=\'', ODD.ODD_ID, '\' name=\'prod[]\' class=\'chbox\'><br>')
				ORDER BY IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) DESC, ODD.ODD_ID
				SEPARATOR ''
			) Checkbox

			,OD.confirmed
			,GROUP_CONCAT(CONCAT('<span class=\'', IF(ODS.IsReady = 1, 'ready', 'inwork'), '\'>', WD.Name, '</span><br>') SEPARATOR '') worker
		FROM OrdersData OD
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
			AND ODD.IsExist ".( $isexist == "NULL" ? "IS NULL" : "= ".$isexist )."
			".( $MT_IDs ? "AND ODD.MT_ID IN ({$MT_IDs})" : "" )."
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		JOIN Shippers SHP ON SHP.SH_ID = MT.SH_ID AND SHP.mtype = {$product}
		LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
										AND ODS.Visible = 1
										AND ODS.Old != 1
										AND ODS.ST_ID IN(SELECT ST_ID FROM StepsTariffs WHERE Short LIKE '%Ст%' OR Short LIKE '%Об%')
		LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
		WHERE OD.DelDate IS NULL AND OD.ReadyDate IS NULL
		GROUP BY OD.OD_ID
		ORDER BY OD.OD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr id='ord{$row["OD_ID"]}'>";
		echo "<td>{$row["Checkbox"]}</td>";
		echo "<td><span class='nowrap'>{$row["Material"]}</span></td>";
		echo "<td><span class='nowrap'>{$row["Shipper"]}</span></td>";
		if( $product == 1 ) echo "<td>{$row["MT_amount"]}</td>";
		echo "<td><a href='orderdetail.php?id={$row["OD_ID"]}' class='nowrap'><b class='code'>{$row["Code"]}</b></a>{$row["showing"]}</td>";
		// Если заказ принят
		if( $row["confirmed"] == 1 ) {
			$class = 'confirmed';
		}
		else {
			$class = 'not_confirmed';
		}
		echo "<td class='{$class}'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";
		echo "<td><span class='nowrap'>{$row["worker"]}</span></td>";
		echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";

		switch ($row["IsPainting"]) {
			case "0":
				echo "<td class='empty'>{$row["Color"]}</td>";
				break;
			case "1":
				echo "<td class='notready'>{$row["Color"]}</td>";
				break;
			case "2":
				echo "<td class='inwork'>{$row["Color"]}</td>";
				break;
			case "3":
				echo "<td class='ready'>{$row["Color"]}</td>";
				break;
			default:
				echo "<td></td>";
				break;
		}
		echo "<td style='background: {$row["CTColor"]};' class='nowrap'>";
		echo "<n".($row["ul"] ? " class='ul' title='юр. лицо'" : "").">{$row["ClientName"]}</n><br>";
		echo "{$row["StartDate"]} - <span class='{$row["Deadline"]}'>{$row["EndDate"]}</span><br>";
		echo "{$row["Shop"]} <b>{$row["OrderNumber"]}</b>";
		echo "</td>";
		echo "<td>{$row["Comment"]}</td>";
		echo "</tr>";
	}
?>
		</tbody>
	</table>

	<!-- Список материалов для буфера обмена -->
	<textarea id='materials_name' style='position: absolute; top: 34px; left: 1px; height: 20px; z-index: -1;'></textarea>

	<p><input type='checkbox' id='selectallbottom'><label for='selectallbottom'>Выбрать все</label></p>
	<p>
		<div class='btnset radiostatus'>
			<input type='radio' id='radio' name='IsExist' value='NULL'>
				<label for='radio'>Неизвестно</label>
			<input type='radio' id='radio0' name='IsExist' value='0'>
				<label for='radio0'>Нет</label>
			<input type='radio' id='radio1' name='IsExist' value='1'>
				<label for='radio1'>Заказано</label>
			<input type='radio' id='radio2' name='IsExist' value='2'>
				<label for='radio2'>В наличии</label>
		</div>
		<div class='order_material' style='display: none;'>
			<span>Заказано:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>Ожидается:</span><br>
			<input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
			&nbsp;&nbsp;-&nbsp;&nbsp;
			<input class='date to' type='text' name='arrival_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y", strtotime("+14 days")) ?>" readonly>
		</div>
	</p>
	<p>
		<label for="Shipper">Поставщик:</label>
		<select id="Shipper" name="Shipper" style="width: 110px;" title="Поставщик">
			<option value=""></option>
			<?
			if( $product > 0 ) {
				$query = "SELECT SH_ID, Shipper FROM Shippers WHERE mtype = {$product} ORDER BY Shipper";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
				}
			}
			else {
				echo "<optgroup label='Ткань'>";
					$query = "SELECT SH_ID, Shipper FROM Shippers WHERE mtype = 1";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
					}
				echo "</optgroup>";
				echo "<optgroup label='Пластик'>";
					$query = "SELECT SH_ID, Shipper FROM Shippers WHERE mtype = 2";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
					}
				echo "</optgroup>";
			}
			?>
		</select>
	</p>
	<input type="hidden" name="isex" value="1">
	<input type='submit' value='Применить'>
</form>

<script>
	$(function(){
		// Расстановка tabindex для метража
		var tabindex = 0;
		$('.footage').each(function() {
			tabindex = tabindex + 1;
			$(this).attr('tabindex', tabindex);
		});

		new Clipboard('#copy-button'); // Копирование материалов в буфер
		$("#copy-button").click(function() {
			noty({timeout: 3000, text: 'Список материалов скопирована в буфер обмена', type: 'success'});
		});

		function selectall(ch)
		{
			$('.chbox').prop('checked', ch);
			$('#selectalltop').prop('checked', ch);
			$('#selectallbottom').prop('checked', ch);
			return false;
		}

		function material_list() {
			var data = $('#formdiv').serialize();
			$("#toprint").attr('href', '/toprint/labels_material.php?' + data);
			$.ajax({ url: "ajax.php?do=material_list&" + data, dataType: "script", async: false });
		}

		// Открытие диалога печати
		$("#toprint").printPage();

		$('#selectalltop').change(function(){
			ch = $('#selectalltop').prop('checked');
			selectall(ch);
			material_list();
			return false;
		});

		$('#selectallbottom').change(function(){
			ch = $('#selectallbottom').prop('checked');
			selectall(ch);
			material_list();
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
			material_list();
			return false;
		});

		$('.footage').on('change', function() {
			var val = $(this).val();
			var oddid = $(this).attr('oddid');
			$.ajax({ url: "ajax.php?do=footage&oddid="+oddid+"&val="+val, dataType: "script", async: false });
			material_list();
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

<?
	include "footer.php";
?>
