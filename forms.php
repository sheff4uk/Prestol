<?
	// Массив форм столешниц в зависимости от модели
	$ModelForm = array();
	$query = "(SELECT 0 PM_ID, PF_ID, Form FROM ProductForms) UNION (SELECT PMF.PM_ID, PMF.PF_ID, PF.Form FROM ProductModelsForms PMF LEFT JOIN ProductForms PF ON PF.PF_ID = PMF.PF_ID)";
	$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($result) ) {
		$ModelForm[$row["PM_ID"]][$row["PF_ID"]] = [$row["Form"]];
	}
?>
	<script>
		// Передаем в JavaScript массив форм столешниц
		ModelForm = <?= json_encode($ModelForm); ?>;
	</script>

<!-- Форма добавления стула -->
<div id='addchair' title='Параметры стула' class='addproduct' style='display:none'>
	<form method='post'>
		<fieldset>
		<input type='hidden' value='1' name='Type'>
		<div>
			<label>Kол-во:</label>
			<input required type='number' min='1' value='1' style='width: 70px; font-size: 2em;' name='Amount' autocomplete="off">
			&nbsp;&nbsp;&nbsp;
			<img src='/img/attention.png' class='attention' id='Amount' title='Изделие в работе. Изменение кол-ва невозможно.'>
		</div>
		<div style='display: none;'>
			<label>Цена за шт:</label>
			<input type='number' min='0' style='width: 100px;' name='Price' autocomplete="off">
		</div>
		<div>
			<label>Модель:</label>
			<input type='hidden' id='Model'>
			<select name='Model' required style="width: 300px;">
			<?
				echo "<option value=''>-=Выберите модель=-</option>";
				$query = "SELECT * FROM ProductModels WHERE PT_ID = 1 AND archive = 0 ORDER BY Model";
				$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($result) )
				{
					echo "<option value='{$row["PM_ID"]}'>{$row["Model"]}</option>";
				}
			?>
			</select>
			&nbsp;&nbsp;&nbsp;
			<img src='/img/attention.png' class='attention' id='Model' title='Изделие в работе. При редактировании произойдут изменения в этапах.'>
		</div>
		<div>
			<label>Патина:</label>
			<input type="text" name="patina" placeholder="Цвет патины" style="width: 100px;">
			<span style="color: #911;">Оставьте пустым, если патина не требуется!</span>
		</div>
		<div>
			<label>Ткань:</label>
			<input type='text' class='materialtags_1 all' name='Material' style='width: 200px;'>
			<select name="Shipper" style="width: 110px;" title="Поставщик">
				<option value="">-=Другой=-</option>
				<?
				$query = "SELECT SH_ID, Shipper FROM Shippers WHERE PT_ID = 1";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
				}
				?>
			</select>
		</div>
		<?
		if( in_array('order_add_confirm', $Rights) ) {
		?>
		<div>
			<label>Наличие:</label>
			<div class='btnset radiostatus'>
				<input type='radio' id='1radio' name='IsExist' value='NULL'>
					<label for='1radio'>Неизвестно</label>
				<input type='radio' id='1radio0' name='IsExist' value='0'>
					<label for='1radio0'>Нет</label>
				<input type='radio' id='1radio1' name='IsExist' value='1'>
					<label for='1radio1'>Заказано</label>
				<input type='radio' id='1radio2' name='IsExist' value='2'>
					<label for='1radio2'>В наличии</label>
			</div>
			<br>
		</div>
		<div class='order_material' style='text-align: center; display: none;'>
			<span>Заказано:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>Ожидается:</span><br>
			<input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
			&nbsp;&nbsp;-&nbsp;&nbsp;
			<input class='date to' type='text' name='arrival_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y", strtotime("+14 days")) ?>" readonly>
		</div>
		<?
		}
		?>
		<div>
			<label>Примечание:</label>
			<textarea name='Comment' rows='3' cols='38'></textarea>
		</div>
		</fieldset>
<?
//	if( in_array('order_add_confirm', $Rights) ) {
//		echo "<div class=\"accordion\">";
//		echo "<h3>Найдено <span></span> \"Свободных\"</h3>";
//		echo "<div>";
//		echo "</div>";
//		echo "</div>";
//	}
?>
		<div>
			<hr>
			<input type='submit' value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!-- Конец формы добавения стула -->

