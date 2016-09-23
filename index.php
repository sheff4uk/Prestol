<?
	include "config.php";
	$title = 'Престол главная';
	include "header.php";

	$datediff = 60; // Максимальный период отображения данных
	
	$location = $_SERVER['REQUEST_URI'];
	
	// Добавление в базу нового заказа
	if( isset($_POST["StartDate"]) )
	{
		if( !in_array('order_add', $Rights) ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$AddDate = date("Y-m-d");
		$StartDate = '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'';
		$EndDate = $_POST["EndDate"] ? '\''.date( "Y-m-d", strtotime($_POST["EndDate"]) ).'\'' : "NULL";
		$ClientName = mysqli_real_escape_string( $mysqli, $_POST["ClientName"] );
		$Shop = $_POST["Shop"] > 0 ? $_POST["Shop"] : "NULL";
		$OrderNumber = mysqli_real_escape_string( $mysqli, $_POST["OrderNumber"] );
		$Color = mysqli_real_escape_string( $mysqli, $_POST["Color"] );
		$Comment = mysqli_real_escape_string( $mysqli, $_POST["Comment"] );
		// Удаляем лишние пробелы
		$ClientName = trim($ClientName);
		$OrderNumber = trim($OrderNumber);
		$Color = trim($Color);
		$Comment = trim($Comment);
		$query = "INSERT INTO OrdersData(CLientName, AddDate, StartDate, EndDate, SH_ID, OrderNumber, Color, Comment)
				  VALUES ('{$ClientName}', '{$AddDate}', $StartDate, $EndDate, $Shop, '{$OrderNumber}', '{$Color}', '{$Comment}')";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		
		// Перенаправление на экран деталей заказа
		$id = mysqli_insert_id( $mysqli );
//		header( "Location: orderdetail.php?id=".$id );
		exit ('<meta http-equiv="refresh" content="0; url=/orderdetail.php?id='.$id.'">');
		die;
	}

	// Удаление заказа
	if( $_GET["del"] )
	{
		if( !in_array('order_add', $Rights) ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$id = (int)$_GET["del"];

//		$query = "DELETE FROM OrdersData WHERE OD_ID={$id}";
		$query = "UPDATE OrdersData SET Del = 1 WHERE OD_ID={$id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		//header( "Location: /" ); // Перезагружаем экран
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}

	// Подтверждение готовности заказа
	if( $_GET["ready"] )
	{
		if( !in_array('order_ready', $Rights) ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$id = (int)$_GET["ready"];
		$date = date("Y-m-d");
		$query = "UPDATE OrdersData SET ReadyDate = '{$date}' WHERE OD_ID={$id}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		//header( "Location: /" ); // Перезагружаем экран
		exit ('<meta http-equiv="refresh" content="0; url=/">');
		die;
	}
?>
	
	<div id="overlay"></div>
	<? include "forms.php"; ?>

	<p>
		<?
//		if( $archive == 1 )
//		{
//			echo "<a href='/' class='button'>К в работе</a>";
//		}
//		else
//		{
//			echo "<a href='?archive=1' class='button'>К готовым</a>";
//		}
		?>
		<form method="get">
			<select name="archive" onchange="this.form.submit()">
				<option value="0" <?=($archive == 0) ? "selected" : ""?>>В работе</option>
				<option value="1" <?=($archive == 1) ? "selected" : ""?>>Готовые</option>
				<option value="2" <?=($archive == 2) ? "selected" : ""?>>Все</option>
			</select>
		</form>
	</p>

	<!-- Форма добавления заказа -->
	<div id='order_form' class='addproduct' title='Новый заказ' style='display:none;'>
		<form method='post'>
			<fieldset>
				<div>
					<label>Заказчик:</label>
					<input type='text' class='clienttags' name='ClientName' size='38'>
				</div>
				<div>
					<label>Дата приема:</label>
					<input required type='text' name='StartDate' class='date from' size='12' value='<?= date("d.m.Y") ?>' autocomplete='off' readonly>
				</div>
				<div>
					<label>Дата сдачи:</label>
					<input type='text' name='EndDate' class='date to' size='12' autocomplete='off' readonly>
				</div>
				<div>
					<label>Салон:</label>
					<select required name='Shop' style="width: 150px;">
						<option value="">-=Выберите салон=-</option>
						<option value="0" style="background: #999;">Свободные</option>
						<?
						$query = "SELECT Shops.SH_ID
										,CONCAT(Cities.City, '/', Shops.Shop) AS Shop
										,Cities.Color
									FROM Shops
									JOIN Cities ON Cities.CT_ID = Shops.CT_ID
									WHERE Cities.CT_ID IN ({$USR_cities})
									ORDER BY Cities.City, Shops.Shop";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["SH_ID"]}' style='background: {$row["Color"]};'>{$row["Shop"]}</option>";
						}
						?>
					</select>
				</div>
				<div>
					<label>№ квитанции:</label>
					<input type='text' name='OrderNumber' autocomplete='off'>
				</div>
				<div>
					<label>Цвет:</label>
					<input required type='text' class='colortags' name='Color' size='38' autocomplete='off'>
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


<?
	// Кнопка добавления заказа
	if( in_array('order_add', $Rights) ) {
		echo "<div id='add_btn' title='Добавить новый заказ'></div>";
	}

	// Кнопка печати
	if( in_array('order_print', $Rights) ) {
		echo '<div id="print_btn" href="#print_tbl" class="open_modal" title="Распечатать таблицу">';
		echo '<a id="toprint"></a>';
		echo '</div>';
	}

	// Копирование ссылки на таблицу в буфер
	if( in_array('order_link', $Rights) ) {
		echo '<input id="post-link" style="position: absolute; z-index: -1;">';
		echo '<div id="copy_link" data-clipboard-target="#post-link" style="display: none;">';
		echo '<a id="copy-button" data-clipboard-target="#post-link" style="display: block; height: 100%" title="Скопировать ссылку в буфер обмена"></a>';
		echo '</div>';
	}

	// Кнопка печати накладной
	if( in_array('print_torg12', $Rights) ) {
		echo '<div id="print_torg12" title="Распечатать накладную" style="display: none;">';
		echo '<a id="torg12" target="_blank">ТОРГ<br>12</a>';
		echo '</div>';
	}

	// Кнопка печати счета
	if( in_array('print_schet', $Rights) ) {
		echo '<div id="print_schet" title="Распечатать счёт" style="display: none;">';
		echo '<a id="schet" target="_blank">СЧЁТ</a>';
		echo '</div>';
	}

	// Кнопка печати этикеток на упаковку
	if( in_array('print_label_box', $Rights) ) {
		echo '<div id="print_labelsbox" title="Распечатать этикетки на упаковку" style="display: none;">';
		echo '<a id="labelsbox" target="_blank"></a>';
		echo '</div>';
	}
?>

	<!-- ФИЛЬТР ГЛАВНОЙ ТАБЛИЦЫ -->
	<table class="main_table">
		<form method='get' action='filter.php'>
		<thead>
		<tr>
			<th width="45"><input type='text' name='f_CD' size='8' value='<?= $_SESSION["f_CD"] ?>' class='<?=($_SESSION["f_CD"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="5%"><input type='text' name='f_CN' size='8' value='<?= $_SESSION["f_CN"] ?>' class='clienttags <?=($_SESSION["f_CN"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="5%"><input type='text' name='f_SD' size='8' value='<?= $_SESSION["f_SD"] ?>' class='<?=($_SESSION["f_SD"] != "") ? "filtered" : ""?>'></th>
			<th width="5%"><input type='text' name='f_ED' size='8' value='<?= $_SESSION["f_ED"] ?>' class='<?=($_SESSION["f_ED"] != "") ? "filtered" : ""?>'></th>
			<th width="5%"><input type='text' name='f_SH' size='8' class='shopstags <?=($_SESSION["f_SH"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_SH"] ?>'></th>
			<th width="5%"><input type='text' name='f_ON' size='8' value='<?= $_SESSION["f_ON"] ?>' class="<?=($_SESSION["f_ON"] != "") ? "filtered" : ""?>"></th>
			<th width="25%"><input type='text' name='f_Z' value='<?= $_SESSION["f_Z"] ?>' class="<?=($_SESSION["f_Z"] != "") ? "filtered" : ""?>"></th>
			<th width="15%"><input type='text' name='f_M' size='8' class='textileplastictags <?=($_SESSION["f_M"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_M"] ?>'></th>
			<th width="15%"><input type='text' name='f_CR' size='8' class='colortags <?=($_SESSION["f_CR"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_CR"] ?>'></th>
			<th width="100" style="font-size: 0;">
				<select name="f_PR" style="width: 70%;" onchange="this.form.submit()" class="<?=($_SESSION["f_PR"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="0" <?= ($_SESSION["f_PR"] === "0") ? 'selected' : '' ?>>Не назначен!</option>
					<option value="02" <?= ($_SESSION["f_PR"] === "02") ? 'selected' : '' ?>>Не назначен! - Столы</option>
					<option value="01" <?= ($_SESSION["f_PR"] === "01") ? 'selected' : '' ?>>Не назначен! - Стулья</option>
					<option value="00" <?= ($_SESSION["f_PR"] === "00") ? 'selected' : '' ?>>Не назначен! - Прочее</option>
					<?
						$query = "SELECT ODS.WD_ID, WD.Name, COUNT(1) Cnt
									FROM OrdersDataSteps ODS
									LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
									WHERE ODS.WD_ID IS NOT NULL
									GROUP BY ODS.WD_ID
									ORDER BY Cnt DESC";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["WD_ID"]}' ";
							if( $_SESSION["f_PR"] == $row["WD_ID"] ) echo "selected";
							echo ">{$row["Name"]}</option>";
						}
					?>
				</select>
				<select name="f_ST" style="width:30%;" onchange="this.form.submit()" class="<?=($_SESSION["f_ST"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="0" <?= ($_SESSION["f_ST"] == "0") ? 'selected' : '' ?> class="inwork">В работе</option>
					<option value="1" <?= ($_SESSION["f_ST"] == "1") ? 'selected' : '' ?> class="ready">Готово</option>
				</select>
			</th>
			<th width="40">
				<select name="f_X" style="width: 100%;" onchange="this.form.submit()" class="<?=($_SESSION["f_X"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="1" <?= ($_SESSION["f_X"] == 1) ? 'selected' : '' ?>>X</option>
				</select>
			</th>
			<th width="45">
				<style>
					.IsPainting {
						width: 100%;
						font-family: FontAwesome;
					}
				</style>
				<select name="f_IP" class="IsPainting <?=($_SESSION["f_IP"] != "") ? "filtered" : ""?>" onchange="this.form.submit()">
					<option></option>
					<option value="1" <?= ($_SESSION["f_IP"] == 1) ? 'selected' : '' ?> class="notready">&#xf006 - Не в работе</option>
					<option value="2" <?= ($_SESSION["f_IP"] == 2) ? 'selected' : '' ?> class="inwork">&#xf123 - В работе</option>
					<option value="3" <?= ($_SESSION["f_IP"] == 3) ? 'selected' : '' ?> class="ready">&#xf005 - Готово</option>
				</select>
			</th>
			<th width="15%"><input type='text' name='f_N' value='<?= $_SESSION["f_N"] ?>' class="<?=($_SESSION["f_N"] != "") ? "filtered" : ""?>"></th>
			<th width="80"><button title="Фильтр"><i class="fa fa-filter fa-lg"></i></button><a href="filter.php?location=<?=$location?>" class="button" title="Сброс"><i class="fa fa-times fa-lg"></i></a><input type='hidden' name='location' value='<?=$location?>'></th>
		</tr>
		</thead>
		</form>
	</table>
	<!-- //ФИЛЬТР ГЛАВНОЙ ТАБЛИЦЫ -->
<div id="print_tbl">
	<!-- Главная таблица -->
	<form id='printtable'>
	<div class="wr_main_table_head"> <!-- Обертка шапки -->
	<table class="main_table">
		<input type="text" id="print_title" name="print_title" placeholder="Введите заголовок таблицы">
		<div id="print_products">
			<input type="checkbox" value="1" checked name="Tables" id="Tables" class="print_products"><label for="Tables">Печатать столы</label>
			<input type="checkbox" value="1" checked name="Chairs" id="Chairs" class="print_products"><label for="Chairs">Печатать стулья</label>
			<input type="checkbox" value="1" checked name="Others" id="Others" class="print_products"><label for="Others">Печатать заготовки и прочее</label>
		</div>
		<thead>
		<tr>
			<th width="45"><input type="checkbox" disabled value="1" checked name="CD" class="print_col" id="CD"><label for="CD">Код</label></th>
			<th width="5%"><input type="checkbox" disabled value="2" name="CN" class="print_col" id="CN"><label for="CN">Заказчик</label></th>
			<th width="5%"><input type="checkbox" disabled value="3" name="SD" class="print_col" id="SD"><label for="SD">Дата<br>приема</label></th>
			<th width="5%"><input type="checkbox" disabled value="4" checked name="ED" class="print_col" id="ED"><label for="ED">Дата<br>сдачи</label></th>
			<th width="5%"><input type="checkbox" disabled value="5" checked name="SH" class="print_col" id="SH"><label for="SH">Салон</label></th>
			<th width="5%"><input type="checkbox" disabled value="6" name="ON" class="print_col" id="ON"><label for="ON">№<br>квитанции</label></th>
			<th width="25%"><input type="checkbox" disabled value="7" checked name="Z" class="print_col" id="Z"><label for="Z">Заказ</label></th>
			<th width="15%"><input type="checkbox" disabled value="8" checked name="M" class="print_col" id="M"><label for="M">Материал</label></th>
			<th width="15%"><input type="checkbox" disabled value="9" checked name="CR" class="print_col" id="CR"><label for="CR">Цвет<br>краски</label></th>
			<th width="100"><input type="checkbox" disabled value="10" name="PR" class="print_col" id="PR"><label for="PR">Этапы</label></th>
			<th width="40"><input type="checkbox" disabled value="11" name="X" class="print_col" id="X"><label for="X">X</label></th>
			<th width="45"><input type="checkbox" disabled value="12" name="IP" class="print_col" id="IP"><label for="IP">Лакировка</label></th>
			<th width="15%"><input type="checkbox" disabled value="13" checked name="N" class="print_col" id="N"><label for="N">Примечание</label></th>
			<th width="80">Действие</th>
		</tr>
		</thead>
	</table>
	</div>
	<div class="wr_main_table_body"> <!-- Обертка тела таблицы -->
	<table class="main_table">
		<thead style="">
		<tr>
			<th width="45"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="5%"></th>
			<th width="25%"></th>
			<th width="15%"></th>
			<th width="15%"></th>
			<th width="100"></th>
			<th width="40"></th>
			<th width="45"></th>
			<th width="15%"></th>
			<th width="80"></th>
		</tr>
		</thead>
		<tbody>
<?
	if( $_SESSION["f_PR"] != "" and $_SESSION["f_ST"] != "" ) {
		if( $_SESSION["f_PR"] === "0" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL, ' ss', '')";
			$SelectStepODB = "IF(ODS.WD_ID IS NULL, ' ss', '')";
		}
		elseif( $_SESSION["f_PR"] === "02" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND IFNULL(PM.PT_ID, 2) = 2 AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "0 PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL AND IFNULL(PM.PT_ID, 2) = 2, ' ss', '')";
			$SelectStepODB = "''";
		}
		elseif( $_SESSION["f_PR"] === "01" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND PM.PT_ID = 1 AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "0 PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL AND PM.PT_ID = 1, ' ss', '')";
			$SelectStepODB = "''";
		}
		elseif( $_SESSION["f_PR"] === "00" ) {
			$PRfilterODD = "0 PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "''";
			$SelectStepODB = "IF(ODS.WD_ID IS NULL, ' ss', '')";
		}
		else {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]}, ' ss', '')";
			$SelectStepODB = "IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]}, ' ss', '')";
		}
	}
	elseif( $_SESSION["f_PR"] != "" and $_SESSION["f_ST"] == "" ) {
		if( $_SESSION["f_PR"] === "0" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL, ' ss', '')";
			$SelectStepODB = "IF(ODS.WD_ID IS NULL, ' ss', '')";
		}
		elseif( $_SESSION["f_PR"] === "02" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND IFNULL(PM.PT_ID, 2) = 2 AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "0 PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL AND IFNULL(PM.PT_ID, 2) = 2, ' ss', '')";
			$SelectStepODB = "''";
		}
		elseif( $_SESSION["f_PR"] === "01" ) {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NULL AND PM.PT_ID = 1 AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "0 PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID IS NULL AND PM.PT_ID = 1, ' ss', '')";
			$SelectStepODB = "''";
		}
		elseif( $_SESSION["f_PR"] === "00" ) {
			$PRfilterODD = "0 PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NULL AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "''";
			$SelectStepODB = "IF(ODS.WD_ID IS NULL, ' ss', '')";
		}
		else {
			$PRfilterODD = "BIT_OR(IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$PRfilterODB = "BIT_OR(IF(ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
			$SelectStepODD = "IF(ODS.WD_ID = {$_SESSION["f_PR"]}, ' ss', '')";
			$SelectStepODB = "IF(ODS.WD_ID = {$_SESSION["f_PR"]}, ' ss', '')";
		}
	}
	elseif( $_SESSION["f_PR"] == "" and $_SESSION["f_ST"] != "" ) {
		$PRfilterODD = "BIT_OR(IF(ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
		$PRfilterODB = "BIT_OR(IF(ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]} AND ODS.Visible = 1 AND ODS.Old = 0, 1, 0)) PRfilter";
		$SelectStepODD = "IF(ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]}, ' ss', '')";
		$SelectStepODB = "IF(ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]}, ' ss', '')";
	}
	else {
		$PRfilterODD = "1 PRfilter";
		$PRfilterODB = "1 PRfilter";
		$SelectStepODD = "''";
		$SelectStepODB = "''";
	}

	$query = "SELECT OD.OD_ID
					,OD.Code
					,IFNULL(OD.ClientName, '') ClientName
					,DATE_FORMAT(OD.StartDate, '%d.%m.%Y') StartDate
					,DATE_FORMAT(IFNULL(OD.ReadyDate, OD.EndDate), '%d.%m.%Y') EndDate
					,DATE_FORMAT(OD.ReadyDate, '%d.%m.%Y') ReadyDate
					,IF(OD.ReadyDate IS NOT NULL, 1, 0) Archive
					,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) AS Shop
					,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
					,OD.OrderNumber
					,OD.Comment
					,COUNT(ODD_ODB.itemID) Child
					,GROUP_CONCAT(ODD_ODB.Zakaz SEPARATOR '') Zakaz
					,OD.Color
					,OD.IsPainting
					,GROUP_CONCAT(ODD_ODB.Material SEPARATOR '') Material
					,GROUP_CONCAT(ODD_ODB.Steps SEPARATOR '') Steps
					,BIT_OR(IFNULL(ODD_ODB.PRfilter, 1)) PRfilter
					,IF(DATEDIFF(OD.EndDate, NOW()) <= 7 AND OD.ReadyDate IS NULL, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline

					,BIT_AND(ODD_ODB.IsReady) IsReady
			  FROM OrdersData OD
			  LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			  LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
			  LEFT JOIN (SELECT ODD.OD_ID
			  				   ,{$PRfilterODD}
							   ,BIT_AND(IF(ODS.Visible = 1 AND ODS.Old = 0, ODS.IsReady, 1)) IsReady
							   ,IFNULL(PM.PT_ID, 2) PT_ID
							   ,ODD.ODD_ID itemID

							   ,CONCAT('<b style=\'line-height: 1.79em;\'><a ".(in_array('order_add', $Rights) ? "href=\'#\'" : "")." id=\'prod', ODD.ODD_ID, '\' location=\'{$location}\' class=\'".(in_array('order_add', $Rights) ? "edit_product', IFNULL(PM.PT_ID, 2), '" : "")."\'', IF(IFNULL(ODD.Comment, '') <> '', CONCAT(' title=\'', ODD.Comment, '\''), ''), '>', IF(IFNULL(ODD.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODD.Amount, ' ', IFNULL(PM.Model, 'Столешница'), ' ', IFNULL(CONCAT(ODD.Length, IF(ODD.Width > 0, CONCAT('х', ODD.Width), ''), IFNULL(CONCAT('/', IFNULL(ODD.PieceAmount, 1), 'x', ODD.PieceSize), '')), ''), ' ', IFNULL(PF.Form, ''), ' ', IFNULL(PME.Mechanism, ''), ' ', '</a></b><br>') Zakaz

							   ,CONCAT(IF(DATEDIFF(ODD.arrival_date, NOW()) <= 0 AND ODD.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODD.arrival_date, NOW()), ' дн.\'>'), ''), '<span id=\'m', ODD.ODD_ID, '\' class=\'',
								CASE ODD.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODD.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
							   '\'>', IFNULL(MT.Material, ''), '</span><br>') Material

							   ,CONCAT('<a ".(in_array('step_update', $Rights) ? "href=\'#\'" : "")." id=\'', ODD.ODD_ID, '\' class=\'".(in_array('step_update', $Rights) ? "edit_steps " : "")."nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\' location=\'{$location}\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, {$SelectStepODD}, ' unvisible'), '\' style=\'width:', ST.Size * 30, 'px;\' title=\'', ST.Step, ' (', IFNULL(WD.Name, 'Не назначен!'), ')\'>', ST.Short, '</div>')) ORDER BY ST.Sort SEPARATOR ''), '</a><br>') Steps

						FROM OrdersDataDetail ODD
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID
						LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
						LEFT JOIN ProductForms PF ON PF.PF_ID = ODD.PF_ID
						LEFT JOIN ProductMechanism PME ON PME.PME_ID = ODD.PME_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						LEFT JOIN StepsTariffs ST ON ST.ST_ID = ODS.ST_ID
						GROUP BY ODD.ODD_ID
						UNION
						SELECT ODB.OD_ID
							  ,{$PRfilterODB}
							  ,BIT_AND(IF(ODS.Visible = 1 AND ODS.Old = 0, ODS.IsReady, 1)) IsReady
							  ,0 PT_ID
							  ,ODB.ODB_ID itemID

							  ,CONCAT('<b style=\'line-height: 1.79em;\'><a ".(in_array('order_add', $Rights) ? "href=\'#\'" : "")." id=\'blank', ODB.ODB_ID, '\'', 'class=\'".(in_array('order_add', $Rights) ? "edit_order_blank" : "")."\' location=\'{$location}\'', IF(IFNULL(ODB.Comment, '') <> '', CONCAT(' title=\'', ODB.Comment, '\''), ''), '>', IF(IFNULL(ODB.Comment, '') <> '', CONCAT('<i class=\'fa fa-comment\' aria-hidden=\'true\'></i>'), ''), ' ', ODB.Amount, ' ', IFNULL(BL.Name, ODB.Other), '</a></b><br>') Zakaz

							  ,CONCAT(IF(DATEDIFF(ODB.arrival_date, NOW()) <= 0 AND ODB.IsExist = 1, CONCAT('<img src=\'/img/attention.png\' class=\'attention\' title=\'', DATEDIFF(ODB.arrival_date, NOW()), ' дн.\'>'), ''), '<span id=\'m', ODB.ODB_ID, '\' class=\'',
								CASE ODB.IsExist
									WHEN 0 THEN 'bg-red'
									WHEN 1 THEN CONCAT('bg-yellow\' title=\'Заказано: ', DATE_FORMAT(ODB.order_date, '%d.%m.%Y'), '&emsp;Ожидается: ', DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y'))
									WHEN 2 THEN 'bg-green'
								END,
							  '\'>', IFNULL(MT.Material, ''), '</span><br>') Material

							  ,CONCAT('<a ".(in_array('step_update', $Rights) ? "href=\'#\'" : "")." odbid=\'', ODB.ODB_ID, '\' class=\'".(in_array('step_update', $Rights) ? "edit_steps " : "")."nowrap shadow', IF(SUM(ODS.Old) > 0, ' attention', ''), '\' location=\'{$location}\'>', GROUP_CONCAT(IF(IFNULL(ODS.Old, 1) = 1, '', CONCAT('<div class=\'step ', IF(ODS.IsReady, 'ready', IF(ODS.WD_ID IS NULL, 'notready', 'inwork')), IF(ODS.Visible = 1, {$SelectStepODB}, ' unvisible'), '\' style=\'width: 30px;\' title=\'(', IFNULL(WD.Name, 'Не назначен!'), ')\'><i class=\"fa fa-cog\" aria-hidden=\"true\" style=\"line-height: 1.45em;\"></i></div>')) SEPARATOR ''), '</a><br>') Steps

			  			FROM OrdersDataBlank ODB
						LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID
						LEFT JOIN BlankList BL ON BL.BL_ID = ODB.BL_ID
						LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
						LEFT JOIN WorkersData WD ON WD.WD_ID = ODS.WD_ID
						GROUP BY ODB.ODB_ID
						ORDER BY PT_ID DESC, itemID
						) ODD_ODB ON ODD_ODB.OD_ID = OD.OD_ID
			  WHERE OD.Del = 0 AND IFNULL(CT.CT_ID, 0) IN ({$USR_cities})";
			  switch ($archive) {
				case 0:
					$query .= " AND OD.ReadyDate IS NULL";
					break;
				case 1:
					$query .= " AND OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}";
					break;
				case 2:
					$query .= " AND ((OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}) OR (OD.ReadyDate IS NULL))";
					break;
			  }
