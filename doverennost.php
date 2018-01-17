<?
	include "config.php";

	$title = 'Подготовка доверенности';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('doverennost', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	if( $_GET["add_doverennost"] ) {

		// Получаем номер очередного документа
		$year = date('Y');
		$date = date('Y-m-d');
		$query = "SELECT COUNT(1)+1 Cnt FROM PrintFormsDoverennost WHERE YEAR(date) = {$year}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$count = mysqli_result($res,0,'Cnt');

		// Сохраняем в таблицу информацию по доверенности, узнаем её ID.
		$query = "INSERT INTO PrintFormsDoverennost SET count = {$count}, date = '{$date}', firma_prodavetc = '{$_POST["firma_prodavetc"]}', fio = '{$_POST["fio"]}', USR_ID = {$_SESSION["id"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$id = mysqli_insert_id($mysqli);

		$_POST["nomer"] = $count;

		// Получаем информацио об организации, выдавшей доверенность
		$query = "SELECT * FROM Rekvizity LIMIT 1";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$_POST["firma_polechatel"] = mysqli_result($res,0,'Name');
		$_POST["inn"] = mysqli_result($res,0,'INN');
		$_POST["kpp"] = mysqli_result($res,0,'KPP');
		$_POST["adres"] = mysqli_result($res,0,'Addres');
		$_POST["director"] = mysqli_result($res,0,'Dir');
		$_POST["buhgalter"] = mysqli_result($res,0,'Dir');
		$_POST["r_sch"] = mysqli_result($res,0,'RS');
		$_POST["bank"] = mysqli_result($res,0,'Bank');
		$_POST["bik"] = mysqli_result($res,0,'BIK');
		$_POST["korr_sch"] = mysqli_result($res,0,'KS');

		if( $curl = curl_init() ) {
			curl_setopt($curl, CURLOPT_URL, 'https://service-online.su/forms/doverennost_TMC/doverennost_TMC.php');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($curl, CURLOPT_REFERER, 'https://service-online.su/forms/doverennost_TMC/');
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$out = curl_exec($curl);

			$url = $out;
			$url = str_replace("<html><head><meta http-equiv='refresh' content='0; url=", "https://service-online.su", $url);
			$url = str_replace("'></head></html>", "", $url);
			$url = preg_replace("/\xEF\xBB\xBF/", "", $url);
			$url = trim($url);
			$out = file_get_contents($url);

			$filename = 'doverennost_'.$id.'_'.$_POST["nomer"].'.pdf';
			file_put_contents("print_forms/".$filename, $out); // Сохраняем файл на сервере

			curl_close($curl);

			exit ('<meta http-equiv="refresh" content="0; url=doverennost.php">');
			die;
		}
	}

	if( $_GET["year"] and (int)$_GET["year"] > 0 ) {
		$year = $_GET["year"];
	}
	else {
		$year = date('Y');
	}
?>

<form style="font-size: 1.2em;">
	<script>
		$(function() {
			$("#year option[value='<?=$year?>']").prop('selected', true);
		});
	</script>
	<label for="year">Год:</label>
	<select name="year" id="year" onchange="this.form.submit()">
<?
	$query = "SELECT YEAR(date) year FROM PrintFormsDoverennost GROUP BY YEAR(date)
				UNION
				SELECT YEAR(NOW())";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<option value='{$row["year"]}'>{$row["year"]}</option>";
	}
?>
	</select>
</form>
<br>

<style>
	#add_doverennost_btn {
		background: url(../img/bt_speed_dial_1x.png) no-repeat scroll center center transparent;
		bottom: 100px;
		cursor: pointer;
		width: 56px;
		height: 56px;
		opacity: .4;
		position: fixed;
		right: 50px;
		z-index: 9;
		border-radius: 50%;
		background-color: #16A085;
		box-shadow: 0 0 4px rgba(0,0,0,.14), 0 4px 8px rgba(0,0,0,.28);
	}

	#add_doverennost_btn:hover {
		opacity: 1;
	}

	.forms input[type="text"] {
		width: 99%;
	}

	.forms .left {
		width: 250px;
	}
