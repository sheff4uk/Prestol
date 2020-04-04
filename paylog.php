<?
	include "config.php";
	$title = 'Платежи';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_paylog', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$location = $_SERVER['REQUEST_URI'];

	$year = date("Y");
	$month = date("n");
	$lastyear = $month == 1 ? $year - 1 : $year;
	$lastmonth = $month == 1 ? 12 : $month - 1;

	//Узнаем дефолтный счет для пользователя
	$query = "SELECT FA_ID FROM FinanceAccount WHERE USR_ID = {$_SESSION['id']} ORDER BY IFNULL(bank, 0) LIMIT 1";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$account = mysqli_result($res,0,'FA_ID');
?>

<style>
	#add_payin_btn {
		text-align: center;
		line-height: 64px;
		color: #fff;
		bottom: 100px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 100px;
		z-index: 9;
		border-radius: 50%;
		background-color: #16A085;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}

	#add_payout_btn {
		text-align: center;
		line-height: 64px;
		color: #fff;
		bottom: 170px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 100px;
		z-index: 9;
		border-radius: 50%;
		background-color: #db4437;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}

	#add_payin_btn:hover, #add_payout_btn:hover {
		opacity: 1;
	}
</style>

	<div id='add_payin_btn' class='edit_pay' <?=isset($_GET["worker"]) ? "worker='{$_GET["worker"]}'" : "" ?> location='<?=$location?>' title='НАЧИСЛИТЬ заработную плату'><i class="fas fa-2x fa-user-cog"></i></div>
	<div id='add_payout_btn' class='edit_pay' account='<?=$account?>' <?=isset($_GET["worker"]) ? "worker='{$_GET["worker"]}'" : "" ?> location='<?=$location?>' title='ВЫДАТЬ заработную плату'><i class="fas fa-2x fa-user-check"></i></div>

	<? include "form_addpay.php"; ?>
	<? include "forms.php"; ?>

	<div class="halfblock">
		<?
			// Баланс сдельных работников
			$query = "
				SELECT WD.WD_ID
					,WD.Name
					,WD.Balance
					,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayIn, 0), 0)) PayIn
					,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayOut, 0), 0)) PayOut
					,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayIn, 0), 0)) LastPayIn
					,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayOut, 0), 0)) LastPayOut
				FROM WorkersData WD
				LEFT JOIN MonthlyPayInOut MPIO ON MPIO.WD_ID = WD.WD_ID
				WHERE WD.IsActive = 1 AND WD.Type = 1
			";
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
								WHERE WD_ID = {$row["WD_ID"]} AND NOT ( Year = YEAR(NOW()) AND Month = MONTH(NOW()) ) AND DATEDIFF(NOW(), DATE( CONCAT( Year, '-', Month, '-01' ) )) <= 365";
					$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					$avg_pay_out = mysqli_result($subres, 0, 'avg_pay_out');

					if( $row["Balance"] < 0 )
						$color = ' bg-red';
					else
						$color = '';
					$format_sum = number_format($row["Balance"], 0, '', ' ');
					$format_MPI = number_format($row["PayIn"], 0, '', ' ');
					$format_MPO = number_format($row["PayOut"], 0, '', ' ');
					$format_LMPI = number_format($row["LastPayIn"], 0, '', ' ');
					$format_LMPO = number_format($row["LastPayOut"], 0, '', ' ');
					$format_avg_pay_out = number_format($avg_pay_out, 0, '', ' ');
					echo "<tr>";
					echo "<td><a href='?worker={$row["WD_ID"]}'>{$row["Name"]}</a></td>";
					echo "<td class='txtright'><b class='{$color} nowrap'>{$format_sum}</b></td>";
					echo "<td class='txtright'><span class='nowrap'>{$format_MPI}</span></td>";
					echo "<td class='txtright'><span class='nowrap'>{$format_MPO}</span></td>";
					echo "<td class='txtright'><span class='nowrap'>{$format_LMPI}</span></td>";
					echo "<td class='txtright'><span class='nowrap'>{$format_LMPO}</span></td>";
					echo "<td class='txtright'><span class='nowrap' style='color: #911;'>{$format_avg_pay_out}</span></td>";
					echo "</tr>";
					$total_sum = $total_sum + $row["Balance"];
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
					echo "<td class='txtright'style='color: #911;'><b>{$total_avg_pay_out}</b></td>";
					echo "</tr>";
				}
				?>
				</tbody>
			</table>

			<?
			}

			// Баланс повременных работников
			$query = "
				SELECT WD.WD_ID
					,WD.Name
					,WD.Balance
					,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayIn, 0), 0)) PayIn
					,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayOut, 0), 0)) PayOut
					,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayIn, 0), 0)) LastPayIn
					,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayOut, 0), 0)) LastPayOut
				FROM WorkersData WD
				LEFT JOIN MonthlyPayInOut MPIO ON MPIO.WD_ID = WD.WD_ID
				WHERE WD.IsActive = 1 AND WD.Type = 2
			";

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
						<th rowspan="2">Среднегодовая<br>выдача</th>
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
					$total_avg_pay_out = 0;

					while( $row = mysqli_fetch_array($res) )
					{
						// Узнаем среднегодовую получку
						$query = "SELECT ROUND(AVG(PayOut)) avg_pay_out
									FROM MonthlyPayInOut
									WHERE WD_ID = {$row["WD_ID"]} AND NOT ( Year = YEAR(NOW()) AND Month = MONTH(NOW()) ) AND DATEDIFF(NOW(), DATE( CONCAT( Year, '-', Month, '-01' ) )) <= 365";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$avg_pay_out = mysqli_result($subres, 0, 'avg_pay_out');

						if( $row["Balance"] < 0 )
							$color = ' bg-red';
						else
							$color = '';
						$format_sum = number_format($row["Balance"], 0, '', ' ');
						$format_MPI = number_format($row["PayIn"], 0, '', ' ');
						$format_MPO = number_format($row["PayOut"], 0, '', ' ');
						$format_LMPI = number_format($row["LastPayIn"], 0, '', ' ');
						$format_LMPO = number_format($row["LastPayOut"], 0, '', ' ');
						$format_avg_pay_out = number_format($avg_pay_out, 0, '', ' ');
						echo "<tr>";
						echo "<td><a href='?worker={$row["WD_ID"]}'>{$row["Name"]}</a></td>";
						echo "<td class='txtright'><b class='{$color} nowrap'>{$format_sum}</b></td>";
						echo "<td class='txtright'><span class='nowrap'>{$format_MPI}</span></td>";
						echo "<td class='txtright'><span class='nowrap'>{$format_MPO}</span></td>";
						echo "<td class='txtright'><span class='nowrap'>{$format_LMPI}</span></td>";
						echo "<td class='txtright'><span class='nowrap'>{$format_LMPO}</span></td>";
						echo "<td class='txtright'><span class='nowrap' style='color: #911;'>{$format_avg_pay_out}</span></td>";
						echo "</tr>";
						$total_sum = $total_sum + $row["Balance"];
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
						echo "<td class='txtright'style='color: #911;'><b>{$total_avg_pay_out}</b></td>";
						echo "</tr>";
					}
				?>
				</tbody>
			</table>
			<?
			}
			// Баланс лакировщиков
			$query = "
				SELECT WD.WD_ID
					,WD.Name
					,WD.Balance
					,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayIn, 0), 0)) PayIn
					,SUM(IF(Year = {$year} AND Month = {$month}, IFNULL(MPIO.PayOut, 0), 0)) PayOut
					,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayIn, 0), 0)) LastPayIn
					,SUM(IF(Year = {$lastyear} AND Month = {$lastmonth}, IFNULL(MPIO.PayOut, 0), 0)) LastPayOut
				FROM WorkersData WD
				LEFT JOIN MonthlyPayInOut MPIO ON MPIO.WD_ID = WD.WD_ID
				WHERE WD.IsActive = 1 AND WD.Type = 3
			";

			if( isset($_GET["worker"]) ) {
				$query .= " AND WD.WD_ID = {$_GET["worker"]}";
			}
			$query .= " GROUP BY WD.WD_ID ORDER BY WD.Name";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			if( mysqli_num_rows($res) ) {
				?>
				<h1>Баланс Лакировщиков</h1>
				<table>
					<thead>
					<tr>
						<th rowspan="2">Работник</th>
						<th rowspan="2">Баланс</th>
						<th colspan="2" class="nowrap"><?=$MONTHS[$month]?> <?=$year?></th>
						<th colspan="2" class="nowrap"><?=$MONTHS[$lastmonth]?> <?=$lastyear?></th>
						<th rowspan="2">Среднегодовая<br>выдача</th>
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
					$total_avg_pay_out = 0;

					while( $row = mysqli_fetch_array($res) )
					{
						// Узнаем среднегодовую получку
						$query = "SELECT ROUND(AVG(PayOut)) avg_pay_out
									FROM MonthlyPayInOut
									WHERE WD_ID = {$row["WD_ID"]} AND NOT ( Year = YEAR(NOW()) AND Month = MONTH(NOW()) ) AND DATEDIFF(NOW(), DATE( CONCAT( Year, '-', Month, '-01' ) )) <= 365";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						$avg_pay_out = mysqli_result($subres, 0, 'avg_pay_out');

						if( $row["Balance"] < 0 )
							$color = ' bg-red';
						else
							$color = '';
						$format_sum = number_format($row["Balance"], 0, '', ' ');
						$format_MPI = number_format($row["PayIn"], 0, '', ' ');
						$format_MPO = number_format($row["PayOut"], 0, '', ' ');
						$format_LMPI = number_format($row["LastPayIn"], 0, '', ' ');
						$format_LMPO = number_format($row["LastPayOut"], 0, '', ' ');
						$format_avg_pay_out = number_format($avg_pay_out, 0, '', ' ');
						echo "<tr>";
						echo "<td><a href='?worker={$row["WD_ID"]}'>{$row["Name"]}</a></td>";
						echo "<td class='txtright'><b class='{$color} nowrap'>{$format_sum}</b></td>";
						echo "<td class='txtright'><span class='nowrap'>{$format_MPI}</span></td>";
						echo "<td class='txtright'><span class='nowrap'>{$format_MPO}</span></td>";
						echo "<td class='txtright'><span class='nowrap'>{$format_LMPI}</span></td>";
						echo "<td class='txtright'><span class='nowrap'>{$format_LMPO}</span></td>";
						echo "<td class='txtright'><span class='nowrap' style='color: #911;'>{$format_avg_pay_out}</span></td>";
						echo "</tr>";
						$total_sum = $total_sum + $row["Balance"];
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
						echo "<td class='txtright'style='color: #911;'><b>{$total_avg_pay_out}</b></td>";
						echo "</tr>";
					}
				?>
				</tbody>
			</table>
			<?
			}
		if( isset($_GET["worker"]) ) {
?>

<!--
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
-->
<?
//			$query = "
//				SELECT BL.Balance
//					,Friendly_date(BL.Date) date
//					,DATE_FORMAT(BL.Date, '%H:%i') Time
//				FROM BalanceLog BL
//				WHERE WD_ID = {$_GET["worker"]}
//				AND DATEDIFF((SELECT MAX(Date) FROM BalanceLog WHERE WD_ID = {$_GET["worker"]}), BL.Date) <= 31
//				ORDER BY BL.Date DESC, BL.Balance DESC
//				#LIMIT 100
//			";
//
//			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
//
//			while( $row = mysqli_fetch_array($res) ) {
//				$format_balance = number_format($row["Balance"], 0, '', ' ');
//				if( $row["Balance"] < 0 )
//					$color = ' bg-red';
//				else
//					$color = '';
//				echo "<tr>";
//				echo "<td><span class='nowrap'><b>{$row["date"]}</b></span></td>";
//				echo "<td><span class='nowrap'>{$row["Time"]}</span></td>";
//				echo "<td class='txtright'><span class='nowrap{$color}'>{$format_balance}</span></td>";
//				echo "</tr>";
//			}
?>
<!--
			</tbody>
		</table>
-->
<?
		}
