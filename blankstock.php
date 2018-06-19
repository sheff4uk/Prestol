<?
//	session_start();
	include "config.php";
	$title = 'Заготовки';
	include "header.php";

	// Проверка прав на доступ к экрану
	if( !in_array('screen_blanks', $Rights) ) {
		header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
		die('Недостаточно прав для совершения операции');
	}

	$location = $_SERVER['REQUEST_URI'];

	// Добавление заготовок
	if( isset($_POST["Blank"]) )
	{
		$Worker = $_POST["Worker"] > 0 ? $_POST["Worker"] : "NULL";
		$Blank = $_POST["Blank"] <> "" ? $_POST["Blank"] : "NULL";
		$Amount = $_POST["Amount"] <> "" ? $_POST["Amount"] : "NULL";
		$Tariff = $_POST["Tariff"] <> "" ? $_POST["Tariff"] : "NULL";
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );

		// Добавление заготовок
		$query = "INSERT INTO BlankStock(WD_ID, BL_ID, Amount, Tariff, Comment, author)
				  VALUES ({$Worker}, {$Blank}, {$Amount}, {$Tariff}, '{$Comment}', {$_SESSION["id"]})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$bs_id = mysqli_insert_id( $mysqli );

		// Добавление связанных заготовок
		foreach ($_POST["wd_id"] as $key => $value) {
			$value = $value > 0 ? $value : "NULL";
			$sub_amount = $_POST["amount"][$key] * $Amount * -1;
			$query = "INSERT INTO BlankStock(WD_ID, BL_ID, Amount, PBS_ID, author)
					  VALUES ({$value}, {$_POST["bll_id"][$key]}, {$sub_amount}, {$bs_id}, {$_SESSION["id"]})";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		exit ('<meta http-equiv="refresh" content="0; url='.$location.'">');
		die;
	}

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

	<div id='add_btn' title='Добавить заготовки'></div>

	<!-- Форма добавления заготовки -->
	<div id='addblank' title='Заготовки' class="addproduct" style='display:none'>
		<form method="post">
			<fieldset style="font-size: 1.2em;">
				<input type='hidden' name='BS_ID'>
				<div>
					<label>Работник:</label>
					<select required name="Worker" id="worker" style="width: 200px;">
						<option value="">-=Выберите работника=-</option>
						<option value="0">Без работника</option>
						<optgroup label="Частые">
							<?
							$query = "SELECT WD.WD_ID, WD.Name, COUNT(1) cnt
										FROM WorkersData WD
										JOIN BlankStock BS ON BS.WD_ID = WD.WD_ID AND DATEDIFF(NOW(), Date) <= 90
										GROUP BY BS.WD_ID
										ORDER BY cnt DESC";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["WD_ID"]}'>{$row["Name"]}</option>";
							}
							?>
						</optgroup>
						<optgroup label="Остальные">
							<?
							$query = "SELECT WD.WD_ID, WD.Name
										FROM WorkersData WD
										LEFT JOIN BlankStock BS ON BS.WD_ID = WD.WD_ID AND DATEDIFF(NOW(), Date) <= 90
										WHERE WD.Type = 1 AND BS.WD_ID IS NULL
										ORDER BY WD.Name;";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["WD_ID"]}'>{$row["Name"]}</option>";
							}
							?>
						</optgroup>
					</select>
				</div>
				<div>
					<label>Заготовка:</label>
					<select required name="Blank" id="blank" style="width: 200px;">
						<!--Формируется аяксом при выборе работника (blank_dropdown)-->
					</select>
				</div>
				<div style="width: 170px; display: inline-block;">
					<label>Кол-во:</label>
					<input required type='number' name='Amount' class='amount'>
				</div>
				<div style="width: 130px; display: inline-block;">
					Тариф:
					<input type='number' name='Tariff' min='0' step='5' class='tariff'>
				</div>
				<fieldset disabled id="subblank" style="display: none; text-align: right;">
					<!--Формируется аяксом при выборе заготовки (subblank_dropdown)-->
				</fieldset>
				<div>
					<label>Примечание:</label>
					<input type='text' name='Comment' style="width: 200px;">