<!-- Форма добавения стола -->
<div id='addtable' title='Параметры стола' class='addproduct' style='display:none'>
	<form method='post'>
		<fieldset>
		<input type='hidden' value='2' name='Type'>
		<div>
			<label>Kол-во:</label>
			<input required type='number' min='1' value='1' style='width: 70px; font-size: 2em;' name='Amount' autocomplete="off">
			&nbsp;&nbsp;&nbsp;
			<img src='/img/attention.png' class='attention' id='Amount' title='Изделие в работе. Изменение кол-ва невозможно.'>
		</div>
		<div style='display: none;'>
			<label>Цена за шт:</label>
			<input type='number' min='0' style='width: 100px;' name='Price' autocomplete="off">
		</div>
		<div>
			<label>Модель:</label>
			<select name="Model" style="width: 300px;">
			<?
				echo "<option value='0'>-=Столешница=-</option>";
				$query = "SELECT * FROM ProductModels WHERE PT_ID = 2 AND archive = 0 ORDER BY Model";
				$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($result) )
				{
					echo "<option value='{$row["PM_ID"]}'>{$row["Model"]}</option>";
				}
			?>
			</select>
			&nbsp;&nbsp;&nbsp;
			<img src='/img/attention.png' class='attention' id='Model' title='Изделие в работе. При редактировании произойдут изменения в этапах.'>
		</div>
		<div>
			<label>Патина:</label>
			<input type="text" name="patina" placeholder="Цвет патины" style="width: 100px;">
			<span style="color: #911;">Оставьте пустым, если патина не требуется!</span>
		</div>
		<div>
			<label>Форма:</label>
			<div class="btnset" id="forms">
				<!--Список формируется в js-->
			</div>
			<br>
		</div>
		<div>
			<label>Механизм:</label>
			<div class="btnset" id="mechanisms">
			<?
				$query = "SELECT PME_ID, Mechanism FROM ProductMechanism";
				$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($result) ) {
					echo "<input type='radio' id='mechanism{$row["PME_ID"]}' name='Mechanism' value='{$row["PME_ID"]}'>";
					echo "<label for='mechanism{$row["PME_ID"]}'>{$row["Mechanism"]}</label>";
				}
			?>
			</div>
			&nbsp;&nbsp;&nbsp;
			<img src='/img/attention.png' class='attention' id='Mechanism' title='Изделие в работе. При редактировании произойдут изменения в этапах.'>
			<br>
		</div>
		<div>
			<label>Размер:</label>
			<input required type='number' min='0' step='10' name='Length' style='width: 80px;' autocomplete='off' title="Длина">
			<img src='/img/attention.png' class='attention' id='Length' title='Изделие в работе. При редактировании произойдут изменения в этапах.'>
			<span>&nbsp;х&nbsp;</span>
			<input required type='number' min='0' step='10' name='Width' style='width: 80px;' autocomplete='off' title="Ширина">
			<span>&nbsp;/&nbsp;</span>
			<input type="number" name="PieceAmount" min="1" max="3" style='width: 50px;' autocomplete="off" title="Кол-во вставок">
			<span>&nbsp;х&nbsp;</span>
			<input type="number" name="PieceSize" min="200" max="550" step="10" style='width: 60px;' autocomplete="off" title="Размер вставки">
		</div>
		<div>
			<label>Пластик:</label>
			<input type='text' class="materialtags_2 all" name='Material' style="width: 200px;">
			<select name="Shipper" style="width: 110px;" title="Поставщик">
				<option value="">-=Другой=-</option>
				<?
				$query = "SELECT SH_ID, Shipper FROM Shippers WHERE PT_ID = 2";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) ) {
					echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
				}
				?>
			</select>
		</div>
		<?
		if( in_array('order_add_confirm', $Rights) ) {
		?>
		<div>
			<label>Наличие:</label>
			<div class='btnset radiostatus'>
				<input type='radio' id='2radio' name='IsExist' value='NULL'>
					<label for='2radio'>Неизвестно</label>
				<input type='radio' id='2radio0' name='IsExist' value='0'>
					<label for='2radio0'>Нет</label>
				<input type='radio' id='2radio1' name='IsExist' value='1'>
					<label for='2radio1'>Заказано</label>
				<input type='radio' id='2radio2' name='IsExist' value='2'>
					<label for='2radio2'>В наличии</label>
			</div>
		</div>
		<br>
		<div class='order_material' style='text-align: center; display: none;'>
			<span>Заказано:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>Ожидается:</span><br>
			<input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
			&nbsp;&nbsp;-&nbsp;&nbsp;
			<input class='date to' type='text' name='arrival_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y", strtotime("+14 days")) ?>" readonly>
		</div>
		<?
		}
		?>
		<div>
			<label>Примечание:</label>
			<textarea name='Comment' rows='3' cols='38'></textarea>
		</div>
		</fieldset>
