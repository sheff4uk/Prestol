$.fx.speeds._default = 300;
var odid;

// Функция генерирует форму с этапами производства
function makeform(id, location)
{
	$.ajax({ url: "ajax.php?do=steps&odd_id="+id, dataType: "script", async: false });
	$( '.isready' ).button();
	$("#steps form").attr("action", "datasave.php?location="+location);
	
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
				 + "&color="+$(element).parents('form').find('input[name="Color"]').val()
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
function FormModelList(model) {
	var forms = "";
	var arr = ModelForm[model];
	if( typeof arr !== "undefined" ) {
		$.each(arr, function(key, val){
			forms += "<input type='radio' id='form" + key + "' name='Form' value='" + key + "'>";
			forms += "<label for='form" + key + "'>" + val + "</label>";
		});
	}
	$('#addtable #forms').html(forms);
	if( forms != "" ) {
		$('#addtable input[name="Form"]:nth-child(1)').prop('checked', true);
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
		active: false,
		heightStyle: 'content'
	});
	
	// Кнопка редактирования этапов
	$('.edit_steps').mouseup( function()
	{
		var id = $(this).attr("id");
		var location = $(this).attr("location");
		makeform(id, location);
		return false;
	});
	$('.edit_steps').click( function()
	{
		var id = $(this).attr("id");
		var location = $(this).attr("location");
		makeform(id, location);
		return false;
	});

	// Форма добавления стульев
	$('.edit_product1').click(function() {

		// Активация формы если была неактивна
		$('#addchair fieldset').prop('disabled', false);
		$('#addchair input[name=free]').val(0);

		var id = $(this).attr('id');
		var free = $(this).attr('free');
		var location = $(this).attr("location");
		
		// Очистка диалога
		$('#addchair input, #addchair select').prop('disabled', false);
		$('#addchair input[type="text"]').val('');
		$('#addchair textarea').val('');
		$('#addchair input[name="Amount"]').val('1');
		$('#addchair select[name="Model"]').val('');
		$('#1radio0').prop('checked', true);
		$('#addchair .radiostatus input[type="radio"]').prop('disabled', true);
		$('#addchair .radiostatus input[type="radio"]').button('refresh');
		$('#addchair input[name="Amount"]').removeAttr('max');
		$('#addchair input[id="Model"]').val('');
		$('#addchair input[id="Model"]').removeAttr('name');
		// В свободных показываем цвет
		if( free == 1 ) {
			$('#addchair input[name="Color"]').parent('div').show('fast');
		}
		else {
			$('#addchair input[name="Color"]').parent('div').hide('fast');
		}
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
			$('#addchair select[name="Model"]').val(odd[id]['model']);
			$('#addchair input[name="Color"]').val(odd[id]['color']);
			$('#addchair textarea[name="Comment"]').val(odd[id]['comment']);
			$('#addchair input[name="Material"]').val(odd[id]['material']);
			$('#1radio'+odd[id]['isexist']).prop('checked', true);
			$('#addchair .radiostatus input[type="radio"]').button('refresh');
			if( odd[id]['isexist'] == 1 ) {
				$('#addchair .order_material').show('fast');
				$('#addchair .order_material input').attr("required", true);
				$('#addchair .order_material input.from').val( odd[id]['order_date'] );
				$('#addchair .order_material input.to' ).val( odd[id]['arrival_date'] );
			}
			
			// Если изделие в работе, то нельзя изменить модель и увеличить кол-во
			if( odd[id]['inprogress'] == 1 )
			{
				$('#addchair img[id="Amount"]').show();
				$('#addchair img[id="Model"]').show();
				$('#addchair input[name="Amount"]').attr('max', odd[id]['amount']);
				$('#addchair select[name="Model"]').prop('disabled', true);
				$('#addchair input[id="Model"]').attr('name', 'Model');
				$('#addchair input[id="Model"]').val(odd[id]['model']);
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
		$('#addchair input[name="Material"]').keyup( function() {
			materialonoff('#addchair');
		});
		
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
		$('#addtable #forms, #addchair #mechanisms' ).buttonset( 'option', 'disabled', false );
		$('#addtable input[name=free]').val(0);

		var id = $(this).attr('id');
		var free = $(this).attr('free');
		var location = $(this).attr("location");

		// Очистка диалога
		$('#addtable input, #addtable select').prop('disabled', false);
		$('#addtable input[type="text"]').val('');
		$('#addtable textarea').val('');
		$('#addtable input[name="Amount"]').val('1');
		$('#addtable select[name="Model"]').val('');
		$('#addtable select[name="Form"]').val('');
		$('#addtable select[name="Mechanism"]').val('');
		$('#addtable input[name="Length"]').val('1300');
		$('#addtable input[name="Width"]').val('800');
		$('#2radio2').prop('checked', true);
		$('#addtable input[name="Form"]:nth-child(1)').prop('checked', true);
		$('#addtable input[name="Form"]').button('refresh');
		$('#addtable input[name="Mechanism"]:nth-child(1)').prop('checked', true);
		$('#addtable input[name="Mechanism"]').button('refresh');
		$('#addtable .radiostatus input[type="radio"]').prop('disabled', true);
		$('#addtable .radiostatus input[type="radio"]').button('refresh');
		$('#addtable input[name="Amount"]').removeAttr('max');
		// В свободных показываем цвет
		if( free == 1 ) {
			$('#addtable input[name="Color"]').parent('div').show('fast');
			//$('#addtable select[name="Model"]').attr('required', 'false');
		}
		else {
			$('#addtable input[name="Color"]').parent('div').hide('fast');
			$('#addtable select[name="Model"]').attr('required', 'true');
		}
		// Очистка инпутов дат заказа пластика
		$('#addtable .order_material').hide('fast');
		$('#addtable .order_material input').attr("required", false);
		$('#addtable .order_material input').val('');
		$('#addtable .order_material input.from').datepicker( "option", "maxDate", null );
		$('#addtable .order_material input.to').datepicker( "option", "minDate", null );
		// Прячем картинки-треугольники
		$('#addtable img[id="Amount"]').hide();
		// Сворачиваем акордион, очищаем
		$('#addtable .accordion').accordion( "option", "active", false );
		$('#addtable .accordion div').html('');
		$('#addtable .accordion h3 span').html('0');
		$('#addtable .accordion').hide(); // Прячем акордион

		// Заполнение
		if( id > 0 )
		{
			// Если известна модель, то выводим соответствующий список форм
			if( odd[id]['model'] ) {
				FormModelList(odd[id]['model']);
			}
			$('#addtable input[name="Amount"]').val(odd[id]['amount']);
			$('#addtable select[name="Model"]').val(odd[id]['model']);
			$('#form'+odd[id]['form']).prop('checked', true);
			$('#addtable input[name="Form"]').button("refresh");
			$('#mechanism'+odd[id]['mechanism']).prop('checked', true);
			$('#addtable input[name="Mechanism"]').button("refresh");
			$('#addtable input[name="Length"]').val(odd[id]['length']);
			$('#addtable input[name="Width"]').val(odd[id]['width']);

			$('#addtable input[name="Color"]').val(odd[id]['color']);
			$('#addtable textarea[name="Comment"]').val(odd[id]['comment']);
			$('#addtable input[name="Material"]').val(odd[id]['material']);
			$('#2radio'+odd[id]['isexist']).prop('checked', true);
			$('#addtable .radiostatus input[type="radio"]').button('refresh');
			if( odd[id]['isexist'] == 1 ) {
				$('#addtable .order_material').show('fast');
				$('#addtable .order_material input').attr("required", true);
				$('#addtable .order_material input.from').val( odd[id]['order_date'] );
				$('#addtable .order_material input.to' ).val( odd[id]['arrival_date'] );
			}

			// Если изделие в работе, то нельзя увеличить кол-во
			if( odd[id]['inprogress'] == 1 )
			{
				$('#addtable img[id="Amount"]').show();
				$('#addtable input[name="Amount"]').attr('max', odd[id]['amount']);
			}

			materialonoff('#addtable');

			$("#addtable form").attr("action", "datasave.php?oddid="+id+"&location="+location);
		}
		else // Иначе добавляем новый стол
		{
			$("#addtable form").attr("action", "orderdetail.php?id="+odid+"&add=1");
			if( id != 0 ) {
				$('#addtable .accordion').show('fast'); // Показываем акордион
			}
		}
		
		// Список форм столешниц в зависимости от модели
		$('#addtable select[name="Model"]').change( function() {
			if( $(this).val() == "" ) {
				FormModelList(0);
			}
			else {
				FormModelList($(this).val());
			}
			livesearch(this);
		});
		
		// Если нет пластика, то кнопка наличия не активна
		$('#addtable input[name="Material"]').keyup( function() {
			materialonoff('#addtable');
		});

		$("#addtable").dialog(
		{
			width: 800,
			modal: true,
			show: 'blind',
			hide: 'explode',
		});
		
		// Автокомплит поверх диалога
		$( ".colortags" ).autocomplete( "option", "appendTo", "#addtable" );
		$( ".plastictags" ).autocomplete( "option", "appendTo", "#addtable" );
		
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
	$('input[name="Color"], input[name="Material"]').keyup( function() { livesearch(this); });
	$('input[name="Color"], input[name="Material"]').on( 'autocompleteselect', function( event, ui ) { $(this).val( ui.item.value ); livesearch(this); } );

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
