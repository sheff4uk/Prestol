$.fx.speeds._default = 300;

// Форматирование числа в денежный формат
Number.prototype.format = function(n, x) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
	return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$& ');
};

// Функция генерирует форму с этапами производства
function makeform(odd_id, location)
{
	$.ajax({ url: "ajax.php?do=steps&odd_id="+odd_id, dataType: "script", async: false });

	$( '.isready' ).button();
	$("#steps form").attr("action", "datasave.php?location="+location);
	
	// Диалог добавления этапов
	$('#steps').dialog({
		resizable: false,
		width: 550,
		modal: true,
		closeText: 'Закрыть'
	});

	// Активация чекбокса готовности если выбран работник
	$('.selectwr').change(function(){
		var val = $(this).val();
		var id = $(this).attr('id');
		var tbody = $(this).parents('tbody');
		var stage = $(this).parents('tr').find('.stage').text();
		
		if( stage == 'Каркас' ) { // Работник каркаса дублируется на сборку
			var tr_sborka = $(this).parents('tbody').find('tr:contains("Сборка")');
			$(tr_sborka).find('select').val(val);

			if( val == '' )
			{
				$(tr_sborka).find('.isready').prop('disabled', true);
				$(tr_sborka).find('.isready').prop('checked', false);
				$(tr_sborka).find('.isready').button('refresh');
			}
			else
			{
				$(tr_sborka).find('.isready').prop('disabled', false);
				$(tr_sborka).find('.isready').button('refresh');
			}
		}
		
		if( val == '' )
		{
			$('#IsReady'+id).prop('disabled', true);
			$('#IsReady'+id).prop('checked', false);
			$('#IsReady'+id).button('refresh');
		}
		else
		{
			$('#IsReady'+id).prop('disabled', false);
			$('#IsReady'+id).button('refresh');
		}
		return false;
	});
	
	return false;
}

