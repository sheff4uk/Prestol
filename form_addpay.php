<?
	// Обновление/добавление платежа
	if( isset($_POST["Pay"]) )
	{
		include "config.php";
		$Worker = $_POST["Worker"] <> "" ? $_POST["Worker"] : "NULL";
		$Pay = $_POST["Pay"] <> "" ? $_POST["Pay"] : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		$Sign = $_POST["sign"];
		$location = $_POST["location"];

		// Редактирование
		if( $_POST["PL_ID"] <> "" ) {
			$query = "UPDATE PayLog
					  SET WD_ID = {$Worker}, Pay = {$Sign}{$Pay}, Comment = '{$Comment}'
					  WHERE PL_ID = '{$_POST["PL_ID"]}'";
		}
		// Добавление
		else {
			$query = "INSERT INTO PayLog(WD_ID, Pay, Comment)
					  VALUES ({$Worker}, {$Sign}{$Pay}, '{$Comment}')";
		}
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		header( "Location: ".$location );
		die;
	}
?>

<!-- Форма добавления платежа -->
<div id='addpay' class="addproduct" style='display:none'>
	<form method="post" action="form_addpay.php">
		<fieldset>
			<input type='hidden' name='PL_ID'>
			<input type='hidden' name='sign'>
			<input type='hidden' name='location'>
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
				<label>Сумма:</label>
				<input required type='number' name='Pay' min='0' style="text-align:right;">
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

			// Очистка диалога
			$('#addpay input, #addpay select, #addpay textarea').val('');

			// Заполнение
			$('#addpay input[name="sign"]').val(sign);
			$('#addpay input[name="location"]').val(location);

			if( typeof worker !== "undefined" ) {
				$('#addpay select[name="Worker"]').val(worker);
			}

			if( typeof id !== "undefined" ) // Редактирование платежа
			{
				var pay = $(this).parents('tr').find('.pay').attr('val');
				var comment = $(this).parents('tr').find('.comment > pre').html();
				$('#addpay input[name="Pay"]').val(pay);
				$('#addpay textarea[name="Comment"]').val(comment);
				$('#addpay input[name="PL_ID"]').val(id);
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
			});
			if (sign == '-') {
				$('#addpay').dialog('option', 'title', 'Выдать');
			}
			else {
				$('#addpay').dialog('option', 'title', 'Начислить');
			}
			return false;
		});
	});
</script>
