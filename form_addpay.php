<?
	// Добавление платежа/редактирование выдачи
	if( isset($_POST["Pay"]) )
	{
		include "config.php";
		include "header.php";

		$PL_ID = $_POST["pl_id"];
		$Worker = $_POST["Worker"] <> "" ? $_POST["Worker"] : "NULL";
		$Pay = $_POST["Pay"] <> "" ? $_POST["Pay"] : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		$Sign = $_POST["account"] ? "-" : "";
		$location = $_POST["location"];
		$account = $_POST["account"] ? $_POST["account"] : "NULL";

		if( $PL_ID ) { //Редактирование выдачи
			$query = "UPDATE PayLog
						SET WD_ID = {$Worker}, Pay = {$Sign}{$Pay}, Comment = '{$Comment}', FA_ID = {$account}, author = {$_SESSION['id']}
						WHERE PL_ID = {$PL_ID}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}
		else { // Добавление
			$query = "INSERT INTO PayLog(WD_ID, Pay, Comment, FA_ID, author)
					  VALUES ({$Worker}, {$Sign}{$Pay}, '{$Comment}', {$account}, {$_SESSION['id']})";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}
?>

<!-- Форма добавления платежа -->
<div id='addpay' class="addproduct" style='display:none'>
	<form method="post" action="form_addpay.php" onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<input type='hidden' name='pl_id'>
			<input type='hidden' name='sign'>
			<input type='hidden' name='location'>
			<div>
				<label>Работник:</label>
				<select required name="Worker" id="worker" style="width: 200px;">
					<option value="">-=Выберите работника=-</option>
					<optgroup label="Работающие">
						<?
						$query = "SELECT WD.WD_ID, WD.Name FROM WorkersData WD WHERE IsActive = 1 ORDER BY WD.Name";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["WD_ID"]}'>{$row["Name"]}</option>";
						}
						?>
					</optgroup>
					<optgroup label="Уволенные">
						<?
						$query = "SELECT WD.WD_ID, WD.Name FROM WorkersData WD WHERE IsActive = 0 ORDER BY WD.Name";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["WD_ID"]}'>{$row["Name"]}</option>";
						}
						?>
					</optgroup>
				</select>
			</div>
			<div>
				<label>Сумма:</label>
				<input required type='number' name='Pay' min='0' style="text-align:right; width: 90px;">
			</div>
			<div id="wr_account">
				<label>Счёт:</label>
				<select name="account" id="account">
					<option value="">-=Выберите счёт=-</option>
						<?
						if( !in_array('finance_account', $Rights) ) {
							echo "<optgroup label='Нал'>";
							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 0 AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
							}
							echo "</optgroup>";
							echo "<optgroup label='Безнал'>";

							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE IFNULL(bank, 0) = 1 AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
							}
							echo "</optgroup>";
						}
						else {
							$query = "SELECT FA_ID, name FROM FinanceAccount WHERE USR_ID = {$_SESSION["id"]} AND archive = 0";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["FA_ID"]}'>{$row["name"]}</option>";
							}
						}
						?>
				</select>
			</div>
			<div>
				<label>Примечание:</label>
				<input type='text' name='Comment'>
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>

<script>
	$(document).ready(function() {
		$('#worker').select2({ placeholder: 'Выберите работника', language: 'ru' });

		// Костыль для Select2 чтобы работал поиск
		$.ui.dialog.prototype._allowInteraction = function (e) {
			return true;
		};

		// Форма добавления платежа
		$('.edit_pay').click(function() {
			var id = $(this).attr('id');
			var location = $(this).attr('location');
			var sign = $(this).attr('sign');
			var account = $(this).attr('account');
			var worker = $(this).attr('worker');
			var pay = $(this).attr('pay');
			var comment = $(this).attr('comment');

			// Очистка диалога
			$( '#addpay input[type="number"], #addpay select, #addpay input[type="text"], #addpay input[type="hidden"]' ).val('');
			$('#addpay #worker').trigger('change');

			// Заполнение
			$('#addpay input[name="pl_id"]').val(id);
			$('#addpay input[name="location"]').val(location);
			$('#addpay input[name="sign"]').val(sign);
			$('#addpay select[name="account"]').val(account);
			$('#addpay select[name="Worker"]').val(worker).trigger('change');
			$('#addpay input[name="Pay"]').val(pay);
			$('#addpay input[name="Comment"]').val(comment);


//			if( typeof worker !== "undefined" ) {
//				$('#addpay select[name="Worker"]').val(worker).trigger('change');
//			}

//			if( typeof $(this).attr('comment') !== "undefined" ) { // Добавление премии из табеля
//				var pay = $(this).attr('pay');
//				var comment = $(this).attr('comment');
//				$('#addpay input[name="Pay"]').val(pay);
//				$('#addpay input[name="Comment"]').val(comment);
//			}

			// Вызов формы
			$('#addpay').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});

			if (account !== undefined) {
				$('#addpay').dialog('option', 'title', 'Выдать');
				$('#wr_account').show();
				$('#account').prop('required',true);
				$('#addpay input[name="Pay"]').attr('min', 0);
			}
			else {
				$('#addpay').dialog('option', 'title', 'Начислить');
				$('#wr_account').hide();
				$('#account').prop('required',false);
				$('#addpay input[name="Pay"]').removeAttr('min');
			}
			return false;
		});
	});
</script>