<?
//	if( in_array('order_add_confirm', $Rights) ) {
//		echo "<div class=\"accordion\">";
//		echo "<h3>Найдено <span></span> \"Свободных\"</h3>";
//		echo "<div>";
//		echo "</div>";
//		echo "</div>";
//	}
?>
		<div>
			<hr>
			<input type='submit' value='Сохранить' style='float: right;'>
		</div>
	</form>
</div>
<!-- Конец формы добавения стола -->

<!-- Форма добавления заготовки -->
<div id='addblank' title='Параметры заготовки/прочего' class='addproduct' style='display:none'>
	<form method='post'>
		<fieldset>
			<div>
				<label>Kол-во:</label>
				<input required type='number' min='1' value='1' style='width: 70px; font-size: 2em;' name='Amount' autocomplete="off">
				&nbsp;&nbsp;&nbsp;
				<img src='/img/attention.png' class='attention' id='Amount' title='Изделие в работе. Изменение кол-ва невозможно.'>
			</div>
			<div style='display: none;'>
				<label>Цена за шт:</label>
				<input type='number' min='0' style='width: 100px;' name='Price' autocomplete="off">
			</div>
			<h3 style="color: #911;">Внимание! Каркасы стульев и другие заготовки нужно выбирать из списка ниже, а не писать вручную.</h3>
			<div>
				<label>Заготовка:</label>
				<select required name="Blanks" style="width: 300px;">
					<option value="">-=Выберите заготовку=-</option>
					<optgroup label="Стулья">
						<?
						$query = "SELECT BL.BL_ID, BL.Name, IF(PB.BL_ID IS NOT NULL, 'bold', '') Bold
								  FROM BlankList BL
								  LEFT JOIN ProductBlank PB ON PB.BL_ID = BL.BL_ID
								  WHERE BL.PT_ID = 1 AND PB.BL_ID IS NOT NULL
								  GROUP BY BL.BL_ID
								  ORDER BY BL.Name";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["BL_ID"]}' class='{$row["Bold"]}'>{$row["Name"]}</option>";
						}
						?>
					</optgroup>
					<optgroup label="Столы">
						<?
						$query = "SELECT BL.BL_ID, BL.Name, IF(PB.BL_ID IS NOT NULL, 'bold', '') Bold
								  FROM BlankList BL
								  LEFT JOIN ProductBlank PB ON PB.BL_ID = BL.BL_ID
								  WHERE BL.PT_ID = 2 AND PB.BL_ID IS NOT NULL
								  GROUP BY BL.BL_ID
								  ORDER BY BL.Name";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["BL_ID"]}' class='{$row["Bold"]}'>{$row["Name"]}</option>";
						}
						?>
					</optgroup>
				</select>
			</div>
			<div>
				<label>Прочее:</label>
				<input required class='othertags' type='text' style='width: 300px;' name='Other' autocomplete="off">
			</div>
			<div>
				<label>Патина:</label>
				<input type="text" name="patina" placeholder="Цвет патины" style="width: 100px;">
				<span style="color: #911;">Оставьте пустым, если патина не требуется!</span>
			</div>
			<div>
				<label>Ткань:</label>
				<input type='text' class='materialtags_1 all' name='Material' style='width: 200px;'>
				<select name="Shipper" style="width: 110px;" title="Поставщик">
					<option value="">-=Другой=-</option>
					<?
					$query = "SELECT SH_ID, Shipper FROM Shippers WHERE PT_ID = 1";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
					}
					?>
				</select>
			</div>
			<div>
				<label>Пластик:</label>
				<input type='text' class="materialtags_2 all" name='Material' style="width: 200px;">
				<select name="Shipper" style="width: 110px;" title="Поставщик">
					<option value="">-=Другой=-</option>
					<?
					$query = "SELECT SH_ID, Shipper FROM Shippers WHERE PT_ID = 2";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
					}
					?>
				</select>
			</div>
			<input type="hidden" name="MPT_ID">
			<?
			if( in_array('order_add_confirm', $Rights) ) {
			?>
			<div>
				<label>Наличие:</label>
				<div class='btnset radiostatus'>
					<input type='radio' id='0radio' name='IsExist' value='NULL'>
						<label for='0radio'>Неизвестно</label>
					<input type='radio' id='0radio0' name='IsExist' value='0'>
						<label for='0radio0'>Нет</label>
					<input type='radio' id='0radio1' name='IsExist' value='1'>
						<label for='0radio1'>Заказано</label>
					<input type='radio' id='0radio2' name='IsExist' value='2'>
						<label for='0radio2'>В наличии</label>
				</div>
			</div>
			<br>
			<div class='order_material' style='text-align: center; display: none;'>
				<span>Заказано:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>Ожидается:</span><br>
				<input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
				&nbsp;&nbsp;-&nbsp;&nbsp;
				<input class='date to' type='text' name='arrival_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y", strtotime("+14 days")) ?>" readonly>
			</div>
			<?
			}
			?>
			<div>
				<label>Примечание:</label>
				<textarea name='Comment' rows='3' style='width: 300px;'></textarea>
			</div>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы добавления заготовки -->

