<?
	include "config.php";

	$title = 'Понедельный график сдачи наборов';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('chart', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

//Средняя мощность производства за год
$query = "
	SELECT IFNULL(ROUND(SUM(ODD.Amount)/52), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) <= 364
		AND OD.DelDate IS NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$average_power = mysqli_result($res,0,'Amount');
$normal = "$average_power, $average_power, $average_power";

//Мощность производства за прошлые 7 дней
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) BETWEEN 8 AND 14
		AND OD.DelDate IS NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$last_power_week = mysqli_result($res,0,'Amount');

//Мощность производства за текущие 7 дней
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) BETWEEN 1 AND 7
		AND OD.DelDate IS NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$current_power_week = mysqli_result($res,0,'Amount');

//Мощность производства за прошлые 28 дней
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) BETWEEN 29 AND 56
		AND OD.DelDate IS NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$last_power_month = mysqli_result($res,0,'Amount');

//Мощность производства за текущие 28 дней
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) BETWEEN 1 AND 28
		AND OD.DelDate IS NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$current_power_month = mysqli_result($res,0,'Amount');

//Мощность производства за прошлые 91 дней
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) BETWEEN 92 AND 182
		AND OD.DelDate IS NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$last_power_quarter = mysqli_result($res,0,'Amount');

//Мощность производства за текущие 91 дней
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	WHERE DATEDIFF(NOW(), OD.ReadyDate) BETWEEN 1 AND 91
		AND OD.DelDate IS NULL
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$current_power_quarter = mysqli_result($res,0,'Amount');

// Вычисляем текущую нагрузку на производство
$load_week = round(($current_power_week/$average_power)*100);
$load_month = round((($current_power_month/4)/$average_power)*100);
$load_quarter = round((($current_power_quarter/13)/$average_power)*100);

// Вычисляем относительное изменение периодов
$diff_week = round($current_power_week/$last_power_week*100) - 100;
$diff_month = round($current_power_month/$last_power_month*100) - 100;
$diff_quarter = round($current_power_quarter/$last_power_quarter*100) - 100;

$format_diff_week = ($last_power_week == 0 or $diff_week == 0) ? "<br>" : "<font title='По сравнению с предыдущим периодом' size='-1' color=".($diff_week > 0 ? "'#26a332'>+" : "'#e51616'>").$diff_week."&thinsp;%</font>";
$format_diff_month = ($last_power_month == 0 or $diff_month == 0) ? "<br>" : "<font title='По сравнению с предыдущим периодом' size='-1' color=".($diff_month > 0 ? "'#26a332'>+" : "'#e51616'>").$diff_month."&thinsp;%</font>";
$format_diff_quarter = ($last_power_quarter == 0 or $diff_quarter == 0) ? "<br>" : "<font title='По сравнению с предыдущим периодом' size='-1' color=".($diff_quarter > 0 ? "'#26a332'>+" : "'#e51616'>").$diff_quarter."&thinsp;%</font>";

?>
<table>
	<thead>
		<tr>
			<th></th>
			<th></th>
			<th>неделя</th>
			<th>месяц</th>
			<th>квартал</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td rowspan="2">Загруженность производства</td>
			<td>в процентах</td>
			<td class="txtright"><b><?=$load_week?>% <i class="fas fa-question-circle" title="Количество отгруженной продукции за последние 7 дней относительно среднегодового показателя"></i></b></td>
			<td class="txtright"><b><?=$load_month?>% <i class="fas fa-question-circle" title="Количество отгруженной продукции за последние 28 дней относительно среднегодового показателя"></i></b></td>
			<td class="txtright"><b><?=$load_quarter?>% <i class="fas fa-question-circle" title="Количество отгруженной продукции за последние 91 дней относительно среднегодового показателя"></i></b></td>
		</tr>
		<tr>
			<td>в единицах продукции</td>
			<td class="txtright"><b><?=$current_power_week?> <i class="fas fa-question-circle" title="Количество единиц отгруженной продукции за последние 7 дней"></i></b><br><?=$format_diff_week?></td>
			<td class="txtright"><b><?=$current_power_month?> <i class="fas fa-question-circle" title="Количество единиц отгруженной продукции за последние 28 дней"></i></b><br><?=$format_diff_month?></td>
			<td class="txtright"><b><?=$current_power_quarter?> <i class="fas fa-question-circle" title="Количество единиц отгруженной продукции за последние 91 дней"></i></b><br><?=$format_diff_quarter?></td>
		</tr>
	</tbody>
</table>
<?
// Меняем на русскую локаль
$query = "SET @@lc_time_names='ru_RU';";
mysqli_query( $mysqli, $query );

