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

$title = 'Тарифы для столов';
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
		<a href="/stepstariffstable.php" style="position: absolute; top: 10px; right: 10px;" class="button">Сброс</a>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Модель:</span>
			<select name="PM_ID" class="<?=$_GET["PM_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT PM.PM_ID
						,PM.Model
					FROM ProductModels PM
					WHERE PM.PT_ID = 2 AND PM.archive = 0
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["PM_ID"] == $_GET["PM_ID"]) ? "selected" : "";
					echo "<option value='{$row["PM_ID"]}' {$selected}>{$row["Model"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Механизм:</span>
			<select name="PME_ID" class="<?=$_GET["PME_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT PME.PME_ID
						,PME.Mechanism
					FROM ProductMechanism PME
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["PME_ID"] == $_GET["PME_ID"]) ? "selected" : "";
					echo "<option value='{$row["PME_ID"]}' {$selected}>{$row["Mechanism"]}</option>";
				}
				?>
			</select>
		</div>

		<div class="nowrap" style="margin-bottom: 10px;">
			<span>Этап:</span>
			<select name="ST_ID" class="<?=$_GET["ST_ID"] ? "filtered" : ""?>">
				<option value=""></option>
				<?
				$query = "
					SELECT ST.ST_ID
						,ST.Step
					FROM StepsTariffs ST
					WHERE ST.PT_ID = 2
					ORDER BY ST.Sort
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$selected = ($row["ST_ID"] == $_GET["ST_ID"]) ? "selected" : "";
					echo "<option value='{$row["ST_ID"]}' {$selected}>{$row["Step"]}</option>";
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
<table style="table-layout: fixed; width: 100%;">
	<thead>
		<tr>
			<th rowspan="2">Модель</th>
			<th rowspan="2">Механизм</th>
			<th rowspan="2">Этап</th>
			<th rowspan="2">Стандартная длина столешницы</th>
			<th colspan="2">Тариф за стандартный стол</th>
			<th rowspan="2">Базовый тариф без учёта наценок за размер, кромку и др.</th>
			<th rowspan="2"></th>
		</tr>
		<tr>
			<th>Без ПВХ</th>
			<th>С ПВХ</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT STM.PMM_ID
		,STM.ST_ID
		,PM.Model
		,PME.Mechanism
		,ST.Step
		,STM.tariff
		,(
			SELECT IF(Width IS NULL, Length * -1, Length)
			FROM OrdersDataDetail
			WHERE standart = 1 AND PM_ID = PM.PM_ID AND PME_ID = PME.PME_ID
			GROUP BY Length
			ORDER BY SUM(1) DESC
			LIMIT 1
		) standart_length
		#Функция вычисляет тариф с учетом параметров
		,StepsTariffs(
			PM.PM_ID
			,PME.PME_ID
			,ST.ST_ID
			,(
				SELECT IF(Width IS NULL, Length * -1, Length)
				FROM OrdersDataDetail
				WHERE standart = 1 AND PM_ID = PM.PM_ID AND PME_ID = PME.PME_ID
				GROUP BY Length
				ORDER BY SUM(1) DESC
				LIMIT 1
			)
			,0
			,0
			,0
		) standart_tariff
		,StepsTariffs(
			PM.PM_ID
			,PME.PME_ID
			,ST.ST_ID
			,(
				SELECT IF(Width IS NULL, Length * -1, Length)
				FROM OrdersDataDetail
				WHERE standart = 1 AND PM_ID = PM.PM_ID AND PME_ID = PME.PME_ID
				GROUP BY Length
				ORDER BY SUM(1) DESC
				LIMIT 1
			)
			,1
			,0
			,0
		) standart_tariff_PVC
	FROM StepsTariffsMechanism STM
	JOIN ProductModelsMechanism PMM ON PMM.PMM_ID = STM.PMM_ID
	JOIN StepsTariffs ST ON ST.ST_ID = STM.ST_ID AND ST.PT_ID = 2
	JOIN ProductModels PM ON PM.PM_ID = PMM.PM_ID AND PM.archive = 0
	JOIN ProductMechanism PME ON PME.PME_ID = PMM.PME_ID
	WHERE 1
		".($_GET["PM_ID"] ? "AND PM.PM_ID={$_GET["PM_ID"]}" : "")."
		".($_GET["PME_ID"] ? "AND PME.PME_ID={$_GET["PME_ID"]}" : "")."
		".($_GET["ST_ID"] ? "AND ST.ST_ID={$_GET["ST_ID"]}" : "")."
	ORDER BY PM.PM_ID, PME.PME_ID, ST.Sort
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["PMM_ID"]?>_<?=$row["ST_ID"]?>">
		<td><?=$row["Model"]?></td>
		<td><?=$row["Mechanism"]?></td>
		<td><?=$row["Step"]?></td>
		<td><?=($row["standart_length"] < 0 ? "Ø".ABS($row["standart_length"]) : $row["standart_length"])?></td>
		<?
			if( $row["standart_tariff"] == $row["standart_tariff_PVC"] ) {
				echo "<td colspan='2'>{$row["standart_tariff"]}</td>";
			}
			else {
				echo "<td>{$row["standart_tariff"]}</td>";
				echo "<td>{$row["standart_tariff_PVC"]}</td>";
			}
		?>
		<td><b><?=$row["tariff"]?></b></td>
		<td><a href="#" class="tariff_edit" PMM_ID="<?=$row["PMM_ID"]?>" ST_ID="<?=$row["ST_ID"]?>" tariff="<?=$row["tariff"]?>" model="<?=htmlspecialchars($row["Model"])?>" mech="<?=$row["Mechanism"]?>" step="<?=$row["Step"]?>" title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>
	</tbody>
