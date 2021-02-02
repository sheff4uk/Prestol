<?
include "config.php";
$title = 'Плетежи';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('selling_all', $Rights) and !in_array('selling_city', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

if( !$_GET["payment_date"] ) {
	$date = date_create('-1 days');
	$_GET["payment_date"] = date_format($date, 'Y-m-d');
}
?>
<form method="get">
	<input type="date" name="payment_date" value="<?=$_GET["payment_date"]?>" onchange="this.form.submit()">
</form>

<?
$query = "
	SELECT OP.CB_ID
		,CB.name
	FROM OrdersPayment OP
	JOIN CashBox CB ON CB.CB_ID = OP.CB_ID AND CB.R_ID = 1
	WHERE OP.uuid IS NOT NULL
		AND DATE(OP.payment_date) = '{$_GET["payment_date"]}'
	GROUP BY OP.CB_ID
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<b>Касса: <?=$row["name"]?></b>
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
					,IF(OP.terminal = 1, OP.payment_sum, '') terminal
					,IFNULL(OD.Code, 'Не связан!') code
				FROM OrdersPayment OP
				LEFT JOIN OrdersData OD ON OD.OD_ID = OP.OD_ID
				JOIN CashBox CB ON CB.CB_ID = OP.CB_ID AND CB.R_ID = 1
				WHERE OP.uuid IS NOT NULL
					AND DATE(OP.payment_date) = '{$_GET["payment_date"]}'
					AND OP.CB_ID = {$row["CB_ID"]}
			";
			$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $subrow = mysqli_fetch_array($subres) ) {
				?>
				<tr>
					<td><?=$subrow["time_format"]?></td>
					<td><?=$subrow["cash"]?></td>
					<td><?=$subrow["terminal"]?></td>
					<td><?=$subrow["code"]?></td>
				</tr>
				<?
			}
			?>
		</tbody>
	</table>
	<?
}
include "footer.php";
?>
