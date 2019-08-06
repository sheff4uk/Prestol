<?
	include "config.php";
	$title = 'Ткани';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_materials', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$location = $_SERVER['REQUEST_URI'];

	if( isset($_GET["isex"]) ) {
		$isexist = $_GET["isex"];
	}
	else {
		$isexist = "NULL";
	}

	$product = 1;

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
				$query = "
					SELECT MT_ID FROM Materials WHERE Material LIKE '{$row["Material"]}' AND SH_ID = {$Shipper}
				";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$subrow = mysqli_fetch_array($subres);
				if ($subrow["MT_ID"]) {
					$mt_id = $subrow["MT_ID"];
				}
				else {
					$query = "
						INSERT INTO Materials SET Material = '{$row["Material"]}', SH_ID = {$Shipper}
					";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$mt_id = mysqli_insert_id( $mysqli );
				}

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
			<select name="MT_ID[]" multiple style="width: 800px;">
				<?
				$query = "
					SELECT SHP.SH_ID, SHP.Shipper
					FROM Shippers SHP
					WHERE SHP.mtype = {$product}
					ORDER BY SHP.Shipper
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<optgroup label='{$row["Shipper"]}'>";

					$query = "
						SELECT MT.MT_ID, MT.Material
						FROM Materials MT
						JOIN OrdersDataDetail ODD ON ODD.MT_ID = MT.MT_ID AND ODD.IsExist ".( $isexist == "NULL" ? "IS NULL" : "= ".$isexist )."
						JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID AND OD.DelDate IS NULL AND OD.ReadyDate IS NULL
						WHERE MT.SH_ID = {$row["SH_ID"]}
						GROUP BY MT.MT_ID
						ORDER BY MT.Material
					";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $subrow = mysqli_fetch_array($subres) ) {
						$selected = in_array($subrow["MT_ID"], $_GET["MT_ID"]) ? "selected" : "";
						echo "<option {$selected} value='{$subrow["MT_ID"]}'>{$subrow["Material"]} ({$row["Shipper"]})</option>";
					}

					echo "</optgroup>";
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

