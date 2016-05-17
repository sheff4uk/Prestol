<?
	session_start();
	include "config.php";

	//$location = "blankctock.php";

	// Обновление основной информации о заказе
	if( isset($_POST["Blank"]) )
	{
        $Worker = $_POST["Worker"] <> "" ? $_POST["Worker"] : "NULL";
        $Blank = $_POST["Blank"] <> "" ? $_POST["Blank"] : "NULL";
        $Amount = $_POST["Amount"] <> "" ? $_POST["Amount"] : "NULL";
        $Tariff = $_POST["Tariff"] <> "" ? $_POST["Tariff"] : "NULL";
        $Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );
		$query = "INSERT INTO BlankStock(WD_ID, BL_ID, Amount, Tariff, Comment)
				  VALUES ({$Worker}, {$Blank}, {$Amount}, {$Tariff}, '{$Comment}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		
		//header( "Location: ".$location );
		header( "Location: ".$_SERVER['REQUEST_URI'] );
		die;
	}

	$title = 'Заготовки';
	include "header.php";
?>
	<p>
		<button class='edit_blank'>Добавить заготовки</button>
	</p>

	<!-- Форма добавления заготовки -->
	<div id='addblank' title='Заготовки' class="addproduct" style='display:none'>
		<form method="post">
			<fieldset>
				<div>
					<label>Работник:</label>
					<select name='Worker'>
						<option value="">-=Выберите работника=-</option>
						<?
						$query = "SELECT WD.WD_ID, WD.Name FROM WorkersData WD";
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
				<input type='submit' value='Сохранить' style='float: right;'>
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
				$query = "SELECT BL.Name,(SUM(BS.Amount) - SUM(IFNULL(SPB.Amount, 0))) Amount
							FROM BlankList BL
							LEFT JOIN BlankStock BS ON BS.BL_ID = BL.BL_ID
							LEFT JOIN (
								SELECT PB.BL_ID, (ODD.Amount * PB.Amount) Amount
								FROM OrdersDataDetail ODD
								JOIN ProductBlank PB ON PB.PM_ID = ODD.PM_ID
								JOIN OrdersDataSteps ODS ON ODS.ST_ID = PB.ST_ID AND ODS.ODD_ID = ODD.ODD_ID AND ODS.WD_ID IS NOT NULL
							) SPB ON SPB.BL_ID = BL.BL_ID
							GROUP BY BL.BL_ID
							ORDER BY BL.PT_ID, BL.Name DESC";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					echo "<tr>";
					echo "<td>{$row["Name"]}</td>";
					echo "<td class='txtright'>{$row["Amount"]}</td>";
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
			$query = "SELECT BS.Date DateKey, DATE_FORMAT(DATE(BS.Date), '%d.%m.%Y') Date, TIME(BS.Date) Time, WD.Name Worker, BL.Name Blank, BS.Amount, BS.Tariff, BS.Comment
						FROM BlankStock BS
						LEFT JOIN WorkersData WD ON WD.WD_ID = BS.WD_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = BS.BL_ID
						ORDER BY BS.Date DESC";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			while( $row = mysqli_fetch_array($res) )
			{
				echo "<tr>";
				echo "<td>{$row["Date"]}</td>";
				echo "<td>{$row["Time"]}</td>";
				echo "<td>{$row["Worker"]}</td>";
				echo "<td>{$row["Blank"]}</td>";
				echo "<td class='txtright'>{$row["Amount"]}</td>";
				echo "<td class='txtright'>{$row["Tariff"]}</td>";
				echo "<td>{$row["Comment"]}</td>";
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
	// Форма добавления заготовок
	$('.edit_blank').click(function() {
		// Форма добавления/редактирования заготовок
		$('#addblank').dialog({
			width: 500,
			modal: true,
			show: 'blind',
			hide: 'explode',
		});
		return false;
	});
</script>
