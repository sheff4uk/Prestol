<?
	session_start();
	include "config.php";

	$location = $_SERVER['REQUEST_URI'];

	// Обновление/добавление часов в табель
	if( isset($_POST["date"]) )
	{
		$Date = '\''.date( 'Y-m-d', strtotime($_POST["date"]) ).'\'';
		$Worker = $_POST["worker"];
		$Hours = $_POST["hours"];
		$Tariff = $_POST["tariff"];
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["comment"] );

		$query = "REPLACE INTO TimeSheet(WD_ID, Date, Hours, Tariff, Comment)
				  VALUES ({$Worker}, {$Date}, {$Hours}, {$Tariff}, '{$Comment}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		header( "Location: ".$location );
		die;
	}

	$title = 'Табель';
	include "header.php";

	if( isset($_GET["year"]) ) {
		$year = $_GET["year"];
	}
	else {
		$year = date('Y');
	}

	if( isset($_GET["month"]) ) {
		$month = $_GET["month"];
	}
	else {
		$month = date('n');
	}

	// Узнаем кол-во дней в выбранном месяце
	$strdate = '01.'.$month.'.'.$year;
	$timestamp = strtotime($strdate);
	$days = date('t', $timestamp);
?>

<p>
	<form method="get" style="display: flex;">
		<label for="year">Год:</label>
		<script>
			$( document ).ready(function() {
				$("#year option[value='<?=$year?>']").prop('selected', true);
			});
		</script>
		<select name="year" id="year">
		<?
			$query = "SELECT YEAR(Date) year FROM TimeSheet GROUP BY year ORDER BY year";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$lastyear = 0;
			if( mysqli_num_rows($res) > 0 ) {
				while( $row = mysqli_fetch_array($res) ) {
					echo "<option value='{$row["year"]}'>{$row["year"]}</option>";
					$lastyear = $row["year"];
				}
			}
			// Когда таблица-табель пустая или в таблице нет текущего года
			if( $lastyear < date('Y') ) {
				?>
				<option value='<?=date('Y')?>'><?=date('Y')?></option>
				<?
			}
		?>
		</select>

		<div class='spase'></div>

		<label for="month">Месяц:</label>
		<script>
			$( document ).ready(function() {
				$("#month option[value='<?=$month?>']").prop('selected', true);
			});
		</script>
		<select name="month" id="month">
			<option value="1">Январь</option>
			<option value="2">Февраль</option>
			<option value="3">Март</option>
			<option value="4">Апрель</option>
			<option value="5">Май</option>
			<option value="6">Июнь</option>
			<option value="7">Июль</option>
			<option value="8">Август</option>
			<option value="9">Сентябрь</option>
			<option value="10">Октябрь</option>
			<option value="11">Ноябрь</option>
			<option value="12">Декабрь</option>
		</select>

		<div class='spase'></div>

		<button>Применить</button>
	</form>
</p>

