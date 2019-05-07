<?
	// Массив форм столешниц в зависимости от модели
	$ModelForm = array();
	$ModelForm_standart = array();
	$query = "
		SELECT PMF.PM_ID
			,PMF.PF_ID
			,PF.Form
			,PMF.standart
		FROM ProductModelsForms PMF
		LEFT JOIN ProductForms PF ON PF.PF_ID = PMF.PF_ID
		UNION
		SELECT 0, PF_ID, Form, 0 FROM ProductForms
	";
	$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($result) ) {
		$ModelForm[$row["PM_ID"]][$row["PF_ID"]] = [$row["Form"]];
		$ModelForm_standart[$row["PM_ID"]][$row["PF_ID"]] = [$row["standart"]];
	}

	// Массив механизмов в зависимости от модели
	$ModelMech = array();
	$ModelMech_box = array();
	$query = "
		SELECT 0 PM_ID, PME_ID, Mechanism, 0 box
		FROM ProductMechanism
		UNION
		SELECT PMM.PM_ID, PMM.PME_ID, PM.Mechanism, PMM.box
		FROM ProductModelsMechanism PMM
		JOIN ProductMechanism PM ON PM.PME_ID = PMM.PME_ID
	";
	$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($result) ) {
		$ModelMech[$row["PM_ID"]][$row["PME_ID"]] = [$row["Mechanism"]];
		$ModelMech_box[$row["PM_ID"]][$row["PME_ID"]] = [$row["box"]];
	}

	// Массив наличия патины и дефолтная форма в зависимости от модели
	$ModelPatina = array();
	$ModelDefForm = array();
	$query = "
		SELECT PM.PM_ID, PM.ptn, MIN(PMF.PF_ID) PF_ID, SUM(IF(PM.PT_ID = 2, 1, 0)) cnt
		FROM ProductModels PM
		LEFT JOIN ProductModelsForms PMF ON PMF.PM_ID = PM.PM_ID AND PMF.standart = 1
		GROUP BY PM.PM_ID
	";
	$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($result) ) {
		$ModelPatina[$row["PM_ID"]] = [$row["ptn"]];
		if ($row["cnt"] == 1) {
			$ModelDefForm[$row["PM_ID"]] = [$row["PF_ID"]];
		}
	}

	// Массив стандартных размеров
	$ModelStandart = array();
	$query = "
		SELECT PMM.PM_ID
			,PMM.PME_ID
			,GROUP_CONCAT(PMM.PMM_ID) PMMs
		FROM ProductModelsMechanism PMM
		GROUP BY PMM.PM_ID, PMM.PME_ID
	";
	$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($result) ) {
		$query = "
			SELECT IF(PSS.Length > 0, PSS.Length, '') Length
				,IF(PSS.Width > 0, PSS.Width, '') Width
				,IF(PSS.PieceAmount > 0, PSS.PieceAmount, 2) PieceAmount
				,IF(PSS.PieceSize > 0, PSS.PieceSize, '') PieceSize
				,CONCAT(IF(PSS.Width > 0, '', 'Ø'), PSS.Length, IF(PSS.PieceSize, CONCAT('(+', IF(PSS.PieceAmount, CONCAT(PSS.PieceAmount, 'x'), ''), PSS.PieceSize, ')'), ''), IF(PSS.Width > 0, CONCAT('х', PSS.Width), '')) size
			FROM ProductStandartSize PSS
			WHERE PSS.PMM_ID IN ({$row["PMMs"]})
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$sizes = "";
		while( $subrow = mysqli_fetch_array($subres) ) {
			$sizes .= "<a href='#' length='{$subrow["Length"]}' width='{$subrow["Width"]}' pa='{$subrow["PieceAmount"]}' ps='{$subrow["PieceSize"]}'>{$subrow["size"]}</a>";
		}

		$ModelStandart[$row["PM_ID"]][$row["PME_ID"]] = [$sizes];
	}
?>
	<script>
		// Передаем в JavaScript массивы
		ModelForm = <?= json_encode($ModelForm); ?>;
		ModelForm_standart = <?= json_encode($ModelForm_standart); ?>;
		ModelPatina = <?= json_encode($ModelPatina); ?>;
		ModelDefForm = <?= json_encode($ModelDefForm); ?>;
		ModelMech = <?= json_encode($ModelMech); ?>;
		ModelMech_box = <?= json_encode($ModelMech_box); ?>;
		ModelStandart = <?= json_encode($ModelStandart); ?>;
	</script>

