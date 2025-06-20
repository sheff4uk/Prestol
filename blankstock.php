<?php
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
		$Tariff = $_POST["Tariff"] <> "" ? $_POST["Tariff"] : 0;
		$Comment = mysqli_real_escape_string( $mysqli,$_POST["Comment"] );

		// Добавление заготовок
		$query = "INSERT INTO BlankStock(USR_ID, BL_ID, Amount, Tariff, Comment, author)
				  VALUES ({$Worker}, {$Blank}, {$Amount}, {$Tariff}, '{$Comment}', {$_SESSION["id"]})";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		$bs_id = mysqli_insert_id( $mysqli );

		// Добавление связанных заготовок
		foreach ($_POST["usr_id"] as $key => $value) {
			$value = $value > 0 ? $value : "NULL";
			$sub_amount = $_POST["amount"][$key] * $Amount * -1;
			$query = "INSERT INTO BlankStock(USR_ID, BL_ID, Amount, PBS_ID, author)
					  VALUES ({$value}, {$_POST["bll_id"][$key]}, {$sub_amount}, {$bs_id}, {$_SESSION["id"]})";
			mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
		}

		// Обновление тарифа заготовки
		$query = "
			UPDATE BlankList
			SET Tariff = {$Tariff}
			WHERE BL_ID = {$Blank}
		";
		mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));

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
		<form method="post" onsubmit="JavaScript:this.subbut.disabled=true;this.subbut.value='Подождите, пожалуйста!';">
			<fieldset style="font-size: 1.2em;">
				<input type='hidden' name='BS_ID'>
				<div>
					<label>Работник:</label>
					<select required name="Worker" id="worker" style="width: 300px;">
						<option value="">-=Выберите работника=-</option>
						<option value="0">Без работника</option>
						<optgroup label="Частые">
							<?php
							$query = "
								SELECT USR.USR_ID, USR_ShortName(USR.USR_ID) Name, COUNT(1) cnt
								FROM Users USR
								JOIN BlankStock BS ON BS.USR_ID = USR.USR_ID AND DATEDIFF(NOW(), Date) <= 90
								WHERE USR.act = 1
								GROUP BY USR.USR_ID
								ORDER BY cnt DESC
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["USR_ID"]}'>{$row["Name"]}</option>";
							}
							?>
						</optgroup>
						<optgroup label="Остальные">
							<?php
							$query = "
								SELECT USR.USR_ID, USR_ShortName(USR.USR_ID) Name
								FROM Users USR
								LEFT JOIN BlankStock BS ON BS.USR_ID = USR.USR_ID AND DATEDIFF(NOW(), Date) <= 90
								WHERE USR.act = 1 AND USR.RL_ID = 10 AND BS.USR_ID IS NULL
								ORDER BY Name
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["USR_ID"]}'>{$row["Name"]}</option>";
							}
							?>
						</optgroup>
						<optgroup label="Уволенные">
							<?php
							$query = "
								SELECT USR.USR_ID, USR_ShortName(USR.USR_ID) Name
								FROM Users USR
								WHERE USR.act = 0 AND USR.RL_ID = 10
								ORDER BY Name
							";
							$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
							while( $row = mysqli_fetch_array($res) )
							{
								echo "<option value='{$row["USR_ID"]}'>{$row["Name"]}</option>";
							}
							?>
						</optgroup>
					</select>
				</div>
				<div>
					<label>Заготовка:</label>
					<select required name="Blank" id="blank" style="width: 300px;">
						<!--Формируется аяксом при выборе работника (blank_dropdown)-->
					</select>
				</div>
				<div style="width: 230px; display: inline-block;">
					<label>Кол-во:</label>
					<input required type='number' name='Amount' class='amount'>
				</div>
				<div style="width: 170px; display: inline-block;">
					Тариф:
					<input type='number' name='Tariff' min='0' step='5' class='tariff'>
				</div>
				<fieldset disabled id="subblank" style="display: none; text-align: right;">
					<!--Формируется аяксом при выборе заготовки (subblank_dropdown)-->
				</fieldset>
				<div>
					<label>Примечание:</label>
					<input type='text' name='Comment' style="width: 300px;">
