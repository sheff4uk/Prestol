<?
//	session_start();
	include "config.php";

	$location = $_SERVER['REQUEST_URI'];

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

	// Обновление/добавление часов в табель
	if( isset($_POST["date"]) )
	{
		$Date = '\''.date( 'Y-m-d', strtotime($_POST["date"]) ).'\'';
		$Worker = $_POST["worker"];
		$Hours = $_POST["hours"];
		$Tariff = $_POST["tariff"];
		$NightBonus = ($_POST["nightshift"] == 1) ? $_POST["nightbonus"] : 'NULL';
		$DayBonus = ($_POST["daybonus"]) ? $_POST["daybonus"] : 'NULL';
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["comment"] );

		$query = "REPLACE INTO TimeSheet(WD_ID, Date, Hours, Tariff, NightBonus, DayBonus, Comment)
				  VALUES ({$Worker}, {$Date}, {$Hours}, {$Tariff}, {$NightBonus}, {$DayBonus}, '{$Comment}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		header( "Location: ".$location );
		die;
	}

	// Обновление/добавление нормы часов за месяц
	if( isset($_POST["normhours"]) ) {
		$NormHours = ($_POST["normhours"]) ? $_POST["normhours"] : 'NULL';
		$query = "REPLACE INTO MonthlyNormHours (Year, Month, Hours)
				  VALUES ({$year}, {$month}, {$NormHours})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		header( "Location: ".$location );
		die;
	}

	$title = 'Табель';
	include "header.php";

	// Узнаем норму часов на этот месяц
	$query = "SELECT IFNULL(Hours, '') Hours FROM MonthlyNormHours WHERE Year = {$year} AND Month = {$month}";
	($result = mysqli_query( $mysqli, $query )) or die("Invalid query: " .mysqli_error( $mysqli ));
	$myrow = mysqli_fetch_array($result);
	$NormHours = $myrow['Hours'];

	// Узнаем кол-во дней в выбранном месяце
	$strdate = '01.'.$month.'.'.$year;
	$timestamp = strtotime($strdate);
	$days = date('t', $timestamp);
?>

<div style="overflow: auto;">
	<!-- Форма выбора месяца -->
	<form method="get" style="display: flex; float: left;">
		<label for="year">Год:&nbsp;</label>
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

		<label for="month">Месяц:&nbsp;</label>
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

	<!-- Форма изменения нормы часов -->
	<form method="post" style="display: flex; float: right;">
		<label for="normhours">Норма часов за месяц:&nbsp;</label>
		<input type="number" name="normhours" value="<?=$NormHours?>" min="0" max="300">
		<div class='spase'></div>
		<button>Сохранить</button>
	</form>
</div>

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
			<th>Часы</th>
			<th>Сумма</th>
			<th>%</th>
			<th>Свой %</th>
			<th title="Не учитывать месячную норму часов">НЧ</th>
			<th>Премия</th>
		</tr>
	</thead>
	<tbody>
		<?
			// Получаем список работников