<!-- Форма добавления этапов производства -->
<div id='steps' title='Этапы производства' style='display:none'>
	<form method='post'>
		<div id='formsteps'></div> <!-- Форма создается в ajax.php -->
		<p>
			<hr>
			<input type='submit' value='Сохранить' style='float: right;'>
		</p>
	</form>
</div>
<!-- Конец формы добавления этапов производства -->

<!-- Форма разбитя заказа -->
<div id='order_cut' title='Разделение заказа' style='display:none'>
	<form method="post" action="index.php">
		<fieldset>

		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы разбитя заказа -->

<script>
	$(function() {
		// Select2
		$('select[name="Model"]').select2({
			placeholder: "Выберите модель",
			language: "ru"
		});
		$('select[name="Blanks"]').select2({
			placeholder: "Выберите заготовку",
			language: "ru"
		});

		// Костыль для Select2 чтобы работал поиск
		$.ui.dialog.prototype._allowInteraction = function (e) {
			return true;
		};

//		// Set the value, creating a new option if necessary
//		if ($('#mySelect2').find("option[value='" + data.id + "']").length) {
//			$('#mySelect2').val(data.id).trigger('change');
//		} else {
//			// Create a DOM Option and pre-select by default
//			var newOption = new Option(data.text, data.id, true, true);
//			// Append it to the select
//			$('#mySelect2').append(newOption).trigger('change');
//		}

		// Форма добавления стульев
		$('.edit_product1').click(function() {

			// Активация формы если была неактивна
			$('#addchair fieldset').prop('disabled', false);
			$('#addchair input[name=free]').val(0);

			id = $(this).attr('id');
			if( typeof id !== "undefined" ) {
				id = id.replace('prod', '');
			}
			var free = $(this).attr('free');
			var location = $(this).attr("location");
			var odid = $(this).attr("odid");

			// Очистка диалога
			$('#addchair input').prop('disabled', false);
			$('#addchair select[name="Model"]').val('').trigger('change');
			$('#addchair input[type="text"], #addchair select').val('');
			$('#addchair textarea').val('');
			$('#addchair input[name="Amount"]').val('');
			$('#addchair input[name="Amount"]').prop('readonly', false);
			$('#addchair input[name="Price"]').val('');
			$('#1radio').prop('checked', true);
			$('#addchair .radiostatus input[type="radio"]').prop('disabled', true);
			$('#addchair .radiostatus input[type="radio"]').button('refresh');
			$('#addchair input[name="Amount"]').removeAttr('max');
			// Очистка инпутов дат заказа ткани
			$('#addchair .order_material').hide('fast');
			$('#addchair .order_material input').attr("required", false);
			$('#addchair .order_material input').val('');
			$('#addchair .order_material input.from').datepicker( "option", "maxDate", null );
			$('#addchair .order_material input.to').datepicker( "option", "minDate", null );
			// Прячем картинки-треугольники
			$('#addchair img[id="Amount"]').hide();
			$('#addchair img[id="Model"]').hide();
			// Сворачиваем акордион, очищаем
			$('#addchair .accordion').accordion( "option", "active", false );
			$('#addchair .accordion div').html('');
			$('#addchair .accordion h3 span').html('0');
			$('#addchair .accordion').hide(); // Прячем акордион
			// Удаляем из селекта архивный элемент
			$('#addchair select[name="Model"] option.archive').remove();

			// Заполнение
			if( id > 0 )
			{
				$('#addchair input[name="Amount"]').val(odd[id]['amount']);
				$('#addchair input[name="Price"]').val(odd[id]['price']);
//				$('#addchair select[name="Model"]').val(odd[id]['model']).trigger('change');

				// Задание значение, создав при необходимости новую опцию
				if ($('#addchair select[name="Model"]').find("option[value='" + odd[id]['model'] + "']").length) {
					$('#addchair select[name="Model"]').val(odd[id]['model']).trigger('change');
				} else {
					// Create a DOM Option and pre-select by default
					var newOption = new Option(odd[id]['model_name'] + " (снят с производства)", odd[id]['model'], true, true);
					newOption.className = "archive";
					// Append it to the select
					$('#addchair select[name="Model"]').append(newOption).trigger('change');
				}

				$('#addchair textarea[name="Comment"]').val(odd[id]['comment']);
				$('#addchair input[name="patina"]').val(odd[id]['patina']);
				$('#addchair input[name="Material"]').val(odd[id]['material']);
				$('#addchair select[name="Shipper"]').val(odd[id]['shipper']);
				$('#1radio'+odd[id]['isexist']).prop('checked', true);
				$('#addchair .radiostatus input[type="radio"]').button('refresh');
				if( odd[id]['isexist'] == 1 ) {
					$('#addchair .order_material').show('fast');
					$('#addchair .order_material input').attr("required", true);
					$('#addchair .order_material input.from').val( odd[id]['order_date'] );
					$('#addchair .order_material input.to' ).val( odd[id]['arrival_date'] );
				}

				// Если изделие в работе, то выводим предупреждения
				if( odd[id]['inprogress'] == 1 )
				{
					$('#addchair img[id="Amount"]').show();
					$('#addchair input[name="Amount"]').prop('readonly', true);
					$('#addchair img[id="Model"]').show();
					$('#addchair input[name="Amount"]').attr('max', odd[id]['amount']);
				}

				materialonoff('#addchair');

				$("#addchair form").attr("action", "datasave.php?oddid="+id+"&location="+location);
			}
			else // Иначе добавляем новый стул
			{
				$('#addchair form').attr('action', 'orderdetail.php?id='+odid+'&add=1');
				if( id != 0 ) {
					$('#addchair .accordion').show('fast'); // Показываем акордион
				}
			}

	//		$('#addchair select[name="Model"]').change( function() { livesearch(this); });
			$.ajax({ url: "ajax.php?do=livesearch&this=addchair&type=1", dataType: "script", async: false });

			// Если нет ткани, то кнопка наличия не активна
			$('#addchair input[name="Material"]').change( function() {
				materialonoff('#addchair');
			});
			$('#addchair input[name="Material"]').on( "autocompleteselect", function() {
				materialonoff('#addchair');
			});
			// Костыль для активации кнопок наличия материала при вставке из буфера
	//		$('#addchair input[name="Material"]').bind('paste', function(e) {
	//			var pastedData = e.originalEvent.clipboardData.getData('text');
	//			$(this).val(' ');
	//			materialonoff('#addchair');
	//			$(this).val('');
	//		});

			// Форма добавления/редактирования стульев
			$('#addchair').dialog({
				width: 600,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			$( ".materialtags_1" ).autocomplete( "option", "appendTo", "#addchair" );

			return false;
		});

		// Форма добавления столов
		$('.edit_product2').click(function() {

			// Активация формы если была неактивна
			$('#addtable fieldset').prop('disabled', false);
			$('#addtable .btnset').buttonset( 'option', 'disabled', false );
			$('#addtable input[name=free]').val(0);

			id = $(this).attr('id');
			if( typeof id !== "undefined" ) {
				id = id.replace('prod', '');
			}
			var free = $(this).attr('free');
			var location = $(this).attr("location");
			var odid = $(this).attr("odid");

			// Очистка диалога
			$('#addtable input, #addtable select').prop('disabled', false);
			$('#addtable input[type="text"]').val('');
			$('#addtable select[name="Model"]').val('0').trigger('change');
			$('#addtable textarea').val('');
			$('#addtable input[name="Amount"]').val('');
			$('#addtable input[name="Amount"]').prop('readonly', false);
			$('#addtable input[name="Price"]').val('');
			$('#addtable input[name="Length"]').val(''); //было 1300
			$('#addtable input[name="Width"]').val(''); //было 800
			$('#2radio').prop('checked', true);
			$('#addtable .radiostatus').buttonset( 'option', 'disabled', true );
			$('#addtable .radiostatus input[type="radio"]').button('refresh');
			$('#addtable input[name="Form"]:nth-child(1)').prop('checked', true);
			$('#addtable input[name="Mechanism"]:nth-child(1)').prop('checked', true);
			$('#addtable input[type="radio"]').button("refresh");
			$('#addtable input[name="Amount"]').removeAttr('max');
			// Очистка инпутов дат заказа пластика
			$('#addtable .order_material').hide('fast');
			$('#addtable .order_material input').attr("required", false);
			$('#addtable .order_material input').val('');
			$('#addtable .order_material input.from').datepicker( "option", "maxDate", null );
			$('#addtable .order_material input.to').datepicker( "option", "minDate", null );
			// Прячем картинки-треугольники
			$('#addtable img[id="Amount"]').hide();
			$('#addtable img[id="Model"]').hide();
			$('#addtable img[id="Mechanism"]').hide();
			$('#addtable img[id="Length"]').hide();
			// Сворачиваем акордион, очищаем
			$('#addtable .accordion').accordion( "option", "active", false );
			$('#addtable .accordion div').html('');
			$('#addtable .accordion h3 span').html('0');
			$('#addtable .accordion').hide(); // Прячем акордион
			// Удаляем из селекта архивный элемент
			$('#addtable select[name="Model"] option.archive').remove();

			// Заполнение
			if( id > 0 )
			{
				var model = odd[id]['model'];
				var form = odd[id]['form'];
				// Если известна модель, то выводим соответствующий список форм
	//			if( odd[id]['model'] ) {
	//				FormModelList(odd[id]['model'], odd[id]['form']);
	//			}
				$('#addtable input[name="Amount"]').val(odd[id]['amount']);
				$('#addtable input[name="Price"]').val(odd[id]['price']);
//				$('#addtable select[name="Model"]').val(odd[id]['model']).trigger('change');

				// Задание значение, создав при необходимости новую опцию
				if ($('#addtable select[name="Model"]').find("option[value='" + odd[id]['model'] + "']").length) {
					$('#addtable select[name="Model"]').val(odd[id]['model']).trigger('change');
				} else {
					// Create a DOM Option and pre-select by default
					var newOption = new Option(odd[id]['model_name'] + " (снят с производства)", odd[id]['model'], true, true);
					newOption.className = "archive";
					// Append it to the select
					$('#addtable select[name="Model"]').append(newOption).trigger('change');
				}

				//$('#form'+odd[id]['form']).prop('checked', true);
				//$('#addtable input[name="Form"]').button("refresh");
				$('#mechanism'+odd[id]['mechanism']).prop('checked', true);
				$('#addtable input[name="Mechanism"]').button("refresh");
				$('#addtable input[name="Length"]').val(odd[id]['length']);
				$('#addtable input[name="Width"]').val(odd[id]['width']);
				$('#addtable input[name="PieceAmount"]').val(odd[id]['PieceAmount']);
				$('#addtable input[name="PieceSize"]').val(odd[id]['PieceSize']);
				$('#addtable textarea[name="Comment"]').val(odd[id]['comment']);
				$('#addtable input[name="patina"]').val(odd[id]['patina']);
				$('#addtable input[name="Material"]').val(odd[id]['material']);
				$('#addtable select[name="Shipper"]').val(odd[id]['shipper']);
				$('#2radio'+odd[id]['isexist']).prop('checked', true);
				$('#addtable .radiostatus input[type="radio"]').button('refresh');
				if( odd[id]['isexist'] == 1 ) {
					$('#addtable .order_material').show('fast');
					$('#addtable .order_material input').attr("required", true);
					$('#addtable .order_material input.from').val( odd[id]['order_date'] );
					$('#addtable .order_material input.to' ).val( odd[id]['arrival_date'] );
				}

				// Если изделие в работе, то выводятся предупреждения
				if( odd[id]['inprogress'] == 1 )
				{
					$('#addtable img[id="Amount"]').show();
					$('#addtable input[name="Amount"]').prop('readonly', true);
					$('#addtable img[id="Model"]').show();
					$('#addtable img[id="Mechanism"]').show();
					$('#addtable img[id="Length"]').show();
					$('#addtable input[name="Amount"]').attr('max', odd[id]['amount']);
				}

				materialonoff('#addtable');

				$("#addtable form").attr("action", "datasave.php?oddid="+id+"&location="+location);
			}
			else // Иначе добавляем новый стол
			{
				var model = 0;
				var form = 0;
				$("#addtable form").attr("action", "orderdetail.php?id="+odid+"&add=1");
				if( id != 0 ) {
					$('#addtable .accordion').show('fast'); // Показываем акордион
				}
			}

			FormModelList(model, form);

			$('#addtable input[name="Form"]').change( function() {
				form = $(this).val();
			});

			// Список форм столешниц в зависимости от модели
			$('#addtable select[name="Model"]').change( function() {
				if( $(this).val() == "" ) {
					FormModelList(0, form);
				}
				else {
					FormModelList($(this).val(), form);
				}

				$('#addtable input[name="Form"]').change( function() {
					form = $(this).val();
				});

	//			livesearch(this);
			});

			$.ajax({ url: "ajax.php?do=livesearch&this=addtable&type=2", dataType: "script", async: false });

			// Если нет пластика, то кнопка наличия не активна
			$('#addtable input[name="Material"]').change( function() {
				materialonoff('#addtable');
			});
			$('#addtable input[name="Material"]').on( "autocompleteselect", function() {
				materialonoff('#addtable');
			});
			// Костыль для активации кнопок наличия материала при вставке из буфера
	//		$('#addtable input[name="Material"]').bind('paste', function(e) {
	//			var pastedData = e.originalEvent.clipboardData.getData('text');
	//			$(this).val(' ');
	//			materialonoff('#addtable');
	//			$(this).val('');
	//		});

			$("#addtable").dialog(
			{
				width: 850,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			$( ".materialtags_2" ).autocomplete( "option", "appendTo", "#addtable" );

			return false;
		});

		// Форма добавления заготовок
		$('.edit_order_blank').click(function() {
			id = $(this).attr('id');
			if( typeof id !== "undefined" ) {
				id = id.replace('blank', '');
			}
			var location = $(this).attr("location");
			var odid = $(this).attr("odid");

			// Очистка диалога
			$('#addblank textarea, #addblank input[type="text"]').val('');
			$('#addblank select').val('').trigger('change');
			$('#addblank input[name="Amount"]').val('');
			$('#addblank input[name="Amount"]').prop('readonly', false);
			$('#addblank input[name="Price"]').val('');
			$('#0radio').prop('checked', true);
			$('#addblank .radiostatus input[type="radio"]').prop('disabled', true);
			$('#addblank .radiostatus input[type="radio"]').button('refresh');
			$('#addblank input[name="Other"]').prop('disabled', false);
			$('#addblank input[name="Other"]').prop("required", true);
			$('#addblank select[name="Blanks"]').prop('disabled', false);
			$('#addblank select[name="Blanks"]').prop('required', true);
			$('#addblank input[name="Material"]').attr('disabled', false);
			$('#addblank select[name="Shipper"]').attr('disabled', false);
			$('#addblank input[name="MPT_ID"]').val('');
			// Очистка инпутов дат заказа пластика
			$('#addblank .order_material').hide('fast');
			$('#addblank .order_material input').attr("required", false);
			$('#addblank .order_material input').val('');
			$('#addblank .order_material input.from').datepicker( "option", "maxDate", null );
			$('#addblank .order_material input.to').datepicker( "option", "minDate", null );
			// Прячем картинки-треугольники
			$('#addblank img[id="Amount"]').hide();

			// Заполнение
			if( id > 0 )
			{
				$('#addblank input[name="Amount"]').val(odb[id]['amount']);
				$('#addblank input[name="Price"]').val(odb[id]['price']);
				if( odb[id]['blank'] > 0 ) {
					$('#addblank select[name="Blanks"]').val(odb[id]['blank']).trigger('change');
					$('#addblank input[name="Other"]').prop('disabled', true);
					$('#addblank input[name="Other"]').prop("required", false);
				}
				else {
					$('#addblank input[name="Other"]').val(odb[id]['other']);
					$('#addblank select[name="Blanks"]').prop('disabled', true);
					$('#addblank select[name="Blanks"]').prop('required', false);
				}
				$('#addblank textarea[name="Comment"]').val(odb[id]['comment']);
				$('#addblank input[name="patina"]').val(odb[id]['patina']);

				// Заполняем ткань/пластик
				if( odb[id]['MPT_ID'] == 1 ) {
					$('#addblank input[name="Material"]:eq(0)').val(odb[id]['material']);
					$('#addblank select[name="Shipper"]:eq(0)').val(odb[id]['shipper']);
					$('#addblank input[name="Material"]:eq(1)').attr('disabled', true);
					$('#addblank select[name="Shipper"]:eq(1)').attr('disabled', true);
					$('#addblank input[name="MPT_ID"]').val('1');
				}
				else if( odb[id]['MPT_ID'] == 2 ) {
					$('#addblank input[name="Material"]:eq(1)').val(odb[id]['material']);
					$('#addblank select[name="Shipper"]:eq(1)').val(odb[id]['shipper']);
					$('#addblank input[name="Material"]:eq(0)').attr('disabled', true);
					$('#addblank select[name="Shipper"]:eq(0)').attr('disabled', true);
					$('#addblank input[name="MPT_ID"]').val('2');
				}

				$('#0radio'+odb[id]['isexist']).prop('checked', true);
				$('#addblank .radiostatus input[type="radio"]').button('refresh');
				if( odb[id]['isexist'] == 1 ) {
					$('#addblank .order_material').show('fast');
					$('#addblank .order_material input').attr("required", true);
					$('#addblank .order_material input.from').val( odb[id]['order_date'] );
					$('#addblank .order_material input.to' ).val( odb[id]['arrival_date'] );
				}

				// Если изделие в работе, то выводятся предупреждения
				if( odb[id]['inprogress'] == 1 )
				{
					$('#addblank img[id="Amount"]').show();
					$('#addblank input[name="Amount"]').prop('readonly', true);
					$('#addblank input[name="Amount"]').attr('max', odb[id]['amount']);
				}

				materialonoff('#addblank');
				$("#addblank form").attr("action", "datasave.php?odbid="+id+"&location="+location);
			}
			else // Иначе добавляем новую заготовку
			{
				$('#addblank form').attr('action', 'orderdetail.php?id='+odid+'&addblank=1');
			}

			$('#addblank select[name="Blanks"]').change( function() {
				if( !(id > 0) ) {
					val = $(this).val();
					if( val != '' ) {
						$('#addblank input[name="Other"]').prop('disabled', true);
						$('#addblank input[name="Other"]').prop("required", false);
					}
					else {
						$('#addblank input[name="Other"]').prop('disabled', false);
						$('#addblank input[name="Other"]').prop("required", true);
					}
				}
			});

			$('#addblank input[name="Other"]').change( function() {
				if( !(id > 0) ) {
					val = $(this).val();
					if( val != '' ) {
						$('#addblank select[name="Blanks"]').prop('disabled', true);
						$('#addblank select[name="Blanks"]').prop('required', false);
					}
					else {
						$('#addblank select[name="Blanks"]').prop('disabled', false);
						$('#addblank select[name="Blanks"]').prop('required', true);
					}
				}
			});

			// Если добавлена ткань - то пластик не доступеню. И наоборот.
			$('#addblank input[name="Material"]:eq(0)').blur(function(){
				if( $(this).val().length > 0 ) {
					$('#addblank input[name="Material"]:eq(1)').attr('disabled', true);
					$('#addblank select[name="Shipper"]:eq(1)').attr('disabled', true);
					$('#addblank input[name="MPT_ID"]').val('1');
				}
				else {
					$('#addblank input[name="Material"]:eq(1)').attr('disabled', false);
					$('#addblank select[name="Shipper"]:eq(1)').attr('disabled', false);
					$('#addblank input[name="MPT_ID"]').val('');
				}
				materialonoff('#addblank');
			});
			$('#addblank input[name="Material"]:eq(1)').blur(function(){
				if( $(this).val().length > 0 ) {
					$('#addblank input[name="Material"]:eq(0)').attr('disabled', true);
					$('#addblank select[name="Shipper"]:eq(0)').attr('disabled', true);
					$('#addblank input[name="MPT_ID"]').val('2');
				}
				else {
					$('#addblank input[name="Material"]:eq(0)').attr('disabled', false);
					$('#addblank select[name="Shipper"]:eq(0)').attr('disabled', false);
					$('#addblank input[name="MPT_ID"]').val('');
				}
				materialonoff('#addblank');
			});

			// Если нет материала, то кнопка наличия не активна
			$('#addblank input[name="Material"]:eq(0)').on( "autocompleteselect", function() {
				$('#addblank input[name="Material"]:eq(0)').blur();
			});
			$('#addblank input[name="Material"]:eq(1)').on( "autocompleteselect", function() {
				$('#addblank input[name="Material"]:eq(1)').blur();
			});

			$("#addblank").dialog(
			{
				width: 600,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			$( ".materialtags_1" ).autocomplete( "option", "appendTo", "#addblank" );
			$( ".materialtags_2" ).autocomplete( "option", "appendTo", "#addblank" );

			return false;
		});
	});
</script>
