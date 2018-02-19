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
			<div class='btnset'>
				<input type='radio' id='1ptn0' name='ptn' value='0'>
					<label for='1ptn0'>Нет</label>
				<input type='radio' id='1ptn1' name='ptn' value='1'>
					<label for='1ptn1' style="background: gold;">Золото</label>
				<input type='radio' id='1ptn2' name='ptn' value='2'>
					<label for='1ptn2' style="background: silver;">Серебро</label>
				<input type='radio' id='1ptn3' name='ptn' value='3'>
					<label for='1ptn3' style="background: chocolate;">Кофе</label>
			</div>
			<br>
		</div>
		<div>
			<label>Ткань:</label>
			<input type='text' class='materialtags_1 all' name='Material' style='width: 200px;'>
			<select name="Shipper" style="width: 110px;" title="Поставщик">
				<?
				$query = "SELECT SH_ID, Shipper FROM Shippers WHERE mtype = 1 ORDER BY Shipper";
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
			<div class='btnset'>
				<input type='radio' id='2ptn0' name='ptn' value='0'>
					<label for='2ptn0'>Нет</label>
				<input type='radio' id='2ptn1' name='ptn' value='1'>
					<label for='2ptn1' style="background: gold;">Золото</label>
				<input type='radio' id='2ptn2' name='ptn' value='2'>
					<label for='2ptn2' style="background: silver;">Серебро</label>
				<input type='radio' id='2ptn3' name='ptn' value='3'>
					<label for='2ptn3' style="background: chocolate;">Кофе</label>
			</div>
			<br>
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
				<?
				$query = "SELECT SH_ID, Shipper FROM Shippers WHERE mtype = 2 ORDER BY Shipper";
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
				<div class='btnset'>
					<input type='radio' id='0ptn0' name='ptn' value='0'>
						<label for='0ptn0'>Нет</label>
					<input type='radio' id='0ptn1' name='ptn' value='1'>
						<label for='0ptn1' style="background: gold;">Золото</label>
					<input type='radio' id='0ptn2' name='ptn' value='2'>
						<label for='0ptn2' style="background: silver;">Серебро</label>
					<input type='radio' id='0ptn3' name='ptn' value='3'>
						<label for='0ptn3' style="background: chocolate;">Кофе</label>
				</div>
				<br>
			</div>
			<div>
				<label>Ткань:</label>
				<input type='text' class='materialtags_1 all' name='Material' style='width: 200px;'>
				<select name="Shipper" style="width: 110px;" title="Поставщик">
					<?
					$query = "SELECT SH_ID, Shipper FROM Shippers WHERE mtype = 1 ORDER BY Shipper";
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
					<?
					$query = "SELECT SH_ID, Shipper FROM Shippers WHERE mtype = 2 ORDER BY Shipper";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<option value='{$row["SH_ID"]}'>{$row["Shipper"]}</option>";
					}
					?>
				</select>
			</div>
			<input type="hidden" name="mtype">
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
	// Функция активирует/деактивирует кнопки наличия ткани/пластика
	function materialonoff(element)
	{
		var length = $(element+' input[name="Material"]:enabled').val().length;
		if( length == 0 )
		{
			$(element+' .radiostatus input[type="radio"]').prop('disabled', true);
			// Очистка инпутов дат заказа пластика
			$('#1radio').prop('checked', true);
			$('#2radio').prop('checked', true);
			$('#0radio').prop('checked', true);
			$(element+' .order_material').hide('fast');
			$(element+' .order_material input').attr("required", false);
			$(element+' .order_material input').val('');
			$(element+' .order_material input.from').datepicker( "option", "maxDate", null );
			$(element+' .order_material input.to').datepicker( "option", "minDate", null );
		}
		else
		{
			$(element+' .radiostatus input[type="radio"]').prop('disabled', false);
		}
		$(element+' .radiostatus input[type="radio"]').button('refresh');
		return false;
	}

	// Функция формирования списка форм в зависимости от модели стола
	function FormModelList(model, form) {
		var forms = "";
		var arr = ModelForm[model];
		var informs = 0;
		if( typeof arr !== "undefined" ) {
			$.each(arr, function(key, val){
				forms += "<input type='radio' id='form" + key + "' name='Form' value='" + key + "'>";
				forms += "<label for='form" + key + "'>" + val + "</label>";
				if( form == key ) { informs = 1; }
			});
		}
		$('#addtable #forms').html(forms);
		if( forms != "" ) {
			if( form > 0 && informs ) {
				$('#addtable input[name="Form"][value="'+form+'"]').prop('checked', true);
			}
			else {
				$('#addtable input[name="Form"]:nth-child(1)').prop('checked', true);
			}
			$('#addtable #forms').buttonset();
		}
	}
	//////////////////////////////////////////////////////////////////////////
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

		// Массив куда будут записыапться данные по изделиям
		odd_data = new Array();
		odb_data = new Array();

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
			$('#1ptn0').prop('checked', true);
			$('#addchair .radiostatus input[type="radio"]').prop('disabled', true);
			$('#addchair input[type="radio"]').button('refresh');
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
			// Удаляем из селекта архивный элемент
			$('#addchair select[name="Model"] option.archive').remove();

			// Заполнение
			if( id > 0 )
			{
				// Через ajax получаем данные об изделии
				$.ajax({ url: "ajax.php?do=odd_data&id=" + id, success:function(msg){ odd_data = msg; }, dataType: "json", async: false });

				$('#addchair input[name="Amount"]').val(odd_data['amount']);
				$('#addchair input[name="Price"]').val(odd_data['price']);

				// Задание значение, создав при необходимости новую опцию
				if ($('#addchair select[name="Model"]').find("option[value='" + odd_data['model'] + "']").length) {
					$('#addchair select[name="Model"]').val(odd_data['model']).trigger('change');
				} else {
					// Create a DOM Option and pre-select by default
					var newOption = new Option(odd_data['model_name'] + " (снят с производства)", odd_data['model'], true, true);
					newOption.className = "archive";
					// Append it to the select
					$('#addchair select[name="Model"]').append(newOption).trigger('change');
				}

				$('#addchair textarea[name="Comment"]').val(odd_data['comment']);
				$('#addchair input[name="patina"]').val(odd_data['patina']);
				$('#addchair input[name="Material"]').val(odd_data['material']);
				$('#addchair select[name="Shipper"]').val(odd_data['shipper']);
				$('#1ptn'+odd_data['ptn']).prop('checked', true);
				$('#1radio'+odd_data['isexist']).prop('checked', true);
				$('#addchair input[type="radio"]').button('refresh');
				if( odd_data['isexist'] == 1 ) {
					$('#addchair .order_material').show('fast');
					$('#addchair .order_material input').attr("required", true);
					$('#addchair .order_material input.from').val( odd_data['order_date'] );
					$('#addchair .order_material input.to' ).val( odd_data['arrival_date'] );
				}

				// Если изделие в работе, то выводим предупреждения
				if( odd_data['inprogress'] == 1 )
				{
					$('#addchair img[id="Amount"]').show();
					$('#addchair input[name="Amount"]').prop('readonly', true);
					$('#addchair img[id="Model"]').show();
					$('#addchair input[name="Amount"]').attr('max', odd_data['amount']);
				}

				materialonoff('#addchair');

				$("#addchair form").attr("action", "datasave.php?oddid="+id+"&location="+location);
			}
			else // Иначе добавляем новый стул
			{
				$('#addchair form').attr('action', 'orderdetail.php?id='+odid+'&add=1');
			}

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

		// Если нет ткани, то кнопка наличия не активна
		$('#addchair input[name="Material"]').change( function() {
			materialonoff('#addchair');
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
			$('#2ptn0').prop('checked', true);
			$('#addtable .radiostatus').buttonset( 'option', 'disabled', true );
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
			// Удаляем из селекта архивный элемент
			$('#addtable select[name="Model"] option.archive').remove();

			// Заполнение
			if( id > 0 )
			{
				// Через ajax получаем данные об изделии
				$.ajax({ url: "ajax.php?do=odd_data&id=" + id, success:function(msg){ odd_data = msg; }, dataType: "json", async: false });

				var model = odd_data['model'];
				var form = odd_data['form'];
				$('#addtable input[name="Amount"]').val(odd_data['amount']);
				$('#addtable input[name="Price"]').val(odd_data['price']);

				// Задание значение, создав при необходимости новую опцию
				if ($('#addtable select[name="Model"]').find("option[value='" + odd_data['model'] + "']").length) {
					$('#addtable select[name="Model"]').val(odd_data['model']).trigger('change');
				} else {
					// Create a DOM Option and pre-select by default
					var newOption = new Option(odd_data['model_name'] + " (снят с производства)", odd_data['model'], true, true);
					newOption.className = "archive";
					// Append it to the select
					$('#addtable select[name="Model"]').append(newOption).trigger('change');
				}

				$('#2ptn'+odd_data['ptn']).prop('checked', true);
				$('#mechanism'+odd_data['mechanism']).prop('checked', true);
				$('#addtable input[name="Length"]').val(odd_data['length']);
				$('#addtable input[name="Width"]').val(odd_data['width']);
				$('#addtable input[name="PieceAmount"]').val(odd_data['PieceAmount']);
				$('#addtable input[name="PieceSize"]').val(odd_data['PieceSize']);
				$('#addtable textarea[name="Comment"]').val(odd_data['comment']);
				$('#addtable input[name="Material"]').val(odd_data['material']);
				$('#addtable select[name="Shipper"]').val(odd_data['shipper']);
				$('#2radio'+odd_data['isexist']).prop('checked', true);
				$('#addtable input[type="radio"]').button('refresh');
				if( odd_data['isexist'] == 1 ) {
					$('#addtable .order_material').show('fast');
					$('#addtable .order_material input').attr("required", true);
					$('#addtable .order_material input.from').val( odd_data['order_date'] );
					$('#addtable .order_material input.to' ).val( odd_data['arrival_date'] );
				}

				// Если изделие в работе, то выводятся предупреждения
				if( odd_data['inprogress'] == 1 )
				{
					$('#addtable img[id="Amount"]').show();
					$('#addtable input[name="Amount"]').prop('readonly', true);
					$('#addtable img[id="Model"]').show();
					$('#addtable img[id="Mechanism"]').show();
					$('#addtable img[id="Length"]').show();
					$('#addtable input[name="Amount"]').attr('max', odd_data['amount']);
				}

				materialonoff('#addtable');

				$("#addtable form").attr("action", "datasave.php?oddid="+id+"&location="+location);
			}
			else // Иначе добавляем новый стол
			{
				var model = 0;
				var form = 0;
				$("#addtable form").attr("action", "orderdetail.php?id="+odid+"&add=1");
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

			});

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

		// Если нет пластика, то кнопка наличия не активна
		$('#addtable input[name="Material"]').change( function() {
			materialonoff('#addtable');
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
			$('#0ptn0').prop('checked', true);
			$('#addblank .radiostatus input[type="radio"]').prop('disabled', true);
			$('#addblank input[type="radio"]').button('refresh');
			$('#addblank input[name="Other"]').prop('disabled', false);
			$('#addblank input[name="Other"]').prop("required", true);
			$('#addblank select[name="Blanks"]').prop('disabled', false);
			$('#addblank select[name="Blanks"]').prop('required', true);
			$('#addblank input[name="Material"]').attr('disabled', false);
			$('#addblank select[name="Shipper"]').attr('disabled', false);
			$('#addblank input[name="mtype"]').val('');
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
				// Через ajax получаем данные об изделии
				$.ajax({ url: "ajax.php?do=odb_data&id=" + id, success:function(msg){ odb_data = msg; }, dataType: "json", async: false });

				$('#addblank input[name="Amount"]').val(odb_data['amount']);
				$('#addblank input[name="Price"]').val(odb_data['price']);
				if( odb_data['blank'] > 0 ) {
					$('#addblank select[name="Blanks"]').val(odb_data['blank']).trigger('change');
					$('#addblank input[name="Other"]').prop('disabled', true);
					$('#addblank input[name="Other"]').prop("required", false);
				}
				else {
					$('#addblank input[name="Other"]').val(odb_data['other']);
					$('#addblank select[name="Blanks"]').prop('disabled', true);
					$('#addblank select[name="Blanks"]').prop('required', false);
				}
				$('#addblank textarea[name="Comment"]').val(odb_data['comment']);

				// Заполняем ткань/пластик
				if( odb_data['mtype'] == 1 ) {
					$('#addblank input[name="Material"]:eq(0)').val(odb_data['material']);
					$('#addblank select[name="Shipper"]:eq(0)').val(odb_data['shipper']);
					$('#addblank input[name="Material"]:eq(1)').attr('disabled', true);
					$('#addblank select[name="Shipper"]:eq(1)').attr('disabled', true);
					$('#addblank input[name="mtype"]').val('1');
				}
				else if( odb_data['mtype'] == 2 ) {
					$('#addblank input[name="Material"]:eq(1)').val(odb_data['material']);
					$('#addblank select[name="Shipper"]:eq(1)').val(odb_data['shipper']);
					$('#addblank input[name="Material"]:eq(0)').attr('disabled', true);
					$('#addblank select[name="Shipper"]:eq(0)').attr('disabled', true);
					$('#addblank input[name="mtype"]').val('2');
				}

				$('#0ptn'+odb_data['isexist']).prop('checked', true);
				$('#0radio'+odb_data['isexist']).prop('checked', true);
				$('#addblank input[type="radio"]').button('refresh');
				if( odb_data['isexist'] == 1 ) {
					$('#addblank .order_material').show('fast');
					$('#addblank .order_material input').attr("required", true);
					$('#addblank .order_material input.from').val( odb_data['order_date'] );
					$('#addblank .order_material input.to' ).val( odb_data['arrival_date'] );
				}

				// Если изделие в работе, то выводятся предупреждения
				if( odb_data['inprogress'] == 1 )
				{
					$('#addblank img[id="Amount"]').show();
					$('#addblank input[name="Amount"]').prop('readonly', true);
					$('#addblank input[name="Amount"]').attr('max', odb_data['amount']);
				}

				materialonoff('#addblank');
				$("#addblank form").attr("action", "datasave.php?odbid="+id+"&location="+location);
			}
			else // Иначе добавляем новую заготовку
			{
				$('#addblank form').attr('action', 'orderdetail.php?id='+odid+'&addblank=1');
			}

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
				$('#addblank input[name="mtype"]').val('1');
			}
			else {
				$('#addblank input[name="Material"]:eq(1)').attr('disabled', false);
				$('#addblank select[name="Shipper"]:eq(1)').attr('disabled', false);
				$('#addblank input[name="mtype"]').val('');
			}
			materialonoff('#addblank');
		});
		$('#addblank input[name="Material"]:eq(1)').blur(function(){
			if( $(this).val().length > 0 ) {
				$('#addblank input[name="Material"]:eq(0)').attr('disabled', true);
				$('#addblank select[name="Shipper"]:eq(0)').attr('disabled', true);
				$('#addblank input[name="mtype"]').val('2');
			}
			else {
				$('#addblank input[name="Material"]:eq(0)').attr('disabled', false);
				$('#addblank select[name="Shipper"]:eq(0)').attr('disabled', false);
				$('#addblank input[name="mtype"]').val('');
			}
			materialonoff('#addblank');
		});
	});
</script>
