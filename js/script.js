$.fx.speeds._default = 300;

// Форматирование числа в денежный формат
Number.prototype.format = function(n, x) {
	var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
	return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$& ');
};

// Функция активирует/деактивирует кнопки типа покрытия
function clearonoff(element)
{
	var length = $(element).val().length;
	var disabled = $(element).prop("disabled");
	if( length == 0 || disabled )
	{
		$(element).parent().find('.btnset input[type="radio"]').prop('disabled', true);
//		$('#clear1').prop('checked', false);
//		$('#clear0').prop('checked', false);
	}
	else
	{
		$(element).parent().find('.btnset input[type="radio"]').prop('disabled', false);
	}
	$(element).parent().find('.btnset input[type="radio"]').button('refresh');
	return false;
}

// Функция генерирует форму с этапами производства
function makeform(id, other, location, plid)
{
	if( other == 0 ) {
		$.ajax({ url: "ajax.php?do=steps&odd_id="+id, dataType: "script", async: false });
	}
	else {
		$.ajax({ url: "ajax.php?do=steps&odb_id="+id, dataType: "script", async: false });
	}
	$( '.isready' ).button();
	$("#steps form").attr("action", "datasave.php?location="+location+"&plid="+plid);
	
	// Диалог добавления этапов
	$('#steps').dialog({
		width: 500,
		modal: true,
		show: 'blind',
		hide: 'explode',
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
	// При очистке информации о цвете деактивируем кнопки прозрачности
	$('#paint_color').on("change", function(){
		clearonoff(this);
	});

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
		items: "img, [html], [title]",
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
			noty({timeout: 10000, text: '<b>Заказ не редактируется. Изменение этапов невозможно.</b>', type: 'alert'});
			return false;
		}
		else {
			if( $(this).parents('.td_step').hasClass('step_confirmed') ) {
				var location = $(this).attr("location");
				var id = $(this).attr("id");
				var odbid = $(this).attr("odbid");
				plid = $(this).attr("plid");
				if( typeof plid === "undefined" ) {
					plid = '';
				}
				if( typeof odbid !== "undefined" ) {
					makeform(odbid, 1, location, plid);
				}
				else {
					makeform(id, 0, location, plid);
				}
			}
			else {
				noty({timeout: 10000, text: 'Заказ не принят в работу. Вы не можете назначать этапы.', type: 'alert'});
			}
		}
		return false;
	});

	// Форма разделения заказа
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
			width: 500,
			modal: true,
			show: 'blind',
			hide: 'explode',
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
			var t = $(this).html();
			var inpt = $(this).parent('.wr_mt').children('input[type="text"]');
			var chbx = $(this).parent('.wr_mt').children('input[type="checkbox"]');
			$(inpt).val(t);
			$(this).hide('fast');
			$(inpt).show('fast');
			if( $(this).hasClass('removed') )
				$(chbx).prop('checked', true);
			else
				$(chbx).prop('checked', false);
			$(chbx).show('fast');
			$(inpt).focus();
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
				$(spn).show('fast');
				$(spn).css('display' , '');
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
				var t = $(th).parent('.wr_mt').children('input[type="text"]').val();
				var spn = $(th).parent('.wr_mt').children('span');
				var inpt = $(th).parent('.wr_mt').children('input[type="text"]');
				var chbx = $(th).parent('.wr_mt').children('input[type="checkbox"]');
				var oldt = $(spn).html();
				var mtid = $(spn).attr('mtid');
				var shid = $(spn).attr('shid');
				var removed = $(chbx).prop('checked');
				if( t != '') {
					$(inpt).hide('fast');
					$(chbx).hide('fast');
					$.ajax({ url: "ajax.php?do=materials&val="+t+"&oldval="+oldt+"&removed="+removed+"&shid="+shid, dataType: "script", async: true });
					if( t != oldt || $(spn).hasClass('removed') != removed ) {
						$('.mt'+mtid).hide('fast');
						$('.mt'+mtid).html(t);
						if( removed ) {
							$('.mt'+mtid).addClass('removed');
						}
						else {
							$('.mt'+mtid).removeClass('removed');
						}
					}
					$('.mt'+mtid).show('fast');
				}
				else {
					noty({timeout: 3000, text: 'Название материала не может быть пустым!', type: 'error'});
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
						$('#clear1, #clear0').prop('disabled', false);
						break;
					case "0":
						$('#clear1').prop('checked', false);
						$('#clear0').prop('checked', true);
						$('#clear1, #clear0').prop('disabled', false);
						break;
					default:
						$('#clear1').prop('checked', false);
						$('#clear0').prop('checked', false);
						$('#clear1, #clear0').prop('disabled', false);
				}
				$('#clear1, #clear0').button('refresh');
			}
		});

		$( ".clienttags" ).autocomplete({ // Автокомплит заказчиков
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
				var archive = $(this).attr('archive');
				var shpid = $(this).attr('shpid');
				var filter = $(this).attr('filter');
				$.ajax({ url: "ajax.php?do=ispainting&od_id="+id+"&val="+val+"&isready="+isready+"&archive="+archive+"&shpid="+shpid+"&filter="+filter, dataType: "script", async: false });
			}
		});

		// Смена статуса принятия аяксом
		$('.edit_confirmed').click(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			var val = $(this).attr('val');
			$.ajax({ url: "ajax.php?do=confirmed&od_id="+id+"&val="+val, dataType: "script", async: false });
		});

		// Смена статуса получения заказчиком аяксом
		$('.taken_confirmed').click(function() {
			var id = $(this).parents('tr').attr('id');
			id = id.replace('ord', '');
			var val = $(this).attr('val');
			$.ajax({ url: "ajax.php?do=taken&od_id="+id+"&val="+val, dataType: "script", async: false });
		});


});
