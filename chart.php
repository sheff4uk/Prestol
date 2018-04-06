<?
	include "config.php";

	$title = 'Понедельный график сдачи заказов';
	include "header.php";

	// Проверка прав на доступ к экрану
//	if( !in_array('chart', $Rights) ) {
//		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
//		die('Недостаточно прав для совершения операции');
//	}

//Средняя мощность производства за год
$query = "
	SELECT ROUND(SUM(ODD_ODB.Amount)/52) Amount
	FROM OrdersData OD
	JOIN (
		SELECT ODD.OD_ID, ODD.Amount
		FROM OrdersDataDetail ODD
		WHERE ODD.Del = 0
		UNION ALL
		SELECT ODB.OD_ID, ODB.Amount
		FROM OrdersDataBlank ODB
		WHERE ODB.Del = 0
	) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) <= 364
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$average_power = mysqli_result($res,0,'Amount');
$normal = "$average_power, $average_power, $average_power";

//Мощность производства за прошедшую неделю
$query = "
	SELECT IFNULL(SUM(ODD_ODB.Amount), 0) Amount
	FROM OrdersData OD
	JOIN (
		SELECT ODD.OD_ID, ODD.Amount
		FROM OrdersDataDetail ODD
		WHERE ODD.Del = 0
		UNION ALL
		SELECT ODB.OD_ID, ODB.Amount
		FROM OrdersDataBlank ODB
		WHERE ODB.Del = 0
	) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) <= 7
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$current_power = mysqli_result($res,0,'Amount');

// Вычисляем текущую на грузку на производство
$load = round(($current_power/$average_power)*100);

// Получаем последовательность недель для отчета и цвета
$query = "
	SELECT WEEK(OD.EndDate, 1) week
		,LEFT(YEARWEEK(OD.EndDate, 1), 4) year
		,YEARWEEK(OD.EndDate, 1) yearweek
		,'rgba(255, 99, 132, 1)' chairs_color
		,'rgba(54, 162, 235, 1)' tables_color
		,'rgba(75, 192, 192, 1)' others_color
	FROM OrdersData OD
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND (YEARWEEK(OD.EndDate, 1) = YEARWEEK(NOW(), 1) OR OD.EndDate > NOW())
	GROUP BY YEARWEEK(OD.EndDate, 1)
	ORDER BY YEARWEEK(OD.EndDate, 1)
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	// Получаем диапазон дат для недели
	$week_number = $row["week"];
	$year = $row["year"];

	$first_day = date('d.m', ($week_number - 1) * 7 * 86400 + strtotime('1/1/' . $year) - date('w', strtotime('1/1/' . $year)) * 86400 + 86400);
	$last_day = date('d.m', $week_number * 7 * 86400 + strtotime('1/1/' . $year) - date('w', strtotime('1/1/' . $year)) * 86400);

	$weeks_list .= ", '{$row["week"]} [{$first_day}-{$last_day}]'";
	$yearweek = $row["yearweek"];
	$chairs_color .= ", '{$row["chairs_color"]}'";
	$tables_color .= ", '{$row["tables_color"]}'";
	$others_color .= ", '{$row["others_color"]}'";
	$normal .= ", $average_power";

	// Получаем количество уже выполненных изделий в очередную неделю (из запланированных в эту неделю)
	$query = "
		SELECT IFNULL(SUM(ODD_ODB.Amount), 0) Amount
		FROM OrdersData OD
		JOIN (
			SELECT ODD.OD_ID, ODD.Amount
			FROM OrdersDataDetail ODD
			WHERE ODD.Del = 0
			UNION ALL
			SELECT ODB.OD_ID, ODB.Amount
			FROM OrdersDataBlank ODB
			WHERE ODB.Del = 0
		) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		WHERE OD.ReadyDate IS NOT NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$already_ready .= ", {$subrow["Amount"]}";
	}

	// Получаем план производства по СТУЛЬЯМ в очередную неделю
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
		JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 1
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$chairs_plan .= ", {$subrow["Amount"]}";
	}

	// Получаем план производства по СТОЛАМ в очередную неделю
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
		JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 2
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$tables_plan .= ", {$subrow["Amount"]}";
	}

	// Получаем план производства по ПРОЧЕМУ в очередную неделю
	$query = "
		SELECT IFNULL(SUM(ODB.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataBlank ODB ON ODB.OD_ID = OD.OD_ID AND ODB.Del = 0
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$others_plan .= ", {$subrow["Amount"]}";
	}
}

