<?
	include "../config.php";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>АКТ СВЕРКИ</title>

	<style>
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
				взаимных расчетов по состоянию на 28.11.2017<br>
				между ООО "Престол"<br>
				и ООО "Мир"<br>
			</h3>
			<p style="text-align: left;">28.11.2017</p>
			<p style="text-align: left;">Мы, нижеподписавшиеся, Директор ООО "Престол" Шабалин А.В., с одной стороны, и
___________________________ ООО "Мир" _______________________________, с другой стороны,
составили настоящий акт сверки в том, что состояние взаимных расчетов по данным учета следующее:</p>
		</div>
		<table>
			<thead class="thead">
				<tr>
					<td colspan="4">По данным ООО "Престол"</td>
				</tr>
			</thead>
		</table>
		<table>
			<thead class="thead">
				<tr>
					<th width="50">№<br>п/п</th>
					<th>Наименование операции, документы</th>
					<th width="100">Дебет</th>
					<th width="100">Кредит</th>
				</tr>
			</thead>
			<tbody>
				<tr style="background: #DDD;">
					<td>1</td>
					<td>Сальдо на 01.11.2011</td>
					<td style="text-align: right;">2'000.00</td>
					<td style="text-align: right;"></td>
				</tr>
				<tr>
					<td>2</td>
					<td>Оплата от покупателя</td>
					<td style="text-align: right;"></td>
					<td style="text-align: right;">1'000.00</td>
				</tr>
				<tr style="background: #DDD;">
					<td>3</td>
					<td>Обороты за период</td>
					<td style="text-align: right;">0.00</td>
					<td style="text-align: right;">1'000.00</td>
				</tr>
				<tr style="background: #DDD;">
					<td>4</td>
					<td>Сальдо на 28.11.2017</td>
					<td style="text-align: right;">1'000.00</td>
					<td style="text-align: right;"></td>
				</tr>
			</tbody>
		</table>
		<div style="display: flex; margin-top: 40px;">
			<div style="width: 50%;">
				По данным ООО "Престол"<br>
				<b>на 28.11.2017 задолженность в пользу ООО "Престол" 1'000.00 руб.</b>
			</div>
			<div style="width: 50%;"></div>
		</div>
		<div style="display: flex; margin-top: 40px;">
			<div style="width: 50%;">
				<p>От ООО "Престол"</p>
				<p>Директор</p>
				<p>__________________ (Шабалин А.В.)</p>
				<p>М.П.</p>
			</div>
			<div style="width: 50%;">
				<p>От ООО "Мир"</p>
				<p>___________</p>
				<p>__________________ (_____________________)</p>
				<p>М.П.</p>
			</div>
		</div>
	</div>
</body>
