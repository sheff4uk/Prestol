<?
	include "config.php";

	// Сохранение статуса упаковки
	if( isset($_POST["ODD_ID"]) ) {
		$packer = $_POST["packer"] ? $_POST["packer"] : "NULL";
		$boxes = ($_POST["boxes"] ? $_POST["boxes"] : ($_POST["packer"] ? 0 : "NULL"));
		$query = "
			UPDATE OrdersDataDetail
			SET packer = {$packer}
				,boxes = {$boxes}
			WHERE ODD_ID = {$_POST["ODD_ID"]}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url=?ct_id='.$_POST["CT_ID"].'#'.$_POST["ODD_ID"].'">');
		die;
	}

	$_GET["ct_id"] = ($_GET["ct_id"] > 0 ? $_GET["ct_id"] : 0);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<link rel='stylesheet' type='text/css' href='css/style.css'>
	<link rel="stylesheet" type='text/css' href="js/ui/jquery-ui.css">
	<script src="js/jquery-1.11.3.min.js"></script>
	<script src="js/ui/jquery-ui.js"></script>
	<script src="js/script.js"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/i18n/ru.js" type="text/javascript"></script>
	<script src="/js/jquery.ui.totop.js"></script>

	<script>
		$(document).ready(function(){
			$( 'input[type=submit], input[type=button], .button, button' ).button();

			// Плавная прокрутка к якорю при загрузке страницы
			var loc = window.location.hash.replace("#","");
			if (loc == "") {loc = "main"}
			var nav = $("#"+loc);
			if (nav.length) {
				var destination = nav.offset().top - 200;
				$("body:not(:animated)").animate({ scrollTop: destination }, 200);
				$("html").animate({ scrollTop: destination }, 200);
			}
		});
	</script>

	<title>УПАКОВКА</title>
