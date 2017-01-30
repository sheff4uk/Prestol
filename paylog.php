<?
//	session_start();
	include "config.php";
	$title = 'Платежи';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_paylog', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$datediff = 60; // Максимальный период отображения данных

	$location = $_SERVER['REQUEST_URI'];

	$year = date("Y");
	$month = date("n");
//	$lastyear = date("Y",strtotime("-1 months"));
//	$lastmonth = date("n",strtotime("-1 months"));
	$lastyear = $month == 1 ? $year - 1 : $year;
	$lastmonth = $month == 1 ? 12 : $month - 1;

?>
	<p>
		<button class='edit_pay' sign='' <?=isset($_GET["worker"]) ? "worker='{$_GET["worker"]}'" : "" ?> date='<?= date("d.m.Y") ?>' location='<?=$location?>'>Начислить</button>
		<button class='edit_pay' sign='-' <?=isset($_GET["worker"]) ? "worker='{$_GET["worker"]}'" : "" ?> date='<?= date("d.m.Y") ?>' location='<?=$location?>'>Выдать</button>
	</p>

	<? include "form_addpay.php"; ?>
	<? include "forms.php"; ?>

	<div class="halfblock">
		<?
			// Баланс сдельных работников
			$query = "SELECT WD.WD_ID
							,WD.Name
							,SUM(IFNULL(MPIO.PayIn, 0) - IFNULL(MPIO.PayOut, 0)) Sum
							,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayIn, 0), 0)) PayIn
							,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayOut, 0), 0)) PayOut
							,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayIn, 0), 0)) LastPayIn
							,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayOut, 0), 0)) LastPayOut
					  FROM WorkersData WD
					  LEFT JOIN MonthlyPayInOut MPIO ON MPIO.WD_ID = WD.WD_ID
					  WHERE WD.IsActive = 1 AND WD.Type = 1";
			if( isset($_GET["worker"]) ) {
				$query .= " AND WD.WD_ID = {$_GET["worker"]}";
			}
			$query .= " GROUP BY WD.WD_ID ORDER BY WD.Name";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			if( mysqli_num_rows($res) ) {
				?>
				<h1>Баланс сдельных работников</h1>
				<table>
					<thead>
					<tr>
						<th rowspan="2">Работник</th>
						<th rowspan="2">Баланс</th>
						<th colspan="2" class="nowrap"><?=$MONTHS[$month]?> <?=$year?></th>
						<th colspan="2" class="nowrap"><?=$MONTHS[$lastmonth]?> <?=$lastyear?></th>
						<th rowspan="2">Среднегодовая<br>выдача</th>
					</tr>
					<tr>
						<th>Начислено</th>
						<th>Выдано</th>
						<th>Начислено</th>
						<th>Выдано</th>
					<tr>
					</tr>
					</thead>
					<tbody>
				<?
				$total_sum = 0;
				$total_MPI = 0;
				$total_MPO = 0;
				$total_LMPI = 0;
				$total_LMPO = 0;
				$total_avg_pay_out = 0;

				while( $row = mysqli_fetch_array($res) )
				{
					// Узнаем среднегодовую получку
					$query = "SELECT ROUND(AVG(PayOut)) avg_pay_out
								FROM MonthlyPayInOut
								WHERE WD_ID = {$row["WD_ID"]} AND NOT ( Year = YEAR(NOW()) AND Month = MONTH(NOW()) ) AND DATEDIFF(NOW(), DATE( CONCAT( Year, '-', Month, '-01' ) )) <= 365 AND PayOut > 0";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$avg_pay_out = mysqli_result($subres, 0, 'avg_pay_out');

					if( $row["Sum"] < 0 )
						$color = ' bg-red';
					else
						$color = '';
					$format_sum = number_format($row["Sum"], 0, '', ' ');
					$format_MPI = number_format($row["PayIn"], 0, '', ' ');
					$format_MPO = number_format($row["PayOut"], 0, '', ' ');
					$format_LMPI = number_format($row["LastPayIn"], 0, '', ' ');
					$format_LMPO = number_format($row["LastPayOut"], 0, '', ' ');
					$format_avg_pay_out = number_format($avg_pay_out, 0, '', ' ');
					echo "<tr>";
					echo "<td><a href='?worker={$row["WD_ID"]}'>{$row["Name"]}</a></td>";
					echo "<td class='txtright'><span class='{$color} nowrap'>{$format_sum}</span></td>";
					echo "<td class='txtright'><span nowrap'>{$format_MPI}</span></td>";
					echo "<td class='txtright'><span nowrap'>{$format_MPO}</span></td>";
					echo "<td class='txtright'><span nowrap'>{$format_LMPI}</span></td>";
					echo "<td class='txtright'><span nowrap'>{$format_LMPO}</span></td>";
					echo "<td class='txtright'><span nowrap'>{$format_avg_pay_out}</span></td>";
					echo "</tr>";
					$total_sum = $total_sum + $row["Sum"];
					$total_MPI = $total_MPI + $row["PayIn"];
					$total_MPO = $total_MPO + $row["PayOut"];
					$total_LMPI = $total_LMPI + $row["LastPayIn"];
					$total_LMPO = $total_LMPO + $row["LastPayOut"];
					$total_avg_pay_out = $total_avg_pay_out + $avg_pay_out;
				}
				$total_sum = number_format($total_sum, 0, '', ' ');
				$total_MPI = number_format($total_MPI, 0, '', ' ');
				$total_MPO = number_format($total_MPO, 0, '', ' ');
				$total_LMPI = number_format($total_LMPI, 0, '', ' ');
				$total_LMPO = number_format($total_LMPO, 0, '', ' ');
				$total_avg_pay_out = number_format($total_avg_pay_out, 0, '', ' ');

				if( !isset($_GET["worker"]) ) {
					echo "<tr>";
					echo "<td class='txtright'><b>Сумма:</b></td>";
					echo "<td class='txtright'><b>{$total_sum}</b></td>";
					echo "<td class='txtright'><b>{$total_MPI}</b></td>";
					echo "<td class='txtright'><b>{$total_MPO}</b></td>";
					echo "<td class='txtright'><b>{$total_LMPI}</b></td>";
					echo "<td class='txtright'><b>{$total_LMPO}</b></td>";
					echo "<td class='txtright'><b>{$total_avg_pay_out}</b></td>";
					echo "</tr>";
				}
				?>
				</tbody>
			</table>

			<?
			}

			// Баланс повременных работников
			$query = "SELECT WD.WD_ID
							,WD.Name
							,SUM(IFNULL(MPIO.PayIn, 0) - IFNULL(MPIO.PayOut, 0)) Sum
							,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayIn, 0), 0)) PayIn
							,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayOut, 0), 0)) PayOut
							,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayIn, 0), 0)) LastPayIn
							,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayOut, 0), 0)) LastPayOut
					  FROM WorkersData WD
					  LEFT JOIN MonthlyPayInOut MPIO ON MPIO.WD_ID = WD.WD_ID
					  WHERE WD.IsActive = 1 AND WD.Type = 2";

			if( isset($_GET["worker"]) ) {
				$query .= " AND WD.WD_ID = {$_GET["worker"]}";
			}
			$query .= " GROUP BY WD.WD_ID ORDER BY WD.Name";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			if( mysqli_num_rows($res) ) {
				?>
				<h1>Баланс повременных работников</h1>
				<table>
					<thead>
					<tr>
						<th rowspan="2">Работник</th>
						<th rowspan="2">Баланс</th>
						<th colspan="2" class="nowrap"><?=$MONTHS[$month]?> <?=$year?></th>
						<th colspan="2" class="nowrap"><?=$MONTHS[$lastmonth]?> <?=$lastyear?></th>
					</tr>
						<th>Начислено</th>
						<th>Выдано</th>
						<th>Начислено</th>
						<th>Выдано</th>
					<tr>
					</tr>
					</thead>
					<tbody>
					<?
					$total_sum = 0;
					$total_MPI = 0;
					$total_MPO = 0;
					$total_LMPI = 0;
					$total_LMPO = 0;

					while( $row = mysqli_fetch_array($res) )
					{
						if( $row["Sum"] < 0 )
							$color = ' bg-red';
						else
							$color = '';
						$format_sum = number_format($row["Sum"], 0, '', ' ');
						$format_MPI = number_format($row["PayIn"], 0, '', ' ');
						$format_MPO = number_format($row["PayOut"], 0, '', ' ');
						$format_LMPI = number_format($row["LastPayIn"], 0, '', ' ');
						$format_LMPO = number_format($row["LastPayOut"], 0, '', ' ');
						echo "<tr>";
						echo "<td><a href='?worker={$row["WD_ID"]}'>{$row["Name"]}</a></td>";
						echo "<td class='txtright'><span class='{$color} nowrap'>{$format_sum}</span></td>";
						echo "<td class='txtright'><span nowrap'>{$format_MPI}</span></td>";
						echo "<td class='txtright'><span nowrap'>{$format_MPO}</span></td>";
						echo "<td class='txtright'><span nowrap'>{$format_LMPI}</span></td>";
						echo "<td class='txtright'><span nowrap'>{$format_LMPO}</span></td>";
						echo "</tr>";
						$total_sum = $total_sum + $row["Sum"];
						$total_MPI = $total_MPI + $row["PayIn"];
						$total_MPO = $total_MPO + $row["PayOut"];
						$total_LMPI = $total_LMPI + $row["LastPayIn"];
						$total_LMPO = $total_LMPO + $row["LastPayOut"];
					}
					$total_sum = number_format($total_sum, 0, '', ' ');
					$total_MPI = number_format($total_MPI, 0, '', ' ');
					$total_MPO = number_format($total_MPO, 0, '', ' ');
					$total_LMPI = number_format($total_LMPI, 0, '', ' ');
					$total_LMPO = number_format($total_LMPO, 0, '', ' ');

					if( !isset($_GET["worker"]) ) {
						echo "<tr>";
						echo "<td class='txtright'><b>Сумма:</b></td>";
						echo "<td class='txtright'><b>{$total_sum}</b></td>";
						echo "<td class='txtright'><b>{$total_MPI}</b></td>";
						echo "<td class='txtright'><b>{$total_MPO}</b></td>";
						echo "<td class='txtright'><b>{$total_LMPI}</b></td>";
						echo "<td class='txtright'><b>{$total_LMPO}</b></td>";
						echo "</tr>";
					}
				?>
				</tbody>
			</table>
			<?
			}
		// Баланс работников ИТР
		$query = "SELECT WD.WD_ID
						,WD.Name
						,SUM(IFNULL(MPIO.PayIn, 0) - IFNULL(MPIO.PayOut, 0)) Sum
						,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayIn, 0), 0)) PayIn
						,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayOut, 0), 0)) PayOut
						,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayIn, 0), 0)) LastPayIn
						,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayOut, 0), 0)) LastPayOut
				  FROM WorkersData WD
				  LEFT JOIN MonthlyPayInOut MPIO ON MPIO.WD_ID = WD.WD_ID
				  WHERE WD.IsActive = 1 AND WD.Type = 3";
			if( isset($_GET["worker"]) ) {
				$query .= " AND WD.WD_ID = {$_GET["worker"]}";
			}
			$query .= " GROUP BY WD.WD_ID ORDER BY WD.Name";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			if( mysqli_num_rows($res) ) {
				?>
				<h1>Баланс работников ИТР</h1>
				<table>
					<thead>
					<tr>
						<th rowspan="2">Работник</th>
						<th rowspan="2">Баланс</th>
						<th colspan="2" class="nowrap"><?=$MONTHS[$month]?> <?=$year?></th>
						<th colspan="2" class="nowrap"><?=$MONTHS[$lastmonth]?> <?=$lastyear?></th>
					</tr>
						<th>Начислено</th>
						<th>Выдано</th>
						<th>Начислено</th>
						<th>Выдано</th>
					<tr>
					</tr>
					</thead>
					<tbody>
					<?
					$total_sum = 0;
					$total_MPI = 0;
					$total_MPO = 0;
					$total_LMPI = 0;
					$total_LMPO = 0;

					while( $row = mysqli_fetch_array($res) )
					{
						if( $row["Sum"] < 0 )
							$color = ' bg-red';
						else
							$color = '';
						$format_sum = number_format($row["Sum"], 0, '', ' ');
						$format_MPI = number_format($row["PayIn"], 0, '', ' ');
						$format_MPO = number_format($row["PayOut"], 0, '', ' ');
						$format_LMPI = number_format($row["LastPayIn"], 0, '', ' ');
						$format_LMPO = number_format($row["LastPayOut"], 0, '', ' ');
						echo "<tr>";
						echo "<td><a href='?worker={$row["WD_ID"]}'>{$row["Name"]}</a></td>";
						echo "<td class='txtright'><span class='{$color} nowrap'>{$format_sum}</span></td>";
						echo "<td class='txtright'><span nowrap'>{$format_MPI}</span></td>";
						echo "<td class='txtright'><span nowrap'>{$format_MPO}</span></td>";
						echo "<td class='txtright'><span nowrap'>{$format_LMPI}</span></td>";
						echo "<td class='txtright'><span nowrap'>{$format_LMPO}</span></td>";
						echo "</tr>";
						$total_sum = $total_sum + $row["Sum"];
						$total_MPI = $total_MPI + $row["PayIn"];
						$total_MPO = $total_MPO + $row["PayOut"];
						$total_LMPI = $total_LMPI + $row["LastPayIn"];
						$total_LMPO = $total_LMPO + $row["LastPayOut"];
					}
					$total_sum = number_format($total_sum, 0, '', ' ');
					$total_MPI = number_format($total_MPI, 0, '', ' ');
					$total_MPO = number_format($total_MPO, 0, '', ' ');
					$total_LMPI = number_format($total_LMPI, 0, '', ' ');
					$total_LMPO = number_format($total_LMPO, 0, '', ' ');

					if( !isset($_GET["worker"]) ) {
						echo "<tr>";
						echo "<td class='txtright'><b>Сумма:</b></td>";
						echo "<td class='txtright'><b>{$total_sum}</b></td>";
						echo "<td class='txtright'><b>{$total_MPI}</b></td>";
						echo "<td class='txtright'><b>{$total_MPO}</b></td>";
						echo "<td class='txtright'><b>{$total_LMPI}</b></td>";
						echo "<td class='txtright'><b>{$total_LMPO}</b></td>";
						echo "</tr>";
					}
				?>
				</tbody>
			</table>
			<?
			}
		if( isset($_GET["worker"]) ) {
?>

		<h1>Журнал изменения баланса</h1>
		<table>
			<thead>
				<tr>
					<th>Дата</th>
					<th>Время</th>
					<th>Баланс</th>
				</tr>
			</thead>
			<tbody>
<?
			$query = "SELECT BL.Balance
							,DATE_FORMAT(DATE(BL.Date), '%d.%m.%Y') Date
							,TIME(BL.Date) Time
					  FROM BalanceLog BL
					  WHERE WD_ID = {$_GET["worker"]} AND DATEDIFF(NOW(), Date) <= {$datediff}
					  ORDER BY BL.Date DESC, BL.Balance DESC";

			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			while( $row = mysqli_fetch_array($res) ) {
				$format_balance = number_format($row["Balance"], 0, '', ' ');
				if( $row["Balance"] < 0 )
					$color = ' bg-red';
				else
					$color = '';
				echo "<tr>";
				echo "<td><span nowrap'>{$row["Date"]}</span></td>";
				echo "<td><span nowrap'>{$row["Time"]}</span></td>";
				echo "<td class='txtright'><span class='nowrap{$color}'>{$format_balance}</span></td>";
				echo "</tr>";
			}
?>
			</tbody>
		</table>
<?
		}
