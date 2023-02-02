<?
include "config.php";
include "checkrights.php";

//Редактирование компонента
if( isset($_POST["density"]) ) {
	session_start();

	// Обработка строк
	$material = convert_str($_POST["material"]);
	$material = mysqli_real_escape_string($mysqli, $material);
	$vendor = convert_str($_POST["vendor"]);
	$vendor = mysqli_real_escape_string($mysqli, $vendor);

	if( isset($_POST["PC_ID"]) ) {
		$query = "
			UPDATE paint__Component
			SET material = '{$material}'
				,vendor = '{$vendor}'
				,density = {$_POST["density"]}
			WHERE PC_ID = {$_POST["PC_ID"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		$PC_ID = $_POST["PC_ID"];
	}
	else {
		$query = "
			INSERT INTO paint__Component
			SET material = '{$material}'
				,vendor = '{$vendor}'
				,density = {$_POST["density"]}
		";
		if( !mysqli_query( $mysqli, $query ) ) {
			$_SESSION["error"][] = "Invalid query: ".mysqli_error( $mysqli );
		}
		else {
			$add = 1;
			$PC_ID = mysqli_insert_id( $mysqli );
		}
	}

	if( !isset($_SESSION["error"]) ) {
		$_SESSION["success"][] = $add ? "Новая запись успешно добавлена." : "Запись успешно отредактирована.";
	}

	// Перенаправление в журнал
	exit ('<meta http-equiv="refresh" content="0; url=#'.$PC_ID.'">');
}

$title = 'Материалы краски';
include "header.php";

//// Проверка прав на доступ к экрану
//if( !in_array('stepstariffs', $Rights) ) {
//	header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
//	die('Недостаточно прав для совершения операции');
//}
?>

<!--Таблица с материалами-->
<table class="MN_table">
	<thead>
		<tr>
			<th>Материал</th>
			<th>Артикул</th>
			<th>Плотность</th>
			<th></th>
		</tr>
	</thead>
	<tbody style="text-align: center;">

<?
$query = "
	SELECT PC.PC_ID
		,PC.material
		,PC.vendor
		,PC.density
	FROM paint__Component PC
	ORDER BY PC.material, PC.vendor
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while( $row = mysqli_fetch_array($res) ) {
	?>
	<tr id="<?=$row["PC_ID"]?>">
		<td><?=$row["material"]?></td>
		<td><?=$row["vendor"]?></td>
		<td><?=$row["density"]?></td>
		<td><a href="#" class="material_edit" PC_ID="<?=$row["PC_ID"]?>" material="<?=htmlspecialchars($row["material"])?>" vendor="<?=htmlspecialchars($row["vendor"])?>" density="<?=$row["density"]?>" title="Редактировать"><i class="fa fa-pencil-alt fa-lg"></i></a></td>
	</tr>
	<?
}
?>
	</tbody>
</table>
<!--Конец таблицы с материалами-->

<div id='add_btn' class="material_edit" title='Добавить материал'></div>

<!--Форма редактирования-->
<style>
	#material_edit_form table td {
		font-size: 1.5em;
	}
</style>

<div id='material_edit_form' title='Изменение материала' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type="hidden" name="PC_ID">

			<table style="width: 100%; table-layout: fixed;">
				<thead>
					<tr>
						<th>Материал</th>
						<th>Артикул</th>
						<th>Плотность</th>
					</tr>
				</thead>
				<tbody style="text-align: center;">
					<tr>
						<td><input type="text" name="material" autocomplete='off' required></td>
						<td><input type="text" name="vendor" autocomplete='off' required></td>
						<td><input type="number" name="density" min="0.9" max="1.1" step="0.001" style="width: 100px;" required></td>
					</tr>
				</tbody>
			</table>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Записать' style='float: right;'>
		</div>
	</form>
</div>
<!--Конец формы-->
<script>
	$(function() {
		$('.material_edit').click( function() {
			// Очистка формы
			$('#material_edit_form input[name="PC_ID"]').val('');
			$('#material_edit_form input[name="PC_ID"]').attr('disabled', true);
			$('#material_edit_form input[name="material"]').val('');
			$('#material_edit_form input[name="vendor"]').val('');
			$('#material_edit_form input[name="density"]').val('');

			var PC_ID = $(this).attr("PC_ID");

			if( PC_ID ) {
				var material = $(this).attr("material"),
					vendor = $(this).attr("vendor"),
					density = $(this).attr("density");

				$('#material_edit_form input[name="PC_ID"]').attr('disabled', false);
				$('#material_edit_form input[name="PC_ID"]').val(PC_ID);
				$('#material_edit_form input[name="material"]').val(material);
				$('#material_edit_form input[name="vendor"]').val(vendor);
				$('#material_edit_form input[name="density"]').val(density);
			}


			$('#material_edit_form').dialog({
				resizable: false,
				width: 1000,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

	});
</script>

<?
include "footer.php";
?>