//			  if( $archive ) {
//				  $query .= " AND OD.ReadyDate IS NOT NULL AND DATEDIFF(NOW(), OD.ReadyDate) <= {$datediff}";
//			  }
//			  else {
//				  $query .= " AND OD.ReadyDate IS NULL";
//			  }
			  if( $_SESSION["f_CD"] != "" ) {
				  $query .= " AND OD.Code LIKE '%{$_SESSION["f_CD"]}%'";
			  }
			  if( $_SESSION["f_CN"] != "" ) {
				  $query .= " AND OD.ClientName LIKE '%{$_SESSION["f_CN"]}%'";
			  }
			  if( $_SESSION["f_SD"] != "" ) {
				  $query .= " AND DATE_FORMAT(OD.StartDate, '%d.%m.%Y') LIKE '%{$_SESSION["f_SD"]}%'";
			  }
			  if( $_SESSION["f_ED"] != "" ) {
			  	$query .= " AND DATE_FORMAT(OD.EndDate, '%d.%m.%Y') LIKE '%{$_SESSION["f_ED"]}%'";
			  }
			  if( $_SESSION["f_SH"] != "" ) {
				  $query .= " AND (CONCAT(CT.City, '/', SH.Shop) LIKE '%{$_SESSION["f_SH"]}%'";
				  if( stripos("Свободные", $_SESSION["f_SH"]) !== false ) {
					  $query .= " OR OD.SH_ID IS NULL";
				  }
				  $query .= ")";
			  }
			  if( $_SESSION["f_ON"] != "" ) {
				  $query .= " AND OD.OrderNumber LIKE '%{$_SESSION["f_ON"]}%'";
			  }
			  if( $_SESSION["f_N"] != "" ) {
				  $query .= " AND OD.Comment LIKE '%{$_SESSION["f_N"]}%'";
			  }
			  if( $_SESSION["f_IP"] != "" ) {
				  $query .= " AND OD.IsPainting = {$_SESSION["f_IP"]}";
			  }
			  if( $_SESSION["f_CR"] != "" ) {
				  $query .= " AND OD.Color LIKE '%{$_SESSION["f_CR"]}%'";
			  }
			  $query .= " GROUP BY OD.OD_ID HAVING PRfilter";
			  if( $_SESSION["f_Z"] != "" ) {
				  $query .= " AND Zakaz LIKE '%{$_SESSION["f_Z"]}%'";
			  }
			  if( $_SESSION["f_M"] != "" ) {
				  $query .= " AND Material LIKE '%{$_SESSION["f_M"]}%'";
			  }
			  if( $_SESSION["f_X"] == "1" ) {
				  $X_ord = '0';
				  foreach( $_SESSION as $k => $v)
				  {
					  if( strpos($k,"X_") === 0 )
					  {
						  $X_ord .= ','.str_replace( "X_", "", $k );
					  }
				  }
				  $query .= " AND OD.OD_ID IN ({$X_ord})";
			  }
              $query .= " ORDER BY OD.OD_ID";

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		echo "<tr id='ord{$row["OD_ID"]}'>";
		echo "<td".($row["Archive"] == 1 ? " style='background: #bf8;'" : "")."><span class='nowrap'>{$row["Code"]}</span></td>";
		echo "<td><span><input type='checkbox' value='1' checked name='order{$row["OD_ID"]}' class='print_row' id='n{$row["OD_ID"]}'><label for='n{$row["OD_ID"]}'>></label>{$row["ClientName"]}</span></td>";
		echo "<td><span>{$row["StartDate"]}</span></td>";
		echo "<td><span><span class='{$row["Deadline"]}'>{$row["EndDate"]}</span></span></td>";
		echo "<td><span style='background: {$row["CTColor"]};'>{$row["Shop"]}</span></td>";
		echo "<td><span>{$row["OrderNumber"]}</span></td>";
		echo "<td><span class='nowrap'>{$row["Zakaz"]}</span></td>";
		echo "<td><span class='nowrap material'>{$row["Material"]}</span></td>";
		echo "<td><span>{$row["Color"]}</span></td>";
		echo "<td><span class='nowrap material'>{$row["Steps"]}</span></td>";
		$checkedX = $_SESSION["X_".$row["OD_ID"]] == 1 ? 'checked' : '';
		echo "<td class='X'><input type='checkbox' {$checkedX} value='1'></td>";
		echo "<td val='{$row["IsPainting"]}'";
			switch ($row["IsPainting"]) {
				case 1:
					$class = "notready";
					$title = "Не в работе";
					break;
				case 2:
					$class = "inwork";
					$title = "В работе";
					break;
				case 3:
					$class = "ready";
					$title = "Готово";
					break;
			}
		echo " class='".(in_array('order_add', $Rights) ? "painting " : "")."{$class}' title='{$title}' isready='{$row["IsReady"]}' archive='{$row["Archive"]}'></td>";
		echo "<td><span>{$row["Comment"]}</span></td>";
		echo "<td>";
		if( in_array('order_add', $Rights) ) {
			echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Редактировать'><i class='fa fa-pencil fa-lg'></i></a> ";
		}

		echo "<action>";
		if( $row["Child"] ) // Если заказ не пустой
		{
			if( $row["IsReady"] && $row["IsPainting"] == 3 && $row["Archive"] == 0 )
			{
				if( in_array('order_ready', $Rights) ) {
					echo "<a href='#' class='' onclick='if(confirm(\"Пожалуйста, подтвердите готовность заказа!\", \"?ready={$row["OD_ID"]}\")) return false;' title='Готово'><i style='color:red;' class='fa fa-flag-checkered fa-lg'></i></a>";
				}
			}
		}
		else
		{
			if( in_array('order_add', $Rights) ) {
				echo "<a href='#' class='' onclick='if(confirm(\"Удалить?\", \"?del={$row["OD_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
			}
		}
		echo "</action>";

		echo "</td></tr>";

		// Заполнение массива для JavaScript
		$query = "SELECT ODD.ODD_ID
						,ODD.Amount
						,ODD.Price
						,ODD.PM_ID
						,ODD.PF_ID
						,ODD.PME_ID
						,ODD.Length
						,ODD.Width
						,ODD.PieceAmount
						,ODD.PieceSize
						,ODD.Comment
						,MT.Material
						,ODD.IsExist
                        ,DATE_FORMAT(ODD.order_date, '%d.%m.%Y') order_date
                        ,DATE_FORMAT(ODD.arrival_date, '%d.%m.%Y') arrival_date
						,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
				  FROM OrdersDataDetail ODD
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1
				  LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
				  WHERE ODD.OD_ID = {$row["OD_ID"]}
				  GROUP BY ODD.ODD_ID";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $sub_row = mysqli_fetch_array($result) )
		{
			$ODD[$sub_row["ODD_ID"]] = array( "amount"=>$sub_row["Amount"], "price"=>$sub_row["Price"], "model"=>$sub_row["PM_ID"], "form"=>$sub_row["PF_ID"], "mechanism"=>$sub_row["PME_ID"], "length"=>$sub_row["Length"], "width"=>$sub_row["Width"], "PieceAmount"=>$sub_row["PieceAmount"], "PieceSize"=>$sub_row["PieceSize"], "color"=>$sub_row["Color"], "comment"=>$sub_row["Comment"], "material"=>$sub_row["Material"], "isexist"=>$sub_row["IsExist"], "inprogress"=>$sub_row["inprogress"], "order_date"=>$sub_row["order_date"], "arrival_date"=>$sub_row["arrival_date"] );
		}

		$query = "SELECT ODB.ODB_ID
						,ODB.Amount
						,ODB.Price
						,ODB.BL_ID
						,ODB.Other
						,ODB.Comment
						,MT.Material
						,ODB.IsExist
						,IF(SUM(ODS.WD_ID) IS NULL, 0, 1) inprogress
						,DATE_FORMAT(ODB.order_date, '%d.%m.%Y') order_date
						,DATE_FORMAT(ODB.arrival_date, '%d.%m.%Y') arrival_date
				  FROM OrdersDataBlank ODB
				  LEFT JOIN OrdersDataSteps ODS ON ODS.ODB_ID = ODB.ODB_ID AND ODS.Visible = 1
				  LEFT JOIN Materials MT ON MT.MT_ID = ODB.MT_ID
				  WHERE ODB.OD_ID = {$row["OD_ID"]}
				  GROUP BY ODB.ODB_ID";
		$result = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $sub_row = mysqli_fetch_array($result) )
		{
			$ODB[$sub_row["ODB_ID"]] = array( "amount"=>$sub_row["Amount"], "price"=>$sub_row["Price"], "blank"=>$sub_row["BL_ID"], "other"=>$sub_row["Other"], "comment"=>$sub_row["Comment"], "material"=>$sub_row["Material"], "isexist"=>$sub_row["IsExist"], "inprogress"=>$sub_row["inprogress"], "order_date"=>$sub_row["order_date"], "arrival_date"=>$sub_row["arrival_date"] );
		}
	}
