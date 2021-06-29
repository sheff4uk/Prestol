<?
include "config.php";

//Редактирование тарифа
if( isset($_POST["tariff"]) ) {
session_start();
	$query = "
		UPDATE StepsTariffsMechanism
		SET tariff = {$_POST["tariff"]}
		WHERE PMM_ID = {$_POST["PMM_ID"]} AND ST_ID = {$_POST["ST_ID"]}
	";
	if( !mysqli_query( $mysqli, $query ) ) {
		$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
	}
	if( count($_SESSION["error"]) == 0) {
		$_SESSION["success"][] = "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=?PM_ID='.$_POST["gPM_ID"].'&PME_ID='.$_POST["gPME_ID"].'&ST_ID='.$_POST["gST_ID"].'#'.$_POST["PMM_ID"].'_'.$_POST["ST_ID"].'">');
}

$title = 'Тарифы для стульев';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('stepstariffs', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}
?>

<!--Фильтр-->
<div id="filter">
	<h3>Фильтр</h3>
	<form method="get" style="position: relative;">
		<a href="/stepstariffschair.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Модель:</span>
			<select name="PM_ID" class="<?=$_GET["PM_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT PM.PM_ID
						,PM.Model
					FROM ProductModels PM
					WHERE PM.PT_ID = 1 AND PM.archive = 0
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["PM_ID"] == $_GET["PM_ID"]) ? "selected" : "";
					echo "<option value='{$row["PM_ID"]}' {$selected}>{$row["Model"]}</option>";
				}
				?>
			</select>
		</div>

		<button style="float: right;">Фильтр</button>
	</form>
</div>
<!--Конец фильтра-->

<?
// Узнаем есть ли фильтр
$filter = 0;
foreach ($_GET as &$value) {
	if( $value ) $filter = 1;
}
?>

<script>
	$(function() {
		$( "#filter" ).accordion({
			active: <?=($filter ? "0" : "false")?>,
			collapsible: true,
			heightStyle: "content"
		});

		// При скроле сворачивается фильтр
		$(window).scroll(function(){
			$( "#filter" ).accordion({
				active: "false"
			});
		});
	});
</script>

<!--Таблица с тарифами-->
<table class="MN_table">
	<thead>
		<tr>
			<th>Модель</th>
			<th>Этап</th>
			<th>Тариф</th>
			<th rowspan="2"></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT STM.PMM_ID
		,STM.ST_ID
		,PM.Model
		,ST.Step
		,STM.tariff
	FROM StepsTariffsMechanism STM
	JOIN ProductModelsMechanism PMM ON PMM.PMM_ID = STM.PMM_ID
	JOIN StepsTariffs ST ON ST.ST_ID = STM.ST_ID AND ST.PT_ID = 1
	JOIN ProductModels PM ON PM.PM_ID = PMM.PM_ID AND PM.archive = 0
	WHERE 1
		".($_GET["PM_ID"] ? "AND PM.PM_ID={$_GET["PM_ID"]}" : "")."
	ORDER BY PM.PM_ID, ST.Sort
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["PMM_ID"]?>_<?=$row["ST_ID"]?>">
		<td><?=$row["Model"]?></td>
		<td><?=$row["Step"]?></td>
		<td><b><?=$row["tariff"]?></b></td>
		<td><a href="#" class="tariff_edit" PMM_ID="<?=$row["PMM_ID"]?>" ST_ID="<?=$row["ST_ID"]?>" tariff="<?=$row["tariff"]?>" model="<?=htmlspecialchars($row["Model"])?>" step="<?=$row["Step"]?>" title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>
	</tbody>
</table>
<!--Конец таблицы с тарифами-->

<!--Форма редактирования-->
<style>
	#tariff_edit_form table td {
		font-size: 1.5em;
	}
</style>

<div id='tariff_edit_form' title='Изменение тарифа' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="PMM_ID">
			<input type="hidden" name="ST_ID">
			<input type="hidden" name="gPM_ID" value="<?=$_GET["PM_ID"]?>">
			<input type="hidden" name="gST_ID" value="<?=$_GET["ST_ID"]?>">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Модель</th>
						<th>Этап</th>
						<th>Тариф</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td id="model"></td>
						<td id="step"></td>
						<td><input type="number" name="tariff" min="0" max="5000" style="width: 70px;" required></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Записать' style='float: right;'>
		</div>
	</form>
</div>
<!--Конец формы-->
<script>
	$(function() {
		$('.tariff_edit').click( function() {
			var PMM_ID = $(this).attr("PMM_ID"),
				ST_ID = $(this).attr("ST_ID"),
				tariff = $(this).attr("tariff"),
				model = $(this).attr("model"),
				step = $(this).attr("step");

			$('#tariff_edit_form input[name="PMM_ID"]').val(PMM_ID);
			$('#tariff_edit_form input[name="ST_ID"]').val(ST_ID);
			$('#tariff_edit_form input[name="tariff"]').val(tariff);
			$('#tariff_edit_form #model').text(model);
			$('#tariff_edit_form #step').text(step);

			$('#tariff_edit_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

	});
</script>

<?
include "footer.php";
?>