<!--					<textarea name='Comment' rows='4' cols='25'></textarea>-->
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
				<th>Коррекция</th>
				<th title="Фактическое наличие неокрашенных заготовок на производстве.">Наличие<i class="fa fa-question-circle" aria-hidden="true"></i></th>
				<th>В покраске</th>
				<th title="Кол-во заготовок, необходимое для выполнения текущих заказов. Через дробь указано сколько нужно заготовок под прозрачное покрытие.">Требуется<i class="fa fa-question-circle" aria-hidden="true"></i></th>
			</tr>
			</thead>
			<tbody id="exist_blank">
			<?
				// Рекурсивная функция вывода дерева заготовок
				function blank_tree( $pid, $level ) {
					global $mysqli;

					$query = "SELECT BLL.BLL_ID, BL.Name, IFNULL(SUM(BC.count + BC.start_balance), 0) Amount, IFNULL(SUM(BC.start_balance), 0) start_balance
								FROM BlankLink BLL
								JOIN BlankList BL ON BL.BL_ID = BLL.BLL_ID
								LEFT JOIN BlankCount BC ON BC.BL_ID = BLL.BLL_ID
								WHERE BLL.BL_ID = {$pid}
								GROUP BY BLL.BLL_ID";
					$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
					while( $row = mysqli_fetch_array($res) ) {
						$color = ( $row["Amount"] < 0 ) ? ' bg-red' : '';
						$tabs = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
						echo "<tr id='{$row["BLL_ID"]}' class='sub_blank'>";
						echo "<td id><b>{$tabs}{$row["Name"]}</b></td>";
						echo "<td class='txtright'><input type='number' disabled class='amount start_balance' value='{$row["start_balance"]}'></td>";
						echo "<td class='txtright blank_amount'><span><b class='{$color}'>{$row["Amount"]}</b></span></td>";
						echo "<td class='txtright'></td>";
						echo "<td class='txtright'></td>";
						echo "</tr>";

						// Вывод запасов заготовок поименно
						$query = "SELECT BC.BL_ID, BC.WD_ID, IFNULL(WD.Name, 'Без работника') Name, (BC.count + BC.start_balance) Amount, BC.start_balance
									FROM BlankCount BC
									LEFT JOIN WorkersData WD ON WD.WD_ID = BC.WD_ID
									WHERE BC.BL_ID = {$row["BLL_ID"]}";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $subrow = mysqli_fetch_array($subres) )
						{
							$subcolor = ( $subrow["Amount"] < 0 ) ? ' bg-red' : '';
							echo "<tr id='blank_{$row["BLL_ID"]}_{$subrow["WD_ID"]}'>";
							echo "<td style='color: #666;'><i>{$tabs}-{$subrow["Name"]}</i></td>";
							echo "<td class='txtright'><input type='number' class='amount start_balance_worker' wd_id='{$subrow["WD_ID"]}' bl_id='{$subrow["BL_ID"]}' value='{$subrow["start_balance"]}'></td>";
							echo "<td style='color: #666;' class='txtright blank_amount'><span><i class='{$subcolor}'>{$subrow["Amount"]}</i></span></td>";
							echo "<td class='txtright'></td>";
							echo "<td class='txtright'></td>";
							echo "</tr>";
						}
						blank_tree( $row["BLL_ID"], $level+1 );
					}
				}

				// Список заготовок верхнего уровня с остатками.
				// ToDo: сделать триггеры для подсчета остатков, получаемых этим запросом.
				$query = "
					SELECT BL.PT_ID
						,BL.BL_ID
						,BL.Name
						,BL.start_balance

						,IFNULL(BL.start_balance, 0) + IFNULL(SBS.Amount, 0) - IFNULL(SODD.Painting, 0) - IFNULL(SODB.Painting, 0) - IFNULL(SODD.PaintingDeleted, 0) - IFNULL(SODB.PaintingDeleted, 0) AmountBeforePainting

						,IFNULL(BL.start_balance, 0) + IFNULL(SBS.Amount, 0) - IFNULL(SODD.Painting, 0) - IFNULL(SODB.Painting, 0) - IFNULL(SODD.PaintingDeleted, 0) - IFNULL(SODB.PaintingDeleted, 0) + IFNULL(SODD.InPainting, 0) + IFNULL(SODB.InPainting, 0) total_amount

						,IFNULL(SODD.InPainting, 0) + IFNULL(SODB.InPainting, 0) AmountInPainting

						,IFNULL(SODD.Amount, 0) - IFNULL(SODD.Painting, 0) + IFNULL(SODB.Amount, 0) - IFNULL(SODB.Painting, 0) BeforePainting

						,IFNULL(SODD.ClearAmount, 0) - IFNULL(SODD.ClearPainting, 0) + IFNULL(SODB.ClearAmount, 0) - IFNULL(SODB.ClearPainting, 0) ClearBeforePainting

					FROM BlankList BL
					JOIN ProductBlank PB ON PB.BL_ID = BL.BL_ID AND PB.BL_ID IS NOT NULL
					JOIN ProductModels PM ON PM.PM_ID = PB.PM_ID AND PM.archive = 0
					LEFT JOIN (
						SELECT BS.BL_ID, SUM(BS.Amount) Amount
						FROM BlankStock BS
						WHERE BS.adj = 0
						GROUP BY BS.BL_ID
					) SBS ON SBS.BL_ID = BL.BL_ID
					LEFT JOIN (
						SELECT PB.BL_ID
								,SUM(ODD.Amount * PB.Amount * IF(OD.DelDate IS NULL, 1, 0)) Amount
								,SUM(ODD.Amount * PB.Amount * IF(OD.DelDate IS NULL, 1, 0) * IFNULL(CL.clear, 0)) ClearAmount
								,SUM(IF(OD.IsPainting IN(2,3), ODD.Amount, 0) * PB.Amount * IF(OD.DelDate IS NULL, 1, 0)) Painting
								,SUM(IF(OD.IsPainting IN(2,3), ODD.Amount, 0) * PB.Amount * IF(OD.DelDate IS NULL, 1, 0) * IFNULL(CL.clear, 0)) ClearPainting
								,SUM(IF(OD.IsPainting = 2, ODD.Amount, 0) * PB.Amount * IF(OD.DelDate IS NULL, 1, 0)) InPainting
								,SUM(IF(OD.IsPainting = 3, ODD.Amount, 0) * PB.Amount * IF(OD.DelDate IS NULL, 0, 1)) PaintingDeleted
						FROM OrdersDataDetail ODD
						JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
						LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
						JOIN ProductBlank PB ON PB.PM_ID = ODD.PM_ID
						WHERE ODD.Del = 0
						GROUP BY PB.BL_ID
					) SODD ON SODD.BL_ID = BL.BL_ID
					LEFT JOIN (
						SELECT ODB.BL_ID
								,SUM(ODB.Amount * IF(OD.DelDate IS NULL, 1, 0)) Amount
								,SUM(ODB.Amount * IF(OD.DelDate IS NULL, 1, 0) * IFNULL(CL.clear, 0)) ClearAmount
								,SUM(IF(OD.IsPainting IN(2,3), ODB.Amount, 0) * IF(OD.DelDate IS NULL, 1, 0)) Painting
								,SUM(IF(OD.IsPainting IN(2,3), ODB.Amount, 0) * IF(OD.DelDate IS NULL, 1, 0) * IFNULL(CL.clear, 0)) ClearPainting
								,SUM(IF(OD.IsPainting = 2, ODB.Amount, 0) * IF(OD.DelDate IS NULL, 1, 0)) InPainting
								,SUM(IF(OD.IsPainting = 3, ODB.Amount, 0) * IF(OD.DelDate IS NULL, 0, 1)) PaintingDeleted
						FROM OrdersDataBlank ODB
						JOIN OrdersData OD ON OD.OD_ID = ODB.OD_ID
						LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
						WHERE ODB.BL_ID IS NOT NULL
						GROUP BY ODB.BL_ID
					) SODB ON SODB.BL_ID = BL.BL_ID
					WHERE BL.Name NOT LIKE 'Клей'
					GROUP BY BL.BL_ID
					ORDER BY BeforePainting DESC, BL.Name ASC
				";
				$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
				while( $row = mysqli_fetch_array($res) )
				{
					$color = ( $row["total_amount"] < 0 ) ? ' bg-red' : '';
					echo "<tr class='top_blank'>";
					echo "<td class='bold'>{$row["Name"]}</td>";
					echo "<td class='txtright'><input type='number' value='{$row["start_balance"]}' bl_id='{$row["BL_ID"]}' class='amount start_balance_blank'></td>";
					echo "<td class='txtright' id='blank_{$row["BL_ID"]}'><b class='{$color}'>{$row["total_amount"]}</b></td>";
					echo "<td class='txtright'><b>{$row["AmountInPainting"]}</b></td>";
					echo "<td class='txtright'><b>{$row["BeforePainting"]}</b><span style='font-size: 0.8em'>/{$row["ClearBeforePainting"]}</span></td>";
					echo "</tr>";

					// Вывод дерева заготовок
					blank_tree( $row["BL_ID"], 1 );
				}
			?>
			</tbody>
		</table>
	</div>

	<div class="log-blank halfblock">
		<!--Содержимое формируется аяксом-->
	</div>

