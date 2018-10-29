<?
	include "config.php";
	if( isset($_GET["shpid"]) ) {
		$title = 'Отгрузка';
	}
	else {
		$title = 'Престол главная';
	}
	include "header.php";

	$datediff = 60; // Максимальный период отображения данных
	
	$location = $_SERVER['REQUEST_URI'];
	$_SESSION["location"] = $location;

	// Добавление в базу нового заказа
	if( isset($_POST["Shop"]) )
	{
		if( !in_array('order_add', $Rights) ) {
			header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
			die('Недостаточно прав для совершения операции');
		}
		$AddDate = date("Y-m-d");
		$StartDate = $_POST["StartDate"] ? '\''.date( 'Y-m-d', strtotime($_POST["StartDate"]) ).'\'' : "NULL";
		$EndDate = $_POST["Shop"] ? ($_POST["EndDate"] ? '\''.date( "Y-m-d", strtotime($_POST["EndDate"]) ).'\'' : '\''.date( "Y-m-d", strtotime($_SESSION["end_date"]) ).'\'') : "NULL";
		$ClientName = mysqli_real_escape_string( $mysqli, $_POST["ClientName"] );
		$ul = ($_POST["ClientName"] and $_POST["ul"]) ? "1" : "0";
		$chars = array("+", " ", "(", ")"); // Символы, которые трубуется удалить из строки с телефоном
		$mtel = $_POST["mtel"] ? '\''.str_replace($chars, "", $_POST["mtel"]).'\'' : 'NULL';
		$address = mysqli_real_escape_string( $mysqli,$_POST["address"] );
		$Shop = $_POST["Shop"] > 0 ? $_POST["Shop"] : "NULL";
		$OrderNumber = mysqli_real_escape_string( $mysqli, $_POST["OrderNumber"] );
		$Color = mysqli_real_escape_string( $mysqli, $_POST["Color"] );
		$clear = isset($_POST["clear"]) ? $_POST["clear"] : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli, $_POST["Comment"] );
		// Удаляем лишние пробелы
		$ClientName = trim($ClientName);
		$OrderNumber = trim($OrderNumber);
		$Color = trim($Color);
		$Comment = trim($Comment);
		$address = trim($address);

		$confirmed = in_array('order_add_confirm', $Rights) ? 1 : 0;

		// Сохраняем в таблицу цветов полученный цвет и узнаем его ID
		if( $Color != '' ) {
			$query = "INSERT INTO Colors
						SET
							color = '{$Color}',
							clear = {$clear},
							count = 0
						ON DUPLICATE KEY UPDATE
							count = count + 1,
							clear = {$clear}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$cl_id = mysqli_insert_id( $mysqli );
		}
		else {
			$cl_id = "NULL";
		}

		$query = "INSERT INTO OrdersData(CLientName, ul, mtel, address, AddDate, StartDate, EndDate, SH_ID, OrderNumber, CL_ID, Comment, author, confirmed)
				  VALUES ('{$ClientName}', $ul, $mtel, '$address', '{$AddDate}', $StartDate, $EndDate, $Shop, '{$OrderNumber}', $cl_id, '{$Comment}', {$_SESSION['id']}, {$confirmed})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		
		// Перенаправление на экран деталей заказа
		$id = mysqli_insert_id( $mysqli );
		exit ('<meta http-equiv="refresh" content="0; url=/orderdetail.php?id='.$id.'">');
		die;
	}

	// Добавление отгрузки
	if( isset($_POST["CT_ID"]) ) {
		//$shipping_date = date( 'Y-m-d', strtotime($_POST["shipping_date"]) );
		$shp_title = mysqli_real_escape_string( $mysqli, $_POST["shp_title"] );
		if( isset($_GET["shpid"]) ) {
			$query = "UPDATE Shipment SET title='{$shp_title}' WHERE SHP_ID = {$_GET["shpid"]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$query = "UPDATE OrdersData SET SHP_ID = NULL WHERE SHP_ID = {$_GET["shpid"]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$SHP_ID = $_GET["shpid"];
		}
		else {
			$query = "INSERT INTO Shipment SET title='{$shp_title}', CT_ID={$_POST["CT_ID"]}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$SHP_ID = mysqli_insert_id( $mysqli );
		}

		foreach ($_POST["ord_sh"] as $key => $value) {
			$query = "UPDATE OrdersData SET SHP_ID = {$SHP_ID} WHERE OD_ID = {$value}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Перенаправление на экран этой отгрузки
		exit ('<meta http-equiv="refresh" content="0; url=/index.php?shpid='.$SHP_ID.'">');
		die;
	}

	// Сохранение даты отгрузки
	if( isset($_POST["shipping_date"]) ) {
		$shipping_date = $_POST[shipping_date] ? '\''.date( "Y-m-d", strtotime($_POST["shipping_date"]) ).'\'' : "NULL";
		// Записываем дату отгрузки в Shipping
		$query = "UPDATE Shipment SET shipping_date = {$shipping_date} WHERE SHP_ID = {$_GET["shpid"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Помечаем заказы как отгруженные
		$query = "UPDATE OrdersData SET ReadyDate = {$shipping_date}, IsPainting = 3, author = {$_SESSION['id']} WHERE SHP_ID = {$_GET["shpid"]}";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Перенаправление на экран этой отгрузки
		exit ('<meta http-equiv="refresh" content="0; url=/index.php?shpid='.$_GET["shpid"].'">');
		die;
	}

	// Разделение заказа
	if( isset($_POST["prod_amount_left"]) ) {
		$OD_ID = $_POST["OD_ID"];
		$location = $_POST["location"];
		$left_sum = array_sum($_POST["prod_amount_left"]);
		$right_sum = array_sum($_POST["prod_amount_right"]);

		if( $left_sum != 0 and $right_sum != 0 ) {
			// Создание копии заказа
			$query = "INSERT INTO OrdersData(SHP_ID, PFI_ID, Code, SH_ID, ClientName, ul, mtel, address, AddDate, StartDate, EndDate, ReadyDate, OrderNumber, CL_ID, IsPainting, WD_ID, Comment, IsReady, author, confirmed)
			SELECT SHP_ID, PFI_ID, Code, SH_ID, ClientName, ul, mtel, address, AddDate, StartDate, EndDate, ReadyDate, OrderNumber, CL_ID, IsPainting, WD_ID, Comment, IsReady, {$_SESSION['id']}, confirmed FROM OrdersData WHERE OD_ID = {$OD_ID}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$newOD_ID = mysqli_insert_id($mysqli);

			// Записываем в журнал событие разделения заказа
			$query = "INSERT INTO OrdersChangeLog SET table_key = 'OD_ID', table_value = {$OD_ID}, field_name = 'Разделение заказа <a href=\'orderdetail.php?id={$newOD_ID}\' class=\'button\' target=\'_blank\'>другая его часть</a>', old_value = '', new_value = '', author = {$_SESSION['id']}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$query = "INSERT INTO OrdersChangeLog SET table_key = 'OD_ID', table_value = {$newOD_ID}, field_name = 'Разделение заказа <a href=\'orderdetail.php?id={$OD_ID}\' class=\'button\' target=\'_blank\'>другая его часть</a>', old_value = '', new_value = '', author = {$_SESSION['id']}";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

			// Цикл по содержимому заказа (используются данные из формы)
			foreach ($_POST["ODD_ID"] as $key => $value) {
				$left = $_POST["prod_amount_left"][$key];
				$right = $_POST["prod_amount_right"][$key];
				if( $left == 0 ) {
					$query = "UPDATE OrdersDataDetail SET OD_ID = {$newOD_ID} WHERE ODD_ID = {$value}";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
				elseif( $right > 0 ) {
					// Меняем количество изделий в исходном заказе
					$query = "UPDATE OrdersDataDetail SET Amount = {$left}, author = NULL WHERE ODD_ID = {$value}";
						mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					// Вставляем в новый заказ переносимые изделия
					$query = "INSERT INTO OrdersDataDetail(OD_ID, PM_ID, BL_ID, Other, PF_ID, PME_ID, Length, Width, PieceAmount, PieceSize, MT_ID, IsExist, Amount, Comment, order_date, arrival_date, min_price, Price, discount, opt_price, sister_ID, author, ptn)
					SELECT {$newOD_ID}, PM_ID, BL_ID, Other, PF_ID, PME_ID, Length, Width, PieceAmount, PieceSize, MT_ID, IsExist, {$right}, Comment, order_date, arrival_date, min_price, Price, discount, opt_price, {$value}, {$_SESSION['id']}, ptn FROM OrdersDataDetail WHERE ODD_ID = {$value}";
					mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				}
			}
		}

		// Перенаправление на исходный экран
		exit ('<meta http-equiv="refresh" content="0; url='.$location.'#ord'.$OD_ID.'">');
		die;
	}

?>

	<div id="overlay"></div>
	<div id="filter_overlay" style="z-index: 10; position: fixed; width: 100%; height: 100%; top: 0; left: 0; cursor: pointer; display: none;"></div>
	<? include "forms.php"; ?>

	<div style="position: absolute; top: 75px; width: 300px; left: calc(50% - 150px); font-size: 16px; text-align: center;">
		Найдено <b id="counter"></b> результатов.
	</div>

	<?
	if($archive == "2") {
		echo "<div style='position: absolute; top: 57px; width: 1000px; left: calc(50% - 500px); text-align: center; color: red;'>Внимание! В списке отгруженных заказов отображаются первые 500 записей. Чтобы найти интересующие заказы воспользуйтесь фильтром.</div>";
	}
	elseif($archive == "3") {
		echo "<div style='position: absolute; top: 57px; width: 1000px; left: calc(50% - 500px); text-align: center; color: red;'>Внимание! В списке удаленных заказов отображаются первые 500 записей. Чтобы найти интересующие заказы воспользуйтесь фильтром.</div>";
	}
	?>

	<?
	if( isset($_GET["shpid"]) ) {
		$query = "SELECT SHP.title, CT.CT_ID, CT.City, CT.Color, DATE_FORMAT(SHP.shipping_date, '%d.%m.%Y') shipping_date
					FROM Shipment SHP
					JOIN Cities CT ON CT.CT_ID = SHP.CT_ID
					WHERE SHP_ID = {$_GET["shpid"]}";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		if( mysqli_num_rows($res) > 0 ) {
			$shipping_date = mysqli_result($res,0,'shipping_date');
			$CT_ID = mysqli_result($res,0,'CT_ID');
			$shp_title = mysqli_result($res,0,'title');
			$City = mysqli_result($res,0,'City');
			$Color = mysqli_result($res,0,'Color');

			// Проверка прав на город
			if( in_array('shipment_view_city', $Rights) and $CT_ID != $USR_City ) {
				die('Недостаточно прав для совершения операции');
			}

			echo "<h3 style='margin: 10px 0;'>Отгрузка на <span style='background: {$Color};'>{$City}</span>".($shp_title != '' ? ' ('.$shp_title.')' : '')."</h3>";

			if( in_array('add_shipment', $Rights) ) {
				echo "<div id='wr_shipping_date'><form method='post'><label>Отгрузка состоялась: <input type='text' name='shipping_date' value='{$shipping_date}' class='date' autocomplete='off'></label><button style='margin-left: 10px;'>Сoхранить</button>";
				echo "<font style='display: none;' color='red'></font></form></div>";
			}
			else {
				echo "Дата отгрузки: {$shipping_date}<br>";
			}
			echo "<br>";

			// Вычисляем объем и количество коробок
			$query = "SELECT PT.PT_ID
							,ROUND(SUM(IFNULL(PMS.space, 0)), 2) space
							,CEIL(SUM(IFNULL(PMS.space, 0) / PT.box_space)) boxes
						FROM ProductTypes PT
						LEFT JOIN (
							SELECT PM.PT_ID, PM.space * ODD.Amount space
							FROM OrdersData OD
							JOIN Shops SH ON SH.SH_ID = OD.SH_ID
							JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
							JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
							WHERE OD.SHP_ID = {$_GET["shpid"]}
								".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
								".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
						) PMS ON PMS.PT_ID = PT.PT_ID
						GROUP BY PT.PT_ID
						ORDER BY PT.PT_ID";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$chair_space = mysqli_result($res,0,'space');
			$chair_boxes = mysqli_result($res,0,'boxes');
			$table_space = mysqli_result($res,1,'space');
			$table_boxes = mysqli_result($res,1,'boxes');
			echo "
				<div style='position: absolute; right: 200px; top: 60px; border: 1px solid #bbb; padding: 10px; border-radius: 10px;'>
					<b class='nowrap'>Объем стульев: {$chair_space} м<sup>3</sup> (Коробок: {$chair_boxes})</b>
					<br>
					<b class='nowrap'>Объем столов: {$table_space} м<sup>3</sup> (Коробок: {$table_boxes})</b>
				</div>
			";
		}
		else {
			die("<h1>Отгрузка не найдена!</h1>");
		}
	}
	else {
	?>
		<p>
			<form method="get">
				<div class='btnset'>
					<input type='radio' id='archive0' name='archive' value='0' <?= ($archive == "0" ? "checked" : "") ?> onchange="this.form.submit()">
						<label for='archive0'>В работе</label>
					<input type='radio' id='archive1' name='archive' value='1' <?= ($archive == "1" ? "checked" : "") ?> onchange="this.form.submit()">
						<label for='archive1'>Свободные</label>
					<input type='radio' id='archive2' name='archive' value='2' <?= ($archive == "2" ? "checked" : "") ?> onchange="this.form.submit()">
						<label for='archive2'>Отгруженные</label>
					<input type='radio' id='archive3' name='archive' value='3' <?= ($archive == "3" ? "checked" : "") ?> onchange="this.form.submit()">
						<label for='archive3'>Удаленные</label>
				</div>
			</form>
		</p>
	<?
	}

	if( in_array('shipment_view', $Rights) or in_array('shipment_view_city', $Rights) ) {
	?>
	<div id="shipment_list">
		<a class="button" href="#">Отгрузки</a>
		<div>
			<table class="main_table">
				<thead>
					<tr>
						<th width="20%">Город</th>
						<th width="40%">Комментарий</th>
						<th width="20%">Дата отгрузки</th>
<!--						<th width="20%">Дата поступления</th>-->
						<th width="30"></th>
					</tr>
				</thead>
				<tbody>
					<?
					$query = "SELECT SHP.SHP_ID
					,				CT.City
									,CT.Color
									,SHP.title
									,DATE_FORMAT(SHP.shipping_date, '%d.%m.%y') shipping_date_format
									,DATE_FORMAT(SHP.arrival_date, '%d.%m.%y') arrival_date_format
								FROM Shipment SHP
								JOIN Cities CT ON CT.CT_ID = SHP.CT_ID".(in_array('shipment_view_city', $Rights) ? " AND CT.CT_ID = {$USR_City}" : "")."
								WHERE SHP.shipping_date IS NULL OR DATEDIFF(NOW(), SHP.shipping_date) <= {$datediff}
								ORDER BY SHP.SHP_ID DESC";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						echo "<tr>";
						echo "<td><span style='background: {$row["Color"]}'>{$row["City"]}</span></td>";
						echo "<td>{$row["title"]}</td>";
						echo "<td><span>{$row["shipping_date_format"]}</span></td>";
//						echo "<td><span>{$row["arrival_date_format"]}</span></td>";
						echo "<td><a href='/?shpid={$row["SHP_ID"]}'><i class='fa fa-truck fa-lg' aria-hidden='true'></i></a></td>";
						echo "</tr>";
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?
	}
	// Отсчитываем дату сдачи - 30 раб. дней и записываем в сессию
	if( !isset($_SESSION["end_date"]) or $_SESSION["today"] != date('d.m.Y') ) {
		$_SESSION["today"] = date('d.m.Y');
		$end_date = date_create(date('Y-m-d'));
		$working_days = 0;
		$year = 0;
		while ($working_days < 30) {
			date_modify($end_date, '+1 day');
			// Если при подсчете рабочих дней изменился год, то получаем новый календарь
			if( $year != date('Y', strtotime(date_format($end_date, 'd.m.Y'))) ) {
				$year = date('Y', strtotime(date_format($end_date, 'd.m.Y')));
				$xml = simplexml_load_file("http://xmlcalendar.ru/data/ru/".$year."/calendar.xml");
				$json = json_encode($xml);
				$data = json_decode($json,TRUE);
			}
			$day_of_week = date('N', strtotime(date_format($end_date, 'd.m.Y')));
			$month = date('m', strtotime(date_format($end_date, 'd.m.Y')));
			$day = date('d', strtotime(date_format($end_date, 'd.m.Y')));
			// Перебираем массив и если находим дату то проверяем ее тип (тип дня: 1 - выходной день, 2 - рабочий и сокращенный (может быть использован для любого дня недели), 3 - рабочий день (суббота/воскресенье))
			$t = 0;
			foreach( $data["days"]["day"] as $key=>$value ) {
				if( $value["@attributes"]["d"] == $month.".".$day) {
					$t = $value["@attributes"]["t"];
				}
			}
			// Если очередной день - рабочий, то увеличиваем счетчик
			if ( !(($day_of_week >= 6 and $t != "3" and $t != "2") or ($t == "1")) ) {
				++$working_days;
			}
		}
		$_SESSION["end_date"] = date_format($end_date, 'd.m.Y');
	}
	?>

	<!-- Форма добавления заказа -->
	<div id='order_form' class='addproduct' title='Новый заказ' style='display:none;'>
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
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["SH_ID"]}' retail='{$row["retail"]}' style='background: {$row["Color"]};'>{$row["Shop"]}</option>";
						}
						?>
					</select>
				</div>
				<div id="ClientName">
					<label>Заказчик:</label>
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
				<div>
					<p style='color: #911;'>ВНИМАНИЕ! Патина указывается у каждого изделия персонально в специальной графе "патина".</p>
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


