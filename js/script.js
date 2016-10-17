$.fx.speeds._default = 300;
//var odid;

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
	});

	// Активация чекбокса готовности если выбран работник
	$('.selectwr').change(function(){
		var val = $(this).find('option:selected').val();
		var id = $(this).attr('id');
		
		if( id == 7 ) { // Работник каркаса дублируется на сборку
			$('select[name="WD_ID8"]').val(val);
			if( val == '' )
			{
				$('#IsReady8').prop('disabled', true);
				$('#IsReady8').prop('checked', false);
				$('#IsReady8').button('refresh');
			}
			else
			{
				$('#IsReady8').prop('disabled', false);			
				$('#IsReady8').button('refresh');
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

// Функция активирует/деактивирует кнопки наличия ткани/пластика
function materialonoff(element)
{
	if( $(element+' input[name="Material"]').val().length == 0 )
	{
		$(element+' .radiostatus input[type="radio"]').prop('disabled', true);
		// Очистка инпутов дат заказа пластика
		$('#1radio0').prop('checked', true);
		$('#2radio2').prop('checked', true);
		$('#0radio0').prop('checked', true);
		$(element+' .order_material').hide('fast');
		$(element+' .order_material input').attr("required", false);
		$(element+' .order_material input').val('');
		$(element+' .order_material input.from').datepicker( "option", "maxDate", null );
		$(element+' .order_material input.to').datepicker( "option", "minDate", null );
	}
	else
	{
		$(element+' .radiostatus input[type="radio"]').prop('disabled', false);
	}
	$(element+' .radiostatus input[type="radio"]').button('refresh');
	return false;
}

// Функция живого поиска в "Свободных" при вводе параметров в форму
function livesearch(element) {
	if ( $(element).parents('form').find('.accordion').is(":visible") ) {
		var line = "&model="+$(element).parents('form').find('select[name="Model"]').val()
				 + "&form="+$(element).parents('form').find('input[name="Form"]:checked').val()
				 + "&mechanism="+$(element).parents('form').find('input[name="Mechanism"]:checked').val()
				 + "&length="+$(element).parents('form').find('input[name="Length"]').val()
				 + "&width="+$(element).parents('form').find('input[name="Width"]').val()
				 + "&material="+$(element).parents('form').find('input[name="Material"]').val()
				 + "&type="+$(element).parents('form').find('input[name="Type"]').val();
		$.ajax({
			url: "ajax.php?do=livesearch&this=" + $(element).parents('form').parent('div').attr('id') + line,
			dataType: "script",
			async: false
		});
		$('.checkstatus').button();
	}
}

// Функция формирования списка форм в зависимости от модели стола
function FormModelList(model, form) {
	var forms = "";
	var arr = ModelForm[model];
	var informs = 0;
	if( typeof arr !== "undefined" ) {
		$.each(arr, function(key, val){
			forms += "<input type='radio' id='form" + key + "' name='Form' value='" + key + "'>";
			forms += "<label for='form" + key + "'>" + val + "</label>";
			if( form == key ) { informs = 1; }
		});
	}
	$('#addtable #forms').html(forms);
	if( forms != "" ) {
		if( form > 0 && informs ) {
			$('#addtable input[name="Form"][value="'+form+'"]').prop('checked', true);
		}
		else {
			$('#addtable input[name="Form"]:nth-child(1)').prop('checked', true);
		}
		$('#addtable #forms').buttonset();
	}
}

$(document).ready(function(){
	$( '.checkstatus' ).button();
	$( '.btnset' ).buttonset();
		// Fix for http://bugs.jqueryui.com/ticket/7856
		$('[type=radio]').change(function() {
			$(this).parent().buttonset("destroy");
			$(this).parent().buttonset();
		});


	$( document ).tooltip({ track: true	});
	
	$( ".accordion" ).accordion({
		collapsible: true,
		heightStyle: 'content'
	});
	
	// Кнопка редактирования этапов
//	$('.edit_steps').mouseup( function()
//	{
//		var id = $(this).attr("id");
//		var location = $(this).attr("location");
//		makeform(id, location);
//		return false;
//	});
	$('.edit_steps').click( function()
	{
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
		return false;
	});

	// Форма добавления стульев
	$('.edit_product1').click(function() {

		// Активация формы если была неактивна
		$('#addchair fieldset').prop('disabled', false);
		$('#addchair input[name=free]').val(0);

		id = $(this).attr('id');
		if( typeof id !== "undefined" ) {
			id = id.replace('prod', '');
		}
		var free = $(this).attr('free');
		var location = $(this).attr("location");
		var odid = $(this).attr("odid");
		
		// Очистка диалога
		$('#addchair input, #addchair select').prop('disabled', false);
		$('#addchair input[type="text"], #addchair select').val('');
		$('#addchair textarea').val('');
		$('#addchair input[name="Amount"]').val('1');
		$('#addchair input[name="Price"]').val('');
		$('#1radio0').prop('checked', true);
		$('#addchair .radiostatus input[type="radio"]').prop('disabled', true);
		$('#addchair .radiostatus input[type="radio"]').button('refresh');
		$('#addchair input[name="Amount"]').removeAttr('max');
		// Очистка инпутов дат заказа ткани
		$('#addchair .order_material').hide('fast');
		$('#addchair .order_material input').attr("required", false);
		$('#addchair .order_material input').val('');
		$('#addchair .order_material input.from').datepicker( "option", "maxDate", null );
		$('#addchair .order_material input.to').datepicker( "option", "minDate", null );
		// Прячем картинки-треугольники
		$('#addchair img[id="Amount"]').hide();
		$('#addchair img[id="Model"]').hide();
		// Сворачиваем акордион, очищаем
		$('#addchair .accordion').accordion( "option", "active", false );
		$('#addchair .accordion div').html('');
		$('#addchair .accordion h3 span').html('0');
		$('#addchair .accordion').hide(); // Прячем акордион

		// Заполнение
		if( id > 0 )
		{
			$('#addchair input[name="Amount"]').val(odd[id]['amount']);
			$('#addchair input[name="Price"]').val(odd[id]['price']);
			$('#addchair select[name="Model"]').val(odd[id]['model']);
			$('#addchair textarea[name="Comment"]').val(odd[id]['comment']);
			$('#addchair input[name="Material"]').val(odd[id]['material']);
			$('#addchair select[name="Shipper"]').val(odd[id]['shipper']);
			$('#1radio'+odd[id]['isexist']).prop('checked', true);
			$('#addchair .radiostatus input[type="radio"]').button('refresh');
			if( odd[id]['isexist'] == 1 ) {
				$('#addchair .order_material').show('fast');
				$('#addchair .order_material input').attr("required", true);
				$('#addchair .order_material input.from').val( odd[id]['order_date'] );
				$('#addchair .order_material input.to' ).val( odd[id]['arrival_date'] );
			}
			
			// Если изделие в работе, то выводим предупреждения
			if( odd[id]['inprogress'] == 1 )
			{
				$('#addchair img[id="Amount"]').show();
				$('#addchair img[id="Model"]').show();
				$('#addchair input[name="Amount"]').attr('max', odd[id]['amount']);
			}

			materialonoff('#addchair');

			$("#addchair form").attr("action", "datasave.php?oddid="+id+"&location="+location);
		}
		else // Иначе добавляем новый стул
		{
			$('#addchair form').attr('action', 'orderdetail.php?id='+odid+'&add=1');
			if( id != 0 ) {
				$('#addchair .accordion').show('fast'); // Показываем акордион
			}
		}

		$('#addchair select[name="Model"]').change( function() { livesearch(this); });

		// Если нет ткани, то кнопка наличия не активна
		$('#addchair input[name="Material"]').change( function() {
			materialonoff('#addchair');
		});
		$('#addchair input[name="Material"]').on( "autocompleteselect", function() {
			materialonoff('#addchair');
		});
		// Костыль для активации кнопок наличия материала при вставке из буфера
//		$('#addchair input[name="Material"]').bind('paste', function(e) {
//			var pastedData = e.originalEvent.clipboardData.getData('text');
//			$(this).val(' ');
//			materialonoff('#addchair');
//			$(this).val('');
//		});
		
		// Форма добавления/редактирования стульев
		$('#addchair').dialog({
			width: 600,
			modal: true,
			show: 'blind',
			hide: 'explode',
		});

		// Автокомплит поверх диалога
		$( ".colortags" ).autocomplete( "option", "appendTo", "#addchair" );
		$( ".textiletags" ).autocomplete( "option", "appendTo", "#addchair" );
		
		return false;
	});

	// Форма добавления столов
	$('.edit_product2').click(function() {

		// Активация формы если была неактивна
		$('#addtable fieldset').prop('disabled', false);
		$('#addtable .btnset').buttonset( 'option', 'disabled', false );
		$('#addtable input[name=free]').val(0);

		id = $(this).attr('id');
		if( typeof id !== "undefined" ) {
			id = id.replace('prod', '');
		}
		var free = $(this).attr('free');
		var location = $(this).attr("location");
		var odid = $(this).attr("odid");

		// Очистка диалога
		$('#addtable input, #addtable select').prop('disabled', false);
		$('#addtable input[type="text"], #addtable select').val('');
		$('#addtable textarea').val('');
		$('#addtable input[name="Amount"]').val('1');
		$('#addtable input[name="Price"]').val('');
		$('#addtable input[name="Length"]').val(''); //было 1300
		$('#addtable input[name="Width"]').val(''); //было 800
		$('#2radio2').prop('checked', true);
		$('#addtable .radiostatus').buttonset( 'option', 'disabled', true );
		$('#addtable .radiostatus input[type="radio"]').button('refresh');
		$('#addtable input[name="Form"]:nth-child(1)').prop('checked', true);
		$('#addtable input[name="Mechanism"]:nth-child(1)').prop('checked', true);
		$('#addtable input[type="radio"]').button("refresh");
		$('#addtable input[name="Amount"]').removeAttr('max');
		// Очистка инпутов дат заказа пластика
		$('#addtable .order_material').hide('fast');
		$('#addtable .order_material input').attr("required", false);
		$('#addtable .order_material input').val('');
		$('#addtable .order_material input.from').datepicker( "option", "maxDate", null );
		$('#addtable .order_material input.to').datepicker( "option", "minDate", null );
		// Прячем картинки-треугольники
		$('#addtable img[id="Amount"]').hide();
		$('#addtable img[id="Model"]').hide();
		$('#addtable img[id="Mechanism"]').hide();
		$('#addtable img[id="Length"]').hide();
		// Сворачиваем акордион, очищаем
		$('#addtable .accordion').accordion( "option", "active", false );
		$('#addtable .accordion div').html('');
		$('#addtable .accordion h3 span').html('0');
		$('#addtable .accordion').hide(); // Прячем акордион
		//FormModelList(0, 0);

		// Заполнение
		if( id > 0 )
		{
			var model = odd[id]['model'];
			var form = odd[id]['form'];
			// Если известна модель, то выводим соответствующий список форм
//			if( odd[id]['model'] ) {
//				FormModelList(odd[id]['model'], odd[id]['form']);
//			}
			$('#addtable input[name="Amount"]').val(odd[id]['amount']);
			$('#addtable input[name="Price"]').val(odd[id]['price']);
			$('#addtable select[name="Model"]').val(odd[id]['model']);
			//$('#form'+odd[id]['form']).prop('checked', true);
			//$('#addtable input[name="Form"]').button("refresh");
			$('#mechanism'+odd[id]['mechanism']).prop('checked', true);
			$('#addtable input[name="Mechanism"]').button("refresh");
			$('#addtable input[name="Length"]').val(odd[id]['length']);
			$('#addtable input[name="Width"]').val(odd[id]['width']);
			$('#addtable input[name="PieceAmount"]').val(odd[id]['PieceAmount']);
			$('#addtable input[name="PieceSize"]').val(odd[id]['PieceSize']);
			$('#addtable textarea[name="Comment"]').val(odd[id]['comment']);
			$('#addtable input[name="Material"]').val(odd[id]['material']);
			$('#addtable select[name="Shipper"]').val(odd[id]['shipper']);
			$('#2radio'+odd[id]['isexist']).prop('checked', true);
			$('#addtable .radiostatus input[type="radio"]').button('refresh');
			if( odd[id]['isexist'] == 1 ) {
				$('#addtable .order_material').show('fast');
				$('#addtable .order_material input').attr("required", true);
				$('#addtable .order_material input.from').val( odd[id]['order_date'] );
				$('#addtable .order_material input.to' ).val( odd[id]['arrival_date'] );
			}

			// Если изделие в работе, то выводятся предупреждения
			if( odd[id]['inprogress'] == 1 )
			{
				$('#addtable img[id="Amount"]').show();
				$('#addtable img[id="Model"]').show();
				$('#addtable img[id="Mechanism"]').show();
				$('#addtable img[id="Length"]').show();
				$('#addtable input[name="Amount"]').attr('max', odd[id]['amount']);
			}

			materialonoff('#addtable');

			$("#addtable form").attr("action", "datasave.php?oddid="+id+"&location="+location);
		}
		else // Иначе добавляем новый стол
		{
			var model = 0;
			var form = 0;
			$("#addtable form").attr("action", "orderdetail.php?id="+odid+"&add=1");
			if( id != 0 ) {
				$('#addtable .accordion').show('fast'); // Показываем акордион
			}
		}
		
		FormModelList(model, form);

		$('#addtable input[name="Form"]').change( function() {
			form = $(this).val();
			//console.log(form);
		});

		// Список форм столешниц в зависимости от модели
		$('#addtable select[name="Model"]').change( function() {
			//console.log(form);
			if( $(this).val() == "" ) {
				FormModelList(0, form);
			}
			else {

				FormModelList($(this).val(), form);
			}

			$('#addtable input[name="Form"]').change( function() {
				form = $(this).val();
				//console.log(form);
			});

			livesearch(this);
		});
		
		// Если нет пластика, то кнопка наличия не активна
		$('#addtable input[name="Material"]').change( function() {
			materialonoff('#addtable');
		});
		$('#addtable input[name="Material"]').on( "autocompleteselect", function() {
			materialonoff('#addtable');
		});
		// Костыль для активации кнопок наличия материала при вставке из буфера
//		$('#addtable input[name="Material"]').bind('paste', function(e) {
//			var pastedData = e.originalEvent.clipboardData.getData('text');
//			$(this).val(' ');
//			materialonoff('#addtable');
//			$(this).val('');
//		});

		$("#addtable").dialog(
		{
			width: 850,
			modal: true,
			show: 'blind',
			hide: 'explode',
		});
		
		// Автокомплит поверх диалога
		$( ".colortags" ).autocomplete( "option", "appendTo", "#addtable" );
		$( ".plastictags" ).autocomplete( "option", "appendTo", "#addtable" );
		
		return false;
	});

	// Форма добавления заготовок
	$('.edit_order_blank').click(function() {
		id = $(this).attr('id');
		if( typeof id !== "undefined" ) {
			id = id.replace('blank', '');
		}
		var location = $(this).attr("location");
		var odid = $(this).attr("odid");

		// Очистка диалога
		$('#addblank textarea, #addblank select, #addblank input[type="text"]').val('');
		$('#addblank input[name="Amount"]').val('1');
		$('#addblank input[name="Price"]').val('');
		$('#0radio0').prop('checked', true);
		$('#addblank .radiostatus input[type="radio"]').prop('disabled', true);
		$('#addblank .radiostatus input[type="radio"]').button('refresh');
		$('#addblank input[name="Other"]').prop('disabled', false);
		$('#addblank input[name="Other"]').prop("required", true);
		$('#addblank select[name="Blanks"]').prop('disabled', false);
		$('#addblank select[name="Blanks"]').prop('required', true);
		// Очистка инпутов дат заказа пластика
		$('#addblank .order_material').hide('fast');
		$('#addblank .order_material input').attr("required", false);
		$('#addblank .order_material input').val('');
		$('#addblank .order_material input.from').datepicker( "option", "maxDate", null );
		$('#addblank .order_material input.to').datepicker( "option", "minDate", null );

		// Заполнение
		if( id > 0 )
		{
			$('#addblank input[name="Amount"]').val(odb[id]['amount']);
			$('#addblank input[name="Price"]').val(odb[id]['price']);
			if( odb[id]['blank'] > 0 ) {
				$('#addblank select[name="Blanks"]').val(odb[id]['blank']);
				$('#addblank input[name="Other"]').prop('disabled', true);
				$('#addblank input[name="Other"]').prop("required", false);
			}
			else {
				$('#addblank input[name="Other"]').val(odb[id]['other']);
				$('#addblank select[name="Blanks"]').prop('disabled', true);
				$('#addblank select[name="Blanks"]').prop('required', false);
			}
			$('#addblank textarea[name="Comment"]').val(odb[id]['comment']);

			$('#addblank input[name="Material"]').val(odb[id]['material']);
			$('#addblank select[name="Shipper"]').val(odb[id]['shipper']);
			$('#0radio'+odb[id]['isexist']).prop('checked', true);
			$('#addblank .radiostatus input[type="radio"]').button('refresh');
			if( odb[id]['isexist'] == 1 ) {
				$('#addblank .order_material').show('fast');
				$('#addblank .order_material input').attr("required", true);
				$('#addblank .order_material input.from').val( odb[id]['order_date'] );
				$('#addblank .order_material input.to' ).val( odb[id]['arrival_date'] );
			}

			materialonoff('#addblank');
			$("#addblank form").attr("action", "datasave.php?odbid="+id+"&location="+location);
		}
		else // Иначе добавляем новую заготовку
		{
			$('#addblank form').attr('action', 'orderdetail.php?id='+odid+'&addblank=1');
		}

		$('#addblank select[name="Blanks"]').change( function() {
			if( !(id > 0) ) {
				val = $(this).val();
				if( val != '' ) {
					$('#addblank input[name="Other"]').prop('disabled', true);
					$('#addblank input[name="Other"]').prop("required", false);
				}
				else {
					$('#addblank input[name="Other"]').prop('disabled', false);
					$('#addblank input[name="Other"]').prop("required", true);
				}
			}
		});

		$('#addblank input[name="Other"]').change( function() {
			if( !(id > 0) ) {
				val = $(this).val();
				if( val != '' ) {
					$('#addblank select[name="Blanks"]').prop('disabled', true);
					$('#addblank select[name="Blanks"]').prop('required', false);
				}
				else {
					$('#addblank select[name="Blanks"]').prop('disabled', false);
					$('#addblank select[name="Blanks"]').prop('required', true);
				}
			}
		});

		// Если нет материала, то кнопка наличия не активна
		$('#addblank input[name="Material"]').change( function() {
			materialonoff('#addblank');
		});
		$('#addblank input[name="Material"]').on( "autocompleteselect", function() {
			materialonoff('#addblank');
		});

		$("#addblank").dialog(
		{
			width: 500,
			modal: true,
			show: 'blind',
			hide: 'explode',
		});

		// Автокомплит поверх диалога
		$( ".textileplastictags" ).autocomplete( "option", "appendTo", "#addblank" );

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
	
	// Живой поиск в "Свободных" при вводе параметров в форму
//	$('select[name="Model"]').change( function() { livesearch(this); });
//	$('input[name="Length"], input[name="Width"]').change( function() { livesearch(this); });
	$('#forms, #mechanisms').on("change", function(){ livesearch(this); });
	$('input[name="Material"]').keyup( function() { livesearch(this); });
	$('input[name="Material"]').on( 'autocompleteselect', function( event, ui ) { $(this).val( ui.item.value ); livesearch(this); } );

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
});