<style>
	.auto_record {
		//opacity: .5;
	}
	.auto_record td {
		visibility: hidden;
		padding: 0px;
		font-size: 0em;
		transition: .3s;
		-webkit-transition: .3s;
		background-color: #ddd;
	}
	.is_parent:hover + .auto_record td {
		visibility: visible;
		font-size: 1em;
	}
	.auto_record:hover td {
		visibility: visible;
		font-size: 1em;
	}
	.is_parent .fa-arrow-right {
		transition: .3s;
		-webkit-transition: .3s;
	}
	.is_parent:hover .fa-arrow-right {
		transform: rotate(90deg);
	}
</style>

<script>
	// Функция вызывает аякс который выводит журнал сдачи заготовок
	function blank_log_table() {
		$.ajax({ url: "ajax.php?do=blank_log_table", dataType: "script", async: true });
	}

	$(function() {
		blank_log_table();

//		$('#worker').select2({ placeholder: 'Выберите работника', language: 'ru' });
//		$('#blank').select2({ placeholder: 'Выберите заготовку', language: 'ru' });
//
//		// Костыль для Select2 чтобы работал поиск
//		$.ui.dialog.prototype._allowInteraction = function (e) {
//			return true;
//		};

		// Редактирование аяксом начального значения заготовок по рабочим
		$('.start_balance_worker').on('blur', function() {
			var val = $(this).val();
			var wd_id = $(this).attr('wd_id');
			var bl_id = $(this).attr('bl_id');
			$.ajax({ url: "ajax.php?do=start_balance_worker&wd_id="+wd_id+"&bl_id="+bl_id+"&val="+val, dataType: "script", async: false });
		});

		// Редактирование аяксом начального значения заготовок верхнего уровня
		$('.start_balance_blank').on('blur', function() {
			var val = $(this).val();
			var bl_id = $(this).attr('bl_id');
			$.ajax({ url: "ajax.php?do=start_balance_blank&bl_id="+bl_id+"&val="+val, dataType: "script", async: false });
		});

		// Выбор заготовок доступен после выбора работника
		$('#addblank #worker').change( function() {
			val = $(this).val();
			if( val != '' ) {
				$('#addblank #blank').prop('disabled', false);
			}
			else {
				$('#addblank #blank').prop('disabled', true);
				$('#addblank #blank').val('').change();
			}
			$.ajax({ url: "ajax.php?do=blank_dropdown&wd_id="+val, dataType: "script", async: false });
		});

		// При выборе заготовки выводится аяксом список задействованных заготовок
		$('#addblank #blank').change( function() {
			val = $(this).val();
			wd_id = $('#addblank #worker').val();
			if( val == '' ) { val = 0; }
			$.ajax({ url: "ajax.php?do=subblank_dropdown&bl_id="+val+"&wd_id="+wd_id, dataType: "script", async: false });
		});

		// Форма добавления заготовок
		$('#add_btn').click(function() {
//			var id = $(this).attr('id');
//			var worker = $(this).parents('tr').find('.worker').attr('val');
//			var blank = $(this).parents('tr').find('.blank').attr('val');
//			var amount = $(this).parents('tr').find('.amount').html();
//			var tariff = $(this).parents('tr').find('.tariff').html();
//			var comment = $(this).parents('tr').find('.comment > pre').html();

			// Очистка диалога
			$('#addblank input, #addblank select, #addblank textarea').val('');
			$('#addblank #worker').val('').trigger('change');
			$('#addblank #blank').val('').trigger('change');

//			// Заполнение
//			if( typeof id !== "undefined" )
//			{
//				$('#addblank select[name="Worker"]').val(worker).trigger('change');
//				$('#addblank select[name="Blank"]').val(blank).trigger('change');
//				$('#addblank input[name="Amount"]').val(amount);
//				$('#addblank input[name="Tariff"]').val(tariff);
//				$('#addblank textarea[name="Comment"]').val(comment);
//				$('#addblank input[name="BS_ID"]').val(id);
//			}

			// Форма добавления/редактирования заготовок
			$('#addblank').dialog({
				width: 500,
				modal: true,
				show: 'blind',
				hide: 'explode',
				closeText: 'Закрыть'
			});
			return false;
		});
	});
</script>

<?
	include "footer.php";
?>
