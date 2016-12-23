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
			<input required type='number' min='1' value='1' style='width: 50px;' name='Amount' autocomplete="off">
			&nbsp;&nbsp;&nbsp;
			<img src='/img/attention.png' class='attention' id='Amount' title='Изделие в работе. Уменьшение кол-ва приведет к перемещению лишних изделий на склад.'>
		</div>
		<div style='display: none;'>
			<label>Цена за шт:</label>
			<input type='number' min='0' style='width: 100px;' name='Price' autocomplete="off">
		</div>
		<div>
			<label>Модель:</label>
			<input type='hidden' id='Model'>
			<select name='Model' required>
			<?
				echo "<option value=''>-=Выберите модель=-</option>";
				$query = "SELECT * FROM ProductModels WHERE PT_ID = 1 ORDER BY Model";
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
			<input type="text" name="patina" placeholder="Цвет патины">
		</div>
		<div>
			<label>Ткань:</label>
			<input type='text' class='textiletags' name='Material' style='width: 200px;'>
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
			<span>Дата заказа:</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>Ожидается:</span><br>
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
	if( in_array('order_add_confirm', $Rights) ) {
?>
		<div class="accordion">
			<h3>Найдено <span></span> "Свободных"</h3>
			<div>
			</div>
		</div>
<?
	}
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
			<input required type='number' min='1' value='1' style='width: 50px;' name='Amount' autocomplete="off">
			&nbsp;&nbsp;&nbsp;
			<img src='/img/attention.png' class='attention' id='Amount' title='Изделие в работе. Уменьшение кол-ва приведет к перемещению лишних изделий на склад.'>
		</div>
		<div style='display: none;'>
			<label>Цена за шт:</label>
			<input type='number' min='0' style='width: 100px;' name='Price' autocomplete="off">
		</div>
		<div>
			<label>Модель:</label>
			<select name="Model">
			<?
				echo "<option value=''>-=Выберите модель=-</option>";
				$query = "SELECT * FROM ProductModels WHERE PT_ID = 2 ORDER BY Model";
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
			<input type="text" name="patina" placeholder="Цвет патины">
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
			<input type='text' class="plastictags" name='Material' style="width: 200px;">
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
	if( in_array('order_add_confirm', $Rights) ) {
?>
		<div class="accordion">
			<h3>Найдено <span></span> "Свободных"</h3>
			<div>
			</div>
		</div>
<?
	}
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
				<input required type='number' min='1' value='1' style='width: 50px;' name='Amount' autocomplete="off">
			</div>
			<div style='display: none;'>
				<label>Цена за шт:</label>
				<input type='number' min='0' style='width: 100px;' name='Price' autocomplete="off">
			</div>
			<div>
				<label>Заготовка:</label>
				<select required name="Blanks">
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
				<input type="text" name="patina" placeholder="Цвет патины">
			</div>
			<div>
				<label>Ткань:</label>
				<input type='text' class='textiletags' name='Material' style='width: 200px;'>
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
				<input type='text' class="plastictags" name='Material' style="width: 200px;">
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
