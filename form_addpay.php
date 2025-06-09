<?php
	// Добавление начисления/выдачи
	if( isset($_POST["Pay"]) ) {
		if( $_POST["Pay"] ) {
			include "config.php";
			include "header.php";

			$Comment = convert_str($_POST["Comment"]);
			$Comment = mysqli_real_escape_string( $mysqli, $Comment );

			//Выдачу сохраняем в Finance
			if( $_POST["account"] ) {
				$money = abs($_POST["Pay"]);
				$category = $_POST["Pay"] > 0 ? 1 : 2;
				$query = "
					INSERT INTO Finance
					SET money = {$money}
						,FA_ID = {$_POST["account"]}
						,FC_ID = {$category}
						,USR_ID = {$_POST["Worker"]}
						".($Comment ? ",comment = '{$Comment}'" : "")."
						,author = {$_SESSION['id']}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
			// Начисление сохраняем в PayLog
			else {
				$query = "
					INSERT INTO PayLog
					SET USR_ID = {$_POST["Worker"]}
						,Pay = {$_POST["Pay"]}
						".($Comment ? ",Comment = '{$Comment}'" : "")."
						,author = {$_SESSION['id']}
				";
				mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			}
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$_POST["location"].'">');
		die;
	}
?>

<!-- Форма добавления платежа -->
<div id='addpay' class="addproduct" style='display:none'>
	<form method="post" action="form_addpay.php" onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<i class="fas fa-3x fa-user-check" style="color: #fff; position: absolute; right: 30px; top: 20px; display: none;"></i>
			<i class="fas fa-3x fa-user-cog" style="color: #fff; position: absolute; right: 30px; top: 20px; display: none;"></i>
			<input type='hidden' name='location'>
			<div>
				<label>Сумма:</label>
				<input required type='number' name='Pay' style="text-align:right; width: 100px; font-size: 20px;">
			</div>
			<div>
				<label>Работник:</label>
				<b id="worker_name"></b>
				<input type="hidden" name="Worker">
			</div>
			<div id="wr_account">
				<label>Счёт:</label>
				<select name="account" id="account">
					<option value="">-=Выберите счёт=-</option>
						<?php
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
				<input type='text' name='Comment' style="width: 300px;" autocomplete="off">
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
		// Форма добавления платежа
		$('.edit_pay').click(function() {
			var location = $(this).attr('location');
			var account = $(this).attr('account');
			var worker = $(this).attr('worker');
			var worker_name = $(this).attr('worker_name');
			var pay = $(this).attr('pay');
			var comment = $(this).attr('comment');

			// Очистка диалога
			$( '#addpay input[type="number"], #addpay input[type="text"], #addpay input[type="hidden"]' ).val('');

			// Заполнение
			$('#addpay input[name="location"]').val(location);
			$('#addpay select[name="account"]').val(account);
			$('#addpay input[name="Pay"]').val(pay);
			$('#addpay input[name="Worker"]').val(worker);
			$('#addpay #worker_name').html(worker_name);
			$('#addpay input[name="Comment"]').val(comment);

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
				$('#addpay fieldset').css('background', '#f0adab');
				$('#addpay .fa-user-check').show();
				$('#addpay .fa-user-cog').hide();
			}
			else {
				$('#addpay').dialog('option', 'title', 'Начислить');
				$('#wr_account').hide();
				$('#account').prop('required',false);
				$('#addpay fieldset').css('background', '#a0d8ce');
				$('#addpay .fa-user-cog').show();
				$('#addpay .fa-user-check').hide();
			}
			return false;
		});
	});
</script>