<?
	// Кнопка добавления заказа
	if( in_array('order_add', $Rights) and !isset($_GET["shpid"]) ) {
		echo "<div id='add_btn' title='Добавить новый заказ'></div>";
	}

	// Кнопка печати
	if( in_array('order_print', $Rights) ) {
		echo '<div id="print_btn" href="#print_tbl" class="open_modal" title="Распечатать таблицу">';
		echo '<a id="toprint"></a>';
		echo '</div>';
	}

	// Кнопка отгрузки
	if( in_array('add_shipment', $Rights) and (isset($shipping_date) ? $shipping_date : '') == '' ) {
		echo '<div id="add_shipment" title="Сформировать список на отгрузку"></div>';
	}

	// Копирование ссылки на таблицу в буфер
	if( in_array('order_link', $Rights) ) {
		echo '<input id="post-link" style="position: absolute; z-index: -1;">';
		echo '<div id="copy_link" data-clipboard-target="#post-link" style="display: none;">';
		echo '<a id="copy-button" data-clipboard-target="#post-link" style="display: block; height: 100%" title="Скопировать ссылку в буфер обмена"></a>';
		echo '</div>';
	}

	// Кнопка печати счета
	if( in_array('sverki_all', $Rights) or in_array('sverki_city', $Rights) ) {
		echo '<div id="print_forms" title="Сформировать счёт на оплату" style="display: none;">';
		echo '<a id="forms" target="_blank"></a>';
		echo '</div>';
	}

	// Кнопка печати этикеток на упаковку
	if( in_array('print_label_box', $Rights) ) {
		echo '<div id="print_labelsbox" title="Распечатать этикетки на упаковку" style="display: none;">';
		echo '<a id="labelsbox" target="_blank"></a>';
		echo '</div>';
	}

	// Для экрана "В работе персональный фильтр по дате сдачи"
	if( $archive == "0" ) {
		$filter_EndDate = "
			<input type='hidden' name='f_ED' value='{$_SESSION["f_ED"]}'>
			<select name='f_EndDate' style='width: 100%;' class='".(($_SESSION["f_EndDate"] != "") ? "filtered" : "")."' onchange='this.form.submit()'>
			<option></option>
			<option value='0' ".(($_SESSION["f_EndDate"] == "0") ? "selected" : "").">Дата отсутствует</option>
		";
		$query = "
			SELECT YEARWEEK(OD.EndDate, 1) yearweek
				,RIGHT(YEARWEEK(OD.EndDate, 1), 2) week
				,LEFT(YEARWEEK(OD.EndDate, 1), 4) year
				,RIGHT(YEARWEEK(NOW(), 1), 2) week_now
				,LEFT(YEARWEEK(NOW(), 1), 4) year_now
			FROM OrdersData OD
			JOIN Shops SH ON SH.SH_ID = OD.SH_ID
			WHERE OD.ReadyDate IS NULL
				AND OD.DelDate IS NULL
				AND NOT(SH.KA_ID IS NULL AND OD.StartDate IS NULL)
				AND OD.EndDate IS NOT NULL
				#AND (YEARWEEK(OD.EndDate, 1) = YEARWEEK(NOW(), 1) OR OD.EndDate > NOW())
			GROUP BY YEARWEEK(OD.EndDate, 1)
			ORDER BY YEARWEEK(OD.EndDate, 1)
		";
		$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		while( $row = mysqli_fetch_array($res) ) {
			$week_now_style = ($row["week"] == $row["week_now"] and $row["year"] == $row["year_now"]) ? "background: coral;" : "";
			$filter_EndDate .= "<option value='{$row["yearweek"]}' ".(($_SESSION["f_EndDate"] == $row["yearweek"]) ? "selected" : "")." style='$week_now_style'>$eq{$row["week"]} неделя {$row["year"]}$eq</option>";
		}
		$filter_EndDate .=  "</select>";
	}
	else {
		$filter_EndDate = "
			<input type='hidden' name='f_EndDate' value='{$_SESSION["f_EndDate"]}'>
			<input type='text' name='f_ED' size='8' value='{$_SESSION["f_ED"]}' class='".(($_SESSION["f_ED"] != "") ? "filtered" : "")."' autocomplete='off'>
		";
	}
