<?
//	session_start();
	include "config.php";

	$datediff = 60; // Максимальный период отображения данных

	$location = $_SERVER['REQUEST_URI'];

	$title = 'Платежи';
	include "header.php";
?>
	<p>
		<button class='edit_pay' sign='' <?=isset($_GET["worker"]) ? "worker='{$_GET["worker"]}'" : "" ?> location='<?=$location?>'>Начислить</button>
		<button class='edit_pay' sign='-' <?=isset($_GET["worker"]) ? "worker='{$_GET["worker"]}'" : "" ?> location='<?=$location?>'>Выдать</button>
	</p>

	<? include "form_addpay.php"; ?>

	<div class="halfblock">
		<h1>Баланс рабочих</h1>
		<table>
			<thead>
			<tr>
				<th>Работник</th>
				<th>Баланс</th>
				<th>Начислено</th>
				<th>Выдано</th>
			</tr>
			</thead>
			<tbody>
			<?
				// Баланс работников
				$query = "SELECT WD.WD_ID, WD.Name, IFNULL(SMP.Pay, 0) Sum, SMPM.PayIn, SMPM.PayOut
							FROM WorkersData WD
							LEFT JOIN (
								SELECT MPIO.WD_ID, SUM(MPIO.PayIn - MPIO.PayOut) Pay
								FROM MonthlyPayInOut MPIO
								GROUP BY MPIO.WD_ID
							) SMP ON SMP.WD_ID = WD.WD_ID
							LEFT JOIN (
								SELECT MPIO.WD_ID, MPIO.PayIn, MPIO.PayOut
								FROM MonthlyPayInOut MPIO
								WHERE Year = YEAR(NOW()) AND Month = MONTH(NOW())
								GROUP BY MPIO.WD_ID
							) SMPM ON SMPM.WD_ID = WD.WD_ID
							WHERE WD.IsActive = 1";
				if( isset($_GET["worker"]) ) {
					$query .= " AND WD.WD_ID = {$_GET["worker"]}";
				}
				$query .= " ORDER BY WD.Name";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					if( $row["Sum"] < 0 )
						$color = ' bg-red';
					else
						$color = '';
					$format_sum = number_format($row["Sum"], 0, '', ' ');
					$format_MPI = number_format($row["PayIn"], 0, '', ' ');
					$format_MPO = number_format($row["PayOut"], 0, '', ' ');
					echo "<tr>";
					echo "<td><a href='?worker={$row["WD_ID"]}'>{$row["Name"]}</a></td>";
					echo "<td class='txtright'><span class='{$color} nowrap'>{$format_sum}</span></td>";
					echo "<td class='txtright'><span nowrap'>{$format_MPI}</span></td>";
					echo "<td class='txtright'><span nowrap'>{$format_MPO}</span></td>";
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
				<th>Время</th>
				<th>Работник</th>
				<th>Начислено</th>
				<th>Выдано</th>
				<th>Примечание</th>
				<th>Действие</th>
			</tr>
			</thead>
			<tbody>

	<?
			$query = "SELECT PL.PL_ID, IFNULL(PL.Link, '') Link, DATE_FORMAT(DATE(PL.Date), '%d.%m.%Y') Date, TIME(PL.Date) Time, WD.Name Worker, ABS(PL.Pay) Pay, PL.Comment, WD.WD_ID, IF(PL.Pay < 0, '-', '') Sign
						FROM PayLog PL
						LEFT JOIN WorkersData WD ON WD.WD_ID = PL.WD_ID
						WHERE DATEDIFF(NOW(), PL.Date) <= {$datediff} AND PL.Pay <> 0";
			if( isset($_GET["worker"]) ) {
				$query .= " AND PL.WD_ID = {$_GET["worker"]}";
			}
			$query .= " ORDER BY PL.Date DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				$format_pay = number_format($row["Pay"], 0, '', ' ');
				echo "<tr>";
				echo "<td>{$row["Date"]}</td>";
				echo "<td>{$row["Time"]}</td>";
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
					echo "<a href='#' id='{$row["PL_ID"]}' sign='{$row["Sign"]}' worker='{$row["WD_ID"]}' class='button edit_pay' location='{$location}' title='Редактировать платеж'><i class='fa fa-pencil fa-lg'></i></a>";
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
