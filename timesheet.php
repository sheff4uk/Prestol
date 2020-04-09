<?
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

// Узнаем базовый процент месячной премии
$query = "SELECT value FROM vars WHERE var LIKE 'premium'";
$res = mysqli_query( $mysqli, $query );
$row = mysqli_fetch_array($res);
$premium_percent = $row["value"];

// Обновление/добавление часов в табель
if( isset($_POST["date"]) ) {
	$Date = '\''.date( 'Y-m-d', strtotime($_POST["date"]) ).'\'';
	$Worker = $_POST["worker"];
	$start1 = ($_POST["start1"] != $_POST["end1"]) ? $_POST["start1"] : 'NULL';
	$end1 = ($_POST["start1"] != $_POST["end1"]) ? $_POST["end1"] : 'NULL';
	$start2 = ($_POST["start2"] != $_POST["end2"]) ? $_POST["start2"] : 'NULL';
	$end2 = ($_POST["start2"] != $_POST["end2"]) ? $_POST["end2"] : 'NULL';
	$Hours = $_POST["hours"];
	$Tariff = $_POST["tariff"];

	$query = "
		INSERT INTO TimeSheet
		SET WD_ID = {$Worker}
			,Date = {$Date}
			,start1 = {$start1}
			,end1 = {$end1}
			,start2 = {$start2}
			,end2 = {$end2}
			,Hours = {$Hours}
			,Tariff = {$Tariff}
			,author = {$_SESSION["id"]}
		ON DUPLICATE KEY UPDATE
			 start1 = {$start1}
			,end1 = {$end1}
			,start2 = {$start2}
			,end2 = {$end2}
			,Hours = {$Hours}
			,Tariff = {$Tariff}
			,author = {$_SESSION["id"]}
	";
	mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

	exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
	die;
}