?>

	<!-- ФИЛЬТР ГЛАВНОЙ ТАБЛИЦЫ -->
	<?
	// Если зашли в отгрузку - не показываем фильтр
	if( !isset($_GET["shpid"]) ) {
	?>
	<table class="main_table">
		<form id="main_filter_form" method='get' action='filter.php'>
		<thead>
		<tr>
			<th width="60"><input type='text' name='f_CD' size='8' value='<?= $_SESSION["f_CD"] ?>' class='<?=($_SESSION["f_CD"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="5%"><input type='text' name='f_CN' size='8' value='<?= $_SESSION["f_CN"] ?>' class='clienttags <?=($_SESSION["f_CN"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="60"><input type='text' name='f_SD' size='8' value='<?= $_SESSION["f_SD"] ?>' class='<?=($_SESSION["f_SD"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="60"><?=$filter_EndDate?></th>
			<th width="5%"><input type='text' name='f_SH' size='8' class='shopstags <?=($_SESSION["f_SH"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_SH"] ?>'></th>
<!--			<th width="40"><input type='text' name='f_ON' size='8' value='<?= $_SESSION["f_ON"] ?>' class="<?=($_SESSION["f_ON"] != "") ? "filtered" : ""?>"></th>-->
			<th width="40"></th>
			<th width="25%">
				<select name="f_Models" style="width: 100%;" onchange="this.form.submit()" class="<?=($_SESSION["f_Models"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="0" <?=(($_SESSION["f_Models"] == "0") ? "selected" : "")?>>Столешницы/Заготовки/Прочее</option>
					<optgroup label="Столы">
					<?
						$query = "
							SELECT PM.PM_ID, CONCAT(PM.Model, IF(PM.archive, ' (архив)', '')) Model
							FROM ProductModels PM
							WHERE PM.PT_ID = 2
							ORDER BY PM.Model
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["PM_ID"]}' ";
							if( $_SESSION["f_Models"] == $row["PM_ID"] ) echo "selected";
							echo ">{$row["Model"]}</option>";
						}
					?>
					</optgroup>
					<optgroup label="Стулья">
					<?
						$query = "
							SELECT PM.PM_ID, CONCAT(PM.Model, IF(PM.archive, ' (архив)', '')) Model
							FROM ProductModels PM
							WHERE PM.PT_ID = 1
							ORDER BY PM.Model
						";
						$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $row = mysqli_fetch_array($res) )
						{
							echo "<option value='{$row["PM_ID"]}' ";
							if( $_SESSION["f_Models"] == $row["PM_ID"] ) echo "selected";
							echo ">{$row["Model"]}</option>";
						}
					?>
					</optgroup>
				</select>
<!--				<input type='text' name='f_Z' value='<?= $_SESSION["f_Z"] ?>' class='<?=($_SESSION["f_Z"] != "") ? "filtered" : ""?>' autocomplete='off'>-->
			</th>
			<th width="15%" id="MT_filter" class="select2_filter"><input type="text" disabled style="width: 100%;" class="<?=( $_SESSION["f_M"] != "" ? "filtered" : "" )?>"><div id="material-select" style=""><select name="MT_ID[]" multiple style="width: 100%;"></select></div></th>
			<th width="10%" style="font-size: 0;">
				<style>
					#material-select {
						display: none;
						position: absolute;
						width: 300px;
						height: 300px;
						background: #ddd;
						overflow: auto;
						border: solid 1px #bbb;
						z-index: 11;
					}
				</style>
				<input type='text' name='f_CR' style='width: calc(100% - 40px);' class='colortags <?=($_SESSION["f_CR"] != "") ? "filtered" : ""?>' value='<?= $_SESSION["f_CR"] ?>'>
				<select name="f_IP" style='width: 40px;' class="<?=($_SESSION["f_IP"] != "") ? "filtered" : ""?>" onchange="this.form.submit()">
					<option></option>
					<option value="0" <?= ($_SESSION["f_IP"] == "0") ? 'selected' : '' ?> class="empty">Без покраски</option>
					<option value="1" <?= ($_SESSION["f_IP"] == "1") ? 'selected' : '' ?> class="notready">Не в работе</option>
					<option value="2" <?= ($_SESSION["f_IP"] == "2") ? 'selected' : '' ?> class="inwork">В работе</option>
					<option value="3" <?= ($_SESSION["f_IP"] == "3") ? 'selected' : '' ?> class="ready">Готово</option>
				</select>
			</th>
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
				<select name="f_CF" style="width: 100%;" onchange="this.form.submit()" class="<?=($_SESSION["f_CF"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="0" <?= ($_SESSION["f_CF"] == "0") ? 'selected' : '' ?>>Нет</option>
					<option value="1" <?= ($_SESSION["f_CF"] == "1") ? 'selected' : '' ?>>Да</option>
				</select>
			</th>
			<th width="40">
				<select name="f_X" style="width: 100%;" onchange="this.form.submit()" class="<?=($_SESSION["f_X"] != "") ? "filtered" : ""?>">
					<option></option>
					<option value="1" <?= ($_SESSION["f_X"] == 1) ? 'selected' : '' ?>>X</option>
				</select>
			</th>
			<th width="15%"><input type='text' name='f_N' value='<?= $_SESSION["f_N"] ?>' class='<?=($_SESSION["f_N"] != "") ? "filtered" : ""?>' autocomplete='off'></th>
			<th width="80"><button title="Фильтр"><i class="fa fa-filter fa-lg"></i></button><a href="filter.php?location=<?=$location?>" class="button" title="Сброс фильтра"><i class="fa fa-times fa-lg"></i></a><input type='hidden' name='location' value='<?=$location?>'></th>
		</tr>
		</thead>
		</form>
	</table>
	<?
	}
	else {
	?>
	<!-- Фильтр для отгрузки -->
	<form id='shipping_filter' method='get' style="display: inline-block; position: absolute; top: 113px; left: calc(50% - 300px); width: 600px; text-align: center;">
		<input type="hidden" name="shpid" value="<?=$_GET["shpid"]?>">
		<div class="btnset">
			<?
			$query = "SELECT SH.SH_ID, SH.Shop
						FROM OrdersData OD
						JOIN Shops SH ON SH.SH_ID = OD.SH_ID
						WHERE OD.SHP_ID = {$_GET["shpid"]}
							".($USR_Shop ? "AND (SH.SH_ID = {$USR_Shop} OR (OD.StartDate IS NULL AND SH.retail = 1) OR OD.SH_ID IS NULL)" : "")."
							".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "")."
						GROUP BY OD.SH_ID";
			$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
			$check_shops = 1; // Если при выходе из цикла будет 1, то выбраны все салоны
			while( $row = mysqli_fetch_array($res) ) {
				if( isset($_GET["shop"]) ) {
					$checked = in_array($row["SH_ID"], $_GET["shop"]) ? "checked" : "";
					$check_shops = $check_shops * in_array($row["SH_ID"], $_GET["shop"]) ? 1 : 0;
				}
				else {
					$checked = "checked";
				}
				echo "<input {$checked} type='checkbox' name='shop[]' id='shop_{$row["SH_ID"]}' value='{$row["SH_ID"]}'>";
				echo "<label for='shop_{$row["SH_ID"]}'>{$row["Shop"]}</label>";
			}
			?>
		</div>
		<button style="position: absolute; top: -22px; left: calc(50% - 40px); width: 80px; display: none;">Фильтр</button>
	</form>
	<script>
		$(document).ready(function() {
			$('#shipping_filter .btnset input[type="checkbox"]').change(function() {
				$('#shipping_filter button').show('fast');
			});
		});
	</script>
	<!-- //Фильтр для отгрузки -->
	<?
	}
	?>
	<!-- //ФИЛЬТР ГЛАВНОЙ ТАБЛИЦЫ -->