//Просроченные стулья
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
	JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 1
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_chairs = mysqli_result($res,0,'Amount');

//Просроченные столы
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
	JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 2
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_tables = mysqli_result($res,0,'Amount');

//Просроченное прочее
$query = "
	SELECT IFNULL(SUM(ODB.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataBlank ODB ON ODB.OD_ID = OD.OD_ID AND ODB.Del = 0
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_others = mysqli_result($res,0,'Amount');

//Выставочные СТУЛЬЯ
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
	JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 1
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_chairs = mysqli_result($res,0,'Amount');

//Выставочные СТОЛЫ
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
	JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 2
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_tables = mysqli_result($res,0,'Amount');

//Выставочное ПРОЧЕЕ
$query = "
	SELECT IFNULL(SUM(ODB.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataBlank ODB ON ODB.OD_ID = OD.OD_ID AND ODB.Del = 0
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_others = mysqli_result($res,0,'Amount');

//Отложенные СТУЛЬЯ
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
	JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 1
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_chairs = mysqli_result($res,0,'Amount');

//Отложенные СТОЛЫ
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
	JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID AND PM.PT_ID = 2
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_tables = mysqli_result($res,0,'Amount');

//Отложенное ПРОЧЕЕ
$query = "
	SELECT IFNULL(SUM(ODB.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataBlank ODB ON ODB.OD_ID = OD.OD_ID AND ODB.Del = 0
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_others = mysqli_result($res,0,'Amount');

?>
<h2>Загруженность производства: <font color="red"><?=$load?>%</font></h2>
<canvas id="myChart" width="400" height="130"></canvas>
<script>
	var barChartData = {
		labels: ["Отложены", "Выставка", "Просрок"<?=$weeks_list?>],
		datasets: [{
			type: 'line',
			label: 'Норма',
			fill: false,
			borderColor: 'rgba(255, 159, 64, 1)',
			backgroundColor: 'rgba(255, 159, 64, 1)',
			data: [<?=$normal?>]
		}, {
			type: 'bar',
			label: 'Стулья',
			backgroundColor: ['rgba(255, 99, 132, 0.5)', 'rgba(255, 99, 132, 0.5)', 'rgba(255, 99, 132, 0.5)'<?=$chairs_color?>],
			data: [<?=$hold_chairs?>, <?=$show_chairs?>, <?=$outdated_chairs?><?=$chairs_plan?>]
		}, {
			type: 'bar',
			label: 'Столы',
			backgroundColor: ['rgba(54, 162, 235, 0.5)', 'rgba(54, 162, 235, 0.5)', 'rgba(54, 162, 235, 0.5)'<?=$tables_color?>],
			data: [<?=$hold_tables?>, <?=$show_tables?>, <?=$outdated_tables?><?=$tables_plan?>]
		}, {
			type: 'bar',
			label: 'Прочее',
			backgroundColor: ['rgba(75, 192, 192, 0.5)', 'rgba(75, 192, 192, 0.5)', 'rgba(75, 192, 192, 0.5)'<?=$others_color?>],
			data: [<?=$hold_others?>, <?=$show_others?>, <?=$outdated_others?><?=$others_plan?>]
		}, {
			type: 'bar',
			label: 'Готовые',
			data: [0, 0, 0<?=$already_ready?>]
		}]
	};

var ctx = $("#myChart");
var myChart = new Chart(ctx, {
	type: 'bar',
	data: barChartData,
	options: {
		title:{
			display:true,
			text:"Понедельный график сдачи заказов"
		},
		tooltips: {
			mode: 'index',
			intersect: false
		},
		responsive: true,
		scales: {
			xAxes: [{
				stacked: true,
			}],
			yAxes: [{
				stacked: true
			}]
		}
	}
});
</script>

<?
	include "footer.php";
?>