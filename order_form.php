<!-- Форма добавления набора -->
<div id='order_form' class='addproduct' title='Новый набор' style='display:none;'>
	<form method='post' onsubmit="JavaScript:this.subbut.disabled=true;
this.subbut.value='Подождите, пожалуйста!';">
		<fieldset>
			<fieldset>
				<legend>Подразделение:</legend>
				<select required name='Shop' style="width: 100%;">
					<?
					if( !$USR_Shop ) {
						echo "<option value=''>-=Выберите подразделение=-</option>";
					}
					if( in_array('order_add_confirm', $Rights) ) {
						echo "<option value='0' style='background: #999;'>-=Свободные=-</option>";
					}

					$num_rows = 0;

					$query = "
						SELECT CT.CT_ID
							,CT.City
						FROM Cities CT
						WHERE CT.CT_ID IN ({$USR_cities})
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
				<input type='text' name='EndDate' class='date' size='12' <?=(in_array('order_add_confirm', $Rights) ? "" : "disabled")?> autocomplete='off'>
				<span style='color: #911;'>автоматически +30 рабочих дней</span>
			</fieldset>
			<fieldset id="Client">
				<legend>Информация о клиенте:</legend>
				<div id="ClientName">
					<label>Клиент:</label>
					<div>
						<input type='text' class='clienttags' name='ClientName' autocomplete='off'>
						<label title='Поставьте галочку, чтобы была возможность сделать накладную.'><input type="checkbox" name='ul'>юр. лицо<i class='fa fa-question-circle'></i></label>
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
				<legend>Цвет краски:</legend>
				<p style='color: #911;'>ВНИМАНИЕ! Патина указывается у каждого изделия персонально в специальной графе "патина".</p>
				<div style="display: inline-block;">
					<input type='text' id='paint_color' class='colortags' name='Color' style='width: 100%;' placeholder='ЗДЕСЬ ПАТИНУ УКАЗЫВАТЬ НЕ НУЖНО'>
					<div class='btnset'>
						<input required type='radio' id='clear1' name='clear' value='1'>
							<label for='clear1'>Прозрачный</label>
						<input required type='radio' id='clear0' name='clear' value='0'>
							<label for='clear0'>Эмаль</label>
					</div>
					<i class='fa fa-question-circle' style='margin: 5px;' title='Прозрачное поктытие - это покрытие, при котором просматривается структура дерева (в том числе лак, тонированный эмалью). Эмаль - это непрозрачное покрытие.'>Подсказка</i>
				</div>

			</fieldset>
			<fieldset>
				<legend>Примечание:</legend>
				<textarea name='Comment' rows='3' style='width: 100%;'></textarea>
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
		// Кнопка добавления набора
		$('.add_order').click( function() {
			odd = $(this).attr('odd');

			// Очистка формы
			$('#order_form fieldset select').val('').trigger('change');
			$('#order_form fieldset input[type="text"]').val('');
			$('#order_form fieldset textarea').val('');
			$('#order_form fieldset input[name="EndDate"]').val('<?=$_SESSION["end_date"]?>');
			$('#order_form fieldset input[name="ul"]').val('1');
			$('#order_form fieldset input[name="ul"]').prop( "checked", false );
			$('#order_form .btnset input').prop( "checked", false ).button('refresh');

			// Скрытие полей
			$('#order_form #Client').hide('fast');
			$('#order_form #Client input, #order_form #Client textarea').attr('disabled', true);
			$('#order_form #StartDate').hide('fast');
			$('#order_form #StartDate input').attr('disabled', true);
			$('#order_form #EndDate').hide('fast');
			$('#order_form #EndDate input').attr('disabled', true);

			<?=(($num_rows == 1) ? "$('#order_form select[name=Shop]').val('{$sh_id}').trigger('change');" : "")?>

			// Деактивация кнопок типа покраски
			clearonoff('#paint_color');

			if (odd) {
				$("#order_form form").attr("action", "index.php?odd="+odd);
			}

			$('#order_form').dialog({
				resizable: false,
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			$( ".colortags" ).autocomplete( "option", "appendTo", "#order_form" );

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
				$('#order_form #StartDate').show('fast');
				$('#order_form #StartDate input').attr('disabled', false);
				$('#order_form #EndDate').hide('fast');
				$('#order_form #EndDate input').attr('disabled', true);
			}
			else {
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
