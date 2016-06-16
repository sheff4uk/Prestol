<?
	session_start();
	include "config.php";

	$location = $_SERVER['REQUEST_URI'];

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
			$query = "SELECT WD_ID, Name FROM WorkersData WHERE Hourly = 1";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				echo "<tr><td>{$row["Name"]}</td>";

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
					if( $i == $day ) {
						echo "<td class='tscell' title='Тариф: {$subrow["Tariff"]}р. ({$subrow["Comment"]})'>{$subrow["Hours"]}</td>";
						$sigma = $sigma + $subrow["Hours"];
						if( $subrow = mysqli_fetch_array($subres) ) {
							$day = $subrow["Day"];
						}
					}
					else {
						echo "<td class='tscell'></td>";
					}
					$i++;
				}

				echo "<td>{$sigma}</td></tr>";
			}
		?>
	</tbody>
</table>

	<!-- Форма ворклог -->
	<div id='dayworklog' title='Дневной отчет' class="addproduct" style='display:none'>
		<form method="post">
			<fieldset>
				<div>
					<label>Часы:</label>
					<input required type='number' name='Hours' step='0.5'>
				</div>
				<div>
					<label>Тариф:</label>
					<input required type='number' name='Tariff'>
				</div>
				<div>
					<label>Примечание:</label>
					<textarea name='Comment' rows='4' cols='25'></textarea>
				</div>
			</fieldset>
			<div>
				<hr>
				<button type='submit' style='float: right;'>Сохранить</button>
			</div>
		</form>
	</div>

<script>
	$(document).ready(function(){
		$('#timesheet').columnHover({eachCell:true, hoverClass:'hover', ignoreCols: [1]});

		// Форма добавления часов
		$('.tscell').click(function() {
//			var id = $(this).attr('id');
//			var worker = $(this).parents('tr').find('.worker').attr('val');
//			var pay = $(this).parents('tr').find('.pay').attr('val');
//			var comment = $(this).parents('tr').find('.comment').html();
//
//			// Очистка диалога
//			$('#addpay input, #addpay select, #addpay textarea').val('');
//
//			// Заполнение
//			if( typeof id !== "undefined" )
//			{
//				$('#addpay select[name="Worker"]').val(worker);
//				$('#addpay input[name="Pay"]').val(pay);
//				$('#addpay textarea[name="Comment"]').val(comment);
//				$('#addpay input[name="id_date"]').val(id);
//			}

			// Вызов формы
			$('#dayworklog').dialog({
				width: 400,
				modal: true,
				show: 'blind',
				hide: 'explode',
			});
			return false;
		});
	});
</script>
