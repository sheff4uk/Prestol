<?
include "config.php";

$title = 'Архив печатных форм';
include "header.php";

// Проверка прав на доступ к экрану
if( !in_array('print_forms_view_all', $Rights) and !in_array('print_forms_view_autor', $Rights) ) {
	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
	die('Недостаточно прав для совершения операции');
}

if( isset($_GET["year"]) ) {
	$year = $_GET["year"];
}
else {
	$year = date('Y');
}
if( isset($_GET["payer"]) and (int)$_GET["payer"] > 0 ) {
	$payer = $_GET["payer"];
}
else {
	$payer = "";
}
?>
<form>
	<script>
		$(function() {
			$("#year option[value='<?=$year?>']").prop('selected', true);
			$("#payer option[value='<?=$payer?>']").prop('selected', true);
		});
	</script>
	<label for="year">Год:</label>
	<select name="year" id="year" onchange="this.form.submit()">
<?
	$query = "SELECT year FROM PrintForms WHERE IFNULL(summa, 0) > 0 GROUP BY year
				UNION
				SELECT YEAR(NOW())";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<option value='{$row["year"]}'>{$row["year"]}</option>";
	}
?>
	</select>
	&nbsp;&nbsp;
	<label for="payer">Плательщик:</label>
	<select name="payer" id="payer" onchange="this.form.submit()">
		<option value="0">-=Все контрагенты=-</option>
<?
	$query = "SELECT KA_ID, Naimenovanie FROM Kontragenty ORDER BY count DESC";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<option value='{$row["KA_ID"]}'>{$row["Naimenovanie"]}</option>";
	}
?>
	</select>
</form>
<br>

<table>
	<thead>
		<tr>
			<th>Сумма</th>
			<th>Плательщик</th>
			<th>Грузополучатель</th>
			<th>Номер</th>
			<th>Накладная</th>
			<th>Счет</th>
			<th>Автор</th>
			<th>Отгрузка</th>
		</tr>
	</thead>
	<tbody>
<?
$query = "SELECT PF_ID
				,PF.summa
				,KAp.Naimenovanie platelshik
				,KAg.Naimenovanie gruzopoluchatel
				,PF.count
				,nakladnaya_date
				,schet_date
				,USR.Name
				,PF.SHP_ID
			FROM PrintForms PF
			LEFT JOIN Users USR ON USR.USR_ID = PF.USR_ID
			LEFT JOIN Kontragenty KAp ON KAp.KA_ID = PF.platelshik_id
			LEFT JOIN Kontragenty KAg ON KAg.KA_ID = PF.gruzopoluchatel_id
			WHERE IFNULL(PF.summa, 0) > 0 AND year = {$year}".(in_array('print_forms_view_autor', $Rights) ? " AND PF.USR_ID = {$_SESSION['id']}" : "").($payer ? " AND KA.KA_ID = {$payer}" : "")."
			ORDER BY PF.PF_ID DESC";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$summa = number_format($row["summa"], 0, '', ' ');
	$number = str_pad($row["count"], 8, '0', STR_PAD_LEFT);
	echo "<tr>";
	echo "<td class='txtright'>{$summa}</td>";
	echo "<td><a href='/print_forms.php?pfid={$row["PF_ID"]}' target='_blank'>{$row["platelshik"]}</a></td>";
	echo "<td><a href='/print_forms.php?pfid={$row["PF_ID"]}' target='_blank'>{$row["gruzopoluchatel"]}</a></td>";
	echo "<td>{$number}</td>";
	echo "<td><a href='open_print_form.php?type=nakladnaya&PF_ID={$row["PF_ID"]}&number={$number}' target='_blank'>{$row["nakladnaya_date"]}</a></td>";
	echo "<td><a href='open_print_form.php?type=schet&PF_ID={$row["PF_ID"]}&number={$number}' target='_blank'>{$row["schet_date"]}</a></td>";
	echo "<td>{$row["Name"]}</td>";
	echo "<td style='text-align: center;'>";
	if( $row["SHP_ID"] ) {
		echo "<a href='/?shpid={$row["SHP_ID"]}' title='К списку отгрузки'><i class='fa fa-truck fa-lg' aria-hidden='true'></i></a>";
	}
	echo "</td>";
	echo "</tr>";
}
?>
	</tbody>
</table>

<script>
	$(function() {
		$('#payer').select2({ placeholder: 'Выберите контрагента', language: 'ru' });
	});
</script>

<?
	include "footer.php";
?>
