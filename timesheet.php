<?
//	session_start();
	include "config.php";
	$title = 'Табель';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_timesheet', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

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

		$query = "INSERT INTO TimeSheet
					 SET WD_ID = {$Worker}
						,Date = {$Date}
						,Hours = {$Hours}
						,Tariff = {$Tariff}
						,NightBonus = {$NightBonus}
						,DayBonus = {$DayBonus}
						,Comment = '{$Comment}'
				  ON DUPLICATE KEY UPDATE
						 Hours = {$Hours}
						,Tariff = {$Tariff}
						,NightBonus = {$NightBonus}
						,DayBonus = {$DayBonus}
						,Comment = '{$Comment}'";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		//header( "Location: ".$location );
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Обновление/добавление нормы часов за месяц
	if( isset($_POST["normhours"]) ) {
		$NormHours = ($_POST["normhours"]) ? $_POST["normhours"] : 'NULL';
		$query = "REPLACE INTO MonthlyNormHours (Year, Month, Hours)
				  VALUES ({$year}, {$month}, {$NormHours})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		//header( "Location: ".$location );
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

	// Сохранение данных из таблицы табеля
	if( isset($_POST["TYear"]) and isset($_POST["TMonth"]) )
	{
		$tyear = $_POST["TYear"];
		$tmonth = $_POST["TMonth"];
		foreach( $_POST as $k => $v)
		{
			if( strpos($k,"MP") === 0 ) // Сохраняем ручной процент премии
			{
				$worker = (int)str_replace( "MP", "", $k );
				$ManPercent = $v <> '' ? $v : 'NULL' ;
				$DNH = $_POST["DNH".$worker] ? $_POST["DNH".$worker] : 'NULL';
				$query = "REPLACE INTO MonthlyPremiumPercent (Year, Month, WD_ID, PremiumPercent, DisableNormHours)
						  VALUES ({$year}, {$month}, {$worker}, {$ManPercent}, {$DNH})";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
			$query = "DELETE FROM MonthlyPremiumPercent WHERE PremiumPercent IS NULL AND DisableNormHours IS NULL";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		//header( "Location: ".$_SERVER['REQUEST_URI'] );
		exit ('<meta http-equiv="refresh" content="0; url='.$_SERVER['REQUEST_URI'].'">');
		die;
	}

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
			<option value="1"><?=$MONTHS[1]?></option>
			<option value="2"><?=$MONTHS[2]?></option>
			<option value="3"><?=$MONTHS[3]?></option>
			<option value="4"><?=$MONTHS[4]?></option>
			<option value="5"><?=$MONTHS[5]?></option>
			<option value="6"><?=$MONTHS[6]?></option>
			<option value="7"><?=$MONTHS[7]?></option>
			<option value="8"><?=$MONTHS[8]?></option>
			<option value="9"><?=$MONTHS[9]?></option>
			<option value="10"><?=$MONTHS[10]?></option>
			<option value="11"><?=$MONTHS[11]?></option>
			<option value="12"><?=$MONTHS[12]?></option>
		</select>

		<div class='spase'></div>

		<button>Применить</button>
	</form>

	<!-- Форма изменения нормы часов -->
	<form method="post" style="display: flex; float: right;">
		<label for="normhours">Норма часов за месяц:&nbsp;</label>
		<input type="number" name="normhours" id="normhours" value="<?=$NormHours?>" min="0" max="300">
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
				$workdays = 0;
				while ($i <= $days) {
					$date = $year.'-'.$month.'-'.$i;
					if (date('N', strtotime($date)) >= 6) { // Выделяем цветом выходные дни
						echo "<th style='background: chocolate;'>".$i++."</th>";
					}
					else {
						echo "<th>".$i++."</th>";
						$workdays++;
					}
				}
			?>
			<script>
				$('#normhours').attr('placeholder', '<?=($workdays*8)?>');
			</script>

			<th>Часы</th>
			<th>Сумма</th>
			<th>%</th>
			<th>Свой %</th>
			<th title="Не учитывать месячную норму часов">НЧ</th>
			<th>Премия</th>
			<th>Итого</th>
		</tr>
	</thead>
	<tbody>
		<form method="post"> <!-- Форма в табеле -->
			<input type="hidden" name="TYear" value="<?=$year?>">
			<input type="hidden" name="TMonth" value="<?=$month?>">
			<button id="timesheetbutton">Сохранить</button>
		<?
			// Получаем список работников
			$query = "SELECT WD.WD_ID, WD.Name
						,IFNULL(WD.HourlyTariff, 0) deftariff
						,IFNULL(WD.NightBonus, 0) defbonus
						,CONCAT('<a class=\"btn\">', IFNULL(WD.HourlyTariff, 0), '</a>') tariffs
						,IFNULL(WD.PremiumPercent, 0) PremiumPercent
						,IFNULL(MPP.PremiumPercent, '') ManPercent
						,IF(MPP.DisableNormHours = 1, 'checked', '') DNHcheck
						,WD.IsActive
						,SUM(TS.Hours) Hours
						FROM WorkersData WD
						LEFT JOIN TimeSheet TS ON TS.WD_ID = WD.WD_ID AND YEAR(TS.Date) = {$year} AND MONTH(TS.Date) = {$month}
						LEFT JOIN MonthlyPremiumPercent MPP ON MPP.WD_ID = WD.WD_ID AND MPP.Year = {$year} AND MPP.Month = {$month}
						WHERE WD.Type = 2
						GROUP BY WD.WD_ID
						HAVING IsActive = 1 OR Hours > 0";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr><td class='worker' val='{$row["WD_ID"]}' deftariff='{$row["deftariff"]}' defbonus='{$row["defbonus"]}'><a href='/paylog.php?worker={$row["WD_ID"]}'>{$row["Name"]}</a>";
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
				$percent = $row["ManPercent"] == '' ? $row["PremiumPercent"] : $row["ManPercent"];
				if ($sigmahours >= $NormHours or $row["DNHcheck"] == 'checked') {
					$premium = round($sigmamoney * $percent / 100);
					$green = "style='color: #191;'";
				}
				else {
					$premium = 0;
					$green = '';
				}
				$total = $sigmamoney + $premium;
				echo "<td class='txtright' {$green}>{$sigmahours}</td>";					// Сумма часов
				echo "<td class='txtright'>{$sigmamoney}</td>";								// Сумма денег
				echo "<td class='txtright'>{$row["PremiumPercent"]}%</td>";					// Процент
				echo "<td><input type='number' name='MP{$row["WD_ID"]}' value='{$row["ManPercent"]}' min='0' max='100'></td>";// Свой процент
				echo "<td><input type='checkbox' name='DNH{$row["WD_ID"]}' {$row["DNHcheck"]} value='1'></td>";	// Не учитывать норматив
				$today = date("d.m.Y");
				echo "<td><button sign='' class='button edit_pay txtright' location='{$location}' title='Начислить премию' style='width: 100%;' worker='{$row["WD_ID"]}' date='{$today}' comment='Премия за {$MONTHS[$month]} {$year} {$percent}%' pay='{$premium}'>{$premium}</button></td>";						// Премия
				echo "<td class='txtright'>{$total}</td>";								// Премия + Сумма
				echo "</tr>";
			}
		?>
		</form>
	</tbody>
	<thead>
		<tr>
			<th>Работник</th>
			<?
				$i = 1;
				while ($i <= $days) {
					$date = $year.'-'.$month.'-'.$i;
					if (date('N', strtotime($date)) >= 6) { // Выделяем цветом выходные дни
						echo "<th style='background: chocolate;'>".$i++."</th>";
					}
					else {
						echo "<th>".$i++."</th>";
					}
				}
			?>
			<th>Часы</th>
			<th>Сумма</th>
			<th>%</th>
			<th>Свой %</th>
			<th title="Не учитывать месячную норму часов">НЧ</th>
			<th>Премия</th>
			<th>Итого</th>
		</tr>
	</thead>
</table>

	<? include "form_addpay.php"; // форма начисления платежа ?>

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
				closeText: 'Закрыть'
			});
			return false;
		});

		// Показывать кнопку сохранить при изменении данных в форме табеля
		$('#timesheet input').change(function() {
			$('#timesheetbutton').css("display", "block");
		});
		$('#timesheet input').keyup(function() {
			$('#timesheetbutton').css("display", "block");
		});
	});
</script>
