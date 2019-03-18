<!-- Форма добавления набора -->
<div id='order_form' class='addproduct' title='Новый набор' style='display:none;'>
	<form method='post'>
		<fieldset>
			<div>
				<label>Подразделение:</label>
				<select required name='Shop' style="width: 300px;">
					<?
					if( !$USR_Shop ) {
						echo "<option value=''>-=Выберите подразделение=-</option>";
					}
					if( in_array('order_add_confirm', $Rights) ) {
						echo "<option value='0' style='background: #999;'>Свободные</option>";
					}
					$query = "SELECT SH.SH_ID
									,CONCAT(CT.City, '/', SH.Shop) AS Shop
									,CT.Color
									,IF(SH.KA_ID IS NULL, 1, 0) retail
								FROM Shops SH
								JOIN Cities CT ON CT.CT_ID = SH.CT_ID
								WHERE CT.CT_ID IN ({$USR_cities})
									".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
									".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
								ORDER BY CT.City, SH.Shop";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

					$num_rows = 0;
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<option value='{$row["SH_ID"]}' retail='{$row["retail"]}' style='background: {$row["Color"]};'>{$row["Shop"]}</option>";
						++$num_rows;
						$sh_id = $row["SH_ID"];
					}
					?>
				</select>
			</div>
			<div id="ClientName">
				<label>Клиент:</label>
				<div>
					<input type='text' class='clienttags' name='ClientName' autocomplete='off'>
					<input type="checkbox" id="ul" name='ul' title='Поставьте галочку если требуется накладная.'>
					<label for="ul">юр. лицо</label>
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
				<textarea name='address' rows='2' cols='38'></textarea>
			</div>
			<div id="StartDate">
				<label>Дата продажи:</label>
				<input type='text' name='StartDate' class='date' size='12' readonly autocomplete='off'>
				<span style='color: #911;'>Оставьте пустым если на выставку.</span>
			</div>
			<div id="EndDate">
				<label>Дата сдачи:</label>
				<input type='text' name='EndDate' class='date' size='12' <?=(in_array('order_add_confirm', $Rights) ? "" : "disabled")?> autocomplete='off'>
				<span style='color: #911;'>+30 рабочих дней</span>
			</div>
			<p style='color: #911;'>ВНИМАНИЕ! Патина указывается у каждого изделия персонально в специальной графе "патина".</p>
			<div>
				<label>Цвет краски:</label>
				<div style="display: inline-block;">
					<input type='text' id='paint_color' class='colortags' name='Color' style='width: 300px;' placeholder='ЗДЕСЬ ПАТИНУ УКАЗЫВАТЬ НЕ НУЖНО'>
					<div class='btnset'>
						<input required type='radio' id='clear1' name='clear' value='1'>
							<label for='clear1'>Прозрачный</label>
						<input required type='radio' id='clear0' name='clear' value='0'>
							<label for='clear0'>Эмаль</label>
					</div>
					<i class='fa fa-question-circle' style='margin: 5px;' title='Прозрачное поктытие - это покрытие, при котором просматривается структура дерева (в том числе лак, тонированный эмалью). Эмаль - это непрозрачное покрытие.'>Подсказка</i>
				</div>

			</div>
			<div>
				<label>Примечание:</label>
				<textarea name='Comment' rows='3' cols='38'></textarea>
			</div>
		</fieldset>
		<div>
			<hr>
			<input type='submit' value='Создать' style='float: right;'>
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
			$('#order_form fieldset #ul').val('1');
			$('#order_form fieldset #ul').prop( "checked", false );
			$('#order_form .btnset input').prop( "checked", false );

			// Скрытие полей
			$('#order_form #ClientName').hide('fast');
				$('#order_form #ClientName input').attr('disabled', true);
			$('#order_form #OrderNumber').hide('fast');
				$('#order_form #OrderNumber input').attr('disabled', true);
			$('#order_form #Phone').hide('fast');
				$('#order_form #Phone input').attr('disabled', true);
			$('#order_form #Address').hide('fast');
				$('#order_form #Address textarea').attr('disabled', true);
			$('#order_form #StartDate').hide('fast');
				$('#order_form #StartDate input').attr('disabled', true);

			<?=(($num_rows == 1) ? "$('#order_form select[name=Shop]').val('{$sh_id}').trigger('change');" : "")?>
//$('#order_form select[name="Shop"]').val('2').trigger('change');
			// Деактивация кнопок типа покраски
			clearonoff('#paint_color');

			if (odd) {
				$("#order_form form").attr("action", "index.php?odd="+odd);
			}

			$('#order_form').dialog({
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

		// Если выбран розничный салон - показываем доп поля в форме добавления набора
		$('#order_form select[name="Shop"]').on("change", function() {
			var value = $(this).val();
			var retail = $('#order_form select[name="Shop"] option:selected').attr('retail');
			if( value > 0 ) {
				$('#order_form #EndDate').show('fast');
			}
			else {
				$('#order_form #EndDate').hide('fast');
			}

			if( retail == 1 ) {
				$('#order_form #ClientName').show('fast');
					$('#order_form #ClientName input').attr('disabled', false);
				$('#order_form #OrderNumber').show('fast');
					$('#order_form #OrderNumber input').attr('disabled', false);
				$('#order_form #Phone').show('fast');
					$('#order_form #Phone input').attr('disabled', false);
				$('#order_form #Address').show('fast');
					$('#order_form #Address textarea').attr('disabled', false);
				$('#order_form #StartDate').show('fast');
					$('#order_form #StartDate input').attr('disabled', false);
			}
			else {
				$('#order_form #ClientName').hide('fast');
					$('#order_form #ClientName input').attr('disabled', true);
				$('#order_form #OrderNumber').hide('fast');
					$('#order_form #OrderNumber input').attr('disabled', true);
				$('#order_form #Phone').hide('fast');
					$('#order_form #Phone input').attr('disabled', true);
				$('#order_form #Address').hide('fast');
					$('#order_form #Address textarea').attr('disabled', true);
				$('#order_form #StartDate').hide('fast');
					$('#order_form #StartDate input').attr('disabled', true);
			}
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