<form method='post' id="formdiv" style='position: relative;' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
	<p><input type='checkbox' id='selectalltop'><label for='selectalltop'>Выбрать все</label></p>
	<table class="main_table">
		<thead>
		<tr class="nowrap">
			<th width="30"></th>
			<th width="20%">Материал</th>
			<th width="60">Метраж</th>
			<th width="60">Код</th>
			<th width="40">Принят</th>
			<th width="70">Работник</th>
			<th width="40%">Набор</th>
			<th width="20%">Цвет</th>
			<th width="130">Клиент<br>Продажа - Сдача<br>Подразделение</th>
			<th width="20%">Примечание</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "
		SELECT OD.OD_ID
			,OD.Code
			,OD.ClientName
			,OD.ul
			,DATE_FORMAT(OD.StartDate, '%d.%m.%y') StartDate
			,DATE_FORMAT(OD.EndDate, '%d.%m.%y') EndDate
			,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
			,IFNULL(SH.retail, 0) retail
			,IF((SH.KA_ID IS NULL AND SH.SH_ID IS NOT NULL AND OD.StartDate IS NULL), '<br><b style=\'background-color: silver;\'>Выставка</b>', '') showing
			,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
			,OD.OrderNumber
			,OD.Comment
			,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
			,Color(OD.CL_ID) Color
			,IF(DATEDIFF(OD.EndDate, NOW()) <= 7, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
			,OD.confirmed
		FROM OrdersData OD
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
			AND ODD.IsExist ".( $isexist == "NULL" ? "IS NULL" : "= ".$isexist )."
			".( $MT_IDs ? "AND ODD.MT_ID IN ({$MT_IDs})" : "" )."
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
		JOIN Shippers SHP ON SHP.SH_ID = MT.SH_ID AND SHP.mtype = {$product}
		WHERE OD.DelDate IS NULL AND OD.ReadyDate IS NULL
		GROUP BY OD.OD_ID
		ORDER BY OD.OD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		// Получаем содержимое набора
		$query = "
			SELECT ODD.ODD_ID
				,ODD.Amount
				,Zakaz(ODD.ODD_ID) zakaz
				,ODD.Comment
				,DATEDIFF(ODD.arrival_date, NOW()) outdate
				,ODD.IsExist
				,Friendly_date(ODD.order_date) order_date
				,Friendly_date(ODD.arrival_date) arrival_date
				,IFNULL(MT.Material, '') Material
				,CONCAT(' <b>', SH.Shipper, '</b>') Shipper
				,ODD.MT_ID
				,IFNULL(ODD.MT_amount, '') MT_amount
				,MT.SH_ID
				,SH.mtype
				,IF(MT.removed=1, 'removed', '') removed
				,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
				,ODS.IsReady
				,WD.Name
			FROM OrdersDataDetail ODD
			LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
			JOIN Shippers SH ON SH.SH_ID = MT.SH_ID AND SH.mtype = {$product}
			LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
							AND ODS.Visible = 1
							AND ODS.Old != 1
							AND (ODS.ST_ID IN(SELECT ST_ID FROM StepsTariffs WHERE Short LIKE 'Ст%' OR Short LIKE '%Об%') OR ODS.ST_ID IS NULL)
			LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
			WHERE ODD.OD_ID = {$row["OD_ID"]}
				AND ODD.IsExist ".( $isexist == "NULL" ? "IS NULL" : "= ".$isexist )."
				".( $MT_IDs ? "AND ODD.MT_ID IN ({$MT_IDs})" : "" )."
			ORDER BY PTID DESC, ODD.ODD_ID
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Формируем подробности набора
		$zakaz = '';
		$material = '';
		$color = '';
		$MT_amount = '';
		$checkbox = '';
		$worker = '';
		while( $subrow = mysqli_fetch_array($subres) ) {
			// Если есть примечание
			if ($subrow["Comment"]) {
				$zakaz .= "<b class='material'><a id='prod{$subrow["ODD_ID"]}' location='{$location}' href='#' class='{$subrow["PMfilter"]} ".((!$disabled and $row["PFI_ID"] == "" and in_array('order_add', $Rights)) ? "edit_product{$subrow["PTID"]}" : "not_edit_product")."' title='{$subrow["Comment"]}'><i class='fa fa-comment'></i> <b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
			}
			else {
				$zakaz .= "<b class='material'><a id='prod{$subrow["ODD_ID"]}' location='{$location}' href='#' class='{$subrow["PMfilter"]} ".((!$disabled and $row["PFI_ID"] == "" and in_array('order_add', $Rights)) ? "edit_product{$subrow["PTID"]}" : "not_edit_product")."'><b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
			}

			if ($subrow["IsExist"] == "0") {
				$color = "bg-red";
			}
			elseif ($subrow["IsExist"] == "1") {
				$color = "bg-yellow' html='Заказано:&nbsp;&nbsp;&nbsp;&nbsp;<b>{$subrow["order_date"]}</b><br>Ожидается:&nbsp;<b>{$subrow["arrival_date"]}</b>";
			}
			elseif ($subrow["IsExist"] == "2") {
				$color = "bg-green";
			}
			else {
				$color = "bg-gray";
			}
			$material .= "<span class='wr_mt'>".(($subrow["outdate"] <= 0 and $subrow["IsExist"] == 1) ? "<i class='fas fa-exclamation-triangle' style='color: #E74C3C;' title='{$subrow["outdate"]} дн.'></i>" : "")."<span shid='{$subrow["SH_ID"]}' mtid='{$subrow["MT_ID"]}' id='m{$subrow["ODD_ID"]}' class='mt{$subrow["MT_ID"]} {$subrow["removed"]} {$subrow["MTfilter"]} material ".(in_array('screen_materials', $Rights) ? "mt_edit" : "")." {$color}'>{$subrow["Material"]}{$subrow["Shipper"]}</span><input type='text' value='{$subrow["Material"]}' class='materialtags_{$subrow["mtype"]}' style='display: none;'><input type='checkbox' ".($subrow["removed"] ? "checked" : "")." style='display: none;' title='Выведен'></span><br>";

			$MT_amount .= "<input class='footage' type='number' step='0.1' min='0' style='width: 50px; height: 19px;' value='{$subrow["MT_amount"]}' oddid='{$subrow["ODD_ID"]}'>";

			$checkbox .= "<input type='checkbox' value='{$subrow["ODD_ID"]}' name='prod[]' class='chbox'><br>";

			$worker .= "<span class='".(($subrow["IsReady"] == 1) ? "ready" : "inwork")."'>{$subrow["Name"]}</span><br>";
		}

		echo "<tr id='ord{$row["OD_ID"]}'>";
		echo "<td>{$checkbox}</td>";
		echo "<td><span class='nowrap'>{$material}</span></td>";
		echo "<td>{$MT_amount}</td>";
		echo "<td><a href='orderdetail.php?id={$row["OD_ID"]}' class='nowrap'><b class='code'>{$row["Code"]}</b></a>{$row["showing"]}</td>";
		// Если набор принят
		if( $row["confirmed"] == 1 ) {
			$class = 'confirmed';
		}
		else {
			$class = 'not_confirmed';
		}
		echo "<td class='{$class}'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";
		echo "<td><span class='nowrap'>{$worker}</span></td>";
		echo "<td><span class='nowrap'>{$zakaz}</span></td>";

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
		echo "<span>";
		echo "<n".($row["ul"] ? " class='ul' title='юр. лицо'" : "").">{$row["ClientName"]}</n><br>";
		echo "{$row["StartDate"]} - <span class='{$row["Deadline"]}'>{$row["EndDate"]}</span><br>";
		echo ($row["retail"] ? "&bull; " : "")."{$row["Shop"]}";
		echo "</span>";
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
				$query = "SELECT SH_ID, Shipper FROM Shippers WHERE mtype = {$product} ORDER BY Shipper";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
				}
			?>
		</select>
	</p>
	<input type="hidden" name="isex" value="1">
	<input type='submit' name="subbut" value='Применить'>
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
			scrollAfterSelect: false,
			language: "ru"
		});
	});
</script>

<?
	include "footer.php";
?>
