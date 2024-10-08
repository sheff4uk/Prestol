<?php
	include "config.php";
	$title = 'Зарплата';
	include "header.php";
	include "forms.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_paylog', $Rights) and !in_array('screen_paylog_read', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$worker = (isset($_GET["worker"]) and (int)$_GET["worker"] > 0) ? $_GET["worker"] : $_SESSION['id'];
	$location = "paylog.php?worker=".$worker;

	// Проверка является ли выбранный работник потомком пользователя
	$query = "SELECT {$worker} IN ({$USR_tree}) in_tree";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	if( !mysqli_result($res,0,'in_tree') ) die('Недостаточно прав для совершения операции');

	// Узнаем имя выбранного работника
	$query = "
		SELECT USR_Name(USR.USR_ID) USR_Name
			,USR_ShortName(USR.USR_ID) USR_ShortName
		FROM Users USR
		WHERE USR.USR_ID = {$worker}
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$USR_Name = mysqli_result($res,0,'USR_Name');
	$USR_ShortName = mysqli_result($res,0,'USR_ShortName');

	$location = $_SERVER['REQUEST_URI'];

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

	ul {
		padding-left: 20px;
		line-height: 2em;
	}
	ul a {font-size: 1.2em;}
</style>

	<?php
	if( in_array('screen_paylog', $Rights) ) {
	?>
		<div id='add_payin_btn' class='edit_pay' worker_name='<?=$USR_ShortName?>' worker='<?=$worker?>' location='<?=$location?>' title='НАЧИСЛИТЬ заработную плату'><i class="fas fa-2x fa-user-cog"></i></div>
		<div id='add_payout_btn' class='edit_pay' account='<?=$account?>' worker_name='<?=$USR_ShortName?>' worker='<?=$worker?>' location='<?=$location?>' title='ВЫДАТЬ заработную плату'><i class="fas fa-2x fa-user-check"></i></div>

	<?php
		include "form_addpay.php";
	}
	?>

	<div class="halfblock">
		<?php
		// Рекурсивная функция вывода дерева пользователей
		function user_tree( $usr_id ) {
			global $mysqli;
			global $worker;

			$query = "
				SELECT USR_Name(USR.USR_ID) Name
					,USR_Icon(USR.USR_ID) Icon
					,USR.Balance
				FROM Users USR
				WHERE USR.USR_ID = {$usr_id}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);

			$format_balance = $row["Balance"] ? number_format($row["Balance"], 0, '', ' ') : "";

			echo "<li>{$row["Icon"]}&nbsp;<a href='?worker={$usr_id}' ".(($usr_id == $worker) ? "style='color: #333; font-weight: bold;'" : "").">{$row["Name"]}</a>&nbsp;<b class='".($row["Balance"] < 0 ? "bg-red " : "")."nowrap'>{$format_balance}</b>";

			// Маркер начислений сегодня
			$query = "SELECT 1 FROM PayLog PL WHERE PL.USR_ID = {$usr_id} AND PL.Date >= CURDATE() AND PL.Pay != 0";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			if( mysqli_num_rows($res) ) {
				echo "&nbsp;<i class='fas fa-circle' style='color: #16A085;' title='Начислено сегодня'></i>";
			}

			// Маркер выдач сегодня
			$query = "SELECT 1 FROM Finance F WHERE F.USR_ID = {$usr_id} AND F.date >= CURDATE() AND F.money != 0";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			if( mysqli_num_rows($res) ) {
				echo "&nbsp;<i class='fas fa-circle' style='color: #db4437;' title='Выдано сегодня'></i>";
			}

			// Выводим потомков, если есть
			$query = "
				SELECT USR.USR_ID
				FROM Users USR
				WHERE USR.act = 1
					AND USR.head = {$usr_id}
				ORDER BY USR.RL_ID, USR_Name(USR.USR_ID)
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			if( mysqli_num_rows($res) ) {
				echo "<ul>";
				while( $row = mysqli_fetch_array($res) ) {
					user_tree( $row["USR_ID"] );
				}
				echo "</ul>";
			}

			echo "</li>";
		}
		// Конец рекурсивной функции

		echo "<ul>";
		user_tree( $_SESSION['id'] );
		echo "</ul>";
		?>
	</div>

	<div class="log-pay halfblock">
		<h1><?=$USR_Name?></h1>

		<?php
			$query = "
				SELECT ODS.ODS_ID
					,ODS.ODD_ID
					,Zakaz(ODS.ODD_ID) Zakaz
					,IFNULL(ST.Step, 'Этап') Step
					,IFNULL(ODS.approved_tariff, 0) approved_tariff
					,ODS.Tariff
					,USR_Icon(ODS.author) author_icon
					,OD.confirmed
					,OD.OD_ID
					,OD.Code
				FROM OrdersDataSteps ODS
				JOIN OrdersDataDetail ODD ON ODD.ODD_ID = ODS.ODD_ID
				JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
				LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
				WHERE ODS.USR_ID = {$worker}
					AND ODS.IsReady
					AND ODS.Tariff
					AND ODS.approved = 0
					AND ODS.Visible
					AND ODS.Old = 0
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			if( mysqli_num_rows($res) ) {
				echo "
					<table class='main_table' style='border: 2px solid #911; margin-bottom: 10px;'>
						<thead>
							<tr>
								<th colspan='7'><h3>Готовые этапы, требующие подтверждения от администрации</h3></th>
							</tr>
							<tr>
								<th>Код набора</th>
								<th colspan='3'>Изделие</th>
								<th>Этап</th>
								<th>Тариф</th>
								<th>Автор</th>
							</tr>
						</thead>
						<tbody  style='text-align: center;'>
				";
				while( $row = mysqli_fetch_array($res) ) {
					echo "
						<tr>
							<td><a href='orderdetail.php?id={$row["OD_ID"]}' target='_blank' title='Посмотреть набор.'><b class='code'>{$row["Code"]}</b></a></td>
							<td style='text-align: left;' colspan='3'>{$row["Zakaz"]}</td>
							<td class='td_step ".($row["confirmed"] == 1 ? "step_confirmed" : "")." ".(!in_array('step_update', $Rights) ? "step_disabled" : "")."'>
								<a id='{$row["ODD_ID"]}' class='button ".(in_array('step_update', $Rights) ? "edit_steps " : "")."' location='{$location}'>{$row["Step"]}</a>
							</td>
							<td style='text-align: right; font-size: 1.2em;'><n style='text-decoration: line-through;'>{$row["approved_tariff"]}</n><br><b>{$row["Tariff"]}</b></td>
							<td>{$row["author_icon"]}</td>
						</tr>
					";
				}
				echo "
						</tbody>
					</table>
				";
			}

			echo "<div id='accordion'>";
			echo "<h3>Аналитика</h3>";
			echo "<div style='display: flex; justify-content: space-around;'>";

			// Персональная аналитика
			echo "<div>";
			echo "<b>Личная:</b>";
			echo "<table>";
			echo "<thead>";
			echo "<tr>";
			echo "<th>Период</th>";
			echo "<th>Начислено</th>";
			echo "<th>Выдано</th>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";

			$query = "SET @@lc_time_names='ru_RU'";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$query = "
				SELECT
					DATE_FORMAT(CONCAT(Year, '-', Month, '-01'), '%b %Y') month_year
					,PayIn
					,PayOut
				FROM MonthlyPayInOut
				WHERE USR_ID = {$worker} AND DATEDIFF(NOW(), DATE( CONCAT( Year, '-', Month, '-01' ) )) <= 365
				ORDER BY Year DESC, Month DESC
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				$format_payin = number_format($row["PayIn"], 0, '', ' ');
				$format_payout = number_format($row["PayOut"], 0, '', ' ');
				echo "<tr>";
				echo "<td><b>{$row["month_year"]}</b></td>";
				echo "<td class='txtright'>{$format_payin}</td>";
				echo "<td class='txtright'>{$format_payout}</td>";
				echo "</tr>";
			}

			echo "</tbody>";

			// Узнаем среднегодовую получку
			$query = "
				SELECT ROUND(AVG(PayIn)) PayIn
					,ROUND(AVG(PayOut)) PayOut
				FROM MonthlyPayInOut
				WHERE USR_ID = {$worker} AND NOT ( Year = YEAR(NOW()) AND Month = MONTH(NOW()) ) AND DATEDIFF(NOW(), DATE( CONCAT( Year, '-', Month, '-01' ) )) <= 365
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$format_payin = number_format($row["PayIn"], 0, '', ' ');
			$format_payout = number_format($row["PayOut"], 0, '', ' ');

			echo "<thead>";
			echo "<tr>";
			echo "<th title='Среднее значение за последние 11 полных месяцев.'><b>В среднем</b>&nbsp;<i class='fas fa-question-circle'></i></th>";
			echo "<th class='txtright'>{$format_payin}</th>";
			echo "<th class='txtright'>{$format_payout}</th>";
			echo "</tr>";
			echo "</thead>";
			echo "</table>";
			echo "</div>";
			///////////////////////////

			// Аналитика по подразделению
			// Получаем список подчиненных работников из дерева
			$query = "SELECT USR_tree({$worker}) array";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);
			$USR_tree_division = $row["array"];

			if( $USR_tree_division ) {
				echo "<div>";
				echo "<b>По подразделению:</b>";
				echo "<table>";
				echo "<thead>";
				echo "<tr>";
				echo "<th>Период</th>";
				echo "<th>Начислено</th>";
				echo "<th>Выдано</th>";
				echo "</tr>";
				echo "</thead>";
				echo "<tbody>";

				$query = "SET @@lc_time_names='ru_RU'";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$query = "
					SELECT
						DATE_FORMAT(CONCAT(Year, '-', Month, '-01'), '%b %Y') month_year
						,SUM(PayIn) PayIn
						,SUM(PayOut) PayOut
					FROM MonthlyPayInOut
					WHERE USR_ID IN ({$USR_tree_division}) AND DATEDIFF(NOW(), DATE( CONCAT( Year, '-', Month, '-01' ) )) <= 365
					GROUP BY month_year
					ORDER BY Year DESC, Month DESC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					$format_payin = number_format($row["PayIn"], 0, '', ' ');
					$format_payout = number_format($row["PayOut"], 0, '', ' ');
					echo "<tr>";
					echo "<td><b>{$row["month_year"]}</b></td>";
					echo "<td class='txtright'>{$format_payin}</td>";
					echo "<td class='txtright'>{$format_payout}</td>";
					echo "</tr>";
				}

				echo "</tbody>";

				// Узнаем среднегодовую получку
				$query = "
				SELECT ROUND(AVG(sMP.PayIn)) PayIn
					,ROUND(AVG(sMP.PayOut)) PayOut
				FROM (
					SELECT
						DATE_FORMAT(CONCAT(Year, '-', Month, '-01'), '%b %Y') month_year
						,SUM(PayIn) PayIn
						,SUM(PayOut) PayOut
					FROM MonthlyPayInOut
					WHERE USR_ID IN ({$USR_tree_division}) AND NOT ( Year = YEAR(NOW()) AND Month = MONTH(NOW()) ) AND DATEDIFF(NOW(), DATE( CONCAT( Year, '-', Month, '-01' ) )) <= 365
					GROUP BY month_year
				) sMP
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				$row = mysqli_fetch_array($res);
				$format_payin = number_format($row["PayIn"], 0, '', ' ');
				$format_payout = number_format($row["PayOut"], 0, '', ' ');

				echo "<thead>";
				echo "<tr>";
				echo "<th title='Среднее значение за последние 11 полных месяцев.'><b>В среднем</b>&nbsp;<i class='fas fa-question-circle'></i></th>";
				echo "<th class='txtright'>{$format_payin}</th>";
				echo "<th class='txtright'>{$format_payout}</th>";
				echo "</tr>";
				echo "</thead>";
				echo "</table>";
				echo "</div>";
			}

			echo "</div>";
			echo "</div>";
		?>
		<script>
			$(document).ready(function() {
				$( "#accordion" ).accordion({
					active: false,
					collapsible: true,
					heightStyle: "content"
				});
			});
		</script>

		<table class='main_table'>
			<thead>
			<tr>
				<th width='60'>Дата</th>
				<th width='60'>Время</th>
				<th width='100%'>Примечание</th>
				<th width='60'>Начислено</th>
				<th width='75'>Выдано</th>
				<th width='75'>Баланс</th>
				<th width='50'>Автор</th>
			</tr>
			</thead>
			<tbody>

	<?php
			$query = "
				SELECT PL.PL_ID ord
					,PL.Date Date_sort
					,Friendly_date(PL.Date) date
					,DATE_FORMAT(PL.Date, '%H:%i') Time
					,PL.Pay PayIn
					,NULL PayOut
					,BL.Balance
					,REPLACE(PL.Comment, '\r\n', '<br>') Comment
					,USR_Icon(PL.author) Name
					,PL.OD_ID
					,OD.Code
					,NULL Account
					,NULL color
				FROM PayLog PL
				LEFT JOIN BalanceLog BL ON BL.PL_ID = PL.PL_ID
				LEFT JOIN OrdersData OD ON OD.OD_ID = PL.OD_ID
				WHERE PL.USR_ID = {$worker} AND DATEDIFF(NOW(), PL.Date) <= 365

				UNION

				SELECT F.F_ID
					,F.date
					,Friendly_date(F.date) date
					,DATE_FORMAT(F.date, '%H:%i') Time
					,NULL
					,F.money * FC.type * -1
					,BL.Balance
					,REPLACE(F.comment, '\r\n', '<br>') Comment
					,USR_Icon(F.author) Name
					,NULL
					,NULL
					,FA.name
					,FA.color
				FROM Finance F
				JOIN FinanceAccount FA ON FA.FA_ID = F.FA_ID
				JOIN FinanceCategory FC ON FC.FC_ID = F.FC_ID
				LEFT JOIN BalanceLog BL ON BL.F_ID = F.F_ID
				WHERE F.USR_ID = {$worker} AND DATEDIFF(NOW(), F.date) <= 365
				ORDER BY Date_sort DESC, ord DESC
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
				echo "<td class='comment nowrap' style='z-index: 2;'><span>";
				// Если запись из этапов производства - выводим код набора
				if( $row["OD_ID"] ) {
					echo "<a href='orderdetail.php?id={$row["OD_ID"]}' target='_blank' title='Посмотреть набор.'><b class='code'>{$row["Code"]}</b></a> ";
				}
				echo "{$row["Comment"]}</span></td>";
				echo "<td class='txtright nowrap'><b style='color: ".($row["PayIn"] < 0 ? "#E74C3C;" : "#16A085;")."'>{$format_payin}</b></td>";
				echo "<td class='txtright nowrap'><b style='color: ".($row["PayOut"] < 0 ? "#E74C3C;" : "#16A085;")."'>{$format_payout}</b><br><span style='font-size: .8em; font-weight: bold; background: {$row["color"]};'>{$row["Account"]}</span></td>";
				echo "<td class='txtright'><b class='".($row["Balance"] < 0 ? "bg-red " : "")."nowrap'>{$format_balance}</b></td>";
				echo "<td>{$row["Name"]}</td>";
				echo "</tr>";
			}
	?>
			</tbody>
		</table>
	</div>

<?php
	include "footer.php";
?>
