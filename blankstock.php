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
	<table>
		<thead>
		<tr>
			<th>Работник</th>
			<th>Заготовка</th>
			<th>Кол-во</th>
			<th>Тариф</th>
			<th>Примечание</th>
			<th>Действие</th>
		</tr>
		</thead>
		<form method='post'>
		<tbody>
		<tr>
			<td>
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
			</td>
			<td>
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
			</td>
			<td><input required type='number' name='Amount' size='6' class='amount'></td>
			<td><input type='number' name='Tariff' min='0' step='10' class='tariff'></td>
			<td><textarea name='Comment' rows='4' cols='15'></textarea></td>
			<td><input type='submit' value='Сохранить'></td>
		</tr>
		</tbody>
		</form>
	</table>

	<h1>Список заготовок</h1>
	
	<div class="log-blank">
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
		</tr>
		</thead>
		<tbody>
		
<?
		$query = "SELECT DATE_FORMAT(DATE(BS.Date), '%d.%m.%Y') Date, TIME(BS.Date) Time, WD.Name Worker, BL.Name Blank, BS.Amount, BS.Tariff, BS.Comment
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
			echo "<td>{$row["Amount"]}</td>";
			echo "<td>{$row["Tariff"]}</td>";
			echo "<td>{$row["Comment"]}</td>";
			echo "</tr>";
		}
?>

		</tbody>
	</table>
	</div>

</body>
</html>
<script>
	//odd = <?= json_encode($ODD); ?>;
</script>