<div id="print_tbl">

	<!-- Главная таблица -->
	<form id='printtable'>
	<div class="wr_main_table_head"> <!-- Обертка шапки -->
	<table class="main_table">
		<input type="hidden" name="shpid" value="<?=$_GET["shpid"]?>">
		<input type="hidden" name="archive" value="<?=$archive?>">
		<input type="text" id="print_title" name="print_title" placeholder="Введите заголовок таблицы">
		<div id="print_products">
			<input type="checkbox" value="1" checked name="Tables" id="Tables" class="print_products"><label for="Tables">Печатать столы</label>
			<input type="checkbox" value="1" checked name="Chairs" id="Chairs" class="print_products"><label for="Chairs">Печатать стулья</label>
			<input type="checkbox" value="1" checked name="Others" id="Others" class="print_products"><label for="Others">Печатать заготовки и прочее</label>
		</div>
		<thead>
		<tr>
			<th width="60"><input type="checkbox" disabled value="1" checked name="CD" class="print_col" id="CD"><label for="CD">Код<br>Создан</label></th>
			<th width="5%"><input type="checkbox" disabled value="2" name="CN" class="print_col" id="CN"><label for="CN">Заказчик<br>Квитанция</label></th>
			<th width="60"><input type="checkbox" disabled value="3" name="SD" class="print_col" id="SD"><label for="SD">Дата<br>продажи</label></th>
			<th width="60"><input type="checkbox" disabled value="4" checked name="ED" class="print_col" id="ED"><label for="ED">Дата<br><?=($archive == 2 ? "отгрузки" : ($archive == 3 ? "удаления" : "сдачи"))?></label></th>
			<th width="5%"><input type="checkbox" disabled value="5" checked name="SH" class="print_col" id="SH"><label for="SH">Подразделение</label></th>
			<th width="40"><input type="checkbox" disabled value="6" name="ON" class="print_col" id="ON"><label for="ON">Мест</label></th>
			<th width="25%"><input type="checkbox" disabled value="7" checked name="Z" class="print_col" id="Z"><label for="Z">Заказ</label></th>
			<th width="15%"><input type="checkbox" disabled value="8" checked name="M" class="print_col" id="M"><label for="M">Материал <i class="fa fa-question-circle" html="<b>Цветовой статус наличия:</b><br><span class='bg-gray'>Неизвестно</span><br><span class='bg-red'>Нет</span><br><span class='bg-yellow'>Заказано</span><br><span class='bg-green'>В наличии</span><br><span class='bg-red removed'>Выведен</span> - нужно менять"></i></label></th>
			<th width="10%"><input type="checkbox" disabled value="9" checked name="CR" class="print_col" id="CR"><label for="CR">Цвет краски <i class="fa fa-question-circle" html="<b>Цветовой статус лакировки:</b><br><span class='empty'>Покраска не требуется</span><br><span class='notready'>Не дано в покраску</span><br><span class='inwork'>Дано в покраску</span><br><span class='ready'>Покрашено</span>"></i></label></th>
			<th width="100"><input type="checkbox" disabled value="10" name="PR" class="print_col" id="PR"><label for="PR">Этапы <i class="fa fa-question-circle" html="<b>Цветовой статус изготовления:</b><br><span class='notready unvisible'>Выполнение не требуется</span><br><span class='notready'>Не дано в работу</span><br><span class='inwork'>Дано в работу</span><br><span class='ready'>Выполнено</span>"></i></label></th>
			<th width="40"><input type="checkbox" disabled value="11" name="CF" class="print_col" id="CF"><label for="CF">Принят <i class="fa fa-question-circle" html="<b>Статус принятия заказа:</b><br><i class='fa fa-check-circle fa-2x not_confirmed'></i> - Не принят в работу<br><i class='fa fa-check-circle fa-2x confirmed'></i> - Принят в работу (изменение заказа может быть ограничено)"></i></label></th>
			<th width="40"><input type="checkbox" disabled value="12" name="X" class="print_col" id="X"><label for="X">X</label>
			<?
				if( isset($_GET["shpid"]) ) {
					$checked = $_GET["X"] ? 'checked' : '';
					$X = $_GET["X"] ? "" : "1";
					echo "<input {$checked} name='X' value='1' form='shipping_filter' id='ship_X' type='checkbox' onchange='this.form.submit()' style='width: 20px; height: 20px;'>";
				}
			?>
			</th>
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
			<th width="60"></th>
			<th width="5%"></th>
			<th width="60"></th>
			<th width="60"></th>
			<th width="5%"></th>
			<th width="40"></th>
			<th width="25%"></th>
			<th width="15%"></th>
			<th width="10%"></th>
			<th width="100"></th>
			<th width="40"></th>
			<th width="40"></th>
			<th width="15%"></th>
			<th width="80"></th>
		</tr>
		</thead>
		<tbody>
