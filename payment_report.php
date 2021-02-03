<?
include "config.php";
$title = 'Чеки';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('selling_all', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

if( !$_GET["payment_date"] ) {
	$date = date_create('-1 days');
	$_GET["payment_date"] = date_format($date, 'Y-m-d');
}
if( !$_GET["R_ID"] ) {
	$_GET["R_ID"] = "1";
}
?>
<form method="get" style="display: inline-block; margin-top: 10px;">
	<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
		<span>Дата:</span>
		<input type="date" name="payment_date" value="<?=$_GET["payment_date"]?>" onchange="this.form.submit()">
	</div>

	<div class="nowrap" style="display: inline-block; margin-bottom: 10px; margin-right: 30px;">
		<span>Организация:</span>
		<select name="R_ID" onchange="this.form.submit()">
			<?
			$query = "SELECT R_ID, Name FROM Rekvizity WHERE R_ID IN (1,2)";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) ) {
				$selected = ($row["R_ID"] == $_GET["R_ID"]) ? "selected" : "";
				echo "<option value='{$row["R_ID"]}' {$selected}>{$row["Name"]}</option>";
			}
			?>
		</select>
	</div>
</form>
<br>

<?
$query = "
	SELECT OP.CB_ID
		,CB.name
	FROM OrdersPayment OP
	JOIN CashBox CB ON CB.CB_ID = OP.CB_ID AND CB.R_ID = {$_GET["R_ID"]}
	WHERE OP.uuid IS NOT NULL
		AND DATE(OP.payment_date) = '{$_GET["payment_date"]}'
	GROUP BY OP.CB_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<span style="margin-top: 20px; display: inline-block; font-size: 1.5em;">Касса: <b><?=$row["name"]?></b></span>
	<table cellspacing='0' cellpadding='2' border='1'>
		<thead>
			<tr>
				<th>Время</th>
				<th>Наличными</th>
				<th>Картой</th>
				<th>Код набора</th>
			</tr>
		</thead>
		<tbody>
			<?
			$query = "
				SELECT DATE_FORMAT(OP.payment_date, '%H:%i') time_format
					,IF(OP.terminal = 0, OP.payment_sum, '') cash
					,IF(OP.terminal = 1, OP.payment_sum, '') card
					,IFNULL(OD.Code, 'Не связан!') code
				FROM OrdersPayment OP
				LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
				JOIN CashBox CB ON CB.CB_ID = OP.CB_ID AND CB.R_ID = {$_GET["R_ID"]}
				WHERE OP.uuid IS NOT NULL
					AND DATE(OP.payment_date) = '{$_GET["payment_date"]}'
					AND OP.CB_ID = {$row["CB_ID"]}
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$sumcash = 0;
			$sumcard = 0;
			while( $subrow = mysqli_fetch_array($subres) ) {
				$sumcash += $subrow["cash"];
				$sumcard += $subrow["card"];
				?>
				<tr>
					<td style='text-align: right;'><?=$subrow["time_format"]?></td>
					<td style='text-align: right;'><?=$subrow["cash"]?></td>
					<td style='text-align: right;'><?=$subrow["card"]?></td>
					<td style='text-align: right;'><span class="code"><?=$subrow["code"]?></span></td>
				</tr>
				<?
			}
			?>
			<tr>
				<td style='text-align: right;'><b>Сумма:</b></td>
				<td style='text-align: right;'><b><?=$sumcash?></b></td>
				<td style='text-align: right;'><b><?=$sumcard?></b></td>
				<td></td>
			</tr>
		</tbody>
	</table>
	<?
}
include "footer.php";
?>
