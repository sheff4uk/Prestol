<?
	include "config.php";
	$title = 'Кромки ПВХ';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_materials', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$location = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

	// Добавление в журнал
	if( isset($_POST["PVCedge"]) )
	{
		$ord = $_POST["subbut"] == "Заказать" ? 1 : 0;
		$comment = convert_str($_POST["comment"]);
		$comment = mysqli_real_escape_string( $mysqli,$comment );

		$query = "
			INSERT INTO PVClog(PVC_ID, size, amount, comment, ord, author)
			VALUES ({$_POST["PVCedge"]}, {$_POST["size"]}, {$_POST["amount"]}, '{$comment}', {$ord}, {$_SESSION["id"]})
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Добавлени/редактирование кромки
	if ( isset($_POST["edge"]) ) {
		$edge = convert_str($_POST["edge"]);
		$edge = mysqli_real_escape_string( $mysqli, $edge );
		if ($_POST["PVC_ID"]) {
			$PVC_ID = $_POST["PVC_ID"];
			$query = "
				UPDATE PVCedge SET edge = '{$edge}' WHERE PVC_ID = {$PVC_ID}
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		else {
			$query = "
				INSERT INTO PVCedge SET edge = '{$edge}'
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$PVC_ID = mysqli_insert_id( $mysqli );
		}
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#pvc'.$PVC_ID.'">');
		die;
	}

	// Приходование заказанной кромки
	if( isset($_GET["incoming"]) )
	{
		$query = "
			UPDATE PVClog SET ord = 0 WHERE PVCL_ID = {$_GET["incoming"]}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Отмена заказа кромки
	if( isset($_GET["ord_del"]) )
	{
		$query = "
			DELETE FROM PVClog WHERE PVCL_ID = {$_GET["ord_del"]} AND ord = 1
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

?>
<style>
	.pvc_label {
		position: relative;
	}
	.pvc_label a {
		position: absolute;
		left: 100px;
		top: 15px;
		opacity: 0;
	}
	.pvc_label:hover a {
		opacity: 1;
	}
</style>

<div id='add_btn' title='Заказать или приходовать кромку'></div>

<!-- Форма добавления заготовки -->
<div id='addpvc' title='Приход кромки ПВХ' class="addproduct" style='display:none'>
	<form method="post" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset style="font-size: 1.2em;">
			<div>
				<label>Кромка:</label>
				<select required name="PVCedge" id="edge" style="width: 200px;">
					<?
					$query = "
						SELECT PVC.PVC_ID, PVC.edge
						FROM PVCedge PVC
						ORDER BY PVC.edge
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<option value='{$row["PVC_ID"]}'>{$row["edge"]}</option>";
					}
					?>
				</select>
			</div>
			<div>
				<label>Размер:</label>
				<div class='btnset'>
					<input type='radio' id='size1' name='size' value='1' required>
						<label for='size1'>2mm</label>
					<input type='radio' id='size0' name='size' value='0' required>
						<label for='size0'>0,4mm</label>
				</div>
			</div>
			<div style="width: 170px; display: inline-block;">
				<label>Кол-во:</label>
				<input required type='number' name='amount' class='amount'>
			</div>
			<div>
				<label>Примечание:</label>
				<input type='text' name='comment' style="width: 200px;" autocomplete="off">
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Заказать' style='float: right;'>
			<input type='submit' name="subbut" value='Приходовать' style='float: left;'>
		</div>
	</form>
</div>

<!-- Форма добавления/редактирования кромки -->
<div id='editpvc' title='Кромка ПВХ' class="addproduct" style='display:none'>
	<form method="post" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset style="font-size: 1.2em;">
			<input type='hidden' name='PVC_ID'>
			<div>
				<label>Название:</label>
				<input type="text" name="edge" required autocomplete="off">
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>

<div class="halfblock">
	<h1>Список кромок</h1>
	<a href="#" class="add_pvc_btn button">Добавить новую кромку ПВХ</a>
	<br><br>
	<table>
		<thead>
		<tr class="nowrap">
			<th>Кромка</th>
			<th>Размер</th>
			<th>Наличие</th>
			<th>Потребность</th>
			<th>Заказано</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "
		SELECT PVC.PVC_ID
			,PVC.edge
			,SUM(ODD.Amount) cnt

			,IFNULL(PVCL.cnt2, 0) - CEIL(SUM(IF(ODS.IsReady = 1, (IF(ODD.Width IS NULL, ODD.Length*PI(), (ODD.Length+ODD.Width)*2) + IFNULL(ODD.PieceSize, 0)*IFNULL(ODD.PieceAmount, 1)*2)/1000, 0))) balance2

			,IFNULL(PVCL.cnt04, 0) - CEIL(SUM(IF(ODS.IsReady = 1, (IF(ODD.sidebar = 0, IF(ODD.Width IS NULL, ODD.Length*PI(), (ODD.Length+ODD.Width)*2), 0) + IF(ODD.PieceSize IS NOT NULL, IFNULL(ODD.Width, ODD.Length), 0)*(IFNULL(ODD.PieceAmount, 1)+1)*2)/1000, 0))) balance04

			,CEIL(SUM(IF(ODS.IsReady = 0, (IF(ODD.Width IS NULL, ODD.Length*PI(), (ODD.Length+ODD.Width)*2) + IFNULL(ODD.PieceSize, 0)*IFNULL(ODD.PieceAmount, 1)*2)/1000, 0))) need2

			,CEIL(SUM(IF(ODS.IsReady = 0, (IF(ODD.sidebar = 0, IF(ODD.Width IS NULL, ODD.Length*PI(), (ODD.Length+ODD.Width)*2), 0) + IF(ODD.PieceSize IS NOT NULL, IFNULL(ODD.Width, ODD.Length), 0)*(IFNULL(ODD.PieceAmount, 1)+1)*2)/1000, 0))) need04

			,IFNULL(PVCL.ord2, 0) ord2

			,IFNULL(PVCL.ord04, 0) ord04

		FROM PVCedge PVC
		LEFT JOIN OrdersDataDetail ODD ON ODD.PVC_ID = PVC.PVC_ID AND ODD.PVC_ID IS NOT NULL
		LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
			AND ODS.Visible = 1
			AND ODS.Old != 1
			AND ODS.ST_ID IN(SELECT ST_ID FROM StepsTariffs WHERE Short LIKE 'Ст%')
		LEFT JOIN (
			SELECT PVC_ID
				,SUM(IF(size = 1 AND ord = 0, amount, 0)) cnt2
				,SUM(IF(size = 0 AND ord = 0, amount, 0)) cnt04
				,SUM(IF(size = 1 AND ord = 1, amount, 0)) ord2
				,SUM(IF(size = 0 AND ord = 1, amount, 0)) ord04
			FROM PVClog
			GROUP BY PVC_ID
		) PVCL ON PVCL.PVC_ID = PVC.PVC_ID
		GROUP BY PVC.PVC_ID
		#ORDER BY cnt DESC
		ORDER BY PVC.edge
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$balance2 = $row["balance2"] ? $row["balance2"] : '-';
		$balance04 = $row["balance04"] ? $row["balance04"] : '-';
		$need2 = $row["need2"] ? $row["need2"] : '-';
		$need04 = $row["need04"] ? $row["need04"] : '-';
		$ord2 = $row["ord2"] ? $row["ord2"] : '-';
		$ord04 = $row["ord04"] ? $row["ord04"] : '-';
		$balance2bg = ($row["balance2"] + $row["ord2"]) < $row["need2"] ? 'bg-red' : '';
		$balance04bg = ($row["balance04"] + $row["ord04"]) < $row["need04"] ? 'bg-red' : '';
		echo "<tr id='pvc{$row["PVC_ID"]}' style='border-top: 2px solid #bbb;'>";
		echo "<td class='pvc_label' rowspan='2'><i>{$row["edge"]}</i><a href='#' class='add_pvc_btn' PVC_ID='{$row["PVC_ID"]}' edge='{$row["edge"]}' title='Редактировать название кромки'><i class='fa fa-pencil-alt fa-lg'></i></a></td>";
		echo "<td><i>2mm</i></td>";
		echo "<td class='txtright {$balance2bg}'>{$balance2}</td>";
		echo "<td class='txtright'>{$need2}</td>";
		echo "<td class='txtright'>{$ord2}</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td><i>0,4mm</i></td>";
		echo "<td class='txtright {$balance04bg}'>{$balance04}</td>";
		echo "<td class='txtright'>{$need04}</td>";
		echo "<td class='txtright'>{$ord04}</td>";
		echo "</tr>";
	}
?>
		</tbody>
	</table>
</div>
<div class="halfblock">
	<h1>Журнал заказа</h1>
	<table>
		<thead>
		<tr class="nowrap">
			<th width="60">Дата</th>
			<th width="60">Время</th>
			<th width="40%">Кромка</th>
			<th width="50">Размер</th>
			<th width="60">Кол-во</th>
			<th width="60%">Примечание</th>
			<th width="50">Автор</th>
			<th width="75">Действие</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "
		SELECT PVCL_ID
			,Friendly_date(PVCL.date) date
			,TIME(PVCL.date) time
			,PVC.edge
			,PVCL.amount
			,PVCL.comment
			,USR_Icon(PVCL.author) Name
			,IF(size = 1, '2mm', '0,4mm') size
		FROM PVClog PVCL
		JOIN PVCedge PVC ON PVC.PVC_ID = PVCL.PVC_ID
		WHERE PVCL.ord = 1
		ORDER BY PVCL.date DESC
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr>";
		echo "<td><span class='nowrap'><b>{$row["date"]}</b></span></td>";
		echo "<td><span class='nowrap'>{$row["time"]}</span></td>";
		echo "<td><span class='nowrap'><i>{$row["edge"]}</i></span></td>";
		echo "<td><i>{$row["size"]}</i></td>";
		echo "<td class='txtright'>{$row["amount"]}</td>";
		echo "<td>{$row["comment"]}</td>";
		echo "<td>{$row["Name"]}</td>";
		echo "<td><a class='button incoming' PVCL_ID='{$row["PVCL_ID"]}' title='Приходовать'><i class='fas fa-download fa-lg'></i></a><a class='button ord_del' PVCL_ID='{$row["PVCL_ID"]}' title='Отменить заказ'><i class='fas fa-times fa-lg'></i></a></td>";
		echo "</tr>";
	}
?>
		</tbody>
	</table>

	<h1>Журнал прихода</h1>
	<table>
		<thead>
		<tr class="nowrap">
			<th width="60">Дата</th>
			<th width="60">Время</th>
			<th width="40%">Кромка</th>
			<th width="50">Размер</th>
			<th width="60">Кол-во</th>
			<th width="60%">Примечание</th>
			<th width="50">Автор</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "
		SELECT Friendly_date(PVCL.date) date
			,TIME(PVCL.date) time
			,PVC.edge
			,PVCL.amount
			,PVCL.comment
			,USR_Icon(PVCL.author) Name
			,IF(size = 1, '2mm', '0,4mm') size
		FROM PVClog PVCL
		JOIN PVCedge PVC ON PVC.PVC_ID = PVCL.PVC_ID
		WHERE PVCL.ord = 0
		ORDER BY PVCL.date DESC
		LIMIT 100
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr>";
		echo "<td><span class='nowrap'><b>{$row["date"]}</b></span></td>";
		echo "<td><span class='nowrap'>{$row["time"]}</span></td>";
		echo "<td><span class='nowrap'><i>{$row["edge"]}</i></span></td>";
		echo "<td><i>{$row["size"]}</i></td>";
		echo "<td class='txtright'>{$row["amount"]}</td>";
		echo "<td>{$row["comment"]}</td>";
		echo "<td>{$row["Name"]}</td>";
		echo "</tr>";
	}
