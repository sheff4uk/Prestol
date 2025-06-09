<!-- Форма добавления набора -->
<div id='order_form' class='addproduct' title='Новый набор' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<fieldset>
				<legend>Подразделение:</legend>
				<select required name='Shop' style="width: 100%;">
					<?php
					if( !$USR_Shop ) {
						echo "<option value=''>-=Выберите подразделение=-</option>";
					}

					$num_rows = 0;

					if( in_array('order_add_confirm', $Rights) or in_array('order_add_free', $Rights) ) {
						echo "<option value='0' style='background: #999;'>-=Свободные=-</option>";
						++$num_rows;
						$sh_id = 0;
					}

					$query = "
						SELECT CT.CT_ID
							,CT.City
						FROM Cities CT
						WHERE CT.CT_ID IN ({$USR_cities})
							".(in_array('order_add_free', $Rights) ? "AND 0" : "")."
							".($USR_Shop ? "AND CT.CT_ID IN (SELECT CT_ID FROM Shops WHERE SH_ID IN ({$USR_Shop}))" : "")."
							".($USR_KA ? "AND CT.CT_ID IN (SELECT CT_ID FROM Shops WHERE KA_ID = {$USR_KA})" : "")."
						ORDER BY CT.City
					";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<optgroup label='{$row["City"]}'>";
						$query = "
							SELECT SH.SH_ID
								,SH.Shop
								,IFNULL(SH.retail, 0) retail
							FROM Shops SH
							WHERE SH.CT_ID = ({$row["CT_ID"]})
								".($USR_Shop ? "AND SH.SH_ID IN ({$USR_Shop})" : "")."
								".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
							ORDER BY SH.retail DESC, SH.Shop
						";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

						while( $subrow = mysqli_fetch_array($subres) )
						{
							echo "<option value='{$subrow["SH_ID"]}' retail='{$subrow["retail"]}'>".($subrow["retail"] ? "&bull; " : "")."{$subrow["Shop"]}</option>";
							++$num_rows;
							$sh_id = $subrow["SH_ID"];
						}
						echo "</optgroup>";
					}

					?>
				</select>
			</fieldset>
			<fieldset id="StartDate">
				<legend>Категория:</legend>
				<div class='btnset'>
					<input type='radio' id='sell' name='StartDate' value='<?=date("Y-m-d")?>' required>
						<label for='sell'>Продажа</label>
					<input type='radio' id='show' name='StartDate' value='' required>
						<label for='show'>Выставка</label>
				</div>
			</fieldset>
			<fieldset id="EndDate">
				<legend>Дата сдачи:</legend>
				<input type='text' name='EndDate' style="width: 90px;" readonly autocomplete='off'>
				<span style='color: #911; display: inline-table;' id="day_limit"></span>
			</fieldset>
			<fieldset id="Client">
				<legend>Информация о клиенте:</legend>
				<div id="ClientName">
					<label>ФИО:</label>
					<div>
						<input type='text' class='clienttags' name='ClientName' autocomplete='off'>
					</div>
				</div>
				<div id="OrderNumber">
					<label>№ квитанции:</label>
					<input type='text' name='OrderNumber' autocomplete='off'>
				</div>
				<div id="Phone">
					<label>Телефон:</label>
					<input type='text' id='mtel' name='mtel' autocomplete='off'>
				</div>
				<div id="Address">
					<label>Адрес доставки:</label>
					<textarea name='address' rows='3' cols='35'></textarea>
				</div>
			</fieldset>
			<fieldset>
				<legend>Примечание:</legend>
				<? if (!in_array('order_add_confirm', $Rights) and !in_array('order_add_free', $Rights)) echo "<i class='fa fa-question-circle' title='Обо всех дополнительных особенностях набора сообщайте через кнопку \"Сообщение на производство\".'></i>"; ?>
				<textarea name='Comment' rows='3' style='width: 100%; resize: vertical;' <?=( (in_array('order_add_confirm', $Rights) or in_array('order_add_free', $Rights)) ? "" : "disabled" )?>></textarea>
			</fieldset>
		</fieldset>
		<div>
			<hr>
			<input type='submit' name="subbut" value='Создать' style='float: right;'>
		</div>
	</form>