// Получаем последовательность недель для отчета и цвета
$query = "
	SELECT RIGHT(YEARWEEK(OD.EndDate, 1), 2) week
		,LEFT(YEARWEEK(OD.EndDate, 1), 4) year
		,YEARWEEK(OD.EndDate, 1) yearweek
		,DATE_FORMAT(adddate(OD.EndDate, INTERVAL 2-DAYOFWEEK(OD.EndDate) DAY), '%e%b') WeekStart
		,DATE_FORMAT(adddate(OD.EndDate, INTERVAL 8-DAYOFWEEK(OD.EndDate) DAY), '%e%b') WeekEnd
		,'rgba(255, 99, 132, 0.5)' chairs_color
		,'rgba(54, 162, 235, 0.5)' tables_color
		,'rgba(75, 255, 192, 0.5)' others_color
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
//	$week_number = $row["week"];
//	$year = $row["year"];

//	$first_day = date('d.m', ($week_number - 1) * 7 * 86400 + strtotime('1/1/' . $year) - date('w', strtotime('1/1/' . $year)) * 86400 + 86400);
//	$last_day = date('d.m', $week_number * 7 * 86400 + strtotime('1/1/' . $year) - date('w', strtotime('1/1/' . $year)) * 86400);

	$weeks_list .= ", '{$row["week"]} [{$row["WeekStart"]}-{$row["WeekEnd"]}]'";
	$yearweek = $row["yearweek"];
	$chairs_color .= ", '{$row["chairs_color"]}'";
	$tables_color .= ", '{$row["tables_color"]}'";
	$others_color .= ", '{$row["others_color"]}'";
	$normal .= ", $average_power";

	// Получаем количество уже выполненных изделий в очередную неделю (из запланированных в эту неделю)
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
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

	// Получаем план производства по СТУЛЬЯМ розница
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
			AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 1
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$chairs_plan_retail .= ", {$subrow["Amount"]}";
	}

	// Получаем план производства по СТУЛЬЯМ опт
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
			AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 1
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$chairs_plan_opt .= ", {$subrow["Amount"]}";
	}

	// Получаем план производства по СТОЛАМ розница
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
			AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 2

	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$tables_plan_retail .= ", {$subrow["Amount"]}";
	}

	// Получаем план производства по СТОЛАМ опт
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
			AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 2
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$tables_plan_opt .= ", {$subrow["Amount"]}";
	}

	// Получаем план производства по ПРОЧЕМУ розница
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
			AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 0
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$others_plan_retail .= ", {$subrow["Amount"]}";
	}

	// Получаем план производства по ПРОЧЕМУ опт
	$query = "
		SELECT IFNULL(SUM(ODD.Amount), 0) Amount
		FROM OrdersData OD
		JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
		JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
		LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
		WHERE OD.ReadyDate IS NULL
			AND OD.DelDate IS NULL
			AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
			AND OD.EndDate IS NOT NULL
			AND YEARWEEK(OD.EndDate, 1) = '$yearweek'
			AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 0
	";
	$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $subrow = mysqli_fetch_array($subres) ) {
		$others_plan_opt .= ", {$subrow["Amount"]}";
	}
}

//Просроченные стулья розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_chairs_retail = mysqli_result($res,0,'Amount');

//Просроченные стулья опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_chairs_opt = mysqli_result($res,0,'Amount');

//Просроченные столы розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 2
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_tables_retail = mysqli_result($res,0,'Amount');

//Просроченные столы опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 2
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_tables_opt = mysqli_result($res,0,'Amount');

//Просроченное прочее розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 0
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_others_retail = mysqli_result($res,0,'Amount');

//Просроченное прочее опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND OD.EndDate IS NOT NULL
		AND OD.EndDate < NOW()
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 0
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$outdated_others_opt = mysqli_result($res,0,'Amount');

//Выставочные СТУЛЬЯ розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_chairs_retail = mysqli_result($res,0,'Amount');

//Выставочные СТУЛЬЯ опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_chairs_opt = mysqli_result($res,0,'Amount');

//Выставочные СТОЛЫ розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 2
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_tables_retail = mysqli_result($res,0,'Amount');

//Выставочные СТОЛЫ опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 2
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_tables_opt = mysqli_result($res,0,'Amount');

//Выставочное ПРОЧЕЕ розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 0
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_others_retail = mysqli_result($res,0,'Amount');

//Выставочное ПРОЧЕЕ опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 0
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$show_others_opt = mysqli_result($res,0,'Amount');