<?
	$MT_IDs = (!isset($_GET["shpid"]) and $_SESSION["f_M"] != "") ? implode(",", $_SESSION["f_M"]) : "";

	$is_orders_ready = 1;	// Собираем готовые заказы чтобы можно ставить дату отгрузки (когда все готовы должна получиться 1)
	$orders_count = 0;		// Счетчик видимых заказов

	// Получаем основные сведения по заказу
	$query = "
		SELECT OD.OD_ID
			,OD.Code
			,DATE_FORMAT(OD.AddDate, '%d.%m.%y') AddDate
			,IFNULL(OD.ClientName, '') ClientName
			,OD.ul
			,OD.mtel
			,OD.address
			,IF((SH.KA_ID IS NULL AND SH.SH_ID IS NOT NULL AND OD.StartDate IS NULL), 'Выставка', DATE_FORMAT(OD.StartDate, '%d.%m.%y')) StartDate
		";
		if ($archive == "0" or isset($_GET["shpid"])) {
			$query .= "
				,DATE_FORMAT(IF((SH.retail AND OD.StartDate IS NULL), '', OD.EndDate), '%d.%m.%y') EndDate
			";
		}
		elseif ($archive == "1") {
			$query .= "
				,'' EndDate
			";
		}
		elseif ($archive == "2") {
			$query .= "
				,DATE_FORMAT(OD.ReadyDate, '%d.%m.%y') EndDate
			";
		}
		elseif ($archive == "3") {
			$query .= "
				,DATE_FORMAT(OD.DelDate, '%d.%m.%y') EndDate
			";
		}

		$query .= "
			,IF(OD.ReadyDate IS NOT NULL, 1, 0) Archive
			,IFNULL(OD.SH_ID, 0) SH_ID
			,IFNULL(SH.KA_ID, 0) KA_ID
			,IF(OD.SH_ID IS NULL, 'Свободные', CONCAT(CT.City, '/', SH.Shop)) Shop
			,IF(OD.SH_ID IS NULL, '#999', CT.Color) CTColor
			,OD.OrderNumber
			,OD.Comment
			,Color(OD.CL_ID) Color
			,IF(OD.CL_ID IS NULL, 0, OD.IsPainting) IsPainting
			,WD.Name
			,IF(DATEDIFF(OD.EndDate, NOW()) <= 7 AND OD.ReadyDate IS NULL AND OD.DelDate IS NULL, IF(DATEDIFF(OD.EndDate, NOW()) <= 0, 'bg-red', 'bg-yellow'), '') Deadline
			,OD_IsReady(OD.OD_ID) IsReady
			,IFNULL(OD.SHP_ID, 0) SHP_ID
			,IF(OS.locking_date IS NOT NULL AND IF(SH.KA_ID IS NULL, 1, 0), 1, 0) is_lock
			,OD.confirmed
			,IF(OD.DelDate IS NULL, 0, 1) Del
			,IF(PFI.rtrn = 1, NULL, OD.PFI_ID) PFI_ID
			,PFI.count
		FROM OrdersData OD
	";
	if (!isset($_GET["shpid"])) {
		if ($_SESSION["f_Models"] != "" or $MT_IDs != "" or $_SESSION["f_PR"] != "" or $_SESSION["f_ST"] != "") {
			$query .= "
				JOIN OrdersDataDetail ODD ON ODD.OD_ID = OD.OD_ID AND ODD.Del = 0
			";
			// Фильтр по модели
			if ($_SESSION["f_Models"] != "") {
				$query .= "
					AND IFNULL(ODD.PM_ID, 0) = {$_SESSION["f_Models"]}
				";
			}
			// Фильтр по материалам
			if ($MT_IDs != "") {
				$query .= "
					AND ODD.MT_ID IN ({$MT_IDs})
				";
			}
			// Фильтр по неназначенным этапам (столы, стулья, прочее)
			if( $_SESSION["f_PR"] === "02" ) {
				$query .= "
					AND ODD.PME_ID IS NOT NULL
				";
			}
			elseif( $_SESSION["f_PR"] === "01" ) {
				$query .= "
					AND ODD.PM_ID IS NOT NULL AND ODD.PME_ID IS NULL AND ODD.BL_ID IS NULL AND ODD.Other IS NULL
				";
			}
			elseif( $_SESSION["f_PR"] === "00" ) {
				$query .= "
					AND (ODD.BL_ID IS NOT NULL OR ODD.Other IS NOT NULL)
				";
			}
			// Фильтр этапов
			if ($_SESSION["f_PR"] != "" or $_SESSION["f_ST"] != "") {
				$query .= "
					JOIN OrdersDataSteps ODS ON ODS.ODD_ID = ODD.ODD_ID AND ODS.Visible = 1 AND ODS.Old = 0
				";
				if ($_SESSION["f_PR"] != "" and $_SESSION["f_ST"] != "") {
					$query .= "
						AND ODS.WD_ID = {$_SESSION["f_PR"]} AND ODS.IsReady = {$_SESSION["f_ST"]}
					";
				}
				elseif ($_SESSION["f_PR"] != "" and $_SESSION["f_ST"] == "") {
					if (strpos($_SESSION["f_PR"], "0") === 0) {
						$query .= "
							AND ODS.WD_ID IS NULL
						";
					}
					else {
						$query .= "
							AND ODS.WD_ID = {$_SESSION["f_PR"]}
						";
					}
				}
				elseif ($_SESSION["f_PR"] == "" and $_SESSION["f_ST"] != "") {
					$query .= "
						AND ODS.WD_ID IS NOT NULL AND ODS.IsReady = {$_SESSION["f_ST"]}
					";
				}
			}
		}
	}

	$query .= "
		LEFT JOIN Shops SH ON SH.SH_ID = OD.SH_ID
		LEFT JOIN Cities CT ON CT.CT_ID = SH.CT_ID
		LEFT JOIN WorkersData WD ON WD.WD_ID = OD.WD_ID
		LEFT JOIN OstatkiShops OS ON OS.year = YEAR(OD.StartDate) AND OS.month = MONTH(OD.StartDate) AND OS.CT_ID = SH.CT_ID
		LEFT JOIN PrintFormsInvoice PFI ON PFI.PFI_ID = OD.PFI_ID
		WHERE IFNULL(SH.CT_ID, 0) IN ({$USR_cities})
		".($USR_Shop ? "AND (SH.SH_ID = {$USR_Shop} OR (OD.StartDate IS NULL AND SH.retail = 1) OR OD.SH_ID IS NULL)" : "")."
		".($USR_KA ? "AND (SH.KA_ID = {$USR_KA} OR (OD.StartDate IS NULL AND SH.stock = 1) OR OD.SH_ID IS NULL)" : "")."
	";

	// Фильтр по галочке
	if( ($_SESSION["f_X"] == "1" and !isset($_GET["shpid"])) or $_GET["X"] == "1" ) {
		$X_ord = '0';
		foreach( $_SESSION as $k => $v)
		{
			if( strpos($k,"X_") === 0 )
			{
				$X_ord .= ','.str_replace( "X_", "", $k );
			}
		}
		$query .= "
			AND OD.OD_ID IN ({$X_ord})
		";
	}

	if (!isset($_GET["shpid"])) { // Если не в отгрузке
		switch ($archive) {
			case 0:
				$query .= "AND OD.DelDate IS NULL AND OD.ReadyDate IS NULL AND OD.SH_ID IS NOT NULL";
				break;
			case 1:
				$query .= "AND OD.DelDate IS NULL AND OD.ReadyDate IS NULL AND OD.SH_ID IS NULL";
				break;
			case 2:
				$query .= "AND OD.DelDate IS NULL AND OD.ReadyDate IS NOT NULL";
				$limit = " LIMIT 500";
				break;
			case 3:
				$query .= "AND OD.DelDate IS NOT NULL";
				// Удаленные свободные показываем только администрации
				if (!in_array('order_add_confirm', $Rights)) {
					$query .= " AND OD.SH_ID IS NOT NULL";
				}
				$limit = " LIMIT 500";
				break;
		}
		if( $_SESSION["f_CD"] != "" ) {
			$query .= " AND (OD.Code LIKE '%{$_SESSION["f_CD"]}%' OR DATE_FORMAT(OD.AddDate, '%d.%m.%y') LIKE '%{$_SESSION["f_CD"]}%')";
		}
		if( $_SESSION["f_CN"] != "" ) {
			$query .= " AND (OD.ClientName LIKE '%{$_SESSION["f_CN"]}%' OR OD.OrderNumber LIKE '%{$_SESSION["f_CN"]}%' OR OD.mtel LIKE '%{$_SESSION["f_CN"]}%' OR OD.address LIKE '%{$_SESSION["f_CN"]}%')";
		}
		if ($_SESSION["f_ED"] != "") {
			if ($archive == "2") {
				$query .= "
					AND OD.ReadyDate LIKE '%{$_SESSION["f_ED"]}%'
				";
			}
			elseif ($archive == "3") {
				$query .= "
					AND OD.DelDate LIKE '%{$_SESSION["f_ED"]}%'
				";
			}
		}
		if( $_SESSION["f_EndDate"] != "" and $archive == "0") {
			if( $_SESSION["f_EndDate"] == "0" ) {
				$query .= " AND IF((SH.KA_ID IS NULL AND OD.StartDate IS NULL), NULL, OD.EndDate) IS NULL";
			}
			else {
				$query .= " AND YEARWEEK(IF((SH.KA_ID IS NULL AND OD.StartDate IS NULL), NULL, OD.EndDate), 1) = '{$_SESSION["f_EndDate"]}'";
			}
		}
		if( $_SESSION["f_N"] != "" ) {
			$query .= " AND OD.Comment LIKE '%{$_SESSION["f_N"]}%'";
		}
		if( $_SESSION["f_IP"] != "" ) {
			$query .= " AND IF(OD.CL_ID IS NULL, 0, OD.IsPainting) = {$_SESSION["f_IP"]}";
		}
		if( $_SESSION["f_CR"] != "" ) {
			$query .= " AND (Color(OD.CL_ID) LIKE '%{$_SESSION["f_CR"]}%' OR WD.Name LIKE '%{$_SESSION["f_CR"]}%')";
		}
		if( $_SESSION["f_CF"] != "" ) {
			$query .= " AND OD.confirmed = {$_SESSION["f_CF"]}";
		}
		$query .= "
			GROUP BY OD.OD_ID
			HAVING 1
		";
		if ($_SESSION["f_SD"] != "" and $archive != "1") {
			$query .= "
				AND StartDate LIKE '%{$_SESSION["f_SD"]}%'
			";
		}
		if ($_SESSION["f_SH"] != "") {
			$query .= "
				AND Shop LIKE '%{$_SESSION["f_SH"]}%'
			";
		}
	}
	else {  // Если в отгрузке - показываем список этой отгрузки
		$query .= "
			AND OD.SHP_ID = {$_GET["shpid"]}
		";
		if( isset($_GET["shop"]) ) {
			$shops = "0";
			foreach( $_GET["shop"] as $k => $v) {
				$shops .= ",".$v;
			}
			$query .= "
				AND OD.SH_ID IN({$shops})
			";
		}
	}

	if($archive == "2") {
		$query .= "
			ORDER BY OD.ReadyDate DESC
		";
	}
	elseif($archive == "3") {
		$query .= "
			ORDER BY OD.DelDate DESC
		";
	}
	else {
		$query .= "
			ORDER BY OD.AddDate
		";
	}
	$query .= "
		,SUBSTRING_INDEX(OD.Code, '-', 1) ASC, CONVERT(SUBSTRING_INDEX(OD.Code, '-', -1), UNSIGNED) ASC, OD.OD_ID
	";
	$query .= $limit;

	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) )
	{
		$is_lock = $row["is_lock"];			// Месяц закрыт в реализации
		if( !in_array('order_add_confirm', $Rights) and !$row["SH_ID"] ) {
			$is_lock = 1;
		}
		$confirmed = $row["confirmed"];		// Заказ принят в работу
		// Запрет на редактирование
		$disabled = !( in_array('order_add', $Rights) and ($confirmed == 0 or in_array('order_add_confirm', $Rights)) and $is_lock == 0 and $row["Archive"] == 0 and $row["Del"] == 0 );

		// Если пользователю доступен только один салон в регионе или оптовик или свободный заказ и нет админских привилегий, то нельзя редактировать общую информацию заказа.
		$editable = (!($USR_Shop and $row["SH_ID"] and $USR_Shop != $row["SH_ID"]) and !($USR_KA and $row["SH_ID"] and $USR_KA != $row["KA_ID"]) and !($row["SH_ID"] == 0 and !in_array('order_add_confirm', $Rights)));

		// Получаем содержимое заказа
		$query = "
			SELECT ODD.ODD_ID
				,ODD.Amount
				,Zakaz(ODD.ODD_ID) zakaz
				,".((!isset($_GET["shpid"]) and $_SESSION["f_Models"] != "") ? "IF(IFNULL(ODD.PM_ID, 0) = {$_SESSION["f_Models"]}, 'ss', '')" : "''")." PMfilter
				,ODD.Comment
				,DATEDIFF(ODD.arrival_date, NOW()) outdate
				,ODD.IsExist
				,DATE_FORMAT(ODD.arrival_date, '%d.%m.%y') arrival_date
				,IFNULL(MT.Material, '') Material
				,".( $MT_IDs != "" ? "IF(ODD.MT_ID IN ({$MT_IDs}), 'ss', '')" : "''" )." MTfilter
				,ODD.MT_ID
				,MT.SH_ID
				,SH.mtype
				,IF(MT.removed=1, 'removed', '') removed
				,IF(ODD.BL_ID IS NULL AND ODD.Other IS NULL, IFNULL(PM.PT_ID, 2), 0) PTID
				,Steps_button(ODD.ODD_ID, ".((!isset($_GET["shpid"]) and ($_SESSION["f_PR"] != "" or $_SESSION["f_ST"] != "")) ? "1" : "0").") Steps
			FROM OrdersDataDetail ODD
			LEFT JOIN ProductModels PM ON PM.PM_ID = ODD.PM_ID
			LEFT JOIN Materials MT ON MT.MT_ID = ODD.MT_ID
			LEFT JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
			WHERE ODD.Del = 0 AND ODD.OD_ID = {$row["OD_ID"]}
			ORDER BY PTID DESC, ODD.ODD_ID
		";
		$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

		// Формируем подробности заказа
		$zakaz = '';
		$item = '';
		$material = '';
		$color = '';
		$steps = '';
		while( $subrow = mysqli_fetch_array($subres) ) {
			// Если есть примечание
			if ($subrow["Comment"]) {
				$zakaz .= "<b class='material'><a id='prod{$subrow["ODD_ID"]}' location='{$location}' class='{$subrow["PMfilter"]} ".((!$disabled and $row["PFI_ID"] == "" and in_array('order_add', $Rights)) ? "edit_product{$subrow["PTID"]}" : "not_edit_product")."' title='{$subrow["Comment"]}'><i class='fa fa-comment'></i> <b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
			}
			else {
				$zakaz .= "<b class='material'><a id='prod{$subrow["ODD_ID"]}' location='{$location}' class='{$subrow["PMfilter"]} ".((!$disabled and $row["PFI_ID"] == "" and in_array('order_add', $Rights)) ? "edit_product{$subrow["PTID"]}" : "not_edit_product")."'><b style='font-size: 1.3em;'>{$subrow["Amount"]}</b> {$subrow["zakaz"]}</a></b><br>";
			}

			if ($subrow["IsExist"] == 0) {
				$color = "bg-red";
			}
			elseif ($subrow["IsExist"] == 1) {
				$color = "bg-yellow' title='Ожидается: {$subrow["arrival_date"]}";
			}
			elseif ($subrow["IsExist"] == 2) {
				$color = "bg-green";
			}
			else {
				$color = "bg-gray";
			}
			$material .= "<span class='wr_mt'>".(($subrow["outdate"] <= 0 and $subrow["IsExist"] == 1) ? "<i class='fas fa-exclamation-triangle' style='color: #E74C3C;' title='{$subrow["outdate"]} дн.'></i>" : "")."<span shid='{$subrow["SH_ID"]}' mtid='{$subrow["MT_ID"]}' id='m{$subrow["ODD_ID"]}' class='mt{$subrow["MT_ID"]} {$subrow["removed"]} {$subrow["MTfilter"]} material ".(in_array('screen_materials', $Rights) ? "mt_edit" : "")." {$color}'>{$subrow["Material"]}</span><input type='text' class='materialtags_{$subrow["mtype"]}' style='display: none;'><input type='checkbox' style='display: none;' title='Выведен'></span><br>";

			$steps .= "<a id='{$subrow["ODD_ID"]}' class='".(in_array('step_update', $Rights) ? "edit_steps " : "")."' location='{$location}'>{$subrow["Steps"]}</a><br>";
		}

		echo "<tr id='ord{$row["OD_ID"]}'>";
		echo "<td".($row["Archive"] == 1 ? " style='background: #bf8;'" : "")."><span class='nowrap'><b class='code'>{$row["Code"]}</b><br>{$row["AddDate"]}</span></td>";
		echo "<td><span ".($row["address"] ? "title='{$row["address"]}'" : "")."><input type='checkbox' value='{$row["OD_ID"]}' checked name='order[]' class='print_row' id='n{$row["OD_ID"]}'><label for='n{$row["OD_ID"]}'>></label><n".($row["ul"] ? " class='ul' title='юр. лицо'" : "").">{$row["ClientName"]}</n><br><b>{$row["OrderNumber"]}</b><br>{$row["mtel"]}</span></td>";

		// Если заказ в накладной - на дате продажи ссылка на накладную
		if( $row["PFI_ID"] ) {
			$invoice = "<br><b><a href='open_print_form.php?type=invoice&PFI_ID={$row["PFI_ID"]}&number={$row["count"]}' target='_blank'>Накладная</a></b>";
		}
		else {
			$invoice = "";
		}

		echo "<td><span>{$row["StartDate"]}{$invoice}</span></td>";
		echo "<td><span><span class='{$row["Deadline"]}'>{$row["EndDate"]}</span></span></td>";
		echo "<td class='".( (!$is_lock and in_array('order_add', $Rights) and !$row["Del"] and !($USR_Shop and $row["SH_ID"] and $USR_Shop != $row["SH_ID"]) and !($USR_KA and $row["SH_ID"] and $USR_KA != $row["KA_ID"])) ? "shop_cell" : "" )."' id='{$row["OD_ID"]}' SH_ID='{$row["SH_ID"]}'><span style='background: {$row["CTColor"]};'>{$row["Shop"]}</span><select class='select_shops' style='display: none; width: 100%;'></select></td>";
		echo "<td><span></span></td>";

		echo "<td><span class='nowrap'>{$zakaz}</span></td>";

		echo "<td class='nowrap'>{$material}</td>";
		echo "<td val='{$row["IsPainting"]}'";
			switch ($row["IsPainting"]) {
				case 0:
					$class = "empty";
					break;
				case 1:
					$class = "notready";
					break;
				case 2:
					$class = "inwork";
					break;
				case 3:
					$class = "ready";
					break;
			}
		echo " class='painting_cell ".(( in_array('order_add_confirm', $Rights) and $row["Archive"] == 0 and $row["Del"] == 0 and $row["IsPainting"] != 0 ) ? "painting " : "")."{$class}' isready='{$row["IsReady"]}' archive='{$row["Archive"]}' shpid='{$_GET["shpid"]}' filter='".(($_GET['shop'] != '' or $_GET['X'] != '') ? 1 : 0)."'><div class='painting_workers'>{$row["Name"]}</div>{$row["Color"]}</td>";
		echo "<td class='td_step ".($row["confirmed"] == 1 ? "step_confirmed" : "")." ".(!in_array('step_update', $Rights) ? "step_disabled" : "")."'><span class='nowrap material'>{$steps}</span></td>";
		$checkedX = $_SESSION["X_".$row["OD_ID"]] == 1 ? 'checked' : '';
		// Если заказ принят
		if( $row["confirmed"] == 1 ) {
			$class = 'confirmed';
		}
		else {
			$class = 'not_confirmed';
		}
		if( in_array('order_add_confirm', $Rights) and $row["Archive"] == 0 and $row["Del"] == 0 ) {
			$class = $class." edit_confirmed";
		}
		echo "<td val='{$row["confirmed"]}' class='{$class}' style='text-align: center;'><i class='fa fa-check-circle fa-2x' aria-hidden='true'></i></td>";
		echo "<td class='X' style='text-align: center;'><input type='checkbox' {$checkedX} value='1'></td>";
		echo "<td class='".( (in_array('order_add', $Rights) and $row["Del"] == 0 and $editable) ? "comment_cell" : "" )."' id='{$row["OD_ID"]}'><span>{$row["Comment"]}</span><textarea style='display: none; width: 100%; resize: vertical;' rows='5'>{$row["Comment"]}</textarea></td>";
		echo "<td>";

		if( $editable ) {
			// Если заказ не заблокирован и не удален, то показываем карандаш и кнопку разделения. Иначе - глаз.
			if( !$is_lock and in_array('order_add', $Rights) and !$row["Del"] ) {
				echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Редактировать'><i class='fa fa-pencil-alt fa-lg'></i></a> ";
				echo "<a href='#' id='{$row["OD_ID"]}' class='order_cut' title='Разделить заказ' location='{$location}'><i class='fa fa-sliders-h fa-lg'></i></a> ";
			}
			else {
				echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Посмотреть'><i class='fa fa-eye fa-lg'></i></a> ";
			}

			echo "<action>";
			if( $row["SHP_ID"] == 0 ) {
				if( $row["Archive"] == 0 and $row["Del"] == 0 ) {
					if( $row["IsReady"] and ($row["IsPainting"] == "3" or $row["IsPainting"] == "0") ) {
						if( in_array('order_ready', $Rights) ) {
							//echo "<a href='#' class='' ".(($row["SH_ID"] == 0) ? "style='display: none;'" : "")." onclick='if(confirm(\"Пожалуйста, подтвердите готовность заказа.\", \"?ready={$row["OD_ID"]}\")) return false;' title='Отгрузить'><i style='color:red;' class='fa fa-flag-checkered fa-lg'></i></a>";
							echo "<a href='#' class='shipping' ".(($row["SH_ID"] == 0) ? "style='display: none;'" : "")." onclick='confirm(\"Пожалуйста, подтвердите <b>отгрузку</b> заказа.\").then(function(status){if(status) $.ajax({ url: \"ajax.php?do=order_shp&od_id={$row["OD_ID"]}\", dataType: \"script\", async: false });});' title='Отгрузить'><i style='color:red;' class='fa fa-flag-checkered fa-lg'></i></a>";
						}
					}
					if( !$disabled ) {
						//echo "<a href='#' class='' onclick='if(confirm(\"<b>Подтвердите удаление заказа!</b>\", \"?del={$row["OD_ID"]}\")) return false;' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
						if( in_array('order_add_confirm', $Rights) ) {
							$message = "<b>Внимание!</b><br>Заказ отмеченный как покрашенный при удалении будет считаться <b>списанным</b> - это означает, что задействованные заготовки, тоже останутся <b>списанными</b>.<br>В остальных случаях заказ будет считаться <b>отмененным</b> и заготовки <b>вернутся</b> на склад.<br>К тому же этапы производства, отмеченные как <b>выполненные</b>, после удаления останутся таковыми <b>с сохранением денежного начисления работнику</b>.";
						}
						else {
							$message = "Пожалуйста, подтвердите <b>удаление</b> заказа.";
						}
						if( !$row["PFI_ID"] ) {
							echo "<a href='#' class='deleting' onclick='confirm(\"{$message}\").then(function(status){if(status) $.ajax({ url: \"ajax.php?do=order_del&od_id={$row["OD_ID"]}\", dataType: \"script\", async: false });});' title='Удалить'><i class='fa fa-times fa-lg'></i></a>";
						}
					}
				}
			}
			else {
				echo "<a href='/?shpid={$row["SHP_ID"]}#ord{$row["OD_ID"]}' title='К списку отгрузки'><i class='fa fa-truck fa-lg' aria-hidden='true'></i></a>";
			}
			echo "</action>";
		}
		// Иначе показываем глаз
		else {
			echo "<a href='./orderdetail.php?id={$row["OD_ID"]}' class='' title='Посмотреть'><i class='fa fa-eye fa-lg'></i></a> ";
		}
		echo "</td></tr>";

		if( !$row["IsReady"] || $row["IsPainting"] == "1" || $row["IsPainting"] == "2" ) {
			$is_orders_ready = 0;
		}
		$orders_count++;
	}
