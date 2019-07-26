<?
	include "config.php";
	$title = 'Кромки ПВХ';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_materials', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$location = $_SERVER['REQUEST_URI'];

	// Добавление заготовок
	if( isset($_POST["PVC_ID"]) )
	{
		$comment = mysqli_real_escape_string( $mysqli,$_POST["comment"] );

		// Добавление заготовок
		$query = "
			INSERT INTO PVClog(PVC_ID, amount, comment, author)
			VALUES ({$_POST["PVC_ID"]}, {$_POST["amount"]}, '{$comment}', {$_SESSION["id"]})
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

?>
<div id='add_btn' title='Приходовать кромку'></div>

<!-- Форма добавления заготовки -->
<div id='addpvc' title='Приход кромки ПВХ' class="addproduct" style='display:none'>
	<form method="post" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset style="font-size: 1.2em;">
			<input type='hidden' name='BS_ID'>
			<div>
				<label>Кромка:</label>
				<select required name="PVC_ID" id="edge" style="width: 200px;">
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
			<div style="width: 170px; display: inline-block;">
				<label>Кол-во:</label>
				<input required type='number' name='amount' class='amount'>
			</div>
			<div>
				<label>Примечание:</label>
				<input type='text' name='comment' style="width: 200px;">
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Добавить' style='float: right;'>
		</div>
	</form>
</div>

<div class="halfblock">
	<h1>Список кромок</h1>
	<table>
		<thead>
		<tr class="nowrap">
			<th>Кромка</th>
			<th>Наличие</th>
			<th>Потребность</th>
		</tr>
		</thead>
		<tbody>
<?
	$query = "
		SELECT PVC.PVC_ID
			,PVC.edge
			,SUM(ODD.Amount) cnt
			,IFNULL(PVCL.cnt, 0) - SUM(IF(ODS.IsReady = 1, ROUND((IF(ODD.Width IS NULL, ODD.Length*PI(), (ODD.Length+ODD.Width)*2) + IFNULL(ODD.PieceSize, 0)*IFNULL(ODD.PieceAmount, 1)*2)/1000), 0)) balance
			,SUM(IF(ODS.IsReady = 0, ROUND((IF(ODD.Width IS NULL, ODD.Length*PI(), (ODD.Length+ODD.Width)*2) + IFNULL(ODD.PieceSize, 0)*IFNULL(ODD.PieceAmount, 1)*2)/1000), 0)) need
		FROM PVCedge PVC
		LEFT JOIN OrdersDataDetail ODD ON ODD.PVC_ID = PVC.PVC_ID AND ODD.PVC_ID IS NOT NULL
		LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
			AND ODS.Visible = 1
			AND ODS.Old != 1
			AND ODS.ST_ID IN(SELECT ST_ID FROM StepsTariffs WHERE Short LIKE 'Ст%')
		LEFT JOIN (
			SELECT PVC_ID, SUM(amount) cnt
			FROM PVClog
			GROUP BY PVC_ID
		) PVCL ON PVCL.PVC_ID = PVC.PVC_ID
		GROUP BY PVC.PVC_ID
		ORDER BY cnt DESC
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr id='{$row["PVC_ID"]}'>";
		echo "<td><i>{$row["edge"]}</i></td>";
		echo "<td class='txtright'>{$row["balance"]}</td>";
		echo "<td class='txtright'>{$row["need"]}</td>";
		echo "</tr>";
	}
?>
		</tbody>
	</table>
</div>
<div class="halfblock">
	<h1>Журнал прихода</h1>
	<table>
		<thead>
		<tr class="nowrap">
			<th width="60">Дата</th>
			<th width="60">Время</th>
			<th width="50%">Кромка</th>
			<th width="60">Кол-во</th>
			<th width="50%">Примечание</th>
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
		FROM PVClog PVCL
		JOIN PVCedge PVC ON PVC.PVC_ID = PVCL.PVC_ID
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

			// Форма добавления/редактирования заготовок
			$('#addpvc').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			return false;
		});
	});
</script>

<?
	include "footer.php";
?>
