<?
	include "config.php";
	$title = 'Платежи';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_paylog', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$worker = (isset($_GET["worker"]) and (int)$_GET["worker"] > 0) ? $_GET["worker"] : $_SESSION['id'];

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

	ul {
		padding-left: 20px;
		font-size: .9em;
		line-height: 1.5em;
	}
</style>

	<div id='add_payin_btn' class='edit_pay' worker_name='<?=$USR_ShortName?>' worker='<?=$worker?>' location='<?=$location?>' title='НАЧИСЛИТЬ заработную плату'><i class="fas fa-2x fa-user-cog"></i></div>
	<div id='add_payout_btn' class='edit_pay' account='<?=$account?>' worker_name='<?=$USR_ShortName?>' worker='<?=$worker?>' location='<?=$location?>' title='ВЫДАТЬ заработную плату'><i class="fas fa-2x fa-user-check"></i></div>

	<? include "form_addpay.php"; ?>

	<div class="halfblock">
		<?
		// Рекурсивная функция вывода дерева пользователей
		function user_tree( $usr_id ) {
			global $mysqli;
			global $worker;

			$query = "
				SELECT USR_Name(USR.USR_ID) Name
					,USR.Balance
				FROM Users USR
				WHERE USR.USR_ID = {$usr_id}
			";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$row = mysqli_fetch_array($res);

			$format_balance = $row["Balance"] ? number_format($row["Balance"], 0, '', ' ') : "";

			echo "<li><a href='?worker={$usr_id}' ".(($usr_id == $worker) ? "style='color: #333; font-weight: bold;'" : "").">{$row["Name"]}</a>&nbsp;<b class='".($row["Balance"] < 0 ? "bg-red " : "")."nowrap'>{$format_balance}</b>";

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
				echo ":<ul>";
				while( $row = mysqli_fetch_array($res) ) {
					user_tree( $row["USR_ID"] );
				}
				echo "</ul>";
			}

			echo "</li>";
		}
		// Конец рекурсивной функции

		echo "<ul style='font-size: 1.6em;'>";
		user_tree( $_SESSION['id'] );
		echo "</ul>";
?>
	</div>

	<div class="log-pay halfblock">
		<h1><?=$USR_Name?></h1>
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

	<?
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

<?
	include "footer.php";
?>