// Сохранение данных из таблицы табеля
if( isset($_POST["TYear"]) and isset($_POST["TMonth"]) ) {
	$tyear = $_POST["TYear"];
	$tmonth = $_POST["TMonth"];
	foreach( $_POST as $k => $v)
	{
		if( strpos($k,"MP") === 0 ) // Сохраняем ручной процент премии
		{
			$worker = (int)str_replace( "MP", "", $k );
			$ManPercent = $v <> '' ? $v : 'NULL' ;
			$DNH = $_POST["DNH".$worker] ? $_POST["DNH".$worker] : 'NULL';
			$query = "
				REPLACE INTO MonthlyPremiumPercent (Year, Month, WD_ID, PremiumPercent, DisableNormHours)
				VALUES ({$year}, {$month}, {$worker}, {$ManPercent}, {$DNH})
			";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		$query = "DELETE FROM MonthlyPremiumPercent WHERE PremiumPercent IS NULL AND DisableNormHours IS NULL";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	}
	//header( "Location: ".$_SERVER['REQUEST_URI'] );
	exit ('<meta http-equiv="refresh" content="0; url='.$_SERVER['REQUEST_URI'].'">');
	die;
}

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
		<select name="year" id="year" onchange="this.form.submit()">
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
		<select name="month" id="month" onchange="this.form.submit()">
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
	</form>
	<span id="normhours" style="float: right;">
		Норма часов за месяц:
		<b style="font-size: 1.5em;"></b>
	</span>
</div>

<table id="timesheet" class="main_table">
	<thead>
		<tr class="nowrap">
			<th width="100">Работник</th>
			<?
				// Получаем производственный календарь на выбранный год
				$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
				$json = json_encode($xml);
				$data = json_decode($json,TRUE);

				$i = 1;
				$workdays = 0;
				while ($i <= $days) {
					$date = $year.'-'.$month.'-'.$i;
					$day_of_week = date('N', strtotime($date));	// День недели
					$day = date('d', strtotime($date));			// День месяца

					// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
					$t = 0;
					foreach( $data["days"]["day"] as $key=>$value ) {
						if( $value["@attributes"]["d"] == $month.".".$day) {
							$t = $value["@attributes"]["t"];
						}
					}

					if ( (($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) { // Выделяем цветом выходные дни
						echo "<th style='background: chocolate;'>".$i++."</th>";
					}
					else {
						echo "<th>".$i++."</th>";
						$workdays++;
					}
				}

				$NormHours = $workdays*8;
			?>
			<script>
				$('#normhours > b').text('<?=$NormHours?>');
			</script>

			<th width="45">Часы</th>
			<th width="55">Сумма</th>
			<th width="40">%</th>
			<th width="50">Свой %</th>
			<th width="30" title="Не учитывать месячную норму часов">НЧ</th>
			<th width="65">Премия</th>
			<th width="50">Итого</th>
		</tr>
	</thead>
	<tbody>
		<form method="post"> <!-- Форма в табеле -->
			<input type="hidden" name="TYear" value="<?=$year?>">
			<input type="hidden" name="TMonth" value="<?=$month?>">
			<button id="timesheetbutton">Сохранить</button>
		<?
			// Получаем список работников
			$query = "
				SELECT WD.WD_ID, WD.Name
					,IFNULL(WD.tariff, 0) deftariff
					,IFNULL(MPP.PremiumPercent, '') ManPercent
					,IF(MPP.DisableNormHours = 1, 'checked', '') DNHcheck
					,WD.act
					,IFNULL(SUM(TS.Hours), 0) Hours
				FROM WorkersData WD
				LEFT JOIN TimeSheet TS ON TS.WD_ID = WD.WD_ID AND YEAR(TS.Date) = {$year} AND MONTH(TS.Date) = {$month}
				LEFT JOIN MonthlyPremiumPercent MPP ON MPP.WD_ID = WD.WD_ID AND MPP.Year = {$year} AND MPP.Month = {$month}
				WHERE WD.tariff IS NOT NULL
				GROUP BY WD.WD_ID
				HAVING act = 1 OR Hours > 0
				ORDER BY WD.Type, WD.Name
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr><td class='worker' val='{$row["WD_ID"]}' deftariff='{$row["deftariff"]}'><span class='nowrap'><a href='/paylog.php?worker={$row["WD_ID"]}'>{$row["Name"]}</a></span>";

				// Получаем список часов по работнику за месяц
				$query = "
					SELECT DAY(Date) Day
						,start1
						,end1
						,start2
						,end2
						,Hours
						,FLOOR(Hours) whole
						,(Hours - FLOOR(Hours)) * 4 frac
						,Tariff
					FROM TimeSheet
					WHERE YEAR(Date) = {$year} AND MONTH(Date) = {$month} AND WD_ID = {$row["WD_ID"]}
				";
				$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

				$sigmahours = 0; // Сумма отработанных часов по работнику
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
						echo "
							<td style='overflow: visible; padding: 0px;' class='tscell nowrap' date='{$date}' start1='{$subrow["start1"]}' end1='{$subrow["end1"]}' start2='{$subrow["start2"]}' end2='{$subrow["end2"]}' hours='{$subrow["Hours"]}' tariff='{$subrow["Tariff"]}' title='Тариф: {$subrow["Tariff"]}р.'>
								<span>{$subrow["whole"]}".($subrow["frac"] == 1 ? "&frac14;" : ($subrow["frac"] == 2 ? "&frac12;" : ($subrow["frac"] == 3 ? "&frac34;" : "")))."</span>
								<div style='background-color: #e78f08; left: ".($subrow["start1"]/60/24*100)."%; width: ".(($subrow["end1"] - $subrow["start1"])/60/24*100)."%; position: absolute; top: 16px; opacity: .5; height: 13px;'></div>
								<div style='background-color: #e78f08; left: ".($subrow["start2"]/60/24*100)."%; width: ".(($subrow["end2"] - $subrow["start2"])/60/24*100)."%; position: absolute; top: 16px; opacity: .5; height: 13px;'></div>
							</td>
						";
						$sigmahours = $sigmahours + $subrow["Hours"];
						$sigmamoney = $sigmamoney + ($subrow["Hours"] * $subrow["Tariff"]);
						if( $subrow = mysqli_fetch_array($subres) ) {
							$day = $subrow["Day"];
						}
					}
					else {
						echo "<td class='tscell' date='{$date}'><span></span></td>";
					}
					$i++;
				}
				$percent = $row["ManPercent"] == '' ? $premium_percent : $row["ManPercent"];
				if ($sigmahours >= $NormHours or $row["DNHcheck"] == 'checked') {
					$premium = round($sigmamoney * $percent / 100);
					$green = "style='color: #191;'";
				}
				else {
					$premium = 0;
					$green = '';
				}
				$sigmamoney = round($sigmamoney);
				$premium = round($premium);
				$total = $sigmamoney + $premium;
				$whole = intval($sigmahours);
				$frac = ($sigmahours - intval($sigmahours)) * 4;

				echo "<td class='txtright' {$green}>{$whole}".($frac == 1 ? "&frac14;" : ($frac == 2 ? "&frac12;" : ($frac == 3 ? "&frac34;" : "")))."</td>";	// Сумма часов
				echo "<td class='txtright'>{$sigmamoney}</td>";								// Сумма денег
				echo "<td class='txtright'>{$premium_percent}%</td>";					// Процент
				echo "<td><input type='number' name='MP{$row["WD_ID"]}' value='{$row["ManPercent"]}' min='0' max='100' style='width: 100%;'></td>";// Свой процент
				echo "<td><input type='checkbox' name='DNH{$row["WD_ID"]}' {$row["DNHcheck"]} value='1'></td>";	// Не учитывать норматив
				echo "<td><button class='button edit_pay txtright' location='{$location}' title='Начислить премию' style='width: 100%;' worker='{$row["WD_ID"]}' comment='Премия за {$MONTHS[$month]} {$year} {$percent}%' pay='{$premium}'>{$premium}</button></td>";						// Премия
				echo "<td class='txtright'>{$total}</td>";								// Премия + Сумма
				echo "</tr>";
			}
		?>
		</form>
	</tbody>
	<thead>
		<tr class="nowrap">
			<th>Работник</th>
			<?
				$i = 1;
				while ($i <= $days) {
					$date = $year.'-'.$month.'-'.$i;
					$day_of_week = date('N', strtotime($date));	// День недели
					$day = date('d', strtotime($date));			// День месяца

					// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
					$t = 0;
					foreach( $data["days"]["day"] as $key=>$value ) {
						if( $value["@attributes"]["d"] == $month.".".$day) {
							$t = $value["@attributes"]["t"];
						}
					}

					if ( (($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) { // Выделяем цветом выходные дни
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
		<form method="post" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
			<fieldset>
				<input type="hidden" name="date">
				<input type="hidden" name="worker">

				<div>
					<label>Интервал №1:</label>
					С <span id="start1" style="width: 45px; font-weight:bold;"></span>
					до <span id="end1" style="width: 45px; font-weight:bold;"></span>
					<input type="hidden" name="start1">
					<input type="hidden" name="end1">
				</div>
				<div id="slider-range1"></div>
				<div style="display: flex; font-size: .8em; margin-top: -18px; text-align: center; margin-left: -22px; width:108%;">
					<span style="width: 8%; font-weight: bold;">|<br>00:00</span>
					<span style="width: 8%;">|<br>03:00</span>
					<span style="width: 8%;">|<br>06:00</span>
					<span style="width: 8%;">|<br>09:00</span>
					<span style="width: 8%;">|<br>12:00</span>
					<span style="width: 8%;">|<br>15:00</span>
					<span style="width: 8%;">|<br>18:00</span>
					<span style="width: 8%;">|<br>21:00</span>
					<span style="width: 8%; font-weight: bold;">|<br>00:00</span>
					<span style="width: 8%;">|<br>03:00</span>
					<span style="width: 8%;">|<br>06:00</span>
					<span style="width: 8%;">|<br>09:00</span>
					<span style="width: 8%;">|<br>12:00</span>
				</div>

				<div>
					<label>Интервал №2:</label>
					С <span id="start2" style="width: 45px; font-weight:bold;"></span>
					до <span id="end2" style="width: 45px; font-weight:bold;"></span>
					<input type="hidden" name="start2">
					<input type="hidden" name="end2">
				</div>
				<div id="slider-range2"></div>
				<div style="display: flex; font-size: .8em; margin-top: -18px; text-align: center; margin-left: -22px; width:108%;">
					<span style="width: 8%; font-weight: bold;">|<br>00:00</span>
					<span style="width: 8%;">|<br>03:00</span>
					<span style="width: 8%;">|<br>06:00</span>
					<span style="width: 8%;">|<br>09:00</span>
					<span style="width: 8%;">|<br>12:00</span>
					<span style="width: 8%;">|<br>15:00</span>
					<span style="width: 8%;">|<br>18:00</span>
					<span style="width: 8%;">|<br>21:00</span>
					<span style="width: 8%; font-weight: bold;">|<br>00:00</span>
					<span style="width: 8%;">|<br>03:00</span>
					<span style="width: 8%;">|<br>06:00</span>
					<span style="width: 8%;">|<br>09:00</span>
					<span style="width: 8%;">|<br>12:00</span>
				</div>

				<div>
					<label>Часы:</label>
					<input required type='number' name='hours' step='0.25' min="0" max="24">
				</div>
				<div>
					<label>Тариф:</label>
					<input required type='number' name='tariff' min="0" max="9999">
				</div>
			</fieldset>
			<div>
				<hr>
				<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
			</div>
		</form>
	</div>

<script>
	$(document).ready(function(){

		// Подсвечивание столбцов таблицы
		$('#timesheet').columnHover({eachCell:true, hoverClass:'hover', ignoreCols: [1]});

		// Инициализация слайдеров
		$( "#slider-range1" ).slider({
			range: true,
			min: 0,
			max: 2160,
			step: 15,
			slide: function( event, ui ) {
				minutes = ui.values[ 0 ] % 60;
				hours = (ui.values[ 0 ] - minutes) / 60;
				hours = hours % 24;
				if( String(minutes).length < 2 ) {minutes = "0" + minutes;}
				if( String(hours).length < 2 ) {hours = "0" + hours;}
				$( "#start1" ).text(hours+":"+minutes);
				minutes = ui.values[ 1 ] % 60;
				hours = (ui.values[ 1 ] - minutes) / 60;
				hours = hours % 24;
				if( String(minutes).length < 2 ) {minutes = "0" + minutes;}
				if( String(hours).length < 2 ) {hours = "0" + hours;}
				$( "#end1" ).text(hours+":"+minutes);
				$('#dayworklog input[name="start1"]').val(ui.values[ 0 ]);
				$('#dayworklog input[name="end1"]').val(ui.values[ 1 ]);
				start2 = $('#dayworklog input[name="start2"]').val();
				end2 = $('#dayworklog input[name="end2"]').val();
				$('#dayworklog input[name="hours"]').val(((ui.values[ 1 ] - ui.values[ 0 ]) + (end2 - start2)) / 60);
			},
			change: function( event, ui ) {
				minutes = ui.values[ 0 ] % 60;
				hours = (ui.values[ 0 ] - minutes) / 60;
				hours = hours % 24;
				if( String(minutes).length < 2 ) {minutes = "0" + minutes;}
				if( String(hours).length < 2 ) {hours = "0" + hours;}
				$( "#start1" ).text(hours+":"+minutes);
				minutes = ui.values[ 1 ] % 60;
				hours = (ui.values[ 1 ] - minutes) / 60;
				hours = hours % 24;
				if( String(minutes).length < 2 ) {minutes = "0" + minutes;}
				if( String(hours).length < 2 ) {hours = "0" + hours;}
				$( "#end1" ).text(hours+":"+minutes);
				$('#dayworklog input[name="start1"]').val(ui.values[ 0 ]);
				$('#dayworklog input[name="end1"]').val(ui.values[ 1 ]);
				start2 = $('#dayworklog input[name="start2"]').val();
				end2 = $('#dayworklog input[name="end2"]').val();
				$('#dayworklog input[name="hours"]').val(((ui.values[ 1 ] - ui.values[ 0 ]) + (end2 - start2)) / 60);
			}
		});
		$( "#slider-range2" ).slider({
			range: true,
			min: 0,
			max: 2160,
			step: 15,
			slide: function( event, ui ) {
				minutes = ui.values[ 0 ] % 60;
				hours = (ui.values[ 0 ] - minutes) / 60;
				hours = hours % 24;
				if( String(minutes).length < 2 ) {minutes = "0" + minutes;}
				if( String(hours).length < 2 ) {hours = "0" + hours;}
				$( "#start2" ).text(hours+":"+minutes);
				minutes = ui.values[ 1 ] % 60;
				hours = (ui.values[ 1 ] - minutes) / 60;
				hours = hours % 24;
				if( String(minutes).length < 2 ) {minutes = "0" + minutes;}
				if( String(hours).length < 2 ) {hours = "0" + hours;}
				$( "#end2" ).text(hours+":"+minutes);
				$('#dayworklog input[name="start2"]').val(ui.values[ 0 ]);
				$('#dayworklog input[name="end2"]').val(ui.values[ 1 ]);
				start1 = $('#dayworklog input[name="start1"]').val();
				end1 = $('#dayworklog input[name="end1"]').val();
				$('#dayworklog input[name="hours"]').val(((ui.values[ 1 ] - ui.values[ 0 ]) + (end1 - start1)) / 60);
			},
			change: function( event, ui ) {
				minutes = ui.values[ 0 ] % 60;
				hours = (ui.values[ 0 ] - minutes) / 60;
				hours = hours % 24;
				if( String(minutes).length < 2 ) {minutes = "0" + minutes;}
				if( String(hours).length < 2 ) {hours = "0" + hours;}
				$( "#start2" ).text(hours+":"+minutes);
				minutes = ui.values[ 1 ] % 60;
				hours = (ui.values[ 1 ] - minutes) / 60;
				hours = hours % 24;
				if( String(minutes).length < 2 ) {minutes = "0" + minutes;}
				if( String(hours).length < 2 ) {hours = "0" + hours;}
				$( "#end2" ).text(hours+":"+minutes);
				$('#dayworklog input[name="start2"]').val(ui.values[ 0 ]);
				$('#dayworklog input[name="end2"]').val(ui.values[ 1 ]);
				start1 = $('#dayworklog input[name="start1"]').val();
				end1 = $('#dayworklog input[name="end1"]').val();
				$('#dayworklog input[name="hours"]').val(((ui.values[ 1 ] - ui.values[ 0 ]) + (end1 - start1)) / 60);
			}
		});

		// Форма добавления часов
		$('.tscell').click(function() {
			var workername = $(this).parents('tr').find('.worker a ').html();
			var date = $(this).attr('date');
			var worker = $(this).parents('tr').find('.worker').attr('val');
			var deftariff = $(this).parents('tr').find('.worker').attr('deftariff');

			if( $(this).attr('start1') != $(this).attr('end1') ) {
				var start1 = $(this).attr('start1');
				var end1 = $(this).attr('end1');
			}
			else {
				var start1 = 720;
				var end1 = 720;
			}

			if( $(this).attr('start2') != $(this).attr('end2') ) {
				var start2 = $(this).attr('start2');
				var end2 = $(this).attr('end2');
			}
			else {
				var start2 = 720;
				var end2 = 720;
			}

			var hours = $(this).attr('hours');
			var tariff = $(this).attr('tariff');

			$('#dayworklog input[name="date"]').val(date);
			$('#dayworklog input[name="worker"]').val(worker);

			// Заполнение
			if( hours > 0 ) // Редактирование
			{
				$( "#slider-range1" ).slider( "option", "values", [ start1, end1 ] );
				$( "#slider-range2" ).slider( "option", "values", [ start2, end2 ] );

				if( start1 != end1 || start2 != end2 ) {
					$('#dayworklog input[name="hours"]').attr('required', false);
					$('#dayworklog input[name="hours"]').attr('readonly', true);
				}
				else {
					$('#dayworklog input[name="hours"]').val(hours);
					$('#dayworklog input[name="hours"]').attr('required', true);
					$('#dayworklog input[name="hours"]').attr('readonly', false);
				}

				$('#dayworklog input[name="tariff"]').val(tariff);
			}
			else { // Добавление
				$('#dayworklog input[name="hours"]').attr('required', false);
				$('#dayworklog input[name="hours"]').attr('readonly', true);
				$('#dayworklog input[name="tariff"]').val(deftariff);
				$( "#slider-range1" ).slider( "option", "values", [ 720, 720 ] );
				$( "#slider-range2" ).slider( "option", "values", [ 720, 720 ] );
			}

			// Вызов формы
			$('#dayworklog').dialog({
				resizable: false,
				title:		workername+' '+date,
				width:		600,
				modal:		true,
				resizable:	false,
				closeText:	'Закрыть'
			});

			return false;
		});

		// Когда сдвинулся слайдер интервала - инпут часов readonly
		$('#slider-range1, #slider-range2').on("slide", function() {
			$('#dayworklog input[name="hours"]').attr('required', false);
			$('#dayworklog input[name="hours"]').attr('readonly', true);
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

<?
	include "footer.php";
?>