</style>

<a id='add_doverennost_btn' href='#' title='Создать доверенность'></a>

<table>
	<thead>
		<tr>
			<th>Номер</th>
			<th>Дата</th>
			<th>Фирма продавец</th>
			<th>На имя</th>
			<th>Автор</th>
			<th></th>
		</tr>
	</thead>
	<tbody>

<?
	$query = "SELECT PFD.PFD_ID
					,PFD.count
					,DATE_FORMAT(PFD.date, '%d.%m.%y') date_format
					,PFD.firma_prodavetc
					,PFD.fio
					,USR_Name(PFD.USR_ID) Name
				FROM PrintFormsDoverennost PFD
				WHERE YEAR(PFD.date) = {$year}
				ORDER BY PFD.date DESC, PFD.PFD_ID DESC";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		echo "<tr>";
		echo "<td><b>{$row["count"]}</b></td>";
		echo "<td><a href='open_print_form.php?type=doverennost&PFD_ID={$row["PFD_ID"]}&number={$row["count"]}' target='_blank'><b>{$row["date_format"]}</b></a></td>";
		echo "<td>{$row["firma_prodavetc"]}</td>";
		echo "<td>{$row["fio"]}</td>";
		echo "<td>{$row["Name"]}</td>";
		echo "</tr>";
	}
?>

	</tbody>
</table>

<!-- Форма подготовки доверенности -->
<div id='add_doverennost_form' style='display:none' title="Доверенность на получение товарно-материальных ценностей">
	<h1>Доверенность на получение товарно-материальных ценностей</h1>
	<form method="post" id="formdiv" action="?add_doverennost=1">

		<div class="formdiv">
			<table style="width: 100%" border="0" cellspacing="4" class="forms">
				<tbody>
					<tr class="forms">
						<td class="left" align="left">Дата составления:
						<td valign="top"><input type="text" name="date" id="date" value="<?=date("d.m.Y")?>" class="" autocomplete="off" readonly style="width: 90px; text-align: center;"></td>
					</tr>
					<tr class="forms">
						<td class="left" align="left">Срок действия доверенности по:</td>
						<td valign="top"><input type="text" name="date_end" id="date_end" class="date" value="<?=(date_format(date_modify(date_create(date('Y-m-d')), '+10 day'), 'd.m.Y'))?>" autocomplete="off" readonly style="width: 90px; text-align: center;"></td>
					</tr>

					<script>
						$(function() {
							$('#date_end').datepicker( "option", "minDate", "<?=( date('d.m.Y') )?>" );
						});
					</script>

					<tr class="forms">
						<td class="left" align="left">От кого или в какой  организации необходимо получить товар</td>
						<td valign="top"><input type="text" name="firma_prodavetc" id="firma_prodavetc" class="forminput" placeholder="" value=""></td>
					</tr>
					<tr class="forms">
						<td class="left" align="left">По документу:</td>
						<td valign="top"><input type="text" name="doc" id="doc" class="forminput" placeholder="" value=""></td>
					</tr>
				</tbody>
			</table>

			<br>

			<table style="width: 100%" border="0" cellspacing="4" class="forms">
				<tbody>
					<tr align="left">
						<td colspan="2"><strong>Информация о лице на чье имя выдается доверенность:</strong></td>
					</tr>
					<tr>
						<td class="left" align="left" valign="top">Должность, Ф.И.О. полностью, в дательном падеже:</td>
						<td valign="top">
						<input type="text" name="fio" id="fio" class="forminput" placeholder="водителю Иванову Ивану Ивановичу" value=""></td>
					</tr>
					<tr>
						<td class="left" align="left" valign="top">Паспорт:</td>
						<td valign="top">
							Серия:
							<div style="max-width:120px; display:inline-block;"><input type="text" name="pasport_seriya" id="pasport_seriya" class="forminput_seriya" placeholder="01 11" value=""></div>
							№:
							<div style="max-width:120px; display:inline-block;"><input type="text" name="pasport_nomer" id="pasport_nomer" class="forminput_N" placeholder="654321" value=""></div></td>
					</tr>
					<tr>
						<td class="left" align="left" valign="top">Кем выдан:</td>
						<td valign="top"><input type="text" name="pasport_vidan_kem" id="pasport_vidan_kem" class="forminput" placeholder="ФМС г. Москвы" value=""></td>
					</tr>
					<tr>
						<td class="left" align="left" valign="top">Дата выдачи: </td>
						<td valign="top"><input type="text" name="pasport_vidan_data" id="pasport_vidan_data" class="forminput" placeholder="12.12.2011" value=""></td>
					</tr>
				</tbody>
			</table>

			<br>

			<table style="width: 100%" border="0" cellspacing="4" class="forms" id="tab1">
				<tbody>
					<tr>
						<th colspan="4" align="left"><strong>Перечень товарно-материальных ценностей, подлежащих получению:</strong></th>
					</tr>
					<tr>
						<th width="442" bgcolor="#D6D6D6">Наименование получаемых предметов</th>
						<th width="100" bgcolor="#D6D6D6">Количество</th>
						<th width="100" bgcolor="#D6D6D6">Единица измерения</th>
						<th width="20"><p>&nbsp;</p></th>
					</tr>
					<tr>
						<td><input type="text" name="tovar_name[]" id="rab" class="forminput_TMX" value=""></td>
						<td><input type="text" name="tovar_kol[]" id="rab" class="forminput_seriya" value=""></td>
						<td><input type="text" name="tovar_ed[]" id="rab" class="forminput_seriya" value=""></td>
						<td><i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i></td>
					</tr>
				</tbody>

				<tbody>
					<tr>
						<td colspan="4">
							<i class="fa fa-plus-square fa-2x" style="color: green;" onclick="addRow('шт');"></i>
							<span onclick="addRow('шт');"><font><font> Добавить строку</font></font></span>
						</td>
					</tr>
				</tbody>
			</table>