<table id="timesheet">
	<thead>
		<tr>
			<th>Работник</th>
			<?
				$i = 1;
				while ($i <= $days) {
					echo "<th>".$i++."</th>";
				}
			?>
			<th title="Сумма">&Sigma;</th>
		</tr>
	</thead>
	<tbody>
		<?
			// Получаем список работников
			$query = "SELECT WD.WD_ID, WD.Name, IF(COUNT(HT.WD_ID) = 1, HT.Tariff, '') deftariff
						,IFNULL(GROUP_CONCAT(CONCAT('<a class=\"btn\" title=\"', HT.Comment, '\">', HT.Tariff, '</a>') ORDER BY HT.Tariff SEPARATOR ' '), '&nbsp;') tariffs
						FROM WorkersData WD
						LEFT JOIN HourlyTariff HT ON HT.WD_ID = WD.WD_ID
						WHERE WD.Hourly = 1
						GROUP BY WD.WD_ID";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr><td class='worker' val='{$row["WD_ID"]}' deftariff='{$row["deftariff"]}'><span>{$row["Name"]}</span>";
				echo "<div class='tariffs' style='display: none;'>{$row["tariffs"]}</div></td>";

				// Получаем список часов по работнику за месяц
				$query = "SELECT DAY(Date) Day, Hours, Tariff, Comment
						  FROM TimeSheet
						  WHERE YEAR(Date) = {$year} AND MONTH(Date) = {$month} AND WD_ID = {$row["WD_ID"]}";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				$sigma = 0;
				$day = 0;
				if( $subrow = mysqli_fetch_array($subres) ) {
					$day = $subrow["Day"];
				}

				// Цикл по количеству дней в месяце
				$i = 1;
				while ($i <= $days) {
					$date = date('d.m.Y', strtotime($year.'-'.$month.'-'.$i));
					if( $i == $day ) {
						echo "<td class='tscell' id='{$date}' tariff='{$subrow["Tariff"]}' comment='{$subrow["Comment"]}' title='Тариф: {$subrow["Tariff"]}р. ({$subrow["Comment"]})'>{$subrow["Hours"]}</td>";
						$sigma = $sigma + $subrow["Hours"];
						if( $subrow = mysqli_fetch_array($subres) ) {
							$day = $subrow["Day"];
						}
					}
					else {
						echo "<td class='tscell' id='{$date}'></td>";
					}
					$i++;
				}
				echo "<td>{$sigma}</td></tr>";
			}
		?>
	</tbody>
</table>

	<!-- Форма ворклог -->
	<div id='dayworklog' class="addproduct" style="display:none">
		<form method="post">
			<fieldset>
				<input type="hidden" name="date">
				<input type="hidden" name="worker">
				<div>
					<label>Часы:</label>
					<input required type='number' name='hours' step='0.5' min="0" max="24">
				</div>
				<div>
					<label>Тарифы:</label>
					<div class="tariffs"></div>
				</div>
				<div>
					<label>Тариф:</label>
					<input required type='number' name='tariff' min="0">
				</div>
				<div>
					<label>Примечание:</label>
					<textarea name='comment' rows='4' cols='25'></textarea>
				</div>
			</fieldset>
			<div>
				<hr>
				<button type='submit' style='float: right;'>Сохранить</button>
			</div>
		</form>
	</div>
</body>
</html>

<script>
	$(document).ready(function(){

		// Подсвечивание столбцов таблицы
		$('#timesheet').columnHover({eachCell:true, hoverClass:'hover', ignoreCols: [1]});

		// Форма добавления часов
		$('.tscell').click(function() {
			var workername = $(this).parents('tr').find('.worker > span').html();
			var workertariffs = $(this).parents('tr').find('.tariffs').html();
			var date = $(this).attr('id');
			var worker = $(this).parents('tr').find('.worker').attr('val');
			var deftariff = $(this).parents('tr').find('.worker').attr('deftariff');
			var hours = $(this).html();
			var tariff = $(this).attr('tariff');
			var comment = $(this).attr('comment');

			$('#dayworklog input[name="date"]').val(date);
			$('#dayworklog input[name="worker"]').val(worker);

			// Заполнение
			if( hours != '' )
			{
				$('#dayworklog input[name="hours"]').val(hours);
				$('#dayworklog input[name="tariff"]').val(tariff);
				$('#dayworklog textarea[name="comment"]').val(comment);
			}
			else {
				$('#dayworklog input[name="hours"]').val('8');
				$('#dayworklog input[name="tariff"]').val(deftariff);
				$('#dayworklog textarea[name="comment"]').val('');
			}

			$('#dayworklog div.tariffs').html(workertariffs);

			$('.tariffs a').click(function() {
				$('#dayworklog input[name="tariff"]').val($(this).html());
			});

			// Вызов формы
			$('#dayworklog').dialog({
				title:	workername+' '+date,
				width:	400,
				modal:	true,
				show:	'blind',
				hide:	'explode',
			});
			return false;
		});
	});
</script>