?>
	</tbody>
	</table>
	</div>
	</form>
</div>

<script>
	$(function() {
		<?
		// Выделяем рамкой отфильтрованные этапы
		if (!isset($_GET["shpid"]) and ($_SESSION["f_PR"] != "" or $_SESSION["f_ST"] != "")) {
			if ($_SESSION["f_PR"] != "" and $_SESSION["f_ST"] != "") {
				echo "$('.step.w{$_SESSION["f_PR"]}.st{$_SESSION["f_ST"]}:not(.unvisible)').addClass('ss');";
			}
			elseif ($_SESSION["f_PR"] != "" and $_SESSION["f_ST"] == "") {
				if( $_SESSION["f_PR"] === "0" ) {
					echo "$('.step.w0:not(.unvisible)').addClass('ss');";
				}
				else {
					echo "$('.step.w{$_SESSION["f_PR"]}:not(.unvisible)').addClass('ss');";
				}
			}
			elseif ($_SESSION["f_PR"] == "" and $_SESSION["f_ST"] != "") {
				echo "$('.step.st{$_SESSION["f_ST"]}:not(.unvisible)').addClass('ss');";
			}
		}
		?>
	});
</script>
<?
	// Генерируем Select2 для фильтра материалов
	$MT_filter = '';
	$MT_string = '';
	$query = "
		SELECT MT.MT_ID, CONCAT(MT.Material, ' (', SH.Shipper, ')') Material
		FROM Materials MT
		JOIN Shippers SH ON SH.SH_ID = MT.SH_ID
		ORDER BY MT.Material
	";
	$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	while( $row = mysqli_fetch_array($res) ) {
		$selected = in_array($row["MT_ID"], $_SESSION["f_M"]) ? "selected" : "";
		$MT_filter .= "<option {$selected} value='{$row["MT_ID"]}'>{$row["Material"]}</option>";
		$MT_string .= ($selected) ? $row["Material"].", " : "";
	}
	$MT_filter = addslashes($MT_filter);
	$MT_string = addslashes($MT_string);

