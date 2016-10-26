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
?>
<form>
	<label for="year">Год:</label>
	<script>
		$( document ).ready(function() {
			$("#year option[value='<?=$year?>']").prop('selected', true);
		});
	</script>
	<select name="year" id="year" onchange="this.form.submit()">
<?
	$query = "SELECT year FROM PrintForms WHERE IFNULL(summa, 0) > 0 GROUP BY year";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<option value='{$row["year"]}'>{$row["year"]}</option>";
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
			<th>Номер</th>
			<th>Накладная</th>
			<th>Счет</th>
			<th>Автор</th>
		</tr>
	</thead>
	<tbody>
<?
$query = "SELECT PF_ID
				,PF.summa
				,KA.Naimenovanie
				,PF.count
				,nakladnaya_date
				,schet_date
				,USR.Name
			FROM PrintForms PF
			LEFT JOIN Cities CT ON CT.CT_ID = PF.CT_ID
			LEFT JOIN Users USR ON USR.USR_ID = PF.USR_ID
			LEFT JOIN Kontragenty KA ON KA.KA_ID = PF.platelshik_id
			WHERE IFNULL(PF.summa, 0) > 0 AND year = {$year}".(in_array('print_forms_view_autor', $Rights) ? " AND PF.USR_ID = {$_SESSION['id']}" : ""). "
			ORDER BY PF.PF_ID DESC";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	$summa = number_format($row["summa"], 0, '', ' ');
	$number = str_pad($row["count"], 8, '0', STR_PAD_LEFT);
	$nakladnaya = $row["nakladnaya"] ? "<i class='fa fa-check-square fa-2x' aria-hidden='true'></i>" : "";
	$schet = $row["schet"] ? "<i class='fa fa-check-square fa-2x' aria-hidden='true'></i>" : "";
	echo "<tr>";
	echo "<td class='txtright'>{$summa}</td>";
	echo "<td><a href='/print_forms.php?pfid={$row["PF_ID"]}' target='_blank'>{$row["Naimenovanie"]}</a></td>";
	echo "<td>{$number}</td>";
	//echo "<td><a href='print_forms/nakladnaya_{$row["PF_ID"]}_{$number}.pdf' target='_blank'>{$row["nakladnaya_date"]}</a></td>";
	echo "<td><a href='open_print_form.php?type=nakladnaya&PF_ID={$row["PF_ID"]}&number={$number}' target='_blank'>{$row["nakladnaya_date"]}</a></td>";
	//echo "<td><a href='print_forms/schet_{$row["PF_ID"]}_{$number}.pdf' target='_blank'>{$row["schet_date"]}</a></td>";
	echo "<td><a href='open_print_form.php?type=schet&PF_ID={$row["PF_ID"]}&number={$number}' target='_blank'>{$row["schet_date"]}</a></td>";
	echo "<td>{$row["Name"]}</td>";
	echo "</tr>";
}
?>
	</tbody>
</table>