//			$query = "SELECT WD.WD_ID, WD.Name, IF(COUNT(HT.WD_ID) = 1, HT.Tariff, '') deftariff
//						,IFNULL(GROUP_CONCAT(CONCAT('<a class=\"btn\" title=\"', HT.Comment, '\">', HT.Tariff, '</a>') ORDER BY HT.Tariff SEPARATOR ' '), '&nbsp;') tariffs
//						FROM WorkersData WD
//						LEFT JOIN HourlyTariff HT ON HT.WD_ID = WD.WD_ID
//						WHERE WD.Hourly = 1
//						GROUP BY WD.WD_ID";
			$query = "SELECT WD.WD_ID, WD.Name
						,IFNULL(WD.HourlyTariff, 0) deftariff
						,IFNULL(WD.NightBonus, 0) defbonus
						,CONCAT('<a class=\"btn\">', IFNULL(WD.HourlyTariff, 0), '</a>') tariffs
						,IFNULL(PremiumPercent, 0) PremiumPercent
						FROM WorkersData WD
						WHERE WD.Hourly = 1";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr><td class='worker' val='{$row["WD_ID"]}' deftariff='{$row["deftariff"]}' defbonus='{$row["defbonus"]}'><span>{$row["Name"]}</span>";
				echo "<div class='tariffs' style='display: none;'>{$row["tariffs"]}</div></td>";

				// Получаем список часов по работнику за месяц
				$query = "SELECT DAY(Date) Day, Hours, Tariff, IFNULL(NightBonus, 0) NightBonus, IFNULL(DayBonus, 0) DayBonus, Comment
						  FROM TimeSheet
						  WHERE YEAR(Date) = {$year} AND MONTH(Date) = {$month} AND WD_ID = {$row["WD_ID"]}";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				$sigmahours = 0; // Сумма отработанных дней по работнику
				$sigmamoney = 0; // Сумма заработанных денег по работнику
				$day = 0;
				if( $subrow = mysqli_fetch_array($subres) ) {
					$day = $subrow["Day"];
				}

				// Цикл по количеству дней в месяце
				$i = 1;
				while ($i <= $days) {
					$date = date('d.m.Y', strtotime($year.'-'.$month.'-'.$i));
					if( $i == $day ) {
						// значек ночной смены
						$nighticon = ($subrow["NightBonus"] > 0) ? "<i class='fa fa-moon-o' aria-hidden='true'></i>" : "";

						echo "<td class='tscell nowrap' id='{$date}' tariff='{$subrow["Tariff"]}' bonus='{$subrow["NightBonus"]}' daybonus='{$subrow["DayBonus"]}' comment='{$subrow["Comment"]}' title='Тариф: {$subrow["Tariff"]}р. ({$subrow["Comment"]})'>{$nighticon}<span>{$subrow["Hours"]}</span></td>";
						$sigmahours = $sigmahours + $subrow["Hours"];
						$sigmamoney = $sigmamoney + ($subrow["Hours"] * $subrow["Tariff"] + $subrow["NightBonus"] + $subrow["DayBonus"]);
						if( $subrow = mysqli_fetch_array($subres) ) {
							$day = $subrow["Day"];
						}
					}
					else {
						echo "<td class='tscell' id='{$date}'><span></span></td>";
					}
					$i++;
				}
				$percent = $row["PremiumPercent"];
				if ($sigmahours > $NormHours) {
					$premium = round($sigmamoney * $row["PremiumPercent"] / 100);
					$green = "style='color: #191;'";
				}
				else {
					$premium = 0;
					$green = '';
				}
				echo "<td class='txtright' {$green}>{$sigmahours}</td>";				// Сумма часов
				echo "<td class='txtright'>{$sigmamoney}</td>";				// Сумма денег
				echo "<td class='txtright'>{$percent}%</td>";							// Процент
				echo "<td><input type='number' min='0' max='100'></td>";	// Свой процент
				echo "<td><input type='checkbox'></td>";					// Не учитывать норматив
				echo "<td class='txtright'>{$premium}</td>";							// Премия
				echo "</tr>";
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
				<div style="display: none;">
					<label>Тарифы:</label>
					<div class="tariffs"></div>
				</div>
				<div>
					<label>Тариф:</label>
					<input required type='number' name='tariff' min="0" max="9999">
				</div>
				<div>
					<label>Смена:</label>
					<input type='checkbox' name='nightshift' id='nightshift' class='button nightshift' value='1'><label for="nightshift"></label>
					<div class='wr-nightbonus'> + <input required type='number' name='nightbonus' min="0" max="9999"></div>
				</div>
				<div>
					<label>Надбавка:</label>
					<input type='number' name='daybonus' min="-9999" max="9999">
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
	// Функция активирует/деактивирует инпут ночной премии
	function bonusonoff(bonus, defbonus) {
		if( bonus > 0 ) {
			$('.nightshift').prop( "checked", true );
			$('.wr-nightbonus').show();
		}
		else {
			$('.nightshift').prop( "checked", false );
			$('.wr-nightbonus').hide();
		}

		$( '.nightshift' ).button("refresh");
	}

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
			var defbonus = $(this).parents('tr').find('.worker').attr('defbonus');
			var hours = $(this).find('span').html();
			var tariff = $(this).attr('tariff');
			var bonus = $(this).attr('bonus');
			var daybonus = $(this).attr('daybonus');
			var comment = $(this).attr('comment');

			$('#dayworklog input[name="date"]').val(date);
			$('#dayworklog input[name="worker"]').val(worker);

			// Заполнение
			if( hours != '' ) // Редактирование
			{
				$('#dayworklog input[name="hours"]').val(hours);
				$('#dayworklog input[name="tariff"]').val(tariff);
				if( bonus > 0 ) {
					$('#dayworklog input[name="nightbonus"]').val(bonus);
				}
				else {
					$('#dayworklog input[name="nightbonus"]').val(defbonus);
				}
				$('#dayworklog input[name="daybonus"]').val(daybonus);
				$('#dayworklog textarea[name="comment"]').val(comment);
				bonusonoff(bonus);
			}
			else { // Добавление
				$('#dayworklog input[name="hours"]').val('8');
				$('#dayworklog input[name="tariff"]').val(deftariff);
				$('#dayworklog input[name="nightbonus"]').val(defbonus);
				$('#dayworklog input[name="daybonus"]').val('');
				$('#dayworklog textarea[name="comment"]').val('');
				bonusonoff(0);
			}

			$('#dayworklog div.tariffs').html(workertariffs);

			$('.tariffs a').click(function() {
				$('#dayworklog input[name="tariff"]').val($(this).html());
			});

			$(".nightshift").change(function() {
				if(this.checked) {
					bonusonoff(1);
				}
				else {
					bonusonoff(0);
				}
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