//Отложенные СТУЛЬЯ розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_chairs_retail = mysqli_result($res,0,'Amount');

//Отложенные СТУЛЬЯ опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 1
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_chairs_opt = mysqli_result($res,0,'Amount');

//Отложенные СТОЛЫ розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 2
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_tables_retail = mysqli_result($res,0,'Amount');

//Отложенные СТОЛЫ опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 2
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_tables_opt = mysqli_result($res,0,'Amount');

//Отложенное ПРОЧЕЕ розница
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 1
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 0
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_others_retail = mysqli_result($res,0,'Amount');

//Отложенное ПРОЧЕЕ опт
$query = "
	SELECT IFNULL(SUM(ODD.Amount), 0) Amount
	FROM OrdersData OD
	JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID
	JOIN Shops SH ON SH.SH_ID = OD.SH_ID AND SH.retail = 0
	LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
	WHERE OD.ReadyDate IS NULL
		AND OD.DelDate IS NULL
		AND OD.EndDate IS NULL
		AND NOT (SH.KA_ID IS NULL AND OD.StartDate IS NULL)
		AND IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) = 0
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
$hold_others_opt = mysqli_result($res,0,'Amount');

?>
<canvas id="myChart" width="400" height="130"></canvas>
<script>
	var barChartData = {
		labels: ["Отложены", "Выставка", "Просрок"<?=$weeks_list?>],
		datasets: [{
			type: 'line',
			label: 'Средний темп',
			fill: false,
			backgroundColor: 'rgba(255, 159, 64, 1)',
			borderWidth: 4,
			pointRadius: 0,
			borderColor: 'rgba(255, 159, 64, 1)',
			data: [<?=$normal?>]
		}, {
			type: 'bar',
			label: 'Стулья розн.',
			backgroundColor: ['rgba(255, 99, 132, 0.5)', 'rgba(255, 99, 132, 0.5)', 'rgba(255, 99, 132, 0.5)'<?=$chairs_color?>],
			borderWidth: 2,
			borderColor: 'rgba(255, 99, 132, 1)',
			data: [<?=$hold_chairs_retail?>, <?=$show_chairs_retail?>, <?=$outdated_chairs_retail?><?=$chairs_plan_retail?>]
		}, {
			type: 'bar',
			label: 'Стулья опт',
			backgroundColor: ['rgba(255, 99, 132, 0.5)', 'rgba(255, 99, 132, 0.5)', 'rgba(255, 99, 132, 0.5)'<?=$chairs_color?>],
			data: [<?=$hold_chairs_opt?>, <?=$show_chairs_opt?>, <?=$outdated_chairs_opt?><?=$chairs_plan_opt?>]
		}, {
			type: 'bar',
			label: 'Столы розн.',
			backgroundColor: ['rgba(54, 162, 235, 0.5)', 'rgba(54, 162, 235, 0.5)', 'rgba(54, 162, 235, 0.5)'<?=$tables_color?>],
			borderWidth: 2,
			borderColor: 'rgba(54, 162, 235, 1)',
			data: [<?=$hold_tables_retail?>, <?=$show_tables_retail?>, <?=$outdated_tables_retail?><?=$tables_plan_retail?>]
		}, {
			type: 'bar',
			label: 'Столы опт',
			backgroundColor: ['rgba(54, 162, 235, 0.5)', 'rgba(54, 162, 235, 0.5)', 'rgba(54, 162, 235, 0.5)'<?=$tables_color?>],
			data: [<?=$hold_tables_opt?>, <?=$show_tables_opt?>, <?=$outdated_tables_opt?><?=$tables_plan_opt?>]
		}, {
			type: 'bar',
			label: 'Прочее розн.',
			backgroundColor: ['rgba(75, 255, 192, 0.5)', 'rgba(75, 255, 192, 0.5)', 'rgba(75, 255, 192, 0.5)'<?=$others_color?>],
			borderWidth: 2,
			borderColor: 'rgba(75, 255, 192, 1)',
			data: [<?=$hold_others_retail?>, <?=$show_others_retail?>, <?=$outdated_others_retail?><?=$others_plan_retail?>]
		}, {
			type: 'bar',
			label: 'Прочее опт',
			backgroundColor: ['rgba(75, 255, 192, 0.5)', 'rgba(75, 255, 192, 0.5)', 'rgba(75, 255, 192, 0.5)'<?=$others_color?>],
			data: [<?=$hold_others_opt?>, <?=$show_others_opt?>, <?=$outdated_others_opt?><?=$others_plan_opt?>]
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
			text:"Понедельный график сдачи наборов"
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