</div>

<script>
	$(function() {
		// Ограничение даты сдачи
		$( '#order_form fieldset input[name="EndDate"]' ).datepicker( "option", "minDate", "<?=( date('d.m.Y') )?>" );

		// Кнопка добавления набора
		$('.add_order').click( function() {
			// Проверяем сессию
			$.ajax({ url: "check_session.php?script=1", dataType: "script", async: false });

			odd = $(this).attr('odd');

			// Очистка формы
			$('#order_form fieldset select').val('').trigger('change');
			$('#order_form fieldset input[type="text"]').val('');
			$('#order_form fieldset textarea').val('');
			$('#order_form fieldset input[name="EndDate"]').val('');
			$('#order_form .btnset input').prop( "checked", false ).button('refresh');

			// Скрытие полей
			$('#order_form #Client').hide('fast');
			$('#order_form #Client input, #order_form #Client textarea').attr('disabled', true);
			$('#order_form #StartDate').hide('fast');
			$('#order_form #StartDate input').attr('disabled', true);
			$('#order_form #EndDate').hide('fast');
			$('#order_form #EndDate input').attr('disabled', true);

			<?=(($num_rows == 1) ? "$('#order_form select[name=Shop]').val('{$sh_id}').trigger('change');" : "")?>

			if (odd) {
				$("#order_form form").attr("action", "index.php?odd="+odd);
			}

			$('#order_form').dialog({
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});

			return false;
		});

		// Смена категории Продажа/Выставка
		$('#order_form #StartDate input').on("change", function() {
			var StartDate = $(this).val();

			if( StartDate  && this.checked) {
				$('#order_form #Client').show('fast');
				$('#order_form #Client input, #order_form #Client textarea').attr('disabled', false);
				$('#order_form #EndDate').show('fast');
				$('#order_form #EndDate input').attr('disabled', false);
			}
			else {
				$('#order_form #Client').hide('fast');
				$('#order_form #Client input, #order_form #Client textarea').attr('disabled', true);
				$('#order_form #EndDate').hide('fast');
				$('#order_form #EndDate input').attr('disabled', true);
			}
		});

		// Если выбран розничный салон - показываем доп поля в форме добавления набора
		$('#order_form select[name="Shop"]').on("change", function() {
			var value = $(this).val();
			var retail = $('#order_form select[name="Shop"] option:selected').attr('retail');

			$('#order_form #StartDate input:checked').attr('checked', false).change();

			if (retail == 1) {
				// Запрашиваем дату сдачи
				$.ajax({ url: "get_end_date.php?retail=1&script=1", dataType: "script", async: false });

				$('#order_form #StartDate').show('fast');
				$('#order_form #StartDate input').attr('disabled', false);
				$('#order_form #EndDate').hide('fast');
				$('#order_form #EndDate input').attr('disabled', true);
			}
			else {
				// Запрашиваем дату сдачи
				$.ajax({ url: "get_end_date.php?retail=0&script=1", dataType: "script", async: false });

				$('#order_form #StartDate').hide('fast');
				$('#order_form #StartDate input').attr('disabled', true);
				if (value > 0) {
					$('#order_form #EndDate').show('fast');
					$('#order_form #EndDate input').attr('disabled', false);
				}
				// Если Свободные, то скрываем дату сдачи
				else {
					$('#order_form #EndDate').hide('fast');
					$('#order_form #EndDate input').attr('disabled', true);
				}
			}

			$('#order_form .btnset input').button('refresh');
		});

		// Select2 для выбора салона
		$('select[name="Shop"]').select2({
			placeholder: "Выберите подразделение",
			language: "ru"
		});
		// Костыль для Select2 чтобы работал поиск
//		$.ui.dialog.prototype._allowInteraction = function (e) {
//			return true;
//		};
	});

</script>
