<?
	// Обновление/добавление платежа
	if( isset($_POST["Pay"]) )
	{
		include "config.php";
		include "header.php";

		$ManDate = '\''.date( 'Y-m-d', strtotime($_POST["ManDate"]) ).'\'';
		$Worker = $_POST["Worker"] <> "" ? $_POST["Worker"] : "NULL";
		$Pay = $_POST["Pay"] <> "" ? $_POST["Pay"] : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		$Sign = $_POST["sign"];
		$location = $_POST["location"];
		$plid = $_POST["plid"];
		$bank = $_POST["bank"] ? $_POST["bank"] : "NULL";

		// Редактирование
		if( $_POST["PL_ID"] <> "" ) {
			$query = "UPDATE PayLog
					  SET ManDate = {$ManDate}, WD_ID = {$Worker}, Pay = {$Sign}{$Pay}, Comment = '{$Comment}', bank = {$bank}
					  WHERE PL_ID = '{$_POST["PL_ID"]}'";
		}
		// Добавление
		else {
			$query = "INSERT INTO PayLog(ManDate, WD_ID, Pay, Comment, bank)
					  VALUES ({$ManDate}, {$Worker}, {$Sign}{$Pay}, '{$Comment}', {$bank})";
		}
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		//header( "Location: ".$location."#pl".$plid );
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#pl'.$plid.'">');
		die;
	}
?>

<style>
	input[type="checkbox"]#bank + label:before {
		content: "Нал";
	}
	input[type="checkbox"]#bank:checked + label:before {
		content: "Безнал";
	}
</style>

<!-- Форма добавления платежа -->
<div id='addpay' class="addproduct" style='display:none'>
	<form method="post" action="form_addpay.php">
		<fieldset>
			<input type='hidden' name='PL_ID'>
			<input type='hidden' name='sign'>
			<input type='hidden' name='location'>
			<input type='hidden' name='plid'>
			<div>
				<label>Работник:</label>
				<select required name='Worker'>
					<option value="">-=Выберите работника=-</option>
					<?
					$query = "SELECT WD.WD_ID, WD.Name FROM WorkersData WD ORDER BY WD.Name";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<option value='{$row["WD_ID"]}'>{$row["Name"]}</option>";
					}
					?>
				</select>
			</div>
			<div>
				<label>Дата:</label>
				<input required type='text' name='ManDate' class='date' size='12' autocomplete='off' readonly>
			</div>
			<div>
				<label>Сумма:</label>
				<input required type='number' name='Pay' min='0' style="text-align:right; width: 90px;">
				<div style="display: inline-block; height: 15px;" id="wr_bank">
					<input type="checkbox" name="bank" id="bank" class="button" value="1">
					<label for="bank"></label>
				</div>
			</div>
			<div>
				<label>Примечание:</label>
				<textarea name='Comment' rows='4' cols='25'></textarea>
			</div>
		</fieldset>
		<div>
			<hr>
			<button type='submit' style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>

<script>
	$(document).ready(function() {
		// Форма добавления платежа
		$('.edit_pay').click(function() {
			var id = $(this).attr('id');
			var location = $(this).attr('location');
			var sign = $(this).attr('sign');
			var worker = $(this).attr('worker');
			var date = $(this).attr('date');

			// Очистка диалога
			$( '#addpay input[type="number"], #addpay select, #addpay textarea' ).val('');
			$( '#bank' ).prop('checked', false);
			$( '#bank' ).button("refresh");

			// Заполнение
			$('#addpay input[name="sign"]').val(sign);
			$('#addpay input[name="location"]').val(location);
			$('#addpay input[name="plid"]').val(id);
			$('#addpay input[name="ManDate"]').val(date);

			if( typeof worker !== "undefined" ) {
				$('#addpay select[name="Worker"]').val(worker);
			}

			if( typeof id !== "undefined" ) // Редактирование платежа
			{
				var pay = $(this).parents('tr').find('.pay').attr('val');
				var comment = $(this).parents('tr').find('.comment > span').html();
				var bank = $(this).attr('bank');
				$('#addpay input[name="Pay"]').val(pay);
				$('#addpay textarea[name="Comment"]').val(comment);
				$('#addpay input[name="PL_ID"]').val(id);
				if( bank ) {
					$( '#bank' ).prop('checked', true);
					$( '#bank' ).button("refresh");
				}
			}
			if( typeof $(this).attr('comment') !== "undefined" ) { // Добавление премии из табеля
				var pay = $(this).attr('pay');
				var comment = $(this).attr('comment');
				$('#addpay input[name="Pay"]').val(pay);
				$('#addpay textarea[name="Comment"]').val(comment);
			}

			// Вызов формы
			$('#addpay').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			if (sign == '-') {
				$('#addpay').dialog('option', 'title', 'Выдать');
				$('#wr_bank').show();
			}
			else {
				$('#addpay').dialog('option', 'title', 'Начислить');
				$('#wr_bank').hide();
			}
			return false;
		});
	});
</script>