?>
		</tbody>
	</table>
</div>

<script>
	$(function() {
		// Форма приходования кромки
		$('#add_btn').click(function() {
			// Очистка диалога
			$('#addpvc input[type="text"], #addpvc input[type="number"], #addpvc select, #addpvc textarea').val('');
			$('#addpvc input[name="size"]').prop('checked', false);
			$('#addpvc input[type="radio"]').button("refresh");

			$('#addpvc').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			return false;
		});

		// Форма добавления/редактирования кромки
		$('.add_pvc_btn').click(function() {
			// Очистка диалога
			$('#editpvc input[name="edge"], #editpvc input[name="PVC_ID"]').val('');

			// Заполнение формы в случае редактирования
			if ($(this).attr('pvc_id')) {
				$('#editpvc input[name="edge"]').val($(this).attr('edge'));
				$('#editpvc input[name="PVC_ID"]').val($(this).attr('pvc_id'));
			}

			$('#editpvc').dialog({
				width: 400,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			return false;
		});

		// Приходование из заказа
		$('.incoming').on('click', function() {
			var pvcl_id = $(this).attr('pvcl_id');
			confirm("Пожалуйста, подтвердите <b>приходование</b> кромки.").then(function(status){if(status) location.href="?incoming="+pvcl_id;});
			return false;
		});

		// Отмена заказа
		$('.ord_del').on('click', function() {
			var pvcl_id = $(this).attr('pvcl_id');
			confirm("Пожалуйста, подтвердите <b>отмену заказа</b> кромки.").then(function(status){if(status) location.href="?ord_del="+pvcl_id;});
			return false;
		});
	});
</script>

<?
	include "footer.php";
?>