</head>
<body>
	<?
	// Кнопки регионов
	$query = "
		SELECT 'СВОБОДНЫЕ' City, 0 CT_ID
		UNION
		SELECT CT.City, CT.CT_ID
		FROM OrdersData OD
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		WHERE DelDate IS NULL AND ReadyDate IS NULL
		GROUP BY SH.CT_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	echo "<div style='position: fixed; top: 0px; left: 0px; background: rgba(0,0,0,0.2); box-shadow: 0 5px 5px rgba(0,0,0,0.2); width: 100%;'>";
	while( $row = mysqli_fetch_array($res) ) {
		echo "<a href='?ct_id={$row["CT_ID"]}' class='button' style='font-size: 1.3em; ".($_GET["ct_id"] == $row["CT_ID"] ? 'border: 1px solid #fbd850; color: #eb8f00;' : '')."'>{$row["City"]}</a>";
	}
	echo "</div>";
	?>
	<table>
		<thead>
			<tr class="thead">
				<th>Код<br>Сдача</th>
				<th>Кол-во</th>
				<th>Набор</th>
				<th>Упаковал</th>
				<th>Мест</th>
			</tr>
		</thead>
		<tbody>
	<?
	$query = "
		SELECT OD.OD_ID
			,ODD.ODD_ID
			,Zakaz(ODD.ODD_ID) Zakaz
			,CONCAT('<b>', ODD.Amount, '</b>') Amount
			,WD.Name
			,ODD.packer
			,ODD.boxes
			,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		LEFT JOIN WorkersData WD ON WD.WD_ID = ODD.packer
		WHERE OD.DelDate IS NULL AND OD.ReadyDate IS NULL
		".($_GET["ct_id"] > 0 ? "AND OD.SH_ID IN (SELECT SH_ID FROM Shops WHERE CT_ID = {$_GET["ct_id"]})" : "AND OD.SH_ID IS NULL")."
		ORDER BY IFNULL(OD.EndDate, '9999-01-01'), CAST(SUBSTRING_INDEX(OD.Code, '-', 1) AS UNSIGNED) ASC, CAST(SUBSTRING_INDEX(OD.Code, '-', -1) AS UNSIGNED) ASC, OD.OD_ID, PTID DESC, ODD.ODD_ID
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	// Меняем на русскую локаль
	$query = "SET @@lc_time_names='ru_RU';";
	mysqli_query( $mysqli, $query );

	// Получаем количество изделий в наборе для группировки ячеек
	$query = "
		SELECT SUM(1) Cnt
			,OD.Code
			,DATE_FORMAT(OD.EndDate, '%e %b') EndDate
			,IF(DATEDIFF(OD.EndDate, NOW()) <= 7 AND OD.ReadyDate IS NULL AND OD.DelDate IS NULL, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		WHERE OD.DelDate IS NULL AND OD.ReadyDate IS NULL
		".($_GET["ct_id"] > 0 ? "AND OD.SH_ID IN (SELECT SH_ID FROM Shops WHERE CT_ID = {$_GET["ct_id"]})" : "AND OD.SH_ID IS NULL")."
		GROUP BY OD.OD_ID
		HAVING Cnt > 0
		ORDER BY IFNULL(OD.EndDate, '9999-01-01'), CAST(SUBSTRING_INDEX(OD.Code, '-', 1) AS UNSIGNED) ASC, CAST(SUBSTRING_INDEX(OD.Code, '-', -1) AS UNSIGNED) ASC, OD.OD_ID
	";

	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$odid = 0;
	while( $row = mysqli_fetch_array($res) ) {
		if( $odid != $row["OD_ID"] ) {
			$subrow = mysqli_fetch_array($subres);
			$cnt = $subrow["Cnt"];
			$odid = $row["OD_ID"];
			$span = 1;
		}
		else {
			$span = 0;
		}

		echo "<tr>";
		if($span) echo "<td style='font-size: 20px;' rowspan='{$cnt}' class='nowrap'><b class='code'>{$subrow["Code"]}</b><br><span class='{$subrow["Deadline"]}'>{$subrow["EndDate"]}</span></td>";
		echo "<td style='font-size: 20px; text-align: center;'>{$row["Amount"]}</td>";
		echo "<td id='{$row["ODD_ID"]}' packer='{$row["packer"]}' boxes='{$row["boxes"]}' style='font-size: 16px; cursor: pointer; color: #1c94c4;' class='packer_link'>{$row["Zakaz"]}</td>";
		echo "<td style='font-size: 16px;'>{$row["Name"]}</td>";
		echo "<td style='font-size: 20px; text-align: center; ".(($row["boxes"] and !$row["packer"]) ? " color: red;" : "")."'><b>{$row["boxes"]}</b></td>";
		echo "</tr>";
	}
	?>
		</tbody>
	</table>

	<!-- Форма статуса упаковки -->
	<div id='form_packer' style='display:none'>
		<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
	this.subbut.value='Подождите, пожалуйста!';">
			<fieldset>
				<input type="hidden" name="ODD_ID">
				<input type="hidden" name="CT_ID" value="<?=$_GET["ct_id"]?>">
				<?
				// Формирование дропдауна со списком рабочих. Сортировка по релевантности.
				$selectworker = $ready_date ? "" : "<option value='' selected>-=Работник не выбран=-</option>";
				$query = "
					SELECT WD.WD_ID
						,WD.Name
						,SUM(IFNULL(ODD.boxes, 0)) CNT
					FROM WorkersData WD
					LEFT JOIN (
						SELECT packer, boxes
						FROM OrdersDataDetail
						WHERE packer IS NOT NULL
						ORDER BY ODD_ID DESC
						LIMIT 100
					) ODD ON ODD.packer = WD.WD_ID
					WHERE WD.Type = 2 and WD.IsActive = 1
					GROUP BY WD.WD_ID
					ORDER BY CNT DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
				while( $subrow = mysqli_fetch_array($res) )
				{
					$selected = ( $row["WD_ID"] == $subrow["WD_ID"] ) ? "selected" : "";
					$selectworker .= "<option {$selected} value='{$subrow["WD_ID"]}'>{$subrow["Name"]}</option>";
				}
				$selectworker .= "<optgroup label='Уволенные'>";
				$query = "
					SELECT WD.WD_ID
						,WD.Name
					FROM WorkersData WD
					WHERE WD.Type = 2 AND WD.IsActive = 0
					ORDER BY WD.Name
				";
				$res = mysqli_query( $mysqli, $query ) or die("noty({text: 'Invalid query: ".str_replace("\n", "", addslashes(htmlspecialchars(mysqli_error( $mysqli ))))."', type: 'error'});");
				while( $subrow = mysqli_fetch_array($res) )
				{
					$selected = ( $row["WD_ID"] == $subrow["WD_ID"] ) ? "selected" : "";
					$selectworker .= "<option {$selected} value=\'{$subrow["WD_ID"]}\'>{$subrow["Name"]}</option>";
				}
				$selectworker .= "</optgroup>";
				// Конец дропдауна со списком рабочих
				?>
				<label>
					<b>Упаковал:</b>
					<select size= "10" style="font-size: 1.5em; width: 100%;" name="packer"><?=$selectworker?></select>
				</label>
				<br>
				<br>
				<label>
					<b>Число мест:</b>
					<input type="number" style="font-size: 2em; text-align: center;" min="0" name="boxes">
				</label>
			</fieldset>
			<div>
				<hr>
				<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
			</div>
		</form>
	</div>
	<!-- Конец формы статуса упаковки -->

	<script>
		$(function() {
			// Ссылка изменения статуса упаковки
			$('.packer_link').click( function() {
				var ODD_ID = $(this).attr('id');
				var packer = $(this).attr('packer');
				var boxes = $(this).attr('boxes');

				// Заполнение формы
				$('#form_packer input[name="ODD_ID"]').val(ODD_ID);
				$('#form_packer select[name="packer"]').val(packer);
				$('#form_packer input[name="boxes"]').val(boxes);

				$('#form_packer').dialog({
					resizable: false,
					width: 400,
					modal: true,
					closeText: 'Закрыть'
				});
			});
		});
	</script>

</body>
</html>
