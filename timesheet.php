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
	echo $days;
?>

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