////////////////////////////////////////////////////////////////////////////////
$(function(){
	$().UItoTop({ easingType: 'easeOutQuart' });
	$( '.checkstatus' ).button();
	$( '.btnset' ).buttonset();
		// Fix for http://bugs.jqueryui.com/ticket/7856
		$('[type=radio]').change(function() {
			$(this).parent().buttonset("destroy");
			$(this).parent().buttonset();
		});

	// Всплывающая подсказка
	$( document ).tooltip({
		track: true,
//		items: "img, [html], [title]",
		items: "[html]",
		content: function() {
			var element = $( this );
			if ( element.is( "[html]" ) ) {
				return element.attr( "html" );
			}
			if ( element.is( "[title]" ) ) {
				return element.attr( "title" );
			}
			if ( element.is( "img" ) ) {
				return element.attr( "alt" );
			}
		}
	});

	// Убираем title select2 в фильтрах таблиц
	$('.select2_filter select').select2().on("select2:select", function() { $('.select2-selection li').attr('title', ''); });
	$('.select2_filter select').select2().on("select2:unselect", function() { $('.select2-selection li').attr('title', ''); });
	
	$( ".accordion" ).accordion({
		collapsible: true,
		heightStyle: 'content'
	});
	
	// Кнопка редактирования этапов
	$('.edit_steps').click( function()
	{
		if( $(this).parents('.td_step').hasClass('step_disabled') ) {
			noty({timeout: 10000, text: '<b>Набор не редактируется. Изменение этапов невозможно.</b>', type: 'alert'});
			return false;
		}
		else {
			if( $(this).parents('.td_step').hasClass('step_confirmed') ) {
				var location = $(this).attr("location");
				var id = $(this).attr("id");
				makeform(id, location);
			}
			else {
				noty({timeout: 10000, text: 'Набор не принят в работу. Вы не можете назначать этапы.', type: 'alert'});
			}
		}
		return false;
	});

	// Форма разделения набора
	$('.order_cut').click(function() {
		var OD_ID = $(this).attr('id');
		var location = $(this).attr("location");
		$.ajax({ url: "ajax.php?do=order_cut&OD_ID="+OD_ID, dataType: "script", async: false });
		$('#order_cut input[name="location"]').val(location);

		$( "#order_cut #slider span" ).each(function() {
			var value = parseInt( $( this ).text(), 10 );
			$( this ).empty().slider({
				range: "min",
				max: value,
				value: value,
				animate: true,
				slide: function( event, ui ) {
					var amount = parseInt( $(this).parent('div').find('left').text(), 10 ) + parseInt( $(this).parent('div').find('right').text(), 10 );
					$(this).parent('div').find('left').text( ui.value );
					$(this).parent('div').find('right').text( amount - ui.value );
					$(this).parent('div').find('input[name="prod_amount_left[]"]').val( ui.value );
					$(this).parent('div').find('input[name="prod_amount_right[]"]').val( amount - ui.value );
				}
			});
		});

		$("#order_cut").dialog(
		{
			resizable: false,
			width: 500,
			modal: true,
			closeText: 'Закрыть'
		});

		return false;
	});


	// Если ткань/пластик заказан - отображается дата заказа и дата ожидания.
	$('.radiostatus input').change(function(){
		if( $(this).val() == 1 ) {
			$(this).parents( "form" ).find('.order_material').show('fast');
			$(this).parents( "form" ).find('.order_material input').attr("required", true);
			$(this).parents( "form" ).find('.from').val( $(this).parents( "form" ).find('.from').attr("defaultdate") );
			$(this).parents( "form" ).find('.to').val( $(this).parents( "form" ).find('.to').attr("defaultdate") );
		}
		else {
			$(this).parents( "form" ).find('.order_material').hide('fast');
			$(this).parents( "form" ).find('.order_material input').attr("required", false);
			$(this).parents( "form" ).find('.order_material input').val( '' );
			$(this).parents( "form" ).find( ".from" ).datepicker( "option", "maxDate", null );
			$(this).parents( "form" ).find( ".to" ).datepicker( "option", "minDate", null );
		}
		return false;
	});

	// В форме заготовки при смене заготовки меняем тариф
	$('#addblank select[name="Blank"]').change(function(){
		var blank = $(this).val();
		if( blank == "" ) {
			$('#addblank input[name="Tariff"]').val('');
		}
		else {
			$('#addblank input[name="Tariff"]').val( BlankTariff[blank] );
		}
	});
/////////////////////////////////////////////
	// Изменение материала аяксом
		$('.mt_edit').on('dblclick', function() {
			noty({timeout: 5000, text: 'Для отмены изменений нажмите клавишу <b>[Esc]</b>', type: 'alert'});
			var inpt = $(this).parent('.wr_mt').children('input[type="text"]');
			var chbx = $(this).parent('.wr_mt').children('input[type="checkbox"]');
			$(this).hide('fast');
			$(inpt).show('fast');
			$(chbx).show('fast');
			$(inpt).focus();
			$(this).parents('.wr_mt').addClass('nowrap');
			// Чтобы автокомплит работал после открытия диалога
			$( ".materialtags_1" ).autocomplete( "option", "appendTo", ".wr_mt" );
			$( ".materialtags_2" ).autocomplete( "option", "appendTo", ".wr_mt" );
		});

		var timeoutID;
		var ESC;

		$('.mt_edit ~ input').keydown(function(e) {
			if( e.keyCode === 27 ) {
				var spn = $(this).parent('.wr_mt').children('span');
				var inpt = $(this).parent('.wr_mt').children('input[type="text"]');
				var chbx = $(this).parent('.wr_mt').children('input[type="checkbox"]');
				ESC = 1;
				$(inpt).hide('fast');
				$(chbx).hide('fast');
				$(this).parents('.wr_mt').removeClass('nowrap');
				$(spn).show('fast');
				if ($(spn).hasClass('removed')) {
					$(chbx).prop('checked', true);
				}
				else {
					$(chbx).prop('checked', false);
				}
				noty({timeout: 3000, text: 'Изменения отменены.', type: 'error'});
				return false;
			}
		});

		$('.mt_edit ~ input').focus(function () {
			if (timeoutID) {
				clearTimeout(timeoutID);
				timeoutID = null;
			}
		});

		$('.mt_edit ~ input').blur(function () {
			if( ESC != 1 )
				releaseTheHounds(this);
			ESC = 0;
		});

		function releaseTheHounds(th) {
			timeoutID = setTimeout(function () {
				var val = $(th).parent('.wr_mt').children('input[type="text"]').val();
				var spn = $(th).parent('.wr_mt').children('span');
				var inpt = $(th).parent('.wr_mt').children('input[type="text"]');
				var chbx = $(th).parent('.wr_mt').children('input[type="checkbox"]');
				var shid = $(spn).attr('shid');
				var mtid = $(spn).attr('mtid');
				var removed = $(chbx).prop('checked');
				if( val != '') {
					$(inpt).hide('fast');
					$(chbx).hide('fast');
					$(th).parents('.wr_mt').removeClass('nowrap');
					$.ajax({ url: "ajax.php?do=materials&val="+val+"&mtid="+mtid+"&removed="+removed+"&shid="+shid, dataType: "script", async: true });
				}
				else {
					noty({timeout: 3000, text: 'Название материала не может быть пустым!', type: 'error'});
					$(inpt).effect( 'highlight', {color: 'red'}, 1000 );
				}
			}, 1);
		}

///////////////////////////////////////////////////////////////////

		// АВТОКОМПЛИТЫ
		$( ".shopstags" ).autocomplete({ // Автокомплит салонов
			source: "autocomplete.php?do=shopstags"
		});

		$( ".colortags" ).autocomplete({ // Автокомплит цветов
			source: "autocomplete.php?do=colortags",
			minLength: 2,
			autoFocus: false,
			select: function( event, ui ) {
				switch (ui.item.clear) {
					case "1":
						$('#clear1').prop('checked', true);
						$('#clear0').prop('checked', false);
						break;
					case "0":
						$('#clear1').prop('checked', false);
						$('#clear0').prop('checked', true);
						break;
					default:
						$('#clear1').prop('checked', false);
						$('#clear0').prop('checked', false);
				}
				$('#clear1, #clear0').button('refresh');
				$('#paint_color select[name="NCS"]').val(ui.item.NCS_ID).trigger('change');
			}
		});

		$( ".clienttags" ).autocomplete({ // Автокомплит клиентов
			source: "autocomplete.php?do=clienttags"
		});

		$( ".materialtags_1" ).autocomplete({ // Автокомплит тканей
			source: "autocomplete.php?do=textiletags&etalon=1",
			minLength: 2,
			select: function( event, ui ) {
				$(this).parent('div').find('select[name="Shipper"]').val(ui.item.SH_ID);
			},
			create: function() {
				$(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					var listItem = $( "<li>" )
						.append( item.label )
						.appendTo( ul );

					if (item.removed == 1) {
						listItem.addClass( "removed" ).attr( "title", "Выведен!" )
					}

					return listItem;
				}
			}
		});
		$( ".materialtags_1.all" ).autocomplete( "option", "source", "autocomplete.php?do=textiletags" );

		$( ".materialtags_2" ).autocomplete({ // Автокомплит пластиков
			source: "autocomplete.php?do=plastictags&etalon=1",
			minLength: 2,
			select: function( event, ui ) {
				$(this).parent('div').find('select[name="Shipper"]').val(ui.item.SH_ID);
			},
			create: function() {
				$(this).data('ui-autocomplete')._renderItem = function( ul, item ) {
					var listItem = $( "<li>" )
						.append( item.label )
						.appendTo( ul );

					if (item.removed == 1) {
						listItem.addClass( "removed" ).attr( "title", "Выведен!" )
					}

					return listItem;
				}
			}
		});
		$( ".materialtags_2.all" ).autocomplete( "option", "source", "autocomplete.php?do=plastictags" );

//////////////////////////////////////////

		// Смена статуса лакировки аяксом
		$('.painting').on("click", function(event) {
			if(event.target == this) {
				var id = $(this).parents('tr').attr('id');
				id = id.replace('ord', '');
				var val = $(this).attr('val');
				var isready = $(this).attr('isready');
				var shpid = $(this).attr('shpid');
				var filter = $(this).attr('filter');
				$.ajax({ url: "ajax.php?do=ispainting&od_id="+id+"&val="+val+"&isready="+isready+"&shpid="+shpid+"&filter="+filter, dataType: "script", async: false });
			}
		});

		// Смена статуса принятия аяксом
		$('.edit_confirmed').click(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			var val = $(this).attr('val');
			$.ajax({ url: "ajax.php?do=confirmed&od_id="+id+"&val="+val, dataType: "script", async: false });
		});

		// Смена статуса получения клиентом
		$('.taken_confirmed').click(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			var val = $(this).attr('val');
			$.ajax({ url: "ajax.php?do=taken&od_id="+id+"&val="+val, dataType: "script", async: false });
		});

		// Отгрузка набора
		$('.shipping').on('click', function() {
			var od_id = $(this).attr('od_id');
			confirm("Пожалуйста, подтвердите <b>отгрузку</b> набора.").then(function(status){if(status) $.ajax({ url: "ajax.php?do=order_shp&od_id="+od_id, dataType: "script", async: false });});
			return false;
		});

		// Отмена отгрузки набора
		$('.undo_shipping').on('click', function() {
			var od_id = $(this).attr('od_id');
			confirm("Вы собираетесь <b>отменить отгрузку</b> набора. Пожалуйста, подтвердите Ваши действия.", "ajax.php?do=order_undo_shp&od_id="+od_id);
			return false;
		});

		// Удаление набора
		$('.deleting').on('click', function() {
			var od_id = $(this).attr('od_id');
			var m_type = $(this).attr('m_type');
			var ord_scr = $(this).attr('ord_scr');
			if (m_type == 1) {
				var message = "<b>Внимание!</b><br>Набор отмеченный как покрашенный при удалении будет считаться <b>списанным</b> - это означает, что задействованные заготовки, тоже останутся <b>списанными</b>.<br>В остальных случаях набор будет считаться <b>отмененным</b> и заготовки <b>вернутся</b> на склад.<br>К тому же этапы производства, отмеченные как <b>выполненные</b>, после удаления останутся таковыми <b>с сохранением денежного начисления работнику</b>.";
			}
			else {
				var message = "Пожалуйста, подтвердите <b>удаление</b> набора.";
			}
			confirm(message).then(function(status){if(status) $.ajax({ url: "ajax.php?do=order_del&od_id="+od_id+"&ord_scr="+ord_scr, dataType: "script", async: false });});
			return false;
		});

		// Восстановление удаленного набора
		$('.undo_deleting').on('click', function() {
			var od_id = $(this).attr('od_id');
			var ord_scr = $(this).attr('ord_scr');
			confirm("Пожалуйста, подтвердите <b>восстановление</b> удалённого набора.").then(function(status){if(status) $.ajax({ url: "ajax.php?do=order_del_undo&od_id="+od_id+"&ord_scr="+ord_scr, dataType: "script", async: false });});
			return false;
		});

		// Клонирование набора
		$('.clone').on('click', function() {
			var od_id = $(this).attr('od_id');
			confirm("Пожалуйста, подтвердите <b>клонирование набора</b>.", "clone_order.php?id="+od_id);
			return false;
		});

});