<!--					<textarea name='Comment' rows='4' cols='25'></textarea>-->
				</div>
			</fieldset>
			<div>
				<hr>
				<input type='submit' name="subbut" value='Сохранить' style='float: right;'>
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
				<th title="Количество заготовок на производстве. Вместе с теми, что даны в покраску.">Наличие<i class="fa fa-question-circle" aria-hidden="true"></i></th>
				<th title="Эти заготовки могут находится либо на складе, либо в лакировочном цехе, либо быть отлакированными.">Из них даны<br>в покраску<i class="fa fa-question-circle" aria-hidden="true"></i></th>
				<th title="Кол-во заготовок, необходимое для выполнения текущих заявок. Через дробь указано сколько нужно заготовок под прозрачное покрытие.">Требуется<i class="fa fa-question-circle" aria-hidden="true"></i></th>
			</tr>
			</thead>
			<tbody id="exist_blank">
			<?php
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
						$query = "
							SELECT BC.BL_ID
								,BC.USR_ID
								,IFNULL(USR_ShortName(BC.USR_ID), 'Без работника') Name
								,(BC.count + BC.start_balance) Amount
								,BC.start_balance
							FROM BlankCount BC
							WHERE BC.BL_ID = {$row["BLL_ID"]}
						";
						$subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
						while( $subrow = mysqli_fetch_array($subres) )
						{
							$subcolor = ( $subrow["Amount"] < 0 ) ? ' bg-red' : '';
							echo "<tr id='blank_{$row["BLL_ID"]}_{$subrow["USR_ID"]}'>";
							echo "<td style='color: #666;'><i>{$tabs}-{$subrow["Name"]}</i></td>";
							echo "<td class='txtright'><input type='number' class='amount start_balance_worker' usr_id='{$subrow["USR_ID"]}' bl_id='{$subrow["BL_ID"]}' value='{$subrow["start_balance"]}'></td>";
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

						,IFNULL(BL.start_balance, 0) + IFNULL(SBS.Amount, 0) - IFNULL(SODD.Ready, 0) - IFNULL(SODB.Ready, 0) total_amount

						,IFNULL(SODD.InPainting, 0) + IFNULL(SODB.InPainting, 0) AmountInPainting

						,IFNULL(SODD.NeedAmount, 0) + IFNULL(SODB.NeedAmount, 0) BeforePainting

						,IFNULL(SODD.ClearNeedAmount, 0) + IFNULL(SODB.ClearNeedAmount, 0) ClearBeforePainting

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
							,SUM(IF(OD.ReadyDate IS NULL AND OD.DelDate IS NULL AND OD.IsPainting != 3 AND !(OD.CL_ID IS NULL AND OD_IsReady(OD.OD_ID)), ODD.Amount, 0) * PB.Amount) NeedAmount
							,SUM(IF(OD.ReadyDate IS NULL AND OD.DelDate IS NULL AND OD.IsPainting != 3 AND !(OD.CL_ID IS NULL AND OD_IsReady(OD.OD_ID)), ODD.Amount, 0) * PB.Amount * IFNULL(CL.clear, 0)) ClearNeedAmount
							,SUM(IF(OD.IsPainting = 3 OR (OD.CL_ID IS NULL AND OD_IsReady(OD.OD_ID)), ODD.Amount, 0) * PB.Amount) Ready
							,SUM(IF(OD.IsPainting = 2, ODD.Amount, 0) * PB.Amount) InPainting
						FROM OrdersDataDetail ODD
						JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
						LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
						JOIN ProductBlank PB ON PB.PM_ID = ODD.PM_ID
						GROUP BY PB.BL_ID
					) SODD ON SODD.BL_ID = BL.BL_ID
					LEFT JOIN (
						SELECT ODD.BL_ID
							,SUM(IF(OD.ReadyDate IS NULL AND OD.DelDate IS NULL AND OD.IsPainting != 3 AND !(OD.CL_ID IS NULL AND OD_IsReady(OD.OD_ID)), ODD.Amount, 0)) NeedAmount
							,SUM(IF(OD.ReadyDate IS NULL AND OD.DelDate IS NULL AND OD.IsPainting != 3 AND !(OD.CL_ID IS NULL AND OD_IsReady(OD.OD_ID)), ODD.Amount, 0) * IFNULL(CL.clear, 0)) ClearNeedAmount
							,SUM(IF(OD.IsPainting = 3 OR (OD.CL_ID IS NULL AND OD_IsReady(OD.OD_ID)), ODD.Amount, 0)) Ready
							,SUM(IF(OD.IsPainting = 2, ODD.Amount, 0)) InPainting
						FROM OrdersDataDetail ODD
						JOIN OrdersData OD ON OD.OD_ID = ODD.OD_ID
						LEFT JOIN Colors CL ON CL.CL_ID = OD.CL_ID
						WHERE ODD.BL_ID IS NOT NULL
						GROUP BY ODD.BL_ID
					) SODB ON SODB.BL_ID = BL.BL_ID
					GROUP BY BL.BL_ID
					ORDER BY BL.Name ASC
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
			var usr_id = $(this).attr('usr_id');
			var bl_id = $(this).attr('bl_id');
			$.ajax({ url: "ajax.php?do=start_balance_worker&usr_id="+usr_id+"&bl_id="+bl_id+"&val="+val, dataType: "script", async: false });
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
			$.ajax({ url: "ajax.php?do=blank_dropdown&usr_id="+val, dataType: "script", async: false });
		});

		// При выборе заготовки выводится аяксом список задействованных заготовок
		$('#addblank #blank').change( function() {
			val = $(this).val();
			usr_id = $('#addblank #worker').val();
			if( val == '' ) { val = 0; }
			$.ajax({ url: "ajax.php?do=subblank_dropdown&bl_id="+val+"&usr_id="+usr_id, dataType: "script", async: false });
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
			$('#addblank input[type="text"], #addblank input[type="number"], #addblank select, #addblank textarea').val('');
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
				resizable: false,
				width: 500,
				modal: true,
				closeText: 'Закрыть'
			});
			return false;
		});
	});
</script>

<?php
	include "footer.php";
?>