<script type="text/javascript">
	var d = document;

	function addRow() {

		// Находим нужную таблицу
		var tbody = d.getElementById('tab1').getElementsByTagName('TBODY')[0];

		// Создаем строку таблицы и добавляем ее
		var row = d.createElement("TR");
		tbody.appendChild(row);

		// Создаем ячейки в вышесозданной строке
		// и добавляем тх
		var td1 = d.createElement("TD");
		var td2 = d.createElement("TD");
		var td3 = d.createElement("TD");
		var td4 = d.createElement("TD");

		row.appendChild(td1);
		row.appendChild(td2);
		row.appendChild(td3);
		row.appendChild(td4);
		// Наполняем ячейки
		td1.innerHTML = '<input type="text" name="tovar_name[]" id="rab" class="forminput_TMX" />';
		td2.innerHTML = '<input type="text" name="tovar_kol[]" id="rab" class="forminput_seriya" />';
		td3.innerHTML = '<input type="text" name="tovar_ed[]" id="rab" class="forminput_seriya" />';
		td4.innerHTML = '<i class="fa fa-minus-square fa-2x" style="color: red;" onclick="deleteRow(this);"></i>';

	}

	function deleteRow(r) {
		var i=r.parentNode.parentNode.rowIndex;
		document.getElementById('tab1').deleteRow(i);
	}
</script>

			<input name="n" type="hidden" value="1">

			<div>
				<hr>
				<input type='submit' value='Создать доверенность' style='float: right;'>
			</div>

		</div>
	</form>
</div>

<script>
	$(function() {

		// Форма составления накладной
		$('#add_doverennost_btn').click(function() {
			$('#add_doverennost_form').dialog({
				position: { my: "center top", at: "center top", of: window },
				draggable: false,
				width: 700,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
		});
	});
</script>
<?
	include "footer.php";
?>