?>
	</tbody>
	</table>
	</div>
	</form>
</div>
</body>
</html>

<script>
	$(document).ready(function(){

		$( ".shopstags" ).autocomplete({ // Автокомплит салонов
			source: "autocomplete.php?do=shopstags"
		});

		$( ".colortags" ).autocomplete({ // Автокомплит цветов
			source: "autocomplete.php?do=colortags"
		});

		$( ".textiletags" ).autocomplete({ // Автокомплит тканей
			source: "autocomplete.php?do=textiletags"
		});

		$( ".plastictags" ).autocomplete({ // Автокомплит пластиков
			source: "autocomplete.php?do=plastictags"
		});

		$( ".textileplastictags" ).autocomplete({ // Автокомплит материалов
			source: "autocomplete.php?do=textileplastictags"
		});

		$( ".clienttags" ).autocomplete({ // Автокомплит заказчиков
			source: "autocomplete.php?do=clienttags"
		});

		new Clipboard('#copy-button'); // Копирование ссылки в буфер

		$('.print_products').button();

		$('select[name="f_PR"]').attr('title', $('select[name="f_PR"] option:selected').html()); // Подсказка выбранного работника в фильтре
		$('select[name="f_ST"]').attr('title', $('select[name="f_ST"] option:selected').html()); // Подсказка статуса этапа в фильтре

		// Фильтрация таблицы при автокомплите
		$( ".main_table .clienttags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .shopstags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .textileplastictags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});
		$( ".main_table .colortags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});

		// Открытие диалога печати
		$("#toprint").printPage();

		$(function() {
			// Кнопка добавления заказа
			$('#add_btn').click( function() {
				$('#order_form').dialog({
					width: 500,
					modal: true,
					show: 'blind',
					hide: 'explode',
				});

				// Автокомплит поверх диалога
				$( ".colortags" ).autocomplete( "option", "appendTo", "#order_form" );

				return false;
			});

			$('.print_col, .print_row, .print_products').change( function() { changelink(); });

			$('#print_btn').click( function() { changelink(); });
			$('#print_title').change( function() { changelink(); });
		});

		$('.painting').click(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			var val = $(this).attr('val');
			var isready = $(this).attr('isready');
			var archive = $(this).attr('archive');
			$.ajax({ url: "ajax.php?do=ispainting&od_id="+id+"&val="+val+"&isready="+isready+"&archive="+archive, dataType: "script", async: false });
		});

		$('.X input[type="checkbox"]').change(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			if ( this.checked ) {
				var val = 1;
			}
			else {
				var val = 0;
			}
			$.ajax({ url: "ajax.php?do=Xlabel&od_id="+id+"&val="+val, dataType: "script", async: false });
		});

		function changelink() { // Добавляем к ссылке печати столбцы и строки которые будем печатать
			var data = $('#printtable').serialize();
			$("#toprint").attr('href', '/toprint/main.php?' + data);
			$("#post-link").val('http://<?=$_SERVER['HTTP_HOST']?>/toprint/main.php?' + data);
			$("#torg12").attr('href', '/torg12.php?' + data);
			$("#schet").attr('href', '/schet.php?' + data);
			$("#labelsbox").attr('href', '/labels_box.php?' + data);
			return false;
		}
		$("#copy-button").click(function() {
			noty({timeout: 3000, text: 'Ссылка на таблицу скопирована в буфер обмена', type: 'success'});
		});

		odd = <?= json_encode($ODD) ?>;
		odb = <?= json_encode($ODB) ?>;
	});
</script>