?>
	</div>

	<div class="log-pay halfblock">
		<h1>Журнал начислений и выплат</h1>
		<table class='main_table'>
			<thead>
			<tr>
				<th width='60'>Дата</th>
				<th width='60'>Время</th>
				<th width='30%'>Работник</th>
				<th width='60'>Начислено</th>
				<th width='75'>Выдано</th>
				<th width='75'>Баланс</th>
				<th width='70%'>Примечание</th>
				<th width='50'>Автор</th>
			</tr>
			</thead>
			<tbody>

	<?
			$query = "
				SELECT Friendly_date(PLF.Date) date
					,DATE_FORMAT(PLF.Date, '%H:%i') Time
					,PLF.WD_ID
					,WD.Name Worker
					,PLF.PayIn
					,PLF.PayOut
					,PLF.Balance
					,REPLACE(PLF.Comment, '\r\n', '<br>') Comment
					,USR_Icon(PLF.author) Name
					,PLF.OD_ID
					,PLF.Code
				FROM (
					SELECT PL.Date
						,PL.Pay PayIn
						,NULL PayOut
						,BL.Balance
						,PL.WD_ID
						,PL.Comment
						,PL.author
						,PL.OD_ID
						,OD.Code
					FROM PayLog PL
					LEFT JOIN BalanceLog BL ON BL.PL_ID = PL.PL_ID
					LEFT JOIN OrdersData OD ON OD.OD_ID = PL.OD_ID
					WHERE ".($_GET["worker"] ? "PL.WD_ID = {$_GET["worker"]} AND DATEDIFF(NOW(), PL.Date) <= 365" : "DATEDIFF(NOW(), PL.Date) <= 31")."

					UNION

					SELECT F.date
						,NULL
						,IF(F.FC_ID = 1, F.money, -1*F.money)
						,BL.Balance
						,F.WD_ID
						,F.comment
						,F.author
						,NULL
						,NULL
					FROM Finance F
					LEFT JOIN BalanceLog BL ON BL.F_ID = F.F_ID
					WHERE ".($_GET["worker"] ? "F.WD_ID = {$_GET["worker"]} AND DATEDIFF(NOW(), F.date) <= 365" : "F.WD_ID IS NOT NULL AND DATEDIFF(NOW(), F.date) <= 31")."
				) PLF
				LEFT JOIN WorkersData WD ON WD.WD_ID = PLF.WD_ID
				ORDER BY PLF.Date DESC
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				$format_payin = $row["PayIn"] ? number_format($row["PayIn"], 0, '', ' ') : "";
				$format_payout = $row["PayOut"] ? number_format($row["PayOut"], 0, '', ' ') : "";
				$format_balance = $row["Balance"] ? number_format($row["Balance"], 0, '', ' ') : "";
				echo "<tr>";
				echo "<td><span class='nowrap'><b>{$row["date"]}</b></span></td>";
				echo "<td><span>{$row["Time"]}</span></td>";
				echo "<td class='worker'><span><a href='?worker={$row["WD_ID"]}'>{$row["Worker"]}</a></span></td>";
				echo "<td class='txtright nowrap'><b style='color: ".($row["PayIn"] < 0 ? "#E74C3C;" : "#16A085;")."'>{$format_payin}</b></td>";
				echo "<td class='txtright nowrap'><b style='color: ".($row["PayOut"] < 0 ? "#E74C3C;" : "#16A085;")."'>{$format_payout}</b></td>";
				echo "<td class='txtright'><b class='".($row["Balance"] < 0 ? "bg-red " : "")."nowrap'>{$format_balance}</b></td>";
				echo "<td class='comment nowrap' style='z-index: 2;'><span>";
				// Если запись из этапов производства - выводим код набора
				if( $row["OD_ID"] ) {
					echo "<a href='orderdetail.php?id={$row["OD_ID"]}' target='_blank' title='Посмотреть набор.'><b class='code'>{$row["Code"]}</b></a> ";
				}
				echo "{$row["Comment"]}</span></td>";
				echo "<td>{$row["Name"]}</td>";
				echo "</tr>";
			}
	?>
			</tbody>
		</table>
	</div>

<?
	include "footer.php";
?>
