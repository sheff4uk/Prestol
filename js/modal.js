$(function() {
	var overlay = $('#overlay');
	var open_modal = $('.open_modal');
	var close = $('.modal_close, #overlay');
	var modal = $('.modal_div, #print_tbl');
	var col = $('#print_tbl thead input[type="checkbox"]:not(#ship_X)');

	open_modal.click( function(event){
		event.preventDefault();
		var div = $(this).attr('href');
		col.prop( "disabled", false );
//		$('#print_tbl thead input[type="checkbox"][value="6"]').prop( "disabled", true );
		$('#print_tbl thead input[type="checkbox"][value="11"]').prop( "disabled", true );
		$('#print_tbl thead input[type="checkbox"][value="12"]').prop( "disabled", true );
		$('.print_col').button();
		$('#print_btn > a').css('display', 'block');
		$('#copy_link').css('display', 'block');
		$('#print_forms').css('display', 'block');
		$('#print_title').css('display', 'block');
		$('#print_products').css('display', 'block');
		$('#print_labelsbox').css('display', 'block');
		$(div)
			.css('z-index', '11')
			.css('position', 'absolute');
//		$('.wr_main_table_body').css('height', 'calc(100% - 50px)');
		$('.wr_main_table_body').css('margin-right', '0');
		$('.wr_main_table_head').css('width', 'calc(100% - 15px)');
		overlay.fadeIn(400);

		$('#print_tbl td:last-child, #print_tbl th:last-child').css('display', 'none');
		col.each(function() {
			if (!$(this).prop('checked')) {
				$('#print_tbl td:nth-child('+$(this).val()+')').css('background', 'rgba(0,0,0,.5)');
				$('#print_tbl td:nth-child('+$(this).val()+')').css('opacity', '.5');
			}
		});
	});

	close.click( function(){
			overlay.fadeOut(400,
			function() {
				$('#print_btn > a').css('display', 'none');
				$('#copy_link').css('display', 'none');
				$('#print_forms').css('display', 'none');
				$('#print_title').css('display', 'none');
				$('#print_products').css('display', 'none');
				$('#print_labelsbox').css('display', 'none');
				modal.css('z-index', '').css('position', '');
//				$('.wr_main_table_body').css('height', '');
				$('.wr_main_table_body').css('margin-right', '');
				$('.wr_main_table_head').css('width', '');
				$('.print_col').button('destroy');
			}
		);
		$('#print_tbl td').css('background', '');
		$('#print_tbl td').css('opacity', '');
		$('#print_tbl tr').css('opacity', '');
		$('#print_tbl td:last-child, #print_tbl th:last-child').css('display', '');
		col.prop( "disabled", true );
		$("#ship_X").prop( "disabled", false );
	});
	
	col.change(function() {
		if ($(this).prop('checked')) {
			$('#print_tbl td:nth-child('+$(this).val()+')').css('background', '');
			$('#print_tbl td:nth-child('+$(this).val()+')').css('opacity', '');
		}
		else {
			$('#print_tbl td:nth-child('+$(this).val()+')').css('background', 'rgba(0,0,0,.5)');
			$('#print_tbl td:nth-child('+$(this).val()+')').css('opacity', '.5');
		}
	});
});
