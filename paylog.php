<?
//	session_start();
	include "config.php";

	$datediff = 60; // Максимальный период отображения данных

	$location = $_SERVER['REQUEST_URI'];

	$title = 'Платежи';
	include "header.php";
?>
	<p>
		<button class='edit_pay' sign='' location='<?=$location?>'>Начислить</button>
		<button class='edit_pay' sign='-' location='<?=$location?>'>Выдать</button>
	</p>

	<? include "form_addpay.php"; ?>

	<div class="halfblock">
		<h1>Баланс рабочих</h1>
		<table>
			<thead>
			<tr>
				<th>Работник</th>
				<th>Баланс</th>
			</tr>
			</thead>
			<tbody>
			<?
				// Баланс работников
				$query = "SELECT WD.WD_ID, WD.Name, (IFNULL(SPL.Pay, 0) + IFNULL(SODS.Tariff, 0) + IFNULL(SBS.Tariff, 0) + IFNULL(SSTS.Tariff, 0)) Sum
							FROM WorkersData WD
							LEFT JOIN (
								SELECT PL.WD_ID, SUM(PL.Pay) Pay
								FROM PayLog PL
								GROUP BY PL.WD_ID
							) SPL ON SPL.WD_ID = WD.WD_ID
							LEFT JOIN (
								SELECT ODS.WD_ID, SUM(ODD.Amount * ODS.Tariff) Tariff
								FROM OrdersDataSteps ODS
								JOIN OrdersDataDetail ODD ON ODD.ODD_ID = ODS.ODD_ID
								WHERE ODS.IsReady = 1
								GROUP BY ODS.WD_ID
							) SODS ON SODS.WD_ID = WD.WD_ID
							LEFT JOIN (
								SELECT BS.WD_ID, SUM(BS.Amount * BS.Tariff) Tariff
								FROM BlankStock BS
								GROUP BY BS.WD_ID
							) SBS ON SBS.WD_ID = WD.WD_ID
							LEFT JOIN (
								SELECT STS.WD_ID, SUM(STS.Tariff + STS.Premium*0) Tariff FROM
								(
								SELECT TS.WD_ID
									,SUM(ROUND(TS.Hours * TS.Tariff) + IFNULL(TS.NightBonus, 0) + IFNULL(TS.DayBonus, 0)) Tariff
									,IF(SUM(TS.Hours) >= MNH.Hours OR MPP.DisableNormHours = 1, ROUND(SUM(TS.Hours * TS.Tariff + IFNULL(TS.NightBonus, 0) + IFNULL(TS.DayBonus, 0)) * IF(MPP.PremiumPercent IS NULL, WD.PremiumPercent, MPP.PremiumPercent) / 100), 0) Premium
								FROM TimeSheet TS
								LEFT JOIN MonthlyNormHours MNH ON MNH.Year = YEAR(TS.Date) AND MNH.Month = MONTH(TS.Date)
								LEFT JOIN MonthlyPremiumPercent MPP ON MPP.Year = YEAR(TS.Date) AND MPP.Month = MONTH(TS.Date) AND MPP.WD_ID = TS.WD_ID
								LEFT JOIN WorkersData WD ON WD.WD_ID = TS.WD_ID
								GROUP BY TS.WD_ID, YEAR(TS.Date), MONTH(TS.Date)
								) STS
								GROUP BY STS.WD_ID

							) SSTS ON SSTS.WD_ID = WD.WD_ID
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
					echo "<tr>";
					echo "<td><a href='?worker={$row["WD_ID"]}'>{$row["Name"]}</a></td>";
					echo "<td class='txtright'><span class='{$color} nowrap'>{$format_sum}</span></td>";
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
				<th>Сумма</th>
				<th>Примечание</th>
				<th>Действие</th>
			</tr>
			</thead>
			<tbody>

	<?
			$query = "SELECT PL.Date DateKey, DATE_FORMAT(DATE(PL.Date), '%d.%m.%Y') Date, TIME(PL.Date) Time, WD.Name Worker, ABS(PL.Pay) Pay, PL.Comment, WD.WD_ID, IF(PL.Pay < 0, '-', '') Sign
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
				$format_pay = number_format($row["Sign"].$row["Pay"], 0, '', ' ');
				echo "<tr>";
				echo "<td>{$row["Date"]}</td>";
				echo "<td>{$row["Time"]}</td>";
				echo "<td class='worker' val='{$row["WD_ID"]}'>{$row["Worker"]}</td>";
				echo "<td class='pay txtright nowrap' val='{$row["Pay"]}'>{$format_pay}</td>";
				echo "<td class='comment'>{$row["Comment"]}</td>";
				echo "<td><a href='#' id='{$row["DateKey"]}' sign='{$row["Sign"]}' class='button edit_pay' location='{$location}' title='Редактировать платеж'><i class='fa fa-pencil fa-lg'></i></a></td>";
				echo "</tr>";
			}
	?>

			</tbody>
		</table>
	</div>

</body>
</html>