?>

<!-- Форма добавления наименования отгрузки -->
<div id='add_shipment_form' title='Параметры отгрузки' style='display:none'>
	<form method='post'>
		<fieldset style="text-align: center;">
			<div>
				<label>Город:</label>
				<select name="CT_ID" required>
					<?=$_GET["shpid"] ? '' : '<option value="">-=Выберите город=-</option>'?>
					<?
					// Ограничиваем вывод списка салонов в дропдауне
					$query = "SET @@group_concat_max_len = 50;";
					mysqli_query( $mysqli, $query );

					$query = "SELECT CT.CT_ID, CONCAT(CT.City, ' (', GROUP_CONCAT(SH.Shop), IF(LENGTH(GROUP_CONCAT(SH.Shop)) > 48, '...', ''), ')') City, CT.Color
								FROM Cities CT
								JOIN Shops SH ON SH.CT_ID = CT.CT_ID
								".(isset($_GET["shpid"]) ? ' WHERE CT.CT_ID = '.$CT_ID : '')."
								".($USR_Shop ? "AND SH.SH_ID = {$USR_Shop}" : "")."
								".($USR_KA ? "AND SH.KA_ID = {$USR_KA}" : "")."
								GROUP BY CT.CT_ID
								ORDER BY CT.City";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) )
					{
						echo "<option value='{$row["CT_ID"]}' style='background: {$row["Color"]};'>{$row["City"]}</option>";
					}
					?>
				</select>
			</div>
			<br>
			<div class="accordion">
				<h3>Список заказов на отгрузку</h3>
				<div id="orders_to_shipment" style='text-align: left;'></div>
			</div>
			<br>
			<div>
				<label>Комментарий к отгрузке:</label>
				<input type='text' style='width: 350px;' name='shp_title' autocomplete="off">
			</div>
			<div>
				<hr>
				<input type='submit' value='Сохранить' style='float: right;'>
			</div>
		</fieldset>
	</form>
</div>