<!-- Форма добавления стула -->
<div id='addchair' title='Параметры стула' class='addproduct' style='display:none'>
	<form method='post'>
		<fieldset>
		<input type='hidden' value='1' name='Type'>
		<div>
			<label>Kол-во:</label>
			<input required type='number' min='1' value='1' style='width: 70px; font-size: 2em;' name='Amount' autocomplete="off">
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
					echo "<option value='{$row["PM_ID"]}' data-foo='{$row["code"]}' data-comment='{$row["comment"]}'>{$row["Model"]}</option>";
				}
			?>
			</select>
		</div>
		<div>
			<label>Патина:</label>
			<div class='btnset'>
				<input type='radio' id='1ptn0' name='ptn' value='0' required>
					<label for='1ptn0'>Нет</label>
				<input type='radio' id='1ptn1' name='ptn' value='1' required>
					<label for='1ptn1'><i class='fa fa-paint-brush fa-lg' style="color: gold;"></i>Золото</label>
				<input type='radio' id='1ptn2' name='ptn' value='2' required>
					<label for='1ptn2'><i class='fa fa-paint-brush fa-lg' style="color: silver;"></i>Серебро</label>
				<input type='radio' id='1ptn3' name='ptn' value='3' required>
					<label for='1ptn3'><i class='fa fa-paint-brush fa-lg' style="color: chocolate;"></i>Кофе</label>
			</div>
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
		</div>
		<div class='order_material' style='text-align: center; display: none;'>
			<label></label>
			<span>Заказано:</span>
			<input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<span>Ожидается:</span>
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
		<span>Стандартные столы после добавления помечаются символом - <b>❋</b></span>
		<input type='hidden' value='2' name='Type'>
		<input type='hidden' value='1' name='Amount'>
		<div>
			<label>Kол-во:</label>
			<span id="amount" style="font-size: 2em;">1</span>
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
					echo "<option value='{$row["PM_ID"]}' data-foo='{$row["code"]}'  data-comment='{$row["comment"]}'>{$row["Model"]}</option>";
				}
			?>
			</select>
		</div>
		<div>
			<label title="Стандартная форма для выбранной модели выделена красным цветом."><i class='fa fa-question-circle'></i>Форма:</label>
			<div class="btnset" id="forms">
				<!--Список формируется в js-->
			</div>
			<br>
		</div>
		<div>
			<label>Механизм:</label>
			<div class="btnset" id="mechanisms">
				<!--Список формируется в js-->
			</div>
			<br>
		</div>
		<style>
			#box:checked + label:before {
				content: "Да";
			}
			#box + label:before {
				content: "Нет";
			}
		</style>
		<div id="wr_box">
			<label>Ящик:</label>
			<input type="checkbox" name="box" id="box" class="button" value="1">
			<label for="box"></label>
			<br>
		</div>
		<div>
			<label title="Используйте стандартные размеры для автозаполнения. Эти размеры соответствуют выбранной модели и механизму. Они доступны только если выбрана стандартная форма."><i class='fa fa-question-circle'></i>Стандарт:</label>
			<div id="standart" style="display: table;">
				<!--Список формируется в js-->
			</div>
		</div>
		<div>
			<label>Размер:</label>
			<input id="length" required type='number' min='500' max='3000' step='10' name='Length' style='width: 70px;' autocomplete='off' title="Длина" placeholder="Длина">
			<div id="sliding" style="display: inline-block;">
				<b>(</b>
				<b>+</b>
				<div id="piece_amount" style="display: inline-block;">
					<select name="PieceAmount" style="width: 30px;" title="Кол-во вставок">
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
					</select>
					<b>x</b>
				</div>
				<input type="number" name="PieceSize" required min="200" max="650" step="10" style='width: 70px;' autocomplete="off" title="Размер вставки" placeholder="Вставка">
				<b>)</b>
			</div>
			<b id="second_x">x</b>
			<input id='width' required type='number' min='500' max='1500' step='10' name='Width' style='width: 70px;' autocomplete='off' title="Ширина" placeholder="Ширина">
			<span id="separately">
				<label>
					<input type="checkbox" name="piece_stored" value="1">
					Вставки хранятся отдельно
				</label>
			</span>
			<br>
		</div>
		<div>
			<label>Патина:</label>
			<div class='btnset'>
				<input type='radio' id='2ptn0' name='ptn' value='0' required>
					<label for='2ptn0'>Нет</label>
				<input type='radio' id='2ptn1' name='ptn' value='1' required>
					<label for='2ptn1'><i class='fa fa-paint-brush fa-lg' style="color: gold;"></i>Золото</label>
				<input type='radio' id='2ptn2' name='ptn' value='2' required>
					<label for='2ptn2'><i class='fa fa-paint-brush fa-lg' style="color: silver;"></i>Серебро</label>
				<input type='radio' id='2ptn3' name='ptn' value='3' required>
					<label for='2ptn3'><i class='fa fa-paint-brush fa-lg' style="color: chocolate;"></i>Кофе</label>
			</div>
			<br>
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
		if ($page != "calc") {
			if (in_array('order_add_confirm', $Rights)) {
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
		<div class='order_material' style='text-align: center; display: none;'>
			<label></label>
			<span>Заказано:</span>
			<input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<span>Ожидается:</span>
			<input class='date to' type='text' name='arrival_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y", strtotime("+14 days")) ?>" readonly>
		</div>
		<?
			}
		?>
		<div>
			<label>Кромка ПВХ:</label>
			<input type='text' name='edge' style="width: 300px;" autocomplete='off' placeholder="Название кромки">
		</div>
		<div>
			<label>Примечание:</label>
			<textarea name='Comment' rows='3' cols='38'></textarea>
		</div>
		<?
		}
		?>
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
					<input type='radio' id='0ptn0' name='ptn' value='0' required>
						<label for='0ptn0'>Нет</label>
					<input type='radio' id='0ptn1' name='ptn' value='1' required>
						<label for='0ptn1'><i class='fa fa-paint-brush fa-lg' style="color: gold;"></i>Золото</label>
					<input type='radio' id='0ptn2' name='ptn' value='2' required>
						<label for='0ptn2'><i class='fa fa-paint-brush fa-lg' style="color: silver;"></i>Серебро</label>
					<input type='radio' id='0ptn3' name='ptn' value='3' required>
						<label for='0ptn3'><i class='fa fa-paint-brush fa-lg' style="color: chocolate;"></i>Кофе</label>
				</div>
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
			<div class='order_material' style='text-align: center; display: none;'>
				<label></label>
				<span>Заказано:</span>
				<input class='date from' type='text' name='order_date' size='12' autocomplete="off" defaultdate="<?= date("d.m.Y") ?>" readonly>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<span>Ожидается:</span>
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

