<?
	include "../config.php";
	// Узнаём токен и по нему доп информацию из БД
	$token = $_GET["t"];
	$query = "SELECT KA_ID
					,DATE_FORMAT(DATE(date_from), '%d.%m.%Y') date_from
					,DATE_FORMAT(DATE(date_to), '%d.%m.%Y') date_to
				FROM ActSverki
				WHERE token = '{$token}'";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	if( mysqli_num_rows($res) ) {
		$payer = mysqli_result($res,0,'KA_ID');
		$date_from = mysqli_result($res,0,'date_from');
		$date_to = mysqli_result($res,0,'date_to');
	}
	else {
		exit ("Недействительная ссылка.");
	}

	// Информация о грузоотправителе
	$query = "SELECT * FROM Rekvizity LIMIT 1";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$gruzootpravitel_name = mysqli_result($res,0,'Name');
	$gruzootpravitel_director = mysqli_result($res,0,'Dir');

	// Информация о плательщике
	$query = "SELECT * FROM Kontragenty WHERE KA_ID = {$payer}";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$platelshik_name = mysqli_result($res,0,'Naimenovanie');
	$saldo = mysqli_result($res,0,'saldo');
	$debt_format = number_format(abs($saldo), 0, '', '\'').".00";

	// Вычисление оборота за период
	$query = "SELECT SUM(SUB.debet) debet, SUM(SUB.kredit) kredit
				FROM (
					SELECT IF(PFI.rtrn = 1, NULL, PFI.summa) debet
						,IF(PFI.rtrn = 1, PFI.summa, NULL) kredit
					FROM PrintFormsInvoice PFI
					WHERE PFI.date BETWEEN STR_TO_DATE('{$date_from}', '%d.%m.%Y') AND STR_TO_DATE('{$date_to}', '%d.%m.%Y') AND PFI.platelshik_id = {$payer} AND PFI.del = 0

					UNION ALL

					SELECT NULL debet
						,F.money kredit
					FROM Finance F
					WHERE F.date BETWEEN STR_TO_DATE('{$date_from}', '%d.%m.%Y') AND STR_TO_DATE('{$date_to}', '%d.%m.%Y') AND F.KA_ID = {$payer}
				) SUB";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$debet_profit = mysqli_result($res,0,'debet'); // Дебетовый оборот
	$kredit_profit = mysqli_result($res,0,'kredit'); // Кредитовый оборот
	$start_saldo = $saldo + $debet_profit - $kredit_profit;
	$start_saldo_format = number_format(abs($start_saldo), 0, '', '\'').".00";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>АКТ СВЕРКИ</title>

	<style>
		@media print {
			div {
				page-break-inside: avoid;
			}
		}
		body, td {
			margin: 20px;
			color: #333;
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 12pt;
		}
		table {
			table-layout: fixed;
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		.thead {
			text-align: center;
			font-weight: bold;
		}
		td, th {
			padding: 3px;
			border: 1px solid black;
			line-height: 1.45em;
		}
		.coupon {
			page-break-inside: avoid;
			border: 2px solid black;
			border-bottom: none;
		}
		.nowrap {
			white-space: nowrap;
		}
	</style>
</head>
<body>
	<div style="width: 1000px; margin: auto;">
		<div style="text-align: center;">
			<h2>АКТ СВЕРКИ</h2>
			<h3>
				взаимных расчетов по состоянию на <?=$date_to?><br>
				между <?=$gruzootpravitel_name?><br>
				и <?=$platelshik_name?><br>
			</h3>
			<p style="text-align: left;"><?=$date_to?></p>
			<p style="text-align: left;">Мы, нижеподписавшиеся, Директор <?=$gruzootpravitel_name?> <?=$gruzootpravitel_director?>, с одной стороны, и
___________________________ <?=$platelshik_name?> _______________________________, с другой стороны,
составили настоящий акт сверки в том, что состояние взаимных расчетов по данным учета следующее:</p>
		</div>
		<table>
			<thead class="thead">
				<tr>
					<td colspan="5">По данным <?=$gruzootpravitel_name?></td>
				</tr>
			</thead>
		</table>
		<table>
			<thead class="thead">
				<tr>
					<th width="50">№<br>п/п</th>
					<th width="100">Дата</th>
					<th>Наименование операции, документы</th>
					<th width="100">Дебет</th>
					<th width="100">Кредит</th>
				</tr>
			</thead>
			<tbody>
				<tr style="background: #DDD;">
					<td colspan="3">Сальдо на <?=$date_from?></td>
					<td style="text-align: right;"><?=($start_saldo < 0 ? $start_saldo_format : "")?></td>
					<td style="text-align: right;"><?=($start_saldo > 0 ? $start_saldo_format : "")?></td>
				</tr>
<?
	$query = "SELECT PFI.PFI_ID ID
					,IF(PFI.rtrn = 1, NULL, PFI.summa) debet
					,IF(PFI.rtrn = 1, PFI.summa, NULL) kredit
					,IF(PFI.rtrn = 1, CONCAT('Возврат товара, накладная <b>№', PFI.count, '</b>'), CONCAT('Реализация, накладная <b>№', PFI.count, '</b>')) document
					,DATE_FORMAT(PFI.date, '%d.%m.%Y') date_format
					,PFI.date
				FROM PrintFormsInvoice PFI
				WHERE PFI.date BETWEEN STR_TO_DATE('{$date_from}', '%d.%m.%Y') AND STR_TO_DATE('{$date_to}', '%d.%m.%Y') AND PFI.platelshik_id = {$payer} AND PFI.del = 0

				UNION ALL

				SELECT F.F_ID ID
					,NULL debet
					,F.money kredit
					,CONCAT('Оплата от покупателя, <b>', F.comment, '</b>') document
					,DATE_FORMAT(F.date, '%d.%m.%Y') date_format
					,F.date
				FROM Finance F
				WHERE F.date BETWEEN STR_TO_DATE('{$date_from}', '%d.%m.%Y') AND STR_TO_DATE('{$date_to}', '%d.%m.%Y') AND F.KA_ID = {$payer}

				ORDER BY date, ID";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$i = 0;
	while( $row = mysqli_fetch_array($res) ) {
		++$i;
		$debet = $row["debet"] ? number_format($row["debet"], 0, '', '\'').".00" : "";
		$kredit = $row["kredit"] ? number_format($row["kredit"], 0, '', '\'').".00" : "";
		echo "
			<tr>
				<td>{$i}</td>
				<td>{$row["date_format"]}</td>
				<td>{$row["document"]}</td>
				<td style='text-align: right;'>{$debet}</td>
				<td style='text-align: right;'>{$kredit}</td>
			</tr>
		";
	}
	$debet_profit = number_format($debet_profit, 0, '', '\'').".00";
	$kredit_profit = number_format($kredit_profit, 0, '', '\'').".00";

?>
				<tr style="background: #DDD;">
					<td colspan="3">Обороты за период</td>
					<td style="text-align: right;"><?=$debet_profit?></td>
					<td style="text-align: right;"><?=$kredit_profit?></td>
				</tr>
				<tr style="background: #DDD;">
					<td colspan="3">Сальдо на <?=$date_to?></td>
					<td style="text-align: right;"><?=($saldo < 0 ? $debt_format : "")?></td>
					<td style="text-align: right;"><?=($saldo > 0 ? $debt_format : "")?></td>
				</tr>
			</tbody>
		</table>
		<div style="display: flex; margin-top: 40px;">
			<div style="width: 50%;">
				По данным <?=$gruzootpravitel_name?><br>
				<?
					echo "<b>на {$date_to} задолженность ".(($saldo < 0) ? "в пользу {$gruzootpravitel_name} {$debt_format} руб." : (($saldo > 0) ? "в пользу {$platelshik_name} {$debt_format} руб." : "отсутствует."))."</b>";
				?>
			</div>
			<div style="width: 50%;"></div>
		</div>
		<div style="display: flex; margin-top: 40px;">
			<div style="width: 50%;">
				<p>От <?=$gruzootpravitel_name?></p>
				<p>Директор</p>
				<p>__________________ (<?=$gruzootpravitel_director?>)</p>
				<p>М.П.</p>
			</div>
			<div style="width: 50%;">
				<p>От <?=$platelshik_name?></p>
				<p>___________</p>
				<p>__________________ (_____________________)</p>
				<p>М.П.</p>
			</div>
		</div>
	</div>
</body>
