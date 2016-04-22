$(document).ready(function() {
	var overlay = $('#overlay');
	var open_modal = $('.open_modal');
	var close = $('.modal_close, #overlay');
	var modal = $('.modal_div, #print_tbl');
	var col = $('#print_tbl thead input[type="checkbox"]');
	var row = $('.print_row');

	open_modal.click( function(event){
		event.preventDefault();
		var div = $(this).attr('href');
		col.prop( "disabled", false );
		$('.print_col').button();
		$('.print_row').button();
		$('#print_btn > a').css('display', 'block');
		$(div)
			.css('z-index', '11')
			.css('position', 'absolute');
		overlay.fadeIn(400);

		$('#print_tbl td:last-child, #print_tbl th:last-child').css('display', 'none');
		col.each(function() {
			if (!$(this).prop('checked')) {
				$('#print_tbl td:nth-child('+$(this).val()+')').css('background', 'rgba(0,0,0,.5)');
			}
		});
		row.each(function() {
			if (!$(this).prop('checked')) {
				$(this).parents('tr').css('opacity', '.5');
			}
		});
	});

	close.click( function(){
			overlay.fadeOut(400,
			function() {
			$('#print_btn > a').css('display', 'none');
				modal.css('z-index', '').css('position', '');
				$('.print_col').button('destroy');
				$('.print_row').button('destroy');
	 		}
		);
		$('#print_tbl td').css('background', '');
		$('#print_tbl tr').css('opacity', '');
		$('#print_tbl td:last-child, #print_tbl th:last-child').css('display', '');
		col.prop( "disabled", true );
	});
	
	col.change(function() {
		if ($(this).prop('checked')) {
			$('#print_tbl td:nth-child('+$(this).val()+')').css('background', '');
		}
		else {
			$('#print_tbl td:nth-child('+$(this).val()+')').css('background', 'rgba(0,0,0,.5)');
		}
	});

	row.change(function() {
		if ($(this).prop('checked')) {
			$(this).parents('tr').css('opacity', '');
		}
		else {
			$(this).parents('tr').css('opacity', '.5');
		}
	});
});