<!-- Форма разбитя набора -->
<div id='order_cut' title='Разделение набора' style='display:none'>
	<form method="post" action="index.php">
		<fieldset>

		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы разбитя набора -->

<!-- Форма редактирования стоимости набора -->
<div id='update_price' title='Изменение стоимости набора' style='display:none'>
	<form method='post'>
		<fieldset>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы редактирования стоимости набора -->

<!-- Форма добавления оплаты к набору-->
<style>
	#add_payment table {
		text-align: center;
	}
	#add_payment input.payment_sum {
		width: 70px;
		text-align: right;
	}
	#add_payment input.terminal_payer {
		width: 180px;
	}
</style>
<div id='add_payment' title='Добавление оплаты' style='display:none'>
	<form method='post'>
		<fieldset>
		</fieldset>
		<div>
			<hr>
			<button style='float: right;'>Сохранить</button>
		</div>
	</form>
</div>
<!-- Конец формы добавления оплаты -->

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

	// Функция скрывает поле ширины для круглых форм
	function size_from_form(form) {
		if( form == 4 ) {
			$('#addtable #length').attr('title', 'Диаметр');
			$('#addtable #length').attr('placeholder', 'Диаметр');
			$('#addtable #second_x').hide('fast');
			$('#addtable #width').hide('fast');
			$('#addtable #width').attr('required', false);
		}
		else {
			$('#addtable #length').attr('title', 'Длина');
			$('#addtable #length').attr('placeholder', 'Длина');
			$('#addtable #second_x').show('fast');
			$('#addtable #width').show('fast');
			$('#addtable #width').attr('required', true);
		}
	}

	// Функция скрывает поля вставок при выборе механизма
	function piece_from_mechanism(mech) {
		if( mech == 1 || mech == 2 || mech == 5 ) {
			$('#addtable #sliding').show('fast');
			$('#addtable #sliding input[type="number"]').attr('required', true);
		}
		else {
			$('#addtable #sliding').hide('fast');
			$('#addtable #sliding input[type="number"]').attr('required', false);
			$('#addtable input[name="piece_stored"]').prop('checked', false);
		}

		if( mech == 2 || mech == 5 ) {
			$('#addtable #piece_amount').show('fast');
			$('#addtable #separately').show('fast');
		}
		else {
			$('#addtable #piece_amount').hide('fast');
			$('#addtable #separately').hide('fast');
		}
	}

	// Функция формирования списка форм в зависимости от модели стола
	function form_model_list(model, form) {
		var forms = "";
		var arr_model = ModelForm[model];	// Список форм для модели
		var arr_standart = ModelForm_standart[model];	// Список стандартных форм для модели
		var informs = 0;
		if( typeof arr_model !== "undefined" ) {
			$.each(arr_model, function(key, val){
				// Выделение цветом стандартных форм
				if (arr_standart[key] == 1) {
					var standart = "standart";
//					var filter = "filter: drop-shadow(2px 2px 3px black);";
				}
				else {
					var standart = "";
//					var filter = "filter: grayscale(100%);";
				}
				forms += "<input type='radio' required id='form" + key + "' name='Form' value='" + key + "' standart='"+arr_standart[key]+"'>";
				forms += "<label for='form" + key + "'><img class='form "+standart+"' src='/img/form"+key+".png'><br>"+val+"</label>";
				if( form == key ) { informs = 1; }
			});
		}
		$('#addtable #forms').html(forms);
		if( forms != "" ) {
			if( form > 0 && informs ) {
				$('#addtable input[name="Form"][value="'+form+'"]').prop('checked', true);
				size_from_form(form);
			}
			else {
				$('#addtable input[name="Form"][value="'+ModelDefForm[model]+'"]').prop('checked', true);
				size_from_form(ModelDefForm[model]);
			}
			$('#addtable #forms').buttonset();
		}
//		var val = $('#addtable input[name="Form"]:checked').val();
//		size_from_form(val);
	}

	// Функция формирования списка стандартных размеров в зависимости от модели и механизма стола
	function standart_model_list(model, form, mech) {
		var stn_form = $('#addtable input[name="Form"][value="'+form+'"]').attr('standart');
		if( stn_form == 1 ) {
			var standart = ModelStandart[model][mech];	// Список стандартных размеров для модели
		}
		else {
			var standart = "<br>";
		}
		$('#addtable #standart').html(standart);
		$('#addtable #standart a').button();
	}

	// Функция формирования списка механизмов в зависимости от модели стола
	function mech_model_list(model, mech) {
		var mechs = "";
		var arr_model = ModelMech[model];	// Список механизмов для модели
		var inmechs = 0;
		if( typeof arr_model !== "undefined" ) {
			$.each(arr_model, function(key, val){
				mechs += "<input type='radio' id='mechanism" + key + "' name='Mechanism' value='" + key + "'>";
				mechs += "<label for='mechanism" + key + "'>" + val + "</label>";
				if( mech == key ) { inmechs = 1; }
			});
		}
		$('#addtable #mechanisms').html(mechs);
		if( mechs != "" ) {
			if( mech > 0 && inmechs ) {
				$('#addtable input[name="Mechanism"][value="'+mech+'"]').prop('checked', true);
			}
			else {
				$('#addtable input[name="Mechanism"]:nth-child(1)').prop('checked', true);
			}
			$('#addtable #mechanisms').buttonset();
			var form_val = $('#addtable input[name="Form"]:checked').val();
			var mech_val = $('#addtable input[name="Mechanism"]:checked').val();
			mech_model_box(model, mech_val);
			piece_from_mechanism(mech_val);
			standart_model_list(model, form_val, mech_val);
		}
	}

	// Функция включения/выключения чекбокса ящика в зависимости от модели стола и механизма
	function mech_model_box(model, mech) {
		if( ModelMech_box[model][mech] == 1 ) {
			$('#addtable #wr_box').show('fast');
		}
		else {
			$('#addtable #wr_box').hide('fast');
		}
	}

	// Функция включения золотой патины для моделей с патиной
	function patina_model_list(model, type) {
		if (ModelPatina[model] == 1) {
			$('input[name="ptn"]').prop('checked', false);
		}
		else {
			$('#'+type+'ptn0').prop('checked', true);
		}
		$('input[name="ptn"]').button('refresh');
	}

	// Функция пересчитывает итог в форме редактирования стоимости набора
	function updtotal() {
		var total_sum = 0;
		var total_discount = 0;
		var total_percent = 0;
		$('.prod_price').each(function(){
			var prod_price = $(this).find('input').val();
			var prod_discount = $(this).parents('tr').find('.prod_discount input').val();
			var prod_amount = $(this).parents('tr').find('.prod_amount').html();
			var prod_sum = (prod_price - prod_discount) * prod_amount;
			var prod_percent = (prod_discount / prod_price * 100).toFixed(1);
			total_sum = total_sum + prod_sum;
			total_discount = total_discount + prod_discount * prod_amount;
			prod_sum = prod_sum.format();
			$(this).parents('tr').find('.prod_sum').html(prod_sum);
			$(this).parents('tr').find('.prod_percent').html(prod_percent);
		});
		total_percent = (total_discount / (total_sum + total_discount) * 100).toFixed(1);
		$('#prod_total input').val(total_sum);
		$('#discount input').val(total_discount);
		$('#discount span').html(total_percent);
	}

	//////////////////////////////////////////////////////////////////////////
	$(function() {
		// Select2
		function format (state) {
			var originalOption = state.element;
			if (!state.id || !$(originalOption).data('foo')) return state.text; // optgroup
			return "<div style='display: flex;'><img style='width: 50px; height: 50px; margin-right: 5px;' src='https://фабрикастульев.рф/images/prodlist/" + $(originalOption).data('foo') + ".jpg'/><span><b>" + state.text + "</b><br><i>" + $(originalOption).data('comment') + "</i></span><div>";
		};
		$('select[name="Model"]').select2({
			placeholder: "Выберите модель",
			language: "ru",
			templateResult: format,
			escapeMarkup: function(m) { return m; }
		});

		$('select[name="Blanks"]').select2({
			placeholder: "Выберите заготовку",
			language: "ru"
		});

		// Костыль для Select2 чтобы работал поиск
		$.ui.dialog.prototype._allowInteraction = function (e) {
			return true;
		};

		// Массив куда будут записываться данные по изделиям
		odd_data = new Array();

		// Глобальные переменные для хранения выбранных модели, формы, механизма
		var model;
		var form;
		var mechanism;

		// Форма добавления стульев
		$('.edit_product1').click(function() {

			id = $(this).attr('id');
			if( typeof id !== "undefined" ) {
				id = id.replace('prod', '');
			}
			var location = $(this).attr("location");
			var odid = $(this).attr("odid");

			// Очистка диалога
			$('#addchair select').val('').trigger('change');
			$('#addchair input[type="text"]').val('');
			$('#addchair select[name="Shipper"]').attr("required", false);
			$('#addchair textarea').val('');
			$('#addchair input[name="Amount"]').val('');
			$('#addchair input[name="Amount"]').prop('readonly', false);
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

				// Если изделие в работе, то кол-во не редактируется
				if( odd_data['inprogress'] == 1 )
				{
					$('#addchair input[name="Amount"]').prop('readonly', true);
				}

				materialonoff('#addchair');

				$("#addchair form").attr("action", "datasave.php?oddid="+id+"&location="+location);
			}
			else // Иначе добавляем новый стул
			{
				$('#addchair form').attr('action', 'orderdetail.php?id='+odid+'&add');
				patina_model_list(0, 1);
			}


			// Форма добавления/редактирования стульев
			$('#addchair').dialog({
				resizable: false,
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

		// При выборе модели стула включается патина
		$('#addchair select[name="Model"]').change( function() {
			if( $(this).val() == "" ) {
				patina_model_list(0, 1);
			}
			else {
				patina_model_list($(this).val(), 1);
			}
		});

		// Если нет ткани, то кнопка наличия не активна
		$('#addchair input[name="Material"]').change( function() {
			materialonoff('#addchair');
		});

		// Форма добавления столов
		$('.edit_product2').click(function() {

			id = $(this).attr('id');
			if( typeof id !== "undefined" ) {
				id = id.replace('prod', '');
			}
			var location = $(this).attr("location");
			var odid = $(this).attr("odid");

			// Очистка диалога
//			$('#addtable select[name="Model"] option').attr('disabled', false);
//			$('#addtable select[name="Model"]').select2();

			$('#addtable select').val('').trigger('change');
			$('#addtable input[type="text"]').val('');
			$('#addtable select[name="Shipper"]').attr("required", false);
			$('#addtable textarea').val('');
			$('#addtable #amount').text('1');
			$('#addtable #amount').parent('div').hide('fast');
			$('#addtable input[name="Amount"]').val('1');
			$('#addtable input[name="Length"]').val('');
			$('#addtable input[name="Width"]').val('');
			$('#addtable input[name="PieceSize"]').val('');
			$('#addtable input[name="piece_stored"]').prop('checked', false);
			$('#2radio').prop('checked', true);
			$('#2ptn0').prop('checked', true);
			$('#addtable .radiostatus').buttonset( 'option', 'disabled', true );

			// Выключается ящик
			$('#addtable #box').prop('checked', false);
			$('#addtable #box').button('refresh');

			$('#addtable input[type="radio"]').button("refresh");
			// Очистка инпутов дат заказа пластика
			$('#addtable .order_material').hide('fast');
			$('#addtable .order_material input').attr("required", false);
			$('#addtable .order_material input').val('');
			$('#addtable .order_material input.from').datepicker( "option", "maxDate", null );
			$('#addtable .order_material input.to').datepicker( "option", "minDate", null );
			// Устанавливаем дефолтное значение кол-ва вставок
			$('#addtable select[name=PieceAmount]').val('2');
			// Прячем картинки-треугольники
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

				model = odd_data['model'];
				form = odd_data['form'];
				mechanism = odd_data['mechanism'];

//				if( model > 0 ) { // Если не столешница
//					// Деактивируем список моделей в дропдауне
//					$('#addtable select[name="Model"] option').attr('disabled', true);
//					$('#addtable select[name="Model"] option[value="0"]').attr('disabled', false);		// Включаем столешницу
//					$('#addtable select[name="Model"] option[value='+model+']').attr('disabled', false);	// Включаем эту модель
//					$('#addtable select[name="Model"]').select2();
//				}

				if (odd_data['amount'] > 1) {
					$('#addtable #amount').text(odd_data['amount']);
					$('#addtable #amount').parent('div').show('fast');
				}
				$('#addtable input[name="Amount"]').val(odd_data['amount']);

				// Задание значение, создав при необходимости новую опцию
				if ($('#addtable select[name="Model"]').find("option[value='" + model + "']").length) {
					$('#addtable select[name="Model"]').val(model).trigger('change');
				} else {
					// Create a DOM Option and pre-select by default
					var newOption = new Option(odd_data['model_name'] + " (снят с производства)", model, true, true);
					newOption.className = "archive";
					// Append it to the select
					$('#addtable select[name="Model"]').append(newOption).trigger('change');
				}

				$('#2ptn'+odd_data['ptn']).prop('checked', true);
				$('#form'+form).prop('checked', true);
				$('#mechanism'+mechanism).prop('checked', true);
					piece_from_mechanism(mechanism);

				// Если есть ящик
				if( odd_data['box'] == 1 ) {
					$('#addtable #box').prop('checked', true);
					$('#addtable #box').button('refresh');
				}

				$('#addtable input[name="Length"]').val(odd_data['length']);
				$('#addtable input[name="Width"]').val(odd_data['width']);
				$('#addtable select[name="PieceAmount"]').val(odd_data['PieceAmount']);
				$('#addtable input[name="PieceSize"]').val(odd_data['PieceSize']);
				// Если вставки хранятся отдельно
				if( odd_data['piece_stored'] == 1 ) {
					$('#addtable input[name="piece_stored"]').prop('checked', true);
				}
				$('#addtable textarea[name="Comment"]').val(odd_data['comment']);
				$('#addtable input[name="Material"]').val(odd_data['material']);
				$('#addtable select[name="Shipper"]').val(odd_data['shipper']);
				$('#addtable input[name="edge"]').val(odd_data['edge']);
				$('#2radio'+odd_data['isexist']).prop('checked', true);
				$('#addtable input[type="radio"]').button('refresh');
				if( odd_data['isexist'] == 1 ) {
					$('#addtable .order_material').show('fast');
					$('#addtable .order_material input').attr("required", true);
					$('#addtable .order_material input.from').val( odd_data['order_date'] );
					$('#addtable .order_material input.to' ).val( odd_data['arrival_date'] );
				}

				materialonoff('#addtable');

				if (odid == 0) {
					$("#addtable form").attr("action", "orderdetail.php?id="+odid+"&location="+location+"&add");
				}
				else {
					$("#addtable form").attr("action", "datasave.php?oddid="+id+"&location="+location);
				}
			}
			else // Иначе добавляем новый стол
			{
				model = 0;
				form = 0;
				mechanism = 0;
				if (odid == 0) {
					$("#addtable form").attr("action", "orderdetail.php?id="+odid+"&location="+location+"&add");
				}
				else {
					$("#addtable form").attr("action", "orderdetail.php?id="+odid+"&add");
				}
				patina_model_list(0, 2);
			}

//			form_model_list(model, form);
			mech_model_list(model, mechanism);

			$("#addtable").dialog(
			{
				resizable: false,
				width: 750,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			// Автокомплит поверх диалога
			$( ".materialtags_2" ).autocomplete( "option", "appendTo", "#addtable" );

			return false;
		});

		// При выборе модели стола предлагаются формы столешниц, выводятся стандартные размеры и включается патина
		$('#addtable select[name="Model"]').on('change', function() {
			model = $(this).val();
			if( model == "" ) {
				form_model_list(0, form);
				mech_model_list(0, mechanism);
				patina_model_list(0, 2);
			}
			else {
				form_model_list($(this).val(), form);
				mech_model_list($(this).val(), mechanism);
				patina_model_list($(this).val(), 2);
			}
			// Очищаем размеры
			$('#addtable input[name="Length"]').val('');
			$('#addtable input[name="Width"]').val('');
			$('#addtable select[name="PieceAmount"]').val('2');
			$('#addtable input[name="PieceSize"]').val('');
		});

		// При смене формы - записываем значение в переменную form
		$('#addtable').on('change', 'input[name="Form"]', function() {
			form = $(this).val();
			size_from_form(form);
			var mech_val = $('#addtable input[name="Mechanism"]:checked').val();
			standart_model_list(model, form, mech_val);
			// Если форма меняется на нестандартную - очищаем размеры
			var stn_form = $('#addtable input[name="Form"][value="'+form+'"]').attr('standart');
			if( stn_form != 1 ) {
				$('#addtable input[name="Length"]').val('');
				$('#addtable input[name="Width"]').val('');
				$('#addtable select[name="PieceAmount"]').val('2');
				$('#addtable input[name="PieceSize"]').val('');
			}

		});

		// При выборе механизма - задействуются инпуты для вставок, показывается или прячется чекбокс ящика
		$('#addtable').on('change', 'input[name="Mechanism"]', function() {
			mechanism = $(this).val();
			piece_from_mechanism(mechanism);
			mech_model_box(model, mechanism);
			var form_val = $('#addtable input[name="Form"]:checked').val();
			standart_model_list(model, form_val, mechanism);
			// Очищаем размеры
			$('#addtable input[name="Length"]').val('');
			$('#addtable input[name="Width"]').val('');
			$('#addtable select[name="PieceAmount"]').val('2');
			$('#addtable input[name="PieceSize"]').val('');
		});

		// Автоматическое заполнение стандартных размеров
		$('#addtable').on('click', '#standart a', function() {
			$('#addtable input[name="Length"]').val($(this).attr('length'));
			$('#addtable input[name="Width"]').val($(this).attr('width'));
			$('#addtable select[name="PieceAmount"]').val($(this).attr('pa'));
			$('#addtable input[name="PieceSize"]').val($(this).attr('ps'));
		});

		// Если нет пластика, то кнопка наличия не активна
		$('#addtable input[name="Material"]').change( function() {
			materialonoff('#addtable');
		});

		// Форма добавления заготовок
		$('.edit_product0').click(function() {
			id = $(this).attr('id');
			if( typeof id !== "undefined" ) {
				id = id.replace('prod', '');
			}
			var location = $(this).attr("location");
			var odid = $(this).attr("odid");

			// Очистка диалога
			$('#addblank select').val('').trigger('change');
			$('#addblank input[type="text"]').val('');
			$('#addblank select[name="Shipper"]').attr("required", false);
			$('#addblank textarea').val('');
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
				$.ajax({ url: "ajax.php?do=odd_data&id=" + id, success:function(msg){ odd_data = msg; }, dataType: "json", async: false });

				$('#addblank input[name="Amount"]').val(odd_data['amount']);
				$('#addblank input[name="Price"]').val(odd_data['price']);
				if( odd_data['blank'] > 0 ) {
					$('#addblank select[name="Blanks"]').val(odd_data['blank']).trigger('change');
					$('#addblank select[name="Blanks"]').prop("required", true);
					$('#addblank input[name="Other"]').prop("required", false);
				}
				else {
					$('#addblank input[name="Other"]').val(odd_data['other']);
					$('#addblank input[name="Other"]').prop("required", true);
					$('#addblank select[name="Blanks"]').prop('required', false);
				}
				$('#addblank textarea[name="Comment"]').val(odd_data['comment']);

				// Заполняем ткань/пластик
				if( odd_data['mtype'] == 1 ) {
					$('#addblank input[name="Material"]:eq(0)').val(odd_data['material']);
					$('#addblank select[name="Shipper"]:eq(0)').val(odd_data['shipper']);
					$('#addblank input[name="Material"]:eq(1)').attr('disabled', true);
					$('#addblank select[name="Shipper"]:eq(1)').attr('disabled', true);
					$('#addblank input[name="mtype"]').val('1');
				}
				else if( odd_data['mtype'] == 2 ) {
					$('#addblank input[name="Material"]:eq(1)').val(odd_data['material']);
					$('#addblank select[name="Shipper"]:eq(1)').val(odd_data['shipper']);
					$('#addblank input[name="Material"]:eq(0)').attr('disabled', true);
					$('#addblank select[name="Shipper"]:eq(0)').attr('disabled', true);
					$('#addblank input[name="mtype"]').val('2');
				}

				$('#0ptn'+odd_data['ptn']).prop('checked', true);
				$('#0radio'+odd_data['isexist']).prop('checked', true);
				$('#addblank input[type="radio"]').button('refresh');
				if( odd_data['isexist'] == 1 ) {
					$('#addblank .order_material').show('fast');
					$('#addblank .order_material input').attr("required", true);
					$('#addblank .order_material input.from').val( odd_data['order_date'] );
					$('#addblank .order_material input.to' ).val( odd_data['arrival_date'] );
				}

				// Если изделие в работе, то кол-во не редактируется
				if( odd_data['inprogress'] == 1 )
				{
					$('#addblank input[name="Amount"]').prop('readonly', true);
				}

				materialonoff('#addblank');
				$("#addblank form").attr("action", "datasave.php?oddid="+id+"&location="+location);
			}
			else // Иначе добавляем новую заготовку
			{
				$('#addblank form').attr('action', 'orderdetail.php?id='+odid+'&add');
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
				resizable: false,
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

		// Если выбрана заготовка, то прочее очищается
		$('#addblank select[name="Blanks"]').change( function() {
			$('#addblank input[name="Other"]').val('');
			$('#addblank input[name="Other"]').prop("required", false);
			$('#addblank select[name="Blanks"]').prop("required", true);
		});

		// Если указано прочее, то заготовка очищается
		$('#addblank input[name="Other"]').change( function() {
			val = $(this).val();
			$('#addblank select[name="Blanks"]').val('').trigger('change');
			$('#addblank select[name="Blanks"]').prop('required', false);
			$('#addblank input[name="Other"]').prop('required', true);
			$('#addblank input[name="Other"]').val(val);
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

		// При очистке поля с материалом - очищаем поставщика
		$( ".materialtags_1, .materialtags_2" ).on("keyup", function() {
			if( $(this).val().length < 2 ) {
				$(this).parent('div').find('select[name="Shipper"]').val('');
			}
			if( $(this).val().length > 0 ) {
				$(this).parent('div').find('select[name="Shipper"]').attr("required", true);
			}
			else {
				$(this).parent('div').find('select[name="Shipper"]').attr("required", false);
			}
		});

		// Форма редактирования стоимости набора
		$('.update_price_btn').click( function() {
			var OD_ID = $(this).attr('id');
			var location = $(this).attr("location");
			$.ajax({ url: "ajax.php?do=update_price&OD_ID="+OD_ID, dataType: "script", async: false });

			$("#update_price form").attr("action", "datasave.php?OD_ID="+OD_ID+"&add_price");
			$("#update_price input[name=location]").val(location);

			$('#update_price').dialog({
				resizable: false,
				width: 700,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});

			updtotal();

			$('.prod_price input, .prod_discount input').on('input', function() {
				updtotal();
			});

			return false;
		});

		// При включении галки "терминал" активируется инпут для фамилии
		$('#add_payment').on("change", ".terminal", function() {
			var ch = $(this).prop('checked');
			var terminal_payer = $(this).parents('tr').find('input[type="text"].terminal_payer');
			var terminal_payer_hidden = $(this).parents('tr').find('input[type="hidden"].terminal_payer');
			var account = $(this).parents('tr').find('select.account');
			var payment_date = $(this).parents('tr').find('.payment_date');
			if( ch ) {
				$(terminal_payer).prop('disabled', false);
				$(terminal_payer).prop('required', true);
				$(terminal_payer_hidden).val( $(terminal_payer).val() );
				$(account).prop('disabled', true);
				$(account).hide('fast');
				$(payment_date).datepicker();
				$(payment_date).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );
				$(payment_date).focus();
			}
			else {
				$(terminal_payer).prop('disabled', true);
				$(terminal_payer).prop('required', false);
				$(terminal_payer_hidden).val('');
				$(account).prop('disabled', false);
				$(account).show('fast');
				$(payment_date).datepicker('destroy');
				$(payment_date).val('<?=( date('d.m.Y') )?>');
			}
		});

		// Кнопка добавления оплаты к набору
		$('.add_payment_btn').click( function() {
			var OD_ID = $(this).attr('id');
			var location = $(this).attr("location");
			$.ajax({ url: "ajax.php?do=add_payment&OD_ID="+OD_ID, dataType: "script", async: false });

			$("#add_payment form").attr("action", "datasave.php?OD_ID="+OD_ID+"&add_payment");
			$("#add_payment input[name=location]").val(location);

			$('#add_payment').dialog({
				width: 650,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			$('input[name=payment_sum_add]').focus();

			$('#add_payment .terminal').change();
			return false;
		});

	});
</script>