</table>
<!--Конец таблицы с тарифами-->

<div style="display: inline-block;">
	<h3>Наценки за размер круглых столов</h3>
	<table style="text-align: center;">
		<thead>
			<tr>
				<th>Этап</th>
				<th>Диаметр столешницы</th>
				<th>Наценка</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT ST.Step
					,PSLT.From
					,PSLT.Tariff
					,PSLT.ST_ID
				FROM ProductSizeLengthTariff PSLT
				JOIN StepsTariffs ST ON ST.ST_ID = PSLT.ST_ID
				WHERE round = 1
				ORDER BY PSLT.ST_ID, PSLT.From
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				if( $step == $row["ST_ID"] ) {
					$tariff += $row["Tariff"];
				}
				else {
					$step = $row["ST_ID"];
					$tariff = $row["Tariff"];
				}
				echo "<tr><td>{$row["Step"]}</td><td>от {$row["From"]}</td><td>{$tariff}</td></tr>";
			}
			?>
		</tbody>
	</table>
</div>

<div style="display: inline-block;">
	<h3>Наценки за размер остальных столов</h3>
	<table style="text-align: center;">
		<thead>
			<tr>
				<th>Этап</th>
				<th>Длинная сторона столешницы</th>
				<th>Наценка</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT ST.Step
					,PSLT.From
					,PSLT.Tariff
					,PSLT.ST_ID
				FROM ProductSizeLengthTariff PSLT
				JOIN StepsTariffs ST ON ST.ST_ID = PSLT.ST_ID
				WHERE round = 0
				ORDER BY PSLT.ST_ID, PSLT.From
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				if( $step == $row["ST_ID"] ) {
					$tariff += $row["Tariff"];
				}
				else {
					$step = $row["ST_ID"];
					$tariff = $row["Tariff"];
				}
				echo "<tr><td>{$row["Step"]}</td><td>от {$row["From"]}</td><td>{$tariff}</td></tr>";
			}
			?>
		</tbody>
	</table>
</div>

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
			<input type="hidden" name="gPME_ID" value="<?=$_GET["PME_ID"]?>">
			<input type="hidden" name="gST_ID" value="<?=$_GET["ST_ID"]?>">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Модель</th>
						<th>Механизм</th>
						<th>Этап</th>
						<th>Базовый тариф без учёта наценок за размер, кромку и др.</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td id="model"></td>
						<td id="mech"></td>
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
				mech = $(this).attr("mech"),
				step = $(this).attr("step");

			$('#tariff_edit_form input[name="PMM_ID"]').val(PMM_ID);
			$('#tariff_edit_form input[name="ST_ID"]').val(ST_ID);
			$('#tariff_edit_form input[name="tariff"]').val(tariff);
			$('#tariff_edit_form #model').text(model);
			$('#tariff_edit_form #mech').text(mech);
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
