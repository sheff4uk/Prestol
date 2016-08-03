<?
//	session_start();
	include "config.php";

	$datediff = 60; // Максимальный период отображения данных

	$location = $_SERVER['REQUEST_URI'];

	$year = date("Y");
	$month = date("n");
	$lastyear = date("Y",strtotime("-1 months"));
	$lastmonth = date("n",strtotime("-1 months"));

	$MONTHS = array(1=>'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');

	$title = 'Платежи';
	include "header.php";
?>
	<p>
		<button class='edit_pay' sign='' <?=isset($_GET["worker"]) ? "worker='{$_GET["worker"]}'" : "" ?> date='<?= date("d.m.Y") ?>' location='<?=$location?>'>Начислить</button>
		<button class='edit_pay' sign='-' <?=isset($_GET["worker"]) ? "worker='{$_GET["worker"]}'" : "" ?> date='<?= date("d.m.Y") ?>' location='<?=$location?>'>Выдать</button>
	</p>

	<? include "form_addpay.php"; ?>

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
			}
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
			}

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

	</div>

	<div class="log-pay halfblock">
		<h1>Движение денег</h1>
		<table>
			<thead>
			<tr>
				<th>Дата</th>
				<th>Работник</th>
				<th>Начислено</th>
				<th>Выдано</th>
				<th>Примечание</th>
				<th>Действие</th>
			</tr>
			</thead>
			<tbody>

	<?
			$query = "SELECT PL.PL_ID
							,IFNULL(PL.Link, '') Link
							,DATE_FORMAT(PL.ManDate, '%d.%m.%Y') ManDate
							,WD.Name Worker
							,ABS(PL.Pay) Pay
							,PL.Comment
							,WD.WD_ID, IF(PL.Pay < 0, '-', '') Sign
						FROM PayLog PL
						LEFT JOIN WorkersData WD ON WD.WD_ID = PL.WD_ID
						WHERE DATEDIFF(NOW(), PL.ManDate) <= {$datediff} AND PL.Pay <> 0";
			if( isset($_GET["worker"]) ) {
				$query .= " AND PL.WD_ID = {$_GET["worker"]}";
			}
			$query .= " ORDER BY PL.ManDate DESC, PL.PL_ID DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				$format_pay = number_format($row["Pay"], 0, '', ' ');
				echo "<tr>";
				echo "<td>{$row["ManDate"]}</td>";
				echo "<td class='worker' val='{$row["WD_ID"]}'>{$row["Worker"]}</td>";
				if ($row["Sign"] == '-') {
					echo "<td></td>";
					echo "<td class='pay txtright nowrap' val='{$row["Pay"]}'>{$format_pay}</td>";
				}
				else {
					echo "<td class='pay txtright nowrap' val='{$row["Pay"]}'>{$format_pay}</td>";
					echo "<td></td>";
				}
				echo "<td class='comment'><pre>{$row["Comment"]}</pre></td>";
				echo "<td>";
				if ($row["Link"] == '') {
					echo "<a href='#' id='{$row["PL_ID"]}' sign='{$row["Sign"]}' worker='{$row["WD_ID"]}' date='{$row["ManDate"]}' {$row["ManDate"]} class='button edit_pay' location='{$location}' title='Редактировать платеж'><i class='fa fa-pencil fa-lg'></i></a>";
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