<script>
	// Функция проверяет готов ли список заказов к отгрузке
	function check_shipping(ready, count, filter) {
		if( filter ) {
			$('#wr_shipping_date input[name="shipping_date"]').prop('disabled', true);
			$('#wr_shipping_date button').hide('fast');
			$('#wr_shipping_date font').hide('fast');
			$('#wr_shipping_date font').html();
		}
		else {
			if(!ready || !count) {
				$('#wr_shipping_date input[name="shipping_date"]').prop('disabled', true);
				$('#wr_shipping_date button').hide('fast');
				$('#wr_shipping_date font').show('fast');
				if( !count ) {
					$('#wr_shipping_date font').html('&nbsp;&nbsp;Список пуст!');
				}
				else {
					$('#wr_shipping_date font').html('&nbsp;&nbsp;Есть неготовые или пустые заказы!');
				}
			}
			else {
				$('#wr_shipping_date input[name="shipping_date"]').prop('disabled', false);
				$('#wr_shipping_date button').show('fast');
				$('#wr_shipping_date font').hide('fast');
				$('#wr_shipping_date font').html();
			}
		}
	}

	// Добавляем к ссылке печати столбцы и строки которые будем печатать
	function changelink() {
		var data = $('#printtable').serialize();
		$("#toprint").attr('href', '/toprint/main.php?' + data);
		$("#post-link").val('http://<?=$_SERVER['HTTP_HOST']?>/toprint/main.php?' + data);
		$("#print_forms > a").attr('href', '/bills.php?' + data);
		$("#labelsbox").attr('href', '/labels_box.php?' + data);
		return false;
	}

	// Выбрать все в форме отгрузки
	function selectall(ch) {
		$('#orders_to_shipment .chbox.show').prop('checked', ch).change();
		$('#orders_to_shipment #selectalltop').prop('checked', ch);
		$('#orders_to_shipment #selectallbottom').prop('checked', ch);
		return false;
	}

	$(function(){

		// Если выбран розничный салон - показываем доп поля в форме добавления заказа
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

		$('#counter').html('<?=$orders_count?>');

		// Select2 для выбора салона
		$('select[name="Shop"]').select2({
			placeholder: "Выберите подразделение",
			language: "ru"
		});
		// Костыль для Select2 чтобы работал поиск
//		$.ui.dialog.prototype._allowInteraction = function (e) {
//			return true;
//		};

		// Фильтр по материалам (инициализация)
		$('#MT_filter select').html('<?=$MT_filter?>');
		$('#MT_filter input').val('<?=$MT_string?>');
		$('#MT_filter select').select2({
			placeholder: "Материалы",
			allowClear: true,
			closeOnSelect: false,
			language: "ru"
		});

		$('#MT_filter').click(function() {
			$('#material-select').show('fast');
			$('#filter_overlay').show();
		});

		$('#filter_overlay').click(function() {
			$(this).hide();
			$('#material-select').hide('fast');
			$('#main_filter_form').submit();
		});

		// Проверяем можно ли отгружать
		check_shipping(<?=$is_orders_ready?>, <?=$orders_count?> ,<?=(($_GET["shop"] != "" and $check_shops == 0) or $_GET["X"] != "") ? 1 : 0?>);

		new Clipboard('#copy-button'); // Копирование ссылки в буфер

		$('.print_products').button();
		$('.print_col, .print_row, .print_products').change( function() { changelink(); });

		$('#print_btn').click( function() { changelink(); });
		$('#print_title').change( function() { changelink(); });

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
		$( ".main_table .colortags" ).on( "autocompleteselect", function( event, ui ) {
			$(this).val(ui.item.value);
			$(event.target.form).submit();
		});

		// Открытие диалога печати
		$("#toprint").printPage();

		// Ограничение дат продажи и сдачи
		$( '#order_form fieldset input[name="StartDate"]' ).datepicker( "option", "maxDate", "<?=( date('d.m.Y') )?>" );
		$( '#order_form fieldset input[name="EndDate"]' ).datepicker( "option", "minDate", "<?=( date('d.m.Y') )?>" );

		// Кнопка добавления заказа
		$('#add_btn').click( function() {
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

			// Деактивация кнопок типа покраски
			clearonoff('#paint_color');

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

		// Обработчики чекбоксов в форме отгрузки
		$('#orders_to_shipment').on('change', '#selectalltop', function(){
			ch = $('#selectalltop').prop('checked');
			selectall(ch);
			return false;
		});
		$('#orders_to_shipment').on('change', '#selectallbottom', function(){
			ch = $('#selectallbottom').prop('checked');
			selectall(ch);
			return false;
		});
		$('#orders_to_shipment').on('change', '.chbox', function(){
			var checked_status = true;
			$('.chbox.show').each(function(){
				if( !$(this).prop('checked') )
				{
					checked_status = $(this).prop('checked');
				}
			});
			$('#selectalltop').prop('checked', checked_status);
			$('#selectallbottom').prop('checked', checked_status);
			return false;
		});
		// Конец обработчиков чекбоксов

		// В форме отгрузки фильтр по салонам
		$('#orders_to_shipment').on('change', '.button_shops', function(){
			var id = $(this).attr('id');
			if( $(this).prop('checked') ) {
				$('#to_shipment .'+id).show('fast');
				$('#to_shipment .'+id+' input[type=checkbox]').removeClass('hide');
				$('#to_shipment .'+id+' input[type=checkbox]').addClass('show');
				$('#to_shipment .'+id+' input[type=checkbox]').change();
			}
			else {
				$('#to_shipment .'+id+' input[type=checkbox]').prop('checked', false);
				$('#to_shipment .'+id).hide('fast');
				$('#to_shipment .'+id+' input[type=checkbox]').removeClass('show');
				$('#to_shipment .'+id+' input[type=checkbox]').addClass('hide');
				$('#to_shipment .'+id+' input[type=checkbox]').change();
			}
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

		$("#copy-button").click(function() {
			noty({timeout: 3000, text: 'Ссылка на таблицу скопирована в буфер обмена', type: 'success'});
		});

		// Форма составления отгрузки
		$('#add_shipment').click(function() {
			$('select[name="CT_ID"]').val('');
			$('#orders_to_shipment').html('');
			$('#add_shipment_form .accordion').accordion( "option", "active", 1 );

			<?
			// Если на экране отгрузки - заполняем форму отгрузки
			if( isset($_GET["shpid"]) ) { // Если в отгрузке - заполняем форму отгрузки
				echo "$('#add_shipment_form select[name=CT_ID]').val('{$CT_ID}');";
				echo "$('#add_shipment_form input[name=shp_title]').val('{$shp_title}');";
				echo '$.ajax({ url: "ajax.php?do=shipment&CT_ID='.$CT_ID.'&shpid='.$_GET["shpid"].'", dataType: "script", async: false });';
				echo "$('#add_shipment_form .accordion').accordion( 'option', 'active', 0 );";
			}
			?>

			$('#add_shipment_form').dialog({
				position: { my: "center top", at: "center top", of: window },
				draggable: false,
				width: 1000,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
		});

		// Динамическая подгрузка заказов при выборе города (в форме отгрузки)
		$('select[name="CT_ID"]').on('change', function() {
			var CT_ID = $(this).val();
			$.ajax({ url: "ajax.php?do=shipment&CT_ID="+CT_ID, dataType: "script", async: false });
			$('#add_shipment_form .accordion').accordion( "option", "active", 0 );
		});

		// Редактирование салона аяксом
		$('.shop_cell').dblclick(function() {
			var OD_ID = $(this).attr('id');
			var SH_ID = $(this).attr('SH_ID');
			$.ajax({ url: "ajax.php?do=create_shop_select&OD_ID="+OD_ID+"&SH_ID="+SH_ID, dataType: "script", async: false });
			$(this).find('span').hide();
			$(this).find('.select_shops').show().focus();
		});
		$('.shop_cell select').change(function() {
			var OD_ID = $(this).parents('td').attr('id');
			var val = $(this).val();
			$.ajax({ url: "ajax.php?do=update_shop&OD_ID="+OD_ID+"&SH_ID="+val, dataType: "script", async: false });
			$(this).parents('.shop_cell').find('select').hide();
			$(this).parents('.shop_cell').find('span').show();
		});
		$('.shop_cell select').blur(function() {
			$(this).parents('.shop_cell').find('select').hide();
			$(this).parents('.shop_cell').find('span').show();
		});

		// Редактирование примечания аяксом
		$('.comment_cell').dblclick(function() {
			$(this).find('span').hide();
			$(this).find('textarea').show();
			$(this).find('textarea').focus();
		});
		$('.comment_cell textarea').change(function() {
			var OD_ID = $(this).parents('td').attr('id');
			var val = $(this).val();
			val = val.split("\u000A").join("%0d%0a\u000A"); // Замена символов переноса строки для GET
			$.ajax({ url: "ajax.php?do=update_comment&OD_ID="+OD_ID+"&val="+val, dataType: "script", async: true });
			$(this).parent('.comment_cell').find('textarea').hide();
			$(this).parent('.comment_cell').find('span').show();
		});
		$('.comment_cell textarea').blur(function() {
			$(this).parent('.comment_cell').find('textarea').hide();
			$(this).parent('.comment_cell').find('span').show();
		});

//		// В форме добавления заказа если выбираем Свободные - дата продажи пустая
//		$('#order_form select[name="Shop"]').on("change", function() {
//			var StartDate = $('#order_form input[name="StartDate"]').attr('date');
//			if( $(this).val() === '0' ) {
//				$('#order_form input[name="StartDate"]').val('');
//			}
//			else {
//				$('#order_form input[name="StartDate"]').val(StartDate);
//			}
//		});

//		$('#order_form input[name="StartDate"]').on("change", function() {
//			$(this).attr('date', $(this).val());
//		});
	});
</script>

<?
	include "footer.php";
?>