?>
	</div>

	<div class="log-pay halfblock">
		<h1>Движение денег</h1>
		<table class='main_table'>
			<thead>
			<tr>
				<th width='90'>Дата</th>
				<th width='30%'>Работник</th>
				<th width='60'>Начислено</th>
				<th width='60'>Выдано</th>
				<th width='70%'>Примечание</th>
				<th width='60'>Действие</th>
			</tr>
			</thead>
			<tbody>

	<?
			$query = "SELECT PL.PL_ID
							,IFNULL(PL.Link, '') Link
							,DATE_FORMAT(PL.ManDate, '%d.%m.%Y') ManDate
							,WD.Name Worker
							,ABS(PL.Pay) Pay
							,REPLACE(PL.Comment, '\r\n', '<br>') Comment
							,WD.WD_ID
							,IF(PL.Pay < 0, '-', '') Sign
							,IF(PL.Archive = 1, 'pl-archive', '') Archive
						FROM PayLog PL
						LEFT JOIN WorkersData WD ON WD.WD_ID = PL.WD_ID
						WHERE DATEDIFF(NOW(), PL.ManDate) <= {$datediff} AND PL.Pay <> 0";
			if( isset($_GET["worker"]) ) {
				$query .= " AND PL.WD_ID = {$_GET["worker"]}";
			}
			$query .= " ORDER BY PL.ManDate DESC, PL.Link, PL.PL_ID DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				$format_pay = number_format($row["Pay"], 0, '', ' ');
				echo "<tr class='{$row["Archive"]}' id='pl{$row["PL_ID"]}'>";
				echo "<td>{$row["ManDate"]}</td>";
				echo "<td class='worker' val='{$row["WD_ID"]}'><span><a href='?worker={$row["WD_ID"]}'>{$row["Worker"]}</a></span></td>";
				if ($row["Sign"] == '-') {
					echo "<td></td>";
					echo "<td class='pay txtright nowrap' val='{$row["Pay"]}'>{$format_pay}</td>";
				}
				else {
					echo "<td class='pay txtright nowrap' val='{$row["Pay"]}'>{$format_pay}</td>";
					echo "<td></td>";
				}
				echo "<td class='comment nowrap' style='z-index: 2;'><span>";
				// Если запись из этапов производства - выводим код заказа, узнаем статус принятия заказа
				if( strpos($row["Link"],"ODS") === 0 ) {
					$odd = substr($row["Link"], 4);
					$step = strstr($odd, '_');
					$step = substr($step, 1);
					$pos = strpos($odd, '_');
					$odd = substr($odd, 0, $pos);
					if( $step == '0' ) {
						$query = "SELECT IFNULL(OD.Code, 'Свободные') Code, IFNULL(OD.confirmed, 1) confirmed FROM OrdersDataBlank ODB LEFT JOIN OrdersData OD ON OD.OD_ID = ODB.OD_ID WHERE ODB.ODB_ID = {$odd}";
					}
					else {
						$query = "SELECT IFNULL(OD.Code, 'Свободные') Code, IFNULL(OD.confirmed, 1) confirmed FROM OrdersDataDetail ODD LEFT JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID WHERE ODD.ODD_ID = {$odd}";
					}
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$Code = mysqli_result($subres,0,'Code');
					$confirmed = mysqli_result($subres,0,'confirmed');
					echo "<b>|{$Code}|</b> ";
				}
				echo "{$row["Comment"]}</span></td>";
				echo "<td".( (strpos($row["Link"],"ODS") === 0 and $confirmed) ? " class='td_step step_confirmed'" : "" ).">";
				if ($row["Link"] == '') {
					echo "<a href='#' id='{$row["PL_ID"]}' sign='{$row["Sign"]}' worker='{$row["WD_ID"]}' date='{$row["ManDate"]}' {$row["ManDate"]} class='edit_pay' location='{$location}' title='Редактировать платеж'><i class='fa fa-pencil fa-lg'></i></a>";
				}
				if( strpos($row["Link"],"ODS") === 0 ) { // Если запись из этапов производства - редактируем
					if( $step == '0' ) {
						echo "<a href='#' odbid='{$odd}' plid='{$row["PL_ID"]}' class='".(in_array('step_update', $Rights) ? "edit_steps " : "")."' location='{$location}' title='Редактировать этапы'><i class='fa fa-pencil fa-lg'></i></a>";
					}
					else {
						echo "<a href='#' id='{$odd}' plid='{$row["PL_ID"]}' class='".(in_array('step_update', $Rights) ? "edit_steps " : "")."' location='{$location}' title='Редактировать этапы'><i class='fa fa-pencil fa-lg'></i></a>";
					}
				}
				echo "</td>";
				echo "</tr>";
			}
	?>
			</tbody>
		</table>
	</div>

</body>
</html>
