<?
	session_start();
	include "config.php";

	$datediff = 60; // Максимальный период отображения данных

	$location = $_SERVER['REQUEST_URI'];

	// Обновление/добавление заготовок
	if( isset($_POST["Blank"]) )
	{
		$Worker = $_POST["Worker"] <> "" ? $_POST["Worker"] : "NULL";
		$Blank = $_POST["Blank"] <> "" ? $_POST["Blank"] : "NULL";
		$Amount = $_POST["Amount"] <> "" ? $_POST["Amount"] : "NULL";
		$Tariff = $_POST["Tariff"] <> "" ? $_POST["Tariff"] : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );

		// Редактирование
		if( $_POST["id_date"] <> "" ) {
			$query = "UPDATE BlankStock
					  SET WD_ID = {$Worker}, BL_ID = {$Blank}, Amount = {$Amount}, Tariff = {$Tariff}, Comment = '{$Comment}'
					  WHERE Date = '{$_POST["id_date"]}'";
		}
		// Добавление
		else {
			$query = "INSERT INTO BlankStock(WD_ID, BL_ID, Amount, Tariff, Comment)
					  VALUES ({$Worker}, {$Blank}, {$Amount}, {$Tariff}, '{$Comment}')";
		}
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		
		header( "Location: ".$location );
		//header( "Location: ".$_SERVER['REQUEST_URI'] );
		die;
	}

	$title = 'Заготовки';
	include "header.php";

	// Массив тарифов заготовок
	$BlankTariff = array();
	$query = "SELECT BL_ID, Tariff FROM BlankList";
	$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($result) ) {
		$BlankTariff[$row["BL_ID"]] = [$row["Tariff"]];
	}
?>
	<script>
		// Передаем в JavaScript массив тарифов заготовок
		BlankTariff = <?= json_encode($BlankTariff); ?>;
	</script>

	<p>
		<button class='edit_blank'>Добавить заготовки</button>
	</p>

	<!-- Форма добавления заготовки -->
	<div id='addblank' title='Заготовки' class="addproduct" style='display:none'>
		<form method="post">
			<fieldset>
				<input type='hidden' name='id_date'>
				<div>
					<label>Работник:</label>
					<select name='Worker'>
						<option value="">-=Выберите работника=-</option>
						<?
						$query = "SELECT WD.WD_ID, WD.Name FROM WorkersData WD WHERE WD.Hourly = 0 ORDER BY WD.Name";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["WD_ID"]}'>{$row["Name"]}</option>";
						}
						?>
					</select>
				</div>
				<div>
					<label>Заготовка:</label>
					<select required name='Blank'>
						<option value="">-=Выберите заготовку=-</option>
						<?
						$query = "SELECT BL.BL_ID, BL.Name FROM BlankList BL ORDER BY BL.Name";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["BL_ID"]}'>{$row["Name"]}</option>";
						}
						?>
					</select>
				</div>
				<div>
					<label>Кол-во:</label>
					<input required type='number' name='Amount' class='amount'>
				</div>
				<div>
					<label>Тариф:</label>
					<input type='number' name='Tariff' min='0' step='5' class='tariff'>
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

	<div class="halfblock">
		<h1>Наличие заготовок</h1>
		<table>
			<thead>
			<tr>
				<th>Заготовка</th>
				<th>Кол-во</th>
			</tr>
			</thead>
			<tbody>
			<?
				// Количество остатков заготовок
				$query = "SELECT BL.Name, (IFNULL(SBS.Amount, 0) - IFNULL(SPB.Amount, 0)) Amount
							FROM BlankList BL
							LEFT JOIN (
								SELECT BS.BL_ID, SUM(BS.Amount) Amount
								FROM BlankStock BS
								GROUP BY BS.BL_ID
							) SBS ON SBS.BL_ID = BL.BL_ID
							LEFT JOIN (
								SELECT PB.BL_ID, SUM(ODD.Amount * PB.Amount) Amount
								FROM OrdersDataDetail ODD
								JOIN ProductBlank PB ON PB.PM_ID = ODD.PM_ID
								GROUP BY PB.BL_ID
							) SPB ON SPB.BL_ID = BL.BL_ID
							ORDER BY BL.PT_ID DESC, BL.Name ASC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					if( $row["Amount"] < 0 )
						$color = ' bg-red';
					else
						$color = '';
					echo "<tr>";
					echo "<td>{$row["Name"]}</td>";
					echo "<td class='txtright'><span class='{$color}'>{$row["Amount"]}</span></td>";
					echo "</tr>";
				}
			?>
			</tbody>
		</table>
	</div>

	<div class="log-blank halfblock">
		<h1>Журнал заготовок</h1>
		<table>
			<thead>
			<tr>
				<th>Дата</th>
				<th>Время</th>
				<th>Работник</th>
				<th>Заготовка</th>
				<th>Кол-во</th>
				<th>Тариф</th>
				<th>Примечание</th>
				<th>Действие</th>
			</tr>
			</thead>
			<tbody>

	<?
			$query = "SELECT BS.Date DateKey, DATE_FORMAT(DATE(BS.Date), '%d.%m.%Y') Date, TIME(BS.Date) Time, WD.Name Worker, BL.Name Blank, BS.Amount, BS.Tariff, BS.Comment, WD.WD_ID, BL.BL_ID
						FROM BlankStock BS
						LEFT JOIN WorkersData WD ON WD.WD_ID = BS.WD_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = BS.BL_ID
						WHERE DATEDIFF(NOW(), BS.Date) <= {$datediff}
						ORDER BY BS.Date DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				echo "<tr>";
				echo "<td>{$row["Date"]}</td>";
				echo "<td>{$row["Time"]}</td>";
				echo "<td class='worker' val='{$row["WD_ID"]}'>{$row["Worker"]}</td>";
				echo "<td class='blank' val='{$row["BL_ID"]}'>{$row["Blank"]}</td>";
				echo "<td class='amount txtright'>{$row["Amount"]}</td>";
				echo "<td class='tariff txtright'>{$row["Tariff"]}</td>";
				echo "<td class='comment'>{$row["Comment"]}</td>";
				echo "<td><a href='#' id='{$row["DateKey"]}' class='button edit_blank' location='{$location}' title='Редактировать заготовки'><i class='fa fa-pencil fa-lg'></i></a></td>";
				echo "</tr>";
			}
	?>

			</tbody>
		</table>
	</div>

</body>
</html>
<script>
	$(document).ready(function() {
		// Форма добавления заготовок
		$('.edit_blank').click(function() {
			var id = $(this).attr('id');
			var worker = $(this).parents('tr').find('.worker').attr('val');
			var blank = $(this).parents('tr').find('.blank').attr('val');
			var amount = $(this).parents('tr').find('.amount').html();
			var tariff = $(this).parents('tr').find('.tariff').html();
			var comment = $(this).parents('tr').find('.comment').html();

			// Очистка диалога
			$('#addblank input, #addblank select, #addblank textarea').val('');

			// Заполнение
			if( typeof id !== "undefined" )
			{
				$('#addblank select[name="Worker"]').val(worker);
				$('#addblank select[name="Blank"]').val(blank);
				$('#addblank input[name="Amount"]').val(amount);
				$('#addblank input[name="Tariff"]').val(tariff);
				$('#addblank textarea[name="Comment"]').val(comment);
				$('#addblank input[name="id_date"]').val(id);
			}

			// Форма добавления/редактирования заготовок
			$('#addblank').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
			});
			return false;
		});
	});
</script>
